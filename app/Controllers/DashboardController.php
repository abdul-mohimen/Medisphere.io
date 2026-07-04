<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\Appointment;
use App\Models\ClinicalDocument;
use App\Models\DataPrivacyRequest;
use App\Models\ComplianceIncident;
use App\Models\DiseaseScan;
use App\Models\ConsultationSession;
use App\Models\Doctor;
use App\Models\DoctorHospitalRequest;
use App\Models\Hospital;
use App\Models\HospitalDepartment;
use App\Models\HospitalDoctorAssignment;
use App\Models\HospitalInventoryItem;
use App\Models\Invoice;
use App\Models\MedicalReport;
use App\Models\Patient;
use App\Models\PatientProfileShare;
use App\Models\PatientHistoryEvent;
use App\Models\PatientHealthMetric;
use App\Models\PatientFamilyHistory;
use App\Models\PatientInsuranceProfile;
use App\Models\PatientEmergencyContact;
use App\Models\PatientMedication;
use App\Models\PatientAllergy;
use App\Models\Payment;
use App\Models\Prescription;
use App\Models\RefundRequest;
use App\Models\User;
use App\Services\SubscriptionAccess;

class DashboardController
{
    public function index(): void
    {
        Auth::requireLogin();
        $role = Auth::type();
        $userId = Auth::id();

        $data = [
            'title' => ucfirst((string) $role) . ' Dashboard',
            'role' => $role,
            'authUser' => Auth::user(),
        ];

        if ($role === 'patient') {
            $appointmentModel = new Appointment();
            $invoiceModel = new Invoice();
            $paymentModel = new Payment();
            $subscriptionAccess = new SubscriptionAccess();
            $data['profile'] = (new Patient())->findByUserId($userId);
            $data['appointmentStats'] = $appointmentModel->statsForPatient($userId);
            $data['appointments'] = $appointmentModel->forPatient($userId);
            $data['reportsCount'] = (new MedicalReport())->countByPatient($userId);
            $data['scanCount'] = (new DiseaseScan())->countByPatient($userId);
            $data['recentScans'] = (new DiseaseScan())->recentByPatient($userId);
            $data['billingStats'] = [
                'paid_total' => $paymentModel->paidTotalByPayer($userId),
                'unpaid_invoices' => $invoiceModel->countUnpaidByUser($userId),
            ];
            $data['consultationCount'] = count((new ConsultationSession())->forUser($userId, 'patient'));
            $data['prescriptionCount'] = (new Prescription())->countByPatient($userId);
            $data['clinicalDocumentCount'] = (new ClinicalDocument())->countByPatient($userId);
            $data['patientAccessLevel'] = $subscriptionAccess->accessLevel($userId);
            $data['patientCurrentSubscription'] = $subscriptionAccess->currentSubscription($userId);
            $data['patientCanUseBasic'] = $subscriptionAccess->canAccessLevel('basic', $userId);
            $data['patientCanUsePremium'] = $subscriptionAccess->canAccessLevel('premium', $userId);
            $data = array_merge($data, $subscriptionAccess->modalData(
                'Premium patient care tools',
                'Upgrade to unlock AI scanning, advanced map routing, premium education, and richer clinical guidance from the patient dashboard.',
                route_url('dashboard'),
                false
            ));
        } elseif ($role === 'doctor') {
            $appointmentModel = new Appointment();
            $paymentModel = new Payment();
            $data['profile'] = (new Doctor())->findByUserId($userId);
            $data['todayAppointments'] = $appointmentModel->todayForDoctor($userId);
            $data['appointments'] = $appointmentModel->forDoctor($userId);
            $data['affiliationRequests'] = (new DoctorHospitalRequest())->byDoctor($userId);
            $data['paymentStats'] = $paymentModel->statsForDoctor($userId);
            $data['consultationCount'] = count((new ConsultationSession())->forUser($userId, 'doctor'));
            $data['prescriptionCount'] = (new Prescription())->countByDoctor($userId);
            $data['clinicalDocumentCount'] = (new ClinicalDocument())->countByDoctor($userId);
        } elseif ($role === 'hospital') {
            $hospital = (new Hospital())->findByUserId($userId);
            $doctors = (new Doctor())->search(['hospital_id' => $hospital['id'] ?? 0]);
            $data['profile'] = $hospital;
            $data['hospitalDoctors'] = $doctors;
            $data['hospitalStats'] = [
                'departments' => $hospital ? (new HospitalDepartment())->countByHospital((int) $hospital['id']) : 0,
                'inventory' => $hospital ? (new HospitalInventoryItem())->countByHospital((int) $hospital['id']) : 0,
                'maintenance_due' => $hospital ? (new HospitalInventoryItem())->maintenanceDueCount((int) $hospital['id']) : 0,
                'assignments' => $hospital ? (new HospitalDoctorAssignment())->countByHospital((int) $hospital['id']) : 0,
                'pending_requests' => $hospital ? (new DoctorHospitalRequest())->pendingCountByHospital((int) $hospital['id']) : 0,
            ];
        } elseif ($role === 'admin') {
            $paymentModel = new Payment();
            $data['userCounts'] = (new User())->countByType();
            $data['appointmentStats'] = (new Appointment())->statsOverall();
            $data['pendingDoctors'] = (new Doctor())->pending();
            $data['pendingHospitals'] = (new Hospital())->pending();
            $data['paymentStats'] = $paymentModel->statsOverall();
            $data['refundPendingCount'] = (new RefundRequest())->countPending();
            $data['complianceStats'] = [
                'pending_requests' => (new DataPrivacyRequest())->pendingCount(),
                'open_incidents' => (new ComplianceIncident())->openCount(),
            ];
        }

        View::render('dashboard', $data);
    }

    public function profile(): void
    {
        Auth::requireLogin();
        $role = Auth::type();
        $userId = Auth::id();

        $profile = null;
        if ($role === 'patient') {
            $profile = (new Patient())->findByUserId($userId);
            View::render('health_profile', [
                'title' => 'Health Profile',
                'role' => $role,
                'authUser' => Auth::user(),
                'profile' => $profile,
                'historyEvents' => (new PatientHistoryEvent())->byPatient($userId),
                'allergies' => (new PatientAllergy())->byPatient($userId),
                'medications' => (new PatientMedication())->byPatient($userId),
                'emergencyContact' => (new PatientEmergencyContact())->findByPatient($userId),
                'insurance' => (new PatientInsuranceProfile())->findByPatient($userId),
                'familyHistory' => (new PatientFamilyHistory())->byPatient($userId),
                'healthMetrics' => (new PatientHealthMetric())->byPatient($userId),
                'profileShare' => (new PatientProfileShare())->getOrCreate($userId),
            ]);
            return;
        } elseif ($role === 'doctor') {
            $profile = (new Doctor())->findByUserId($userId);
        } elseif ($role === 'hospital') {
            $profile = (new Hospital())->findByUserId($userId);
        }

        View::render('profile', [
            'title' => 'Professional Profile',
            'role' => $role,
            'profile' => $profile,
        ]);
    }
}
