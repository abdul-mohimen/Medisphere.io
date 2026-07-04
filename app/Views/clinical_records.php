<?php
$role = (string) ($role ?? '');
$recordTotal = count((array) ($prescriptions ?? [])) + count((array) ($documents ?? []));
$appointmentTotal = count((array) ($appointments ?? []));
$doctorAppointments = array_values((array) ($appointments ?? []));
$hasDoctorAppointments = !empty($doctorAppointments);
?>
<section class="clinical-records-hero glass-panel mb-4" data-aos="fade-up">
    <div class="clinical-hero-copy">
        <span class="eyebrow">Clinical workflow</span>
        <h1>Clinical Records</h1>
        <p><?= $role === 'doctor' ? 'Generate prescriptions, treatment plans, certificates, and discharge summaries with signed, verifiable printable layouts.' : 'Review doctor-issued prescriptions, certificates, treatment plans, discharge summaries, and follow-up notes.' ?></p>
    </div>
    <div class="clinical-hero-panel">
        <span class="clinical-hero-pill"><i class="fa-solid fa-file-shield"></i> Verification Ready</span>
        <div class="clinical-hero-metrics">
            <div><strong><?= $recordTotal ?></strong><span>Total records</span></div>
            <div><strong><?= count((array) ($prescriptions ?? [])) ?></strong><span>Prescriptions</span></div>
            <div><strong><?= $role === 'doctor' ? $appointmentTotal : count((array) ($documents ?? [])) ?></strong><span><?= $role === 'doctor' ? 'Appointments' : 'Documents' ?></span></div>
        </div>
    </div>
</section>

