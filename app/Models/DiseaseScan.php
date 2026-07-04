<?php
namespace App\Models;

class DiseaseScan extends BaseModel
{
    public function create(array $data): int
    {
        $payload = [
            'patient_id' => $data['patient_id'],
            'scan_image' => $data['scan_image'],
            'ai_result' => $data['ai_result'],
            'confidence_score' => $data['confidence_score'],
        ];

        $optional = [
            'scan_type' => $data['scan_type'] ?? 'image',
            'body_part' => $data['body_part'] ?? 'general',
            'symptom_text' => $data['symptom_text'] ?? null,
            'urgency_level' => $data['urgency_level'] ?? 'routine',
            'specialist_recommendation' => $data['specialist_recommendation'] ?? null,
            'care_recommendations' => $data['care_recommendations'] ?? null,
        ];

        $columns = $this->columns();
        foreach ($optional as $column => $value) {
            if (in_array($column, $columns, true)) {
                $payload[$column] = $value;
            }
        }

        $fields = array_keys($payload);
        $placeholders = array_map(static fn(string $field): string => ':' . $field, $fields);
        $sql = 'INSERT INTO disease_scans (' . implode(', ', $fields) . ', timestamp) VALUES (' . implode(', ', $placeholders) . ', NOW())';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($payload);
        return (int) $this->db->lastInsertId();
    }

    public function recentByPatient(int $patientUserId, int $limit = 5): array
    {
        $stmt = $this->db->prepare('SELECT * FROM disease_scans WHERE patient_id = :patient_id ORDER BY timestamp DESC LIMIT ' . (int) $limit);
        $stmt->execute(['patient_id' => $patientUserId]);
        return $stmt->fetchAll();
    }

    public function allByPatient(int $patientUserId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM disease_scans WHERE patient_id = :patient_id ORDER BY timestamp DESC');
        $stmt->execute(['patient_id' => $patientUserId]);
        return $stmt->fetchAll();
    }

    public function countByPatient(int $patientUserId): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM disease_scans WHERE patient_id = :patient_id');
        $stmt->execute(['patient_id' => $patientUserId]);
        return (int) $stmt->fetchColumn();
    }

    private function columns(): array
    {
        static $columns = null;
        if ($columns !== null) {
            return $columns;
        }

        try {
            $columns = array_map(static fn(array $row): string => $row['Field'], $this->db->query('SHOW COLUMNS FROM disease_scans')->fetchAll());
        } catch (\Throwable) {
            $columns = ['patient_id', 'scan_image', 'ai_result', 'confidence_score', 'timestamp'];
        }

        return $columns;
    }
}
