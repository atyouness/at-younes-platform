<?php
/**
 * Helper Functions
 *
 * @package SMC Group DZ Child
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Retrieves SMC specific user data with defaults.
 * Ensures daily counters are reset if needed.
 *
 * @param int $user_id The ID of the user.
 * @return array An array containing user data, or empty array if user_id is invalid.
 */
function smc_get_user_data($user_id) {
    if (!$user_id || !is_numeric($user_id) || $user_id <= 0) {
        error_log("SMC Error: Invalid user ID provided to smc_get_user_data: " . print_r($user_id, true));
        return []; // Return empty if invalid user ID
    }

    global $wpdb;
    $user_deposits_table = $wpdb->prefix . 'user_deposits';
    $current_time_unix = current_time('timestamp');

    $data = [
        // This 'current_deposit' will represent the *spendable tasks deposit balance* for the dashboard.
        // It's calculated as active original task deposits MINUS ad costs for today.
        'current_deposit'                 => 0.0, // Initialize, will be recalculated specifically for tasks display
        'profit_balance' => (float) (get_user_meta($user_id, SMC_PROFIT_BALANCE, true) ?? 0.0),
        'last_deposit_timestamp' => (int) (get_user_meta($user_id, SMC_LAST_DEPOSIT_TIMESTAMP, true) ?? 0),
        'deposit_end_date' => get_user_meta($user_id, SMC_DEPOSIT_END_DATE, true) ?: null,
        'ads_watched_today' => (int) (get_user_meta($user_id, SMC_ADS_WATCHED_TODAY, true) ?? 0),
        'last_ad_watch_date' => get_user_meta($user_id, SMC_LAST_AD_WATCH_DATE, true) ?: null,
        'daily_ad_limit' => (int) (get_user_meta($user_id, SMC_DAILY_AD_LIMIT, true) ?? 0),
        'total_clicks' => (int) (get_user_meta($user_id, SMC_TOTAL_CLICKS, true) ?? 0),
        'daily_clicks' => (int) (get_user_meta($user_id, SMC_DAILY_CLICKS, true) ?? 0),
        'last_click_date' => get_user_meta($user_id, SMC_LAST_CLICK_DATE, true) ?: null,
        'referred_by' => (int) (get_user_meta($user_id, SMC_REFERRED_BY, true) ?? 0),
        'referral_code' => get_user_meta($user_id, SMC_REFERRAL_CODE, true) ?: null,
        'points_balance' => (int) (get_user_meta($user_id, SMC_POINTS_BALANCE, true) ?? 0),
        'daily_profit' => (float) (get_user_meta($user_id, SMC_DAILY_EARNINGS_TOTAL, true) ?? 0.0),
        'total_profit' => (float) (get_user_meta($user_id, SMC_PROFIT_BALANCE, true) ?? 0.0), // total_profit is the same as profit_balance
        'last_earning_reset_date' => get_user_meta($user_id, SMC_LAST_EARNING_RESET_DATE, true) ?: null,
        // New fields for tasks-specific deposit info
        'current_tasks_deposit_balance' => 0.0, // This will hold the sum of *original* active task deposits
        'last_tasks_deposit_timestamp' => null,
        'tasks_deposit_end_date_str' => null, // Formatted string for display
        'tasks_deposit_end_timestamp_for_calc' => null, // Raw timestamp for calculations
        'active_investments_details' => [], // For individual investment details
        'total_active_investment_amount' => 0.0, // Sum of all active investment deposits
        'withdrawable_investment_amount' => 0.0, // Sum of withdrawable investment deposits
        'tasks_deposit_withdrawable_amount' => 0.0, // Explicitly for tasks deposit
        'attended_today' => false, // Will be set later
    ];

    // --- Fetch and calculate sum of *original active* 'daily_tasks' deposits and their end date ---
    $tasks_deposits = $wpdb->get_results($wpdb->prepare(
        "SELECT amount, approval_date, investment_duration
         FROM {$user_deposits_table}
         WHERE user_id = %d AND deposit_type = %s AND status = 'approved'
         ORDER BY approval_date DESC",
        $user_id,
        'daily_tasks'
    ));

    $latest_tasks_approval_timestamp = 0;
    $active_task_deposits_original_sum = 0.0;
    $tasks_deposit_is_withdrawable = false;

    if ($tasks_deposits) {
        foreach ($tasks_deposits as $task_deposit) {
            $approval_timestamp = $task_deposit->approval_date ? strtotime($task_deposit->approval_date) : 0;
            if ($approval_timestamp === false) {
                error_log("SMC Helper Warning: Invalid approval_date '{$task_deposit->approval_date}' for task deposit (User ID: {$user_id}). Skipping this deposit for end_date calculation.");
                continue;
            }

            $duration_days = !empty($task_deposit->investment_duration) && is_numeric($task_deposit->investment_duration) ? (int) $task_deposit->investment_duration : 90;
            $deposit_end_ts = strtotime("+$duration_days days", $approval_timestamp);

            if ($deposit_end_ts !== false && $deposit_end_ts > $current_time_unix) {
                $active_task_deposits_original_sum += (float) $task_deposit->amount;
                if ($approval_timestamp > $latest_tasks_approval_timestamp) {
                    $latest_tasks_approval_timestamp = $approval_timestamp;
                    $data['tasks_deposit_end_timestamp_for_calc'] = $deposit_end_ts;
                    if ($current_time_unix >= $deposit_end_ts) {
                        $tasks_deposit_is_withdrawable = true;
                    }
            }
        } // End of if ($deposit_end_ts !== false && $deposit_end_ts > $current_time_unix)
        } // End of foreach ($tasks_deposits as $task_deposit)      
        $data['current_tasks_deposit_balance'] = $active_task_deposits_original_sum; // Sum of original active task deposits

        if ($latest_tasks_approval_timestamp > 0) {
            $data['last_tasks_deposit_timestamp'] = $latest_tasks_approval_timestamp;
        }
        if ($data['tasks_deposit_end_timestamp_for_calc']) {
            $data['tasks_deposit_end_date_str'] = date_i18n('j F Y \ف\ي h:i:s A', $data['tasks_deposit_end_timestamp_for_calc']);
        } else {
            $data['tasks_deposit_end_date_str'] = 'غير محدد';
        }
    } else {
        $data['current_tasks_deposit_balance'] = 0.0;
        $data['tasks_deposit_end_date_str'] = 'غير محدد';
    }
    $data['tasks_deposit_withdrawable_amount'] = $tasks_deposit_is_withdrawable ? $active_task_deposits_original_sum : 0.0;

    // --- Fetch and process active investment deposits ---
    $investment_withdrawal_requests_table = $wpdb->prefix . 'smc_investment_withdrawal_requests';
    $all_investment_types_config_helper = get_option('smc_investment_types_settings', []);
    $active_investments = $wpdb->get_results($wpdb->prepare(
        "SELECT id, amount, deposit_type, investment_package, investment_start_datetime, investment_duration, status AS deposit_status
         FROM {$user_deposits_table}
         WHERE user_id = %d AND deposit_type != %s AND status IN ('approved', 'withdrawal_scheduled') AND investment_start_datetime IS NOT NULL AND investment_duration > 0",
        $user_id,
        'daily_tasks' // Exclude daily_tasks type
    ));

    if ($active_investments) {
        foreach ($active_investments as $inv) {
            $investment_title = $inv->deposit_type; // Fallback to key
            if (isset($all_investment_types_config_helper[$inv->deposit_type]['title'])) {
                $investment_title = $all_investment_types_config_helper[$inv->deposit_type]['title'];
            }

            $inv_start_ts = $inv->investment_start_datetime ? strtotime($inv->investment_start_datetime) : 0;
            $inv_duration_days = (int) $inv->investment_duration;
            $inv_end_ts = 0;
            $inv_end_date_str = 'N/A';
            $is_investment_ending_soon = false; // Within 36 hours of ending
            $is_inv_withdrawable = false;

            if ($inv_start_ts && $inv_duration_days > 0) {
                // Investment profit distribution starts after 24h, withdrawal is after full duration from start_datetime
                $inv_end_ts = $inv_start_ts + ($inv_duration_days * 24 * 60 * 60);
                $inv_end_date_str = date_i18n('Y-m-d H:i', $inv_end_ts);
                if ($current_time_unix >= $inv_end_ts) {
                    // This investment has naturally ended, it's not "withdrawable" in the sense of early cancellation
                    // but its funds should be available if not already processed.
                    // The 'withdrawable_investment_amount' will be for *scheduled* withdrawals.
                }
                if ($inv_end_ts > 0 && ($inv_end_ts - $current_time_unix) <= (36 * 60 * 60)) {
                    $is_investment_ending_soon = true;
                }
            }

            // Check for existing scheduled withdrawal request
            $scheduled_request = $wpdb->get_row($wpdb->prepare(
                "SELECT id, status FROM {$investment_withdrawal_requests_table} WHERE deposit_id = %d AND user_id = %d AND status NOT IN ('completed', 'cancelled_by_user', 'cancelled_by_admin', 'failed')",
                $inv->id, $user_id
            ));

            $can_request_scheduled = !$scheduled_request && $inv->deposit_status === 'approved' && !$is_investment_ending_soon; // Cannot request if already ending soon or request exists or not 'approved'
            // Can cancel investment if it's 'approved' and not ending soon, OR if it's 'withdrawal_scheduled' and not ending soon
            $can_cancel_investment_direct = ($inv->deposit_status === 'approved' || $inv->deposit_status === 'withdrawal_scheduled') && !$is_investment_ending_soon;

            if ($inv->deposit_status === 'approved' && $is_inv_withdrawable) { // This condition might need review based on how "withdrawable" is defined
                 $data['withdrawable_investment_amount'] += (float) $inv->amount;
            }

            $data['active_investments_details'][] = [
                'id' => $inv->id,
                'title' => $investment_title,
                'package' => $inv->investment_package,
                'amount' => (float) $inv->amount,
                'start_datetime_str' => $inv->investment_start_datetime ? date_i18n('Y-m-d H:i', $inv_start_ts) : 'N/A',
                'duration_days' => $inv_duration_days,
                'end_date_timestamp' => $inv_end_ts,
                'end_date_str' => $inv_end_date_str,
                'is_withdrawable_naturally' => ($current_time_unix >= $inv_end_ts), // Has the investment period naturally ended?
                'deposit_status' => $inv->deposit_status, // e.g., 'approved', 'withdrawal_scheduled'
                'can_request_scheduled_withdrawal' => $can_request_scheduled,
                'can_cancel_investment_now' => $can_cancel_investment_direct, // Can initiate cancellation/scheduled withdrawal
                'scheduled_withdrawal_request_id' => $scheduled_request ? $scheduled_request->id : null,
                'scheduled_withdrawal_status' => $scheduled_request ? $scheduled_request->status : null,
                'is_ending_soon' => $is_investment_ending_soon, // Within 36h of natural end
            ];
            if ($inv->deposit_status === 'approved' || $inv->deposit_status === 'withdrawal_scheduled') { // Only sum up if not fully withdrawn/completed
                $data['total_active_investment_amount'] += (float) $inv->amount;
            }
        }
    }

    $today_date = current_time('Y-m-d');
    // --- Calculate spendable tasks deposit balance for display on dashboard ---
    $ad_deals_log_table = $wpdb->prefix . 'smc_ad_deals_log';
    $total_ad_price_spent_today = 0.0;

    if ($active_task_deposits_original_sum > 0) {
        $total_ad_price_spent_today = (float) $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(ad_price) FROM {$ad_deals_log_table} WHERE user_id = %d AND DATE(completion_timestamp) = %s",
            $user_id,
            $today_date
        ));
        $data['current_deposit'] = $active_task_deposits_original_sum - $total_ad_price_spent_today;
    } else {
        $data['current_deposit'] = 0.0;
    }
    // Ensure spendable balance is not negative
    if ($data['current_deposit'] < 0) {
        $data['current_deposit'] = 0.0;
    }

    // Reset Ads Watched
    if ($data['last_ad_watch_date'] !== $today_date) {
        update_user_meta($user_id, SMC_ADS_WATCHED_TODAY, 0);
        update_user_meta($user_id, SMC_LAST_AD_WATCH_DATE, $today_date);
        $data['ads_watched_today'] = 0;
        $data['last_ad_watch_date'] = $today_date;
    }

    // Reset Daily Earnings
    if ($data['last_earning_reset_date'] !== $today_date) {
        update_user_meta($user_id, SMC_DAILY_EARNINGS_TOTAL, 0.0);
        update_user_meta($user_id, SMC_LAST_EARNING_RESET_DATE, $today_date);
        $data['daily_profit'] = 0.0;
        $data['last_earning_reset_date'] = $today_date;
    }

    // Reset Daily Clicks (if used)
    if ($data['last_click_date'] !== $today_date) {
        update_user_meta($user_id, SMC_DAILY_CLICKS, 0);
        update_user_meta($user_id, SMC_LAST_CLICK_DATE, $today_date);
        $data['daily_clicks'] = 0;
        $data['last_click_date'] = $today_date;
    }

    // --- Recalculate Daily Ad Limit based on *active task deposits sum* ---
    $new_daily_ad_limit = 0;
    if ($active_task_deposits_original_sum > 0) {
        $new_daily_ad_limit = 10; // Default if some task deposit exists
        $ad_settings = function_exists('smc_get_ad_deal_settings') ? smc_get_ad_deal_settings() : [];
        foreach ($ad_settings as $key_plan => $plan_data) {
            if (strpos($key_plan, 'plan_') === 0 && is_array($plan_data) && isset($plan_data['deposit_min'], $plan_data['deposit_max'], $plan_data['daily_limit'])) {
                if ($active_task_deposits_original_sum >= $plan_data['deposit_min'] && $active_task_deposits_original_sum <= $plan_data['deposit_max']) {
                    $new_daily_ad_limit = (int)$plan_data['daily_limit'];
                    break;
                }
            }
        }
    }
    $data['daily_ad_limit'] = $new_daily_ad_limit;
    update_user_meta($user_id, SMC_DAILY_AD_LIMIT, $new_daily_ad_limit);

    // Check attendance for today
    $wp_timezone_obj = wp_timezone();
    $today_dt_attendance = new DateTime('now', $wp_timezone_obj);
    $today_date_str_att = $today_dt_attendance->format('Y-m-d');
    $current_year_att = $today_dt_attendance->format('Y');
    $current_month_att = $today_dt_attendance->format('m');
    $attendance_this_month = [];
    if (function_exists('smc_get_user_attendance_for_month')) {
        $attendance_this_month = smc_get_user_attendance_for_month($user_id, $current_year_att, $current_month_att);
        if (!is_array($attendance_this_month)) $attendance_this_month = [];
    }
    $data['attended_today'] = in_array($today_date_str_att, $attendance_this_month);


    return $data;
}

