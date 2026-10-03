<?php
$liked = !empty($post['my_reaction']);
$likes = (int) ($post['reaction_count'] ?? 0);
$commentsCount = (int) ($post['comment_count'] ?? 0);
$linkHost = !empty($post['link_url']) ? (parse_url($post['link_url'], PHP_URL_HOST) ?: $post['link_url']) : '';
$media = $post['media'] ?? [];
$isOwner = (int) ($meId ?? 0) === (int) $post['user_id'];
$canAct = (int) ($meId ?? 0) > 0;
?>
<article class="pcard" id="post-<?= (int) $post['id'] ?>">
    <?php if (!empty($post['repost_name'])): ?>
        <p class="pcard-repost"><?= e($post['repost_name']) ?> a partagé</p>
    <?php endif; ?>

    <header class="pcard-head">
        <a href="<?= url('user/' . $post['user_id']) ?>" class="pcard-user">
            <img src="<?= upload_url($post['photo']) ?>" alt="" class="pcard-ava">
            <span>
                <strong><?= e($post['prenom'] . ' ' . $post['nom']) ?></strong>
                <small>
                    <?php if (!empty($post['location'])): ?><?= e($post['location']) ?> · <?php endif; ?>
                    <time datetime="<?= e($post['created_at']) ?>"><?= time_ago($post['created_at']) ?></time>
                </small>
            </span>
        </a>
        <details class="pcard-more">
            <summary aria-label="Options">···</summary>
            <div class="pcard-menu">
                <button type="button" data-copy="<?= e(url('posts/' . $post['id'])) ?>">Copier le lien</button>
                <button type="button" data-share="<?= e(url('posts/' . $post['id'])) ?>">Partager</button>
                <?php if ($isOwner): ?>
                    <a href="<?= url('posts/' . $post['id'] . '/edit') ?>">Modifier</a>
                    <form method="POST" action="<?= url('posts/' . $post['id'] . '/pin') ?>"><?= $csrf ?><button type="submit"><?= !empty($post['is_pinned']) ? 'Désépingler' : 'Épingler' ?></button></form>
                    <form method="POST" action="<?= url('posts/' . $post['id'] . '/delete') ?>" onsubmit="return confirm('Supprimer cette publication ?');"><?= $csrf ?><button type="submit" class="is-danger">Supprimer</button></form>
                <?php else: ?>
                    <form method="POST" action="<?= url('posts/' . $post['id'] . '/hide') ?>"><?= $csrf ?><button type="submit">Masquer</button></form>
                    <?php if ($canAct): ?>
                    <details>
                        <summary class="is-danger">Signaler</summary>
                        <form method="POST" action="<?= url('posts/' . $post['id'] . '/report') ?>" class="pcard-report">
                            <?= $csrf ?>
                            <select name="reason">
                                <option value="spam">Spam</option>
                                <option value="fraude">Fraude</option>
                                <option value="harcelement">Harcèlement</option>
                                <option value="inapproprie">Inapproprié</option>
                                <option value="autre">Autre</option>
                            </select>
                            <button type="submit">Envoyer</button>
                        </form>
                    </details>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </details>
    </header>

    <?php if (!empty($post['contenu'])): ?>
        <div class="pcard-text"><?= render_content($post['contenu']) ?></div>
    <?php endif; ?>

    <?php if ($media): ?>
        <div class="pcard-media media-<?= min(count($media), 4) ?>">
            <?php foreach ($media as $file): ?>
                <?php if ($file['file_type'] === 'video'): ?>
                    <video src="<?= upload_url($file['file_path']) ?>" controls playsinline></video>
                <?php else: ?>
                    <img src="<?= upload_url($file['file_path']) ?>" alt="">
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($post['link_url'])): ?>
        <a class="pcard-link" href="<?= e($post['link_url']) ?>" target="_blank" rel="noopener noreferrer">
            <span><?= e($linkHost) ?></span>
            <strong><?= e($post['link_url']) ?></strong>
        </a>
    <?php endif; ?>

    <?php if ($likes > 0 || $commentsCount > 0): ?>
    <div class="pcard-meta">
        <?php if ($likes > 0): ?><strong><?= $likes ?> j’aime</strong><?php endif; ?>
        <?php if ($commentsCount > 0): ?>
            <a href="<?= url('posts/' . $post['id']) ?>#comments"><?= $commentsCount ?> commentaire<?= $commentsCount > 1 ? 's' : '' ?></a>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="pcard-actions">
        <?php if ($canAct): ?>
            <form method="POST" action="<?= url('posts/' . $post['id'] . '/react') ?>">
                <?= $csrf ?>
                <input type="hidden" name="reaction" value="like">
                <button type="submit" class="pcard-act <?= $liked ? 'is-liked' : '' ?>" aria-label="J’aime">
                    <?php if ($liked): ?>
                        <svg viewBox="0 0 24 24" width="22" height="22"><path fill="currentColor" d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>
                    <?php else: ?>
                        <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
                    <?php endif; ?>
                    <span>J’aime</span>
                </button>
            </form>
        <?php else: ?>
            <a class="pcard-act" href="<?= url('login') ?>"><svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg><span>J’aime</span></a>
        <?php endif; ?>

        <a class="pcard-act" href="<?= url('posts/' . $post['id']) ?>#comments">
            <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
            <span>Commenter</span>
        </a>

        <?php if ($canAct): ?>
            <form method="POST" action="<?= url('posts/' . $post['id'] . '/repost') ?>">
                <?= $csrf ?>
                <button type="submit" class="pcard-act <?= !empty($post['is_reposted']) ? 'is-on' : '' ?>">
                    <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 12v7a1 1 0 0 0 1 1h7"/><path d="m4 19 5-5"/><path d="M20 12V5a1 1 0 0 0-1-1h-7"/><path d="m20 5-5 5"/></svg>
                    <span>Partager</span>
                </button>
            </form>
            <form method="POST" action="<?= url('posts/' . $post['id'] . '/save') ?>">
                <?= $csrf ?>
                <button type="submit" class="pcard-act <?= !empty($post['is_saved']) ? 'is-saved' : '' ?>">
                    <?php if (!empty($post['is_saved'])): ?>
                        <svg viewBox="0 0 24 24" width="20" height="20"><path fill="currentColor" d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>
                    <?php else: ?>
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>
                    <?php endif; ?>
                    <span>Enregistrer</span>
                </button>
            </form>
        <?php endif; ?>
    </div>

    <?php if ($canAct): ?>
    <form class="pcard-comment" method="POST" action="<?= url('posts/' . $post['id'] . '/comment') ?>">
        <img src="<?= upload_url($me['photo'] ?? null) ?>" alt="" class="pcard-ava sm">
        <?= $csrf ?>
        <input type="text" name="contenu" maxlength="500" placeholder="Écrire un commentaire…" required>
        <button type="submit">Publier</button>
    </form>
    <?php endif; ?>
</article>
