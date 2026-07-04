<section class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
        <span class="eyebrow">Hospital operations</span>
        <h1 class="mb-0">Hospital Management</h1>
    </div>
    <div class="small text-muted">Manage departments, inventory, beds, doctor affiliations, patient consents, and operational privileges.</div>
</section>

<div class="row g-4 mb-4">
    <div class="col-md-6 col-xl-2"><div class="stat-card gradient-card"><span>Departments</span><h2><?= (int) ($stats['departments'] ?? 0) ?></h2></div></div>
    <div class="col-md-6 col-xl-2"><div class="stat-card gradient-card"><span>Inventory</span><h2><?= (int) ($stats['inventory'] ?? 0) ?></h2></div></div>
    <div class="col-md-6 col-xl-2"><div class="stat-card gradient-card"><span>Total Beds</span><h2><?= (int) ($stats['bed_total'] ?? 0) ?></h2><div class="small mt-2">Occupied <?= (int) ($stats['bed_occupied'] ?? 0) ?></div></div></div>
    <div class="col-md-6 col-xl-3"><div class="stat-card gradient-card"><span>Assigned Doctors</span><h2><?= (int) ($stats['assignments'] ?? 0) ?></h2><div class="small mt-2">Pending requests <?= (int) ($stats['pending_requests'] ?? 0) ?></div></div></div>
    <div class="col-md-6 col-xl-3"><div class="stat-card gradient-card"><span>Active Patient Consents</span><h2><?= (int) ($stats['active_consents'] ?? 0) ?></h2><div class="small mt-2">Monthly appointments <?= (int) ($stats['monthly_appointments'] ?? 0) ?></div></div></div>
</div>

<div class="ux-stepper" data-stepper>
    <div class="ux-stepper-nav" role="tablist" aria-label="Hospital management sections">
        <button class="ux-stepper-tab is-active" data-stepper-tab data-stepper-target="hospital-step-departments">
            <span class="ux-stepper-number">1</span>
            <span class="ux-stepper-label"><strong>Setup</strong><span>Departments and inventory</span></span>
        </button>
        <button class="ux-stepper-tab" data-stepper-tab data-stepper-target="hospital-step-beds">
            <span class="ux-stepper-number">2</span>
            <span class="ux-stepper-label"><strong>Beds</strong><span>Capacity and analytics</span></span>
        </button>
        <button class="ux-stepper-tab" data-stepper-tab data-stepper-target="hospital-step-doctors">
            <span class="ux-stepper-number">3</span>
            <span class="ux-stepper-label"><strong>Doctors</strong><span>Requests and privileges</span></span>
        </button>
        <button class="ux-stepper-tab" data-stepper-tab data-stepper-target="hospital-step-access">
            <span class="ux-stepper-number">4</span>
            <span class="ux-stepper-label"><strong>Patient Access</strong><span>Consents and logs</span></span>
        </button>
    </div>
    <div class="ux-stepper-panels">
        <section class="ux-stepper-panel is-active" id="hospital-step-departments" data-stepper-panel>
