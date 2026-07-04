<?php
$gate = $subscriptionGate ?? [];
$returnUrl = $gate['return_url'] ?? route_url('home');
$subscriptionPageUrl = $gate['subscription_page_url'] ?? route_url('payments/subscriptions', ['return_to' => $returnUrl]);
?>

<section class="premium-gate-shell glass-panel" data-aos="fade-up">
    <div class="premium-gate-media">
        <?php if (!empty($gateImage)): ?>
            <img src="<?= e($gateImage) ?>" alt="<?= e($gateHeading ?? 'Premium content') ?>" loading="eager">
        <?php endif; ?>
        <div class="premium-gate-lock">
            <i class="fa-solid fa-lock"></i>
        </div>
    </div>
    <div class="premium-gate-copy">
        <span class="eyebrow"><?= e($gateTitle ?? 'Premium access required') ?></span>
        <h1><?= e($gateHeading ?? 'This feature is premium') ?></h1>
        <p><?= e($gateDescription ?? 'Subscribe to unlock this premium MediSphere experience.') ?></p>
        <div class="premium-gate-actions">
            <a class="btn btn-primary" href="<?= e($subscriptionPageUrl) ?>">
                <i class="fa-solid fa-crown"></i>
                View Full-Page Plans
            </a>
            <a href="<?= route_url('payments') ?>" class="btn btn-outline-primary">
                <i class="fa-solid fa-credit-card"></i>
                Billing Center
            </a>
        </div>
    </div>
</section>
