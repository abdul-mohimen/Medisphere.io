<?php
$role = (string) ($role ?? '');
$appointments = array_values((array) ($appointments ?? []));
$doctors = array_values((array) ($doctors ?? []));
$hospitals = array_values((array) ($hospitals ?? []));
$today = date('Y-m-d');
$requestedHospitalId = (int) ($_GET['hospital_id'] ?? 0);
$requestedDoctorId = (int) ($_GET['doctor_id'] ?? 0);
$statusCounts = ['pending' => 0, 'confirmed' => 0, 'completed' => 0, 'cancelled' => 0, 'rescheduled' => 0];
foreach ($appointments as $appointment) {
    $status = (string) ($appointment['status'] ?? 'pending');
    if (!array_key_exists($status, $statusCounts)) {
        $statusCounts[$status] = 0;
    }
    $statusCounts[$status]++;
}
$upcomingAppointments = array_values(array_filter($appointments, static function (array $appointment) use ($today): bool {
    return (string) ($appointment['date'] ?? '') >= $today && !in_array((string) ($appointment['status'] ?? ''), ['cancelled', 'completed'], true);
}));
$nextAppointment = $upcomingAppointments[0] ?? null;
$canBook = $role === 'patient';
$canUpdate = in_array($role, ['doctor', 'admin'], true);
$heroCopy = [
    'patient' => 'Book visits with verified care teams, then track requests as they move from pending review to confirmed consultation.',
    'doctor' => 'Review patient requests, confirm the right time, and keep diagnosis notes connected to each visit.',
    'hospital' => 'Monitor appointments routed through doctors linked to your hospital profile.',
    'admin' => 'Audit appointment activity across patients, doctors, and hospitals from one operational view.',
][$role] ?? 'Coordinate upcoming visits, symptoms, care teams, and consultation status from one place.';
$primaryPartyLabel = $role === 'doctor' ? 'Patient' : ($role === 'patient' ? 'Care team' : 'Patient / Care team');
?>
<section class="appointment-page-hero glass-panel mb-4" data-aos="fade-up">
    <div class="appointment-hero-copy">
        <span class="eyebrow">Appointment management</span>
        <h1>Appointments</h1>
        <p><?= e($heroCopy) ?></p>
        <?php if ($canBook): ?>
            <button type="button" class="btn btn-primary btn-lg" data-bs-toggle="modal" data-bs-target="#bookAppointmentModal" data-open-booking-modal>
                <i class="fa-solid fa-calendar-plus me-2"></i> Book Appointment
            </button>
        <?php endif; ?>
    </div>
    <div class="appointment-hero-panel" aria-label="Appointment booking flow">
        <span class="appointment-hero-pill"><i class="fa-solid fa-route"></i> Booking Flow</span>
        <div class="appointment-flow-list">
            <div><strong>1</strong><span>Patient selects a hospital and matching doctor.</span></div>
            <div><strong>2</strong><span>Request is saved as pending in patient and doctor appointments.</span></div>
            <div><strong>3</strong><span>Linked hospital and admin views can track the booking automatically.</span></div>
        </div>
    </div>
</section>

<section class="appointment-stat-grid mb-4" aria-label="Appointment summary">
    <div class="appointment-stat-card"><span>Total</span><strong><?= count($appointments) ?></strong><small>All visible bookings</small></div>
    <div class="appointment-stat-card"><span>Pending</span><strong><?= (int) ($statusCounts['pending'] ?? 0) ?></strong><small>Waiting for review</small></div>
    <div class="appointment-stat-card"><span>Confirmed</span><strong><?= (int) ($statusCounts['confirmed'] ?? 0) ?></strong><small>Ready for visit</small></div>
    <div class="appointment-stat-card"><span>Upcoming</span><strong><?= count($upcomingAppointments) ?></strong><small><?= $nextAppointment ? e(date('M j', strtotime((string) $nextAppointment['date']))) : 'No active visit' ?></small></div>
</section>

