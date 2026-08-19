-- Run once in phpMyAdmin for an existing users table.
ALTER TABLE users
    ADD COLUMN IF NOT EXISTS whatsapp VARCHAR(20) NULL AFTER phone;