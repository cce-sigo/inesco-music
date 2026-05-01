<?php
require_once __DIR__ . '/auth.php';
$pageTitle = 'Mitglieder';

$members = read_json('members');
$action  = $_GET['action'] ?? 'list';
$id      = $_GET['id'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['csrf'] ?? null)) {
        flash_set('err', 'Sicherheitstoken ungültig.');
        redirect(url('admin/members.php'));
    }
    $op = $_POST['op'] ?? '';

    if ($op === 'save') {
        $mid  = trim((string)$_POST['id']);
        $name = trim((string)$_POST['name']);
        $role = trim((string)$_POST['role']);
        $bio  = trim((string)$_POST['bio']);
        $imagePath = $_POST['existing_image'] ?? '';

        if (!empty($_FILES['image']['name'])) {
            $up = handle_upload($_FILES['image'], 'img', ['jpg','jpeg','png','webp']);
            if ($up) $imagePath = $up;
        }

        $found = false;
        foreach ($members as &$m) {
            if (($m['id'] ?? '') === $mid) {
                $m = ['id' => $mid, 'name' => $name, 'role' => $role, 'image' => $imagePath, 'bio' => $bio];
                $found = true; break;
            }
        }
        unset($m);
        if (!$found) {
            $mid = $mid ?: (preg_replace('/[^a-z0-9]+/i', '-', strtolower($name)) ?: next_id($members));
            $members[] = ['id' => $mid, 'name' => $name, 'role' => $role, 'image' => $imagePath, 'bio' => $bio];
        }
        write_json('members', $members);
        flash_set('ok', 'Gespeichert.');
        redirect(url('admin/members.php'));
    }

    if ($op === 'delete') {
        $mid = $_POST['id'] ?? '';
        $members = array_values(array_filter($members, fn($m) => ($m['id'] ?? '') !== $mid));
        write_json('members', $members);
        flash_set('ok', 'Gelöscht.');
        redirect(url('admin/members.php'));
    }
}

include __DIR__ . '/header.php';

if ($action === 'edit' || $action === 'new') {
    $editing = ['id' => '', 'name' => '', 'role' => '', 'image' => '', 'bio' => ''];
    if ($action === 'edit') {
        foreach ($members as $m) if (($m['id'] ?? '') === $id) { $editing = $m; break; }
    }
    ?>
    <h1><?= $action === 'new' ? 'Neues Mitglied' : 'Mitglied bearbeiten' ?></h1>
    <form method="post" class="form" enctype="multipart/form-data">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="op" value="save">
        <input type="hidden" name="existing_image" value="<?= e($editing['image']) ?>">
        <label>ID (Slug)<input type="text" name="id" value="<?= e($editing['id']) ?>" placeholder="z. B. ines"></label>
        <label>Name<input type="text" name="name" value="<?= e($editing['name']) ?>" required></label>
        <label>Rolle<input type="text" name="role" value="<?= e($editing['role']) ?>" required></label>
        <label>Bio<textarea name="bio" rows="6"><?= e($editing['bio']) ?></textarea></label>
        <label>Bild ersetzen<input type="file" name="image" accept="image/*"></label>
        <?php if ($editing['image']): ?>
            <p>Aktuell: <img src="<?= e(url($editing['image'])) ?>" style="height:80px;border-radius:6px;vertical-align:middle"></p>
        <?php endif; ?>
        <button class="btn-primary" type="submit">Speichern</button>
        <a class="btn-ghost" href="members.php">Abbrechen</a>
    </form>
    <?php
} else {
    ?>
    <h1>Mitglieder <a class="btn-primary" href="?action=new">+ Neu</a></h1>
    <table class="data">
        <thead><tr><th>Bild</th><th>Name</th><th>Rolle</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($members as $m): ?>
            <tr>
                <td><?php if (!empty($m['image'])): ?><img src="<?= e(url($m['image'])) ?>" style="height:50px;border-radius:6px"><?php endif; ?></td>
                <td><?= e($m['name']) ?></td>
                <td><?= e($m['role']) ?></td>
                <td>
                    <a href="?action=edit&id=<?= e($m['id']) ?>">Bearbeiten</a>
                    <form method="post" style="display:inline" onsubmit="return confirm('Wirklich löschen?')">
                        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="op" value="delete">
                        <input type="hidden" name="id" value="<?= e($m['id']) ?>">
                        <button class="link-danger" type="submit">Löschen</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php
}
include __DIR__ . '/footer.php';
