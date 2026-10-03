<div class="feed-col alone">
    <header class="feed-top"><h1>Enregistrements</h1></header>
    <?php if (empty($posts)): ?>
        <div class="empty-state"><p>Vous n’avez encore rien enregistré.</p></div>
    <?php else: ?>
        <?php require __DIR__ . '/cards.php'; ?>
    <?php endif; ?>
</div>
