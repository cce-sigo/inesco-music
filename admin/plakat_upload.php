<?php
require_once __DIR__ . '/auth.php';

header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

$csrf = $_POST['csrf'] ?? null;
if (!csrf_check($csrf)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Ungueltiges CSRF-Token']);
    exit;
}

$file = $_FILES['image'] ?? null;
if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Keine Datei ausgewaehlt']);
    exit;
}

$path = handle_upload($file, 'img/uploadlogos', ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg', 'avif']);
if (!$path) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Upload fehlgeschlagen']);
    exit;
}

echo json_encode(['ok' => true, 'path' => $path], JSON_UNESCAPED_SLASHES);
