<?php
require_once __DIR__ . '/auth.php';
$pageTitle = 'Videos';

$videos = read_json('videos');
$action = $_GET['action'] ?? 'list';
$id     = $_GET['id'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['csrf'] ?? null)) { flash_set('err','Sicherheitstoken ungültig.'); redirect(url('admin/videos.php')); }
    $op = $_POST['op'] ?? '';

    if ($op === 'save') {
        $vid   = $_POST['id'] ?: next_id($videos);
        $title = trim((string)$_POST['title']);
        $type  = $_POST['type'] ?? 'youtube';
        $src   = trim((string)$_POST['src']);
        $thumb = trim((string)($_POST['existing_thumb'] ?? ''));

        // canvas-extracted frame (base64 JPEG)
        $thumbData = trim((string)($_POST['thumb_data'] ?? ''));
        if ($thumbData !== '' && str_starts_with($thumbData, 'data:image/jpeg;base64,')) {
            $jpeg = base64_decode(substr($thumbData, strlen('data:image/jpeg;base64,')), true);
            if ($jpeg !== false && strlen($jpeg) > 0) {
                $fname = 'thumb-' . substr(bin2hex(random_bytes(4)), 0, 6) . '.jpg';
                $tDir  = UPLOAD_PATH . '/img';
                if (!is_dir($tDir)) @mkdir($tDir, 0775, true);
                if (file_put_contents($tDir . '/' . $fname, $jpeg) !== false) {
                    $thumb = 'assets/img/' . $fname;
                }
            }
        } elseif (!empty($_FILES['thumbnail']['name'])) {
            $up = handle_upload($_FILES['thumbnail'], 'img', ['jpg','jpeg','png','webp','avif'], 5 * 1024 * 1024);
            if ($up) $thumb = $up;
        }

        if ($type === 'youtube') {
            if (preg_match('#(?:youtube\.com/watch\?v=|youtu\.be/)([\w-]{6,})#', $src, $m)) {
                $src = 'https://www.youtube.com/embed/' . $m[1];
            }
        } elseif ($type === 'vimeo') {
            if (preg_match('#vimeo\.com/(\d+)#', $src, $m)) {
                $src = 'https://player.vimeo.com/video/' . $m[1];
            }
        } elseif ($type === 'upload') {
            if (!empty($_FILES['file']['name'])) {
                $up = handle_upload($_FILES['file'], 'video', ['mp4','webm','mov'], 200 * 1024 * 1024);
                if ($up) $src = $up;
            } else {
                $src = $_POST['existing_src'] ?? '';
            }
        }

        $found = false;
        foreach ($videos as &$v) if (($v['id']??'') === $vid) {
            $v = ['id'=>$vid,'title'=>$title,'type'=>$type,'src'=>$src,'thumbnail'=>$thumb];
            $found = true; break;
        }
        unset($v);
        if (!$found) $videos[] = ['id'=>$vid,'title'=>$title,'type'=>$type,'src'=>$src,'thumbnail'=>$thumb];

        write_json('videos', $videos);
        flash_set('ok','Gespeichert.');
        redirect(url('admin/videos.php'));
    }
    if ($op === 'delete') {
        $vid = $_POST['id'] ?? '';
        foreach ($videos as $v) {
            if (($v['id'] ?? '') !== $vid) continue;
            if (($v['type'] ?? '') === 'upload') maybe_unlink_asset($v['src'] ?? '', 'videos', $vid);
            maybe_unlink_asset($v['thumbnail'] ?? '', 'videos', $vid);
            break;
        }
        $videos = array_values(array_filter($videos, fn($v) => ($v['id'] ?? '') !== $vid));
        write_json('videos', $videos);
        flash_set('ok','Gelöscht.');
        redirect(url('admin/videos.php'));
    }
}

include __DIR__ . '/header.php';

