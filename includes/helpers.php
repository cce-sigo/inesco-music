<?php
declare(strict_types=1);

/**
 * Liest eine JSON-Datei aus dem data-Ordner.
 */
function read_json(string $name, $default = []) {
    $path = DATA_PATH . '/' . basename($name) . '.json';
    if (!is_file($path)) return $default;
    $raw = file_get_contents($path);
    if ($raw === false || $raw === '') return $default;
    $data = json_decode($raw, true);
    return $data === null ? $default : $data;
}

/**
 * Speichert Daten als JSON (atomar).
 */
function write_json(string $name, $data): bool {
    $path = DATA_PATH . '/' . basename($name) . '.json';
    $tmp = $path . '.tmp';
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json === false) return false;
    if (file_put_contents($tmp, $json, LOCK_EX) === false) return false;
    return @rename($tmp, $path);
}

function e(?string $s): string {
    return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $path = ''): string {
    return BASE_URL . ltrim($path, '/');
}

function csrf_token(): string {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_check(?string $token): bool {
    return !empty($_SESSION['csrf']) && is_string($token) && hash_equals($_SESSION['csrf'], $token);
}

function redirect(string $to): void {
    header('Location: ' . $to);
    exit;
}

function flash_set(string $type, string $msg): void {
    $_SESSION['flash'][] = ['type' => $type, 'msg' => $msg];
}

function flash_pop(): array {
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

function next_id(array $items): string {
    $max = 0;
    foreach ($items as $it) {
        if (isset($it['id']) && is_numeric($it['id']) && (int)$it['id'] > $max) {
            $max = (int)$it['id'];
        }
    }
    return (string)($max + 1);
}

/**
 * Sortiert Konzerte nach Datum aufsteigend, vergangene optional ans Ende.
 */
function sort_concerts(array $items, bool $upcomingFirst = true): array {
    $today = date('Y-m-d');
    usort($items, function ($a, $b) use ($today, $upcomingFirst) {
        $da = $a['date'] ?? '';
        $db = $b['date'] ?? '';
        if ($upcomingFirst) {
            $aPast = $da < $today; $bPast = $db < $today;
            if ($aPast !== $bPast) return $aPast ? 1 : -1;
        }
        return strcmp($da, $db);
    });
    return $items;
}

function format_date_de(string $iso): string {
    $ts = strtotime($iso);
    if (!$ts) return $iso;
    $months = [1=>'Jan.','Feb.','März','Apr.','Mai','Juni','Juli','Aug.','Sept.','Okt.','Nov.','Dez.'];
    return date('d.', $ts) . ' ' . $months[(int)date('n', $ts)] . ' ' . date('Y', $ts);
}

/**
 * Sicheres Datei-Upload-Handling.
 */
function handle_upload(array $file, string $subdir, array $allowedExt, int $maxBytes = 20971520): ?string {
    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) return null;
    if ($file['size'] > $maxBytes) return null;

    $name = $file['name'];
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExt, true)) return null;

    $safeBase = preg_replace('/[^a-z0-9_-]+/i', '-', pathinfo($name, PATHINFO_FILENAME));
    $safeBase = trim($safeBase, '-') ?: 'file';
    $fname = $safeBase . '-' . substr(bin2hex(random_bytes(4)), 0, 6) . '.' . $ext;

    $targetDir = UPLOAD_PATH . '/' . trim($subdir, '/');
    if (!is_dir($targetDir)) @mkdir($targetDir, 0775, true);
    $target = $targetDir . '/' . $fname;
    if (!move_uploaded_file($file['tmp_name'], $target)) return null;

    return 'assets/' . trim($subdir, '/') . '/' . $fname;
}

/**
 * Liefert eine Liste der Hero-Bilder (relative Pfade) aus assets/img/hero/.
 * Optional gemischt (random) oder alphabetisch sortiert.
 */
function hero_images(bool $shuffle = false): array {
    $dir = dirname(__DIR__) . '/assets/img/hero';
    if (!is_dir($dir)) return [];
    $files = [];
    foreach (scandir($dir) ?: [] as $f) {
        if ($f === '.' || $f === '..') continue;
        $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg','jpeg','png','webp','avif','gif'], true)) continue;
        if (!is_file($dir . '/' . $f)) continue;
        $files[] = 'assets/img/hero/' . $f;
    }
    sort($files, SORT_NATURAL | SORT_FLAG_CASE);
    if ($shuffle) shuffle($files);
    return $files;
}

