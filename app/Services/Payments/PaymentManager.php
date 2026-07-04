<?php
namespace App\Services\Payments;

class PaymentManager
{
    public function gateway(string $provider): GatewayInterface
    {
        return match ($provider) {
            'stripe' => new StripeGateway(),
            'paypal' => new PayPalGateway(),
            'jazzcash' => new JazzCashGateway(),
            'easypaisa' => new EasyPaisaGateway(),
            default => new MockGateway(),
        };
    }

    public function process(string $provider, array $invoice, array $payment, array $customer): array
    {
        return $this->gateway($provider)->initiate($invoice, $payment, $customer);
    }
}
