<?php
namespace App\Models;

class Prescription extends BaseModel
{
    public function create(array $data): int
    {
        $stmt = $this->db->prepare('INSERT INTO prescriptions (appointment_id, doctor_id, patient_id, title, diagnosis, medications_json, notes, advice, follow_up_date, digital_signature, signature_snapshot_name, signature_image_path, verification_code, integrity_hash, status, created_at, updated_at) VALUES (:appointment_id, :doctor_id, :patient_id, :title, :diagnosis, :medications_json, :notes, :advice, :follow_up_date, :digital_signature, :signature_snapshot_name, :signature_image_path, :verification_code, :integrity_hash, :status, NOW(), NOW())');
        $stmt->execute([
            'appointment_id' => $data['appointment_id'] ?? null,
            'doctor_id' => $data['doctor_id'],
            'patient_id' => $data['patient_id'],
            'title' => $data['title'],
            'diagnosis' => $data['diagnosis'] ?? null,
            'medications_json' => $data['medications_json'] ?? '[]',
            'notes' => $data['notes'] ?? null,
            'advice' => $data['advice'] ?? null,
            'follow_up_date' => $data['follow_up_date'] ?: null,
            'digital_signature' => $data['digital_signature'] ?? null,
            'signature_snapshot_name' => $data['signature_snapshot_name'] ?? null,
            'signature_image_path' => $data['signature_image_path'] ?? null,
            'verification_code' => $data['verification_code'],
            'integrity_hash' => $data['integrity_hash'],
            'status' => $data['status'] ?? 'draft',
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT pr.*, d.name AS doctor_name, d.specialization, d.license_number, pt.name AS patient_name, u.email AS patient_email, a.date AS appointment_date, a.time AS appointment_time
            FROM prescriptions pr
            LEFT JOIN doctors d ON d.user_id = pr.doctor_id
            LEFT JOIN patients pt ON pt.user_id = pr.patient_id
            LEFT JOIN users u ON u.id = pr.patient_id
            LEFT JOIN appointments a ON a.id = pr.appointment_id
            WHERE pr.id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findByVerificationCode(string $verificationCode): ?array
    {
        $stmt = $this->db->prepare('SELECT pr.*, d.name AS doctor_name, d.specialization, d.license_number, pt.name AS patient_name, u.email AS patient_email, a.date AS appointment_date, a.time AS appointment_time
            FROM prescriptions pr
            LEFT JOIN doctors d ON d.user_id = pr.doctor_id
            LEFT JOIN patients pt ON pt.user_id = pr.patient_id
            LEFT JOIN users u ON u.id = pr.patient_id
            LEFT JOIN appointments a ON a.id = pr.appointment_id
            WHERE pr.verification_code = :verification_code LIMIT 1');
        $stmt->execute(['verification_code' => $verificationCode]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function byDoctor(int $doctorUserId): array
    {
        $stmt = $this->db->prepare('SELECT pr.*, pt.name AS patient_name, a.date AS appointment_date, a.time AS appointment_time
            FROM prescriptions pr
            LEFT JOIN patients pt ON pt.user_id = pr.patient_id
            LEFT JOIN appointments a ON a.id = pr.appointment_id
            WHERE pr.doctor_id = :doctor_id
            ORDER BY pr.created_at DESC');
        $stmt->execute(['doctor_id' => $doctorUserId]);
        return $stmt->fetchAll();
    }

    public function byPatient(int $patientUserId): array
    {
        $stmt = $this->db->prepare('SELECT pr.*, d.name AS doctor_name, d.specialization, a.date AS appointment_date, a.time AS appointment_time
            FROM prescriptions pr
            LEFT JOIN doctors d ON d.user_id = pr.doctor_id
            LEFT JOIN appointments a ON a.id = pr.appointment_id
            WHERE pr.patient_id = :patient_id
            ORDER BY pr.created_at DESC');
        $stmt->execute(['patient_id' => $patientUserId]);
        return $stmt->fetchAll();
    }

    public function countByDoctor(int $doctorUserId): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM prescriptions WHERE doctor_id = :doctor_id');
        $stmt->execute(['doctor_id' => $doctorUserId]);
        return (int) $stmt->fetchColumn();
    }

    public function countByPatient(int $patientUserId): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM prescriptions WHERE patient_id = :patient_id');
        $stmt->execute(['patient_id' => $patientUserId]);
        return (int) $stmt->fetchColumn();
    }
}
