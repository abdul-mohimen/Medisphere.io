<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\CSRF;
use App\Core\Database;
use App\Core\Security;
use App\Core\View;
use App\Models\Appointment;
use App\Models\ConsultationFeedback;
use App\Models\ConsultationSession;
use App\Models\ConsultationSignal;
use App\Models\DoctorAvailability;
use App\Models\Doctor;
use App\Models\Hospital;
use App\Models\HospitalDoctorAssignment;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\SubscriptionAccess;

class ConsultationController
{
    public function index(): void
    {
        Auth::requireLogin(['patient', 'doctor', 'hospital', 'admin']);
        $role = Auth::type();
        $userId = Auth::id();
        $subscriptionAccess = new SubscriptionAccess();

        $sessionModel = new ConsultationSession();
        $appointments = [];
        $data = [
            'title' => __('consultations.title'),
            'role' => $role,
            'sessions' => in_array($role, ['patient', 'doctor'], true) ? $sessionModel->forUser($userId, $role) : [],
            'subscriptionLocked' => $role === 'patient' && $subscriptionAccess->shouldGatePatientFeature(),
            ...$subscriptionAccess->modalData(
                'Premium video consultations',
                'Subscribe to open video and voice rooms, use screen sharing, and keep consultation workflows connected to your care record.',
                route_url('consultations'),
                $role === 'patient' && isset($_GET['subscribe'])
            ),
        ];

        if ($role === 'patient') {
            $appointments = (new Appointment())->forPatient($userId);
            $data['appointments'] = $appointments;
        } elseif ($role === 'doctor') {
            $appointments = (new Appointment())->forDoctor($userId);
            $data['appointments'] = $appointments;
            $data['waitingSessions'] = $sessionModel->waitingForDoctor($userId);
            $data['availability'] = (new DoctorAvailability())->getStatus($userId);
        } elseif ($role === 'hospital') {
            $data['appointments'] = [];
            $data['waitingSessions'] = [];
        } elseif ($role === 'admin') {
            $data['message'] = 'Consultation monitoring is focused on patient and doctor workflows in this phase.';
        }

        $data['consultationContacts'] = $this->consultationContacts($role, $userId, $appointments);
        View::render('consultations', $data);
    }

    public function create(): void
    {
        Auth::requireLogin(['patient', 'doctor']);
        if (!CSRF::validate($_POST['csrf_token'] ?? null)) {
            flash('error', 'Invalid security token.', 'danger');
            redirect('consultations');
        }

        if (Auth::type() === 'patient' && (new SubscriptionAccess())->shouldGatePatientFeature()) {
            flash('error', 'A premium subscription is required to open video or voice consultation rooms.', 'warning');
            redirect('consultations', ['subscribe' => 'video']);
        }

        $appointmentId = Security::cleanInt($_POST['appointment_id'] ?? 0);
        $callType = Security::cleanString($_POST['call_type'] ?? 'video');
        $callType = in_array($callType, ['video', 'voice'], true) ? $callType : 'video';
        $consentRecording = isset($_POST['consent_recording']) ? 1 : 0;

        $appointment = (new Appointment())->find($appointmentId);
        if (!$appointment) {
            flash('error', 'Appointment not found.', 'danger');
            redirect('consultations');
        }

        $userId = Auth::id();
        $isParticipant = (int) $appointment['patient_id'] === $userId || (int) $appointment['doctor_id'] === $userId;
        if (!$isParticipant && Auth::type() !== 'admin') {
            flash('error', 'You do not have access to that appointment.', 'danger');
            redirect('consultations');
        }

        $existing = (new ConsultationSession())->findByAppointmentId($appointmentId);
        if ($existing) {
            redirect('consultations/room', ['id' => $existing['id']]);
        }

        $sessionId = (new ConsultationSession())->create([
            'appointment_id' => $appointmentId,
            'initiator_id' => $userId,
            'patient_id' => (int) $appointment['patient_id'],
            'doctor_id' => (int) $appointment['doctor_id'],
            'session_token' => bin2hex(random_bytes(16)),
            'room_name' => 'Room-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8)),
            'call_type' => $callType,
            'status' => 'waiting',
            'consent_recording' => $consentRecording,
        ]);

        $counterpartyId = $userId === (int) $appointment['patient_id'] ? (int) $appointment['doctor_id'] : (int) $appointment['patient_id'];
        $counterparty = (new User())->contactProfile($counterpartyId);
        $initiator = (new User())->contactProfile($userId);
        (new NotificationService())->sendToUser($counterpartyId, 'generic', [
            'name' => $counterparty['name'] ?? $counterparty['email'] ?? 'User',
            'platform_name' => config('app.name', 'MediSphere'),
        ], [
            'category' => 'appointment',
            'type' => 'info',
            'action_url' => route_url('consultations/room', ['id' => $sessionId]),
        ]);

        flash('success', 'Consultation room created. Waiting for ' . ($counterparty['name'] ?? 'participant') . ' to join.', 'success');
        redirect('consultations/room', ['id' => $sessionId]);
    }

