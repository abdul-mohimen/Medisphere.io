USE healthcare_platform;

DROP PROCEDURE IF EXISTS add_phase16_subscription_columns;
DELIMITER //
CREATE PROCEDURE add_phase16_subscription_columns()
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'subscription_status'
    ) THEN
        ALTER TABLE users ADD COLUMN subscription_status ENUM('free', 'pending', 'subscribed', 'past_due', 'cancelled') NOT NULL DEFAULT 'free' AFTER status;
    END IF;

    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'subscription_plan'
    ) THEN
        ALTER TABLE users ADD COLUMN subscription_plan VARCHAR(80) NULL AFTER subscription_status;
    END IF;

    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'subscription_expires_at'
    ) THEN
        ALTER TABLE users ADD COLUMN subscription_expires_at DATETIME NULL AFTER subscription_plan;
    END IF;

    IF NOT EXISTS (
        SELECT 1 FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND INDEX_NAME = 'idx_users_subscription_status'
    ) THEN
        ALTER TABLE users ADD INDEX idx_users_subscription_status (subscription_status);
    END IF;

    IF NOT EXISTS (
        SELECT 1 FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND INDEX_NAME = 'idx_users_subscription_expires'
    ) THEN
        ALTER TABLE users ADD INDEX idx_users_subscription_expires (subscription_expires_at);
    END IF;
END//
DELIMITER ;
CALL add_phase16_subscription_columns();
DROP PROCEDURE IF EXISTS add_phase16_subscription_columns;

CREATE TABLE IF NOT EXISTS user_subscriptions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    plan_slug VARCHAR(80) NOT NULL,
    provider VARCHAR(60) NOT NULL,
    provider_checkout_id VARCHAR(160) NULL,
    provider_subscription_id VARCHAR(160) NULL,
    transaction_reference VARCHAR(120) NOT NULL UNIQUE,
    status ENUM('pending', 'subscribed', 'failed', 'cancelled', 'expired', 'past_due') NOT NULL DEFAULT 'pending',
    amount DECIMAL(10,2) NOT NULL,
    currency VARCHAR(10) NOT NULL DEFAULT 'USD',
    current_period_start DATETIME NULL,
    current_period_end DATETIME NULL,
    metadata LONGTEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_user_subscriptions_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_user_subscriptions_user_status (user_id, status),
    INDEX idx_user_subscriptions_reference (transaction_reference),
    INDEX idx_user_subscriptions_provider (provider),
    INDEX idx_user_subscriptions_period (current_period_end)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS subscription_payments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    subscription_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    provider VARCHAR(60) NOT NULL,
    provider_payment_id VARCHAR(160) NULL,
    transaction_reference VARCHAR(120) NOT NULL UNIQUE,
    amount DECIMAL(10,2) NOT NULL,
    currency VARCHAR(10) NOT NULL DEFAULT 'USD',
    status ENUM('pending', 'paid', 'failed', 'refunded') NOT NULL DEFAULT 'pending',
    gateway_response LONGTEXT NULL,
    paid_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_subscription_payments_subscription FOREIGN KEY (subscription_id) REFERENCES user_subscriptions(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_subscription_payments_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_subscription_payments_user (user_id),
    INDEX idx_subscription_payments_provider (provider),
    INDEX idx_subscription_payments_status (status),
    INDEX idx_subscription_payments_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS subscription_webhook_events (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    provider VARCHAR(60) NOT NULL,
    event_id VARCHAR(190) NOT NULL,
    event_type VARCHAR(120) NULL,
    payload_hash VARCHAR(64) NOT NULL,
    payload LONGTEXT NOT NULL,
    processed_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_subscription_webhook_event (provider, event_id),
    INDEX idx_subscription_webhook_provider (provider),
    INDEX idx_subscription_webhook_processed (processed_at),
    INDEX idx_subscription_webhook_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO email_templates (template_key, subject, body_html, body_text, status) VALUES
('subscription_pending_patient', 'Complete your {{plan_name}} subscription checkout', '<p>Hello {{name}},</p><p>Your {{platform_name}} subscription checkout for <strong>{{plan_name}}</strong> has started.</p><p>Reference: {{transaction_reference}}. Amount: {{currency}} {{amount}}.</p>', 'Your {{platform_name}} subscription checkout for {{plan_name}} has started. Reference: {{transaction_reference}}. Amount: {{currency}} {{amount}}.', 'active'),
('subscription_active_patient', 'Your {{plan_name}} subscription is active', '<p>Hello {{name}},</p><p>Your <strong>{{plan_name}}</strong> subscription is active. Premium features are unlocked until {{expires_at}}.</p><p>Reference: {{transaction_reference}}.</p>', 'Your {{plan_name}} subscription is active. Premium features are unlocked until {{expires_at}}. Reference: {{transaction_reference}}.', 'active');

INSERT IGNORE INTO sms_templates (template_key, body_text, status) VALUES
('subscription_pending_patient', '{{plan_name}} subscription checkout started. Reference {{transaction_reference}}.', 'active'),
('subscription_active_patient', '{{plan_name}} subscription active until {{expires_at}}.', 'active');
