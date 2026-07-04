<?php
namespace App\Models;

class Doctor extends BaseModel
{
    public function create(array $data): bool
    {
        $stmt = $this->db->prepare('INSERT INTO doctors (user_id, name, specialization, qualification_details, license_number, experience, hospital_id, verified_status, profile_image, consultation_fee, languages_spoken, bio) VALUES (:user_id, :name, :specialization, :qualification_details, :license_number, :experience, :hospital_id, :verified_status, :profile_image, :consultation_fee, :languages_spoken, :bio)');
        return $stmt->execute([
            'user_id' => $data['user_id'],
            'name' => $data['name'],
            'specialization' => $data['specialization'],
            'qualification_details' => $data['qualification_details'],
            'license_number' => $data['license_number'],
            'experience' => $data['experience'],
            'hospital_id' => $data['hospital_id'] ?: null,
            'verified_status' => $data['verified_status'] ?? 'pending',
            'profile_image' => $data['profile_image'] ?? null,
            'consultation_fee' => $data['consultation_fee'] ?? 0,
            'languages_spoken' => $data['languages_spoken'] ?? null,
            'bio' => $data['bio'] ?? null,
        ]);
    }

    public function findByUserId(int $userId): ?array
    {
        $stmt = $this->db->prepare('SELECT d.*, u.email, u.status AS user_status, h.user_id AS hospital_user_id, h.name AS hospital_name, h.phone AS hospital_phone, h.email AS hospital_email, h.coordinates AS hospital_coordinates, h.address AS hospital_address, h.city AS hospital_city, h.country AS hospital_country FROM doctors d JOIN users u ON u.id = d.user_id LEFT JOIN hospitals h ON h.id = d.hospital_id WHERE d.user_id = :user_id LIMIT 1');
        $stmt->execute(['user_id' => $userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function search(array $filters = []): array
    {
        $sql = 'SELECT d.*, h.name AS hospital_name, h.phone AS hospital_phone, h.email AS hospital_email, h.coordinates AS hospital_coordinates, h.address AS hospital_address, h.city AS hospital_city, h.country AS hospital_country, u.status, COALESCE(da.availability_status, "offline") AS availability_status, da.updated_at AS availability_updated_at
                FROM doctors d
                JOIN users u ON u.id = d.user_id
                LEFT JOIN hospitals h ON h.id = d.hospital_id
                LEFT JOIN doctor_availability da ON da.doctor_id = d.user_id
                WHERE 1=1';
        $params = [];

        if (!empty($filters['specialization'])) {
            $sql .= ' AND d.specialization LIKE :specialization';
            $params['specialization'] = '%' . $filters['specialization'] . '%';
        }
        if (!empty($filters['hospital_id'])) {
            $sql .= ' AND d.hospital_id = :hospital_id';
            $params['hospital_id'] = $filters['hospital_id'];
        }
        if (!empty($filters['max_fee'])) {
            $sql .= ' AND d.consultation_fee <= :max_fee';
            $params['max_fee'] = $filters['max_fee'];
        }
        if (!empty($filters['location'])) {
            $sql .= ' AND (h.city LIKE :location_city OR h.country LIKE :location_country OR h.address LIKE :location_address OR h.name LIKE :location_hospital)';
            $location = '%' . $filters['location'] . '%';
            $params['location_city'] = $location;
            $params['location_country'] = $location;
            $params['location_address'] = $location;
            $params['location_hospital'] = $location;
        }
        if (!empty($filters['verified_only'])) {
            $sql .= ' AND d.verified_status = "verified"';
        }
        if (!empty($filters['active_only'])) {
            $sql .= ' AND u.status = "active"';
        }

        $sql .= ' ORDER BY d.verified_status = "verified" DESC, d.experience DESC, d.name ASC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }


    public function searchPublic(string $query): array
    {
        $stmt = $this->db->prepare('SELECT d.*, h.name AS hospital_name, h.phone AS hospital_phone, h.email AS hospital_email, h.city AS hospital_city, h.country AS hospital_country, u.status
            FROM doctors d
            JOIN users u ON u.id = d.user_id
            LEFT JOIN hospitals h ON h.id = d.hospital_id
            WHERE u.status = "active" AND (d.name LIKE :query_name OR d.specialization LIKE :query_specialization OR h.name LIKE :query_hospital OR h.city LIKE :query_city OR h.country LIKE :query_country)
            ORDER BY d.verified_status = "verified" DESC, d.experience DESC, d.name ASC');
        $term = '%' . $query . '%';
        $stmt->execute([
            'query_name' => $term,
            'query_specialization' => $term,
            'query_hospital' => $term,
            'query_city' => $term,
            'query_country' => $term,
        ]);
        return $stmt->fetchAll();
    }
    public function pending(): array
    {
        $stmt = $this->db->query('SELECT d.*, u.email FROM doctors d JOIN users u ON u.id = d.user_id WHERE d.verified_status = "pending" ORDER BY u.created_at DESC');
        return $stmt->fetchAll();
    }

    public function verify(int $userId, string $status): bool
    {
        $stmt = $this->db->prepare('UPDATE doctors SET verified_status = :status WHERE user_id = :user_id');
        return $stmt->execute(['status' => $status, 'user_id' => $userId]);
    }

    public function updatePrimaryHospital(int $userId, ?int $hospitalId): bool
    {
        $stmt = $this->db->prepare('UPDATE doctors SET hospital_id = :hospital_id WHERE user_id = :user_id');
        return $stmt->execute([
            'hospital_id' => $hospitalId,
            'user_id' => $userId,
        ]);
    }
}
