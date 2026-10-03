<?php

$config = require dirname(__DIR__) . '/config/database.php';
$pdo = new PDO(
    sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=%s',
        $config['host'],
        $config['port'] ?? '3306',
        $config['dbname'],
        $config['charset'] ?? 'utf8mb4'
    ),
    $config['username'],
    $config['password'] ?? '',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$count = (int) $pdo->query('SELECT COUNT(*) FROM posts')->fetchColumn();
if ($count > 0) {
    echo "posts already present: {$count}\n";
    exit(0);
}

$listings = $pdo->query(
    "SELECT l.user_id, l.title, l.description, l.city, l.created_at
     FROM listings l
     WHERE l.status = 'active'
     ORDER BY l.created_at DESC
     LIMIT 20"
)->fetchAll(PDO::FETCH_ASSOC);

$ins = $pdo->prepare(
    'INSERT INTO posts (user_id, contenu, location, visibility, is_draft, created_at)
     VALUES (?, ?, ?, \'public\', 0, ?)'
);

$n = 0;
foreach ($listings as $row) {
    $text = trim(($row['title'] ?? '') . "\n\n" . ($row['description'] ?? ''));
    if ($text === '') {
        continue;
    }
    $ins->execute([
        (int) $row['user_id'],
        mb_substr($text, 0, 2000),
        $row['city'] ?: null,
        $row['created_at'] ?: date('Y-m-d H:i:s'),
    ]);
    $n++;
}

if ($n === 0) {
    $ins->execute([2, "Bienvenue sur le fil TWEET ! Publiez vos actualités ici. #bienvenue", 'Kinshasa', date('Y-m-d H:i:s')]);
    $ins->execute([3, "Première publication locale — on teste le fil d’actualité.", 'Kinshasa', date('Y-m-d H:i:s')]);
    $n = 2;
}

echo "seeded posts: {$n}\n";
