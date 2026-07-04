USE healthcare_platform;

CREATE TABLE IF NOT EXISTS notifications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    title VARCHAR(190) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('info', 'success', 'warning', 'danger') NOT NULL DEFAULT 'info',
    channel ENUM('in_app', 'email', 'sms') NOT NULL DEFAULT 'in_app',
    action_url VARCHAR(255) NULL,
    metadata LONGTEXT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    read_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_notifications_user (user_id),
    INDEX idx_notifications_unread (user_id, is_read),
    INDEX idx_notifications_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS notification_preferences (
    user_id INT UNSIGNED PRIMARY KEY,
    email_enabled TINYINT(1) NOT NULL DEFAULT 1,
    sms_enabled TINYINT(1) NOT NULL DEFAULT 0,
    in_app_enabled TINYINT(1) NOT NULL DEFAULT 1,
    appointment_updates TINYINT(1) NOT NULL DEFAULT 1,
    payment_updates TINYINT(1) NOT NULL DEFAULT 1,
    security_updates TINYINT(1) NOT NULL DEFAULT 1,
    marketing_updates TINYINT(1) NOT NULL DEFAULT 0,
    preferred_language VARCHAR(10) NOT NULL DEFAULT 'en',
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_notification_preferences_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS email_templates (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    template_key VARCHAR(100) NOT NULL UNIQUE,
    subject VARCHAR(190) NOT NULL,
    body_html MEDIUMTEXT NOT NULL,
    body_text MEDIUMTEXT NOT NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sms_templates (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    template_key VARCHAR(100) NOT NULL UNIQUE,
    body_text TEXT NOT NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO email_templates (template_key, subject, body_html, body_text, status) VALUES
('appointment_created_doctor', 'New appointment request from {{patient_name}}', '<p>Hello {{name}},</p><p>A new appointment request was submitted by <strong>{{patient_name}}</strong> for {{appointment_date}} at {{appointment_time}}.</p><p>Symptoms: {{symptoms}}</p>', 'New appointment request from {{patient_name}} for {{appointment_date}} at {{appointment_time}}. Symptoms: {{symptoms}}', 'active'),
('appointment_created_patient', 'Appointment request submitted with Dr. {{doctor_name}}', '<p>Hello {{name}},</p><p>Your appointment request with <strong>Dr. {{doctor_name}}</strong> has been submitted for {{appointment_date}} at {{appointment_time}}.</p>', 'Your appointment request with Dr. {{doctor_name}} has been submitted for {{appointment_date}} at {{appointment_time}}.', 'active'),
('appointment_updated_patient', 'Appointment status changed to {{appointment_status}}', '<p>Hello {{name}},</p><p>Your appointment with <strong>Dr. {{doctor_name}}</strong> is now <strong>{{appointment_status}}</strong>.</p><p>{{diagnosis}}</p>', 'Your appointment with Dr. {{doctor_name}} is now {{appointment_status}}. {{diagnosis}}', 'active'),
('invoice_created_patient', 'Invoice {{invoice_number}} is ready', '<p>Hello {{name}},</p><p>Your invoice <strong>{{invoice_number}}</strong> for {{currency}} {{amount}} has been generated.</p><p>You can pay it from your billing center.</p>', 'Your invoice {{invoice_number}} for {{currency}} {{amount}} has been generated.', 'active'),
('payment_paid_patient', 'Payment confirmed: {{transaction_reference}}', '<p>Hello {{name}},</p><p>Your payment <strong>{{transaction_reference}}</strong> for {{currency}} {{amount}} was completed successfully.</p>', 'Your payment {{transaction_reference}} for {{currency}} {{amount}} was completed successfully.', 'active'),
('payment_pending_patient', 'Payment initiated: {{transaction_reference}}', '<p>Hello {{name}},</p><p>Your payment request <strong>{{transaction_reference}}</strong> has been created. Complete the provider flow to finish payment.</p>', 'Your payment request {{transaction_reference}} has been created. Complete the provider flow to finish payment.', 'active'),
('payment_paid_doctor', 'Payment received for appointment {{appointment_id}}', '<p>Hello {{name}},</p><p>You received a payment of {{currency}} {{amount}} from {{patient_name}}.</p>', 'You received a payment of {{currency}} {{amount}} from {{patient_name}}.', 'active'),
('refund_requested_admin', 'Refund requested for {{transaction_reference}}', '<p>Hello Admin,</p><p>A refund was requested by {{requester_name}} for transaction <strong>{{transaction_reference}}</strong>.</p><p>Reason: {{reason}}</p>', 'Refund requested by {{requester_name}} for {{transaction_reference}}. Reason: {{reason}}', 'active'),
('refund_updated_patient', 'Refund request {{refund_status}}', '<p>Hello {{name}},</p><p>Your refund request for {{transaction_reference}} has been updated to <strong>{{refund_status}}</strong>.</p>', 'Your refund request for {{transaction_reference}} is now {{refund_status}}.', 'active'),
('doctor_verification_updated', 'Doctor verification status: {{verification_status}}', '<p>Hello {{name}},</p><p>Your doctor verification status is now <strong>{{verification_status}}</strong>.</p>', 'Your doctor verification status is now {{verification_status}}.', 'active'),
('hospital_verification_updated', 'Hospital verification status: {{verification_status}}', '<p>Hello {{name}},</p><p>Your hospital verification status is now <strong>{{verification_status}}</strong>.</p>', 'Your hospital verification status is now {{verification_status}}.', 'active');

INSERT IGNORE INTO sms_templates (template_key, body_text, status) VALUES
('appointment_created_doctor', 'New appointment from {{patient_name}} on {{appointment_date}} {{appointment_time}}.', 'active'),
('appointment_created_patient', 'Your appointment request with Dr. {{doctor_name}} was submitted.', 'active'),
('appointment_updated_patient', 'Appointment with Dr. {{doctor_name}} is now {{appointment_status}}.', 'active'),
('invoice_created_patient', 'Invoice {{invoice_number}} for {{currency}} {{amount}} is ready in {{platform_name}}.', 'active'),
('payment_paid_patient', 'Payment {{transaction_reference}} of {{currency}} {{amount}} completed.', 'active'),
('payment_pending_patient', 'Payment {{transaction_reference}} initiated. Complete the provider flow.', 'active'),
('payment_paid_doctor', 'Payment of {{currency}} {{amount}} received from {{patient_name}}.', 'active'),
('refund_requested_admin', 'Refund requested for {{transaction_reference}} by {{requester_name}}.', 'active'),
('refund_updated_patient', 'Refund request for {{transaction_reference}} is {{refund_status}}.', 'active'),
('doctor_verification_updated', 'Doctor verification: {{verification_status}}.', 'active'),
('hospital_verification_updated', 'Hospital verification: {{verification_status}}.', 'active');
