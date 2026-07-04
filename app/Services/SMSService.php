<?php
namespace App\Services;

class SMSService
{
    public function send(string $to, string $message): bool
    {
        if (!config('services.twilio_sid') || !config('services.twilio_token') || !config('services.twilio_from')) {
            $this->log($to, $message, false, 'Twilio credentials are missing.');
            return false;
        }

        if (!class_exists('Twilio\\Rest\\Client')) {
            $this->log($to, $message, false, 'Twilio SDK is not installed.');
            return false;
        }

        try {
            $client = new \Twilio\Rest\Client(config('services.twilio_sid'), config('services.twilio_token'));
            $client->messages->create($to, [
                'from' => config('services.twilio_from'),
                'body' => $message,
            ]);
            $this->log($to, $message, true, 'SMS sent successfully.');
            return true;
        } catch (\Throwable $exception) {
            $this->log($to, $message, false, $exception->getMessage());
            return false;
        }
    }

    private function log(string $to, string $message, bool $success, string $extra): void
    {
        $line = sprintf("[%s] [%s] To:%s Message:%s Extra:%s%s", date('Y-m-d H:i:s'), $success ? 'SUCCESS' : 'FAIL', $to, $message, $extra, PHP_EOL);
        @file_put_contents(__DIR__ . '/../../storage/logs/sms.log', $line, FILE_APPEND);
    }
}
