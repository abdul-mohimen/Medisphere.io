<section class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
        <span class="eyebrow">Invoice</span>
        <h1 class="mb-0"><?= e($invoice['invoice_number'] ?? 'Invoice') ?></h1>
        <p class="text-muted mb-0 mt-2">Review the consultation charge, appointment details, payment status, and printable billing record.</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <button class="btn btn-primary" onclick="window.print()"><i class="fa-solid fa-print"></i> Print / Save PDF</button>
        <a href="<?= route_url('payments') ?>" class="btn btn-outline-primary">Back to Payments</a>
    </div>
</section>

<div class="glass-panel invoice-sheet" data-aos="fade-up">
    <div class="row g-4">
        <div class="col-md-6">
            <div class="brand-link mb-3">
                <span class="logo-badge"><i class="fa-solid fa-heart-pulse"></i></span>
                <span>MediSphere Billing</span>
            </div>
            <div class="small text-muted">Premium healthcare billing and appointment invoicing</div>
        </div>
        <div class="col-md-6 text-md-end">
            <div><strong>Status:</strong> <span class="badge text-bg-light text-capitalize"><?= e($invoice['status'] ?? 'unpaid') ?></span></div>
            <div><strong>Issued:</strong> <?= e($invoice['issued_at'] ?? '') ?></div>
            <div><strong>Due Date:</strong> <?= e($invoice['due_date'] ?? '') ?></div>
        </div>
    </div>

    <hr>

    <div class="row g-4">
        <div class="col-md-6">
            <h5>Bill To</h5>
            <div class="invoice-meta">
                <div><strong>Patient:</strong> <?= e($invoice['patient_name'] ?? 'Patient') ?></div>
                <div><strong>Email:</strong> <?= e($invoice['patient_email'] ?? '') ?></div>
            </div>
        </div>
        <div class="col-md-6">
            <h5>Consultation Details</h5>
            <div class="invoice-meta">
                <div><strong>Doctor:</strong> <?= e($invoice['doctor_name'] ?? 'Doctor') ?></div>
                <div><strong>Specialization:</strong> <?= e($invoice['specialization'] ?? 'General') ?></div>
                <div><strong>Appointment:</strong> <?= e($invoice['appointment_date'] ?? '') ?> <?= e($invoice['appointment_time'] ?? '') ?></div>
            </div>
        </div>
    </div>

    <div class="table-responsive mt-4">
        <table class="table align-middle">
            <thead><tr><th>Description</th><th>Qty</th><th>Unit Price</th><th>Total</th></tr></thead>
            <tbody>
                <tr>
                    <td>
                        Doctor consultation fee
                        <div class="small text-muted"><?= e($invoice['notes'] ?? 'Consultation billing') ?></div>
                    </td>
                    <td>1</td>
                    <td><?= e($invoice['currency']) ?> <?= number_format((float) $invoice['amount'], 2) ?></td>
                    <td><?= e($invoice['currency']) ?> <?= number_format((float) $invoice['amount'], 2) ?></td>
                </tr>
            </tbody>
            <tfoot>
                <tr><th colspan="3" class="text-end">Grand Total</th><th><?= e($invoice['currency']) ?> <?= number_format((float) $invoice['amount'], 2) ?></th></tr>
            </tfoot>
        </table>
    </div>

    <?php if (!empty($payments)): ?>
        <div class="mt-4">
            <h5>Payment Records</h5>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead><tr><th>Reference</th><th>Provider</th><th>Status</th><th>Amount</th></tr></thead>
                    <tbody>
                    <?php foreach ($payments as $payment): ?>
                        <tr>
                            <td><?= e($payment['transaction_reference']) ?></td>
                            <td class="text-uppercase"><?= e($payment['provider']) ?></td>
                            <td><span class="badge text-bg-light text-capitalize"><?= e($payment['status']) ?></span></td>
                            <td><?= e($payment['currency']) ?> <?= number_format((float) $payment['amount'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

    <div class="invoice-footer-note mt-4 small text-muted">
        This invoice can be printed or saved as PDF from your browser. For live online capture, connect your payment provider credentials and callback endpoints.
    </div>
</div>
