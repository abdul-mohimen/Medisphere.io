<?php
$subscriptionLocked = !empty($subscriptionLocked);
$premiumAttrs = $subscriptionLocked ? ' data-premium-locked="1"' : '';
$mapSearchValue = trim((string) ($_GET['location'] ?? $_GET['q'] ?? ''));
$autoNearby = isset($_GET['nearby']) || $mapSearchValue === '';
$allowedMapFocusTypes = ['hospital', 'global_hospital', 'doctor', 'pharmacy', 'emergency'];
$mapFocusType = trim((string) ($_GET['focus'] ?? ''));
if (!in_array($mapFocusType, $allowedMapFocusTypes, true)) {
    $mapFocusType = '';
}
$mapGoogleQuery = $mapSearchValue !== '' ? $mapSearchValue : 'healthcare near me';
$mapGoogleSearchUrl = 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($mapGoogleQuery);
$activeSearchFilters = array_filter([
    'Specialty' => trim((string) ($_GET['specialization'] ?? '')),
    'Location' => $mapSearchValue,
    'Max fee' => trim((string) ($_GET['max_fee'] ?? '')),
], static fn(string $value): bool => $value !== '');

$featuredWorldHospitals = [
    [
        'id' => 'world-mayo',
        'global_rank' => 1,
        'name' => 'Mayo Clinic - Rochester',
        'address' => '200 First St SW',
        'city' => 'Rochester',
        'country' => 'United States',
        'coordinates' => '44.0229,-92.4663',
        'recognition' => 'Featured world hospital marker',
        'source' => 'MediSphere demo map',
        'image_url' => provider_photo_url('hospital', 'mayo-clinic-rochester'),
    ],
    [
        'id' => 'world-toronto-general',
        'global_rank' => 2,
        'name' => 'Toronto General - University Health Network',
        'address' => '200 Elizabeth St',
        'city' => 'Toronto',
        'country' => 'Canada',
        'coordinates' => '43.6596,-79.3888',
        'recognition' => 'Featured world hospital marker',
        'source' => 'MediSphere demo map',
        'image_url' => provider_photo_url('hospital', 'toronto-general'),
    ],
    [
        'id' => 'world-cleveland-clinic',
        'global_rank' => 3,
        'name' => 'Cleveland Clinic',
        'address' => '9500 Euclid Ave',
        'city' => 'Cleveland',
        'country' => 'United States',
        'coordinates' => '41.5031,-81.6205',
        'recognition' => 'Featured world hospital marker',
        'source' => 'MediSphere demo map',
        'image_url' => provider_photo_url('hospital', 'cleveland-clinic'),
    ],
];

$supportPlaces = [];

$mapHospitals = $hospitals;
$mapDoctors = $doctors;
$hospitalMarkerCount = count($mapHospitals) + count($featuredWorldHospitals);

