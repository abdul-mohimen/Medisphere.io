<?php
namespace App\Models;

class SubscriptionPlanCatalog
{
    private array $plans = [
        'free-access' => [
            'slug' => 'free-access',
            'name' => 'Free',
            'headline' => 'Generic platform access for exploring MediSphere',
            'price' => 0.00,
            'annual_price' => 0.00,
            'currency' => 'PKR',
            'interval' => 'month',
            'annual_interval' => 'year',
            'period_days' => 365,
            'annual_period_days' => 365,
            'badge' => 'Free',
            'access_level' => 'free',
            'requires_payment' => false,
            'featured' => false,
            'features' => [
                'Generic platform access and standard homepage views',
                'Basic newsletter version with platform updates',
                'Public articles, FAQ, and standard account access',
            ],
            'comparison' => [
                'Generic platform access' => true,
                'Standard homepage views' => true,
                'Basic newsletter version' => true,
                'Ad-free browsing' => false,
                'Standard disease library searches' => false,
                'Basic doctor/hospital mapping' => false,
                'Advanced clinical insights' => false,
                'Premium instructional video content' => false,
                'Detailed health guidelines' => false,
                'Full premium newsletter setup' => false,
            ],
        ],
        'basic-care' => [
            'slug' => 'basic-care',
            'name' => 'Lite',
            'headline' => 'Essential premium access for cleaner browsing and care discovery',
            'price' => 9.00,
            'annual_price' => 86.00,
            'currency' => 'USD',
            'interval' => 'month',
            'annual_interval' => 'year',
            'period_days' => 30,
            'annual_period_days' => 365,
            'badge' => 'Lite',
            'access_level' => 'basic',
            'requires_payment' => true,
            'featured' => false,
            'features' => [
                'Ad-free browsing across patient education areas',
                'Standard disease library searches and care preparation',
                'Basic doctor and hospital mapping access',
                'Basic newsletter upgrade for care reminders',
                'Secure subscription receipts and account history',
            ],
            'comparison' => [
                'Generic platform access' => true,
                'Standard homepage views' => true,
                'Basic newsletter version' => true,
                'Ad-free browsing' => true,
                'Standard disease library searches' => true,
                'Basic doctor/hospital mapping' => true,
                'Advanced clinical insights' => false,
                'Premium instructional video content' => false,
                'Detailed health guidelines' => false,
                'Full premium newsletter setup' => false,
            ],
        ],
        'premium-care' => [
            'slug' => 'premium-care',
            'name' => 'Pro',
            'headline' => 'Full unrestricted access to high-end clinical features',
            'price' => 29.00,
            'annual_price' => 278.00,
            'currency' => 'USD',
            'interval' => 'month',
            'annual_interval' => 'year',
            'period_days' => 30,
            'annual_period_days' => 365,
            'badge' => 'Premium',
            'access_level' => 'premium',
            'requires_payment' => true,
            'featured' => true,
            'value_badge' => 'Most Popular',
            'features' => [
                'Full unrestricted access to premium care tools',
                'Advanced clinical insights and detailed guidelines',
                'Premium instructional videos and full newsletter setup',
                'AI scanner, map, and care-discovery unlocks',
                'Video consultation workflow access',
            ],
            'comparison' => [
                'Generic platform access' => true,
                'Standard homepage views' => true,
                'Basic newsletter version' => true,
                'Ad-free browsing' => true,
                'Standard disease library searches' => true,
                'Basic doctor/hospital mapping' => true,
                'Advanced clinical insights' => true,
                'Premium instructional video content' => true,
                'Detailed health guidelines' => true,
                'Full premium newsletter setup' => true,
            ],
        ],
        'advanced-care' => [
            'slug' => 'advanced-care',
            'name' => 'Advanced',
            'headline' => 'Elite healthcare network access for maximum platform depth',
            'price' => 49.00,
            'annual_price' => 470.00,
            'currency' => 'USD',
            'interval' => 'month',
            'annual_interval' => 'year',
            'period_days' => 30,
            'annual_period_days' => 365,
            'badge' => 'Advanced',
            'access_level' => 'premium',
            'requires_payment' => true,
            'featured' => false,
            'features' => [
                'Everything in Pro with expanded care coordination',
                'Advanced hospital, pathogen, and global-care intelligence',
                'Priority premium newsletter and clinical education layer',
                'Enhanced consultation preparation and follow-up workflows',
                'Best fit for power users and complex care journeys',
            ],
            'comparison' => [
                'Generic platform access' => true,
                'Standard homepage views' => true,
                'Basic newsletter version' => true,
                'Ad-free browsing' => true,
                'Standard disease library searches' => true,
                'Basic doctor/hospital mapping' => true,
                'Advanced clinical insights' => true,
                'Premium instructional video content' => true,
                'Detailed health guidelines' => true,
                'Full premium newsletter setup' => true,
            ],
        ],
    ];

    public function all(): array
    {
        return array_values($this->plans);
    }

    public function find(string $slug): ?array
    {
        return $this->plans[$slug] ?? null;
    }

    public function findForBillingCycle(string $slug, string $billingCycle): ?array
    {
        $plan = $this->find($slug);
        if (!$plan) {
            return null;
        }

        return $this->withBillingCycle($plan, $billingCycle);
    }

    public function withBillingCycle(array $plan, string $billingCycle): array
    {
        $billingCycle = $billingCycle === 'yearly' ? 'yearly' : 'monthly';
        $plan['billing_cycle'] = $billingCycle;

        if ($billingCycle === 'yearly') {
            $plan['price'] = (float) ($plan['annual_price'] ?? $plan['price'] ?? 0);
            $plan['interval'] = (string) ($plan['annual_interval'] ?? 'year');
            $plan['period_days'] = (int) ($plan['annual_period_days'] ?? 365);
        }

        return $plan;
    }

    public function periodEnd(array $plan, ?int $start = null): string
    {
        $days = max(1, (int) ($plan['period_days'] ?? 30));
        $start = $start ?: time();
        return date('Y-m-d H:i:s', strtotime('+' . $days . ' days', $start));
    }
}
