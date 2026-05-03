<?php
declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class MailService
{
    private string $fromEmail;
    private string $fromName;
    private string $smtpHost;
    private int $smtpPort;
    private string $smtpEncryption;
    private string $smtpUser;
    private string $smtpPass;

    public function __construct()
    {
        $env = parse_ini_file(__DIR__ . '/../../.env', false, INI_SCANNER_RAW);

        $this->fromEmail      = $env['MAIL_FROM_EMAIL'] ?? $env['MAIL_FROM'] ?? '';
        $this->fromName       = $env['MAIL_FROM_NAME'] ?? 'TechShop';
        $this->smtpHost       = $env['MAIL_HOST'] ?? 'smtp.gmail.com';
        $this->smtpPort       = (int)($env['MAIL_PORT'] ?? 587);
        $this->smtpEncryption = $env['MAIL_ENCRYPTION'] ?? 'tls';
        $this->smtpUser       = $env['SMTP_USER'] ?? $env['MAIL_USERNAME'] ?? '';
        $this->smtpPass       = $env['SMTP_PASS'] ?? $env['MAIL_PASSWORD'] ?? '';
    }

    public function sendTwoFactorCode(string $toEmail, string $fullName, string $code): void
    {
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->AuthType = 'LOGIN'; // Forza il metodo LOGIN invece di CRAM-MD5
            $mail->Timeout    = 10;
            $mail->CharSet    = 'UTF-8';
            $mail->SMTPDebug  = 2;
            $mail->Host       = $this->smtpHost;
            $mail->SMTPAuth   = true;
            $mail->Username   = $this->smtpUser;
            $mail->Password   = $this->smtpPass;
            $mail->SMTPSecure = $this->smtpEncryption;
            $mail->Port       = $this->smtpPort;

            $mail->setFrom($this->fromEmail, $this->fromName);
            $mail->addAddress($toEmail);

            $mail->isHTML(true);
            $mail->Subject = 'Codice OTP - TechShop';

            $mail->Body = "
                <h2>Ciao {$fullName}</h2>
                <p>Il tuo codice è:</p>
                <h1>{$code}</h1>
                <p>Scade tra 10 minuti</p>
            ";

            $mail->send();

        } catch (Exception $e) {
            throw new RuntimeException('Errore invio mail: ' . $mail->ErrorInfo);
        }
    }

    public function sendOrderConfirmation(string $toEmail, string $customerName, int $orderId, float $total): void
    {
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->AuthType = 'LOGIN'; // Forza il metodo LOGIN invece di CRAM-MD5
            $mail->Timeout    = 10;
            $mail->CharSet    = 'UTF-8';
            $mail->Host       = $this->smtpHost;
            $mail->SMTPAuth   = true;
            $mail->Username   = $this->smtpUser;
            $mail->Password   = $this->smtpPass;
            $mail->SMTPSecure = $this->smtpEncryption;
            $mail->Port       = $this->smtpPort;

            $mail->setFrom($this->fromEmail, $this->fromName);
            $mail->addAddress($toEmail);

            $mail->isHTML(true);
            $mail->Subject = "Ordine #{$orderId} confermato";

            $mail->Body = "
                <h2>Grazie {$customerName}</h2>
                <p>Ordine #{$orderId} ricevuto</p>
                <p>Totale: € " . number_format($total, 2, ',', '.') . "</p>
            ";

            $mail->send();

        } catch (Exception $e) {
            throw new RuntimeException('Errore invio mail: ' . $mail->ErrorInfo);
        }
    }
}