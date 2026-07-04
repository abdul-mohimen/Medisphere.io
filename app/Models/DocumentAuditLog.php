<?php
namespace App\Models;

class DocumentAuditLog extends BaseModel
{
    public function add(string $documentType, int $documentId, ?int $actorId, string $action, ?string $contextData = null): int
    {
        $stmt = $this->db->prepare('INSERT INTO document_audit_logs (document_type, document_id, actor_id, action, context_data, created_at) VALUES (:document_type, :document_id, :actor_id, :action, :context_data, NOW())');
        $stmt->execute([
            'document_type' => $documentType,
            'document_id' => $documentId,
            'actor_id' => $actorId,
            'action' => $action,
            'context_data' => $contextData,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function forDocument(string $documentType, int $documentId): array
    {
        $stmt = $this->db->prepare('SELECT dal.*, u.email AS actor_email FROM document_audit_logs dal LEFT JOIN users u ON u.id = dal.actor_id WHERE dal.document_type = :document_type AND dal.document_id = :document_id ORDER BY dal.created_at DESC');
        $stmt->execute([
            'document_type' => $documentType,
            'document_id' => $documentId,
        ]);
        return $stmt->fetchAll();
    }
}
