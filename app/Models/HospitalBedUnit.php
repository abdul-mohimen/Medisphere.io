<?php
namespace App\Models;

class HospitalBedUnit extends BaseModel
{
    public function create(array $data): int
    {
        $stmt = $this->db->prepare('INSERT INTO hospital_bed_units (hospital_id, ward_name, bed_label, bed_type, occupancy_status, assigned_patient_id, notes, created_at, updated_at) VALUES (:hospital_id, :ward_name, :bed_label, :bed_type, :occupancy_status, :assigned_patient_id, :notes, NOW(), NOW())');
        $stmt->execute([
            'hospital_id' => $data['hospital_id'],
            'ward_name' => $data['ward_name'],
            'bed_label' => $data['bed_label'],
            'bed_type' => $data['bed_type'] ?? null,
            'occupancy_status' => $data['occupancy_status'] ?? 'available',
            'assigned_patient_id' => $data['assigned_patient_id'] ?: null,
            'notes' => $data['notes'] ?? null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, int $hospitalId, array $data): bool
    {
        $stmt = $this->db->prepare('UPDATE hospital_bed_units SET ward_name = :ward_name, bed_label = :bed_label, bed_type = :bed_type, occupancy_status = :occupancy_status, assigned_patient_id = :assigned_patient_id, notes = :notes, updated_at = NOW() WHERE id = :id AND hospital_id = :hospital_id');
        return $stmt->execute([
            'id' => $id,
            'hospital_id' => $hospitalId,
            'ward_name' => $data['ward_name'],
            'bed_label' => $data['bed_label'],
            'bed_type' => $data['bed_type'] ?? null,
            'occupancy_status' => $data['occupancy_status'] ?? 'available',
            'assigned_patient_id' => $data['assigned_patient_id'] ?: null,
            'notes' => $data['notes'] ?? null,
        ]);
    }

    public function allByHospital(int $hospitalId): array
    {
        $stmt = $this->db->prepare('SELECT hb.*, p.name AS assigned_patient_name FROM hospital_bed_units hb LEFT JOIN patients p ON p.user_id = hb.assigned_patient_id WHERE hb.hospital_id = :hospital_id ORDER BY hb.ward_name ASC, hb.bed_label ASC');
        $stmt->execute(['hospital_id' => $hospitalId]);
        return $stmt->fetchAll();
    }

    public function countByHospital(int $hospitalId): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM hospital_bed_units WHERE hospital_id = :hospital_id');
        $stmt->execute(['hospital_id' => $hospitalId]);
        return (int) $stmt->fetchColumn();
    }

    public function occupancyStats(int $hospitalId): array
    {
        $stmt = $this->db->prepare('SELECT occupancy_status, COUNT(*) AS total FROM hospital_bed_units WHERE hospital_id = :hospital_id GROUP BY occupancy_status');
        $stmt->execute(['hospital_id' => $hospitalId]);
        $stats = ['available' => 0, 'occupied' => 0, 'reserved' => 0, 'maintenance' => 0, 'cleaning' => 0];
        foreach ($stmt->fetchAll() as $row) {
            $stats[$row['occupancy_status']] = (int) $row['total'];
        }
        $stats['total'] = array_sum($stats);
        return $stats;
    }
}