/**
 * Translates payment method keys to Arabic.
 * This function should be in the global scope.
 *
 * @param string $method_key The payment method key.
 * @return string The translated payment method or the original key if not found.
 */
function translate_payment_method_smc($method_key) {
    $translations = [
        'ccp' => 'CCP (حساب بريدي جار)',
        'bank' => 'تحويل بنكي',
        'baridimob' => 'BaridiMob',
        'usdt_trc20' => 'USDT (TRC20)',
        'profit_balance' => 'من رصيد الأرباح'
    ];
    return $translations[$method_key] ?? esc_html($method_key);
}

/**
 * Adds an amount to the user's daily earnings total.
 *
 * @param int $user_id The ID of the user.
 * @param float $amount The amount to add.
 * @return bool True on success, false on failure.
 */
function smc_add_to_daily_earnings($user_id, $amount) {
    if (!$user_id || !is_numeric($amount) || $amount <= 0) {
        error_log("SMC Debug (smc_add_to_daily_earnings): Invalid input. User ID: $user_id, Amount: $amount");
        return false;
    }

    $last_reset_date = get_user_meta($user_id, SMC_LAST_EARNING_RESET_DATE, true);
    $today_date = current_time('Y-m-d');
    if ($last_reset_date !== $today_date) {
        error_log("SMC Debug (smc_add_to_daily_earnings): Resetting daily earnings for user $user_id. Last reset: $last_reset_date, Today: $today_date");
        update_user_meta($user_id, SMC_DAILY_EARNINGS_TOTAL, 0.0);
        update_user_meta($user_id, SMC_LAST_EARNING_RESET_DATE, $today_date);
        $current_daily_total = 0.0;
    } else {
        $current_daily_total = (float) (get_user_meta($user_id, SMC_DAILY_EARNINGS_TOTAL, true) ?? 0.0);
    }

    $new_daily_total = $current_daily_total + (float)$amount;
    error_log("SMC Debug (smc_add_to_daily_earnings): Updating daily earnings for user $user_id. Old: $current_daily_total, Amount: $amount, New: $new_daily_total");
    $update_result = update_user_meta($user_id, SMC_DAILY_EARNINGS_TOTAL, $new_daily_total);

    if (!$update_result) {
        error_log("SMC Error (smc_add_to_daily_earnings): Failed to update meta SMC_DAILY_EARNINGS_TOTAL for user $user_id.");
    }

    return $update_result;
}

