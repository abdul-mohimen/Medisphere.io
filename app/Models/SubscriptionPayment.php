<?php
namespace App\Models;

use PDOException;

class SubscriptionPayment extends BaseModel
{
    public function createPending(array $data): int
    {
        $stmt = $this->db->prepare('INSERT INTO subscription_payments
            (subscription_id, user_id, provider, provider_payment_id, transaction_reference, amount, currency, status, gateway_response, paid_at, created_at, updated_at)
            VALUES
            (:subscription_id, :user_id, :provider, :provider_payment_id, :transaction_reference, :amount, :currency, :status, :gateway_response, :paid_at, NOW(), NOW())');
        $stmt->execute([
            'subscription_id' => $data['subscription_id'],
            'user_id' => $data['user_id'],
            'provider' => $data['provider'],
            'provider_payment_id' => $data['provider_payment_id'] ?? null,
            'transaction_reference' => $data['transaction_reference'],
            'amount' => $data['amount'],
            'currency' => $data['currency'] ?? 'USD',
            'status' => $data['status'] ?? 'pending',
            'gateway_response' => $data['gateway_response'] ?? null,
            'paid_at' => $data['paid_at'] ?? null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function byUser(int $userId): array
    {
        try {
            $stmt = $this->db->prepare('SELECT sp.*, us.plan_slug
                FROM subscription_payments sp
                JOIN user_subscriptions us ON us.id = sp.subscription_id
                WHERE sp.user_id = :user_id
                ORDER BY sp.created_at DESC, sp.id DESC');
            $stmt->execute(['user_id' => $userId]);
            return $stmt->fetchAll();
        } catch (PDOException) {
            return [];
        }
    }

    public function findByReference(string $reference): ?array
    {
        try {
            $stmt = $this->db->prepare('SELECT sp.*, us.plan_slug, us.access_level, us.current_period_start, us.current_period_end
                FROM subscription_payments sp
                JOIN user_subscriptions us ON us.id = sp.subscription_id
                WHERE sp.transaction_reference = :reference
                LIMIT 1');
            $stmt->execute(['reference' => $reference]);
            $row = $stmt->fetch();
            return $row ?: null;
        } catch (PDOException) {
            return null;
        }
    }

    public function markPaidByReference(string $reference, array $data): bool
    {
        $stmt = $this->db->prepare('UPDATE subscription_payments
            SET status = "paid",
                provider_payment_id = COALESCE(:provider_payment_id, provider_payment_id),
                gateway_response = COALESCE(:gateway_response, gateway_response),
                paid_at = COALESCE(:paid_at, NOW()),
                updated_at = NOW()
            WHERE transaction_reference = :transaction_reference');
        return $stmt->execute([
            'provider_payment_id' => $data['provider_payment_id'] ?? null,
            'gateway_response' => $data['gateway_response'] ?? null,
            'paid_at' => $data['paid_at'] ?? date('Y-m-d H:i:s'),
            'transaction_reference' => $reference,
        ]);
    }

    public function markStatusByReference(string $reference, string $status, ?string $gatewayResponse = null): bool
    {
        $stmt = $this->db->prepare('UPDATE subscription_payments
            SET status = :status,
                gateway_response = COALESCE(:gateway_response, gateway_response),
                updated_at = NOW()
            WHERE transaction_reference = :transaction_reference');
        return $stmt->execute([
            'status' => $status,
            'gateway_response' => $gatewayResponse,
            'transaction_reference' => $reference,
        ]);
    }
}
