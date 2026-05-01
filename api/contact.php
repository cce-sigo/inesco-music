<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/mailer.php';

header('Content-Type: application/json; charset=UTF-8');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Methode nicht erlaubt.']);
    exit;
}

if (!csrf_check($_POST['csrf'] ?? null)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Ungültiges Sicherheitstoken. Bitte Seite neu laden.']);
    exit;
}

// Spam-Erkennung: nur abweisen, wenn Honeypot offensichtlich Spam enthält
// (Browser/Passwort-Manager füllen die Felder oft ungewollt mit Text aus).
$hpRaw = trim((string)($_POST['website'] ?? '')) . ' ' . trim((string)($_POST['hp_url'] ?? ''));
if ($hpRaw !== '' && preg_match('~https?://|www\.|<a\s|\[url=~i', $hpRaw)) {
    error_log('contact.php: Spam erkannt (Honeypot enthält Link), IP=' . ($_SERVER['REMOTE_ADDR'] ?? '?'));
    echo json_encode(['ok' => true]); // still pretend success
    exit;
}

// Mindest-Renderzeit: bot submits sind meist <2s nach Seitenaufruf
$tsForm = (int)($_POST['ts'] ?? 0);
if ($tsForm > 0 && (time() - $tsForm) < 2) {
    error_log('contact.php: Submit zu schnell (' . (time() - $tsForm) . 's), IP=' . ($_SERVER['REMOTE_ADDR'] ?? '?'));
    echo json_encode(['ok' => true]);
    exit;
}

// Rate limit pro Session
$now = time();
if (isset($_SESSION['contact_last']) && ($now - (int)$_SESSION['contact_last']) < 20) {
    http_response_code(429);
    echo json_encode(['ok' => false, 'error' => 'Bitte kurz warten und erneut versuchen.']);
    exit;
}

$name    = trim((string)($_POST['name'] ?? ''));
$email   = trim((string)($_POST['email'] ?? ''));
$message = trim((string)($_POST['message'] ?? ''));

$errors = [];
if ($name === '' || mb_strlen($name) > 100)       $errors[] = 'Name ungültig.';
if (!filter_var($email, FILTER_VALIDATE_EMAIL))   $errors[] = 'E-Mail ungültig.';
if ($message === '' || mb_strlen($message) > 3000) $errors[] = 'Nachricht ungültig.';

// Verhindere Header-Injection im Mail-Header durch Newlines
foreach ([$name, $email] as $v) {
    if (preg_match('/[\r\n]/', $v)) {
        $errors[] = 'Eingaben enthalten ungültige Zeichen.';
        break;
    }
}

if ($errors) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => implode(' ', $errors)]);
    exit;
}

$contact = read_json('contact');
$to = $contact['mailTo'] ?? ($contact['email'] ?? null);
if (!$to) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Empfänger nicht konfiguriert.']);
    exit;
}

error_log("contact.php: sende Mail an $to (von $email)");
$ok = send_contact_mail($to, $name, $email, $message);
$_SESSION['contact_last'] = $now;

if (!$ok) {
    error_log('contact.php: send_contact_mail() lieferte FALSE');
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Mail konnte nicht gesendet werden.']);
    exit;
}

error_log('contact.php: Mail erfolgreich versendet');
echo json_encode(['ok' => true, 'message' => 'Vielen Dank! Wir melden uns bald.']);
