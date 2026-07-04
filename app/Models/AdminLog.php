<?php
namespace App\Models;

class AdminLog extends BaseModel
{
    public function add(int $adminId, string $action): bool
    {
        $stmt = $this->db->prepare('INSERT INTO admin_logs (admin_id, action, timestamp) VALUES (:admin_id, :action, NOW())');
        return $stmt->execute(['admin_id' => $adminId, 'action' => $action]);
    }
}
