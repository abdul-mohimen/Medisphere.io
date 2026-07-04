<?php
namespace App\Models;

class DoctorHospitalRequest extends BaseModel
{
    public function create(array $data): int
    {
        $stmt = $this->db->prepare('INSERT INTO doctor_hospital_requests (doctor_id, hospital_id, status, submitted_date, cover_letter, credentials, preferred_departments) VALUES (:doctor_id, :hospital_id, :status, NOW(), :cover_letter, :credentials, :preferred_departments)');
        $stmt->execute([
            'doctor_id' => $data['doctor_id'],
            'hospital_id' => $data['hospital_id'],
            'status' => $data['status'] ?? 'pending',
            'cover_letter' => $data['cover_letter'] ?? null,
            'credentials' => $data['credentials'] ?? null,
            'preferred_departments' => $data['preferred_departments'] ?? null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function byDoctor(int $doctorUserId): array
    {
        $stmt = $this->db->prepare('SELECT r.*, h.name AS hospital_name FROM doctor_hospital_requests r JOIN hospitals h ON h.id = r.hospital_id WHERE r.doctor_id = :doctor_id ORDER BY submitted_date DESC');
        $stmt->execute(['doctor_id' => $doctorUserId]);
        return $stmt->fetchAll();
    }

    public function byHospital(int $hospitalId): array
    {
        $stmt = $this->db->prepare('SELECT r.*, d.name AS doctor_name, d.specialization, d.license_number, u.email AS doctor_email
            FROM doctor_hospital_requests r
            JOIN doctors d ON d.user_id = r.doctor_id
            JOIN users u ON u.id = r.doctor_id
            WHERE r.hospital_id = :hospital_id
            ORDER BY r.status = "pending" DESC, r.submitted_date DESC');
        $stmt->execute(['hospital_id' => $hospitalId]);
        return $stmt->fetchAll();
    }

    public function find(int $id, int $hospitalId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM doctor_hospital_requests WHERE id = :id AND hospital_id = :hospital_id LIMIT 1');
        $stmt->execute(['id' => $id, 'hospital_id' => $hospitalId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function updateStatus(int $id, int $hospitalId, string $status): bool
    {
        $stmt = $this->db->prepare('UPDATE doctor_hospital_requests SET status = :status WHERE id = :id AND hospital_id = :hospital_id');
        return $stmt->execute([
            'status' => $status,
            'id' => $id,
            'hospital_id' => $hospitalId,
        ]);
    }

    public function pendingCountByHospital(int $hospitalId): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM doctor_hospital_requests WHERE hospital_id = :hospital_id AND status = "pending"');
        $stmt->execute(['hospital_id' => $hospitalId]);
        return (int) $stmt->fetchColumn();
    }
}
