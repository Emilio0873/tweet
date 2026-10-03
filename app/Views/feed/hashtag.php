<div class="feed-col alone">
    <header class="feed-top"><h1>#<?= e($tag) ?></h1></header>
    <?php if (empty($posts)): ?>
        <div class="empty-state"><p>Aucune publication avec ce hashtag.</p></div>
    <?php else: ?>
        <?php require __DIR__ . '/cards.php'; ?>
    <?php endif; ?>
</div>
