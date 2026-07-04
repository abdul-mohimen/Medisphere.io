<?php
namespace App\Services;

use App\Models\EmailTemplate;
use App\Models\Notification;
use App\Models\NotificationPreference;
use App\Models\SmsTemplate;
use App\Models\User;

class NotificationService
{
    public function sendToUser(int $userId, string $templateKey, array $data = [], array $options = []): void
    {
        $user = (new User())->contactProfile($userId);
        if (!$user) {
            return;
        }

        $preferences = (new NotificationPreference())->forUser($userId);
        $category = $options['category'] ?? 'general';
        $actionUrl = $options['action_url'] ?? null;
        $type = $options['type'] ?? 'info';

        if (!$this->categoryEnabled($preferences, $category)) {
            return;
        }

        $payload = array_merge([
            'name' => $user['name'] ?? $user['email'],
            'email' => $user['email'] ?? '',
            'phone' => $user['phone'] ?? '',
            'platform_name' => config('app.name', 'MediSphere'),
            'currency' => config('app.currency', 'PKR'),
            'app_url' => config('app.base_url', ''),
        ], $data);

        [$subject, $html, $text] = $this->resolveEmailTemplate($templateKey, $payload);
        [$title, $message] = $this->resolveInAppCopy($templateKey, $payload, $subject, $text);

        if (!empty($preferences['in_app_enabled'])) {
            (new Notification())->create([
                'user_id' => $userId,
                'title' => $title,
                'message' => $message,
                'type' => $type,
                'channel' => 'in_app',
                'action_url' => $actionUrl,
                'metadata' => json_encode($payload),
            ]);
        }

        if (!empty($preferences['email_enabled']) && !empty($user['email'])) {
            (new Mailer())->send($user['email'], $subject, $html, $text);
        }

        if (!empty($preferences['sms_enabled']) && !empty($user['phone'])) {
            $smsText = $this->resolveSmsTemplate($templateKey, $payload, $message);
            (new SMSService())->send($user['phone'], $smsText);
        }
    }

    private function categoryEnabled(array $preferences, string $category): bool
    {
        return match ($category) {
            'appointment' => !empty($preferences['appointment_updates']),
            'payment' => !empty($preferences['payment_updates']),
            'security' => !empty($preferences['security_updates']),
            'marketing' => !empty($preferences['marketing_updates']),
            default => true,
        };
    }

    private function resolveEmailTemplate(string $templateKey, array $data): array
    {
        $template = (new EmailTemplate())->findByKey($templateKey);
        if ($template && ($template['status'] ?? 'active') === 'active') {
            return [
                $this->renderTemplate($template['subject'], $data),
                $this->renderTemplate($template['body_html'], $data),
                $this->renderTemplate($template['body_text'], $data),
            ];
        }

        $defaults = $this->defaultEmailTemplates();
        $fallback = $defaults[$templateKey] ?? $defaults['generic'];
        return [
            $this->renderTemplate($fallback['subject'], $data),
            $this->renderTemplate($fallback['html'], $data),
            $this->renderTemplate($fallback['text'], $data),
        ];
    }

    private function resolveSmsTemplate(string $templateKey, array $data, string $fallback): string
    {
        $template = (new SmsTemplate())->findByKey($templateKey);
        if ($template && ($template['status'] ?? 'active') === 'active') {
            return $this->renderTemplate($template['body_text'], $data);
        }

        $defaults = $this->defaultSmsTemplates();
        return $this->renderTemplate($defaults[$templateKey] ?? $fallback, $data);
    }

    private function resolveInAppCopy(string $templateKey, array $data, string $subject, string $text): array
    {
        $titles = [
            'appointment_created_doctor' => 'New appointment request',
            'appointment_created_patient' => 'Appointment request submitted',
            'appointment_updated_patient' => 'Appointment updated',
            'invoice_created_patient' => 'Invoice generated',
            'payment_paid_patient' => 'Payment received',
            'payment_pending_patient' => 'Payment initiated',
            'payment_paid_doctor' => 'Consultation payment received',
            'subscription_pending_patient' => 'Subscription checkout started',
            'subscription_active_patient' => 'Subscription active',
            'subscription_expired_patient' => 'Subscription expired',
            'refund_requested_admin' => 'Refund request submitted',
            'refund_updated_patient' => 'Refund request updated',
            'prescription_created_patient' => 'Prescription issued',
            'clinical_document_created_patient' => 'Clinical document issued',
            'doctor_verification_updated' => 'Doctor verification update',
            'hospital_verification_updated' => 'Hospital verification update',
        ];

        $title = $titles[$templateKey] ?? $subject;
        $message = trim(strip_tags($text));
        if ($message === '') {
            $message = $subject;
        }

        return [$title, $message];
    }

