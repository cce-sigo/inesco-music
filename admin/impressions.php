<?php
require_once __DIR__ . '/auth.php';
$pageTitle = 'Impressionen';

$items  = read_json('impressions');
$action = $_GET['action'] ?? 'list';
$id     = $_GET['id'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['csrf'] ?? null)) {
        flash_set('err', 'Sicherheitstoken ungültig.');
        redirect(url('admin/impressions.php'));
    }
    $op = $_POST['op'] ?? '';

    if ($op === 'save') {
        $iid     = $_POST['id'] ?: next_id($items);
        $type    = $_POST['type'] ?? 'text';
        if (!in_array($type, ['image','video','text'], true)) $type = 'text';
        $caption = trim((string)($_POST['caption'] ?? ''));
        $text    = trim((string)($_POST['text'] ?? ''));
        $src     = trim((string)($_POST['existing_src'] ?? ''));
        $kind    = $_POST['video_kind'] ?? 'youtube';

        if ($type === 'image') {
            if (!empty($_FILES['image_file']['name'])) {
                $up = handle_upload($_FILES['image_file'], 'img/impressions',
                    ['jpg','jpeg','png','webp','avif','gif'], 20 * 1024 * 1024);
                if ($up) $src = $up;
            }
            if ($src === '') {
                flash_set('err', 'Bitte ein Bild hochladen.');
                redirect(url('admin/impressions.php?action=' . ($_POST['id'] ? 'edit&id=' . urlencode($_POST['id']) : 'new')));
            }
        } elseif ($type === 'video') {
            if (!in_array($kind, ['youtube','vimeo','upload'], true)) $kind = 'youtube';
            if ($kind === 'upload') {
                if (!empty($_FILES['video_file']['name'])) {
                    $up = handle_upload($_FILES['video_file'], 'video',
                        ['mp4','webm','mov'], 200 * 1024 * 1024);
                    if ($up) $src = $up;
                }
            } else {
                $url = trim((string)($_POST['video_url'] ?? ''));
                if ($kind === 'youtube' && preg_match('#(?:youtube\.com/watch\?v=|youtu\.be/|youtube\.com/embed/)([\w-]{6,})#', $url, $m)) {
                    $src = 'https://www.youtube.com/embed/' . $m[1];
                } elseif ($kind === 'vimeo' && preg_match('#vimeo\.com/(?:video/)?(\d+)#', $url, $m)) {
                    $src = 'https://player.vimeo.com/video/' . $m[1];
                } elseif ($url !== '') {
                    $src = $url;
                }
            }
            if ($src === '') {
                flash_set('err', 'Bitte ein Video angeben (URL oder Datei).');
                redirect(url('admin/impressions.php?action=' . ($_POST['id'] ? 'edit&id=' . urlencode($_POST['id']) : 'new')));
            }
        } else { // text
            if ($text === '') {
                flash_set('err', 'Bitte einen Text eingeben.');
                redirect(url('admin/impressions.php?action=' . ($_POST['id'] ? 'edit&id=' . urlencode($_POST['id']) : 'new')));
            }
        }

        $entry = [
            'id'         => (string)$iid,
            'type'       => $type,
            'caption'    => $caption,
            'text'       => $type === 'text' ? $text : '',
            'src'        => $type === 'text' ? '' : $src,
            'video_kind' => $type === 'video' ? $kind : '',
        ];

        $found = false;
        foreach ($items as &$it) {
            if (($it['id'] ?? '') === $entry['id']) { $it = $entry; $found = true; break; }
        }
        unset($it);
        if (!$found) $items[] = $entry;

        if (write_json('impressions', $items)) flash_set('ok', 'Gespeichert.');
        else flash_set('err', 'Fehler beim Speichern.');
        redirect(url('admin/impressions.php'));
    }

    if ($op === 'delete') {
        $iid = $_POST['id'] ?? '';
        foreach ($items as $it) {
            if (($it['id'] ?? '') !== $iid) continue;
            $type = $it['type'] ?? '';
            $src  = (string)($it['src'] ?? '');
            $isUploadedVideo = $type === 'video' && (($it['video_kind'] ?? '') === 'upload');
            if ($type === 'image' || $isUploadedVideo) {
                maybe_unlink_asset($src, 'impressions', $iid);
            }
            break;
        }
        $items = array_values(array_filter($items, fn($it) => ($it['id'] ?? '') !== $iid));
        write_json('impressions', $items);
        flash_set('ok', 'Gelöscht.');
        redirect(url('admin/impressions.php'));
    }

    if ($op === 'bulk_delete') {
        $ids = $_POST['ids'] ?? [];
        if (!is_array($ids)) $ids = [];
        $ids = array_map('strval', $ids);
        $removed = 0;
        foreach ($items as $it) {
            if (!in_array((string)($it['id'] ?? ''), $ids, true)) continue;
            $type = $it['type'] ?? '';
            $src  = (string)($it['src'] ?? '');
            $isUploadedVideo = $type === 'video' && (($it['video_kind'] ?? '') === 'upload');
            if ($type === 'image' || $isUploadedVideo) {
                maybe_unlink_asset($src, 'impressions', (string)($it['id'] ?? ''));
            }
            $removed++;
        }
        $items = array_values(array_filter($items, fn($it) => !in_array((string)($it['id'] ?? ''), $ids, true)));
        write_json('impressions', $items);
        flash_set('ok', $removed ? "$removed Eintrag/Einträge gelöscht." : 'Nichts ausgewählt.');
        redirect(url('admin/impressions.php'));
    }

    if ($op === 'move') {
        $iid = (string)($_POST['id'] ?? '');
        $dir = (string)($_POST['dir'] ?? '');
        $idx = null;
        foreach ($items as $i => $it) if (($it['id'] ?? '') === $iid) { $idx = $i; break; }
        if ($idx !== null && in_array($dir, ['up','down'], true)) {
            $swap = $dir === 'up' ? $idx - 1 : $idx + 1;
            if ($swap >= 0 && $swap < count($items)) {
                [$items[$idx], $items[$swap]] = [$items[$swap], $items[$idx]];
                write_json('impressions', $items);
            }
        }
        redirect(url('admin/impressions.php'));
    }

    if ($op === 'bulk_upload') {
        $imgExt = ['jpg','jpeg','png','webp','avif','gif'];
        $vidExt = ['mp4','webm','mov'];
        $okCount = 0; $errCount = 0; $errors = [];

        if (!empty($_FILES['bulk_files']) && is_array($_FILES['bulk_files']['name'])) {
            $files = $_FILES['bulk_files'];
            for ($i = 0, $n = count($files['name']); $i < $n; $i++) {
                $err = $files['error'][$i] ?? UPLOAD_ERR_NO_FILE;
                if ($err === UPLOAD_ERR_NO_FILE) continue;
                $name = $files['name'][$i];
                $ext  = strtolower(pathinfo($name, PATHINFO_EXTENSION));

                if ($err !== UPLOAD_ERR_OK) {
                    $errCount++;
                    $errors[] = $name . ' (PHP-Upload-Fehler ' . (int)$err . ')';
                    continue;
                }

                $single = [
                    'name'     => $files['name'][$i],
                    'type'     => $files['type'][$i] ?? '',
                    'tmp_name' => $files['tmp_name'][$i],
                    'error'    => $err,
                    'size'     => $files['size'][$i],
                ];

                if (in_array($ext, $imgExt, true)) {
                    $up = handle_upload($single, 'img/impressions', $imgExt, 20 * 1024 * 1024);
                    if ($up) {
                        $items[] = [
                            'id'         => next_id($items),
                            'type'       => 'image',
                            'caption'    => '',
                            'text'       => '',
                            'src'        => $up,
                            'video_kind' => '',
                        ];
                        $okCount++;
                    } else { $errCount++; $errors[] = $name . ' (Bild abgelehnt)'; }
                } elseif (in_array($ext, $vidExt, true)) {
                    $up = handle_upload($single, 'video', $vidExt, 200 * 1024 * 1024);
                    if ($up) {
                        $items[] = [
                            'id'         => next_id($items),
                            'type'       => 'video',
                            'caption'    => '',
                            'text'       => '',
                            'src'        => $up,
                            'video_kind' => 'upload',
                        ];
                        $okCount++;
                    } else { $errCount++; $errors[] = $name . ' (Video abgelehnt)'; }
                } else {
                    $errCount++;
                    $errors[] = $name . ' (Format „.' . $ext . '“ nicht unterstützt)';
                }
            }
        }

        if ($okCount) write_json('impressions', $items);

        $isAjax = (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest');
        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'ok'      => $okCount,
                'errors'  => $errCount,
                'details' => $errors,
            ]);
            exit;
        }

        if ($okCount) flash_set('ok', "$okCount Datei(en) hinzugefügt.");
        if ($errCount) flash_set('err', "$errCount Datei(en) konnten nicht verarbeitet werden: " . implode('; ', $errors));
        if (!$okCount && !$errCount) flash_set('err', 'Keine Datei ausgewählt.');
        redirect(url('admin/impressions.php'));
    }
}

