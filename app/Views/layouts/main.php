<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? 'TWEET') ?> — TWEET</title>
    <link rel="icon" href="<?= asset('images/logo.png') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>?v=8">
</head>
<body class="app-body">
<?php
    $navUri = $_SERVER['REQUEST_URI'] ?? '';
    $unread = 0;
    $chatUnread = 0;
    $me = \App\Core\Auth::user();
    if ($me) {
        $unread = (new \App\Models\Notification())->unreadCount((int) $me['id']);
        $chatUnread = (new \App\Models\Conversation())->unreadCount((int) $me['id']);
    }
?>
    <div class="app-shell">
        <aside class="sidebar" id="app-side">
            <div class="sidebar-brand">
                <a href="<?= url('') ?>" class="brand-link">
                    <img class="brand-mark" src="<?= asset('images/logo.png') ?>" alt="TWEET">
                    <span class="brand-name">TWEET</span>
                </a>
            </div>

            <nav class="sidebar-nav">
                <a href="<?= url('') ?>" class="nav-item <?= !str_contains($navUri, '/feed') && !str_contains($navUri, '/chat') && !str_contains($navUri, '/profile') && !str_contains($navUri, '/explore') ? '' : '' ?>">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 10.5 12 4l8 6.5V20a1 1 0 0 1-1 1h-5v-6H10v6H5a1 1 0 0 1-1-1z"/></svg>
                    Annonces
                </a>
                <a href="<?= url('feed') ?>" class="nav-item <?= str_contains($navUri, '/feed') ? 'active' : '' ?>">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h10"/></svg>
                    Fil
                </a>
                <a href="<?= url('chat') ?>" class="nav-item <?= str_contains($navUri, '/chat') ? 'active' : '' ?>">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                    Messages<?php if (!empty($chatUnread)): ?> <em class="badge-count"><?= (int) $chatUnread ?></em><?php endif; ?>
                </a>
                <a href="<?= url('notifications') ?>" class="nav-item <?= str_contains($navUri, '/notifications') ? 'active' : '' ?>">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8a6 6 0 1 0-12 0c0 7-3 7-3 7h18s-3 0-3-7"/><path d="M13.7 21a2 2 0 0 1-3.4 0"/></svg>
                    Alertes<?php if ($unread): ?> <em class="badge-count"><?= (int) $unread ?></em><?php endif; ?>
                </a>
                <a href="<?= url('contacts') ?>" class="nav-item <?= str_contains($navUri, '/contacts') ? 'active' : '' ?>">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    Contacts
                </a>
                <a href="<?= url('profile') ?>" class="nav-item <?= str_contains($navUri, '/profile') || str_contains($navUri, '/sessions') ? 'active' : '' ?>">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    Profil
                </a>
            </nav>

            <div class="sidebar-footer">
                <?php if ($me): ?>
                <a href="<?= url('profile') ?>" class="user-chip">
                    <img src="<?= upload_url($me['photo']) ?>" alt="" class="avatar avatar-sm">
                    <span><?= e($me['prenom']) ?></span>
                </a>
                <?php endif; ?>
                <a href="<?= url('logout') ?>" class="nav-item logout">Déconnexion</a>
            </div>
        </aside>

        <main class="main-panel">
            <?php if (!empty($flash)): ?>
            <div class="flash flash-<?= e($flash['type']) ?>" data-flash>
                <?= e($flash['message']) ?>
                <button type="button" class="flash-close" aria-label="Fermer">&times;</button>
            </div>
            <?php endif; ?>
            <?= $content ?>
        </main>
    </div>
    <script src="<?= asset('js/app.js') ?>?v=5"></script>
</body>
</html>
