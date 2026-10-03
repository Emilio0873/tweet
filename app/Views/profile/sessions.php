<div class="fb-profile-page">
    <div class="fb-profile-inner fb-profile-content">
        <section class="fb-card fb-card-wide">
            <div class="sessions-head">
                <h2>Sessions actives</h2>
                <a class="quiet-link" href="<?= url('settings') ?>">← Retour aux paramètres</a>
            </div>
            <p class="muted">Appareils connectés à votre compte.</p>
            <ul class="session-list">
                <?php foreach ($sessions as $s): ?>
                    <li>
                        <div>
                            <strong><?= e($s['device'] ?: 'Appareil inconnu') ?></strong>
                            <span>IP <?= e($s['ip_address'] ?? '—') ?> · Activité <?= time_ago($s['last_activity']) ?></span>
                        </div>
                        <?php if (($s['session_token'] ?? '') === $currentToken): ?>
                            <em class="badge-now">Session actuelle</em>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
            <?php if (count($sessions) > 1): ?>
                <form method="POST" action="<?= url('sessions/revoke') ?>" onsubmit="return confirm('Fermer toutes les autres sessions ?');">
                    <?= $csrf ?>
                    <button type="submit" class="btn btn-soft danger">Fermer les autres sessions</button>
                </form>
            <?php endif; ?>
        </section>
    </div>
</div>
