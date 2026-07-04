<?php
namespace App\Services\Payments;

class PayPalGateway implements GatewayInterface
{
    public function initiate(array $invoice, array $payment, array $customer): array
    {
        if (!config('services.paypal_client_id')) {
            return [
                'success' => false,
                'status' => 'pending',
                'gateway_response' => json_encode(['message' => 'PayPal credentials are not configured.']),
                'message' => 'PayPal client credentials are missing. Add them in config/config.php.',
            ];
        }

        return [
            'success' => false,
            'status' => 'pending',
            'gateway_response' => json_encode([
                'message' => 'PayPal order creation flow is scaffolded and ready for SDK hookup.',
                'invoice' => $invoice['invoice_number'] ?? null,
            ]),
            'message' => 'PayPal is configured, but live order capture flow still needs the provider SDK integration.',
        ];
    }
}
