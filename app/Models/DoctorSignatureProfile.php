<?php
namespace App\Models;

class DoctorSignatureProfile extends BaseModel
{
    public function findByDoctor(int $doctorUserId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM doctor_signature_profiles WHERE doctor_id = :doctor_id LIMIT 1');
        $stmt->execute(['doctor_id' => $doctorUserId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function upsert(int $doctorUserId, array $data): bool
    {
        $stmt = $this->db->prepare('INSERT INTO doctor_signature_profiles (doctor_id, signature_name, stamp_text, signature_image_path, updated_at) VALUES (:doctor_id, :signature_name, :stamp_text, :signature_image_path, NOW()) ON DUPLICATE KEY UPDATE signature_name = VALUES(signature_name), stamp_text = VALUES(stamp_text), signature_image_path = VALUES(signature_image_path), updated_at = NOW()');
        return $stmt->execute([
            'doctor_id' => $doctorUserId,
            'signature_name' => $data['signature_name'],
            'stamp_text' => $data['stamp_text'] ?? null,
            'signature_image_path' => $data['signature_image_path'] ?? null,
        ]);
    }
}
