<?php $nested = $children[(int) $comment['id']] ?? []; ?>
<article class="comment" id="comment-<?= (int) $comment['id'] ?>">
    <img src="<?= upload_url($comment['photo']) ?>" alt="" class="avatar avatar-sm">
    <div>
        <header>
            <a href="<?= url('user/' . $comment['user_id']) ?>"><strong><?= e($comment['prenom'] . ' ' . $comment['nom']) ?></strong></a>
            <small>@<?= e($comment['username'] ?? '') ?> · <?= time_ago($comment['created_at']) ?><?php if (!empty($comment['is_edited'])): ?> · modifié<?php endif; ?></small>
        </header>
        <p><?= render_content($comment['contenu']) ?></p>
        <div class="comment-actions">
            <form method="POST" action="<?= url('comments/' . $comment['id'] . '/like') ?>">
                <?= $csrf ?>
                <button type="submit"><?= !empty($comment['liked']) ? '❤️' : '🤍' ?> <?= (int) $comment['like_count'] ?></button>
            </form>
            <details>
                <summary>Répondre</summary>
                <form method="POST" action="<?= url('posts/' . $comment['post_id'] . '/comment') ?>">
                    <?= $csrf ?>
                    <input type="hidden" name="parent_id" value="<?= (int) $comment['id'] ?>">
                    <input type="text" name="contenu" maxlength="500" required placeholder="Votre réponse">
                    <button type="submit" class="btn btn-secondary btn-sm">Répondre</button>
                </form>
            </details>
            <?php if ((int) $comment['user_id'] === (int) $meId): ?>
                <details>
                    <summary>Modifier</summary>
                    <form method="POST" action="<?= url('comments/' . $comment['id'] . '/update') ?>">
                        <?= $csrf ?>
                        <input type="text" name="contenu" value="<?= e($comment['contenu']) ?>" maxlength="500" required>
                        <button type="submit" class="btn btn-secondary btn-sm">Enregistrer</button>
                    </form>
                </details>
                <form method="POST" action="<?= url('comments/' . $comment['id'] . '/delete') ?>" onsubmit="return confirm('Supprimer ce commentaire ?');">
                    <?= $csrf ?>
                    <button type="submit" class="danger">Supprimer</button>
                </form>
            <?php endif; ?>
        </div>
        <?php if ($nested): ?>
            <div class="replies">
                <?php foreach ($nested as $reply): ?>
                    <?php $comment = $reply; require __FILE__; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</article>
