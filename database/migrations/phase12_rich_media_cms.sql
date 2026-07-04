USE healthcare_platform;

CREATE TABLE IF NOT EXISTS media_assets (
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

ALTER TABLE cms_articles
    ADD COLUMN IF NOT EXISTS featured_image_path VARCHAR(255) NULL AFTER published_at,
    ADD COLUMN IF NOT EXISTS seo_title VARCHAR(255) NULL AFTER featured_image_path,
    ADD COLUMN IF NOT EXISTS seo_description VARCHAR(255) NULL AFTER seo_title;

ALTER TABLE disease_information
    ADD COLUMN IF NOT EXISTS featured_image_path VARCHAR(255) NULL AFTER status,
    ADD COLUMN IF NOT EXISTS seo_title VARCHAR(255) NULL AFTER featured_image_path,
    ADD COLUMN IF NOT EXISTS seo_description VARCHAR(255) NULL AFTER seo_title;