/**
 * Get user attendance dates for a specific month.
 *
 * @param int $user_id User ID.
 * @param int $year Year.
 * @param int $month Month (1-12).
 * @return array Array of 'Y-m-d' date strings or empty array.
 */
function smc_get_user_attendance_for_month($user_id, $year, $month) {
    global $wpdb;
    $attendance_table = $wpdb->prefix . 'smc_attendance_log';
    $dates = [];

    if (!ctype_digit((string)$user_id) || !ctype_digit((string)$year) || !ctype_digit((string)$month) || $month < 1 || $month > 12) {
        return $dates;
    }

    $start_date = sprintf('%d-%02d-01', $year, $month);
    $end_date = date('Y-m-t', strtotime($start_date));

    $results = $wpdb->get_results($wpdb->prepare(
        "SELECT attendance_date FROM {$attendance_table} WHERE user_id = %d AND attendance_date BETWEEN %s AND %s",
        $user_id,
        $start_date,
        $end_date
    ));

    if ($results) {
        foreach ($results as $row) {
            $dates[] = $row->attendance_date;
        }
    }
    return $dates;
}

/**
 * Generates a unique referral code for a user if they don't have one.
 *
 * @param int $user_id The user ID.
 * @return string|false The referral code or false on failure.
 */
function smc_generate_referral_code($user_id) {
    $existing_code = get_user_meta($user_id, SMC_REFERRAL_CODE, true);
    if (!empty($existing_code)) {
        return $existing_code;
    }

    global $wpdb;
    $max_attempts = 10;
    $attempt = 0;

    while ($attempt < $max_attempts) {
        $code = strtoupper(substr(md5(uniqid($user_id, true)), 0, 8));

        $code_exists = $wpdb->get_var($wpdb->prepare(
            "SELECT user_id FROM $wpdb->usermeta WHERE meta_key = %s AND meta_value = %s AND user_id != %d",
            SMC_REFERRAL_CODE,
            $code,
            $user_id
        ));

        if (!$code_exists) {
            if (update_user_meta($user_id, SMC_REFERRAL_CODE, $code)) {
                return $code;
            } else {
                error_log("SMC Error: Failed to save referral code for user ID: $user_id");
                return false;
            }
        }
        $attempt++;
    }

    error_log("SMC Error: Failed to generate a unique referral code for user ID: $user_id after $max_attempts attempts.");
    return false;
}

