<?php
$gatewayLabel = ucwords(str_replace('_', ' ', $internationalGateway ?? 'verifone_2checkout'));
$currentPlan = $currentSubscription['plan_slug'] ?? '';
$newsletterIntent = !empty($newsletterIntent);
$newsletterEmail = $newsletterPending['email'] ?? '';
$digest = $newsletterDigest ?? ['free' => [], 'premium' => []];
$plans = $plans ?? [];
$featureRows = [
    'Generic platform access',
    'Standard homepage views',
    'Basic newsletter version',
    'Ad-free browsing',
    'Standard disease library searches',
    'Basic doctor/hospital mapping',
    'Advanced clinical insights',
    'Premium instructional video content',
    'Detailed health guidelines',
    'Full premium newsletter setup',
];
$displayPlanName = static function (array $plan): string {
    return (string) ($plan['name'] ?? 'Plan');
};
$priceLabel = static function (float $price): string {
    return number_format($price, $price > 0 && floor($price) !== $price ? 2 : 0);
};
$featureIcon = static function (bool $included): string {
    if ($included) {
        return '<svg class="pricing-feature-svg included" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M20 6 9 17l-5-5"/></svg>';
    }

    return '<svg class="pricing-feature-svg locked" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M18 6 6 18M6 6l12 12"/></svg>';
};
?>

<section class="pricing-shell" data-aos="fade-up" data-subscription-pricing data-billing-cycle="monthly">
    <div class="pricing-hero">
        <div class="pricing-hero-copy">
            <span class="eyebrow">Secure subscription checkout</span>
            <h1>Choose the access tier that matches your care journey.</h1>
            <p>Start free, move into focused care discovery with Lite, or choose Pro and Advanced for unrestricted clinical insights, premium learning content, and the full newsletter intelligence layer.</p>
            <div class="pricing-hero-actions">
                <a href="#pricingPlans" class="btn btn-primary"><i class="fa-solid fa-layer-group"></i> View Packages</a>
                <a href="#planComparison" class="btn btn-outline-primary"><i class="fa-solid fa-table-list"></i> Compare Features</a>
            </div>
        </div>
        <div class="pricing-checkout-panel">
            <div class="pricing-lock-badge"><i class="fa-solid fa-lock"></i></div>
            <span>Current access</span>
            <strong><?= e(ucfirst($currentAccessLevel ?? 'free')) ?></strong>
            <?php if (!empty($currentSubscription['current_period_end'])): ?>
                <small>Valid until <?= e($currentSubscription['current_period_end']) ?></small>
            <?php else: ?>
                <small>Choose a plan to activate tiered access</small>
            <?php endif; ?>
            <div class="pricing-provider-row">
                <i class="fa-solid fa-shield-halved"></i>
                <span><?= e($gatewayLabel) ?> hosted checkout</span>
            </div>
        </div>
    </div>

    <?php if ($newsletterIntent): ?>
        <div class="pricing-newsletter-intent">
            <div>
                <span class="eyebrow">Newsletter signup</span>
                <h2>Complete a package choice to finish newsletter access</h2>
                <p><?= $newsletterEmail ? 'Pending email: ' . e($newsletterEmail) . '. ' : '' ?>Free receives the basic digest. Lite, Pro, and Advanced register paid newsletter access only after payment confirmation.</p>
            </div>
            <i class="fa-solid fa-envelope-circle-check"></i>
        </div>
    <?php endif; ?>

    <div class="pricing-control-row">
        <div class="pricing-billing-toggle" role="tablist" aria-label="Billing cycle">
            <button type="button" class="active" data-billing-cycle-button="monthly" aria-pressed="true">Monthly</button>
            <button type="button" data-billing-cycle-button="yearly" aria-pressed="false">Yearly <span>Save up to 20%</span></button>
        </div>
        <div class="pricing-trust-row">
            <span><i class="fa-solid fa-circle-check"></i> Free standard option</span>
            <span><i class="fa-solid fa-user-shield"></i> Patient-only subscription security</span>
            <span><i class="fa-brands fa-cc-visa"></i> Visa</span>
            <span><i class="fa-brands fa-cc-mastercard"></i> Mastercard</span>
            <span><i class="fa-solid fa-building-columns"></i> <?= e($settlementAccount ?? 'Connected Pakistani settlement account') ?></span>
        </div>
    </div>
</section>

