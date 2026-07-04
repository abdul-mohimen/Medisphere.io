<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\CSRF;
use App\Core\Security;
use App\Core\View;
use App\Models\Appointment;
use App\Models\ClinicalDocument;
use App\Models\Doctor;
use App\Models\DoctorSignatureProfile;
use App\Models\DocumentAuditLog;
use App\Models\Patient;
use App\Models\Prescription;
use App\Services\NotificationService;

class ClinicalController
{
    public function index(): void
    {
        Auth::requireLogin(['patient', 'doctor']);
        $role = Auth::type();
        $userId = Auth::id();

        $data = [
            'title' => 'Clinical Records',
            'role' => $role,
        ];

        if ($role === 'doctor') {
            $appointments = (new Appointment())->forDoctor($userId);
            $doctor = (new Doctor())->findByUserId($userId);
            $data['appointments'] = $appointments;
            $data['doctorProfile'] = $doctor;
            $data['signatureProfile'] = (new DoctorSignatureProfile())->findByDoctor($userId);
            $data['prescriptions'] = (new Prescription())->byDoctor($userId);
            $data['documents'] = (new ClinicalDocument())->byDoctor($userId);
        } else {
            $data['patientProfile'] = (new Patient())->findByUserId($userId);
            $data['prescriptions'] = (new Prescription())->byPatient($userId);
            $data['documents'] = (new ClinicalDocument())->byPatient($userId);
        }

        View::render('clinical_records', $data);
    }

    public function saveSignatureProfile(): void
    {
        Auth::requireLogin(['doctor']);
        if (!$this->validateCsrf()) {
            return;
        }

        $signatureName = Security::cleanString($_POST['signature_name'] ?? '');
        $stampText = Security::cleanString($_POST['stamp_text'] ?? '');
        $signatureDataUrl = trim((string) ($_POST['signature_data_url'] ?? ''));

        if ($signatureName === '') {
            flash('error', 'Signature name is required.', 'danger');
            redirect('clinical-records');
        }

        $current = (new DoctorSignatureProfile())->findByDoctor(Auth::id());
        $signaturePath = $current['signature_image_path'] ?? null;
        if ($signatureDataUrl !== '') {
            $saved = $this->saveSignatureDataUrl($signatureDataUrl, Auth::id());
            if (!$saved) {
                flash('error', 'Signature image could not be saved. Please try again.', 'danger');
                redirect('clinical-records');
            }
            $signaturePath = $saved;
        }

        (new DoctorSignatureProfile())->upsert(Auth::id(), [
            'signature_name' => $signatureName,
            'stamp_text' => $stampText,
            'signature_image_path' => $signaturePath,
        ]);

        flash('success', 'Digital signature profile saved successfully.', 'success');
        redirect('clinical-records');
    }

