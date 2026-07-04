USE healthcare_platform;

CREATE TABLE IF NOT EXISTS doctor_availability (
    doctor_id INT UNSIGNED PRIMARY KEY,
    availability_status ENUM('online', 'offline', 'busy') NOT NULL DEFAULT 'offline',
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_doctor_availability_doctor FOREIGN KEY (doctor_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS consultation_sessions (
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

CREATE TABLE IF NOT EXISTS consultation_signals (
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

CREATE TABLE IF NOT EXISTS consultation_feedback (
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
