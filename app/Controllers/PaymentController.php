<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\CSRF;
use App\Core\Database;
use App\Core\Security;
use App\Core\View;
use App\Models\Appointment;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\RefundRequest;
use App\Models\SubscriptionPayment;
use App\Models\SubscriptionPlanCatalog;
use App\Models\SubscriptionWebhookEvent;
use App\Models\User;
use App\Models\UserSubscription;
use App\Services\NotificationService;
use App\Services\NewsletterSignup;
use App\Services\Payments\PaymentManager;
use App\Services\Payments\SubscriptionGatewayManager;
use App\Services\SubscriptionAccess;

class PaymentController
{
    public function index(): void
    {
        Auth::requireLogin();
        $role = Auth::type();
        $userId = Auth::id();

        $data = [
            'title' => 'Payments & Invoices',
            'role' => $role,
        ];

        if ($role === 'patient') {
            $appointmentModel = new Appointment();
            $appointments = $appointmentModel->forPatient($userId);
            $invoiceModel = new Invoice();
            $invoiceMap = [];
            foreach ($appointments as $appointment) {
                if (!empty($appointment['id'])) {
                    $invoice = $invoiceModel->findByAppointmentId((int) $appointment['id']);
                    if ($invoice) {
                        $invoiceMap[(int) $appointment['id']] = $invoice;
                    }
                }
            }

            $data['appointments'] = $appointments;
            $data['invoiceMap'] = $invoiceMap;
            $data['invoices'] = $invoiceModel->byUser($userId);
            $data['payments'] = (new Payment())->byPayer($userId);
            $data['refunds'] = (new RefundRequest())->byUser($userId);
            $data['refundPaymentIds'] = array_map(static fn(array $refund) => (int) $refund['payment_id'], $data['refunds']);
            $subscriptionAccess = new SubscriptionAccess();
            $subscriptionAccess->enforceExpiry($userId);
            $data['subscriptionPlans'] = $subscriptionAccess->plans();
            $subscriptionModel = new UserSubscription();
            $data['activeSubscription'] = $subscriptionModel->activeForUser($userId);
            $data['latestSubscription'] = $data['activeSubscription'] ?: $subscriptionModel->latestForUser($userId);
            $data['subscriptionPayments'] = (new SubscriptionPayment())->byUser($userId);
            $data['internationalGateway'] = config('services.international_gateway', 'verifone_2checkout');
            $data['settlementAccount'] = config('services.subscription_settlement_account', 'Connected Pakistani settlement account');
            $data['subscriptionPkrRate'] = config('services.subscription_usd_to_pkr_rate', 278.00);
            $data['paymentStats'] = [
                'paid_total' => (new Payment())->paidTotalByPayer($userId),
                'unpaid_invoices' => $invoiceModel->countUnpaidByUser($userId),
                'refund_requests' => count($data['refunds']),
            ];
        } elseif ($role === 'doctor') {
            $paymentModel = new Payment();
            $data['payments'] = $paymentModel->byDoctor($userId);
            $data['paymentStats'] = $paymentModel->statsForDoctor($userId);
        } elseif ($role === 'admin') {
            $paymentModel = new Payment();
            $refundModel = new RefundRequest();
            $data['payments'] = $paymentModel->all();
            $data['refunds'] = $refundModel->all();
            $data['paymentStats'] = $paymentModel->statsOverall();
            $data['refundPendingCount'] = $refundModel->countPending();
            $data['invoices'] = (new Invoice())->all();
        } else {
            $data['message'] = 'Payment operations are currently enabled for patients, doctors, and administrators.';
        }

        View::render('payments', $data);
    }

    public function createInvoice(): void
    {
        Auth::requireLogin(['patient']);
        if (!CSRF::validate($_POST['csrf_token'] ?? null)) {
            flash('error', 'Invalid security token.', 'danger');
            redirect('payments');
        }

        $appointmentId = Security::cleanInt($_POST['appointment_id'] ?? 0);
        $appointment = (new Appointment())->findForPayment($appointmentId, Auth::id());
        if (!$appointment) {
            flash('error', 'Appointment not found for invoice generation.', 'danger');
            redirect('payments');
        }

        if (($appointment['status'] ?? '') === 'cancelled') {
            flash('error', 'Cancelled appointments cannot be invoiced.', 'danger');
            redirect('payments');
        }

        $invoiceModel = new Invoice();
        if ($invoiceModel->findByAppointmentId($appointmentId)) {
            flash('error', 'An invoice already exists for this appointment.', 'danger');
            redirect('payments');
        }

        $amount = (float) ($appointment['consultation_fee'] ?? 0);
        if ($amount <= 0) {
            flash('error', 'This appointment does not have a valid consultation fee.', 'danger');
            redirect('payments');
        }

        $invoiceNumber = 'INV-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
        $invoiceId = $invoiceModel->create([
            'user_id' => Auth::id(),
            'appointment_id' => $appointmentId,
            'invoice_number' => $invoiceNumber,
            'amount' => $amount,
            'currency' => config('app.currency', 'PKR'),
            'status' => 'unpaid',
            'due_date' => $appointment['date'] ?: date('Y-m-d', strtotime('+7 days')),
            'notes' => 'Consultation invoice for Dr. ' . ($appointment['doctor_name'] ?? 'Doctor') . ' (' . ($appointment['specialization'] ?? 'General') . ')',
        ]);

        (new NotificationService())->sendToUser(Auth::id(), 'invoice_created_patient', [
            'invoice_number' => $invoiceNumber,
            'amount' => number_format($amount, 2),
            'doctor_name' => $appointment['doctor_name'] ?? 'Doctor',
        ], [
            'category' => 'payment',
            'type' => 'info',
            'action_url' => route_url('payments/invoice', ['id' => $invoiceId]),
        ]);

        flash('success', 'Invoice #' . $invoiceId . ' generated successfully.', 'success');
        redirect('payments');
    }

