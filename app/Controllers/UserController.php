<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Helpers\Security;
use App\Models\BlockedUser;
use App\Models\Contact;
use App\Models\Follow;
use App\Models\Listing;
use App\Models\Post;
use App\Models\SessionModel;
use App\Models\User;

class UserController extends Controller
{
    public function profile(): void
    {
        $user = Auth::user();
        $tab = (string) $this->input('tab', 'posts');
        if ($tab === 'settings') {
            $this->redirect('settings');
        }
        $uid = (int) $user['id'];
        $this->view('profile/index', [
            'title'     => 'Mon profil',
            'user'      => $user,
            'posts'     => (new Post())->byUser($uid, $uid),
            'listings'  => (new Listing())->byUser($uid, $uid),
            'tab'       => $tab,
            'flash'     => $this->getFlash(),
            'csrf'      => Csrf::field(),
            'me'        => $user,
            'meId'      => $uid,
            'pageClass' => 'page-profile',
        ], 'layouts/market');
    }

    public function settings(): void
    {
        $user = Auth::user();
        $this->view('profile/settings', [
            'title'     => 'Paramètres',
            'user'      => $user,
            'flash'     => $this->getFlash(),
            'csrf'      => Csrf::field(),
            'me'        => $user,
            'pageClass' => 'page-settings',
        ], 'layouts/market');
    }

    public function updateProfile(): void
    {
        if (!$this->validateCsrf()) {
            $this->flash('error', 'Jeton de sécurité invalide.');
            $this->redirect('settings');
        }

        $userId = Auth::id();
        $userModel = new User();

        $data = [
            'nom'    => Security::clean((string) $this->input('nom', '')),
            'prenom' => Security::clean((string) $this->input('prenom', '')),
            'bio'    => Security::clean((string) $this->input('bio', '')),
            'email'  => mb_strtolower(trim((string) $this->input('email', ''))),
            'telephone' => preg_replace('/\s+/', '', Security::clean((string) $this->input('telephone', ''))),
            'username'  => mb_strtolower(preg_replace('/[^a-zA-Z0-9._]/', '', (string) $this->input('username', ''))),
            'privacy_photo'     => $this->sanitizePrivacy($this->input('privacy_photo')),
            'privacy_last_seen' => $this->sanitizePrivacy($this->input('privacy_last_seen')),
            'privacy_status'    => $this->sanitizePrivacy($this->input('privacy_status')),
            'privacy_contact'   => $this->sanitizePrivacy($this->input('privacy_contact')),
            'privacy_group'     => $this->sanitizePrivacy($this->input('privacy_group')),
        ];

        if ($data['nom'] === '' || $data['prenom'] === '') {
            $this->flash('error', 'Le nom et le prénom sont obligatoires.');
            $this->redirect('settings');
        }
        if ($data['email'] === '' || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $this->flash('error', 'Email invalide.');
            $this->redirect('settings');
        }
        if ($data['telephone'] === '' || mb_strlen($data['telephone']) < 8) {
            $this->flash('error', 'Téléphone invalide.');
            $this->redirect('settings');
        }
        if ($data['username'] !== '' && mb_strlen($data['username']) < 3) {
            $this->flash('error', 'Le pseudo doit faire au moins 3 caractères.');
            $this->redirect('settings');
        }
        if ($data['username'] === '') {
            $data['username'] = null;
        }
        if ($userModel->emailTaken($data['email'], $userId)) {
            $this->flash('error', 'Cet email est déjà utilisé.');
            $this->redirect('settings');
        }
        if ($userModel->phoneTaken($data['telephone'], $userId)) {
            $this->flash('error', 'Ce téléphone est déjà utilisé.');
            $this->redirect('settings');
        }
        if ($data['username'] && $userModel->usernameTaken($data['username'], $userId)) {
            $this->flash('error', 'Ce pseudo est déjà pris.');
            $this->redirect('settings');
        }

        if (!empty($_FILES['photo']['name'])) {
            $upload = $this->handleImageUpload($_FILES['photo'], 'avatars', 'avatar_');
            if (isset($upload['error'])) {
                $this->flash('error', $upload['error']);
                $this->redirect('settings');
            }
            $data['photo'] = $upload['path'];
        }

        if (!empty($_FILES['cover_photo']['name'])) {
            $upload = $this->handleImageUpload($_FILES['cover_photo'], 'covers', 'cover_');
            if (isset($upload['error'])) {
                $this->flash('error', $upload['error']);
                $this->redirect('settings');
            }
            $data['cover_photo'] = $upload['path'];
        }

        $userModel->updateProfile($userId, $data);
        $_SESSION['user_name'] = $data['prenom'] . ' ' . $data['nom'];
        $this->flash('success', 'Profil mis à jour.');
        $this->redirect('settings');
    }

