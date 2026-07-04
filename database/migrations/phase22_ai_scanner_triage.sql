ALTER TABLE disease_scans
    MODIFY scan_image VARCHAR(255) NULL,
    ADD COLUMN IF NOT EXISTS scan_type ENUM('image', 'symptom') NOT NULL DEFAULT 'image' AFTER scan_image,
    ADD COLUMN IF NOT EXISTS body_part VARCHAR(60) NOT NULL DEFAULT 'general' AFTER scan_type,
    ADD COLUMN IF NOT EXISTS symptom_text TEXT NULL AFTER body_part,
    ADD COLUMN IF NOT EXISTS urgency_level ENUM('routine', 'soon', 'urgent') NOT NULL DEFAULT 'routine' AFTER confidence_score,
    ADD COLUMN IF NOT EXISTS specialist_recommendation VARCHAR(190) NULL AFTER urgency_level,
    ADD COLUMN IF NOT EXISTS care_recommendations LONGTEXT NULL AFTER specialist_recommendation;

CREATE INDEX IF NOT EXISTS idx_scans_type_body ON disease_scans (scan_type, body_part);
CREATE INDEX IF NOT EXISTS idx_scans_urgency ON disease_scans (urgency_level);
