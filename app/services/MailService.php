<?php
declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class MailService
{
    private string $fromEmail;
    private string $fromName;
    private string $smtpUser;
    private string $smtpPass;

    public function __construct()
    {
        $env = parse_ini_file(__DIR__ . '/../../.env', false, INI_SCANNER_RAW);

        $this->fromEmail = $env['MAIL_FROM_EMAIL'] ?? '';
        $this->fromName  = $env['MAIL_FROM_NAME'] ?? 'TechShop';
        $this->smtpUser  = $env['SMTP_USER'] ?? '';
        $this->smtpPass  = $env['SMTP_PASS'] ?? '';
    }

    public function sendTwoFactorCode(string $toEmail, string $fullName, string $code): void
    {
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Timeout = 10;
            $mail->CharSet = 'UTF-8';
            $mail->SMTPDebug = 0;
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = $this->smtpUser;
            $mail->Password   = $this->smtpPass;
            $mail->SMTPSecure = 'tls';
            $mail->Port       = 587;

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
            $mail->Timeout = 10;
            $mail->CharSet = 'UTF-8';
            $mail->SMTPDebug = 0;
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = $this->smtpUser;
            $mail->Password   = $this->smtpPass;
            $mail->SMTPSecure = 'tls';
            $mail->Port       = 465;

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
            // non bloccare ordine
        }
    }
}