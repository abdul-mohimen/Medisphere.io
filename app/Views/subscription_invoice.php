<?php
$planName = $plan['name'] ?? ucwords(str_replace('-', ' ', (string) ($subscription['plan_slug'] ?? 'Subscription')));
$amountPkrText = number_format((float) $amountPkr, 0);
?>

<section class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
        <span class="eyebrow">Subscription invoice</span>
        <h1 class="mb-0"><?= e($subscription['transaction_reference'] ?? 'Subscription') ?></h1>
        <p class="text-muted mb-0 mt-2">Review subscription tier, provider reference, access window, and printable receipt details.</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <button class="btn btn-primary" onclick="window.print()"><i class="fa-solid fa-print"></i> Print / Save PDF</button>
        <a href="<?= route_url('payments') ?>" class="btn btn-outline-primary">Back to Payments</a>
    </div>
</section>

<div class="glass-panel invoice-sheet subscription-invoice-sheet" data-aos="fade-up">
    <div class="row g-4">
        <div class="col-md-6">
            <div class="brand-link mb-3">
                <span class="logo-badge"><i class="fa-solid fa-heart-pulse"></i></span>
                <span>MediSphere Subscription Billing</span>
            </div>
            <div class="small text-muted">Detailed patient subscription invoice with purchase and expiry timestamps.</div>
        </div>
        <div class="col-md-6 text-md-end">
            <div><strong>Status:</strong> <span class="badge text-bg-light text-capitalize"><?= e($subscription['status'] ?? 'pending') ?></span></div>
            <div><strong>Purchased:</strong> <?= e($purchasedAt ?? '') ?></div>
            <div><strong>Expires:</strong> <?= e($expiresAt ?? 'No expiry') ?></div>
        </div>
    </div>

    <hr>

    <div class="subscription-confirmation-receipt">
        <div><span>Patient user ID</span><strong>#<?= (int) ($subscription['user_id'] ?? 0) ?></strong></div>
        <div><span>Plan</span><strong><?= e($planName) ?></strong></div>
        <div><span>Access level</span><strong><?= e(ucfirst((string) ($subscription['access_level'] ?? 'free'))) ?></strong></div>
        <div><span>Provider</span><strong><?= e($subscription['provider'] ?? '') ?></strong></div>
        <div><span>Provider payment ID</span><strong><?= e($payment['provider_payment_id'] ?? 'Pending') ?></strong></div>
        <div><span>Reference</span><strong><?= e($subscription['transaction_reference'] ?? '') ?></strong></div>
    </div>

    <div class="table-responsive mt-4">
        <table class="table align-middle">
            <thead><tr><th>Description</th><th>Purchased At</th><th>Expires At</th><th>Total</th></tr></thead>
            <tbody>
                <tr>
                    <td>
                        <?= e($planName) ?> subscription access
                        <div class="small text-muted"><?= e(ucfirst((string) ($subscription['access_level'] ?? 'free'))) ?> access tier</div>
                    </td>
                    <td><?= e($purchasedAt ?? '') ?></td>
                    <td><?= e($expiresAt ?? 'No expiry') ?></td>
                    <td><?= e($amountPkrText) ?> PKR</td>
                </tr>
            </tbody>
            <tfoot>
                <tr><th colspan="3" class="text-end">Grand Total</th><th><?= e($amountPkrText) ?> PKR</th></tr>
            </tfoot>
        </table>
    </div>
</div>
