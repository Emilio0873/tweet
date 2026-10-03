<?php

namespace App\Core;

use App\Models\User;
use App\Models\SessionModel;

class Auth
{
    public static function attempt(string $identifier, string $password): bool|string
    {
        if (self::isRateLimited($identifier)) {
            return 'Trop de tentatives. Réessayez dans 15 minutes.';
        }

        $userModel = new User();
        $user = $userModel->findByEmailOrPhone($identifier);

        if (!$user || !password_verify($password, $user['password'])) {
            self::recordAttempt($identifier);
            return 'Identifiants incorrects.';
        }

        if ($user['statut'] !== 'actif') {
            return 'Votre compte est suspendu ou désactivé.';
        }

        self::login($user);
        return true;
    }

    public static function login(array $user): void
    {
        session_regenerate_id(true);

        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_name'] = $user['prenom'] . ' ' . $user['nom'];

        $token = bin2hex(random_bytes(32));
        $_SESSION['session_token'] = $token;

        $sessionModel = new SessionModel();
        $sessionModel->create([
            'user_id'       => (int) $user['id'],
            'session_token' => $token,
            'device'        => substr($_SERVER['HTTP_USER_AGENT'] ?? 'unknown', 0, 255),
            'ip_address'    => $_SERVER['REMOTE_ADDR'] ?? null,
        ]);

        $userModel = new User();
        $userModel->updatePresence((int) $user['id'], true);
    }

    public static function logout(): void
    {
        if (self::check()) {
            $userModel = new User();
            $userModel->updatePresence(self::id(), false);

            if (!empty($_SESSION['session_token'])) {
                $sessionModel = new SessionModel();
                $sessionModel->deleteByToken($_SESSION['session_token']);
            }
        }

        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
    }

    public static function check(): bool
    {
        return isset($_SESSION['user_id']);
    }

    public static function id(): ?int
    {
        return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
    }

    public static function user(): ?array
    {
        if (!self::check()) {
            return null;
        }
        $userModel = new User();
        return $userModel->find(self::id());
    }

    public static function isAdmin(): bool
    {
        return ($_SESSION['user_role'] ?? '') === 'admin';
    }

    private static function isRateLimited(string $identifier): bool
    {
        $db = Database::getInstance();
        $stmt = $db->prepare(
            'SELECT COUNT(*) FROM login_attempts
             WHERE identifier = ? AND attempted_at > (NOW() - INTERVAL 15 MINUTE)'
        );
        $stmt->execute([$identifier]);
        return (int) $stmt->fetchColumn() >= 5;
    }

    private static function recordAttempt(string $identifier): void
    {
        $db = Database::getInstance();
        $stmt = $db->prepare(
            'INSERT INTO login_attempts (identifier, ip_address) VALUES (?, ?)'
        );
        $stmt->execute([$identifier, $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0']);
    }
}
