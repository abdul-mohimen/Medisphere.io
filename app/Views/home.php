<?php
$homeFeatures = trans('home.features');
$homeSteps = trans('home.steps');
$homeBlogs = trans('home.blogs');
$featuredBlog = $blogPosts[0] ?? null;
$featuredBlogUrl = !empty($featuredBlog['slug']) ? route_url('article', ['slug' => $featuredBlog['slug']]) : route_url('blog');
$doctorSpotlightPhotos = [
    'https://images.unsplash.com/photo-1559839734-2b71ea197ec2?auto=format&fit=crop&w=900&q=85',
    'https://images.unsplash.com/photo-1678695972687-033fa0bdbac9?auto=format&fit=crop&w=900&q=85',
    'https://images.unsplash.com/photo-1612349317150-e413f6a5b16d?auto=format&fit=crop&w=900&q=85',
];
$worldHospitals = [
    [
        'rank' => '01',
        'name' => 'Mayo Clinic - Rochester',
        'location' => 'Rochester, Minnesota, USA',
        'note' => 'Global benchmark for complex specialty care, research programs, and coordinated clinical teams.',
        'image' => 'https://images.unsplash.com/photo-1586773860418-d37222d8fce3?auto=format&fit=crop&w=1000&q=85',
    ],
    [
        'rank' => '02',
        'name' => 'Toronto General Hospital',
        'location' => 'Toronto, Canada',
        'note' => 'A leading academic hospital known for transplant, cardiac, and advanced medical innovation.',
        'image' => 'https://images.unsplash.com/photo-1519494026892-80bbd2d6fd0d?auto=format&fit=crop&w=1000&q=85',
    ],
    [
        'rank' => '03',
        'name' => 'Cleveland Clinic',
        'location' => 'Cleveland, Ohio, USA',
        'note' => 'Internationally recognized for cardiac care, specialty programs, and patient-centered outcomes.',
        'image' => 'https://images.unsplash.com/photo-1538108149393-fbbd81895907?auto=format&fit=crop&w=1000&q=85',
    ],
];
$doctorSpotlightDefaults = [
    [
        'name' => 'Dr. Amara Khan',
        'specialization' => 'Cardiology',
        'bio' => 'Heart-care consultant focused on prevention, diagnostics, and treatment planning for long-term cardiovascular health.',
        'hospital_name' => 'Global Heart Institute',
        'qualification_details' => 'MD, FACC',
        'consultation_fee' => 12000,
        'spotlight_location' => 'London, UK',
    ],
    [
        'name' => 'Dr. Sofia Martinez',
        'specialization' => 'Family Medicine',
        'bio' => 'Primary-care specialist helping families coordinate preventive care, chronic-condition follow-ups, and practical wellness plans.',
        'hospital_name' => 'CityCare Medical Center',
        'qualification_details' => 'MD, Board Certified',
        'consultation_fee' => 9000,
        'spotlight_location' => 'Toronto, Canada',
    ],
    [
        'name' => 'Dr. Daniel Chen',
        'specialization' => 'Neurology',
        'bio' => 'Neurology consultant supporting advanced triage, care coordination, and specialist follow-ups for complex conditions.',
        'hospital_name' => 'NeuroLife Clinic',
        'qualification_details' => 'MD, Neurology',
        'consultation_fee' => 15000,
        'spotlight_location' => 'Singapore',
    ],
];
$doctorSpotlight = array_values(array_slice((array) $featuredDoctors, 0, 3));
for ($i = count($doctorSpotlight); $i < 3; $i++) {
    $doctorSpotlight[] = $doctorSpotlightDefaults[$i];
}
foreach ($doctorSpotlight as $i => $doctor) {
    $fallback = $doctorSpotlightDefaults[$i];
    $doctorSpotlight[$i] = array_merge($fallback, (array) $doctor);
    $doctorSpotlight[$i]['spotlight_photo'] = $doctorSpotlightPhotos[$i % count($doctorSpotlightPhotos)];
    $doctorSpotlight[$i]['spotlight_location'] = $doctor['hospital_city'] ?? $doctor['spotlight_location'] ?? $fallback['spotlight_location'];
}
$hospitalPreview = array_values(array_slice((array) $featuredHospitals, 0, 3));
$homeMapQuery = trim(($hospitalPreview[0]['city'] ?? 'Lahore') . ' ' . ($hospitalPreview[0]['country'] ?? 'Pakistan') . ' hospitals');
$homeMapSrc = 'https://www.google.com/maps?q=' . rawurlencode($homeMapQuery) . '&output=embed';