    public function checkout(): void
    {
        Auth::requireLogin(['patient']);
        if (!CSRF::validate($_POST['csrf_token'] ?? null)) {
            flash('error', 'Invalid security token.', 'danger');
            redirect('payments');
        }

        $invoiceId = Security::cleanInt($_POST['invoice_id'] ?? 0);
        $provider = Security::cleanString($_POST['provider'] ?? 'mock');
        $provider = in_array($provider, ['mock', 'stripe', 'paypal', 'jazzcash', 'easypaisa'], true) ? $provider : 'mock';

        $invoiceModel = new Invoice();
        $invoice = $invoiceModel->findByIdForUser($invoiceId, Auth::id());
        if (!$invoice) {
            flash('error', 'Invoice not found.', 'danger');
            redirect('payments');
        }
        if (($invoice['status'] ?? '') === 'paid') {
            flash('error', 'This invoice has already been paid.', 'danger');
            redirect('payments');
        }
        if (in_array(($invoice['status'] ?? ''), ['cancelled', 'refunded'], true)) {
            flash('error', 'This invoice is not available for payment.', 'danger');
            redirect('payments');
        }

        $appointment = !empty($invoice['appointment_id']) ? (new Appointment())->findForPayment((int) $invoice['appointment_id'], Auth::id()) : null;
        $payeeId = $appointment['doctor_user_id'] ?? null;
        $transactionReference = strtoupper('TXN-' . substr(bin2hex(random_bytes(6)), 0, 12));

        $db = Database::connection();
        $db->beginTransaction();

        try {
            $paymentModel = new Payment();
            $paymentId = $paymentModel->create([
                'invoice_id' => $invoiceId,
                'appointment_id' => $invoice['appointment_id'] ?? null,
                'payer_id' => Auth::id(),
                'payee_id' => $payeeId,
                'provider' => $provider,
                'transaction_reference' => $transactionReference,
                'amount' => $invoice['amount'],
                'currency' => $invoice['currency'],
                'status' => 'initiated',
            ]);

            $result = (new PaymentManager())->process($provider, $invoice, ['id' => $paymentId], ['email' => Auth::user()['email'] ?? '']);
            $status = $result['status'] ?? 'pending';

            $paymentModel->updateAfterGateway($paymentId, [
                'provider_payment_id' => $result['provider_payment_id'] ?? null,
                'status' => $status,
                'gateway_response' => $result['gateway_response'] ?? null,
                'paid_at' => $result['paid_at'] ?? null,
            ]);

            if ($status === 'paid') {
                $invoiceModel->markPaid($invoiceId);
            }

            $db->commit();

            $notificationService = new NotificationService();
            if ($status === 'paid') {
                $notificationService->sendToUser(Auth::id(), 'payment_paid_patient', [
                    'transaction_reference' => $transactionReference,
                    'amount' => number_format((float) $invoice['amount'], 2),
                ], [
                    'category' => 'payment',
                    'type' => 'success',
                    'action_url' => route_url('payments'),
                ]);

                if ($payeeId) {
                    $notificationService->sendToUser((int) $payeeId, 'payment_paid_doctor', [
                        'appointment_id' => (int) ($invoice['appointment_id'] ?? 0),
                        'amount' => number_format((float) $invoice['amount'], 2),
                        'patient_name' => $appointment['patient_name'] ?? 'Patient',
                    ], [
                        'category' => 'payment',
                        'type' => 'success',
                        'action_url' => route_url('payments'),
                    ]);
                }

                flash('success', $result['message'] ?? 'Payment completed successfully.', 'success');
            } else {
                $notificationService->sendToUser(Auth::id(), 'payment_pending_patient', [
                    'transaction_reference' => $transactionReference,
                    'amount' => number_format((float) $invoice['amount'], 2),
                ], [
                    'category' => 'payment',
                    'type' => 'info',
                    'action_url' => route_url('payments'),
                ]);
                flash('success', $result['message'] ?? 'Payment request created. Complete the provider flow to finalize payment.', 'info');
            }
        } catch (\Throwable $exception) {
            $db->rollBack();
            flash('error', 'Payment processing failed. ' . (config('app.debug') ? $exception->getMessage() : 'Please try again.'), 'danger');
        }

        redirect('payments');
    }