/**
 * Liefert die URL für das Haupt-/Hauptlogo (logo.png) inkl. Cache-Buster.
 */
function main_logo_url(): string {
    $rel = 'assets/img/logo.png';
    $abs = dirname(__DIR__) . '/' . $rel;
    $v   = is_file($abs) ? @filemtime($abs) : 0;
    return url($rel) . ($v ? ('?v=' . $v) : '');
}

/**
 * Liefert die URL für das kleine Header-/Marken-Logo.
 * Sucht in dieser Reihenfolge: logo-header.svg, .png, .webp, .jpg.
 * Fällt sonst auf das Hauptlogo (logo.png) zurück.
 * Enthält einen Cache-Buster (?v=mtime).
 */
function header_logo_url(): string {
    $base = dirname(__DIR__) . '/assets/img/';
    foreach (['svg','png','webp','jpg','jpeg'] as $ext) {
        $f = $base . 'logo-header.' . $ext;
        if (is_file($f)) {
            return url('assets/img/logo-header.' . $ext) . '?v=' . @filemtime($f);
        }
    }
    return main_logo_url();
}

/**
 * Pfad (relativ) zur aktuell vorhandenen Header-Logo-Datei oder null.
 */
function header_logo_path(): ?string {
    $base = dirname(__DIR__) . '/assets/img/';
    foreach (['svg','png','webp','jpg','jpeg'] as $ext) {
        if (is_file($base . 'logo-header.' . $ext)) {
            return 'assets/img/logo-header.' . $ext;
        }
    }
    return null;
}

/**
 * Default-Navigationspunkte. Die `id` muss mit dem Anker / der Section-ID
 * in `index.php` übereinstimmen (#bio, #musik, ...).
 */
function nav_defaults(): array {
    return [
        ['id' => 'bio',          'label' => 'Band',              'visible' => true],
        ['id' => 'musik',        'label' => 'Musik',             'visible' => true],
        ['id' => 'videos',       'label' => 'Videos',            'visible' => true],
        ['id' => 'impressionen', 'label' => 'Impressionen',      'visible' => true],
        ['id' => 'konzerte',     'label' => 'Konzerte',          'visible' => true],
        ['id' => 'kontakt',      'label' => 'Kontakt',           'visible' => true],
        ['id' => 'partner',      'label' => 'Freunde & Sponsoren', 'visible' => true],
        ['id' => 'gaestebuch',   'label' => 'Gästebuch',         'visible' => true],
    ];
}

/**
 * Liefert die konfigurierten Navigationspunkte (in Reihenfolge).
 * Fehlende Default-Items werden hinten angefügt, damit man auch nach
 * einem Software-Update neue Punkte konfigurieren kann.
 */
function nav_items(): array {
    static $cache = null;
    if ($cache !== null) return $cache;

    $defaults = nav_defaults();
    $defaultIds = array_column($defaults, 'id');

    $content = read_json('content');
    $items = $content['nav'] ?? null;

    if (!is_array($items) || empty($items)) {
        return $cache = $defaults;
    }

    $out = [];
    $seen = [];
    foreach ($items as $it) {
        if (!is_array($it) || empty($it['id'])) continue;
        $id = (string)$it['id'];
        if (isset($seen[$id])) continue;
        $seen[$id] = true;
        $out[] = [
            'id'      => $id,
            'label'   => (string)($it['label'] ?? $id),
            'visible' => !empty($it['visible']),
        ];
    }
    // Fehlende Default-Items am Ende ergänzen
    foreach ($defaults as $d) {
        if (!isset($seen[$d['id']])) $out[] = $d;
    }
    return $cache = $out;
}

/**
 * Prüft, ob ein Nav-Punkt sichtbar ist (zugleich: ob die Section gerendert wird).
 * Unbekannte IDs gelten als sichtbar.
 */
function nav_is_visible(string $id): bool {
    foreach (nav_items() as $it) {
        if ($it['id'] === $id) return !empty($it['visible']);
    }
    return true;
}

