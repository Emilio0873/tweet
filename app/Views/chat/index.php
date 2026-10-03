<?php
$unreadTotal = 0;
foreach ($inbox ?? [] as $row) {
    $unreadTotal += (int) ($row['unread'] ?? 0);
}
$qChat = trim((string) ($_GET['q'] ?? ''));
?>
<div class="chat-layout <?= !empty($active) ? 'has-thread' : '' ?>">
    <section class="conversation-list">
        <header class="chat-list-head">
            <div>
                <h1>Messages</h1>
                <p class="chat-sub">
                    <?php if ($unreadTotal > 0): ?>
                        <?= $unreadTotal ?> message<?= $unreadTotal > 1 ? 's' : '' ?> non lu<?= $unreadTotal > 1 ? 's' : '' ?>
                    <?php else: ?>
                        Aucun message non lu
                    <?php endif; ?>
                </p>
            </div>
            <a class="compose-btn" href="<?= url('search') ?>" title="Nouvelle discussion">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
            </a>
        </header>

        <form class="chat-search" method="GET" action="<?= url('chat') ?>">
            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
            <input type="search" name="q" value="<?= e($qChat) ?>" placeholder="Rechercher une discussion…">
        </form>

        <?php
        $filtered = $inbox ?? [];
        if ($qChat !== '') {
            $filtered = array_values(array_filter($filtered, function ($item) use ($qChat) {
                $hay = mb_strtolower(($item['prenom'] ?? '') . ' ' . ($item['nom'] ?? '') . ' ' . ($item['last_message'] ?? ''));
                return str_contains($hay, mb_strtolower($qChat));
            }));
        }
        ?>

        <?php if (empty($filtered)): ?>
            <div class="empty-state compact">
                <p>Aucune discussion pour le moment.</p>
                <a href="<?= url('search') ?>" class="btn btn-blue">Trouver quelqu’un</a>
            </div>
        <?php else: ?>
            <ul class="list">
                <?php foreach ($filtered as $item): ?>
                    <li>
                        <a href="<?= url('chat/' . $item['id']) ?>" class="list-item <?= !empty($active) && (int) $active['conversation_id'] === (int) $item['id'] ? 'is-active' : '' ?>">
                            <div class="avatar-wrap">
                                <img src="<?= upload_url($item['photo']) ?>" alt="" class="avatar">
                                <?php if (!empty($item['is_online'])): ?><span class="online-dot"></span><?php endif; ?>
                                <?php if ((int) $item['unread'] > 0): ?>
                                    <em class="avatar-badge"><?= (int) $item['unread'] ?></em>
                                <?php endif; ?>
                            </div>
                            <div class="list-meta">
                                <div class="list-top">
                                    <strong><?= e($item['prenom'] . ' ' . $item['nom']) ?></strong>
                                    <time><?= !empty($item['last_at']) ? date('d/m/y', strtotime($item['last_at'])) : '' ?></time>
                                </div>
                                <span><?= e($item['last_message'] ?: 'Nouvelle discussion') ?></span>
                            </div>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>

    <section class="thread">
        <?php if (empty($active)): ?>
            <div class="welcome-inner">
                <div class="welcome-orb" aria-hidden="true">
                    <svg viewBox="0 0 64 64" width="54" height="54" fill="none"><path d="M18 42c0-10 8-18 18-18h2c8 0 14 5 14 12s-6 12-14 12h-3l-8 6 2-7c-5-1-9-3-11-5z" fill="#0F2847"/><path d="M14 28c0-8 7-14 15-14h1c6 0 11 3 13 8" stroke="#4ba3f0" stroke-width="3" stroke-linecap="round"/></svg>
                </div>
                <h2>Vos conversations</h2>
                <p>Sélectionnez une discussion dans la liste pour commencer à écrire</p>
            </div>
        <?php else: ?>
            <header class="thread-head">
                <a href="<?= url('chat') ?>" class="btn btn-ghost thread-back">←</a>
                <a href="<?= url('user/' . $active['id']) ?>" class="post-author">
                    <img src="<?= upload_url($active['photo']) ?>" alt="" class="avatar avatar-sm">
                    <span>
                        <strong><?= e($active['prenom'] . ' ' . $active['nom']) ?></strong>
                        <small>Voir le profil</small>
                    </span>
                </a>
            </header>
            <?php $lastMessage = $messages ? $messages[array_key_last($messages)] : null; ?>
            <div class="thread-messages" id="thread-messages"
                 data-poll="<?= url('chat/' . $active['conversation_id'] . '/poll') ?>"
                 data-last="<?= (int) ($lastMessage['id'] ?? 0) ?>">
                <?php foreach ($messages as $message): ?>
                    <div class="bubble <?= (int) $message['sender_id'] === (int) $meId ? 'mine' : 'theirs' ?>" data-id="<?= (int) $message['id'] ?>">
                        <p><?= e(($message['is_deleted_for_all'] ?? 0) ? 'Message supprimé' : $message['contenu']) ?></p>
                        <time><?= date('H:i', strtotime($message['created_at'])) ?></time>
                    </div>
                <?php endforeach; ?>
            </div>
            <form class="thread-form" method="POST" action="<?= url('chat/' . $active['conversation_id'] . '/send') ?>" id="thread-form">
                <?= $csrf ?>
                <input type="text" name="contenu" maxlength="2000" placeholder="Écrire un message…" autocomplete="off" required>
                <button type="submit" class="btn btn-blue send-ico" aria-label="Envoyer">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 2 11 13"/><path d="M22 2 15 22l-4-9-9-4z"/></svg>
                </button>
            </form>
        <?php endif; ?>
    </section>
</div>
