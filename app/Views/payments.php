<section class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
        <span class="eyebrow">Billing center</span>
        <h1 class="mb-0">Payments & Invoices</h1>
        <p class="text-muted mb-0 mt-2">Track consultation invoices, subscription receipts, payment status, and refund requests from one billing workspace.</p>
    </div>
    <?php if (($role ?? '') === 'patient'): ?>
        <div class="small text-muted">Subscriptions use USD hosted checkout through <?= e(str_replace('_', ' ', $internationalGateway ?? 'verifone_2checkout')) ?>.</div>
    <?php endif; ?>
</section>

<?php if (($role ?? '') === 'patient'): ?>
    <div class="row g-4 mb-4">
        <div class="col-md-4"><div class="stat-card gradient-card"><span>Total Paid</span><h2><?= e(config('app.currency', 'PKR')) ?> <?= number_format((float) ($paymentStats['paid_total'] ?? 0), 2) ?></h2></div></div>
        <div class="col-md-4"><div class="stat-card gradient-card"><span>Unpaid Invoices</span><h2><?= (int) ($paymentStats['unpaid_invoices'] ?? 0) ?></h2></div></div>
        <div class="col-md-4"><div class="stat-card gradient-card"><span>Refund Requests</span><h2><?= (int) ($paymentStats['refund_requests'] ?? 0) ?></h2></div></div>
    </div>

    <?php
        $gatewayLabel = ucwords(str_replace('_', ' ', $internationalGateway ?? 'verifone_2checkout'));
        $activePlanSlug = $activeSubscription['plan_slug'] ?? '';
        $currentSubscription = $activeSubscription ?? $latestSubscription ?? null;
        $currentStatus = $currentSubscription['status'] ?? 'free';
    ?>
    <div class="ux-stepper" data-stepper>
        <div class="ux-stepper-nav" role="tablist" aria-label="Billing sections">
            <button class="ux-stepper-tab is-active" data-stepper-tab data-stepper-target="payments-step-subscription">
                <span class="ux-stepper-number">1</span>
                <span class="ux-stepper-label"><strong>Subscription</strong><span>Plan and receipts</span></span>
            </button>
            <button class="ux-stepper-tab" data-stepper-tab data-stepper-target="payments-step-appointments">
                <span class="ux-stepper-number">2</span>
                <span class="ux-stepper-label"><strong>Appointments</strong><span>Ready for billing</span></span>
            </button>
            <button class="ux-stepper-tab" data-stepper-tab data-stepper-target="payments-step-invoices">
                <span class="ux-stepper-number">3</span>
                <span class="ux-stepper-label"><strong>Invoices</strong><span>Checkout actions</span></span>
            </button>
            <button class="ux-stepper-tab" data-stepper-tab data-stepper-target="payments-step-history">
                <span class="ux-stepper-number">4</span>
                <span class="ux-stepper-label"><strong>Payments</strong><span>Transaction history</span></span>
            </button>
            <button class="ux-stepper-tab" data-stepper-tab data-stepper-target="payments-step-refunds">
                <span class="ux-stepper-number">5</span>
                <span class="ux-stepper-label"><strong>Refunds</strong><span>Request tracking</span></span>
            </button>
        </div>
        <div class="ux-stepper-panels">
            <section class="ux-stepper-panel is-active" id="payments-step-subscription" data-stepper-panel>
    <section class="subscription-suite mb-4" data-aos="fade-up">
        <div class="subscription-suite-head">
            <div>
                <span class="eyebrow">Global subscription</span>
                <h2>Free, Lite, Pro, and Advanced access</h2>
                <p>Free activates standard access instantly. Lite, Pro, and Advanced use hosted checkout while settlement remains tied to your connected Pakistani payout account.</p>
            </div>
            <div class="subscription-status-panel">
                <span class="subscription-status-kicker">Account status</span>
                <strong><?= e(ucfirst((string) $currentStatus)) ?></strong>
                <?php if (!empty($currentSubscription['current_period_end'])): ?>
                    <small>Valid until <?= e($currentSubscription['current_period_end']) ?></small>
                <?php else: ?>
                    <small>Choose a package to unlock premium access</small>
                <?php endif; ?>
            </div>
        </div>

        <div class="subscription-trust-row">
            <span><i class="fa-solid fa-shield-halved"></i> Hosted secure checkout</span>
            <span><i class="fa-brands fa-cc-visa"></i> Visa</span>
            <span><i class="fa-brands fa-cc-mastercard"></i> Mastercard</span>
            <span><i class="fa-brands fa-cc-amex"></i> American Express</span>
            <span><i class="fa-solid fa-building-columns"></i> <?= e($settlementAccount ?? 'Connected Pakistani settlement account') ?></span>
        </div>

        <div class="subscription-suite-action">
            <div>
                <strong>Plans now live on the dedicated subscription page.</strong>
                <span>Compare Free, Lite, Pro, and Advanced packages in a focused checkout layout with newsletter tier details.</span>
            </div>
            <a href="<?= route_url('payments/subscriptions', ['return_to' => route_url('payments')]) ?>" class="btn btn-primary">
                <i class="fa-solid fa-arrow-up-right-from-square"></i>
                Open Subscription Page
            </a>
        </div>
    </section>

    <?php if (!empty($subscriptionPayments)): ?>
        <div class="glass-panel mb-4 subscription-receipts-panel" data-aos="fade-up">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0">Subscription Receipts</h4>
                <span class="small text-muted">International subscription audit trail</span>
            </div>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead><tr><th>Reference</th><th>Package</th><th>Provider</th><th>Amount</th><th>Status</th><th>Paid</th></tr></thead>
                    <tbody>
                    <?php foreach (($subscriptionPayments ?? []) as $subscriptionPayment): ?>
                        <tr>
                            <td>
                                <a href="<?= route_url('payments/subscription/invoice', ['ref' => $subscriptionPayment['transaction_reference']]) ?>" class="fw-semibold"><?= e($subscriptionPayment['transaction_reference']) ?></a>
                                <div class="small text-muted"><?= e($subscriptionPayment['created_at']) ?></div>
                            </td>
                            <td><?= e(ucwords(str_replace('-', ' ', $subscriptionPayment['plan_slug'] ?? 'subscription'))) ?></td>
                            <td class="text-uppercase"><?= e($subscriptionPayment['provider']) ?></td>
                            <td>
                                <?= e($subscriptionPayment['currency']) ?> <?= number_format((float) $subscriptionPayment['amount'], 2) ?>
                                <div class="small text-muted">Approx. <?= number_format((float) $subscriptionPayment['amount'] * (float) ($subscriptionPkrRate ?? 278), 0) ?> PKR</div>
                            </td>
                            <td><span class="badge text-bg-light text-capitalize"><?= e($subscriptionPayment['status']) ?></span></td>
                            <td><?= e($subscriptionPayment['paid_at'] ?? 'Pending') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

            </section>
            <section class="ux-stepper-panel" id="payments-step-appointments" data-stepper-panel>
    <div class="glass-panel mb-4" data-aos="fade-up">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="mb-0">Appointments ready for billing</h4>
            <span class="small text-muted">Generate invoices from confirmed or scheduled consultations</span>
        </div>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead><tr><th>Appointment</th><th>Doctor</th><th>Fee</th><th>Status</th><th>Invoice</th><th>Action</th></tr></thead>
                <tbody>
                <?php foreach (($appointments ?? []) as $appointment): ?>
                    <?php $existingInvoice = $invoiceMap[(int) $appointment['id']] ?? null; ?>
                    <tr>
                        <td>#<?= (int) $appointment['id'] ?><div class="small text-muted"><?= e($appointment['date']) ?> at <?= e($appointment['time']) ?></div></td>
                        <td><?= e($appointment['doctor_name']) ?><div class="small text-muted"><?= e($appointment['specialization'] ?? '') ?></div></td>
                        <td><?= e(config('app.currency', 'PKR')) ?> <?= number_format((float) ($appointment['consultation_fee'] ?? 0), 2) ?></td>
                        <td><span class="badge text-bg-light text-capitalize"><?= e($appointment['status']) ?></span></td>
                        <td>
                            <?php if ($existingInvoice): ?>
                                <span class="fw-semibold"><?= e($existingInvoice['invoice_number']) ?></span>
                                <div class="small text-muted text-capitalize"><?= e($existingInvoice['status']) ?></div>
                            <?php else: ?>
                                <span class="text-muted">Not generated</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!$existingInvoice): ?>
                                <form method="POST" action="<?= route_url('payments/create-invoice') ?>">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="appointment_id" value="<?= (int) $appointment['id'] ?>">
                                    <button class="btn btn-primary btn-sm">Generate Invoice</button>
                                </form>
                            <?php else: ?>
                                <a href="<?= route_url('payments/invoice', ['id' => (int) $existingInvoice['id']]) ?>" class="btn btn-outline-primary btn-sm">View Invoice</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($appointments)): ?><tr><td colspan="6" class="text-center text-muted py-4">No billable appointments are available yet. Confirmed or scheduled consultations will appear here so invoices can be generated.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

            </section>
            <section class="ux-stepper-panel" id="payments-step-invoices" data-stepper-panel>
    <div class="glass-panel mb-4" data-aos="fade-up">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="mb-0">Invoices</h4>
            <span class="small text-muted">Choose a payment provider for each unpaid invoice</span>
        </div>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead><tr><th>Invoice</th><th>Appointment</th><th>Amount</th><th>Status</th><th>Checkout</th><th>Actions</th></tr></thead>
                <tbody>
                <?php foreach (($invoices ?? []) as $invoice): ?>
                    <tr>
                        <td><?= e($invoice['invoice_number']) ?><div class="small text-muted">Issued <?= e($invoice['issued_at']) ?></div></td>
                        <td><?= e($invoice['doctor_name'] ?? 'Doctor') ?><div class="small text-muted"><?= e($invoice['appointment_date'] ?? '') ?> <?= e($invoice['appointment_time'] ?? '') ?></div></td>
                        <td><?= e($invoice['currency']) ?> <?= number_format((float) $invoice['amount'], 2) ?></td>
                        <td><span class="badge text-bg-light text-capitalize"><?= e($invoice['status']) ?></span></td>
                        <td>
                            <?php if (($invoice['status'] ?? '') !== 'paid' && ($invoice['status'] ?? '') !== 'refunded'): ?>
                                <form method="POST" action="<?= route_url('payments/checkout') ?>" class="d-flex gap-2 flex-wrap align-items-center">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="invoice_id" value="<?= (int) $invoice['id'] ?>">
                                    <select name="provider" class="form-select form-select-sm payment-provider-select">
                                        <option value="mock">Mock Sandbox</option>
                                        <option value="stripe">Stripe</option>
                                        <option value="paypal">PayPal</option>
                                        <option value="jazzcash">JazzCash</option>
                                        <option value="easypaisa">EasyPaisa</option>
                                    </select>
                                    <button class="btn btn-primary btn-sm">Pay Now</button>
                                </form>
                            <?php else: ?>
                                <span class="text-muted small">No action required</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="d-flex gap-2 flex-wrap">
                                <a href="<?= route_url('payments/invoice', ['id' => (int) $invoice['id']]) ?>" class="btn btn-outline-primary btn-sm">View</a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($invoices)): ?><tr><td colspan="6" class="text-center text-muted py-4">No invoices generated yet. Once a consultation is ready for billing, create an invoice and track payment status here.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

            </section>
            <section class="ux-stepper-panel" id="payments-step-history" data-stepper-panel>
    <div class="glass-panel mb-4" data-aos="fade-up">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="mb-0">Payment History</h4>
            <span class="small text-muted">All your transaction records in one place</span>
        </div>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead><tr><th>Reference</th><th>Invoice</th><th>Provider</th><th>Amount</th><th>Status</th><th>Refund</th></tr></thead>
                <tbody>
                <?php foreach (($payments ?? []) as $payment): ?>
                    <tr>
                        <td><?= e($payment['transaction_reference']) ?><div class="small text-muted"><?= e($payment['created_at']) ?></div></td>
                        <td><?= e($payment['invoice_number']) ?><div class="small text-muted"><?= e($payment['doctor_name'] ?? '') ?></div></td>
                        <td class="text-uppercase"><?= e($payment['provider']) ?></td>
                        <td><?= e($payment['currency']) ?> <?= number_format((float) $payment['amount'], 2) ?></td>
                        <td><span class="badge text-bg-light text-capitalize"><?= e($payment['status']) ?></span></td>
                        <td>
                            <?php if (($payment['status'] ?? '') === 'paid' && !in_array((int) $payment['id'], $refundPaymentIds ?? [], true)): ?>
                                <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#refundModal<?= (int) $payment['id'] ?>">Request Refund</button>
                            <?php elseif (in_array((int) $payment['id'], $refundPaymentIds ?? [], true)): ?>
                                <span class="text-muted small">Refund requested</span>
                            <?php else: ?>
                                <span class="text-muted small">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($payments)): ?><tr><td colspan="6" class="text-center text-muted py-4">No payments found yet. Completed checkouts, transaction references, and refund eligibility will appear here.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

            </section>
            <section class="ux-stepper-panel" id="payments-step-refunds" data-stepper-panel>
    <div class="glass-panel" data-aos="fade-up">
        <h4 class="mb-3">Refund Requests</h4>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead><tr><th>Reference</th><th>Provider</th><th>Amount</th><th>Status</th><th>Requested</th></tr></thead>
                <tbody>
                <?php foreach (($refunds ?? []) as $refund): ?>
                    <tr>
                        <td><?= e($refund['transaction_reference']) ?></td>
                        <td class="text-uppercase"><?= e($refund['provider']) ?></td>
                        <td><?= e(config('app.currency', 'PKR')) ?> <?= number_format((float) $refund['amount'], 2) ?></td>
                        <td><span class="badge text-bg-light text-capitalize"><?= e($refund['status']) ?></span></td>
                        <td><?= e($refund['created_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($refunds)): ?><tr><td colspan="5" class="text-center text-muted py-4">No refund requests submitted. Refund review status and provider references will appear here when a request is filed.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
            </section>
        </div>
    </div>

    <?php foreach (($payments ?? []) as $payment): ?>
        <div class="modal fade" id="refundModal<?= (int) $payment['id'] ?>" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header"><h5 class="modal-title">Request Refund</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
                    <form method="POST" action="<?= route_url('payments/refund-request') ?>">
                        <div class="modal-body">
                            <?= csrf_field() ?>
                            <input type="hidden" name="payment_id" value="<?= (int) $payment['id'] ?>">
                            <div class="mb-3 small text-muted">Reference: <?= e($payment['transaction_reference']) ?></div>
                            <label class="form-label">Reason for refund</label>
                            <textarea name="reason" class="form-control" rows="4" required placeholder="Explain the refund request..."></textarea>
                        </div>
                        <div class="modal-footer"><button class="btn btn-primary">Submit Request</button></div>
                    </form>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

<?php elseif (($role ?? '') === 'doctor'): ?>
    <div class="row g-4 mb-4">
        <div class="col-md-3"><div class="stat-card gradient-card"><span>Revenue</span><h2><?= e(config('app.currency', 'PKR')) ?> <?= number_format((float) ($paymentStats['revenue'] ?? 0), 2) ?></h2></div></div>
        <div class="col-md-3"><div class="stat-card gradient-card"><span>Total Transactions</span><h2><?= (int) ($paymentStats['total_transactions'] ?? 0) ?></h2></div></div>
        <div class="col-md-3"><div class="stat-card gradient-card"><span>Paid</span><h2><?= (int) ($paymentStats['paid_transactions'] ?? 0) ?></h2></div></div>
        <div class="col-md-3"><div class="stat-card gradient-card"><span>Pending</span><h2><?= (int) ($paymentStats['pending_transactions'] ?? 0) ?></h2></div></div>
    </div>

    <div class="glass-panel" data-aos="fade-up">
        <h4 class="mb-3">Consultation Payments</h4>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead><tr><th>Transaction</th><th>Patient</th><th>Invoice</th><th>Amount</th><th>Provider</th><th>Status</th></tr></thead>
                <tbody>
                <?php foreach (($payments ?? []) as $payment): ?>
                    <tr>
                        <td><?= e($payment['transaction_reference']) ?><div class="small text-muted"><?= e($payment['created_at']) ?></div></td>
                        <td><?= e($payment['patient_name'] ?? 'Patient') ?></td>
                        <td><?= e($payment['invoice_number']) ?><div class="small text-muted"><?= e($payment['appointment_date'] ?? '') ?> <?= e($payment['appointment_time'] ?? '') ?></div></td>
                        <td><?= e($payment['currency']) ?> <?= number_format((float) $payment['amount'], 2) ?></td>
                        <td class="text-uppercase"><?= e($payment['provider']) ?></td>
                        <td><span class="badge text-bg-light text-capitalize"><?= e($payment['status']) ?></span></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($payments)): ?><tr><td colspan="6" class="text-center text-muted py-4">No payment records yet. Paid consultation invoices will appear here with patient, provider, and transaction details.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php elseif (($role ?? '') === 'admin'): ?>
    <div class="row g-4 mb-4">
        <div class="col-md-3"><div class="stat-card gradient-card"><span>Gross Revenue</span><h2><?= e(config('app.currency', 'PKR')) ?> <?= number_format((float) ($paymentStats['gross_revenue'] ?? 0), 2) ?></h2></div></div>
        <div class="col-md-3"><div class="stat-card gradient-card"><span>Total Transactions</span><h2><?= (int) ($paymentStats['total_transactions'] ?? 0) ?></h2></div></div>
        <div class="col-md-3"><div class="stat-card gradient-card"><span>Paid Transactions</span><h2><?= (int) ($paymentStats['paid_transactions'] ?? 0) ?></h2></div></div>
        <div class="col-md-3"><div class="stat-card gradient-card"><span>Pending Refunds</span><h2><?= (int) ($refundPendingCount ?? 0) ?></h2></div></div>
    </div>

    <div class="ux-stepper" data-stepper>
        <div class="ux-stepper-nav" role="tablist" aria-label="Admin billing sections">
            <button class="ux-stepper-tab is-active" data-stepper-tab data-stepper-target="admin-payments-step-invoices">
                <span class="ux-stepper-number">1</span>
                <span class="ux-stepper-label"><strong>Invoices</strong><span>Platform billing</span></span>
            </button>
            <button class="ux-stepper-tab" data-stepper-tab data-stepper-target="admin-payments-step-transactions">
                <span class="ux-stepper-number">2</span>
                <span class="ux-stepper-label"><strong>Transactions</strong><span>Gateway activity</span></span>
            </button>
            <button class="ux-stepper-tab" data-stepper-tab data-stepper-target="admin-payments-step-refunds">
                <span class="ux-stepper-number">3</span>
                <span class="ux-stepper-label"><strong>Refunds</strong><span>Review queue</span></span>
            </button>
        </div>
        <div class="ux-stepper-panels">
            <section class="ux-stepper-panel is-active" id="admin-payments-step-invoices" data-stepper-panel>
    <div class="glass-panel mb-4" data-aos="fade-up">
        <h4 class="mb-3">Invoices</h4>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead><tr><th>Invoice</th><th>User</th><th>Doctor</th><th>Amount</th><th>Status</th></tr></thead>
                <tbody>
                <?php foreach (($invoices ?? []) as $invoice): ?>
                    <tr>
                        <td><?= e($invoice['invoice_number']) ?><div class="small text-muted"><?= e($invoice['issued_at']) ?></div></td>
                        <td><?= e($invoice['user_email']) ?></td>
                        <td><?= e($invoice['doctor_name'] ?? 'Doctor') ?></td>
                        <td><?= e($invoice['currency']) ?> <?= number_format((float) $invoice['amount'], 2) ?></td>
                        <td><span class="badge text-bg-light text-capitalize"><?= e($invoice['status']) ?></span></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($invoices)): ?><tr><td colspan="5" class="text-center text-muted py-4">No invoices found. Platform invoices will populate here as patients generate consultation or subscription billing records.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

            </section>
            <section class="ux-stepper-panel" id="admin-payments-step-transactions" data-stepper-panel>
    <div class="glass-panel mb-4" data-aos="fade-up">
        <h4 class="mb-3">Transactions</h4>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead><tr><th>Reference</th><th>Payer</th><th>Payee</th><th>Provider</th><th>Amount</th><th>Status</th></tr></thead>
                <tbody>
                <?php foreach (($payments ?? []) as $payment): ?>
                    <tr>
                        <td><?= e($payment['transaction_reference']) ?><div class="small text-muted"><?= e($payment['created_at']) ?></div></td>
                        <td><?= e($payment['payer_email']) ?></td>
                        <td><?= e($payment['payee_email'] ?? '—') ?></td>
                        <td class="text-uppercase"><?= e($payment['provider']) ?></td>
                        <td><?= e($payment['currency']) ?> <?= number_format((float) $payment['amount'], 2) ?></td>
                        <td><span class="badge text-bg-light text-capitalize"><?= e($payment['status']) ?></span></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($payments)): ?><tr><td colspan="6" class="text-center text-muted py-4">No transactions found. Payment gateway activity, payer details, and settlement status will appear here.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

            </section>
            <section class="ux-stepper-panel" id="admin-payments-step-refunds" data-stepper-panel>
    <div class="glass-panel" data-aos="fade-up">
        <h4 class="mb-3">Refund Management</h4>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead><tr><th>Transaction</th><th>Requested By</th><th>Amount</th><th>Reason</th><th>Status</th><th>Action</th></tr></thead>
                <tbody>
                <?php foreach (($refunds ?? []) as $refund): ?>
                    <tr>
                        <td><?= e($refund['transaction_reference']) ?><div class="small text-muted text-uppercase"><?= e($refund['provider']) ?></div></td>
                        <td><?= e($refund['requested_by_email']) ?><div class="small text-muted"><?= e($refund['created_at']) ?></div></td>
                        <td><?= e(config('app.currency', 'PKR')) ?> <?= number_format((float) $refund['amount'], 2) ?></td>
                        <td><?= e($refund['reason']) ?></td>
                        <td><span class="badge text-bg-light text-capitalize"><?= e($refund['status']) ?></span></td>
                        <td>
                            <?php if (($refund['status'] ?? '') === 'pending' || ($refund['status'] ?? '') === 'approved'): ?>
                                <form method="POST" action="<?= route_url('admin/refunds/update') ?>" class="d-grid gap-2">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="refund_id" value="<?= (int) $refund['id'] ?>">
                                    <select name="status" class="form-select form-select-sm">
                                        <option value="approved" <?= $refund['status'] === 'approved' ? 'selected' : '' ?>>Approve</option>
                                        <option value="processed">Mark Processed</option>
                                        <option value="rejected">Reject</option>
                                    </select>
                                    <button class="btn btn-outline-primary btn-sm">Update</button>
                                </form>
                            <?php else: ?>
                                <span class="text-muted small">Reviewed by <?= e($refund['reviewed_by_email'] ?? 'admin') ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($refunds)): ?><tr><td colspan="6" class="text-center text-muted py-4">No refund requests available. Patient refund submissions will appear here for review and processing.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
            </section>
        </div>
    </div>

<?php else: ?>
    <div class="glass-panel text-center py-5">
        <h4>Payments Module</h4>
        <p class="text-muted mb-0"><?= e($message ?? 'No payment workflows available for this role yet.') ?></p>
    </div>
<?php endif; ?>
