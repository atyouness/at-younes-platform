-- Run once in phpMyAdmin to use the requested three permission levels.
UPDATE roles SET name = 'supervisor', description = 'مشرف النظام' WHERE id = 3;
-- Keep any legacy role 4 row to avoid breaking existing foreign keys.