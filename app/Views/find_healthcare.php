<?php
$subscriptionLocked = !empty($subscriptionLocked);
$worldHospitals = [
    [
        'rank' => 1,
        'name' => 'Mayo Clinic - Rochester',
        'city' => 'Rochester',
        'country' => 'United States',
        'address' => '200 First St SW, Rochester, Minnesota',
        'coordinates' => '44.0229,-92.4663',
        'recognition' => 'Ranked No. 1 in Newsweek World\'s Best Hospitals 2026.',
        'image' => 'https://images.unsplash.com/photo-1586773860418-d37222d8fce3?auto=format&fit=crop&w=1100&q=85',
    ],
    [
        'rank' => 2,
        'name' => 'Toronto General - University Health Network',
        'city' => 'Toronto',
        'country' => 'Canada',
        'address' => '200 Elizabeth St, Toronto, Ontario',
        'coordinates' => '43.6596,-79.3888',
        'recognition' => 'Ranked No. 2 globally in Newsweek World\'s Best Hospitals 2026.',
        'image' => 'https://images.unsplash.com/photo-1519494026892-80bbd2d6fd0d?auto=format&fit=crop&w=1100&q=85',
    ],
    [
        'rank' => 3,
        'name' => 'Cleveland Clinic',
        'city' => 'Cleveland',
        'country' => 'United States',
        'address' => '9500 Euclid Ave, Cleveland, Ohio',
        'coordinates' => '41.5031,-81.6205',
        'recognition' => 'Ranked No. 3 globally in Newsweek World\'s Best Hospitals 2026.',
        'image' => 'https://images.unsplash.com/photo-1538108149393-fbbd81895907?auto=format&fit=crop&w=1100&q=85',
    ],
];

$doctorFallbacks = [
    [
        'name' => 'Dr. Amara Khan',
        'specialization' => 'Cardiology',
        'hospital_name' => 'Global Heart Institute',
        'hospital_city' => 'London',
        'hospital_country' => 'United Kingdom',
        'experience' => 12,
        'consultation_fee' => 12000,
        'image_url' => 'https://images.unsplash.com/photo-1559839734-2b71ea197ec2?auto=format&fit=crop&w=900&q=85',
    ],
    [
        'name' => 'Dr. Sofia Martinez',
        'specialization' => 'Family Medicine',
        'hospital_name' => 'CityCare Medical Center',
        'hospital_city' => 'Toronto',
        'hospital_country' => 'Canada',
        'experience' => 9,
        'consultation_fee' => 9000,
        'image_url' => 'https://images.unsplash.com/photo-1678695972687-033fa0bdbac9?auto=format&fit=crop&w=900&q=85',
    ],
    [
        'name' => 'Dr. Daniel Chen',
        'specialization' => 'Neurology',
        'hospital_name' => 'NeuroLife Clinic',
        'hospital_city' => 'Singapore',
        'hospital_country' => 'Singapore',
        'experience' => 14,
        'consultation_fee' => 15000,
        'image_url' => 'https://images.unsplash.com/photo-1612349317150-e413f6a5b16d?auto=format&fit=crop&w=900&q=85',
    ],
];

$facilityCards = [
    [
        'title' => 'Emergency & Trauma',
        'text' => 'Rapid urgent-care routing, ambulance-ready locations, and emergency support details.',
        'image' => 'https://images.unsplash.com/photo-1584982751601-97dcc096659c?auto=format&fit=crop&w=1000&q=88',
        'map' => 'emergency hospital near Rochester Minnesota',
        'focus' => 'emergency',
        'icon' => 'fa-truck-medical',
    ],
    [
        'title' => 'Diagnostics & Imaging',
        'text' => 'Find hospitals with imaging, labs, specialist scans, and report coordination.',
        'image' => 'https://images.unsplash.com/photo-1579684385127-1ef15d508118?auto=format&fit=crop&w=1000&q=88',
        'map' => 'diagnostic imaging hospital near Toronto Canada',
        'focus' => 'hospital',
        'icon' => 'fa-x-ray',
    ],
    [
        'title' => 'Pharmacy & Follow-up',
        'text' => 'Connect care plans with pharmacy access, follow-up visits, and patient education.',
        'image' => 'https://images.unsplash.com/photo-1587854692152-cbe660dbde88?auto=format&fit=crop&w=1000&q=88',
        'map' => 'hospital pharmacy near Cleveland Clinic',
        'focus' => 'pharmacy',
        'icon' => 'fa-capsules',
    ],
];

$visibleDoctors = array_slice($doctors ?: $doctorFallbacks, 0, 3);
$sourceUrl = 'https://rankings.newsweek.com/worlds-best-hospitals-2026';
$careMapUrl = static function (array $params = []): string {
    return route_url('map', array_filter($params, static fn($value): bool => $value !== null && $value !== ''));
};
$mapCanvasAnchor = '#healthcareExplorerMap';
$nearbyMapUrl = $careMapUrl(['nearby' => '1']) . $mapCanvasAnchor;
$focusMapUrl = static fn(string $focus): string => $careMapUrl(['nearby' => '1', 'focus' => $focus]) . '#healthcareExplorerMap';
?>