    private function renderTemplate(string $template, array $data): string
    {
        $replacements = [];
        foreach ($data as $key => $value) {
            $replacements['{{' . $key . '}}'] = (string) $value;
        }
        return strtr($template, $replacements);
    }

    private function defaultEmailTemplates(): array
    {
        return [
            'generic' => [
                'subject' => '{{platform_name}} Notification',
                'html' => '<p>Hello {{name}},</p><p>You have a new notification from {{platform_name}}.</p>',
                'text' => 'Hello {{name}}, You have a new notification from {{platform_name}}.',
            ],
            'appointment_created_doctor' => [
                'subject' => 'New appointment request from {{patient_name}}',
                'html' => '<p>Hello {{name}},</p><p>A new appointment request was submitted by <strong>{{patient_name}}</strong> for {{appointment_date}} at {{appointment_time}}.</p><p>Symptoms: {{symptoms}}</p>',
                'text' => 'New appointment request from {{patient_name}} for {{appointment_date}} at {{appointment_time}}. Symptoms: {{symptoms}}',
            ],
            'appointment_created_patient' => [
                'subject' => 'Appointment request submitted with Dr. {{doctor_name}}',
                'html' => '<p>Hello {{name}},</p><p>Your appointment request with <strong>Dr. {{doctor_name}}</strong> has been submitted for {{appointment_date}} at {{appointment_time}}.</p>',
                'text' => 'Your appointment request with Dr. {{doctor_name}} has been submitted for {{appointment_date}} at {{appointment_time}}.',
            ],
            'appointment_updated_patient' => [
                'subject' => 'Appointment status changed to {{appointment_status}}',
                'html' => '<p>Hello {{name}},</p><p>Your appointment with <strong>Dr. {{doctor_name}}</strong> is now <strong>{{appointment_status}}</strong>.</p><p>{{diagnosis}}</p>',
                'text' => 'Your appointment with Dr. {{doctor_name}} is now {{appointment_status}}. {{diagnosis}}',
            ],
            'invoice_created_patient' => [
                'subject' => 'Invoice {{invoice_number}} is ready',
                'html' => '<p>Hello {{name}},</p><p>Your invoice <strong>{{invoice_number}}</strong> for {{currency}} {{amount}} has been generated.</p><p>You can pay it from your billing center.</p>',
                'text' => 'Your invoice {{invoice_number}} for {{currency}} {{amount}} has been generated.',
            ],
            'payment_paid_patient' => [
                'subject' => 'Payment confirmed: {{transaction_reference}}',
                'html' => '<p>Hello {{name}},</p><p>Your payment <strong>{{transaction_reference}}</strong> for {{currency}} {{amount}} was completed successfully.</p>',
                'text' => 'Your payment {{transaction_reference}} for {{currency}} {{amount}} was completed successfully.',
            ],
            'payment_pending_patient' => [
                'subject' => 'Payment initiated: {{transaction_reference}}',
                'html' => '<p>Hello {{name}},</p><p>Your payment request <strong>{{transaction_reference}}</strong> has been created. Complete the provider flow to finish payment.</p>',
                'text' => 'Your payment request {{transaction_reference}} has been created. Complete the provider flow to finish payment.',
            ],
            'payment_paid_doctor' => [
                'subject' => 'Payment received for appointment {{appointment_id}}',
                'html' => '<p>Hello {{name}},</p><p>You received a payment of {{currency}} {{amount}} from {{patient_name}}.</p>',
                'text' => 'You received a payment of {{currency}} {{amount}} from {{patient_name}}.',
            ],
            'subscription_pending_patient' => [
                'subject' => 'Complete your {{plan_name}} subscription checkout',
                'html' => '<p>Hello {{name}},</p><p>Your {{platform_name}} subscription checkout for <strong>{{plan_name}}</strong> has started.</p><p>Reference: {{transaction_reference}}. Amount: {{currency}} {{amount}}.</p>',
                'text' => 'Your {{platform_name}} subscription checkout for {{plan_name}} has started. Reference: {{transaction_reference}}. Amount: {{currency}} {{amount}}.',
            ],
            'subscription_active_patient' => [
                'subject' => 'Your {{plan_name}} subscription is active',
                'html' => '<p>Hello {{name}},</p><p>Your <strong>{{plan_name}}</strong> subscription is active. Premium features are unlocked until {{expires_at}}.</p><p>Reference: {{transaction_reference}}.</p>',
                'text' => 'Your {{plan_name}} subscription is active. Premium features are unlocked until {{expires_at}}. Reference: {{transaction_reference}}.',
            ],
            'subscription_expired_patient' => [
                'subject' => 'Your {{plan_name}} subscription has expired',
                'html' => '<p>Hello {{name}},</p><p>Your <strong>{{plan_name}}</strong> subscription expired on {{expired_at}}. Premium features are locked until you renew.</p>',
                'text' => 'Your {{plan_name}} subscription expired on {{expired_at}}. Premium features are locked until you renew.',
            ],
            'refund_requested_admin' => [
                'subject' => 'Refund requested for {{transaction_reference}}',
                'html' => '<p>Hello Admin,</p><p>A refund was requested by {{requester_name}} for transaction <strong>{{transaction_reference}}</strong>.</p><p>Reason: {{reason}}</p>',
                'text' => 'Refund requested by {{requester_name}} for {{transaction_reference}}. Reason: {{reason}}',
            ],
            'refund_updated_patient' => [
                'subject' => 'Refund request {{refund_status}}',
                'html' => '<p>Hello {{name}},</p><p>Your refund request for {{transaction_reference}} has been updated to <strong>{{refund_status}}</strong>.</p>',
                'text' => 'Your refund request for {{transaction_reference}} is now {{refund_status}}.',
            ],
            'prescription_created_patient' => [
                'subject' => 'New prescription from Dr. {{doctor_name}}',
                'html' => '<p>Hello {{name}},</p><p>A new prescription titled <strong>{{prescription_title}}</strong> has been issued by <strong>Dr. {{doctor_name}}</strong>.</p>',
                'text' => 'A new prescription {{prescription_title}} has been issued by Dr. {{doctor_name}}.',
            ],
            'clinical_document_created_patient' => [
                'subject' => 'New clinical document: {{document_title}}',
                'html' => '<p>Hello {{name}},</p><p>A new clinical document <strong>{{document_title}}</strong> has been issued by <strong>Dr. {{doctor_name}}</strong>.</p>',
                'text' => 'A new clinical document {{document_title}} has been issued by Dr. {{doctor_name}}.',
            ],
            'doctor_verification_updated' => [
                'subject' => 'Doctor verification status: {{verification_status}}',
                'html' => '<p>Hello {{name}},</p><p>Your doctor verification status is now <strong>{{verification_status}}</strong>.</p>',
                'text' => 'Your doctor verification status is now {{verification_status}}.',
            ],
            'hospital_verification_updated' => [
                'subject' => 'Hospital verification status: {{verification_status}}',
                'html' => '<p>Hello {{name}},</p><p>Your hospital verification status is now <strong>{{verification_status}}</strong>.</p>',
                'text' => 'Your hospital verification status is now {{verification_status}}.',
            ],
        ];
    }

