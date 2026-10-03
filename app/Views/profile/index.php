<?php
$tab = in_array(($tab ?? 'posts'), ['posts', 'about', 'listings'], true) ? $tab : 'posts';
$fullName = trim(($user['prenom'] ?? '') . ' ' . ($user['nom'] ?? ''));
$coverUrl = !empty($user['cover_photo']) ? upload_url($user['cover_photo']) : null;
$postCount = (int) count($posts ?? []);
$listingCount = (int) count($listings ?? []);
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
            <form class="pf-cover-form" method="POST" action="<?= url('profile/cover') ?>" enctype="multipart/form-data">
                <?= $csrf ?>
                <label class="pf-cover-btn">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 7h3l1.5-2h7L17 7h3a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V9a2 2 0 0 1 2-2z"/><circle cx="12" cy="13" r="3.5"/></svg>
                    <span><?= $coverUrl ? 'Modifier la couverture' : 'Ajouter une couverture' ?></span>
                    <input type="file" name="cover_photo" accept="image/jpeg,image/png,image/webp" onchange="this.form.submit()">
                </label>
            </form>
        </div>

        <div class="pf-identity">
            <div class="pf-avatar-wrap">
                <img class="pf-avatar" src="<?= upload_url($user['photo'] ?? null) ?>" alt="">
                <form class="pf-avatar-form" method="POST" action="<?= url('profile/avatar') ?>" enctype="multipart/form-data" title="Changer la photo">
                    <?= $csrf ?>
                    <label class="pf-avatar-btn">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 7h3l1.5-2h7L17 7h3a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V9a2 2 0 0 1 2-2z"/><circle cx="12" cy="13" r="3.5"/></svg>
                        <input type="file" name="photo" accept="image/jpeg,image/png,image/webp" onchange="this.form.submit()">
                    </label>
                </form>
            </div>

            <div class="pf-id">
                <div class="pf-id-text">
                    <h1><?= e($fullName) ?></h1>
                    <?php if (!empty($user['username'])): ?>
                        <p class="pf-handle">@<?= e($user['username']) ?></p>
                    <?php endif; ?>
                    <p class="pf-stats">
                        <span><?= $postCount ?> publication<?= $postCount > 1 ? 's' : '' ?></span>
                        <span>·</span>
                        <span><?= $listingCount ?> annonce<?= $listingCount > 1 ? 's' : '' ?></span>
                    </p>
                </div>
                <div class="pf-actions">
                    <a class="btn btn-blue" href="<?= url('publier') ?>">Publier</a>
                    <a class="btn btn-soft" href="<?= url('settings') ?>">Modifier le profil</a>
                </div>
            </div>
        </div>

        <nav class="pf-tabs" aria-label="Sections du profil">
            <a class="<?= $tab === 'posts' ? 'on' : '' ?>" href="<?= url('profile?tab=posts') ?>">Publications</a>
            <a class="<?= $tab === 'listings' ? 'on' : '' ?>" href="<?= url('profile?tab=listings') ?>">Annonces</a>
            <a class="<?= $tab === 'about' ? 'on' : '' ?>" href="<?= url('profile?tab=about') ?>">À propos</a>
        </nav>
    </header>

    <div class="pf-body">
        <?php if ($tab === 'about'): ?>
            <section class="pf-card pf-card-wide">
                <h2>À propos</h2>
                <ul class="pf-meta">
                    <li><strong>Nom complet</strong><span><?= e($fullName) ?></span></li>
                    <li><strong>Pseudo</strong><span><?= e($user['username'] ?: '—') ?></span></li>
                    <li><strong>Email</strong><span><?= e($user['email']) ?></span></li>
                    <li><strong>Téléphone</strong><span><?= e($user['telephone'] ?? '—') ?></span></li>
                    <li><strong>Bio</strong><span><?= e($user['bio'] ?: 'Non renseignée') ?></span></li>
                    <li><strong>Membre depuis</strong><span><?= date('d/m/Y', strtotime($user['created_at'])) ?></span></li>
                </ul>
                <a class="btn btn-soft" href="<?= url('settings') ?>">Modifier</a>
            </section>
        <?php else: ?>
            <aside class="pf-aside">
                <section class="pf-card">
                    <h2>Intro</h2>
                    <?php if (!empty($user['bio'])): ?>
                        <p class="pf-bio"><?= nl2br(e($user['bio'])) ?></p>
                    <?php else: ?>
                        <p class="pf-muted">Ajoutez une bio pour vous présenter.</p>
                    <?php endif; ?>
                    <ul class="pf-intro">
                        <li>
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 6h16v12H4z"/><path d="m4 7 8 6 8-6"/></svg>
                            <span><?= e($user['email']) ?></span>
                        </li>
                        <?php if (!empty($user['telephone'])): ?>
                        <li>
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M6.5 3.5h3l1.5 4-2 1.5a12 12 0 0 0 5.5 5.5l1.5-2 4 1.5v3a2 2 0 0 1-2.2 2A16 16 0 0 1 4.5 5.7a2 2 0 0 1 2-2.2z"/></svg>
                            <span><?= e($user['telephone']) ?></span>
                        </li>
                        <?php endif; ?>
                        <li>
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/></svg>
                            <span>Membre depuis <?= date('d/m/Y', strtotime($user['created_at'])) ?></span>
                        </li>
                    </ul>
                    <a class="btn btn-soft btn-block" href="<?= url('settings') ?>">Modifier les infos</a>
                </section>
            </aside>

            <section class="pf-main">
                <?php if ($tab === 'listings'): ?>
                    <?php if (empty($listings)): ?>
                        <div class="pf-empty">
                            <h2>Aucune annonce</h2>
                            <p>Vos annonces marketplace apparaîtront ici.</p>
                            <a class="btn btn-blue" href="<?= url('publier') ?>">Créer une annonce</a>
                        </div>
                    <?php else: ?>
                        <div class="card-grid">
                            <?php foreach ($listings as $listing): ?>
                                <?php require dirname(__DIR__) . '/partials/listing_card.php'; ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <?php if (empty($posts)): ?>
                        <div class="pf-empty">
                            <h2>Aucune publication</h2>
                            <p>Quand vous publiez sur le fil, vos posts apparaissent ici.</p>
                            <a class="btn btn-blue" href="<?= url('') ?>">Aller au fil</a>
                        </div>
                    <?php else: ?>
                        <div class="pf-feed" id="social-feed">
                            <?php foreach ($posts as $post): ?>
                                <?php require dirname(__DIR__) . '/partials/post_card.php'; ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </section>
        <?php endif; ?>
    </div>
</div>