<section class="find-care-showcase find-care-showcase-short glass-panel">
    <div class="find-care-showcase-copy">
        <div class="find-care-hero-intro">
            <span class="eyebrow">Find care worldwide</span>
            <h1>Find trusted hospitals, doctors, and care services.</h1>
            <p class="text-muted">Search verified care options, compare providers, and open map directions, emergency routes, and booking details in one place.</p>
            <div class="d-flex flex-wrap gap-2">
                <a href="<?= e($nearbyMapUrl) ?>" class="btn btn-primary magnetic-btn"><i class="fa-solid fa-map-location-dot"></i> Open Map</a>
                <a href="#careDoctors" class="btn btn-outline-primary"><i class="fa-solid fa-user-doctor"></i> See Doctors</a>
            </div>
        </div>
        <div class="find-care-hero-details" aria-label="Find Care service highlights">
            <div>
                <i class="fa-solid fa-hospital-user"></i>
                <strong>Verified care network</strong>
                <span>Hospitals, specialists, diagnostics, and emergency care.</span>
            </div>
            <div>
                <i class="fa-solid fa-map-location-dot"></i>
                <strong>Map-first discovery</strong>
                <span>Nearby routes, directions, and service filters.</span>
            </div>
            <div>
                <i class="fa-solid fa-calendar-check"></i>
                <strong>Booking-ready decisions</strong>
                <span>Compare specialty, fees, experience, and location.</span>
            </div>
        </div>
    </div>
    <div class="find-care-showcase-media">
        <img src="https://images.unsplash.com/photo-1576091160550-2173dba999ef?auto=format&fit=crop&w=1400&q=85" alt="Doctor reviewing digital healthcare records with a patient" loading="eager" fetchpriority="high">
        <div class="showcase-media-caption">
            <span>Connected care</span>
            <strong>Search, compare, open map, then book.</strong>
        </div>
    </div>
</section>

<?php if ($subscriptionLocked): ?>
    <section class="premium-inline-banner mt-4" data-aos="fade-up">
        <div>
            <span class="eyebrow">Lite discovery tools</span>
            <h2>The directory is free to browse</h2>
            <p>Provider cards, search, and booking links remain available. Lite unlocks live comparison sync and advanced nearby discovery from the map workflow.</p>
        </div>
        <button class="btn btn-primary" type="button" data-premium-locked="1">
            <i class="fa-solid fa-crown"></i>
            Unlock Discovery
        </button>
    </section>
<?php endif; ?>

