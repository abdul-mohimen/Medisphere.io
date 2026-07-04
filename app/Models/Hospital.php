<?php
namespace App\Models;

class Hospital extends BaseModel
{
    public function create(array $data): int
    {
        $stmt = $this->db->prepare('INSERT INTO hospitals (user_id, name, type, address, city, country, phone, email, registration_number, facilities, departments, verified_status, coordinates) VALUES (:user_id, :name, :type, :address, :city, :country, :phone, :email, :registration_number, :facilities, :departments, :verified_status, :coordinates)');
        $stmt->execute([
            'user_id' => $data['user_id'],
            'name' => $data['name'],
            'type' => $data['type'],
            'address' => $data['address'],
            'city' => $data['city'],
            'country' => $data['country'],
            'phone' => $data['phone'],
            'email' => $data['email'],
            'registration_number' => $data['registration_number'],
            'facilities' => $data['facilities'] ?? null,
            'departments' => $data['departments'] ?? null,
            'verified_status' => $data['verified_status'] ?? 'pending',
            'coordinates' => $data['coordinates'] ?? null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function all(): array
    {
        return $this->db->query('SELECT * FROM hospitals ORDER BY verified_status = "verified" DESC, name ASC')->fetchAll();
    }

    public function searchPublic(string $query): array
    {
        $stmt = $this->db->prepare('SELECT * FROM hospitals WHERE name LIKE :query_name OR city LIKE :query_city OR country LIKE :query_country OR address LIKE :query_address ORDER BY verified_status = "verified" DESC, name ASC');
        $term = '%' . $query . '%';
        $stmt->execute([
            'query_name' => $term,
            'query_city' => $term,
            'query_country' => $term,
            'query_address' => $term,
        ]);
        return $stmt->fetchAll();
    }

    public function findByUserId(int $userId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM hospitals WHERE user_id = :user_id LIMIT 1');
        $stmt->execute(['user_id' => $userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function pending(): array
    {
        $stmt = $this->db->query('SELECT * FROM hospitals WHERE verified_status = "pending" ORDER BY id DESC');
        return $stmt->fetchAll();
    }

    public function verifyByUserId(int $userId, string $status): bool
    {
        $stmt = $this->db->prepare('UPDATE hospitals SET verified_status = :status WHERE user_id = :user_id');
        return $stmt->execute(['status' => $status, 'user_id' => $userId]);
    }
}
