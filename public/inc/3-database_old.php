<?php
/**
 * Database Setup: Create and manage custom tables.
 * Includes version check to run dbDelta only when needed.
 *
 * @package SMC Group DZ Child
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Creates or updates custom database tables using dbDelta.
 * Runs only if the stored database version is lower than the defined SMC_DB_VERSION.
 */
function smc_create_custom_tables() {
    // --- Version Check ---
    // Ensure the version constant is defined (should be in 1-constants.php)
    if (!defined('SMC_DB_VERSION')) {
        error_log("SMC DB Error: SMC_DB_VERSION constant is not defined! Cannot check/update database.");
        // Optionally add an admin notice
        add_action('admin_notices', function() {
            echo '<div class="notice notice-error"><p>SMC Plugin Error: Database version constant (SMC_DB_VERSION) is missing. Database updates might not run.</p></div>';
        });
        return; // Stop execution if version constant is missing
    }

    $installed_db_version = get_option('smc_db_version', '0'); // Get stored version, default to '0'

    // Compare versions using version_compare for safety (handles formats like 1.0, 1.1, 1.1.0)
    if (version_compare($installed_db_version, SMC_DB_VERSION, '>=')) {
        // Database is up to date or newer, no need to run dbDelta
        // error_log("SMC DB Info: Database version ({$installed_db_version}) is up to date (".SMC_DB_VERSION."). Skipping dbDelta."); // Uncomment for debugging
        return;
    }

    error_log("SMC DB Info: Updating database structure from version {$installed_db_version} to " . SMC_DB_VERSION);
    // --- End Version Check ---

    global $wpdb;
    // Ensure upgrade.php is loaded (contains dbDelta)
    if (!function_exists('dbDelta')) {
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    }
    $charset_collate = $wpdb->get_charset_collate();
    $table_name_deposits_check = $wpdb->prefix . 'user_deposits';

    // --- Explicitly add missing columns and keys to user_deposits if they don't exist ---
    // This is a more robust way if dbDelta fails to add them.

    // Check and add investment_start_datetime column
    $column_check_ist = $wpdb->get_results($wpdb->prepare("SHOW COLUMNS FROM `{$table_name_deposits_check}` LIKE %s", 'investment_start_datetime'));
    if (empty($column_check_ist)) {
        $wpdb->query("ALTER TABLE `{$table_name_deposits_check}` ADD COLUMN `investment_start_datetime` DATETIME DEFAULT NULL AFTER `expected_daily_roi`");
        error_log("SMC DB Info: Explicitly ADDED investment_start_datetime column to {$table_name_deposits_check}.");
    }

    // Check and add last_profit_distribution_timestamp column if it doesn't exist
    $column_check_lpdts = $wpdb->get_results($wpdb->prepare("SHOW COLUMNS FROM `{$table_name_deposits_check}` LIKE %s", 'last_profit_distribution_timestamp'));
    if (empty($column_check_lpdts)) {
        // Check if the old column 'last_profit_distribution_date' exists to decide whether to ADD or MODIFY
        $old_column_check_lpdd = $wpdb->get_results($wpdb->prepare("SHOW COLUMNS FROM `{$table_name_deposits_check}` LIKE %s", 'last_profit_distribution_date'));
        if (!empty($old_column_check_lpdd)) {
            $wpdb->query("ALTER TABLE `{$table_name_deposits_check}` CHANGE COLUMN `last_profit_distribution_date` `last_profit_distribution_timestamp` BIGINT(20) DEFAULT NULL");
            error_log("SMC DB Info: Explicitly CHANGED last_profit_distribution_date to last_profit_distribution_timestamp in {$table_name_deposits_check}.");
        } else {
            $wpdb->query("ALTER TABLE `{$table_name_deposits_check}` ADD COLUMN `last_profit_distribution_timestamp` BIGINT(20) DEFAULT NULL AFTER `investment_start_datetime`");
            error_log("SMC DB Info: Explicitly ADDED last_profit_distribution_timestamp column to {$table_name_deposits_check}.");
        }
    }

    // Check and add final_margin_processed column
    $column_check_fmp = $wpdb->get_results($wpdb->prepare("SHOW COLUMNS FROM `{$table_name_deposits_check}` LIKE %s", 'final_margin_processed'));
    if (empty($column_check_fmp)) {
        $wpdb->query("ALTER TABLE `{$table_name_deposits_check}` ADD COLUMN `final_margin_processed` TINYINT(1) NOT NULL DEFAULT 0 AFTER `selected_investment_plan_index`");
        error_log("SMC DB Info: Explicitly ADDED final_margin_processed column to {$table_name_deposits_check}.");
    }

    // --- Table Definitions ---

    // 1. Extended User Data
    $table_name_extended = $wpdb->prefix . 'smc_user_extended_data';
    $sql_extended = "CREATE TABLE {$table_name_extended} (
        user_id BIGINT(20) UNSIGNED NOT NULL,
        father_name VARCHAR(255) DEFAULT NULL,
        birth_date DATE DEFAULT NULL,
        place_birth VARCHAR(255) DEFAULT NULL,
        gender VARCHAR(50) DEFAULT NULL,
        country VARCHAR(100) DEFAULT NULL,
        mobile_number VARCHAR(50) DEFAULT NULL,
        invitation_id VARCHAR(100) DEFAULT NULL,
        privacy_policy_accepted_at TIMESTAMP NULL DEFAULT NULL,
        PRIMARY KEY  (user_id)
    ) {$charset_collate};";
    dbDelta($sql_extended);

    // 2. User Deposits
    $table_name_deposits = $wpdb->prefix . 'user_deposits';
    $sql_deposits = "CREATE TABLE {$table_name_deposits} (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id BIGINT(20) UNSIGNED NOT NULL,
        amount DECIMAL(15, 2) NOT NULL,
        payment_method VARCHAR(100) NOT NULL,
        status VARCHAR(50) NOT NULL DEFAULT 'pending',
        deposit_date DATETIME NOT NULL,
        approval_date DATETIME DEFAULT NULL,
        approved_by BIGINT(20) UNSIGNED DEFAULT NULL,
        deposit_proof_path VARCHAR(255) DEFAULT NULL,
        deposit_type VARCHAR(50) DEFAULT 'daily_tasks' NOT NULL,
        investment_package VARCHAR(50) DEFAULT NULL,
        investment_shares INT DEFAULT NULL,
        investment_duration INT DEFAULT NULL,
        expected_daily_roi DECIMAL(10,7) DEFAULT NULL,
        investment_start_datetime DATETIME DEFAULT NULL,
        last_profit_distribution_timestamp BIGINT(20) DEFAULT NULL,
        selected_investment_plan_index TINYINT DEFAULT NULL,
        final_margin_processed TINYINT(1) NOT NULL DEFAULT 0,
        PRIMARY KEY  (id),
        INDEX user_id (user_id),
        INDEX status (status),
        INDEX deposit_type (deposit_type),
        INDEX investment_start_datetime (investment_start_datetime),
        INDEX last_profit_distribution_timestamp (last_profit_distribution_timestamp)
    ) {$charset_collate};";
    dbDelta($sql_deposits);

    // 3. User Deposit Withdrawals
    $table_name_withdrawals = $wpdb->prefix . 'user_withdrawals';
    $sql_withdrawals = "CREATE TABLE {$table_name_withdrawals} (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id BIGINT(20) UNSIGNED NOT NULL,
        amount DECIMAL(15, 2) NOT NULL,
        fee_amount DECIMAL(15, 2) DEFAULT 0.00 NULL,
        payment_method VARCHAR(100) NOT NULL,
        withdrawal_details TEXT DEFAULT NULL,
        status VARCHAR(50) NOT NULL DEFAULT 'pending',
        withdrawal_date DATETIME NOT NULL,
        approval_date DATETIME DEFAULT NULL,
        approved_by BIGINT(20) UNSIGNED DEFAULT NULL,
        withdrawal_type VARCHAR(50) DEFAULT 'tasks_deposit' NOT NULL,
        PRIMARY KEY  (id),
        INDEX user_id (user_id),
        INDEX status (status),
        INDEX withdrawal_type (withdrawal_type)
    ) {$charset_collate};";
    dbDelta($sql_withdrawals);

    // 4. User Profit Withdrawals
    $table_name_profit_withdrawals = $wpdb->prefix . 'user_profit_withdrawals';
    $sql_profit_withdrawals = "CREATE TABLE {$table_name_profit_withdrawals} (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id BIGINT(20) UNSIGNED NOT NULL,
        amount DECIMAL(15, 2) NOT NULL,
        fee_amount DECIMAL(15, 2) DEFAULT 0.00 NULL,
        payment_method VARCHAR(100) NOT NULL,
        withdrawal_details TEXT DEFAULT NULL,
        status VARCHAR(50) NOT NULL DEFAULT 'pending',
        withdrawal_date DATETIME NOT NULL,
        approval_date DATETIME DEFAULT NULL,
        approved_by BIGINT(20) UNSIGNED DEFAULT NULL,
        withdrawal_type VARCHAR(50) DEFAULT 'profit' NOT NULL,
        PRIMARY KEY  (id),
        INDEX user_id (user_id),
        INDEX status (status),
        INDEX withdrawal_type (withdrawal_type)
    ) {$charset_collate};";
    dbDelta($sql_profit_withdrawals);

    // 5. Daily Attendance Log
    $table_name_attendance = $wpdb->prefix . 'smc_attendance_log';
    $sql_attendance = "CREATE TABLE {$table_name_attendance} (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id BIGINT(20) UNSIGNED NOT NULL,
        attendance_date DATE NOT NULL,
        attendance_timestamp DATETIME NOT NULL,
        points_awarded INT DEFAULT 0,
        PRIMARY KEY  (id),
        UNIQUE KEY unique_attendance (user_id, attendance_date),
        INDEX user_id (user_id),
        INDEX attendance_date (attendance_date)
    ) {$charset_collate};";
    dbDelta($sql_attendance);

    // 6. Rewards Log
    $rewards_table = $wpdb->prefix . 'smc_rewards_log';
    $sql_rewards = "CREATE TABLE {$rewards_table} (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id BIGINT(20) UNSIGNED NOT NULL,
        reward_type VARCHAR(100) NOT NULL,
        amount DECIMAL(15, 2) NOT NULL,
        reward_timestamp DATETIME NOT NULL,
        source_user_id BIGINT(20) UNSIGNED DEFAULT NULL,
        related_info TEXT DEFAULT NULL,
        PRIMARY KEY  (id),
        INDEX user_id (user_id),
        INDEX reward_type (reward_type),
        INDEX reward_timestamp (reward_timestamp)
    ) {$charset_collate};";
    dbDelta($sql_rewards);

    // 7. Ad Deals Log
    $ad_deals_log_table = $wpdb->prefix . 'smc_ad_deals_log';
    $sql_ad_deals = "CREATE TABLE {$ad_deals_log_table} (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id BIGINT(20) UNSIGNED NOT NULL,
        completion_timestamp DATETIME NOT NULL,
        ad_name VARCHAR(255) DEFAULT NULL,
        ad_price DECIMAL(15, 2) NOT NULL,
        ad_tax DECIMAL(15, 2) NOT NULL,
        net_ad_price DECIMAL(15, 2) NOT NULL,
        profit_value DECIMAL(15, 2) NOT NULL,
        profit_tax DECIMAL(15, 2) NOT NULL,
        net_profit DECIMAL(15, 2) NOT NULL,
        user_benefit DECIMAL(15, 2) NOT NULL,
        profit_percentage DECIMAL(8, 5) NOT NULL,
        deal_id VARCHAR(10) DEFAULT NULL,
        ad_duration INT UNSIGNED NOT NULL,
        deal_tax DECIMAL(15, 2) NOT NULL,
        PRIMARY KEY  (id),
        INDEX user_id (user_id),
        INDEX completion_timestamp (completion_timestamp),
        INDEX deal_id (deal_id)
    ) {$charset_collate};";
    dbDelta($sql_ad_deals);

    // 8. Click Log
    $click_log_table = $wpdb->prefix . 'smc_click_log';
    $sql_clicks = "CREATE TABLE {$click_log_table} (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id BIGINT(20) UNSIGNED NOT NULL,
        button_id VARCHAR(255) DEFAULT NULL,
        page_url VARCHAR(2083) DEFAULT NULL,
        click_timestamp DATETIME NOT NULL,
        ip_address VARCHAR(100) DEFAULT NULL,
        user_agent TEXT DEFAULT NULL,
        PRIMARY KEY  (id),
        INDEX user_id (user_id),
        INDEX button_id (button_id),
        INDEX click_timestamp (click_timestamp)
    ) {$charset_collate};";
    dbDelta($sql_clicks);

    // 9. Ad Impressions Log
    $impressions_table = $wpdb->prefix . 'smc_ad_impressions';
    $sql_impressions = "CREATE TABLE {$impressions_table} (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        ad_id BIGINT(20) UNSIGNED NOT NULL,
        advertiser_id BIGINT(20) UNSIGNED DEFAULT NULL,
        user_id BIGINT(20) UNSIGNED NOT NULL,
        impression_timestamp DATETIME NOT NULL,
        ip_address VARCHAR(100) DEFAULT NULL,
        user_agent TEXT DEFAULT NULL,
        PRIMARY KEY  (id),
        INDEX ad_id (ad_id),
        INDEX user_id (user_id),
        INDEX impression_timestamp (impression_timestamp)
    ) {$charset_collate};";
    dbDelta($sql_impressions);

    // 10. Referral Log (Corrected Definition)
    $referrals_table = $wpdb->prefix . 'smc_referrals';
    $sql_referrals = "CREATE TABLE {$referrals_table} (
      id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
      referrer_user_id BIGINT(20) UNSIGNED NOT NULL,
      invitee_user_id BIGINT(20) UNSIGNED NOT NULL,
      invitation_code_used VARCHAR(100) DEFAULT NULL,
      referral_timestamp DATETIME NOT NULL,
      PRIMARY KEY  (id),
      UNIQUE KEY unique_referral (referrer_user_id, invitee_user_id),
      INDEX invitee_user_id (invitee_user_id)
    ) {$charset_collate};";
    dbDelta($sql_referrals);

    // 11. Scheduled Investment Withdrawal Requests
    $table_name_inv_withdraw_req = $wpdb->prefix . 'smc_investment_withdrawal_requests';
    $sql_inv_withdraw_req = "CREATE TABLE {$table_name_inv_withdraw_req} (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id BIGINT(20) UNSIGNED NOT NULL,
        deposit_id BIGINT(20) UNSIGNED NOT NULL,
        investment_type VARCHAR(100) NOT NULL,
        amount_requested DECIMAL(15, 2) NOT NULL,
        shares_to_release INT DEFAULT 0,
        request_timestamp DATETIME NOT NULL,
        scheduled_process_date DATETIME NOT NULL,
        status VARCHAR(50) NOT NULL DEFAULT 'scheduled',
        cancellation_reason TEXT DEFAULT NULL,
        processed_timestamp DATETIME DEFAULT NULL,
        admin_notes TEXT DEFAULT NULL,
        PRIMARY KEY  (id),
        INDEX user_id (user_id),
        INDEX deposit_id (deposit_id),
        INDEX status (status),
        INDEX scheduled_process_date (scheduled_process_date)
    ) {$charset_collate};";
    dbDelta($sql_inv_withdraw_req);

    // --- Update DB Version ---
    // Update the stored version number after dbDelta has run successfully
    update_option('smc_db_version', SMC_DB_VERSION);
    error_log("SMC DB Info: Database structure updated to version " . SMC_DB_VERSION);
    // --- End Update DB Version ---
}

/**
 * Checks the database version on admin load and triggers update if necessary.
 * This runs less frequently than admin_init.
 */
function smc_check_db_update_on_admin_load() {
    // The version check is inside smc_create_custom_tables now
    smc_create_custom_tables();
}

// --- Action Hooks ---

// Run on theme activation (good practice)
add_action('after_switch_theme', 'smc_create_custom_tables');

// Run on admin menu load (less frequent than admin_init)
add_action('admin_menu', 'smc_check_db_update_on_admin_load', 1); // Priority 1 to run early

// Remove or comment out the admin_init hook to prevent excessive runs
// add_action('admin_init', 'smc_create_custom_tables');

?>
