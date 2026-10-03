<article class="ad-card">
    <a class="ad-link" href="<?= url('annonces/' . $listing['id']) ?>">
        <div class="ad-media">
            <?php $cover = $listing['images'][0]['file_path'] ?? null; ?>
            <?php if ($cover): ?>
                <img src="<?= upload_url($cover) ?>" alt="">
            <?php else: ?>
                <div class="ad-fallback cat-<?= e($listing['category']) ?>"></div>
            <?php endif; ?>
            <span class="ad-price"><?= money($listing['price']) ?></span>
        </div>
        <div class="ad-body">
            <h3><?= e($listing['title']) ?></h3>
            <p><?= e($listing['city']) ?> · <?= time_ago($listing['created_at']) ?></p>
        </div>
    </a>
    <form method="POST" action="<?= url('annonces/' . $listing['id'] . '/save') ?>" class="save-form">
        <?= $csrf ?>
        <button type="submit" class="heart <?= !empty($listing['saved']) ? 'on' : '' ?>" aria-label="Enregistrer">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="<?= !empty($listing['saved']) ? 'currentColor' : 'none' ?>" stroke="currentColor" stroke-width="2"><path d="M12 20s-7-4.4-7-9a4 4 0 0 1 7-2 4 4 0 0 1 7 2c0 4.6-7 9-7 9z"/></svg>
        </button>
    </form>
</article>
