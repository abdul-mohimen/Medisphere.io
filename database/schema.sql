CREATE DATABASE IF NOT EXISTS healthcare_platform CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE healthcare_platform;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS admin_logs;
DROP TABLE IF EXISTS compliance_incidents;
DROP TABLE IF EXISTS data_privacy_requests;
DROP TABLE IF EXISTS user_policy_acknowledgements;
DROP TABLE IF EXISTS policy_documents;
DROP TABLE IF EXISTS patient_profile_shares;
DROP TABLE IF EXISTS patient_health_metrics;
DROP TABLE IF EXISTS patient_family_history;
DROP TABLE IF EXISTS patient_insurance_profiles;
DROP TABLE IF EXISTS patient_emergency_contacts;
DROP TABLE IF EXISTS patient_medications;
DROP TABLE IF EXISTS patient_allergies;
DROP TABLE IF EXISTS patient_history_events;
DROP TABLE IF EXISTS media_assets;
DROP TABLE IF EXISTS patient_record_access_logs;
DROP TABLE IF EXISTS patient_record_consents;
DROP TABLE IF EXISTS hospital_bed_units;
DROP TABLE IF EXISTS document_audit_logs;
DROP TABLE IF EXISTS doctor_signature_profiles;
DROP TABLE IF EXISTS site_settings;
DROP TABLE IF EXISTS disease_information;
DROP TABLE IF EXISTS cms_articles;
DROP TABLE IF EXISTS cms_faqs;
DROP TABLE IF EXISTS hospital_doctor_assignments;
DROP TABLE IF EXISTS hospital_inventory_items;
DROP TABLE IF EXISTS hospital_departments;
DROP TABLE IF EXISTS clinical_documents;
DROP TABLE IF EXISTS prescriptions;
DROP TABLE IF EXISTS consultation_feedback;
DROP TABLE IF EXISTS consultation_signals;
DROP TABLE IF EXISTS consultation_sessions;
DROP TABLE IF EXISTS doctor_availability;
DROP TABLE IF EXISTS sms_templates;
DROP TABLE IF EXISTS email_templates;
DROP TABLE IF EXISTS notification_preferences;
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS refund_requests;
DROP TABLE IF EXISTS subscription_webhook_events;
DROP TABLE IF EXISTS subscription_payments;
DROP TABLE IF EXISTS user_subscriptions;
DROP TABLE IF EXISTS payments;
DROP TABLE IF EXISTS invoices;
DROP TABLE IF EXISTS doctor_hospital_requests;
DROP TABLE IF EXISTS disease_scans;
DROP TABLE IF EXISTS chat_messages;
DROP TABLE IF EXISTS medical_reports;
DROP TABLE IF EXISTS appointments;
DROP TABLE IF EXISTS doctors;
DROP TABLE IF EXISTS hospitals;
DROP TABLE IF EXISTS patients;
DROP TABLE IF EXISTS password_resets;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(190) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    user_type ENUM('patient', 'doctor', 'hospital', 'admin') NOT NULL,
    status ENUM('active', 'pending', 'suspended', 'inactive') NOT NULL DEFAULT 'active',
    subscription_status ENUM('free', 'basic', 'premium', 'pending', 'subscribed', 'past_due', 'cancelled', 'expired') NOT NULL DEFAULT 'free',
    subscription_plan VARCHAR(80) NULL,
    subscription_expires_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_users_type_status (user_type, status),
    INDEX idx_users_email_type_status (email, user_type, status),
    INDEX idx_users_subscription_status (subscription_status),
    INDEX idx_users_subscription_expires (subscription_expires_at),
    INDEX idx_users_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE cms_faqs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    question VARCHAR(255) NOT NULL,
    answer TEXT NOT NULL,
    category VARCHAR(120) NOT NULL DEFAULT 'General',
    status ENUM('draft', 'published') NOT NULL DEFAULT 'published',
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_cms_faqs_status (status),
    INDEX idx_cms_faqs_sort (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE media_assets (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(180) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    mime_type VARCHAR(120) NOT NULL,
    file_size INT UNSIGNED NOT NULL DEFAULT 0,
    uploaded_by INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_media_assets_uploaded_by FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_media_assets_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE cms_articles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(180) NOT NULL UNIQUE,
    title VARCHAR(255) NOT NULL,
    excerpt TEXT NULL,
    content MEDIUMTEXT NOT NULL,
    type ENUM('blog', 'news', 'guideline', 'emergency_protocol') NOT NULL DEFAULT 'blog',
    status ENUM('draft', 'published') NOT NULL DEFAULT 'published',
    author_id INT UNSIGNED NULL,
    published_at DATETIME NULL,
    featured_image_path VARCHAR(255) NULL,
    seo_title VARCHAR(255) NULL,
    seo_description VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_cms_articles_author FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_cms_articles_type_status (type, status),
    INDEX idx_cms_articles_published (published_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE disease_information (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(180) NOT NULL UNIQUE,
    disease_name VARCHAR(200) NOT NULL,
    overview TEXT NULL,
    symptoms TEXT NULL,
    causes TEXT NULL,
    prevention TEXT NULL,
    treatment TEXT NULL,
    emergency_notes TEXT NULL,
    status ENUM('draft', 'published') NOT NULL DEFAULT 'published',
    featured_image_path VARCHAR(255) NULL,
    seo_title VARCHAR(255) NULL,
    seo_description VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_disease_information_status (status),
    INDEX idx_disease_information_name (disease_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE site_settings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(120) NOT NULL UNIQUE,
    setting_value TEXT NOT NULL,
    setting_group VARCHAR(80) NOT NULL DEFAULT 'general',
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_site_settings_group (setting_group)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO site_settings (setting_key, setting_value, setting_group) VALUES
('homepage_notice', '', 'content'),
('footer_contact', '', 'content'),
('emergency_hotline', '', 'content'),
('about_summary', '', 'content');

CREATE TABLE notifications (
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

CREATE TABLE policy_documents (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(180) NOT NULL UNIQUE,
    title VARCHAR(255) NOT NULL,
    category ENUM('privacy_policy', 'terms_of_service', 'hipaa_notice', 'gdpr_rights', 'security_policy') NOT NULL DEFAULT 'privacy_policy',
    content MEDIUMTEXT NOT NULL,
    status ENUM('draft', 'published') NOT NULL DEFAULT 'published',
    version_label VARCHAR(40) NOT NULL DEFAULT 'v1.0',
    effective_date DATE NOT NULL,
    requires_acknowledgement TINYINT(1) NOT NULL DEFAULT 0,
    is_public TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_policy_documents_status (status),
    INDEX idx_policy_documents_effective (effective_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE user_policy_acknowledgements (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    policy_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    acknowledged_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_user_policy_ack_policy FOREIGN KEY (policy_id) REFERENCES policy_documents(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_user_policy_ack_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
    UNIQUE KEY uniq_policy_user_ack (policy_id, user_id),
    INDEX idx_user_policy_ack_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE data_privacy_requests (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    request_type ENUM('access', 'export', 'delete', 'correction', 'restriction') NOT NULL,
    description TEXT NOT NULL,
    status ENUM('submitted', 'in_review', 'completed', 'rejected') NOT NULL DEFAULT 'submitted',
    admin_notes TEXT NULL,
    resolved_by INT UNSIGNED NULL,
    resolved_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_data_privacy_requests_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_data_privacy_requests_resolver FOREIGN KEY (resolved_by) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_data_privacy_requests_status (status),
    INDEX idx_data_privacy_requests_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE compliance_incidents (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    incident_type ENUM('policy', 'privacy', 'security', 'breach', 'access') NOT NULL DEFAULT 'policy',
    severity ENUM('low', 'medium', 'high', 'critical') NOT NULL DEFAULT 'medium',
    status ENUM('open', 'investigating', 'resolved', 'closed') NOT NULL DEFAULT 'open',
    description TEXT NULL,
    action_taken TEXT NULL,
    reported_by INT UNSIGNED NULL,
    resolved_by INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_compliance_incidents_reported_by FOREIGN KEY (reported_by) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_compliance_incidents_resolved_by FOREIGN KEY (resolved_by) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_compliance_incidents_status (status),
    INDEX idx_compliance_incidents_severity (severity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO policy_documents (slug, title, category, content, status, version_label, effective_date, requires_acknowledgement, is_public) VALUES
('privacy-policy', 'Privacy Policy', 'privacy_policy', '<p>This privacy policy explains how medical, profile, appointment, and billing data are handled on MediSphere.</p><ul><li>Use secure access and verified consent flows.</li><li>Limit data access to authorized roles.</li><li>Maintain access logs for sensitive records.</li></ul>', 'published', 'v1.0', CURDATE(), 1, 1),
('terms-of-service', 'Terms of Service', 'terms_of_service', '<p>These terms govern access to MediSphere services and user responsibilities.</p><ul><li>Users must provide accurate account information.</li><li>Medical AI outputs are assistive and not a substitute for licensed medical judgment.</li><li>Misuse of records or consultations is prohibited.</li></ul>', 'published', 'v1.0', CURDATE(), 1, 1),
('hipaa-notice', 'HIPAA / Confidentiality Notice', 'hipaa_notice', '<p>MediSphere supports confidentiality controls, access restriction, and audit logging for medical workflows.</p>', 'published', 'v1.0', CURDATE(), 0, 1),
('gdpr-rights', 'GDPR / Data Rights Notice', 'gdpr_rights', '<p>Users may request access, export, correction, restriction, or deletion of personal data where applicable.</p>', 'published', 'v1.0', CURDATE(), 0, 1);

CREATE TABLE notification_preferences (
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

CREATE TABLE email_templates (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    template_key VARCHAR(100) NOT NULL UNIQUE,
    subject VARCHAR(190) NOT NULL,
    body_html MEDIUMTEXT NOT NULL,
    body_text MEDIUMTEXT NOT NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE sms_templates (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    template_key VARCHAR(100) NOT NULL UNIQUE,
    body_text TEXT NOT NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO email_templates (template_key, subject, body_html, body_text, status) VALUES
('appointment_created_doctor', 'New appointment request from {{patient_name}}', '<p>Hello {{name}},</p><p>A new appointment request was submitted by <strong>{{patient_name}}</strong> for {{appointment_date}} at {{appointment_time}}.</p><p>Symptoms: {{symptoms}}</p>', 'New appointment request from {{patient_name}} for {{appointment_date}} at {{appointment_time}}. Symptoms: {{symptoms}}', 'active'),
('appointment_created_patient', 'Appointment request submitted with Dr. {{doctor_name}}', '<p>Hello {{name}},</p><p>Your appointment request with <strong>Dr. {{doctor_name}}</strong> has been submitted for {{appointment_date}} at {{appointment_time}}.</p>', 'Your appointment request with Dr. {{doctor_name}} has been submitted for {{appointment_date}} at {{appointment_time}}.', 'active'),
('appointment_updated_patient', 'Appointment status changed to {{appointment_status}}', '<p>Hello {{name}},</p><p>Your appointment with <strong>Dr. {{doctor_name}}</strong> is now <strong>{{appointment_status}}</strong>.</p><p>{{diagnosis}}</p>', 'Your appointment with Dr. {{doctor_name}} is now {{appointment_status}}. {{diagnosis}}', 'active'),
('invoice_created_patient', 'Invoice {{invoice_number}} is ready', '<p>Hello {{name}},</p><p>Your invoice <strong>{{invoice_number}}</strong> for {{currency}} {{amount}} has been generated.</p><p>You can pay it from your billing center.</p>', 'Your invoice {{invoice_number}} for {{currency}} {{amount}} has been generated.', 'active'),
('payment_paid_patient', 'Payment confirmed: {{transaction_reference}}', '<p>Hello {{name}},</p><p>Your payment <strong>{{transaction_reference}}</strong> for {{currency}} {{amount}} was completed successfully.</p>', 'Your payment {{transaction_reference}} for {{currency}} {{amount}} was completed successfully.', 'active'),
('payment_pending_patient', 'Payment initiated: {{transaction_reference}}', '<p>Hello {{name}},</p><p>Your payment request <strong>{{transaction_reference}}</strong> has been created. Complete the provider flow to finish payment.</p>', 'Your payment request {{transaction_reference}} has been created. Complete the provider flow to finish payment.', 'active'),
('payment_paid_doctor', 'Payment received for appointment {{appointment_id}}', '<p>Hello {{name}},</p><p>You received a payment of {{currency}} {{amount}} from {{patient_name}}.</p>', 'You received a payment of {{currency}} {{amount}} from {{patient_name}}.', 'active'),
('subscription_pending_patient', 'Complete your {{plan_name}} subscription checkout', '<p>Hello {{name}},</p><p>Your {{platform_name}} subscription checkout for <strong>{{plan_name}}</strong> has started.</p><p>Reference: {{transaction_reference}}. Amount: {{currency}} {{amount}}.</p>', 'Your {{platform_name}} subscription checkout for {{plan_name}} has started. Reference: {{transaction_reference}}. Amount: {{currency}} {{amount}}.', 'active'),
('subscription_active_patient', 'Your {{plan_name}} subscription is active', '<p>Hello {{name}},</p><p>Your <strong>{{plan_name}}</strong> subscription is active. Premium features are unlocked until {{expires_at}}.</p><p>Reference: {{transaction_reference}}.</p>', 'Your {{plan_name}} subscription is active. Premium features are unlocked until {{expires_at}}. Reference: {{transaction_reference}}.', 'active'),
('subscription_expired_patient', 'Your {{plan_name}} subscription has expired', '<p>Hello {{name}},</p><p>Your <strong>{{plan_name}}</strong> subscription expired on {{expired_at}}. Premium features are locked until you renew.</p>', 'Your {{plan_name}} subscription expired on {{expired_at}}. Premium features are locked until you renew.', 'active'),
('refund_requested_admin', 'Refund requested for {{transaction_reference}}', '<p>Hello Admin,</p><p>A refund was requested by {{requester_name}} for transaction <strong>{{transaction_reference}}</strong>.</p><p>Reason: {{reason}}</p>', 'Refund requested by {{requester_name}} for {{transaction_reference}}. Reason: {{reason}}', 'active'),
('refund_updated_patient', 'Refund request {{refund_status}}', '<p>Hello {{name}},</p><p>Your refund request for {{transaction_reference}} has been updated to <strong>{{refund_status}}</strong>.</p>', 'Your refund request for {{transaction_reference}} is now {{refund_status}}.', 'active'),
('doctor_verification_updated', 'Doctor verification status: {{verification_status}}', '<p>Hello {{name}},</p><p>Your doctor verification status is now <strong>{{verification_status}}</strong>.</p>', 'Your doctor verification status is now {{verification_status}}.', 'active'),
('hospital_verification_updated', 'Hospital verification status: {{verification_status}}', '<p>Hello {{name}},</p><p>Your hospital verification status is now <strong>{{verification_status}}</strong>.</p>', 'Your hospital verification status is now {{verification_status}}.', 'active');

INSERT INTO sms_templates (template_key, body_text, status) VALUES
('appointment_created_doctor', 'New appointment from {{patient_name}} on {{appointment_date}} {{appointment_time}}.', 'active'),
('appointment_created_patient', 'Your appointment request with Dr. {{doctor_name}} was submitted.', 'active'),
('appointment_updated_patient', 'Appointment with Dr. {{doctor_name}} is now {{appointment_status}}.', 'active'),
('invoice_created_patient', 'Invoice {{invoice_number}} for {{currency}} {{amount}} is ready in {{platform_name}}.', 'active'),
('payment_paid_patient', 'Payment {{transaction_reference}} of {{currency}} {{amount}} completed.', 'active'),
('payment_pending_patient', 'Payment {{transaction_reference}} initiated. Complete the provider flow.', 'active'),
('payment_paid_doctor', 'Payment of {{currency}} {{amount}} received from {{patient_name}}.', 'active'),
('subscription_pending_patient', '{{plan_name}} subscription checkout started. Reference {{transaction_reference}}.', 'active'),
('subscription_active_patient', '{{plan_name}} subscription active until {{expires_at}}.', 'active'),
('subscription_expired_patient', '{{plan_name}} subscription expired on {{expired_at}}. Renew to unlock premium features.', 'active'),
('refund_requested_admin', 'Refund requested for {{transaction_reference}} by {{requester_name}}.', 'active'),
('refund_updated_patient', 'Refund request for {{transaction_reference}} is {{refund_status}}.', 'active'),
('doctor_verification_updated', 'Doctor verification: {{verification_status}}.', 'active'),
('hospital_verification_updated', 'Hospital verification: {{verification_status}}.', 'active');

CREATE TABLE patients (
    user_id INT UNSIGNED PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    age TINYINT UNSIGNED NULL,
    gender VARCHAR(20) NULL,
    blood_group VARCHAR(10) NULL,
    address VARCHAR(255) NULL,
    phone VARCHAR(30) NULL,
    city VARCHAR(100) NULL,
    country VARCHAR(100) NULL,
    profile_image VARCHAR(255) NULL,
    profile_image_data MEDIUMBLOB NULL,
    profile_image_mime VARCHAR(100) NULL,
    profile_image_updated_at DATETIME NULL,
    CONSTRAINT fk_patients_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_patients_name (name),
    INDEX idx_patients_phone (phone)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE patient_history_events (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    patient_id INT UNSIGNED NOT NULL,
    event_date DATE NOT NULL,
    category VARCHAR(80) NOT NULL DEFAULT 'general',
    title VARCHAR(180) NOT NULL,
    description TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_patient_history_events_patient FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_patient_history_events_patient (patient_id),
    INDEX idx_patient_history_events_date (event_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE patient_allergies (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    patient_id INT UNSIGNED NOT NULL,
    allergen VARCHAR(160) NOT NULL,
    severity ENUM('mild', 'moderate', 'severe') NOT NULL DEFAULT 'moderate',
    notes VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_patient_allergies_patient FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_patient_allergies_patient (patient_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE patient_medications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    patient_id INT UNSIGNED NOT NULL,
    medicine_name VARCHAR(160) NOT NULL,
    dosage VARCHAR(120) NULL,
    frequency VARCHAR(120) NULL,
    start_date DATE NULL,
    notes VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_patient_medications_patient FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_patient_medications_patient (patient_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE patient_emergency_contacts (
    patient_id INT UNSIGNED PRIMARY KEY,
    contact_name VARCHAR(160) NOT NULL,
    relationship VARCHAR(120) NULL,
    phone VARCHAR(40) NULL,
    alternate_phone VARCHAR(40) NULL,
    address VARCHAR(255) NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_patient_emergency_contacts_patient FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE patient_insurance_profiles (
    patient_id INT UNSIGNED PRIMARY KEY,
    provider_name VARCHAR(180) NULL,
    policy_number VARCHAR(120) NULL,
    plan_name VARCHAR(160) NULL,
    valid_until DATE NULL,
    coverage_notes TEXT NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_patient_insurance_profiles_patient FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE patient_family_history (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    patient_id INT UNSIGNED NOT NULL,
    relation_name VARCHAR(120) NOT NULL,
    condition_name VARCHAR(160) NOT NULL,
    notes VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_patient_family_history_patient FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_patient_family_history_patient (patient_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE patient_health_metrics (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    patient_id INT UNSIGNED NOT NULL,
    metric_type VARCHAR(80) NOT NULL,
    value_primary DECIMAL(10,2) NOT NULL,
    value_secondary DECIMAL(10,2) NULL,
    unit VARCHAR(40) NULL,
    recorded_at DATE NOT NULL,
    notes VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_patient_health_metrics_patient FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_patient_health_metrics_patient (patient_id),
    INDEX idx_patient_health_metrics_recorded (recorded_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE patient_profile_shares (
    patient_id INT UNSIGNED PRIMARY KEY,
    access_token VARCHAR(80) NOT NULL UNIQUE,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_patient_profile_shares_patient FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE hospitals (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL UNIQUE,
    name VARCHAR(180) NOT NULL,
    type ENUM('government', 'private') NOT NULL DEFAULT 'private',
    address VARCHAR(255) NOT NULL,
    city VARCHAR(100) NOT NULL,
    country VARCHAR(100) NOT NULL,
    phone VARCHAR(30) NULL,
    email VARCHAR(190) NULL,
    registration_number VARCHAR(120) NULL,
    facilities TEXT NULL,
    departments TEXT NULL,
    verified_status ENUM('pending', 'verified', 'rejected') NOT NULL DEFAULT 'pending',
    coordinates VARCHAR(100) NULL,
    CONSTRAINT fk_hospitals_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_hospitals_location (city, country),
    INDEX idx_hospitals_verified (verified_status),
    INDEX idx_hospitals_user_verified (user_id, verified_status),
    INDEX idx_hospitals_city_verified (city, verified_status),
    INDEX idx_hospitals_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE doctors (
    user_id INT UNSIGNED PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    specialization VARCHAR(120) NOT NULL,
    qualification_details VARCHAR(255) NULL,
    license_number VARCHAR(120) NOT NULL UNIQUE,
    experience TINYINT UNSIGNED NOT NULL DEFAULT 0,
    hospital_id INT UNSIGNED NULL,
    verified_status ENUM('pending', 'verified', 'rejected') NOT NULL DEFAULT 'pending',
    profile_image VARCHAR(255) NULL,
    consultation_fee DECIMAL(10,2) NOT NULL DEFAULT 0,
    languages_spoken VARCHAR(255) NULL,
    bio TEXT NULL,
    CONSTRAINT fk_doctors_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_doctors_hospital FOREIGN KEY (hospital_id) REFERENCES hospitals(id) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_doctors_name (name),
    INDEX idx_doctors_specialization (specialization),
    INDEX idx_doctors_specialization_verified (specialization, verified_status),
    INDEX idx_doctors_hospital (hospital_id),
    INDEX idx_doctors_hospital_verified (hospital_id, verified_status),
    INDEX idx_doctors_verified (verified_status),
    INDEX idx_doctors_fee (consultation_fee)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE hospital_departments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    hospital_id INT UNSIGNED NOT NULL,
    name VARCHAR(150) NOT NULL,
    description TEXT NULL,
    head_doctor_id INT UNSIGNED NULL,
    timings VARCHAR(180) NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_hospital_departments_hospital FOREIGN KEY (hospital_id) REFERENCES hospitals(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_hospital_departments_head FOREIGN KEY (head_doctor_id) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE,
    UNIQUE KEY uniq_hospital_department_name (hospital_id, name),
    INDEX idx_hospital_departments_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE hospital_inventory_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    hospital_id INT UNSIGNED NOT NULL,
    item_name VARCHAR(180) NOT NULL,
    category VARCHAR(120) NULL,
    quantity INT UNSIGNED NOT NULL DEFAULT 0,
    status ENUM('available', 'maintenance', 'critical', 'out_of_service') NOT NULL DEFAULT 'available',
    next_maintenance DATE NULL,
    notes TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_hospital_inventory_hospital FOREIGN KEY (hospital_id) REFERENCES hospitals(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_hospital_inventory_status (status),
    INDEX idx_hospital_inventory_maintenance (next_maintenance)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE hospital_doctor_assignments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    hospital_id INT UNSIGNED NOT NULL,
    doctor_id INT UNSIGNED NOT NULL,
    department_id INT UNSIGNED NULL,
    privileges VARCHAR(255) NULL,
    status ENUM('active', 'suspended', 'pending') NOT NULL DEFAULT 'active',
    assigned_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_hospital_assignments_hospital FOREIGN KEY (hospital_id) REFERENCES hospitals(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_hospital_assignments_doctor FOREIGN KEY (doctor_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_hospital_assignments_department FOREIGN KEY (department_id) REFERENCES hospital_departments(id) ON DELETE SET NULL ON UPDATE CASCADE,
    UNIQUE KEY uniq_hospital_doctor_assignment (hospital_id, doctor_id),
    INDEX idx_hospital_assignments_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE hospital_bed_units (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    hospital_id INT UNSIGNED NOT NULL,
    ward_name VARCHAR(150) NOT NULL,
    bed_label VARCHAR(120) NOT NULL,
    bed_type VARCHAR(120) NULL,
    occupancy_status ENUM('available', 'occupied', 'reserved', 'maintenance', 'cleaning') NOT NULL DEFAULT 'available',
    assigned_patient_id INT UNSIGNED NULL,
    notes TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_hospital_bed_units_hospital FOREIGN KEY (hospital_id) REFERENCES hospitals(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_hospital_bed_units_patient FOREIGN KEY (assigned_patient_id) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE,
    UNIQUE KEY uniq_hospital_bed_label (hospital_id, ward_name, bed_label),
    INDEX idx_hospital_beds_status (occupancy_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE patient_record_consents (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    patient_id INT UNSIGNED NOT NULL,
    hospital_id INT UNSIGNED NOT NULL,
    scope VARCHAR(255) NOT NULL,
    status ENUM('active', 'revoked', 'expired') NOT NULL DEFAULT 'active',
    starts_at DATE NOT NULL,
    expires_at DATE NULL,
    notes TEXT NULL,
    granted_by_patient_at DATETIME NULL,
    revoked_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_patient_record_consents_patient FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_patient_record_consents_hospital FOREIGN KEY (hospital_id) REFERENCES hospitals(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_patient_record_consents_status (status),
    INDEX idx_patient_record_consents_expiry (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE patient_record_access_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    hospital_id INT UNSIGNED NOT NULL,
    patient_id INT UNSIGNED NOT NULL,
    consent_id INT UNSIGNED NOT NULL,
    accessed_by_user_id INT UNSIGNED NOT NULL,
    access_type VARCHAR(100) NOT NULL,
    context_data LONGTEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_patient_record_access_logs_hospital FOREIGN KEY (hospital_id) REFERENCES hospitals(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_patient_record_access_logs_patient FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_patient_record_access_logs_consent FOREIGN KEY (consent_id) REFERENCES patient_record_consents(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_patient_record_access_logs_accessor FOREIGN KEY (accessed_by_user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_patient_record_access_logs_hospital (hospital_id),
    INDEX idx_patient_record_access_logs_patient (patient_id),
    INDEX idx_patient_record_access_logs_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE appointments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    patient_id INT UNSIGNED NOT NULL,
    doctor_id INT UNSIGNED NOT NULL,
    date DATE NOT NULL,
    time TIME NOT NULL,
    status ENUM('pending', 'confirmed', 'completed', 'cancelled', 'rescheduled') NOT NULL DEFAULT 'pending',
    symptoms TEXT NULL,
    diagnosis TEXT NULL,
    CONSTRAINT fk_appointments_patient FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_appointments_doctor FOREIGN KEY (doctor_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_appointments_patient (patient_id),
    INDEX idx_appointments_doctor (doctor_id),
    INDEX idx_appointments_datetime (date, time),
    INDEX idx_appointments_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE doctor_availability (
    doctor_id INT UNSIGNED PRIMARY KEY,
    availability_status ENUM('online', 'offline', 'busy') NOT NULL DEFAULT 'offline',
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_doctor_availability_doctor FOREIGN KEY (doctor_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE consultation_sessions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    appointment_id INT UNSIGNED NULL,
    initiator_id INT UNSIGNED NOT NULL,
    patient_id INT UNSIGNED NOT NULL,
    doctor_id INT UNSIGNED NOT NULL,
    session_token VARCHAR(64) NOT NULL UNIQUE,
    room_name VARCHAR(80) NOT NULL,
    call_type ENUM('video', 'voice') NOT NULL DEFAULT 'video',
    status ENUM('waiting', 'active', 'ended', 'cancelled') NOT NULL DEFAULT 'waiting',
    consent_recording TINYINT(1) NOT NULL DEFAULT 0,
    started_at DATETIME NULL,
    ended_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_consultation_session_appointment FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_consultation_session_initiator FOREIGN KEY (initiator_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_consultation_session_patient FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_consultation_session_doctor FOREIGN KEY (doctor_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_consultation_session_appointment (appointment_id),
    INDEX idx_consultation_session_doctor (doctor_id),
    INDEX idx_consultation_session_patient (patient_id),
    INDEX idx_consultation_session_status (status),
    INDEX idx_consultation_session_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE consultation_signals (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    session_id INT UNSIGNED NOT NULL,
    sender_id INT UNSIGNED NOT NULL,
    signal_type ENUM('presence', 'offer', 'answer', 'candidate', 'hangup', 'screen-share', 'status') NOT NULL,
    payload LONGTEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_consultation_signal_session FOREIGN KEY (session_id) REFERENCES consultation_sessions(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_consultation_signal_sender FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_consultation_signal_session (session_id),
    INDEX idx_consultation_signal_session_id (session_id, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE consultation_feedback (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    session_id INT UNSIGNED NOT NULL,
    reviewer_id INT UNSIGNED NOT NULL,
    rating TINYINT UNSIGNED NOT NULL,
    review_text TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_consultation_feedback_session FOREIGN KEY (session_id) REFERENCES consultation_sessions(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_consultation_feedback_reviewer FOREIGN KEY (reviewer_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
    UNIQUE KEY uniq_consultation_feedback (session_id, reviewer_id),
    INDEX idx_consultation_feedback_rating (rating)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE invoices (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    appointment_id INT UNSIGNED NULL UNIQUE,
    invoice_number VARCHAR(60) NOT NULL UNIQUE,
    amount DECIMAL(10,2) NOT NULL,
    currency VARCHAR(10) NOT NULL DEFAULT 'PKR',
    status ENUM('unpaid', 'paid', 'cancelled', 'refunded') NOT NULL DEFAULT 'unpaid',
    due_date DATE NULL,
    issued_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    notes TEXT NULL,
    CONSTRAINT fk_invoices_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_invoices_appointment FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_invoices_user (user_id),
    INDEX idx_invoices_status (status),
    INDEX idx_invoices_issued (issued_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE payments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    invoice_id INT UNSIGNED NOT NULL,
    appointment_id INT UNSIGNED NULL,
    payer_id INT UNSIGNED NOT NULL,
    payee_id INT UNSIGNED NULL,
    provider ENUM('mock', 'stripe', 'paypal', 'jazzcash', 'easypaisa') NOT NULL DEFAULT 'mock',
    provider_payment_id VARCHAR(120) NULL,
    transaction_reference VARCHAR(120) NOT NULL UNIQUE,
    amount DECIMAL(10,2) NOT NULL,
    currency VARCHAR(10) NOT NULL DEFAULT 'PKR',
    status ENUM('initiated', 'pending', 'paid', 'failed', 'refunded') NOT NULL DEFAULT 'initiated',
    gateway_response TEXT NULL,
    paid_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_payments_invoice FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_payments_appointment FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_payments_payer FOREIGN KEY (payer_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_payments_payee FOREIGN KEY (payee_id) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_payments_invoice (invoice_id),
    INDEX idx_payments_payer (payer_id),
    INDEX idx_payments_payee (payee_id),
    INDEX idx_payments_status (status),
    INDEX idx_payments_provider (provider),
    INDEX idx_payments_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE user_subscriptions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    plan_slug VARCHAR(80) NOT NULL,
    access_level ENUM('free', 'basic', 'premium') NOT NULL DEFAULT 'premium',
    provider VARCHAR(60) NOT NULL,
    provider_checkout_id VARCHAR(160) NULL,
    provider_subscription_id VARCHAR(160) NULL,
    transaction_reference VARCHAR(120) NOT NULL UNIQUE,
    status ENUM('pending', 'subscribed', 'failed', 'cancelled', 'expired', 'past_due') NOT NULL DEFAULT 'pending',
    amount DECIMAL(10,2) NOT NULL,
    currency VARCHAR(10) NOT NULL DEFAULT 'USD',
    current_period_start DATETIME NULL,
    current_period_end DATETIME NULL,
    expired_notified_at DATETIME NULL,
    metadata LONGTEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_user_subscriptions_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_user_subscriptions_user_status (user_id, status),
    INDEX idx_user_subscriptions_active_lookup (user_id, status, current_period_end, access_level),
    INDEX idx_user_subscriptions_plan_status (plan_slug, status),
    INDEX idx_user_subscriptions_reference (transaction_reference),
    INDEX idx_user_subscriptions_provider (provider),
    INDEX idx_user_subscriptions_period (current_period_end)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE subscription_payments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    subscription_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    provider VARCHAR(60) NOT NULL,
    provider_payment_id VARCHAR(160) NULL,
    transaction_reference VARCHAR(120) NOT NULL UNIQUE,
    amount DECIMAL(10,2) NOT NULL,
    currency VARCHAR(10) NOT NULL DEFAULT 'USD',
    status ENUM('pending', 'paid', 'failed', 'refunded') NOT NULL DEFAULT 'pending',
    gateway_response LONGTEXT NULL,
    paid_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_subscription_payments_subscription FOREIGN KEY (subscription_id) REFERENCES user_subscriptions(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_subscription_payments_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_subscription_payments_user (user_id),
    INDEX idx_subscription_payments_provider (provider),
    INDEX idx_subscription_payments_status (status),
    INDEX idx_subscription_payments_reference_status (transaction_reference, status),
    INDEX idx_subscription_payments_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE subscription_webhook_events (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    provider VARCHAR(60) NOT NULL,
    event_id VARCHAR(190) NOT NULL,
    event_type VARCHAR(120) NULL,
    payload_hash VARCHAR(64) NOT NULL,
    payload LONGTEXT NOT NULL,
    processed_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_subscription_webhook_event (provider, event_id),
    INDEX idx_subscription_webhook_provider (provider),
    INDEX idx_subscription_webhook_processed (processed_at),
    INDEX idx_subscription_webhook_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE doctor_signature_profiles (
    doctor_id INT UNSIGNED PRIMARY KEY,
    signature_name VARCHAR(180) NOT NULL,
    stamp_text VARCHAR(255) NULL,
    signature_image_path VARCHAR(255) NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_doctor_signature_profile_doctor FOREIGN KEY (doctor_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE document_audit_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    document_type ENUM('prescription', 'clinical_document') NOT NULL,
    document_id INT UNSIGNED NOT NULL,
    actor_id INT UNSIGNED NULL,
    action VARCHAR(80) NOT NULL,
    context_data LONGTEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_document_audit_logs_actor FOREIGN KEY (actor_id) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_document_audit_lookup (document_type, document_id),
    INDEX idx_document_audit_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE prescriptions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    appointment_id INT UNSIGNED NULL,
    doctor_id INT UNSIGNED NOT NULL,
    patient_id INT UNSIGNED NOT NULL,
    title VARCHAR(180) NOT NULL,
    diagnosis VARCHAR(255) NULL,
    medications_json LONGTEXT NOT NULL,
    notes TEXT NULL,
    advice TEXT NULL,
    follow_up_date DATE NULL,
    digital_signature VARCHAR(255) NULL,
    signature_snapshot_name VARCHAR(180) NULL,
    signature_image_path VARCHAR(255) NULL,
    verification_code VARCHAR(40) NULL UNIQUE,
    integrity_hash VARCHAR(128) NULL,
    status ENUM('draft', 'finalized') NOT NULL DEFAULT 'finalized',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_prescriptions_appointment FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_prescriptions_doctor FOREIGN KEY (doctor_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_prescriptions_patient FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_prescriptions_doctor (doctor_id),
    INDEX idx_prescriptions_patient (patient_id),
    INDEX idx_prescriptions_created (created_at),
    INDEX idx_prescriptions_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE clinical_documents (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    appointment_id INT UNSIGNED NULL,
    doctor_id INT UNSIGNED NOT NULL,
    patient_id INT UNSIGNED NOT NULL,
    document_type ENUM('medical_certificate', 'discharge_summary', 'treatment_plan', 'follow_up_note') NOT NULL DEFAULT 'treatment_plan',
    title VARCHAR(180) NOT NULL,
    summary VARCHAR(255) NULL,
    content MEDIUMTEXT NOT NULL,
    issue_date DATE NOT NULL,
    digital_signature VARCHAR(255) NULL,
    signature_snapshot_name VARCHAR(180) NULL,
    signature_image_path VARCHAR(255) NULL,
    verification_code VARCHAR(40) NULL UNIQUE,
    integrity_hash VARCHAR(128) NULL,
    status ENUM('draft', 'finalized') NOT NULL DEFAULT 'finalized',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_clinical_documents_appointment FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_clinical_documents_doctor FOREIGN KEY (doctor_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_clinical_documents_patient FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_clinical_documents_doctor (doctor_id),
    INDEX idx_clinical_documents_patient (patient_id),
    INDEX idx_clinical_documents_type (document_type),
    INDEX idx_clinical_documents_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE refund_requests (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    payment_id INT UNSIGNED NOT NULL,
    requested_by INT UNSIGNED NOT NULL,
    reason TEXT NOT NULL,
    status ENUM('pending', 'approved', 'rejected', 'processed') NOT NULL DEFAULT 'pending',
    reviewed_by INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_refunds_payment FOREIGN KEY (payment_id) REFERENCES payments(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_refunds_requester FOREIGN KEY (requested_by) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_refunds_reviewer FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_refunds_payment (payment_id),
    INDEX idx_refunds_requester (requested_by),
    INDEX idx_refunds_status (status),
    INDEX idx_refunds_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE medical_reports (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    patient_id INT UNSIGNED NOT NULL,
    doctor_id INT UNSIGNED NULL,
    report_type VARCHAR(80) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NULL,
    uploaded_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_reports_patient FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_reports_doctor FOREIGN KEY (doctor_id) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_reports_patient (patient_id),
    INDEX idx_reports_doctor (doctor_id),
    INDEX idx_reports_type (report_type),
    INDEX idx_reports_uploaded (uploaded_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE chat_messages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sender_id INT UNSIGNED NOT NULL,
    receiver_id INT UNSIGNED NOT NULL,
    message TEXT NOT NULL,
    timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    read_status TINYINT(1) NOT NULL DEFAULT 0,
    CONSTRAINT fk_chat_sender FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_chat_receiver FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_chat_pair_time (sender_id, receiver_id, timestamp),
    INDEX idx_chat_receiver_read (receiver_id, read_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE disease_scans (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    patient_id INT UNSIGNED NOT NULL,
    scan_image VARCHAR(255) NULL,
    scan_type ENUM('image', 'symptom') NOT NULL DEFAULT 'image',
    body_part VARCHAR(60) NOT NULL DEFAULT 'general',
    symptom_text TEXT NULL,
    ai_result VARCHAR(255) NOT NULL,
    confidence_score DECIMAL(5,2) NOT NULL DEFAULT 0,
    urgency_level ENUM('routine', 'soon', 'urgent') NOT NULL DEFAULT 'routine',
    specialist_recommendation VARCHAR(190) NULL,
    care_recommendations LONGTEXT NULL,
    timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_scans_patient FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_scans_patient (patient_id),
    INDEX idx_scans_type_body (scan_type, body_part),
    INDEX idx_scans_urgency (urgency_level),
    INDEX idx_scans_timestamp (timestamp)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE doctor_hospital_requests (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    doctor_id INT UNSIGNED NOT NULL,
    hospital_id INT UNSIGNED NOT NULL,
    status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    submitted_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    cover_letter TEXT NULL,
    credentials TEXT NULL,
    preferred_departments VARCHAR(255) NULL,
    CONSTRAINT fk_dhr_doctor FOREIGN KEY (doctor_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_dhr_hospital FOREIGN KEY (hospital_id) REFERENCES hospitals(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_dhr_doctor (doctor_id),
    INDEX idx_dhr_hospital (hospital_id),
    INDEX idx_dhr_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE admin_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    admin_id INT UNSIGNED NOT NULL,
    action TEXT NOT NULL,
    timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_admin_logs_admin FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_admin_logs_admin (admin_id),
    INDEX idx_admin_logs_timestamp (timestamp)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE password_resets (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(190) NOT NULL,
    token VARCHAR(120) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_password_resets_email (email),
    INDEX idx_password_resets_token (token),
    INDEX idx_password_resets_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create the first admin from the app by visiting:
-- http://localhost/healthcare-platform/public/index.php?route=setup-admin
