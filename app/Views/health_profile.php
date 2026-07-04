<?php
$profile = is_array($profile ?? null) ? $profile : [];
$authUser = is_array($authUser ?? null) ? $authUser : [];
$metricChartData = array_reverse(array_map(static function (array $metric) {
    return [
        'label' => $metric['recorded_at'] . ' · ' . strtoupper($metric['metric_type']),
        'value' => (float) $metric['value_primary'],
    ];
}, array_slice($healthMetrics ?? [], 0, 12)));
$shareUrl = !empty($profileShare['access_token']) ? route_url('profile/public', ['token' => $profileShare['access_token']]) : '';
$qrUrl = $shareUrl ? 'https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=' . urlencode($shareUrl) : '';
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
$profileName = $profile['name'] ?? 'Patient';
$profileInitial = strtoupper(substr($profileName ?: 'P', 0, 1));
$profileEmail = (string) ($profile['email'] ?? $authUser['email'] ?? '');
$profileBloodGroup = (string) ($profile['blood_group'] ?? '');
$profileAge = $profile['age'] ?? null;
$profileGender = (string) ($profile['gender'] ?? '');
$profilePhone = (string) ($profile['phone'] ?? '');
$profileCity = (string) ($profile['city'] ?? '');
$profileCountry = (string) ($profile['country'] ?? '');
$genderOptions = ['', 'Female', 'Male', 'Non-binary', 'Prefer not to say', 'Other'];
$bloodGroupOptions = ['', 'A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
?>
<section class="profile-page-hero health-profile-hero glass-panel mb-4">
    <div class="profile-hero-copy">
        <span class="eyebrow">Patient health profile</span>
        <h1>Health Profile & Metrics</h1>
        <p>Track longitudinal health, allergies, medications, contacts, insurance, family history, and quick-access sharing.</p>
    </div>
    <div class="profile-hero-panel" aria-label="Profile status">
        <span class="profile-hero-pill"><i class="fa-solid fa-shield-heart"></i> Secure patient record</span>
        <div class="profile-hero-highlights">
            <span><i class="fa-solid fa-qrcode"></i> QR share <?= !empty($shareUrl) ? 'enabled' : 'unavailable' ?></span>
            <span><i class="fa-solid fa-notes-medical"></i> <?= count($historyEvents ?? []) ?> history events</span>
            <span><i class="fa-solid fa-pills"></i> <?= count($medications ?? []) ?> medications</span>
        </div>
    </div>
</section>

<div class="row g-4 mb-4">
    <div class="col-md-6 col-xl-3"><div class="stat-card gradient-card"><span>History Events</span><h2><?= count($historyEvents ?? []) ?></h2></div></div>
    <div class="col-md-6 col-xl-3"><div class="stat-card gradient-card"><span>Allergies</span><h2><?= count($allergies ?? []) ?></h2></div></div>
    <div class="col-md-6 col-xl-3"><div class="stat-card gradient-card"><span>Medications</span><h2><?= count($medications ?? []) ?></h2></div></div>
    <div class="col-md-6 col-xl-3"><div class="stat-card gradient-card"><span>Health Metrics</span><h2><?= count($healthMetrics ?? []) ?></h2><div class="small mt-2">QR share <?= !empty($shareUrl) ? 'Enabled' : 'Unavailable' ?></div></div></div>
</div>

<div class="ux-stepper" data-stepper>
    <div class="ux-stepper-nav" role="tablist" aria-label="Health profile sections">
        <button class="ux-stepper-tab is-active" data-stepper-tab data-stepper-target="health-step-overview">
            <span class="ux-stepper-number">1</span>
            <span class="ux-stepper-label"><strong>Overview</strong><span>Profile and timeline</span></span>
        </button>
        <button class="ux-stepper-tab" data-stepper-tab data-stepper-target="health-step-care">
            <span class="ux-stepper-number">2</span>
            <span class="ux-stepper-label"><strong>Care Details</strong><span>Allergies and medication</span></span>
        </button>
        <button class="ux-stepper-tab" data-stepper-tab data-stepper-target="health-step-admin">
            <span class="ux-stepper-number">3</span>
            <span class="ux-stepper-label"><strong>Access</strong><span>Contact and insurance</span></span>
        </button>
        <button class="ux-stepper-tab" data-stepper-tab data-stepper-target="health-step-metrics">
            <span class="ux-stepper-number">4</span>
            <span class="ux-stepper-label"><strong>Trends</strong><span>Family history and metrics</span></span>
        </button>
    </div>
    <div class="ux-stepper-panels">
        <section class="ux-stepper-panel is-active" id="health-step-overview" data-stepper-panel>
<div class="row g-4">
    <div class="col-xl-4" data-aos="fade-up">
        <div class="glass-panel h-100 text-center patient-profile-summary">
            <div class="patient-profile-avatar mx-auto mb-3">
                <img id="patientProfilePreview" src="<?= e($profileImageSrc ?: 'data:image/gif;base64,R0lGODlhAQABAAAAACw=') ?>" alt="<?= e($profileName) ?> profile picture" class="patient-profile-avatar-img <?= $profileImageSrc ? '' : 'd-none' ?>" data-profile-image-preview>
                <div id="patientProfilePlaceholder" class="patient-profile-avatar-placeholder <?= $profileImageSrc ? 'd-none' : '' ?>" data-profile-image-placeholder><?= e($profileInitial) ?></div>
            </div>
            <div class="avatar-upload-helper mb-3">
                <label class="avatar-file-trigger" for="profile_image">
                    <i class="fa-solid fa-camera"></i>
                    <span>Choose Photo</span>
                </label>
                <input type="file" id="profile_image" name="profile_image" class="visually-hidden" accept="image/jpeg,image/png,image/webp" form="patientProfileForm" data-profile-image-input data-preview-target="#patientProfilePreview" data-placeholder-target="#patientProfilePlaceholder" data-remove-target="#remove_profile_image">
                <div class="avatar-upload-note">Use a clear JPG, PNG, or WebP image up to 3 MB. It saves when you click Save Profile.</div>
                <?php if ($profileImageSrc): ?>
                    <label class="avatar-remove-toggle" for="remove_profile_image">
                        <input type="checkbox" name="remove_profile_image" value="1" id="remove_profile_image" form="patientProfileForm">
                        <span>Remove current photo</span>
                    </label>
                <?php endif; ?>
                <button type="submit" form="patientProfileForm" class="btn btn-primary btn-lg patient-profile-save-side">
                    <i class="fa-solid fa-floppy-disk me-2"></i> Save Profile
                </button>
            </div>
            <h3><?= e($profileName) ?></h3>
            <div class="small text-muted mb-2"><?= e($profileEmail) ?></div>
            <div class="badge text-bg-light mb-3"><?= e($profileBloodGroup ?: 'Blood group not set') ?></div>
            <div class="metric-list mt-3">
                <div><span>Age</span><strong><?= e((string) ($profileAge ?? 'Not set')) ?></strong></div>
                <div><span>Gender</span><strong><?= e($profileGender ?: 'Not set') ?></strong></div>
                <div><span>Phone</span><strong><?= e($profilePhone ?: 'Not set') ?></strong></div>
                <div><span>City</span><strong><?= e($profileCity ?: 'Not set') ?></strong></div>
                <div><span>Country</span><strong><?= e($profileCountry ?: 'Not set') ?></strong></div>
            </div>
            <hr>
            <div class="profile-qr-heading">
                <span>Quick Access QR</span>
                <strong>Auto-generated</strong>
            </div>
            <?php if ($shareUrl): ?>
                <a href="<?= e($shareUrl) ?>" class="profile-qr-frame" target="_blank" rel="noopener" aria-label="Open shared patient profile">
                    <img src="<?= e($qrUrl) ?>" alt="Profile QR" class="profile-qr-image">
                </a>
                <div class="profile-qr-help">
                    <i class="fa-solid fa-qrcode"></i>
                    <span>Scan this QR to open your shared patient summary. Regenerate it when you want a fresh access code.</span>
                </div>
                <form method="POST" action="<?= route_url('profile/share/regenerate') ?>" class="mt-3">
                    <?= csrf_field() ?>
                    <button class="btn btn-outline-primary btn-sm"><i class="fa-solid fa-rotate me-1"></i> Regenerate QR Code</button>
                </form>
            <?php else: ?>
                <div class="text-muted">Share token unavailable.</div>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-xl-8" data-aos="fade-up">
        <div class="glass-panel h-100 patient-profile-editor">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
                <div>
                    <h4 class="mb-1">Profile Settings</h4>
                    <p class="text-muted mb-0">Keep your identity and contact details ready for appointments, reports, and secure care sharing.</p>
                </div>
                <span class="patient-profile-secure-badge"><i class="fa-solid fa-shield-halved"></i> Protected</span>
            </div>

            <form method="POST" action="<?= route_url('profile/update') ?>" enctype="multipart/form-data" id="patientProfileForm" class="row g-3 patient-profile-form">
                <?= csrf_field() ?>
                <div class="col-md-6">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="name" class="form-control" value="<?= e($profile['name'] ?? '') ?>" maxlength="120" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" class="form-control" value="<?= e($profileEmail) ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Age</label>
                    <input type="number" name="age" class="form-control" value="<?= e((string) ($profileAge ?? '')) ?>" min="0" max="120">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Gender</label>
                    <select name="gender" class="form-select">
                        <?php foreach ($genderOptions as $option): ?>
                            <option value="<?= e($option) ?>" <?= ($profileGender === $option) ? 'selected' : '' ?>><?= e($option ?: 'Not set') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Blood Group</label>
                    <select name="blood_group" class="form-select">
                        <?php foreach ($bloodGroupOptions as $option): ?>
                            <option value="<?= e($option) ?>" <?= ($profileBloodGroup === $option) ? 'selected' : '' ?>><?= e($option ?: 'Not set') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Phone</label>
                    <input type="text" name="phone" class="form-control" value="<?= e($profilePhone) ?>" maxlength="30">
                </div>
                <div class="col-md-4">
                    <label class="form-label">City</label>
                    <input type="text" name="city" class="form-control" value="<?= e($profileCity) ?>" maxlength="100">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Country</label>
                    <input type="text" name="country" class="form-control" value="<?= e($profileCountry) ?>" maxlength="100">
                </div>
                <div class="col-12">
                    <label class="form-label">Address</label>
                    <textarea name="address" class="form-control" rows="3" maxlength="255"><?= e($profile['address'] ?? '') ?></textarea>
                </div>
                <div class="col-12">
                    <div class="profile-form-note">
                        <i class="fa-solid fa-circle-info"></i>
                        <span>Your profile photo and all details save together from the left profile card.</span>
                    </div>
                </div>
            </form>

            <div class="danger-zone-card mt-4">
                <div class="danger-zone-copy">
                    <span class="danger-zone-icon"><i class="fa-solid fa-triangle-exclamation"></i></span>
                    <div>
                        <span class="danger-zone-kicker">Danger Zone</span>
                        <h5>Delete Account Permanently</h5>
                        <p>This removes your patient profile, QR access, and connected portal records. This action cannot be undone.</p>
                    </div>
                </div>
                <div class="danger-zone-warning">
                    <i class="fa-solid fa-lock"></i>
                    <span>For protection, confirm your password and type DELETE before this account can be removed.</span>
                </div>
                <form method="POST" action="<?= route_url('profile/delete-account') ?>" class="row g-3 align-items-end" onsubmit="return confirm('This will permanently delete your patient account and profile data. Continue?');">
                    <?= csrf_field() ?>
                    <div class="col-md-5">
                        <label class="form-label">Current Password</label>
                        <input type="password" name="current_password" class="form-control" autocomplete="current-password" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Type DELETE</label>
                        <input type="text" name="confirm_delete" class="form-control" autocomplete="off" required>
                    </div>
                    <div class="col-md-3 d-grid">
                        <button class="btn btn-danger"><i class="fa-solid fa-user-slash me-2"></i> Delete Account</button>
                    </div>
                </form>
            </div>
            <div class="profile-account-safety">
                <div class="profile-account-safety-head">
                    <span><i class="fa-solid fa-shield-heart"></i></span>
                    <div>
                        <strong>Profile Safety Summary</strong>
                        <small>Everything on this page is saved with your secure patient record.</small>
                    </div>
                </div>
                <div class="profile-account-safety-grid">
                    <div><i class="fa-solid fa-floppy-disk"></i><span>Profile changes update your portal record instantly.</span></div>
                    <div><i class="fa-solid fa-qrcode"></i><span>QR access stays active and can be regenerated anytime.</span></div>
                    <div><i class="fa-solid fa-key"></i><span>Account deletion requires password and DELETE confirmation.</span></div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12" data-aos="fade-up">
        <div class="glass-panel h-100">
            <h4 class="mb-3">Medical History Timeline</h4>
            <form method="POST" action="<?= route_url('profile/history/save') ?>" class="row g-3 mb-4 profile-add-card">
                <?= csrf_field() ?>
                <div class="col-md-4"><label class="form-label">Date</label><input type="date" name="event_date" class="form-control" value="<?= date('Y-m-d') ?>" required></div>
                <div class="col-md-4"><label class="form-label">Category</label><select name="category" class="form-select"><option value="diagnosis">Diagnosis</option><option value="surgery">Surgery</option><option value="lab">Lab Test</option><option value="medication">Medication</option><option value="general">General</option></select></div>
                <div class="col-md-4"><label class="form-label">Title</label><input type="text" name="title" class="form-control" placeholder="Appendectomy" required></div>
                <div class="col-12"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="3" placeholder="Short summary of the medical event"></textarea></div>
                <div class="col-12 d-grid"><button class="btn btn-primary">Add History Event</button></div>
            </form>
            <div class="timeline mt-2">
                <?php foreach (($historyEvents ?? []) as $event): ?>
                    <div class="timeline-item history-item-card">
                        <div class="d-flex justify-content-between gap-3 flex-wrap">
                            <div>
                                <strong><?= e($event['title']) ?></strong>
                                <div class="small text-muted text-capitalize"><?= e($event['category']) ?> · <?= e($event['event_date']) ?></div>
                                <div class="small mt-2"><?= e($event['description'] ?: 'No description') ?></div>
                            </div>
                            <form method="POST" action="<?= route_url('profile/history/delete') ?>">
                                <?= csrf_field() ?>
                                <input type="hidden" name="history_id" value="<?= (int) $event['id'] ?>">
                                <button class="btn btn-outline-danger btn-sm" aria-label="Delete history event"><i class="fa-solid fa-trash me-1"></i>Delete</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($historyEvents)): ?><div class="text-muted">No medical history events added yet. Add diagnoses, surgeries, lab events, or important care milestones to build a clear timeline.</div><?php endif; ?>
            </div>
        </div>
    </div>
