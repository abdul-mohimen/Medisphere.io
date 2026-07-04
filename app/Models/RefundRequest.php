<?php
namespace App\Models;

class RefundRequest extends BaseModel
{
    public function create(array $data): int
    {
        $stmt = $this->db->prepare('INSERT INTO refund_requests (payment_id, requested_by, reason, status, reviewed_by, created_at, updated_at) VALUES (:payment_id, :requested_by, :reason, :status, :reviewed_by, NOW(), NOW())');
        $stmt->execute([
            'payment_id' => $data['payment_id'],
            'requested_by' => $data['requested_by'],
            'reason' => $data['reason'],
            'status' => $data['status'] ?? 'pending',
            'reviewed_by' => $data['reviewed_by'] ?? null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function byUser(int $userId): array
    {
        $stmt = $this->db->prepare('SELECT rr.*, p.transaction_reference, p.amount, p.provider
            FROM refund_requests rr
            JOIN payments p ON p.id = rr.payment_id
            WHERE rr.requested_by = :user_id
            ORDER BY rr.created_at DESC');
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll();
    }

    public function all(): array
    {
        $stmt = $this->db->query('SELECT rr.*, p.transaction_reference, p.amount, p.provider, requester.email AS requested_by_email, reviewer.email AS reviewed_by_email
            FROM refund_requests rr
            JOIN payments p ON p.id = rr.payment_id
            JOIN users requester ON requester.id = rr.requested_by
            LEFT JOIN users reviewer ON reviewer.id = rr.reviewed_by
            ORDER BY rr.created_at DESC');
        return $stmt->fetchAll();
    }

    public function countPending(): int
    {
        $stmt = $this->db->query('SELECT COUNT(*) FROM refund_requests WHERE status = "pending"');
        return (int) $stmt->fetchColumn();
    }

    public function findByPaymentAndUser(int $paymentId, int $userId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM refund_requests WHERE payment_id = :payment_id AND requested_by = :user_id LIMIT 1');
        $stmt->execute(['payment_id' => $paymentId, 'user_id' => $userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function updateStatus(int $id, string $status, int $reviewedBy): bool
    {
        $stmt = $this->db->prepare('UPDATE refund_requests SET status = :status, reviewed_by = :reviewed_by, updated_at = NOW() WHERE id = :id');
        return $stmt->execute([
            'status' => $status,
            'reviewed_by' => $reviewedBy,
            'id' => $id,
        ]);
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM refund_requests WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
}