    public function subscriptions(): void
    {
        $subscriptionAccess = new SubscriptionAccess();
        $returnTo = $this->safeReturnTo(trim((string) ($_GET['return_to'] ?? route_url(Auth::check() ? 'dashboard' : 'home'))));
        $newsletterPending = (new NewsletterSignup())->pending();
        $newsletterIntent = ($_GET['source'] ?? '') === 'newsletter' || $newsletterPending !== null;

        $newsletterDigest = (new NewsletterSignup())->digestForLevel(Auth::check() && Auth::type() === 'patient' ? $subscriptionAccess->accessLevel(Auth::id()) : 'free');

        View::render('subscription_pricing', [
            'title' => 'Subscription Plans',
            'plans' => $subscriptionAccess->plans(),
            'returnTo' => $returnTo,
            'newsletterIntent' => $newsletterIntent,
            'newsletterPending' => $newsletterPending,
            'newsletterDigest' => $newsletterDigest,
            'isAuthenticated' => Auth::check(),
            'isPatient' => Auth::type() === 'patient',
            'currentSubscription' => Auth::check() && Auth::type() === 'patient' ? $subscriptionAccess->currentSubscription(Auth::id()) : null,
            'currentAccessLevel' => Auth::check() && Auth::type() === 'patient' ? $subscriptionAccess->accessLevel(Auth::id()) : 'free',
            'internationalGateway' => config('services.international_gateway', 'verifone_2checkout'),
            'settlementAccount' => config('services.subscription_settlement_account', 'Connected Pakistani settlement account'),
            'metaDescription' => 'Choose Free, Lite, Pro, or Advanced MediSphere subscription access.',
            'breadcrumbs' => [
                ['label' => 'Home', 'url' => route_url('home')],
                ['label' => 'Subscription Plans', 'url' => null],
            ],
        ]);
    }

    public function activateFreeSubscription(): void
    {
        Auth::requireLogin(['patient']);
        if (!CSRF::validate($_POST['csrf_token'] ?? null)) {
            flash('error', 'Invalid security token.', 'danger');
            redirect('payments/subscriptions');
        }

        $planCatalog = new SubscriptionPlanCatalog();
        $billingCycle = $this->billingCycle(Security::cleanString($_POST['billing_cycle'] ?? 'monthly'));
        $plan = $planCatalog->findForBillingCycle('free-access', $billingCycle);
        if (!$plan) {
            flash('error', 'Free subscription plan is not configured.', 'danger');
            redirect('payments/subscriptions');
        }

        $source = $this->subscriptionSource(Security::cleanString($_POST['source'] ?? 'subscription_page'));
        $reference = $this->activatePlanWithoutPayment(
            $plan,
            $this->safeReturnTo(trim((string) ($_POST['return_to'] ?? route_url('dashboard')))),
            array_merge(['source' => $source], $source === 'newsletter' ? (new NewsletterSignup())->paidMetadata() : [])
        );
        redirect('payments/subscription/success', ['ref' => $reference]);
    }