</div>

        </section>
        <section class="ux-stepper-panel" id="health-step-care" data-stepper-panel>
<div class="row g-4 mt-1">
    <div class="col-xl-6" data-aos="fade-up">
        <div class="glass-panel h-100">
            <h4 class="mb-3">Allergies</h4>
            <form method="POST" action="<?= route_url('profile/allergy/add') ?>" class="row g-3 mb-4 profile-add-card">
                <?= csrf_field() ?>
                <div class="col-md-5"><label class="form-label">Allergen</label><input type="text" name="allergen" class="form-control" placeholder="Penicillin" maxlength="160" required></div>
                <div class="col-md-3"><label class="form-label">Severity</label><select name="severity" class="form-select"><option value="mild">Mild</option><option value="moderate">Moderate</option><option value="severe">Severe</option></select></div>
                <div class="col-md-4"><label class="form-label">Reaction Notes</label><input type="text" name="notes" class="form-control" placeholder="Rash, swelling, nausea" maxlength="255"></div>
                <div class="col-12 d-grid"><button class="btn btn-primary"><i class="fa-solid fa-plus me-2"></i>Add Allergy</button></div>
            </form>
            <div class="chip-list-grid">
                <?php foreach (($allergies ?? []) as $allergy): ?>
                    <div class="chip-card">
                        <div>
                            <strong><?= e($allergy['allergen']) ?></strong>
                            <div class="profile-record-meta">
                                <span class="text-capitalize"><?= e($allergy['severity']) ?></span>
                                <?php if (!empty($allergy['notes'])): ?><span><?= e($allergy['notes']) ?></span><?php endif; ?>
                            </div>
                        </div>
                        <form method="POST" action="<?= route_url('profile/allergy/delete') ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="allergy_id" value="<?= (int) $allergy['id'] ?>">
                            <button class="btn btn-outline-danger btn-sm" aria-label="Delete allergy"><i class="fa-solid fa-trash"></i></button>
                        </form>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($allergies)): ?><div class="text-muted">No allergies recorded. Add known allergens and reaction severity so clinicians can plan safer treatment.</div><?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-xl-6" data-aos="fade-up">
        <div class="glass-panel h-100">
            <h4 class="mb-3">Current Medications</h4>
            <form method="POST" action="<?= route_url('profile/medication/add') ?>" class="row g-3 mb-4 profile-add-card">
                <?= csrf_field() ?>
                <div class="col-md-4"><label class="form-label">Medicine</label><input type="text" name="medicine_name" class="form-control" placeholder="Metformin" maxlength="160" required></div>
                <div class="col-md-3"><label class="form-label">Dosage</label><input type="text" name="dosage" class="form-control" placeholder="500mg" maxlength="120"></div>
                <div class="col-md-3"><label class="form-label">Frequency</label><input type="text" name="frequency" class="form-control" placeholder="Twice daily" maxlength="120"></div>
                <div class="col-md-2"><label class="form-label">Start Date</label><input type="date" name="start_date" class="form-control"></div>
                <div class="col-12"><label class="form-label">Notes</label><input type="text" name="notes" class="form-control" placeholder="Purpose, timing, or clinician instructions" maxlength="255"></div>
                <div class="col-12 d-grid"><button class="btn btn-primary"><i class="fa-solid fa-plus me-2"></i>Add Medication</button></div>
            </form>
            <div class="chip-list-grid">
                <?php foreach (($medications ?? []) as $medication): ?>
                    <?php $medicationDetail = trim(((string) ($medication['dosage'] ?? '')) . ' ' . ((string) ($medication['frequency'] ?? ''))); ?>
                    <div class="chip-card">
                        <div>
                            <strong><?= e($medication['medicine_name']) ?></strong>
                            <div class="profile-record-meta">
                                <?php if ($medicationDetail !== ''): ?><span><?= e($medicationDetail) ?></span><?php endif; ?>
                                <?php if (!empty($medication['start_date'])): ?><span>Started <?= e($medication['start_date']) ?></span><?php endif; ?>
                                <?php if (!empty($medication['notes'])): ?><span><?= e($medication['notes']) ?></span><?php endif; ?>
                            </div>
                        </div>
                        <form method="POST" action="<?= route_url('profile/medication/delete') ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="medication_id" value="<?= (int) $medication['id'] ?>">
                            <button class="btn btn-outline-danger btn-sm" aria-label="Delete medication"><i class="fa-solid fa-trash"></i></button>
                        </form>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($medications)): ?><div class="text-muted">No medications recorded. Add current prescriptions, dosage, and frequency to keep visits and refills aligned.</div><?php endif; ?>
            </div>
        </div>
    </div>
