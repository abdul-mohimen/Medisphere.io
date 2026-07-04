<?php
namespace App\Services\Payments;

class MockGateway implements GatewayInterface
{
    public function initiate(array $invoice, array $payment, array $customer): array
    {
        return [
            'success' => true,
            'status' => 'paid',
            'provider_payment_id' => 'MOCK-' . strtoupper(bin2hex(random_bytes(4))),
            'gateway_response' => json_encode([
                'message' => 'Mock sandbox payment completed instantly.',
                'invoice_number' => $invoice['invoice_number'] ?? null,
                'customer' => $customer['email'] ?? null,
            ]),
            'paid_at' => date('Y-m-d H:i:s'),
            'message' => 'Mock payment completed successfully.',
        ];
    }
}