    public function room(): void
    {
        Auth::requireLogin(['patient', 'doctor']);
        $id = Security::cleanInt($_GET['id'] ?? 0);
        $session = (new ConsultationSession())->find($id);
        if (!$session) {
            flash('error', 'Consultation session not found.', 'danger');
            redirect('consultations');
        }

        $userId = Auth::id();
        if (!in_array($userId, [(int) $session['patient_id'], (int) $session['doctor_id']], true)) {
            flash('error', 'You do not have access to this consultation room.', 'danger');
            redirect('consultations');
        }

        $subscriptionAccess = new SubscriptionAccess();
        $subscriptionLocked = $userId === (int) $session['patient_id'] && $subscriptionAccess->shouldGatePatientFeature();

        $feedbackModel = new ConsultationFeedback();
        View::render('consultation_room', [
            'title' => __('consultations.title') . ' Room',
            'session' => $session,
            'role' => Auth::type(),
            'selfRole' => $userId === (int) $session['doctor_id'] ? 'doctor' : 'patient',
            'feedback' => $feedbackModel->forSession($id),
            'myFeedback' => $feedbackModel->findBySessionAndReviewer($id, $userId),
            'subscriptionLocked' => $subscriptionLocked,
            ...$subscriptionAccess->modalData(
                'Premium video consultations',
                'Subscribe to open this consultation room and unlock premium telemedicine tools.',
                route_url('consultations/room', ['id' => (int) $session['id']]),
                false
            ),
        ]);
    }

    public function sessionInfoApi(): void
    {
        Auth::requireLogin(['patient', 'doctor']);
        header('Content-Type: application/json');
        $session = $this->authorizedSession(Security::cleanInt($_GET['session_id'] ?? 0));
        if (!$session) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Session not found']);
            return;
        }
        if (!$this->subscriptionAllowsSession($session)) {
            http_response_code(402);
            echo json_encode(['success' => false, 'message' => 'Premium subscription required for consultation media.']);
            return;
        }

