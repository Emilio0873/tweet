<?php
$fullName = trim(($profile['prenom'] ?? '') . ' ' . ($profile['nom'] ?? ''));
$coverUrl = !empty($profile['cover_photo']) ? upload_url($profile['cover_photo']) : null;
$postCount = (int) count($posts ?? $listings ?? []);
?>
<div class="pf">
    <header class="pf-head">
        <div class="pf-cover<?= $coverUrl ? ' has-cover' : '' ?>">
            <?php if ($coverUrl): ?>
                <img class="pf-cover-img" src="<?= e($coverUrl) ?>" alt="">
            <?php else: ?>
                <div class="pf-cover-fallback" aria-hidden="true"></div>
            <?php endif; ?>
            <div class="pf-cover-shade" aria-hidden="true"></div>
        </div>

        <div class="pf-identity">
            <div class="pf-avatar-wrap">
                <img class="pf-avatar" src="<?= upload_url($profile['photo'] ?? null) ?>" alt="">
            </div>
            <div class="pf-id">
                <div class="pf-id-text">
                    <h1><?= e($fullName) ?></h1>
                    <?php if (!empty($profile['username'])): ?>
                        <p class="pf-handle">@<?= e($profile['username']) ?></p>
                    <?php endif; ?>
                    <p class="pf-stats">
                        <span><?= $postCount ?> publication<?= $postCount > 1 ? 's' : '' ?></span>
                        <span>·</span>
                        <span><?= (int) ($followers ?? 0) ?> abonné<?= (int) ($followers ?? 0) > 1 ? 's' : '' ?></span>
                    </p>
                    <?php if (empty($profile['hide_last_seen'])): ?>
                        <p class="pf-presence">
                            <?php if (!empty($profile['is_online'])): ?>
                                <em class="online"></em> En ligne
                            <?php elseif (!empty($profile['derniere_connexion'])): ?>
                                Vu <?= time_ago($profile['derniere_connexion']) ?>
                            <?php endif; ?>
                        </p>
                    <?php endif; ?>
                </div>
                <div class="pf-actions">
                    <?php if (!empty($isSelf)): ?>
                        <a class="btn btn-soft" href="<?= url('profile') ?>">Mon profil</a>
                        <a class="btn btn-soft" href="<?= url('settings') ?>">Paramètres</a>
                    <?php else: ?>
                        <?php if (!empty($canContact)): ?>
                        <form method="POST" action="<?= url('chat/start') ?>">
                            <?= $csrf ?>
                            <input type="hidden" name="user_id" value="<?= (int) $profile['id'] ?>">
                            <button type="submit" class="btn btn-blue">Message</button>
                        </form>
                        <?php endif; ?>
                        <?php if (empty($isFollowing)): ?>
                        <form method="POST" action="<?= url('follow') ?>">
                            <?= $csrf ?>
                            <input type="hidden" name="user_id" value="<?= (int) $profile['id'] ?>">
                            <button type="submit" class="btn btn-soft">Suivre</button>
                        </form>
                        <?php else: ?>
                        <form method="POST" action="<?= url('unfollow') ?>">
                            <?= $csrf ?>
                            <input type="hidden" name="user_id" value="<?= (int) $profile['id'] ?>">
                            <button type="submit" class="btn btn-soft">Ne plus suivre</button>
                        </form>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>

    <div class="pf-body">
        <aside class="pf-aside">
            <section class="pf-card">
                <h2>Intro</h2>
                <?php if (!empty($profile['bio'])): ?>
                    <p class="pf-bio"><?= nl2br(e($profile['bio'])) ?></p>
                <?php else: ?>
                    <p class="pf-muted">Aucune bio pour le moment.</p>
                <?php endif; ?>
                <ul class="pf-intro">
                    <li>
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/></svg>
                        <span>Membre depuis <?= date('d/m/Y', strtotime($profile['created_at'])) ?></span>
                    </li>
                </ul>
            </section>
        </aside>
        <section class="pf-main">
            <?php
                $feedItems = $posts ?? $listings ?? [];
                $isPosts = isset($posts);
            ?>
            <?php if (empty($feedItems)): ?>
                <div class="pf-empty">
                    <h2>Aucune publication</h2>
                </div>
            <?php elseif ($isPosts): ?>
                <div class="pf-feed" id="social-feed">
                    <?php foreach ($posts as $post): ?>
                        <?php require dirname(__DIR__) . '/partials/post_card.php'; ?>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="pf-feed">
                    <?php foreach ($listings as $listing): ?>
                        <?php require dirname(__DIR__) . '/partials/feed_post.php'; ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>
</div>
