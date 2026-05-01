<?php
require_once __DIR__ . '/auth.php';
$pageTitle = 'Texte';

$content = read_json('content');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['csrf'] ?? null)) {
        flash_set('err', 'Sicherheitstoken ungültig.');
        redirect(url('admin/content.php'));
    }
    $op = $_POST['op'] ?? 'save_text';

    if ($op === 'upload_hero') {
        $count = 0; $errors = 0;
        // $_FILES['hero_files'] is structured as arrays when input is name="hero_files[]"
        if (!empty($_FILES['hero_files']) && is_array($_FILES['hero_files']['name'])) {
            $files = $_FILES['hero_files'];
            for ($i = 0, $n = count($files['name']); $i < $n; $i++) {
                if (($files['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) continue;
                $single = [
                    'name'     => $files['name'][$i],
                    'type'     => $files['type'][$i] ?? '',
                    'tmp_name' => $files['tmp_name'][$i],
                    'error'    => $files['error'][$i],
                    'size'     => $files['size'][$i],
                ];
                $up = handle_upload($single, 'img/hero', ['jpg','jpeg','png','webp','avif','gif']);
                if ($up) $count++; else $errors++;
            }
        }
        if ($count) flash_set('ok', "$count Bild(er) hochgeladen.");
        if ($errors) flash_set('err', "$errors Datei(en) konnten nicht hochgeladen werden.");
        if (!$count && !$errors) flash_set('err', 'Keine Datei ausgewählt.');
        redirect(url('admin/content.php') . '#hero-images');
    }

    if ($op === 'delete_hero') {
        $rel = (string)($_POST['file'] ?? '');
        // Pfad muss innerhalb assets/img/hero liegen – keine traversal
        if ($rel && strpos($rel, 'assets/img/hero/') === 0 && strpos($rel, '..') === false) {
            $abs = BASE_PATH . '/' . $rel;
            $real = realpath($abs);
            $heroDir = realpath(BASE_PATH . '/assets/img/hero');
            if ($real && $heroDir && strpos($real, $heroDir) === 0 && is_file($real)) {
                if (@unlink($real)) flash_set('ok', 'Bild gelöscht.');
                else flash_set('err', 'Bild konnte nicht gelöscht werden.');
            } else {
                flash_set('err', 'Datei nicht gefunden.');
            }
        } else {
            flash_set('err', 'Ungültiger Pfad.');
        }
        redirect(url('admin/content.php') . '#hero-images');
    }

    if ($op === 'upload_logo') {
        $f = $_FILES['logo_file'] ?? null;
        if (!$f || ($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            flash_set('err', 'Keine Datei ausgewählt.');
        } elseif ($f['error'] !== UPLOAD_ERR_OK) {
            flash_set('err', 'Upload fehlgeschlagen.');
        } else {
            $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
            $allowed = ['png','jpg','jpeg','webp','svg'];
            if (!in_array($ext, $allowed, true)) {
                flash_set('err', 'Dateityp nicht erlaubt (png, jpg, jpeg, webp, svg).');
            } elseif ($f['size'] > 5 * 1024 * 1024) {
                flash_set('err', 'Datei zu groß (max. 5 MB).');
            } else {
                // Logo immer unter logo.png speichern (Hauptpfad), zusätzlich Originalformat behalten
                $target = BASE_PATH . '/assets/img/logo.' . $ext;
                $main   = BASE_PATH . '/assets/img/logo.png';
                if (move_uploaded_file($f['tmp_name'], $target)) {
                    // Wenn nicht png, png-Datei zusätzlich aktualisieren falls möglich
                    if ($ext !== 'png' && $ext !== 'svg' && function_exists('imagecreatefromstring')) {
                        $raw = @file_get_contents($target);
                        $im  = $raw ? @imagecreatefromstring($raw) : false;
                        if ($im) {
                            imagesavealpha($im, true);
                            imagepng($im, $main);
                            imagedestroy($im);
                        }
                    } elseif ($ext === 'png') {
                        // bereits am richtigen Ort
                    }
                    flash_set('ok', 'Logo aktualisiert.');
                } else {
                    flash_set('err', 'Logo konnte nicht gespeichert werden.');
                }
            }
        }
        redirect(url('admin/content.php') . '#logo-section');
    }

    if ($op === 'upload_logo_small') {
        $f = $_FILES['logo_small_file'] ?? null;
        if (!$f || ($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            flash_set('err', 'Keine Datei ausgewählt.');
        } elseif ($f['error'] !== UPLOAD_ERR_OK) {
            flash_set('err', 'Upload fehlgeschlagen.');
        } else {
            $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
            $allowed = ['png','jpg','jpeg','webp','svg'];
            if (!in_array($ext, $allowed, true)) {
                flash_set('err', 'Dateityp nicht erlaubt (png, jpg, jpeg, webp, svg).');
            } elseif ($f['size'] > 5 * 1024 * 1024) {
                flash_set('err', 'Datei zu groß (max. 5 MB).');
            } else {
                // Vorhandene Header-Logo-Varianten zuerst entfernen,
                // damit immer genau eine Datei aktiv ist.
                foreach (['svg','png','webp','jpg','jpeg'] as $oldExt) {
                    $old = BASE_PATH . '/assets/img/logo-header.' . $oldExt;
                    if (is_file($old)) @unlink($old);
                }
                $target = BASE_PATH . '/assets/img/logo-header.' . $ext;
                if (move_uploaded_file($f['tmp_name'], $target)) {
                    flash_set('ok', 'Header-Logo aktualisiert.');
                } else {
                    flash_set('err', 'Header-Logo konnte nicht gespeichert werden.');
                }
            }
        }
        redirect(url('admin/content.php') . '#logo-section');
    }

    if ($op === 'reset_logo_small') {
        $removed = 0;
        foreach (['svg','png','webp','jpg','jpeg'] as $oldExt) {
            $old = BASE_PATH . '/assets/img/logo-header.' . $oldExt;
            if (is_file($old) && @unlink($old)) $removed++;
        }
        flash_set($removed ? 'ok' : 'err',
            $removed ? 'Header-Logo entfernt – es wird wieder das Hauptlogo verwendet.'
                     : 'Kein separates Header-Logo vorhanden.');
        redirect(url('admin/content.php') . '#logo-section');
    }

    // Default: Texte speichern
    $content['site']['title']       = trim((string)$_POST['site_title']);
    $content['site']['description'] = trim((string)$_POST['site_desc']);
    $content['site']['keywords']    = trim((string)$_POST['site_kw']);

    $content['hero']['headline']    = trim((string)$_POST['hero_headline']);
    $content['hero']['subline']     = trim((string)$_POST['hero_subline']);
    $content['hero']['slogan']      = trim((string)$_POST['hero_slogan']);
    $content['hero']['ctaPrimary']  = ['label' => trim((string)$_POST['cta1_label']), 'href' => trim((string)$_POST['cta1_href'])];
    $content['hero']['ctaSecondary']= ['label' => trim((string)$_POST['cta2_label']), 'href' => trim((string)$_POST['cta2_href'])];

    $mode = $_POST['hero_mode'] ?? 'slideshow';
    if (!in_array($mode, ['slideshow', 'random', 'static'], true)) $mode = 'slideshow';
    $content['hero']['mode']     = $mode;
    $content['hero']['interval'] = max(2, min(60, (int)($_POST['hero_interval'] ?? 6)));

    $content['bio']['title']        = trim((string)$_POST['bio_title']);
    $content['bio']['text']         = trim((string)$_POST['bio_text']);

    if (write_json('content', $content)) flash_set('ok', 'Texte gespeichert.');
    else flash_set('err', 'Fehler beim Speichern.');
    redirect(url('admin/content.php'));
}

include __DIR__ . '/header.php';
?>
<h1>Texte bearbeiten</h1>
<form method="post" class="form">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="op" value="save_text">

    <fieldset>
        <legend>Site / SEO</legend>
        <label>Titel<input type="text" name="site_title" value="<?= e($content['site']['title'] ?? '') ?>"></label>
        <label>Beschreibung<textarea name="site_desc" rows="2"><?= e($content['site']['description'] ?? '') ?></textarea></label>
        <label>Keywords<input type="text" name="site_kw" value="<?= e($content['site']['keywords'] ?? '') ?>"></label>
    </fieldset>

    <fieldset>
        <legend>Hero</legend>
        <label>Headline<input type="text" name="hero_headline" value="<?= e($content['hero']['headline'] ?? '') ?>"></label>
        <label>Subline<input type="text" name="hero_subline" value="<?= e($content['hero']['subline'] ?? '') ?>"></label>
        <label>Slogan<input type="text" name="hero_slogan" value="<?= e($content['hero']['slogan'] ?? '') ?>"></label>
        <div class="grid-2">
            <label>CTA 1 – Label<input type="text" name="cta1_label" value="<?= e($content['hero']['ctaPrimary']['label'] ?? '') ?>"></label>
            <label>CTA 1 – Link<input type="text" name="cta1_href" value="<?= e($content['hero']['ctaPrimary']['href'] ?? '') ?>"></label>
            <label>CTA 2 – Label<input type="text" name="cta2_label" value="<?= e($content['hero']['ctaSecondary']['label'] ?? '') ?>"></label>
            <label>CTA 2 – Link<input type="text" name="cta2_href" value="<?= e($content['hero']['ctaSecondary']['href'] ?? '') ?>"></label>
        </div>
    </fieldset>

    <fieldset>
        <legend>Hero-Bilder</legend>
        <p class="muted" style="margin:0 0 .75rem">
            Bilder werden automatisch aus <code>assets/img/hero/</code> geladen.
            Aktuell erkannt: <strong><?= count(hero_images()) ?></strong> Bild(er).
        </p>
        <div class="grid-2">
            <label>Modus
                <select name="hero_mode">
                    <?php $hm = $content['hero']['mode'] ?? 'slideshow'; ?>
                    <option value="slideshow" <?= $hm === 'slideshow' ? 'selected' : '' ?>>Slideshow (Crossfade)</option>
                    <option value="random"    <?= $hm === 'random'    ? 'selected' : '' ?>>Zufallsbild bei jedem Aufruf</option>
                    <option value="static"    <?= $hm === 'static'    ? 'selected' : '' ?>>Nur erstes Bild (statisch)</option>
                </select>
            </label>
            <label>Wechsel-Intervall (Sekunden)
                <input type="number" name="hero_interval" min="2" max="60" step="1"
                       value="<?= e((string)($content['hero']['interval'] ?? 6)) ?>">
            </label>
        </div>
    </fieldset>

    <fieldset>
        <legend>Band-Bio</legend>
        <label>Überschrift<input type="text" name="bio_title" value="<?= e($content['bio']['title'] ?? '') ?>"></label>
        <label>Text<textarea name="bio_text" rows="8"><?= e($content['bio']['text'] ?? '') ?></textarea></label>
    </fieldset>

    <button class="btn-primary" type="submit">Speichern</button>
</form>

<!-- Logo verwalten -->
<section id="logo-section" style="margin-top:2rem">
    <h2>Logo</h2>
    <div style="display:flex;align-items:center;gap:1.5rem;flex-wrap:wrap;margin-bottom:1rem">
        <img src="<?= e(url('assets/img/logo.png')) ?>?v=<?= @filemtime(BASE_PATH . '/assets/img/logo.png') ?>"
             alt="Aktuelles Logo"
             style="width:120px;height:auto;border-radius:12px;background:rgba(0,0,0,.3);padding:.25rem">
        <div>
            <p class="muted" style="margin:0">
                Aktuelles Logo: <code>assets/img/logo.png</code>.<br>
                Beim Hochladen wird die Datei ersetzt. Empfohlen: PNG mit Transparenz, ca. 1000 × 900 px.
            </p>
        </div>
    </div>

    <form method="post" enctype="multipart/form-data" class="form">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="op" value="upload_logo">
        <label>Neues Logo hochladen
            <input type="file" name="logo_file" accept="image/png,image/jpeg,image/webp,image/svg+xml" required>
        </label>
        <p class="muted" style="margin:.25rem 0 .75rem">
            Erlaubt: png, jpg, jpeg, webp, svg. Max. 5 MB.
        </p>
        <button class="btn-primary" type="submit">Logo ersetzen</button>
    </form>
</section>

<!-- Kleines Header-Logo (optional, separat) -->
<section id="logo-header-section" style="margin-top:2rem">
    <h2>Header-Logo (klein)</h2>
    <p class="muted" style="margin:0 0 1rem">
        Optionales, separates Logo speziell für die kleine Darstellung im Header und im Hero-Bereich.
        Wenn keines hochgeladen ist, wird automatisch das Hauptlogo verwendet.<br>
        <strong>Empfehlung gegen Pixeligkeit:</strong> SVG hochladen (vektorbasiert, scharf in jeder Größe)
        oder eine PNG-Datei mit mindestens 200 × 200 px.
    </p>
    <?php $hsmall = header_logo_path(); ?>
    <div style="display:flex;align-items:center;gap:1.5rem;flex-wrap:wrap;margin-bottom:1rem">
        <div style="background:rgba(0,0,0,.4);padding:.5rem;border-radius:12px;display:inline-flex;align-items:center;justify-content:center">
            <img src="<?= e(header_logo_url()) ?>"
                 alt="Aktuelles Header-Logo"
                 style="width:60px;height:60px;object-fit:contain;display:block">
        </div>
        <div>
            <p class="muted" style="margin:0">
                <?php if ($hsmall): ?>
                    Aktiv: <code><?= e($hsmall) ?></code>
                <?php else: ?>
                    Kein separates Header-Logo gesetzt – Fallback auf <code>assets/img/logo.png</code>.
                <?php endif; ?>
            </p>
        </div>
    </div>

    <form method="post" enctype="multipart/form-data" class="form" style="margin-bottom:1rem">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="op" value="upload_logo_small">
        <label>Neues Header-Logo hochladen
            <input type="file" name="logo_small_file" accept="image/svg+xml,image/png,image/webp,image/jpeg" required>
        </label>
        <p class="muted" style="margin:.25rem 0 .75rem">
            Erlaubt: svg (empfohlen), png, jpg, jpeg, webp. Max. 5 MB.
        </p>
        <button class="btn-primary" type="submit">Header-Logo speichern</button>
    </form>

    <?php if ($hsmall): ?>
        <form method="post" onsubmit="return confirm('Separates Header-Logo wirklich entfernen? Es wird dann wieder das Hauptlogo verwendet.')">
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="op" value="reset_logo_small">
            <button class="link-danger" type="submit">Separates Header-Logo entfernen</button>
        </form>
    <?php endif; ?>
</section>

<!-- Hero-Bilder verwalten (eigene Forms, nicht im Texte-Form verschachtelbar) -->
<section id="hero-images" style="margin-top:2rem">
    <h2>Hero-Bilder verwalten</h2>

    <form method="post" enctype="multipart/form-data" class="form" style="margin-bottom:1.5rem">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="op" value="upload_hero">
        <label>Neue Bilder hochladen (mehrere möglich)
            <input type="file" name="hero_files[]" accept="image/*" multiple required>
        </label>
        <p class="muted" style="margin:.25rem 0 .75rem">
            Erlaubt: jpg, jpeg, png, webp, avif, gif. Max. 20 MB pro Datei.
        </p>
        <button class="btn-primary" type="submit">Hochladen</button>
    </form>

    <?php $imgs = hero_images(); ?>
    <?php if (empty($imgs)): ?>
        <p class="muted">Keine Hero-Bilder vorhanden.</p>
    <?php else: ?>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:1rem">
            <?php foreach ($imgs as $img): ?>
                <div style="border:1px solid rgba(255,255,255,.1);border-radius:8px;padding:.5rem;text-align:center;background:rgba(0,0,0,.2)">
                    <img src="<?= e(url($img)) ?>" alt="" style="width:100%;height:120px;object-fit:cover;border-radius:6px;display:block;margin-bottom:.5rem">
                    <p style="margin:.25rem 0;font-size:.8rem;word-break:break-all"><?= e(basename($img)) ?></p>
                    <form method="post" style="margin:0" onsubmit="return confirm('Dieses Bild wirklich löschen?')">
                        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="op" value="delete_hero">
                        <input type="hidden" name="file" value="<?= e($img) ?>">
                        <button class="link-danger" type="submit">Löschen</button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php include __DIR__ . '/footer.php'; ?>
