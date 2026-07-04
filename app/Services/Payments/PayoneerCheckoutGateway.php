<?php
namespace App\Services\Payments;

class PayoneerCheckoutGateway implements SubscriptionGatewayInterface
{
    public function initiate(array $plan, array $subscription, array $customer): array
    {
        $username = trim((string) config('services.payoneer_checkout_username', ''));
        $password = trim((string) config('services.payoneer_checkout_password', ''));
        $division = trim((string) config('services.payoneer_checkout_division', ''));
        $apiBase = rtrim((string) config('services.payoneer_checkout_api_base', 'https://api.sandbox.oscato.com'), '/');

        if ($username === '' || $password === '' || $division === '') {
            return [
                'success' => false,
                'status' => 'failed',
                'gateway_response' => json_encode(['message' => 'Payoneer Checkout credentials are missing.']),
                'message' => 'Add Payoneer Checkout username, password, and division in config/config.php before live checkout.',
            ];
        }

        if (!function_exists('curl_init')) {
            return [
                'success' => false,
                'status' => 'failed',
                'gateway_response' => json_encode(['message' => 'PHP cURL extension is required for Payoneer Checkout.']),
                'message' => 'Enable PHP cURL to create Payoneer hosted checkout sessions.',
            ];
        }

        $reference = $subscription['transaction_reference'];
        $payload = [
            'integration' => 'HOSTED',
            'transactionId' => $reference,
            'country' => $customer['country'] ?? 'US',
            'division' => $division,
            'callback' => [
                'returnUrl' => route_url('payments/subscription/callback', ['ref' => $reference]),
                'cancelUrl' => route_url('payments/subscription/cancel', ['ref' => $reference]),
                'notificationUrl' => route_url('payments/webhook/payoneer'),
            ],
            'customer' => [
                'email' => $customer['email'] ?? '',
            ],
            'payment' => [
                'amount' => number_format((float) $plan['price'], 2, '.', ''),
                'currency' => $plan['currency'] ?? 'USD',
                'reference' => $reference,
                'description' => config('app.name', 'MediSphere') . ' ' . $plan['name'],
            ],
            'style' => [
                'language' => 'en',
                'hostedVersion' => 'v5',
            ],
        ];

        $ch = curl_init($apiBase . '/api/lists');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
            CURLOPT_USERPWD => $username . ':' . $password,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Accept: application/json'],
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_TIMEOUT => 25,
        ]);
        $body = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($body === false || $error !== '') {
            return [
                'success' => false,
                'status' => 'failed',
                'gateway_response' => json_encode(['message' => $error ?: 'Payoneer request failed.']),
                'message' => 'Payoneer Checkout session could not be created.',
            ];
        }

        $decoded = json_decode((string) $body, true) ?: [];
        $redirectUrl = $this->extractRedirectUrl($decoded);

        if ($httpCode >= 200 && $httpCode < 300 && $redirectUrl) {
            return [
                'success' => true,
                'status' => 'pending',
                'redirect_url' => $redirectUrl,
                'provider_checkout_id' => (string) ($decoded['identification']['longId'] ?? $decoded['longId'] ?? $reference),
                'gateway_response' => json_encode($decoded),
                'message' => 'Redirecting to Payoneer secure hosted checkout.',
            ];
        }

        return [
            'success' => false,
            'status' => 'failed',
            'gateway_response' => json_encode($decoded ?: ['raw' => $body, 'http_code' => $httpCode]),
            'message' => 'Payoneer Checkout did not return a hosted payment URL.',
        ];
    }

    public function verifyWebhook(array $payload, string $rawBody, array $headers = []): bool
    {
        $secret = trim((string) config('services.payoneer_checkout_webhook_secret', ''));
        if ($secret === '') {
            return true;
        }

        $signature = $headers['x-payoneer-signature'] ?? $headers['x-oscato-signature'] ?? '';
        if ($signature === '') {
            return false;
        }

        return hash_equals(hash_hmac('sha256', $rawBody, $secret), strtolower((string) $signature));
    }

    public function normalizeWebhook(array $payload, string $rawBody): array
    {
        $status = strtoupper((string) ($payload['status'] ?? $payload['resultCode'] ?? $payload['payment']['status'] ?? ''));
        $approved = ['APPROVED', 'CAPTURED', 'PAID', 'SUCCESS', 'CHARGED'];
        $failed = ['FAILED', 'DECLINED', 'CANCELLED', 'CANCELED', 'ERROR', 'REJECTED'];

        $paymentStatus = 'pending';
        if (in_array($status, $approved, true)) {
            $paymentStatus = 'approved';
        } elseif (in_array($status, $failed, true)) {
            $paymentStatus = 'failed';
        }

        return [
            'reference' => (string) ($payload['transactionId'] ?? $payload['reference'] ?? $payload['payment']['reference'] ?? ''),
            'provider_payment_id' => (string) ($payload['longId'] ?? $payload['identification']['longId'] ?? $payload['payment']['id'] ?? ''),
            'provider_subscription_id' => '',
            'status' => $paymentStatus,
            'event_type' => (string) ($payload['eventType'] ?? $status ?: 'payoneer_notification'),
            'event_id' => (string) ($payload['eventId'] ?? $payload['longId'] ?? sha1($rawBody ?: json_encode($payload))),
            'amount' => (float) ($payload['payment']['amount'] ?? $payload['amount'] ?? 0),
            'currency' => (string) ($payload['payment']['currency'] ?? $payload['currency'] ?? 'USD'),
            'paid_at' => date('Y-m-d H:i:s'),
        ];
    }

    public function acknowledgement(array $payload): string
    {
        return 'OK';
    }

    private function extractRedirectUrl(array $decoded): ?string
    {
        $candidates = [
            $decoded['redirect']['url'] ?? null,
            $decoded['links']['payment']['href'] ?? null,
            $decoded['links']['hostedPaymentPage']['href'] ?? null,
            $decoded['links']['self']['href'] ?? null,
            $decoded['url'] ?? null,
        ];

        if (!empty($decoded['links']) && is_array($decoded['links'])) {
            foreach ($decoded['links'] as $link) {
                if (is_array($link) && !empty($link['href']) && str_contains((string) $link['href'], 'paymentpage')) {
                    $candidates[] = $link['href'];
                }
            }
        }

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && filter_var($candidate, FILTER_VALIDATE_URL)) {
                return $candidate;
            }
        }

        return null;
    }
}
