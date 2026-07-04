<?php
namespace App\Services\Payments;

class StripeGateway implements GatewayInterface
{
    public function initiate(array $invoice, array $payment, array $customer): array
    {
        if (!config('services.stripe_public_key') || !config('services.stripe_secret_key')) {
            return [
                'success' => false,
                'status' => 'pending',
                'gateway_response' => json_encode(['message' => 'Stripe credentials are not configured.']),
                'message' => 'Stripe credentials are missing. Add them in config/config.php.',
            ];
        }

        return [
            'success' => false,
            'status' => 'pending',
            'gateway_response' => json_encode([
                'message' => 'Stripe checkout session architecture is ready. Attach the official SDK / Checkout Session endpoint next.',
                'invoice' => $invoice['invoice_number'] ?? null,
            ]),
            'message' => 'Stripe is configured, but SDK checkout wiring is still required for live charges.',
        ];
    }
}
