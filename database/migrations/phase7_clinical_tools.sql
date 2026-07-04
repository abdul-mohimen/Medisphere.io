USE healthcare_platform;

CREATE TABLE IF NOT EXISTS prescriptions (
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

CREATE TABLE IF NOT EXISTS clinical_documents (
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
