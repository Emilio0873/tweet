<div class="feed-col alone">
    <a href="<?= url('') ?>" class="btn btn-ghost back-link">← Accueil</a>
    <?php require dirname(__DIR__) . '/partials/post_card.php'; ?>

    <section class="panel comments" id="comments">
        <div class="comments-head">
            <h2>Commentaires</h2>
            <div>
                <a href="<?= url('posts/' . $post['id'] . '?sort=recent') ?>#comments" class="<?= $sort === 'recent' ? 'active' : '' ?>">Récents</a>
                <a href="<?= url('posts/' . $post['id'] . '?sort=popular') ?>#comments" class="<?= $sort === 'popular' ? 'active' : '' ?>">Populaires</a>
            </div>
        </div>

        <form method="POST" action="<?= url('posts/' . $post['id'] . '/comment') ?>" class="comment-form">
            <?= $csrf ?>
            <textarea name="contenu" rows="2" maxlength="500" placeholder="Écrire un commentaire…" required></textarea>
            <button type="submit" class="btn btn-primary">Commenter</button>
        </form>

        <?php if (empty($roots)): ?>
            <p class="muted">Pas encore de commentaire.</p>
        <?php endif; ?>

        <?php foreach ($roots as $comment): ?>
            <?php require dirname(__DIR__) . '/partials/comment.php'; ?>
        <?php endforeach; ?>
    </section>
</div>
