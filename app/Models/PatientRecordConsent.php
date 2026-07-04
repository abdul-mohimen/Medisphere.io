<?php
namespace App\Models;

class PatientRecordConsent extends BaseModel
{
    public function create(array $data): int
    {
        $stmt = $this->db->prepare('INSERT INTO patient_record_consents (patient_id, hospital_id, scope, status, starts_at, expires_at, notes, granted_by_patient_at, created_at, updated_at) VALUES (:patient_id, :hospital_id, :scope, :status, :starts_at, :expires_at, :notes, NOW(), NOW(), NOW())');
        $stmt->execute([
            'patient_id' => $data['patient_id'],
            'hospital_id' => $data['hospital_id'],
            'scope' => $data['scope'],
            'status' => $data['status'] ?? 'active',
            'starts_at' => $data['starts_at'] ?: date('Y-m-d'),
            'expires_at' => $data['expires_at'] ?: null,
            'notes' => $data['notes'] ?? null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function byPatient(int $patientId): array
    {
        $stmt = $this->db->prepare('SELECT prc.*, h.name AS hospital_name, h.city, h.country FROM patient_record_consents prc JOIN hospitals h ON h.id = prc.hospital_id WHERE prc.patient_id = :patient_id ORDER BY prc.created_at DESC');
        $stmt->execute(['patient_id' => $patientId]);
        return $stmt->fetchAll();
    }

    public function activeByHospital(int $hospitalId): array
    {
        $stmt = $this->db->prepare('SELECT prc.*, p.name AS patient_name, p.phone, u.email AS patient_email FROM patient_record_consents prc JOIN patients p ON p.user_id = prc.patient_id JOIN users u ON u.id = prc.patient_id WHERE prc.hospital_id = :hospital_id AND prc.status = "active" AND (prc.expires_at IS NULL OR prc.expires_at >= CURDATE()) ORDER BY prc.granted_by_patient_at DESC');
        $stmt->execute(['hospital_id' => $hospitalId]);
        return $stmt->fetchAll();
    }

    public function countActiveByHospital(int $hospitalId): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM patient_record_consents WHERE hospital_id = :hospital_id AND status = "active" AND (expires_at IS NULL OR expires_at >= CURDATE())');
        $stmt->execute(['hospital_id' => $hospitalId]);
        return (int) $stmt->fetchColumn();
    }

    public function activeForHospitalPatient(int $hospitalId, int $patientId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM patient_record_consents WHERE hospital_id = :hospital_id AND patient_id = :patient_id AND status = "active" AND (expires_at IS NULL OR expires_at >= CURDATE()) ORDER BY id DESC LIMIT 1');
        $stmt->execute(['hospital_id' => $hospitalId, 'patient_id' => $patientId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function revoke(int $id, int $patientId): bool
    {
        $stmt = $this->db->prepare('UPDATE patient_record_consents SET status = "revoked", revoked_at = NOW(), updated_at = NOW() WHERE id = :id AND patient_id = :patient_id');
        return $stmt->execute(['id' => $id, 'patient_id' => $patientId]);
    }

    public function find(int $id, int $patientId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM patient_record_consents WHERE id = :id AND patient_id = :patient_id LIMIT 1');
        $stmt->execute(['id' => $id, 'patient_id' => $patientId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
}
