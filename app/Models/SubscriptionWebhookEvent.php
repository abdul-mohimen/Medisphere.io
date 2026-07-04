<?php
namespace App\Models;

use PDOException;

class SubscriptionWebhookEvent extends BaseModel
{
    public function recordIfNew(array $data): ?int
    {
        try {
            $stmt = $this->db->prepare('INSERT INTO subscription_webhook_events
                (provider, event_id, event_type, payload_hash, payload, processed_at, created_at)
                VALUES (:provider, :event_id, :event_type, :payload_hash, :payload, NULL, NOW())');
            $stmt->execute([
                'provider' => $data['provider'],
                'event_id' => $data['event_id'],
                'event_type' => $data['event_type'] ?? null,
                'payload_hash' => $data['payload_hash'],
                'payload' => $data['payload'],
            ]);
            return (int) $this->db->lastInsertId();
        } catch (PDOException $exception) {
            if ($exception->getCode() === '23000') {
                return null;
            }
            throw $exception;
        }
    }

    public function markProcessed(int $id): bool
    {
        $stmt = $this->db->prepare('UPDATE subscription_webhook_events SET processed_at = NOW() WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }
}