<?php if ($role === 'doctor'): ?>
    <div class="row g-4 mb-4">
        <div class="col-md-3"><div class="stat-card gradient-card"><span>Total Prescriptions</span><h2><?= count($prescriptions ?? []) ?></h2></div></div>
        <div class="col-md-3"><div class="stat-card gradient-card"><span>Clinical Documents</span><h2><?= count($documents ?? []) ?></h2></div></div>
        <div class="col-md-3"><div class="stat-card gradient-card"><span>Managed Appointments</span><h2><?= count($appointments ?? []) ?></h2><div class="small mt-2">License <?= e($doctorProfile['license_number'] ?? 'Pending') ?></div></div></div>
        <div class="col-md-3"><div class="stat-card gradient-card"><span>Signature Profile</span><h2><?= !empty($signatureProfile['signature_image_path']) ? 'Ready' : 'Setup' ?></h2><div class="small mt-2">Verification-ready documents</div></div></div>
    </div>

    <div class="ux-stepper" data-stepper>
        <div class="ux-stepper-nav" role="tablist" aria-label="Clinical record sections">
            <button class="ux-stepper-tab is-active" data-stepper-tab data-stepper-target="clinical-step-signature">
                <span class="ux-stepper-number">1</span>
                <span class="ux-stepper-label"><strong>Signature</strong><span>Doctor seal setup</span></span>
            </button>
            <button class="ux-stepper-tab" data-stepper-tab data-stepper-target="clinical-step-create">
                <span class="ux-stepper-number">2</span>
                <span class="ux-stepper-label"><strong>Create</strong><span>Prescription and document</span></span>
            </button>
            <button class="ux-stepper-tab" data-stepper-tab data-stepper-target="clinical-step-history">
                <span class="ux-stepper-number">3</span>
                <span class="ux-stepper-label"><strong>History</strong><span>Recent signed records</span></span>
            </button>
        </div>
        <div class="ux-stepper-panels">
            <section class="ux-stepper-panel is-active" id="clinical-step-signature" data-stepper-panel>
                <div class="glass-panel mb-4" data-aos="fade-up">
                    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                        <div>
                            <h4 class="mb-1">Digital Signature Profile</h4>
                            <div class="small text-muted">Save a reusable doctor signature and seal for prescriptions and documents.</div>
                        </div>
                        <?php if (!empty($signatureProfile['signature_image_path'])): ?>
                            <div class="signature-preview-chip">
                                <img src="<?= app_url($signatureProfile['signature_image_path']) ?>" alt="Doctor signature preview">
                                <div>
                                    <div class="fw-semibold"><?= e($signatureProfile['signature_name'] ?? ($doctorProfile['name'] ?? 'Doctor')) ?></div>
                                    <div class="small text-muted"><?= e($signatureProfile['stamp_text'] ?? 'Clinical signature profile') ?></div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    <form method="POST" action="<?= route_url('clinical-records/signature/save') ?>" class="row g-3" id="signatureProfileForm">
                        <?= csrf_field() ?>
                        <div class="col-md-6">
                            <label class="form-label">Signature Name</label>
                            <input type="text" name="signature_name" class="form-control" value="<?= e($signatureProfile['signature_name'] ?? ($doctorProfile['name'] ?? '')) ?>" placeholder="Dr. John Doe" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Stamp / Seal Text</label>
                            <input type="text" name="stamp_text" class="form-control" value="<?= e($signatureProfile['stamp_text'] ?? ($doctorProfile['license_number'] ?? '')) ?>" placeholder="PMC Verified / License Number">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Draw Signature</label>
                            <div class="signature-pad-shell">
                                <canvas id="doctorSignatureCanvas" class="signature-pad-canvas" width="900" height="220"></canvas>
                                <input type="hidden" name="signature_data_url" id="doctorSignatureData">
                            </div>
                            <div class="d-flex gap-2 mt-2 flex-wrap">
                                <button type="button" class="btn btn-outline-primary btn-sm" id="clearSignaturePadBtn">Clear Signature</button>
                                <span class="small text-muted align-self-center">Saved signature image will be attached to future prescriptions and certificates.</span>
                            </div>
                        </div>
                        <div class="col-12 d-grid"><button class="btn btn-primary">Save Signature Profile</button></div>
                    </form>
                </div>
            </section>

            <section class="ux-stepper-panel" id="clinical-step-create" data-stepper-panel>
                <div class="clinical-create-intro <?= $hasDoctorAppointments ? '' : 'is-warning' ?>" data-aos="fade-up">
                    <div class="clinical-create-intro-icon">
                        <i class="fa-solid <?= $hasDoctorAppointments ? 'fa-file-circle-plus' : 'fa-calendar-xmark' ?>"></i>
                    </div>
                    <div>
                        <h4><?= $hasDoctorAppointments ? 'Create appointment-linked clinical records' : 'Appointment required before generating records' ?></h4>
                        <p><?= $hasDoctorAppointments ? 'Choose an appointment first so the patient, doctor, visit date, signature, verification code, and audit trail are connected automatically.' : 'Prescriptions and certificates need a booked appointment. Once an appointment exists, these forms will create signed records and notify the patient.' ?></p>
                    </div>
                    <a href="<?= route_url('appointments') ?>" class="btn btn-outline-primary"><i class="fa-solid fa-calendar-check"></i> Open Appointments</a>
                </div>

                <div class="clinical-builder-grid">
                    <article class="clinical-form-card" data-aos="fade-up">
                        <div class="clinical-form-card-head">
                            <div>
                                <span class="clinical-card-kicker">Medication plan</span>
                                <h4>Create Prescription</h4>
                                <p>One medication or instruction per line. The saved prescription becomes printable, signed, and verification-ready.</p>
                            </div>
                            <span class="clinical-card-icon"><i class="fa-solid fa-prescription-bottle-medical"></i></span>
                        </div>

                        <form method="POST" action="<?= route_url('clinical-records/prescription/save') ?>" class="row g-3 clinical-record-form" data-clinical-form="prescription">
                            <?= csrf_field() ?>
                            <div class="col-12">
                                <div class="clinical-template-row" aria-label="Prescription starters">
                                    <span>Start with</span>
                                    <button type="button" class="clinical-template-btn" data-prescription-template="consultation">Consultation</button>
                                    <button type="button" class="clinical-template-btn" data-prescription-template="followup">Follow-up</button>
                                    <button type="button" class="clinical-template-btn" data-prescription-template="labreview">Lab review</button>
                                </div>
                            </div>
                            <div class="col-md-7">
                                <label class="form-label">Appointment</label>
                                <select name="appointment_id" class="form-select" required <?= !$hasDoctorAppointments ? 'disabled' : '' ?>>
                                    <option value=""><?= $hasDoctorAppointments ? 'Select appointment' : 'No appointment available' ?></option>
                                    <?php foreach ($doctorAppointments as $appointment): ?>
                                        <option
                                            value="<?= (int) $appointment['id'] ?>"
                                            data-patient-name="<?= e($appointment['patient_name'] ?? 'Patient') ?>"
                                            data-appointment-date="<?= e($appointment['date'] ?? '') ?>"
                                            data-appointment-time="<?= e($appointment['time'] ?? '') ?>"
                                        >#<?= (int) $appointment['id'] ?> - <?= e($appointment['patient_name'] ?? 'Patient') ?> (<?= e($appointment['date'] ?? '') ?> <?= e($appointment['time'] ?? '') ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label">Title</label>
                                <input type="text" name="title" class="form-control" value="Prescription" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Diagnosis / Clinical Impression</label>
                                <input type="text" name="diagnosis" class="form-control" placeholder="Primary diagnosis, complaint, or clinical impression" data-prescription-diagnosis>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Medication Details</label>
                                <textarea name="medications" class="form-control clinical-textarea-tall" rows="7" placeholder="Medication name - strength - route - frequency - duration&#10;Investigation / instruction - timing - notes" required data-prescription-medications></textarea>
                                <div class="form-text">Each line is saved as a separate prescription item in the printable record.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Clinical Notes</label>
                                <textarea name="notes" class="form-control" rows="4" placeholder="History, examination findings, allergies, precautions" data-prescription-notes></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Advice / Lifestyle</label>
                                <textarea name="advice" class="form-control" rows="4" placeholder="Diet, rest, warning signs, follow-up advice" data-prescription-advice></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Follow-up Date</label>
                                <input type="date" name="follow_up_date" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <option value="finalized">Finalized</option>
                                    <option value="draft">Draft</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <div class="clinical-verification-strip">
                                    <span><i class="fa-solid fa-user-check"></i> Patient linked</span>
                                    <span><i class="fa-solid fa-signature"></i> Doctor signature</span>
                                    <span><i class="fa-solid fa-shield-halved"></i> Verification code</span>
                                </div>
                            </div>
                            <div class="col-12 d-grid">
                                <button class="btn btn-primary btn-lg" <?= !$hasDoctorAppointments ? 'disabled' : '' ?>><i class="fa-solid fa-wand-magic-sparkles"></i> Generate Prescription</button>
                            </div>
                        </form>
                    </article>

                    <article class="clinical-form-card" data-aos="fade-up">
                        <div class="clinical-form-card-head">
                            <div>
                                <span class="clinical-card-kicker">Certificates and summaries</span>
                                <h4>Create Clinical Document</h4>
                                <p>Generate medical certificates, treatment plans, discharge summaries, and follow-up notes from the same signed workflow.</p>
                            </div>
                            <span class="clinical-card-icon"><i class="fa-solid fa-file-signature"></i></span>
                        </div>

                        <form method="POST" action="<?= route_url('clinical-records/document/save') ?>" class="row g-3 clinical-record-form" data-clinical-form="document">
                            <?= csrf_field() ?>
                            <div class="col-12">
                                <div class="clinical-template-row" aria-label="Document starters">
                                    <span>Templates</span>
                                    <button type="button" class="clinical-template-btn" data-document-template="medical_certificate">Certificate</button>
                                    <button type="button" class="clinical-template-btn" data-document-template="treatment_plan">Plan</button>
                                    <button type="button" class="clinical-template-btn" data-document-template="discharge_summary">Discharge</button>
                                    <button type="button" class="clinical-template-btn" data-document-template="follow_up_note">Follow-up</button>
                                </div>
                            </div>
                            <div class="col-md-7">
                                <label class="form-label">Appointment</label>
                                <select name="appointment_id" class="form-select" required <?= !$hasDoctorAppointments ? 'disabled' : '' ?>>
                                    <option value=""><?= $hasDoctorAppointments ? 'Select appointment' : 'No appointment available' ?></option>
                                    <?php foreach ($doctorAppointments as $appointment): ?>
                                        <option
                                            value="<?= (int) $appointment['id'] ?>"
                                            data-patient-name="<?= e($appointment['patient_name'] ?? 'Patient') ?>"
                                            data-appointment-date="<?= e($appointment['date'] ?? '') ?>"
                                            data-appointment-time="<?= e($appointment['time'] ?? '') ?>"
                                        >#<?= (int) $appointment['id'] ?> - <?= e($appointment['patient_name'] ?? 'Patient') ?> (<?= e($appointment['date'] ?? '') ?> <?= e($appointment['time'] ?? '') ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label">Document Type</label>
                                <select name="document_type" class="form-select" data-document-type-select>
                                    <option value="medical_certificate">Medical Certificate</option>
                                    <option value="discharge_summary">Discharge Summary</option>
                                    <option value="treatment_plan">Treatment Plan</option>
                                    <option value="follow_up_note">Follow-up Note</option>
                                </select>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label">Title</label>
                                <input type="text" name="title" class="form-control" placeholder="Document title" required data-document-title>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Issue Date</label>
                                <input type="date" name="issue_date" class="form-control" value="<?= date('Y-m-d') ?>">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Summary</label>
                                <input type="text" name="summary" class="form-control" placeholder="Short overview or one-line note" data-document-summary>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Document Content</label>
                                <textarea name="content" class="form-control clinical-textarea-tall" rows="11" placeholder="Write the full medical certificate, treatment plan, discharge summary, or follow-up note here." required data-document-content></textarea>
                                <div class="form-text">The final document is stored with a verification code, integrity hash, and audit trail.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <option value="finalized">Finalized</option>
                                    <option value="draft">Draft</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <div class="clinical-verification-mini">
                                    <strong>Secured on save</strong>
                                    <span>Signature snapshot, patient notification, and verification link are generated automatically.</span>
                                </div>
                            </div>
                            <div class="col-12 d-grid">
                                <button class="btn btn-primary btn-lg" <?= !$hasDoctorAppointments ? 'disabled' : '' ?>><i class="fa-solid fa-file-circle-check"></i> Generate Document</button>
                            </div>
                        </form>
                    </article>
                </div>
            </section>

            <section class="ux-stepper-panel" id="clinical-step-history" data-stepper-panel>
                <div class="row g-4 mt-1">
                    <div class="col-xl-6" data-aos="fade-up">
                        <div class="glass-panel h-100">
                            <h4 class="mb-3">Recent Prescriptions</h4>
                            <div class="table-responsive">
                                <table class="table align-middle">
                                    <thead><tr><th>Patient</th><th>Diagnosis</th><th>Status</th><th>Verify</th><th>Action</th></tr></thead>
                                    <tbody>
                                    <?php foreach (($prescriptions ?? []) as $prescription): ?>
                                        <tr>
                                            <td><?= e($prescription['patient_name']) ?><div class="small text-muted"><?= e($prescription['created_at']) ?></div></td>
                                            <td><?= e($prescription['diagnosis'] ?: '-') ?></td>
                                            <td><span class="badge text-bg-light text-capitalize"><?= e($prescription['status']) ?></span></td>
                                            <td><code><?= e($prescription['verification_code'] ?? 'Pending') ?></code></td>
                                            <td><a class="btn btn-outline-primary btn-sm" href="<?= route_url('clinical-records/prescription', ['id' => (int) $prescription['id']]) ?>">Open</a></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($prescriptions)): ?><tr><td colspan="5" class="text-center text-muted py-4">No prescriptions generated yet. Create a signed prescription after a consultation to add it to this verification-ready list.</td></tr><?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-6" data-aos="fade-up">
                        <div class="glass-panel h-100">
                            <h4 class="mb-3">Recent Clinical Documents</h4>
                            <div class="table-responsive">
                                <table class="table align-middle">
                                    <thead><tr><th>Patient</th><th>Type</th><th>Status</th><th>Verify</th><th>Action</th></tr></thead>
                                    <tbody>
                                    <?php foreach (($documents ?? []) as $document): ?>
                                        <tr>
                                            <td><?= e($document['patient_name']) ?><div class="small text-muted"><?= e($document['created_at']) ?></div></td>
                                            <td class="text-capitalize"><?= e(str_replace('_', ' ', $document['document_type'])) ?></td>
                                            <td><span class="badge text-bg-light text-capitalize"><?= e($document['status']) ?></span></td>
                                            <td><code><?= e($document['verification_code'] ?? 'Pending') ?></code></td>
                                            <td><a class="btn btn-outline-primary btn-sm" href="<?= route_url('clinical-records/document', ['id' => (int) $document['id']]) ?>">Open</a></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($documents)): ?><tr><td colspan="5" class="text-center text-muted py-4">No clinical documents generated yet. Certificates, treatment plans, discharge summaries, and follow-up notes will appear here.</td></tr><?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>

