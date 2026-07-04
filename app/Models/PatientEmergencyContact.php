<?php
namespace App\Models;

class PatientEmergencyContact extends BaseModel
{
    public function findByPatient(int $patientId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM patient_emergency_contacts WHERE patient_id = :patient_id LIMIT 1');
        $stmt->execute(['patient_id' => $patientId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function upsert(int $patientId, array $data): bool
    {
        $stmt = $this->db->prepare('INSERT INTO patient_emergency_contacts (patient_id, contact_name, relationship, phone, alternate_phone, address, updated_at) VALUES (:patient_id, :contact_name, :relationship, :phone, :alternate_phone, :address, NOW()) ON DUPLICATE KEY UPDATE contact_name = VALUES(contact_name), relationship = VALUES(relationship), phone = VALUES(phone), alternate_phone = VALUES(alternate_phone), address = VALUES(address), updated_at = NOW()');
        return $stmt->execute([
            'patient_id' => $patientId,
            'contact_name' => $data['contact_name'],
            'relationship' => $data['relationship'] ?? null,
            'phone' => $data['phone'] ?? null,
            'alternate_phone' => $data['alternate_phone'] ?? null,
            'address' => $data['address'] ?? null,
        ]);
    }
}
