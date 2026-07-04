USE healthcare_platform;

DROP PROCEDURE IF EXISTS add_index_if_missing;
DELIMITER //
CREATE PROCEDURE add_index_if_missing(IN p_table_name VARCHAR(80), IN p_index_name VARCHAR(80), IN p_ddl TEXT)
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = p_table_name
          AND INDEX_NAME = p_index_name
    ) THEN
        SET @index_sql = p_ddl;
        PREPARE stmt FROM @index_sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END//
DELIMITER ;

CALL add_index_if_missing('users', 'idx_users_email_type_status', 'ALTER TABLE users ADD INDEX idx_users_email_type_status (email, user_type, status)');
CALL add_index_if_missing('hospitals', 'idx_hospitals_user_verified', 'ALTER TABLE hospitals ADD INDEX idx_hospitals_user_verified (user_id, verified_status)');
CALL add_index_if_missing('hospitals', 'idx_hospitals_city_verified', 'ALTER TABLE hospitals ADD INDEX idx_hospitals_city_verified (city, verified_status)');
CALL add_index_if_missing('doctors', 'idx_doctors_name', 'ALTER TABLE doctors ADD INDEX idx_doctors_name (name)');
CALL add_index_if_missing('doctors', 'idx_doctors_specialization_verified', 'ALTER TABLE doctors ADD INDEX idx_doctors_specialization_verified (specialization, verified_status)');
CALL add_index_if_missing('doctors', 'idx_doctors_hospital_verified', 'ALTER TABLE doctors ADD INDEX idx_doctors_hospital_verified (hospital_id, verified_status)');
CALL add_index_if_missing('user_subscriptions', 'idx_user_subscriptions_active_lookup', 'ALTER TABLE user_subscriptions ADD INDEX idx_user_subscriptions_active_lookup (user_id, status, current_period_end, access_level)');
CALL add_index_if_missing('user_subscriptions', 'idx_user_subscriptions_plan_status', 'ALTER TABLE user_subscriptions ADD INDEX idx_user_subscriptions_plan_status (plan_slug, status)');
CALL add_index_if_missing('subscription_payments', 'idx_subscription_payments_reference_status', 'ALTER TABLE subscription_payments ADD INDEX idx_subscription_payments_reference_status (transaction_reference, status)');

DROP PROCEDURE IF EXISTS add_index_if_missing;

SET @qa_email = 'ASDF1223@gmail.com';
SET @qa_reference = 'QA-PRO-ASDF1223-GMAIL-COM';
SET @qa_user_id = (
    SELECT id
    FROM users
    WHERE LOWER(email) = LOWER(@qa_email)
    LIMIT 1
);

UPDATE users
SET status = 'active',
    subscription_status = 'premium',
    subscription_plan = 'premium-care',
    subscription_expires_at = '2099-12-31 23:59:59'
WHERE id = @qa_user_id;

INSERT INTO user_subscriptions
(user_id, plan_slug, access_level, provider, transaction_reference, status, amount, currency, current_period_start, current_period_end, metadata, created_at, updated_at)
SELECT
    @qa_user_id,
    'premium-care',
    'premium',
    'qa_allowlist',
    @qa_reference,
    'subscribed',
    0.00,
    'USD',
    NOW(),
    '2099-12-31 23:59:59',
    JSON_OBJECT('source', 'qa_premium_seed', 'email', @qa_email, 'note', 'Permanent local QA Pro/Premium entitlement.'),
    NOW(),
    NOW()
WHERE @qa_user_id IS NOT NULL
  AND NOT EXISTS (
      SELECT 1
      FROM user_subscriptions
      WHERE transaction_reference = @qa_reference
  );

UPDATE user_subscriptions
SET user_id = @qa_user_id,
    plan_slug = 'premium-care',
    access_level = 'premium',
    provider = 'qa_allowlist',
    status = 'subscribed',
    amount = 0.00,
    currency = 'USD',
    current_period_end = '2099-12-31 23:59:59',
    metadata = JSON_OBJECT('source', 'qa_premium_seed', 'email', @qa_email, 'note', 'Permanent local QA Pro/Premium entitlement.'),
    updated_at = NOW()
WHERE @qa_user_id IS NOT NULL
  AND transaction_reference = @qa_reference;

SET @qa_subscription_id = (
    SELECT id
    FROM user_subscriptions
    WHERE transaction_reference = @qa_reference
    LIMIT 1
);

INSERT INTO subscription_payments
(subscription_id, user_id, provider, provider_payment_id, transaction_reference, amount, currency, status, gateway_response, paid_at, created_at, updated_at)
SELECT
    @qa_subscription_id,
    @qa_user_id,
    'qa_allowlist',
    'QA-PRO-LOCAL',
    @qa_reference,
    0.00,
    'USD',
    'paid',
    JSON_OBJECT('message', 'Permanent local QA premium subscription activated without gateway payment.'),
    NOW(),
    NOW(),
    NOW()
WHERE @qa_user_id IS NOT NULL
  AND @qa_subscription_id IS NOT NULL
  AND NOT EXISTS (
      SELECT 1
      FROM subscription_payments
      WHERE transaction_reference = @qa_reference
  );
