<h1 class="glass-title">Connexion</h1>

<form method="POST" action="<?= url('login') ?>" class="glass-form" novalidate>
    <?= $csrf ?>

    <label class="glass-field">
        <span class="glass-ico" aria-hidden="true">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="8" r="3.2"/><path d="M5 19.2a7 7 0 0 1 14 0"/></svg>
        </span>
        <input type="text" name="identifier" id="identifier" required autocomplete="username" placeholder="Email">
    </label>

    <label class="glass-field">
        <span class="glass-ico" aria-hidden="true">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="5" y="10" width="14" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
        </span>
        <input type="password" name="password" id="password" required autocomplete="current-password" placeholder="Mot de passe">
        <button type="button" class="glass-eye" data-toggle-password="#password" aria-label="Afficher le mot de passe">
            <svg class="eye-off" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 3l18 18"/><path d="M10.6 10.6a2 2 0 0 0 2.8 2.8"/><path d="M9.5 5.1A10.4 10.4 0 0 1 12 4.8c5 0 8.8 3.7 10 7.2a11.3 11.3 0 0 1-4.1 5.1"/><path d="M6.1 6.1A11.2 11.2 0 0 0 2 12c1.2 3.5 5 7.2 10 7.2 1.1 0 2.1-.2 3.1-.5"/></svg>
            <svg class="eye-on" hidden viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></svg>
        </button>
    </label>

    <div class="glass-row">
        <label class="glass-check">
            <input type="checkbox" name="remember" value="1">
            <span>Se souvenir de moi</span>
        </label>
        <a class="glass-link" href="<?= url('forgot-password') ?>">Mot de passe oublié ?</a>
    </div>

    <button type="submit" class="glass-btn">Connexion</button>
</form>

<p class="glass-foot">
    Pas encore de compte ?
    <a href="<?= url('register') ?>">S’inscrire</a>
</p>
