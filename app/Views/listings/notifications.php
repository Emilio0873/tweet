<div class="fb-profile-page">
    <div class="fb-profile-inner fb-profile-content">
        <section class="fb-card fb-card-wide">
            <h2>Notifications</h2>
            <?php if (empty($items)): ?>
                <p class="muted">Aucune notification pour le moment.</p>
            <?php else: ?>
                <ul class="notif-list">
                    <?php foreach ($items as $item): ?>
                        <li class="<?= empty($item['is_read']) ? 'unread' : '' ?>">
                            <a href="<?= !empty($item['link']) ? url($item['link']) : url('') ?>">
                                <strong><?= e($item['title']) ?></strong>
                                <span><?= e($item['contenu'] ?? '') ?></span>
                                <small><?= time_ago($item['created_at']) ?></small>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>
    </div>
</div>