/**
 * Prüft, ob neue Gästebuch-Einträge zunächst geprüft werden müssen.
 * Default ist aktiv, damit bestehende Installationen sicher bleiben.
 */
function comment_review_enabled(): bool {
    $content = read_json('content');
    if (!isset($content['comments']) || !is_array($content['comments'])) {
        return true;
    }
    return !array_key_exists('reviewing', $content['comments']) || !empty($content['comments']['reviewing']);
}

/**
 * Scans an assets sub-directory recursively and returns sorted relative paths
 * (e.g. "assets/img/impressions/foo.jpg") filtered by allowed extensions.
 */
function scan_asset_files(string $subdir, array $allowedExt): array {
    $baseAbs = rtrim(str_replace('\\', '/', BASE_PATH . '/assets/' . trim($subdir, '/\\')), '/');
    if (!is_dir($baseAbs)) return [];
    $results = [];
    $iter = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($baseAbs, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::LEAVES_ONLY
    );
    foreach ($iter as $file) {
        if (!$file->isFile()) continue;
        $ext = strtolower($file->getExtension());
        if (!in_array($ext, $allowedExt, true)) continue;
        $abs = str_replace('\\', '/', $file->getPathname());
        $rel = 'assets/' . trim($subdir, '/\\') . '/' . ltrim(substr($abs, strlen($baseAbs)), '/');
        $results[] = $rel;
    }
    sort($results, SORT_NATURAL | SORT_FLAG_CASE);
    return $results;
}

/**
 * Checks whether a relative asset path (e.g. "assets/img/foo.jpg") is still
 * referenced in ANY data JSON file, optionally ignoring one specific entry
 * (the one being deleted right now).
 *
 * @param string $relPath   The relative path to check (as stored in JSON).
 * @param string $skipJson  JSON store name to skip one entry in (e.g. 'impressions').
 * @param string $skipId    The id value of the entry to skip.
 */
function asset_in_use(string $relPath, string $skipJson = '', string $skipId = ''): bool {
    if ($relPath === '' || preg_match('#^https?://#i', $relPath)) return false;
    $relPath = ltrim(str_replace('\\', '/', $relPath), '/');

    // All JSON stores that may contain asset paths
    $stores = ['impressions', 'videos', 'tracks', 'members', 'sponsors', 'concerts', 'content'];
    // Fields that can hold asset paths (flat or nested)
    $fields = ['src', 'file', 'cover', 'thumbnail', 'image', 'logo', 'photo', 'img'];

    foreach ($stores as $store) {
        $items = read_json($store, []);
        if (!is_array($items)) continue;

        // content.json can be a nested object — flatten one level for hero images etc.
        $rows = isset($items[0]) ? $items : [$items];

        foreach ($rows as $item) {
            if (!is_array($item)) continue;
            $itemId = (string)($item['id'] ?? '');
            // Skip the entry currently being deleted
            if ($store === $skipJson && $skipId !== '' && $itemId === $skipId) continue;

            foreach ($fields as $f) {
                $val = $item[$f] ?? null;
                if ($val === null) continue;
                if (!is_array($val)) $val = [$val];
                foreach ($val as $v) {
                    if (!is_string($v)) continue;
                    $v = ltrim(str_replace('\\', '/', $v), '/');
                    if ($v === $relPath) return true;
                }
            }
        }
    }
    return false;
}

/**
 * Safely deletes a local asset file only when no other entry still references it.
 * Returns true if the file was deleted, false if kept or not found.
 */
function maybe_unlink_asset(string $relPath, string $skipJson = '', string $skipId = ''): bool {
    if ($relPath === '' || preg_match('#^https?://#i', $relPath)) return false;
    if (asset_in_use($relPath, $skipJson, $skipId)) return false;

    $rel  = ltrim(str_replace('\\', '/', $relPath), '/');
    $abs  = BASE_PATH . '/' . $rel;
    $real = realpath($abs);
    $assetsRoot = realpath(BASE_PATH . '/assets');
    if ($real && $assetsRoot && str_starts_with($real, $assetsRoot) && is_file($real)) {
        return (bool)@unlink($real);
    }
    return false;
}

function is_logged_in(): bool {
    return !empty($_SESSION['admin_user']);
}

function require_login(): void {
    if (!is_logged_in()) {
        redirect(url('admin/login.php'));
    }
}