        echo json_encode([
            'success' => true,
            'session' => $session,
            'participant_count' => (new ConsultationSession())->participantCount((int) $session['id']),
        ]);
    }

    public function signalsApi(): void
    {
        Auth::requireLogin(['patient', 'doctor']);
        header('Content-Type: application/json');
        $session = $this->authorizedSession(Security::cleanInt($_GET['session_id'] ?? 0));
        if (!$session) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Session not found']);
            return;
        }
        if (!$this->subscriptionAllowsSession($session)) {
            http_response_code(402);
            echo json_encode(['success' => false, 'message' => 'Premium subscription required for consultation media.']);
            return;
        }

        $afterId = Security::cleanInt($_GET['after_id'] ?? 0);
        $signals = (new ConsultationSignal())->fetchSince((int) $session['id'], $afterId, Auth::id());
        echo json_encode(['success' => true, 'signals' => $signals]);
    }

    public function sendSignal(): void
    {
        Auth::requireLogin(['patient', 'doctor']);
        header('Content-Type: application/json');
        if (!CSRF::validate($_POST['csrf_token'] ?? null)) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Invalid security token']);
            return;
        }

        $session = $this->authorizedSession(Security::cleanInt($_POST['session_id'] ?? 0));
        if (!$session) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Session not found']);
            return;
        }
        if (!$this->subscriptionAllowsSession($session)) {
            http_response_code(402);
            echo json_encode(['success' => false, 'message' => 'Premium subscription required for consultation media.']);
            return;
        }

        $signalType = Security::cleanString($_POST['signal_type'] ?? 'message');
        $allowed = ['presence', 'offer', 'answer', 'candidate', 'hangup', 'screen-share', 'status'];
        if (!in_array($signalType, $allowed, true)) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Invalid signal type']);
            return;
        }

        $id = (new ConsultationSignal())->create([
            'session_id' => (int) $session['id'],
            'sender_id' => Auth::id(),
            'signal_type' => $signalType,
            'payload' => trim((string) ($_POST['payload'] ?? '')),
        ]);

        if ($signalType === 'presence' && ($session['status'] ?? '') === 'waiting') {
            if ((new ConsultationSession())->participantCount((int) $session['id']) >= 2) {
                (new ConsultationSession())->updateStatus((int) $session['id'], 'active', date('Y-m-d H:i:s'));
            }
        }

        echo json_encode(['success' => true, 'id' => $id]);
    }

    public function end(): void
    {
        Auth::requireLogin(['patient', 'doctor']);
        if (!CSRF::validate($_POST['csrf_token'] ?? null)) {
            flash('error', 'Invalid security token.', 'danger');
            redirect('consultations');
        }

        $session = $this->authorizedSession(Security::cleanInt($_POST['session_id'] ?? 0));
        if (!$session) {
            flash('error', 'Session not found.', 'danger');
            redirect('consultations');
        }

        (new ConsultationSession())->updateStatus((int) $session['id'], 'ended', null, date('Y-m-d H:i:s'));
        (new ConsultationSignal())->create([
            'session_id' => (int) $session['id'],
            'sender_id' => Auth::id(),
            'signal_type' => 'hangup',
            'payload' => json_encode(['ended_by' => Auth::id()]),
        ]);

        $otherUserId = Auth::id() === (int) $session['patient_id'] ? (int) $session['doctor_id'] : (int) $session['patient_id'];
        (new NotificationService())->sendToUser($otherUserId, 'generic', [
            'name' => (new User())->contactProfile($otherUserId)['name'] ?? 'User',
            'platform_name' => config('app.name', 'MediSphere'),
        ], [
            'category' => 'appointment',
            'type' => 'info',
            'action_url' => route_url('consultations'),
        ]);

        flash('success', 'Consultation session ended.', 'success');
        redirect('consultations');
    }

    public function feedback(): void
    {
        Auth::requireLogin(['patient', 'doctor']);
        if (!CSRF::validate($_POST['csrf_token'] ?? null)) {
            flash('error', 'Invalid security token.', 'danger');
            redirect('consultations');
        }

        $session = $this->authorizedSession(Security::cleanInt($_POST['session_id'] ?? 0));
        if (!$session) {
            flash('error', 'Session not found.', 'danger');
            redirect('consultations');
        }

        $rating = max(1, min(5, Security::cleanInt($_POST['rating'] ?? 5)));
        $review = Security::cleanString($_POST['review_text'] ?? '');
        $feedbackModel = new ConsultationFeedback();
        if ($feedbackModel->findBySessionAndReviewer((int) $session['id'], Auth::id())) {
            flash('error', 'Feedback was already submitted for this session.', 'danger');
            redirect('consultations/room', ['id' => $session['id']]);
        }

        $feedbackModel->create([
            'session_id' => (int) $session['id'],
            'reviewer_id' => Auth::id(),
            'rating' => $rating,
            'review_text' => $review,
        ]);

        flash('success', 'Thank you for your consultation feedback.', 'success');
        redirect('consultations/room', ['id' => $session['id']]);
    }

    public function availability(): void
    {
        Auth::requireLogin(['doctor']);
        if (!CSRF::validate($_POST['csrf_token'] ?? null)) {
            flash('error', 'Invalid security token.', 'danger');
            redirect('consultations');
        }

        $status = Security::cleanString($_POST['availability_status'] ?? 'offline');
        $status = in_array($status, ['online', 'offline', 'busy'], true) ? $status : 'offline';
        (new DoctorAvailability())->upsert(Auth::id(), $status);
        flash('success', 'Availability updated.', 'success');
        redirect('consultations');
    }

    private function authorizedSession(int $sessionId): ?array
    {
        $session = (new ConsultationSession())->find($sessionId);
        if (!$session) {
            return null;
        }

        if (!in_array(Auth::id(), [(int) $session['patient_id'], (int) $session['doctor_id']], true)) {
            return null;
        }

        return $session;
    }

    private function subscriptionAllowsSession(array $session): bool
    {
        if (Auth::type() !== 'patient') {
            return true;
        }

        if ((int) ($session['patient_id'] ?? 0) !== (int) Auth::id()) {
            return true;
        }

        return !(new SubscriptionAccess())->shouldGatePatientFeature();
    }

    private function consultationContacts(string $role, int $userId, array $appointments = []): array
    {
        return match ($role) {
            'patient' => $this->patientConsultationContacts($appointments),
            'doctor' => $this->doctorConsultationContacts($userId, $appointments),
            'hospital' => $this->hospitalConsultationContacts($userId),
            default => [],
        };
    }

    private function patientConsultationContacts(array $appointments): array
    {
        $contacts = [];
        $seen = [];
        foreach ($appointments as $appointment) {
            $doctorId = (int) ($appointment['doctor_id'] ?? 0);
            if (!$doctorId || isset($seen[$doctorId]) || $this->isClosedAppointment($appointment)) {
                continue;
            }
            $seen[$doctorId] = true;
            $contacts[] = [
                'id' => $doctorId,
                'type' => 'doctor',
                'name' => 'Dr. ' . ($appointment['doctor_name'] ?? 'Doctor'),
                'subtitle' => $appointment['specialization'] ?? 'General care',
                'meta' => trim(($appointment['date'] ?? '') . ' at ' . ($appointment['time'] ?? '')),
                'phone' => '',
                'status' => $appointment['status'] ?? 'scheduled',
                'appointment_id' => (int) ($appointment['id'] ?? 0),
                'message_url' => route_url('messages', ['contact_id' => $doctorId]),
                'fallback_url' => route_url('appointments', ['book' => '1', 'doctor_id' => $doctorId]),
            ];
        }

        foreach ((new Doctor())->search(['active_only' => true, 'verified_only' => true]) as $doctor) {
            $doctorId = (int) ($doctor['user_id'] ?? 0);
            if (!$doctorId || isset($seen[$doctorId])) {
                continue;
            }
            $seen[$doctorId] = true;
            $contacts[] = [
                'id' => $doctorId,
                'type' => 'doctor',
                'name' => 'Dr. ' . ($doctor['name'] ?? 'Doctor'),
                'subtitle' => $doctor['specialization'] ?? 'General care',
                'meta' => $doctor['hospital_name'] ?? 'Independent clinic',
                'phone' => $doctor['hospital_phone'] ?? '',
                'status' => $doctor['availability_status'] ?? 'offline',
                'appointment_id' => 0,
                'message_url' => route_url('messages', ['contact_id' => $doctorId]),
                'fallback_url' => route_url('appointments', ['book' => '1', 'doctor_id' => $doctorId]),
            ];
            if (count($contacts) >= 8) {
                break;
            }
        }

        return $contacts;
    }

    private function doctorConsultationContacts(int $doctorUserId, array $appointments): array
    {
        $contacts = [];
        $seen = [];
        foreach ($appointments as $appointment) {
            $patientId = (int) ($appointment['patient_id'] ?? 0);
            if (!$patientId || isset($seen['patient-' . $patientId]) || $this->isClosedAppointment($appointment)) {
                continue;
            }
            $seen['patient-' . $patientId] = true;
            $contacts[] = [
                'id' => $patientId,
                'type' => 'patient',
                'name' => $appointment['patient_name'] ?? 'Patient',
                'subtitle' => 'Patient consultation',
                'meta' => trim(($appointment['date'] ?? '') . ' at ' . ($appointment['time'] ?? '')),
                'phone' => $appointment['phone'] ?? '',
                'status' => $appointment['status'] ?? 'scheduled',
                'appointment_id' => (int) ($appointment['id'] ?? 0),
                'message_url' => route_url('messages', ['contact_id' => $patientId]),
                'fallback_url' => route_url('messages', ['contact_id' => $patientId]),
            ];
            if (count($contacts) >= 8) {
                break;
            }
        }

        $doctor = (new Doctor())->findByUserId($doctorUserId);
        $hospitalUserId = (int) ($doctor['hospital_user_id'] ?? 0);
        if ($hospitalUserId > 0) {
            $contacts[] = [
                'id' => $hospitalUserId,
                'type' => 'hospital',
                'name' => $doctor['hospital_name'] ?? 'Hospital',
                'subtitle' => 'Hospital coordination',
                'meta' => trim(($doctor['hospital_city'] ?? '') . ', ' . ($doctor['hospital_country'] ?? ''), ', '),
                'phone' => $doctor['hospital_phone'] ?? '',
                'status' => 'care desk',
                'appointment_id' => 0,
                'message_url' => route_url('messages', ['contact_id' => $hospitalUserId]),
                'fallback_url' => route_url('messages', ['contact_id' => $hospitalUserId]),
            ];
        }

        return $contacts;
    }

    private function hospitalConsultationContacts(int $hospitalUserId): array
    {
        $hospital = (new Hospital())->findByUserId($hospitalUserId);
        if (!$hospital) {
            return [];
        }

        $contacts = [];
        $seen = [];
        foreach ((new HospitalDoctorAssignment())->byHospital((int) $hospital['id']) as $assignment) {
            if (($assignment['status'] ?? '') === 'suspended') {
                continue;
            }
            $doctorId = (int) ($assignment['doctor_id'] ?? 0);
            if (!$doctorId || isset($seen[$doctorId])) {
                continue;
            }
            $seen[$doctorId] = true;
            $contacts[] = [
                'id' => $doctorId,
                'type' => 'doctor',
                'name' => 'Dr. ' . ($assignment['doctor_name'] ?? 'Doctor'),
                'subtitle' => $assignment['specialization'] ?? 'Doctor',
                'meta' => ($assignment['department_name'] ?? '') ?: ($assignment['privileges'] ?? 'Hospital doctor'),
                'phone' => $hospital['phone'] ?? '',
                'status' => $assignment['status'] ?? 'active',
                'appointment_id' => 0,
                'message_url' => route_url('messages', ['contact_id' => $doctorId]),
                'fallback_url' => route_url('messages', ['contact_id' => $doctorId]),
            ];
        }

        if (!$contacts) {
            foreach ((new Doctor())->search(['hospital_id' => (int) $hospital['id'], 'active_only' => true]) as $doctor) {
                $doctorId = (int) ($doctor['user_id'] ?? 0);
                if (!$doctorId || isset($seen[$doctorId])) {
                    continue;
                }
                $seen[$doctorId] = true;
                $contacts[] = [
                    'id' => $doctorId,
                    'type' => 'doctor',
                    'name' => 'Dr. ' . ($doctor['name'] ?? 'Doctor'),
                    'subtitle' => $doctor['specialization'] ?? 'Doctor',
                    'meta' => $doctor['hospital_name'] ?? 'Hospital doctor',
                    'phone' => $doctor['hospital_phone'] ?? ($hospital['phone'] ?? ''),
                    'status' => $doctor['availability_status'] ?? 'offline',
                    'appointment_id' => 0,
                    'message_url' => route_url('messages', ['contact_id' => $doctorId]),
                    'fallback_url' => route_url('messages', ['contact_id' => $doctorId]),
                ];
                if (count($contacts) >= 8) {
                    break;
                }
            }
        }

        return $contacts;
    }

    private function isClosedAppointment(array $appointment): bool
    {
        $status = (string) ($appointment['status'] ?? '');
        return in_array($status, ['cancelled', 'completed'], true);
    }
}