/**
 * Get the referral upline (referrers) for a user.
 *
 * @param int $user_id The ID of the user whose upline to find.
 * @param int $max_levels Maximum number of levels to go up.
 * @return array Array of WP_User objects representing the upline, ordered from direct referrer upwards.
 */
function smc_get_referral_upline($user_id, $max_levels = 3) {
    $upline = [];
    $current_user_id = $user_id;

    for ($level = 0; $level < $max_levels; $level++) {
        $referrer_id = get_user_meta($current_user_id, SMC_REFERRED_BY, true);

        if (empty($referrer_id) || !is_numeric($referrer_id) || $referrer_id <= 0) {
            break;
        }

        $referrer_user = get_userdata($referrer_id);
        if ($referrer_user) {
            $upline[] = $referrer_user;
            $current_user_id = $referrer_id;
        } else {
            break;
        }
    }
    return $upline;
}

/**
 * Get the referral downline for a user, recursively up to a certain number of levels.
 *
 * @param int $user_id The ID of the user whose downline to find.
 * @param int $max_levels Maximum number of levels to go down.
 * @param int $current_level Internal use for recursion.
 * @return array Nested array of WP_User objects representing the downline.
 */
function smc_get_referral_downline_recursive($user_id, $max_levels = 3, $current_level = 0) {
    if ($current_level >= $max_levels) {
        return [];
    }

    $downline_users = [];
    $args = array(
        'meta_key'   => SMC_REFERRED_BY,
        'meta_value' => $user_id,
        'fields'     => 'all',
        'orderby'    => 'user_registered',
        'order'      => 'ASC'
    );
    $direct_referrals = get_users($args);

    if (!empty($direct_referrals)) {
        foreach ($direct_referrals as $referral) {
            $downline_users[] = [
                'user' => $referral,
                'downline' => smc_get_referral_downline_recursive($referral->ID, $max_levels, $current_level + 1)
            ];
        }
    }
    return $downline_users;
}

