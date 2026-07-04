<?php
$role = $role ?? '';
$isPatientDashboard = $role === 'patient';
$patientAccessLevel = $patientAccessLevel ?? 'free';
$patientAccessLabel = ucfirst((string) $patientAccessLevel);
$patientCanUseBasic = !empty($patientCanUseBasic);
$patientCanUsePremium = !empty($patientCanUsePremium);
$dashboardTitle = $isPatientDashboard
    ? 'Welcome back' . (!empty($profile['name']) ? ', ' . e($profile['name']) : '') . '.'
    : 'Welcome' . (!empty($profile['name']) ? ', ' . e($profile['name']) : '') . '.';
$dashboardSubtitle = $isPatientDashboard
    ? 'Your care command center for appointments, records, global disease intelligence, top hospitals, and premium clinical tools.'
    : 'Manage appointments, reports, analytics, communication, and operational workflows from one place.';
$profileMeta = $isPatientDashboard ? 'Access: ' . $patientAccessLabel : (string) ($authUser['email'] ?? '');
?>
<section class="dashboard-header glass-panel mb-4" data-aos="fade-up">
    <div class="row align-items-center g-3">
        <div class="col-lg-8">
            <span class="eyebrow text-capitalize"><?= e($role) ?> portal</span>
            <h1 class="mb-1"><?= e($dashboardTitle) ?></h1>
            <p class="text-muted mb-0"><?= e($dashboardSubtitle) ?></p>
        </div>
        <div class="col-lg-4 text-lg-end">
            <div class="profile-chip">
                <div class="avatar-lg"><?= strtoupper(substr($authUser['email'] ?? 'U', 0, 1)) ?></div>
                <div>
                    <div class="fw-semibold text-capitalize"><?= e($role) ?></div>
                    <div class="text-muted small"><?= e($profileMeta) ?></div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php if ($role === 'patient'): ?>
    <?php
    $today = date('Y-m-d');
    $upcomingAppointments = array_values(array_filter((array) ($appointments ?? []), static fn(array $item): bool => (string) ($item['date'] ?? '') >= $today));
    usort($upcomingAppointments, static function (array $a, array $b): int {
        return strcmp((string) ($a['date'] ?? '') . ' ' . (string) ($a['time'] ?? ''), (string) ($b['date'] ?? '') . ' ' . (string) ($b['time'] ?? ''));
    });
    $nextAppointment = $upcomingAppointments[0] ?? null;
    $scannerLocked = !$patientCanUsePremium;
    $consultationLocked = !$patientCanUsePremium;
    $subscription = $patientCurrentSubscription ?? null;
    $subscriptionExpiry = !empty($subscription['current_period_end']) ? date('M j, Y', strtotime((string) $subscription['current_period_end'])) : 'Not active';
    $clinicalRecordCount = (int) ($prescriptionCount ?? 0) + (int) ($clinicalDocumentCount ?? 0);
    $careStats = [
        ['label' => 'Upcoming visits', 'value' => (int) ($appointmentStats['upcoming'] ?? 0), 'icon' => 'fa-calendar-check', 'tone' => 'blue', 'meta' => 'Manage schedule', 'url' => route_url('appointments')],
        ['label' => 'Reports', 'value' => (int) ($reportsCount ?? 0), 'icon' => 'fa-file-waveform', 'tone' => 'teal', 'meta' => 'Medical files', 'url' => route_url('reports')],
        ['label' => 'Clinical records', 'value' => $clinicalRecordCount, 'icon' => 'fa-file-prescription', 'tone' => 'violet', 'meta' => 'Prescriptions and docs', 'url' => route_url('clinical-records')],
        ['label' => 'Unpaid invoices', 'value' => (int) ($billingStats['unpaid_invoices'] ?? 0), 'icon' => 'fa-receipt', 'tone' => 'amber', 'meta' => 'Review payments', 'url' => route_url('payments')],
    ];
    $quickActions = [
        ['label' => 'Appointments', 'text' => 'Book and manage visits.', 'icon' => 'fa-calendar-plus', 'url' => route_url('appointments', ['book' => '1']), 'locked' => false, 'badge' => 'Free'],
        ['label' => 'Reports', 'text' => 'Upload medical files.', 'icon' => 'fa-folder-open', 'url' => route_url('reports'), 'locked' => false, 'badge' => 'Free'],
        ['label' => 'Find Care', 'text' => 'Search doctors and hospitals.', 'icon' => 'fa-stethoscope', 'url' => route_url('find-healthcare'), 'locked' => false, 'badge' => 'Free'],
        ['label' => 'Disease Library', 'text' => 'Review conditions and pathogens.', 'icon' => 'fa-virus-covid', 'url' => route_url('diseases'), 'locked' => false, 'badge' => 'Free'],
        ['label' => 'Care Map', 'text' => 'Open live healthcare map.', 'icon' => 'fa-map-location-dot', 'url' => route_url('map'), 'locked' => false, 'badge' => 'Free'],
        ['label' => 'Payments', 'text' => 'Invoices and subscriptions.', 'icon' => 'fa-credit-card', 'url' => route_url('payments'), 'locked' => false, 'badge' => 'Free'],
        ['label' => 'Consultations', 'text' => 'Video and voice care rooms.', 'icon' => 'fa-video', 'url' => route_url('consultations'), 'locked' => $consultationLocked, 'badge' => $consultationLocked ? 'Pro' : 'Open'],
        ['label' => 'AI Scanner', 'text' => 'Analyze and save scan results.', 'icon' => 'fa-microscope', 'url' => route_url('scanner'), 'locked' => $scannerLocked, 'badge' => $scannerLocked ? 'Pro' : 'Open'],
    ];
    ?>

    <section class="patient-dashboard-shell patient-dashboard-minimal">
        <section class="patient-stat-grid" aria-label="Patient dashboard metrics">
            <?php foreach ($careStats as $stat): ?>
                <a href="<?= e($stat['url']) ?>" class="patient-stat-card tone-<?= e($stat['tone']) ?>" data-aos="fade-up">
                    <i class="fa-solid <?= e($stat['icon']) ?>"></i>
                    <div>
                        <span><?= e($stat['label']) ?></span>
                        <strong><?= e((string) $stat['value']) ?></strong>
                        <small><?= e($stat['meta']) ?></small>
                    </div>
                </a>
            <?php endforeach; ?>
        </section>

        <section class="patient-balanced-grid">
            <section class="patient-panel patient-actions-panel" data-aos="fade-up">
                <div class="patient-panel-head">
                    <div><span class="eyebrow">Core actions</span><h3>Patient essentials</h3></div>
                    <a href="<?= route_url('find-healthcare') ?>" class="btn btn-outline-primary btn-sm">Find Care</a>
                </div>
                <div class="patient-action-grid">
                    <?php foreach ($quickActions as $action): ?>
                        <a href="<?= e($action['url']) ?>" class="patient-action-tile <?= $action['locked'] ? 'is-locked' : '' ?>" <?= $action['locked'] ? 'data-premium-locked="1"' : '' ?>>
                            <i class="fa-solid <?= e($action['icon']) ?>"></i>
                            <div><strong><?= e($action['label']) ?></strong><p><?= e($action['text']) ?></p></div>
                            <span><?= e($action['badge']) ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="patient-panel patient-status-panel" data-aos="fade-up">
                <div class="patient-panel-head">
                    <div><span class="eyebrow">Status</span><h3>Care snapshot</h3></div>
                    <a href="<?= route_url('appointments') ?>" class="btn btn-outline-primary btn-sm">Manage</a>
                </div>
                <div class="patient-status-grid">
                    <div class="patient-next-appointment">
                        <div class="patient-mini-heading">
                            <i class="fa-solid fa-calendar-day"></i>
                            <span>Next appointment</span>
                        </div>
                    <?php if ($nextAppointment): ?>
                        <strong><?= e($nextAppointment['doctor_name'] ?? 'Appointment') ?></strong>
                        <p><?= e(date('M j, Y', strtotime((string) $nextAppointment['date']))) ?> at <?= e(substr((string) ($nextAppointment['time'] ?? ''), 0, 5)) ?></p>
                        <small><?= e($nextAppointment['hospital_name'] ?? $nextAppointment['specialization'] ?? 'Care provider') ?></small>
                    <?php else: ?>
                        <strong>No upcoming visit</strong>
                        <p>Book a specialist or explore verified hospitals to start your care plan.</p>
                        <a href="<?= route_url('appointments', ['book' => '1']) ?>" class="btn btn-primary btn-sm">Book now</a>
                    <?php endif; ?>
                    </div>

                    <div class="patient-access-summary">
                        <div class="patient-mini-heading">
                            <i class="fa-solid fa-shield-heart"></i>
                            <span>Access status</span>
                        </div>
                        <div class="patient-tier-line">
                            <span>Current tier</span>
                            <strong><?= e($patientAccessLabel) ?></strong>
                        </div>
                        <div class="patient-tier-line">
                            <span>Premium tools</span>
                            <strong><?= $patientCanUsePremium ? 'Unlocked' : 'Locked' ?></strong>
                        </div>
                        <div class="patient-tier-line">
                            <span>Expiry</span>
                            <strong><?= e($subscriptionExpiry) ?></strong>
                        </div>
                        <?php if (!$patientCanUsePremium): ?>
                            <a href="<?= route_url('payments/subscriptions', ['return_to' => route_url('dashboard')]) ?>" class="btn btn-primary btn-sm">Compare plans</a>
                        <?php else: ?>
                            <a href="<?= route_url('payments') ?>" class="btn btn-outline-primary btn-sm">View billing</a>
                        <?php endif; ?>
                    </div>
                </div>
            </section>
        </section>
    </section>
