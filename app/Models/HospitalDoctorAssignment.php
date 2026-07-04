<?php
namespace App\Models;

class HospitalDoctorAssignment extends BaseModel
{
    public function upsert(array $data): bool
    {
        $stmt = $this->db->prepare('INSERT INTO hospital_doctor_assignments (hospital_id, doctor_id, department_id, privileges, status, assigned_at, updated_at) VALUES (:hospital_id, :doctor_id, :department_id, :privileges, :status, NOW(), NOW()) ON DUPLICATE KEY UPDATE department_id = VALUES(department_id), privileges = VALUES(privileges), status = VALUES(status), updated_at = NOW()');
        return $stmt->execute([
            'hospital_id' => $data['hospital_id'],
            'doctor_id' => $data['doctor_id'],
            'department_id' => $data['department_id'] ?: null,
            'privileges' => $data['privileges'] ?? null,
            'status' => $data['status'] ?? 'active',
        ]);
    }

    public function byHospital(int $hospitalId): array
    {
        $stmt = $this->db->prepare('SELECT hda.*, d.name AS doctor_name, d.specialization, hd.name AS department_name, u.email AS doctor_email
            FROM hospital_doctor_assignments hda
            JOIN doctors d ON d.user_id = hda.doctor_id
            JOIN users u ON u.id = hda.doctor_id
            LEFT JOIN hospital_departments hd ON hd.id = hda.department_id
            WHERE hda.hospital_id = :hospital_id
            ORDER BY hda.status = "active" DESC, d.name ASC');
        $stmt->execute(['hospital_id' => $hospitalId]);
        return $stmt->fetchAll();
    }

    public function find(int $id, int $hospitalId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM hospital_doctor_assignments WHERE id = :id AND hospital_id = :hospital_id LIMIT 1');
        $stmt->execute(['id' => $id, 'hospital_id' => $hospitalId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function update(int $id, int $hospitalId, array $data): bool
    {
        $stmt = $this->db->prepare('UPDATE hospital_doctor_assignments SET department_id = :department_id, privileges = :privileges, status = :status, updated_at = NOW() WHERE id = :id AND hospital_id = :hospital_id');
        return $stmt->execute([
            'id' => $id,
            'hospital_id' => $hospitalId,
            'department_id' => $data['department_id'] ?: null,
            'privileges' => $data['privileges'] ?? null,
            'status' => $data['status'] ?? 'active',
        ]);
    }

    public function countByHospital(int $hospitalId): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM hospital_doctor_assignments WHERE hospital_id = :hospital_id');
        $stmt->execute(['hospital_id' => $hospitalId]);
        return (int) $stmt->fetchColumn();
    }
}
