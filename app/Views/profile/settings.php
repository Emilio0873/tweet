<div class="settings-wrap">
    <h1 class="settings-title">Paramètres</h1>

    <section class="set-card">
        <div class="set-card-head">
            <div>
                <h2>Contact</h2>
                <p class="muted"><?= e(($user['prenom'] ?? '') . ' ' . ($user['nom'] ?? '')) ?> · <?= e($user['email'] ?? '') ?></p>
                <p class="muted small"><?= e($user['telephone'] ?: 'Aucun numéro') ?></p>
            </div>
            <a class="set-edit" href="#compte" title="Modifier">✎</a>
        </div>
    </section>

    <section class="set-card">
        <h2>Apparence</h2>
        <p class="set-label">Thème de l’application</p>
        <p class="muted small">Automatique suit le thème du téléphone. Vous pouvez aussi forcer clair ou sombre.</p>
        <div class="theme-pills" id="theme-pills">
            <button type="button" class="theme-pill" data-theme="auto">Auto</button>
            <button type="button" class="theme-pill" data-theme="light">Clair</button>
            <button type="button" class="theme-pill" data-theme="dark">Sombre</button>
        </div>
    </section>

    <section class="set-card">
        <h2>Général</h2>
        <label class="set-toggle">
            <span>
                <strong>Activer les notifications</strong>
                <small>Notifications in-app en temps réel</small>
            </span>
            <input type="checkbox" id="pref-notif" checked>
            <em></em>
        </label>
    </section>

    <section class="set-card" id="compte">
        <h2>Compte</h2>
        <form method="POST" action="<?= url('profile') ?>" enctype="multipart/form-data" class="fb-form">
            <?= $csrf ?>
            <div class="fb-form-row">
                <label>Prénom<input type="text" name="prenom" value="<?= e($user['prenom']) ?>" required></label>
                <label>Nom<input type="text" name="nom" value="<?= e($user['nom']) ?>" required></label>
            </div>
            <div class="fb-form-row">
                <label>Pseudo<input type="text" name="username" value="<?= e($user['username'] ?? '') ?>" minlength="3" maxlength="60"></label>
                <label>Téléphone<input type="text" name="telephone" value="<?= e($user['telephone'] ?? '') ?>" required maxlength="30"></label>
            </div>
            <label>Email<input type="email" name="email" value="<?= e($user['email']) ?>" required></label>
            <label>À propos<textarea name="bio" rows="3" maxlength="500"><?= e($user['bio'] ?? '') ?></textarea></label>
            <div class="fb-form-row">
                <label class="file-line">Photo de profil<input type="file" name="photo" accept="image/jpeg,image/png,image/webp"></label>
                <label class="file-line">Photo de couverture<input type="file" name="cover_photo" accept="image/jpeg,image/png,image/webp"></label>
            </div>
            <h3>Confidentialité</h3>
            <?php
            $privacyOptions = [
                'everyone' => 'Tout le monde',
                'contacts' => 'Mes contacts',
                'nobody'   => 'Personne',
            ];
            $privacyFields = [
                'privacy_photo'     => 'Qui voit ma photo',
                'privacy_last_seen' => 'Qui voit ma dernière connexion',
                'privacy_status'    => 'Qui voit mes statuts',
                'privacy_contact'   => 'Qui peut me contacter',
                'privacy_group'     => 'Qui peut m\'ajouter à un groupe',
            ];
            foreach ($privacyFields as $key => $label):
            ?>
            <label>
                <?= e($label) ?>
                <select name="<?= $key ?>">
                    <?php foreach ($privacyOptions as $val => $optLabel): ?>
                        <option value="<?= $val ?>" <?= ($user[$key] ?? '') === $val ? 'selected' : '' ?>><?= e($optLabel) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <?php endforeach; ?>
            <button type="submit" class="btn btn-blue">Enregistrer</button>
        </form>
    </section>

    <section class="set-card">
        <h2>Sécurité</h2>
        <form method="POST" action="<?= url('profile/password') ?>" class="fb-form">
            <?= $csrf ?>
            <label>Mot de passe actuel<input type="password" name="current_password" required></label>
            <label>Nouveau mot de passe<input type="password" name="new_password" required minlength="8"></label>
            <label>Confirmation<input type="password" name="new_password_confirm" required minlength="8"></label>
            <button type="submit" class="btn btn-soft">Modifier le mot de passe</button>
        </form>
        <a class="quiet-link" href="<?= url('sessions') ?>">Gérer les sessions connectées</a>
    </section>
</div>
