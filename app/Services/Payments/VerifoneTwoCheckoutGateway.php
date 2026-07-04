<?php
namespace App\Services\Payments;

class VerifoneTwoCheckoutGateway implements SubscriptionGatewayInterface
{
    public function initiate(array $plan, array $subscription, array $customer): array
    {
        $merchantCode = trim((string) config('services.verifone_2checkout_merchant_code', ''));
        $buyLinkSecret = trim((string) config('services.verifone_2checkout_buy_link_secret', ''));
        $baseUrl = rtrim((string) config('services.verifone_2checkout_buy_link_url', 'https://secure.2checkout.com/checkout/buy'), '?');

        if ($merchantCode === '' || $buyLinkSecret === '') {
            return [
                'success' => false,
                'status' => 'failed',
                'gateway_response' => json_encode(['message' => 'Verifone 2Checkout merchant code or buy-link secret is missing.']),
                'message' => 'Add Verifone 2Checkout merchant code and buy-link secret in config/config.php before live checkout.',
            ];
        }

        $reference = $subscription['transaction_reference'];
        $currency = $plan['currency'] ?? 'USD';
        $priceValue = number_format((float) $plan['price'], 2, '.', '');
        $price = $currency . ':' . $priceValue;
        $expiration = time() + 3600;
        $callbackUrl = route_url('payments/subscription/callback', ['ref' => $reference]);

        $params = [
            'merchant' => $merchantCode,
            'dynamic' => '1',
            'prod' => $plan['name'],
            'price' => $price,
            'currency' => $currency,
            'qty' => '1',
            'type' => 'digital',
            'expiration' => (string) $expiration,
            'return-url' => $callbackUrl,
            'return-type' => 'redirect',
            'order-ext-ref' => $reference,
            'customer-ext-ref' => 'USER-' . (int) ($customer['id'] ?? 0),
            'customer-ref' => 'USER-' . (int) ($customer['id'] ?? 0),
            'src' => 'medisphere_subscription',
        ];

        if (config('services.verifone_2checkout_test_mode', true)) {
            $params['test'] = '1';
        }

        $signature = $this->buildBuyLinkSignature($params, $buyLinkSecret);
        $params['signature'] = $signature;

        return [
            'success' => true,
            'status' => 'pending',
            'redirect_url' => $baseUrl . '?' . http_build_query($params),
            'provider_checkout_id' => $reference,
            'gateway_response' => json_encode([
                'provider' => 'verifone_2checkout',
                'mode' => config('services.verifone_2checkout_test_mode', true) ? 'test' : 'live',
                'order_ext_ref' => $reference,
                'amount' => $priceValue,
                'currency' => $currency,
            ]),
            'message' => 'Redirecting to Verifone 2Checkout secure hosted checkout.',
        ];
    }

    public function verifyWebhook(array $payload, string $rawBody, array $headers = []): bool
    {
        $secretKey = trim((string) config('services.verifone_2checkout_secret_key', ''));
        $requireHash = (bool) config('services.verifone_2checkout_require_ipn_hash', true);
        $receivedSha2 = $payload['HASH'] ?? $payload['hash'] ?? $payload['SIGNATURE_SHA2_256'] ?? '';
        $receivedSha3 = $payload['SIGNATURE_SHA3_256'] ?? '';

        if ($secretKey === '') {
            return !$requireHash;
        }

        if ($receivedSha2 === '' && $receivedSha3 === '') {
            return !$requireHash;
        }

        $fields = $payload;
        unset($fields['HASH'], $fields['hash'], $fields['SIGNATURE_SHA2_256'], $fields['SIGNATURE_SHA3_256']);

        $source = $this->serializeValues($fields);
        $expectedSha2 = hash_hmac('sha256', $source, $secretKey);

        if ($receivedSha2 !== '' && hash_equals($expectedSha2, strtolower((string) $receivedSha2))) {
            return true;
        }

        if ($receivedSha3 !== '' && in_array('sha3-256', hash_hmac_algos(), true)) {
            return hash_equals(hash_hmac('sha3-256', $source, $secretKey), strtolower((string) $receivedSha3));
        }

        return false;
    }

