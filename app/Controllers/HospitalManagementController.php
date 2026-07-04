<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\CSRF;
use App\Core\Security;
use App\Core\View;
use App\Models\Appointment;
use App\Models\ClinicalDocument;
use App\Models\DiseaseScan;
use App\Models\Doctor;
use App\Models\DoctorHospitalRequest;
use App\Models\Hospital;
use App\Models\HospitalBedUnit;
use App\Models\HospitalDepartment;
use App\Models\HospitalDoctorAssignment;
use App\Models\HospitalInventoryItem;
use App\Models\MedicalReport;
use App\Models\Patient;
use App\Models\PatientRecordAccessLog;
use App\Models\PatientRecordConsent;
use App\Models\Prescription;
use App\Services\NotificationService;

class HospitalManagementController
{
    public function index(): void
    {
        Auth::requireLogin(['hospital']);
        $hospital = $this->hospitalOrRedirect();
        if (!$hospital) {
            return;
        }

        $departmentModel = new HospitalDepartment();
        $inventoryModel = new HospitalInventoryItem();
        $assignmentModel = new HospitalDoctorAssignment();
        $requestModel = new DoctorHospitalRequest();
        $bedModel = new HospitalBedUnit();
        $consentModel = new PatientRecordConsent();
        $appointmentModel = new Appointment();
        $hospitalId = (int) $hospital['id'];

        $departments = $departmentModel->allByHospital($hospitalId);
        $assignments = $assignmentModel->byHospital($hospitalId);
        $doctorDirectory = (new Doctor())->search(['hospital_id' => $hospitalId]);
        $recentPatients = $appointmentModel->recentPatientsForHospital($hospitalId);
        $bedStats = $bedModel->occupancyStats($hospitalId);
        $appointmentStats = $appointmentModel->statsForHospital($hospitalId);

        View::render('hospital_management', [
            'title' => 'Hospital Management',
            'hospital' => $hospital,
            'departments' => $departments,
            'inventoryItems' => $inventoryModel->allByHospital($hospitalId),
            'assignments' => $assignments,
            'pendingRequests' => $requestModel->byHospital($hospitalId),
            'bedUnits' => $bedModel->allByHospital($hospitalId),
            'consents' => $consentModel->activeByHospital($hospitalId),
            'accessLogs' => (new PatientRecordAccessLog())->byHospital($hospitalId),
            'appointmentStats' => $appointmentStats,
            'bedStats' => $bedStats,
            'stats' => [
                'departments' => $departmentModel->countByHospital($hospitalId),
                'inventory' => $inventoryModel->countByHospital($hospitalId),
                'maintenance_due' => $inventoryModel->maintenanceDueCount($hospitalId),
                'assignments' => $assignmentModel->countByHospital($hospitalId),
                'pending_requests' => $requestModel->pendingCountByHospital($hospitalId),
                'active_consents' => $consentModel->countActiveByHospital($hospitalId),
                'bed_total' => (int) ($bedStats['total'] ?? 0),
                'bed_occupied' => (int) ($bedStats['occupied'] ?? 0),
                'unique_patients' => (int) ($appointmentStats['unique_patients'] ?? 0),
                'monthly_appointments' => (int) ($appointmentStats['monthly_appointments'] ?? 0),
            ],
            'doctorOptions' => array_map(fn(array $row) => [
                'id' => (int) $row['user_id'],
                'name' => $row['name'],
            ], $doctorDirectory),
            'patientOptions' => $recentPatients,
        ]);
    }

    public function saveDepartment(): void
    {
        Auth::requireLogin(['hospital']);
        $hospital = $this->hospitalOrRedirect();
        if (!$hospital || !$this->validateCsrf('hospital-management')) {
            return;
        }

        $departmentId = Security::cleanInt($_POST['department_id'] ?? 0);
        $name = Security::cleanString($_POST['name'] ?? '');
        if ($name === '') {
            flash('error', 'Department name is required.', 'danger');
            redirect('hospital-management');
        }

        $payload = [
            'name' => $name,
            'description' => Security::cleanString($_POST['description'] ?? ''),
            'head_doctor_id' => Security::cleanInt($_POST['head_doctor_id'] ?? 0),
            'timings' => Security::cleanString($_POST['timings'] ?? ''),
            'status' => Security::cleanString($_POST['status'] ?? 'active'),
        ];

        $model = new HospitalDepartment();
        if ($departmentId) {
            $model->update($departmentId, (int) $hospital['id'], $payload);
            flash('success', 'Department updated successfully.', 'success');
        } else {
            $model->create(array_merge($payload, ['hospital_id' => (int) $hospital['id']]));
            flash('success', 'Department created successfully.', 'success');
        }
        redirect('hospital-management');
    }

