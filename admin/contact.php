<?php
require_once __DIR__ . '/auth.php';
$pageTitle = 'Kontakt';

$contact = read_json('contact');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['csrf'] ?? null)) {
        flash_set('err','Sicherheitstoken ungültig.');
    } else {
        $contact['email']  = trim((string)$_POST['email']);
        $contact['phone']  = trim((string)$_POST['phone']);
        $contact['mailTo'] = trim((string)$_POST['mailTo']) ?: $contact['email'];
        $contact['social'] = [
            'instagram' => trim((string)$_POST['instagram']),
            'facebook'  => trim((string)$_POST['facebook']),
            'youtube'   => trim((string)$_POST['youtube']),
        ];
        $contact['legal'] = [
            'owner'   => trim((string)($_POST['legal_owner']   ?? '')),
            'street'  => trim((string)($_POST['legal_street']  ?? '')),
            'zip'     => trim((string)($_POST['legal_zip']     ?? '')),
            'city'    => trim((string)($_POST['legal_city']    ?? '')),
            'country' => trim((string)($_POST['legal_country'] ?? 'Österreich')),
        ];
        write_json('contact', $contact);
        flash_set('ok','Gespeichert.');
        redirect(url('admin/contact.php'));
    }
}

include __DIR__ . '/header.php';
?>
<h1>Kontaktdaten</h1>
<form method="post" class="form">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <div class="grid-2">
        <label>Öffentliche E-Mail<input type="email" name="email" value="<?= e($contact['email'] ?? '') ?>"></label>
        <label>Telefon<input type="text" name="phone" value="<?= e($contact['phone'] ?? '') ?>"></label>
        <label>Empfänger Kontaktformular<input type="email" name="mailTo" value="<?= e($contact['mailTo'] ?? '') ?>"></label>
    </div>
    <fieldset>
        <legend>Social Media (volle URLs)</legend>
        <label>Instagram<input type="url" name="instagram" value="<?= e($contact['social']['instagram'] ?? '') ?>"></label>
        <label>Facebook<input type="url" name="facebook" value="<?= e($contact['social']['facebook'] ?? '') ?>"></label>
        <label>YouTube<input type="url" name="youtube" value="<?= e($contact['social']['youtube'] ?? '') ?>"></label>
    </fieldset>

    <?php $L = $contact['legal'] ?? []; ?>
    <fieldset>
        <legend>Impressum &amp; Datenschutz – persönliche Daten</legend>
        <p class="muted" style="margin:0 0 .75rem">Diese Angaben erscheinen auf <code>impressum.php</code> und <code>datenschutz.php</code>.</p>
        <label>Inhaber:in (Vor- und Nachname)<input type="text" name="legal_owner" value="<?= e($L['owner'] ?? '') ?>"></label>
        <label>Straße und Hausnummer<input type="text" name="legal_street" value="<?= e($L['street'] ?? '') ?>"></label>
        <div class="grid-2">
            <label>PLZ<input type="text" name="legal_zip" value="<?= e($L['zip'] ?? '') ?>"></label>
            <label>Ort<input type="text" name="legal_city" value="<?= e($L['city'] ?? '') ?>"></label>
            <label>Land<input type="text" name="legal_country" value="<?= e($L['country'] ?? 'Österreich') ?>"></label>
        </div>
        <p class="muted" style="margin:.25rem 0 0">Telefon und E-Mail werden aus den oberen Feldern übernommen.</p>
    </fieldset>

    <button class="btn-primary" type="submit">Speichern</button>
</form>
<?php include __DIR__ . '/footer.php'; ?>
