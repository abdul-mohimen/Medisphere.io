<?php
namespace App\Models;

use PDO;

class Patient extends BaseModel
{
    private static bool $profileImageColumnsEnsured = false;

    public function create(array $data): bool
    {
        $stmt = $this->db->prepare('INSERT INTO patients (user_id, name, age, gender, blood_group, address, phone, city, country, profile_image) VALUES (:user_id, :name, :age, :gender, :blood_group, :address, :phone, :city, :country, :profile_image)');
        return $stmt->execute([
            'user_id' => $data['user_id'],
            'name' => $data['name'],
            'age' => $data['age'],
            'gender' => $data['gender'],
            'blood_group' => $data['blood_group'],
            'address' => $data['address'],
            'phone' => $data['phone'],
            'city' => $data['city'],
            'country' => $data['country'],
            'profile_image' => $data['profile_image'] ?? null,
        ]);
    }

    public function findByUserId(int $userId): ?array
    {
        $stmt = $this->db->prepare('SELECT p.*, u.email, u.status FROM patients p JOIN users u ON u.id = p.user_id WHERE p.user_id = :user_id LIMIT 1');
        $stmt->execute(['user_id' => $userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function updateProfile(int $userId, array $data): bool
    {
        $stmt = $this->db->prepare('INSERT INTO patients (user_id, name, age, gender, blood_group, address, phone, city, country)
            VALUES (:user_id, :name, :age, :gender, :blood_group, :address, :phone, :city, :country)
            ON DUPLICATE KEY UPDATE
                name = VALUES(name),
                age = VALUES(age),
                gender = VALUES(gender),
                blood_group = VALUES(blood_group),
                address = VALUES(address),
                phone = VALUES(phone),
                city = VALUES(city),
                country = VALUES(country)');

        return $stmt->execute([
            'user_id' => $userId,
            'name' => $data['name'],
            'age' => $data['age'],
            'gender' => $data['gender'],
            'blood_group' => $data['blood_group'],
            'address' => $data['address'],
            'phone' => $data['phone'],
            'city' => $data['city'],
            'country' => $data['country'],
        ]);
    }

    public function updateProfileImage(int $userId, string $imageData, string $mimeType): bool
    {
        $this->ensureProfileImageColumns();

        $stmt = $this->db->prepare('UPDATE patients
            SET profile_image = NULL,
                profile_image_data = :profile_image_data,
                profile_image_mime = :profile_image_mime,
                profile_image_updated_at = NOW()
            WHERE user_id = :user_id');
        $stmt->bindValue(':profile_image_data', $imageData, PDO::PARAM_LOB);
        $stmt->bindValue(':profile_image_mime', $mimeType);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);

        return $stmt->execute();
    }

    public function clearProfileImage(int $userId): bool
    {
        $this->ensureProfileImageColumns();

        $stmt = $this->db->prepare('UPDATE patients
            SET profile_image = NULL,
                profile_image_data = NULL,
                profile_image_mime = NULL,
                profile_image_updated_at = NULL
            WHERE user_id = :user_id');

        return $stmt->execute(['user_id' => $userId]);
    }

    public function ensureProfileImageColumns(): void
    {
        if (self::$profileImageColumnsEnsured) {
            return;
        }

        $columns = [];
        $stmt = $this->db->query('SHOW COLUMNS FROM patients');
        foreach ($stmt->fetchAll() as $column) {
            $columns[$column['Field']] = true;
        }

        $alter = [];
        if (empty($columns['profile_image_data'])) {
            $alter[] = 'ADD COLUMN profile_image_data MEDIUMBLOB NULL AFTER profile_image';
        }
        if (empty($columns['profile_image_mime'])) {
            $alter[] = 'ADD COLUMN profile_image_mime VARCHAR(100) NULL AFTER profile_image_data';
        }
        if (empty($columns['profile_image_updated_at'])) {
            $alter[] = 'ADD COLUMN profile_image_updated_at DATETIME NULL AFTER profile_image_mime';
        }

        foreach ($alter as $clause) {
            $this->db->exec('ALTER TABLE patients ' . $clause);
        }

        self::$profileImageColumnsEnsured = true;
    }
}