    public function subscriptionCheckout(): void
    {
        Auth::requireLogin(['patient']);
        if (!CSRF::validate($_POST['csrf_token'] ?? null)) {
            flash('error', 'Invalid security token.', 'danger');
            redirect('payments');
        }

        $planSlug = Security::cleanString($_POST['plan_slug'] ?? '');
        $planCatalog = new SubscriptionPlanCatalog();
        $billingCycle = $this->billingCycle(Security::cleanString($_POST['billing_cycle'] ?? 'monthly'));
        $plan = $planCatalog->findForBillingCycle($planSlug, $billingCycle);
        if (!$plan) {
            flash('error', 'Subscription package not found.', 'danger');
            redirect('payments');
        }

        $source = $this->subscriptionSource(Security::cleanString($_POST['source'] ?? 'subscription_page'));
        $newsletterMetadata = $source === 'newsletter' ? (new NewsletterSignup())->paidMetadata() : [];

        if (empty($plan['requires_payment'])) {
            $reference = $this->activatePlanWithoutPayment(
                $plan,
                $this->safeReturnTo(trim((string) ($_POST['return_to'] ?? route_url('dashboard')))),
                array_merge(['source' => $source], $newsletterMetadata)
            );
            redirect('payments/subscription/success', ['ref' => $reference]);
        }

        $provider = (string) config('services.international_gateway', 'verifone_2checkout');
        $provider = in_array($provider, ['verifone_2checkout', 'payoneer_checkout'], true) ? $provider : 'verifone_2checkout';
        $transactionReference = strtoupper('SUB-' . date('Ymd') . '-' . substr(bin2hex(random_bytes(6)), 0, 12));
        $userId = Auth::id();
        $returnTo = $this->safeReturnTo(trim((string) ($_POST['return_to'] ?? route_url('payments'))));
        $baseMetadata = array_merge([
            'plan' => $plan,
            'source' => $source,
            'return_to' => $returnTo,
            'amount_pkr' => (new SubscriptionAccess())->amountToPkr((float) $plan['price'], (string) $plan['currency']),
        ], $newsletterMetadata);
        $contactProfile = (new User())->contactProfile($userId) ?: [];
        $customer = [
            'id' => $userId,
            'email' => Auth::user()['email'] ?? '',
            'name' => $contactProfile['name'] ?? (Auth::user()['email'] ?? 'Customer'),
            'country' => 'US',
        ];

        $db = Database::connection();
        $subscriptionModel = new UserSubscription();
        $paymentModel = new SubscriptionPayment();
        $subscriptionId = 0;

        $db->beginTransaction();
        try {
            $subscriptionId = $subscriptionModel->createPending([
                'user_id' => $userId,
                'plan_slug' => $plan['slug'],
                'access_level' => $plan['access_level'] ?? 'premium',
                'provider' => $provider,
                'transaction_reference' => $transactionReference,
                'amount' => $plan['price'],
                'currency' => $plan['currency'],
                'metadata' => json_encode($baseMetadata),
            ]);

            $paymentModel->createPending([
                'subscription_id' => $subscriptionId,
                'user_id' => $userId,
                'provider' => $provider,
                'transaction_reference' => $transactionReference,
                'amount' => $plan['price'],
                'currency' => $plan['currency'],
            ]);

            (new User())->updateSubscriptionStatus($userId, 'pending', (string) $plan['slug']);

            $db->commit();
        } catch (\Throwable $exception) {
            $db->rollBack();
            flash('error', 'Subscription tables are not ready. Import the latest subscription migration, then try again.', 'danger');
            redirect('payments');
        }

        $subscription = [
            'id' => $subscriptionId,
            'transaction_reference' => $transactionReference,
            'amount' => $plan['price'],
            'currency' => $plan['currency'],
        ];

        $result = (new SubscriptionGatewayManager())->process($provider, $plan, $subscription, $customer);
        $gatewayResponse = $result['gateway_response'] ?? json_encode($result);
        $status = $result['status'] ?? 'pending';

        if (!empty($result['provider_checkout_id'])) {
            $subscriptionModel->updateGatewayStart($subscriptionId, [
                'provider_checkout_id' => $result['provider_checkout_id'],
                'metadata' => json_encode(array_merge($baseMetadata, [
                    'checkout' => json_decode((string) $gatewayResponse, true) ?: $gatewayResponse,
                ])),
            ]);
        }

        if (!$result['success'] || $status === 'failed') {
            $subscriptionModel->markStatus($subscriptionId, 'failed', $gatewayResponse);
            $paymentModel->markStatusByReference($transactionReference, 'failed', $gatewayResponse);
            (new User())->updateSubscriptionStatus($userId, 'free');
        }

        if ($result['success'] && $status !== 'failed') {
            (new NotificationService())->sendToUser($userId, 'subscription_pending_patient', [
                'plan_name' => $plan['name'],
                'transaction_reference' => $transactionReference,
                'amount' => number_format((float) $plan['price'], 2),
                'currency' => $plan['currency'],
            ], [
                'category' => 'payment',
                'type' => 'info',
                'action_url' => route_url('payments/subscription/success', ['ref' => $transactionReference]),
            ]);
        }

        View::render('subscription_checkout', [
            'title' => 'Secure Subscription Checkout',
            'plan' => $plan,
            'subscription' => (new UserSubscription())->findByReference($transactionReference),
            'result' => $result,
            'provider' => $provider,
            'returnTo' => $returnTo,
            'amountPkr' => (new SubscriptionAccess())->amountToPkr((float) $plan['price'], (string) $plan['currency']),
            'isReady' => !empty($result['success']) && !empty($result['redirect_url']),
            'breadcrumbs' => [
                ['label' => 'Home', 'url' => route_url('home')],
                ['label' => 'Subscription Plans', 'url' => route_url('payments/subscriptions')],
                ['label' => 'Checkout', 'url' => null],
            ],
        ]);
    }

    public function subscriptionCallback(): void
    {
        Auth::requireLogin(['patient']);
        $reference = Security::cleanString($_GET['ref'] ?? $_GET['REFNOEXT'] ?? '');
        redirect('payments/subscription/success', ['ref' => $reference]);
    }

    public function subscriptionCancel(): void
    {
        Auth::requireLogin(['patient']);
        flash('error', 'Subscription checkout was cancelled before payment confirmation.', 'warning');
        redirect('payments');
    }

    public function verifoneWebhook(): void
    {
        $this->handleSubscriptionWebhook('verifone_2checkout');
    }

