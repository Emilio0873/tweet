<section class="results-head">
    <div>
        <p class="eyebrow">Recherche</p>
        <h1>
            <?php if (($filters['q'] ?? '') !== ''): ?>
                <?= e($filters['q']) ?>
            <?php elseif (!empty($filters['category']) && isset($categories[$filters['category']])): ?>
                <?= e($categories[$filters['category']]) ?>
            <?php else: ?>
                Toutes les annonces
            <?php endif; ?>
        </h1>
    </div>
    <form class="filters" method="GET" action="<?= url('annonces') ?>">
        <input type="search" name="q" value="<?= e($filters['q']) ?>" placeholder="Mot-clé">
        <select name="category">
            <option value="">Catégorie</option>
            <?php foreach ($categories as $slug => $label): ?>
                <option value="<?= e($slug) ?>" <?= $filters['category'] === $slug ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
        <input type="text" name="city" value="<?= e($filters['city']) ?>" placeholder="Ville">
        <select name="sort">
            <option value="recent" <?= $filters['sort'] === 'recent' ? 'selected' : '' ?>>Plus récentes</option>
            <option value="popular" <?= $filters['sort'] === 'popular' ? 'selected' : '' ?>>Populaires</option>
            <option value="price_asc" <?= $filters['sort'] === 'price_asc' ? 'selected' : '' ?>>Prix croissant</option>
            <option value="price_desc" <?= $filters['sort'] === 'price_desc' ? 'selected' : '' ?>>Prix décroissant</option>
        </select>
        <button type="submit" class="btn btn-blue">Filtrer</button>
    </form>
</section>

<?php if (empty($listings)): ?>
    <div class="empty">
        <h2>Aucun résultat</h2>
        <p>Essayez un autre mot-clé, une autre ville ou une autre catégorie.</p>
    </div>
<?php else: ?>
    <div class="card-grid">
        <?php foreach ($listings as $listing): ?>
            <?php require dirname(__DIR__) . '/partials/listing_card.php'; ?>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
