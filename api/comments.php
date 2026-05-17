<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/notifications.php';

header('Content-Type: application/json; charset=UTF-8');

$reviewingEnabled = comment_review_enabled();
$rateLimitSeconds = 1;

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

// Honeypot-Prüfung
$hp = trim((string)($_POST['website'] ?? ''));
if ($hp !== '' && preg_match('~https?://|www\.|<a\s|\[url=~i', $hp)) {
    echo json_encode(['ok' => true, 'message' => $reviewingEnabled
        ? 'Danke! Dein Eintrag wird nach Prüfung sichtbar.'
        : 'Danke! Dein Eintrag ist jetzt sichtbar.']);
    exit;
}

// Mindest-Renderzeit
$tsForm = (int)($_POST['ts'] ?? 0);
if ($tsForm > 0 && (time() - $tsForm) < 2) {
    echo json_encode(['ok' => true, 'message' => $reviewingEnabled
        ? 'Danke! Dein Eintrag wird nach Prüfung sichtbar.'
        : 'Danke! Dein Eintrag ist jetzt sichtbar.']);
    exit;
}

// Rate-Limit pro Session
$now = time();
if (isset($_SESSION['gb_last']) && ($now - (int)$_SESSION['gb_last']) < $rateLimitSeconds) {
    $retryAfter = $rateLimitSeconds - ($now - (int)$_SESSION['gb_last']);
    if ($retryAfter < 1) $retryAfter = 1;
    header('Retry-After: ' . $retryAfter);
    http_response_code(429);
    echo json_encode([
        'ok' => false,
        'error' => 'Bitte kurz warten und erneut versuchen.',
        'retry_after' => $retryAfter,
    ]);
    exit;
}

$name    = trim((string)($_POST['name']    ?? ''));
$message = trim((string)($_POST['message'] ?? ''));

$errors = [];
if ($name === '' || mb_strlen($name) > 100)        $errors[] = 'Name ungültig.';
if ($message === '' || mb_strlen($message) > 1000) $errors[] = 'Nachricht ungültig.';
if (preg_match('/[\r\n]/', $name))                 $errors[] = 'Eingaben enthalten ungültige Zeichen.';

if ($errors) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => implode(' ', $errors)]);
    exit;
}

$comments = read_json('comments');
if (!is_array($comments)) $comments = [];

$newComment = [
    'id'       => (string)($now . '_' . substr(bin2hex(random_bytes(4)), 0, 6)),
    'name'     => $name,
    'message'  => $message,
    'ts'       => $now,
    'approved' => !$reviewingEnabled,
    'reply'    => '',
];

$comments[] = $newComment;
$_SESSION['gb_last'] = $now;

if (!write_json('comments', $comments)) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Speichern fehlgeschlagen. Bitte später erneut versuchen.']);
    exit;
}

send_guestbook_whatsapp_notification($newComment, $reviewingEnabled);

echo json_encode(['ok' => true, 'message' => $reviewingEnabled
    ? 'Danke! Dein Eintrag wird nach Prüfung sichtbar.'
    : 'Danke! Dein Eintrag ist jetzt sichtbar.']);
