<?php
$profile = is_array($profile ?? null) ? $profile : [];
$metricPreview = array_slice($metrics ?? [], 0, 10);
$profileImageData = $profile['profile_image_data'] ?? null;
if (is_resource($profileImageData)) {
    $profileImageData = stream_get_contents($profileImageData);
}
$profileImageSrc = '';
if (is_string($profileImageData) && $profileImageData !== '' && !empty($profile['profile_image_mime'])) {
    $profileImageSrc = 'data:' . (string) $profile['profile_image_mime'] . ';base64,' . base64_encode($profileImageData);
} elseif (!empty($profile['profile_image']) && filter_var((string) $profile['profile_image'], FILTER_VALIDATE_URL)) {
    $profileImageSrc = (string) $profile['profile_image'];
}
$profileName = (string) ($profile['name'] ?? 'Patient');
$profileInitial = strtoupper(substr($profileName ?: 'P', 0, 1));
$profileBloodGroup = (string) ($profile['blood_group'] ?? '');
?>
<section class="mb-4 text-center">
    <span class="eyebrow">Quick patient profile</span>
    <h1><?= e($profile['name'] ?? 'Patient Profile') ?></h1>
    <p class="text-muted">Shared healthcare summary for quick access in emergencies or consultations.</p>
</section>

<div class="row g-4">
    <div class="col-lg-4" data-aos="fade-up">
        <div class="glass-panel h-100 text-center">
            <div class="patient-profile-avatar mx-auto mb-3">
                <?php if ($profileImageSrc): ?>
                    <img src="<?= e($profileImageSrc) ?>" alt="<?= e($profileName) ?> profile picture" class="patient-profile-avatar-img">
                <?php else: ?>
                    <div class="patient-profile-avatar-placeholder"><?= e($profileInitial) ?></div>
                <?php endif; ?>
            </div>
            <h3><?= e($profileName) ?></h3>
            <div class="badge text-bg-light mb-3"><?= e($profileBloodGroup ?: 'Blood group not set') ?></div>
            <div class="metric-list mt-3">
                <div><span>Age</span><strong><?= e((string) ($profile['age'] ?? 'Not set')) ?></strong></div>
                <div><span>Gender</span><strong><?= e(($profile['gender'] ?? '') ?: 'Not set') ?></strong></div>
                <div><span>Phone</span><strong><?= e(($profile['phone'] ?? '') ?: 'Not set') ?></strong></div>
                <div><span>Address</span><strong><?= e(($profile['address'] ?? '') ?: 'Not set') ?></strong></div>
            </div>
        </div>
    </div>
    <div class="col-lg-8" data-aos="fade-up">
        <div class="row g-4">
            <div class="col-12"><div class="glass-panel h-100"><h4 class="mb-3">Emergency Contact</h4><?php if (!empty($emergencyContact)): ?><div class="metric-list"><div><span>Name</span><strong><?= e($emergencyContact['contact_name']) ?></strong></div><div><span>Relationship</span><strong><?= e($emergencyContact['relationship']) ?></strong></div><div><span>Phone</span><strong><?= e($emergencyContact['phone']) ?></strong></div><div><span>Alternate</span><strong><?= e($emergencyContact['alternate_phone'] ?: 'N/A') ?></strong></div></div><?php else: ?><div class="text-muted">No emergency contact is shared on this profile yet.</div><?php endif; ?></div></div>
            <div class="col-md-6"><div class="glass-panel h-100"><h4 class="mb-3">Allergies</h4><div class="chip-list-grid"><?php foreach (($allergies ?? []) as $allergy): ?><div class="chip-card"><div><strong><?= e($allergy['allergen']) ?></strong><div class="small text-muted text-capitalize"><?= e($allergy['severity']) ?></div></div></div><?php endforeach; ?><?php if (empty($allergies)): ?><div class="text-muted">No allergies are shared on this profile yet.</div><?php endif; ?></div></div></div>
            <div class="col-md-6"><div class="glass-panel h-100"><h4 class="mb-3">Current Medications</h4><div class="chip-list-grid"><?php foreach (($medications ?? []) as $med): ?><div class="chip-card"><div><strong><?= e($med['medicine_name']) ?></strong><div class="small text-muted"><?= e(trim(($med['dosage'] ?: '') . ' ' . ($med['frequency'] ?: ''))) ?></div></div></div><?php endforeach; ?><?php if (empty($medications)): ?><div class="text-muted">No medications are shared on this profile yet.</div><?php endif; ?></div></div></div>
            <div class="col-md-6"><div class="glass-panel h-100"><h4 class="mb-3">Family History</h4><div class="chip-list-grid"><?php foreach (($familyHistory ?? []) as $item): ?><div class="chip-card"><div><strong><?= e($item['relation_name']) ?></strong><div class="small text-muted"><?= e($item['condition_name']) ?></div></div></div><?php endforeach; ?><?php if (empty($familyHistory)): ?><div class="text-muted">No family history is shared on this profile yet.</div><?php endif; ?></div></div></div>
            <div class="col-md-6"><div class="glass-panel h-100"><h4 class="mb-3">Insurance</h4><?php if (!empty($insurance)): ?><div class="metric-list"><div><span>Provider</span><strong><?= e($insurance['provider_name'] ?: 'N/A') ?></strong></div><div><span>Policy</span><strong><?= e($insurance['policy_number'] ?: 'N/A') ?></strong></div><div><span>Plan</span><strong><?= e($insurance['plan_name'] ?: 'N/A') ?></strong></div><div><span>Valid Until</span><strong><?= e($insurance['valid_until'] ?: 'N/A') ?></strong></div></div><?php else: ?><div class="text-muted">No insurance information is shared on this profile yet.</div><?php endif; ?></div></div>
            <div class="col-12"><div class="glass-panel h-100"><h4 class="mb-3">Recent Metrics</h4><div class="table-responsive"><table class="table align-middle"><thead><tr><th>Metric</th><th>Value</th><th>Date</th></tr></thead><tbody><?php foreach ($metricPreview as $metric): ?><tr><td><?= e(str_replace('_', ' ', ucfirst($metric['metric_type']))) ?></td><td><?= e((string) $metric['value_primary']) ?><?= $metric['value_secondary'] !== null ? ' / ' . e((string) $metric['value_secondary']) : '' ?> <?= e($metric['unit'] ?? '') ?></td><td><?= e($metric['recorded_at']) ?></td></tr><?php endforeach; ?><?php if (empty($metricPreview)): ?><tr><td colspan="3" class="text-center text-muted py-4">No recent metrics are shared on this profile yet.</td></tr><?php endif; ?></tbody></table></div></div></div>
        </div>
    </div>
</div>