if (!function_exists('getDoctorSvgAvatar')) {
    function getDoctorSvgAvatar($specialization, $name) {
        $hash = md5($name);
        $hue1 = hexdec(substr($hash, 0, 2)) % 360;
        $hue2 = ($hue1 + 40) % 360;
        
        $specLower = strtolower($specialization);
        $iconPath = '';
        if (strpos($specLower, 'heart') !== false || strpos($specLower, 'cardio') !== false) {
            $iconPath = '<path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" fill="#ffffff" />';
        } else if (strpos($specLower, 'brain') !== false || strpos($specLower, 'neuro') !== false) {
            $iconPath = '<path d="M12 2c-4.97 0-9 4.03-9 9 0 2.12.74 4.07 1.97 5.61L4.35 19.4c-.39.39-.39 1.02 0 1.41.39.39 1.02.39 1.41 0l2.79-2.79C10.09 18.68 11.03 19 12 19c4.97 0 9-4.03 9-9s-4.03-9-9-9zm0 15c-3.86 0-7-3.14-7-7s3.14-7 7-7 7 3.14 7 7-3.14 7-7 7z" fill="#ffffff" />';
        } else if (strpos($specLower, 'skin') !== false || strpos($specLower, 'derm') !== false) {
            $iconPath = '<path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm0 18c-3.86 0-7-3.14-7-7s3.14-7 7-7 7 3.14 7 7-3.14 7-7 7z" fill="#ffffff" />';
        } else {
            $iconPath = '<path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z" fill="#ffffff" />';
        }
        
        return '
        <div class="vector-illustration doctor-vector-container" style="background: linear-gradient(135deg, hsl('.$hue1.', 85%, 65%), hsl('.$hue2.', 85%, 45%))">
            <svg width="36" height="36" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                '.$iconPath.'
            </svg>
        </div>';
    }
}

