<?php
namespace App\Models;

class ClinicalDocument extends BaseModel
{
    public function create(array $data): int
    {
        $stmt = $this->db->prepare('INSERT INTO clinical_documents (appointment_id, doctor_id, patient_id, document_type, title, summary, content, issue_date, digital_signature, signature_snapshot_name, signature_image_path, verification_code, integrity_hash, status, created_at, updated_at) VALUES (:appointment_id, :doctor_id, :patient_id, :document_type, :title, :summary, :content, :issue_date, :digital_signature, :signature_snapshot_name, :signature_image_path, :verification_code, :integrity_hash, :status, NOW(), NOW())');
        $stmt->execute([
            'appointment_id' => $data['appointment_id'] ?? null,
            'doctor_id' => $data['doctor_id'],
            'patient_id' => $data['patient_id'],
            'document_type' => $data['document_type'],
            'title' => $data['title'],
            'summary' => $data['summary'] ?? null,
            'content' => $data['content'] ?? null,
            'issue_date' => $data['issue_date'] ?: date('Y-m-d'),
            'digital_signature' => $data['digital_signature'] ?? null,
            'signature_snapshot_name' => $data['signature_snapshot_name'] ?? null,
            'signature_image_path' => $data['signature_image_path'] ?? null,
            'verification_code' => $data['verification_code'],
            'integrity_hash' => $data['integrity_hash'],
            'status' => $data['status'] ?? 'draft',
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT cd.*, d.name AS doctor_name, d.specialization, d.license_number, pt.name AS patient_name, u.email AS patient_email, a.date AS appointment_date, a.time AS appointment_time
            FROM clinical_documents cd
            LEFT JOIN doctors d ON d.user_id = cd.doctor_id
            LEFT JOIN patients pt ON pt.user_id = cd.patient_id
            LEFT JOIN users u ON u.id = cd.patient_id
            LEFT JOIN appointments a ON a.id = cd.appointment_id
            WHERE cd.id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findByVerificationCode(string $verificationCode): ?array
    {
        $stmt = $this->db->prepare('SELECT cd.*, d.name AS doctor_name, d.specialization, d.license_number, pt.name AS patient_name, u.email AS patient_email, a.date AS appointment_date, a.time AS appointment_time
            FROM clinical_documents cd
            LEFT JOIN doctors d ON d.user_id = cd.doctor_id
            LEFT JOIN patients pt ON pt.user_id = cd.patient_id
            LEFT JOIN users u ON u.id = cd.patient_id
            LEFT JOIN appointments a ON a.id = cd.appointment_id
            WHERE cd.verification_code = :verification_code LIMIT 1');
        $stmt->execute(['verification_code' => $verificationCode]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function byDoctor(int $doctorUserId): array
    {
        $stmt = $this->db->prepare('SELECT cd.*, pt.name AS patient_name, a.date AS appointment_date, a.time AS appointment_time
            FROM clinical_documents cd
            LEFT JOIN patients pt ON pt.user_id = cd.patient_id
            LEFT JOIN appointments a ON a.id = cd.appointment_id
            WHERE cd.doctor_id = :doctor_id
            ORDER BY cd.created_at DESC');
        $stmt->execute(['doctor_id' => $doctorUserId]);
        return $stmt->fetchAll();
    }

    public function byPatient(int $patientUserId): array
    {
        $stmt = $this->db->prepare('SELECT cd.*, d.name AS doctor_name, d.specialization, a.date AS appointment_date, a.time AS appointment_time
            FROM clinical_documents cd
            LEFT JOIN doctors d ON d.user_id = cd.doctor_id
            LEFT JOIN appointments a ON a.id = cd.appointment_id
            WHERE cd.patient_id = :patient_id
            ORDER BY cd.created_at DESC');
        $stmt->execute(['patient_id' => $patientUserId]);
        return $stmt->fetchAll();
    }

    public function countByDoctor(int $doctorUserId): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM clinical_documents WHERE doctor_id = :doctor_id');
        $stmt->execute(['doctor_id' => $doctorUserId]);
        return (int) $stmt->fetchColumn();
    }

    public function countByPatient(int $patientUserId): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM clinical_documents WHERE patient_id = :patient_id');
        $stmt->execute(['patient_id' => $patientUserId]);
        return (int) $stmt->fetchColumn();
    }
}
