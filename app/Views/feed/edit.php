<div class="feed-col alone">
    <a href="<?= url('posts/' . $post['id']) ?>" class="btn btn-ghost back-link">← Publication</a>
    <section class="panel">
        <h1>Modifier la publication</h1>
        <form class="composer" method="POST" action="<?= url('posts/' . $post['id'] . '/update') ?>">
            <?= $csrf ?>
            <textarea name="contenu" rows="5" maxlength="2000"><?= e($post['contenu'] ?? '') ?></textarea>
            <div class="composer-extra">
                <input type="text" name="location" value="<?= e($post['location'] ?? '') ?>" placeholder="Localisation" maxlength="160">
                <input type="url" name="link_url" value="<?= e($post['link_url'] ?? '') ?>" placeholder="https://lien">
                <select name="visibility">
                    <?php foreach (['public' => 'Public', 'friends' => 'Amis', 'followers' => 'Abonnés', 'private' => 'Privé'] as $val => $label): ?>
                        <option value="<?= $val ?>" <?= $post['visibility'] === $val ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <label class="check"><input type="checkbox" name="is_draft" value="1" <?= !empty($post['is_draft']) ? 'checked' : '' ?>> Brouillon</label>
            <button type="submit" class="btn btn-primary">Enregistrer</button>
        </form>
    </section>
</div>