    public function normalizeWebhook(array $payload, string $rawBody): array
    {
        $status = strtoupper((string) ($payload['ORDERSTATUS'] ?? $payload['MESSAGE_TYPE'] ?? $payload['status'] ?? ''));
        $messageType = strtoupper((string) ($payload['MESSAGE_TYPE'] ?? ''));
        $fraudStatus = strtoupper((string) ($payload['FRAUD_STATUS'] ?? $payload['FraudStatus'] ?? ''));

        $approvedStatuses = ['COMPLETE', 'PAYMENT_RECEIVED', 'PAYMENT_AUTHORIZED', 'APPROVED', 'AUTHORIZED'];
        $failedStatuses = ['INVALID', 'CANCELED', 'CANCELLED', 'REVERSED', 'REFUND', 'DENIED', 'FAILED'];

        $paymentStatus = 'pending';
        if ($fraudStatus === 'DENIED' || in_array($status, $failedStatuses, true) || in_array($messageType, $failedStatuses, true)) {
            $paymentStatus = 'failed';
        } elseif (in_array($status, $approvedStatuses, true) || in_array($messageType, $approvedStatuses, true)) {
            $paymentStatus = 'approved';
        }

        $reference = $payload['REFNOEXT']
            ?? $payload['ORIGINAL_REFNOEXT']
            ?? $payload['SHOPPER_REFERENCE_NUMBER']
            ?? $payload['merchant_order_id']
            ?? $payload['order_ext_ref']
            ?? '';

        if (is_array($reference)) {
            $reference = reset($reference) ?: '';
        }

        return [
            'reference' => (string) $reference,
            'provider_payment_id' => (string) ($payload['REFNO'] ?? $payload['ORDERNO'] ?? ''),
            'provider_subscription_id' => (string) ($payload['SUBSCRIPTION_REFERENCE'] ?? $payload['IPN_PCODE'][0] ?? ''),
            'status' => $paymentStatus,
            'event_type' => (string) ($payload['MESSAGE_TYPE'] ?? $payload['ORDERSTATUS'] ?? 'ipn'),
            'event_id' => implode('-', array_filter([
                $payload['REFNO'] ?? $payload['ORDERNO'] ?? null,
                $payload['ORDERSTATUS'] ?? $payload['MESSAGE_TYPE'] ?? null,
                $payload['IPN_DATE'] ?? null,
            ])) ?: sha1($rawBody ?: json_encode($payload)),
            'amount' => (float) ($payload['IPN_TOTALGENERAL'] ?? $payload['IPN_TOTAL'][0] ?? 0),
            'currency' => (string) ($payload['CURRENCY'] ?? 'USD'),
            'paid_at' => $this->resolvePaidAt($payload),
        ];
    }

    public function acknowledgement(array $payload): string
    {
        $secretKey = trim((string) config('services.verifone_2checkout_secret_key', ''));
        $date = gmdate('YmdHis');

        if ($secretKey === '' || empty($payload['IPN_PID'][0]) || empty($payload['IPN_PNAME'][0]) || empty($payload['IPN_DATE'])) {
            return 'OK';
        }

        $source = $this->serializeValues([
            $payload['IPN_PID'][0],
            $payload['IPN_PNAME'][0],
            $payload['IPN_DATE'],
            $date,
        ]);
        $hash = hash_hmac('sha256', $source, $secretKey);

        return '<sig algo="sha256" date="' . $date . '">' . $hash . '</sig>';
    }

    private function buildBuyLinkSignature(array $params, string $secret): string
    {
        $signedKeys = [
            'currency',
            'customer-ext-ref',
            'customer-ref',
            'description',
            'duration',
            'expiration',
            'item-ext-ref',
            'opt',
            'order-ext-ref',
            'price',
            'prod',
            'qty',
            'recurrence',
            'renewal-price',
            'return-type',
            'return-url',
            'type',
        ];

        $values = [];
        foreach ($signedKeys as $key) {
            if (array_key_exists($key, $params) && $params[$key] !== '') {
                $values[$key] = (string) $params[$key];
            }
        }

        ksort($values);
        return hash_hmac('sha256', $this->serializeValues($values), $secret);
    }

    private function serializeValues(array $values): string
    {
        $serialized = '';
        foreach ($values as $value) {
            if (is_array($value)) {
                $serialized .= $this->serializeValues($value);
                continue;
            }

            $value = (string) $value;
            if ($value === '') {
                $serialized .= '0';
                continue;
            }

            $serialized .= strlen($value) . $value;
        }
        return $serialized;
    }

    private function resolvePaidAt(array $payload): string
    {
        foreach (['PAYMENTDATE', 'COMPLETE_DATE', 'SALEDATE'] as $field) {
            if (!empty($payload[$field])) {
                $timestamp = strtotime((string) $payload[$field]);
                if ($timestamp) {
                    return date('Y-m-d H:i:s', $timestamp);
                }
            }
        }

        return date('Y-m-d H:i:s');
    }
}
