<?php

namespace App\Models;

use App\Core\Model;

class Conversation extends Model
{
    protected string $table = 'conversations';

    public function inbox(int $userId): array
    {
        $sql = 'SELECT c.id,
                       other.id AS other_id, other.prenom, other.nom, other.photo, other.username, other.is_online,
                       lm.contenu AS last_message, lm.created_at AS last_at,
                       (
                         SELECT COUNT(*)
                         FROM messages m
                         LEFT JOIN message_reads r ON r.message_id = m.id AND r.user_id = :me_read
                         WHERE m.conversation_id = c.id
                           AND m.sender_id != :me_sender
                           AND m.is_deleted_for_all = 0
                           AND r.read_at IS NULL
                       ) AS unread
                FROM conversations c
                INNER JOIN conversation_members mine
                    ON mine.conversation_id = c.id AND mine.user_id = :me_member AND mine.left_at IS NULL
                INNER JOIN conversation_members theirs
                    ON theirs.conversation_id = c.id AND theirs.user_id != :me_other AND theirs.left_at IS NULL
                INNER JOIN users other ON other.id = theirs.user_id
                LEFT JOIN messages lm ON lm.id = (
                    SELECT m2.id FROM messages m2
                    WHERE m2.conversation_id = c.id AND m2.is_deleted_for_all = 0
                    ORDER BY m2.id DESC LIMIT 1
                )
                WHERE c.type = \'private\'
                ORDER BY COALESCE(lm.created_at, c.created_at) DESC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'me_read' => $userId,
            'me_sender' => $userId,
            'me_member' => $userId,
            'me_other' => $userId,
        ]);
        return $stmt->fetchAll();
    }

    public function unreadCount(int $userId): int
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*)
             FROM messages m
             INNER JOIN conversation_members cm ON cm.conversation_id = m.conversation_id AND cm.user_id = ? AND cm.left_at IS NULL
             LEFT JOIN message_reads r ON r.message_id = m.id AND r.user_id = ?
             WHERE m.sender_id != ? AND m.is_deleted_for_all = 0 AND r.read_at IS NULL'
        );
        $stmt->execute([$userId, $userId, $userId]);
        return (int) $stmt->fetchColumn();
    }

    public function findPrivate(int $userId, int $otherId): ?int
    {
        $stmt = $this->db->prepare(
            'SELECT c.id
             FROM conversations c
             INNER JOIN conversation_members a ON a.conversation_id = c.id AND a.user_id = ?
             INNER JOIN conversation_members b ON b.conversation_id = c.id AND b.user_id = ?
             WHERE c.type = \'private\'
             LIMIT 1'
        );
        $stmt->execute([$userId, $otherId]);
        $id = $stmt->fetchColumn();
        return $id ? (int) $id : null;
    }

    public function openPrivate(int $userId, int $otherId): int
    {
        $existing = $this->findPrivate($userId, $otherId);
        if ($existing) {
            return $existing;
        }

        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare(
                'INSERT INTO conversations (type, created_by) VALUES (\'private\', ?)'
            );
            $stmt->execute([$userId]);
            $id = (int) $this->db->lastInsertId();
            $member = $this->db->prepare(
                'INSERT INTO conversation_members (conversation_id, user_id, role) VALUES (?, ?, \'member\')'
            );
            $member->execute([$id, $userId]);
            $member->execute([$id, $otherId]);
            $this->db->commit();
            return $id;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function participant(int $conversationId, int $userId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT other.id, other.prenom, other.nom, other.photo, other.username, other.is_online
             FROM conversation_members mine
             INNER JOIN conversation_members theirs
                ON theirs.conversation_id = mine.conversation_id AND theirs.user_id != mine.user_id
             INNER JOIN users other ON other.id = theirs.user_id
             INNER JOIN conversations c ON c.id = mine.conversation_id AND c.type = \'private\'
             WHERE mine.conversation_id = ? AND mine.user_id = ? AND mine.left_at IS NULL
             LIMIT 1'
        );
        $stmt->execute([$conversationId, $userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function messages(int $conversationId, int $userId, int $afterId = 0): array
    {
        $stmt = $this->db->prepare(
            'SELECT m.id, m.sender_id, m.contenu, m.created_at, m.is_deleted_for_all,
                    u.prenom, u.nom, u.photo
             FROM messages m
             INNER JOIN users u ON u.id = m.sender_id
             WHERE m.conversation_id = ?
               AND m.id > ?
               AND NOT EXISTS (
                    SELECT 1 FROM message_deletions d
                    WHERE d.message_id = m.id AND d.user_id = ?
               )
             ORDER BY m.id ASC
             LIMIT 80'
        );
        $stmt->execute([$conversationId, $afterId, $userId]);
        return $stmt->fetchAll();
    }

    public function send(int $conversationId, int $userId, string $contenu): array
    {
        $stmt = $this->db->prepare(
            'INSERT INTO messages (conversation_id, sender_id, type, contenu) VALUES (?, ?, \'text\', ?)'
        );
        $stmt->execute([$conversationId, $userId, $contenu]);
        $id = (int) $this->db->lastInsertId();
        $this->db->prepare('UPDATE conversations SET updated_at = NOW() WHERE id = ?')->execute([$conversationId]);
        $this->markRead($conversationId, $userId);
        $row = $this->messages($conversationId, $userId, $id - 1);
        return $row[0] ?? ['id' => $id, 'sender_id' => $userId, 'contenu' => $contenu, 'created_at' => date('Y-m-d H:i:s')];
    }

    public function markRead(int $conversationId, int $userId): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO message_reads (message_id, user_id, delivered_at, read_at)
             SELECT m.id, ?, NOW(), NOW()
             FROM messages m
             WHERE m.conversation_id = ? AND m.sender_id != ?
             ON DUPLICATE KEY UPDATE read_at = NOW(), delivered_at = COALESCE(delivered_at, NOW())'
        );
        $stmt->execute([$userId, $conversationId, $userId]);
    }
}
