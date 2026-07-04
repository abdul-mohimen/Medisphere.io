<?php
namespace App\Services;

use App\Core\Auth;
use App\Models\SubscriptionPlanCatalog;
use App\Models\User;
use App\Models\UserSubscription;
use App\Services\NotificationService;

class SubscriptionAccess
{
    public function isPatientSubscribed(?int $userId = null): bool
    {
        if ($userId === null) {
            if (!Auth::check() || Auth::type() !== 'patient') {
                return false;
            }
            $userId = Auth::id();
        }

        if ($this->isQaPremiumUser((int) $userId)) {
            return true;
        }

        try {
            $this->enforceExpiry((int) $userId);
            return (new UserSubscription())->activeForUser((int) $userId) !== null;
        } catch (\Throwable) {
            return false;
        }
    }

    public function currentSubscription(?int $userId = null): ?array
    {
        if ($userId === null) {
            if (!Auth::check() || Auth::type() !== 'patient') {
                return null;
            }
            $userId = Auth::id();
        }

        try {
            $this->enforceExpiry((int) $userId);
            $model = new UserSubscription();
            $subscription = $model->activeForUser((int) $userId) ?: $model->latestForUser((int) $userId);
            if ($subscription) {
                return $subscription;
            }

            return $this->isQaPremiumUser((int) $userId) ? $this->qaPremiumSubscription((int) $userId) : null;
        } catch (\Throwable) {
            return $this->isQaPremiumUser((int) $userId) ? $this->qaPremiumSubscription((int) $userId) : null;
        }
    }

    public function shouldGatePatientFeature(string $requiredLevel = 'premium'): bool
    {
        if (!Auth::check() || Auth::type() !== 'patient') {
            return true;
        }
        return !$this->canAccessLevel($requiredLevel, Auth::id());
    }

    public function canViewPremiumContent(): bool
    {
        return $this->canAccessLevel('premium');
    }

    public function requiredLevelForArticle(array $article): string
    {
        if (isset($article['access_level'])) {
            return $this->normalizeLevel((string) $article['access_level']);
        }

        if (($article['media_type'] ?? '') === 'video') {
            return 'premium';
        }

        if (in_array(($article['category'] ?? ''), ['Privacy', 'Medication safety', 'Hospital management', 'Care continuity'], true)) {
            return 'basic';
        }

        return 'free';
    }

    public function isPremiumArticle(array $article): bool
    {
        return $this->requiredLevelForArticle($article) !== 'free';
    }

    public function annotateArticles(array $articles): array
    {
        return array_map(function (array $article): array {
            $requiredLevel = $this->requiredLevelForArticle($article);
            $article['required_level'] = $requiredLevel;
            $article['is_premium'] = $requiredLevel !== 'free';
            $article['is_locked'] = !$this->canAccessLevel($requiredLevel);
            return $article;
        }, $articles);
    }

    public function plans(): array
    {
        return array_map(function (array $plan): array {
            $plan['price_pkr'] = $this->amountToPkr((float) $plan['price'], (string) ($plan['currency'] ?? 'USD'));
            $plan['annual_price_pkr'] = $this->amountToPkr((float) ($plan['annual_price'] ?? $plan['price'] ?? 0), (string) ($plan['currency'] ?? 'USD'));
            return $plan;
        }, (new SubscriptionPlanCatalog())->all());
    }

    public function amountToPkr(float $amount, string $currency = 'USD'): float
    {
        if (strtoupper($currency) === 'PKR') {
            return $amount;
        }

        $rate = (float) config('services.subscription_usd_to_pkr_rate', 278.00);
        return round($amount * max($rate, 1), 2);
    }

    public function accessLevel(?int $userId = null): string
    {
        if ($userId === null && (!Auth::check() || Auth::type() !== 'patient')) {
            return Auth::check() && in_array(Auth::type(), ['doctor', 'hospital', 'admin'], true) ? 'premium' : 'free';
        }

        $userId = $userId ?? Auth::id();
        if (!$userId) {
            return 'free';
        }

        if ($this->isQaPremiumUser((int) $userId)) {
            return 'premium';
        }

        $this->enforceExpiry((int) $userId);
        $subscription = (new UserSubscription())->activeForUser((int) $userId);
        if (!$subscription) {
            return 'free';
        }

        return (string) ($subscription['access_level'] ?? $this->levelFromPlan((string) ($subscription['plan_slug'] ?? 'free-access')));
    }

    public function canAccessLevel(string $requiredLevel, ?int $userId = null): bool
    {
        $requiredLevel = $this->normalizeLevel($requiredLevel);
        return $this->levelRank($this->accessLevel($userId)) >= $this->levelRank($requiredLevel);
    }

