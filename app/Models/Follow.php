<?php

namespace App\Models;

use App\Core\Model;

class Follow extends Model
{
    protected string $table = 'follows';

    public function follow(int $followerId, int $followingId): bool
    {
        if ($followerId === $followingId) {
            return false;
        }
        $stmt = $this->db->prepare(
            'INSERT IGNORE INTO follows (follower_id, following_id) VALUES (?, ?)'
        );
        return $stmt->execute([$followerId, $followingId]);
    }

    public function unfollow(int $followerId, int $followingId): bool
    {
        $stmt = $this->db->prepare(
            'DELETE FROM follows WHERE follower_id = ? AND following_id = ?'
        );
        return $stmt->execute([$followerId, $followingId]);
    }

    public function isFollowing(int $followerId, int $followingId): bool
    {
        $stmt = $this->db->prepare(
            'SELECT id FROM follows WHERE follower_id = ? AND following_id = ? LIMIT 1'
        );
        $stmt->execute([$followerId, $followingId]);
        return (bool) $stmt->fetch();
    }

    public function countFollowers(int $userId): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM follows WHERE following_id = ?');
        $stmt->execute([$userId]);
        return (int) $stmt->fetchColumn();
    }

    public function countFollowing(int $userId): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM follows WHERE follower_id = ?');
        $stmt->execute([$userId]);
        return (int) $stmt->fetchColumn();
    }

    public function suggestions(int $userId, int $limit = 4): array
    {
        $stmt = $this->db->prepare(
            'SELECT u.id, u.prenom, u.nom, u.username, u.photo, u.bio
             FROM users u
             WHERE u.statut = \'actif\'
               AND u.id != :me
               AND u.id NOT IN (SELECT following_id FROM follows WHERE follower_id = :me2)
               AND u.id NOT IN (
                    SELECT blocked_id FROM blocked_users WHERE blocker_id = :me3
                    UNION
                    SELECT blocker_id FROM blocked_users WHERE blocked_id = :me4
               )
             ORDER BY u.created_at DESC
             LIMIT :lim'
        );
        $stmt->bindValue('me', $userId, \PDO::PARAM_INT);
        $stmt->bindValue('me2', $userId, \PDO::PARAM_INT);
        $stmt->bindValue('me3', $userId, \PDO::PARAM_INT);
        $stmt->bindValue('me4', $userId, \PDO::PARAM_INT);
        $stmt->bindValue('lim', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
