<?php

namespace App\Models;

use App\Core\Model;

class User extends Model
{
    protected string $table = 'users';

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findByPhone(string $phone): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE telephone = ? LIMIT 1');
        $stmt->execute([$phone]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findByEmailOrPhone(string $identifier): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM users WHERE email = ? OR telephone = ? LIMIT 1'
        );
        $stmt->execute([$identifier, $identifier]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO users (nom, prenom, email, telephone, password, photo, bio)
             VALUES (:nom, :prenom, :email, :telephone, :password, :photo, :bio)'
        );
        $stmt->execute([
            'nom'        => $data['nom'],
            'prenom'     => $data['prenom'],
            'email'      => $data['email'],
            'telephone'  => $data['telephone'],
            'password'   => password_hash($data['password'], PASSWORD_DEFAULT),
            'photo'      => $data['photo'] ?? null,
            'bio'        => $data['bio'] ?? null,
        ]);
        $id = (int) $this->db->lastInsertId();
        $this->assignUsername($id, $data['prenom'], $data['nom']);
        return $id;
    }

    public function assignUsername(int $id, string $prenom, string $nom): void
    {
        $base = $this->slug($prenom) . '.' . $this->slug($nom);
        $username = $base;
        $n = 1;
        while ($this->usernameTaken($username, $id)) {
            $username = $base . $n;
            $n++;
        }
        $stmt = $this->db->prepare('UPDATE users SET username = ? WHERE id = ?');
        $stmt->execute([$username, $id]);
    }

    public function findByUsername(string $username): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE username = ? AND statut = \'actif\' LIMIT 1');
        $stmt->execute([strtolower($username)]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    private function slug(string $value): string
    {
        $value = mb_strtolower($value);
        $value = strtr($value, [
            'à' => 'a', 'á' => 'a', 'â' => 'a', 'ä' => 'a', 'ã' => 'a', 'å' => 'a',
            'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e',
            'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i',
            'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'ö' => 'o', 'õ' => 'o',
            'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u',
            'ç' => 'c', 'ñ' => 'n', 'œ' => 'oe', 'æ' => 'ae', 'ÿ' => 'y',
        ]);
        $value = (string) preg_replace('/[^a-z0-9]+/', '', $value);
        return $value !== '' ? $value : 'user';
    }

    public function updateProfile(int $id, array $data): bool
    {
        $fields = [];
        $params = ['id' => $id];

        foreach (['nom', 'prenom', 'bio', 'photo', 'cover_photo', 'telephone', 'email', 'username', 'privacy_photo', 'privacy_last_seen', 'privacy_status', 'privacy_contact', 'privacy_group'] as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "{$field} = :{$field}";
                $params[$field] = $data[$field];
            }
        }

        if (empty($fields)) {
            return false;
        }

        $sql = 'UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = :id';
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function updatePassword(int $id, string $password): bool
    {
        $stmt = $this->db->prepare('UPDATE users SET password = ? WHERE id = ?');
        return $stmt->execute([password_hash($password, PASSWORD_DEFAULT), $id]);
    }

    public function updatePresence(int $id, bool $online): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE users SET is_online = ?, derniere_connexion = NOW() WHERE id = ?'
        );
        return $stmt->execute([$online ? 1 : 0, $id]);
    }

    public function search(string $query, int $excludeId, int $limit = 20): array
    {
        $like = '%' . $query . '%';
        $clean = ltrim($query, '@');
        $stmt = $this->db->prepare(
            'SELECT id, nom, prenom, username, email, telephone, photo, bio, is_online, derniere_connexion
             FROM users
             WHERE statut = \'actif\'
               AND id != ?
               AND (nom LIKE ? OR prenom LIKE ? OR email LIKE ? OR telephone LIKE ?
                    OR username LIKE ? OR username LIKE ?
                    OR CONCAT(prenom, \' \', nom) LIKE ? OR CONCAT(nom, \' \', prenom) LIKE ?)
             ORDER BY
               CASE WHEN username = ? THEN 0 WHEN username LIKE ? THEN 1 ELSE 2 END,
               prenom, nom
             LIMIT ?'
        );
        $stmt->bindValue(1, $excludeId, \PDO::PARAM_INT);
        $stmt->bindValue(2, $like);
        $stmt->bindValue(3, $like);
        $stmt->bindValue(4, $like);
        $stmt->bindValue(5, $like);
        $stmt->bindValue(6, $like);
        $stmt->bindValue(7, '%' . $clean . '%');
        $stmt->bindValue(8, $like);
        $stmt->bindValue(9, $like);
        $stmt->bindValue(10, $clean);
        $stmt->bindValue(11, $clean . '%');
        $stmt->bindValue(12, $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function emailTaken(string $email, int $exceptId = 0): bool
    {
        $stmt = $this->db->prepare('SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1');
        $stmt->execute([$email, $exceptId]);
        return (bool) $stmt->fetch();
    }

    public function phoneTaken(string $phone, int $exceptId = 0): bool
    {
        $stmt = $this->db->prepare('SELECT id FROM users WHERE telephone = ? AND id != ? LIMIT 1');
        $stmt->execute([$phone, $exceptId]);
        return (bool) $stmt->fetch();
    }

    public function usernameTaken(string $username, int $exceptId = 0): bool
    {
        $stmt = $this->db->prepare('SELECT id FROM users WHERE username = ? AND id != ? LIMIT 1');
        $stmt->execute([$username, $exceptId]);
        return (bool) $stmt->fetch();
    }

    public function canSee(string $privacy, int $ownerId, ?int $viewerId, bool $isContact = false): bool
    {
        if ($viewerId && $viewerId === $ownerId) {
            return true;
        }
        return match ($privacy) {
            'nobody'   => false,
            'contacts' => $isContact,
            default    => true,
        };
    }

    public function publicProfile(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, nom, prenom, username, email, telephone, photo, cover_photo, bio, is_online,
                    derniere_connexion, privacy_photo, privacy_last_seen, privacy_status,
                    privacy_contact, privacy_group, created_at
             FROM users WHERE id = ? AND statut = \'actif\' LIMIT 1'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
}