</div>

        </section>
        <section class="ux-stepper-panel" id="health-step-admin" data-stepper-panel>
<div class="row g-4 mt-1">
    <div class="col-xl-6" data-aos="fade-up">
        <div class="glass-panel h-100">
            <h4 class="mb-3">Emergency Contact</h4>
            <form method="POST" action="<?= route_url('profile/emergency-contact/save') ?>" class="row g-3 profile-add-card">
                <?= csrf_field() ?>
                <div class="col-md-6"><label class="form-label">Name</label><input type="text" name="contact_name" class="form-control" value="<?= e($emergencyContact['contact_name'] ?? '') ?>" maxlength="160" required></div>
                <div class="col-md-6"><label class="form-label">Relationship</label><input type="text" name="relationship" class="form-control" value="<?= e($emergencyContact['relationship'] ?? '') ?>" placeholder="Brother / Mother" maxlength="120"></div>
                <div class="col-md-6"><label class="form-label">Phone</label><input type="text" name="phone" class="form-control" value="<?= e($emergencyContact['phone'] ?? '') ?>" maxlength="40"></div>
                <div class="col-md-6"><label class="form-label">Alternate Phone</label><input type="text" name="alternate_phone" class="form-control" value="<?= e($emergencyContact['alternate_phone'] ?? '') ?>" maxlength="40"></div>
                <div class="col-12"><label class="form-label">Address</label><textarea name="address" class="form-control" rows="3" maxlength="255"><?= e($emergencyContact['address'] ?? '') ?></textarea></div>
                <div class="col-12 d-grid"><button class="btn btn-primary"><i class="fa-solid fa-floppy-disk me-2"></i>Save Emergency Contact</button></div>
            </form>
        </div>
    </div>

    <div class="col-xl-6" data-aos="fade-up">
        <div class="glass-panel h-100">
            <h4 class="mb-3">Insurance Profile</h4>
            <form method="POST" action="<?= route_url('profile/insurance/save') ?>" class="row g-3 profile-add-card">
                <?= csrf_field() ?>
                <div class="col-md-6"><label class="form-label">Provider Name</label><input type="text" name="provider_name" class="form-control" value="<?= e($insurance['provider_name'] ?? '') ?>" placeholder="State Life" maxlength="180"></div>
                <div class="col-md-6"><label class="form-label">Policy Number</label><input type="text" name="policy_number" class="form-control" value="<?= e($insurance['policy_number'] ?? '') ?>" maxlength="120"></div>
                <div class="col-md-6"><label class="form-label">Plan Name</label><input type="text" name="plan_name" class="form-control" value="<?= e($insurance['plan_name'] ?? '') ?>" maxlength="160"></div>
                <div class="col-md-6"><label class="form-label">Valid Until</label><input type="date" name="valid_until" class="form-control" value="<?= e($insurance['valid_until'] ?? '') ?>"></div>
                <div class="col-12"><label class="form-label">Coverage Notes</label><textarea name="coverage_notes" class="form-control" rows="3"><?= e($insurance['coverage_notes'] ?? '') ?></textarea></div>
                <div class="col-12 d-grid"><button class="btn btn-primary"><i class="fa-solid fa-floppy-disk me-2"></i>Save Insurance</button></div>
            </form>
        </div>
    </div>
