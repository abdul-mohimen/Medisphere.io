<section class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
        <span class="eyebrow">Admin panel</span>
        <h1 class="mb-0">Verification Center</h1>
        <p class="text-muted mb-0 mt-2">Review provider credentials, hospital registrations, and user account status before approving access.</p>
    </div>
</section>

<div class="ux-stepper" data-stepper>
    <div class="ux-stepper-nav" role="tablist" aria-label="Verification center sections">
        <button class="ux-stepper-tab is-active" data-stepper-tab data-stepper-target="verification-step-doctors">
            <span class="ux-stepper-number">1</span>
            <span class="ux-stepper-label"><strong>Doctors</strong><span>Credential review</span></span>
        </button>
        <button class="ux-stepper-tab" data-stepper-tab data-stepper-target="verification-step-hospitals">
            <span class="ux-stepper-number">2</span>
            <span class="ux-stepper-label"><strong>Hospitals</strong><span>Registration review</span></span>
        </button>
        <button class="ux-stepper-tab" data-stepper-tab data-stepper-target="verification-step-users">
            <span class="ux-stepper-number">3</span>
            <span class="ux-stepper-label"><strong>Users</strong><span>Account audit</span></span>
        </button>
    </div>
    <div class="ux-stepper-panels">
        <section class="ux-stepper-panel is-active" id="verification-step-doctors" data-stepper-panel>
<div class="row g-4">
    <div class="col-12" data-aos="fade-up">
        <div class="glass-panel h-100">
            <h5>Pending Doctor Verifications</h5>
            <div class="table-responsive mt-3">
                <table class="table align-middle">
                    <thead><tr><th>Name</th><th>Specialization</th><th>License</th><th>Action</th></tr></thead>
                    <tbody>
                    <?php foreach ($pendingDoctors as $doctor): ?>
                        <tr>
                            <td><?= e($doctor['name']) ?><div class="small text-muted"><?= e($doctor['email']) ?></div></td>
                            <td><?= e($doctor['specialization']) ?></td>
                            <td><?= e($doctor['license_number']) ?></td>
                            <td>
                                <div class="d-flex gap-2">
                                    <form method="POST" action="<?= route_url('admin/verify-doctor') ?>">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="user_id" value="<?= (int) $doctor['user_id'] ?>">
                                        <input type="hidden" name="status" value="verified">
                                        <button class="btn btn-success btn-sm">Approve</button>
                                    </form>
                                    <form method="POST" action="<?= route_url('admin/verify-doctor') ?>">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="user_id" value="<?= (int) $doctor['user_id'] ?>">
                                        <input type="hidden" name="status" value="rejected">
                                        <button class="btn btn-outline-danger btn-sm">Reject</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$pendingDoctors): ?><tr><td colspan="4" class="text-center text-muted py-4">No doctor verifications are waiting right now. New credential submissions will appear here for approval.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    </div>
        </section>
        <section class="ux-stepper-panel" id="verification-step-hospitals" data-stepper-panel>
<div class="row g-4">
    <div class="col-12" data-aos="fade-up">
        <div class="glass-panel h-100">
            <h5>Pending Hospital Verifications</h5>
            <div class="table-responsive mt-3">
                <table class="table align-middle">
                    <thead><tr><th>Hospital</th><th>City</th><th>Registration</th><th>Action</th></tr></thead>
                    <tbody>
                    <?php foreach ($pendingHospitals as $hospital): ?>
                        <tr>
                            <td><?= e($hospital['name']) ?><div class="small text-muted"><?= e($hospital['email']) ?></div></td>
                            <td><?= e($hospital['city']) ?></td>
                            <td><?= e($hospital['registration_number']) ?></td>
                            <td>
                                <div class="d-flex gap-2">
                                    <form method="POST" action="<?= route_url('admin/verify-hospital') ?>">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="user_id" value="<?= (int) $hospital['user_id'] ?>">
                                        <input type="hidden" name="status" value="verified">
                                        <button class="btn btn-success btn-sm">Approve</button>
                                    </form>
                                    <form method="POST" action="<?= route_url('admin/verify-hospital') ?>">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="user_id" value="<?= (int) $hospital['user_id'] ?>">
                                        <input type="hidden" name="status" value="rejected">
                                        <button class="btn btn-outline-danger btn-sm">Reject</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$pendingHospitals): ?><tr><td colspan="4" class="text-center text-muted py-4">No hospital verifications are waiting right now. Registration reviews will appear here when facilities apply.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

        </section>
        <section class="ux-stepper-panel" id="verification-step-users" data-stepper-panel>
<div class="glass-panel mt-4" data-aos="fade-up">
    <h5>All Users</h5>
    <div class="table-responsive mt-3">
        <table class="table align-middle">
            <thead><tr><th>ID</th><th>Email</th><th>Role</th><th>Status</th><th>Created</th></tr></thead>
            <tbody>
            <?php foreach ($users as $user): ?>
                <tr>
                    <td>#<?= (int) $user['id'] ?></td>
                    <td><?= e($user['email']) ?></td>
                    <td class="text-capitalize"><?= e($user['user_type']) ?></td>
                    <td><span class="badge text-bg-light text-capitalize"><?= e($user['status']) ?></span></td>
                    <td><?= e($user['created_at']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
        </section>
    </div>
</div>