include __DIR__ . '/header.php';

if ($action === 'edit' || $action === 'new') {
    $editing = ['id' => '', 'type' => 'image', 'caption' => '', 'text' => '', 'src' => '', 'video_kind' => 'youtube'];
    if ($action === 'edit') {
        foreach ($items as $it) if (($it['id'] ?? '') === $id) { $editing = array_merge($editing, $it); break; }
    }
    $imgExt = ['jpg','jpeg','png','webp','avif','gif'];
    $serverImages = scan_asset_files('img', $imgExt);
    $serverVideos = scan_asset_files('video', ['mp4','webm','mov']);
    ?>
    <h1><?= $action === 'new' ? 'Neue Impression' : 'Impression bearbeiten' ?></h1>
    <form method="post" class="form" enctype="multipart/form-data">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="op" value="save">
        <input type="hidden" name="id" value="<?= e($editing['id']) ?>">
        <input type="hidden" name="existing_src" value="<?= e($editing['src']) ?>">

        <label>Typ
            <select name="type" id="impr-type">
                <option value="image" <?= $editing['type'] === 'image' ? 'selected' : '' ?>>Bild</option>
                <option value="video" <?= $editing['type'] === 'video' ? 'selected' : '' ?>>Video</option>
                <option value="text"  <?= $editing['type'] === 'text'  ? 'selected' : '' ?>>Text / Zitat</option>
            </select>
        </label>

        <fieldset class="impr-block" data-for="image">
            <legend>Bild</legend>
            <?php if ($editing['type'] === 'image' && !empty($editing['src'])): ?>
                <p class="muted">Aktuell: <code><?= e($editing['src']) ?></code></p>
                <img src="<?= e(url($editing['src'])) ?>" alt="" style="max-width:240px;border-radius:8px;margin-bottom:.5rem">
            <?php endif; ?>
            <label>Neues Bild hochladen
                <input type="file" name="image_file" accept="image/*">
            </label>
            <p class="muted" style="margin:.25rem 0">Erlaubt: jpg, jpeg, png, webp, avif, gif. Max. 20 MB.</p>
            <?php if (!empty($serverImages)): ?>
            <label>— oder vorhandene Server-Datei wählen
                <select id="srv-img" onchange="document.querySelector('[name=existing_src]').value=this.value">
                    <option value="">– Neue Datei hochladen –</option>
                    <?php foreach ($serverImages as $sf): ?>
                        <option value="<?= e($sf) ?>" <?= $editing['src']===$sf?'selected':'' ?>>
                            <?= e(ltrim(str_replace('assets/', '', $sf), '/')) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <?php endif; ?>
        </fieldset>

        <fieldset class="impr-block" data-for="video">
            <legend>Video</legend>
            <label>Quelle
                <select name="video_kind" id="impr-vkind">
                    <option value="youtube" <?= ($editing['video_kind'] ?? '') === 'youtube' ? 'selected' : '' ?>>YouTube</option>
                    <option value="vimeo"   <?= ($editing['video_kind'] ?? '') === 'vimeo'   ? 'selected' : '' ?>>Vimeo</option>
                    <option value="upload"  <?= ($editing['video_kind'] ?? '') === 'upload'  ? 'selected' : '' ?>>Eigene Datei</option>
                </select>
            </label>
            <label>URL (für YouTube/Vimeo)
                <input type="text" name="video_url"
                       value="<?= e($editing['type'] === 'video' && in_array(($editing['video_kind'] ?? ''), ['youtube','vimeo'], true) ? $editing['src'] : '') ?>"
                       placeholder="https://www.youtube.com/watch?v=...">
            </label>
            <label>Neue Datei hochladen (nur „Eigene Datei")
                <input type="file" name="video_file" accept="video/*">
            </label>
            <?php if ($editing['type'] === 'video' && ($editing['video_kind'] ?? '') === 'upload' && !empty($editing['src'])): ?>
                <p class="muted">Aktuelle Datei: <code><?= e($editing['src']) ?></code></p>
            <?php endif; ?>
            <?php if (!empty($serverVideos)): ?>
            <label>— oder vorhandene Server-Datei wählen
                <select id="srv-vid" onchange="document.querySelector('[name=existing_src]').value=this.value">
                    <option value="">– Neue Datei hochladen –</option>
                    <?php foreach ($serverVideos as $sf): ?>
                        <option value="<?= e($sf) ?>" <?= ($editing['src']===$sf && ($editing['video_kind']??'')==='upload')?'selected':'' ?>>
                            <?= e(ltrim(str_replace('assets/', '', $sf), '/')) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <?php endif; ?>
        </fieldset>

        <fieldset class="impr-block" data-for="text">
            <legend>Text / Zitat</legend>
            <label>Text
                <textarea name="text" rows="5"><?= e($editing['text'] ?? '') ?></textarea>
            </label>
        </fieldset>

        <label>Bildunterschrift / Quelle (optional)
            <input type="text" name="caption" value="<?= e($editing['caption'] ?? '') ?>" maxlength="200">
        </label>

        <button class="btn-primary" type="submit">Speichern</button>
        <a class="btn-ghost" href="<?= e(url('admin/impressions.php')) ?>">Abbrechen</a>
    </form>

    <script>
    (function () {
        var sel = document.getElementById('impr-type');
        function sync() {
            var v = sel.value;
            document.querySelectorAll('.impr-block').forEach(function (b) {
                b.style.display = (b.dataset.for === v) ? '' : 'none';
            });
        }
        sel.addEventListener('change', sync);
        sync();
    })();
    </script>
<?php } else { ?>
    <h1>Impressionen <a class="btn-primary" href="?action=new">+ Neu</a></h1>
    <p class="muted" style="margin-top:0">
        Bilder, Videos und Texte / Zitate von Live-Auftritten. Anzeigereihenfolge per ↑/↓.
    </p>

    <form id="bulk-form" method="post" enctype="multipart/form-data" class="form"
          action="<?= e(url('admin/impressions.php')) ?>"
          style="margin-bottom:1.5rem;border:1px solid rgba(255,255,255,.1);padding:1rem;border-radius:8px">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="op" value="bulk_upload">
        <strong>Schnell-Upload (Bilder &amp; Videos)</strong>
        <label style="margin-top:.5rem">Mehrere Dateien auswählen
            <input type="file" id="bulk-files" name="bulk_files[]" accept="image/*,video/*" multiple required>
        </label>
        <p class="muted" style="margin:.25rem 0 .75rem">
            Bilder: jpg, jpeg, png, webp, avif, gif (max. 20 MB).<br>
            Videos: mp4, webm, mov (max. 200 MB).<br>
            Jede Datei wird als eigener Eintrag angelegt – Caption / Reihenfolge danach pro Eintrag bearbeiten.
        </p>
        <button class="btn-primary" type="submit">Hochladen</button>
        <ul id="bulk-progress" class="bulk-progress" hidden></ul>
    </form>

    <style>
        .bulk-progress { list-style:none; padding:0; margin:1rem 0 0; display:flex; flex-direction:column; gap:.5rem; }
        .bulk-progress li {
            border:1px solid rgba(255,255,255,.12); border-radius:6px;
            padding:.5rem .75rem; background:rgba(0,0,0,.25);
            display:grid; grid-template-columns: 1fr auto; gap:.4rem .75rem; align-items:center;
        }
        .bulk-progress .bp-name { font-size:.9rem; word-break:break-all; }
        .bulk-progress .bp-status { font-size:.8rem; color:var(--gold-soft, #d4b061); white-space:nowrap; }
        .bulk-progress .bp-bar {
            grid-column: 1 / -1; height:6px; border-radius:3px;
            background:rgba(255,255,255,.08); overflow:hidden;
        }
        .bulk-progress .bp-fill {
            height:100%; width:0%; background:var(--gold, #c9a14a);
            transition: width .15s linear;
        }
        .bulk-progress li.is-done .bp-fill { background:#3aa776; width:100% !important; }
        .bulk-progress li.is-done .bp-status::before { content:"✓ "; }
        .bulk-progress li.is-error .bp-fill { background:#c0392b; }
        .bulk-progress li.is-error .bp-status::before { content:"✗ "; color:#ff8b80; }
    </style>

    <script>
    (function () {
        var form = document.getElementById('bulk-form');
        if (!form) { console.warn('[impr] bulk-form not found'); return; }
        var input = document.getElementById('bulk-files');
        var list  = document.getElementById('bulk-progress');
        var btn   = form.querySelector('button[type="submit"]');
        var csrfEl = form.querySelector('input[name="csrf"]');
        var csrf  = csrfEl ? csrfEl.value : '';
        // Action immer auf die aktuelle URL setzen (umgeht falsche BASE_URL-Probleme)
        var action = window.location.pathname + window.location.search;
        console.log('[impr] init', { action: action, hasInput: !!input, hasList: !!list, csrfLen: csrf.length });

        function fmt(bytes) {
            if (!bytes && bytes !== 0) return '';
            var u = ['B','KB','MB','GB'], i = 0;
            while (bytes >= 1024 && i < u.length - 1) { bytes /= 1024; i++; }
            return bytes.toFixed(bytes >= 10 || i === 0 ? 0 : 1) + ' ' + u[i];
        }

        function makeRow(file) {
            var li = document.createElement('li');
            li.innerHTML =
                '<span class="bp-name"></span>' +
                '<span class="bp-status">wartet…</span>' +
                '<div class="bp-bar"><div class="bp-fill"></div></div>';
            li.querySelector('.bp-name').textContent = file.name + ' (' + fmt(file.size) + ')';
            list.appendChild(li);
            return li;
        }

        function uploadOne(file) {
            return new Promise(function (resolve) {
                var li = makeRow(file);
                var fill = li.querySelector('.bp-fill');
                var status = li.querySelector('.bp-status');

                var fd = new FormData();
                fd.append('csrf', csrf);
                fd.append('op', 'bulk_upload');
                fd.append('bulk_files[]', file);

                var xhr = new XMLHttpRequest();
                xhr.open('POST', action, true);
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                xhr.setRequestHeader('Accept', 'application/json');
                xhr.upload.onprogress = function (ev) {
                    if (ev.lengthComputable) {
                        var pct = Math.round(ev.loaded / ev.total * 100);
                        fill.style.width = pct + '%';
                        status.textContent = pct + '%';
                    } else {
                        status.textContent = 'lädt…';
                    }
                };
                xhr.upload.onload = function () {
                    status.textContent = 'verarbeite…';
                };
                xhr.upload.onerror = function () {
                    li.classList.add('is-error');
                    status.textContent = 'Upload-Fehler';
                };
                xhr.onload = function () {
                    console.log('[impr] response', xhr.status, xhr.responseText.slice(0, 300));
                    var data = null;
                    try { data = JSON.parse(xhr.responseText); } catch (e) {}
                    if (xhr.status >= 200 && xhr.status < 400 && data && data.ok > 0) {
                        li.classList.add('is-done');
                        status.textContent = 'fertig';
                        fill.style.width = '100%';
                    } else {
                        li.classList.add('is-error');
                        var msg;
                        if (data && data.details && data.details[0]) msg = data.details[0];
                        else if (xhr.status === 0) msg = 'Verbindung abgebrochen';
                        else if (!data) msg = 'Server antwortete nicht mit JSON (HTTP ' + xhr.status + ')';
                        else msg = 'Fehler ' + xhr.status;
                        status.textContent = msg;
                    }
                    resolve();
                };
                xhr.onerror = function () {
                    console.error('[impr] xhr error');
                    li.classList.add('is-error');
                    status.textContent = 'Netzwerkfehler';
                    resolve();
                };
                xhr.send(fd);
            });
        }

        form.addEventListener('submit', function (e) {
            console.log('[impr] submit fired, files:', input && input.files ? input.files.length : 0);
            if (!input || !input.files || !input.files.length) {
                // nichts ausgewählt -> normales Browser-Verhalten zulassen
                return;
            }
            e.preventDefault();
            list.hidden = false;
            list.innerHTML = '';
            btn.disabled = true;

            var files = Array.from(input.files);
            (async function () {
                for (var i = 0; i < files.length; i++) {
                    await uploadOne(files[i]);
                }
                btn.disabled = false;
                if (list.querySelector('li.is-done')) {
                    setTimeout(function () { window.location.reload(); }, 1200);
                }
            })();
        });
    })();
    </script>
    <?php if (empty($items)): ?>
        <p class="muted">Noch keine Einträge.</p>
    <?php else: ?>
        <div style="margin:.75rem 0;display:flex;gap:.5rem;align-items:center;flex-wrap:wrap">
            <button type="button" id="impr-bulk-delete" class="link-danger" disabled>Selektierte löschen</button>
            <span class="muted" id="impr-bulk-count">0 ausgewählt</span>
        </div>
        <form method="post" id="impr-bulk-form" style="display:none">
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="op" value="bulk_delete">
            <div id="impr-bulk-ids"></div>
        </form>
        <table class="data" style="width:100%">
            <thead><tr>
                <th style="width:1%"><input type="checkbox" id="impr-check-all" style="width:20px;height:20px;cursor:pointer"></th>
                <th>Typ</th><th>Vorschau</th><th>Caption / Text</th><th>Reihenfolge</th><th></th>
            </tr></thead>
            <tbody>
            <?php foreach ($items as $i => $it):
                $type = $it['type'] ?? 'text';
            ?>
                <tr>
                    <td><input type="checkbox" class="impr-check" value="<?= e($it['id']) ?>" style="width:20px;height:20px;cursor:pointer"></td>
                    <td><?= e($type) ?></td>
                    <td>
                        <?php
                            $thumbStyle = 'width:90px;height:60px;object-fit:cover;border-radius:4px;cursor:zoom-in';
                            $previewKind = ''; $previewSrc = '';
                            if ($type === 'image' && !empty($it['src'])) {
                                $previewKind = 'image'; $previewSrc = url($it['src']);
                            } elseif ($type === 'video') {
                                $vk   = $it['video_kind'] ?? '';
                                $vsrc = (string)($it['src'] ?? '');
                                if ($vk === 'upload' && $vsrc !== '') { $previewKind = 'video'; $previewSrc = url($vsrc); }
                                elseif ($vk === 'youtube' || $vk === 'vimeo') { $previewKind = 'iframe'; $previewSrc = $vsrc; }
                            }
                        ?>
                        <?php if ($type === 'image' && !empty($it['src'])): ?>
                            <img src="<?= e(url($it['src'])) ?>" alt="" style="<?= $thumbStyle ?>"
                                 class="impr-preview" data-kind="image" data-src="<?= e($previewSrc) ?>">
                        <?php elseif ($type === 'video'):
                            $vk    = $it['video_kind'] ?? '';
                            $vsrc  = (string)($it['src'] ?? '');
                            $thumb = '';
                            if ($vk === 'youtube' && preg_match('#/embed/([A-Za-z0-9_-]+)#', $vsrc, $m)) {
                                $thumb = 'https://img.youtube.com/vi/' . $m[1] . '/mqdefault.jpg';
                            } elseif ($vk === 'vimeo' && preg_match('#/video/(\d+)#', $vsrc, $m)) {
                                $vdata = @file_get_contents('https://vimeo.com/api/v2/video/' . $m[1] . '.json');
                                if ($vdata && ($j = json_decode($vdata, true)) && !empty($j[0]['thumbnail_medium'])) {
                                    $thumb = $j[0]['thumbnail_medium'];
                                }
                            }
                        ?>
                            <?php if ($thumb): ?>
                                <img src="<?= e($thumb) ?>" alt="" style="<?= $thumbStyle ?>"
                                     class="impr-preview" data-kind="<?= e($previewKind) ?>" data-src="<?= e($previewSrc) ?>">
                            <?php elseif ($vk === 'upload' && $vsrc): ?>
                                <video src="<?= e(url($vsrc)) ?>#t=0.5" preload="metadata" muted playsinline
                                       style="<?= $thumbStyle ?>;background:#000"
                                       class="impr-preview" data-kind="video" data-src="<?= e($previewSrc) ?>"></video>
                            <?php else: ?>
                                <span class="muted"><?= e($vk) ?></span><br>
                                <small style="word-break:break-all"><?= e($vsrc) ?></small>
                            <?php endif; ?>
                        <?php else: ?>
                            <em class="muted"><?= e(mb_strimwidth((string)($it['text'] ?? ''), 0, 80, '…', 'UTF-8')) ?></em>
                        <?php endif; ?>
                    </td>
                    <td><?= e($it['caption'] ?? '') ?></td>
                    <td style="white-space:nowrap">
                        <form method="post" style="display:inline">
                            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="op" value="move">
                            <input type="hidden" name="id" value="<?= e($it['id']) ?>">
                            <input type="hidden" name="dir" value="up">
                            <button type="submit" class="btn-secondary" <?= $i === 0 ? 'disabled' : '' ?>>↑</button>
                        </form>
                        <form method="post" style="display:inline">
                            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="op" value="move">
                            <input type="hidden" name="id" value="<?= e($it['id']) ?>">
                            <input type="hidden" name="dir" value="down">
                            <button type="submit" class="btn-secondary" <?= $i === count($items) - 1 ? 'disabled' : '' ?>>↓</button>
                        </form>
                    </td>
                    <td style="white-space:nowrap">
                        <a href="?action=edit&id=<?= e($it['id']) ?>">Bearbeiten</a>
                        <form method="post" style="display:inline" onsubmit="return confirm('Wirklich löschen?')">
                            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="op" value="delete">
                            <input type="hidden" name="id" value="<?= e($it['id']) ?>">
                            <button class="link-danger" type="submit">Löschen</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <script>
        (function () {
            var all   = document.getElementById('impr-check-all');
            var boxes = Array.prototype.slice.call(document.querySelectorAll('.impr-check'));
            var btn   = document.getElementById('impr-bulk-delete');
            var count = document.getElementById('impr-bulk-count');
            var form  = document.getElementById('impr-bulk-form');
            var ids   = document.getElementById('impr-bulk-ids');
            function update() {
                var checked = boxes.filter(function (b) { return b.checked; });
                btn.disabled = checked.length === 0;
                count.textContent = checked.length + ' ausgewählt';
                if (all) all.checked = checked.length === boxes.length && boxes.length > 0;
            }
            if (all) all.addEventListener('change', function () {
                boxes.forEach(function (b) { b.checked = all.checked; });
                update();
            });
            boxes.forEach(function (b) { b.addEventListener('change', update); });
            btn.addEventListener('click', function () {
                var checked = boxes.filter(function (b) { return b.checked; });
                if (!checked.length) return;
                if (!confirm('Wirklich ' + checked.length + ' Eintrag/Einträge löschen?')) return;
                ids.innerHTML = '';
                checked.forEach(function (b) {
                    var inp = document.createElement('input');
                    inp.type = 'hidden';
                    inp.name = 'ids[]';
                    inp.value = b.value;
                    ids.appendChild(inp);
                });
                form.submit();
            });
        })();
        </script>
        <div id="impr-lightbox" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.85);z-index:9999;align-items:center;justify-content:center;padding:2rem">
            <button type="button" id="impr-lightbox-close" aria-label="Schließen"
                    style="position:absolute;top:1rem;right:1rem;background:transparent;border:0;color:#fff;font-size:2.5rem;line-height:1;cursor:pointer">&times;</button>
            <div id="impr-lightbox-body" style="max-width:70vw;max-height:70vh;display:flex;align-items:center;justify-content:center"></div>
        </div>
        <script>
        (function () {
            var lb    = document.getElementById('impr-lightbox');
            var body  = document.getElementById('impr-lightbox-body');
            var close = document.getElementById('impr-lightbox-close');
            function open(kind, src) {
                body.innerHTML = '';
                var el;
                if (kind === 'image') {
                    el = document.createElement('img');
                    el.src = src;
                    el.style.cssText = 'max-width:70vw;max-height:70vh;object-fit:contain;border-radius:6px;box-shadow:0 8px 32px rgba(0,0,0,.6)';
                } else if (kind === 'video') {
                    el = document.createElement('video');
                    el.src = src;
                    el.controls = true;
                    el.autoplay = true;
                    el.style.cssText = 'max-width:70vw;max-height:70vh;background:#000;border-radius:6px;box-shadow:0 8px 32px rgba(0,0,0,.6)';
                } else if (kind === 'iframe') {
                    el = document.createElement('iframe');
                    el.src = src + (src.indexOf('?') === -1 ? '?' : '&') + 'autoplay=1';
                    el.allow = 'autoplay; encrypted-media; picture-in-picture';
                    el.allowFullscreen = true;
                    el.style.cssText = 'width:min(70vw,960px);aspect-ratio:16/9;border:0;border-radius:6px;background:#000;box-shadow:0 8px 32px rgba(0,0,0,.6)';
                }
                if (el) body.appendChild(el);
                lb.style.display = 'flex';
            }
            function hide() {
                lb.style.display = 'none';
                body.innerHTML = '';
            }
            document.querySelectorAll('.impr-preview').forEach(function (n) {
                n.addEventListener('click', function (e) {
                    e.preventDefault();
                    var k = n.getAttribute('data-kind');
                    var s = n.getAttribute('data-src');
                    if (k && s) open(k, s);
                });
            });
            close.addEventListener('click', hide);
            lb.addEventListener('click', function (e) { if (e.target === lb) hide(); });
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape' && lb.style.display === 'flex') hide();
            });
        })();
        </script>
    <?php endif; ?>
<?php }
include __DIR__ . '/footer.php';
