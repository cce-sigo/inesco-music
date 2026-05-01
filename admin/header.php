<?php /** @var string $pageTitle */ ?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title><?= e(($pageTitle ?? 'Admin') . ' · INESCO') ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex,nofollow">
    <link rel="stylesheet" href="<?= e(url('admin/style.css')) ?>">
</head>
<body class="admin-body">
<header class="admin-header">
    <div class="container">
        <a class="brand" href="<?= e(url('admin/')) ?>">INESCO Admin</a>
        <nav>
            <a href="<?= e(url('admin/index.php')) ?>">Dashboard</a>
            <a href="<?= e(url('admin/content.php')) ?>">Texte</a>
            <a href="<?= e(url('admin/navigation.php')) ?>">Navigation</a>
            <a href="<?= e(url('admin/members.php')) ?>">Mitglieder</a>
            <a href="<?= e(url('admin/tracks.php')) ?>">Audio</a>
            <a href="<?= e(url('admin/videos.php')) ?>">Videos</a>
            <a href="<?= e(url('admin/impressions.php')) ?>">Impressionen</a>
            <a href="<?= e(url('admin/concerts.php')) ?>">Konzerte</a>
            <a href="<?= e(url('admin/contact.php')) ?>">Kontakt</a>
            <a href="<?= e(url('admin/visitenkarte.php')) ?>">Visitenkarte</a>
            <a class="logout" href="<?= e(url('admin/logout.php')) ?>">Logout (<?= e($_SESSION['admin_user'] ?? '') ?>)</a>
        </nav>
    </div>
</header>
<main class="admin-main container">
<?php foreach (flash_pop() as $f): ?>
    <p class="msg <?= e($f['type']) ?>"><?= e($f['msg']) ?></p>
<?php endforeach; ?>
