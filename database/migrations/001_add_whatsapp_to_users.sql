-- Run once in phpMyAdmin for an existing users table.
ALTER TABLE users
    ADD COLUMN IF NOT EXISTS whatsapp VARCHAR(20) NULL AFTER phone,
    ADD COLUMN IF NOT EXISTS email_verified_at TIMESTAMP NULL AFTER is_active,
    ADD COLUMN IF NOT EXISTS verification_token_hash CHAR(64) NULL AFTER email_verified_at,
    ADD COLUMN IF NOT EXISTS verification_expires_at TIMESTAMP NULL AFTER verification_token_hash;