    public function savePrescription(): void
    {
        Auth::requireLogin(['doctor']);
        if (!$this->validateCsrf()) {
            return;
        }

        $appointmentId = Security::cleanInt($_POST['appointment_id'] ?? 0);
        $title = Security::cleanString($_POST['title'] ?? 'Prescription');
        $diagnosis = Security::cleanString($_POST['diagnosis'] ?? '');
        $notes = Security::cleanString($_POST['notes'] ?? '');
        $advice = Security::cleanString($_POST['advice'] ?? '');
        $followUpDate = Security::cleanString($_POST['follow_up_date'] ?? '');
        $status = Security::cleanString($_POST['status'] ?? 'finalized');
        $status = in_array($status, ['draft', 'finalized'], true) ? $status : 'finalized';
        $medicationsRaw = trim((string) ($_POST['medications'] ?? ''));
        $title = $title !== '' ? $title : 'Prescription';

        $appointment = (new Appointment())->find($appointmentId);
        if (!$appointment || (int) $appointment['doctor_id'] !== (int) Auth::id()) {
            flash('error', 'Appointment not found or access denied.', 'danger');
            redirect('clinical-records');
        }
        if ($followUpDate !== '' && !$this->isValidDate($followUpDate)) {
            flash('error', 'Please choose a valid follow-up date.', 'danger');
            redirect('clinical-records');
        }

        $medications = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $medicationsRaw))));
        if (!$medications) {
            flash('error', 'Please provide at least one medication line.', 'danger');
            redirect('clinical-records');
        }

        $doctor = (new Doctor())->findByUserId(Auth::id());
        $signatureProfile = (new DoctorSignatureProfile())->findByDoctor(Auth::id());
        $verificationCode = 'RX-' . strtoupper(substr(bin2hex(random_bytes(6)), 0, 12));
        $signatureName = $signatureProfile['signature_name'] ?? ($doctor['name'] ?? 'Doctor');
        $signatureImagePath = $signatureProfile['signature_image_path'] ?? null;
        $digitalSignature = ($signatureName ?: ($doctor['name'] ?? 'Doctor')) . ' | ' . ($doctor['license_number'] ?? 'License pending');
        if (!empty($signatureProfile['stamp_text'])) {
            $digitalSignature .= ' | ' . $signatureProfile['stamp_text'];
        }

        $integrityHash = $this->computeIntegrityHash('prescription', [
            'appointment_id' => $appointmentId,
            'doctor_id' => Auth::id(),
            'patient_id' => (int) $appointment['patient_id'],
            'title' => $title,
            'diagnosis' => $diagnosis,
            'medications' => $medications,
            'notes' => $notes,
            'advice' => $advice,
            'follow_up_date' => $followUpDate,
            'signature_name' => $signatureName,
            'digital_signature' => $digitalSignature,
            'status' => $status,
            'verification_code' => $verificationCode,
        ]);

        $prescriptionId = (new Prescription())->create([
            'appointment_id' => $appointmentId,
            'doctor_id' => Auth::id(),
            'patient_id' => (int) $appointment['patient_id'],
            'title' => $title,
            'diagnosis' => $diagnosis,
            'medications_json' => json_encode($medications),
            'notes' => $notes,
            'advice' => $advice,
            'follow_up_date' => $followUpDate,
            'digital_signature' => $digitalSignature,
            'signature_snapshot_name' => $signatureName,
            'signature_image_path' => $signatureImagePath,
            'verification_code' => $verificationCode,
            'integrity_hash' => $integrityHash,
            'status' => $status,
        ]);

        $this->audit('prescription', $prescriptionId, 'created', ['doctor_id' => Auth::id(), 'verification_code' => $verificationCode]);

        (new NotificationService())->sendToUser((int) $appointment['patient_id'], 'prescription_created_patient', [
            'name' => (new Patient())->findByUserId((int) $appointment['patient_id'])['name'] ?? 'Patient',
            'doctor_name' => $doctor['name'] ?? 'Doctor',
            'prescription_title' => $title,
        ], [
            'category' => 'appointment',
            'type' => 'success',
            'action_url' => route_url('clinical-records/prescription', ['id' => $prescriptionId]),
        ]);

        flash('success', 'Prescription created successfully.', 'success');
        redirect('clinical-records/prescription', ['id' => $prescriptionId]);
    }

    public function saveDocument(): void
    {
        Auth::requireLogin(['doctor']);
        if (!$this->validateCsrf()) {
            return;
        }

        $appointmentId = Security::cleanInt($_POST['appointment_id'] ?? 0);
        $documentType = Security::cleanString($_POST['document_type'] ?? 'treatment_plan');
        $documentType = in_array($documentType, ['medical_certificate', 'discharge_summary', 'treatment_plan', 'follow_up_note'], true)
            ? $documentType : 'treatment_plan';
        $title = Security::cleanString($_POST['title'] ?? ucfirst(str_replace('_', ' ', $documentType)));
        $summary = Security::cleanString($_POST['summary'] ?? '');
        $content = trim((string) ($_POST['content'] ?? ''));
        $issueDate = Security::cleanString($_POST['issue_date'] ?? date('Y-m-d'));
        $status = Security::cleanString($_POST['status'] ?? 'finalized');
        $status = in_array($status, ['draft', 'finalized'], true) ? $status : 'finalized';
        $title = $title !== '' ? $title : ucwords(str_replace('_', ' ', $documentType));
        $issueDate = $issueDate !== '' ? $issueDate : date('Y-m-d');

        $appointment = (new Appointment())->find($appointmentId);
        if (!$appointment || (int) $appointment['doctor_id'] !== (int) Auth::id()) {
            flash('error', 'Appointment not found or access denied.', 'danger');
            redirect('clinical-records');
        }
        if (!$this->isValidDate($issueDate)) {
            flash('error', 'Please choose a valid issue date.', 'danger');
            redirect('clinical-records');
        }
        if ($content === '') {
            flash('error', 'Document content is required.', 'danger');
            redirect('clinical-records');
        }

        $doctor = (new Doctor())->findByUserId(Auth::id());
        $signatureProfile = (new DoctorSignatureProfile())->findByDoctor(Auth::id());
        $verificationCode = 'DOC-' . strtoupper(substr(bin2hex(random_bytes(6)), 0, 12));
        $signatureName = $signatureProfile['signature_name'] ?? ($doctor['name'] ?? 'Doctor');
        $signatureImagePath = $signatureProfile['signature_image_path'] ?? null;
        $digitalSignature = ($signatureName ?: ($doctor['name'] ?? 'Doctor')) . ' | ' . ($doctor['license_number'] ?? 'License pending');
        if (!empty($signatureProfile['stamp_text'])) {
            $digitalSignature .= ' | ' . $signatureProfile['stamp_text'];
        }

        $integrityHash = $this->computeIntegrityHash('clinical_document', [
            'appointment_id' => $appointmentId,
            'doctor_id' => Auth::id(),
            'patient_id' => (int) $appointment['patient_id'],
            'document_type' => $documentType,
            'title' => $title,
            'summary' => $summary,
            'content' => $content,
            'issue_date' => $issueDate,
            'signature_name' => $signatureName,
            'digital_signature' => $digitalSignature,
            'status' => $status,
            'verification_code' => $verificationCode,
        ]);

        $documentId = (new ClinicalDocument())->create([
            'appointment_id' => $appointmentId,
            'doctor_id' => Auth::id(),
            'patient_id' => (int) $appointment['patient_id'],
            'document_type' => $documentType,
            'title' => $title,
            'summary' => $summary,
            'content' => $content,
            'issue_date' => $issueDate,
            'digital_signature' => $digitalSignature,
            'signature_snapshot_name' => $signatureName,
            'signature_image_path' => $signatureImagePath,
            'verification_code' => $verificationCode,
            'integrity_hash' => $integrityHash,
            'status' => $status,
        ]);

        $this->audit('clinical_document', $documentId, 'created', ['doctor_id' => Auth::id(), 'verification_code' => $verificationCode]);

        (new NotificationService())->sendToUser((int) $appointment['patient_id'], 'clinical_document_created_patient', [
            'name' => (new Patient())->findByUserId((int) $appointment['patient_id'])['name'] ?? 'Patient',
            'doctor_name' => $doctor['name'] ?? 'Doctor',
            'document_title' => $title,
        ], [
            'category' => 'appointment',
            'type' => 'info',
            'action_url' => route_url('clinical-records/document', ['id' => $documentId]),
        ]);

        flash('success', 'Clinical document generated successfully.', 'success');
        redirect('clinical-records/document', ['id' => $documentId]);
    }

    public function prescription(): void
    {
        Auth::requireLogin(['patient', 'doctor', 'admin']);
        $id = Security::cleanInt($_GET['id'] ?? 0);
        $prescription = (new Prescription())->find($id);
        if (!$prescription) {
            flash('error', 'Prescription not found.', 'danger');
            redirect('clinical-records');
        }

        $this->authorizeRecord((int) $prescription['doctor_id'], (int) $prescription['patient_id']);
        $this->audit('prescription', $id, 'viewed', ['viewer_id' => Auth::id()]);

        View::render('prescription_view', [
            'title' => 'Prescription',
            'prescription' => $prescription,
            'medications' => json_decode((string) ($prescription['medications_json'] ?? '[]'), true) ?: [],
            'verifyUrl' => route_url('verify-document', ['type' => 'prescription', 'code' => $prescription['verification_code']]),
            'auditLogs' => (new DocumentAuditLog())->forDocument('prescription', $id),
            'integrityValid' => $this->isPrescriptionIntegrityValid($prescription),
        ]);
    }

    public function document(): void
    {
        Auth::requireLogin(['patient', 'doctor', 'admin']);
        $id = Security::cleanInt($_GET['id'] ?? 0);
        $document = (new ClinicalDocument())->find($id);
        if (!$document) {
            flash('error', 'Clinical document not found.', 'danger');
            redirect('clinical-records');
        }

        $this->authorizeRecord((int) $document['doctor_id'], (int) $document['patient_id']);
        $this->audit('clinical_document', $id, 'viewed', ['viewer_id' => Auth::id()]);

        View::render('clinical_document_view', [
            'title' => 'Clinical Document',
            'document' => $document,
            'verifyUrl' => route_url('verify-document', ['type' => 'clinical_document', 'code' => $document['verification_code']]),
            'auditLogs' => (new DocumentAuditLog())->forDocument('clinical_document', $id),
            'integrityValid' => $this->isClinicalDocumentIntegrityValid($document),
        ]);
    }

    public function verify(): void
    {
        $type = Security::cleanString($_GET['type'] ?? '');
        $code = Security::cleanString($_GET['code'] ?? '');
        $record = null;
        $integrityValid = false;
        $resolvedType = $type;

        if ($code !== '') {
            if ($type === 'prescription') {
                $record = (new Prescription())->findByVerificationCode($code);
                $integrityValid = $record ? $this->isPrescriptionIntegrityValid($record) : false;
            } elseif ($type === 'clinical_document') {
                $record = (new ClinicalDocument())->findByVerificationCode($code);
                $integrityValid = $record ? $this->isClinicalDocumentIntegrityValid($record) : false;
            } else {
                $record = (new Prescription())->findByVerificationCode($code);
                if ($record) {
                    $resolvedType = 'prescription';
                    $integrityValid = $this->isPrescriptionIntegrityValid($record);
                } else {
                    $record = (new ClinicalDocument())->findByVerificationCode($code);
                    if ($record) {
                        $resolvedType = 'clinical_document';
                        $integrityValid = $this->isClinicalDocumentIntegrityValid($record);
                    }
                }
            }
        }

        if ($record) {
            $this->audit($resolvedType, (int) $record['id'], 'verified', [
                'verified_by_user_id' => Auth::check() ? Auth::id() : null,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
                'code' => $code,
            ]);
        }

        View::render('verify_document', [
            'title' => 'Document Verification',
            'verificationCode' => $code,
            'documentType' => $resolvedType,
            'record' => $record,
            'integrityValid' => $integrityValid,
        ]);
    }

    private function authorizeRecord(int $doctorId, int $patientId): void
    {
        if (Auth::type() === 'admin') {
            return;
        }

        if (!in_array(Auth::id(), [$doctorId, $patientId], true)) {
            flash('error', 'You do not have access to that record.', 'danger');
            redirect('dashboard');
        }
    }

    private function validateCsrf(): bool
    {
        if (!CSRF::validate($_POST['csrf_token'] ?? null)) {
            flash('error', 'Invalid security token.', 'danger');
            redirect('clinical-records');
            return false;
        }
        return true;
    }

    private function saveSignatureDataUrl(string $dataUrl, int $doctorId): ?string
    {
        if (!preg_match('/^data:image\/(png|jpeg);base64,/', $dataUrl, $matches)) {
            return null;
        }

        $binary = base64_decode(substr($dataUrl, strpos($dataUrl, ',') + 1), true);
        if ($binary === false) {
            return null;
        }

        $extension = $matches[1] === 'jpeg' ? 'jpg' : 'png';
        $filename = 'signature_' . $doctorId . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
        $relativePath = 'uploads/signatures/' . $filename;
        $target = __DIR__ . '/../../public/' . $relativePath;

        return file_put_contents($target, $binary) !== false ? $relativePath : null;
    }

    private function audit(string $documentType, int $documentId, string $action, array $context = []): void
    {
        (new DocumentAuditLog())->add(
            $documentType,
            $documentId,
            Auth::check() ? Auth::id() : null,
            $action,
            json_encode($context)
        );
    }

    private function computeIntegrityHash(string $documentType, array $payload): string
    {
        ksort($payload);
        return hash_hmac('sha256', $documentType . '|' . json_encode($payload), config('security.document_signing_key', 'medisphere-doc-signing-key'));
    }

    private function isValidDate(string $date): bool
    {
        $parsed = \DateTimeImmutable::createFromFormat('Y-m-d', $date);
        return $parsed !== false && $parsed->format('Y-m-d') === $date;
    }

    private function isPrescriptionIntegrityValid(array $prescription): bool
    {
        $payload = [
            'appointment_id' => (int) ($prescription['appointment_id'] ?? 0),
            'doctor_id' => (int) ($prescription['doctor_id'] ?? 0),
            'patient_id' => (int) ($prescription['patient_id'] ?? 0),
            'title' => $prescription['title'] ?? '',
            'diagnosis' => $prescription['diagnosis'] ?? '',
            'medications' => json_decode((string) ($prescription['medications_json'] ?? '[]'), true) ?: [],
            'notes' => $prescription['notes'] ?? '',
            'advice' => $prescription['advice'] ?? '',
            'follow_up_date' => $prescription['follow_up_date'] ?? '',
            'signature_name' => $prescription['signature_snapshot_name'] ?? '',
            'digital_signature' => $prescription['digital_signature'] ?? '',
            'status' => $prescription['status'] ?? '',
            'verification_code' => $prescription['verification_code'] ?? '',
        ];

        return hash_equals((string) ($prescription['integrity_hash'] ?? ''), $this->computeIntegrityHash('prescription', $payload));
    }

    private function isClinicalDocumentIntegrityValid(array $document): bool
    {
        $payload = [
            'appointment_id' => (int) ($document['appointment_id'] ?? 0),
            'doctor_id' => (int) ($document['doctor_id'] ?? 0),
            'patient_id' => (int) ($document['patient_id'] ?? 0),
            'document_type' => $document['document_type'] ?? '',
            'title' => $document['title'] ?? '',
            'summary' => $document['summary'] ?? '',
            'content' => $document['content'] ?? '',
            'issue_date' => $document['issue_date'] ?? '',
            'signature_name' => $document['signature_snapshot_name'] ?? '',
            'digital_signature' => $document['digital_signature'] ?? '',
            'status' => $document['status'] ?? '',
            'verification_code' => $document['verification_code'] ?? '',
        ];

        return hash_equals((string) ($document['integrity_hash'] ?? ''), $this->computeIntegrityHash('clinical_document', $payload));
    }
}