<section class="appointment-workflow-card glass-panel mb-4" data-aos="fade-up">
    <div>
        <span class="eyebrow">Where bookings go</span>
        <h3>After booking, the appointment is not lost.</h3>
        <p>It is stored in the appointments table, shown here for the patient, sent to the selected doctor's queue, counted in the linked hospital dashboard, and available to admins for oversight.</p>
    </div>
    <div class="appointment-workflow-grid">
        <div><i class="fa-solid fa-user"></i><strong>Patient</strong><span>Sees the request here and in dashboard upcoming visits.</span></div>
        <div><i class="fa-solid fa-user-doctor"></i><strong>Doctor</strong><span>Can confirm, complete, cancel, or add diagnosis notes.</span></div>
        <div><i class="fa-solid fa-hospital"></i><strong>Hospital</strong><span>Gets visibility when the doctor is linked with that hospital.</span></div>
    </div>
</section>

<div class="glass-panel appointments-table-card" data-aos="fade-up">
    <div class="appointments-table-head">
        <div>
            <span class="eyebrow">Schedule</span>
            <h4 class="mb-0">Current appointments</h4>
        </div>
        <?php if ($canBook): ?>
            <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#bookAppointmentModal" data-open-booking-modal>
                <i class="fa-solid fa-plus me-1"></i> New booking
            </button>
        <?php endif; ?>
    </div>
    <div class="table-responsive">
        <table class="table align-middle appointments-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th><?= e($primaryPartyLabel) ?></th>
                    <th>Date & Time</th>
                    <th>Status</th>
                    <th>Symptoms</th>
                    <th>Consultation</th>
                    <?php if ($canUpdate): ?><th>Action</th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($appointments as $appointment): ?>
                    <?php
                    $doctorName = (string) ($appointment['doctor_name'] ?? 'Doctor');
                    $patientName = (string) ($appointment['patient_name'] ?? 'Patient');
                    $hospitalName = (string) ($appointment['hospital_name'] ?? '');
                    $specialization = (string) ($appointment['specialization'] ?? '');
                    $timeLabel = substr((string) ($appointment['time'] ?? ''), 0, 5);
                    ?>
                    <tr>
                        <td><strong>#<?= (int) $appointment['id'] ?></strong></td>
                        <td>
                            <div class="appointment-provider-cell">
                                <?php if ($role === 'doctor'): ?>
                                    <strong><?= e($patientName) ?></strong>
                                    <span><?= e($appointment['phone'] ?? 'Patient phone not added') ?></span>
                                <?php elseif (in_array($role, ['hospital', 'admin'], true)): ?>
                                    <strong><?= e($patientName) ?></strong>
                                    <span>Dr. <?= e($doctorName) ?><?= $specialization !== '' ? ' - ' . e($specialization) : '' ?></span>
                                    <?php if ($hospitalName !== ''): ?><small><?= e($hospitalName) ?></small><?php endif; ?>
                                <?php else: ?>
                                    <strong>Dr. <?= e($doctorName) ?></strong>
                                    <span><?= e($specialization ?: 'General care') ?></span>
                                    <small><?= e($hospitalName ?: 'Independent / clinic booking') ?></small>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <div class="appointment-date-cell">
                                <strong><?= e(date('M j, Y', strtotime((string) $appointment['date']))) ?></strong>
                                <span><?= e($timeLabel) ?></span>
                            </div>
                        </td>
                        <td><span class="appointment-status-badge status-<?= e((string) $appointment['status']) ?>"><?= e(ucfirst((string) $appointment['status'])) ?></span></td>
                        <td class="appointment-symptoms-cell"><?= e($appointment['symptoms'] ?: 'No symptoms added') ?></td>
                        <td>
                            <?php if ($role === 'hospital'): ?>
                                <a href="<?= route_url('hospital-management') ?>" class="btn btn-outline-primary btn-sm">Hospital View</a>
                            <?php else: ?>
                                <a href="<?= route_url('consultations') ?>" class="btn btn-outline-primary btn-sm">Open Hub</a>
                            <?php endif; ?>
                        </td>
                        <?php if ($canUpdate): ?>
                            <td>
                                <form method="POST" action="<?= route_url('appointments/update') ?>" class="appointment-update-form">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="appointment_id" value="<?= (int) $appointment['id'] ?>">
                                    <select name="status" class="form-select form-select-sm">
                                        <?php foreach (['pending','confirmed','rescheduled','completed','cancelled'] as $status): ?>
                                            <option value="<?= e($status) ?>" <?= $appointment['status'] === $status ? 'selected' : '' ?>><?= e(ucfirst($status)) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <input type="text" name="diagnosis" class="form-control form-control-sm" placeholder="Diagnosis notes">
                                    <button class="btn btn-outline-primary btn-sm">Save</button>
                                </form>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$appointments): ?><tr><td colspan="<?= $canUpdate ? 7 : 6 ?>" class="text-center text-muted py-4">No appointments are scheduled yet. New bookings, symptom notes, and status updates will appear here as soon as they are created.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($canBook): ?>
