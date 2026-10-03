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
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>?v=12">
    <meta name="theme-color" content="#0a2a3a">
</head>
<body class="auth-body">
    <div class="auth-bg" aria-hidden="true"></div>
    <div class="auth-veil" aria-hidden="true"></div>

    <main class="auth-center">
        <?php if (!empty($flash)): ?>
        <div class="flash flash-<?= e($flash['type']) ?>" data-flash>
            <?= e($flash['message']) ?>
            <button type="button" class="flash-close" aria-label="Fermer">&times;</button>
        </div>
        <?php endif; ?>

        <div class="glass-card<?= !empty($authWide) ? ' glass-card-wide' : '' ?>">
            <?= $content ?>
        </div>
    </main>

    <script src="<?= asset('js/app.js') ?>?v=6"></script>
</body>
</html>
