<?php
namespace App\Models;

class ConsultationFeedback extends BaseModel
{
    public function create(array $data): int
    {
        $stmt = $this->db->prepare('INSERT INTO consultation_feedback (session_id, reviewer_id, rating, review_text, created_at) VALUES (:session_id, :reviewer_id, :rating, :review_text, NOW())');
        $stmt->execute([
            'session_id' => $data['session_id'],
            'reviewer_id' => $data['reviewer_id'],
            'rating' => $data['rating'],
            'review_text' => $data['review_text'] ?? null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function forSession(int $sessionId): array
    {
        $stmt = $this->db->prepare('SELECT cf.*, u.email AS reviewer_email FROM consultation_feedback cf JOIN users u ON u.id = cf.reviewer_id WHERE cf.session_id = :session_id ORDER BY cf.created_at DESC');
        $stmt->execute(['session_id' => $sessionId]);
        return $stmt->fetchAll();
    }

    public function findBySessionAndReviewer(int $sessionId, int $reviewerId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM consultation_feedback WHERE session_id = :session_id AND reviewer_id = :reviewer_id LIMIT 1');
        $stmt->execute(['session_id' => $sessionId, 'reviewer_id' => $reviewerId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
}
