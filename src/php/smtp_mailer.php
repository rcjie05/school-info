<?php
/**
 * SMTP Mailer — powered by PHPMailer
 *
 * Drop-in replacement for the raw-socket SMTPMailer.
 * The public interface (constructor + send()) is identical so all callers
 * (approve_application, reject_application, forgot_password,
 *  onboard_applicant, save_applicant) work without modification.
 *
 * REQUIREMENTS
 * ────────────
 * Install PHPMailer via Composer in the project root:
 *
 *   composer require phpmailer/phpmailer
 *
 * This file expects the Composer autoloader at:
 *   <project-root>/vendor/autoload.php
 *
 * The autoloader path is resolved relative to this file's directory
 * (php/ → ../vendor/autoload.php).
 *
 * SMTP credentials are read from php/config.php constants:
 *   SMTP_HOST, SMTP_PORT, SMTP_ENCRYPTION ('tls'|'ssl'),
 *   SMTP_USERNAME, SMTP_PASSWORD, SMTP_FROM_EMAIL, SMTP_FROM_NAME
 */

// ── Load PHPMailer via Composer autoloader ────────────────────────────────────
$_autoloader = __DIR__ . '/../vendor/autoload.php';

if (!file_exists($_autoloader)) {
    // Friendly error — avoids a fatal "class not found" with no context
    throw new RuntimeException(
        "PHPMailer not found. Run: composer require phpmailer/phpmailer\n" .
        "Expected autoloader at: " . realpath(__DIR__ . '/..') . "/vendor/autoload.php"
    );
}

require_once $_autoloader;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

// ── SMTPMailer wrapper ────────────────────────────────────────────────────────

class SMTPMailer
{
    private string $host;
    private int    $port;
    private string $username;
    private string $password;
    private string $encryption;   // 'tls' or 'ssl'
    private string $from_email;
    private string $from_name;
    private string $last_response = '';

    public function __construct(array $config = [])
    {
        $this->host       = $config['host']       ?? SMTP_HOST;
        $this->port       = (int)($config['port'] ?? SMTP_PORT);
        $this->username   = $config['username']   ?? SMTP_USERNAME;
        $this->password   = $config['password']   ?? SMTP_PASSWORD;
        $this->encryption = $config['encryption'] ?? SMTP_ENCRYPTION;
        $this->from_email = $config['from_email'] ?? SMTP_FROM_EMAIL;
        $this->from_name  = $config['from_name']  ?? SMTP_FROM_NAME;
    }

    /**
     * Send an HTML email.
     *
     * @param  string $to_email   Recipient e-mail address
     * @param  string $to_name    Recipient display name
     * @param  string $subject    Message subject
     * @param  string $body       HTML message body
     * @return bool               true on success, false on failure
     */
    public function send(
        string $to_email,
        string $to_name,
        string $subject,
        string $body
    ): bool {
        try {
            $mail = new PHPMailer(true); // true = throw exceptions

            // ── Server settings ───────────────────────────────────────
            $mail->isSMTP();
            $mail->Host       = $this->host;
            $mail->SMTPAuth   = true;
            $mail->Username   = $this->username;
            $mail->Password   = $this->password;
            $mail->Port       = $this->port;

            if ($this->encryption === 'ssl') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;      // port 465
            } else {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;   // port 587
            }

            // ── Sender / recipient ────────────────────────────────────
            $mail->setFrom($this->from_email, $this->from_name);
            $mail->addAddress($to_email, $to_name);
            $mail->addReplyTo($this->from_email, $this->from_name);

            // ── Content ───────────────────────────────────────────────
            $mail->isHTML(true);
            $mail->CharSet  = PHPMailer::CHARSET_UTF8;
            $mail->XMailer  = 'SCC-School-System';
            $mail->Subject  = $subject;
            $mail->Body     = $body;
            $mail->AltBody  = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $body));

            $mail->send();

            $this->last_response = 'Message sent successfully';
            return true;

        } catch (PHPMailerException $e) {
            $this->last_response = $e->getMessage();
            error_log('SMTPMailer (PHPMailer) Error: ' . $e->getMessage());
            return false;
        } catch (Exception $e) {
            $this->last_response = $e->getMessage();
            error_log('SMTPMailer Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Returns the last SMTP response / error message.
     */
    public function getLastResponse(): string
    {
        return $this->last_response;
    }
}
