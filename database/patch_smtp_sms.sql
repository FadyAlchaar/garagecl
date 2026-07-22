-- ============================================================
-- PATCH: Add SMTP + SMS settings columns
-- Run this in phpMyAdmin if your database already exists
-- ============================================================
USE garage_cl;

ALTER TABLE settings
    ADD COLUMN IF NOT EXISTS smtp_host   VARCHAR(255) DEFAULT 'localhost',
    ADD COLUMN IF NOT EXISTS smtp_port   INT          DEFAULT 587,
    ADD COLUMN IF NOT EXISTS smtp_secure VARCHAR(10)  DEFAULT 'tls',
    ADD COLUMN IF NOT EXISTS smtp_user   VARCHAR(255) DEFAULT '',
    ADD COLUMN IF NOT EXISTS smtp_pass   VARCHAR(255) DEFAULT '',
    ADD COLUMN IF NOT EXISTS smtp_from   VARCHAR(255) DEFAULT '',
    ADD COLUMN IF NOT EXISTS smtp_name   VARCHAR(255) DEFAULT '',
    ADD COLUMN IF NOT EXISTS sms_provider VARCHAR(100) DEFAULT '',
    ADD COLUMN IF NOT EXISTS sms_api_key  VARCHAR(500) DEFAULT '',
    ADD COLUMN IF NOT EXISTS sms_api_url  VARCHAR(500) DEFAULT '',
    ADD COLUMN IF NOT EXISTS sms_sender   VARCHAR(100) DEFAULT '';

SELECT 'SMTP and SMS columns added successfully' AS result;
