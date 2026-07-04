<?php
namespace App\Models;

class Appointment extends BaseModel
{
    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM appointments WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findForPayment(int $appointmentId, int $patientUserId): ?array
    {
        $stmt = $this->db->prepare('SELECT a.*, d.name AS doctor_name, d.specialization, d.consultation_fee, d.user_id AS doctor_user_id, p.name AS patient_name
            FROM appointments a
            JOIN doctors d ON d.user_id = a.doctor_id
            JOIN patients p ON p.user_id = a.patient_id
            WHERE a.id = :id AND a.patient_id = :patient_id
            LIMIT 1');
        $stmt->execute(['id' => $appointmentId, 'patient_id' => $patientUserId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare('INSERT INTO appointments (patient_id, doctor_id, date, time, status, symptoms, diagnosis) VALUES (:patient_id, :doctor_id, :date, :time, :status, :symptoms, :diagnosis)');
        $stmt->execute([
            'patient_id' => $data['patient_id'],
            'doctor_id' => $data['doctor_id'],
            'date' => $data['date'],
            'time' => $data['time'],
            'status' => $data['status'] ?? 'pending',
            'symptoms' => $data['symptoms'] ?? null,
            'diagnosis' => $data['diagnosis'] ?? null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function forPatient(int $patientUserId): array
    {
        $stmt = $this->db->prepare('SELECT a.*, d.name AS doctor_name, d.specialization, d.consultation_fee, h.id AS hospital_id, h.name AS hospital_name, h.city AS hospital_city, h.country AS hospital_country FROM appointments a JOIN doctors d ON d.user_id = a.doctor_id LEFT JOIN hospitals h ON h.id = d.hospital_id WHERE a.patient_id = :patient_id ORDER BY a.date ASC, a.time ASC');
        $stmt->execute(['patient_id' => $patientUserId]);
        return $stmt->fetchAll();
    }

    public function forDoctor(int $doctorUserId): array
    {
        $stmt = $this->db->prepare('SELECT a.*, p.name AS patient_name, p.phone, h.id AS hospital_id, h.name AS hospital_name FROM appointments a JOIN patients p ON p.user_id = a.patient_id JOIN doctors d ON d.user_id = a.doctor_id LEFT JOIN hospitals h ON h.id = d.hospital_id WHERE a.doctor_id = :doctor_id ORDER BY a.date ASC, a.time ASC');
        $stmt->execute(['doctor_id' => $doctorUserId]);
        return $stmt->fetchAll();
    }

    public function forHospital(int $hospitalId): array
    {
        $stmt = $this->db->prepare('SELECT a.*, p.name AS patient_name, p.phone, d.name AS doctor_name, d.specialization, h.id AS hospital_id, h.name AS hospital_name
            FROM appointments a
            JOIN patients p ON p.user_id = a.patient_id
            JOIN doctors d ON d.user_id = a.doctor_id
            LEFT JOIN hospitals h ON h.id = d.hospital_id
            WHERE d.hospital_id = :hospital_id
            ORDER BY a.date ASC, a.time ASC');
        $stmt->execute(['hospital_id' => $hospitalId]);
        return $stmt->fetchAll();
    }

    public function allDetailed(): array
    {
        $stmt = $this->db->query('SELECT a.*, p.name AS patient_name, p.phone, d.name AS doctor_name, d.specialization, h.id AS hospital_id, h.name AS hospital_name
            FROM appointments a
            JOIN patients p ON p.user_id = a.patient_id
            JOIN doctors d ON d.user_id = a.doctor_id
            LEFT JOIN hospitals h ON h.id = d.hospital_id
            ORDER BY a.date ASC, a.time ASC');
        return $stmt->fetchAll();
    }

    public function existsForDoctorAt(int $doctorUserId, string $date, string $time): bool
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM appointments WHERE doctor_id = :doctor_id AND date = :date AND time = :time AND status NOT IN ("cancelled", "completed")');
        $stmt->execute([
            'doctor_id' => $doctorUserId,
            'date' => $date,
            'time' => $time,
        ]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function todayForDoctor(int $doctorUserId): array
    {
        $stmt = $this->db->prepare('SELECT a.*, p.name AS patient_name FROM appointments a JOIN patients p ON p.user_id = a.patient_id WHERE a.doctor_id = :doctor_id AND a.date = CURDATE() ORDER BY a.time ASC');
        $stmt->execute(['doctor_id' => $doctorUserId]);
        return $stmt->fetchAll();
    }

    public function statsForPatient(int $patientUserId): array
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) total, SUM(CASE WHEN date >= CURDATE() THEN 1 ELSE 0 END) upcoming FROM appointments WHERE patient_id = :patient_id');
        $stmt->execute(['patient_id' => $patientUserId]);
        $row = $stmt->fetch() ?: ['total' => 0, 'upcoming' => 0];
        return ['total' => (int) $row['total'], 'upcoming' => (int) $row['upcoming']];
    }

    public function statsForHospital(int $hospitalId): array
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) AS total_appointments,
                SUM(a.status = "completed") AS completed_appointments,
                SUM(a.status = "pending") AS pending_appointments,
                COUNT(DISTINCT a.patient_id) AS unique_patients,
                SUM(CASE WHEN MONTH(a.date) = MONTH(CURDATE()) AND YEAR(a.date) = YEAR(CURDATE()) THEN 1 ELSE 0 END) AS monthly_appointments
            FROM appointments a
            JOIN doctors d ON d.user_id = a.doctor_id
            WHERE d.hospital_id = :hospital_id');
        $stmt->execute(['hospital_id' => $hospitalId]);
        $row = $stmt->fetch() ?: [];
        return [
            'total_appointments' => (int) ($row['total_appointments'] ?? 0),
            'completed_appointments' => (int) ($row['completed_appointments'] ?? 0),
            'pending_appointments' => (int) ($row['pending_appointments'] ?? 0),
            'unique_patients' => (int) ($row['unique_patients'] ?? 0),
            'monthly_appointments' => (int) ($row['monthly_appointments'] ?? 0),
        ];
    }

    public function recentPatientsForHospital(int $hospitalId): array
    {
        $stmt = $this->db->prepare('SELECT DISTINCT p.user_id, p.name, p.phone, u.email
            FROM appointments a
            JOIN doctors d ON d.user_id = a.doctor_id
            JOIN patients p ON p.user_id = a.patient_id
            JOIN users u ON u.id = p.user_id
            WHERE d.hospital_id = :hospital_id
            ORDER BY a.date DESC, a.time DESC
            LIMIT 40');
        $stmt->execute(['hospital_id' => $hospitalId]);
        return $stmt->fetchAll();
    }

    public function statsOverall(): array
    {
        $stmt = $this->db->query('SELECT COUNT(*) total, SUM(status = "pending") pending, SUM(status = "confirmed") confirmed, SUM(status = "completed") completed, SUM(status = "cancelled") cancelled FROM appointments');
        $row = $stmt->fetch() ?: [];
        return array_map('intval', $row ?: ['total' => 0, 'pending' => 0, 'confirmed' => 0, 'completed' => 0, 'cancelled' => 0]);
    }

    public function updateStatus(int $id, string $status, ?string $diagnosis = null): bool
    {
        $stmt = $this->db->prepare('UPDATE appointments SET status = :status, diagnosis = COALESCE(:diagnosis, diagnosis) WHERE id = :id');
        return $stmt->execute(['status' => $status, 'diagnosis' => $diagnosis, 'id' => $id]);
    }
}
