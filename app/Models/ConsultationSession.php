<?php
namespace App\Models;

class ConsultationSession extends BaseModel
{
    public function create(array $data): int
    {
        $stmt = $this->db->prepare('INSERT INTO consultation_sessions (appointment_id, initiator_id, patient_id, doctor_id, session_token, room_name, call_type, status, consent_recording, created_at) VALUES (:appointment_id, :initiator_id, :patient_id, :doctor_id, :session_token, :room_name, :call_type, :status, :consent_recording, NOW())');
        $stmt->execute([
            'appointment_id' => $data['appointment_id'] ?? null,
            'initiator_id' => $data['initiator_id'],
            'patient_id' => $data['patient_id'],
            'doctor_id' => $data['doctor_id'],
            'session_token' => $data['session_token'],
            'room_name' => $data['room_name'],
            'call_type' => $data['call_type'] ?? 'video',
            'status' => $data['status'] ?? 'waiting',
            'consent_recording' => !empty($data['consent_recording']) ? 1 : 0,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT cs.*, p.name AS patient_name, d.name AS doctor_name, d.specialization
            FROM consultation_sessions cs
            LEFT JOIN patients p ON p.user_id = cs.patient_id
            LEFT JOIN doctors d ON d.user_id = cs.doctor_id
            WHERE cs.id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findByAppointmentId(int $appointmentId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM consultation_sessions WHERE appointment_id = :appointment_id AND status IN ("waiting", "active") ORDER BY id DESC LIMIT 1');
        $stmt->execute(['appointment_id' => $appointmentId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function forUser(int $userId, string $role): array
    {
        $column = $role === 'doctor' ? 'doctor_id' : 'patient_id';
        $stmt = $this->db->prepare("SELECT cs.*, p.name AS patient_name, d.name AS doctor_name, d.specialization, a.date AS appointment_date, a.time AS appointment_time
            FROM consultation_sessions cs
            LEFT JOIN patients p ON p.user_id = cs.patient_id
            LEFT JOIN doctors d ON d.user_id = cs.doctor_id
            LEFT JOIN appointments a ON a.id = cs.appointment_id
            WHERE cs.{$column} = :user_id
            ORDER BY cs.created_at DESC");
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll();
    }

    public function waitingForDoctor(int $doctorUserId): array
    {
        $stmt = $this->db->prepare('SELECT cs.*, p.name AS patient_name, a.date AS appointment_date, a.time AS appointment_time
            FROM consultation_sessions cs
            LEFT JOIN patients p ON p.user_id = cs.patient_id
            LEFT JOIN appointments a ON a.id = cs.appointment_id
            WHERE cs.doctor_id = :doctor_id AND cs.status = "waiting"
            ORDER BY cs.created_at ASC');
        $stmt->execute(['doctor_id' => $doctorUserId]);
        return $stmt->fetchAll();
    }

    public function activeForUser(int $userId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM consultation_sessions WHERE (patient_id = :user_id OR doctor_id = :user_id2) AND status IN ("waiting", "active") ORDER BY id DESC');
        $stmt->execute(['user_id' => $userId, 'user_id2' => $userId]);
        return $stmt->fetchAll();
    }

    public function updateStatus(int $id, string $status, ?string $startedAt = null, ?string $endedAt = null): bool
    {
        $stmt = $this->db->prepare('UPDATE consultation_sessions SET status = :status, started_at = COALESCE(:started_at, started_at), ended_at = COALESCE(:ended_at, ended_at) WHERE id = :id');
        return $stmt->execute([
            'status' => $status,
            'started_at' => $startedAt,
            'ended_at' => $endedAt,
            'id' => $id,
        ]);
    }

    public function participantCount(int $id): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(DISTINCT sender_id) FROM consultation_signals WHERE session_id = :session_id AND signal_type = "presence"');
        $stmt->execute(['session_id' => $id]);
        return (int) $stmt->fetchColumn();
    }
}