if (!function_exists('getHospitalSvgVisual')) {
    function getHospitalSvgVisual($name) {
        $hash = md5($name);
        $hue1 = hexdec(substr($hash, 4, 2)) % 360;
        $hue2 = ($hue1 + 50) % 360;
        
        return '
        <div class="vector-illustration hospital-vector-container mb-3" style="background: linear-gradient(135deg, hsl('.$hue1.', 80%, 60%), hsl('.$hue2.', 80%, 40%))">
            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M2 22H22" stroke="#ffffff" stroke-width="2" stroke-linecap="round" />
                <rect x="5" y="6" width="14" height="14" rx="2" fill="rgba(255,255,255,0.2)" stroke="#ffffff" stroke-width="2" />
                <circle cx="12" cy="11" r="3" fill="#ffffff" />
                <path d="M12 9V13M10 11H14" stroke="hsl('.$hue1.', 80%, 50%)" stroke-width="1.5" stroke-linecap="round" />
                <path d="M10 20V17C10 15.9 10.9 15 12 15C13.1 15 14 15.9 14 17V20" stroke="#ffffff" stroke-width="2" fill="rgba(255,255,255,0.3)" />
                <line x1="8" y1="9" x2="9.01" y2="9" stroke="#ffffff" stroke-width="2" stroke-linecap="round" />
                <line x1="15" y1="9" x2="16.01" y2="9" stroke="#ffffff" stroke-width="2" stroke-linecap="round" />
            </svg>
        </div>';
    }
}
?>
<section class="hero-section glass-panel overflow-hidden position-relative">
    <div id="particles-js" class="hero-particles"></div>
    <div class="row align-items-stretch g-5 position-relative">
        <div class="col-lg-6 reveal hero-copy-col">
            <span class="eyebrow"><?= e(__('home.eyebrow')) ?></span>
            <h1 class="display-5 fw-bold mt-3"><?= e(__('home.title')) ?></h1>
            <p class="lead text-muted mt-3"><?= e(__('home.subtitle')) ?></p>
            <?php if (!empty($homepageNotice)): ?><div class="homepage-notice"><?= e($homepageNotice) ?></div><?php endif; ?>
            <div class="d-flex flex-wrap gap-3 mt-4">
                <a href="<?= route_url('register') ?>" class="btn btn-primary btn-lg magnetic-btn"><?= e(__('common.get_started')) ?></a>
                <a href="<?= route_url('find-healthcare') ?>" class="btn btn-outline-primary btn-lg"><?= e(__('common.find_healthcare')) ?></a>
            </div>
            <div class="hero-metrics mt-4">
                <div><strong>50+</strong><span><?= e(__('home.metrics.specializations')) ?></span></div>
                <div><strong>24/7</strong><span><?= e(__('home.metrics.support')) ?></span></div>
                <div><strong>HIPAA</strong><span><?= e(__('home.metrics.security')) ?></span></div>
            </div>
        </div>
        <div class="col-lg-6 reveal reveal-delay-2 hero-visual-col">
            <div class="hero-photo-panel tilt-3d">
                <div class="tilt-shine"></div>
                <img src="<?= e(provider_photo_url('care', 'hero-care-team')) ?>" alt="Doctor consulting with a patient in a care room" loading="eager" fetchpriority="high">
                <div class="hero-photo-overlay">
                    <div>
                        <span>Connected care</span>
                        <strong>Premium care coordination for every patient journey</strong>
                    </div>
                    <i class="fa-solid fa-heart-pulse"></i>
                </div>
            </div>
            <div class="hero-card-stack mt-4">
                <div class="floating-card hero-snapshot-card tilt-3d border border-warning shadow-sm position-relative overflow-hidden" style="background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); color: white;">
                    <div class="tilt-shine"></div>
                    <div class="position-absolute top-0 end-0 m-2">
                        <span class="badge bg-warning text-dark px-2 py-1 shadow-sm"><i class="fa-solid fa-crown me-1"></i>Premium</span>
                    </div>
                    <div class="hero-card-head border-bottom border-light pb-2 mb-3" style="--bs-border-opacity: .2;">
                        <div>
                            <small class="text-light opacity-75">Connect Instantly</small>
                            <strong class="fs-5 d-block text-white">Live Video Consultation</strong>
                        </div>
                        <span class="badge bg-danger bg-opacity-25 text-white"><span class="pulse-dot bg-danger"></span> Live</span>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-white text-primary rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 50px; height: 50px; font-size: 1.5rem;">
                            <i class="fa-solid fa-video"></i>
                        </div>
                        <div style="flex: 1;">
                            <p class="mb-1 small text-light opacity-75">Talk to verified doctors face-to-face from anywhere with high-quality video & audio.</p>
                        </div>
                        <a href="<?= !App\Core\Auth::check() ? route_url('payments/subscriptions', ['return_to' => route_url('consultations')]) : route_url('consultations') ?>" class="btn btn-light btn-sm text-primary rounded-circle shadow-sm" style="width: 40px; height: 40px; line-height: 28px;" aria-label="Open Video Consultation"><i class="fa-solid fa-arrow-right"></i></a>
                    </div>
                </div>

                <div class="floating-card hero-ai-card mt-3 tilt-3d border border-info shadow-sm position-relative overflow-hidden" style="background: linear-gradient(135deg, #fdfbfb 0%, #ebedee 100%);">
                    <div class="tilt-shine"></div>
                    <div class="position-absolute top-0 end-0 m-2">
                        <span class="badge bg-warning text-dark px-2 py-1 shadow-sm"><i class="fa-solid fa-crown me-1"></i>Premium</span>
                    </div>
                    <div class="hero-ai-main align-items-start mt-2">
                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 50px; height: 50px; font-size: 1.5rem;">
                            <i class="fa-solid fa-brain"></i>
                        </div>
                        <div class="ms-3">
                            <small class="text-primary fw-bold">Powered by Gemini AI</small>
                            <h5 class="fw-bold mb-1 text-dark">Real AI Disease Scanner</h5>
                            <p class="text-muted small mb-0">Upload images and describe symptoms to get a real-time, highly accurate medical assessment.</p>
                        </div>
                    </div>
                    <a href="<?= !App\Core\Auth::check() ? route_url('payments/subscriptions', ['return_to' => route_url('scanner')]) : route_url('scanner') ?>" class="btn btn-primary btn-sm rounded-pill w-100 mt-3 shadow-sm" aria-label="Open AI scanner">Try AI Scanner <i class="fa-solid fa-arrow-right ms-1"></i></a>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="trust-bar glass-panel">
    <div class="trust-item"><i class="fa-solid fa-shield-halved"></i><span>HIPAA-ready</span></div>
    <div class="trust-item"><i class="fa-solid fa-lock"></i><span>Secure Uploads</span></div>
    <div class="trust-item"><i class="fa-solid fa-certificate"></i><span>Verified Doctors</span></div>
    <div class="trust-item"><i class="fa-solid fa-globe"></i><span>Hospital Network</span></div>
