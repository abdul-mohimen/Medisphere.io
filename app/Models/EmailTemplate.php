<?php
namespace App\Models;

class EmailTemplate extends BaseModel
{
    public function all(): array
    {
        return $this->db->query('SELECT * FROM email_templates ORDER BY template_key ASC')->fetchAll();
    }

    public function findByKey(string $templateKey): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM email_templates WHERE template_key = :template_key LIMIT 1');
        $stmt->execute(['template_key' => $templateKey]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function upsert(string $templateKey, string $subject, string $bodyHtml, string $bodyText, string $status = 'active'): bool
    {
        $stmt = $this->db->prepare('INSERT INTO email_templates (template_key, subject, body_html, body_text, status, updated_at) VALUES (:template_key, :subject, :body_html, :body_text, :status, NOW()) ON DUPLICATE KEY UPDATE subject = VALUES(subject), body_html = VALUES(body_html), body_text = VALUES(body_text), status = VALUES(status), updated_at = NOW()');
        return $stmt->execute([
            'template_key' => $templateKey,
            'subject' => $subject,
            'body_html' => $bodyHtml,
            'body_text' => $bodyText,
            'status' => $status,
        ]);
    }
}
