<?php
namespace App\Services\Payments;

interface GatewayInterface
{
    public function initiate(array $invoice, array $payment, array $customer): array;
}