<div class="row g-4">
    <div class="col-xl-6" data-aos="fade-up">
        <div class="glass-panel h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0">Department Management</h4>
                <span class="small text-muted">Add departments, department heads, timings, and status</span>
            </div>
            <form method="POST" action="<?= route_url('hospital-management/department/save') ?>" class="row g-3 mb-4">
                <?= csrf_field() ?>
                <input type="hidden" name="department_id" value="">
                <div class="col-md-6"><label class="form-label">Department Name</label><input type="text" name="name" class="form-control" placeholder="Cardiology" required></div>
                <div class="col-md-6"><label class="form-label">Department Timings</label><input type="text" name="timings" class="form-control" placeholder="Mon-Sat, 9 AM - 5 PM"></div>
                <div class="col-md-6"><label class="form-label">Department Head</label><select name="head_doctor_id" class="form-select"><option value="">Select doctor</option><?php foreach (($doctorOptions ?? []) as $doctor): ?><option value="<?= (int) $doctor['id'] ?>"><?= e($doctor['name']) ?></option><?php endforeach; ?></select></div>
                <div class="col-md-6"><label class="form-label">Status</label><select name="status" class="form-select"><option value="active">Active</option><option value="inactive">Inactive</option></select></div>
                <div class="col-12"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="3" placeholder="Department resources, coverage, and clinical purpose"></textarea></div>
                <div class="col-12 d-grid"><button class="btn btn-primary">Save Department</button></div>
            </form>
            <div class="table-responsive"><table class="table align-middle"><thead><tr><th>Name</th><th>Head</th><th>Timings</th><th>Status</th></tr></thead><tbody><?php foreach (($departments ?? []) as $department): ?><tr><td><?= e($department['name']) ?><div class="small text-muted"><?= e($department['description'] ?: 'No description') ?></div></td><td><?= e($department['head_doctor_name'] ?? 'Not assigned') ?></td><td><?= e($department['timings'] ?: 'Not set') ?></td><td><span class="badge text-bg-light text-capitalize"><?= e($department['status']) ?></span></td></tr><?php endforeach; ?><?php if (empty($departments)): ?><tr><td colspan="4" class="text-center text-muted py-4">No departments created yet. Add clinical departments, heads, timings, and descriptions to organize hospital services.</td></tr><?php endif; ?></tbody></table></div>
        </div>
    </div>

    <div class="col-xl-6" data-aos="fade-up">
        <div class="glass-panel h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0">Facility & Equipment Inventory</h4>
                <span class="small text-muted">Track equipment, ambulances, maintenance schedules, and status</span>
            </div>
            <form method="POST" action="<?= route_url('hospital-management/inventory/save') ?>" class="row g-3 mb-4">
                <?= csrf_field() ?>
                <input type="hidden" name="item_id" value="">
                <div class="col-md-6"><label class="form-label">Item Name</label><input type="text" name="item_name" class="form-control" placeholder="MRI Scanner" required></div>
                <div class="col-md-6"><label class="form-label">Category</label><input type="text" name="category" class="form-control" placeholder="Imaging / Ambulance / ICU"></div>
                <div class="col-md-4"><label class="form-label">Quantity</label><input type="number" name="quantity" class="form-control" min="0" value="1"></div>
                <div class="col-md-4"><label class="form-label">Status</label><select name="status" class="form-select"><option value="available">Available</option><option value="maintenance">Maintenance</option><option value="critical">Critical</option><option value="out_of_service">Out of Service</option></select></div>
                <div class="col-md-4"><label class="form-label">Next Maintenance</label><input type="date" name="next_maintenance" class="form-control"></div>
                <div class="col-12"><label class="form-label">Notes</label><textarea name="notes" class="form-control" rows="3" placeholder="Vendor, warranty, maintenance remarks, deployment area"></textarea></div>
                <div class="col-12 d-grid"><button class="btn btn-primary">Save Inventory Item</button></div>
            </form>
            <div class="table-responsive"><table class="table align-middle"><thead><tr><th>Item</th><th>Category</th><th>Qty</th><th>Status</th><th>Next Maintenance</th></tr></thead><tbody><?php foreach (($inventoryItems ?? []) as $item): ?><tr><td><?= e($item['item_name']) ?><div class="small text-muted"><?= e($item['notes'] ?: 'No notes') ?></div></td><td><?= e($item['category'] ?: 'General') ?></td><td><?= (int) $item['quantity'] ?></td><td><span class="badge text-bg-light text-capitalize"><?= e(str_replace('_', ' ', $item['status'])) ?></span></td><td><?= e($item['next_maintenance'] ?: 'Not scheduled') ?></td></tr><?php endforeach; ?><?php if (empty($inventoryItems)): ?><tr><td colspan="5" class="text-center text-muted py-4">No inventory items tracked yet. Add equipment, ambulances, supplies, and maintenance dates to support operational planning.</td></tr><?php endif; ?></tbody></table></div>
        </div>
    </div>
