<?php
/**
 * Outgoing email helper (SMTP via PHPMailer).
 *
 * PHPMailer is bundled in lib/PHPMailer/ so no Composer install is needed.
 * All SMTP settings come from .env (see .env.example) through the constants
 * defined in config.php, so require_once 'config.php' before this file.
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailException;

require_once __DIR__ . '/lib/PHPMailer/Exception.php';
require_once __DIR__ . '/lib/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/lib/PHPMailer/SMTP.php';

/**
 * True when the minimum SMTP settings are present in .env.
 */
function smtpIsConfigured(): bool
{
    return SMTP_HOST !== '' && SMTP_USERNAME !== '' && SMTP_PASSWORD !== '';
}

/**
 * Send an email through the SMTP server configured in .env.
 *
 * Never throws — returns false on any failure and writes the reason to the
 * PHP error log (XAMPP: xampp/apache/logs/error.log) so the cause is visible
 * without leaking SMTP details to the browser.
 *
 * @param string $toEmail  Recipient address.
 * @param string $subject  Subject line.
 * @param string $htmlBody HTML version of the message.
 * @param string $textBody Plain-text fallback for clients that don't render HTML.
 * @return bool True if the SMTP server accepted the message.
 */
function sendMail(string $toEmail, string $subject, string $htmlBody, string $textBody = ''): bool
{
    if (!smtpIsConfigured()) {
        error_log('sendMail: SMTP is not configured — set SMTP_HOST, SMTP_USERNAME and SMTP_PASSWORD in .env');
        return false;
    }

    $mail = new PHPMailer(true); // true = throw exceptions, caught below

    try {
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;
        $mail->Port       = (int) SMTP_PORT;
        // Port 465 = implicit TLS; anything else (587) = STARTTLS.
        $mail->SMTPSecure = ((int) SMTP_PORT === 465)
            ? PHPMailer::ENCRYPTION_SMTPS
            : PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Timeout    = 15;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($toEmail);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $htmlBody;
        $mail->AltBody = $textBody !== '' ? $textBody : trim(strip_tags($htmlBody));

        $mail->send();
        return true;
    } catch (MailException $e) {
        error_log('sendMail failed: ' . $mail->ErrorInfo);
        return false;
    }
}
