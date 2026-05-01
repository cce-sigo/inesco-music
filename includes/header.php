<?php
/** @var array $content */
/** @var array $contact */
$siteTitle = $content['site']['title'] ?? 'INESCO';
$siteDesc  = $content['site']['description'] ?? '';
$siteKw    = $content['site']['keywords'] ?? '';

// Navigationsziele relativ zur Startseite (funktioniert auch auf Unterseiten)
$scriptName = basename($_SERVER['SCRIPT_NAME'] ?? '');
$isHome     = ($scriptName === '' || $scriptName === 'index.php');
$navBase    = $isHome ? '' : url('');
?><!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($siteTitle) ?></title>
    <meta name="description" content="<?= e($siteDesc) ?>">
    <meta name="keywords" content="<?= e($siteKw) ?>">
    <meta name="theme-color" content="#1a0b0d">

    <meta property="og:title" content="<?= e($siteTitle) ?>">
    <meta property="og:description" content="<?= e($siteDesc) ?>">
    <meta property="og:type" content="website">
    <meta property="og:image" content="<?= e(url('assets/img/logo.png')) ?>">

    <link rel="icon" type="image/png" href="<?= e(url('assets/img/logo.png')) ?>">
    <?php if (!empty($heroPreload)): ?>
        <link rel="preload" as="image" href="<?= e($heroPreload) ?>" fetchpriority="high">
    <?php endif; ?>
    <?php if (!empty($mediaPrefetch) && is_array($mediaPrefetch)): ?>
        <?php foreach ($mediaPrefetch as $mp): ?>
            <link rel="prefetch" as="<?= e($mp['as']) ?>" href="<?= e($mp['href']) ?>">
        <?php endforeach; ?>
    <?php endif; ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;700&family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <?php $cssPath = BASE_PATH . '/assets/css/style.css'; $cssVer = is_file($cssPath) ? filemtime($cssPath) : time(); ?>
    <link rel="stylesheet" href="<?= e(url('assets/css/style.css')) ?>?v=<?= $cssVer ?>">
</head>
<body>
<header class="site-header" id="top">
    <div class="container header-inner">
        <a class="brand" href="<?= e(url('')) ?>">
            <img src="<?= e(header_logo_url()) ?>" alt="INESCO Logo" width="40" height="40">
            <span>INESCO</span>
        </a>
        <button class="nav-toggle" aria-label="Menü" aria-expanded="false">
            <span></span><span></span><span></span>
        </button>
        <nav class="main-nav">
            <ul>
                <?php foreach (nav_items() as $nav): ?>
                    <?php if (empty($nav['visible'])) continue; ?>
                    <li><a href="<?= e($navBase) ?>#<?= e($nav['id']) ?>"><?= e($nav['label']) ?></a></li>
                <?php endforeach; ?>
            </ul>
        </nav>
    </div>
</header>
