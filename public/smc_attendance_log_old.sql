CREATE TABLE {$wpdb->prefix}smc_attendance_log (
    id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT(20) UNSIGNED NOT NULL,
    attendance_date DATE NOT NULL, -- تاريخ الحضور (فقط اليوم)
    attendance_timestamp DATETIME NOT NULL, -- وقت الحضور الدقيق
    points_awarded INT DEFAULT 0, -- النقاط الممنوحة لهذا الحضور
    PRIMARY KEY  (id),
    UNIQUE KEY unique_attendance (user_id, attendance_date), -- منع تسجيل الحضور أكثر من مرة في اليوم
    KEY user_id (user_id),
    KEY attendance_date (attendance_date)
) {$charset_collate};