</div>

        </section>
        <section class="ux-stepper-panel" id="hospital-step-beds" data-stepper-panel>
<div class="row g-4 mt-1">
    <div class="col-xl-6" data-aos="fade-up">
        <div class="glass-panel h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0">Bed Management</h4>
                <span class="small text-muted">Track bed availability, wards, and assigned patients</span>
            </div>
            <form method="POST" action="<?= route_url('hospital-management/bed/save') ?>" class="row g-3 mb-4">
                <?= csrf_field() ?>
                <input type="hidden" name="bed_id" value="">
                <div class="col-md-6"><label class="form-label">Ward Name</label><input type="text" name="ward_name" class="form-control" placeholder="ICU Ward A" required></div>
                <div class="col-md-6"><label class="form-label">Bed Label</label><input type="text" name="bed_label" class="form-control" placeholder="Bed-01" required></div>
                <div class="col-md-4"><label class="form-label">Bed Type</label><input type="text" name="bed_type" class="form-control" placeholder="ICU / General / Ventilator"></div>
                <div class="col-md-4"><label class="form-label">Status</label><select name="occupancy_status" class="form-select"><option value="available">Available</option><option value="occupied">Occupied</option><option value="reserved">Reserved</option><option value="maintenance">Maintenance</option><option value="cleaning">Cleaning</option></select></div>
                <div class="col-md-4"><label class="form-label">Assigned Patient</label><select name="assigned_patient_id" class="form-select"><option value="">None</option><?php foreach (($patientOptions ?? []) as $patientOption): ?><option value="<?= (int) $patientOption['user_id'] ?>"><?= e($patientOption['name']) ?> (<?= e($patientOption['email']) ?>)</option><?php endforeach; ?></select></div>
                <div class="col-12"><label class="form-label">Notes</label><textarea name="notes" class="form-control" rows="3" placeholder="Bed remarks, ward notes, nurse allocation"></textarea></div>
                <div class="col-12 d-grid"><button class="btn btn-primary">Save Bed Unit</button></div>
            </form>
            <div class="table-responsive"><table class="table align-middle"><thead><tr><th>Ward / Bed</th><th>Type</th><th>Status</th><th>Assigned Patient</th></tr></thead><tbody><?php foreach (($bedUnits ?? []) as $bed): ?><tr><td><?= e($bed['ward_name']) ?><div class="small text-muted"><?= e($bed['bed_label']) ?></div></td><td><?= e($bed['bed_type'] ?: 'General') ?></td><td><span class="badge text-bg-light text-capitalize"><?= e($bed['occupancy_status']) ?></span></td><td><?= e($bed['assigned_patient_name'] ?: 'Unassigned') ?></td></tr><?php endforeach; ?><?php if (empty($bedUnits)): ?><tr><td colspan="4" class="text-center text-muted py-4">No bed units tracked yet. Add wards, bed labels, occupancy status, and patient assignments to monitor capacity.</td></tr><?php endif; ?></tbody></table></div>
        </div>
    </div>

    <div class="col-xl-6" data-aos="fade-up">
        <div class="glass-panel h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0">Operational Analytics</h4>
                <span class="small text-muted">Snapshot of hospital activity and patient flow</span>
            </div>
            <div class="metric-list mb-4">
                <div><span>Total Appointments</span><strong><?= (int) ($appointmentStats['total_appointments'] ?? 0) ?></strong></div>
                <div><span>Completed Appointments</span><strong><?= (int) ($appointmentStats['completed_appointments'] ?? 0) ?></strong></div>
                <div><span>Pending Appointments</span><strong><?= (int) ($appointmentStats['pending_appointments'] ?? 0) ?></strong></div>
                <div><span>Unique Patients</span><strong><?= (int) ($appointmentStats['unique_patients'] ?? 0) ?></strong></div>
                <div><span>Monthly Footfall</span><strong><?= (int) ($appointmentStats['monthly_appointments'] ?? 0) ?></strong></div>
                <div><span>Available Beds</span><strong><?= (int) ($bedStats['available'] ?? 0) ?></strong></div>
            </div>
            <div class="health-tip-card">
                <strong>Operational note</strong>
                <p class="mb-0 text-muted">Use bed status, inventory maintenance dates, and consent coverage together to improve capacity planning and reduce admission delays.</p>
            </div>
        </div>
    </div>
