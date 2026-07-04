ALTER TABLE patients ADD COLUMN IF NOT EXISTS profile_image_data MEDIUMBLOB NULL AFTER profile_image;
ALTER TABLE patients ADD COLUMN IF NOT EXISTS profile_image_mime VARCHAR(100) NULL AFTER profile_image_data;
ALTER TABLE patients ADD COLUMN IF NOT EXISTS profile_image_updated_at DATETIME NULL AFTER profile_image_mime;
