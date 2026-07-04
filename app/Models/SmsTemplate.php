<?php
namespace App\Models;

class SmsTemplate extends BaseModel
{
    public function all(): array
    {
        return $this->db->query('SELECT * FROM sms_templates ORDER BY template_key ASC')->fetchAll();
    }

    public function findByKey(string $templateKey): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM sms_templates WHERE template_key = :template_key LIMIT 1');
        $stmt->execute(['template_key' => $templateKey]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function upsert(string $templateKey, string $bodyText, string $status = 'active'): bool
    {
        $stmt = $this->db->prepare('INSERT INTO sms_templates (template_key, body_text, status, updated_at) VALUES (:template_key, :body_text, :status, NOW()) ON DUPLICATE KEY UPDATE body_text = VALUES(body_text), status = VALUES(status), updated_at = NOW()');
        return $stmt->execute([
            'template_key' => $templateKey,
            'body_text' => $bodyText,
            'status' => $status,
        ]);
    }
}
