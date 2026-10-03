<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Helpers\Security;
use App\Models\User;

class AuthController extends Controller
{
    public function showLogin(): void
    {
        $this->view('auth/login', [
            'title' => 'Connexion',
            'flash' => $this->getFlash(),
            'csrf'  => Csrf::field(),
        ], 'layouts/auth');
    }

    public function login(): void
    {
        if (!$this->validateCsrf()) {
            $this->flash('error', 'Jeton de sécurité invalide.');
            $this->redirect('login');
        }

        $identifier = Security::clean((string) $this->input('identifier', ''));
        $password   = (string) $this->input('password', '');

        if ($identifier === '' || $password === '') {
            $this->flash('error', 'Veuillez remplir tous les champs.');
            $this->redirect('login');
        }

        $result = Auth::attempt($identifier, $password);

        if ($result !== true) {
            $this->flash('error', is_string($result) ? $result : 'Connexion impossible.');
            $this->redirect('login');
        }

        if ($this->input('remember')) {
            $app = require dirname(__DIR__, 2) . '/config/app.php';
            $params = session_get_cookie_params();
            setcookie(session_name(), session_id(), [
                'expires'  => time() + (int) ($app['session_lifetime'] ?? 60 * 60 * 24 * 30),
                'path'     => $params['path'] ?: '/',
                'domain'   => $params['domain'] ?? '',
                'secure'   => (bool) ($params['secure'] ?? false),
                'httponly' => true,
                'samesite' => $params['samesite'] ?? 'Lax',
            ]);
        }

        $this->redirect('');
    }

    public function showRegister(): void
    {
        $this->view('auth/register', [
            'title'    => 'Inscription',
            'flash'    => $this->getFlash(),
            'csrf'     => Csrf::field(),
            'authWide' => true,
        ], 'layouts/auth');
    }

    public function register(): void
    {
        if (!$this->validateCsrf()) {
            $this->flash('error', 'Jeton de sécurité invalide.');
            $this->redirect('register');
        }

        $nom        = Security::clean((string) $this->input('nom', ''));
        $prenom     = Security::clean((string) $this->input('prenom', ''));
        $email      = strtolower(Security::clean((string) $this->input('email', '')));
        $telephone  = preg_replace('/[\s\-.]/', '', Security::clean((string) $this->input('telephone', '')));
        $password   = (string) $this->input('password', '');
        $password2  = (string) $this->input('password_confirm', '');
        $bio        = Security::clean((string) $this->input('bio', ''));

        $errors = [];

        if ($nom === '' || $prenom === '') {
            $errors[] = 'Le nom et le prénom sont obligatoires.';
        }
        if (!Security::validateEmail($email)) {
            $errors[] = 'Adresse email invalide.';
        }
        if (!Security::validatePhone($telephone)) {
            $errors[] = 'Numéro de téléphone invalide.';
        }
        if (!Security::strongPassword($password)) {
            $errors[] = 'Le mot de passe doit contenir au moins 8 caractères.';
        }
        if ($password !== $password2) {
            $errors[] = 'Les mots de passe ne correspondent pas.';
        }

        $userModel = new User();
        if ($userModel->findByEmail($email)) {
            $errors[] = 'Cet email est déjà utilisé.';
        }
        if ($userModel->findByPhone($telephone)) {
            $errors[] = 'Ce numéro de téléphone est déjà utilisé.';
        }

        $photoPath = null;
        if (!empty($_FILES['photo']['name'])) {
            $upload = $this->handleAvatarUpload($_FILES['photo']);
            if (isset($upload['error'])) {
                $errors[] = $upload['error'];
            } else {
                $photoPath = $upload['path'];
            }
        }

        if ($errors) {
            $this->flash('error', implode(' ', $errors));
            $this->redirect('register');
        }

        $userId = $userModel->create([
            'nom'       => $nom,
            'prenom'    => $prenom,
            'email'     => $email,
            'telephone' => $telephone,
            'password'  => $password,
            'photo'     => $photoPath,
            'bio'       => $bio !== '' ? $bio : null,
        ]);

        $user = $userModel->find($userId);
        Auth::login($user);
        $this->flash('success', 'Bienvenue sur TWEET !');
        $this->redirect('');
    }

    public function logout(): void
    {
        Auth::logout();
        $this->redirect('login');
    }

    public function showForgot(): void
    {
        $this->view('auth/forgot', [
            'title' => 'Mot de passe oublié',
            'flash' => $this->getFlash(),
            'csrf'  => Csrf::field(),
        ], 'layouts/auth');
    }

    public function forgot(): void
    {
        if (!$this->validateCsrf()) {
            $this->flash('error', 'Jeton de sécurité invalide.');
            $this->redirect('forgot-password');
        }

        // Phase 1 : message générique (pas d'envoi email réel)
        $this->flash('success', 'Si un compte existe avec cet identifiant, un lien de réinitialisation sera envoyé.');
        $this->redirect('forgot-password');
    }

    private function handleAvatarUpload(array $file): array
    {
        $app = require dirname(__DIR__, 2) . '/config/app.php';
        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['error' => 'Erreur lors du téléversement de la photo.'];
        }
        if ($file['size'] > $app['upload_max']) {
            return ['error' => 'La photo ne doit pas dépasser 5 Mo.'];
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($file['tmp_name']);

        if (!isset($allowed[$mime])) {
            return ['error' => 'Format de photo non autorisé (JPG, PNG, WEBP).'];
        }

        $name = 'avatar_' . bin2hex(random_bytes(8)) . '.' . $allowed[$mime];
        $dest = dirname(__DIR__, 2) . '/public/uploads/avatars/' . $name;

        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            return ['error' => 'Impossible d\'enregistrer la photo.'];
        }

        return ['path' => 'avatars/' . $name];
    }
}
