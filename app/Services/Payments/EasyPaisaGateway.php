<?php
namespace App\Services\Payments;

class EasyPaisaGateway implements GatewayInterface
{
    public function initiate(array $invoice, array $payment, array $customer): array
    {
        return [
            'success' => false,
            'status' => 'pending',
            'gateway_response' => json_encode([
                'message' => 'EasyPaisa request signing and callback validation need merchant credentials and API onboarding.',
                'invoice' => $invoice['invoice_number'] ?? null,
            ]),
            'message' => 'EasyPaisa merchant API integration is scaffolded and ready for credentials.',
        ];
    }
}
