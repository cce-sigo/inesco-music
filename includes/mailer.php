<?php
declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

/**
 * Versendet eine Kontaktanfrage über SMTP (PHPMailer).
 * Konfiguration siehe includes/config.php (SMTP_* Konstanten).
 */
function send_contact_mail(string $to, string $name, string $email, string $message): bool {
    if (!class_exists(PHPMailer::class)) {
        error_log('send_contact_mail: PHPMailer nicht geladen (vendor/autoload.php fehlt).');
        return false;
    }

    $mail = new PHPMailer(true);
    try {
        // SMTP
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->Port       = SMTP_PORT;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_SECURE; // 'ssl' oder 'tls'
        $mail->CharSet    = 'UTF-8';
        $mail->Encoding   = 'base64';
        $mail->SMTPDebug  = defined('SMTP_DEBUG') ? (int)SMTP_DEBUG : 0;
        $mail->Debugoutput = function ($str, $level) {
            error_log('PHPMailer: ' . trim($str));
        };

        // Absender / Empfänger
        $mail->setFrom(SMTP_FROM, SMTP_FROM_NAME);
        $mail->addAddress($to);
        $mail->addReplyTo($email, $name);

        // Inhalt
        $mail->Subject = '[INESCO Website] Neue Nachricht von ' . $name;
        $body  = "Name:    $name\n";
        $body .= "E-Mail:  $email\n";
        $body .= "Datum:   " . date('Y-m-d H:i') . "\n\n";
        $body .= "Nachricht:\n----------\n$message\n";
        $mail->Body = $body;
        $mail->isHTML(false);

        return $mail->send();
    } catch (PHPMailerException $e) {
        error_log('send_contact_mail PHPMailer-Fehler: ' . $mail->ErrorInfo);
        return false;
    } catch (\Throwable $e) {
        error_log('send_contact_mail Fehler: ' . $e->getMessage());
        return false;
    }
}
