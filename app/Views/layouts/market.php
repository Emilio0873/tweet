<!DOCTYPE html>
<html lang="fr" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? 'Annonces') ?> — TWEET</title>
    <link rel="icon" href="<?= asset('images/logo.png') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset('css/market.css') ?>?v=33">
    <meta name="theme-color" content="#0F2847">
    <script>
      (function () {
        var t = localStorage.getItem('tweet-theme') || 'light';
        document.documentElement.setAttribute('data-theme', t === 'auto'
          ? (matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light')
          : t);
        document.documentElement.dataset.themePref = t;
      })();
    </script>
</head>
<body>
<?php
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    $on = function (string $path) use ($uri): string {
        if ($path === '/') {
            $pathOnly = parse_url($uri, PHP_URL_PATH) ?: '';
            return preg_match('#/(public)?/?$#', $pathOnly) ? 'on' : '';
        }
        return str_contains($uri, $path) ? 'on' : '';
    };
    $notifCount = 0;
    $chatUnread = 0;
    if (!empty($me['id'])) {
        $notifCount = (new \App\Models\Notification())->unreadCount((int) $me['id']);
        $chatUnread = (new \App\Models\Conversation())->unreadCount((int) $me['id']);
    }
    $isChat = str_contains($uri, '/chat');
    $profileOn = $on('/profile') && !str_contains($uri, '/settings') && !str_contains($uri, '/sessions') ? 'on' : '';
    $settingsOn = ($on('/settings') || str_contains($uri, '/sessions')) ? 'on' : '';
?>
<div class="shell <?= $isChat ? 'shell-chat' : '' ?>">
    <?php if ($isChat): ?>
    <aside class="side-rail" id="side">
        <a href="<?= url('') ?>" class="rail-logo" title="TWEET">
            <img src="<?= asset('images/logo.png') ?>" alt="TWEET">
        </a>
        <nav class="rail-nav">
            <a class="<?= $on('/') ?>" href="<?= url('') ?>" title="Accueil">
                <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 10.5 12 4l8 6.5V20a1 1 0 0 1-1 1h-5v-6H10v6H5a1 1 0 0 1-1-1z"/></svg>
            </a>
            <a class="<?= $on('/explore') ?>" href="<?= url(!empty($me) ? 'explore' : 'login') ?>" title="Recherche">
                <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
            </a>
            <a class="<?= $on('/publier') ?>" href="<?= url(!empty($me) ? 'publier' : 'login') ?>" title="Publier">
                <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="4" y="4" width="16" height="16" rx="4"/><path d="M12 8v8M8 12h8"/></svg>
            </a>
            <a class="on" href="<?= url(!empty($me) ? 'chat' : 'login') ?>" title="Messages">
                <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M21 12a8 8 0 0 1-11.5 7.2L4 20l.9-4.2A8 8 0 1 1 21 12z"/></svg>
                <?php if ($chatUnread > 0): ?><span class="rail-badge"><?= $chatUnread > 9 ? '9+' : $chatUnread ?></span><?php endif; ?>
            </a>
            <a class="<?= $on('/annonces') ?>" href="<?= url('annonces') ?>" title="Annonces">
                <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 7h16v12a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V7z"/><path d="M8 7V5a4 4 0 0 1 8 0v2"/></svg>
            </a>
            <a class="<?= $on('/notifications') ?>" href="<?= url(!empty($me) ? 'notifications' : 'login') ?>" title="Notifications">
                <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M15 17h5l-1.4-1.4A2 2 0 0 1 18 14.2V11a6 6 0 1 0-12 0v3.2a2 2 0 0 1-.6 1.4L4 17h5"/><path d="M9.5 17a2.5 2.5 0 0 0 5 0"/></svg>
                <?php if ($notifCount > 0): ?><span class="rail-badge"><?= $notifCount > 9 ? '9+' : $notifCount ?></span><?php endif; ?>
            </a>
            <a class="<?= $profileOn ?>" href="<?= url(!empty($me) ? 'profile' : 'login') ?>" title="Profil">
                <?php if (!empty($me['photo'])): ?>
                    <img class="rail-avatar" src="<?= upload_url($me['photo']) ?>" alt="">
                <?php else: ?>
                    <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="8" r="3.2"/><path d="M5 19.2a7 7 0 0 1 14 0"/></svg>
                <?php endif; ?>
            </a>
        </nav>
        <div class="rail-bottom">
            <a class="<?= $settingsOn ?>" href="<?= url(!empty($me) ? 'settings' : 'login') ?>" title="Paramètres">
                <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="3"/><path d="M12 1v2M12 21v2M4.2 4.2l1.4 1.4M18.4 18.4l1.4 1.4M1 12h2M21 12h2M4.2 19.8l1.4-1.4M18.4 5.6l1.4-1.4"/></svg>
            </a>
            <?php if (!empty($me)): ?>
                <a href="<?= url('logout') ?>" title="Déconnexion" class="rail-logout">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5M21 12H9"/></svg>
                </a>
            <?php endif; ?>
        </div>
    </aside>
    <?php else: ?>
    <aside class="side" id="side">
        <a href="<?= url('') ?>" class="logo">
            <img class="logo-mark" src="<?= asset('images/logo.png') ?>" alt="TWEET">
            <span>TWEET</span>
        </a>
        <nav class="side-nav">
            <a class="<?= $on('/') ?>" href="<?= url('') ?>">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 10.5 12 4l8 6.5V20a1 1 0 0 1-1 1h-5v-6H10v6H5a1 1 0 0 1-1-1z"/></svg>
                Accueil
            </a>
            <a class="<?= $on('/explore') ?>" href="<?= url(!empty($me) ? 'explore' : 'login') ?>">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                Recherche
            </a>
            <a class="<?= $on('/annonces') ?>" href="<?= url('annonces') ?>">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 7h16v12a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V7z"/><path d="M8 7V5a4 4 0 0 1 8 0v2"/></svg>
                Annonces
            </a>
            <a class="<?= $on('/publier') ?>" href="<?= url(!empty($me) ? 'publier' : 'login') ?>">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                Publier
            </a>
            <a class="<?= $on('/chat') ?>" href="<?= url(!empty($me) ? 'chat' : 'login') ?>">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a8 8 0 0 1-11.5 7.2L4 20l.9-4.2A8 8 0 1 1 21 12z"/></svg>
                Messages
                <?php if ($chatUnread > 0): ?><span class="nav-badge"><?= $chatUnread > 9 ? '9+' : $chatUnread ?></span><?php endif; ?>
            </a>
            <a class="<?= $on('/notifications') ?>" href="<?= url(!empty($me) ? 'notifications' : 'login') ?>">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 17h5l-1.4-1.4A2 2 0 0 1 18 14.2V11a6 6 0 1 0-12 0v3.2a2 2 0 0 1-.6 1.4L4 17h5"/><path d="M9.5 17a2.5 2.5 0 0 0 5 0"/></svg>
                Notifications
                <?php if ($notifCount > 0): ?><span class="nav-badge"><?= $notifCount > 9 ? '9+' : $notifCount ?></span><?php endif; ?>
            </a>
            <a class="<?= $profileOn ?>" href="<?= url(!empty($me) ? 'profile' : 'login') ?>">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="3.2"/><path d="M5 19.2a7 7 0 0 1 14 0"/></svg>
                Profil
            </a>
            <a class="<?= $settingsOn ?>" href="<?= url(!empty($me) ? 'settings' : 'login') ?>">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M12 1v2M12 21v2M4.2 4.2l1.4 1.4M18.4 18.4l1.4 1.4M1 12h2M21 12h2M4.2 19.8l1.4-1.4M18.4 5.6l1.4-1.4"/></svg>
                Paramètres
            </a>
        </nav>
        <div class="side-foot">
            <?php if (!empty($me)): ?>
                <a href="<?= url('profile') ?>" class="who">
                    <img src="<?= upload_url($me['photo'] ?? null) ?>" alt="">
                    <span><?= e($me['prenom']) ?></span>
                </a>
                <a href="<?= url('logout') ?>" class="quiet">Déconnexion</a>
            <?php else: ?>
                <a href="<?= url('login') ?>" class="btn btn-blue">Connexion</a>
            <?php endif; ?>
        </div>
    </aside>
    <?php endif; ?>
    <div class="scrim" id="scrim"></div>

    <div class="stage">
        <?php if (!$isChat): ?>
        <header class="page-head">
            <button type="button" class="menu-btn" id="menu-btn" aria-label="Ouvrir le menu">
                <span></span><span></span><span></span>
            </button>
            <div class="page-head-brand">
                <strong>TWEET</strong>
                <span>100 % local · Annonces &amp; social</span>
            </div>
            <div class="page-head-actions">
                <a href="<?= url(!empty($me) ? 'notifications' : 'login') ?>" class="head-ico" title="Notifications">
                    <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M15 17h5l-1.4-1.4A2 2 0 0 1 18 14.2V11a6 6 0 1 0-12 0v3.2a2 2 0 0 1-.6 1.4L4 17h5"/><path d="M9.5 17a2.5 2.5 0 0 0 5 0"/></svg>
                    <?php if ($notifCount > 0): ?><em><?= $notifCount > 9 ? '9+' : $notifCount ?></em><?php endif; ?>
                </a>
                <a href="<?= url(!empty($me) ? 'chat' : 'login') ?>" class="head-ico" title="Messages">
                    <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M21 12a8 8 0 0 1-11.5 7.2L4 20l.9-4.2A8 8 0 1 1 21 12z"/></svg>
                    <?php if ($chatUnread > 0): ?><em><?= $chatUnread > 9 ? '9+' : $chatUnread ?></em><?php endif; ?>
                </a>
            </div>
        </header>
        <?php else: ?>
        <button type="button" class="menu-btn chat-menu-btn" id="menu-btn" aria-label="Ouvrir le menu">
            <span></span><span></span><span></span>
        </button>
        <?php endif; ?>
        <main class="page<?= !empty($pageClass) ? ' ' . e($pageClass) : '' ?>">
            <?php if (!empty($flash)): ?>
                <div class="flash"><?= e($flash['message']) ?></div>
            <?php endif; ?>
            <?= $content ?>
        </main>
    </div>
</div>
<script src="<?= asset('js/market.js') ?>?v=11"></script>
</body>
</html>
