<?php

namespace App\Models;

use App\Core\Model;

class BlockedUser extends Model
{
    protected string $table = 'blocked_users';

    public function block(int $blockerId, int $blockedId): bool
    {
        if ($blockerId === $blockedId) {
            return false;
        }
        $stmt = $this->db->prepare(
            'INSERT IGNORE INTO blocked_users (blocker_id, blocked_id) VALUES (?, ?)'
        );
        return $stmt->execute([$blockerId, $blockedId]);
    }

    public function unblock(int $blockerId, int $blockedId): bool
    {
        $stmt = $this->db->prepare(
            'DELETE FROM blocked_users WHERE blocker_id = ? AND blocked_id = ?'
        );
        return $stmt->execute([$blockerId, $blockedId]);
    }

    public function isBlocked(int $blockerId, int $blockedId): bool
    {
        $stmt = $this->db->prepare(
            'SELECT id FROM blocked_users WHERE blocker_id = ? AND blocked_id = ? LIMIT 1'
        );
        $stmt->execute([$blockerId, $blockedId]);
        return (bool) $stmt->fetch();
    }

    public function isEitherBlocked(int $userA, int $userB): bool
    {
        $stmt = $this->db->prepare(
            'SELECT id FROM blocked_users
             WHERE (blocker_id = ? AND blocked_id = ?)
                OR (blocker_id = ? AND blocked_id = ?)
             LIMIT 1'
        );
        $stmt->execute([$userA, $userB, $userB, $userA]);
        return (bool) $stmt->fetch();
    }

    public function listBlocked(int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT u.id, u.nom, u.prenom, u.photo, b.created_at
             FROM blocked_users b
             INNER JOIN users u ON u.id = b.blocked_id
             WHERE b.blocker_id = ?
             ORDER BY b.created_at DESC'
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }
}
