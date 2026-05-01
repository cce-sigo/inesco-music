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
            $v = ['id'=>$vid,'title'=>$title,'type'=>$type,'src'=>$src];
            $found = true; break;
        }
        unset($v);
        if (!$found) $videos[] = ['id'=>$vid,'title'=>$title,'type'=>$type,'src'=>$src];

        write_json('videos', $videos);
        flash_set('ok','Gespeichert.');
        redirect(url('admin/videos.php'));
    }
    if ($op === 'delete') {
        $vid = $_POST['id'] ?? '';
        $videos = array_values(array_filter($videos, fn($v) => ($v['id'] ?? '') !== $vid));
        write_json('videos', $videos);
        flash_set('ok','Gelöscht.');
        redirect(url('admin/videos.php'));
    }
}

include __DIR__ . '/header.php';

if ($action === 'edit' || $action === 'new') {
    $editing = ['id'=>'','title'=>'','type'=>'youtube','src'=>''];
    if ($action === 'edit') foreach ($videos as $v) if (($v['id']??'') === $id) { $editing = $v; break; }
    ?>
    <h1><?= $action==='new' ? 'Neues Video' : 'Video bearbeiten' ?></h1>
    <form method="post" class="form" enctype="multipart/form-data">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="op" value="save">
        <input type="hidden" name="id" value="<?= e($editing['id']) ?>">
        <input type="hidden" name="existing_src" value="<?= e($editing['src']) ?>">
        <label>Titel<input type="text" name="title" value="<?= e($editing['title']) ?>" required></label>
        <label>Quelle
            <select name="type">
                <option value="youtube" <?= $editing['type']==='youtube'?'selected':'' ?>>YouTube</option>
                <option value="vimeo"   <?= $editing['type']==='vimeo'?'selected':'' ?>>Vimeo</option>
                <option value="upload"  <?= $editing['type']==='upload'?'selected':'' ?>>Eigene Datei</option>
            </select>
        </label>
        <label>URL (für YouTube/Vimeo) – Watch- oder Embed-Link
            <input type="text" name="src" value="<?= e($editing['src']) ?>">
        </label>
        <label>Datei (nur bei „Eigene Datei“)<input type="file" name="file" accept="video/*"></label>
        <button class="btn-primary" type="submit">Speichern</button>
        <a class="btn-ghost" href="videos.php">Abbrechen</a>
    </form>
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
