-- Run once in phpMyAdmin after migration 001.
-- This intentionally requires every existing account to verify its email.
UPDATE users
SET status = 'pending',
    is_active = 0,
    email_verified_at = NULL,
    verification_token_hash = NULL,
    verification_expires_at = NULL;