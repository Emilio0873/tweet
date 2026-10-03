<?php $meId = (int) ($meId ?? $me['id'] ?? 0); ?>
<div class="feed" id="social-feed" data-mode="<?= e($mode) ?>" data-newest="<?= (int) ($newestId ?? 0) ?>" data-poll="<?= e(url('feed/poll')) ?>">
    <div class="feed-shell">
        <nav class="feed-tabs" aria-label="Tri du fil">
            <a href="<?= url('?mode=recent') ?>" class="<?= $mode === 'recent' ? 'is-active' : '' ?>">Récentes</a>
            <a href="<?= url('?mode=following') ?>" class="<?= $mode === 'following' ? 'is-active' : '' ?>">Suivis</a>
            <a href="<?= url('?mode=foryou') ?>" class="<?= $mode === 'foryou' ? 'is-active' : '' ?>">Pour vous</a>
            <a href="<?= url('?mode=popular') ?>" class="<?= $mode === 'popular' ? 'is-active' : '' ?>">Populaires</a>
        </nav>

        <button type="button" class="feed-toast" id="feed-new-banner" hidden>
            <span>Nouvelles publications</span>
            <em>Actualiser</em>
        </button>

        <?php if (!empty($me)): ?>
            <?php require dirname(__DIR__) . '/partials/composer.php'; ?>
        <?php else: ?>
            <section class="composer composer-guest">
                <p>Connectez-vous pour publier sur le fil local.</p>
                <a class="btn btn-blue" href="<?= url('login') ?>">Connexion</a>
            </section>
        <?php endif; ?>

        <div class="feed-stream" id="feed-list">
            <?php if (empty($posts)): ?>
                <div class="feed-empty">
                    <div class="feed-empty-orb" aria-hidden="true"></div>
                    <h2>Rien pour le moment</h2>
                    <p>Publiez une actualité locale, ou suivez des personnes pour remplir votre fil.</p>
                </div>
            <?php else: ?>
                <?php require __DIR__ . '/cards.php'; ?>
            <?php endif; ?>
        </div>

        <?php if (!empty($hasMore)): ?>
            <button type="button" class="feed-more" id="load-more"
                data-next="<?= url('?mode=' . urlencode($mode) . '&page=2&ajax=1') ?>">
                <span>Charger plus</span>
            </button>
        <?php endif; ?>
    </div>
</div>
