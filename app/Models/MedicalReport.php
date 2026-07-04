<?php
namespace App\Models;

class MedicalReport extends BaseModel
{
    public function create(array $data): int
    {
        $stmt = $this->db->prepare('INSERT INTO medical_reports (patient_id, doctor_id, report_type, file_path, uploaded_date, original_name) VALUES (:patient_id, :doctor_id, :report_type, :file_path, NOW(), :original_name)');
        $stmt->execute([
            'patient_id' => $data['patient_id'],
            'doctor_id' => $data['doctor_id'] ?? null,
            'report_type' => $data['report_type'],
            'file_path' => $data['file_path'],
            'original_name' => $data['original_name'],
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function byPatient(int $patientUserId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM medical_reports WHERE patient_id = :patient_id ORDER BY uploaded_date DESC');
        $stmt->execute(['patient_id' => $patientUserId]);
        return $stmt->fetchAll();
    }

    public function findForPatient(int $id, int $patientUserId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM medical_reports WHERE id = :id AND patient_id = :patient_id LIMIT 1');
        $stmt->execute([
            'id' => $id,
            'patient_id' => $patientUserId,
        ]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function countByPatient(int $patientUserId): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM medical_reports WHERE patient_id = :patient_id');
        $stmt->execute(['patient_id' => $patientUserId]);
        return (int) $stmt->fetchColumn();
    }

    public function countsByType(int $patientUserId): array
    {
        $stmt = $this->db->prepare('SELECT report_type, COUNT(*) AS total FROM medical_reports WHERE patient_id = :patient_id GROUP BY report_type ORDER BY total DESC, report_type ASC');
        $stmt->execute(['patient_id' => $patientUserId]);
        return $stmt->fetchAll();
    }

    public function deleteForPatient(int $id, int $patientUserId): bool
    {
        $stmt = $this->db->prepare('DELETE FROM medical_reports WHERE id = :id AND patient_id = :patient_id');
        return $stmt->execute([
            'id' => $id,
            'patient_id' => $patientUserId,
        ]);
    }
}
