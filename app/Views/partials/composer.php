<form class="composer" method="POST" action="<?= url('posts') ?>" enctype="multipart/form-data">
    <?= $csrf ?>
    <div class="composer-main">
        <img class="composer-ava" src="<?= upload_url($me['photo'] ?? null) ?>" alt="">
        <div class="composer-body">
            <textarea name="contenu" rows="2" maxlength="2000" placeholder="Quoi de neuf près de chez vous ?"></textarea>
            <div class="preview-row" data-preview-row></div>
            <details class="composer-meta">
                <summary>Options</summary>
                <div class="composer-extra">
                    <input type="text" name="location" placeholder="Localisation" maxlength="160">
                    <input type="url" name="link_url" placeholder="Lien https://…">
                    <select name="visibility" aria-label="Visibilité">
                        <option value="public">Public</option>
                        <option value="friends">Amis</option>
                        <option value="followers">Abonnés</option>
                        <option value="private">Privé</option>
                    </select>
                    <label class="composer-draft"><input type="checkbox" name="is_draft" value="1"> Brouillon</label>
                </div>
            </details>
            <div class="composer-bar">
                <div class="composer-tools">
                    <label class="composer-tool" title="Photo ou vidéo">
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="5" width="18" height="14" rx="3"/><circle cx="9" cy="11" r="2"/><path d="m21 15-4.5-4.5L9 18"/></svg>
                        <input type="file" name="media[]" accept="image/jpeg,image/png,image/webp,image/gif,video/mp4,video/webm" multiple data-preview>
                    </label>
                </div>
                <button type="submit" class="composer-submit">Publier</button>
            </div>
        </div>
    </div>
</form>
