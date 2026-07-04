<?php
namespace App\Models;

class PatientMedication extends BaseModel
{
    public function create(array $data): int
    {
        $stmt = $this->db->prepare('INSERT INTO patient_medications (patient_id, medicine_name, dosage, frequency, start_date, notes, created_at) VALUES (:patient_id, :medicine_name, :dosage, :frequency, :start_date, :notes, NOW())');
        $stmt->execute([
            'patient_id' => $data['patient_id'],
            'medicine_name' => $data['medicine_name'],
            'dosage' => $data['dosage'] ?? null,
            'frequency' => $data['frequency'] ?? null,
            'start_date' => ($data['start_date'] ?? '') ?: null,
            'notes' => $data['notes'] ?? null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function byPatient(int $patientId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM patient_medications WHERE patient_id = :patient_id ORDER BY id DESC');
        $stmt->execute(['patient_id' => $patientId]);
        return $stmt->fetchAll();
    }

    public function delete(int $id, int $patientId): bool
    {
        $stmt = $this->db->prepare('DELETE FROM patient_medications WHERE id = :id AND patient_id = :patient_id');
        return $stmt->execute(['id' => $id, 'patient_id' => $patientId]);
    }
}