    public function saveInventory(): void
    {
        Auth::requireLogin(['hospital']);
        $hospital = $this->hospitalOrRedirect();
        if (!$hospital || !$this->validateCsrf('hospital-management')) {
            return;
        }

        $itemId = Security::cleanInt($_POST['item_id'] ?? 0);
        $itemName = Security::cleanString($_POST['item_name'] ?? '');
        if ($itemName === '') {
            flash('error', 'Inventory item name is required.', 'danger');
            redirect('hospital-management');
        }

        $payload = [
            'item_name' => $itemName,
            'category' => Security::cleanString($_POST['category'] ?? ''),
            'quantity' => Security::cleanInt($_POST['quantity'] ?? 0),
            'status' => Security::cleanString($_POST['status'] ?? 'available'),
            'next_maintenance' => Security::cleanString($_POST['next_maintenance'] ?? ''),
            'notes' => Security::cleanString($_POST['notes'] ?? ''),
        ];

        $model = new HospitalInventoryItem();
        if ($itemId) {
            $model->update($itemId, (int) $hospital['id'], $payload);
            flash('success', 'Inventory item updated.', 'success');
        } else {
            $model->create(array_merge($payload, ['hospital_id' => (int) $hospital['id']]));
            flash('success', 'Inventory item created.', 'success');
        }
        redirect('hospital-management');
    }

    public function saveBed(): void
    {
        Auth::requireLogin(['hospital']);
        $hospital = $this->hospitalOrRedirect();
        if (!$hospital || !$this->validateCsrf('hospital-management')) {
            return;
        }

        $bedId = Security::cleanInt($_POST['bed_id'] ?? 0);
        $wardName = Security::cleanString($_POST['ward_name'] ?? '');
        $bedLabel = Security::cleanString($_POST['bed_label'] ?? '');
        if ($wardName === '' || $bedLabel === '') {
            flash('error', 'Ward name and bed label are required.', 'danger');
            redirect('hospital-management');
        }

        $payload = [
            'ward_name' => $wardName,
            'bed_label' => $bedLabel,
            'bed_type' => Security::cleanString($_POST['bed_type'] ?? ''),
            'occupancy_status' => Security::cleanString($_POST['occupancy_status'] ?? 'available'),
            'assigned_patient_id' => Security::cleanInt($_POST['assigned_patient_id'] ?? 0),
            'notes' => Security::cleanString($_POST['notes'] ?? ''),
        ];

        $model = new HospitalBedUnit();
        if ($bedId) {
            $model->update($bedId, (int) $hospital['id'], $payload);
            flash('success', 'Bed unit updated successfully.', 'success');
        } else {
            $model->create(array_merge($payload, ['hospital_id' => (int) $hospital['id']]));
            flash('success', 'Bed unit created successfully.', 'success');
        }
        redirect('hospital-management');
    }

    public function updateRequest(): void
    {
        Auth::requireLogin(['hospital']);
        $hospital = $this->hospitalOrRedirect();
        if (!$hospital || !$this->validateCsrf('hospital-management')) {
            return;
        }

        $requestId = Security::cleanInt($_POST['request_id'] ?? 0);
        $status = Security::cleanString($_POST['status'] ?? 'rejected');
        if (!in_array($status, ['approved', 'rejected'], true)) {
            flash('error', 'Invalid request action.', 'danger');
            redirect('hospital-management');
        }

        $requestModel = new DoctorHospitalRequest();
        $request = $requestModel->find($requestId, (int) $hospital['id']);
        if (!$request) {
            flash('error', 'Affiliation request not found.', 'danger');
            redirect('hospital-management');
        }

        $requestModel->updateStatus($requestId, (int) $hospital['id'], $status);

        if ($status === 'approved') {
            $departmentId = Security::cleanInt($_POST['department_id'] ?? 0);
            $privileges = Security::cleanString($_POST['privileges'] ?? 'Standard clinical access');
            (new HospitalDoctorAssignment())->upsert([
                'hospital_id' => (int) $hospital['id'],
                'doctor_id' => (int) $request['doctor_id'],
                'department_id' => $departmentId,
                'privileges' => $privileges,
                'status' => 'active',
            ]);
            (new Doctor())->updatePrimaryHospital((int) $request['doctor_id'], (int) $hospital['id']);
        }

        (new NotificationService())->sendToUser((int) $request['doctor_id'], 'generic', [
            'name' => (new Doctor())->findByUserId((int) $request['doctor_id'])['name'] ?? 'Doctor',
            'platform_name' => config('app.name', 'MediSphere'),
        ], [
            'category' => 'appointment',
            'type' => $status === 'approved' ? 'success' : 'warning',
            'action_url' => route_url('dashboard'),
        ]);

        flash('success', 'Affiliation request updated.', 'success');
        redirect('hospital-management');
    }