</div>

        </section>
        <section class="ux-stepper-panel" id="hospital-step-doctors" data-stepper-panel>
<div class="row g-4 mt-1">
    <div class="col-xl-6" data-aos="fade-up">
        <div class="glass-panel h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0">Doctor Affiliation Requests</h4>
                <span class="small text-muted">Approve doctors, assign departments, and set privileges</span>
            </div>
            <div class="table-responsive"><table class="table align-middle"><thead><tr><th>Doctor</th><th>Credentials</th><th>Preferred Dept.</th><th>Status</th><th>Action</th></tr></thead><tbody><?php foreach (($pendingRequests ?? []) as $request): ?><tr><td><?= e($request['doctor_name']) ?><div class="small text-muted"><?= e($request['specialization']) ?> · <?= e($request['doctor_email']) ?></div></td><td><div class="small"><strong>License:</strong> <?= e($request['license_number']) ?></div><div class="small text-muted"><?= e($request['credentials'] ?: 'Credentials not attached') ?></div></td><td><?= e($request['preferred_departments'] ?: 'Not specified') ?></td><td><span class="badge text-bg-light text-capitalize"><?= e($request['status']) ?></span></td><td><?php if (($request['status'] ?? '') === 'pending'): ?><form method="POST" action="<?= route_url('hospital-management/request/update') ?>" class="d-grid gap-2"><?= csrf_field() ?><input type="hidden" name="request_id" value="<?= (int) $request['id'] ?>"><select name="department_id" class="form-select form-select-sm"><option value="">Assign department</option><?php foreach (($departments ?? []) as $department): ?><option value="<?= (int) $department['id'] ?>"><?= e($department['name']) ?></option><?php endforeach; ?></select><input type="text" name="privileges" class="form-control form-control-sm" placeholder="Privileges e.g. ICU access, OPD, prescribing"><div class="d-flex gap-2"><button name="status" value="approved" class="btn btn-success btn-sm flex-grow-1">Approve</button><button name="status" value="rejected" class="btn btn-outline-danger btn-sm flex-grow-1">Reject</button></div></form><?php else: ?><span class="text-muted small">Request already reviewed</span><?php endif; ?></td></tr><?php endforeach; ?><?php if (empty($pendingRequests)): ?><tr><td colspan="5" class="text-center text-muted py-4">No doctor requests available. Affiliation requests will appear here when doctors ask to join this hospital.</td></tr><?php endif; ?></tbody></table></div>
        </div>
    </div>

    <div class="col-xl-6" data-aos="fade-up">
        <div class="glass-panel h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0">Assigned Doctors & Privileges</h4>
                <span class="small text-muted">Manage department placement and operational access</span>
            </div>
            <div class="table-responsive"><table class="table align-middle"><thead><tr><th>Doctor</th><th>Department</th><th>Privileges</th><th>Status</th><th>Update</th></tr></thead><tbody><?php foreach (($assignments ?? []) as $assignment): ?><tr><td><?= e($assignment['doctor_name']) ?><div class="small text-muted"><?= e($assignment['specialization']) ?> · <?= e($assignment['doctor_email']) ?></div></td><td><?= e($assignment['department_name'] ?: 'Unassigned') ?></td><td><?= e($assignment['privileges'] ?: 'Standard access') ?></td><td><span class="badge text-bg-light text-capitalize"><?= e($assignment['status']) ?></span></td><td><form method="POST" action="<?= route_url('hospital-management/assignment/update') ?>" class="d-grid gap-2"><?= csrf_field() ?><input type="hidden" name="assignment_id" value="<?= (int) $assignment['id'] ?>"><select name="department_id" class="form-select form-select-sm"><option value="">Assign department</option><?php foreach (($departments ?? []) as $department): ?><option value="<?= (int) $department['id'] ?>" <?= (int) ($assignment['department_id'] ?? 0) === (int) $department['id'] ? 'selected' : '' ?>><?= e($department['name']) ?></option><?php endforeach; ?></select><input type="text" name="privileges" class="form-control form-control-sm" value="<?= e($assignment['privileges'] ?: '') ?>" placeholder="Privileges"><select name="status" class="form-select form-select-sm"><option value="active" <?= ($assignment['status'] ?? '') === 'active' ? 'selected' : '' ?>>Active</option><option value="suspended" <?= ($assignment['status'] ?? '') === 'suspended' ? 'selected' : '' ?>>Suspended</option><option value="pending" <?= ($assignment['status'] ?? '') === 'pending' ? 'selected' : '' ?>>Pending</option></select><button class="btn btn-outline-primary btn-sm">Save</button></form></td></tr><?php endforeach; ?><?php if (empty($assignments)): ?><tr><td colspan="5" class="text-center text-muted py-4">No doctor assignments yet. Approved affiliations will appear here with department placement and operational privileges.</td></tr><?php endif; ?></tbody></table></div>
        </div>
    </div>
