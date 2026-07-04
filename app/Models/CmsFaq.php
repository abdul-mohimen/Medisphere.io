<?php
namespace App\Models;

class CmsFaq extends BaseModel
{
    public function all(string $status = 'published'): array
    {
        if ($status === 'all') {
            return $this->db->query('SELECT * FROM cms_faqs ORDER BY sort_order ASC, id DESC')->fetchAll();
        }

        $stmt = $this->db->prepare('SELECT * FROM cms_faqs WHERE status = :status ORDER BY sort_order ASC, id DESC');
        $stmt->execute(['status' => $status]);
        return $stmt->fetchAll();
    }

    public function groupedPublished(): array
    {
        $items = $this->all('published');
        $grouped = [];
        foreach ($items as $item) {
            $category = $item['category'] ?: 'General';
            $grouped[$category][] = $item;
        }
        return $grouped;
    }

    public function searchPublic(string $query): array
    {
        $stmt = $this->db->prepare('SELECT * FROM cms_faqs WHERE status = "published" AND (question LIKE :query_question OR answer LIKE :query_answer OR category LIKE :query_category) ORDER BY sort_order ASC, id DESC');
        $term = '%' . $query . '%';
        $stmt->execute([
            'query_question' => $term,
            'query_answer' => $term,
            'query_category' => $term,
        ]);
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM cms_faqs WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function save(array $data): int
    {
        if (!empty($data['id'])) {
            $stmt = $this->db->prepare('UPDATE cms_faqs SET question = :question, answer = :answer, category = :category, status = :status, sort_order = :sort_order, updated_at = NOW() WHERE id = :id');
            $stmt->execute([
                'id' => $data['id'],
                'question' => $data['question'],
                'answer' => $data['answer'],
                'category' => $data['category'] ?? 'General',
                'status' => $data['status'] ?? 'published',
                'sort_order' => $data['sort_order'] ?? 0,
            ]);
            return (int) $data['id'];
        }

        $stmt = $this->db->prepare('INSERT INTO cms_faqs (question, answer, category, status, sort_order, created_at, updated_at) VALUES (:question, :answer, :category, :status, :sort_order, NOW(), NOW())');
        $stmt->execute([
            'question' => $data['question'],
            'answer' => $data['answer'],
            'category' => $data['category'] ?? 'General',
            'status' => $data['status'] ?? 'published',
            'sort_order' => $data['sort_order'] ?? 0,
        ]);
        return (int) $this->db->lastInsertId();
    }
}