/**
 * Counts the total number of members in a nested downline array.
 *
 * @param array $downline_data The nested array from smc_get_referral_downline_recursive().
 * @return int Total number of members in the downline.
 */
function smc_count_downline_members_recursive($downline_data) {
    $count = 0;
    if (empty($downline_data)) {
        return 0;
    }
    foreach ($downline_data as $item) {
        $count++;
        if (!empty($item['downline'])) {
            $count += smc_count_downline_members_recursive($item['downline']);
        }
    }
    return $count;
}

/**
* Get Default Reward Settings (Used in admin settings and enqueue)
 *
 * @return array Default reward settings.
 */
function smc_get_default_reward_settings() {
    return [
        'referral_deposit_l1' => ['type' => 'percentage', 'value' => 0.03, 'trigger' => 'deposit', 'level' => 1, 'description' => 'مكافئة الإحالة مستوى الأول (إيداع)'],
        'referral_deposit_l2' => ['type' => 'percentage', 'value' => 0.02, 'trigger' => 'deposit', 'level' => 2, 'description' => 'مكافئة الإحالة مستوى الثاني (إيداع)'],
        'referral_deposit_l3' => ['type' => 'percentage', 'value' => 0.01, 'trigger' => 'deposit', 'level' => 3, 'description' => 'مكافئة الإحالة مستوى الثالث (إيداع)'],
        'daily_task_l1' => ['type' => 'percentage', 'value' => 0.03, 'trigger' => 'ad_completion', 'level' => 1, 'description' => 'مكافئة المهام اليومية مستوى الأول'],
        'daily_task_l2' => ['type' => 'percentage', 'value' => 0.02, 'trigger' => 'ad_completion', 'level' => 2, 'description' => 'مكافئة المهام اليومية مستوى الثاني'],
        'daily_task_l3' => ['type' => 'percentage', 'value' => 0.01, 'trigger' => 'ad_completion', 'level' => 3, 'description' => 'مكافئة المهام اليومية مستوى الثالث'],
        'investment_l1' => ['type' => 'percentage', 'value' => 0.03, 'trigger' => 'deposit', 'level' => 1, 'description' => 'مكافئة الإستثمار مستوى الأول'],
        'investment_l2' => ['type' => 'percentage', 'value' => 0.02, 'trigger' => 'deposit', 'level' => 2, 'description' => 'مكافئة الإستثمار مستوى الثاني'],
        'investment_l3' => ['type' => 'percentage', 'value' => 0.01, 'trigger' => 'deposit', 'level' => 3, 'description' => 'مكافئة الإستثمار مستوى الثالث'],
        'rank_vip1' => ['type' => 'fixed_monthly', 'value' => 3000, 'trigger' => 'rank_check', 'level' => null, 'required_referrals_min' => 2, 'required_referrals_max' => 7, 'description' => 'مكافئة الرتبة VIP1 (راتب شهري)'],
        'rank_vip2' => ['type' => 'fixed_monthly', 'value' => 7000, 'trigger' => 'rank_check', 'level' => null, 'required_referrals_min' => 3, 'required_referrals_max' => 15, 'description' => 'مكافئة الرتبة VIP2 (راتب شهري)'],
        'rank_vip3' => ['type' => 'fixed_monthly', 'value' => 20000, 'trigger' => 'rank_check', 'level' => null, 'required_referrals_min' => 4, 'required_referrals_max' => 35, 'description' => 'مكافئة الرتبة VIP3 (راتب شهري)'],
        'rank_vip4' => ['type' => 'fixed_monthly', 'value' => 45000, 'trigger' => 'rank_check', 'level' => null, 'required_referrals_min' => 5, 'required_referrals_max' => 70, 'description' => 'مكافئة الرتبة VIP4 (راتب شهري)'],
        'rank_vip5' => ['type' => 'fixed_monthly', 'value' => 100000, 'trigger' => 'rank_check', 'level' => null, 'required_referrals_min' => 7, 'required_referrals_max' => 150, 'description' => 'مكافئة الرتبة VIP5 (راتب شهري)'],
        'agent_district' => ['type' => 'fixed_monthly', 'value' => 30000, 'trigger' => 'agent_check', 'level' => null, 'required_vip3_min' => 2, 'required_vip3_max' => 10, 'location_scope' => 'district', 'description' => 'مكافئة وكيل الحي (راتب شهري)'],
        'agent_city' => ['type' => 'fixed_monthly', 'value' => 100000, 'trigger' => 'agent_check', 'level' => null, 'required_vip3_min' => 5, 'required_vip3_max' => 30, 'location_scope' => 'city', 'description' => 'مكافئة وكيل المدينة (راتب شهري)'],
        'deposit_withdrawal_fee' => ['type' => 'percentage_plus_fixed', 'value' => ['percentage' => 0.01, 'fixed' => 30], 'trigger' => 'withdrawal_approval', 'level' => null, 'description' => 'رسوم سحب الوديعة (1% + 30 دج)'],
        'profit_withdrawal_fee' => ['type' => 'percentage_plus_fixed', 'value' => ['percentage' => 0.01, 'fixed' => 30], 'trigger' => 'withdrawal_approval', 'level' => null, 'description' => 'رسوم سحب الأرباح (1% + 30 دج)'],
        'signup_bonus' => ['type' => 'fixed', 'value' => 0, 'trigger' => 'registration', 'level' => null, 'description' => 'مكافأة التسجيل (للمستخدم الجديد)'],
        'referrer_signup_bonus' => ['type' => 'fixed', 'value' => 0, 'trigger' => 'referral_signup', 'level' => null, 'description' => 'مكافأة دعوة صديق (للداعي)'],
        'daily_attendance' => ['type' => 'fixed', 'value' => 10, 'trigger' => 'attendance', 'level' => null, 'description' => 'مكافأة الحضور اليومي'],
    ];
}

