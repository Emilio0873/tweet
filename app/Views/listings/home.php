<div class="fb">
    <?php if (!empty($me)): ?>
    <section class="composer-box">
        <form method="POST" action="<?= url('annonces') ?>" enctype="multipart/form-data" class="composer-form">
            <?= $csrf ?>
            <div class="composer-row">
                <img class="composer-avatar" src="<?= upload_url($me['photo'] ?? null) ?>" alt="">
                <input type="text" name="title" maxlength="160" required placeholder="Que voulez-vous publier, <?= e($me['prenom']) ?> ?">
            </div>
            <textarea name="description" rows="2" maxlength="4000" placeholder="Décrivez votre annonce…"></textarea>
            <div class="composer-tools">
                <select name="category" required aria-label="Catégorie">
                    <?php foreach ($categories as $slug => $label): ?>
                        <option value="<?= e($slug) ?>"><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="text" name="city" required maxlength="80" placeholder="Ville">
                <input type="number" name="price" min="0" step="1" placeholder="Prix (DH)">
                <label class="file-chip">
                    Photo
                    <input type="file" name="images[]" accept="image/jpeg,image/png,image/webp" multiple>
                </label>
                <button type="submit" class="btn btn-blue">Publier</button>
            </div>
        </form>
    </section>
    <?php else: ?>
    <section class="composer-box guest">
        <p>Connectez-vous pour publier.</p>
        <a class="btn btn-blue" href="<?= url('login') ?>">Connexion</a>
    </section>
    <?php endif; ?>

    <section class="fb-feed" id="feed">
        <?php if (empty($feed)): ?>
            <div class="empty">
                <h2>Fil d’actualité</h2>
                <p class="muted">Les publications apparaissent ici, juste sous la boîte de publication.</p>
            </div>
        <?php else: ?>
            <?php foreach ($feed as $listing): ?>
                <?php require dirname(__DIR__) . '/partials/feed_post.php'; ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </section>
</div>
