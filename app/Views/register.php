<?php
$hospitals = $hospitals ?? (new App\Models\Hospital())->all();
$activeRole = $activeRole ?? 'patient';
$oldData = $_SESSION['_old'] ?? [];
$oldFacilities = isset($oldData['facilities']) ? (array) $oldData['facilities'] : [];
$returnTo = $returnTo ?? route_url('dashboard');
$isActive = static fn(string $role): string => $activeRole === $role ? 'active' : '';
$isShown = static fn(string $role): string => $activeRole === $role ? 'show active' : '';
$selected = static fn(string $key, string $value): string => (($oldData[$key] ?? '') === $value) ? 'selected' : '';
$checked = static fn(string $value): string => in_array($value, $oldFacilities, true) ? 'checked' : '';
$specializations = [
    'Cardiology',
    'Dermatology',
    'Emergency Medicine',
    'Family Medicine',
    'General Surgery',
    'Internal Medicine',
    'Neurology',
    'Obstetrics & Gynecology',
    'Oncology',
    'Ophthalmology',
    'Orthopedics',
    'Pediatrics',
    'Psychiatry',
    'Radiology',
];
$facilityOptions = [
    ['label' => 'ICU', 'icon' => 'fa-bed-pulse'],
    ['label' => 'Emergency', 'icon' => 'fa-truck-medical'],
    ['label' => 'OT', 'icon' => 'fa-user-nurse'],
    ['label' => 'Radiology', 'icon' => 'fa-x-ray'],
    ['label' => 'Lab', 'icon' => 'fa-flask-vial'],
    ['label' => 'Pharmacy', 'icon' => 'fa-prescription-bottle-medical'],
    ['label' => 'Dialysis', 'icon' => 'fa-droplet'],
    ['label' => 'Maternity', 'icon' => 'fa-baby'],
    ['label' => 'Ambulance', 'icon' => 'fa-truck-medical'],
];
$socialReturnTo = ['return_to' => $returnTo];
?>
<section class="auth-premium-shell auth-register-shell">
    <aside class="auth-visual-panel" data-aos="fade-right">
        <img class="auth-visual-image" src="<?= e(provider_photo_url('hospital', 'medisphere-registration-clinical-network')) ?>" alt="Modern hospital reception and clinical network" loading="eager">
        <div class="auth-visual-shade"></div>
        <div class="auth-brand-mark">
            <span><i class="fa-solid fa-heart-pulse"></i></span>
            <strong>MediSphere</strong>
        </div>
        <div class="auth-visual-content">
            <span class="auth-kicker">Create secure access</span>
            <h1>Join as a patient, doctor, or hospital.</h1>
            <p>Choose the right profile. Patient accounts activate instantly; professional accounts go through admin verification.</p>
            <div class="auth-verification-map">
                <div><i class="fa-solid fa-user-check"></i><span>Patients</span><strong>Instant access</strong></div>
                <div><i class="fa-solid fa-user-doctor"></i><span>Doctors</span><strong>Admin approval</strong></div>
                <div><i class="fa-solid fa-hospital-user"></i><span>Hospitals</span><strong>Admin approval</strong></div>
            </div>
        </div>
    </aside>

    <section class="auth-form-panel" data-aos="fade-left">
        <div class="auth-form-card auth-register-card">
            <div class="auth-form-heading">
                <span class="eyebrow">Registration</span>
                <h2>Create your account</h2>
                <p>Select a role and complete the required profile details.</p>
            </div>

            <div class="auth-social-grid" aria-label="Social registration options">
                <a href="<?= route_url('auth/google', $socialReturnTo) ?>" class="auth-social-button google">
                    <i class="fa-brands fa-google"></i>
                    <span>Google</span>
                </a>
                <a href="<?= route_url('auth/facebook', $socialReturnTo) ?>" class="auth-social-button facebook">
                    <i class="fa-brands fa-facebook-f"></i>
                    <span>Facebook</span>
                </a>
                <a href="<?= route_url('auth/x', $socialReturnTo) ?>" class="auth-social-button x">
                    <i class="fa-brands fa-x-twitter"></i>
                    <span>X</span>
                </a>
            </div>
            <p class="auth-social-note">Social sign-in creates patient access. Doctors and hospitals must use the verified forms below.</p>

            <ul class="nav nav-pills auth-tabs auth-role-tabs" id="registerTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?= $isActive('patient') ?>" data-bs-toggle="pill" data-bs-target="#patientPane" type="button" role="tab">
                        <i class="fa-solid fa-user"></i>
                        <span>Patient</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?= $isActive('doctor') ?>" data-bs-toggle="pill" data-bs-target="#doctorPane" type="button" role="tab">
                        <i class="fa-solid fa-user-doctor"></i>
                        <span>Doctor</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?= $isActive('hospital') ?>" data-bs-toggle="pill" data-bs-target="#hospitalPane" type="button" role="tab">
                        <i class="fa-solid fa-hospital"></i>
                        <span>Hospital</span>
                    </button>
                </li>
            </ul>

            <div class="tab-content auth-register-scroll">
                <div class="tab-pane fade <?= $isShown('patient') ?>" id="patientPane" role="tabpanel">
                    <form method="POST" action="<?= route_url('register') ?>" class="auth-premium-form auth-grid-form">
                        <?= csrf_field() ?>
                        <input type="hidden" name="user_type" value="patient">
                        <input type="hidden" name="return_to" value="<?= e($returnTo) ?>">
                        <div class="auth-section-note instant">
                            <i class="fa-solid fa-bolt"></i>
                            <span>Patient access opens immediately. Medical details can be completed later.</span>
                        </div>
                        <div class="auth-field"><label>Name</label><input type="text" name="name" class="form-control" value="<?= old('name') ?>" autocomplete="name" required></div>
                        <div class="auth-field"><label>Email</label><input type="email" name="email" class="form-control" value="<?= old('email') ?>" autocomplete="email" required></div>
                        <div class="auth-field"><label>Password</label><input type="password" name="password" class="form-control patient-password" autocomplete="new-password" required></div>
                        <div class="auth-field"><label>Confirm Password</label><input type="password" name="confirm_password" class="form-control" autocomplete="new-password" required></div>
                        <div class="auth-field"><label>Age</label><input type="number" name="age" class="form-control" min="1" max="120" value="<?= old('age') ?>" required></div>
                        <div class="auth-field">
                            <label>Gender</label>
                            <select name="gender" class="form-select" required>
                                <option value="">Select</option>
                                <option value="Male" <?= $selected('gender', 'Male') ?>>Male</option>
                                <option value="Female" <?= $selected('gender', 'Female') ?>>Female</option>
                                <option value="Other" <?= $selected('gender', 'Other') ?>>Other</option>
                            </select>
                        </div>
                        <button class="btn btn-primary auth-submit-button auth-wide"><i class="fa-solid fa-user-plus"></i> Create Patient Account</button>
                    </form>
                </div>

                <div class="tab-pane fade <?= $isShown('doctor') ?>" id="doctorPane" role="tabpanel">
                    <form method="POST" action="<?= route_url('register') ?>" class="auth-premium-form auth-grid-form">
                        <?= csrf_field() ?>
                        <input type="hidden" name="user_type" value="doctor">
                        <input type="hidden" name="return_to" value="<?= e($returnTo) ?>">
                        <div class="auth-section-note approval">
                            <i class="fa-solid fa-shield-halved"></i>
                            <span>Doctor accounts are reviewed by admin before access opens.</span>
                        </div>
                        <div class="auth-field"><label>Full Name</label><input type="text" name="name" class="form-control" value="<?= old('name') ?>" autocomplete="name" required></div>
                        <div class="auth-field"><label>Email</label><input type="email" name="email" class="form-control" value="<?= old('email') ?>" autocomplete="email" required></div>
                        <div class="auth-field"><label>Password</label><input type="password" name="password" class="form-control" autocomplete="new-password" required></div>
                        <div class="auth-field"><label>Confirm Password</label><input type="password" name="confirm_password" class="form-control" autocomplete="new-password" required></div>
                        <div class="auth-field"><label>License Number</label><input type="text" name="license_number" class="form-control" value="<?= old('license_number') ?>" required></div>
                        <div class="auth-field">
                            <label>Specialization</label>
                            <select name="specialization" class="form-select" required>
                                <option value="">Select specialization</option>
                                <?php foreach ($specializations as $specialization): ?>
                                    <option value="<?= e($specialization) ?>" <?= $selected('specialization', $specialization) ?>><?= e($specialization) ?></option>
                                <?php endforeach; ?>
                                <?php if (!empty($oldData['specialization']) && !in_array($oldData['specialization'], $specializations, true)): ?>
                                    <option value="<?= e($oldData['specialization']) ?>" selected><?= e($oldData['specialization']) ?></option>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="auth-field"><label>Qualification</label><input type="text" name="qualification_details" class="form-control" value="<?= old('qualification_details') ?>" required></div>
                        <div class="auth-field"><label>Experience</label><input type="number" name="experience" class="form-control" min="0" value="<?= old('experience') ?>" required></div>
                        <div class="auth-field"><label>Consultation Fee</label><input type="number" name="consultation_fee" class="form-control" min="0" value="<?= old('consultation_fee') ?>"></div>
                        <div class="auth-field">
                            <label>Hospital</label>
                            <select name="hospital_id" class="form-select">
                                <option value="">Independent</option>
                                <?php foreach ($hospitals as $hospital): ?>
                                    <option value="<?= (int) $hospital['id'] ?>" <?= $selected('hospital_id', (string) $hospital['id']) ?>><?= e($hospital['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="auth-field auth-wide"><label>Languages</label><input type="text" name="languages_spoken" class="form-control" value="<?= old('languages_spoken') ?>" placeholder="English, Urdu, Arabic"></div>
                        <div class="auth-field auth-wide"><label>Bio / Summary</label><textarea name="bio" class="form-control" rows="3"><?= old('bio') ?></textarea></div>
                        <button class="btn btn-primary auth-submit-button auth-wide"><i class="fa-solid fa-user-doctor"></i> Submit Doctor Registration</button>
                    </form>
                </div>

                <div class="tab-pane fade <?= $isShown('hospital') ?>" id="hospitalPane" role="tabpanel">
                    <form method="POST" action="<?= route_url('register') ?>" class="auth-premium-form auth-grid-form">
                        <?= csrf_field() ?>
                        <input type="hidden" name="user_type" value="hospital">
                        <input type="hidden" name="return_to" value="<?= e($returnTo) ?>">
                        <div class="auth-section-note approval">
                            <i class="fa-solid fa-building-shield"></i>
                            <span>Hospital accounts stay pending until admin reviews facility details.</span>
                        </div>
                        <div class="auth-field"><label>Hospital Name</label><input type="text" name="hospital_name" class="form-control" value="<?= old('hospital_name') ?>" required></div>
                        <div class="auth-field"><label>Official Email</label><input type="email" name="email" class="form-control" value="<?= old('email') ?>" autocomplete="email" required></div>
                        <div class="auth-field"><label>Password</label><input type="password" name="password" class="form-control" autocomplete="new-password" required></div>
                        <div class="auth-field"><label>Confirm Password</label><input type="password" name="confirm_password" class="form-control" autocomplete="new-password" required></div>
                        <div class="auth-field">
                            <label>Type</label>
                            <select name="hospital_type" class="form-select">
                                <option value="private" <?= $selected('hospital_type', 'private') ?>>Private</option>
                                <option value="government" <?= $selected('hospital_type', 'government') ?>>Government</option>
                            </select>
                        </div>
                        <div class="auth-field"><label>Phone</label><input type="text" name="phone" class="form-control" value="<?= old('phone') ?>" autocomplete="tel"></div>
                        <div class="auth-field"><label>Registration Number</label><input type="text" name="registration_number" class="form-control" value="<?= old('registration_number') ?>" required></div>
                        <div class="auth-field"><label>GPS Coordinates</label><input type="text" name="coordinates" class="form-control" value="<?= old('coordinates') ?>" placeholder="31.5204,74.3587"></div>
                        <div class="auth-field auth-wide"><label>Address</label><input type="text" name="address" class="form-control" value="<?= old('address') ?>" autocomplete="street-address"></div>
                        <div class="auth-field"><label>City</label><input type="text" name="city" class="form-control" value="<?= old('city') ?>"></div>
                        <div class="auth-field"><label>Country</label><input type="text" name="country" class="form-control" value="<?= old('country') ?>"></div>
                        <div class="auth-field auth-wide">
                            <label>Facilities</label>
                            <div class="facility-checks auth-chip-checks">
                                <?php foreach ($facilityOptions as $facility): ?>
                                    <label class="chip-check">
                                        <input type="checkbox" name="facilities[]" value="<?= e($facility['label']) ?>" <?= $checked($facility['label']) ?>>
                                        <span><i class="fa-solid <?= e($facility['icon']) ?>"></i><?= e($facility['label']) ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="auth-field auth-wide"><label>Departments</label><textarea name="departments" class="form-control" rows="3" placeholder="Cardiology, Surgery, Pediatrics"><?= old('departments') ?></textarea></div>
                        <button class="btn btn-primary auth-submit-button auth-wide"><i class="fa-solid fa-hospital"></i> Submit Hospital Registration</button>
                    </form>
                </div>
            </div>

            <div class="auth-switch-panel auth-premium-switch">
                <div>
                    <strong>Already registered?</strong>
                    <p>Approved accounts can return directly to the login screen.</p>
                </div>
                <a href="<?= route_url('login', ['return_to' => $returnTo]) ?>" class="btn btn-outline-primary btn-sm">Login</a>
            </div>
        </div>
    </section>
</section>
