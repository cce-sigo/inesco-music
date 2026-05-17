<?php
declare(strict_types=1);

/**
 * Normalisiert eine Telefonnummer für API-Aufrufe.
 * Erlaubt nur internationale Schreibweise mit optionalem Plus.
 */
function normalize_whatsapp_phone(string $phone): string {
    $phone = preg_replace('/[^0-9+]/', '', trim($phone)) ?? '';
    if ($phone === '') {
        return '';
    }
    if ($phone[0] === '+') {
        $phone = substr($phone, 1);
    }
    return preg_match('/^[1-9][0-9]{7,19}$/', $phone) ? $phone : '';
}

/**
 * Sendet eine WhatsApp-Benachrichtigung über CallMeBot.
 * Benötigt INESCO_WHATSAPP_API_KEY in der .env-Datei.
 */
function send_guestbook_whatsapp_notification(array $comment, bool $reviewingEnabled): bool {
    $rawPhone = comment_whatsapp_number();
    if ($rawPhone === '') {
        return false;
    }

    $apiKey = trim((string)(getenv('INESCO_WHATSAPP_API_KEY') ?: ''));
    if ($apiKey === '') {
        error_log('guestbook WhatsApp: INESCO_WHATSAPP_API_KEY ist nicht gesetzt.');
        return false;
    }

    $phone = normalize_whatsapp_phone($rawPhone);
    if ($phone === '') {
        error_log('guestbook WhatsApp: Ungültige Zielnummer in content.comments.whatsappNumber.');
        return false;
    }

    $name = trim((string)($comment['name'] ?? ''));
    $message = trim((string)($comment['message'] ?? ''));
    $status = $reviewingEnabled ? 'wartet auf Freigabe' : 'ist sofort sichtbar';
    $body = "Neuer Gästebuch-Eintrag auf inesco-music.at\n"
        . "Name: {$name}\n"
        . "Status: {$status}\n"
        . "Nachricht:\n{$message}";

    $url = 'https://api.callmebot.com/whatsapp.php?phone=' . rawurlencode($phone)
        . '&text=' . rawurlencode($body)
        . '&apikey=' . rawurlencode($apiKey);

    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 10,
            'ignore_errors' => true,
        ],
    ]);

    $result = @file_get_contents($url, false, $context);
    $statusLine = $http_response_header[0] ?? '';
    if ($result === false || !preg_match('~\s2\d\d\s~', $statusLine)) {
        error_log('guestbook WhatsApp: Versand fehlgeschlagen. Response=' . $statusLine);
        return false;
    }

    return true;
}