</div>

        </section>
        <section class="ux-stepper-panel" id="hospital-step-access" data-stepper-panel>
<div class="row g-4 mt-1">
    <div class="col-xl-6" data-aos="fade-up">
        <div class="glass-panel h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0">Patient Record Consents</h4>
                <span class="small text-muted">Access patient records only where active consent exists</span>
            </div>
            <div class="table-responsive"><table class="table align-middle"><thead><tr><th>Patient</th><th>Scope</th><th>Expires</th><th>Action</th></tr></thead><tbody><?php foreach (($consents ?? []) as $consent): ?><tr><td><?= e($consent['patient_name']) ?><div class="small text-muted"><?= e($consent['patient_email']) ?> · <?= e($consent['phone'] ?: 'No phone') ?></div></td><td><?= e($consent['scope']) ?></td><td><?= e($consent['expires_at'] ?: 'No expiry') ?></td><td><a href="<?= route_url('hospital-management/patient-record', ['patient_id' => (int) $consent['patient_id']]) ?>" class="btn btn-outline-primary btn-sm">Open Record</a></td></tr><?php endforeach; ?><?php if (empty($consents)): ?><tr><td colspan="4" class="text-center text-muted py-4">No active patient consents yet. Patient-approved record access will appear here with scope and expiry details.</td></tr><?php endif; ?></tbody></table></div>
        </div>
    </div>

    <div class="col-xl-6" data-aos="fade-up">
        <div class="glass-panel h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0">Patient Record Access Logs</h4>
                <span class="small text-muted">Auditable trail of consent-based access</span>
            </div>
            <div class="table-responsive"><table class="table align-middle"><thead><tr><th>Patient</th><th>Accessor</th><th>Access Type</th><th>When</th></tr></thead><tbody><?php foreach (($accessLogs ?? []) as $log): ?><tr><td><?= e($log['patient_name']) ?></td><td><?= e($log['accessor_email']) ?></td><td><?= e($log['access_type']) ?></td><td><?= e($log['created_at']) ?></td></tr><?php endforeach; ?><?php if (empty($accessLogs)): ?><tr><td colspan="4" class="text-center text-muted py-4">No access logs recorded yet. Every consent-based record view will be listed here for audit visibility.</td></tr><?php endif; ?></tbody></table></div>
        </div>
    </div>
</div>
        </section>
    </div>
</div>
