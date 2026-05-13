<?php
declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class MailService
{
    // ── Costruisce il mailer SMTP usando le costanti definite in config/mail.php ──

    private function buildMailer(): PHPMailer
    {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->AuthType   = 'LOGIN';
        $mail->Timeout    = 10;
        $mail->CharSet    = 'UTF-8';
        $mail->Host       = MAIL_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_USERNAME;
        $mail->Password   = MAIL_PASSWORD;
        $mail->SMTPSecure = MAIL_ENCRYPTION;
        $mail->Port       = MAIL_PORT;$mail->SMTPDebug = 2; // mostra tutto il dialogo SMTP
        $mail->Debugoutput = function($str, $level) {
            error_log("SMTP: $str");
        };
        $mail->setFrom(MAIL_FROM_EMAIL, MAIL_FROM_NAME);
        return $mail;
    }

    private function renderTemplate(string $template, array $vars): string
    {
        extract($vars);
        ob_start();
        require __DIR__ . '/../../views/emails/' . $template . '.php';
        return ob_get_clean();
    }

    // ── Email pubbliche ───────────────────────────────────────────────────────

    public function sendTwoFactorCode(string $toEmail, string $fullName, string $code): void
    {
        $mail = $this->buildMailer();
        $mail->addAddress($toEmail);
        $mail->isHTML(true);
        $mail->Subject = 'Codice OTP - TechShop';
        $mail->Body    = $this->renderTemplate('two_factor', compact('fullName', 'code'));

        try {
            $mail->send();
        } catch (Exception $e) {
            throw new RuntimeException('Errore invio OTP: ' . $mail->ErrorInfo);
        }
    }

    public function sendOrderConfirmation(
        string $toEmail,
        string $customerName,
        int    $orderId,
        float  $total
    ): void {
        $mail = $this->buildMailer();
        $mail->addAddress($toEmail);
        $mail->isHTML(true);
        $mail->Subject = "Ordine confermato - TechShop";
        $mail->Body    = $this->renderTemplate('order_confirmation', compact('customerName', 'orderId', 'total'));

        try {
            $mail->send();
        } catch (Exception $e) {
            throw new RuntimeException('Errore invio conferma ordine: ' . $mail->ErrorInfo);
        }
    }
}