    public function updateCover(): void
    {
        if (!$this->validateCsrf()) {
            $this->flash('error', 'Jeton de sécurité invalide.');
            $this->redirect('profile');
        }

        if (empty($_FILES['cover_photo']['name'])) {
            $this->flash('error', 'Choisissez une image de couverture.');
            $this->redirect('profile');
        }

        $upload = $this->handleImageUpload($_FILES['cover_photo'], 'covers', 'cover_');
        if (isset($upload['error'])) {
            $this->flash('error', $upload['error']);
            $this->redirect('profile');
        }

        (new User())->updateProfile(Auth::id(), ['cover_photo' => $upload['path']]);
        $this->flash('success', 'Couverture mise à jour.');
        $this->redirect('profile');
    }

    public function updateAvatar(): void
    {
        if (!$this->validateCsrf()) {
            $this->flash('error', 'Jeton de sécurité invalide.');
            $this->redirect('profile');
        }

        if (empty($_FILES['photo']['name'])) {
            $this->flash('error', 'Choisissez une photo de profil.');
            $this->redirect('profile');
        }

        $upload = $this->handleImageUpload($_FILES['photo'], 'avatars', 'avatar_');
        if (isset($upload['error'])) {
            $this->flash('error', $upload['error']);
            $this->redirect('profile');
        }

        (new User())->updateProfile(Auth::id(), ['photo' => $upload['path']]);
        $this->flash('success', 'Photo de profil mise à jour.');
        $this->redirect('profile');
    }

    public function changePassword(): void
    {
        if (!$this->validateCsrf()) {
            $this->flash('error', 'Jeton de sécurité invalide.');
            $this->redirect('settings');
        }

        $current = (string) $this->input('current_password', '');
        $new     = (string) $this->input('new_password', '');
        $confirm = (string) $this->input('new_password_confirm', '');

        $user = Auth::user();

        if (!password_verify($current, $user['password'])) {
            $this->flash('error', 'Mot de passe actuel incorrect.');
            $this->redirect('settings');
        }
        if (!Security::strongPassword($new)) {
            $this->flash('error', 'Le nouveau mot de passe est trop faible.');
            $this->redirect('settings');
        }
        if ($new !== $confirm) {
            $this->flash('error', 'Les mots de passe ne correspondent pas.');
            $this->redirect('settings');
        }

        (new User())->updatePassword(Auth::id(), $new);
        $this->flash('success', 'Mot de passe modifié.');
        $this->redirect('settings');
    }

