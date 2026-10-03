<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Helpers\Security;
use App\Models\Notification;
use App\Models\Post;
use App\Models\User;

class PostController extends Controller
{
    public function store(): void
    {
        if (!$this->validateCsrf()) {
            $this->flash('error', 'Jeton de sécurité invalide.');
            $this->redirect('');
        }

        $data = $this->payload();
        $files = $this->collectUploads();
        if (isset($files['error'])) {
            $this->flash('error', $files['error']);
            $this->redirect('');
        }

        if ($data['contenu'] === '' && !$files) {
            $this->flash('error', 'Écrivez un texte ou ajoutez un média.');
            $this->redirect('');
        }

        $data['user_id'] = Auth::id();
        $postModel = new Post();
        $id = $postModel->create($data);
        foreach ($files as $file) {
            $postModel->addMedia($id, $file);
        }
        $this->notifyMentions($id, $data['contenu'], Auth::user());
        $this->flash('success', $data['is_draft'] ? 'Brouillon enregistré.' : 'Publication envoyée.');
        $this->redirect('');
    }

    public function show(string $id): void
    {
        $postModel = new Post();
        $post = $postModel->findVisible((int) $id, Auth::id());
        if (!$post) {
            $this->flash('error', 'Publication introuvable.');
            $this->redirect('');
        }
        if ((int) $post['user_id'] !== Auth::id()) {
            $postModel->recordView((int) $post['id'], Auth::id());
        }
        $sort = $this->input('sort') === 'popular' ? 'popular' : 'recent';
        $thread = $postModel->comments((int) $post['id'], Auth::id(), $sort);

        $this->view('feed/show', [
            'title'     => 'Publication',
            'post'      => $post,
            'roots'     => $thread['roots'],
            'children'  => $thread['children'],
            'sort'      => $sort,
            'csrf'      => Csrf::field(),
            'me'        => Auth::user(),
            'meId'      => (int) Auth::id(),
            'flash'     => $this->getFlash(),
            'pageClass' => 'page-feed',
        ], 'layouts/market');
    }

    public function edit(string $id): void
    {
        $post = (new Post())->findRaw((int) $id);
        if (!$post || (int) $post['user_id'] !== Auth::id()) {
            $this->flash('error', 'Publication introuvable.');
            $this->redirect('');
        }
        $this->view('feed/edit', [
            'title'     => 'Modifier',
            'post'      => $post,
            'csrf'      => Csrf::field(),
            'me'        => Auth::user(),
            'flash'     => $this->getFlash(),
            'pageClass' => 'page-feed',
        ], 'layouts/market');
    }

    public function update(string $id): void
    {
        if (!$this->validateCsrf()) {
            $this->flash('error', 'Jeton de sécurité invalide.');
            $this->redirect('');
        }
        $postModel = new Post();
        $post = $postModel->findRaw((int) $id);
        if (!$post || (int) $post['user_id'] !== Auth::id()) {
            $this->flash('error', 'Action impossible.');
            $this->redirect('');
        }
        $data = $this->payload();
        if ($data['contenu'] === '' && !$this->hasMedia((int) $id)) {
            $this->flash('error', 'La publication ne peut pas être vide.');
            $this->redirect('posts/' . $id . '/edit');
        }
        $postModel->updatePost((int) $id, Auth::id(), $data);
        $this->flash('success', 'Publication mise à jour.');
        $this->redirect('posts/' . $id);
    }

    public function destroy(string $id): void
    {
        if (!$this->validateCsrf()) {
            $this->flash('error', 'Jeton de sécurité invalide.');
            $this->redirect('');
        }
        (new Post())->deleteOwned((int) $id, Auth::id());
        $this->flash('success', 'Publication supprimée.');
        $this->redirect('');
    }

    public function react(string $id): void
    {
        if (!$this->validateCsrf()) {
            $this->back('');
        }
        $post = $this->visiblePost((int) $id);
        $reaction = (string) $this->input('reaction', 'like');
        $state = (new Post())->react((int) $post['id'], Auth::id(), $reaction);
        if ($state === 'added' && (int) $post['user_id'] !== Auth::id()) {
            $me = Auth::user();
            (new Notification())->notify(
                (int) $post['user_id'],
                'reaction',
                'Nouvelle réaction',
                $me['prenom'] . ' a réagi à votre publication.',
                'posts/' . $post['id']
            );
        }
        $this->back('posts/' . $post['id']);
    }