    private function defaultSmsTemplates(): array
    {
        return [
            'appointment_created_doctor' => 'New appointment from {{patient_name}} on {{appointment_date}} {{appointment_time}}.',
            'appointment_created_patient' => 'Your appointment request with Dr. {{doctor_name}} was submitted.',
            'appointment_updated_patient' => 'Appointment with Dr. {{doctor_name}} is now {{appointment_status}}.',
            'invoice_created_patient' => 'Invoice {{invoice_number}} for {{currency}} {{amount}} is ready in {{platform_name}}.',
            'payment_paid_patient' => 'Payment {{transaction_reference}} of {{currency}} {{amount}} completed.',
            'payment_pending_patient' => 'Payment {{transaction_reference}} initiated. Complete the provider flow.',
            'payment_paid_doctor' => 'Payment of {{currency}} {{amount}} received from {{patient_name}}.',
            'subscription_pending_patient' => '{{plan_name}} subscription checkout started. Reference {{transaction_reference}}.',
            'subscription_active_patient' => '{{plan_name}} subscription active until {{expires_at}}.',
            'subscription_expired_patient' => '{{plan_name}} subscription expired on {{expired_at}}. Renew to unlock premium features.',
            'refund_requested_admin' => 'Refund requested for {{transaction_reference}} by {{requester_name}}.',
            'refund_updated_patient' => 'Refund request for {{transaction_reference}} is {{refund_status}}.',
            'prescription_created_patient' => 'New prescription {{prescription_title}} from Dr. {{doctor_name}}.',
            'clinical_document_created_patient' => 'New clinical document {{document_title}} from Dr. {{doctor_name}}.',
            'doctor_verification_updated' => 'Doctor verification: {{verification_status}}.',
            'hospital_verification_updated' => 'Hospital verification: {{verification_status}}.',
        ];
    }
}
