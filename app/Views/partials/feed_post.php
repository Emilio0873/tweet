<?php
$cover = $listing['images'][0]['file_path'] ?? null;
$catLabel = \App\Models\Listing::CATEGORIES[$listing['category']] ?? $listing['category'];
$likes = (int) ($listing['like_count'] ?? 0);
$commentsCount = (int) ($listing['comment_count'] ?? 0);
$comments = $listing['comments'] ?? [];
$liked = !empty($listing['liked']);
$saved = !empty($listing['saved']);
?>
<article class="fb-post" id="post-<?= (int) $listing['id'] ?>">
    <header class="fb-head">
        <a href="<?= url('user/' . $listing['user_id']) ?>" class="fb-author">
            <img src="<?= upload_url($listing['photo']) ?>" alt="">
            <span>
                <strong><?= e($listing['prenom'] . ' ' . $listing['nom']) ?></strong>
                <small><?= time_ago($listing['created_at']) ?> · <?= e($catLabel) ?> · <?= e($listing['city']) ?></small>
            </span>
        </a>
        <span class="fb-price"><?= money($listing['price']) ?></span>
        <?php if (!empty($me) && (int) $me['id'] === (int) $listing['user_id']): ?>
            <div class="post-owner">
                <a href="<?= url('annonces/' . $listing['id'] . '/edit') ?>" title="Modifier">Modifier</a>
                <form method="POST" action="<?= url('annonces/' . $listing['id'] . '/delete') ?>" onsubmit="return confirm('Supprimer cette publication ?');">
                    <?= $csrf ?>
                    <button type="submit">Supprimer</button>
                </form>
            </div>
        <?php endif; ?>
    </header>

    <h3 class="fb-title"><a href="<?= url('annonces/' . $listing['id']) ?>"><?= e($listing['title']) ?></a></h3>
    <?php if (!empty($listing['description'])): ?>
        <p class="fb-text"><?= nl2br(e(mb_strimwidth($listing['description'], 0, 320, '…'))) ?></p>
    <?php endif; ?>

    <?php if ($cover): ?>
        <a class="fb-media" href="<?= url('annonces/' . $listing['id']) ?>">
            <img src="<?= upload_url($cover) ?>" alt="">
        </a>
    <?php endif; ?>

    <div class="ig-actions">
        <div class="ig-actions-left">
            <?php if (!empty($me)): ?>
                <form method="POST" action="<?= url('annonces/' . $listing['id'] . '/like') ?>">
                    <?= $csrf ?>
                    <button type="submit" class="ig-btn <?= $liked ? 'liked' : '' ?>" aria-label="J’aime" title="J’aime">
                        <?php if ($liked): ?>
                            <svg viewBox="0 0 24 24" width="26" height="26" aria-hidden="true"><path fill="currentColor" d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>
                        <?php else: ?>
                            <svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
                        <?php endif; ?>
                    </button>
                </form>
            <?php else: ?>
                <a class="ig-btn" href="<?= url('login') ?>" aria-label="J’aime" title="J’aime">
                    <svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
                </a>
            <?php endif; ?>

            <a class="ig-btn" href="#comments-<?= (int) $listing['id'] ?>" aria-label="Commenter" title="Commenter">
                <svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
            </a>

            <a class="ig-btn" href="<?= url('annonces/' . $listing['id']) ?>" aria-label="Partager" title="Ouvrir">
                <svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
            </a>
        </div>

        <?php if (!empty($me)): ?>
            <form method="POST" action="<?= url('annonces/' . $listing['id'] . '/save') ?>">
                <?= $csrf ?>
                <button type="submit" class="ig-btn <?= $saved ? 'saved' : '' ?>" aria-label="Enregistrer" title="Enregistrer">
                    <?php if ($saved): ?>
                        <svg viewBox="0 0 24 24" width="24" height="24" aria-hidden="true"><path fill="currentColor" d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>
                    <?php else: ?>
                        <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>
                    <?php endif; ?>
                </button>
            </form>
        <?php endif; ?>
    </div>

    <?php if ($likes > 0 || $commentsCount > 0): ?>
    <div class="ig-stats">
        <?php if ($likes > 0): ?>
            <strong><?= $likes ?> j’aime</strong>
        <?php endif; ?>
        <?php if ($commentsCount > 0): ?>
            <span><?= $commentsCount ?> commentaire<?= $commentsCount > 1 ? 's' : '' ?></span>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="fb-comments" id="comments-<?= (int) $listing['id'] ?>">
        <?php foreach ($comments as $comment): ?>
            <div class="fb-comment">
                <img src="<?= upload_url($comment['photo'] ?? null) ?>" alt="">
                <div>
                    <strong><?= e(($comment['prenom'] ?? '') . ' ' . ($comment['nom'] ?? '')) ?></strong>
                    <p><?= nl2br(e($comment['contenu'])) ?></p>
                    <small><?= time_ago($comment['created_at']) ?></small>
                </div>
            </div>
        <?php endforeach; ?>

        <?php if ($commentsCount > count($comments)): ?>
            <a class="fb-more-comments" href="<?= url('annonces/' . $listing['id']) ?>#comments">Voir tous les commentaires</a>
        <?php endif; ?>

        <?php if (!empty($me)): ?>
            <form class="fb-comment-form" method="POST" action="<?= url('annonces/' . $listing['id'] . '/comment') ?>">
                <?= $csrf ?>
                <img src="<?= upload_url($me['photo'] ?? null) ?>" alt="">
                <input type="text" name="contenu" maxlength="1000" required placeholder="Ajouter un commentaire…">
                <button type="submit" class="ig-send" aria-label="Publier">Publier</button>
            </form>
        <?php else: ?>
            <p class="muted"><a href="<?= url('login') ?>">Connectez-vous</a> pour commenter.</p>
        <?php endif; ?>
    </div>
</article>