if ($action === 'edit' || $action === 'new') {
    $editing = ['id'=>'','title'=>'','type'=>'youtube','src'=>'','thumbnail'=>''];
    if ($action === 'edit') foreach ($videos as $v) if (($v['id']??'') === $id) { $editing = $v; break; }
    $serverVideos  = scan_asset_files('video', ['mp4','webm','mov']);
    $serverImages  = scan_asset_files('img', ['jpg','jpeg','png','webp','avif','gif']);
    ?>
    <h1><?= $action==='new' ? 'Neues Video' : 'Video bearbeiten' ?></h1>
    <form method="post" class="form" enctype="multipart/form-data">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="op" value="save">
        <input type="hidden" name="id" value="<?= e($editing['id']) ?>">
        <input type="hidden" name="existing_src" value="<?= e($editing['src']) ?>">
        <input type="hidden" name="existing_thumb" value="<?= e($editing['thumbnail'] ?? '') ?>">
        <input type="hidden" name="thumb_data" id="thumb-data">
        <label>Titel<input type="text" name="title" value="<?= e($editing['title']) ?>" required></label>
        <label>Quelle
            <select id="vid-type" name="type">
                <option value="youtube" <?= $editing['type']==='youtube'?'selected':'' ?>>YouTube</option>
                <option value="vimeo"   <?= $editing['type']==='vimeo'?'selected':'' ?>>Vimeo</option>
                <option value="upload"  <?= $editing['type']==='upload'?'selected':'' ?>>Eigene Datei</option>
            </select>
        </label>
        <label>URL (für YouTube/Vimeo) – Watch- oder Embed-Link
            <input id="vid-url" type="text" name="src" value="<?= e(in_array($editing['type'],['youtube','vimeo'],true)?$editing['src']:'') ?>">
        </label>
        <label>Neue Datei hochladen (nur bei „Eigene Datei")<input type="file" name="file" accept="video/*"></label>
        <?php if (!empty($serverVideos)): ?>
        <label>— oder vorhandene Server-Datei vom Server wählen
            <select id="srv-vid-sel" onchange="vidPickServer(this.value)">
                <option value="">– Neue Datei hochladen –</option>
                <?php foreach ($serverVideos as $sf): ?>
                    <option value="<?= e($sf) ?>" <?= ($editing['src']===$sf && $editing['type']==='upload')?'selected':'' ?>>
                        <?= e(ltrim(str_replace('assets/', '', $sf), '/')) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <?php endif; ?>
        <fieldset style="border:1px solid rgba(255,255,255,.12);border-radius:6px;padding:.75rem 1rem;margin-top:.5rem">
            <legend>Thumbnail (Vorschaubild)</legend>
            <?php $curThumb = $editing['thumbnail'] ?? ''; ?>
            <img id="thumb-preview"
                 src="<?= $curThumb ? e(preg_match('#^https?://#',$curThumb)?$curThumb:url($curThumb)) : '' ?>"
                 style="<?= $curThumb ? '' : 'display:none;' ?>height:80px;border-radius:4px;margin-bottom:.5rem;display:block">
            <video id="thumb-vid" style="display:none" muted crossorigin="anonymous"></video>
            <canvas id="thumb-canvas" style="display:none"></canvas>
            <div style="display:flex;gap:.75rem;flex-wrap:wrap;align-items:center;margin-top:.25rem">
                <button type="button" class="btn-ghost" id="thumb-extract-btn" style="display:none"
                        onclick="extractThumb()">&#x1F4F7; Frame extrahieren</button>
                <label style="margin:0">oder Bild hochladen
                    <input type="file" name="thumbnail" accept="image/*" onchange="previewThumbFile(this)">
                </label>
            </div>
            <?php if (!empty($serverImages)): ?>
            <label style="margin-top:.5rem">— oder Serverdatei wählen
                <select onchange="document.querySelector('[name=existing_thumb]').value=this.value;showThumb(this.value?'<?= e(BASE_URL) ?>'+this.value:'')">
                    <option value="">– keine Serverdatei –</option>
                    <?php foreach ($serverImages as $sf): ?>
                        <option value="<?= e($sf) ?>" <?= $curThumb===$sf?'selected':'' ?>>
                            <?= e(ltrim(str_replace('assets/','', $sf), '/')) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <?php endif; ?>
            <p class="muted" style="margin:.5rem 0 0;font-size:.8rem">Bei YouTube wird automatisch das YouTube-Thumbnail verwendet, wenn kein eigenes Bild gesetzt ist.</p>
        </fieldset>
        <button class="btn-primary" type="submit">Speichern</button>
        <a class="btn-ghost" href="videos.php">Abbrechen</a>
    </form>
    <script>
    var _vidObjectURL = null;

    function vidPickServer(val) {
        document.querySelector('[name=existing_src]').value = val;
        if (val) {
            document.getElementById('vid-type').value = 'upload';
            document.getElementById('vid-url').value = '';
            // load video for frame extraction
            loadVidForThumb('<?= e(BASE_URL) ?>' + val);
        }
    }

    // called when a new video file is selected via the file input
    document.addEventListener('DOMContentLoaded', function () {
        var fileInput = document.querySelector('input[name="file"]');
        if (fileInput) fileInput.addEventListener('change', function () {
            if (!this.files || !this.files[0]) return;
            if (_vidObjectURL) URL.revokeObjectURL(_vidObjectURL);
            _vidObjectURL = URL.createObjectURL(this.files[0]);
            loadVidForThumb(_vidObjectURL);
        });
    });

    function loadVidForThumb(src) {
        var vid = document.getElementById('thumb-vid');
        var btn = document.getElementById('thumb-extract-btn');
        if (!vid) return;
        vid.src = src;
        vid.load();
        vid.addEventListener('loadeddata', function onLoaded() {
            vid.removeEventListener('loadeddata', onLoaded);
            if (btn) btn.style.display = '';
        }, { once: true });
    }

    function extractThumb() {
        var vid    = document.getElementById('thumb-vid');
        var canvas = document.getElementById('thumb-canvas');
        var data   = document.getElementById('thumb-data');
        var prev   = document.getElementById('thumb-preview');
        if (!vid || !canvas) return;
        // seek to 1s (or 10% of duration)
        var seekTo = Math.min(1, vid.duration * 0.1 || 1);
        vid.currentTime = seekTo;
        vid.addEventListener('seeked', function onSeeked() {
            vid.removeEventListener('seeked', onSeeked);
            canvas.width  = vid.videoWidth  || 1280;
            canvas.height = vid.videoHeight || 720;
            var ctx = canvas.getContext('2d');
            ctx.drawImage(vid, 0, 0, canvas.width, canvas.height);
            var jpg = canvas.toDataURL('image/jpeg', 0.85);
            data.value = jpg;
            // clear file upload and server-select so only canvas data is used
            document.querySelector('[name=existing_thumb]').value = '';
            // show preview
            showThumb(jpg);
        }, { once: true });
    }

    function showThumb(src) {
        var prev = document.getElementById('thumb-preview');
        if (!prev) return;
        if (src) { prev.src = src; prev.style.display = 'block'; }
        else      { prev.style.display = 'none'; }
    }

    function previewThumbFile(input) {
        if (!input.files || !input.files[0]) return;
        document.getElementById('thumb-data').value = '';
        document.querySelector('[name=existing_thumb]').value = '';
        var reader = new FileReader();
        reader.onload = function (e) { showThumb(e.target.result); };
        reader.readAsDataURL(input.files[0]);
    }
    </script>
<?php } else { ?>
    <h1>Videos <a class="btn-primary" href="?action=new">+ Neu</a></h1>
    <table class="data">
        <thead><tr><th>Titel</th><th>Typ</th><th>Quelle</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($videos as $v): ?>
            <tr>
                <td><?= e($v['title']) ?></td>
                <td><?= e($v['type']) ?></td>
                <td class="muted" style="word-break:break-all"><?= e($v['src']) ?></td>
                <td>
                    <a href="?action=edit&id=<?= e($v['id']) ?>">Bearbeiten</a>
                    <form method="post" style="display:inline" onsubmit="return confirm('Löschen?')">
                        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="op" value="delete">
                        <input type="hidden" name="id" value="<?= e($v['id']) ?>">
                        <button class="link-danger" type="submit">Löschen</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php }
include __DIR__ . '/footer.php';
