USE healthcare_platform;

DROP PROCEDURE IF EXISTS add_phase17_subscription_tiers;
DELIMITER //
CREATE PROCEDURE add_phase17_subscription_tiers()
BEGIN
    ALTER TABLE users MODIFY subscription_status ENUM('free', 'basic', 'premium', 'pending', 'subscribed', 'past_due', 'cancelled', 'expired') NOT NULL DEFAULT 'free';

    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'user_subscriptions' AND COLUMN_NAME = 'access_level'
    ) THEN
        ALTER TABLE user_subscriptions ADD COLUMN access_level ENUM('free', 'basic', 'premium') NOT NULL DEFAULT 'premium' AFTER plan_slug;
    END IF;

    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'user_subscriptions' AND COLUMN_NAME = 'expired_notified_at'
    ) THEN
        ALTER TABLE user_subscriptions ADD COLUMN expired_notified_at DATETIME NULL AFTER current_period_end;
    END IF;
END//
DELIMITER ;
CALL add_phase17_subscription_tiers();
DROP PROCEDURE IF EXISTS add_phase17_subscription_tiers;

UPDATE user_subscriptions
SET access_level = CASE
    WHEN plan_slug IN ('free-access') THEN 'free'
    WHEN plan_slug IN ('basic-care', 'care-plus') THEN 'basic'
    ELSE 'premium'
END
WHERE access_level IS NULL OR access_level = 'premium';

INSERT IGNORE INTO email_templates (template_key, subject, body_html, body_text, status) VALUES
('subscription_expired_patient', 'Your {{plan_name}} subscription has expired', '<p>Hello {{name}},</p><p>Your <strong>{{plan_name}}</strong> subscription expired on {{expired_at}}. Premium features are locked until you renew.</p><p>You can renew from the billing center.</p>', 'Your {{plan_name}} subscription expired on {{expired_at}}. Premium features are locked until you renew.', 'active');

INSERT IGNORE INTO sms_templates (template_key, body_text, status) VALUES
('subscription_expired_patient', '{{plan_name}} subscription expired on {{expired_at}}. Renew from billing to unlock premium features.', 'active');
