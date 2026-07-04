<?php
namespace App\Services\Payments;

class SubscriptionGatewayManager
{
    public function gateway(string $provider): SubscriptionGatewayInterface
    {
        return match ($provider) {
            'payoneer_checkout' => new PayoneerCheckoutGateway(),
            default => new VerifoneTwoCheckoutGateway(),
        };
    }

    public function process(string $provider, array $plan, array $subscription, array $customer): array
    {
        return $this->gateway($provider)->initiate($plan, $subscription, $customer);
    }
}