    public function payoneerWebhook(): void
    {
        $this->handleSubscriptionWebhook('payoneer_checkout');
    }

    public function subscriptionSuccess(): void
    {
        Auth::requireLogin(['patient']);
        $reference = Security::cleanString($_GET['ref'] ?? '');
        $subscription = $reference !== '' ? (new UserSubscription())->findByReference($reference) : (new UserSubscription())->activeForUser(Auth::id());

        if (!$subscription || (int) $subscription['user_id'] !== (int) Auth::id()) {
            flash('error', 'Subscription receipt not found for your account.', 'danger');
            redirect('payments');
        }

        $metadata = $this->decodeMetadata($subscription['metadata'] ?? null);
        $catalogPlan = (new SubscriptionPlanCatalog())->find((string) $subscription['plan_slug']) ?: [];
        $plan = is_array($metadata['plan'] ?? null) ? array_merge($catalogPlan, $metadata['plan']) : $catalogPlan;
        $amountPkr = (float) ($metadata['amount_pkr'] ?? (new SubscriptionAccess())->amountToPkr((float) $subscription['amount'], (string) $subscription['currency']));
        $returnTo = $this->safeReturnTo((string) ($metadata['return_to'] ?? route_url('payments')));
        $isActive = ($subscription['status'] ?? '') === 'subscribed';
        $accessLevel = (new SubscriptionAccess())->normalizeLevel((string) ($subscription['access_level'] ?? ($plan['access_level'] ?? 'free')));
        $newsletterSignupStatus = $this->handleNewsletterForSubscription($subscription, $plan, $metadata, $isActive, $accessLevel);

        View::render('subscription_success', [
            'title' => ($subscription['status'] ?? '') === 'subscribed' ? 'Subscription Activated' : 'Subscription Confirmation',
            'subscription' => $subscription,
            'plan' => $plan,
            'accessLevel' => $accessLevel,
            'amountPkr' => $amountPkr,
            'returnTo' => $returnTo,
            'payment' => (new SubscriptionPayment())->findByReference((string) $subscription['transaction_reference']),
            'invoiceUrl' => route_url('payments/subscription/invoice', ['ref' => $subscription['transaction_reference']]),
            'isActive' => $isActive,
            'newsletterSignupStatus' => $newsletterSignupStatus,
            'metaDescription' => 'Subscription payment confirmation and premium access status.',
            'breadcrumbs' => [
                ['label' => 'Home', 'url' => route_url('home')],
                ['label' => 'Payments', 'url' => route_url('payments')],
                ['label' => 'Subscription confirmation', 'url' => null],
            ],
        ]);
    }

    public function subscriptionInvoice(): void
    {
        Auth::requireLogin(['patient', 'admin']);
        $reference = Security::cleanString($_GET['ref'] ?? '');
        $subscription = $reference !== '' ? (new UserSubscription())->findByReference($reference) : null;

        if (!$subscription) {
            flash('error', 'Subscription invoice not found.', 'danger');
            redirect('payments');
        }

        if (Auth::type() === 'patient' && (int) $subscription['user_id'] !== (int) Auth::id()) {
            flash('error', 'You do not have permission to view that subscription invoice.', 'danger');
            redirect('payments');
        }

        $metadata = $this->decodeMetadata($subscription['metadata'] ?? null);
        $catalogPlan = (new SubscriptionPlanCatalog())->find((string) $subscription['plan_slug']) ?: [];
        $plan = is_array($metadata['plan'] ?? null) ? array_merge($catalogPlan, $metadata['plan']) : $catalogPlan;
        $payment = (new SubscriptionPayment())->findByReference($reference);
        $amountPkr = (float) ($metadata['amount_pkr'] ?? (new SubscriptionAccess())->amountToPkr((float) $subscription['amount'], (string) $subscription['currency']));

        View::render('subscription_invoice', [
            'title' => 'Subscription Invoice ' . $reference,
            'subscription' => $subscription,
            'payment' => $payment,
            'plan' => $plan,
            'amountPkr' => $amountPkr,
            'purchasedAt' => $payment['paid_at'] ?? $subscription['current_period_start'] ?? $subscription['created_at'],
            'expiresAt' => $subscription['current_period_end'] ?? null,
            'breadcrumbs' => [
                ['label' => 'Home', 'url' => route_url('home')],
                ['label' => 'Payments', 'url' => route_url('payments')],
                ['label' => 'Subscription Invoice', 'url' => null],
            ],
        ]);
    }

