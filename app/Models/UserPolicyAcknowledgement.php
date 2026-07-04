<?php
namespace App\Models;

class UserPolicyAcknowledgement extends BaseModel
{
    public function byUser(int $userId): array
    {
        $stmt = $this->db->prepare('SELECT upa.*, pd.title, pd.slug, pd.version_label, pd.category FROM user_policy_acknowledgements upa JOIN policy_documents pd ON pd.id = upa.policy_id WHERE upa.user_id = :user_id ORDER BY upa.acknowledged_at DESC');
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll();
    }

    public function acknowledge(int $policyId, int $userId): bool
    {
        $stmt = $this->db->prepare('INSERT INTO user_policy_acknowledgements (policy_id, user_id, acknowledged_at) VALUES (:policy_id, :user_id, NOW()) ON DUPLICATE KEY UPDATE acknowledged_at = NOW()');
        return $stmt->execute(['policy_id' => $policyId, 'user_id' => $userId]);
    }

    public function countByPolicy(int $policyId): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM user_policy_acknowledgements WHERE policy_id = :policy_id');
        $stmt->execute(['policy_id' => $policyId]);
        return (int) $stmt->fetchColumn();
    }
}
