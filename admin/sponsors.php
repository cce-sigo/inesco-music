<?php
require_once __DIR__ . '/auth.php';
$pageTitle = 'Freunde & Sponsoren';

$sponsors = read_json('sponsors');
$action   = $_GET['action'] ?? 'list';
$id       = $_GET['id'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['csrf'] ?? null)) {
        flash_set('err', 'Sicherheitstoken ungültig.');
        redirect(url('admin/sponsors.php'));
    }
    $op = $_POST['op'] ?? '';

    if ($op === 'save') {
        $sid       = trim((string)($_POST['id'] ?? ''));
        $group     = in_array($_POST['group'] ?? '', ['freunde', 'sponsoren'], true) ? $_POST['group'] : 'sponsoren';
        $name      = trim((string)($_POST['name'] ?? ''));
        $urlVal    = trim((string)($_POST['url'] ?? ''));
        $text      = trim((string)($_POST['text'] ?? ''));
        $logoPath  = trim((string)($_POST['existing_logo'] ?? ''));

        // Basic URL validation
        if ($urlVal !== '' && !filter_var($urlVal, FILTER_VALIDATE_URL)) {
            flash_set('err', 'Ungültige URL.');
            redirect(url('admin/sponsors.php?action=' . ($sid ? 'edit&id=' . urlencode($sid) : 'new')));
        }

        if (!empty($_FILES['logo']['name'])) {
            $up = handle_upload($_FILES['logo'], 'img/sponsors', ['jpg','jpeg','png','webp','svg']);
            if ($up) $logoPath = $up;
        }

        $visible = isset($_POST['visible']) ? true : false;

        $entry = [
            'id'      => $sid,
            'group'   => $group,
            'name'    => $name,
            'url'     => $urlVal,
            'logo'    => $logoPath,
            'text'    => $text,
            'visible' => $visible,
        ];

        $found = false;
        foreach ($sponsors as &$s) {
            if (($s['id'] ?? '') === $sid && $sid !== '') {
                $s = $entry;
                $found = true;
                break;
            }
        }
        unset($s);
        if (!$found) {
            $entry['id'] = next_id($sponsors);
            $sponsors[] = $entry;
        }
        write_json('sponsors', $sponsors);
        flash_set('ok', 'Gespeichert.');
        redirect(url('admin/sponsors.php'));
    }

    if ($op === 'delete') {
        $did = (string)($_POST['id'] ?? '');
        $sponsors = array_values(array_filter($sponsors, fn($s) => ($s['id'] ?? '') !== $did));
        write_json('sponsors', $sponsors);
        flash_set('ok', 'Gelöscht.');
        redirect(url('admin/sponsors.php'));
    }

    if ($op === 'toggle') {
        $tid = (string)($_POST['id'] ?? '');
        foreach ($sponsors as &$s) {
            if (($s['id'] ?? '') === $tid) {
                $s['visible'] = !(($s['visible'] ?? true));
                break;
            }
        }
        unset($s);
        write_json('sponsors', $sponsors);
        redirect(url('admin/sponsors.php'));
    }

    if ($op === 'move') {
        $mid = (string)($_POST['id'] ?? '');
        $dir = (string)($_POST['dir'] ?? '');
        foreach ($sponsors as $i => $s) {
            if (($s['id'] ?? '') === $mid) {
                $swap = $dir === 'up' ? $i - 1 : $i + 1;
                if ($swap >= 0 && $swap < count($sponsors)) {
                    [$sponsors[$i], $sponsors[$swap]] = [$sponsors[$swap], $sponsors[$i]];
                }
                break;
            }
        }
        $sponsors = array_values($sponsors);
        write_json('sponsors', $sponsors);
        redirect(url('admin/sponsors.php'));
    }
}

include __DIR__ . '/header.php';

