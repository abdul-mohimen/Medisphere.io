<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\CSRF;
use App\Core\Security;
use App\Core\View;
use App\Models\EmailTemplate;
use App\Models\Notification;
use App\Models\NotificationPreference;
use App\Models\SmsTemplate;

class NotificationController
{
    public function index(): void
    {
        Auth::requireLogin();
        $notifications = new Notification();
        $preferences = (new NotificationPreference())->forUser(Auth::id());

        View::render('notifications', [
            'title' => __('notifications.title'),
            'notifications' => $notifications->allForUser(Auth::id()),
            'unreadCount' => $notifications->unreadCount(Auth::id()),
            'preferences' => $preferences,
        ]);
    }

    public function api(): void
    {
        Auth::requireLogin();
        header('Content-Type: application/json');
        $notifications = new Notification();
        echo json_encode([
            'unread_count' => $notifications->unreadCount(Auth::id()),
            'notifications' => $notifications->recentForUser(Auth::id(), 6),
        ]);
    }

    public function markRead(): void
    {
        Auth::requireLogin();
        if (!CSRF::validate($_POST['csrf_token'] ?? null)) {
            flash('error', 'Invalid security token.', 'danger');
            redirect('notifications');
        }

        $id = Security::cleanInt($_POST['notification_id'] ?? 0);
        (new Notification())->markRead($id, Auth::id());
        flash('success', 'Notification marked as read.', 'success');
        redirect('notifications');
    }

    public function markAllRead(): void
    {
        Auth::requireLogin();
        if (!CSRF::validate($_POST['csrf_token'] ?? null)) {
            flash('error', 'Invalid security token.', 'danger');
            redirect('notifications');
        }

        (new Notification())->markAllRead(Auth::id());
        flash('success', 'All notifications marked as read.', 'success');
        redirect('notifications');
    }

    public function updatePreferences(): void
    {
        Auth::requireLogin();
        if (!CSRF::validate($_POST['csrf_token'] ?? null)) {
            flash('error', 'Invalid security token.', 'danger');
            redirect('notifications');
        }

        (new NotificationPreference())->updateForUser(Auth::id(), [
            'email_enabled' => isset($_POST['email_enabled']) ? 1 : 0,
            'sms_enabled' => isset($_POST['sms_enabled']) ? 1 : 0,
            'in_app_enabled' => isset($_POST['in_app_enabled']) ? 1 : 0,
            'appointment_updates' => isset($_POST['appointment_updates']) ? 1 : 0,
            'payment_updates' => isset($_POST['payment_updates']) ? 1 : 0,
            'security_updates' => isset($_POST['security_updates']) ? 1 : 0,
            'marketing_updates' => isset($_POST['marketing_updates']) ? 1 : 0,
            'preferred_language' => Security::cleanString($_POST['preferred_language'] ?? 'en'),
        ]);

        flash('success', 'Notification preferences updated.', 'success');
        redirect('notifications');
    }

    public function templates(): void
    {
        Auth::requireLogin(['admin']);
        View::render('notification_templates', [
            'title' => __('common.templates'),
            'emailTemplates' => (new EmailTemplate())->all(),
            'smsTemplates' => (new SmsTemplate())->all(),
        ]);
    }

    public function saveEmailTemplate(): void
    {
        Auth::requireLogin(['admin']);
        if (!CSRF::validate($_POST['csrf_token'] ?? null)) {
            flash('error', 'Invalid security token.', 'danger');
            redirect('admin/communications');
        }

        (new EmailTemplate())->upsert(
            Security::cleanString($_POST['template_key'] ?? ''),
            trim((string) ($_POST['subject'] ?? '')),
            trim((string) ($_POST['body_html'] ?? '')),
            trim((string) ($_POST['body_text'] ?? '')),
            Security::cleanString($_POST['status'] ?? 'active')
        );

        flash('success', 'Email template saved.', 'success');
        redirect('admin/communications');
    }

    public function saveSmsTemplate(): void
    {
        Auth::requireLogin(['admin']);
        if (!CSRF::validate($_POST['csrf_token'] ?? null)) {
            flash('error', 'Invalid security token.', 'danger');
            redirect('admin/communications');
        }

        (new SmsTemplate())->upsert(
            Security::cleanString($_POST['template_key'] ?? ''),
            trim((string) ($_POST['body_text'] ?? '')),
            Security::cleanString($_POST['status'] ?? 'active')
        );

        flash('success', 'SMS template saved.', 'success');
        redirect('admin/communications');
    }
}
