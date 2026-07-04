<?php
namespace App\Models;

class PatientRecordAccessLog extends BaseModel
{
    public function create(array $data): int
    {
        $stmt = $this->db->prepare('INSERT INTO patient_record_access_logs (hospital_id, patient_id, consent_id, accessed_by_user_id, access_type, context_data, created_at) VALUES (:hospital_id, :patient_id, :consent_id, :accessed_by_user_id, :access_type, :context_data, NOW())');
        $stmt->execute([
            'hospital_id' => $data['hospital_id'],
            'patient_id' => $data['patient_id'],
            'consent_id' => $data['consent_id'],
            'accessed_by_user_id' => $data['accessed_by_user_id'],
            'access_type' => $data['access_type'],
            'context_data' => $data['context_data'] ?? null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function byHospital(int $hospitalId, int $limit = 25): array
    {
        $stmt = $this->db->prepare('SELECT pral.*, p.name AS patient_name, u.email AS accessor_email FROM patient_record_access_logs pral JOIN patients p ON p.user_id = pral.patient_id JOIN users u ON u.id = pral.accessed_by_user_id WHERE pral.hospital_id = :hospital_id ORDER BY pral.created_at DESC LIMIT ' . (int) $limit);
        $stmt->execute(['hospital_id' => $hospitalId]);
        return $stmt->fetchAll();
    }

    public function byPatient(int $patientId, int $limit = 25): array
    {
        $stmt = $this->db->prepare('SELECT pral.*, h.name AS hospital_name, u.email AS accessor_email FROM patient_record_access_logs pral JOIN hospitals h ON h.id = pral.hospital_id JOIN users u ON u.id = pral.accessed_by_user_id WHERE pral.patient_id = :patient_id ORDER BY pral.created_at DESC LIMIT ' . (int) $limit);
        $stmt->execute(['patient_id' => $patientId]);
        return $stmt->fetchAll();
    }
}
