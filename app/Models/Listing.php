<?php

namespace App\Models;

use App\Core\Model;

class Listing extends Model
{
    protected string $table = 'listings';

    public const CATEGORIES = [
        'artisans'     => 'Artisans',
        'immobilier'   => 'Immobilier',
        'vehicules'    => 'Véhicules',
        'electronique' => 'Électronique',
        'maison'       => 'Maison',
        'mode'         => 'Mode',
        'loisirs'      => 'Loisirs',
        'emploi'       => 'Emploi',
        'services'     => 'Services',
    ];

    public function feed(?int $viewerId = null, int $limit = 30): array
    {
        return $this->queryList('l.created_at DESC', $limit, '1=1', $viewerId);
    }

    public function homeRails(?int $viewerId = null): array
    {
        return [
            'popular'     => $this->queryList('l.views DESC, l.created_at DESC', 8, '1=1', $viewerId),
            'recent'      => $this->queryList('l.created_at DESC', 8, '1=1', $viewerId),
            'recommended' => $this->queryList('l.is_featured DESC, l.views DESC, l.created_at DESC', 8, 'l.is_featured = 1', $viewerId),
        ];
    }

    public function search(array $filters): array
    {
        $where = ["l.status = 'active'"];
        $params = [];

        $q = trim((string) ($filters['q'] ?? ''));
        if ($q !== '') {
            $where[] = '(l.title LIKE :q OR l.description LIKE :q2 OR l.city LIKE :q3)';
            $like = '%' . $q . '%';
            $params['q'] = $like;
            $params['q2'] = $like;
            $params['q3'] = $like;
        }
        if (!empty($filters['category']) && isset(self::CATEGORIES[$filters['category']])) {
            $where[] = 'l.category = :category';
            $params['category'] = $filters['category'];
        }
        if (!empty($filters['city'])) {
            $where[] = 'l.city LIKE :city';
            $params['city'] = '%' . $filters['city'] . '%';
        }

        $sort = $filters['sort'] ?? 'recent';
        $order = match ($sort) {
            'price_asc'  => 'l.price IS NULL, l.price ASC',
            'price_desc' => 'l.price IS NULL, l.price DESC',
            'popular'    => 'l.views DESC, l.created_at DESC',
            default      => 'l.created_at DESC',
        };

        $sql = $this->selectSql(implode(' AND ', $where), $order, 24);
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $this->withImages($stmt->fetchAll(), isset($filters['viewer']) ? (int) $filters['viewer'] : null);
    }