/**
 * Award Referral Bonuses based on deposit.
 *
 * @param int $depositing_user_id ID of the user who made the deposit.
 * @param float $deposit_amount Amount deposited.
 * @param string $deposit_type Type of deposit ('daily_tasks', or a dynamic investment key like 'project_alpha'). Defaults to 'daily_tasks'.
 */
function smc_award_referral_bonuses($depositing_user_id, $deposit_amount, $deposit_type = 'daily_tasks') {
    global $wpdb;
    $rewards_table = $wpdb->prefix . 'smc_rewards_log';
    $current_time_mysql = current_time('mysql');

    $reward_settings = get_option(SMC_REWARD_SETTINGS_OPTION, smc_get_default_reward_settings());
    // error_log('Deposit Bonus - Settings Check: ' . print_r($reward_settings, true));

    $upline = smc_get_referral_upline($depositing_user_id, 3);
    // error_log('Deposit Bonus - Upline Check for user ' . $depositing_user_id . ': ' . print_r($upline, true));

    foreach ($upline as $level => $referrer) {
        $bonus_type_key_to_use = '';

        // Check if the deposit_type is one of the configured dynamic investment types
        $investment_types_defaults_func_bonus = function_exists('smc_get_default_investment_types_settings') ? 'smc_get_default_investment_types_settings' : function() { return []; };
        $all_investment_types_config_helper = get_option('smc_investment_types_settings', $investment_types_defaults_func_bonus());
        $is_dynamic_investment = is_array($all_investment_types_config_helper) && isset($all_investment_types_config_helper[$deposit_type]);

        if ($is_dynamic_investment) {
            // error_log("SMC Bonus: Deposit type '{$deposit_type}' is a dynamic investment. Using 'investment_lX' bonus keys.");
            if ($level === 0) $bonus_type_key_to_use = 'investment_l1';
            elseif ($level === 1) $bonus_type_key_to_use = 'investment_l2';
            elseif ($level === 2) $bonus_type_key_to_use = 'investment_l3';
        } elseif ($deposit_type === 'daily_tasks') {
            // error_log("SMC Bonus: Deposit type is 'daily_tasks'. Using 'referral_deposit_lX' bonus keys.");
            if ($level === 0) $bonus_type_key_to_use = 'referral_deposit_l1';
            elseif ($level === 1) $bonus_type_key_to_use = 'referral_deposit_l2';
            elseif ($level === 2) $bonus_type_key_to_use = 'referral_deposit_l3';
        } else {
            // error_log("SMC Bonus: Unknown deposit_type '{$deposit_type}'. No referral bonus keys assigned for this type.");
            continue; // Skip if deposit type is neither dynamic investment nor daily_tasks
        }


        if (empty($bonus_type_key_to_use)) {
            // error_log("SMC Bonus: Could not determine bonus type key for deposit_type '{$deposit_type}' and level {$level}. Skipping bonus for this level.");
            continue;
        }

        $bonus_config = $reward_settings[$bonus_type_key_to_use] ?? null;
        $bonus_amount = 0;

        if ($bonus_config && $bonus_config['type'] === 'percentage' && isset($bonus_config['value']) && $bonus_config['value'] > 0) {
            $bonus_percentage = (float) $bonus_config['value'];
            $bonus_amount = $deposit_amount * $bonus_percentage;

            if ($bonus_amount > 0) {
                $referrer_profit = (float) (get_user_meta($referrer->ID, SMC_PROFIT_BALANCE, true) ?? 0.0);
                $new_referrer_profit = $referrer_profit + $bonus_amount;

                // error_log("Deposit Bonus ({$bonus_type_key_to_use}): Attempting to award {$bonus_amount} to user {$referrer->ID} (Level " . ($level + 1) . ") for deposit type '{$deposit_type}'");
                update_user_meta($referrer->ID, SMC_PROFIT_BALANCE, $new_referrer_profit);

                $wpdb->insert(
                    $rewards_table,
                    array(
                        'user_id' => $referrer->ID,
                        'reward_type' => $bonus_type_key_to_use,
                        'amount' => $bonus_amount,
                        'reward_timestamp' => $current_time_mysql,
                        'source_user_id' => $depositing_user_id,
                        'related_info' => 'Deposit Amount: ' . number_format($deposit_amount, 2) . ', Level: ' . ($level + 1) . ', Type: ' . $deposit_type
                    ),
                    array('%d', '%s', '%f', '%s', '%d', '%s')
                );
                // error_log("SMC Bonus ({$bonus_type_key_to_use}): Awarded $bonus_amount to user {$referrer->ID} (Level " . ($level + 1) . ") from deposit by user $depositing_user_id of type '{$deposit_type}'.");
            }
        }
    }
}

