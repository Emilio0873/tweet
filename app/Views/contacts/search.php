<div class="page-header">
    <h1>Rechercher</h1>
</div>

<section class="panel">
    <form method="GET" action="<?= url('search') ?>" class="search-form">
        <input type="search" name="q" value="<?= e($query ?? '') ?>" placeholder="Nom, email ou téléphone…" minlength="2" autofocus>
        <button type="submit" class="btn btn-primary">Chercher</button>
    </form>

    <?php if (($query ?? '') !== '' && mb_strlen($query) < 2): ?>
    <p class="muted mt">Saisissez au moins 2 caractères.</p>
    <?php elseif (($query ?? '') !== '' && empty($results)): ?>
    <div class="empty-state compact mt">
        <p>Aucun résultat pour « <?= e($query) ?> ».</p>
    </div>
    <?php elseif (!empty($results)): ?>
    <ul class="list mt">
        <?php foreach ($results as $r): ?>
        <li class="list-item static">
            <a href="<?= url('user/' . $r['id']) ?>" class="list-item-link">
                <img src="<?= upload_url($r['photo']) ?>" alt="" class="avatar">
                <div class="list-meta">
                    <strong><?= e($r['prenom'] . ' ' . $r['nom']) ?></strong>
                    <span><?= e($r['telephone']) ?></span>
                </div>
            </a>
            <form method="POST" action="<?= url('contacts/add') ?>">
                <?= $csrf ?>
                <input type="hidden" name="contact_id" value="<?= (int)$r['id'] ?>">
                <button type="submit" class="btn btn-secondary btn-sm">Ajouter</button>
            </form>
        </li>
        <?php endforeach; ?>
    </ul>
    <?php endif; ?>
</section>
