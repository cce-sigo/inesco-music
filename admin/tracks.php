<?php
require_once __DIR__ . '/auth.php';
$pageTitle = 'Audio';

$tracks = read_json('tracks');
$action = $_GET['action'] ?? 'list';
$id     = $_GET['id'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['csrf'] ?? null)) { flash_set('err','Sicherheitstoken ungültig.'); redirect(url('admin/tracks.php')); }
    $op = $_POST['op'] ?? '';

    if ($op === 'save') {
        $tid    = $_POST['id'] ?: next_id($tracks);
        $title  = trim((string)$_POST['title']);
        $artist = trim((string)$_POST['artist']) ?: 'INESCO';
        $file   = $_POST['existing_file'] ?? '';
        $cover  = $_POST['existing_cover'] ?? 'assets/img/logo.png';

        if (!empty($_FILES['file']['name'])) {
            $up = handle_upload($_FILES['file'], 'audio', ['mp3','m4a','ogg','wav'], 30 * 1024 * 1024);
            if ($up) $file = $up;
        }
        if (!empty($_FILES['cover']['name'])) {
            $up = handle_upload($_FILES['cover'], 'img', ['jpg','jpeg','png','webp']);
            if ($up) $cover = $up;
        }

        $found = false;
        foreach ($tracks as &$t) if (($t['id'] ?? '') === $tid) {
            $t = ['id'=>$tid,'title'=>$title,'artist'=>$artist,'file'=>$file,'cover'=>$cover];
            $found = true; break;
        }
        unset($t);
        if (!$found) $tracks[] = ['id'=>$tid,'title'=>$title,'artist'=>$artist,'file'=>$file,'cover'=>$cover];

        write_json('tracks', $tracks);
        flash_set('ok','Gespeichert.');
        redirect(url('admin/tracks.php'));
    }
    if ($op === 'delete') {
        $tid = $_POST['id'] ?? '';
        foreach ($tracks as $t) {
            if (($t['id'] ?? '') !== $tid) continue;
            maybe_unlink_asset($t['file']  ?? '', 'tracks', $tid);
            maybe_unlink_asset($t['cover'] ?? '', 'tracks', $tid);
            break;
        }
        $tracks = array_values(array_filter($tracks, fn($t) => ($t['id'] ?? '') !== $tid));
        write_json('tracks', $tracks);
        flash_set('ok','Gelöscht.');
        redirect(url('admin/tracks.php'));
    }
}

include __DIR__ . '/header.php';

if ($action === 'edit' || $action === 'new') {
    $editing = ['id'=>'','title'=>'','artist'=>'INESCO','file'=>'','cover'=>''];
    if ($action === 'edit') foreach ($tracks as $t) if (($t['id']??'') === $id) { $editing = $t; break; }
    $serverAudio = scan_asset_files('audio', ['mp3','m4a','ogg','wav']);
    $serverImages = scan_asset_files('img', ['jpg','jpeg','png','webp','avif','gif']);
    ?>
    <h1><?= $action==='new' ? 'Neuer Track' : 'Track bearbeiten' ?></h1>
    <form method="post" class="form" enctype="multipart/form-data">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="op" value="save">
        <input type="hidden" name="id" value="<?= e($editing['id']) ?>">
        <input type="hidden" name="existing_file" value="<?= e($editing['file']) ?>">
        <input type="hidden" name="existing_cover" value="<?= e($editing['cover']) ?>">
        <label>Titel<input type="text" name="title" value="<?= e($editing['title']) ?>" required></label>
        <label>Artist<input type="text" name="artist" value="<?= e($editing['artist']) ?>"></label>
        <label>Neue Audiodatei hochladen (MP3/OGG/M4A/WAV)<input type="file" name="file" accept="audio/*"></label>
        <?php if ($editing['file']): ?><p class="muted">Aktuell: <?= e($editing['file']) ?></p><?php endif; ?>
        <?php if (!empty($serverAudio)): ?>
        <label>— oder vorhandene Server-Datei wählen
            <select onchange="document.querySelector('[name=existing_file]').value=this.value">
                <option value="">– Neue Datei hochladen –</option>
                <?php foreach ($serverAudio as $sf): ?>
                    <option value="<?= e($sf) ?>" <?= $editing['file']===$sf?'selected':'' ?>>
                        <?= e(ltrim(str_replace('assets/', '', $sf), '/')) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <?php endif; ?>
        <label>Neues Cover hochladen (optional)<input type="file" name="cover" accept="image/*"></label>
        <?php if ($editing['cover']): ?><p><img src="<?= e(url($editing['cover'])) ?>" style="height:60px;border-radius:6px"></p><?php endif; ?>
        <?php if (!empty($serverImages)): ?>
        <label>— oder vorhandenes Cover aus Server-Dateien wählen
            <select onchange="document.querySelector('[name=existing_cover]').value=this.value">
                <option value="">– Neue Datei hochladen –</option>
                <?php foreach ($serverImages as $sf): ?>
                    <option value="<?= e($sf) ?>" <?= $editing['cover']===$sf?'selected':'' ?>>
                        <?= e(ltrim(str_replace('assets/', '', $sf), '/')) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <?php endif; ?>
        <button class="btn-primary" type="submit">Speichern</button>
        <a class="btn-ghost" href="tracks.php">Abbrechen</a>
    </form>
<?php } else { ?>
    <h1>Audio-Tracks <a class="btn-primary" href="?action=new">+ Neu</a></h1>
    <table class="data">
        <thead><tr><th>Cover</th><th>Titel</th><th>Artist</th><th>Datei</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($tracks as $t): ?>
            <tr>
                <td><?php if (!empty($t['cover'])): ?><img src="<?= e(url($t['cover'])) ?>" style="height:40px"><?php endif; ?></td>
                <td><?= e($t['title']) ?></td>
                <td><?= e($t['artist'] ?? '') ?></td>
                <td class="muted"><?= e(basename($t['file'] ?? '')) ?></td>
                <td>
                    <a href="?action=edit&id=<?= e($t['id']) ?>">Bearbeiten</a>
                    <form method="post" style="display:inline" onsubmit="return confirm('Löschen?')">
                        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="op" value="delete">
                        <input type="hidden" name="id" value="<?= e($t['id']) ?>">
                        <button class="link-danger" type="submit">Löschen</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php }
include __DIR__ . '/footer.php';
