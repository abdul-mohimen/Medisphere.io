USE healthcare_platform;

CREATE TABLE IF NOT EXISTS hospital_bed_units (
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

CREATE TABLE IF NOT EXISTS patient_record_consents (
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

CREATE TABLE IF NOT EXISTS patient_record_access_logs (
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