    public function levelFromPlan(string $planSlug): string
    {
        if (in_array($planSlug, ['care-plus', 'basic-care'], true)) {
            return 'basic';
        }

        if (in_array($planSlug, ['family-guard', 'global-care', 'premium-care'], true)) {
            return 'premium';
        }

        $plan = (new SubscriptionPlanCatalog())->find($planSlug);
        return $this->normalizeLevel((string) ($plan['access_level'] ?? 'free'));
    }

    public function normalizeLevel(string $level): string
    {
        return in_array($level, ['free', 'basic', 'premium'], true) ? $level : 'free';
    }

    public function levelRank(string $level): int
    {
        return match ($this->normalizeLevel($level)) {
            'premium' => 3,
            'basic' => 2,
            default => 1,
        };
    }

    public function enforceExpiry(?int $userId = null): void
    {
        if ($userId === null) {
            if (!Auth::check() || Auth::type() !== 'patient') {
                return;
            }
            $userId = Auth::id();
        }

        if ($this->isQaPremiumUser((int) $userId)) {
            return;
        }

        try {
            $subscriptionModel = new UserSubscription();
            $expired = $subscriptionModel->expireDueForUser((int) $userId);
            if (!$expired) {
                return;
            }

            if (!$subscriptionModel->hasActiveForUser((int) $userId)) {
                (new User())->updateSubscriptionStatus((int) $userId, 'expired');
            }

            foreach ($expired as $subscription) {
                $plan = (new SubscriptionPlanCatalog())->find((string) ($subscription['plan_slug'] ?? ''));
                (new NotificationService())->sendToUser((int) $userId, 'subscription_expired_patient', [
                    'plan_name' => $plan['name'] ?? ucwords(str_replace('-', ' ', (string) ($subscription['plan_slug'] ?? 'subscription'))),
                    'expired_at' => $subscription['current_period_end'] ?? date('Y-m-d H:i:s'),
                ], [
                    'category' => 'payment',
                    'type' => 'warning',
                    'action_url' => route_url('payments/subscriptions'),
                ]);
            }
        } catch (\Throwable) {
            return;
        }
    }

    public function modalData(string $featureTitle, string $featureDescription, string $returnUrl, bool $autoOpen = false): array
    {
        return [
            'subscriptionModal' => true,
            'subscriptionGate' => [
                'feature_title' => $featureTitle,
                'feature_description' => $featureDescription,
                'plans' => $this->plans(),
                'return_url' => $this->safeReturnUrl($returnUrl),
                'subscription_page_url' => route_url('payments/subscriptions', ['return_to' => $this->safeReturnUrl($returnUrl)]),
                'auto_open' => $autoOpen,
                'is_authenticated' => Auth::check(),
                'is_patient' => Auth::type() === 'patient',
                'active_subscription' => Auth::check() && Auth::type() === 'patient' ? $this->currentSubscription(Auth::id()) : null,
            ],
        ];
    }

    public function safeReturnUrl(string $url): string
    {
        return Auth::safeReturnUrl($url, route_url('payments'));
    }

    private function isQaPremiumUser(?int $userId = null): bool
    {
        $emails = config('features.qa_premium_emails', []);
        if (!is_array($emails) || empty($emails)) {
            return false;
        }

        $normalizedAllowlist = array_map(
            static fn($email): string => strtolower(trim((string) $email)),
            $emails
        );

        $email = '';
        try {
            if ($userId) {
                $email = (string) ((new User())->findById((int) $userId)['email'] ?? '');
            } elseif (Auth::check()) {
                $email = (string) (Auth::user()['email'] ?? '');
            }
        } catch (\Throwable) {
            return false;
        }

        return $email !== '' && in_array(strtolower(trim($email)), $normalizedAllowlist, true);
    }

    private function qaPremiumSubscription(int $userId): array
    {
        return [
            'id' => 0,
            'user_id' => $userId,
            'plan_slug' => 'premium-care',
            'access_level' => 'premium',
            'provider' => 'qa_allowlist',
            'transaction_reference' => 'QA-PRO-ASDF1223-GMAIL-COM',
            'status' => 'subscribed',
            'amount' => 0.00,
            'currency' => 'USD',
            'current_period_start' => '2026-06-02 00:00:00',
            'current_period_end' => '2099-12-31 23:59:59',
            'metadata' => json_encode([
                'source' => 'qa_premium_email_allowlist',
                'note' => 'Permanent local QA premium entitlement from config/features.qa_premium_emails.',
            ]),
        ];
    }
}
