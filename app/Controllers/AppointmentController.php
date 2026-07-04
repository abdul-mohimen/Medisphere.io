<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\CSRF;
use App\Core\Security;
use App\Core\View;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Hospital;
use App\Services\NotificationService;

class AppointmentController
{
    public function index(): void
    {
        Auth::requireLogin(['patient', 'doctor', 'hospital', 'admin']);
        $role = Auth::type();
        $appointmentModel = new Appointment();
        $hospitalProfile = null;

        if ($role === 'doctor') {
            $appointments = $appointmentModel->forDoctor(Auth::id());
        } elseif ($role === 'hospital') {
            $hospitalProfile = (new Hospital())->findByUserId(Auth::id());
            $appointments = $hospitalProfile ? $appointmentModel->forHospital((int) $hospitalProfile['id']) : [];
        } elseif ($role === 'admin') {
            $appointments = $appointmentModel->allDetailed();
        } else {
            $appointments = $appointmentModel->forPatient(Auth::id());
        }

        View::render('appointments', [
            'title' => 'Appointments',
            'appointments' => $appointments,
            'doctors' => (new Doctor())->search(['active_only' => true]),
            'hospitals' => (new Hospital())->all(),
            'hospitalProfile' => $hospitalProfile,
            'role' => $role,
        ]);
    }

    public function create(): void
    {
        Auth::requireLogin(['patient']);
        if (!CSRF::validate($_POST['csrf_token'] ?? null)) {
            flash('error', 'Invalid security token.', 'danger');
            redirect('appointments');
        }

        $hospitalId = Security::cleanInt($_POST['hospital_id'] ?? 0);
        $doctorId = Security::cleanInt($_POST['doctor_id'] ?? 0);
        $date = Security::cleanString($_POST['date'] ?? '');
        $time = Security::cleanString($_POST['time'] ?? '');
        $symptoms = Security::cleanString($_POST['symptoms'] ?? '');

        if (!$doctorId || !$date || !$time) {
            flash('error', 'Doctor, date, and time are required.', 'danger');
            redirect('appointments');
        }

        $dateObject = \DateTimeImmutable::createFromFormat('Y-m-d', $date);
        if (!$dateObject || $dateObject->format('Y-m-d') !== $date || $date < date('Y-m-d')) {
            flash('error', 'Please select a valid upcoming appointment date.', 'danger');
            redirect('appointments');
        }

        if (!preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $time)) {
            flash('error', 'Please select a valid appointment time.', 'danger');
            redirect('appointments');
        }
        $timeForDb = $time . ':00';

        $doctorModel = new Doctor();
        $doctor = $doctorModel->findByUserId($doctorId);
        if (!$doctor || (string) ($doctor['user_status'] ?? 'active') !== 'active') {
            flash('error', 'Selected doctor is not available for booking.', 'danger');
            redirect('appointments');
        }

        if ($hospitalId > 0 && (int) ($doctor['hospital_id'] ?? 0) !== $hospitalId) {
            flash('error', 'Selected doctor is not linked with the selected hospital. Please choose a matching doctor.', 'danger');
            redirect('appointments');
        }

        $appointmentModel = new Appointment();
        if ($appointmentModel->existsForDoctorAt($doctorId, $date, $timeForDb)) {
            flash('error', 'That doctor already has an active appointment at this date and time. Please choose another slot.', 'danger');
            redirect('appointments');
        }

        $appointmentId = $appointmentModel->create([
            'patient_id' => Auth::id(),
            'doctor_id' => $doctorId,
            'date' => $date,
            'time' => $timeForDb,
            'status' => 'pending',
            'symptoms' => $symptoms,
        ]);

        $patientProfile = (new \App\Models\Patient())->findByUserId(Auth::id());
        $notificationService = new NotificationService();

        if ($doctor) {
            $notificationService->sendToUser($doctorId, 'appointment_created_doctor', [
                'patient_name' => $patientProfile['name'] ?? (Auth::user()['email'] ?? 'Patient'),
                'appointment_date' => $date,
                'appointment_time' => $time,
                'symptoms' => $symptoms ?: 'Not specified',
                'appointment_id' => $appointmentId,
            ], [
                'category' => 'appointment',
                'type' => 'info',
                'action_url' => route_url('appointments'),
            ]);
        }

        $notificationService->sendToUser(Auth::id(), 'appointment_created_patient', [
            'doctor_name' => $doctor['name'] ?? 'Doctor',
            'appointment_date' => $date,
            'appointment_time' => $time,
            'appointment_id' => $appointmentId,
        ], [
            'category' => 'appointment',
            'type' => 'success',
            'action_url' => route_url('appointments'),
        ]);

        flash('success', 'Appointment request submitted. It now appears in your appointments, the doctor queue, and the hospital dashboard when the doctor is linked to a hospital.', 'success');
        redirect('appointments');
    }

    public function update(): void
    {
        Auth::requireLogin(['doctor', 'admin']);
        if (!CSRF::validate($_POST['csrf_token'] ?? null)) {
            flash('error', 'Invalid security token.', 'danger');
            redirect('appointments');
        }

        $id = Security::cleanInt($_POST['appointment_id'] ?? 0);
        $status = Security::cleanString($_POST['status'] ?? 'pending');
        $diagnosis = Security::cleanString($_POST['diagnosis'] ?? '');
        if (!in_array($status, ['pending', 'confirmed', 'rescheduled', 'completed', 'cancelled'], true)) {
            flash('error', 'Invalid appointment status.', 'danger');
            redirect('appointments');
        }

        $appointmentModel = new Appointment();
        $appointment = $appointmentModel->find($id);
        if (!$appointment) {
            flash('error', 'Appointment not found.', 'danger');
            redirect('appointments');
        }

        if (Auth::type() === 'doctor' && (int) $appointment['doctor_id'] !== (int) Auth::id()) {
            flash('error', 'You can only update your own appointments.', 'danger');
            redirect('appointments');
        }

        $appointmentModel->updateStatus($id, $status, $diagnosis ?: null);

        $doctor = (new Doctor())->findByUserId((int) $appointment['doctor_id']);
        (new NotificationService())->sendToUser((int) $appointment['patient_id'], 'appointment_updated_patient', [
            'doctor_name' => $doctor['name'] ?? 'Doctor',
            'appointment_status' => ucfirst($status),
            'diagnosis' => $diagnosis ?: 'No additional diagnosis notes were added.',
            'appointment_id' => $id,
        ], [
            'category' => 'appointment',
            'type' => in_array($status, ['cancelled'], true) ? 'warning' : 'success',
            'action_url' => route_url('appointments'),
        ]);

        flash('success', 'Appointment updated.', 'success');
        redirect('appointments');
    }
}