<section class="pricing-plan-grid" id="pricingPlans" data-aos="fade-up">
    <?php foreach ($plans as $plan): ?>
        <?php
        $isCurrent = $currentPlan === ($plan['slug'] ?? '');
        $isFree = empty($plan['requires_payment']);
        $level = (string) ($plan['access_level'] ?? 'free');
        $isPro = ($plan['slug'] ?? '') === 'premium-care';
        $monthlyPrice = (float) ($plan['price'] ?? 0);
        $yearlyPrice = (float) ($plan['annual_price'] ?? $monthlyPrice);
        $monthlyPkr = (float) ($plan['price_pkr'] ?? 0);
        $yearlyPkr = (float) ($plan['annual_price_pkr'] ?? $monthlyPkr);
        $comparison = $plan['comparison'] ?? [];
        ?>
        <article class="pricing-plan-card <?= !empty($plan['featured']) ? 'featured' : '' ?> <?= $isCurrent ? 'active' : '' ?> <?= $isPro ? 'pro' : '' ?>">
            <?php if ($isPro): ?>
                <div class="pricing-value-badge"><?= e($plan['value_badge'] ?? 'Best Value') ?></div>
            <?php endif; ?>
            <div class="pricing-plan-top">
                <span class="subscription-plan-badge"><?= e($plan['badge'] ?? ucfirst($level)) ?></span>
                <?php if ($isCurrent): ?><span class="subscription-active-badge"><i class="fa-solid fa-circle-check"></i> Active</span><?php endif; ?>
            </div>
            <h2><?= e($displayPlanName($plan)) ?></h2>
            <p><?= e($plan['headline'] ?? '') ?></p>
            <div class="pricing-price"
                 data-plan-price
                 data-currency="<?= e($plan['currency'] ?? 'USD') ?>"
                 data-monthly-price="<?= e($priceLabel($monthlyPrice)) ?>"
                 data-yearly-price="<?= e($priceLabel($yearlyPrice)) ?>"
                 data-monthly-interval="<?= e($plan['interval'] ?? 'month') ?>"
                 data-yearly-interval="<?= e($plan['annual_interval'] ?? 'year') ?>">
                <?php if ($isFree): ?>
                    <strong data-price-amount>Free</strong><small data-price-interval>/ <?= e($plan['interval'] ?? 'month') ?></small>
                <?php else: ?>
                    <span data-price-currency><?= e($plan['currency'] ?? 'USD') ?></span><strong data-price-amount><?= e($priceLabel($monthlyPrice)) ?></strong><small data-price-interval>/ <?= e($plan['interval'] ?? 'month') ?></small>
                <?php endif; ?>
            </div>
            <div class="subscription-pkr-note"
                 data-plan-pkr
                 data-monthly-pkr="<?= e(number_format($monthlyPkr, 0)) ?>"
                 data-yearly-pkr="<?= e(number_format($yearlyPkr, 0)) ?>">
                <i class="fa-solid fa-receipt"></i>
                <span>Invoice amount: <strong data-pkr-amount><?= number_format($monthlyPkr, 0) ?></strong> PKR</span>
            </div>
            <ul class="pricing-feature-list">
                <?php foreach ($featureRows as $feature): ?>
                    <?php $included = !empty($comparison[$feature]); ?>
                    <li class="<?= $included ? 'included' : 'locked' ?>">
                        <span class="pricing-feature-icon"><?= $featureIcon($included) ?></span>
                        <span><?= e($feature) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>

            <?php if (!$isAuthenticated): ?>
                <div class="subscription-auth-actions">
                    <a href="<?= route_url('login', ['return_to' => $returnTo]) ?>" class="btn <?= !empty($plan['featured']) ? 'btn-primary' : 'btn-outline-primary' ?> w-100 subscription-cta">
                        <i class="fa-solid fa-credit-card"></i>
                        Subscribe to Pay
                    </a>
                </div>
            <?php elseif (!$isPatient): ?>
                <button class="btn btn-outline-primary w-100 subscription-cta" disabled>
                    <i class="fa-solid fa-user-shield"></i>
                    Patient Plan Only
                </button>
            <?php elseif ($isCurrent): ?>
                <button class="btn btn-outline-primary w-100 subscription-cta" disabled>
                    <i class="fa-solid fa-circle-check"></i>
                    Current Plan
                </button>
            <?php elseif ($isFree): ?>
                <form method="POST" action="<?= route_url('payments/subscription/free') ?>" class="mt-auto" data-subscription-checkout-form>
                    <?= csrf_field() ?>
                    <input type="hidden" name="return_to" value="<?= e($returnTo) ?>">
                    <input type="hidden" name="billing_cycle" value="monthly" data-billing-cycle-input>
                    <?php if ($newsletterIntent): ?><input type="hidden" name="source" value="newsletter"><?php endif; ?>
                    <button class="btn btn-outline-primary w-100 subscription-cta pricing-pay-button" data-loading-label="Activating Securely...">
                        <span class="pricing-button-label"><i class="fa-solid fa-unlock"></i> Activate Free Access</span>
                    </button>
                </form>
            <?php else: ?>
                <form method="POST" action="<?= route_url('payments/subscription/checkout') ?>" class="mt-auto" data-subscription-checkout-form>
                    <?= csrf_field() ?>
                    <input type="hidden" name="plan_slug" value="<?= e($plan['slug']) ?>">
                    <input type="hidden" name="return_to" value="<?= e($returnTo) ?>">
                    <input type="hidden" name="billing_cycle" value="monthly" data-billing-cycle-input>
                    <?php if ($newsletterIntent): ?><input type="hidden" name="source" value="newsletter"><?php endif; ?>
                    <button class="btn <?= !empty($plan['featured']) ? 'btn-primary' : 'btn-outline-primary' ?> w-100 subscription-cta pricing-pay-button" data-loading-label="Processing Securely...">
                        <span class="pricing-button-label"><i class="fa-solid fa-credit-card"></i> Pay Now</span>
                    </button>
                </form>
            <?php endif; ?>
        </article>
    <?php endforeach; ?>
