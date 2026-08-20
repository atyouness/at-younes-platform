-- Run once in phpMyAdmin for an existing users table.
ALTER TABLE users
    ADD COLUMN IF NOT EXISTS show_phone_to_level_2 TINYINT(1) NOT NULL DEFAULT 0 AFTER whatsapp,
    ADD COLUMN IF NOT EXISTS show_phone_to_level_3 TINYINT(1) NOT NULL DEFAULT 0 AFTER show_phone_to_level_2;