    public function updateAssignment(): void
    {
        Auth::requireLogin(['hospital']);
        $hospital = $this->hospitalOrRedirect();
        if (!$hospital || !$this->validateCsrf('hospital-management')) {
            return;
        }

        $assignmentId = Security::cleanInt($_POST['assignment_id'] ?? 0);
        $assignmentModel = new HospitalDoctorAssignment();
        $assignment = $assignmentModel->find($assignmentId, (int) $hospital['id']);
        if (!$assignment) {
            flash('error', 'Doctor assignment not found.', 'danger');
            redirect('hospital-management');
        }

        $assignmentModel->update($assignmentId, (int) $hospital['id'], [
            'department_id' => Security::cleanInt($_POST['department_id'] ?? 0),
            'privileges' => Security::cleanString($_POST['privileges'] ?? ''),
            'status' => Security::cleanString($_POST['status'] ?? 'active'),
        ]);

        flash('success', 'Doctor assignment updated.', 'success');
        redirect('hospital-management');
    }

    public function patientRecord(): void
    {
        Auth::requireLogin(['hospital']);
        $hospital = $this->hospitalOrRedirect();
        if (!$hospital) {
            return;
        }

        $patientId = Security::cleanInt($_GET['patient_id'] ?? 0);
        $consent = (new PatientRecordConsent())->activeForHospitalPatient((int) $hospital['id'], $patientId);
        if (!$consent) {
            flash('error', 'No active patient consent exists for that record access.', 'danger');
            redirect('hospital-management');
        }

        $scopes = array_map('trim', explode(',', (string) $consent['scope']));
        $patient = (new Patient())->findByUserId($patientId);
        if (!$patient) {
            flash('error', 'Patient profile not found.', 'danger');
            redirect('hospital-management');
        }

        (new PatientRecordAccessLog())->create([
            'hospital_id' => (int) $hospital['id'],
            'patient_id' => $patientId,
            'consent_id' => (int) $consent['id'],
            'accessed_by_user_id' => Auth::id(),
            'access_type' => 'view_summary',
            'context_data' => json_encode(['scopes' => $scopes]),
        ]);

        View::render('hospital_patient_record', [
            'title' => 'Patient Record Access',
            'hospital' => $hospital,
            'patient' => $patient,
            'consent' => $consent,
            'scopes' => $scopes,
            'reports' => in_array('reports', $scopes, true) ? (new MedicalReport())->byPatient($patientId) : [],
            'prescriptions' => in_array('prescriptions', $scopes, true) ? (new Prescription())->byPatient($patientId) : [],
            'documents' => in_array('documents', $scopes, true) ? (new ClinicalDocument())->byPatient($patientId) : [],
            'appointments' => in_array('appointments', $scopes, true) ? (new Appointment())->forPatient($patientId) : [],
            'scans' => in_array('scans', $scopes, true) ? (new DiseaseScan())->allByPatient($patientId) : [],
        ]);
    }

    private function hospitalOrRedirect(): ?array
    {
        $hospital = (new Hospital())->findByUserId(Auth::id());
        if (!$hospital) {
            flash('error', 'Hospital profile not found.', 'danger');
            redirect('dashboard');
        }
        return $hospital;
    }

    private function validateCsrf(string $route): bool
    {
        if (!CSRF::validate($_POST['csrf_token'] ?? null)) {
            flash('error', 'Invalid security token.', 'danger');
            redirect($route);
            return false;
        }
        return true;
    }
}