</section>

<section class="pricing-compare-section glass-panel" id="planComparison" data-aos="fade-up">
    <div class="newsletter-tier-head">
        <div>
            <span class="eyebrow">Feature tiering</span>
            <h2>Free vs Lite vs Pro vs Advanced at a glance</h2>
        </div>
        <span class="newsletter-secure-pill"><i class="fa-solid fa-shield-halved"></i> Stored as free / basic / premium</span>
    </div>
    <div class="pricing-compare-table">
        <div class="pricing-compare-row pricing-compare-head">
            <span>Feature</span>
            <?php foreach ($plans as $plan): ?>
                <strong><?= e($displayPlanName($plan)) ?></strong>
            <?php endforeach; ?>
        </div>
        <?php foreach ($featureRows as $feature): ?>
            <div class="pricing-compare-row">
                <span><?= e($feature) ?></span>
                <?php foreach ($plans as $plan): ?>
                    <?php $included = !empty(($plan['comparison'] ?? [])[$feature]); ?>
                    <strong class="<?= $included ? 'included' : 'locked' ?>"><?= $featureIcon($included) ?><span><?= $included ? 'Included' : 'Locked' ?></span></strong>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<section class="newsletter-tier-section glass-panel" id="newsletterTiers" data-aos="fade-up">
    <div class="newsletter-tier-head">
        <div>
            <span class="eyebrow">Newsletter engine</span>
            <h2>Free digest vs paid clinical intelligence</h2>
        </div>
        <span class="newsletter-secure-pill"><i class="fa-solid fa-lock"></i> Tier-aware delivery</span>
    </div>
    <div class="newsletter-tier-grid">
        <div class="newsletter-tier-column">
            <div class="newsletter-tier-title free"><i class="fa-solid fa-bell"></i><span>Free version</span></div>
            <?php foreach (($digest['free'] ?? []) as $item): ?>
                <article class="newsletter-tier-item">
                    <i class="fa-solid <?= e($item['icon'] ?? 'fa-circle-check') ?>"></i>
                    <div><strong><?= e($item['title']) ?></strong><p><?= e($item['body']) ?></p></div>
                </article>
            <?php endforeach; ?>
        </div>
        <div class="newsletter-tier-column premium">
            <div class="newsletter-tier-title premium"><i class="fa-solid fa-crown"></i><span>Paid Lite + Pro + Advanced</span></div>
            <?php foreach (($digest['premium'] ?? []) as $item): ?>
                <article class="newsletter-tier-item <?= !empty($item['locked']) ? 'locked' : '' ?>">
                    <i class="fa-solid <?= e($item['icon'] ?? 'fa-lock') ?>"></i>
                    <div><strong><?= e($item['title']) ?></strong><p><?= e($item['body']) ?></p></div>
                    <?php if (!empty($item['locked'])): ?><span class="newsletter-lock-label"><i class="fa-solid fa-lock"></i> Paid</span><?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
