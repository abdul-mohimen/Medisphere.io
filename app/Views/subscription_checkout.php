<?php
$gatewayLabel = ucwords(str_replace('_', ' ', $provider ?? 'gateway'));
$redirectUrl = $result['redirect_url'] ?? '';
$reference = $subscription['transaction_reference'] ?? '';
?>

<section class="subscription-checkout-shell glass-panel" data-aos="fade-up">
    <div class="subscription-checkout-copy">
        <span class="eyebrow">Secure checkout step</span>
        <h1><?= !empty($isReady) ? 'Your checkout is ready' : 'Gateway setup needs attention' ?></h1>
        <p><?= !empty($isReady) ? 'Review your plan, then continue to the certified hosted payment page. Card details are never stored on MediSphere.' : e($result['message'] ?? 'The payment provider did not return a hosted checkout URL.') ?></p>
    </div>

    <div class="subscription-confirmation-receipt">
        <div><span>Plan</span><strong><?= e($plan['name'] ?? 'Subscription') ?></strong></div>
        <div><span>Reference</span><strong><?= e($reference) ?></strong></div>
        <div><span>USD amount</span><strong><?= e($plan['currency'] ?? 'USD') ?> <?= number_format((float) ($plan['price'] ?? 0), 2) ?></strong></div>
        <div><span>PKR invoice amount</span><strong><?= number_format((float) $amountPkr, 0) ?> PKR</strong></div>
        <div><span>Provider</span><strong><?= e($gatewayLabel) ?></strong></div>
        <div><span>Status</span><strong><?= e(ucfirst((string) ($subscription['status'] ?? 'pending'))) ?></strong></div>
    </div>

    <div class="checkout-security-strip mt-4">
        <span><i class="fa-solid fa-lock"></i> Hosted provider collects card details securely</span>
        <span><i class="fa-solid fa-bolt"></i> Webhook approval activates access and generates invoice timing</span>
    </div>

    <div class="subscription-confirmation-actions">
        <?php if (!empty($isReady)): ?>
            <a href="<?= e($redirectUrl) ?>" class="btn btn-primary">
                <i class="fa-solid fa-arrow-up-right-from-square"></i>
                Continue to <?= e($gatewayLabel) ?>
            </a>
            <a href="<?= route_url('payments/subscription/success', ['ref' => $reference]) ?>" class="btn btn-outline-primary">
                <i class="fa-solid fa-clock"></i>
                View Pending Confirmation
            </a>
        <?php else: ?>
            <a href="<?= route_url('payments/subscriptions', ['return_to' => $returnTo]) ?>" class="btn btn-outline-primary">
                <i class="fa-solid fa-arrow-left"></i>
                Back to Plans
            </a>
        <?php endif; ?>
    </div>
</section>