</section>

<section class="mt-5">
    <div class="section-heading text-center mb-5 reveal">
        <span class="eyebrow"><?= e(__('home.features_eyebrow')) ?></span>
        <h2><?= e(__('home.features_title')) ?></h2>
    </div>
    <div class="row g-4">
        <?php $fi = 0; foreach ((array) $homeFeatures as $feature): $fi++; ?>
            <div class="col-md-6 col-xl-4 reveal reveal-delay-<?= min($fi, 3) ?>" data-aos="zoom-in">
                <div class="feature-card glass-panel h-100 tilt-3d">
                    <div class="tilt-shine"></div>
                    <div class="feature-icon"><i class="fa-solid <?= e($feature['icon']) ?>"></i></div>
                    <h5><?= e($feature['title']) ?></h5>
                    <p class="text-muted mb-0"><?= e($feature['text']) ?></p>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<section class="home-flow-section mt-5" aria-labelledby="home-flow-title">
    <div class="home-flow-shell">
        <div class="home-flow-copy reveal" data-aos="fade-right">
            <span class="eyebrow home-flow-eyebrow"><?= e(__('home.how_it_works')) ?></span>
            <h2 id="home-flow-title"><?= e(__('home.how_title')) ?></h2>
        </div>
        <div class="home-flow-steps">
            <?php foreach ((array) $homeSteps as $index => $step): ?>
                <article class="home-flow-card tilt-3d" data-aos="fade-up" data-aos-delay="<?= (int) $index * 80 ?>">
                    <div class="tilt-shine"></div>
                    <div class="home-flow-number"><?= e($step['n']) ?></div>
                    <h3><?= e($step['t']) ?></h3>
                    <p><?= e($step['d']) ?></p>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="mt-5 reveal" id="doctorsSection" data-aos="fade-up">
    <div class="d-flex justify-content-between align-items-end mb-4 flex-wrap gap-3">
        <div>
            <span class="eyebrow"><?= e(__('home.featured_doctors')) ?></span>
            <h2 class="mt-2 mb-0"><?= e(__('home.trusted_specialists')) ?></h2>
        </div>
        <div>
            <a href="<?= route_url('find-healthcare') ?>" class="btn btn-outline-primary btn-sm px-4"><?= e(__('common.view_all')) ?></a>
        </div>
    </div>
    
    <div class="row g-4 paging-container" id="featuredDoctorsContainer">
        <?php foreach ($doctorSpotlight as $di => $doctor): ?>
            <div class="col-md-6 col-lg-4 doctor-card-col">
                <div class="doctor-card doctor-spotlight-card white-card tilt-3d h-100 d-flex flex-column justify-content-between">
                    <div class="tilt-shine"></div>
                    <div class="provider-card-photo doctor-photo mb-3">
                        <img src="<?= e($doctor['spotlight_photo']) ?>" alt="<?= e($doctor['name']) ?> portrait" loading="lazy">
                        <span class="provider-photo-badge"><?= e($doctor['specialization']) ?></span>
                    </div>
                    <div>
                        <div class="d-flex align-items-center mb-3">
                            <div class="global-doctor-badge"><i class="fa-solid fa-earth-americas"></i></div>
                            <div>
                                <h6 class="mb-1 fw-bold"><?= e($doctor['name']) ?></h6>
                                <div class="text-primary small fw-semibold"><?= e($doctor['specialization']) ?></div>
                            </div>
                        </div>
                        <div class="doctor-location-chip mb-3"><i class="fa-solid fa-location-dot"></i><?= e($doctor['spotlight_location']) ?></div>
                        <p class="text-muted small mb-3"><?= e($doctor['bio'] ?? 'Experienced specialist committed to delivering exceptional patient care, modern diagnostics, and personal consultation.') ?></p>
                        <div class="small mt-auto pt-3 border-top border-light d-flex flex-column gap-2" style="--border: rgba(255,255,255,0.06);">
                            <div class="d-flex align-items-center gap-2 text-secondary">
                                <i class="fa-solid fa-hospital text-primary"></i>
                                <span><?= e($doctor['hospital_name'] ?? 'Independent Practice') ?></span>
                            </div>
                            <div class="d-flex align-items-center gap-2 text-secondary">
                                <i class="fa-solid fa-graduation-cap text-primary"></i>
                                <span><?= e($doctor['qualification_details'] ?? 'MBBS, Certified Consultant') ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top border-light" style="--border: rgba(255,255,255,0.06);">
                        <span class="badge text-bg-light p-2">PKR <?= number_format((float) $doctor['consultation_fee']) ?></span>
                        <?php
                        $bookingParams = ['book' => '1', 'doctor_id' => (int) ($doctor['user_id'] ?? 0)];
                        if (!empty($doctor['hospital_id'])) {
                            $bookingParams['hospital_id'] = (int) $doctor['hospital_id'];
                        }
                        ?>
                        <a href="<?= route_url('appointments', $bookingParams) ?>" class="btn btn-primary btn-sm px-3" style="min-height: 32px;">Book</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="paging-controls" id="doctorsPagingControls">
        <button class="paging-btn" id="doctorsPrevBtn" aria-label="Previous page"><i class="fa-solid fa-chevron-left"></i></button>
        <div class="paging-dots" id="doctorsPagingDots"></div>
        <button class="paging-btn" id="doctorsNextBtn" aria-label="Next page"><i class="fa-solid fa-chevron-right"></i></button>
    </div>