    public function showUser(string $id): void
    {
        $targetId = (int) $id;
        $userModel = new User();
        $profile = $userModel->publicProfile($targetId);

        if (!$profile) {
            $this->flash('error', 'Utilisateur introuvable.');
            $this->redirect('contacts');
        }

        $me = Auth::id();
        $contactModel = new Contact();
        $blockModel = new BlockedUser();
        $followModel = new Follow();
        $postModel = new Post();
        $isContact = $contactModel->exists($me, $targetId);
        $isSelf = $me === $targetId;

        if (!$userModel->canSee($profile['privacy_photo'] ?? 'everyone', $targetId, $me, $isContact)) {
            $profile['photo'] = null;
        }
        if (!$userModel->canSee($profile['privacy_last_seen'] ?? 'everyone', $targetId, $me, $isContact)) {
            $profile['is_online'] = 0;
            $profile['derniere_connexion'] = null;
            $profile['hide_last_seen'] = true;
        }
        $canContact = $isSelf || $userModel->canSee($profile['privacy_contact'] ?? 'everyone', $targetId, $me, $isContact);

        $listings = (new Listing())->byUser($targetId, $me);
        $posts = $postModel->byUser($targetId, $me);

        $this->view('profile/show', [
            'title'       => $profile['prenom'] . ' ' . $profile['nom'],
            'profile'     => $profile,
            'posts'       => $posts,
            'listings'    => $listings,
            'isContact'   => $isContact,
            'isBlocked'   => $blockModel->isBlocked($me, $targetId),
            'isFollowing' => $followModel->isFollowing($me, $targetId),
            'isMuted'     => $postModel->isMuted($me, $targetId),
            'followers'   => $followModel->countFollowers($targetId),
            'following'   => $followModel->countFollowing($targetId),
            'canContact'  => $canContact,
            'isSelf'      => $isSelf,
            'me'          => Auth::user(),
            'meId'        => $me,
            'csrf'        => Csrf::field(),
            'flash'       => $this->getFlash(),
            'pageClass'   => 'page-profile',
        ], 'layouts/market');
    }

    public function search(): void
    {
        $q = Security::clean((string) $this->input('q', ''));
        $qs = $q !== '' ? ('explore?q=' . rawurlencode($q)) : 'explore';
        $this->redirect($qs);
    }

    public function sessions(): void
    {
        $sessionModel = new SessionModel();
        $sessions = $sessionModel->listForUser(Auth::id());
        $currentToken = $_SESSION['session_token'] ?? '';

        $this->view('profile/sessions', [
            'title'        => 'Sessions actives',
            'sessions'     => $sessions,
            'currentToken' => $currentToken,
            'csrf'         => Csrf::field(),
            'flash'        => $this->getFlash(),
            'me'           => Auth::user(),
            'pageClass'    => 'page-profile',
        ], 'layouts/market');
    }

    public function revokeSessions(): void
    {
        if (!$this->validateCsrf()) {
            $this->flash('error', 'Jeton de sécurité invalide.');
            $this->redirect('sessions');
        }

        $sessionModel = new SessionModel();
        $sessionModel->deleteAllForUser(Auth::id(), $_SESSION['session_token'] ?? null);
        $this->flash('success', 'Autres sessions fermées.');
        $this->redirect('sessions');
    }

    private function sanitizePrivacy($value): string
    {
        $allowed = ['everyone', 'contacts', 'nobody'];
        return in_array($value, $allowed, true) ? $value : 'everyone';
    }

    private function wantsJson(): bool
    {
        return isset($_GET['ajax']) || (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json'));
    }

    private function handleImageUpload(array $file, string $folder, string $prefix): array
    {
        $app = require dirname(__DIR__, 2) . '/config/app.php';
        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['error' => 'Erreur lors du téléversement.'];
        }
        if ($file['size'] > $app['upload_max']) {
            return ['error' => 'Fichier trop volumineux (max 5 Mo).'];
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($file['tmp_name']);
        if (!isset($allowed[$mime])) {
            return ['error' => 'Format non autorisé (JPG, PNG, WEBP).'];
        }

        $dir = dirname(__DIR__, 2) . '/public/uploads/' . $folder;
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $name = $prefix . bin2hex(random_bytes(8)) . '.' . $allowed[$mime];
        $dest = $dir . '/' . $name;
        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            return ['error' => 'Impossible d\'enregistrer le fichier.'];
        }

        return ['path' => $folder . '/' . $name];
    }
}
