<section class="publish">
    <p class="eyebrow">Modifier</p>
    <h1>Mettre à jour la publication</h1>
    <form class="publish-form" method="POST" action="<?= url('annonces/' . $listing['id'] . '/update') ?>" enctype="multipart/form-data">
        <?= $csrf ?>
        <label>Titre
            <input type="text" name="title" required maxlength="160" value="<?= e($listing['title']) ?>">
        </label>
        <div class="split">
            <label>Catégorie
                <select name="category" required>
                    <?php foreach ($categories as $slug => $label): ?>
                        <option value="<?= e($slug) ?>" <?= $listing['category'] === $slug ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Ville
                <input type="text" name="city" required maxlength="80" value="<?= e($listing['city']) ?>">
            </label>
        </div>
        <label>Prix (DH)
            <input type="number" name="price" min="0" step="1" value="<?= e((string) ($listing['price'] ?? '')) ?>" placeholder="Laisser vide si sur demande">
        </label>
        <label>Description
            <textarea name="description" rows="6" maxlength="4000"><?= e($listing['description'] ?? '') ?></textarea>
        </label>
        <?php if (!empty($listing['images'])): ?>
            <div class="edit-thumbs">
                <?php foreach ($listing['images'] as $image): ?>
                    <img src="<?= upload_url($image['file_path']) ?>" alt="">
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <label>Ajouter des photos
            <input type="file" name="images[]" accept="image/jpeg,image/png,image/webp" multiple>
        </label>
        <div class="edit-actions">
            <button type="submit" class="btn btn-blue btn-lg">Enregistrer</button>
            <a class="btn btn-soft" href="<?= url('annonces/' . $listing['id']) ?>">Annuler</a>
        </div>
    </form>
</section>
