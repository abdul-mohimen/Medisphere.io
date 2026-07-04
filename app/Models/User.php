<?php
namespace App\Models;

class User extends BaseModel
{
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public function create(string $email, string $password, string $userType, string $status = 'active'): int
    {
        $stmt = $this->db->prepare('INSERT INTO users (email, password, user_type, status, created_at) VALUES (:email, :password, :user_type, :status, NOW())');
        $stmt->execute([
            'email' => $email,
            'password' => password_hash($password, config('security.password_algo')),
            'user_type' => $userType,
            'status' => $status,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function countByType(): array
    {
        $stmt = $this->db->query('SELECT user_type, COUNT(*) as total FROM users GROUP BY user_type');
        $rows = $stmt->fetchAll();
        $mapped = ['patient' => 0, 'doctor' => 0, 'hospital' => 0, 'admin' => 0];
        foreach ($rows as $row) {
            $mapped[$row['user_type']] = (int) $row['total'];
        }
        return $mapped;
    }

    public function all(?string $type = null): array
    {
        if ($type) {
            $stmt = $this->db->prepare('SELECT * FROM users WHERE user_type = :user_type ORDER BY created_at DESC');
            $stmt->execute(['user_type' => $type]);
            return $stmt->fetchAll();
        }

        return $this->db->query('SELECT * FROM users ORDER BY created_at DESC')->fetchAll();
    }

    public function messagingDirectory(int $currentUserId, string $currentUserType, string $query = ''): array
    {
        $allowedTypes = match ($currentUserType) {
            'patient' => ['doctor'],
            'doctor' => ['patient', 'hospital'],
            'hospital' => ['doctor'],
            'admin' => ['patient', 'doctor', 'hospital'],
            default => ['patient', 'doctor', 'hospital', 'admin'],
        };

        $typePlaceholders = [];
        $params = ['current_user_id' => $currentUserId, 'suspended_status' => 'suspended'];
        foreach ($allowedTypes as $index => $type) {
            $key = 'type_' . $index;
            $typePlaceholders[] = ':' . $key;
            $params[$key] = $type;
        }

        $where = [
            'u.id != :current_user_id',
            'u.status != :suspended_status',
            'u.user_type IN (' . implode(', ', $typePlaceholders) . ')',
        ];

        $query = trim($query);
        if ($query !== '') {
            $where[] = '(u.email LIKE :query_email OR p.name LIKE :query_patient OR d.name LIKE :query_doctor OR h.name LIKE :query_hospital OR d.specialization LIKE :query_specialization OR h.city LIKE :query_city)';
            $term = '%' . $query . '%';
            $params['query_email'] = $term;
            $params['query_patient'] = $term;
            $params['query_doctor'] = $term;
            $params['query_hospital'] = $term;
            $params['query_specialization'] = $term;
            $params['query_city'] = $term;
        }

        $sql = 'SELECT
                u.id,
                u.email,
                u.user_type,
                u.status,
                COALESCE(p.name, d.name, h.name, u.email) AS name,
                COALESCE(p.phone, h.phone) AS phone,
                d.specialization,
                h.city,
                h.country
            FROM users u
            LEFT JOIN patients p ON p.user_id = u.id
            LEFT JOIN doctors d ON d.user_id = u.id
            LEFT JOIN hospitals h ON h.user_id = u.id
            WHERE ' . implode(' AND ', $where) . '
            ORDER BY name ASC, u.email ASC
            LIMIT 80';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function updateStatus(int $id, string $status): bool
    {
        $stmt = $this->db->prepare('UPDATE users SET status = :status WHERE id = :id');
        return $stmt->execute(['status' => $status, 'id' => $id]);
    }

    public function updateEmail(int $id, string $email): bool
    {
        $stmt = $this->db->prepare('UPDATE users SET email = :email WHERE id = :id');
        return $stmt->execute(['email' => $email, 'id' => $id]);
    }

    public function deleteById(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM users WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }

    public function updateSubscriptionStatus(int $id, string $status, ?string $planSlug = null, ?string $expiresAt = null): bool
    {
        try {
            $stmt = $this->db->prepare('UPDATE users
                SET subscription_status = :subscription_status,
                    subscription_plan = :subscription_plan,
                    subscription_expires_at = :subscription_expires_at
                WHERE id = :id');
            return $stmt->execute([
                'subscription_status' => $status,
                'subscription_plan' => $planSlug,
                'subscription_expires_at' => $expiresAt,
                'id' => $id,
            ]);
        } catch (\PDOException) {
            return false;
        }
    }

    public function contactProfile(int $id): ?array
    {
        $user = $this->findById($id);
        if (!$user) {
            return null;
        }

        $profile = [
            'id' => (int) $user['id'],
            'email' => $user['email'],
            'user_type' => $user['user_type'],
            'status' => $user['status'],
            'name' => $user['email'],
            'phone' => null,
        ];

        if ($user['user_type'] === 'patient') {
            $stmt = $this->db->prepare('SELECT name, phone FROM patients WHERE user_id = :user_id LIMIT 1');
            $stmt->execute(['user_id' => $id]);
            $row = $stmt->fetch();
            if ($row) {
                $profile['name'] = $row['name'] ?: $profile['name'];
                $profile['phone'] = $row['phone'] ?: null;
            }
        } elseif ($user['user_type'] === 'doctor') {
            $stmt = $this->db->prepare('SELECT d.name, h.phone AS hospital_phone FROM doctors d LEFT JOIN hospitals h ON h.id = d.hospital_id WHERE d.user_id = :user_id LIMIT 1');
            $stmt->execute(['user_id' => $id]);
            $row = $stmt->fetch();
            if ($row) {
                $profile['name'] = $row['name'] ?: $profile['name'];
                $profile['phone'] = $row['hospital_phone'] ?: null;
            }
        } elseif ($user['user_type'] === 'hospital') {
            $stmt = $this->db->prepare('SELECT name, phone FROM hospitals WHERE user_id = :user_id LIMIT 1');
            $stmt->execute(['user_id' => $id]);
            $row = $stmt->fetch();
            if ($row) {
                $profile['name'] = $row['name'] ?: $profile['name'];
                $profile['phone'] = $row['phone'] ?: null;
            }
        }

        return $profile;
    }
}
