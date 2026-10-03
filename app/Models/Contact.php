<?php

namespace App\Models;

use App\Core\Model;

class Contact extends Model
{
    protected string $table = 'contacts';

    public function add(int $userId, int $contactId): bool
    {
        if ($userId === $contactId) {
            return false;
        }

        $stmt = $this->db->prepare(
            'INSERT IGNORE INTO contacts (user_id, contact_id) VALUES (?, ?)'
        );
        return $stmt->execute([$userId, $contactId]);
    }

    public function remove(int $userId, int $contactId): bool
    {
        $stmt = $this->db->prepare(
            'DELETE FROM contacts WHERE user_id = ? AND contact_id = ?'
        );
        return $stmt->execute([$userId, $contactId]);
    }

    public function exists(int $userId, int $contactId): bool
    {
        $stmt = $this->db->prepare(
            'SELECT id FROM contacts WHERE user_id = ? AND contact_id = ? LIMIT 1'
        );
        $stmt->execute([$userId, $contactId]);
        return (bool) $stmt->fetch();
    }

    public function listForUser(int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT u.id, u.nom, u.prenom, u.email, u.telephone, u.photo, u.bio,
                    u.is_online, u.derniere_connexion, c.created_at AS contact_since
             FROM contacts c
             INNER JOIN users u ON u.id = c.contact_id
             WHERE c.user_id = ? AND u.statut = \'actif\'
             ORDER BY u.prenom, u.nom'
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }
}
