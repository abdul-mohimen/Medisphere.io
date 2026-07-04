<?php
namespace App\Models;

class DataPrivacyRequest extends BaseModel
{
    public function create(array $data): int
    {
        $stmt = $this->db->prepare('INSERT INTO data_privacy_requests (user_id, request_type, description, status, admin_notes, resolved_by, resolved_at, created_at, updated_at) VALUES (:user_id, :request_type, :description, :status, :admin_notes, :resolved_by, :resolved_at, NOW(), NOW())');
        $stmt->execute([
            'user_id' => $data['user_id'],
            'request_type' => $data['request_type'],
            'description' => $data['description'] ?? null,
            'status' => $data['status'] ?? 'submitted',
            'admin_notes' => $data['admin_notes'] ?? null,
            'resolved_by' => $data['resolved_by'] ?? null,
            'resolved_at' => $data['resolved_at'] ?? null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function byUser(int $userId): array
    {
        $stmt = $this->db->prepare('SELECT dpr.*, u.email AS resolver_email FROM data_privacy_requests dpr LEFT JOIN users u ON u.id = dpr.resolved_by WHERE dpr.user_id = :user_id ORDER BY dpr.created_at DESC');
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll();
    }

    public function all(): array
    {
        return $this->db->query('SELECT dpr.*, u.email AS requester_email, ru.email AS resolver_email FROM data_privacy_requests dpr JOIN users u ON u.id = dpr.user_id LEFT JOIN users ru ON ru.id = dpr.resolved_by ORDER BY dpr.created_at DESC')->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM data_privacy_requests WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function updateStatus(int $id, array $data): bool
    {
        $stmt = $this->db->prepare('UPDATE data_privacy_requests SET status = :status, admin_notes = :admin_notes, resolved_by = :resolved_by, resolved_at = :resolved_at, updated_at = NOW() WHERE id = :id');
        return $stmt->execute([
            'id' => $id,
            'status' => $data['status'],
            'admin_notes' => $data['admin_notes'] ?? null,
            'resolved_by' => $data['resolved_by'] ?? null,
            'resolved_at' => $data['resolved_at'] ?? null,
        ]);
    }

    public function pendingCount(): int
    {
        $stmt = $this->db->query('SELECT COUNT(*) FROM data_privacy_requests WHERE status IN ("submitted", "in_review")');
        return (int) $stmt->fetchColumn();
    }
}
