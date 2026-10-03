<h1 class="glass-title">Inscription</h1>
<p class="glass-lead">Rejoignez TWEET en quelques secondes.</p>

<form method="POST" action="<?= url('register') ?>" class="glass-form" enctype="multipart/form-data" novalidate>
    <?= $csrf ?>
    <div class="glass-grid">
        <label class="glass-field">
            <input type="text" id="prenom" name="prenom" required maxlength="100" placeholder="Prénom">
        </label>
        <label class="glass-field">
            <input type="text" id="nom" name="nom" required maxlength="100" placeholder="Nom">
        </label>
    </div>
    <label class="glass-field">
        <span class="glass-ico" aria-hidden="true">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="8" r="3.2"/><path d="M5 19.2a7 7 0 0 1 14 0"/></svg>
        </span>
        <input type="email" id="email" name="email" required autocomplete="email" placeholder="Email">
    </label>
    <label class="glass-field">
        <input type="tel" id="telephone" name="telephone" required placeholder="Téléphone">
    </label>
    <div class="glass-grid">
        <label class="glass-field">
            <span class="glass-ico" aria-hidden="true">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="5" y="10" width="14" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
            </span>
            <input type="password" id="password" name="password" required minlength="8" autocomplete="new-password" placeholder="Mot de passe">
        </label>
        <label class="glass-field">
            <input type="password" id="password_confirm" name="password_confirm" required minlength="8" autocomplete="new-password" placeholder="Confirmer">
        </label>
    </div>
    <label class="glass-field glass-field-area">
        <textarea id="bio" name="bio" rows="2" maxlength="500" placeholder="À propos (optionnel)"></textarea>
    </label>
    <label class="glass-file">
        <span>Photo de profil (optionnel)</span>
        <input type="file" id="photo" name="photo" accept="image/jpeg,image/png,image/webp">
    </label>
    <button type="submit" class="glass-btn">S’inscrire</button>
</form>

<p class="glass-foot">
    Déjà un compte ?
    <a href="<?= url('login') ?>">Se connecter</a>
</p>
