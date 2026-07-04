<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\CSRF;
use App\Core\Security;
use App\Core\View;
use App\Models\Hospital;
use App\Models\PatientRecordAccessLog;
use App\Models\PatientRecordConsent;
use App\Services\NotificationService;

class RecordConsentController
{
    public function index(): void
    {
        Auth::requireLogin(['patient']);
        View::render('record_consents', [
            'title' => 'Record Consents',
            'hospitals' => (new Hospital())->all(),
            'consents' => (new PatientRecordConsent())->byPatient(Auth::id()),
            'accessLogs' => (new PatientRecordAccessLog())->byPatient(Auth::id()),
        ]);
    }

    public function save(): void
    {
        Auth::requireLogin(['patient']);
        if (!CSRF::validate($_POST['csrf_token'] ?? null)) {
            flash('error', 'Invalid security token.', 'danger');
            redirect('record-consents');
        }

        $hospitalId = Security::cleanInt($_POST['hospital_id'] ?? 0);
        $scope = isset($_POST['scope']) ? implode(',', array_map('trim', (array) $_POST['scope'])) : '';
        if (!$hospitalId || $scope === '') {
            flash('error', 'Hospital and at least one record scope are required.', 'danger');
            redirect('record-consents');
        }

        $startsAt = Security::cleanString($_POST['starts_at'] ?? date('Y-m-d'));
        $expiresAt = Security::cleanString($_POST['expires_at'] ?? '');
        $notes = Security::cleanString($_POST['notes'] ?? '');

        $consentId = (new PatientRecordConsent())->create([
            'patient_id' => Auth::id(),
            'hospital_id' => $hospitalId,
            'scope' => $scope,
            'status' => 'active',
            'starts_at' => $startsAt,
            'expires_at' => $expiresAt,
            'notes' => $notes,
        ]);

        $hospital = null;
        foreach ((new Hospital())->all() as $item) {
            if ((int) $item['id'] === $hospitalId) {
                $hospital = $item;
                break;
            }
        }
        if ($hospital && !empty($hospital['user_id'])) {
            (new NotificationService())->sendToUser((int) $hospital['user_id'], 'generic', [
                'name' => $hospital['name'] ?? 'Hospital',
                'platform_name' => config('app.name', 'MediSphere'),
            ], [
                'category' => 'security',
                'type' => 'info',
                'action_url' => route_url('hospital-management'),
            ]);
        }

        flash('success', 'Patient record consent granted successfully.', 'success');
        redirect('record-consents');
    }

    public function revoke(): void
    {
        Auth::requireLogin(['patient']);
        if (!CSRF::validate($_POST['csrf_token'] ?? null)) {
            flash('error', 'Invalid security token.', 'danger');
            redirect('record-consents');
        }

        $consentId = Security::cleanInt($_POST['consent_id'] ?? 0);
        $consent = (new PatientRecordConsent())->find($consentId, Auth::id());
        if (!$consent) {
            flash('error', 'Consent not found.', 'danger');
            redirect('record-consents');
        }

        (new PatientRecordConsent())->revoke($consentId, Auth::id());
        flash('success', 'Consent revoked successfully.', 'success');
        redirect('record-consents');
    }
}
