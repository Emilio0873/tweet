<?php
$q = (string) ($q ?? '');
$scope = (string) ($scope ?? 'all');
$type = (string) ($type ?? '');
$hasQuery = trim($q) !== '';
$scopes = [
    'all'      => 'Tout',
    'users'    => 'Utilisateurs',
    'posts'    => 'Publications',
    'listings' => 'Annonces',
    'hashtags' => 'Hashtags',
    'places'   => 'Lieux',
];
$counts = [
    'users'    => count($users ?? []),
    'posts'    => count($posts ?? []),
    'listings' => count($listings ?? []),
    'hashtags' => count($hashtags ?? []),
    'places'   => count($places ?? []),
];
?>
<div class="sx" id="search-page"
     data-suggest="<?= e(url('explore/suggest')) ?>"
     data-explore="<?= e(url('explore')) ?>"
     data-base="<?= e(rtrim(url(''), '/') . '/') ?>"
     data-asset="<?= e(rtrim(asset(''), '/') . '/') ?>"
     data-scope="<?= e($scope) ?>"
     data-type="<?= e($type) ?>">

    <div class="sx-hero">
        <p class="sx-brand">TWEET</p>
        <h1 class="sx-title">Recherche</h1>
        <p class="sx-sub">Trouvez des personnes, des publications, des annonces et des lieux.</p>

        <form class="sx-bar" method="GET" action="<?= url('explore') ?>" id="search-form" autocomplete="off" role="search">
            <span class="sx-bar-ico" aria-hidden="true">
                <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
            </span>
            <input type="search" name="q" id="search-input" value="<?= e($q) ?>"
                   placeholder="Qui ou quoi cherchez-vous ?"
                   autofocus enterkeyhint="search" aria-label="Recherche"
                   aria-autocomplete="list" aria-controls="search-suggest">
            <input type="hidden" name="scope" id="search-scope" value="<?= e($scope) ?>">
            <?php if ($type !== ''): ?><input type="hidden" name="type" id="search-type" value="<?= e($type) ?>"><?php endif; ?>
            <button type="button" class="sx-clear" id="search-clear" aria-label="Effacer" <?= $hasQuery ? '' : 'hidden' ?>>
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M6 6l12 12M18 6 6 18"/></svg>
            </button>
            <button type="submit" class="sx-go">Rechercher</button>
        </form>
        <div class="sx-suggest" id="search-suggest" hidden role="listbox"></div>
        <p class="sx-status" id="search-live-status" hidden>Recherche en cours…</p>
    </div>

    <nav class="sx-tabs" aria-label="Catégories" id="search-scopes">
        <?php foreach ($scopes as $key => $label): ?>
            <?php
                $n = $key === 'all' ? (int) ($total ?? 0) : ($counts[$key] ?? 0);
                $href = url('explore?q=' . rawurlencode($q) . '&scope=' . $key . ($type !== '' ? '&type=' . urlencode($type) : ''));
            ?>
            <a href="<?= $href ?>" data-scope="<?= e($key) ?>" class="<?= $scope === $key ? 'is-active' : '' ?>">
                <?= e($label) ?>
                <?php if ($hasQuery && $n > 0): ?><em><?= $n ?></em><?php endif; ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <details class="sx-filters" <?= $type !== '' ? 'open' : '' ?>>
        <summary>Filtres avancés<?= $type !== '' ? ' · ' . e($type) : '' ?></summary>
        <form class="sx-filters-form" method="GET" action="<?= url('explore') ?>" id="search-filters-form">
            <input type="hidden" name="q" value="<?= e($q) ?>">
            <input type="hidden" name="scope" value="<?= e($scope) ?>">
            <label>
                Format
                <select name="type">
                    <option value="">Tous</option>
                    <option value="text" <?= $type === 'text' ? 'selected' : '' ?>>Texte</option>
                    <option value="image" <?= $type === 'image' ? 'selected' : '' ?>>Images</option>
                    <option value="video" <?= $type === 'video' ? 'selected' : '' ?>>Vidéos</option>
                </select>
            </label>
            <button type="submit" class="sx-filter-btn">Appliquer</button>
            <?php if ($type !== ''): ?>
                <a class="sx-reset" href="<?= url('explore?q=' . rawurlencode($q) . '&scope=' . urlencode($scope)) ?>">Réinitialiser</a>
            <?php endif; ?>
        </form>
    </details>

    <div id="search-live">
    <?php if (!$hasQuery): ?>
        <div class="sx-home">
            <section class="sx-section">
                <div class="sx-section-head">
                    <h2>Récentes</h2>
                    <button type="button" class="sx-text-btn" id="search-recent-clear" hidden>Effacer</button>
                </div>
                <div class="sx-recent" id="search-recent"></div>
                <p class="sx-hint" id="search-recent-empty">Aucune recherche récente pour le moment.</p>
            </section>

            <section class="sx-section">
                <div class="sx-section-head"><h2>Suggestions</h2></div>
                <?php if (empty($suggestions)): ?>
                    <p class="sx-hint">Les tendances locales s’afficheront ici.</p>
                <?php else: ?>
                    <div class="sx-suggest-list">
                        <?php foreach ($suggestions as $s): ?>
                            <a class="sx-suggest-chip" href="<?= url($s['href']) ?>"><?= e($s['label']) ?></a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <?php if (!empty($people)): ?>
            <section class="sx-section">
                <div class="sx-section-head"><h2>À découvrir</h2></div>
                <div class="sx-list">
                    <?php foreach ($people as $u): ?>
                        <a class="sx-row" href="<?= url('user/' . $u['id']) ?>">
                            <img class="sx-ava" src="<?= upload_url($u['photo'] ?? null) ?>" alt="">
                            <span class="sx-meta">
                                <strong><?= e(trim(($u['prenom'] ?? '') . ' ' . ($u['nom'] ?? ''))) ?></strong>
                                <small>@<?= e($u['username'] ?: ('user' . $u['id'])) ?><?= !empty($u['bio']) ? ' · ' . e(mb_strimwidth((string) $u['bio'], 0, 40, '…')) : '' ?></small>
                            </span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>

            <?php if (!empty($trends)): ?>
            <section class="sx-section">
                <div class="sx-section-head"><h2>Tendances</h2></div>
                <div class="sx-list">
                    <?php foreach ($trends as $i => $trend): ?>
                        <a class="sx-row" href="<?= url('explore?q=' . rawurlencode('#' . $trend['name']) . '&scope=hashtags') ?>">
                            <span class="sx-ico hash">#</span>
                            <span class="sx-meta">
                                <strong>#<?= e($trend['name']) ?></strong>
                                <small><?= (int) $trend['total'] ?> publication<?= (int) $trend['total'] > 1 ? 's' : '' ?></small>
                            </span>
                            <span class="sx-rank"><?= (int) $i + 1 ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>
        </div>
    <?php elseif ((int) ($total ?? 0) === 0): ?>
        <div class="sx-empty">
            <div class="sx-empty-mark" aria-hidden="true"></div>
            <h2>Aucun résultat</h2>
            <p>Rien pour « <?= e($q) ?> ». Essayez un @pseudo, un #hashtag ou un autre mot.</p>
            <a href="<?= url('explore') ?>">Effacer la recherche</a>
        </div>
    <?php else: ?>
        <div class="sx-results">
            <?php if (($scope === 'all' || $scope === 'users') && !empty($users)): ?>
            <section class="sx-section" style="--i:0">
                <div class="sx-section-head">
                    <h2>Utilisateurs</h2>
                    <?php if ($scope === 'all'): ?><a href="<?= url('explore?q=' . rawurlencode($q) . '&scope=users') ?>">Tout voir</a><?php endif; ?>
                </div>
                <div class="sx-list">
                    <?php foreach ($users as $u): ?>
                        <a class="sx-row" href="<?= url('user/' . $u['id']) ?>">
                            <img class="sx-ava" src="<?= upload_url($u['photo'] ?? null) ?>" alt="">
                            <span class="sx-meta">
                                <strong><?= e(trim(($u['prenom'] ?? '') . ' ' . ($u['nom'] ?? ''))) ?></strong>
                                <small>@<?= e($u['username'] ?: ('user' . $u['id'])) ?><?= !empty($u['bio']) ? ' · ' . e(mb_strimwidth((string) $u['bio'], 0, 48, '…')) : '' ?></small>
                            </span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>

            <?php if (($scope === 'all' || $scope === 'posts') && !empty($posts)): ?>
            <section class="sx-section" style="--i:1">
                <div class="sx-section-head">
                    <h2>Publications</h2>
                    <?php if ($scope === 'all'): ?><a href="<?= url('explore?q=' . rawurlencode($q) . '&scope=posts') ?>">Tout voir</a><?php endif; ?>
                </div>
                <div class="sx-list">
                    <?php foreach ($posts as $p): ?>
                        <?php
                            $excerpt = mb_strimwidth(trim(preg_replace('/\s+/', ' ', strip_tags((string) ($p['contenu'] ?? ''))) ?? ''), 0, 110, '…');
                            $uname = $p['username'] ?: ('user' . ($p['user_id'] ?? ''));
                        ?>
                        <a class="sx-row" href="<?= url('posts/' . $p['id']) ?>">
                            <img class="sx-ava" src="<?= upload_url($p['photo'] ?? null) ?>" alt="">
                            <span class="sx-meta">
                                <strong><?= e(trim(($p['prenom'] ?? '') . ' ' . ($p['nom'] ?? ''))) ?> <em>@<?= e($uname) ?></em></strong>
                                <small><?= e($excerpt) ?></small>
                            </span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>

            <?php if (($scope === 'all' || $scope === 'listings') && !empty($listings)): ?>
            <section class="sx-section" style="--i:2">
                <div class="sx-section-head">
                    <h2>Annonces</h2>
                    <?php if ($scope === 'all'): ?><a href="<?= url('explore?q=' . rawurlencode($q) . '&scope=listings') ?>">Tout voir</a><?php endif; ?>
                </div>
                <div class="sx-list">
                    <?php foreach ($listings as $l): ?>
                        <?php $cover = $l['images'][0]['file_path'] ?? null; ?>
                        <a class="sx-row" href="<?= url('annonces/' . $l['id']) ?>">
                            <img class="sx-thumb" src="<?= $cover ? upload_url($cover) : asset('images/default-avatar.svg') ?>" alt="">
                            <span class="sx-meta">
                                <strong><?= e($l['title'] ?? '') ?></strong>
                                <small><?= e($l['city'] ?? '') ?><?= isset($l['price']) && $l['price'] !== '' && $l['price'] !== null ? ' · ' . e(money($l['price'])) : '' ?></small>
                            </span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>

            <?php if (($scope === 'all' || $scope === 'hashtags') && !empty($hashtags)): ?>
            <section class="sx-section" style="--i:3">
                <div class="sx-section-head">
                    <h2>Hashtags</h2>
                    <?php if ($scope === 'all'): ?><a href="<?= url('explore?q=' . rawurlencode($q) . '&scope=hashtags') ?>">Tout voir</a><?php endif; ?>
                </div>
                <div class="sx-list">
                    <?php foreach ($hashtags as $h): ?>
                        <a class="sx-row" href="<?= url('hashtag/' . rawurlencode($h['name'])) ?>">
                            <span class="sx-ico hash">#</span>
                            <span class="sx-meta">
                                <strong>#<?= e($h['name']) ?></strong>
                                <small><?= (int) $h['total'] ?> publication<?= (int) $h['total'] > 1 ? 's' : '' ?></small>
                            </span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>

            <?php if (($scope === 'all' || $scope === 'places') && !empty($places)): ?>
            <section class="sx-section" style="--i:4">
                <div class="sx-section-head">
                    <h2>Lieux</h2>
                    <?php if ($scope === 'all'): ?><a href="<?= url('explore?q=' . rawurlencode($q) . '&scope=places') ?>">Tout voir</a><?php endif; ?>
                </div>
                <div class="sx-list">
                    <?php foreach ($places as $place): ?>
                        <a class="sx-row" href="<?= url('explore?q=' . rawurlencode($place['name']) . '&scope=posts') ?>">
                            <span class="sx-ico loc">
                                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 21s7-5.4 7-11a7 7 0 1 0-14 0c0 5.6 7 11 7 11z"/><circle cx="12" cy="10" r="2.5"/></svg>
                            </span>
                            <span class="sx-meta">
                                <strong><?= e($place['name']) ?></strong>
                                <small><?= (int) $place['total'] ?> publication<?= (int) $place['total'] > 1 ? 's' : '' ?></small>
                            </span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    </div>
</div>
