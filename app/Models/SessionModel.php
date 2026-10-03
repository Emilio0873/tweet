<?php

namespace App\Models;

use App\Core\Model;

class SessionModel extends Model
{
    protected string $table = 'sessions';

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO sessions (user_id, session_token, device, ip_address)
             VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['user_id'],
            $data['session_token'],
            $data['device'] ?? null,
            $data['ip_address'] ?? null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function deleteByToken(string $token): bool
    {
        $stmt = $this->db->prepare('DELETE FROM sessions WHERE session_token = ?');
        return $stmt->execute([$token]);
    }

    public function deleteAllForUser(int $userId, ?string $exceptToken = null): bool
    {
        if ($exceptToken) {
            $stmt = $this->db->prepare(
                'DELETE FROM sessions WHERE user_id = ? AND session_token != ?'
            );
            return $stmt->execute([$userId, $exceptToken]);
        }
        $stmt = $this->db->prepare('DELETE FROM sessions WHERE user_id = ?');
        return $stmt->execute([$userId]);
    }

    public function listForUser(int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT id, device, ip_address, last_activity, created_at, session_token
             FROM sessions WHERE user_id = ? ORDER BY last_activity DESC'
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function touch(string $token): void
    {
        $stmt = $this->db->prepare(
            'UPDATE sessions SET last_activity = NOW() WHERE session_token = ?'
        );
        $stmt->execute([$token]);
    }
}
