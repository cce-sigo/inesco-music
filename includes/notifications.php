<?php
declare(strict_types=1);

/**
 * Sends an HTTP GET request and returns [ok, statusLine, body, error].
 * Uses stream wrapper first and falls back to cURL on stricter hosts.
 */
function http_get_with_fallback(string $url, int $timeoutSeconds = 10): array {
    $statusLine = '';
    $body = '';
    $error = '';

    // Attempt 1: file_get_contents (requires allow_url_fopen)
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => $timeoutSeconds,
            'ignore_errors' => true,
        ],
    ]);
    $result = @file_get_contents($url, false, $context);
    $headers = $http_response_header ?? [];
    $statusLine = is_array($headers) && isset($headers[0]) ? (string)$headers[0] : '';
    if ($result !== false) {
        $body = (string)$result;
        $ok = (bool)preg_match('~\s2\d\d\s~', $statusLine);
        return [$ok, $statusLine, $body, $error];
    }

    $lastError = error_get_last();
    if (is_array($lastError) && isset($lastError['message'])) {
        $error = (string)$lastError['message'];
    }

    // Attempt 2: cURL fallback (common on shared hosting)
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        if ($ch !== false) {
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_CONNECTTIMEOUT => $timeoutSeconds,
                CURLOPT_TIMEOUT => $timeoutSeconds,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
            ]);
            $curlBody = curl_exec($ch);
            $curlErr = curl_error($ch);
            $code = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            curl_close($ch);

            if ($curlBody !== false) {
                $body = (string)$curlBody;
                $statusLine = $code > 0 ? ('HTTP ' . $code) : $statusLine;
                $ok = $code >= 200 && $code < 300;
                return [$ok, $statusLine, $body, $error];
            }

            if ($curlErr !== '') {
                $error = $error !== '' ? ($error . ' | cURL: ' . $curlErr) : ('cURL: ' . $curlErr);
            }
        }
    }

    return [false, $statusLine, $body, $error];
}

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
        error_log('guestbook WhatsApp: Keine Zielnummer gesetzt (content.comments.whatsappNumber leer).');
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

    [$ok, $statusLine, $responseBody, $transportError] = http_get_with_fallback($url, 10);
    if (!$ok) {
        $responseShort = trim(substr(preg_replace('/\s+/', ' ', (string)$responseBody), 0, 250));
        error_log('guestbook WhatsApp: Versand fehlgeschlagen. HTTP=' . $statusLine
            . ($transportError !== '' ? ' TransportError=' . $transportError : '')
            . ($responseShort !== '' ? ' Body=' . $responseShort : ''));
        return false;
    }

    return true;
}