/**
 * Helper function to get specific fee settings.
 * Used in enqueue script and potentially elsewhere.
 *
 * @param string $fee_type The key for the fee type (e.g., 'deposit_withdrawal_fee').
 * @return array The fee configuration array ['percentage' => x, 'fixed' => y] or defaults.
 */
if (!function_exists('smc_get_fee_settings')) {
    function smc_get_fee_settings($fee_type) {
        $option_name = defined('SMC_REWARD_SETTINGS_OPTION') ? SMC_REWARD_SETTINGS_OPTION : 'smc_reward_settings';
        $defaults_func = function_exists('smc_get_default_reward_settings') ? 'smc_get_default_reward_settings' : (function_exists('smc_get_default_reward_settings_local_fallback') ? 'smc_get_default_reward_settings_local_fallback' : null);

        $all_settings = get_option($option_name, $defaults_func ? $defaults_func() : []);
        $default_fee_value = ['percentage' => 0, 'fixed' => 0];

        return isset($all_settings[$fee_type]['value']) && is_array($all_settings[$fee_type]['value']) ? $all_settings[$fee_type]['value'] : $default_fee_value;
    }
}

/**
 * Counts the number of active direct referrals for a user.
 * An active referral is defined as having a deposit balance >= 2000.
 *
 * @param int $user_id The ID of the referrer.
 * @return int Number of active direct referrals.
 */
function smc_count_active_direct_referrals($user_id) {
    if (!$user_id || !is_numeric($user_id) || $user_id <= 0) {
        return 0;
    }
    $active_referrals_count = 0;
    $direct_referrals = get_users([
        'meta_key' => SMC_REFERRED_BY,
        'meta_value' => $user_id,
        'fields' => 'ID',
    ]);

    foreach ($direct_referrals as $referral_id) {
        // Check the *active task deposit balance* for the referral
        $referral_smc_data = smc_get_user_data($referral_id); // Use the main helper
        $active_task_deposit_for_referral = $referral_smc_data['current_tasks_deposit_balance'] ?? 0.0;

        if ($active_task_deposit_for_referral >= 2000) {
            $active_referrals_count++;
        }
    }
    return $active_referrals_count;
}

/**
 * Get user rank.
 * Implement actual rank logic here based on your criteria (e.g., referrals, team size, deposits).
 *
 * @param int $user_id User ID.
 * @return string User's rank (e.g., 'VIP0', 'VIP1').
 */
function smc_get_user_rank($user_id) {
    $stored_rank = get_user_meta($user_id, 'smc_user_rank', true);
    if (!empty($stored_rank) && strtoupper($stored_rank) !== 'VIP0') {
        return strtoupper($stored_rank);
    }

    $reward_settings = get_option(SMC_REWARD_SETTINGS_OPTION, smc_get_default_reward_settings());
    $active_direct_referrals_count = smc_count_active_direct_referrals($user_id);

    $vip_ranks_config = [];
    foreach ($reward_settings as $key => $setting) {
        if (strpos($key, 'rank_vip') === 0 && isset($setting['type']) && $setting['type'] === 'fixed_monthly') {
            $level = (int) str_replace('rank_vip', '', $key);
            $vip_ranks_config[$level] = [
                'key' => strtoupper($key),
                'name' => $key,
                'required_referrals_min' => (int) ($setting['required_referrals_min'] ?? ($setting['required_referrals'] ?? 0)),
                'required_referrals_max' => (int) ($setting['required_referrals_max'] ?? ($setting['required_referrals'] ?? PHP_INT_MAX))
            ];
        }
    }
    krsort($vip_ranks_config);

    $calculated_rank = 'VIP0';

    foreach ($vip_ranks_config as $level_config) {
        if ($active_direct_referrals_count >= $level_config['required_referrals_min'] &&
            $active_direct_referrals_count <= $level_config['required_referrals_max']) {
            $calculated_rank = str_replace('RANK_', '', $level_config['key']);
            break;
        }
    }

    $agent_district_config = $reward_settings['agent_district'] ?? null;
    $agent_city_config = $reward_settings['agent_city'] ?? null;

    if ($agent_district_config || $agent_city_config) {
        $direct_referrals_ids = get_users([
            'meta_key' => SMC_REFERRED_BY,
            'meta_value' => $user_id,
            'fields' => 'ID',
        ]);

        $active_vip3_referrals_details = [];

        foreach ($direct_referrals_ids as $referral_id) {
            $referral_smc_data = smc_get_user_data($referral_id);
            $active_task_deposit_for_referral = $referral_smc_data['current_tasks_deposit_balance'] ?? 0.0;

            if ($active_task_deposit_for_referral >= 2000) {
                $referral_rank = smc_get_user_rank($referral_id);
                if ($referral_rank === 'VIP3') {
                    $referral_district = get_user_meta($referral_id, 'user_district', true);
                    if ($referral_district) {
                        if (!isset($active_vip3_referrals_details[$referral_district])) {
                            $active_vip3_referrals_details[$referral_district] = 0;
                        }
                        $active_vip3_referrals_details[$referral_district]++;
                    }
                }
            }
        }

        if ($agent_city_config && isset($agent_city_config['required_vip3_min'], $agent_city_config['required_vip3_max'])) {
            $required_vip3_city_min = (int) $agent_city_config['required_vip3_min'];
            $required_vip3_city_max = (int) $agent_city_config['required_vip3_max'];
            $total_vip3_for_city_agent = 0;
            foreach ($active_vip3_referrals_details as $count) {
                $total_vip3_for_city_agent += $count;
            }
            if (count($active_vip3_referrals_details) >= 3 &&
                $total_vip3_for_city_agent >= $required_vip3_city_min &&
                $total_vip3_for_city_agent <= $required_vip3_city_max) {
                $calculated_rank = 'AGENT_CITY';
            }
        }

        if ($calculated_rank !== 'AGENT_CITY' &&
            $agent_district_config &&
            isset($agent_district_config['required_vip3_min'], $agent_district_config['required_vip3_max'])) {
            $required_vip3_district_min = (int) $agent_district_config['required_vip3_min'];
            $required_vip3_district_max = (int) $agent_district_config['required_vip3_max'];
            $agent_own_district = get_user_meta($user_id, 'user_district', true);

            if ($agent_own_district && isset($active_vip3_referrals_details[$agent_own_district]) &&
                $active_vip3_referrals_details[$agent_own_district] >= $required_vip3_district_min &&
                $active_vip3_referrals_details[$agent_own_district] <= $required_vip3_district_max) {
                $calculated_rank = 'AGENT_DISTRICT';
            }
        }
    }
    return $calculated_rank;
}

