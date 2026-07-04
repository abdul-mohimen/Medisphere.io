<?php
namespace App\Models;

class PatientInsuranceProfile extends BaseModel
{
    public function findByPatient(int $patientId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM patient_insurance_profiles WHERE patient_id = :patient_id LIMIT 1');
        $stmt->execute(['patient_id' => $patientId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function upsert(int $patientId, array $data): bool
    {
        $stmt = $this->db->prepare('INSERT INTO patient_insurance_profiles (patient_id, provider_name, policy_number, plan_name, valid_until, coverage_notes, updated_at) VALUES (:patient_id, :provider_name, :policy_number, :plan_name, :valid_until, :coverage_notes, NOW()) ON DUPLICATE KEY UPDATE provider_name = VALUES(provider_name), policy_number = VALUES(policy_number), plan_name = VALUES(plan_name), valid_until = VALUES(valid_until), coverage_notes = VALUES(coverage_notes), updated_at = NOW()');
        return $stmt->execute([
            'patient_id' => $patientId,
            'provider_name' => $data['provider_name'] ?? null,
            'policy_number' => $data['policy_number'] ?? null,
            'plan_name' => $data['plan_name'] ?? null,
            'valid_until' => ($data['valid_until'] ?? '') ?: null,
            'coverage_notes' => $data['coverage_notes'] ?? null,
        ]);
    }
}