    public function invoice(): void
    {
        Auth::requireLogin();
        $invoiceId = Security::cleanInt($_GET['id'] ?? 0);
        $invoice = (new Invoice())->find($invoiceId);
        if (!$invoice) {
            flash('error', 'Invoice not found.', 'danger');
            redirect('payments');
        }

        $allowed = Auth::type() === 'admin'
            || (Auth::type() === 'patient' && (int) $invoice['user_id'] === (int) Auth::id())
            || (Auth::type() === 'doctor' && (int) ($invoice['doctor_user_id'] ?? 0) === (int) Auth::id());

        if (!$allowed) {
            flash('error', 'You do not have permission to view that invoice.', 'danger');
            redirect('payments');
        }

        $payments = [];
        if (Auth::type() === 'patient') {
            $payments = (new Payment())->byPayer((int) $invoice['user_id']);
            $payments = array_values(array_filter($payments, fn(array $payment) => (int) $payment['invoice_id'] === $invoiceId));
        }

        View::render('invoice', [
            'title' => 'Invoice ' . ($invoice['invoice_number'] ?? ''),
            'invoice' => $invoice,
            'payments' => $payments,
        ]);
    }

    public function requestRefund(): void
    {
        Auth::requireLogin(['patient']);
        if (!CSRF::validate($_POST['csrf_token'] ?? null)) {
            flash('error', 'Invalid security token.', 'danger');
            redirect('payments');
        }

        $paymentId = Security::cleanInt($_POST['payment_id'] ?? 0);
        $reason = Security::cleanString($_POST['reason'] ?? '');
        if (!$reason) {
            flash('error', 'Refund reason is required.', 'danger');
            redirect('payments');
        }

        $payment = (new Payment())->find($paymentId);
        if (!$payment || (int) $payment['payer_id'] !== (int) Auth::id()) {
            flash('error', 'Payment not found.', 'danger');
            redirect('payments');
        }
        if (($payment['status'] ?? '') !== 'paid') {
            flash('error', 'Only paid transactions can be refunded.', 'danger');
            redirect('payments');
        }

        $refundModel = new RefundRequest();
        if ($refundModel->findByPaymentAndUser($paymentId, Auth::id())) {
            flash('error', 'A refund request already exists for this payment.', 'danger');
            redirect('payments');
        }

        $refundModel->create([
            'payment_id' => $paymentId,
            'requested_by' => Auth::id(),
            'reason' => $reason,
            'status' => 'pending',
        ]);

        $userModel = new User();
        foreach ($userModel->all('admin') as $adminUser) {
            (new NotificationService())->sendToUser((int) $adminUser['id'], 'refund_requested_admin', [
                'transaction_reference' => $payment['transaction_reference'],
                'requester_name' => $userModel->contactProfile(Auth::id())['name'] ?? (Auth::user()['email'] ?? 'User'),
                'reason' => $reason,
            ], [
                'category' => 'payment',
                'type' => 'warning',
                'action_url' => route_url('payments'),
            ]);
        }

        flash('success', 'Refund request submitted successfully.', 'success');
        redirect('payments');
    }

    public function updateRefund(): void
    {
        Auth::requireLogin(['admin']);
        if (!CSRF::validate($_POST['csrf_token'] ?? null)) {
            flash('error', 'Invalid security token.', 'danger');
            redirect('payments');
        }

        $refundId = Security::cleanInt($_POST['refund_id'] ?? 0);
        $status = Security::cleanString($_POST['status'] ?? 'pending');
        if (!in_array($status, ['approved', 'rejected', 'processed'], true)) {
            flash('error', 'Invalid refund status.', 'danger');
            redirect('payments');
        }

        $refundModel = new RefundRequest();
        $refund = $refundModel->find($refundId);
        if (!$refund) {
            flash('error', 'Refund request not found.', 'danger');
            redirect('payments');
        }

        $db = Database::connection();
        $db->beginTransaction();
        $payment = (new Payment())->find((int) $refund['payment_id']);

        try {
            $refundModel->updateStatus($refundId, $status, Auth::id());

            if ($status === 'processed' && $payment) {
                $paymentModel = new Payment();
                $paymentModel->markRefunded((int) $payment['id'], json_encode(['message' => 'Refund processed by admin']));
                (new Invoice())->markRefunded((int) $payment['invoice_id']);
            }

            $db->commit();

            (new NotificationService())->sendToUser((int) $refund['requested_by'], 'refund_updated_patient', [
                'transaction_reference' => $payment['transaction_reference'] ?? 'transaction',
                'refund_status' => ucfirst($status),
            ], [
                'category' => 'payment',
                'type' => $status === 'rejected' ? 'warning' : 'success',
                'action_url' => route_url('payments'),
            ]);

            flash('success', 'Refund request updated.', 'success');
        } catch (\Throwable $exception) {
            $db->rollBack();
            flash('error', 'Refund update failed. ' . (config('app.debug') ? $exception->getMessage() : 'Please try again.'), 'danger');
        }

        redirect('payments');
    }

