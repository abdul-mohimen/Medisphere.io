<?php
namespace App\Models;

class Invoice extends BaseModel
{
    public function create(array $data): int
    {
        $stmt = $this->db->prepare('INSERT INTO invoices (user_id, appointment_id, invoice_number, amount, currency, status, due_date, issued_at, notes) VALUES (:user_id, :appointment_id, :invoice_number, :amount, :currency, :status, :due_date, NOW(), :notes)');
        $stmt->execute([
            'user_id' => $data['user_id'],
            'appointment_id' => $data['appointment_id'] ?? null,
            'invoice_number' => $data['invoice_number'],
            'amount' => $data['amount'],
            'currency' => $data['currency'] ?? config('app.currency', 'PKR'),
            'status' => $data['status'] ?? 'unpaid',
            'due_date' => $data['due_date'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT i.*, a.date AS appointment_date, a.time AS appointment_time, d.user_id AS doctor_user_id, d.name AS doctor_name, d.specialization, d.consultation_fee, p.name AS patient_name, u.email AS patient_email
            FROM invoices i
            LEFT JOIN appointments a ON a.id = i.appointment_id
            LEFT JOIN doctors d ON d.user_id = a.doctor_id
            LEFT JOIN patients p ON p.user_id = i.user_id
            LEFT JOIN users u ON u.id = i.user_id
            WHERE i.id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findByAppointmentId(int $appointmentId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM invoices WHERE appointment_id = :appointment_id LIMIT 1');
        $stmt->execute(['appointment_id' => $appointmentId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findByIdForUser(int $id, int $userId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM invoices WHERE id = :id AND user_id = :user_id LIMIT 1');
        $stmt->execute(['id' => $id, 'user_id' => $userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function byUser(int $userId): array
    {
        $stmt = $this->db->prepare('SELECT i.*, a.date AS appointment_date, a.time AS appointment_time, d.name AS doctor_name, d.specialization
            FROM invoices i
            LEFT JOIN appointments a ON a.id = i.appointment_id
            LEFT JOIN doctors d ON d.user_id = a.doctor_id
            WHERE i.user_id = :user_id
            ORDER BY i.issued_at DESC');
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll();
    }

    public function all(): array
    {
        $stmt = $this->db->query('SELECT i.*, u.email AS user_email, a.date AS appointment_date, d.name AS doctor_name
            FROM invoices i
            JOIN users u ON u.id = i.user_id
            LEFT JOIN appointments a ON a.id = i.appointment_id
            LEFT JOIN doctors d ON d.user_id = a.doctor_id
            ORDER BY i.issued_at DESC');
        return $stmt->fetchAll();
    }

    public function countUnpaidByUser(int $userId): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM invoices WHERE user_id = :user_id AND status = "unpaid"');
        $stmt->execute(['user_id' => $userId]);
        return (int) $stmt->fetchColumn();
    }

    public function markPaid(int $id): bool
    {
        $stmt = $this->db->prepare('UPDATE invoices SET status = "paid" WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }

    public function markRefunded(int $id): bool
    {
        $stmt = $this->db->prepare('UPDATE invoices SET status = "refunded" WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }
}
