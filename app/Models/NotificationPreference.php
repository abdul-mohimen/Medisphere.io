<?php
namespace App\Models;

class NotificationPreference extends BaseModel
{
    public function forUser(int $userId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM notification_preferences WHERE user_id = :user_id LIMIT 1');
        $stmt->execute(['user_id' => $userId]);
        $row = $stmt->fetch();
        if ($row) {
            return $row;
        }

        $this->createDefault($userId);
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetch() ?: [];
    }

    public function createDefault(int $userId): bool
    {
        $stmt = $this->db->prepare('INSERT IGNORE INTO notification_preferences (user_id, email_enabled, sms_enabled, in_app_enabled, appointment_updates, payment_updates, security_updates, marketing_updates, preferred_language, updated_at) VALUES (:user_id, 1, 0, 1, 1, 1, 1, 0, :preferred_language, NOW())');
        return $stmt->execute([
            'user_id' => $userId,
            'preferred_language' => 'en',
        ]);
    }

    public function updateForUser(int $userId, array $data): bool
    {
        $stmt = $this->db->prepare('INSERT INTO notification_preferences (user_id, email_enabled, sms_enabled, in_app_enabled, appointment_updates, payment_updates, security_updates, marketing_updates, preferred_language, updated_at) VALUES (:user_id, :email_enabled, :sms_enabled, :in_app_enabled, :appointment_updates, :payment_updates, :security_updates, :marketing_updates, :preferred_language, NOW()) ON DUPLICATE KEY UPDATE email_enabled = VALUES(email_enabled), sms_enabled = VALUES(sms_enabled), in_app_enabled = VALUES(in_app_enabled), appointment_updates = VALUES(appointment_updates), payment_updates = VALUES(payment_updates), security_updates = VALUES(security_updates), marketing_updates = VALUES(marketing_updates), preferred_language = VALUES(preferred_language), updated_at = NOW()');
        return $stmt->execute([
            'user_id' => $userId,
            'email_enabled' => $data['email_enabled'],
            'sms_enabled' => $data['sms_enabled'],
            'in_app_enabled' => $data['in_app_enabled'],
            'appointment_updates' => $data['appointment_updates'],
            'payment_updates' => $data['payment_updates'],
            'security_updates' => $data['security_updates'],
            'marketing_updates' => $data['marketing_updates'],
            'preferred_language' => $data['preferred_language'] ?? 'en',
        ]);
    }
}
