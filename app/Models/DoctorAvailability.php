<?php
namespace App\Models;

class DoctorAvailability extends BaseModel
{
    public function getStatus(int $doctorUserId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM doctor_availability WHERE doctor_id = :doctor_id LIMIT 1');
        $stmt->execute(['doctor_id' => $doctorUserId]);
        $row = $stmt->fetch();
        if ($row) {
            return $row;
        }

        $this->upsert($doctorUserId, 'offline');
        $stmt->execute(['doctor_id' => $doctorUserId]);
        return $stmt->fetch() ?: ['doctor_id' => $doctorUserId, 'availability_status' => 'offline'];
    }

    public function upsert(int $doctorUserId, string $status): bool
    {
        $stmt = $this->db->prepare('INSERT INTO doctor_availability (doctor_id, availability_status, updated_at) VALUES (:doctor_id, :availability_status, NOW()) ON DUPLICATE KEY UPDATE availability_status = VALUES(availability_status), updated_at = NOW()');
        return $stmt->execute([
            'doctor_id' => $doctorUserId,
            'availability_status' => $status,
        ]);
    }
}
