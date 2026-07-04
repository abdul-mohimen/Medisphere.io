<?php
namespace App\Models;

class ChatMessage extends BaseModel
{
    public function send(int $senderId, int $receiverId, string $message): int
    {
        $stmt = $this->db->prepare('INSERT INTO chat_messages (sender_id, receiver_id, message, timestamp, read_status) VALUES (:sender_id, :receiver_id, :message, NOW(), 0)');
        $stmt->execute([
            'sender_id' => $senderId,
            'receiver_id' => $receiverId,
            'message' => $message,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function conversation(int $userA, int $userB): array
    {
        $stmt = $this->db->prepare('SELECT * FROM chat_messages WHERE (sender_id = :userA_sender AND receiver_id = :userB_receiver) OR (sender_id = :userB_sender AND receiver_id = :userA_receiver) ORDER BY timestamp ASC');
        $stmt->execute([
            'userA_sender' => $userA,
            'userB_receiver' => $userB,
            'userB_sender' => $userB,
            'userA_receiver' => $userA,
        ]);
        return $stmt->fetchAll();
    }

    public function markConversationRead(int $viewerId, int $contactId): bool
    {
        $stmt = $this->db->prepare('UPDATE chat_messages SET read_status = 1 WHERE sender_id = :contact_id AND receiver_id = :viewer_id AND read_status = 0');
        return $stmt->execute([
            'contact_id' => $contactId,
            'viewer_id' => $viewerId,
        ]);
    }

    public function contactsForUser(int $userId): array
    {
        $stmt = $this->db->prepare('SELECT
                u.id,
                u.email,
                u.user_type,
                u.status,
                COALESCE(p.name, d.name, h.name, u.email) AS name,
                COALESCE(p.phone, h.phone) AS phone,
                d.specialization,
                h.city,
                h.country,
                MAX(m.timestamp) AS last_at,
                SUM(CASE WHEN m.receiver_id = :viewer_id_unread AND m.sender_id = u.id AND m.read_status = 0 THEN 1 ELSE 0 END) AS unread_count
            FROM users u
            JOIN chat_messages m ON (u.id = m.sender_id AND m.receiver_id = :user_id_receiver) OR (u.id = m.receiver_id AND m.sender_id = :user_id_sender)
            LEFT JOIN patients p ON p.user_id = u.id
            LEFT JOIN doctors d ON d.user_id = u.id
            LEFT JOIN hospitals h ON h.user_id = u.id
            WHERE u.id != :user_id2
            GROUP BY u.id, u.email, u.user_type, u.status, p.name, p.phone, d.name, d.specialization, h.name, h.phone, h.city, h.country
            ORDER BY last_at DESC, name ASC');
        $stmt->execute([
            'user_id_receiver' => $userId,
            'user_id_sender' => $userId,
            'user_id2' => $userId,
            'viewer_id_unread' => $userId,
        ]);
        return $stmt->fetchAll();
    }
}
