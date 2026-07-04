<?php
namespace App\Models;

class MediaAsset extends BaseModel
{
    public function create(array $data): int
    {
        $stmt = $this->db->prepare('INSERT INTO media_assets (title, file_path, mime_type, file_size, uploaded_by, created_at) VALUES (:title, :file_path, :mime_type, :file_size, :uploaded_by, NOW())');
        $stmt->execute([
            'title' => $data['title'],
            'file_path' => $data['file_path'],
            'mime_type' => $data['mime_type'],
            'file_size' => $data['file_size'],
            'uploaded_by' => $data['uploaded_by'] ?? null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function recent(int $limit = 12): array
    {
        $stmt = $this->db->prepare('SELECT ma.*, u.email AS uploader_email FROM media_assets ma LEFT JOIN users u ON u.id = ma.uploaded_by ORDER BY ma.created_at DESC LIMIT ' . (int) $limit);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function all(): array
    {
        return $this->db->query('SELECT ma.*, u.email AS uploader_email FROM media_assets ma LEFT JOIN users u ON u.id = ma.uploaded_by ORDER BY ma.created_at DESC')->fetchAll();
    }
}