    private function handleSubscriptionWebhook(string $provider): void
    {
        $rawBody = file_get_contents('php://input') ?: '';
        $payload = $_POST;
        if (!$payload && $rawBody !== '') {
            $decoded = json_decode($rawBody, true);
            if (is_array($decoded)) {
                $payload = $decoded;
            } else {
                parse_str($rawBody, $payload);
            }
        }

        $gateway = (new SubscriptionGatewayManager())->gateway($provider);
        if (!$gateway->verifyWebhook($payload, $rawBody, $this->requestHeaders())) {
            http_response_code(400);
            echo 'Invalid webhook signature.';
            return;
        }

        $normalized = $gateway->normalizeWebhook($payload, $rawBody);
        $eventId = (string) ($normalized['event_id'] ?: sha1($rawBody ?: json_encode($payload)));
        $eventModel = new SubscriptionWebhookEvent();

        try {
            $storedEventId = $eventModel->recordIfNew([
                'provider' => $provider,
                'event_id' => $eventId,
                'event_type' => $normalized['event_type'] ?? 'subscription_payment',
                'payload_hash' => hash('sha256', $rawBody ?: json_encode($payload)),
                'payload' => json_encode($payload),
            ]);
        } catch (\Throwable $exception) {
            http_response_code(500);
            echo 'Webhook event storage failed.';
            return;
        }

        if ($storedEventId === null) {
            echo $gateway->acknowledgement($payload);
            return;
        }

        $reference = (string) ($normalized['reference'] ?? '');
        $subscriptionModel = new UserSubscription();
        $subscription = $reference !== '' ? $subscriptionModel->findByReference($reference) : null;

        if ($subscription && ($normalized['status'] ?? '') === 'approved') {
            $existingMetadata = $this->decodeMetadata($subscription['metadata'] ?? null);
            $catalogPlan = (new SubscriptionPlanCatalog())->find((string) $subscription['plan_slug']);
            $plan = is_array($existingMetadata['plan'] ?? null) ? array_merge($catalogPlan ?: [], $existingMetadata['plan']) : $catalogPlan;
            $periodEnd = (new SubscriptionPlanCatalog())->periodEnd($plan ?: ['period_days' => 30]);

            $db = Database::connection();
            $db->beginTransaction();
            try {
                $subscriptionModel->markSubscribed((int) $subscription['id'], [
                    'provider_checkout_id' => $normalized['provider_payment_id'] ?? null,
                    'provider_subscription_id' => $normalized['provider_subscription_id'] ?? null,
                    'current_period_start' => date('Y-m-d H:i:s'),
                    'current_period_end' => $periodEnd,
                    'metadata' => json_encode(array_merge($existingMetadata, ['webhook' => $payload])),
                ]);
                (new SubscriptionPayment())->markPaidByReference($reference, [
                    'provider_payment_id' => $normalized['provider_payment_id'] ?? null,
                    'gateway_response' => json_encode($payload),
                    'paid_at' => $normalized['paid_at'] ?? date('Y-m-d H:i:s'),
                ]);
                $accessLevel = (new SubscriptionAccess())->levelFromPlan((string) $subscription['plan_slug']);
                (new User())->updateSubscriptionStatus((int) $subscription['user_id'], $accessLevel, (string) $subscription['plan_slug'], $periodEnd);
                $db->commit();
                $this->handleNewsletterForSubscription(
                    array_merge($subscription, ['status' => 'subscribed', 'access_level' => $accessLevel]),
                    $plan ?: [],
                    $existingMetadata,
                    true,
                    $accessLevel
                );

                (new NotificationService())->sendToUser((int) $subscription['user_id'], 'subscription_active_patient', [
                    'plan_name' => $plan['name'] ?? $subscription['plan_slug'],
                    'transaction_reference' => $reference,
                    'amount' => number_format((float) ($subscription['amount'] ?? 0), 2),
                    'currency' => $subscription['currency'] ?? 'USD',
                    'expires_at' => $periodEnd,
                ], [
                    'category' => 'payment',
                    'type' => 'success',
                    'action_url' => route_url('payments'),
                ]);
            } catch (\Throwable $exception) {
                $db->rollBack();
                http_response_code(500);
                echo 'Subscription activation failed.';
                return;
            }
        } elseif ($subscription && ($normalized['status'] ?? '') === 'failed') {
            $subscriptionModel->markStatus((int) $subscription['id'], 'failed', json_encode($payload));
            (new SubscriptionPayment())->markStatusByReference($reference, 'failed', json_encode($payload));
            (new User())->updateSubscriptionStatus((int) $subscription['user_id'], 'free');
        }

        $eventModel->markProcessed($storedEventId);
        echo $gateway->acknowledgement($payload);
    }

    private function safeReturnTo(string $url): string
    {
        return (new SubscriptionAccess())->safeReturnUrl($url);
    }

    private function subscriptionSource(string $source): string
    {
        return in_array($source, ['newsletter', 'subscription_modal', 'subscription_page'], true) ? $source : 'subscription_page';
    }

    private function billingCycle(string $billingCycle): string
    {
        return $billingCycle === 'yearly' ? 'yearly' : 'monthly';
    }

