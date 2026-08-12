<?php
/**
 * Cron Jobs and Scheduled Events
 * Handles monthly salary payments and daily investment profit distribution.
 *
 * @package SMC Group DZ Child
 */

if (!defined('ABSPATH')) exit;

// --- Cron Hook Names ---
define('SMC_MONTHLY_SALARY_CRON_HOOK', 'smc_monthly_salary_payment_hook');
define('SMC_PROCESS_INVESTMENT_PROFIT_CRON_HOOK', 'smc_process_investment_profit_hook'); // New hook for more frequent processing
define('SMC_CALCULATE_FINAL_MARGIN_CRON_HOOK', 'smc_calculate_final_margin_hook'); // New hook for final margin

// --- Schedule the Cron Jobs ---
/**
 * Schedules all custom cron jobs for the SMC theme.
 * Runs on theme activation or init.
 */
function smc_schedule_custom_crons() {
    // Add 'every_minute' schedule if it doesn't exist
    if (!wp_get_schedule('every_minute')) {
        add_filter('cron_schedules', function($schedules) {
            $schedules['every_minute'] = array(
                'interval' => 60, // 60 seconds
                'display'  => __('Every Minute', 'smc'),
            );
            return $schedules;
        });
    }

    // 1. Schedule Monthly Salary Payment
    if (!wp_next_scheduled(SMC_MONTHLY_SALARY_CRON_HOOK)) {
        wp_schedule_event(strtotime('tomorrow'), 'monthly', SMC_MONTHLY_SALARY_CRON_HOOK);
        error_log("SMC Cron: Monthly salary payment cron job scheduled.");
    }

    // 2. Schedule Investment Profit Distribution (now more frequent)
    if (!wp_next_scheduled(SMC_PROCESS_INVESTMENT_PROFIT_CRON_HOOK)) {
        wp_schedule_event(time(), 'every_minute', SMC_PROCESS_INVESTMENT_PROFIT_CRON_HOOK); // Example: Run every minute
        error_log("SMC Cron: Investment profit processing cron job scheduled to run every minute.");
    }
    // 3. Schedule Final Margin Calculation (e.g., daily)
    if (!wp_next_scheduled(SMC_CALCULATE_FINAL_MARGIN_CRON_HOOK)) {
        wp_schedule_event(strtotime('tomorrow 02:00:00'), 'daily', SMC_CALCULATE_FINAL_MARGIN_CRON_HOOK); // Example: Run daily at 2 AM
        error_log("SMC Cron: Final margin calculation cron job scheduled.");
    }
}
// Schedule on theme activation or init
add_action('init', 'smc_schedule_custom_crons'); // Using 'init' ensures it's checked regularly

/**
 * Unschedules custom cron jobs.
 * Useful for theme deactivation or plugin uninstall.
 */
function smc_unschedule_custom_crons() {
    // Unschedule monthly salary
    $timestamp_monthly = wp_next_scheduled(SMC_MONTHLY_SALARY_CRON_HOOK);
    if ($timestamp_monthly) {
        wp_unschedule_event($timestamp_monthly, 'monthly', SMC_MONTHLY_SALARY_CRON_HOOK);
        error_log("SMC Cron: Monthly salary payment cron job unscheduled.");
    }

    // Unschedule investment profit processing
    $timestamp_process_profit = wp_next_scheduled(SMC_PROCESS_INVESTMENT_PROFIT_CRON_HOOK);
    if ($timestamp_process_profit) {
        wp_unschedule_event($timestamp_process_profit, 'every_minute', SMC_PROCESS_INVESTMENT_PROFIT_CRON_HOOK); // Match the schedule
        error_log("SMC Cron: Investment profit processing cron job unscheduled.");
    }

    // Unschedule final margin calculation
    $timestamp_final_margin = wp_next_scheduled(SMC_CALCULATE_FINAL_MARGIN_CRON_HOOK);
    if ($timestamp_final_margin) {
        wp_unschedule_event($timestamp_final_margin, 'daily', SMC_CALCULATE_FINAL_MARGIN_CRON_HOOK);
        error_log("SMC Cron: Final margin calculation cron job unscheduled.");
    }
}

