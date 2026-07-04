<?php
namespace App\Models;

class ComplianceIncident extends BaseModel
{
    public function create(array $data): int
    {
        $stmt = $this->db->prepare('INSERT INTO compliance_incidents (title, incident_type, severity, status, description, action_taken, reported_by, resolved_by, created_at, updated_at) VALUES (:title, :incident_type, :severity, :status, :description, :action_taken, :reported_by, :resolved_by, NOW(), NOW())');
        $stmt->execute([
            'title' => $data['title'],
            'incident_type' => $data['incident_type'],
            'severity' => $data['severity'] ?? 'medium',
            'status' => $data['status'] ?? 'open',
            'description' => $data['description'] ?? null,
            'action_taken' => $data['action_taken'] ?? null,
            'reported_by' => $data['reported_by'] ?? null,
            'resolved_by' => $data['resolved_by'] ?? null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function all(): array
    {
        return $this->db->query('SELECT ci.*, u.email AS reporter_email, ru.email AS resolver_email FROM compliance_incidents ci LEFT JOIN users u ON u.id = ci.reported_by LEFT JOIN users ru ON ru.id = ci.resolved_by ORDER BY ci.created_at DESC')->fetchAll();
    }

    public function openCount(): int
    {
        $stmt = $this->db->query('SELECT COUNT(*) FROM compliance_incidents WHERE status IN ("open", "investigating")');
        return (int) $stmt->fetchColumn();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM compliance_incidents WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->db->prepare('UPDATE compliance_incidents SET title = :title, incident_type = :incident_type, severity = :severity, status = :status, description = :description, action_taken = :action_taken, resolved_by = :resolved_by, updated_at = NOW() WHERE id = :id');
        return $stmt->execute([
            'id' => $id,
            'title' => $data['title'],
            'incident_type' => $data['incident_type'],
            'severity' => $data['severity'],
            'status' => $data['status'],
            'description' => $data['description'] ?? null,
            'action_taken' => $data['action_taken'] ?? null,
            'resolved_by' => $data['resolved_by'] ?? null,
        ]);
    }
}