<?php elseif ($role === 'doctor'): ?>
    <div class="row g-4">
        <div class="col-md-6 col-xl-3"><div class="stat-card gradient-card"><span>Today's Appointments</span><h2><?= count($todayAppointments ?? []) ?></h2></div></div>
        <div class="col-md-6 col-xl-3"><div class="stat-card gradient-card"><span>Total Scheduled</span><h2><?= count($appointments ?? []) ?></h2></div></div>
        <div class="col-md-6 col-xl-3"><div class="stat-card gradient-card"><span>Affiliation Requests</span><h2><?= count($affiliationRequests ?? []) ?></h2><div class="small mt-2">Sessions <?= (int) ($consultationCount ?? 0) ?></div></div></div>
        <div class="col-md-6 col-xl-3"><div class="stat-card gradient-card"><span>Revenue</span><h2><?= e(config('app.currency', 'PKR')) ?> <?= number_format((float) ($paymentStats['revenue'] ?? 0), 0) ?></h2></div></div>
    </div>
    <div class="row g-4 mt-1">
        <div class="col-lg-7" data-aos="fade-up">
            <div class="glass-panel h-100">
                <h4>Today's appointments</h4>
                <div class="table-responsive mt-3">
                    <table class="table align-middle">
                        <thead><tr><th>Patient</th><th>Time</th><th>Status</th></tr></thead>
                        <tbody>
                        <?php foreach (($todayAppointments ?? []) as $appointment): ?>
                            <tr>
                                <td><?= e($appointment['patient_name']) ?></td>
                                <td><?= e($appointment['time']) ?></td>
                                <td><span class="badge text-bg-light text-capitalize"><?= e($appointment['status']) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-5" data-aos="fade-up">
            <div class="glass-panel h-100">
                <h4>Professional Snapshot</h4>
                <div class="metric-list mt-3">
                    <div><span>Specialization</span><strong><?= e($profile['specialization'] ?? '-') ?></strong></div>
                    <div><span>License Number</span><strong><?= e($profile['license_number'] ?? '-') ?></strong></div>
                    <div><span>Experience</span><strong><?= e((string) ($profile['experience'] ?? 0)) ?> years</strong></div>
                    <div><span>Verification</span><strong class="text-capitalize"><?= e($profile['verified_status'] ?? 'pending') ?></strong></div>
                    <div><span>Prescriptions</span><strong><?= (int) ($prescriptionCount ?? 0) ?></strong></div>
                    <div><span>Clinical Documents</span><strong><?= (int) ($clinicalDocumentCount ?? 0) ?></strong></div>
                </div>
            </div>
        </div>
    </div>