</section>

<section class="mt-5 reveal world-hospitals-section" data-aos="fade-up">
    <div class="d-flex justify-content-between align-items-end mb-4 flex-wrap gap-3">
        <div>
            <span class="eyebrow">World hospital spotlight</span>
            <h2 class="mt-2 mb-0">World-class hospital network</h2>
        </div>
        <a href="<?= route_url('find-healthcare') ?>" class="btn btn-outline-primary btn-sm px-4">Explore Care</a>
    </div>
    <div class="row g-4">
        <?php foreach ($worldHospitals as $hospital): ?>
            <div class="col-md-6 col-xl-4">
                <div class="world-hospital-card tilt-3d">
                    <div class="tilt-shine"></div>
                    <div class="world-hospital-photo">
                        <img src="<?= e($hospital['image']) ?>" alt="<?= e($hospital['name']) ?> hospital image" loading="lazy">
                        <span>Rank <?= e($hospital['rank']) ?></span>
                    </div>
                    <div class="world-hospital-body">
                        <small><?= e($hospital['location']) ?></small>
                        <h5><?= e($hospital['name']) ?></h5>
                        <p><?= e($hospital['note']) ?></p>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<section class="mt-5 reveal" id="hospitalsSection" data-aos="fade-up">
    <div class="d-flex justify-content-between align-items-end mb-4 flex-wrap gap-3">
        <div>
            <span class="eyebrow"><?= e(__('home.partner_hospitals')) ?></span>
            <h2 class="mt-2 mb-0"><?= e(__('home.global_directory')) ?></h2>
        </div>
        <div>
            <a href="<?= route_url('find-healthcare') ?>" class="btn btn-outline-primary btn-sm px-4">Interactive Map</a>
        </div>
    </div>
    
    <div class="home-map-showcase glass-panel mb-4">
        <div class="home-map-copy">
            <span class="eyebrow">Original Google Map</span>
            <h3>Explore hospitals on the live map</h3>
            <p class="text-muted mb-0">Search nearby hospitals with the original Google Map preview and open the full directory for filters, routes, and details.</p>
            <div class="home-map-stats">
                <span><strong><?= count((array) $featuredHospitals) ?>+</strong> partners</span>
                <span><strong>24/7</strong> emergency view</span>
                <span><strong>Map</strong> ready</span>
            </div>
        </div>
        <div class="home-google-map">
            <iframe
                src="<?= e($homeMapSrc) ?>"
                title="Hospital directory Google Map preview"
                loading="lazy"
                referrerpolicy="no-referrer-when-downgrade"></iframe>
        </div>
    </div>

    <div class="row g-4 paging-container" id="featuredHospitalsContainer">
        <?php foreach ($hospitalPreview as $hospital): ?>
            <div class="col-md-6 col-lg-4 hospital-card-col">
                <div class="feature-card glass-panel h-100 tilt-3d d-flex flex-column justify-content-between p-4">
                    <div class="tilt-shine"></div>
                    <div>
                        <div class="provider-card-photo hospital-photo mb-3">
                            <img src="<?= e(provider_photo_url('hospital', $hospital['name'] ?? 'hospital')) ?>" alt="<?= e($hospital['name']) ?>" loading="lazy">
                            <span class="provider-photo-badge"><?= e($hospital['city'] ?? 'Hospital') ?></span>
                        </div>
                        <div class="d-flex justify-content-between align-items-start mb-2 gap-2">
                            <h5 class="mb-0 fw-bold"><?= e($hospital['name']) ?></h5>
                            <span class="badge text-bg-info text-capitalize small" style="font-size: 0.65rem; padding: 4px 8px;"><?= e($hospital['type']) ?></span>
                        </div>
                        <p class="text-secondary small mb-3">
                            <i class="fa-solid fa-location-dot text-primary me-2"></i><?= e($hospital['address']) ?>, <?= e($hospital['city']) ?>
                        </p>
                        <?php if(!empty($hospital['facilities'])): ?>
                            <div class="small mb-3">
                                <strong>Facilities:</strong>
                                <div class="d-flex flex-wrap gap-1 mt-1">
                                    <?php 
                                    $facilities = explode(',', $hospital['facilities']);
                                    foreach(array_slice($facilities, 0, 3) as $fac): 
                                    ?>
                                        <span class="badge text-bg-light text-muted x-small" style="font-size: 0.65rem; padding: 3px 6px;"><?= trim(e($fac)) ?></span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top border-light" style="--border: rgba(255,255,255,0.06);">
                        <span class="small text-muted"><i class="fa-solid fa-phone text-primary me-1"></i><?= e($hospital['phone'] ?? 'N/A') ?></span>
                        <a href="<?= route_url('find-healthcare') ?>" class="btn btn-outline-primary btn-sm px-3" style="min-height: 32px;">Map Details</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="paging-controls" id="hospitalsPagingControls">
        <button class="paging-btn" id="hospitalsPrevBtn" aria-label="Previous page"><i class="fa-solid fa-chevron-left"></i></button>
        <div class="paging-dots" id="hospitalsPagingDots"></div>
        <button class="paging-btn" id="hospitalsNextBtn" aria-label="Next page"><i class="fa-solid fa-chevron-right"></i></button>
    </div>
