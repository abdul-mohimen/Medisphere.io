USE healthcare_platform;

CREATE TABLE IF NOT EXISTS policy_documents (
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

CREATE TABLE IF NOT EXISTS user_policy_acknowledgements (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    policy_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    acknowledged_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_user_policy_ack_policy FOREIGN KEY (policy_id) REFERENCES policy_documents(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_user_policy_ack_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
    UNIQUE KEY uniq_policy_user_ack (policy_id, user_id),
    INDEX idx_user_policy_ack_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS data_privacy_requests (
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

CREATE TABLE IF NOT EXISTS compliance_incidents (
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

INSERT IGNORE INTO policy_documents (slug, title, category, content, status, version_label, effective_date, requires_acknowledgement, is_public) VALUES
('privacy-policy', 'Privacy Policy', 'privacy_policy', '<p>This privacy policy explains how medical, profile, appointment, and billing data are handled on MediSphere.</p><ul><li>Use secure access and verified consent flows.</li><li>Limit data access to authorized roles.</li><li>Maintain access logs for sensitive records.</li></ul>', 'published', 'v1.0', CURDATE(), 1, 1),
('terms-of-service', 'Terms of Service', 'terms_of_service', '<p>These terms govern access to MediSphere services and user responsibilities.</p><ul><li>Users must provide accurate account information.</li><li>Medical AI outputs are assistive and not a substitute for licensed medical judgment.</li><li>Misuse of records or consultations is prohibited.</li></ul>', 'published', 'v1.0', CURDATE(), 1, 1),
('hipaa-notice', 'HIPAA / Confidentiality Notice', 'hipaa_notice', '<p>MediSphere supports confidentiality controls, access restriction, and audit logging for medical workflows.</p>', 'published', 'v1.0', CURDATE(), 0, 1),
('gdpr-rights', 'GDPR / Data Rights Notice', 'gdpr_rights', '<p>Users may request access, export, correction, restriction, or deletion of personal data where applicable.</p>', 'published', 'v1.0', CURDATE(), 0, 1);
