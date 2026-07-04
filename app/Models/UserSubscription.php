<?php
namespace App\Models;

use PDOException;

class UserSubscription extends BaseModel
{
    public function activeForUser(int $userId): ?array
    {
        try {
            $this->expireDueForUser($userId);
            $stmt = $this->db->prepare('SELECT * FROM user_subscriptions
                WHERE user_id = :user_id
                  AND status = "subscribed"
                  AND (current_period_end IS NULL OR current_period_end >= NOW())
                ORDER BY current_period_end DESC, id DESC
                LIMIT 1');
            $stmt->execute(['user_id' => $userId]);
            $row = $stmt->fetch();
            return $row ?: null;
        } catch (PDOException) {
            return null;
        }
    }

    public function latestForUser(int $userId): ?array
    {
        try {
            $stmt = $this->db->prepare('SELECT * FROM user_subscriptions WHERE user_id = :user_id ORDER BY created_at DESC, id DESC LIMIT 1');
            $stmt->execute(['user_id' => $userId]);
            $row = $stmt->fetch();
            return $row ?: null;
        } catch (PDOException) {
            return null;
        }
    }

    public function findByReference(string $reference): ?array
    {
        try {
            $stmt = $this->db->prepare('SELECT * FROM user_subscriptions WHERE transaction_reference = :reference LIMIT 1');
            $stmt->execute(['reference' => $reference]);
            $row = $stmt->fetch();
            return $row ?: null;
        } catch (PDOException) {
            return null;
        }
    }

    public function createPending(array $data): int
    {
        $stmt = $this->db->prepare('INSERT INTO user_subscriptions
            (user_id, plan_slug, access_level, provider, provider_checkout_id, provider_subscription_id, transaction_reference, status, amount, currency, current_period_start, current_period_end, metadata, created_at, updated_at)
            VALUES
            (:user_id, :plan_slug, :access_level, :provider, :provider_checkout_id, :provider_subscription_id, :transaction_reference, :status, :amount, :currency, :current_period_start, :current_period_end, :metadata, NOW(), NOW())');
        $stmt->execute([
            'user_id' => $data['user_id'],
            'plan_slug' => $data['plan_slug'],
            'access_level' => $data['access_level'] ?? 'premium',
            'provider' => $data['provider'],
            'provider_checkout_id' => $data['provider_checkout_id'] ?? null,
            'provider_subscription_id' => $data['provider_subscription_id'] ?? null,
            'transaction_reference' => $data['transaction_reference'],
            'status' => $data['status'] ?? 'pending',
            'amount' => $data['amount'],
            'currency' => $data['currency'] ?? 'USD',
            'current_period_start' => $data['current_period_start'] ?? null,
            'current_period_end' => $data['current_period_end'] ?? null,
            'metadata' => $data['metadata'] ?? null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function updateGatewayStart(int $id, array $data): bool
    {
        $stmt = $this->db->prepare('UPDATE user_subscriptions
            SET provider_checkout_id = COALESCE(:provider_checkout_id, provider_checkout_id),
                metadata = COALESCE(:metadata, metadata),
                updated_at = NOW()
            WHERE id = :id');
        return $stmt->execute([
            'provider_checkout_id' => $data['provider_checkout_id'] ?? null,
            'metadata' => $data['metadata'] ?? null,
            'id' => $id,
        ]);
    }

    public function markSubscribed(int $id, array $data): bool
    {
        $stmt = $this->db->prepare('UPDATE user_subscriptions
            SET status = "subscribed",
                provider_checkout_id = COALESCE(:provider_checkout_id, provider_checkout_id),
                provider_subscription_id = COALESCE(:provider_subscription_id, provider_subscription_id),
                current_period_start = COALESCE(current_period_start, :current_period_start),
                current_period_end = :current_period_end,
                metadata = COALESCE(:metadata, metadata),
                updated_at = NOW()
            WHERE id = :id');
        return $stmt->execute([
            'provider_checkout_id' => $data['provider_checkout_id'] ?? null,
            'provider_subscription_id' => $data['provider_subscription_id'] ?? null,
            'current_period_start' => $data['current_period_start'] ?? date('Y-m-d H:i:s'),
            'current_period_end' => $data['current_period_end'],
            'metadata' => $data['metadata'] ?? null,
            'id' => $id,
        ]);
    }

    public function markStatus(int $id, string $status, ?string $metadata = null): bool
    {
        $stmt = $this->db->prepare('UPDATE user_subscriptions SET status = :status, metadata = COALESCE(:metadata, metadata), updated_at = NOW() WHERE id = :id');
        return $stmt->execute([
            'status' => $status,
            'metadata' => $metadata,
            'id' => $id,
        ]);
    }

    public function expireDueForUser(int $userId): array
    {
        try {
            $stmt = $this->db->prepare('SELECT * FROM user_subscriptions
                WHERE user_id = :user_id
                  AND status = "subscribed"
                  AND current_period_end IS NOT NULL
                  AND current_period_end < NOW()
                ORDER BY current_period_end ASC');
            $stmt->execute(['user_id' => $userId]);
            $expired = $stmt->fetchAll();
            if (!$expired) {
                return [];
            }

            $ids = array_map(static fn(array $row): int => (int) $row['id'], $expired);
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $update = $this->db->prepare('UPDATE user_subscriptions
                SET status = "expired",
                    expired_notified_at = COALESCE(expired_notified_at, NOW()),
                    updated_at = NOW()
                WHERE id IN (' . $placeholders . ')');
            $update->execute($ids);

            return $expired;
        } catch (PDOException) {
            return [];
        }
    }

    public function hasActiveForUser(int $userId): bool
    {
        try {
            $stmt = $this->db->prepare('SELECT COUNT(*) FROM user_subscriptions
                WHERE user_id = :user_id
                  AND status = "subscribed"
                  AND (current_period_end IS NULL OR current_period_end >= NOW())');
            $stmt->execute(['user_id' => $userId]);
            return (int) $stmt->fetchColumn() > 0;
        } catch (PDOException) {
            return false;
        }
    }
}