    private function handleNewsletterForSubscription(array $subscription, array $plan, array $metadata, bool $isActive, string $accessLevel): ?string
    {
        $newsletter = $metadata['newsletter_signup'] ?? null;
        if (!is_array($newsletter) || empty($newsletter['email'])) {
            return null;
        }

        if (!$isActive) {
            return 'pending_payment';
        }

        $requiresPayment = array_key_exists('requires_payment', $plan)
            ? !empty($plan['requires_payment'])
            : (float) ($subscription['amount'] ?? 0) > 0;

        $email = (string) $newsletter['email'];
        if ($accessLevel === 'free' || !$requiresPayment) {
            $registered = (new NewsletterSignup())->registerSubscriber(
                $email,
                'free',
                (string) ($subscription['plan_slug'] ?? ($plan['slug'] ?? 'free-access')),
                (string) ($subscription['transaction_reference'] ?? ''),
                (string) ($newsletter['source'] ?? 'home_newsletter')
            );
            if ($registered) {
                (new NewsletterSignup())->consumeIfMatches($email);
            }
            return $registered ? 'free_registered' : 'free_registration_failed';
        }

        $registered = (new NewsletterSignup())->registerPaidSubscriber(
            $email,
            $accessLevel,
            (string) ($subscription['plan_slug'] ?? ($plan['slug'] ?? '')),
            (string) ($subscription['transaction_reference'] ?? ''),
            (string) ($newsletter['source'] ?? 'home_newsletter')
        );

        if ($registered) {
            (new NewsletterSignup())->consumeIfMatches($email);
        }

        return $registered ? 'paid_registered' : 'paid_registration_failed';
    }

    private function activatePlanWithoutPayment(array $plan, string $returnTo, array $metadata = []): string
    {
        $userId = Auth::id();
        $transactionReference = strtoupper('SUB-FREE-' . date('Ymd') . '-' . substr(bin2hex(random_bytes(5)), 0, 10));
        $startedAt = date('Y-m-d H:i:s');
        $periodEnd = (new SubscriptionPlanCatalog())->periodEnd($plan);
        $amountPkr = (new SubscriptionAccess())->amountToPkr((float) $plan['price'], (string) $plan['currency']);

        $db = Database::connection();
        $db->beginTransaction();

        try {
            $subscriptionId = (new UserSubscription())->createPending([
                'user_id' => $userId,
                'plan_slug' => $plan['slug'],
                'access_level' => $plan['access_level'] ?? 'free',
                'provider' => 'free',
                'transaction_reference' => $transactionReference,
                'status' => 'subscribed',
                'amount' => $plan['price'],
                'currency' => $plan['currency'],
                'current_period_start' => $startedAt,
                'current_period_end' => $periodEnd,
                'metadata' => json_encode(array_merge([
                    'plan' => $plan,
                    'source' => 'free_tier_activation',
                    'return_to' => $returnTo,
                    'amount_pkr' => $amountPkr,
                ], $metadata)),
            ]);

            (new SubscriptionPayment())->createPending([
                'subscription_id' => $subscriptionId,
                'user_id' => $userId,
                'provider' => 'free',
                'transaction_reference' => $transactionReference,
                'amount' => $plan['price'],
                'currency' => $plan['currency'],
                'status' => 'paid',
                'paid_at' => $startedAt,
                'gateway_response' => json_encode(['message' => 'Free standard plan activated without payment.']),
            ]);

            (new User())->updateSubscriptionStatus((int) $userId, (string) ($plan['access_level'] ?? 'free'), (string) $plan['slug'], $periodEnd);
            $db->commit();
        } catch (\Throwable $exception) {
            $db->rollBack();
            flash('error', 'Free plan activation failed. ' . (config('app.debug') ? $exception->getMessage() : 'Please try again.'), 'danger');
            redirect('payments/subscriptions');
        }

        (new NotificationService())->sendToUser((int) $userId, 'subscription_active_patient', [
            'plan_name' => $plan['name'],
            'transaction_reference' => $transactionReference,
            'amount' => number_format((float) $plan['price'], 2),
            'currency' => $plan['currency'],
            'expires_at' => $periodEnd,
        ], [
            'category' => 'payment',
            'type' => 'success',
            'action_url' => route_url('payments/subscription/success', ['ref' => $transactionReference]),
        ]);

        return $transactionReference;
    }

    private function decodeMetadata(?string $metadata): array
    {
        if (!$metadata) {
            return [];
        }

        $decoded = json_decode($metadata, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function requestHeaders(): array
    {
        if (function_exists('getallheaders')) {
            $headers = getallheaders() ?: [];
        } else {
            $headers = [];
            foreach ($_SERVER as $key => $value) {
                if (str_starts_with($key, 'HTTP_')) {
                    $name = strtolower(str_replace('_', '-', substr($key, 5)));
                    $headers[$name] = $value;
                }
            }
        }

        $normalized = [];
        foreach ($headers as $key => $value) {
            $normalized[strtolower((string) $key)] = $value;
        }
        return $normalized;
    }
}