<?php else: ?>
    <div class="row g-4 mb-4">
        <div class="col-md-6"><div class="stat-card gradient-card"><span>My Prescriptions</span><h2><?= count($prescriptions ?? []) ?></h2></div></div>
        <div class="col-md-6"><div class="stat-card gradient-card"><span>Clinical Documents</span><h2><?= count($documents ?? []) ?></h2></div></div>
    </div>

    <div class="ux-stepper" data-stepper>
        <div class="ux-stepper-nav" role="tablist" aria-label="Patient clinical record sections">
            <button class="ux-stepper-tab is-active" data-stepper-tab data-stepper-target="patient-clinical-step-prescriptions">
                <span class="ux-stepper-number">1</span>
                <span class="ux-stepper-label"><strong>Prescriptions</strong><span>Medication plans</span></span>
            </button>
            <button class="ux-stepper-tab" data-stepper-tab data-stepper-target="patient-clinical-step-documents">
                <span class="ux-stepper-number">2</span>
                <span class="ux-stepper-label"><strong>Documents</strong><span>Clinical summaries</span></span>
            </button>
        </div>
        <div class="ux-stepper-panels">
            <section class="ux-stepper-panel is-active" id="patient-clinical-step-prescriptions" data-stepper-panel>
                <div class="row g-4">
                    <div class="col-12" data-aos="fade-up">
                        <div class="glass-panel h-100">
                            <h4 class="mb-3">Prescriptions</h4>
                            <div class="table-responsive">
                                <table class="table align-middle">
                                    <thead><tr><th>Doctor</th><th>Issued</th><th>Status</th><th>Verify</th><th>Action</th></tr></thead>
                                    <tbody>
                                    <?php foreach (($prescriptions ?? []) as $prescription): ?>
                                        <tr>
                                            <td>Dr. <?= e($prescription['doctor_name'] ?? 'Doctor') ?><div class="small text-muted"><?= e($prescription['specialization'] ?? '') ?></div></td>
                                            <td><?= e($prescription['created_at']) ?></td>
                                            <td><span class="badge text-bg-light text-capitalize"><?= e($prescription['status']) ?></span></td>
                                            <td><code><?= e($prescription['verification_code'] ?? 'Pending') ?></code></td>
                                            <td><a class="btn btn-outline-primary btn-sm" href="<?= route_url('clinical-records/prescription', ['id' => (int) $prescription['id']]) ?>">View</a></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($prescriptions)): ?><tr><td colspan="5" class="text-center text-muted py-4">No prescriptions available yet. Signed medication plans from your doctors will appear here with verification codes.</td></tr><?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            <section class="ux-stepper-panel" id="patient-clinical-step-documents" data-stepper-panel>
                <div class="row g-4">
                    <div class="col-12" data-aos="fade-up">
                        <div class="glass-panel h-100">
                            <h4 class="mb-3">Clinical Documents</h4>
                            <div class="table-responsive">
                                <table class="table align-middle">
                                    <thead><tr><th>Title</th><th>Type</th><th>Issued</th><th>Verify</th><th>Action</th></tr></thead>
                                    <tbody>
                                    <?php foreach (($documents ?? []) as $document): ?>
                                        <tr>
                                            <td><?= e($document['title']) ?><div class="small text-muted">Dr. <?= e($document['doctor_name'] ?? 'Doctor') ?></div></td>
                                            <td class="text-capitalize"><?= e(str_replace('_', ' ', $document['document_type'])) ?></td>
                                            <td><?= e($document['issue_date']) ?></td>
                                            <td><code><?= e($document['verification_code'] ?? 'Pending') ?></code></td>
                                            <td><a class="btn btn-outline-primary btn-sm" href="<?= route_url('clinical-records/document', ['id' => (int) $document['id']]) ?>">View</a></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($documents)): ?><tr><td colspan="5" class="text-center text-muted py-4">No clinical documents available yet. Doctor-issued certificates, plans, and summaries will appear here when shared.</td></tr><?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
<?php endif; ?>