    public function comment(string $id): void
    {
        if (!$this->validateCsrf()) {
            $this->redirect('');
        }
        $post = $this->visiblePost((int) $id);
        $contenu = trim(Security::clean((string) $this->input('contenu', '')));
        if ($contenu === '') {
            $this->flash('error', 'Le commentaire est vide.');
            $this->redirect('posts/' . $post['id']);
        }
        $parentId = (int) $this->input('parent_id', 0) ?: null;
        $postModel = new Post();
        if ($parentId) {
            $parent = $postModel->findComment($parentId);
            if (!$parent || (int) $parent['post_id'] !== (int) $post['id']) {
                $parentId = null;
            }
        }
        $postModel->addComment((int) $post['id'], Auth::id(), $contenu, $parentId);
        $me = Auth::user();
        $notif = new Notification();
        if ((int) $post['user_id'] !== Auth::id()) {
            $notif->notify(
                (int) $post['user_id'],
                'comment',
                'Nouveau commentaire',
                $me['prenom'] . ' a commenté votre publication.',
                'posts/' . $post['id']
            );
        }
        if ($parentId && isset($parent) && (int) $parent['user_id'] !== Auth::id()) {
            $notif->notify(
                (int) $parent['user_id'],
                'reply',
                'Nouvelle réponse',
                $me['prenom'] . ' a répondu à votre commentaire.',
                'posts/' . $post['id']
            );
        }
        $this->redirect('posts/' . $post['id'] . '#comments');
    }

    public function updateComment(string $id): void
    {
        if (!$this->validateCsrf()) {
            $this->redirect('');
        }
        $comment = (new Post())->findComment((int) $id);
        if (!$comment || (int) $comment['user_id'] !== Auth::id()) {
            $this->flash('error', 'Commentaire introuvable.');
            $this->redirect('');
        }
        $contenu = trim(Security::clean((string) $this->input('contenu', '')));
        if ($contenu !== '') {
            (new Post())->updateComment((int) $id, Auth::id(), $contenu);
        }
        $this->redirect('posts/' . $comment['post_id'] . '#comments');
    }

    public function deleteComment(string $id): void
    {
        if (!$this->validateCsrf()) {
            $this->redirect('');
        }
        $comment = (new Post())->findComment((int) $id);
        if ($comment) {
            (new Post())->deleteComment((int) $id, Auth::id());
            $this->redirect('posts/' . $comment['post_id'] . '#comments');
        }
        $this->redirect('');
    }

    public function likeComment(string $id): void
    {
        if (!$this->validateCsrf()) {
            $this->redirect('');
        }
        $comment = (new Post())->findComment((int) $id);
        if (!$comment) {
            $this->redirect('');
        }
        $liked = (new Post())->toggleCommentLike((int) $id, Auth::id());
        if ($liked && (int) $comment['user_id'] !== Auth::id()) {
            $me = Auth::user();
            (new Notification())->notify(
                (int) $comment['user_id'],
                'comment_like',
                'Commentaire aimé',
                $me['prenom'] . ' aime votre commentaire.',
                'posts/' . $comment['post_id']
            );
        }
        $this->redirect('posts/' . $comment['post_id'] . '#comments');
    }

    public function repost(string $id): void
    {
        if (!$this->validateCsrf()) {
            $this->back('');
        }
        $post = $this->visiblePost((int) $id);
        $added = (new Post())->toggleRepost(Auth::id(), (int) $post['id']);
        if ($added && (int) $post['user_id'] !== Auth::id()) {
            $me = Auth::user();
            (new Notification())->notify(
                (int) $post['user_id'],
                'repost',
                'Publication partagée',
                $me['prenom'] . ' a republié votre publication.',
                'posts/' . $post['id']
            );
        }
        $this->flash('success', $added ? 'Publication republiée.' : 'Republication retirée.');
        $this->back('posts/' . $post['id']);
    }

    public function save(string $id): void
    {
        if (!$this->validateCsrf()) {
            $this->back('');
        }
        $post = $this->visiblePost((int) $id);
        $saved = (new Post())->toggleSave(Auth::id(), (int) $post['id']);
        $this->flash('success', $saved ? 'Publication enregistrée.' : 'Publication retirée des enregistrements.');
        $this->back('posts/' . $post['id']);
    }

    public function hide(string $id): void
    {
        if (!$this->validateCsrf()) {
            $this->back('');
        }
        $this->visiblePost((int) $id);
        (new Post())->hide(Auth::id(), (int) $id);
        $this->flash('success', 'Publication masquée.');
        $this->back('');
    }

    public function pin(string $id): void
    {
        if (!$this->validateCsrf()) {
            $this->redirect('');
        }
        (new Post())->togglePin((int) $id, Auth::id());
        $this->back('posts/' . $id);
    }

    public function report(string $id): void
    {
        if (!$this->validateCsrf()) {
            $this->redirect('');
        }
        $post = $this->visiblePost((int) $id);
        $reason = (string) $this->input('reason', 'autre');
        $allowed = ['spam', 'fraude', 'harcelement', 'inapproprie', 'usurpation', 'autre'];
        if (!in_array($reason, $allowed, true)) {
            $reason = 'autre';
        }
        (new Post())->report([
            'reporter_id' => Auth::id(),
            'reported_user_id' => (int) $post['user_id'],
            'post_id' => (int) $post['id'],
            'reason' => $reason,
            'description' => Security::clean((string) $this->input('description', '')),
        ]);
        $this->flash('success', 'Signalement envoyé.');
        $this->back('posts/' . $post['id']);
    }

    public function mute(): void
    {
        if (!$this->validateCsrf()) {
            $this->back('');
        }
        $userId = (int) $this->input('user_id', 0);
        if ($userId && $userId !== Auth::id()) {
            (new Post())->mute(Auth::id(), $userId);
            $this->flash('success', 'Utilisateur mis en sourdine.');
        }
        $this->back('');
    }