/**
 * Checks if an investment deposit can be cancelled or a scheduled withdrawal can be cancelled by the user.
 *
 * @param int $deposit_id The ID of the investment deposit in user_deposits.
 * @param int $user_id The ID of the user.
 * @return bool True if cancellation is allowed, false otherwise.
 */
function smc_can_user_cancel_investment_or_request($deposit_id, $user_id) {
    global $wpdb;
    $user_deposits_table = $wpdb->prefix . 'user_deposits';
    $investment_withdrawal_requests_table = $wpdb->prefix . 'smc_investment_withdrawal_requests';

    $investment = $wpdb->get_row($wpdb->prepare(
        "SELECT investment_start_datetime, investment_duration, status FROM {$user_deposits_table} WHERE id = %d AND user_id = %d AND deposit_type != 'daily_tasks'",
        $deposit_id, $user_id
    ));

    if (!$investment || !in_array($investment->status, ['approved', 'withdrawal_scheduled'])) {
        return false; // Investment not found, not owned, not an investment, or not in a cancellable state
    }

    $inv_start_ts = $investment->investment_start_datetime ? strtotime($investment->investment_start_datetime) : 0;
    $inv_duration_days = (int) $investment->investment_duration;

    if (!$inv_start_ts || $inv_duration_days <= 0) {
        return false; // Invalid investment data
    }

    $investment_natural_end_timestamp = $inv_start_ts + ($inv_duration_days * 24 * 60 * 60);
    return (current_time('timestamp') < ($investment_natural_end_timestamp - (36 * 60 * 60)));
}

/**
 * Retrieves the details of a specific investment plan.
 *
 * @param string $investment_type_key The unique key of the investment type.
 * @param int    $plan_index The index of the plan (0, 1, or 2).
 * @return array|null The plan details array if found, otherwise null.
 */
function smc_get_investment_plan_details($investment_type_key, $plan_index) {
    // A plan index can be 0, 1, 2, or 3 (for up to 4 plans)
    if (empty($investment_type_key) || !is_numeric($plan_index) || $plan_index < 0 || $plan_index > 3) {
        error_log("SMC Helper (get_investment_plan_details): Invalid input. Type: {$investment_type_key}, Index: {$plan_index}");
        return null;
    }

    $all_investment_settings = get_option('smc_investment_types_settings', []);

    if (isset($all_investment_settings[$investment_type_key]['roi_plans'][$plan_index])) {
        $plan = $all_investment_settings[$investment_type_key]['roi_plans'][$plan_index];
        // Ensure all expected keys are present in the returned plan, providing defaults if not
        return wp_parse_args($plan, [
            'duration_value' => 0,
            'duration_unit' => 'days',
            'avg_roi' => 0, // This is the percentage value, e.g., 1.2 for 1.2%
            'unit' => 'daily', // per_minute, hourly, daily
            'min_roi' => 0, // Add default for min_roi
            'max_roi' => 0  // Add default for max_roi
        ]);
    }
    error_log("SMC Helper (get_investment_plan_details): Plan not found for Type: {$investment_type_key}, Index: {$plan_index}. All settings: " . print_r($all_investment_settings, true));
    return null;
}
?>
