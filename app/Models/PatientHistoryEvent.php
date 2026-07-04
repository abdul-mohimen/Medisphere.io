<?php
namespace App\Models;

class PatientHistoryEvent extends BaseModel
{
    public function create(array $data): int
    {
        $stmt = $this->db->prepare('INSERT INTO patient_history_events (patient_id, event_date, category, title, description, created_at) VALUES (:patient_id, :event_date, :category, :title, :description, NOW())');
        $stmt->execute([
            'patient_id' => $data['patient_id'],
            'event_date' => $data['event_date'],
            'category' => $data['category'] ?? 'general',
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function byPatient(int $patientId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM patient_history_events WHERE patient_id = :patient_id ORDER BY event_date DESC, id DESC');
        $stmt->execute(['patient_id' => $patientId]);
        return $stmt->fetchAll();
    }

    public function delete(int $id, int $patientId): bool
    {
        $stmt = $this->db->prepare('DELETE FROM patient_history_events WHERE id = :id AND patient_id = :patient_id');
        return $stmt->execute(['id' => $id, 'patient_id' => $patientId]);
    }
}
