<?php
/** @var array $content */
/** @var array $contact */
/** @var string|null $pageTitle Optional per-page title override */
/** @var string|null $pageDesc  Optional per-page description override */
// Example (set before include __DIR__ . '/includes/header.php'; on any page):
//   $pageTitle = 'Impressum – INESCO';
//   $pageDesc  = 'Impressum und rechtliche Angaben zu INESCO – Ines & Sigo.';
$siteTitle = $pageTitle ?? $content['site']['title'] ?? 'INESCO';
$siteDesc  = $pageDesc  ?? $content['site']['description'] ?? '';
$siteKw    = $content['site']['keywords'] ?? '';

// Canonical URL (strip query string, sanitise host)
$_proto       = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$_host        = preg_replace('/[^a-zA-Z0-9\-\.\:]/', '', $_SERVER['HTTP_HOST'] ?? 'inesco-music.at');
$_path        = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
$canonicalUrl = $_proto . '://' . $_host . $_path;
$siteBaseUrl  = $_proto . '://' . $_host . rtrim(BASE_URL, '/') . '/';

// Navigationsziele relativ zur Startseite (funktioniert auch auf Unterseiten)
$scriptName = basename($_SERVER['SCRIPT_NAME'] ?? '');
$isHome     = ($scriptName === '' || $scriptName === 'index.php');
$navBase    = $isHome ? '' : url('');
$brandHref  = $isHome ? '#top' : url('');
?><!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($siteTitle) ?></title>
    <meta name="description" content="<?= e($siteDesc) ?>">
    <meta name="keywords" content="<?= e($siteKw) ?>">
    <meta name="theme-color" content="#1a0b0d">

    <link rel="canonical" href="<?= e($canonicalUrl) ?>">
    <meta name="robots" content="index, follow">

    <meta property="og:title" content="<?= e($siteTitle) ?>">
    <meta property="og:description" content="<?= e($siteDesc) ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= e($canonicalUrl) ?>">
    <meta property="og:site_name" content="INESCO">
    <meta property="og:image" content="<?= e(url('assets/img/band.png')) ?>">
    <meta property="og:image:width" content="1064">
    <meta property="og:image:height" content="757">
    <meta property="og:locale" content="de_DE">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= e($siteTitle) ?>">
    <meta name="twitter:description" content="<?= e($siteDesc) ?>">
    <meta name="twitter:image" content="<?= e(url('assets/img/band.png')) ?>">

    <meta name="author" content="INESCO – Ines & Sigo">
    <meta name="geo.region" content="AT-2">
    <meta name="geo.placename" content="Feldkirchen in Kärnten">
    <meta name="geo.position" content="46.7239;14.0925">
    <meta name="ICBM" content="46.7239, 14.0925">

    <link rel="icon" type="image/png" href="<?= e(url('assets/img/logo.png')) ?>">
    <?php if (!empty($heroPreload)): ?>
        <link rel="preload" as="image" href="<?= e($heroPreload) ?>" fetchpriority="high">
    <?php endif; ?>
    <?php if (!empty($mediaPrefetch) && is_array($mediaPrefetch)): ?>
        <?php foreach ($mediaPrefetch as $mp): ?>
            <link rel="prefetch" as="<?= e($mp['as']) ?>" href="<?= e($mp['href']) ?>">
        <?php endforeach; ?>
    <?php endif; ?>
    <?php $fontsCssPath = BASE_PATH . '/assets/css/fonts.css'; $fontsVer = is_file($fontsCssPath) ? filemtime($fontsCssPath) : time(); ?>
    <link rel="stylesheet" href="<?= e(url('assets/css/fonts.css')) ?>?v=<?= $fontsVer ?>">
    <?php $cssPath = BASE_PATH . '/assets/css/style.css'; $cssVer = is_file($cssPath) ? filemtime($cssPath) : time(); ?>
    <link rel="stylesheet" href="<?= e(url('assets/css/style.css')) ?>?v=<?= $cssVer ?>">

    <?php
    // ---- JSON-LD Structured Data ----
    $_ldSocial = array_values(array_filter([
        $contact['social']['facebook'] ?? '',
        $contact['social']['instagram'] ?? '',
        $contact['social']['youtube'] ?? '',
    ]));

    $_ldMembers = [];
    foreach (isset($members) && is_array($members) ? $members : [] as $_m) {
        $_ldMembers[] = [
            '@type'    => 'OrganizationRole',
            'member'   => ['@type' => 'Person', 'name' => $_m['name'] ?? ''],
            'roleName' => $_m['role'] ?? '',
        ];
    }

    $_ldMusicGroup = [
        '@context'     => 'https://schema.org',
        '@type'        => 'MusicGroup',
        'name'         => 'INESCO',
        'description'  => $siteDesc,
        'url'          => $siteBaseUrl,
        'genre'        => ['Soul', 'Blues', 'Gypsy Jazz', 'Latin Rock'],
        'contactPoint' => [
            '@type'       => 'ContactPoint',
            'email'       => $contact['email'] ?? '',
            'telephone'   => $contact['phone'] ?? '',
            'contactType' => 'booking',
        ],
    ];
    if (!empty($_ldMembers))  { $_ldMusicGroup['member']  = $_ldMembers; }
    if (!empty($_ldSocial))   { $_ldMusicGroup['sameAs']  = $_ldSocial; }

    $_ldGraphItems = [$_ldMusicGroup];

    // Add Event schemas for each concert
    $today = date('Y-m-d');
    foreach (isset($concerts) && is_array($concerts) ? $concerts : [] as $_c) {
        if (empty($_c['date']) || $_c['date'] < $today) continue;
        $startDate = $_c['date'] . (isset($_c['time']) ? 'T' . $_c['time'] : '');
        $_ldGraphItems[] = [
            '@context'             => 'https://schema.org',
            '@type'                => 'Event',
            'name'                 => trim('INESCO' . (isset($_c['description']) && $_c['description'] !== '' ? ' – ' . $_c['description'] : ' – ' . ($_c['venue'] ?? ''))),
            'startDate'            => $startDate,
            'eventStatus'          => 'https://schema.org/EventScheduled',
            'eventAttendanceMode'  => 'https://schema.org/OfflineEventAttendanceMode',
            'location'             => [
                '@type'   => 'Place',
                'name'    => $_c['venue'] ?? '',
                'address' => [
                    '@type'           => 'PostalAddress',
                    'addressLocality' => $_c['city'] ?? '',
                ],
            ],
            'performer' => ['@type' => 'MusicGroup', 'name' => 'INESCO'],
            'organizer' => ['@type' => 'MusicGroup', 'name' => 'INESCO', 'url' => $siteBaseUrl],
        ];
    }
    $_jsonEncodeFlags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
    foreach ($_ldGraphItems as $_ldItem): ?>
    <script type="application/ld+json"><?= json_encode($_ldItem, $_jsonEncodeFlags) ?></script>
    <?php endforeach; ?>

    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-BPGBYHS59V"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());
      gtag('config', 'G-BPGBYHS59V');
    </script>
</head>
<body>
<header class="site-header" id="top">
    <div class="container header-inner">
        <a class="brand" href="<?= e($brandHref) ?>">
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
