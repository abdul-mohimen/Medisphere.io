<?php
namespace App\Models;

class HospitalInventoryItem extends BaseModel
{
    public function create(array $data): int
    {
        $stmt = $this->db->prepare('INSERT INTO hospital_inventory_items (hospital_id, item_name, category, quantity, status, next_maintenance, notes, created_at, updated_at) VALUES (:hospital_id, :item_name, :category, :quantity, :status, :next_maintenance, :notes, NOW(), NOW())');
        $stmt->execute([
            'hospital_id' => $data['hospital_id'],
            'item_name' => $data['item_name'],
            'category' => $data['category'] ?? null,
            'quantity' => $data['quantity'] ?? 0,
            'status' => $data['status'] ?? 'available',
            'next_maintenance' => $data['next_maintenance'] ?: null,
            'notes' => $data['notes'] ?? null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, int $hospitalId, array $data): bool
    {
        $stmt = $this->db->prepare('UPDATE hospital_inventory_items SET item_name = :item_name, category = :category, quantity = :quantity, status = :status, next_maintenance = :next_maintenance, notes = :notes, updated_at = NOW() WHERE id = :id AND hospital_id = :hospital_id');
        return $stmt->execute([
            'id' => $id,
            'hospital_id' => $hospitalId,
            'item_name' => $data['item_name'],
            'category' => $data['category'] ?? null,
            'quantity' => $data['quantity'] ?? 0,
            'status' => $data['status'] ?? 'available',
            'next_maintenance' => $data['next_maintenance'] ?: null,
            'notes' => $data['notes'] ?? null,
        ]);
    }

    public function allByHospital(int $hospitalId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM hospital_inventory_items WHERE hospital_id = :hospital_id ORDER BY status = "critical" DESC, status = "maintenance" DESC, item_name ASC');
        $stmt->execute(['hospital_id' => $hospitalId]);
        return $stmt->fetchAll();
    }

    public function countByHospital(int $hospitalId): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM hospital_inventory_items WHERE hospital_id = :hospital_id');
        $stmt->execute(['hospital_id' => $hospitalId]);
        return (int) $stmt->fetchColumn();
    }

    public function maintenanceDueCount(int $hospitalId): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM hospital_inventory_items WHERE hospital_id = :hospital_id AND (status IN ("maintenance", "critical") OR (next_maintenance IS NOT NULL AND next_maintenance <= CURDATE()))');
        $stmt->execute(['hospital_id' => $hospitalId]);
        return (int) $stmt->fetchColumn();
    }
}