if ($action === 'edit' || $action === 'new') {
    $editing = ['id' => '', 'group' => 'sponsoren', 'name' => '', 'url' => '', 'logo' => '', 'text' => ''];
    if ($action === 'edit') {
        foreach ($sponsors as $s) if (($s['id'] ?? '') === $id) { $editing = $s; break; }
    }
    ?>
    <h1><?= $action === 'new' ? 'Neuer Eintrag' : 'Eintrag bearbeiten' ?></h1>
    <form method="post" class="form" enctype="multipart/form-data">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="op" value="save">
        <input type="hidden" name="id" value="<?= e($editing['id']) ?>">
        <input type="hidden" name="existing_logo" value="<?= e($editing['logo']) ?>">

        <label style="display:inline-flex;align-items:center;gap:.5rem;cursor:pointer;margin-bottom:1rem;text-transform:none;letter-spacing:0;font-size:.9rem;color:var(--text)">
            <input type="checkbox" name="visible" value="1" <?= ($editing['visible'] ?? true) ? 'checked' : '' ?> style="width:16px;height:16px;flex-shrink:0;margin:0;cursor:pointer;accent-color:var(--gold)">
            Auf Website anzeigen
        </label>

        <label>Gruppe
            <select name="group">
                <option value="freunde"   <?= $editing['group'] === 'freunde'   ? 'selected' : '' ?>>Freunde</option>
                <option value="sponsoren" <?= $editing['group'] === 'sponsoren' ? 'selected' : '' ?>>Sponsoren</option>
            </select>
        </label>
        <label>Name<input type="text" name="name" value="<?= e($editing['name']) ?>" required maxlength="120"></label>
        <label>Website-URL<input type="url" name="url" value="<?= e($editing['url']) ?>" maxlength="300" placeholder="https://"></label>
        <label>Text / Kurzbeschreibung<textarea name="text" rows="4" maxlength="500"><?= e($editing['text']) ?></textarea></label>
        <label>Logo hochladen<input type="file" name="logo" accept="image/*,.svg"></label>
        <?php if (!empty($editing['logo'])): ?>
            <p>Aktuell: <img src="<?= e(url($editing['logo'])) ?>" style="height:60px;max-width:160px;object-fit:contain;vertical-align:middle;background:#fff;padding:4px;border-radius:4px"></p>
        <?php endif; ?>
        <button class="btn-primary" type="submit">Speichern</button>
        <a class="btn-ghost" href="sponsors.php">Abbrechen</a>
    </form>
    <?php
} else {
    $freunde   = array_values(array_filter($sponsors, fn($s) => ($s['group'] ?? '') === 'freunde'));
    $sponsoren = array_values(array_filter($sponsors, fn($s) => ($s['group'] ?? '') === 'sponsoren'));
    ?>
    <h1>Freunde &amp; Sponsoren <a class="btn-primary" href="?action=new">+ Neu</a></h1>

    <?php foreach ([['Freunde', $freunde], ['Sponsoren', $sponsoren]] as [$label, $group]): ?>
    <h2 style="margin-top:2rem;color:var(--gold)"><?= $label ?></h2>
    <?php if (empty($group)): ?>
        <p class="muted">Noch keine Einträge.</p>
    <?php else: ?>
    <table class="data">
        <thead><tr><th>Logo</th><th>Name</th><th>URL</th><th>Text</th><th>Sichtbar</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($group as $sp): ?>
            <tr>
                <td>
                    <?php if (!empty($sp['logo'])): ?>
                        <img src="<?= e(url($sp['logo'])) ?>" style="height:40px;max-width:100px;object-fit:contain;background:#fff;padding:2px;border-radius:3px">
                    <?php endif; ?>
                </td>
                <td><?= e($sp['name']) ?></td>
                <td>
                    <?php if (!empty($sp['url'])): ?>
                        <a href="<?= e($sp['url']) ?>" target="_blank" rel="noopener noreferrer"><?= e($sp['url']) ?></a>
                    <?php endif; ?>
                </td>
                <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= e($sp['text']) ?></td>
                <td style="text-align:center">
                    <form method="post" style="display:inline;margin:0">
                        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="op" value="toggle">
                        <input type="hidden" name="id" value="<?= e($sp['id']) ?>">
                        <button type="submit" title="Sichtbarkeit umschalten" style="background:none;border:none;cursor:pointer;font-size:1.1rem;padding:0 .3rem"><?= ($sp['visible'] ?? true) ? '<span style="color:var(--gold)">✓</span>' : '<span style="color:#555">✗</span>' ?></button>
                    </form>
                </td>
                <td style="white-space:nowrap">
                    <a href="?action=edit&id=<?= e($sp['id']) ?>">Bearbeiten</a>

                    <form method="post" style="display:inline">
                        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="op" value="move">
                        <input type="hidden" name="id" value="<?= e($sp['id']) ?>">
                        <input type="hidden" name="dir" value="up">
                        <button class="link-btn" type="submit" title="Nach oben">↑</button>
                    </form>
                    <form method="post" style="display:inline">
                        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="op" value="move">
                        <input type="hidden" name="id" value="<?= e($sp['id']) ?>">
                        <input type="hidden" name="dir" value="down">
                        <button class="link-btn" type="submit" title="Nach unten">↓</button>
                    </form>

                    <form method="post" style="display:inline" onsubmit="return confirm('Wirklich löschen?')">
                        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="op" value="delete">
                        <input type="hidden" name="id" value="<?= e($sp['id']) ?>">
                        <button class="link-danger" type="submit">Löschen</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
    <?php endforeach; ?>
    <?php
}
include __DIR__ . '/footer.php';
