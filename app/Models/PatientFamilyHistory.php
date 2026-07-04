<?php
namespace App\Models;

class PatientFamilyHistory extends BaseModel
{
    public function create(array $data): int
    {
        $stmt = $this->db->prepare('INSERT INTO patient_family_history (patient_id, relation_name, condition_name, notes, created_at) VALUES (:patient_id, :relation_name, :condition_name, :notes, NOW())');
        $stmt->execute([
            'patient_id' => $data['patient_id'],
            'relation_name' => $data['relation_name'],
            'condition_name' => $data['condition_name'],
            'notes' => $data['notes'] ?? null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function byPatient(int $patientId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM patient_family_history WHERE patient_id = :patient_id ORDER BY id DESC');
        $stmt->execute(['patient_id' => $patientId]);
        return $stmt->fetchAll();
    }

    public function delete(int $id, int $patientId): bool
    {
        $stmt = $this->db->prepare('DELETE FROM patient_family_history WHERE id = :id AND patient_id = :patient_id');
        return $stmt->execute(['id' => $id, 'patient_id' => $patientId]);
    }
}
