<?php
namespace App\Services\Payments;

interface SubscriptionGatewayInterface
{
    public function initiate(array $plan, array $subscription, array $customer): array;

    public function verifyWebhook(array $payload, string $rawBody, array $headers = []): bool;

    public function normalizeWebhook(array $payload, string $rawBody): array;

    public function acknowledgement(array $payload): string;
}
