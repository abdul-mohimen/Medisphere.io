<?php
namespace App\Models;

class CmsArticle extends BaseModel
{
    public function recent(string $type = 'blog', int $limit = 6): array
    {
        $stmt = $this->db->prepare('SELECT * FROM cms_articles WHERE type = :type AND status = "published" ORDER BY published_at DESC, id DESC LIMIT ' . (int) $limit);
        $stmt->execute(['type' => $type]);
        return $stmt->fetchAll();
    }

    public function byType(string $type, string $status = 'published'): array
    {
        if ($status === 'all') {
            $stmt = $this->db->prepare('SELECT * FROM cms_articles WHERE type = :type ORDER BY created_at DESC');
            $stmt->execute(['type' => $type]);
            return $stmt->fetchAll();
        }

        $stmt = $this->db->prepare('SELECT * FROM cms_articles WHERE type = :type AND status = :status ORDER BY published_at DESC, id DESC');
        $stmt->execute(['type' => $type, 'status' => $status]);
        return $stmt->fetchAll();
    }

    public function all(string $status = 'all'): array
    {
        if ($status === 'all') {
            return $this->db->query('SELECT * FROM cms_articles ORDER BY created_at DESC')->fetchAll();
        }

        $stmt = $this->db->prepare('SELECT * FROM cms_articles WHERE status = :status ORDER BY published_at DESC, id DESC');
        $stmt->execute(['status' => $status]);
        return $stmt->fetchAll();
    }

    public function searchPublic(string $query): array
    {
        $stmt = $this->db->prepare('SELECT * FROM cms_articles WHERE status = "published" AND (title LIKE :query_title OR excerpt LIKE :query_excerpt OR content LIKE :query_content OR type LIKE :query_type) ORDER BY published_at DESC, id DESC');
        $term = '%' . $query . '%';
        $stmt->execute([
            'query_title' => $term,
            'query_excerpt' => $term,
            'query_content' => $term,
            'query_type' => $term,
        ]);
        return $stmt->fetchAll();
    }

    public function related(int $id, string $type, int $limit = 3): array
    {
        $stmt = $this->db->prepare('SELECT * FROM cms_articles WHERE id != :id AND type = :type AND status = "published" ORDER BY published_at DESC, id DESC LIMIT ' . (int) $limit);
        $stmt->execute(['id' => $id, 'type' => $type]);
        return $stmt->fetchAll();
    }

    public function countPublishedByType(string $type): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM cms_articles WHERE type = :type AND status = "published"');
        $stmt->execute(['type' => $type]);
        return (int) $stmt->fetchColumn();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM cms_articles WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findBySlug(string $slug): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM cms_articles WHERE slug = :slug LIMIT 1');
        $stmt->execute(['slug' => $slug]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function save(array $data): int
    {
        $publishedAt = $data['status'] === 'published'
            ? ($data['published_at'] ?: date('Y-m-d H:i:s'))
            : null;

        $payload = [
            'slug' => $data['slug'],
            'title' => $data['title'],
            'excerpt' => $data['excerpt'] ?? null,
            'content' => $data['content'],
            'type' => $data['type'],
            'status' => $data['status'],
            'author_id' => $data['author_id'] ?? null,
            'published_at' => $publishedAt,
            'featured_image_path' => $data['featured_image_path'] ?? null,
            'seo_title' => $data['seo_title'] ?? null,
            'seo_description' => $data['seo_description'] ?? null,
        ];

        if (!empty($data['id'])) {
            $stmt = $this->db->prepare('UPDATE cms_articles SET slug = :slug, title = :title, excerpt = :excerpt, content = :content, type = :type, status = :status, author_id = :author_id, published_at = :published_at, featured_image_path = :featured_image_path, seo_title = :seo_title, seo_description = :seo_description, updated_at = NOW() WHERE id = :id');
            $stmt->execute(array_merge($payload, ['id' => $data['id']]));
            return (int) $data['id'];
        }

        $stmt = $this->db->prepare('INSERT INTO cms_articles (slug, title, excerpt, content, type, status, author_id, published_at, featured_image_path, seo_title, seo_description, created_at, updated_at) VALUES (:slug, :title, :excerpt, :content, :type, :status, :author_id, :published_at, :featured_image_path, :seo_title, :seo_description, NOW(), NOW())');
        $stmt->execute($payload);
        return (int) $this->db->lastInsertId();
    }
}
