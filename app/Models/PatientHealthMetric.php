<?php
namespace App\Models;

class PatientHealthMetric extends BaseModel
{
    public function create(array $data): int
    {
        $stmt = $this->db->prepare('INSERT INTO patient_health_metrics (patient_id, metric_type, value_primary, value_secondary, unit, recorded_at, notes, created_at) VALUES (:patient_id, :metric_type, :value_primary, :value_secondary, :unit, :recorded_at, :notes, NOW())');
        $stmt->execute([
            'patient_id' => $data['patient_id'],
            'metric_type' => $data['metric_type'],
            'value_primary' => $data['value_primary'],
            'value_secondary' => $data['value_secondary'] ?? null,
            'unit' => $data['unit'] ?? null,
            'recorded_at' => ($data['recorded_at'] ?? '') ?: date('Y-m-d'),
            'notes' => $data['notes'] ?? null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function byPatient(int $patientId, int $limit = 50): array
    {
        $stmt = $this->db->prepare('SELECT * FROM patient_health_metrics WHERE patient_id = :patient_id ORDER BY recorded_at DESC, id DESC LIMIT ' . (int) $limit);
        $stmt->execute(['patient_id' => $patientId]);
        return $stmt->fetchAll();
    }

    public function delete(int $id, int $patientId): bool
    {
        $stmt = $this->db->prepare('DELETE FROM patient_health_metrics WHERE id = :id AND patient_id = :patient_id');
        return $stmt->execute(['id' => $id, 'patient_id' => $patientId]);
    }
}