// --- Hook Cron Functions ---
add_action(SMC_MONTHLY_SALARY_CRON_HOOK, 'smc_perform_monthly_salary_payment');
add_action(SMC_PROCESS_INVESTMENT_PROFIT_CRON_HOOK, 'smc_perform_investment_profit_distribution');
add_action(SMC_CALCULATE_FINAL_MARGIN_CRON_HOOK, 'smc_calculate_and_record_final_profit_margin');


/**
 * Awards monthly salaries to eligible users.
 * This function is triggered by the WordPress cron system.
 */
function smc_perform_monthly_salary_payment() {
    error_log("SMC Cron: Starting monthly salary payment process at " . current_time('mysql'));

    if (!function_exists('smc_get_user_rank') || !function_exists('smc_get_default_reward_settings') || !defined('SMC_PROFIT_BALANCE') || !defined('SMC_REWARD_SETTINGS_OPTION')) {
        error_log("SMC Cron Error (Salary): Required functions or constants missing.");
        return;
    }

    global $wpdb;
    $rewards_table = $wpdb->prefix . 'smc_rewards_log';
    $current_time_mysql = current_time('mysql');
    $current_timestamp = current_time('timestamp');
    $thirty_days_ago = strtotime('-30 days', $current_timestamp);

    $users = get_users(['fields' => 'ID']);
    if (empty($users)) {
        error_log("SMC Cron (Salary): No users found.");
        return;
    }

    $reward_settings = get_option(SMC_REWARD_SETTINGS_OPTION, smc_get_default_reward_settings());
    $processed_count = 0;
    $paid_count = 0;

    foreach ($users as $user_id) {
        $processed_count++;
        $last_payment_timestamp = (int) get_user_meta($user_id, 'smc_last_monthly_salary_date', true);

        if ($last_payment_timestamp && $last_payment_timestamp > $thirty_days_ago) {
            continue;
        }

        $current_rank = smc_get_user_rank($user_id);
        $monthly_salary = 0;
        $rank_setting_key_raw = strtolower($current_rank);
        $rank_setting_key_for_lookup = '';

        if (strpos($rank_setting_key_raw, 'vip') === 0) {
            $rank_setting_key_for_lookup = 'rank_' . $rank_setting_key_raw;
        } elseif (strpos($rank_setting_key_raw, 'agent_') === 0) {
            $rank_setting_key_for_lookup = $rank_setting_key_raw;
        }

        if (!empty($rank_setting_key_for_lookup) &&
            isset($reward_settings[$rank_setting_key_for_lookup]['type']) &&
            $reward_settings[$rank_setting_key_for_lookup]['type'] === 'fixed_monthly' &&
            isset($reward_settings[$rank_setting_key_for_lookup]['value'])) {
            $monthly_salary = (float) $reward_settings[$rank_setting_key_for_lookup]['value'];
        }

        if ($monthly_salary > 0) {
            $wpdb->query('START TRANSACTION');
            $current_profit_balance = (float) (get_user_meta($user_id, SMC_PROFIT_BALANCE, true) ?? 0.0);
            $new_profit_balance = $current_profit_balance + $monthly_salary;
            $update_profit_result = update_user_meta($user_id, SMC_PROFIT_BALANCE, $new_profit_balance);

            $log_insert_result = $wpdb->insert($rewards_table, [
                'user_id' => $user_id, 'reward_type' => 'monthly_salary',
                'amount' => $monthly_salary, 'reward_timestamp' => $current_time_mysql,
                'source_user_id' => 0, 'related_info' => 'Rank: ' . $current_rank,
            ], ['%d', '%s', '%f', '%s', '%d', '%s']);

            $update_date_result = update_user_meta($user_id, 'smc_last_monthly_salary_date', $current_timestamp);

            if ($update_profit_result && $log_insert_result && $update_date_result) {
                $wpdb->query('COMMIT');
                $paid_count++;
                error_log("SMC Cron (Salary): Paid $monthly_salary to user $user_id (Rank: $current_rank).");
            } else {
                $wpdb->query('ROLLBACK');
                error_log("SMC Cron Error (Salary): Failed to pay user $user_id. Rolled back. DB Error: " . $wpdb->last_error);
            }
        }
    }
    error_log("SMC Cron: Monthly salary payment process completed. Processed $processed_count users, Paid $paid_count users at " . current_time('mysql'));
}