</section>

<section class="mt-5 mb-2 content-newsletter-section reveal" data-aos="fade-up">
    <div class="content-hub-heading">
        <div class="content-hub-heading-copy">
            <span class="eyebrow"><?= e(__('home.content_eyebrow')) ?></span>
            <h3><?= e(__('home.content_title')) ?></h3>
            <p class="text-muted mb-0">Articles, guidelines, disease education, and newsletter updates are organized into a compact knowledge hub.</p>
        </div>
        <a href="<?= route_url('blog') ?>" class="btn btn-outline-primary btn-sm content-hub-browse">
            Browse Content <i class="fa-solid fa-arrow-right"></i>
        </a>
    </div>
    <div class="content-hub-grid content-newsletter-grid">
        <article class="content-hub-card content-hub-card--blog tilt-3d">
            <div class="tilt-shine"></div>
            <div class="content-card-top">
                <i class="fa-solid fa-newspaper content-card-icon"></i>
                <span class="content-card-kicker">Blog</span>
            </div>
            <strong><a href="<?= e($featuredBlogUrl) ?>" class="content-card-title-link"><?= e($featuredBlog['title'] ?? ((array) $homeBlogs)[0] ?? 'Fresh healthcare articles') ?></a></strong>
            <small>Latest care insights from the platform.</small>
            <div class="content-card-meta">
                <span><i class="fa-regular fa-clock"></i> Fresh reads</span>
                <a href="<?= e($featuredBlogUrl) ?>" class="content-card-action" aria-label="Read featured blog article">Read <i class="fa-solid fa-arrow-right"></i></a>
            </div>
        </article>
        <article class="content-hub-card content-hub-card--guidelines tilt-3d">
            <div class="tilt-shine"></div>
            <div class="content-card-top">
                <i class="fa-solid fa-book-medical content-card-icon"></i>
                <span class="content-card-kicker">Guidelines</span>
            </div>
            <strong>Clinical help and care workflows</strong>
            <small>Step-by-step support for core healthcare workflows.</small>
            <div class="content-card-meta">
                <span><i class="fa-solid fa-shield-heart"></i> Care ready</span>
                <a href="<?= route_url('guidelines') ?>" class="content-card-action" aria-label="Open guidelines">Open <i class="fa-solid fa-arrow-right"></i></a>
            </div>
        </article>
        <article class="content-hub-card content-hub-card--education tilt-3d">
            <div class="tilt-shine"></div>
            <div class="content-card-top">
                <i class="fa-solid fa-graduation-cap content-card-icon"></i>
                <span class="content-card-kicker">Patient Education</span>
            </div>
            <strong>Disease library and health learning</strong>
            <small>Disease information and patient-friendly learning resources.</small>
            <div class="content-card-meta">
                <span><i class="fa-solid fa-circle-info"></i> Easy learning</span>
                <a href="<?= route_url('diseases') ?>" class="content-card-action" aria-label="Learn from patient education library">Learn <i class="fa-solid fa-arrow-right"></i></a>
            </div>
        </article>
        <div class="content-hub-card newsletter-card newsletter-hub-card content-hub-card--newsletter tilt-3d" id="newsletter">
            <div class="tilt-shine"></div>
            <div class="content-card-top">
                <i class="fa-solid fa-envelope-open-text content-card-icon"></i>
                <span class="content-card-kicker"><?= e(__('home.newsletter_eyebrow')) ?></span>
            </div>
            <strong><?= e(__('home.newsletter_title')) ?></strong>
            <small><?= e(__('home.newsletter_note')) ?></small>
            <div class="newsletter-benefits">
                <span><i class="fa-solid fa-check"></i> Care insights</span>
                <span><i class="fa-solid fa-check"></i> Guideline updates</span>
            </div>
            <form method="POST" action="<?= route_url('newsletter/subscribe') ?>" class="d-grid gap-2 newsletter-form">
                <?= csrf_field() ?>
                <input type="hidden" name="source" value="newsletter">
                <input type="email" name="email" class="form-control" placeholder="<?= e(__('home.newsletter_placeholder')) ?>" required>
                <button type="submit" class="btn btn-primary magnetic-btn"><?= e(__('common.subscribe')) ?></button>
            </form>
            <?php if (!empty($emergencyHotline)): ?><div class="newsletter-hotline">Emergency Hotline: <?= e($emergencyHotline) ?></div><?php endif; ?>
        </div>
    </div>
</section>
