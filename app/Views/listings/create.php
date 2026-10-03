<section class="publish">
    <p class="eyebrow">Nouvelle annonce</p>
    <h1>Publier en quelques instants.</h1>
    <form class="publish-form" method="POST" action="<?= url('annonces') ?>" enctype="multipart/form-data">
        <?= $csrf ?>
        <label>Titre
            <input type="text" name="title" required maxlength="160" placeholder="Ex. Appartement lumineux avec terrasse">
        </label>
        <div class="split">
            <label>Catégorie
                <select name="category" required>
                    <?php foreach ($categories as $slug => $label): ?>
                        <option value="<?= e($slug) ?>"><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Ville
                <input type="text" name="city" required maxlength="80" placeholder="Casablanca">
            </label>
        </div>
        <label>Prix (DH)
            <input type="number" name="price" min="0" step="1" placeholder="Laisser vide si sur demande">
        </label>
        <label>Description
            <textarea name="description" rows="6" maxlength="4000" placeholder="Décrivez l’essentiel : état, dimensions, disponibilité."></textarea>
        </label>
        <label>Photos
            <input type="file" name="images[]" accept="image/jpeg,image/png,image/webp" multiple>
        </label>
        <button type="submit" class="btn btn-blue btn-lg">Mettre en ligne</button>
    </form>
</section>