/**
 * Distributes profits for active investments based on their plan's unit.
 * This function is triggered by the WordPress cron system.
 */
function smc_perform_investment_profit_distribution() {
    global $wpdb;
    $user_deposits_table = $wpdb->prefix . 'user_deposits';
    $rewards_table = $wpdb->prefix . 'smc_rewards_log';

    $current_time_mysql = current_time('mysql');
    $current_timestamp_unix = current_time('timestamp');

    error_log("SMC Cron (Investment Profit): Starting investment profit distribution process at " . $current_time_mysql);

    if (!defined('SMC_PROFIT_BALANCE')) {
        error_log("SMC Cron Error (Investment Profit): SMC_PROFIT_BALANCE constant is not defined.");
        return;
    }

    // Fetch all investment settings once
    $all_investment_settings = get_option('smc_investment_types_settings', []);

    $eligible_investments = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM {$user_deposits_table}
             WHERE status = %s
             AND deposit_type != %s
             AND investment_start_datetime IS NOT NULL
             AND investment_start_datetime <= %s
             AND investment_duration > 0
             AND selected_investment_plan_index IS NOT NULL
             ",
            'approved',
            'daily_tasks',
            $current_time_mysql
        )
    );

    if (empty($eligible_investments)) {
        // error_log("SMC Cron (Investment Profit): No eligible investments found for profit distribution at this time.");
        return;
    }

    error_log("SMC Cron (Investment Profit): Found " . count($eligible_investments) . " potentially eligible investments.");
    $distributed_count = 0;

    foreach ($eligible_investments as $investment) {
        $investment_id = $investment->id;
        $user_id = $investment->user_id;
        $investment_amount = (float) $investment->amount;
        $plan_avg_roi_decimal = (float) $investment->expected_daily_roi; // This is the avg_roi from the selected plan, stored as decimal
        $investment_start_datetime_str = $investment->investment_start_datetime;
        $investment_duration_days = (int) $investment->investment_duration; // This is the overall project duration from the main config
        $selected_plan_index = $investment->selected_investment_plan_index;

        // --- DEBUG LOGGING START ---
        // Check if the main investment type configuration exists before proceeding
        if (!isset($all_investment_settings[$investment->deposit_type])) {
            error_log("SMC Cron Warning (Investment Profit): Investment ID {$investment_id} for user {$user_id} refers to a deposit_type '{$investment->deposit_type}' not found in current settings. Skipping profit distribution.");
            continue;
        }
        // --- END NEW CHECK ---
        error_log("SMC CRON DEBUG (Inv ID: {$investment_id}): User: {$user_id}, Type: {$investment->deposit_type}, Plan Index: {$selected_plan_index}");
        // --- DEBUG LOGGING END ---

        if (empty($investment_start_datetime_str) || $investment_duration_days <= 0 || $plan_avg_roi_decimal <= 0) {
            error_log("SMC Cron Warning (Investment Profit): Investment ID {$investment_id} for user {$user_id} has invalid core data (start_date, duration, or ROI). ROI: {$plan_avg_roi_decimal}. Skipping.");
            continue;
        }

        $plan_details = smc_get_investment_plan_details($investment->deposit_type, $selected_plan_index);

        // --- DEBUG LOGGING START ---
        error_log("SMC CRON DEBUG (Inv ID: {$investment_id}): Plan Details from smc_get_investment_plan_details: " . print_r($plan_details, true));
        // --- DEBUG LOGGING END ---

        if (!$plan_details) {
            error_log("SMC Cron Error (Investment Profit): Could not retrieve plan details for investment ID {$investment_id} (Type: {$investment->deposit_type}, Plan Index: {$selected_plan_index}). Skipping.");
            continue;
        }

        $plan_roi_unit = $plan_details['unit']; // 'per_minute', 'hourly', 'daily'
        $plan_duration_value = (int) $plan_details['duration_value'];
        $plan_duration_unit = $plan_details['duration_unit']; // 'minutes', 'hours', 'days'

        // --- DEBUG LOGGING START ---
        error_log("SMC CRON DEBUG (Inv ID: {$investment_id}): Plan ROI Unit: {$plan_roi_unit}, Plan Avg ROI Decimal (from DB): {$plan_avg_roi_decimal}");
        // --- DEBUG LOGGING END ---

        $plan_duration_in_seconds = 0;
        if ($plan_duration_unit === 'minutes') $plan_duration_in_seconds = $plan_duration_value * 60;
        elseif ($plan_duration_unit === 'hours') $plan_duration_in_seconds = $plan_duration_value * 3600;
        else $plan_duration_in_seconds = $plan_duration_value * 86400; // days

        if ($plan_duration_in_seconds <= 0) {
            error_log("SMC Cron Warning (Investment Profit): Investment ID {$investment_id} for user {$user_id} has an invalid plan duration (<=0 seconds). Plan: " . print_r($plan_details, true) . ". Skipping.");
            continue;
        }

        $investment_start_timestamp = strtotime($investment_start_datetime_str);
        $investment_profit_period_ends_timestamp = $investment_start_timestamp + $plan_duration_in_seconds;

        // --- DEBUG LOGGING START ---
        error_log("SMC CRON DEBUG (Inv ID: {$investment_id}): Investment Start TS: " . date('Y-m-d H:i:s', $investment_start_timestamp) . " ({$investment_start_timestamp})");
        error_log("SMC CRON DEBUG (Inv ID: {$investment_id}): Plan Duration (sec): {$plan_duration_in_seconds}, Profit Period Ends TS: " . date('Y-m-d H:i:s', $investment_profit_period_ends_timestamp) . " ({$investment_profit_period_ends_timestamp})");
        error_log("SMC CRON DEBUG (Inv ID: {$investment_id}): Current TS: " . date('Y-m-d H:i:s', $current_timestamp_unix) . " ({$current_timestamp_unix})");
        // --- DEBUG LOGGING END ---

        $last_dist_ts = (int) $investment->last_profit_distribution_timestamp;
        $time_since_last_dist = $current_timestamp_unix - $last_dist_ts;
        $should_distribute_now = false;
        $distribution_interval_seconds = 86400; // Default daily

        if ($plan_roi_unit === 'per_minute') {
            $distribution_interval_seconds = 60;
        } elseif ($plan_roi_unit === 'hourly') {
            $distribution_interval_seconds = 3600;
        }

        // --- DEBUG LOGGING START ---
        error_log("SMC CRON DEBUG (Inv ID: {$investment_id}): Last Dist TS: {$last_dist_ts} (" . ($last_dist_ts ? date('Y-m-d H:i:s', $last_dist_ts) : 'N/A') . ")");
        error_log("SMC CRON DEBUG (Inv ID: {$investment_id}): Time Since Last Dist: {$time_since_last_dist}, Distribution Interval: {$distribution_interval_seconds}");
        // --- DEBUG LOGGING END ---

        if ($last_dist_ts === 0 && $current_timestamp_unix >= $investment_start_timestamp) {
            $should_distribute_now = true;
        } elseif ($last_dist_ts > 0 && $time_since_last_dist >= $distribution_interval_seconds) {
            $should_distribute_now = true;
        }
        
        // --- DEBUG LOGGING START ---
        error_log("SMC CRON DEBUG (Inv ID: {$investment_id}): Should Distribute Now? " . ($should_distribute_now ? 'YES' : 'NO'));
        // --- DEBUG LOGGING END ---

        if ($current_timestamp_unix >= $investment_profit_period_ends_timestamp) {
            error_log("SMC CRON INFO (Inv ID: {$investment_id}): Profit period for this plan has ENDED.");
            continue; 
        }


        if ($should_distribute_now) {
            $periodic_profit_amount = 0;

            if ($plan_roi_unit === 'per_minute') {
                $periodic_profit_amount = $investment_amount * $plan_avg_roi_decimal;
            } elseif ($plan_roi_unit === 'hourly') {
                // This assumes $plan_avg_roi_decimal (from expected_daily_roi) is the DAILY ROI rate.
                $periodic_profit_amount = $investment_amount * ($plan_avg_roi_decimal / 24);
            } else { // daily
                $periodic_profit_amount = $investment_amount * $plan_avg_roi_decimal;
            }
            
            // --- DEBUG LOGGING START ---
            error_log("SMC CRON DEBUG (Inv ID: {$investment_id}): Calculated Periodic Profit Amount: {$periodic_profit_amount}");
            // --- DEBUG LOGGING END ---


            if ($periodic_profit_amount <= 0) {
                error_log("SMC Cron Info (Investment Profit): Investment ID {$investment_id} (User {$user_id}) calculated periodic profit is zero or negative ({$periodic_profit_amount}). ROI: {$plan_avg_roi_decimal}, Amount: {$investment_amount}, Unit: {$plan_roi_unit}. Skipping.");
                continue;
            }

            $wpdb->query('START TRANSACTION');
            $current_profit_balance = (float) (get_user_meta($user_id, SMC_PROFIT_BALANCE, true) ?? 0.0);
            $new_profit_balance = $current_profit_balance + $periodic_profit_amount;
            $updated_meta = update_user_meta($user_id, SMC_PROFIT_BALANCE, $new_profit_balance);

            $inserted_log = $wpdb->insert(
                $rewards_table,
                [
                    'user_id' => $user_id,
                    'reward_type' => 'investment_periodic_profit',
                    'amount' => $periodic_profit_amount,
                    'reward_timestamp' => $current_time_mysql,
                    'source_user_id' => 0, 
                    'related_info' => "Investment ID: {$investment_id}, Type: {$investment->deposit_type}, Plan Index: {$selected_plan_index}, Original Investment: " . number_format($investment_amount, 2) . ", Plan Avg ROI: " . ($plan_avg_roi_decimal * 100) . "% per " . $plan_roi_unit . "."
                ],
                ['%d', '%s', '%f', '%s', '%d', '%s']
            );

            $updated_deposit_date = $wpdb->update(
                $user_deposits_table,
                ['last_profit_distribution_timestamp' => $current_timestamp_unix],
                ['id' => $investment_id],
                ['%d'], 
                ['%d']  
            );

            if ($updated_meta !== false && $inserted_log !== false && $updated_deposit_date !== false) {
                $wpdb->query('COMMIT');
                $distributed_count++;
                error_log("SMC Cron (Investment Profit): Successfully distributed {$periodic_profit_amount} for investment ID {$investment_id} (User {$user_id}). Unit: {$plan_roi_unit}.");
            } else {
                $wpdb->query('ROLLBACK');
                error_log("SMC Cron Error (Investment Profit): Failed to distribute profit for investment ID {$investment_id} (User {$user_id}). Rolled back. DB Error: " . $wpdb->last_error . " Meta: " . ($updated_meta?'OK':'Fail') . " Log: " . ($inserted_log?'OK':'Fail') . " DepositDate: " . ($updated_deposit_date?'OK':'Fail'));
            }
        }
    }
    error_log("SMC Cron (Investment Profit): Investment profit distribution process completed. Distributed profits for $distributed_count investments at " . current_time('mysql'));
}

