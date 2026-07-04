<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\CSRF;
use App\Core\Security;
use App\Core\View;
use App\Models\ClinicalDocument;
use App\Models\MedicalReport;
use App\Models\Prescription;

class ReportController
{
    private const REPORT_TYPES = ['Lab Tests', 'Prescriptions', 'X-Rays', 'MRI', 'CT Scan', 'Discharge Summary', 'General'];

    public function index(): void
    {
        Auth::requireLogin(['patient', 'doctor', 'admin']);
        $role = Auth::type();
        $reports = [];
        $reportTypeCounts = [];
        $clinicalStats = ['prescriptions' => 0, 'documents' => 0];
        $clinicalPrescriptions = [];
        $clinicalDocuments = [];

        if ($role === 'patient') {
            $patientId = Auth::id();
            $reportModel = new MedicalReport();
            $reports = $reportModel->byPatient($patientId);
            $reportTypeCounts = $reportModel->countsByType($patientId);
            $clinicalPrescriptions = (new Prescription())->byPatient($patientId);
            $clinicalDocuments = (new ClinicalDocument())->byPatient($patientId);
            $clinicalStats = [
                'prescriptions' => count($clinicalPrescriptions),
                'documents' => count($clinicalDocuments),
            ];
        }

        View::render('reports', [
            'title' => 'Medical Reports',
            'role' => $role,
            'reports' => $reports,
            'reportTypes' => self::REPORT_TYPES,
            'reportTypeCounts' => $reportTypeCounts,
            'clinicalStats' => $clinicalStats,
            'clinicalPrescriptions' => $clinicalPrescriptions,
            'clinicalDocuments' => $clinicalDocuments,
            'maxUploadMb' => (int) config('app.upload_max_mb', 10),
        ]);
    }

    public function upload(): void
    {
        Auth::requireLogin(['patient']);
        if (!CSRF::validate($_POST['csrf_token'] ?? null)) {
            flash('error', 'Invalid security token.', 'danger');
            redirect('reports');
        }

        if (empty($_FILES['report_file']['name'])) {
            flash('error', 'Please choose a report file.', 'danger');
            redirect('reports');
        }

        $allowed = [
            'application/pdf' => 'pdf',
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
        ];
        $file = $_FILES['report_file'];
        $mime = $file['error'] === UPLOAD_ERR_OK ? (mime_content_type($file['tmp_name']) ?: '') : '';
        if ($file['error'] !== UPLOAD_ERR_OK || !array_key_exists($mime, $allowed)) {
            flash('error', 'Only PDF, JPG, PNG, and WebP files are allowed.', 'danger');
            redirect('reports');
        }

        if (($file['size'] / 1024 / 1024) > config('app.upload_max_mb')) {
            flash('error', 'File exceeds maximum upload size.', 'danger');
            redirect('reports');
        }

        $reportType = Security::cleanString($_POST['report_type'] ?? 'General');
        if (!in_array($reportType, self::REPORT_TYPES, true)) {
            $reportType = 'General';
        }

        $uploadDir = __DIR__ . '/../../public/uploads/reports';
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
            flash('error', 'Report storage folder could not be prepared.', 'danger');
            redirect('reports');
        }

        $filename = 'report_' . bin2hex(random_bytes(12)) . '.' . $allowed[$mime];
        $relativePath = 'uploads/reports/' . $filename;
        $target = $uploadDir . '/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $target)) {
            flash('error', 'Upload failed. Please try again.', 'danger');
            redirect('reports');
        }

        (new MedicalReport())->create([
            'patient_id' => Auth::id(),
            'report_type' => $reportType,
            'file_path' => $relativePath,
            'original_name' => Security::cleanString($file['name'] ?? 'Medical report'),
        ]);

        flash('success', 'Report uploaded successfully.', 'success');
        redirect('reports');
    }

    public function delete(): void
    {
        Auth::requireLogin(['patient']);
        if (!CSRF::validate($_POST['csrf_token'] ?? null)) {
            flash('error', 'Invalid security token.', 'danger');
            redirect('reports');
        }

        $reportId = Security::cleanInt($_POST['report_id'] ?? 0);
        $reportModel = new MedicalReport();
        $report = $reportModel->findForPatient($reportId, Auth::id());
        if (!$report) {
            flash('error', 'Report not found or access denied.', 'danger');
            redirect('reports');
        }

        if ($reportModel->deleteForPatient($reportId, Auth::id())) {
            $path = __DIR__ . '/../../public/' . ltrim((string) ($report['file_path'] ?? ''), '/');
            $base = realpath(__DIR__ . '/../../public/uploads/reports');
            $target = realpath($path);
            if ($base && $target && str_starts_with($target, $base) && is_file($target)) {
                @unlink($target);
            }
            flash('success', 'Report removed successfully.', 'success');
        } else {
            flash('error', 'Report could not be removed.', 'danger');
        }

        redirect('reports');
    }
}