$facilityPayload = [
    'hospitals' => array_map(function (array $hospital) {
        return [
            'id' => (int) ($hospital['id'] ?? 0),
            'name' => $hospital['name'] ?? 'Hospital',
            'address' => $hospital['address'] ?? '',
            'city' => $hospital['city'] ?? '',
            'country' => $hospital['country'] ?? '',
            'phone' => $hospital['phone'] ?? '',
            'email' => $hospital['email'] ?? '',
            'facilities' => $hospital['facilities'] ?? '',
            'verified_status' => $hospital['verified_status'] ?? 'pending',
            'coordinates' => $hospital['coordinates'] ?? '',
            'image_url' => provider_photo_url('hospital', $hospital['name'] ?? 'hospital'),
            'type' => 'hospital',
        ];
    }, $mapHospitals),
    'world_hospitals' => $featuredWorldHospitals,
    'doctors' => array_map(function (array $doctor) {
        return [
            'id' => (int) ($doctor['user_id'] ?? $doctor['id'] ?? 0),
            'name' => $doctor['name'] ?? 'Doctor',
            'specialization' => $doctor['specialization'] ?? 'General Medicine',
            'experience' => (int) ($doctor['experience'] ?? 0),
            'consultation_fee' => (float) ($doctor['consultation_fee'] ?? 0),
            'verified_status' => $doctor['verified_status'] ?? 'pending',
            'hospital_id' => !empty($doctor['hospital_id']) ? (int) $doctor['hospital_id'] : null,
            'hospital_name' => $doctor['hospital_name'] ?? '',
            'hospital_address' => $doctor['hospital_address'] ?? '',
            'hospital_city' => $doctor['hospital_city'] ?? '',
            'hospital_country' => $doctor['hospital_country'] ?? '',
            'hospital_coordinates' => $doctor['hospital_coordinates'] ?? '',
            'image_url' => provider_photo_url('doctor', $doctor['name'] ?? 'doctor'),
            'type' => 'doctor',
        ];
    }, $mapDoctors),
    'support_places' => $supportPlaces,
];
?>
<section class="map-hero-shell glass-panel" data-aos="fade-up">
    <div class="map-hero-copy">
        <span class="eyebrow">Live healthcare map</span>
        <h1>Find trusted care with a clearer world view.</h1>
        <p>Search exact areas, inspect hospital and doctor markers, switch map styles, and route from your location without leaving the page.</p>
        <div class="map-hero-actions">
            <a href="#healthcareExplorerMap" class="btn btn-primary">
                <i class="fa-solid fa-map-location-dot"></i>
                Open Live Map
            </a>
            <a href="<?= route_url('find-healthcare') ?>" class="btn btn-outline-primary">
                <i class="fa-solid fa-stethoscope"></i>
                Care Directory
            </a>
            <a href="<?= e($mapGoogleSearchUrl) ?>" class="btn btn-outline-primary" target="_blank" rel="noopener" data-google-map-link>
                <i class="fa-brands fa-google"></i>
                Google Area
            </a>
        </div>
        <div class="map-hero-metrics">
            <span><strong><?= $hospitalMarkerCount ?></strong> Hospitals</span>
            <span><strong><?= count($mapDoctors) ?></strong> Doctors</span>
            <span><strong>4</strong> Care layers</span>
        </div>
    </div>
    <div class="map-hero-preview" aria-hidden="true">
        <div class="map-hero-radar"></div>
        <span class="map-hero-pin hospital" style="--x: 24%; --y: 34%;"><i class="fa-solid fa-hospital"></i></span>
        <span class="map-hero-pin doctor" style="--x: 58%; --y: 26%;"><i class="fa-solid fa-user-doctor"></i></span>
        <span class="map-hero-pin pharmacy" style="--x: 72%; --y: 60%;"><i class="fa-solid fa-capsules"></i></span>
        <span class="map-hero-pin emergency" style="--x: 38%; --y: 72%;"><i class="fa-solid fa-truck-medical"></i></span>
        <div class="map-hero-route"></div>
        <div class="map-hero-floating-card">
            <span>Satellite ready</span>
            <strong>Care markers synced</strong>
        </div>
    </div>
</section>

<?php if ($subscriptionLocked): ?>
    <section class="premium-inline-banner mb-4" data-aos="fade-up">
        <div>
            <span class="eyebrow">Premium map tools</span>
            <h2>Browse the map for free, unlock advanced navigation when needed</h2>
            <p>Search, filters, facility cards, and provider markers remain visible. Premium unlocks live location, satellite view, directions, and deeper nearby discovery.</p>
        </div>
        <button class="btn btn-primary" type="button" data-premium-locked="1">
            <i class="fa-solid fa-crown"></i>
            Unlock Map Tools
        </button>
    </section>
<?php endif; ?>

