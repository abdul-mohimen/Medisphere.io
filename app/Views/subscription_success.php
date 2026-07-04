<?php
$planName = $plan['name'] ?? ucwords(str_replace('-', ' ', (string) ($subscription['plan_slug'] ?? 'Premium')));
$amountPkrText = number_format((float) $amountPkr, 0);
$status = $subscription['status'] ?? 'pending';
$purchasedAt = $payment['paid_at'] ?? $subscription['current_period_start'] ?? $subscription['created_at'] ?? '';
$expiresAt = $subscription['current_period_end'] ?? '';
$accessLevel = $accessLevel ?? ($subscription['access_level'] ?? ($plan['access_level'] ?? 'free'));
$activeEyebrow = match ($accessLevel) {
    'premium' => 'Premium unlocked',
    'basic' => 'Lite access active',
    default => 'Free access active',
};
$activeMessage = match ($accessLevel) {
    'premium' => 'Premium articles, video features, AI scanner access, video consultations, and advanced map workflows are now unlocked.',
    'basic' => 'Lite premium articles and advanced doctor/hospital discovery are now unlocked. Pro and Advanced video, AI scanner, consultation, and advanced map tools remain locked.',
    default => 'Your Free Access plan is active with public articles, guidelines, FAQ, patient education, and standard account access. Premium features remain locked until you choose a paid plan.',
};
?>

<section class="subscription-confirmation-shell <?= $isActive ? 'is-active' : 'is-pending' ?>" data-aos="fade-up">
    <div class="subscription-confirmation-icon">
        <i class="fa-solid <?= $isActive ? 'fa-circle-check' : 'fa-clock' ?>"></i>
    </div>
    <div class="subscription-confirmation-copy">
        <span class="eyebrow"><?= e($isActive ? $activeEyebrow : 'Secure confirmation') ?></span>
        <?php if ($isActive): ?>
            <h1>Your subscription has been successfully activated for <?= e($amountPkrText) ?> PKR!</h1>
            <p>Your <?= e($planName) ?> plan is active. <?= e($activeMessage) ?></p>
        <?php else: ?>
            <h1>We are confirming your subscription payment.</h1>
            <p>Your checkout was received. The provider webhook is still confirming the final payment approval, and this page will refresh automatically.</p>
            <meta http-equiv="refresh" content="5">
        <?php endif; ?>
    </div>

    <div class="subscription-confirmation-receipt">
        <div>
            <span>Plan</span>
            <strong><?= e($planName) ?></strong>
        </div>
        <div>
            <span>Reference</span>
            <strong><?= e($subscription['transaction_reference'] ?? '') ?></strong>
        </div>
        <div>
            <span>Paid amount</span>
            <strong><?= e($amountPkrText) ?> PKR</strong>
        </div>
        <div>
            <span>Status</span>
            <strong><?= e(ucfirst((string) $status)) ?></strong>
        </div>
        <?php if (!empty($subscription['current_period_end'])): ?>
            <div>
                <span>Valid until</span>
                <strong><?= e($subscription['current_period_end']) ?></strong>
            </div>
        <?php endif; ?>
        <div>
            <span>Purchased at</span>
            <strong><?= e($purchasedAt) ?></strong>
        </div>
        <?php if ($expiresAt): ?>
            <div>
                <span>Expiry date and time</span>
                <strong><?= e($expiresAt) ?></strong>
            </div>
        <?php endif; ?>
    </div>

    <div class="subscription-confirmation-actions">
        <a href="<?= e($returnTo ?: route_url('payments')) ?>" class="btn btn-primary">
            <i class="fa-solid fa-arrow-right"></i>
            Continue
        </a>
        <a href="<?= route_url('payments') ?>" class="btn btn-outline-primary">
            <i class="fa-solid fa-receipt"></i>
            Billing Center
        </a>
        <?php if (!empty($invoiceUrl)): ?>
            <a href="<?= e($invoiceUrl) ?>" class="btn btn-outline-primary">
                <i class="fa-solid fa-file-invoice-dollar"></i>
                Detailed Invoice
            </a>
        <?php endif; ?>
    </div>

    <?php if (!empty($newsletterSignupStatus)): ?>
        <div class="premium-inline-banner mt-4">
            <div>
                <span class="eyebrow">Newsletter signup</span>
                <?php if ($newsletterSignupStatus === 'paid_registered'): ?>
                    <h2>Paid newsletter access is active</h2>
                    <p>Your email is registered for paid update access for this <?= e($planName) ?> subscription.</p>
                <?php elseif ($newsletterSignupStatus === 'free_registered'): ?>
                    <h2>Free newsletter digest is active</h2>
                    <p>Your email is registered for standard platform updates and general health alerts. Paid clinical insights and exclusive resources remain locked.</p>
                <?php elseif ($newsletterSignupStatus === 'free_registration_failed'): ?>
                    <h2>Free newsletter registration needs attention</h2>
                    <p>Free access is active, but the basic newsletter registration could not be saved. Please try again from the home page.</p>
                <?php elseif ($newsletterSignupStatus === 'pending_payment'): ?>
                    <h2>Newsletter signup is waiting for payment confirmation</h2>
                    <p>Your email will be registered only after the payment provider confirms a paid subscription.</p>
                <?php else: ?>
                    <h2>Newsletter registration needs attention</h2>
                    <p>Your subscription is active, but the newsletter registration could not be saved. Please try again from the home page.</p>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</section>
