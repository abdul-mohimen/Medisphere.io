<?php
namespace App\Models;

class ConsultationSignal extends BaseModel
{
    public function create(array $data): int
    {
        $stmt = $this->db->prepare('INSERT INTO consultation_signals (session_id, sender_id, signal_type, payload, created_at) VALUES (:session_id, :sender_id, :signal_type, :payload, NOW())');
        $stmt->execute([
            'session_id' => $data['session_id'],
            'sender_id' => $data['sender_id'],
            'signal_type' => $data['signal_type'],
            'payload' => $data['payload'] ?? null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function fetchSince(int $sessionId, int $afterId, int $excludeSenderId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM consultation_signals WHERE session_id = :session_id AND id > :after_id AND sender_id != :exclude_sender_id ORDER BY id ASC');
        $stmt->execute([
            'session_id' => $sessionId,
            'after_id' => $afterId,
            'exclude_sender_id' => $excludeSenderId,
        ]);
        return $stmt->fetchAll();
    }

    public function clearForSession(int $sessionId): bool
    {
        $stmt = $this->db->prepare('DELETE FROM consultation_signals WHERE session_id = :session_id');
        return $stmt->execute(['session_id' => $sessionId]);
    }
}