/**
 * Calculates and records the final profit margin for completed investments.
 * This function is triggered by the WordPress cron system (e.g., daily).
 */
function smc_calculate_and_record_final_profit_margin() {
    global $wpdb;
    $user_deposits_table = $wpdb->prefix . 'user_deposits';
    $rewards_table = $wpdb->prefix . 'smc_rewards_log';
    $current_time_mysql = current_time('mysql');
    $current_timestamp_unix = current_time('timestamp');

    error_log("SMC Cron (Final Margin): Starting final profit margin calculation process at " . $current_time_mysql . " - Timestamp: " . $current_timestamp_unix);

    $all_investment_settings = get_option('smc_investment_types_settings', []);

    $completed_investments = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM {$user_deposits_table}
             WHERE status = %s
             AND deposit_type != %s
             AND investment_start_datetime IS NOT NULL AND investment_duration > 0
             AND (UNIX_TIMESTAMP(investment_start_datetime) + (investment_duration * 24 * 60 * 60)) < %d
             AND final_margin_processed = 0",
            'approved', 
            'daily_tasks',
            $current_timestamp_unix 
        )
    );

    if (empty($completed_investments)) {
        error_log("SMC Cron (Final Margin): No completed investments found needing final margin calculation.");
        return;
    }
    error_log("SMC Cron (Final Margin): Found " . count($completed_investments) . " potentially eligible completed investments for final margin.");

    foreach ($completed_investments as $investment) {
        $investment_id = $investment->id;
        $user_id = $investment->user_id;
        $investment_type_key = $investment->deposit_type;
        $investment_config = $all_investment_settings[$investment_type_key] ?? null;

        if (!$investment_config) {
            error_log("SMC Cron (Final Margin): Configuration not found for investment type '{$investment_type_key}'. Skipping deposit ID {$investment_id}.");
            continue;
        }

        $actual_project_profit_total = isset($investment_config['actual_final_profit']) && is_numeric($investment_config['actual_final_profit'])
            ? (float)$investment_config['actual_final_profit']
            : null;
        $project_final_margin_is_recorded_by_admin = !empty($investment_config['final_profit_margin_recorded']);

        if ($actual_project_profit_total === null || !$project_final_margin_is_recorded_by_admin) {
            error_log("SMC Cron (Final Margin): 'Actual Final Profit for Project' not set, not numeric, or project margin not marked as recorded by admin for investment type '{$investment_type_key}'. Skipping deposit ID {$investment_id}. Actual Profit: " . print_r($actual_project_profit_total, true) . ", Admin Recorded Flag: " . ($project_final_margin_is_recorded_by_admin ? 'Yes' : 'No'));
            continue;
        }

        $total_project_shares = isset($investment_config['total_shares']) && is_numeric($investment_config['total_shares']) ? (int)$investment_config['total_shares'] : 0;
        $company_shares = isset($investment_config['company_shares']) && is_numeric($investment_config['company_shares']) ? (int)$investment_config['company_shares'] : 0;
        $investor_shares_total = $total_project_shares - $company_shares;

        if ($investor_shares_total <= 0) {
            error_log("SMC Cron (Final Margin): Total investor shares is zero or less for investment type '{$investment_type_key}' (Total: {$total_project_shares}, Company: {$company_shares}). Cannot calculate per-share profit. Skipping deposit ID {$investment_id}.");
            continue;
        }

        $actual_profit_per_share = $actual_project_profit_total / $investor_shares_total;
        $user_investment_shares = (int) $investment->investment_shares;
        $user_actual_profit_share = $actual_profit_per_share * $user_investment_shares;

        $total_distributed_periodic_profit = (float) $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(amount) FROM {$rewards_table}
             WHERE reward_type = %s
             AND user_id = %d
             AND related_info LIKE %s", 
            'investment_periodic_profit', 
            $user_id,
            $wpdb->esc_like("Investment ID: {$investment_id}") . '%'
        ));

        $final_margin = $user_actual_profit_share - $total_distributed_periodic_profit;

        error_log("SMC Cron (Final Margin): Deposit ID {$investment_id}, User ID {$user_id}, Type '{$investment_type_key}'. Project Actual Profit: {$actual_project_profit_total}, Investor Shares: {$investor_shares_total}, Profit/Share: {$actual_profit_per_share}. User Shares: {$user_investment_shares}, User Actual Share: {$user_actual_profit_share}. Distributed Periodic: {$total_distributed_periodic_profit}. Final Margin: {$final_margin}.");

        if ($final_margin == 0) { 
             error_log("SMC Cron (Final Margin): Final margin is zero for deposit ID {$investment_id}. Marking as processed without reward log.");
             $wpdb->update(
                $user_deposits_table,
                ['final_margin_processed' => 1],
                ['id' => $investment_id],
                ['%d'],
                ['%d']
            );
            continue; 
        }
        if ($final_margin < 0) {
            error_log("SMC Cron Warning (Final Margin): Calculated final margin is NEGATIVE ({$final_margin}) for deposit ID {$investment_id}. This indicates periodic profits exceeded actual share. No negative margin will be applied. Marking as processed.");
            $wpdb->update(
                $user_deposits_table,
                ['final_margin_processed' => 1],
                ['id' => $investment_id],
                ['%d'],
                ['%d']
            );
            continue;
        }

        $wpdb->query('START TRANSACTION');

        $current_profit_balance_fm = (float) (get_user_meta($user_id, SMC_PROFIT_BALANCE, true) ?? 0.0);
        $new_profit_balance_fm = $current_profit_balance_fm + $final_margin;
        $updated_profit_meta = update_user_meta($user_id, SMC_PROFIT_BALANCE, $new_profit_balance_fm);

        $inserted_margin_log = false;
        if ($updated_profit_meta) {
            $inserted_margin_log = $wpdb->insert(
                $rewards_table,
                [
                    'user_id' => $user_id,
                    'reward_type' => 'investment_final_margin',
                    'amount' => $final_margin,
                    'reward_timestamp' => $current_time_mysql,
                    'source_user_id' => 0, 
                    'related_info' => "Final margin for Investment ID: {$investment_id}. Type: {$investment_type_key}. Actual User Share: " . number_format($user_actual_profit_share, 2) . ", Distributed Periodic: " . number_format($total_distributed_periodic_profit, 2)
                ],
                ['%d', '%s', '%f', '%s', '%d', '%s']
            );
        }

        $updated_deposit_flag = false;
        if ($inserted_margin_log) { 
            $updated_deposit_flag = $wpdb->update(
                $user_deposits_table,
                ['final_margin_processed' => 1],
                ['id' => $investment_id],
                ['%d'],
                ['%d']
            );
        }

        if ($updated_profit_meta && $inserted_margin_log && $updated_deposit_flag) {
            $wpdb->query('COMMIT');
            error_log("SMC Cron (Final Margin): Successfully recorded final margin of {$final_margin} for deposit ID {$investment_id}, User ID {$user_id}.");
        } else {
            $wpdb->query('ROLLBACK');
            error_log("SMC Cron (Final Margin) Error: Failed to process final margin for deposit ID {$investment_id}. Profit Meta: " . ($updated_profit_meta?'OK':'Fail') . ", Inserted Log: " . ($inserted_margin_log ? 'Yes' : 'No') . ", Updated Flag: " . ($updated_deposit_flag ? 'Yes' : 'No') . ". DB Error: " . $wpdb->last_error);
        }
    }
    error_log("SMC Cron (Final Margin): Process completed at " . current_time_mysql);
}
?>


