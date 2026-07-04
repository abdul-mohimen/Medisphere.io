<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\CSRF;
use App\Core\Security;
use App\Core\View;
use App\Models\AdminLog;
use App\Models\Doctor;
use App\Models\Hospital;
use App\Models\User;
use App\Services\NotificationService;

class AdminController
{
    public function verifications(): void
    {
        Auth::requireLogin(['admin']);
        View::render('admin_verifications', [
            'title' => 'Verification Center',
            'pendingDoctors' => (new Doctor())->pending(),
            'pendingHospitals' => (new Hospital())->pending(),
            'users' => (new User())->all(),
        ]);
    }

    public function verifyDoctor(): void
    {
        Auth::requireLogin(['admin']);
        if (!CSRF::validate($_POST['csrf_token'] ?? null)) {
            flash('error', 'Invalid security token.', 'danger');
            redirect('admin/verifications');
        }

        $userId = Security::cleanInt($_POST['user_id'] ?? 0);
        $status = Security::cleanString($_POST['status'] ?? 'rejected');

        (new Doctor())->verify($userId, $status);
        (new User())->updateStatus($userId, $status === 'verified' ? 'active' : 'suspended');
        (new AdminLog())->add(Auth::id(), "Doctor verification updated for user {$userId}: {$status}");
        (new NotificationService())->sendToUser($userId, 'doctor_verification_updated', [
            'verification_status' => ucfirst($status),
        ], [
            'category' => 'security',
            'type' => $status === 'verified' ? 'success' : 'warning',
            'action_url' => route_url('login'),
        ]);
        flash('success', 'Doctor verification updated.', 'success');
        redirect('admin/verifications');
    }

    public function verifyHospital(): void
    {
        Auth::requireLogin(['admin']);
        if (!CSRF::validate($_POST['csrf_token'] ?? null)) {
            flash('error', 'Invalid security token.', 'danger');
            redirect('admin/verifications');
        }

        $userId = Security::cleanInt($_POST['user_id'] ?? 0);
        $status = Security::cleanString($_POST['status'] ?? 'rejected');

        (new Hospital())->verifyByUserId($userId, $status);
        (new User())->updateStatus($userId, $status === 'verified' ? 'active' : 'suspended');
        (new AdminLog())->add(Auth::id(), "Hospital verification updated for user {$userId}: {$status}");
        (new NotificationService())->sendToUser($userId, 'hospital_verification_updated', [
            'verification_status' => ucfirst($status),
        ], [
            'category' => 'security',
            'type' => $status === 'verified' ? 'success' : 'warning',
            'action_url' => route_url('login'),
        ]);
        flash('success', 'Hospital verification updated.', 'success');
        redirect('admin/verifications');
    }
}
