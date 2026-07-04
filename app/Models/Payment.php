<?php
namespace App\Models;

class Payment extends BaseModel
{
    public function create(array $data): int
    {
        $stmt = $this->db->prepare('INSERT INTO payments (invoice_id, appointment_id, payer_id, payee_id, provider, provider_payment_id, transaction_reference, amount, currency, status, gateway_response, paid_at, created_at, updated_at)
            VALUES (:invoice_id, :appointment_id, :payer_id, :payee_id, :provider, :provider_payment_id, :transaction_reference, :amount, :currency, :status, :gateway_response, :paid_at, NOW(), NOW())');
        $stmt->execute([
            'invoice_id' => $data['invoice_id'],
            'appointment_id' => $data['appointment_id'] ?? null,
            'payer_id' => $data['payer_id'],
            'payee_id' => $data['payee_id'] ?? null,
            'provider' => $data['provider'],
            'provider_payment_id' => $data['provider_payment_id'] ?? null,
            'transaction_reference' => $data['transaction_reference'],
            'amount' => $data['amount'],
            'currency' => $data['currency'] ?? config('app.currency', 'PKR'),
            'status' => $data['status'] ?? 'initiated',
            'gateway_response' => $data['gateway_response'] ?? null,
            'paid_at' => $data['paid_at'] ?? null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM payments WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function updateAfterGateway(int $id, array $data): bool
    {
        $stmt = $this->db->prepare('UPDATE payments SET provider_payment_id = :provider_payment_id, status = :status, gateway_response = :gateway_response, paid_at = :paid_at, updated_at = NOW() WHERE id = :id');
        return $stmt->execute([
            'provider_payment_id' => $data['provider_payment_id'] ?? null,
            'status' => $data['status'],
            'gateway_response' => $data['gateway_response'] ?? null,
            'paid_at' => $data['paid_at'] ?? null,
            'id' => $id,
        ]);
    }

    public function byPayer(int $payerId): array
    {
        $stmt = $this->db->prepare('SELECT p.*, i.invoice_number, d.name AS doctor_name, a.date AS appointment_date, a.time AS appointment_time
            FROM payments p
            JOIN invoices i ON i.id = p.invoice_id
            LEFT JOIN appointments a ON a.id = p.appointment_id
            LEFT JOIN doctors d ON d.user_id = a.doctor_id
            WHERE p.payer_id = :payer_id
            ORDER BY p.created_at DESC');
        $stmt->execute(['payer_id' => $payerId]);
        return $stmt->fetchAll();
    }

    public function byDoctor(int $doctorUserId): array
    {
        $stmt = $this->db->prepare('SELECT p.*, i.invoice_number, a.date AS appointment_date, a.time AS appointment_time, pt.name AS patient_name
            FROM payments p
            JOIN invoices i ON i.id = p.invoice_id
            LEFT JOIN appointments a ON a.id = p.appointment_id
            LEFT JOIN patients pt ON pt.user_id = p.payer_id
            WHERE p.payee_id = :doctor_id
            ORDER BY p.created_at DESC');
        $stmt->execute(['doctor_id' => $doctorUserId]);
        return $stmt->fetchAll();
    }

    public function all(): array
    {
        $stmt = $this->db->query('SELECT p.*, i.invoice_number, payer.email AS payer_email, payee.email AS payee_email
            FROM payments p
            JOIN invoices i ON i.id = p.invoice_id
            JOIN users payer ON payer.id = p.payer_id
            LEFT JOIN users payee ON payee.id = p.payee_id
            ORDER BY p.created_at DESC');
        return $stmt->fetchAll();
    }

    public function paidTotalByPayer(int $payerId): float
    {
        $stmt = $this->db->prepare('SELECT COALESCE(SUM(amount), 0) FROM payments WHERE payer_id = :payer_id AND status = "paid"');
        $stmt->execute(['payer_id' => $payerId]);
        return (float) $stmt->fetchColumn();
    }


    public function markRefunded(int $id, ?string $gatewayResponse = null): bool
    {
        $stmt = $this->db->prepare('UPDATE payments SET status = "refunded", gateway_response = COALESCE(:gateway_response, gateway_response), updated_at = NOW() WHERE id = :id');
        return $stmt->execute(['gateway_response' => $gatewayResponse, 'id' => $id]);
    }
    public function statsForDoctor(int $doctorUserId): array
    {
        $stmt = $this->db->prepare('SELECT COALESCE(SUM(CASE WHEN status = "paid" THEN amount ELSE 0 END), 0) AS revenue, COUNT(*) AS total_transactions, SUM(status = "paid") AS paid_transactions, SUM(status IN ("initiated", "pending")) AS pending_transactions
            FROM payments WHERE payee_id = :doctor_id');
        $stmt->execute(['doctor_id' => $doctorUserId]);
        $row = $stmt->fetch() ?: [];
        return [
            'revenue' => (float) ($row['revenue'] ?? 0),
            'total_transactions' => (int) ($row['total_transactions'] ?? 0),
            'paid_transactions' => (int) ($row['paid_transactions'] ?? 0),
            'pending_transactions' => (int) ($row['pending_transactions'] ?? 0),
        ];
    }

    public function statsOverall(): array
    {
        $stmt = $this->db->query('SELECT COALESCE(SUM(CASE WHEN status = "paid" THEN amount ELSE 0 END), 0) AS gross_revenue, COUNT(*) AS total_transactions, SUM(status = "paid") AS paid_transactions, SUM(status = "failed") AS failed_transactions, SUM(status = "refunded") AS refunded_transactions FROM payments');
        $row = $stmt->fetch() ?: [];
        return [
            'gross_revenue' => (float) ($row['gross_revenue'] ?? 0),
            'total_transactions' => (int) ($row['total_transactions'] ?? 0),
            'paid_transactions' => (int) ($row['paid_transactions'] ?? 0),
            'failed_transactions' => (int) ($row['failed_transactions'] ?? 0),
            'refunded_transactions' => (int) ($row['refunded_transactions'] ?? 0),
        ];
    }
}
