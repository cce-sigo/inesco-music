<?php
require_once __DIR__ . '/auth.php';
$pageTitle = 'Navigation';

$content = read_json('content');
$current = nav_items(); // bereits normalisiert + Defaults gemerged

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['csrf'] ?? null)) {
        flash_set('err', 'Sicherheitstoken ungültig.');
        redirect(url('admin/navigation.php'));
    }
    $op = $_POST['op'] ?? 'save';

    // IDs sind fest vorgegeben (entsprechen den Section-Ankern in index.php)
    $allowedIds = array_column($current, 'id');

    if ($op === 'move') {
        $id  = (string)($_POST['id'] ?? '');
        $dir = (string)($_POST['dir'] ?? '');
        if (in_array($id, $allowedIds, true) && in_array($dir, ['up','down'], true)) {
            $idx = null;
            foreach ($current as $i => $it) if ($it['id'] === $id) { $idx = $i; break; }
            if ($idx !== null) {
                $swap = $dir === 'up' ? $idx - 1 : $idx + 1;
                if ($swap >= 0 && $swap < count($current)) {
                    [$current[$idx], $current[$swap]] = [$current[$swap], $current[$idx]];
                    $content['nav'] = $current;
                    if (write_json('content', $content)) flash_set('ok', 'Reihenfolge gespeichert.');
                    else flash_set('err', 'Fehler beim Speichern.');
                }
            }
        }
        redirect(url('admin/navigation.php'));
    }

    // Default: alle Items aus dem Form übernehmen
    $new = [];
    $postedOrder = $_POST['order'] ?? []; // Reihenfolge per hidden input
    if (!is_array($postedOrder) || empty($postedOrder)) {
        $postedOrder = $allowedIds;
    }
    foreach ($postedOrder as $id) {
        $id = (string)$id;
        if (!in_array($id, $allowedIds, true)) continue;
        $label = trim((string)($_POST['label'][$id] ?? ''));
        if ($label === '') {
            // Fallback: Default-Label oder ID
            foreach (nav_defaults() as $d) if ($d['id'] === $id) { $label = $d['label']; break; }
            if ($label === '') $label = $id;
        }
        $visible = !empty($_POST['visible'][$id]);
        $new[] = ['id' => $id, 'label' => $label, 'visible' => $visible];
    }
    $content['nav'] = $new;
    if (write_json('content', $content)) flash_set('ok', 'Navigation gespeichert.');
    else flash_set('err', 'Fehler beim Speichern.');
    redirect(url('admin/navigation.php'));
}

include __DIR__ . '/header.php';
?>
<h1>Navigation verwalten</h1>
<p class="muted" style="margin-top:0">
    Sichtbarkeit, Beschriftung und Reihenfolge der Hauptnavigation.
    Wird ein Punkt deaktiviert, verschwindet sowohl der Menüeintrag als auch
    der zugehörige Bereich auf der Startseite.
</p>

<form method="post" class="form">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="op" value="save">

    <table class="data-table" style="width:100%;border-collapse:collapse">
        <thead>
            <tr>
                <th style="text-align:left;padding:.5rem">Sichtbar</th>
                <th style="text-align:left;padding:.5rem">ID / Anker</th>
                <th style="text-align:left;padding:.5rem">Beschriftung im Menü</th>
                <th style="text-align:left;padding:.5rem">Reihenfolge</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($current as $i => $it): ?>
            <tr style="border-top:1px solid rgba(255,255,255,.1)">
                <td style="padding:.5rem;vertical-align:middle">
                    <input type="hidden" name="order[]" value="<?= e($it['id']) ?>">
                    <label style="display:inline-flex;align-items:center;gap:.4rem">
                        <input type="checkbox" name="visible[<?= e($it['id']) ?>]" value="1"
                               <?= !empty($it['visible']) ? 'checked' : '' ?>>
                        <span><?= !empty($it['visible']) ? 'an' : 'aus' ?></span>
                    </label>
                </td>
                <td style="padding:.5rem;vertical-align:middle">
                    <code>#<?= e($it['id']) ?></code>
                </td>
                <td style="padding:.5rem;vertical-align:middle">
                    <input type="text" name="label[<?= e($it['id']) ?>]"
                           value="<?= e($it['label']) ?>" maxlength="60" style="width:100%">
                </td>
                <td style="padding:.5rem;vertical-align:middle;white-space:nowrap">
                    <button type="submit" form="move-<?= e($it['id']) ?>-up"
                            class="btn-secondary" <?= $i === 0 ? 'disabled' : '' ?>>↑</button>
                    <button type="submit" form="move-<?= e($it['id']) ?>-down"
                            class="btn-secondary" <?= $i === count($current) - 1 ? 'disabled' : '' ?>>↓</button>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <div style="margin-top:1rem">
        <button class="btn-primary" type="submit">Speichern</button>
    </div>
</form>

<?php // Separate Mini-Forms für Move-Buttons (damit das Hauptform nicht abgesendet wird) ?>
<?php foreach ($current as $it): ?>
    <form id="move-<?= e($it['id']) ?>-up" method="post" style="display:none">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="op" value="move">
        <input type="hidden" name="id" value="<?= e($it['id']) ?>">
        <input type="hidden" name="dir" value="up">
    </form>
    <form id="move-<?= e($it['id']) ?>-down" method="post" style="display:none">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="op" value="move">
        <input type="hidden" name="id" value="<?= e($it['id']) ?>">
        <input type="hidden" name="dir" value="down">
    </form>
<?php endforeach; ?>

<p class="muted" style="margin-top:1.5rem">
    Hinweis: Die IDs (Anker wie <code>#bio</code>, <code>#musik</code> …) sind technisch
    fest mit den Bereichen auf der Startseite verknüpft und können hier nicht geändert werden.
</p>

<?php include __DIR__ . '/footer.php'; ?>