<?php elseif ($role === 'hospital'): ?>
    <div class="row g-4">
        <div class="col-md-6 col-xl-3"><div class="stat-card gradient-card"><span>Affiliated Doctors</span><h2><?= count($hospitalDoctors ?? []) ?></h2></div></div>
        <div class="col-md-6 col-xl-3"><div class="stat-card gradient-card"><span>Departments</span><h2><?= (int) ($hospitalStats['departments'] ?? 0) ?></h2></div></div>
        <div class="col-md-6 col-xl-3"><div class="stat-card gradient-card"><span>Inventory</span><h2><?= (int) ($hospitalStats['inventory'] ?? 0) ?></h2><div class="small mt-2">Maintenance due <?= (int) ($hospitalStats['maintenance_due'] ?? 0) ?></div></div></div>
        <div class="col-md-6 col-xl-3"><div class="stat-card gradient-card"><span>Pending Requests</span><h2><?= (int) ($hospitalStats['pending_requests'] ?? 0) ?></h2><div class="small mt-2 text-capitalize">Status <?= e($profile['verified_status'] ?? 'pending') ?></div></div></div>
    </div>
    <div class="row g-4 mt-1">
        <div class="col-lg-6"><div class="glass-panel h-100"><h4>Hospital overview</h4><p class="text-muted mt-3 mb-2"><?= e($profile['name'] ?? '') ?></p><p class="mb-1"><strong>Address:</strong> <?= e($profile['address'] ?? '') ?></p><p class="mb-1"><strong>Facilities:</strong> <?= e($profile['facilities'] ?? '') ?></p><p class="mb-1"><strong>Coordinates:</strong> <?= e($profile['coordinates'] ?? '') ?></p><p class="mb-0"><strong>Assignments:</strong> <?= (int) ($hospitalStats['assignments'] ?? 0) ?></p></div></div>
        <div class="col-lg-6"><div class="glass-panel h-100"><div class="d-flex justify-content-between align-items-center mb-3"><h4 class="mb-0">Doctor management</h4><a href="<?= route_url('hospital-management') ?>" class="btn btn-outline-primary btn-sm">Open hospital management</a></div><div class="list-group list-group-flush mt-3"><?php foreach (($hospitalDoctors ?? []) as $doctor): ?><div class="list-group-item bg-transparent px-0"><strong><?= e($doctor['name']) ?></strong><div class="small text-muted"><?= e($doctor['specialization']) ?></div></div><?php endforeach; ?><?php if (empty($hospitalDoctors)): ?><div class="text-muted">No affiliated doctors yet. Approved doctors and department assignments will appear here once hospital management is configured.</div><?php endif; ?></div></div></div>
    </div>