    public function findPublic(int $id, ?int $viewerId = null): ?array
    {
        $sql = $this->selectSql('l.id = :id AND l.status = \'active\'', 'l.id', 1);
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }
        $rows = $this->withImages([$row], $viewerId);
        return $rows[0] ?? null;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO listings (user_id, title, description, price, category, city)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['user_id'],
            $data['title'],
            $data['description'] ?: null,
            $data['price'],
            $data['category'],
            $data['city'],
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function findOwned(int $id, int $userId): ?array
    {
        $sql = $this->selectSql('l.id = :id AND l.user_id = :uid AND l.status = \'active\'', 'l.id', 1);
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id, 'uid' => $userId]);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }
        $rows = $this->withImages([$row], $userId);
        return $rows[0] ?? null;
    }

    public function update(int $id, int $userId, array $data): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE listings
             SET title = ?, description = ?, price = ?, category = ?, city = ?
             WHERE id = ? AND user_id = ? AND status = \'active\''
        );
        $stmt->execute([
            $data['title'],
            $data['description'] ?: null,
            $data['price'],
            $data['category'],
            $data['city'],
            $id,
            $userId,
        ]);
        return $stmt->rowCount() >= 0;
    }

    public function softDelete(int $id, int $userId): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE listings SET status = \'hidden\' WHERE id = ? AND user_id = ? AND status = \'active\''
        );
        $stmt->execute([$id, $userId]);
        return $stmt->rowCount() > 0;
    }

    public function ownerId(int $listingId): ?int
    {
        $stmt = $this->db->prepare('SELECT user_id FROM listings WHERE id = ? AND status = \'active\' LIMIT 1');
        $stmt->execute([$listingId]);
        $value = $stmt->fetchColumn();
        return $value === false ? null : (int) $value;
    }

    public function addImage(int $listingId, string $path): void
    {
        $stmt = $this->db->prepare('INSERT INTO listing_images (listing_id, file_path) VALUES (?, ?)');
        $stmt->execute([$listingId, $path]);
    }

    public function hit(int $id): void
    {
        $stmt = $this->db->prepare('UPDATE listings SET views = views + 1 WHERE id = ?');
        $stmt->execute([$id]);
    }

    public function toggleSave(int $userId, int $listingId): bool
    {
        $stmt = $this->db->prepare('SELECT 1 FROM listing_saves WHERE user_id = ? AND listing_id = ?');
        $stmt->execute([$userId, $listingId]);
        if ($stmt->fetch()) {
            $this->db->prepare('DELETE FROM listing_saves WHERE user_id = ? AND listing_id = ?')->execute([$userId, $listingId]);
            return false;
        }
        $this->db->prepare('INSERT INTO listing_saves (user_id, listing_id) VALUES (?, ?)')->execute([$userId, $listingId]);
        return true;
    }

    public function toggleLike(int $userId, int $listingId): bool
    {
        $stmt = $this->db->prepare('SELECT 1 FROM listing_likes WHERE user_id = ? AND listing_id = ?');
        $stmt->execute([$userId, $listingId]);
        if ($stmt->fetch()) {
            $this->db->prepare('DELETE FROM listing_likes WHERE user_id = ? AND listing_id = ?')->execute([$userId, $listingId]);
            return false;
        }
        $this->db->prepare('INSERT INTO listing_likes (user_id, listing_id) VALUES (?, ?)')->execute([$userId, $listingId]);
        return true;
    }

    public function comments(int $listingId, int $limit = 30): array
    {
        $stmt = $this->db->prepare(
            'SELECT c.*, u.prenom, u.nom, u.photo
             FROM listing_comments c
             INNER JOIN users u ON u.id = c.user_id
             WHERE c.listing_id = ? AND u.role <> \'admin\'
             ORDER BY c.created_at ASC
             LIMIT ' . (int) $limit
        );
        $stmt->execute([$listingId]);
        return $stmt->fetchAll();
    }

    public function addComment(int $listingId, int $userId, string $contenu): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO listing_comments (listing_id, user_id, contenu) VALUES (?, ?, ?)'
        );
        $stmt->execute([$listingId, $userId, $contenu]);
        return (int) $this->db->lastInsertId();
    }

    public function count(): int
    {
        return (int) $this->db->query('SELECT COUNT(*) FROM listings')->fetchColumn();
    }

    public function byUser(int $userId, ?int $viewerId = null, int $limit = 40): array
    {
        $sql = $this->selectSql('l.status = \'active\' AND l.user_id = :uid', 'l.created_at DESC', $limit);
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['uid' => $userId]);
        return $this->withImages($stmt->fetchAll(), $viewerId ?? $userId);
    }

    private function queryList(string $order, int $limit, string $extra = '1=1', ?int $viewerId = null): array
    {
        $sql = $this->selectSql("l.status = 'active' AND {$extra}", $order, $limit);
        $rows = $this->db->query($sql)->fetchAll();
        return $this->withImages($rows, $viewerId);
    }

    private function selectSql(string $where, string $order, int $limit): string
    {
        return "SELECT l.*, u.prenom, u.nom, u.photo, u.username
                FROM listings l
                INNER JOIN users u ON u.id = l.user_id
                WHERE {$where}
                ORDER BY {$order}
                LIMIT {$limit}";
    }

    private function withImages(array $rows, ?int $viewerId = null): array
    {
        if (!$rows) {
            return [];
        }
        $ids = array_map(fn($row) => (int) $row['id'], $rows);
        $in = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->db->prepare("SELECT * FROM listing_images WHERE listing_id IN ($in) ORDER BY id ASC");
        $stmt->execute($ids);
        $images = [];
        foreach ($stmt->fetchAll() as $image) {
            $images[(int) $image['listing_id']][] = $image;
        }

        $likeCounts = [];
        $stmt = $this->db->prepare("SELECT listing_id, COUNT(*) AS c FROM listing_likes WHERE listing_id IN ($in) GROUP BY listing_id");
        $stmt->execute($ids);
        foreach ($stmt->fetchAll() as $row) {
            $likeCounts[(int) $row['listing_id']] = (int) $row['c'];
        }

        $commentCounts = [];
        $stmt = $this->db->prepare(
            "SELECT c.listing_id, COUNT(*) AS c
             FROM listing_comments c
             INNER JOIN users u ON u.id = c.user_id
             WHERE c.listing_id IN ($in) AND u.role <> 'admin'
             GROUP BY c.listing_id"
        );
        $stmt->execute($ids);
        foreach ($stmt->fetchAll() as $row) {
            $commentCounts[(int) $row['listing_id']] = (int) $row['c'];
        }

        $saved = [];
        $liked = [];
        if ($viewerId) {
            $stmt = $this->db->prepare("SELECT listing_id FROM listing_saves WHERE user_id = ? AND listing_id IN ($in)");
            $stmt->execute(array_merge([$viewerId], $ids));
            foreach ($stmt->fetchAll() as $row) {
                $saved[(int) $row['listing_id']] = true;
            }
            $stmt = $this->db->prepare("SELECT listing_id FROM listing_likes WHERE user_id = ? AND listing_id IN ($in)");
            $stmt->execute(array_merge([$viewerId], $ids));
            foreach ($stmt->fetchAll() as $row) {
                $liked[(int) $row['listing_id']] = true;
            }
        }

        $previewComments = [];
        $stmt = $this->db->prepare(
            "SELECT c.id, c.listing_id, c.contenu, c.created_at, u.prenom, u.nom, u.photo
             FROM listing_comments c
             INNER JOIN users u ON u.id = c.user_id
             WHERE c.listing_id IN ($in) AND u.role <> 'admin'
             ORDER BY c.created_at DESC"
        );
        $stmt->execute($ids);
        foreach ($stmt->fetchAll() as $row) {
            $lid = (int) $row['listing_id'];
            if (!isset($previewComments[$lid])) {
                $previewComments[$lid] = [];
            }
            if (count($previewComments[$lid]) < 3) {
                $previewComments[$lid][] = $row;
            }
        }
        foreach ($previewComments as &$list) {
            $list = array_reverse($list);
        }
        unset($list);

        foreach ($rows as &$row) {
            $id = (int) $row['id'];
            $row['images'] = $images[$id] ?? [];
            $row['saved'] = !empty($saved[$id]);
            $row['liked'] = !empty($liked[$id]);
            $row['like_count'] = $likeCounts[$id] ?? 0;
            $row['comment_count'] = $commentCounts[$id] ?? 0;
            $row['comments'] = $previewComments[$id] ?? [];
        }
        return $rows;
    }
}
