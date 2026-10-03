<h1 class="glass-title">Mot de passe oublié</h1>
<p class="glass-lead">Entrez votre email ou téléphone.</p>

<form method="POST" action="<?= url('forgot-password') ?>" class="glass-form" novalidate>
    <?= $csrf ?>
    <label class="glass-field">
        <span class="glass-ico" aria-hidden="true">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="8" r="3.2"/><path d="M5 19.2a7 7 0 0 1 14 0"/></svg>
        </span>
        <input type="text" id="identifier" name="identifier" required placeholder="Email ou téléphone">
    </label>
    <button type="submit" class="glass-btn">Envoyer le lien</button>
</form>

<p class="glass-foot">
    <a href="<?= url('login') ?>">Retour à la connexion</a>
</p>
