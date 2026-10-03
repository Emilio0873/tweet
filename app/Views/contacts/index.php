<div class="page-header">
    <h1>Contacts</h1>
    <a href="<?= url('search') ?>" class="btn btn-primary">Rechercher</a>
</div>

<section class="panel">
    <?php if (empty($contacts)): ?>
    <div class="empty-state compact">
        <p>Aucun contact pour le moment.</p>
        <a href="<?= url('search') ?>" class="btn btn-secondary">Trouver des utilisateurs</a>
    </div>
    <?php else: ?>
    <ul class="list">
        <?php foreach ($contacts as $c): ?>
        <li>
            <a href="<?= url('user/' . $c['id']) ?>" class="list-item">
                <div class="avatar-wrap">
                    <img src="<?= upload_url($c['photo']) ?>" alt="" class="avatar">
                    <?php if ($c['is_online']): ?><span class="online-dot"></span><?php endif; ?>
                </div>
                <div class="list-meta">
                    <strong><?= e($c['prenom'] . ' ' . $c['nom']) ?></strong>
                    <span><?= e($c['bio'] ?: ($c['is_online'] ? 'En ligne' : 'Hors ligne')) ?></span>
                </div>
            </a>
        </li>
        <?php endforeach; ?>
    </ul>
    <?php endif; ?>
</section>

<?php if (!empty($blocked)): ?>
<section class="panel mt">
    <h3 class="section-title">Utilisateurs bloqués</h3>
    <ul class="list">
        <?php foreach ($blocked as $b): ?>
        <li class="list-item static">
            <img src="<?= upload_url($b['photo']) ?>" alt="" class="avatar">
            <div class="list-meta">
                <strong><?= e($b['prenom'] . ' ' . $b['nom']) ?></strong>
                <span>Bloqué le <?= date('d/m/Y', strtotime($b['created_at'])) ?></span>
            </div>
            <form method="POST" action="<?= url('contacts/unblock') ?>">
                <?= $csrf ?>
                <input type="hidden" name="user_id" value="<?= (int)$b['id'] ?>">
                <button type="submit" class="btn btn-ghost btn-sm">Débloquer</button>
            </form>
        </li>
        <?php endforeach; ?>
    </ul>
</section>
<?php endif; ?>
