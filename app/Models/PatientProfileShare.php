<?php
namespace App\Models;

class PatientProfileShare extends BaseModel
{
    public function getByPatient(int $patientId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM patient_profile_shares WHERE patient_id = :patient_id LIMIT 1');
        $stmt->execute(['patient_id' => $patientId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function getByToken(string $token): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM patient_profile_shares WHERE access_token = :access_token AND is_active = 1 LIMIT 1');
        $stmt->execute(['access_token' => $token]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function getOrCreate(int $patientId): array
    {
        $share = $this->getByPatient($patientId);
        if ($share) {
            return $share;
        }

        $token = bin2hex(random_bytes(18));
        $stmt = $this->db->prepare('INSERT INTO patient_profile_shares (patient_id, access_token, is_active, updated_at) VALUES (:patient_id, :access_token, 1, NOW())');
        $stmt->execute(['patient_id' => $patientId, 'access_token' => $token]);
        return $this->getByPatient($patientId) ?: ['patient_id' => $patientId, 'access_token' => $token, 'is_active' => 1];
    }

    public function regenerate(int $patientId): bool
    {
        $stmt = $this->db->prepare('INSERT INTO patient_profile_shares (patient_id, access_token, is_active, updated_at) VALUES (:patient_id, :access_token, 1, NOW()) ON DUPLICATE KEY UPDATE access_token = VALUES(access_token), is_active = 1, updated_at = NOW()');
        return $stmt->execute([
            'patient_id' => $patientId,
            'access_token' => bin2hex(random_bytes(18)),
        ]);
    }
}