<section class="find-care-core-grid mt-4">
    <div class="find-care-core-left">
        <div class="glass-panel find-filter-panel">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
                <div>
                    <span class="eyebrow">Care search logic</span>
                    <h2 class="h4 mb-1">Search care and open the map</h2>
                </div>
                <a href="<?= e($nearbyMapUrl) ?>" class="btn btn-outline-primary btn-sm">Full map</a>
            </div>
            <form class="row g-3 align-items-end" method="GET" action="<?= app_url('index.php') ?>">
                <input type="hidden" name="route" value="map">
                <div class="col-md-6">
                    <label class="form-label">Specialty</label>
                    <input type="text" name="specialization" class="form-control" value="<?= e($filters['specialization'] ?? '') ?>" placeholder="Cardiology, Dermatology...">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Location</label>
                    <input type="text" name="location" class="form-control" value="<?= e($filters['location'] ?? '') ?>" placeholder="Exact area, city, country">
                </div>
                <div class="col-md-7">
                    <label class="form-label">Hospital</label>
                    <select name="hospital_id" class="form-select">
                        <option value="">All hospitals</option>
                        <?php foreach ($hospitals as $hospital): ?>
                            <option value="<?= (int) $hospital['id'] ?>" <?= ($filters['hospital_id'] ?? '') == $hospital['id'] ? 'selected' : '' ?>><?= e($hospital['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Max fee</label>
                    <input type="number" name="max_fee" class="form-control" value="<?= e($filters['max_fee'] ?? '') ?>" placeholder="PKR" min="0" step="500" inputmode="numeric">
                </div>
                <div class="col-md-2 d-grid">
                    <button class="btn btn-primary" type="submit" aria-label="Search map"><i class="fa-solid fa-magnifying-glass"></i></button>
                </div>
            </form>
            <div class="care-logic-row mt-4">
                <div><i class="fa-solid fa-filter"></i><strong>Filter</strong><span>specialty, city, hospital</span></div>
                <div><i class="fa-solid fa-hospital"></i><strong>Compare</strong><span>rank, services, details</span></div>
                <div><i class="fa-solid fa-map-location-dot"></i><strong>Map</strong><span>open full map page</span></div>
            </div>
            <div class="care-search-support">
                <i class="fa-solid fa-circle-check"></i>
                <span>Search opens the map and focuses your exact location, with specialty, hospital, and fee kept in the request.</span>
            </div>
        </div>

    </div>

    <aside class="find-care-image-panel glass-panel">
        <div class="find-care-section-head">
            <div>
                <span class="eyebrow">Care areas</span>
                <h2>Facilities in focus</h2>
            </div>
            <a href="<?= e($nearbyMapUrl) ?>" class="btn btn-outline-primary btn-sm">Open map</a>
        </div>
        <div class="care-small-image-row">
            <?php foreach ($facilityCards as $card): ?>
                <a href="<?= e($focusMapUrl($card['focus'] ?? 'hospital')) ?>" class="care-small-image-card">
                    <img src="<?= e($card['image']) ?>" alt="<?= e($card['title']) ?>" loading="lazy">
                    <span>
                        <i class="fa-solid <?= e($card['icon']) ?>"></i>
                        <strong><?= e($card['title']) ?></strong>
                        <small><?= e($card['text']) ?></small>
                    </span>
                </a>
            <?php endforeach; ?>
        </div>
    </aside>
</section>

<section class="find-care-gallery-section mt-4">
    <div class="find-care-section-head">
        <div>
            <span class="eyebrow">Facilities</span>
            <h2>Three service areas with images</h2>
        </div>
    </div>
    <div class="find-care-card-grid">
        <?php foreach ($facilityCards as $card): ?>
            <article class="find-care-image-card">
                <img src="<?= e($card['image']) ?>" alt="<?= e($card['title']) ?>" loading="lazy">
                <div>
                    <span><i class="fa-solid <?= e($card['icon']) ?>"></i> Facility</span>
                    <h3><?= e($card['title']) ?></h3>
                    <p><?= e($card['text']) ?></p>
                    <a href="<?= e($focusMapUrl($card['focus'] ?? 'hospital')) ?>" class="btn btn-outline-primary btn-sm">Show on map</a>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<section class="find-care-gallery-section mt-4">
    <div class="find-care-section-head">
        <div>
            <span class="eyebrow">Hospitals</span>
            <h2>Three top hospital image cards</h2>
        </div>
        <a href="<?= e($sourceUrl) ?>" target="_blank" rel="noopener">Ranking source</a>
    </div>
    <div class="find-care-card-grid">
        <?php foreach ($worldHospitals as $hospital): ?>
            <article class="find-care-image-card">
                <img src="<?= e($hospital['image']) ?>" alt="<?= e($hospital['name']) ?>" loading="lazy">
                <div>
                    <span>#<?= (int) $hospital['rank'] ?> global hospital</span>
                    <h3><?= e($hospital['name']) ?></h3>
                    <p><?= e($hospital['recognition']) ?></p>
                    <div class="d-flex gap-2 flex-wrap">
                        <a href="<?= e($focusMapUrl('global_hospital')) ?>" class="btn btn-outline-primary btn-sm">Show on map</a>
                        <a href="<?= e($focusMapUrl('global_hospital')) ?>" class="btn btn-primary btn-sm">Open map page</a>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<section class="find-care-gallery-section mt-4" id="careDoctors">
    <div class="find-care-section-head">
        <div>
            <span class="eyebrow">Doctors</span>
            <h2>Three real doctor image cards</h2>
        </div>
        <span class="badge text-bg-light"><?= count($visibleDoctors) ?> shown</span>
    </div>
    <div class="find-care-card-grid">
        <?php foreach ($visibleDoctors as $doctor): ?>
            <article class="find-care-doctor-card">
                <img src="<?= e($doctor['image_url'] ?? provider_photo_url('doctor', $doctor['name'] ?? 'doctor')) ?>" alt="<?= e($doctor['name']) ?>" loading="lazy">
                <div>
                    <span><?= e($doctor['specialization']) ?></span>
                    <h3><?= e($doctor['name']) ?></h3>
                    <p><i class="fa-solid fa-hospital text-primary"></i> <?= e($doctor['hospital_name'] ?? 'Independent') ?></p>
                    <p><i class="fa-solid fa-location-dot text-success"></i> <?= e(trim(($doctor['hospital_city'] ?? '') . ', ' . ($doctor['hospital_country'] ?? ''), ', ')) ?: 'Location not specified' ?></p>
                    <div class="provider-price-row">
                        <span><?= e((string) ($doctor['experience'] ?? 0)) ?> years</span>
                        <strong>PKR <?= number_format((float) ($doctor['consultation_fee'] ?? 0)) ?></strong>
                    </div>
                    <?php
                    $bookingParams = ['book' => '1', 'doctor_id' => (int) ($doctor['user_id'] ?? 0)];
                    if (!empty($doctor['hospital_id'])) {
                        $bookingParams['hospital_id'] = (int) $doctor['hospital_id'];
                    }
                    ?>
                    <div class="d-flex gap-2 flex-wrap mt-3">
                        <a href="<?= route_url('appointments', $bookingParams) ?>" class="btn btn-primary btn-sm">Book</a>
                        <a href="<?= e($focusMapUrl('doctor')) ?>" class="btn btn-outline-primary btn-sm">Show map</a>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</section>
