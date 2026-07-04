<?php
namespace App\Models;

class PatientAllergy extends BaseModel
{
    public function create(array $data): int
    {
        $stmt = $this->db->prepare('INSERT INTO patient_allergies (patient_id, allergen, severity, notes, created_at) VALUES (:patient_id, :allergen, :severity, :notes, NOW())');
        $stmt->execute([
            'patient_id' => $data['patient_id'],
            'allergen' => $data['allergen'],
            'severity' => $data['severity'] ?? 'moderate',
            'notes' => $data['notes'] ?? null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function byPatient(int $patientId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM patient_allergies WHERE patient_id = :patient_id ORDER BY id DESC');
        $stmt->execute(['patient_id' => $patientId]);
        return $stmt->fetchAll();
    }

    public function delete(int $id, int $patientId): bool
    {
        $stmt = $this->db->prepare('DELETE FROM patient_allergies WHERE id = :id AND patient_id = :patient_id');
        return $stmt->execute(['id' => $id, 'patient_id' => $patientId]);
    }
}
