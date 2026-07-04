<?php
$subscriptionLocked = !empty($subscriptionLocked);
$premiumAttrs = $subscriptionLocked ? ' data-premium-locked="1"' : '';
$bodyParts = [
    'general' => ['label' => 'General body', 'icon' => 'fa-user'],
    'skin' => ['label' => 'Skin', 'icon' => 'fa-hand-dots'],
    'eye' => ['label' => 'Eye', 'icon' => 'fa-eye'],
    'chest' => ['label' => 'Chest / breathing', 'icon' => 'fa-lungs'],
    'abdomen' => ['label' => 'Abdomen', 'icon' => 'fa-notes-medical'],
    'head_neuro' => ['label' => 'Head / nerves', 'icon' => 'fa-brain'],
    'bones_joints' => ['label' => 'Bones / joints', 'icon' => 'fa-bone'],
    'pregnancy' => ['label' => 'Pregnancy', 'icon' => 'fa-person-pregnant'],
    'child' => ['label' => 'Child health', 'icon' => 'fa-child'],
    'mental_health' => ['label' => 'Mental health', 'icon' => 'fa-brain'],
];
$consultationDoctors = $consultationDoctors ?? [];
$phoneMeta = static function (?string $phone): array {
    $raw = trim((string) $phone);
    if ($raw === '' || preg_match('/^not listed$/i', $raw)) {
        return ['raw' => '', 'tel' => '', 'whatsapp' => ''];
    }

    $tel = preg_replace('/[^\d+]/', '', $raw) ?: '';
    $digits = preg_replace('/\D+/', '', $raw) ?: '';
    return [
        'raw' => $raw,
        'tel' => strlen($digits) >= 7 ? 'tel:' . $tel : '',
        'whatsapp' => strlen($digits) >= 7 ? 'https://wa.me/' . $digits . '?text=' . rawurlencode('Hello doctor, I need a consultation after my AI scanner result. Please guide me.') : '',
    ];
};
?>
<section class="premium-scanner-hero mb-4" data-aos="fade-up">
    <div class="hero-bg-accent"></div>
    <div class="scanner-page-hero-copy">
        <span class="eyebrow"><i class="fa-solid fa-microchip"></i> Premium AI Assistant</span>
        <h1 class="mb-0">Medical AI Scanner</h1>
        <p class="text-muted mb-0 mt-2">Professional real-time camera triage, symptom analysis, intelligent specialist matching, and safe next steps in one clinical workspace.</p>
        <div class="scanner-hero-actions">
            <a href="#lens" class="btn btn-light btn-sm" data-bs-toggle="pill" data-bs-target="#lens" role="tab" aria-controls="lens">
                <i class="fa-solid fa-camera"></i>
                Open Lens
            </a>
            <a href="#analyzer" class="btn btn-outline-light btn-sm" data-bs-toggle="pill" data-bs-target="#analyzer" role="tab" aria-controls="analyzer">
                <i class="fa-solid fa-file-medical"></i>
                Analyze Report
            </a>
        </div>
    </div>
    <div class="scanner-page-hero-panel" aria-label="Scanner status">
        <div>
            <span>Engine</span>
            <strong>Gemini 3.1 Pro (High)</strong>
        </div>
        <div>
            <span>Care flow</span>
            <strong>Scan, report, consult</strong>
        </div>
        <div>
            <span>Safety</span>
            <strong>Doctor review advised</strong>
        </div>
    </div>
</section>

<?php if ($subscriptionLocked): ?>
    <section class="premium-inline-banner mb-4" data-aos="fade-up">
        <div>
            <span class="eyebrow">Premium scanner action</span>
            <h2>Image and symptom analysis need Premium</h2>
            <p>The scanner page stays visible. Subscribe when you are ready to analyze images, save scan history, and unlock specialist guidance.</p>
        </div>
        <button class="btn btn-primary" type="button" data-premium-locked="1">
            <i class="fa-solid fa-crown"></i>
            Unlock Scanner
        </button>
    </section>
<?php endif; ?>

