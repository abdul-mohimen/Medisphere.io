<?php
$gate = $subscriptionGate ?? [];
$plans = $gate['plans'] ?? [];
$featureTitle = $gate['feature_title'] ?? 'Premium access';
$featureDescription = $gate['feature_description'] ?? 'Choose a premium plan to unlock advanced MediSphere features.';
$returnUrl = $gate['return_url'] ?? route_url('payments');
$subscriptionPageUrl = $gate['subscription_page_url'] ?? route_url('payments/subscriptions', ['return_to' => $returnUrl]);
$isAuthenticated = !empty($gate['is_authenticated']);
$isPatient = !empty($gate['is_patient']);
$activeSubscription = $gate['active_subscription'] ?? null;
$currentPlan = (string) ($activeSubscription['plan_slug'] ?? '');
$modalId = 'subscriptionGateModal';

$modalPlans = array_values(array_filter($plans, static fn(array $plan): bool => !empty($plan['requires_payment'])));
$modalPlans = array_slice($modalPlans, 0, 3);
$modalDescriptions = [
    'basic-care' => 'Core premium discovery tools.',
    'premium-care' => 'Full premium clinical intelligence.',
    'advanced-care' => 'Maximum care coordination access.',
];

$priceLabel = static function (float $price): string {
    return number_format($price, $price > 0 && floor($price) !== $price ? 2 : 0);
};
?>

<div class="modal fade subscription-gate-modal premium-access-modal" id="<?= e($modalId) ?>" tabindex="-1" aria-hidden="true" data-subscription-auto-open="<?= !empty($gate['auto_open']) ? '1' : '0' ?>" data-subscription-page-url="<?= e($subscriptionPageUrl) ?>">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <button type="button" class="premium-access-close" data-bs-dismiss="modal" aria-label="Close premium access modal">
                <i class="fa-solid fa-xmark" aria-hidden="true"></i>
            </button>

            <div class="modal-body">
                <section class="premium-access-shell" data-subscription-pricing data-billing-cycle="monthly">
                    <div class="premium-access-heading">
                        <span class="premium-access-offer"><i class="fa-solid fa-tags"></i> Save 20% with yearly billing</span>
                        <h2><?= e($featureTitle) ?></h2>
                        <p><?= e($featureDescription) ?></p>
                    </div>

                    <div class="premium-access-toggle-wrap">
                        <div class="pricing-billing-toggle premium-access-toggle" role="tablist" aria-label="Billing cycle">
                            <button type="button" class="active" data-billing-cycle-button="monthly" aria-pressed="true">Monthly</button>
                            <button type="button" data-billing-cycle-button="yearly" aria-pressed="false">Yearly</button>
                        </div>
                    </div>

                    <?php if (!$isAuthenticated): ?>
                        <div class="premium-access-note">
                            <i class="fa-solid fa-lock"></i>
                            <span>Sign in or create a patient account to attach premium access securely to your profile.</span>
                        </div>
                    <?php elseif (!$isPatient): ?>
                        <div class="premium-access-note">
                            <i class="fa-solid fa-user-shield"></i>
                            <span>Premium subscriptions are available for patient accounts. Professional accounts can continue using their workspace tools.</span>
                        </div>
                    <?php endif; ?>

                    <div class="premium-access-grid">
                        <?php foreach ($modalPlans as $plan): ?>
                            <?php
                            $slug = (string) ($plan['slug'] ?? '');
                            $isCurrent = $currentPlan === $slug;
                            $isFeatured = !empty($plan['featured']);
                            $monthlyPrice = (float) ($plan['price'] ?? 0);
                            $yearlyPrice = (float) ($plan['annual_price'] ?? $monthlyPrice);
                            $features = array_slice($plan['features'] ?? [], 0, 5);
                            ?>
                            <article class="premium-access-card <?= $isFeatured ? 'featured' : '' ?>">
                                <h3><?= e($plan['name'] ?? 'Plan') ?></h3>
                                <div class="premium-access-price"
                                     data-plan-price
                                     data-currency="<?= e($plan['currency'] ?? 'USD') ?>"
                                     data-monthly-price="<?= e($priceLabel($monthlyPrice)) ?>"
                                     data-yearly-price="<?= e($priceLabel($yearlyPrice)) ?>"
                                     data-monthly-interval="<?= e($plan['interval'] ?? 'month') ?>"
                                     data-yearly-interval="<?= e($plan['annual_interval'] ?? 'year') ?>">
                                    <span data-price-currency><?= e($plan['currency'] ?? 'USD') ?></span>
                                    <strong data-price-amount><?= e($priceLabel($monthlyPrice)) ?></strong>
                                    <small data-price-interval>/ <?= e($plan['interval'] ?? 'month') ?></small>
                                </div>
                                <p><?= e($modalDescriptions[$slug] ?? ($plan['headline'] ?? 'Premium healthcare access for advanced features.')) ?></p>

                                <?php if (!$isAuthenticated): ?>
                                    <a href="<?= route_url('login', ['return_to' => $returnUrl]) ?>" class="btn <?= $isFeatured ? 'btn-primary' : 'btn-outline-primary' ?> premium-access-cta">
                                        <i class="fa-solid fa-right-to-bracket"></i>
                                        Choose Plan
                                    </a>
                                <?php elseif (!$isPatient): ?>
                                    <button class="btn btn-outline-primary premium-access-cta" type="button" disabled>
                                        <i class="fa-solid fa-user-shield"></i>
                                        Patient Plan Only
                                    </button>
                                <?php elseif ($isCurrent): ?>
                                    <button class="btn btn-outline-primary premium-access-cta" type="button" disabled>
                                        <i class="fa-solid fa-circle-check"></i>
                                        Current Plan
                                    </button>
                                <?php else: ?>
                                    <form method="POST" action="<?= route_url('payments/subscription/checkout') ?>" class="premium-access-form" data-subscription-checkout-form>
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="plan_slug" value="<?= e($slug) ?>">
                                        <input type="hidden" name="return_to" value="<?= e($returnUrl) ?>">
                                        <input type="hidden" name="billing_cycle" value="monthly" data-billing-cycle-input>
                                        <button class="btn <?= $isFeatured ? 'btn-primary' : 'btn-outline-primary' ?> premium-access-cta" type="submit" data-loading-label="Opening Secure Checkout...">
                                            <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                            <?= $isFeatured ? 'Upgrade' : 'Choose Plan' ?>
                                        </button>
                                    </form>
                                <?php endif; ?>

                                <ul class="premium-access-feature-list">
                                    <?php foreach ($features as $feature): ?>
                                        <li><i class="fa-solid fa-check"></i><span><?= e($feature) ?></span></li>
                                    <?php endforeach; ?>
                                </ul>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </section>
            </div>
        </div>
    </div>
</div>
