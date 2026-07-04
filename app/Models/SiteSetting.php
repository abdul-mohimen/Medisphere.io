<?php
namespace App\Models;

class SiteSetting extends BaseModel
{
    public function all(): array
    {
        return $this->db->query('SELECT * FROM site_settings ORDER BY setting_key ASC')->fetchAll();
    }

    public function get(string $key, ?string $default = null): ?string
    {
        $stmt = $this->db->prepare('SELECT setting_value FROM site_settings WHERE setting_key = :setting_key LIMIT 1');
        $stmt->execute(['setting_key' => $key]);
        $value = $stmt->fetchColumn();
        return $value !== false ? (string) $value : $default;
    }

    public function save(string $key, string $value, string $group = 'general'): bool
    {
        $stmt = $this->db->prepare('INSERT INTO site_settings (setting_key, setting_value, setting_group, updated_at) VALUES (:setting_key, :setting_value, :setting_group, NOW()) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), setting_group = VALUES(setting_group), updated_at = NOW()');
        return $stmt->execute([
            'setting_key' => $key,
            'setting_value' => $value,
            'setting_group' => $group,
        ]);
    }
}