    public function unmute(): void
    {
        if (!$this->validateCsrf()) {
            $this->back('');
        }
        (new Post())->unmute(Auth::id(), (int) $this->input('user_id', 0));
        $this->flash('success', 'Sourdine retirée.');
        $this->back('');
    }

    public function byUsername(string $username): void
    {
        $user = (new User())->findByUsername($username);
        if (!$user) {
            $this->flash('error', 'Utilisateur introuvable.');
            $this->redirect('explore');
        }
        $this->redirect('user/' . $user['id']);
    }

    private function visiblePost(int $id): array
    {
        $post = (new Post())->findVisible($id, Auth::id());
        if (!$post) {
            $this->flash('error', 'Publication introuvable.');
            $this->redirect('');
        }
        return $post;
    }

    private function payload(): array
    {
        $contenu = trim((string) $this->input('contenu', ''));
        $extra = trim((string) $this->input('hashtags', ''));
        if ($extra !== '') {
            foreach (preg_split('/\s+/', $extra) as $part) {
                $part = ltrim($part, '#');
                if ($part !== '') {
                    $contenu .= ' #' . $part;
                }
            }
        }
        $contenu = mb_substr($contenu, 0, 2000);
        $link = trim((string) $this->input('link_url', ''));
        if ($link !== '' && !filter_var($link, FILTER_VALIDATE_URL)) {
            $link = '';
        }
        $visibility = (string) $this->input('visibility', 'public');
        if (!in_array($visibility, ['public', 'friends', 'followers', 'private'], true)) {
            $visibility = 'public';
        }
        return [
            'contenu'    => $contenu,
            'location'   => mb_substr(Security::clean((string) $this->input('location', '')), 0, 160),
            'link_url'   => $link,
            'visibility' => $visibility,
            'is_draft'   => $this->input('is_draft') ? 1 : 0,
        ];
    }

    private function collectUploads(): array
    {
        if (empty($_FILES['media']['name']) || !is_array($_FILES['media']['name'])) {
            return [];
        }
        $allowed = [
            'image/jpeg' => ['image', 'jpg'],
            'image/png'  => ['image', 'png'],
            'image/webp' => ['image', 'webp'],
            'image/gif'  => ['gif', 'gif'],
            'video/mp4'  => ['video', 'mp4'],
            'video/webm' => ['video', 'webm'],
        ];
        $max = 20 * 1024 * 1024;
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $stored = [];
        $count = 0;

        foreach ($_FILES['media']['name'] as $i => $name) {
            if ($_FILES['media']['error'][$i] === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            if ($count >= 4) {
                return ['error' => '4 fichiers maximum.'];
            }
            if ($_FILES['media']['error'][$i] !== UPLOAD_ERR_OK) {
                return ['error' => 'Erreur pendant l’envoi d’un fichier.'];
            }
            if ($_FILES['media']['size'][$i] > $max) {
                return ['error' => 'Un fichier dépasse 20 Mo.'];
            }
            $mime = $finfo->file($_FILES['media']['tmp_name'][$i]);
            if (!isset($allowed[$mime])) {
                return ['error' => 'Format non autorisé (JPG, PNG, WEBP, GIF, MP4, WEBM).'];
            }
            [$type, $ext] = $allowed[$mime];
            $filename = 'post_' . bin2hex(random_bytes(8)) . '.' . $ext;
            $dest = dirname(__DIR__, 2) . '/public/uploads/posts/' . $filename;
            if (!move_uploaded_file($_FILES['media']['tmp_name'][$i], $dest)) {
                return ['error' => 'Impossible d’enregistrer le fichier.'];
            }
            $stored[] = [
                'file_name' => basename((string) $name),
                'file_path' => 'posts/' . $filename,
                'file_type' => $type,
                'mime'      => $mime,
                'file_size' => (int) $_FILES['media']['size'][$i],
            ];
            $count++;
        }
        return $stored;
    }

    private function hasMedia(int $postId): bool
    {
        $db = \App\Core\Database::getInstance();
        $stmt = $db->prepare('SELECT id FROM post_media WHERE post_id = ? LIMIT 1');
        $stmt->execute([$postId]);
        return (bool) $stmt->fetch();
    }

    private function notifyMentions(int $postId, string $contenu, array $author): void
    {
        $notif = new Notification();
        foreach ((new Post())->mentionedUsers($contenu) as $user) {
            if ((int) $user['id'] === (int) $author['id']) {
                continue;
            }
            $notif->notify(
                (int) $user['id'],
                'mention',
                'Vous avez été mentionné',
                $author['prenom'] . ' vous a mentionné dans une publication.',
                'posts/' . $postId
            );
        }
    }

    private function back(string $fallback): void
    {
        $ref = $_SERVER['HTTP_REFERER'] ?? '';
        $app = require dirname(__DIR__, 2) . '/config/app.php';
        if ($ref !== '' && str_contains($ref, $app['url'])) {
            header('Location: ' . $ref);
            exit;
        }
        $this->redirect($fallback);
    }
}
