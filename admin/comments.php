<?php
require_once __DIR__ . '/auth.php';
$pageTitle = 'Gästebuch';

$content = read_json('content');
$comments = read_json('comments');
if (!is_array($comments)) $comments = [];
$reviewingEnabled = comment_review_enabled();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['csrf'] ?? null)) {
        flash_set('err', 'Sicherheitstoken ungültig.');
        redirect(url('admin/comments.php'));
    }
    $op  = $_POST['op']  ?? '';
    $cid = (string)($_POST['id'] ?? '');

    if ($op === 'toggle_reviewing') {
        if (!isset($content['comments']) || !is_array($content['comments'])) {
            $content['comments'] = [];
        }
        $content['comments']['reviewing'] = !empty($_POST['reviewing']);
        $content['comments']['whatsappNumber'] = trim((string)($_POST['whatsapp_number'] ?? ''));
        if (write_json('content', $content)) {
            flash_set('ok', !empty($_POST['reviewing'])
                ? 'Prüfung für neue Einträge aktiviert.'
                : 'Prüfung für neue Einträge deaktiviert. Neue Einträge werden sofort veröffentlicht.');
        } else {
            flash_set('err', 'Einstellung konnte nicht gespeichert werden.');
        }
        redirect(url('admin/comments.php'));
    }

    if ($op === 'approve') {
        foreach ($comments as &$c) {
            if (($c['id'] ?? '') === $cid) { $c['approved'] = true; break; }
        } unset($c);
        write_json('comments', $comments);
        flash_set('ok', 'Eintrag freigegeben.');
        redirect(url('admin/comments.php'));
    }

    if ($op === 'unapprove') {
        foreach ($comments as &$c) {
            if (($c['id'] ?? '') === $cid) { $c['approved'] = false; break; }
        } unset($c);
        write_json('comments', $comments);
        flash_set('ok', 'Eintrag zurückgezogen.');
        redirect(url('admin/comments.php'));
    }

    if ($op === 'reply') {
        $reply = trim((string)($_POST['reply'] ?? ''));
        foreach ($comments as &$c) {
            if (($c['id'] ?? '') === $cid) { $c['reply'] = $reply; break; }
        } unset($c);
        write_json('comments', $comments);
        flash_set('ok', 'Antwort gespeichert.');
        redirect(url('admin/comments.php'));
    }

    if ($op === 'delete') {
        $comments = array_values(array_filter($comments, fn($c) => ($c['id'] ?? '') !== $cid));
        write_json('comments', $comments);
        flash_set('ok', 'Eintrag gelöscht.');
        redirect(url('admin/comments.php'));
    }
}

// Sort: newest first
usort($comments, fn($a, $b) => (int)($b['ts'] ?? 0) - (int)($a['ts'] ?? 0));

$pending  = array_values(array_filter($comments, fn($c) => empty($c['approved'])));
$approved = array_values(array_filter($comments, fn($c) => !empty($c['approved'])));

include __DIR__ . '/header.php';
?>
<h1>Gästebuch verwalten</h1>

<form method="post" class="form" style="margin:1rem 0 2rem">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="op" value="toggle_reviewing">
    <label style="display:flex;align-items:center;gap:.75rem;flex-wrap:wrap">
        <input type="checkbox" name="reviewing" value="1" <?= $reviewingEnabled ? 'checked' : '' ?>>
        <span>Prüfung neuer Gästebuch-Einträge aktivieren</span>
    </label>
    <p class="muted" style="margin:.5rem 0 0">
        <?= $reviewingEnabled
            ? 'Neue Einträge landen zuerst unter "Ausstehend" und müssen freigegeben werden.'
            : 'Neue Einträge werden sofort öffentlich angezeigt. Bestehende ausstehende Einträge bleiben unverändert.' ?>
    </p>
    <label style="display:block;margin-top:1rem">
        WhatsApp-Zielnummer
        <input type="text" name="whatsapp_number" value="<?= e($content['comments']['whatsappNumber'] ?? '') ?>"
               placeholder="z. B. +436641234567" style="max-width:320px">
    </label>
    <p class="muted" style="margin:.5rem 0 0">
        Bei neuen Gästebuch-Einträgen wird eine WhatsApp-Nachricht an diese Nummer gesendet, wenn zusätzlich
        <strong>INESCO_WHATSAPP_API_KEY</strong> in der <strong>.env</strong> gesetzt ist.
    </p>
    <button class="btn-primary" type="submit" style="margin-top:.75rem">Einstellung speichern</button>
</form>

<?php if (!empty($pending)): ?>
<h2 style="margin-top:1.5rem;color:var(--gold)">Ausstehend (<?= count($pending) ?>)</h2>
<table class="data">
    <thead><tr><th>Datum</th><th>Name</th><th>Nachricht</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($pending as $c): ?>
        <tr>
            <td style="white-space:nowrap"><?= e(date('d.m.Y H:i', (int)($c['ts'] ?? 0))) ?></td>
            <td><?= e($c['name']) ?></td>
            <td style="max-width:400px;word-break:break-word"><?= nl2br(e($c['message'])) ?></td>
            <td style="white-space:nowrap">
                <form method="post" style="display:inline">
                    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="op"   value="approve">
                    <input type="hidden" name="id"   value="<?= e($c['id']) ?>">
                    <button class="btn-primary" type="submit">Freigeben</button>
                </form>
                <form method="post" style="display:inline" onsubmit="return confirm('Löschen?')">
                    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="op"   value="delete">
                    <input type="hidden" name="id"   value="<?= e($c['id']) ?>">
                    <button class="btn-danger" type="submit">Löschen</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php else: ?>
    <p class="muted">Keine ausstehenden Einträge.</p>
<?php endif; ?>

<h2 style="margin-top:2rem">Freigegebene Einträge (<?= count($approved) ?>)</h2>
<?php if (empty($approved)): ?>
    <p class="muted">Noch keine freigegebenen Einträge.</p>
<?php else: ?>
<table class="data">
    <thead><tr><th>Datum</th><th>Name</th><th>Nachricht</th><th>Antwort</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($approved as $c): ?>
        <tr>
            <td style="white-space:nowrap"><?= e(date('d.m.Y H:i', (int)($c['ts'] ?? 0))) ?></td>
            <td><?= e($c['name']) ?></td>
            <td style="max-width:300px;word-break:break-word"><?= nl2br(e($c['message'])) ?></td>
            <td style="max-width:250px">
                <form method="post">
                    <input type="hidden" name="csrf"  value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="op"    value="reply">
                    <input type="hidden" name="id"    value="<?= e($c['id']) ?>">
                    <textarea name="reply" rows="2" style="width:100%;min-width:180px"
                              placeholder="Antwort von INESCO …"><?= e($c['reply'] ?? '') ?></textarea>
                    <button class="btn-primary" type="submit" style="margin-top:.3rem">Antwort speichern</button>
                </form>
            </td>
            <td style="white-space:nowrap">
                <form method="post" style="display:inline">
                    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="op"   value="unapprove">
                    <input type="hidden" name="id"   value="<?= e($c['id']) ?>">
                    <button class="btn-ghost" type="submit">Zurückziehen</button>
                </form>
                <form method="post" style="display:inline" onsubmit="return confirm('Löschen?')">
                    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="op"   value="delete">
                    <input type="hidden" name="id"   value="<?= e($c['id']) ?>">
                    <button class="btn-danger" type="submit">Löschen</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>

<?php include __DIR__ . '/footer.php'; ?>
