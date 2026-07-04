USE healthcare_platform;

CREATE TABLE IF NOT EXISTS doctor_signature_profiles (
    doctor_id INT UNSIGNED PRIMARY KEY,
    signature_name VARCHAR(180) NOT NULL,
    stamp_text VARCHAR(255) NULL,
    signature_image_path VARCHAR(255) NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_doctor_signature_profile_doctor FOREIGN KEY (doctor_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS document_audit_logs (
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

ALTER TABLE prescriptions
    ADD COLUMN IF NOT EXISTS signature_snapshot_name VARCHAR(180) NULL AFTER digital_signature,
    ADD COLUMN IF NOT EXISTS signature_image_path VARCHAR(255) NULL AFTER signature_snapshot_name,
    ADD COLUMN IF NOT EXISTS verification_code VARCHAR(40) NULL UNIQUE AFTER signature_image_path,
    ADD COLUMN IF NOT EXISTS integrity_hash VARCHAR(128) NULL AFTER verification_code;

ALTER TABLE clinical_documents
    ADD COLUMN IF NOT EXISTS signature_snapshot_name VARCHAR(180) NULL AFTER digital_signature,
    ADD COLUMN IF NOT EXISTS signature_image_path VARCHAR(255) NULL AFTER signature_snapshot_name,
    ADD COLUMN IF NOT EXISTS verification_code VARCHAR(40) NULL UNIQUE AFTER signature_image_path,
    ADD COLUMN IF NOT EXISTS integrity_hash VARCHAR(128) NULL AFTER verification_code;
