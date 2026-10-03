<?php

namespace App\Models;

use App\Core\Model;

class Post extends Model
{
    protected string $table = 'posts';

    public const REACTIONS = [
        'like'  => '👍',
        'love'  => '❤️',
        'laugh' => '😂',
        'wow'   => '😮',
        'sad'   => '😢',
        'angry' => '😡',
    ];

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO posts (user_id, contenu, location, link_url, visibility, is_draft)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['user_id'],
            $data['contenu'] !== '' ? $data['contenu'] : null,
            $data['location'] ?: null,
            $data['link_url'] ?: null,
            $data['visibility'],
            !empty($data['is_draft']) ? 1 : 0,
        ]);
        $id = (int) $this->db->lastInsertId();
        $this->syncTags($id, (string) ($data['contenu'] ?? ''));
        return $id;
    }

    public function updatePost(int $id, int $userId, array $data): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE posts
             SET contenu = ?, location = ?, link_url = ?, visibility = ?, is_draft = ?, is_edited = 1
             WHERE id = ? AND user_id = ?'
        );
        $ok = $stmt->execute([
            $data['contenu'] !== '' ? $data['contenu'] : null,
            $data['location'] ?: null,
            $data['link_url'] ?: null,
            $data['visibility'],
            !empty($data['is_draft']) ? 1 : 0,
            $id,
            $userId,
        ]);
        if ($ok) {
            $this->syncTags($id, (string) ($data['contenu'] ?? ''));
        }
        return $ok && $stmt->rowCount() >= 0;
    }

    public function addMedia(int $postId, array $file): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO post_media (post_id, file_name, file_path, file_type, mime, file_size)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $postId,
            $file['file_name'],
            $file['file_path'],
            $file['file_type'],
            $file['mime'],
            $file['file_size'],
        ]);
    }

    public function deleteOwned(int $id, int $userId): bool
    {
        $media = $this->mediaFor([$id]);
        $stmt = $this->db->prepare('DELETE FROM posts WHERE id = ? AND user_id = ?');
        $ok = $stmt->execute([$id, $userId]);
        if ($ok && $stmt->rowCount() > 0) {
            foreach ($media[$id] ?? [] as $file) {
                $path = dirname(__DIR__, 2) . '/public/uploads/' . $file['file_path'];
                if (is_file($path)) {
                    unlink($path);
                }
            }
            return true;
        }
        return false;
    }

    public function findRaw(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM posts WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findVisible(int $id, int $viewerId): ?array
    {
        [$where, $params] = $this->visibilitySql('p', $viewerId, true);
        $sql = "SELECT p.*, u.prenom, u.nom, u.photo, u.username
                FROM posts p
                INNER JOIN users u ON u.id = p.user_id
                WHERE p.id = :pid AND {$where}
                LIMIT 1";
        $params['pid'] = $id;
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }
        $rows = $this->hydrate([$row], $viewerId);
        return $rows[0] ?? null;
    }

    public function feed(int $viewerId, string $mode, int $page, int $limit = 8): array
    {
        $offset = max(0, ($page - 1) * $limit);
        $fetch = $limit + 1;

        if ($mode === 'foryou' || $mode === 'popular') {
            $rows = $this->feedRanked($viewerId, $offset, $fetch, $mode === 'popular' ? 90 : 30);
        } else {
            $rows = $this->feedChrono($viewerId, $mode, $offset, $fetch);
        }

        $hasMore = count($rows) > $limit;
        if ($hasMore) {
            $rows = array_slice($rows, 0, $limit);
        }

        return [
            'posts'   => $this->hydrateFeed($rows, $viewerId),
            'hasMore' => $hasMore,
        ];
    }

    public function countNewerThan(int $viewerId, string $mode, int $afterId): int
    {
        if ($afterId <= 0) {
            return 0;
        }
        [$where, $params] = $this->visibilitySql('p', $viewerId, false);
        $sql = "SELECT COUNT(*) FROM posts p WHERE p.is_draft = 0 AND p.id > :after AND {$where}";
        $params['after'] = $afterId;

        if ($mode === 'following') {
            $sql .= ' AND (p.user_id = :self OR EXISTS (
                SELECT 1 FROM follows f WHERE f.follower_id = :self2 AND f.following_id = p.user_id
            ))';
            $params['self'] = $viewerId;
            $params['self2'] = $viewerId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    public function byUser(int $profileId, int $viewerId, bool $drafts = false): array
    {
        if ($drafts) {
            $stmt = $this->db->prepare(
                'SELECT p.id AS post_id, p.created_at AS sort_at, NULL AS repost_user_id, NULL AS repost_name
                 FROM posts p
                 WHERE p.user_id = ? AND p.is_draft = 1
                 ORDER BY p.updated_at DESC, p.created_at DESC
                 LIMIT 20'
            );
            $stmt->execute([$profileId]);
            return $this->hydrateFeed($stmt->fetchAll(), $viewerId);
        }

        [$where, $params] = $this->visibilitySql('p', $viewerId, false);
        $sql = "SELECT p.id AS post_id, p.created_at AS sort_at, NULL AS repost_user_id, NULL AS repost_name
                FROM posts p
                WHERE p.user_id = :uid AND p.is_draft = 0 AND {$where}
                ORDER BY p.is_pinned DESC, p.created_at DESC
                LIMIT 30";
        $params['uid'] = $profileId;
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $this->hydrateFeed($stmt->fetchAll(), $viewerId);
    }

    public function search(int $viewerId, array $filters, int $limit = 20): array
    {
        [$where, $params] = $this->visibilitySql('p', $viewerId, false);
        $sql = "SELECT p.id AS post_id, p.created_at AS sort_at, NULL AS repost_user_id, NULL AS repost_name
                FROM posts p
                INNER JOIN users u ON u.id = p.user_id
                WHERE p.is_draft = 0 AND {$where}";

        $q = trim((string) ($filters['q'] ?? ''));
        if ($q !== '') {
            $sql .= ' AND (p.contenu LIKE :q OR p.location LIKE :qloc OR u.prenom LIKE :q2 OR u.nom LIKE :q3 OR u.username LIKE :q4)';
            $like = '%' . $q . '%';
            $params['q'] = $like;
            $params['qloc'] = $like;
            $params['q2'] = $like;
            $params['q3'] = $like;
            $params['q4'] = $like;
        }

        $type = $filters['type'] ?? '';
        if ($type === 'image') {
            $sql .= " AND EXISTS (SELECT 1 FROM post_media m WHERE m.post_id = p.id AND m.file_type IN ('image','gif'))";
        } elseif ($type === 'video') {
            $sql .= " AND EXISTS (SELECT 1 FROM post_media m WHERE m.post_id = p.id AND m.file_type = 'video')";
        } elseif ($type === 'text') {
            $sql .= ' AND NOT EXISTS (SELECT 1 FROM post_media m WHERE m.post_id = p.id)';
        }

        if (!empty($filters['username'])) {
            $sql .= ' AND u.username = :uname';
            $params['uname'] = $filters['username'];
        }

        if (!empty($filters['hashtag'])) {
            $sql .= ' AND EXISTS (
                SELECT 1 FROM post_hashtags ph
                INNER JOIN hashtags h ON h.id = ph.hashtag_id
                WHERE ph.post_id = p.id AND h.name = :tag
            )';
            $params['tag'] = mb_strtolower($filters['hashtag']);
        }

        if (!empty($filters['date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $filters['date'])) {
            $sql .= ' AND DATE(p.created_at) = :pdate';
            $params['pdate'] = $filters['date'];
        }

        $sql .= ' ORDER BY p.created_at DESC LIMIT ' . (int) $limit;
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $this->hydrateFeed($stmt->fetchAll(), $viewerId);
    }

    public function trending(int $limit = 5): array
    {
        $stmt = $this->db->prepare(
            'SELECT h.name, COUNT(*) AS total
             FROM post_hashtags ph
             INNER JOIN hashtags h ON h.id = ph.hashtag_id
             INNER JOIN posts p ON p.id = ph.post_id
             WHERE p.visibility = \'public\' AND p.is_draft = 0
               AND p.created_at > (NOW() - INTERVAL 7 DAY)
             GROUP BY h.id, h.name
             ORDER BY total DESC
             LIMIT ?'
        );
        $stmt->bindValue(1, $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function searchHashtags(string $query, int $limit = 8): array
    {
        $q = ltrim(mb_strtolower(trim($query)), '#');
        if ($q === '') {
            return $this->trending($limit);
        }
        $stmt = $this->db->prepare(
            'SELECT h.name, COUNT(ph.post_id) AS total
             FROM hashtags h
             LEFT JOIN post_hashtags ph ON ph.hashtag_id = h.id
             WHERE h.name LIKE ?
             GROUP BY h.id, h.name
             ORDER BY total DESC, h.name ASC
             LIMIT ?'
        );
        $stmt->bindValue(1, '%' . $q . '%');
        $stmt->bindValue(2, $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function searchLocations(string $query, int $limit = 8): array
    {
        $q = trim($query);
        if ($q === '') {
            $stmt = $this->db->prepare(
                'SELECT location AS name, COUNT(*) AS total
                 FROM posts
                 WHERE location IS NOT NULL AND location != \'\' AND is_draft = 0 AND visibility = \'public\'
                 GROUP BY location
                 ORDER BY total DESC
                 LIMIT ?'
            );
            $stmt->bindValue(1, $limit, \PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        }
        $stmt = $this->db->prepare(
            'SELECT location AS name, COUNT(*) AS total
             FROM posts
             WHERE location IS NOT NULL AND location LIKE ? AND is_draft = 0 AND visibility = \'public\'
             GROUP BY location
             ORDER BY total DESC
             LIMIT ?'
        );
        $stmt->bindValue(1, '%' . $q . '%');
        $stmt->bindValue(2, $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function react(int $postId, int $userId, string $reaction): string
    {
        if (!isset(self::REACTIONS[$reaction])) {
            return 'invalid';
        }
        $stmt = $this->db->prepare(
            'SELECT reaction FROM post_reactions WHERE post_id = ? AND user_id = ?'
        );
        $stmt->execute([$postId, $userId]);
        $current = $stmt->fetchColumn();

        if ($current === $reaction) {
            $del = $this->db->prepare('DELETE FROM post_reactions WHERE post_id = ? AND user_id = ?');
            $del->execute([$postId, $userId]);
            return 'removed';
        }

        if ($current) {
            $upd = $this->db->prepare(
                'UPDATE post_reactions SET reaction = ? WHERE post_id = ? AND user_id = ?'
            );
            $upd->execute([$reaction, $postId, $userId]);
            return 'updated';
        }

        $ins = $this->db->prepare(
            'INSERT INTO post_reactions (post_id, user_id, reaction) VALUES (?, ?, ?)'
        );
        $ins->execute([$postId, $userId, $reaction]);
        return 'added';
    }

    public function toggleRepost(int $userId, int $postId): bool
    {
        $stmt = $this->db->prepare('SELECT id FROM reposts WHERE user_id = ? AND post_id = ?');
        $stmt->execute([$userId, $postId]);
        if ($stmt->fetch()) {
            $del = $this->db->prepare('DELETE FROM reposts WHERE user_id = ? AND post_id = ?');
            $del->execute([$userId, $postId]);
            return false;
        }
        $ins = $this->db->prepare('INSERT INTO reposts (user_id, post_id) VALUES (?, ?)');
        $ins->execute([$userId, $postId]);
        return true;
    }

    public function toggleSave(int $userId, int $postId): bool
    {
        $stmt = $this->db->prepare('SELECT id FROM saved_posts WHERE user_id = ? AND post_id = ?');
        $stmt->execute([$userId, $postId]);
        if ($stmt->fetch()) {
            $del = $this->db->prepare('DELETE FROM saved_posts WHERE user_id = ? AND post_id = ?');
            $del->execute([$userId, $postId]);
            return false;
        }
        $ins = $this->db->prepare('INSERT INTO saved_posts (user_id, post_id) VALUES (?, ?)');
        $ins->execute([$userId, $postId]);
        return true;
    }

    public function hide(int $userId, int $postId): void
    {
        $stmt = $this->db->prepare('INSERT IGNORE INTO hidden_posts (user_id, post_id) VALUES (?, ?)');
        $stmt->execute([$userId, $postId]);
    }

    public function togglePin(int $postId, int $userId): void
    {
        $stmt = $this->db->prepare(
            'UPDATE posts SET is_pinned = IF(is_pinned = 1, 0, 1) WHERE id = ? AND user_id = ?'
        );
        $stmt->execute([$postId, $userId]);
    }

    public function recordView(int $postId, int $userId): void
    {
        $stmt = $this->db->prepare(
            'INSERT IGNORE INTO post_views (post_id, user_id) VALUES (?, ?)'
        );
        $stmt->execute([$postId, $userId]);
    }

    public function savedBy(int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT p.id AS post_id, s.created_at AS sort_at, NULL AS repost_user_id, NULL AS repost_name
             FROM saved_posts s
             INNER JOIN posts p ON p.id = s.post_id
             WHERE s.user_id = ?
             ORDER BY s.created_at DESC
             LIMIT 40'
        );
        $stmt->execute([$userId]);
        return $this->hydrateFeed($stmt->fetchAll(), $userId);
    }

    public function comments(int $postId, int $viewerId, string $sort = 'recent'): array
    {
        $order = $sort === 'popular'
            ? '(SELECT COUNT(*) FROM comment_likes cl WHERE cl.comment_id = c.id) DESC, c.created_at DESC'
            : 'c.created_at ASC';

        $sql = "SELECT c.*, u.prenom, u.nom, u.photo, u.username,
                       (SELECT COUNT(*) FROM comment_likes cl WHERE cl.comment_id = c.id) AS like_count,
                       (SELECT 1 FROM comment_likes cl WHERE cl.comment_id = c.id AND cl.user_id = ?) AS liked
                FROM comments c
                INNER JOIN users u ON u.id = c.user_id
                WHERE c.post_id = ?
                ORDER BY {$order}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$viewerId, $postId]);
        $rows = $stmt->fetchAll();

        $roots = [];
        $children = [];
        foreach ($rows as $row) {
            if ($row['parent_id']) {
                $children[(int) $row['parent_id']][] = $row;
            } else {
                $roots[] = $row;
            }
        }
        return ['roots' => $roots, 'children' => $children];
    }

    public function addComment(int $postId, int $userId, string $contenu, ?int $parentId = null): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO comments (post_id, user_id, parent_id, contenu) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$postId, $userId, $parentId, $contenu]);
        return (int) $this->db->lastInsertId();
    }

    public function findComment(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM comments WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function updateComment(int $id, int $userId, string $contenu): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE comments SET contenu = ?, is_edited = 1 WHERE id = ? AND user_id = ?'
        );
        return $stmt->execute([$contenu, $id, $userId]);
    }

    public function deleteComment(int $id, int $userId): bool
    {
        $stmt = $this->db->prepare('DELETE FROM comments WHERE id = ? AND user_id = ?');
        $stmt->execute([$id, $userId]);
        return $stmt->rowCount() > 0;
    }

    public function toggleCommentLike(int $commentId, int $userId): bool
    {
        $stmt = $this->db->prepare('SELECT id FROM comment_likes WHERE comment_id = ? AND user_id = ?');
        $stmt->execute([$commentId, $userId]);
        if ($stmt->fetch()) {
            $del = $this->db->prepare('DELETE FROM comment_likes WHERE comment_id = ? AND user_id = ?');
            $del->execute([$commentId, $userId]);
            return false;
        }
        $ins = $this->db->prepare('INSERT INTO comment_likes (comment_id, user_id) VALUES (?, ?)');
        $ins->execute([$commentId, $userId]);
        return true;
    }

    public function mentionedUsers(string $contenu): array
    {
        if (!preg_match_all('/@([a-zA-Z0-9._]+)/', $contenu, $m)) {
            return [];
        }
        $names = array_values(array_unique(array_map('strtolower', $m[1])));
        if (!$names) {
            return [];
        }
        $in = implode(',', array_fill(0, count($names), '?'));
        $stmt = $this->db->prepare("SELECT id, username, prenom, nom FROM users WHERE username IN ($in)");
        $stmt->execute($names);
        return $stmt->fetchAll();
    }

    public function mute(int $userId, int $mutedId): void
    {
        $stmt = $this->db->prepare('INSERT IGNORE INTO muted_users (user_id, muted_id) VALUES (?, ?)');
        $stmt->execute([$userId, $mutedId]);
    }

    public function unmute(int $userId, int $mutedId): void
    {
        $stmt = $this->db->prepare('DELETE FROM muted_users WHERE user_id = ? AND muted_id = ?');
        $stmt->execute([$userId, $mutedId]);
    }

    public function isMuted(int $userId, int $mutedId): bool
    {
        $stmt = $this->db->prepare('SELECT id FROM muted_users WHERE user_id = ? AND muted_id = ?');
        $stmt->execute([$userId, $mutedId]);
        return (bool) $stmt->fetch();
    }

    public function report(array $data): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO reports (reporter_id, reported_user_id, post_id, comment_id, reason, description)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['reporter_id'],
            $data['reported_user_id'],
            $data['post_id'] ?? null,
            $data['comment_id'] ?? null,
            $data['reason'],
            $data['description'] ?: null,
        ]);
    }

    private function syncTags(int $postId, string $contenu): void
    {
        $this->db->prepare('DELETE FROM post_hashtags WHERE post_id = ?')->execute([$postId]);
        $this->db->prepare('DELETE FROM mentions WHERE post_id = ?')->execute([$postId]);

        if (preg_match_all('/#([\p{L}\p{N}_]+)/u', $contenu, $tags)) {
            $names = array_unique(array_map(fn($t) => mb_strtolower($t), $tags[1]));
            $find = $this->db->prepare('SELECT id FROM hashtags WHERE name = ?');
            $ins = $this->db->prepare('INSERT INTO hashtags (name) VALUES (?)');
            $link = $this->db->prepare('INSERT IGNORE INTO post_hashtags (post_id, hashtag_id) VALUES (?, ?)');
            foreach ($names as $name) {
                $name = mb_substr($name, 0, 80);
                $find->execute([$name]);
                $hid = $find->fetchColumn();
                if (!$hid) {
                    $ins->execute([$name]);
                    $hid = (int) $this->db->lastInsertId();
                }
                $link->execute([$postId, $hid]);
            }
        }

        foreach ($this->mentionedUsers($contenu) as $user) {
            $stmt = $this->db->prepare('INSERT IGNORE INTO mentions (post_id, user_id) VALUES (?, ?)');
            $stmt->execute([$postId, $user['id']]);
        }
    }

    private function feedChrono(int $viewerId, string $mode, int $offset, int $fetch): array
    {
        [$where, $params] = $this->visibilitySql('p', $viewerId, false, 'a');
        [$whereRepost, $paramsRepost] = $this->visibilitySql('p', $viewerId, false, 'b');
        $params = array_merge($params, $paramsRepost);
        $followSql = '';
        if ($mode === 'following') {
            $followSql = ' AND (p.user_id = :self OR EXISTS (
                SELECT 1 FROM follows f WHERE f.follower_id = :self2 AND f.following_id = p.user_id
            ))';
            $params['self'] = $viewerId;
            $params['self2'] = $viewerId;
        }

        $sql = "SELECT * FROM (
                    SELECT p.id AS post_id, p.created_at AS sort_at, NULL AS repost_user_id, NULL AS repost_name
                    FROM posts p
                    WHERE {$where} {$followSql}

                    UNION ALL

                    SELECT p.id, r.created_at, r.user_id, CONCAT(ru.prenom, ' ', ru.nom)
                    FROM reposts r
                    INNER JOIN posts p ON p.id = r.post_id
                    INNER JOIN users ru ON ru.id = r.user_id
                    WHERE {$whereRepost}
                      AND (
                        r.user_id = :reposter
                        OR EXISTS (
                            SELECT 1 FROM follows f2
                            WHERE f2.follower_id = :reposter2 AND f2.following_id = r.user_id
                        )
                      )
                      AND r.user_id NOT IN (
                        SELECT muted_id FROM muted_users WHERE user_id = :reposter3
                      )
                ) feed
                ORDER BY sort_at DESC
                LIMIT {$fetch} OFFSET {$offset}";

        $params['reposter'] = $viewerId;
        $params['reposter2'] = $viewerId;
        $params['reposter3'] = $viewerId;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    private function feedRanked(int $viewerId, int $offset, int $fetch, int $days): array
    {
        [$where, $params] = $this->visibilitySql('p', $viewerId, false);
        $days = max(7, min(180, $days));
        $sql = "SELECT p.id AS post_id, p.created_at AS sort_at, NULL AS repost_user_id, NULL AS repost_name
                FROM posts p
                WHERE p.is_draft = 0 AND p.visibility = 'public' AND {$where}
                  AND p.created_at > (NOW() - INTERVAL {$days} DAY)
                ORDER BY (
                    (SELECT COUNT(*) FROM post_reactions pr WHERE pr.post_id = p.id) * 3
                    + (SELECT COUNT(*) FROM comments c WHERE c.post_id = p.id) * 2
                    + (SELECT COUNT(*) FROM reposts rp WHERE rp.post_id = p.id)
                ) DESC, p.created_at DESC
                LIMIT {$fetch} OFFSET {$offset}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    private function visibilitySql(string $alias, int $viewerId, bool $includeDraftsForOwner, string $prefix = ''): array
    {
        $p = $prefix;
        $draft = $includeDraftsForOwner
            ? "({$alias}.is_draft = 0 OR {$alias}.user_id = :{$p}draft_owner)"
            : "{$alias}.is_draft = 0";

        $sql = "{$draft}
            AND {$alias}.user_id NOT IN (
                SELECT blocked_id FROM blocked_users WHERE blocker_id = :{$p}blk1
                UNION
                SELECT blocker_id FROM blocked_users WHERE blocked_id = :{$p}blk2
            )
            AND {$alias}.user_id NOT IN (SELECT muted_id FROM muted_users WHERE user_id = :{$p}mute1)
            AND {$alias}.id NOT IN (SELECT post_id FROM hidden_posts WHERE user_id = :{$p}hide1)
            AND (
                {$alias}.visibility = 'public'
                OR {$alias}.user_id = :{$p}own1
                OR ({$alias}.visibility = 'followers' AND EXISTS (
                    SELECT 1 FROM follows {$p}fvis WHERE {$p}fvis.follower_id = :{$p}fol1 AND {$p}fvis.following_id = {$alias}.user_id
                ))
                OR ({$alias}.visibility = 'friends' AND EXISTS (
                    SELECT 1 FROM contacts {$p}cvis
                    WHERE ({$p}cvis.user_id = {$alias}.user_id AND {$p}cvis.contact_id = :{$p}fr1)
                       OR ({$p}cvis.user_id = :{$p}fr2 AND {$p}cvis.contact_id = {$alias}.user_id)
                ))
            )";

        $params = [
            $p . 'blk1' => $viewerId,
            $p . 'blk2' => $viewerId,
            $p . 'mute1' => $viewerId,
            $p . 'hide1' => $viewerId,
            $p . 'own1' => $viewerId,
            $p . 'fol1' => $viewerId,
            $p . 'fr1' => $viewerId,
            $p . 'fr2' => $viewerId,
        ];

        if ($includeDraftsForOwner) {
            $params[$p . 'draft_owner'] = $viewerId;
        }

        return [$sql, $params];
    }

    private function hydrateFeed(array $rows, int $viewerId): array
    {
        if (!$rows) {
            return [];
        }
        $ids = [];
        foreach ($rows as $row) {
            $ids[(int) $row['post_id']] = true;
        }
        $idList = array_keys($ids);
        $in = implode(',', array_fill(0, count($idList), '?'));

        $sql = "SELECT p.*, u.prenom, u.nom, u.photo, u.username,
                       (SELECT COUNT(*) FROM post_reactions pr WHERE pr.post_id = p.id) AS reaction_count,
                       (SELECT COUNT(*) FROM comments c WHERE c.post_id = p.id) AS comment_count,
                       (SELECT COUNT(*) FROM reposts rp WHERE rp.post_id = p.id) AS share_count,
                       (SELECT COUNT(*) FROM post_views pv WHERE pv.post_id = p.id) AS view_count,
                       (SELECT reaction FROM post_reactions pr WHERE pr.post_id = p.id AND pr.user_id = ?) AS my_reaction,
                       (SELECT 1 FROM saved_posts sp WHERE sp.post_id = p.id AND sp.user_id = ?) AS is_saved,
                       (SELECT 1 FROM reposts rp2 WHERE rp2.post_id = p.id AND rp2.user_id = ?) AS is_reposted
                FROM posts p
                INNER JOIN users u ON u.id = p.user_id
                WHERE p.id IN ($in)";
        $stmt = $this->db->prepare($sql);
        $params = array_merge([$viewerId, $viewerId, $viewerId], $idList);
        $stmt->execute($params);
        $byId = [];
        foreach ($stmt->fetchAll() as $post) {
            $byId[(int) $post['id']] = $post;
        }

        $media = $this->mediaFor($idList);
        $out = [];
        foreach ($rows as $row) {
            $id = (int) $row['post_id'];
            if (!isset($byId[$id])) {
                continue;
            }
            $post = $byId[$id];
            $post['media'] = $media[$id] ?? [];
            $post['repost_user_id'] = $row['repost_user_id'];
            $post['repost_name'] = $row['repost_name'];
            $post['sort_at'] = $row['sort_at'];
            $out[] = $post;
        }
        return $out;
    }

    private function hydrate(array $posts, int $viewerId): array
    {
        $rows = [];
        foreach ($posts as $post) {
            $rows[] = [
                'post_id' => $post['id'],
                'sort_at' => $post['created_at'],
                'repost_user_id' => null,
                'repost_name' => null,
            ];
        }
        return $this->hydrateFeed($rows, $viewerId);
    }

    private function mediaFor(array $ids): array
    {
        if (!$ids) {
            return [];
        }
        $in = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->db->prepare(
            "SELECT * FROM post_media WHERE post_id IN ($in) ORDER BY id ASC"
        );
        $stmt->execute($ids);
        $grouped = [];
        foreach ($stmt->fetchAll() as $file) {
            $grouped[(int) $file['post_id']][] = $file;
        }
        return $grouped;
    }
}