<div class="modal fade appointment-modal" id="bookAppointmentModal" tabindex="-1" aria-labelledby="bookAppointmentModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content appointment-booking-modal">
            <div class="modal-header">
                <div>
                    <span class="eyebrow">New appointment</span>
                    <h5 class="modal-title" id="bookAppointmentModalTitle">Book Appointment</h5>
                </div>
                <button class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="<?= route_url('appointments/create') ?>" data-appointment-booking-form data-default-hospital-id="<?= $requestedHospitalId > 0 ? (int) $requestedHospitalId : '' ?>" data-default-doctor-id="<?= $requestedDoctorId > 0 ? (int) $requestedDoctorId : '' ?>">
                <div class="modal-body appointment-booking-form">
                    <?= csrf_field() ?>
                    <div class="appointment-booking-note">
                        <i class="fa-solid fa-circle-info"></i>
                        <span>Selecting a hospital filters the doctors linked with that hospital. New hospitals are loaded from the database automatically.</span>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Hospital</label>
                            <select name="hospital_id" class="form-select" data-appointment-hospital-select>
                                <option value="">All hospitals and independent doctors</option>
                                <?php foreach ($hospitals as $hospital): ?>
                                    <?php $hospitalLocation = trim(((string) ($hospital['city'] ?? '')) . ', ' . ((string) ($hospital['country'] ?? '')), ' ,'); ?>
                                    <option value="<?= (int) $hospital['id'] ?>">
                                        <?= e($hospital['name']) ?><?= $hospitalLocation !== '' ? ' - ' . e($hospitalLocation) : '' ?><?= !empty($hospital['verified_status']) ? ' (' . e($hospital['verified_status']) . ')' : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Doctor</label>
                            <select name="doctor_id" class="form-select" required data-appointment-doctor-select>
                                <option value="">Select doctor</option>
                                <?php foreach ($doctors as $doctor): ?>
                                    <?php
                                    $doctorHospitalId = (int) ($doctor['hospital_id'] ?? 0);
                                    $doctorHospitalName = (string) ($doctor['hospital_name'] ?? '');
                                    $doctorMeta = trim((string) ($doctor['specialization'] ?? 'General care'));
                                    if ($doctorHospitalName !== '') {
                                        $doctorMeta .= ' at ' . $doctorHospitalName;
                                    }
                                    ?>
                                    <option value="<?= (int) $doctor['user_id'] ?>" data-hospital-id="<?= $doctorHospitalId ?: '' ?>">
                                        <?= e($doctor['name']) ?> - <?= e($doctorMeta) ?> (<?= e(config('app.currency', 'PKR')) ?> <?= number_format((float) ($doctor['consultation_fee'] ?? 0)) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="appointment-doctor-empty" data-appointment-doctor-empty hidden>No doctors match this hospital selection.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Date</label>
                            <input type="date" name="date" class="form-control" min="<?= e($today) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Time</label>
                            <input type="time" name="time" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Symptoms</label>
                            <textarea name="symptoms" rows="4" class="form-control" placeholder="Describe symptoms, duration, urgency, or visit reason"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn btn-primary"><i class="fa-solid fa-paper-plane me-2"></i> Submit Request</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>
