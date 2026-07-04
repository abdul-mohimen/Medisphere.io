<?php
namespace App\Models;

class PolicyDocument extends BaseModel
{
    public function publicAll(): array
    {
        return $this->db->query('SELECT * FROM policy_documents WHERE status = "published" AND is_public = 1 ORDER BY effective_date DESC, id DESC')->fetchAll();
    }

    public function all(): array
    {
        return $this->db->query('SELECT * FROM policy_documents ORDER BY effective_date DESC, id DESC')->fetchAll();
    }

    public function searchPublic(string $query): array
    {
        $stmt = $this->db->prepare('SELECT * FROM policy_documents WHERE status = "published" AND is_public = 1 AND (title LIKE :query_title OR content LIKE :query_content OR category LIKE :query_category) ORDER BY effective_date DESC, id DESC');
        $term = '%' . $query . '%';
        $stmt->execute([
            'query_title' => $term,
            'query_content' => $term,
            'query_category' => $term,
        ]);
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM policy_documents WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findBySlug(string $slug): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM policy_documents WHERE slug = :slug LIMIT 1');
        $stmt->execute(['slug' => $slug]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function save(array $data): int
    {
        $payload = [
            'slug' => $data['slug'],
            'title' => $data['title'],
            'category' => $data['category'],
            'content' => $data['content'],
            'status' => $data['status'] ?? 'published',
            'version_label' => $data['version_label'] ?? 'v1.0',
            'effective_date' => $data['effective_date'] ?: date('Y-m-d'),
            'requires_acknowledgement' => !empty($data['requires_acknowledgement']) ? 1 : 0,
            'is_public' => !empty($data['is_public']) ? 1 : 0,
        ];

        if (!empty($data['id'])) {
            $stmt = $this->db->prepare('UPDATE policy_documents SET slug = :slug, title = :title, category = :category, content = :content, status = :status, version_label = :version_label, effective_date = :effective_date, requires_acknowledgement = :requires_acknowledgement, is_public = :is_public, updated_at = NOW() WHERE id = :id');
            $stmt->execute(array_merge($payload, ['id' => $data['id']]));
            return (int) $data['id'];
        }

        $stmt = $this->db->prepare('INSERT INTO policy_documents (slug, title, category, content, status, version_label, effective_date, requires_acknowledgement, is_public, created_at, updated_at) VALUES (:slug, :title, :category, :content, :status, :version_label, :effective_date, :requires_acknowledgement, :is_public, NOW(), NOW())');
        $stmt->execute($payload);
        return (int) $this->db->lastInsertId();
    }

    public function pendingAcknowledgementsForUser(int $userId): array
    {
        $stmt = $this->db->prepare('SELECT pd.* FROM policy_documents pd
            LEFT JOIN user_policy_acknowledgements upa ON upa.policy_id = pd.id AND upa.user_id = :user_id
            WHERE pd.status = "published" AND pd.requires_acknowledgement = 1 AND upa.id IS NULL
            ORDER BY pd.effective_date DESC');
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll();
    }
}