<?php elseif ($role === 'admin'): ?>
    <div class="row g-4">
        <div class="col-md-6 col-xl-3"><div class="stat-card gradient-card"><span>Patients</span><h2><?= (int) ($userCounts['patient'] ?? 0) ?></h2></div></div>
        <div class="col-md-6 col-xl-3"><div class="stat-card gradient-card"><span>Doctors</span><h2><?= (int) ($userCounts['doctor'] ?? 0) ?></h2></div></div>
        <div class="col-md-6 col-xl-3"><div class="stat-card gradient-card"><span>Hospitals</span><h2><?= (int) ($userCounts['hospital'] ?? 0) ?></h2></div></div>
        <div class="col-md-6 col-xl-3"><div class="stat-card gradient-card"><span>Revenue</span><h2><?= e(config('app.currency', 'PKR')) ?> <?= number_format((float) ($paymentStats['gross_revenue'] ?? 0), 0) ?></h2><div class="small mt-2">Refunds <?= (int) ($refundPendingCount ?? 0) ?> - Compliance <?= (int) ($complianceStats['pending_requests'] ?? 0) ?></div></div></div>
    </div>
    <div class="row g-4 mt-1">
        <div class="col-lg-8" data-aos="fade-up">
            <div class="glass-panel h-100">
                <div class="d-flex justify-content-between align-items-center mb-3"><h4 class="mb-0">Registration & appointment analytics</h4></div>
                <canvas id="adminAnalyticsChart" height="110" data-patients="<?= (int) ($userCounts['patient'] ?? 0) ?>" data-doctors="<?= (int) ($userCounts['doctor'] ?? 0) ?>" data-hospitals="<?= (int) ($userCounts['hospital'] ?? 0) ?>" data-appointments="<?= (int) ($appointmentStats['total'] ?? 0) ?>"></canvas>
            </div>
        </div>
        <div class="col-lg-4" data-aos="fade-up">
            <div class="glass-panel h-100">
                <h4>Verification queue</h4>
                <div class="metric-list mt-3">
                    <div><span>Pending doctors</span><strong><?= count($pendingDoctors ?? []) ?></strong></div>
                    <div><span>Pending hospitals</span><strong><?= count($pendingHospitals ?? []) ?></strong></div>
                    <div><span>Completed appointments</span><strong><?= (int) ($appointmentStats['completed'] ?? 0) ?></strong></div>
                    <div><span>Cancelled appointments</span><strong><?= (int) ($appointmentStats['cancelled'] ?? 0) ?></strong></div>
                </div>
                <div class="small text-muted mb-3">Compliance incidents open: <?= (int) ($complianceStats['open_incidents'] ?? 0) ?></div>
                <a href="<?= route_url('admin/verifications') ?>" class="btn btn-primary w-100 mt-2">Open verification center</a>
                <a href="<?= route_url('admin/compliance') ?>" class="btn btn-outline-primary w-100 mt-2">Open compliance center</a>
            </div>
        </div>
    </div>
<?php endif; ?>
