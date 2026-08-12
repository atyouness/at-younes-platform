CREATE TABLE {$wpdb->prefix}smc_rewards_log (
    id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT(20) UNSIGNED NOT NULL, -- المستخدم الذي حصل على المكافأة
    reward_type VARCHAR(100) NOT NULL, -- (e.g., 'referral_l1', 'task_l2', 'rank_vip1', 'agent_city')
    amount DECIMAL(15, 2) NOT NULL, -- قيمة المكافأة بالدينار
    reward_timestamp DATETIME NOT NULL, -- وقت منح المكافأة
    source_user_id BIGINT(20) UNSIGNED DEFAULT NULL, -- المستخدم المصدر (مثل المدعو الذي قام بالإيداع)
    related_info TEXT DEFAULT NULL, -- معلومات إضافية (مثل ID الإيداع، اسم الرتبة)
    PRIMARY KEY  (id),
    KEY user_id (user_id),
    KEY reward_type (reward_type),
    KEY reward_timestamp (reward_timestamp)
) {$charset_collate};
