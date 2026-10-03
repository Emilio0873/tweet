<div class="feed-col alone">
    <header class="feed-top"><h1>Notifications</h1></header>
    <section class="panel">
        <?php if (empty($items)): ?>
            <p class="muted">Aucune notification.</p>
        <?php else: ?>
            <ul class="list">
                <?php foreach ($items as $item): ?>
                    <li>
                        <a href="<?= $item['link'] ? url($item['link']) : url('feed') ?>" class="list-item">
                            <div class="list-meta">
                                <strong><?= e($item['title']) ?></strong>
                                <span><?= e($item['contenu']) ?> · <?= time_ago($item['created_at']) ?></span>
                            </div>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
</div>
