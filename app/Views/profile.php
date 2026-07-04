<section class="profile-page-hero professional-profile-hero glass-panel mb-4">
    <div class="profile-hero-copy">
        <span class="eyebrow">Profile center</span>
        <h1>Professional Profile</h1>
        <p>Review identity, credentials, and verification details that shape how this account appears across MediSphere.</p>
    </div>
    <div class="profile-hero-panel" aria-label="Professional profile status">
        <span class="profile-hero-pill"><i class="fa-solid fa-id-badge"></i> <?= e(ucfirst((string) ($role ?? 'User'))) ?> account</span>
        <div class="profile-hero-highlights">
            <span><i class="fa-solid fa-circle-check"></i> <?= e(ucfirst((string) ($profile['verified_status'] ?? 'Profile ready'))) ?></span>
            <?php if (!empty($profile['specialization'])): ?><span><i class="fa-solid fa-stethoscope"></i> <?= e($profile['specialization']) ?></span><?php endif; ?>
            <span><i class="fa-solid fa-lock"></i> Verified account details</span>
        </div>
    </div>
</section>

<div class="row g-4">
    <div class="col-lg-4" data-aos="fade-up">
        <div class="glass-panel h-100 text-center">
            <div class="avatar-2xl mx-auto mb-3"><?= strtoupper(substr(($profile['name'] ?? 'U'), 0, 1)) ?></div>
            <h3><?= e($profile['name'] ?? $profile['email'] ?? 'Profile') ?></h3>
            <p class="text-muted text-capitalize mb-1"><?= e($role) ?></p>
            <?php if (!empty($profile['specialization'])): ?><div class="badge text-bg-light"><?= e($profile['specialization']) ?></div><?php endif; ?>
            <?php if (!empty($profile['verified_status'])): ?><div class="small mt-2 text-capitalize">Verification: <?= e($profile['verified_status']) ?></div><?php endif; ?>
        </div>
    </div>
    <div class="col-lg-8" data-aos="fade-up">
        <div class="glass-panel h-100">
            <div class="row g-3">
                <?php if ($profile): foreach ($profile as $key => $value): if (in_array($key, ['profile_image'])) continue; ?>
                    <div class="col-md-6">
                        <div class="profile-field">
                            <span><?= e(ucwords(str_replace('_', ' ', $key))) ?></span>
                            <strong><?= e((string) $value) ?></strong>
                        </div>
                    </div>
                <?php endforeach; endif; ?>
            </div>
            <hr>
            <div class="small text-muted">This profile reflects the verified account details stored for this professional record.</div>
        </div>
    </div>
</div>
