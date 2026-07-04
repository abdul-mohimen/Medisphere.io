<?php
namespace App\Services\Payments;

class JazzCashGateway implements GatewayInterface
{
    public function initiate(array $invoice, array $payment, array $customer): array
    {
        return [
            'success' => false,
            'status' => 'pending',
            'gateway_response' => json_encode([
                'message' => 'JazzCash merchant signing/request flow needs merchant credentials and callback integration.',
                'invoice' => $invoice['invoice_number'] ?? null,
            ]),
            'message' => 'JazzCash production credentials and signed request flow need to be configured next.',
        ];
    }
}
