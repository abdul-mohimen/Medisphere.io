<?php
namespace App\Services;

class Mailer
{
    public function send(string $to, string $subject, string $html, ?string $text = null): bool
    {
        if (class_exists('PHPMailer\\PHPMailer\\PHPMailer') && config('mail.driver') === 'smtp') {
            return $this->sendWithPHPMailer($to, $subject, $html, $text);
        }

        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: ' . config('mail.from_name') . ' <' . config('mail.from_email') . '>',
        ];

        $success = @mail($to, $subject, $html, implode("\r\n", $headers));
        $this->log('mail()', $to, $subject, $success, $success ? 'Sent with native mail().' : 'mail() failed or is unavailable.');
        return $success;
    }

    private function sendWithPHPMailer(string $to, string $subject, string $html, ?string $text = null): bool
    {
        try {
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = config('mail.smtp_host');
            $mail->Port = (int) config('mail.smtp_port', 587);
            $mail->SMTPAuth = true;
            $mail->Username = config('mail.smtp_user');
            $mail->Password = config('mail.smtp_pass');
            $mail->SMTPSecure = config('mail.smtp_secure', 'tls');
            $mail->CharSet = 'UTF-8';
            $mail->setFrom(config('mail.from_email'), config('mail.from_name'));
            $mail->addAddress($to);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $html;
            $mail->AltBody = $text ?: strip_tags($html);
            $mail->send();
            $this->log('PHPMailer', $to, $subject, true, 'Sent via PHPMailer SMTP.');
            return true;
        } catch (\Throwable $exception) {
            $this->log('PHPMailer', $to, $subject, false, $exception->getMessage());
            return false;
        }
    }

    private function log(string $driver, string $to, string $subject, bool $success, string $message): void
    {
        $line = sprintf("[%s] [%s] [%s] To:%s Subject:%s Message:%s%s", date('Y-m-d H:i:s'), $driver, $success ? 'SUCCESS' : 'FAIL', $to, $subject, $message, PHP_EOL);
        @file_put_contents(__DIR__ . '/../../storage/logs/mail.log', $line, FILE_APPEND);
    }
}