<section class="map-page-shell" data-aos="fade-up">
    <div class="map-page-sidebar glass-panel">
        <div class="map-discovery-hero">
            <div class="map-discovery-titlebar">
                <div>
                    <span class="map-live-badge"><i class="fa-solid fa-signal"></i> Live discovery</span>
                    <span class="eyebrow">Geo discovery</span>
                </div>
                <a href="<?= route_url('find-healthcare') ?>" class="btn btn-outline-primary btn-sm"><i class="fa-solid fa-list-ul"></i> Directory</a>
            </div>
            <div class="map-discovery-copy">
                <h1 class="h3 mb-1">Interactive Healthcare Map</h1>
                <p class="text-muted mb-0">Search hospitals, doctor clinics, pharmacies, and emergency support near you.</p>
            </div>
            <div class="map-discovery-trust">
                <span><i class="fa-solid fa-location-crosshairs"></i> Nearby scan</span>
                <span><i class="fa-solid fa-route"></i> Route ready</span>
                <span><i class="fa-solid fa-layer-group"></i> 4 layers</span>
            </div>
        </div>

        <div class="map-visual-strip mt-4">
            <div class="map-media-card">
                <img src="<?= e(provider_photo_url('hospital', 'map-hospital')) ?>" alt="Modern hospital facility" loading="lazy">
                <span>Hospitals</span>
            </div>
            <div class="map-media-card">
                <img src="<?= e(provider_photo_url('doctor', 'map-doctor')) ?>" alt="Doctor consultation" loading="lazy">
                <span>Doctors</span>
            </div>
        </div>

        <div class="map-control-group map-search-panel mt-4">
            <label class="form-label"><i class="fa-solid fa-magnifying-glass-location"></i> Search location</label>
            <div class="input-group map-search-input-group">
                <input type="text" class="form-control" id="mapSearchInput" placeholder="Search city, hospital, or landmark" value="<?= e($mapSearchValue) ?>">
                <button class="btn btn-primary" type="button" id="mapSearchBtn"><i class="fa-solid fa-magnifying-glass"></i></button>
            </div>
            <div class="map-search-feedback">
                <?php if ($activeSearchFilters): ?>
                    <div class="map-query-chips">
                        <?php foreach ($activeSearchFilters as $label => $value): ?>
                            <span><?= e($label) ?>: <?= e($value) ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="small text-muted">Nearby discovery opens all available care options on the map.</div>
                <?php endif; ?>
                <a href="<?= e($mapGoogleSearchUrl) ?>" class="btn btn-outline-primary btn-sm map-google-area-btn" target="_blank" rel="noopener" data-google-map-link>
                    <i class="fa-brands fa-google"></i>
                    Open this area in Google
                </a>
            </div>
        </div>

        <div class="map-action-row mt-3">
            <button class="btn btn-outline-primary" type="button" id="mapUseLocationBtn"<?= $premiumAttrs ?>><i class="fa-solid fa-location-crosshairs"></i> Use My Location</button>
            <button class="btn btn-outline-primary" type="button" id="mapResetViewBtn"><i class="fa-solid fa-rotate-right"></i> Reset View</button>
        </div>

        <div class="map-layer-block mt-4">
            <div class="map-section-label">
                <span>Care layers</span>
                <small>Tap to show or hide markers</small>
            </div>
            <div class="map-filter-pills" id="facilityFilterGroup">
                <button class="filter-pill active" type="button" data-filter-type="hospital"><i class="fa-solid fa-hospital"></i><span>Hospitals</span></button>
                <button class="filter-pill active" type="button" data-filter-type="doctor"><i class="fa-solid fa-user-doctor"></i><span>Doctor Clinics</span></button>
                <button class="filter-pill active" type="button" data-filter-type="pharmacy"><i class="fa-solid fa-capsules"></i><span>Pharmacies</span></button>
                <button class="filter-pill active" type="button" data-filter-type="emergency"><i class="fa-solid fa-truck-medical"></i><span>Emergency</span></button>
            </div>
        </div>

        <div class="map-stats-grid mt-4">
            <div class="mini-stat-box">
                <i class="fa-solid fa-hospital"></i>
                <span>Hospitals</span>
                <strong id="mapHospitalCount"><?= $hospitalMarkerCount ?></strong>
            </div>
            <div class="mini-stat-box">
                <i class="fa-solid fa-user-doctor"></i>
                <span>Doctors</span>
                <strong id="mapDoctorCount"><?= count($mapDoctors) ?></strong>
            </div>
            <div class="mini-stat-box">
                <i class="fa-solid fa-location-dot"></i>
                <span>Nearby places</span>
                <strong id="mapNearbyCount">0</strong>
            </div>
            <div class="mini-stat-box">
                <i class="fa-solid fa-route"></i>
                <span>Directions</span>
                <strong id="mapDirectionsState">Ready</strong>
            </div>
        </div>

        <div class="map-info-card mt-4" id="mapInfoCard">
            <div class="map-empty-icon"><i class="fa-solid fa-map-pin"></i></div>
            <div class="small text-muted text-uppercase fw-semibold mb-2">Selected facility</div>
            <h5 class="mb-1">No facility selected</h5>
            <p class="text-muted mb-2">Click a map marker or nearby item to inspect details.</p>
            <div class="map-info-meta small text-muted">Distance, address, and actions will appear here.</div>
        </div>

        <div class="map-nearby-panel mt-4">
            <div class="map-section-label">
                <span>Nearby results</span>
                <small>Live from map center</small>
            </div>
            <div class="nearby-results-list" id="nearbyResultsList">
                <div class="text-muted small">Opening nearby discovery and loading care markers...</div>
            </div>
        </div>

        <div class="map-directions-panel mt-4">
            <h6 class="mb-2"><i class="fa-solid fa-diamond-turn-right"></i> Directions</h6>
            <div class="small text-muted mb-2">Select any facility, then start navigation from your current or searched location.</div>
            <div class="d-grid gap-2">
                <button class="btn btn-primary" type="button" id="getDirectionsBtn"<?= $premiumAttrs ?> disabled><i class="fa-solid fa-route"></i> Get Directions</button>
                <button class="btn btn-outline-primary" type="button" id="clearDirectionsBtn" disabled>Clear Directions</button>
            </div>
        </div>
    </div>

    <div class="map-page-content">
        <div class="glass-panel map-stage-panel p-0 overflow-hidden">
            <div class="map-stage-toolbar">
                <div>
                    <strong>Full-screen Healthcare Explorer</strong>
                    <div class="small text-muted">Search, filter, inspect facilities, and get directions with the live map.</div>
                </div>
                <div class="map-stage-tools">
                    <div class="map-style-toggle" id="mapStyleControl" aria-label="Map style">
                        <button type="button" class="active" data-map-style="road" aria-pressed="true">
                            <i class="fa-solid fa-road"></i>
                            Road
                        </button>
                        <button type="button" data-map-style="satellite" aria-pressed="false"<?= $premiumAttrs ?>>
                            <i class="fa-solid fa-satellite"></i>
                            Satellite
                        </button>
                    </div>
                    <a href="<?= e($mapGoogleSearchUrl) ?>" class="btn btn-outline-primary btn-sm map-google-toolbar-btn" target="_blank" rel="noopener" data-google-map-link>
                        <i class="fa-brands fa-google"></i>
                        Google
                    </a>
                    <div class="map-legend">
                        <span><i class="fa-solid fa-hospital text-primary"></i> Hospitals</span>
                        <span><i class="fa-solid fa-user-doctor text-success"></i> Doctor clinics</span>
                        <span><i class="fa-solid fa-capsules text-warning"></i> Pharmacies</span>
                        <span><i class="fa-solid fa-truck-medical text-danger"></i> Emergency</span>
                    </div>
                </div>
            </div>
            <div id="healthcareExplorerMap"
                 class="healthcare-map-canvas fullscreen"
                 data-map-mode="explorer"
                 data-auto-nearby="<?= $autoNearby ? '1' : '0' ?>"
                 data-auto-open="<?= ($autoNearby || $mapFocusType !== '') ? '1' : '0' ?>"
                 data-premium-locked="<?= $subscriptionLocked ? '1' : '0' ?>"
                 data-focus-type="<?= e($mapFocusType) ?>"
                 data-facilities='<?= e(json_encode($facilityPayload)) ?>'
                 data-api-endpoint="<?= route_url('api/facilities') ?>">
            </div>
        </div>
    </div>
</section>