<ul class="nav nav-pills nav-fill mb-4 scanner-module-tabs" id="scannerTab" role="tablist" data-aos="fade-up">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="lens-tab" data-bs-toggle="pill" data-bs-target="#lens" type="button" role="tab" aria-controls="lens" aria-selected="true" onclick="document.getElementById('scanTypeInput').value='image'">
            <i class="fa-solid fa-camera-retro"></i> Module 1: Real-time AI Lens Scanner
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="analyzer-tab" data-bs-toggle="pill" data-bs-target="#analyzer" type="button" role="tab" aria-controls="analyzer" aria-selected="false" onclick="document.getElementById('scanTypeInput').value='symptom'">
            <i class="fa-solid fa-notes-medical"></i> Module 2: Document & Image Analyzer
        </button>
    </li>
</ul>

<form id="scanForm" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?= e(App\Core\CSRF::token()) ?>">
    <input type="hidden" name="scan_type" id="scanTypeInput" value="image">

    <div class="scanner-dashboard-wrapper">
    <div class="tab-content" id="scannerTabContent">
        <div class="tab-pane fade show active" id="lens" role="tabpanel" aria-labelledby="lens-tab">
            <section class="glass-panel scanner-lens-console" data-aos="fade-up">
                <div class="scanner-console-head">
                    <div>
                        <span class="eyebrow">Module 1</span>
                        <h2>Real-time AI Lens Scanner</h2>
                        <p class="text-muted mb-0 mt-1">Use your camera or upload a clear image, then lock the frame for AI triage.</p>
                    </div>
                    <span id="scannerUsageCounter" class="scanner-usage-counter">24/hour limit</span>
                </div>

                <div class="scanner-lens-grid">
                    <div class="scanner-live-visual scanner-camera-visual" id="scannerVisualizer" data-active-part="general" aria-live="polite">
                        <div class="scanner-camera-lab" id="scannerCameraLab">
                            <div class="scanner-stage scanner-camera-stage">
                                <video id="scannerCameraPreview" autoplay muted playsinline></video>
                                <img id="scannerFrozenFrame" class="scanner-frozen-frame" alt="Locked scan frame" hidden>
                                <div class="scanner-camera-placeholder" id="scannerCameraPlaceholder">
                                    <i class="fa-solid fa-camera"></i>
                                    <span>Start camera or upload an image to begin the lens scan.</span>
                                </div>
                                <div class="scanner-camera-grid" aria-hidden="true"></div>
                                <div class="scanner-camera-lock" aria-hidden="true">
                                    <span></span><span></span><span></span><span></span>
                                </div>
                                <div class="scanner-analyzing-overlay" id="scannerAnalyzingOverlay" hidden>
                                    <span class="scanner-loading-spinner"></span>
                                    <strong>Locking on and analyzing...</strong>
                                </div>
                            </div>
                            <canvas id="scannerCameraCanvas" hidden></canvas>

                            <div class="scanner-camera-footer">
                                <span id="scannerCameraStatus"><i class="fa-solid fa-circle"></i> Camera idle</span>
                                <div class="scanner-camera-actions">
                                    <button class="btn btn-outline-secondary btn-sm" type="button" id="startCameraBtn"<?= $premiumAttrs ?>>
                                        <i class="fa-solid fa-play"></i> Start
                                    </button>
                                    <button class="btn btn-outline-primary btn-sm" type="button" id="lockFrameBtn" disabled<?= $premiumAttrs ?>>
                                        <i class="fa-solid fa-crosshairs"></i> Lock
                                    </button>
                                    <button class="btn btn-primary btn-sm" type="button" id="runScanBtn"<?= $premiumAttrs ?>>
                                        <i class="fa-solid fa-wand-magic-sparkles"></i> Analyze
                                    </button>
                                    <button class="btn btn-outline-danger btn-sm" type="button" id="stopCameraBtn" hidden>
                                        <i class="fa-solid fa-video-slash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <aside class="scanner-lens-side">
                        <div class="scanner-side-card">
                            <div class="scanner-side-card-head">
                                <span class="scanner-side-icon"><i class="fa-solid fa-crosshairs"></i></span>
                                <div>
                                    <span class="eyebrow">Target area</span>
                                    <h3>Select scan focus</h3>
                                </div>
                            </div>
                            <select name="body_part" id="bodyPartSelect" class="form-select">
                                <?php foreach ($bodyParts as $value => $item): ?>
                                    <option value="<?= e($value) ?>"><?= e($item['label']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="scanner-body-chip-row mt-3" aria-label="Quick body area selection">
                                <?php foreach ($bodyParts as $value => $item): ?>
                                    <button type="button" class="scanner-hotspot <?= $value === 'general' ? 'is-active' : '' ?>" data-body-part-hotspot="<?= e($value) ?>">
                                        <i class="fa-solid <?= e($item['icon']) ?>"></i>
                                        <span><?= e($item['label']) ?></span>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="scanner-side-card">
                            <div id="scannerVisualStatus" class="scanner-visual-status">
                                <span class="status-dot"></span>
                                <div>
                                    <strong>Ready to scan</strong>
                                    <small>Choose a target area, then start camera or upload a photo.</small>
                                </div>
                            </div>
                            <div id="scannerLockLabel" class="scanner-visual-chip mt-3"><i class="fa-solid fa-crosshairs"></i> General body</div>
                        </div>

                        <!-- Module 1 Upload UI Removed per design requirement; file input hidden for fallback only -->
                        <div class="scanner-dropzone d-none" id="scannerDropzone">
                            <input type="file" name="scan_image" id="scanImageInput" accept="image/jpeg,image/png,image/webp" class="d-none">
                            <div id="scanPreviewWrap" class="preview-panel d-none">
                                <img id="scanPreview" class="rounded-3" alt="Selected scan preview">
                                <button type="button" class="btn btn-outline-danger btn-sm mt-2" id="clearScanImageBtn">
                                    <i class="fa-solid fa-xmark"></i>
                                </button>
                            </div>
                        </div>
                    </aside>
                </div>
            </section>
        </div>

        <div class="tab-pane fade" id="analyzer" role="tabpanel" aria-labelledby="analyzer-tab">
            <section class="glass-panel scanner-analyzer-console" data-aos="fade-up">
                <div class="scanner-console-head">
                    <div>
                        <span class="eyebrow">Module 2</span>
                        <h2>Symptoms & Image Analyzer</h2>
                        <p class="text-muted mb-0 mt-1">Write symptoms, add a medical image if available, then generate a structured care report.</p>
                    </div>
                    <span class="scanner-mode-pill scanner-module-pill"><i class="fa-solid fa-bolt"></i> AI report</span>
                </div>

                <div class="scanner-analyzer-grid">
                    <div class="scanner-analyzer-card">
                        <label class="scanner-field">
                            <span><i class="fa-solid fa-notes-medical text-primary"></i> Describe symptoms</span>
                            <textarea name="symptoms" id="mod2SymptomInput" class="form-control" rows="5" maxlength="1800" placeholder="Example: abdominal pain, nausea, fever, rash, cough, or report concern..."></textarea>
                        </label>

                        <label class="scanner-field">
                            <span><i class="fa-solid fa-child-reaching text-primary"></i> Body area affected</span>
                            <select name="body_part" id="mod2BodyPartSelect" class="form-select">
                                <?php foreach ($bodyParts as $value => $item): ?>
                                    <option value="<?= e($value) ?>"><?= e($item['label']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>

                        <div class="scanner-mod2-upload" onclick="document.getElementById('mod2FileInput').click()">
                            <div id="mod2UploadState">
                                <i class="fa-solid fa-cloud-arrow-up"></i>
                                <strong>Upload related image</strong>
                                <span>Skin photo, eye image, prescription photo, or report snapshot.</span>
                            </div>
                            <input type="file" name="scan_image" id="mod2FileInput" accept="image/jpeg,image/png,image/webp" class="d-none">
                            <div id="mod2PreviewState" class="d-none scanner-mod2-preview">
                                <img id="mod2ImagePreview" alt="Selected analyzer preview">
                                <button type="button" class="btn btn-danger btn-sm" id="mod2ClearBtn"><i class="fa-solid fa-xmark"></i></button>
                            </div>
                        </div>
                    </div>

                    <div class="scanner-analyzer-card scanner-analyzer-action-card">
                        <div class="scanner-analysis-steps">
                            <div><i class="fa-solid fa-keyboard"></i><span>Symptoms are checked for red flags.</span></div>
                            <div><i class="fa-solid fa-image"></i><span>Image evidence is included when uploaded.</span></div>
                            <div><i class="fa-solid fa-user-doctor"></i><span>Doctor and hospital suggestions are matched to the result.</span></div>
                        </div>

                        <button class="btn btn-primary btn-lg w-100" type="button" id="mod2AnalyzeBtn"<?= $premiumAttrs ?>>
                            <i class="fa-solid fa-wand-magic-sparkles"></i> Generate AI Report
                        </button>

                        <?php if ($subscriptionLocked): ?>
                            <div class="scanner-lock-note"><i class="fa-solid fa-lock"></i> Premium subscription required to run analysis.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </section>
        </div>
    </div>
</form>

<section class="mt-4 scanner-report-shell d-none" id="googleResultsContainer" data-aos="fade-up" data-result-module="">
    <div class="glass-panel scanner-report-panel">
        <div class="scanner-report-header">
            <div>
                <span id="googleResultsModule" class="eyebrow">AI analysis</span>
                <h2 id="googleResultsTitle" class="mb-0"><i class="fa-solid fa-stethoscope"></i> AI Care Analysis</h2>
                <p id="googleResultsSubtitle" class="mb-0 mt-1">Structured report with care suggestions and consultation options.</p>
            </div>
            <span class="scanner-report-safe"><i class="fa-solid fa-shield-heart"></i> Doctor review required</span>
        </div>
        
        <div class="scanner-report-body" id="scanResult"></div>

        <div class="scanner-suggestion-band">
            <div class="scanner-suggestion-head">
                <div>
                    <span class="eyebrow">Next care options</span>
                    <h3>Doctor and hospital suggestions</h3>
                </div>
                <a href="<?= route_url('find-healthcare') ?>" class="btn btn-outline-primary btn-sm"><i class="fa-solid fa-stethoscope"></i> Find healthcare</a>
            </div>
            <div id="allAroundSuggestions" class="scanner-suggestion-grid"></div>
        </div>

        <div class="scanner-critical-disclaimer scanner-report-disclaimer">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <span id="footerDisclaimer">Important: This is an AI assessment. Please consult a concerned professional medical doctor for actual diagnosis.</span>
        </div>
        
        <div class="scanner-report-actions">
            <button class="btn btn-outline-primary" type="button" id="saveScanBtn"<?= $premiumAttrs ?> <?= $subscriptionLocked ? '' : 'disabled' ?>>
                <i class="fa-solid fa-floppy-disk"></i> Save Result
            </button>
        </div>
    </div>
</section>
</div> <!-- Close scanner-dashboard-wrapper -->

<section class="scanner-history-section glass-panel mt-5" data-aos="fade-up">
    <div class="scanner-console-head">
        <div>
            <span class="eyebrow">Saved trail</span>
            <h2>Recent analyses</h2>
        </div>
        <a href="<?= route_url('find-healthcare') ?>" class="btn btn-outline-primary btn-sm">Find doctors</a>
    </div>
    <div class="scanner-history-grid">
        <?php foreach ($recentScans as $scan): ?>
            <article class="scanner-history-card">
                <div class="small text-muted mb-2"><?= e($scan['timestamp']) ?></div>
                <div class="scanner-history-meta">
                    <span><?= e(ucfirst((string) ($scan['scan_type'] ?? 'image'))) ?></span>
                    <span><?= e(str_replace('_', ' ', (string) ($scan['body_part'] ?? 'general'))) ?></span>
                </div>
                <h3><?= e($scan['ai_result']) ?></h3>
                <p>Confidence: <?= e((string) $scan['confidence_score']) ?>%</p>
                <?php if (!empty($scan['specialist_recommendation'])): ?>
                    <small><?= e($scan['specialist_recommendation']) ?></small>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
        <?php if (!$recentScans): ?>
            <div class="scanner-empty-history">No scan history yet. Analyze an image or symptoms and save the result to build a review trail for future doctor visits.</div>
        <?php endif; ?>
    </div>
</section>