</div>

        </section>
        <section class="ux-stepper-panel" id="health-step-metrics" data-stepper-panel>
<div class="row g-4 mt-1">
    <div class="col-xl-6" data-aos="fade-up">
        <div class="glass-panel h-100">
            <h4 class="mb-3">Family Medical History</h4>
            <form method="POST" action="<?= route_url('profile/family-history/add') ?>" class="row g-3 mb-4 profile-add-card">
                <?= csrf_field() ?>
                <div class="col-md-4"><label class="form-label">Relation</label><input type="text" name="relation_name" class="form-control" placeholder="Father" maxlength="120" required></div>
                <div class="col-md-5"><label class="form-label">Condition</label><input type="text" name="condition_name" class="form-control" placeholder="Diabetes" maxlength="160" required></div>
                <div class="col-md-3"><label class="form-label">Notes</label><input type="text" name="notes" class="form-control" placeholder="Age / details" maxlength="255"></div>
                <div class="col-12 d-grid"><button class="btn btn-primary"><i class="fa-solid fa-plus me-2"></i>Add Family History</button></div>
            </form>
            <div class="chip-list-grid">
                <?php foreach (($familyHistory ?? []) as $item): ?>
                    <div class="chip-card">
                        <div>
                            <strong><?= e($item['relation_name']) ?></strong>
                            <div class="profile-record-meta">
                                <span><?= e($item['condition_name']) ?></span>
                                <?php if (!empty($item['notes'])): ?><span><?= e($item['notes']) ?></span><?php endif; ?>
                            </div>
                        </div>
                        <form method="POST" action="<?= route_url('profile/family-history/delete') ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="family_history_id" value="<?= (int) $item['id'] ?>">
                            <button class="btn btn-outline-danger btn-sm" aria-label="Delete family history"><i class="fa-solid fa-trash"></i></button>
                        </form>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($familyHistory)): ?><div class="text-muted">No family history recorded. Add inherited conditions or recurring family patterns that may influence screening and prevention.</div><?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-xl-6" data-aos="fade-up">
        <div class="glass-panel h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0">Health Metrics Tracker</h4>
                <span class="small text-muted">Weight, blood pressure, sugar, oxygen, and more</span>
            </div>
            <form method="POST" action="<?= route_url('profile/metric/add') ?>" class="row g-3 mb-4 profile-add-card">
                <?= csrf_field() ?>
                <div class="col-md-4"><label class="form-label">Metric Type</label><select name="metric_type" class="form-select"><option value="weight">Weight</option><option value="blood_pressure">Blood Pressure</option><option value="sugar">Blood Sugar</option><option value="oxygen">Oxygen</option><option value="temperature">Temperature</option><option value="heart_rate">Heart Rate</option></select></div>
                <div class="col-md-3"><label class="form-label">Primary Value</label><input type="number" step="0.01" name="value_primary" class="form-control" required></div>
                <div class="col-md-3"><label class="form-label">Secondary Value</label><input type="number" step="0.01" name="value_secondary" class="form-control" placeholder="e.g. diastolic"></div>
                <div class="col-md-2"><label class="form-label">Unit</label><input type="text" name="unit" class="form-control" placeholder="kg" maxlength="40"></div>
                <div class="col-md-6"><label class="form-label">Recorded At</label><input type="date" name="recorded_at" class="form-control" value="<?= date('Y-m-d') ?>"></div>
                <div class="col-md-6"><label class="form-label">Notes</label><input type="text" name="notes" class="form-control" placeholder="Fasting / after meal / morning" maxlength="255"></div>
                <div class="col-12 d-grid"><button class="btn btn-primary"><i class="fa-solid fa-plus me-2"></i>Add Metric</button></div>
            </form>
            <?php if (!empty($metricChartData)): ?>
                <canvas id="healthMetricsChart" height="140" data-points='<?= e(json_encode($metricChartData)) ?>'></canvas>
            <?php else: ?>
                <div class="profile-chart-empty">Add your first health metric to generate a visual trend chart.</div>
            <?php endif; ?>
            <div class="table-responsive mt-3 profile-metrics-table"><table class="table align-middle"><thead><tr><th>Metric</th><th>Value</th><th>Date</th><th>Action</th></tr></thead><tbody><?php foreach (($healthMetrics ?? []) as $metric): ?><tr><td><?= e(str_replace('_', ' ', ucfirst($metric['metric_type']))) ?></td><td><?= e((string) $metric['value_primary']) ?><?= $metric['value_secondary'] !== null ? ' / ' . e((string) $metric['value_secondary']) : '' ?> <?= e($metric['unit'] ?? '') ?></td><td><?= e($metric['recorded_at']) ?></td><td><form method="POST" action="<?= route_url('profile/metric/delete') ?>"><?= csrf_field() ?><input type="hidden" name="metric_id" value="<?= (int) $metric['id'] ?>"><button class="btn btn-outline-danger btn-sm" aria-label="Delete health metric"><i class="fa-solid fa-trash me-1"></i>Delete</button></form></td></tr><?php endforeach; ?><?php if (empty($healthMetrics)): ?><tr><td colspan="4" class="text-center text-muted py-4">No health metrics tracked yet. Add blood pressure, glucose, oxygen, weight, or temperature readings to build trends over time.</td></tr><?php endif; ?></tbody></table></div>
        </div>
    </div>
</div>
        </section>
    </div>
</div>
