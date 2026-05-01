<?php
require_once __DIR__ . '/auth.php';
$pageTitle = 'Konzerte';

$concerts = read_json('concerts');
$action   = $_GET['action'] ?? 'list';
$id       = $_GET['id'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['csrf'] ?? null)) { flash_set('err','Sicherheitstoken ungültig.'); redirect(url('admin/concerts.php')); }
    $op = $_POST['op'] ?? '';

    if ($op === 'save') {
        $cid = $_POST['id'] ?: next_id($concerts);
        $rec = [
            'id'          => $cid,
            'date'        => trim((string)$_POST['date']),
            'time'        => trim((string)$_POST['time']),
            'venue'       => trim((string)$_POST['venue']),
            'city'        => trim((string)$_POST['city']),
            'description' => trim((string)$_POST['description']),
        ];
        $found = false;
        foreach ($concerts as &$c) if (($c['id']??'') === $cid) { $c = $rec; $found = true; break; }
        unset($c);
        if (!$found) $concerts[] = $rec;
        $concerts = sort_concerts($concerts, false);
        write_json('concerts', $concerts);
        flash_set('ok','Gespeichert.');
        redirect(url('admin/concerts.php'));
    }
    if ($op === 'delete') {
        $cid = $_POST['id'] ?? '';
        $concerts = array_values(array_filter($concerts, fn($c) => ($c['id'] ?? '') !== $cid));
        write_json('concerts', $concerts);
        flash_set('ok','Gelöscht.');
        redirect(url('admin/concerts.php'));
    }
}

include __DIR__ . '/header.php';

if ($action === 'edit' || $action === 'new') {
    $editing = ['id'=>'','date'=>'','time'=>'20:00','venue'=>'','city'=>'','description'=>''];
    if ($action === 'edit') foreach ($concerts as $c) if (($c['id']??'') === $id) { $editing = $c; break; }
    ?>
    <h1><?= $action==='new' ? 'Neuer Konzerttermin' : 'Termin bearbeiten' ?></h1>
    <form method="post" class="form">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="op" value="save">
        <input type="hidden" name="id" value="<?= e($editing['id']) ?>">
        <div class="grid-2">
            <label>Datum<input type="date" name="date" value="<?= e($editing['date']) ?>" required></label>
            <label>Uhrzeit<input type="time" name="time" value="<?= e($editing['time']) ?>"></label>
            <label>Venue<input type="text" name="venue" value="<?= e($editing['venue']) ?>" required></label>
            <label>Stadt<input type="text" name="city" value="<?= e($editing['city']) ?>" required></label>
        </div>
        <label>Beschreibung<textarea name="description" rows="4"><?= e($editing['description']) ?></textarea></label>
        <button class="btn-primary" type="submit">Speichern</button>
        <a class="btn-ghost" href="concerts.php">Abbrechen</a>
    </form>
<?php } else {
    $sorted = sort_concerts($concerts, false);
    ?>
    <h1>Konzerte <a class="btn-primary" href="?action=new">+ Neu</a></h1>
    <table class="data">
        <thead><tr><th>Datum</th><th>Zeit</th><th>Venue</th><th>Stadt</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($sorted as $c): ?>
            <tr class="<?= ($c['date'] < date('Y-m-d')) ? 'past' : '' ?>">
                <td><?= e($c['date']) ?></td>
                <td><?= e($c['time'] ?? '') ?></td>
                <td><?= e($c['venue']) ?></td>
                <td><?= e($c['city']) ?></td>
                <td>
                    <a href="?action=edit&id=<?= e($c['id']) ?>">Bearbeiten</a>
                    <form method="post" style="display:inline" onsubmit="return confirm('Löschen?')">
                        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="op" value="delete">
                        <input type="hidden" name="id" value="<?= e($c['id']) ?>">
                        <button class="link-danger" type="submit">Löschen</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php }
include __DIR__ . '/footer.php';
