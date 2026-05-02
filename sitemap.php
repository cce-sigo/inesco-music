<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/config.php';

$proto   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host    = preg_replace('/[^a-zA-Z0-9\-\.\:]/', '', $_SERVER['HTTP_HOST'] ?? 'inesco-music.at');
$baseUrl = rtrim($proto . '://' . $host . rtrim(BASE_URL, '/'), '/');

$pages = [
    ['loc' => $baseUrl . '/',                 'changefreq' => 'weekly', 'priority' => '1.0'],
    ['loc' => $baseUrl . '/impressum.php',    'changefreq' => 'yearly', 'priority' => '0.2'],
    ['loc' => $baseUrl . '/datenschutz.php',  'changefreq' => 'yearly', 'priority' => '0.2'],
];

header('Content-Type: application/xml; charset=utf-8');
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<?php foreach ($pages as $p): ?>
    <url>
        <loc><?= htmlspecialchars($p['loc'], ENT_XML1, 'UTF-8') ?></loc>
        <changefreq><?= htmlspecialchars($p['changefreq'], ENT_XML1, 'UTF-8') ?></changefreq>
        <priority><?= htmlspecialchars($p['priority'], ENT_XML1, 'UTF-8') ?></priority>
    </url>
<?php endforeach; ?>
</urlset>
