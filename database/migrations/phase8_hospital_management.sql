USE healthcare_platform;

CREATE TABLE IF NOT EXISTS hospital_departments (
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

CREATE TABLE IF NOT EXISTS hospital_inventory_items (
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

CREATE TABLE IF NOT EXISTS hospital_doctor_assignments (
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
