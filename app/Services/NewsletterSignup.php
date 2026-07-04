<?php
namespace App\Services;

class NewsletterSignup
{
    private const SESSION_KEY = '_pending_newsletter_signup';

    public function rememberPending(string $email, string $source = 'home_newsletter'): void
    {
        $_SESSION[self::SESSION_KEY] = [
            'email' => strtolower(trim($email)),
            'source' => $source,
            'requested_at' => date('c'),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
        ];
    }

    public function pending(): ?array
    {
        $pending = $_SESSION[self::SESSION_KEY] ?? null;
        if (!is_array($pending) || empty($pending['email']) || !filter_var($pending['email'], FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        return $pending;
    }

    public function consumeIfMatches(string $email): void
    {
        $pending = $this->pending();
        if ($pending && strcasecmp((string) $pending['email'], $email) === 0) {
            unset($_SESSION[self::SESSION_KEY]);
        }
    }

    public function paidMetadata(): array
    {
        $pending = $this->pending();
        if (!$pending) {
            return [];
        }

        return [
            'newsletter_signup' => [
                'email' => $pending['email'],
                'requested_at' => $pending['requested_at'] ?? date('c'),
                'source' => $pending['source'] ?? 'home_newsletter',
                'status' => 'pending_payment',
            ],
        ];
    }

    public function registerSubscriber(string $email, string $accessLevel, string $planSlug, string $reference, string $source = 'subscription'): bool
    {
        $email = strtolower(trim($email));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !in_array($accessLevel, ['free', 'basic', 'premium'], true)) {
            return false;
        }

        $path = $this->storagePath();
        $rows = [];
        if (is_file($path)) {
            foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
                $columns = str_getcsv($line);
                if (!isset($columns[0]) || strcasecmp((string) $columns[0], $email) === 0) {
                    continue;
                }
                $rows[] = $columns;
            }
        }

        $rows[] = [
            $email,
            date('c'),
            $_SERVER['REMOTE_ADDR'] ?? '',
            $accessLevel,
            $planSlug,
            $accessLevel === 'free' ? 'free' : 'paid',
            $reference,
            $source,
        ];

        $handle = fopen($path, 'wb');
        if ($handle === false) {
            return false;
        }

        flock($handle, LOCK_EX);
        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }
        flock($handle, LOCK_UN);
        fclose($handle);

        return true;
    }

    public function registerPaidSubscriber(string $email, string $accessLevel, string $planSlug, string $reference, string $source = 'subscription'): bool
    {
        if (!in_array($accessLevel, ['basic', 'premium'], true)) {
            return false;
        }

        return $this->registerSubscriber($email, $accessLevel, $planSlug, $reference, $source);
    }

    public function digestForLevel(string $accessLevel): array
    {
        $accessLevel = in_array($accessLevel, ['free', 'basic', 'premium'], true) ? $accessLevel : 'free';

        $free = [
            [
                'title' => 'Platform care digest',
                'body' => 'Appointment reminders, report-upload tips, privacy notices, and care navigation updates.',
                'icon' => 'fa-calendar-check',
            ],
            [
                'title' => 'General health alerts',
                'body' => 'Seasonal prevention guidance, routine screening reminders, and emergency preparedness notes.',
                'icon' => 'fa-heart-pulse',
            ],
            [
                'title' => 'Patient education picks',
                'body' => 'Plain-language articles from the disease library and public guidelines.',
                'icon' => 'fa-book-medical',
            ],
        ];

        $premium = [
            [
                'title' => 'Clinical insight briefings',
                'body' => 'Deeper care pathways, symptom escalation signals, and specialist-preparation checklists.',
                'icon' => 'fa-user-doctor',
                'locked' => $accessLevel === 'free',
            ],
            [
                'title' => 'Advanced guideline updates',
                'body' => 'Curated protocol changes, consent workflows, and high-value safety guidance.',
                'icon' => 'fa-shield-heart',
                'locked' => $accessLevel === 'free',
            ],
            [
                'title' => 'Exclusive resources',
                'body' => 'Downloadable care planners, premium video explainers, and AI/telemedicine readiness notes.',
                'icon' => 'fa-crown',
                'locked' => $accessLevel === 'free',
            ],
        ];

        return [
            'free' => $free,
            'premium' => $premium,
        ];
    }

    private function storagePath(): string
    {
        return __DIR__ . '/../../storage/newsletter_subscribers.csv';
    }
}
