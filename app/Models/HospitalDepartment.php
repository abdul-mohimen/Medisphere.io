<?php
namespace App\Models;

class HospitalDepartment extends BaseModel
{
    public function create(array $data): int
    {
        $stmt = $this->db->prepare('INSERT INTO hospital_departments (hospital_id, name, description, head_doctor_id, timings, status, created_at, updated_at) VALUES (:hospital_id, :name, :description, :head_doctor_id, :timings, :status, NOW(), NOW())');
        $stmt->execute([
            'hospital_id' => $data['hospital_id'],
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'head_doctor_id' => $data['head_doctor_id'] ?: null,
            'timings' => $data['timings'] ?? null,
            'status' => $data['status'] ?? 'active',
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, int $hospitalId, array $data): bool
    {
        $stmt = $this->db->prepare('UPDATE hospital_departments SET name = :name, description = :description, head_doctor_id = :head_doctor_id, timings = :timings, status = :status, updated_at = NOW() WHERE id = :id AND hospital_id = :hospital_id');
        return $stmt->execute([
            'id' => $id,
            'hospital_id' => $hospitalId,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'head_doctor_id' => $data['head_doctor_id'] ?: null,
            'timings' => $data['timings'] ?? null,
            'status' => $data['status'] ?? 'active',
        ]);
    }

    public function allByHospital(int $hospitalId): array
    {
        $stmt = $this->db->prepare('SELECT hd.*, d.name AS head_doctor_name FROM hospital_departments hd LEFT JOIN doctors d ON d.user_id = hd.head_doctor_id WHERE hd.hospital_id = :hospital_id ORDER BY hd.status = "active" DESC, hd.name ASC');
        $stmt->execute(['hospital_id' => $hospitalId]);
        return $stmt->fetchAll();
    }

    public function find(int $id, int $hospitalId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM hospital_departments WHERE id = :id AND hospital_id = :hospital_id LIMIT 1');
        $stmt->execute(['id' => $id, 'hospital_id' => $hospitalId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function countByHospital(int $hospitalId): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM hospital_departments WHERE hospital_id = :hospital_id');
        $stmt->execute(['hospital_id' => $hospitalId]);
        return (int) $stmt->fetchColumn();
    }
}
