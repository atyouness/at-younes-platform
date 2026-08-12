<?php
/**
 * AJAX Handlers
 *
 * @package SMC Group DZ Child
 */

 if (!defined('ABSPATH')) exit;

/**
 * AJAX Handler: User requests a scheduled withdrawal for an investment.
 */
add_action('wp_ajax_smc_request_scheduled_investment_withdrawal', 'smc_ajax_request_scheduled_investment_withdrawal');
function smc_ajax_request_scheduled_investment_withdrawal() {
    check_ajax_referer('smc_scheduled_withdrawal_nonce', 'nonce');

    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'يرجى تسجيل الدخول أولاً.']);
        return;
    }

    $user_id = get_current_user_id();
    $deposit_id = isset($_POST['deposit_id']) ? intval($_POST['deposit_id']) : 0;

    if (!$deposit_id) {
        wp_send_json_error(['message' => 'معرف الإيداع غير صالح.']);
        return;
    }

    global $wpdb;
    $user_deposits_table = $wpdb->prefix . 'user_deposits';
    $investment_withdrawal_requests_table = $wpdb->prefix . 'smc_investment_withdrawal_requests';

    $investment = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$user_deposits_table} WHERE id = %d AND user_id = %d AND deposit_type != 'daily_tasks'",
        $deposit_id, $user_id
    ));

    if (!$investment) {
        wp_send_json_error(['message' => 'لم يتم العثور على وديعة الاستثمار المحددة أو أنها لا تخصك.']);
        return;
    }

    if ($investment->status !== 'approved') {
        wp_send_json_error(['message' => 'يمكن فقط جدولة سحب لودائع الاستثمار الموافق عليها حاليًا. الحالة الحالية: ' . $investment->status]);
        return;
    }

    // Check if a request already exists and is not completed/cancelled
    $existing_request = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM {$investment_withdrawal_requests_table} WHERE deposit_id = %d AND user_id = %d AND status NOT IN ('completed', 'cancelled_by_user', 'cancelled_by_admin', 'failed')",
        $deposit_id, $user_id
    ));

    if ($existing_request) {
        wp_send_json_error(['message' => 'لديك بالفعل طلب سحب مجدول نشط أو قيد المعالجة لهذه الوديعة.']);
        return;
    }
    
    $inv_start_ts = $investment->investment_start_datetime ? strtotime($investment->investment_start_datetime) : 0;
    $inv_duration_days = (int) $investment->investment_duration;

    if (!$inv_start_ts || $inv_duration_days <= 0) {
        wp_send_json_error(['message' => 'بيانات الاستثمار (تاريخ البدء أو المدة) غير صالحة.']);
        return;
    }
    
    $investment_natural_end_timestamp = $inv_start_ts + ($inv_duration_days * 24 * 60 * 60);
    
    // User cannot request scheduled withdrawal if it's already within 36 hours of its natural end.
    // They should wait for it to complete naturally or for the cron to pick it up if it's a standard end-of-term withdrawal.
    if (current_time('timestamp') >= ($investment_natural_end_timestamp - (36 * 60 * 60))) {
        wp_send_json_error(['message' => 'لا يمكن طلب سحب مجدول لهذا الاستثمار لأنه قيد الانتهاء قريبًا (خلال 36 ساعة).']);
        return;
    }

    // Scheduled process date: 36 hours before the natural end of the investment
    $scheduled_process_timestamp = $investment_natural_end_timestamp - (36 * 60 * 60);
    $scheduled_process_date_mysql = date('Y-m-d H:i:s', $scheduled_process_timestamp);
    $current_time_mysql = current_time('mysql');

    $wpdb->query('START TRANSACTION');

    $insert_request_result = $wpdb->insert(
        $investment_withdrawal_requests_table,
        [
            'user_id' => $user_id,
            'deposit_id' => $deposit_id,
            'investment_type' => $investment->deposit_type,
            'amount_requested' => (float) $investment->amount,
            'shares_to_release' => (int) $investment->investment_shares,
            'request_timestamp' => $current_time_mysql,
            'scheduled_process_date' => $scheduled_process_date_mysql,
            'status' => 'scheduled',
        ],
        ['%d', '%d', '%s', '%f', '%d', '%s', '%s', '%s']
    );

    if (false === $insert_request_result) {
        $wpdb->query('ROLLBACK');
        error_log("SMC Scheduled Withdraw Error: Failed to insert request for deposit ID {$deposit_id}. DB Error: " . $wpdb->last_error);
        wp_send_json_error(['message' => 'فشل تسجيل طلب السحب المجدول في قاعدة البيانات.']);
        return;
    }

    // Update the original deposit status to 'withdrawal_scheduled'
    $update_deposit_status_result = $wpdb->update(
        $user_deposits_table,
        ['status' => 'withdrawal_scheduled'],
        ['id' => $deposit_id, 'user_id' => $user_id],
        ['%s'],
        ['%d', '%d']
    );

    if (false === $update_deposit_status_result) {
        $wpdb->query('ROLLBACK');
        error_log("SMC Scheduled Withdraw Error: Failed to update original deposit status for ID {$deposit_id}. Rolled back.");
        wp_send_json_error(['message' => 'فشل تحديث حالة وديعة الاستثمار الأصلية.']);
        return;
    }

    $wpdb->query('COMMIT');

    wp_send_json_success(['message' => 'تم استلام طلب سحب الاستثمار المجدول بنجاح. سيتم معالجته قبل 36 ساعة من تاريخ نهاية الاستثمار.']);
}


/**
 * AJAX Handler: User cancels their own scheduled investment withdrawal request.
 */
add_action('wp_ajax_smc_user_cancel_scheduled_investment_withdrawal', 'smc_ajax_user_cancel_scheduled_investment_withdrawal');
function smc_ajax_user_cancel_scheduled_investment_withdrawal() {
    check_ajax_referer('smc_cancel_scheduled_withdrawal_nonce', 'nonce');

    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'يرجى تسجيل الدخول أولاً.']);
        return;
    }

    $user_id = get_current_user_id();
    $request_id = isset($_POST['request_id']) ? intval($_POST['request_id']) : 0;

    if (!$request_id) {
        wp_send_json_error(['message' => 'معرف الطلب غير صالح.']);
        return;
    }

    global $wpdb;
    $investment_withdrawal_requests_table = $wpdb->prefix . 'smc_investment_withdrawal_requests';
    $user_deposits_table = $wpdb->prefix . 'user_deposits';

    $request = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$investment_withdrawal_requests_table} WHERE id = %d AND user_id = %d",
        $request_id, $user_id
    ));

    if (!$request) {
        wp_send_json_error(['message' => 'لم يتم العثور على طلب السحب المجدول أو أنه لا يخصك.']);
        return;
    }

    if ($request->status !== 'scheduled') {
        wp_send_json_error(['message' => 'يمكن فقط إلغاء طلبات السحب المجدولة التي هي في حالة "مجدولة". الحالة الحالية: ' . $request->status]);
        return;
    }

    // Check if cancellation is allowed (more than 36 hours before the original investment's natural end)
    if (!function_exists('smc_can_user_cancel_investment_or_request') || !smc_can_user_cancel_investment_or_request($request->deposit_id, $user_id)) {
        wp_send_json_error(['message' => 'لا يمكن إلغاء هذا الطلب. لقد تجاوزت المهلة المسموح بها للإلغاء (أقل من 36 ساعة على نهاية الاستثمار).']);
        return;
    }
    
    $wpdb->query('START TRANSACTION');

    $update_request_result = $wpdb->update(
        $investment_withdrawal_requests_table,
        ['status' => 'cancelled_by_user', 'processed_timestamp' => current_time('mysql')],
        ['id' => $request_id],
        ['%s', '%s'],
        ['%d']
    );

    if (false === $update_request_result) {
        $wpdb->query('ROLLBACK');
        error_log("SMC Cancel Scheduled Withdraw Error: Failed to update request status for ID {$request_id}. DB Error: " . $wpdb->last_error);
        wp_send_json_error(['message' => 'فشل تحديث حالة طلب السحب المجدول.']);
        return;
    }

    // Revert the original deposit status to 'approved'
    $revert_deposit_status_result = $wpdb->update(
        $user_deposits_table,
        ['status' => 'approved'], // Revert to approved
        ['id' => $request->deposit_id, 'user_id' => $user_id, 'status' => 'withdrawal_scheduled'], // Ensure it was in 'withdrawal_scheduled' state
        ['%s'],
        ['%d', '%d', '%s']
    );

    if (false === $revert_deposit_status_result) {
        // If the update count is 0, it might mean the status was already changed or didn't match.
        // This is not necessarily a rollback error for the cancellation itself if the request was updated.
        // However, it's an inconsistency.
        error_log("SMC Cancel Scheduled Withdraw Warning: Failed to revert original deposit status for ID {$request->deposit_id} or it was not in 'withdrawal_scheduled' state. Request cancellation still proceeded.");
        // Decide if this should be a hard error or just a warning. For now, let cancellation proceed.
    }
    
    $wpdb->query('COMMIT');
    wp_send_json_success(['message' => 'تم إلغاء طلب سحب الاستثمار المجدول بنجاح.']);
}

 /**
  * Helper function to generate a unique alphanumeric ID.
  *
  * @param int $length Length of the ID.
  * @return string The generated ID.
  */
 function smc_generate_unique_deal_id($length = 9) {
     // Characters: a-z, A-Z, 0-9 (62 characters)
     $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
     $charactersLength = strlen($characters);
     $randomString = '';
     for ($i = 0; $i < $length; $i++) {
         // Use random_int for better randomness if PHP 7+ is available
         if (function_exists('random_int')) {
             $randomString .= $characters[random_int(0, $charactersLength - 1)];
         } else {
             $randomString .= $characters[rand(0, $charactersLength - 1)]; // Fallback for older PHP
         }
     }
     return $randomString;
 }

// --- Dashboard Data ---
add_action('wp_ajax_fetch_dashboard_data', 'smc_ajax_fetch_dashboard_data');
function smc_ajax_fetch_dashboard_data() {
    // تأكد أن اسم Nonce هنا 'smc_nonce' يطابق ما تم إنشاؤه في inc/2-enqueue.php
    // وأن 'nonce' هو المفتاح المرسل من JavaScript.
    check_ajax_referer('smc_nonce', 'nonce');

    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'User not logged in.']);
        return;
    }

    $user_id = get_current_user_id();

    if (function_exists('smc_get_user_data')) {
        $user_smc_data = smc_get_user_data($user_id);

        if (is_array($user_smc_data)) {
            // إعداد البيانات التي سترسل إلى واجهة المستخدم
            // 'current_deposit' from smc_get_user_data is now the spendable tasks deposit balance.
            $response_data = [
                // This is the spendable tasks deposit balance (original active task deposits - today's ad costs)
                'current_deposit'                 => $user_smc_data['current_deposit'] ?? 0,
                // لـ "تاريخ الإيداع الأخير" في صفحة المهام
                'last_deposit_timestamp'          => $user_smc_data['last_tasks_deposit_timestamp'] ?? null,
                // لـ "تاريخ نهاية وديعة المهام" و "الأيام المتبقية للمهام"
                'deposit_end_date'                => $user_smc_data['tasks_deposit_end_date_str'] ?? 'غير محدد',
                'deposit_end_timestamp_for_calc'  => $user_smc_data['tasks_deposit_end_timestamp_for_calc'] ?? null,

                // بقية البيانات يمكن أن تكون عامة أو خاصة بالمهام حسب حاجة الواجهة
                'profit_balance'                  => $user_smc_data['profit_balance'] ?? 0,
                'ads_watched_today'               => $user_smc_data['ads_watched_today'] ?? 0,
                'daily_ad_limit'                  => $user_smc_data['daily_ad_limit'] ?? 0, // هذا يعتمد الآن على إيداع المهام النشط
                'daily_profit'                    => $user_smc_data['daily_profit'] ?? 0,   // أرباح اليوم من المهام
                'total_profit'                    => $user_smc_data['total_profit'] ?? 0,   // هذا هو رصيد الأرباح الإجمالي
                'points_balance'                  => $user_smc_data['points_balance'] ?? 0,
                'attended_today'                  => $user_smc_data['attended_today'] ?? false, // افتراض أن smc_get_user_data توفر هذا
                // It's good to also send the original tasks deposit sum if needed elsewhere in JS
                // 'current_tasks_deposit_balance' below is the sum of original active task deposits
                // إرسال الحقول الخاصة بالمهام بشكل صريح إذا كان JavaScript يحتاجها لمنطق آخر
                'current_tasks_deposit_balance'   => $user_smc_data['current_tasks_deposit_balance'] ?? 0,
                'last_tasks_deposit_timestamp'    => $user_smc_data['last_tasks_deposit_timestamp'] ?? null,
                'tasks_deposit_end_date_str'      => $user_smc_data['tasks_deposit_end_date_str'] ?? 'غير محدد',
                'tasks_deposit_end_timestamp_for_calc' => $user_smc_data['tasks_deposit_end_timestamp_for_calc'] ?? null,
            ];
            wp_send_json_success($response_data);
        } else {
            wp_send_json_error(['message' => 'Failed to retrieve user data.']);
        }
    } else {
        wp_send_json_error(['message' => 'Helper function not found.']);
    }
}

// --- Ad Deal Fetching ---
add_action('wp_ajax_fetch_ad_details', 'smc_ajax_fetch_ad_details');
function smc_ajax_fetch_ad_details() {
    check_ajax_referer('smc_nonce', 'nonce');
    if (!is_user_logged_in()) { wp_send_json_error(['message' => 'User not logged in.']); }
    if (!function_exists('smc_get_user_data') || !function_exists('smc_get_ad_deal_settings')) {
         wp_send_json_error(['message' => 'Required helper functions not found.']);
    }

    $user_id = get_current_user_id();
    $user_data = smc_get_user_data($user_id);

    if (!is_array($user_data) || !isset($user_data['current_deposit'])) {
         wp_send_json_error(['message' => 'Could not retrieve user data.']);
         return;
    }

    if ($user_data['current_deposit'] < 2000) { // Consider making min deposit dynamic
        wp_send_json_error(['message' => 'رصيد الوديعة غير كافٍ (الحد الأدنى 2000 دج).']);
        return;
    }
    if ($user_data['ads_watched_today'] >= $user_data['daily_ad_limit']) {
        wp_send_json_error(['message' => 'لقد أكملت جميع مهام الإعلانات لهذا اليوم.']);
        return;
    }

    $settings = smc_get_ad_deal_settings();
    $tax_rate = $settings['global_tax_rate'] ?? 0.19;
    $current_plan = null;

    // Find the correct plan based on deposit
    foreach ($settings as $key => $plan) {
        if (strpos($key, 'plan_') === 0 && is_array($plan) && isset($plan['deposit_min'], $plan['deposit_max'])) {
            if ($user_data['current_deposit'] >= $plan['deposit_min'] && $user_data['current_deposit'] <= $plan['deposit_max']) {
                $current_plan = $plan;
                break;
            }
        }
    }

    if (!$current_plan || !isset($current_plan['ad_price_min'], $current_plan['ad_price_max'], $current_plan['profit_perc_min'], $current_plan['profit_perc_max'], $current_plan['duration_min'], $current_plan['duration_max'])) {
        wp_send_json_error(['message' => 'لم يتم العثور على خطة إعلانية مناسبة أو أن الخطة غير مكتملة الإعدادات لوديعتك الحالية.']);
        return;
    }

    // Generate random values within plan limits
    $ad_price = mt_rand($current_plan['ad_price_min'] * 100, $current_plan['ad_price_max'] * 100) / 100.0;
    $profit_percentage = mt_rand($current_plan['profit_perc_min'] * 100000, $current_plan['profit_perc_max'] * 100000) / 100000.0;
    $duration = mt_rand($current_plan['duration_min'], $current_plan['duration_max']);

    // Calculations
    $ad_tax = $ad_price * $tax_rate;
    $net_ad_price = $ad_price - $ad_tax;
    $profit_value = $user_data['current_deposit'] * $profit_percentage;
    $profit_tax = $profit_value * $tax_rate;
    $net_profit = $profit_value - $profit_tax;
    $deal_tax = $ad_tax + $profit_tax;
    $user_benefit = $net_profit - $ad_price; // User benefit is net profit minus the ad cost they paid

    // --- Generate Unique Deal ID ---
    $unique_deal_id = smc_generate_unique_deal_id(9); // إنشاء معرف من 9 خانات
    // --- End Generate Unique Deal ID ---

        // --- Image Selection Logic (Revised for Subfolders) ---
    $image_url = ''; // Initialize image URL
    $ad_name = "صفقة إعلانية"; // Default name
    $base_image_path = get_stylesheet_directory() . '/images/';
    $base_image_url = get_stylesheet_directory_uri() . '/images/';
    $default_placeholder_url = $base_image_url . 'default-ad.png';
    $default_placeholder_path = $base_image_path . 'default-ad.png';

    // 1. Scan for subdirectories in the base image path
    $subfolders = glob($base_image_path . '*', GLOB_ONLYDIR);

    if (!empty($subfolders)) {
        // 2. Select a random subfolder
        $random_subfolder_path = $subfolders[array_rand($subfolders)];
        $subfolder_name = basename($random_subfolder_path);

        // 3. Scan the selected subfolder for image files (jpg, png, gif)
        $image_files = glob($random_subfolder_path . '/{*.jpg,*.jpeg,*.png,*.gif}', GLOB_BRACE | GLOB_NOSORT);

        if (!empty($image_files)) {
            // 4. Select a random image file
            $random_image_path = $image_files[array_rand($image_files)];
            $image_filename = basename($random_image_path);

            // 5. Construct the URL
            $image_url = $base_image_url . rawurlencode($subfolder_name) . '/' . rawurlencode($image_filename);

            // 6. Generate ad name
            $image_name_without_ext = pathinfo($image_filename, PATHINFO_FILENAME);
            $clean_image_name_temp = str_replace(['_', '-'], ' ', $image_name_without_ext);
            $clean_subfolder_name_for_comparison = str_replace(['_', '-'], ' ', $subfolder_name);
            $prefix_to_check = $clean_subfolder_name_for_comparison . ' ';
            if (mb_strtolower(substr($clean_image_name_temp, 0, mb_strlen($prefix_to_check, 'UTF-8')), 'UTF-8') === mb_strtolower($prefix_to_check, 'UTF-8')) {
                $ad_name_base = trim(mb_substr($clean_image_name_temp, mb_strlen($prefix_to_check, 'UTF-8'), null, 'UTF-8'));
            } else {
                $ad_name_base = $clean_image_name_temp;
            }
            $ad_name = ucwords($ad_name_base);
            error_log("SMC Ad Deal AJAX: Selected image: " . $image_url . " | Ad Name: " . $ad_name);
        } else {
            error_log("SMC Ad Deal AJAX: No images found in selected subfolder: " . $random_subfolder_path);
        }
    } else {
        error_log("SMC Ad Deal AJAX: No subfolders found in: " . $base_image_path);
    }

    if (empty($image_url)) {
        if (file_exists($default_placeholder_path)) {
            $image_url = $default_placeholder_url;
            $ad_name = "صفقة إعلانية";
            error_log("SMC Ad Deal AJAX: No image selected from subfolders. Using default placeholder: " . $image_url);
        } else {
            $image_url = '';
            $ad_name = "صفقة إعلانية";
            error_log("SMC Ad Deal AJAX Error: Default placeholder image missing: " . $default_placeholder_path);
        }
    }
    // --- End Image Selection Logic ---

    $transient_key = 'smc_ad_deal_' . $user_id;
    $deal_timestamp = time();
    $deal_data_to_store = [
        'ad_name' => $ad_name,
        'price' => $ad_price, 'adTax' => $ad_tax,
        'netPrice' => $net_ad_price, 'profitValue' => $profit_value, 'profitTax' => $profit_tax,
        'netProfit' => $net_profit, 'userBenefit' => $user_benefit, 'profitPercentage' => $profit_percentage,
        'duration' => $duration, 'dealTax' => $deal_tax, 'timestamp' => $deal_timestamp,
        'ad_price_min' => $current_plan['ad_price_min'],
        'image_url' => $image_url,
        'deal_id' => $unique_deal_id,
    ];
    set_transient($transient_key, $deal_data_to_store, 15 * MINUTE_IN_SECONDS);
    $deal_data_to_store['image_url'] = (string) $deal_data_to_store['image_url'];
    wp_send_json_success($deal_data_to_store);
}

// --- Ad Deal Start Watch ---
add_action('wp_ajax_start_ad_watch', 'smc_ajax_start_ad_watch');
function smc_ajax_start_ad_watch() {
    error_log("SMC Debug (start_ad_watch AJAX): Received POST data: " . print_r($_POST, true));
    check_ajax_referer('smc_nonce', 'nonce');
    if (!is_user_logged_in()) { wp_send_json_error(['message' => 'User not logged in.']); }

    $user_id = get_current_user_id();
    $ad_price_from_frontend = isset($_POST['adPrice']) ? floatval($_POST['adPrice']) : 0;
    $deal_timestamp_from_frontend = isset($_POST['dealTimestamp']) ? intval($_POST['dealTimestamp']) : 0;

    $current_deposit = (float) (get_user_meta($user_id, SMC_DEPOSIT_BALANCE, true) ?? 0.0);
    $transient_key = 'smc_ad_deal_' . $user_id;
    $stored_deal_data = get_transient($transient_key);

    if (!$stored_deal_data) {
        wp_send_json_error(['message' => 'انتهت صلاحية الصفقة الإعلانية. يرجى المحاولة مرة أخرى.']);
        return;
    }

    error_log("SMC Deal Check (start_ad_watch): User $user_id - Comparing Stored Price [{$stored_deal_data['price']}] (Type: " . gettype($stored_deal_data['price']) . ") with Frontend Price [{$ad_price_from_frontend}] (Type: " . gettype($ad_price_from_frontend) . ")");
    if ( abs($stored_deal_data['price'] - $ad_price_from_frontend) > 0.01 || $stored_deal_data['timestamp'] != $deal_timestamp_from_frontend ) {
        error_log("SMC Deal Mismatch (start_ad_watch): User $user_id - Stored Price: {$stored_deal_data['price']}, Frontend Price: $ad_price_from_frontend, Stored TS: {$stored_deal_data['timestamp']}, Frontend TS: $deal_timestamp_from_frontend");
        wp_send_json_error(['message' => 'بيانات الصفقة غير متطابقة. ربما تم تحديث الصفقة. يرجى المحاولة مرة أخرى.']);
        return;
    }

    $actual_ad_price = $stored_deal_data['price'];
    if ($current_deposit < $actual_ad_price) {
        wp_send_json_error(['message' => 'رصيد الوديعة غير كافٍ لخصم سعر الإعلان (' . number_format($actual_ad_price, 2) . ' دج).']);
        return;
    }

    $new_deposit_balance = $current_deposit - $actual_ad_price;
    if (update_user_meta($user_id, SMC_DEPOSIT_BALANCE, $new_deposit_balance)) {
        $stored_deal_data['price_deducted'] = true;
        set_transient($transient_key, $stored_deal_data, 5 * MINUTE_IN_SECONDS);
        wp_send_json_success(['message' => 'تم التحقق من الرصيد وبدء المشاهدة.', 'new_deposit_balance' => $new_deposit_balance]);
    } else {
        error_log("SMC Error (start_ad_watch): Failed to update meta SMC_DEPOSIT_BALANCE for user $user_id.");
        wp_send_json_error(['message' => 'فشل تحديث رصيد الوديعة.']);
    }
}

// --- Ad Deal Completion ---
add_action('wp_ajax_complete_ad_watch', 'smc_ajax_complete_ad_watch');
function smc_ajax_complete_ad_watch() {
    check_ajax_referer('smc_nonce', 'nonce');
    if (!is_user_logged_in()) { wp_send_json_error(['message' => 'User not logged in.']); }
    if (!function_exists('smc_get_user_data') || !function_exists('smc_add_to_daily_earnings') || !function_exists('smc_get_referral_upline') || !function_exists('smc_get_default_reward_settings')) {
         wp_send_json_error(['message' => 'Required helper functions not found.']);
    }

    $user_id = get_current_user_id();
    global $wpdb;
    $ad_deals_log_table = $wpdb->prefix . 'smc_ad_deals_log';
    $rewards_table = $wpdb->prefix . 'smc_rewards_log';
    $current_time_mysql = current_time('mysql');
    $today_date = current_time('Y-m-d');

    $transient_key = 'smc_ad_deal_' . $user_id;
    $deal_data = get_transient($transient_key);

    if (!$deal_data) {
        wp_send_json_error(['message' => 'انتهت صلاحية الصفقة الإعلانية أو تم إكمالها بالفعل.']);
        return;
    }

    if (!isset($deal_data['price_deducted']) || !$deal_data['price_deducted']) {
         wp_send_json_error(['message' => 'خطأ: لم يتم تأكيد خصم سعر الإعلان قبل الإكمال.']);
         delete_transient($transient_key);
         return;
    }

    $user_smc_data = smc_get_user_data($user_id);
    $ads_watched = $user_smc_data['ads_watched_today'] ?? 0;
    $ad_limit = $user_smc_data['daily_ad_limit'] ?? 0;

    if ($ads_watched >= $ad_limit) {
        wp_send_json_error(['message' => 'لقد وصلت بالفعل إلى الحد الأقصى للإعلانات لهذا اليوم.']);
        delete_transient($transient_key);
        return;
    }

    if (!isset($deal_data['netProfit'])) {
        error_log("SMC Error (complete_ad_watch): 'netProfit' key is missing in transient data for user {$user_id}. Data: " . print_r($deal_data, true));
        wp_send_json_error(['message' => 'خطأ داخلي: بيانات الربح مفقودة.']);
        delete_transient($transient_key);
        return;
    }
    $net_profit_earned = (float) $deal_data['netProfit'];

    $wpdb->query('START TRANSACTION');

    $current_profit_balance = (float) (get_user_meta($user_id, SMC_PROFIT_BALANCE, true) ?? 0.0);
    $new_profit_balance = $current_profit_balance + $net_profit_earned;
    $update_profit_result = update_user_meta($user_id, SMC_PROFIT_BALANCE, $new_profit_balance);

    $new_ads_watched = $ads_watched + 1;
    $update_watched_result = update_user_meta($user_id, SMC_ADS_WATCHED_TODAY, $new_ads_watched);
    update_user_meta($user_id, SMC_LAST_AD_WATCH_DATE, $today_date);

    $update_daily_earn_result = smc_add_to_daily_earnings($user_id, $net_profit_earned);

    $log_data = [
        'user_id' => $user_id, 'completion_timestamp' => $current_time_mysql,
        'ad_name' => $deal_data['ad_name'] ?? 'N/A', 'ad_price' => $deal_data['price'] ?? 0.0,
        'ad_tax' => $deal_data['adTax'] ?? 0.0, 'net_ad_price' => $deal_data['netPrice'] ?? 0.0,
        'profit_value' => $deal_data['profitValue'] ?? 0.0, 'profit_tax' => $deal_data['profitTax'] ?? 0.0,
        'net_profit' => $net_profit_earned, 'user_benefit' => $deal_data['userBenefit'] ?? 0.0,
        'profit_percentage' => $deal_data['profitPercentage'] ?? 0.0, 'ad_duration' => $deal_data['duration'] ?? 0,
        'deal_tax' => $deal_data['dealTax'] ?? 0.0,
        'deal_id' => (string) ($deal_data['deal_id'] ?? ''),
    ];
    $log_formats = ['%d', '%s', '%s', '%f', '%f', '%f', '%f', '%f', '%f', '%f', '%f', '%d', '%f', '%s'];
    $log_insert_result = $wpdb->insert($ad_deals_log_table, $log_data, $log_formats);
    error_log("SMC Debug (complete_ad_watch): Logging deal data: " . print_r($log_data, true));

    if (!$update_profit_result || !$update_watched_result || !$update_daily_earn_result || !$log_insert_result) {
        $wpdb->query('ROLLBACK');
        error_log("SMC Error (complete_ad_watch): Failed one or more core updates for user $user_id. Rolled back transaction. ProfitUpdate: $update_profit_result, WatchedUpdate: $update_watched_result, DailyEarnUpdate: $update_daily_earn_result, LogInsert: $log_insert_result");
        wp_send_json_error(['message' => 'حدث خطأ أثناء تحديث بياناتك بعد مشاهدة الإعلان. يرجى الاتصال بالدعم.']);
        delete_transient($transient_key);
        return;
    }

    if ($net_profit_earned > 0) {
        $reward_settings = get_option(SMC_REWARD_SETTINGS_OPTION, smc_get_default_reward_settings());
        error_log('Daily Task Bonus - Settings Check: ' . print_r($reward_settings, true));
        $upline = smc_get_referral_upline($user_id, 3);
        error_log('Daily Task Bonus - Upline Check for user ' . $user_id . ': ' . print_r($upline, true));

        foreach ($upline as $level => $referrer) {
            $task_bonus_amount = 0;
            $task_bonus_key = '';
            $task_bonus_config = null;

            if ($level === 0) $task_bonus_key = 'daily_task_l1';
            elseif ($level === 1) $task_bonus_key = 'daily_task_l2';
            elseif ($level === 2) $task_bonus_key = 'daily_task_l3';

            if (!empty($task_bonus_key) && isset($reward_settings[$task_bonus_key])) {
                $task_bonus_config = $reward_settings[$task_bonus_key];
                if ($task_bonus_config && $task_bonus_config['type'] === 'percentage' && isset($task_bonus_config['value']) && $task_bonus_config['value'] > 0) {
                    $task_bonus_percentage = (float) $task_bonus_config['value'];
                    $task_bonus_amount = $net_profit_earned * $task_bonus_percentage;

                    if ($task_bonus_amount > 0) {
                        $referrer_profit = (float) (get_user_meta($referrer->ID, SMC_PROFIT_BALANCE, true) ?? 0.0);
                        $new_referrer_profit = $referrer_profit + $task_bonus_amount;
                        error_log("Daily Task Bonus ({$task_bonus_key}): Attempting to award {$task_bonus_amount} to user {$referrer->ID} (Level " . ($level + 1) . ")");
                        $ref_update_result = update_user_meta($referrer->ID, SMC_PROFIT_BALANCE, $new_referrer_profit);

                        if ($ref_update_result) {
                            $wpdb->insert(
                                $rewards_table,
                                array(
                                    'user_id' => $referrer->ID,
                                    'reward_type' => $task_bonus_key,
                                    'amount' => $task_bonus_amount,
                                    'reward_timestamp' => $current_time_mysql,
                                    'source_user_id' => $user_id,
                                    'related_info' => 'Invitee Net Profit: ' . number_format($net_profit_earned, 2) . ', Level: ' . ($level + 1)
                                ),
                                array('%d', '%s', '%f', '%s', '%d', '%s')
                            );
                            error_log("SMC Daily Task Bonus ({$task_bonus_key}): Awarded $task_bonus_amount to user {$referrer->ID} from task by user $user_id.");
                        } else {
                             error_log("SMC Warning (complete_ad_watch): Failed to update profit balance for referrer {$referrer->ID} (Level " . ($level + 1) . ") for bonus type {$task_bonus_key}.");
                        }
                    }
                }
            }
        }
    }
    $wpdb->query('COMMIT');
    delete_transient($transient_key);
    wp_send_json_success([
        'message' => 'تم إكمال مشاهدة الإعلان بنجاح!',
        'netProfit' => $net_profit_earned,
        'newTotalProfit' => $new_profit_balance,
        'adsWatched' => $new_ads_watched,
        'adsLimit' => $ad_limit
    ]);
}

// --- Daily Attendance ---
add_action('wp_ajax_smc_handle_daily_attendance', 'smc_ajax_handle_daily_attendance');
function smc_ajax_handle_daily_attendance() {
    check_ajax_referer('smc_attendance_nonce', 'nonce');

    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'يرجى تسجيل الدخول أولاً.']);
    }

    $user_id = get_current_user_id();
    global $wpdb;
    $attendance_table = $wpdb->prefix . 'smc_attendance_log';
    $today_date = current_time('Y-m-d');
    $current_timestamp = current_time('mysql');

    $reward_settings = get_option(SMC_REWARD_SETTINGS_OPTION, function_exists('smc_get_default_reward_settings') ? smc_get_default_reward_settings() : []);
    $points_to_award = isset($reward_settings['daily_attendance']['value']) ? (int)$reward_settings['daily_attendance']['value'] : 10;

    $already_attended = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$attendance_table} WHERE user_id = %d AND attendance_date = %s",
        $user_id,
        $today_date
    ));

    if ($already_attended > 0) {
        wp_send_json_error(['message' => 'لقد قمت بتسجيل حضورك لهذا اليوم بالفعل.']);
    }

    $insert_result = $wpdb->insert(
        $attendance_table,
        array(
            'user_id' => $user_id,
            'attendance_date' => $today_date,
            'attendance_timestamp' => $current_timestamp,
            'points_awarded' => $points_to_award
        ),
        array('%d', '%s', '%s', '%d')
    );

    if (false === $insert_result) {
        error_log("SMC Attendance Error: Failed to insert attendance for user $user_id. Error: " . $wpdb->last_error);
        wp_send_json_error(['message' => 'حدث خطأ أثناء تسجيل الحضور في قاعدة البيانات.']);
    }

    $current_points = (int) (get_user_meta($user_id, SMC_POINTS_BALANCE, true) ?? 0);
    $new_points_balance = $current_points + $points_to_award;
    $update_meta_result = update_user_meta($user_id, SMC_POINTS_BALANCE, $new_points_balance);

    if (false === $update_meta_result) {
         error_log("SMC Attendance Warning: Failed to update points balance for user $user_id after attendance.");
    }

    wp_send_json_success([
        'message' => 'تم تسجيل حضورك بنجاح! لقد حصلت على ' . $points_to_award . ' نقاط.',
        'new_points_balance' => $new_points_balance
    ]);
}

// --- Admin Deposit Approval ---
add_action('wp_ajax_smc_handle_deposit_action', 'smc_ajax_handle_deposit_action');
function smc_ajax_handle_deposit_action() {
    check_ajax_referer('smc_deposit_action_nonce', 'nonce');
    if (!current_user_can('administrator')) {
        wp_send_json_error(['message' => 'ليس لديك الصلاحيات الكافية للقيام بهذا الإجراء.']);
    }
    if (!function_exists('smc_get_default_reward_settings') || !function_exists('smc_award_referral_bonuses')) {
         wp_send_json_error(['message' => 'Required helper functions not found.']);
    }

    $deposit_id = isset($_POST['deposit_id']) ? intval($_POST['deposit_id']) : 0;
    $action = isset($_POST['deposit_action']) ? sanitize_key($_POST['deposit_action']) : '';
    $admin_id = get_current_user_id();

    if (!$deposit_id || !in_array($action, ['approve_deposit', 'reject_deposit'])) {
        wp_send_json_error(['message' => 'بيانات الطلب غير صالحة.']);
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'user_deposits';
    $current_time_mysql = current_time('mysql');

    $deposit = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $deposit_id));

    if (!$deposit) {
        wp_send_json_error(['message' => 'لم يتم العثور على سجل الإيداع المطلوب.']);
    }
    if ($deposit->status !== 'pending_admin_approval') { // *** تعديل الشرط هنا ***
        wp_send_json_error(['message' => 'لا يمكن تغيير حالة هذا الإيداع لأنه ليس في حالة انتظار موافقة المسؤول. الحالة الحالية: ' . $deposit->status]);
    }

    $user_id = $deposit->user_id;
    $deposit_amount = (float)$deposit->amount;
    $new_status = ($action === 'approve_deposit') ? 'approved' : 'rejected';
    $new_status_text = ($new_status === 'approved') ? __('موافقة', 'smc') : __('رفض', 'smc');
    $investment_start_datetime_on_approval = $deposit->investment_start_datetime; // Keep existing if already set

    // If approving an investment, ensure its start date is set from config if not already set or if admin needs to override
    if ($new_status === 'approved' && $deposit->deposit_type !== 'daily_tasks') {
        $investment_types_defaults_func_admin = function_exists('smc_get_default_investment_types_settings') ? 'smc_get_default_investment_types_settings' : function() { return []; };
        $all_investment_types_config_admin = get_option('smc_investment_types_settings', $investment_types_defaults_func_admin());
        if (isset($all_investment_types_config_admin[$deposit->deposit_type]['investment_start_datetime'])) {
            $investment_start_datetime_on_approval = $all_investment_types_config_admin[$deposit->deposit_type]['investment_start_datetime'];
        } else {
            error_log("SMC Admin Deposit Approve Warning: Could not find investment_start_datetime in config for type: " . $deposit->deposit_type . " for deposit ID " . $deposit_id);
            // If not found in config, and not already on deposit, it will remain NULL or its previous value.
        }
    }

    $wpdb->query('START TRANSACTION');

    $update_result = $wpdb->update(
        $table_name,
        array(
            'status' => $new_status, 'approval_date' => $current_time_mysql,
            'approved_by' => $admin_id,
            'investment_start_datetime' => $investment_start_datetime_on_approval // Set/Update start date on approval
        ),
        array('id' => $deposit_id),
        array('%s', '%s', '%d', '%s'), // Added format for investment_start_datetime
        array('%d')
    );

    if (false === $update_result) {
        $wpdb->query('ROLLBACK');
        error_log("SMC Deposit Action Error: Failed to update deposit (ID $deposit_id) status/start_date. Rolled back. Error: " . $wpdb->last_error);
        wp_send_json_error(['message' => 'فشل تحديث حالة الإيداع في قاعدة البيانات.']);
    }

    if ($new_status === 'approved') {
        $current_deposit_balance = (float) (get_user_meta($user_id, SMC_DEPOSIT_BALANCE, true) ?? 0.0);
        $new_deposit_balance = $current_deposit_balance + $deposit_amount;
        $update_balance_result = update_user_meta($user_id, SMC_DEPOSIT_BALANCE, $new_deposit_balance);

        $current_timestamp_unix = current_time('timestamp');
        update_user_meta($user_id, SMC_LAST_DEPOSIT_DATE, $current_time_mysql);
        update_user_meta($user_id, SMC_LAST_DEPOSIT_TIMESTAMP, $current_timestamp_unix);

        // Duration for end_date calculation
        $investment_duration_days_approval = 90;
        if (!empty($deposit->investment_duration) && is_numeric($deposit->investment_duration) && $deposit->investment_duration > 0) {
            $investment_duration_days_approval = (int) $deposit->investment_duration;
        }

        $deposit_end_timestamp = strtotime("+$investment_duration_days_approval days", $current_timestamp_unix);
        // Note: SMC_DEPOSIT_END_DATE meta might be primarily for daily_tasks. Investments might have their own end logic based on their specific duration.
        $deposit_end_date_mysql = date('Y-m-d H:i:s', $deposit_end_timestamp);
        update_user_meta($user_id, SMC_DEPOSIT_END_DATE, $deposit_end_date_mysql);

        $new_daily_ad_limit = 10;
        $ad_settings = function_exists('smc_get_ad_deal_settings') ? smc_get_ad_deal_settings() : [];
        foreach ($ad_settings as $key_plan => $plan_data) {
            if (strpos($key_plan, 'plan_') === 0 && is_array($plan_data) && isset($plan_data['deposit_min'], $plan_data['deposit_max'], $plan_data['daily_limit'])) {
                if ($new_deposit_balance >= $plan_data['deposit_min'] && $new_deposit_balance <= $plan_data['deposit_max']) {
                    $new_daily_ad_limit = (int)$plan_data['daily_limit'];
                    break;
                }
            }
        }
        $update_limit_result = update_user_meta($user_id, SMC_DAILY_AD_LIMIT, $new_daily_ad_limit);

        if (function_exists('smc_award_referral_bonuses')) {
            smc_award_referral_bonuses($user_id, $deposit_amount, $deposit->deposit_type);
        }

        if (!$update_balance_result || !$update_limit_result) {
             $wpdb->query('ROLLBACK');
             error_log("SMC Deposit Action Error: Failed to update user meta (balance or limit) for user $user_id after approving deposit $deposit_id. Rolled back.");
             wp_send_json_error(['message' => 'فشل تحديث بيانات المستخدم (الرصيد أو الحد اليومي) بعد الموافقة على الإيداع. تم التراجع عن العملية.']);
             return;
        }
    }

    $wpdb->query('COMMIT');

    $admin_info = get_userdata($admin_id);
    $admin_username = $admin_info ? $admin_info->user_login : __('مسؤول غير معروف', 'smc');

    wp_send_json_success([
        'message' => __('تم تحديث حالة الإيداع بنجاح.', 'smc'),
        'new_status' => $new_status,
        'new_status_text' => $new_status_text,
        'approval_date' => date_i18n('Y-m-d H:i', strtotime($current_time_mysql)),
        'admin_username' => $admin_username
        // 'debug_start_date' => $investment_start_datetime_on_approval // For debugging if needed
    ]);
}

// --- User Withdrawal Requests ---

// Handle Deposit Withdrawal Request Submission
add_action('wp_ajax_handle_withdraw_deposit', 'smc_ajax_handle_withdraw_deposit');
function smc_ajax_handle_withdraw_deposit() {
    check_ajax_referer('smc_withdraw_deposit_action', 'smc_withdraw_deposit_nonce');
    if (!is_user_logged_in()) { wp_send_json_error(['message' => 'User not logged in.']); }
    if (!function_exists('smc_get_user_data')) { wp_send_json_error(['message' => 'Helper function not found.']); }

    $user_id = get_current_user_id();
    $withdraw_amount = isset($_POST['withdraw_amount']) ? floatval($_POST['withdraw_amount']) : 0;
    $withdraw_method = isset($_POST['withdraw_method']) ? sanitize_text_field($_POST['withdraw_method']) : '';
    $withdraw_details = isset($_POST['withdraw_details']) ? sanitize_textarea_field($_POST['withdraw_details']) : '';
    $current_time_mysql = current_time('mysql');

    $user_data = smc_get_user_data($user_id);
    // Use the sum of original active task deposits for withdrawal
    $tasks_deposit_to_withdraw = $user_data['current_tasks_deposit_balance'] ?? 0.0;
    $tasks_deposit_end_timestamp = $user_data['tasks_deposit_end_timestamp_for_calc'] ?? null;

    if ($tasks_deposit_to_withdraw <= 0) {
        wp_send_json_error(['message' => 'خطأ: لا يوجد رصيد وديعة قابل للسحب حاليًا.']);
    }
    // The $withdraw_amount from POST should match $tasks_deposit_to_withdraw
    if (abs($withdraw_amount - $tasks_deposit_to_withdraw) > 0.01) {
        wp_send_json_error(['message' => 'خطأ: مبلغ السحب المحدد (' . number_format($withdraw_amount,2) . ') لا يطابق رصيد وديعة المهام القابلة للسحب (' . number_format($tasks_deposit_to_withdraw,2) . ').']);
        return;
    }

    if (!$tasks_deposit_end_timestamp) {
         wp_send_json_error(['message' => 'خطأ: لم يتم تحديد تاريخ انتهاء صلاحية وديعة المهام.']);
         return;
    }
    if (current_time('timestamp') < $tasks_deposit_end_timestamp) {
        wp_send_json_error(['message' => 'خطأ: لا يمكنك سحب وديعة المهام قبل تاريخ ' . date_i18n('Y-m-d H:i', $tasks_deposit_end_timestamp) . '.']);
        return;
    }

    if (empty($withdraw_method) || empty($withdraw_details)) {
         wp_send_json_error(['message' => 'خطأ: يرجى اختيار طريقة السحب وإدخال التفاصيل.']);
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'user_withdrawals';
    $existing_pending = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table_name WHERE user_id = %d AND status = 'pending' AND withdrawal_type = 'tasks_deposit'", // Check for pending tasks_deposit withdrawal
        $user_id
    ));
    if ($existing_pending > 0) {
         wp_send_json_error(['message' => 'لديك بالفعل طلب سحب وديعة قيد الانتظار.']);
         return;
    }

    $insert_result = $wpdb->insert(
        $table_name,
        array(
            'user_id' => $user_id,
            'amount' => $withdraw_amount,
            'payment_method' => $withdraw_method,
            'withdrawal_details' => $withdraw_details,
            'status' => 'pending',
            'withdrawal_date' => $current_time_mysql,
            'fee_amount' => 0.00,
            'withdrawal_type' => 'tasks_deposit' // Specify withdrawal type
        ),
        array('%d', '%f', '%s', '%s', '%s', '%s', '%f', '%s') // Added format for withdrawal_type
    );

    if (false === $insert_result) {
        error_log("SMC Deposit Withdraw Error: Failed to insert request for user $user_id. Error: " . $wpdb->last_error);
        wp_send_json_error(['message' => 'فشل تسجيل طلب سحب الوديعة في قاعدة البيانات.']);
    }

    wp_send_json_success(['message' => 'تم استلام طلب سحب وديعة المهام بنجاح وهو قيد المراجعة.']);
}

// Handle Profit Withdrawal Request Submission
add_action('wp_ajax_handle_withdraw_profit', 'smc_ajax_handle_withdraw_profit');
function smc_ajax_handle_withdraw_profit() {
    check_ajax_referer('smc_withdraw_profit_action', 'smc_withdraw_profit_nonce');
    if (!is_user_logged_in()) { wp_send_json_error(['message' => 'User not logged in.']); }
    if (!function_exists('smc_get_user_data')) { wp_send_json_error(['message' => 'Helper function not found.']); }

    $user_id = get_current_user_id();
    $withdraw_amount = isset($_POST['withdraw_amount']) ? floatval($_POST['withdraw_amount']) : 0;
    $withdraw_method = isset($_POST['withdraw_method']) ? sanitize_text_field($_POST['withdraw_method']) : '';
    $withdraw_details = isset($_POST['withdraw_details']) ? sanitize_textarea_field($_POST['withdraw_details']) : '';
    $current_time_mysql = current_time('mysql');
    $min_withdraw_profit = 600;

    $user_data = smc_get_user_data($user_id);
    $profit_balance = $user_data['profit_balance'] ?? 0;

    if ($withdraw_amount < $min_withdraw_profit) {
        wp_send_json_error(['message' => 'خطأ: المبلغ المطلوب للسحب أقل من الحد الأدنى (' . number_format($min_withdraw_profit, 2) . ' دج).']);
    }
    if ($withdraw_amount > $profit_balance) {
        wp_send_json_error(['message' => 'خطأ: المبلغ المطلوب للسحب يتجاوز رصيد أرباحك المتاح (' . number_format($profit_balance, 2) . ' دج).']);
    }
     if (empty($withdraw_method) || empty($withdraw_details)) {
         wp_send_json_error(['message' => 'خطأ: يرجى اختيار طريقة السحب وإدخال التفاصيل.']);
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'user_profit_withdrawals';
     $existing_pending = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table_name WHERE user_id = %d AND status = 'pending'",
        $user_id
    ));
    if ($existing_pending > 0) {
         wp_send_json_error(['message' => 'لديك بالفعل طلب سحب أرباح قيد الانتظار.']);
    }

    $insert_result = $wpdb->insert(
        $table_name,
        array(
            'user_id' => $user_id,
            'amount' => $withdraw_amount,
            'payment_method' => $withdraw_method,
            'withdrawal_details' => $withdraw_details,
            'status' => 'pending',
            'withdrawal_date' => $current_time_mysql,
            'fee_amount' => 0.00
        ),
        array('%d', '%f', '%s', '%s', '%s', '%s', '%f')
    );

     if (false === $insert_result) {
        error_log("SMC Profit Withdraw Error: Failed to insert request for user $user_id. Error: " . $wpdb->last_error);
        wp_send_json_error(['message' => 'فشل تسجيل طلب سحب الأرباح في قاعدة البيانات.']);
    }

    wp_send_json_success(['message' => 'تم استلام طلب سحب الأرباح بنجاح وهو قيد المراجعة.']);
}


// --- Admin Withdrawal Approval/Rejection ---

// Handle Deposit Withdrawal Action (Approve/Reject)
add_action('wp_ajax_smc_handle_deposit_withdrawal_action', 'smc_ajax_handle_deposit_withdrawal_action');
function smc_ajax_handle_deposit_withdrawal_action() {
    check_ajax_referer('smc_withdraw_deposit_approval_action', 'nonce');
    if (!current_user_can('administrator')) { wp_send_json_error(['message' => 'ليس لديك الصلاحيات الكافية.']); }
    if (!function_exists('smc_get_default_reward_settings')) { wp_send_json_error(['message' => 'Helper function not found.']); }

    $withdrawal_id = isset($_POST['withdrawal_id']) ? intval($_POST['withdrawal_id']) : 0;
    $action = isset($_POST['withdrawal_action']) ? sanitize_key($_POST['withdrawal_action']) : '';
    $admin_id = get_current_user_id();

    if (!$withdrawal_id || !in_array($action, ['approve', 'reject'])) { wp_send_json_error(['message' => 'بيانات الطلب غير صالحة.']); }

    global $wpdb;
    $table_name = $wpdb->prefix . 'user_withdrawals';
    $current_time_mysql = current_time('mysql');
    $reward_settings = get_option(SMC_REWARD_SETTINGS_OPTION, smc_get_default_reward_settings());

    $withdrawal = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $withdrawal_id));
    if (!$withdrawal) { wp_send_json_error(['message' => 'لم يتم العثور على طلب السحب.']); }
    if ($withdrawal->status !== 'pending') { wp_send_json_error(['message' => 'لا يمكن تغيير حالة هذا الطلب (ليس قيد الانتظار).']); }

    $user_id_to_update = $withdrawal->user_id;
    $amount_withdrawn = (float) $withdrawal->amount;
    $new_status = ($action === 'approve') ? 'approved' : 'rejected';
    $fee_amount = 0.00;

    $wpdb->query('START TRANSACTION');

    if ($new_status === 'approved') {
        $fee_config = $reward_settings['deposit_withdrawal_fee'] ?? null;
        if ($fee_config && $fee_config['type'] === 'percentage_plus_fixed' && isset($fee_config['value']['percentage']) && isset($fee_config['value']['fixed'])) {
            $fee_percentage = (float) $fee_config['value']['percentage'];
            $fee_fixed = (float) $fee_config['value']['fixed'];
            $fee_amount = ($amount_withdrawn * $fee_percentage) + $fee_fixed;
            $fee_amount = max(0, $fee_amount);
        }

        $current_profit_balance_for_fee = (float) get_user_meta($user_id_to_update, SMC_PROFIT_BALANCE, true);
        // --- Get the actual amount of task deposits to be zeroed out ---
        // This should be the sum of original active task deposits for the user
        $user_data_for_approval = smc_get_user_data($user_id_to_update);
        $tasks_deposit_sum_to_clear = $user_data_for_approval['current_tasks_deposit_balance'] ?? 0.0;
        // --- End get task deposit sum ---

        if ($current_profit_balance_for_fee < $fee_amount) {
            $new_status = 'rejected';
            // If fee cannot be covered, the withdrawal is rejected, and deposit balance is NOT zeroed out.
            $fee_amount = 0.00;
            $update_data = ['status' => $new_status, 'approval_date' => $current_time_mysql, 'approved_by' => $admin_id, 'fee_amount' => $fee_amount];
            $update_formats = ['%s', '%s', '%d', '%f'];
            $wpdb->update($table_name, $update_data, ['id' => $withdrawal_id], $update_formats, ['%d']);
            $wpdb->query('COMMIT');
            error_log("SMC Deposit Withdraw Action Error: Insufficient profit balance ($current_profit_balance_for_fee) for user $user_id_to_update to cover fee ($fee_amount) for withdrawal $withdrawal_id. Auto-rejected.");
            wp_send_json_error(['message' => 'خطأ: رصيد أرباح المستخدم غير كافٍ لتغطية رسوم السحب (' . number_format($fee_amount, 2) . ' دج). تم رفض الطلب تلقائياً.']);
            return;
        }
    }

    $update_data = ['status' => $new_status, 'approval_date' => $current_time_mysql, 'approved_by' => $admin_id, 'fee_amount' => $fee_amount];
    $update_formats = ['%s', '%s', '%d', '%f'];
    $update_result = $wpdb->update($table_name, $update_data, ['id' => $withdrawal_id], $update_formats, ['%d']);

    if (false === $update_result) {
        $wpdb->query('ROLLBACK');
        error_log("SMC Deposit Withdraw Action Error: Failed to update status/fee for ID $withdrawal_id. Rolled back. Error: " . $wpdb->last_error);
        wp_send_json_error(['message' => 'فشل تحديث حالة الطلب أو الرسوم في قاعدة البيانات.']);
    }

    if ($new_status === 'approved') {
        $new_profit_balance_after_fee = $current_profit_balance_for_fee - $fee_amount;
        $update_fee_result = update_user_meta($user_id_to_update, SMC_PROFIT_BALANCE, $new_profit_balance_after_fee);
        
        // When a tasks_deposit withdrawal is approved, reduce SMC_DEPOSIT_BALANCE by the sum of original active task deposits.
        // And potentially mark those specific task deposits in user_deposits table as 'withdrawn'.
        $current_spendable_deposit_balance = (float) (get_user_meta($user_id_to_update, SMC_DEPOSIT_BALANCE, true) ?? 0.0);
        $new_spendable_deposit_balance = max(0, $current_spendable_deposit_balance - $tasks_deposit_sum_to_clear); // Ensure it doesn't go negative
        $update_deposit_result = update_user_meta($user_id_to_update, SMC_DEPOSIT_BALANCE, $new_spendable_deposit_balance);

        if (!$update_fee_result || !$update_deposit_result) {
            $wpdb->query('ROLLBACK');
            error_log("SMC Deposit Withdraw Action Error: Failed to update user meta (profit or deposit) for user $user_id_to_update after approving withdrawal $withdrawal_id. Rolled back.");
            wp_send_json_error(['message' => 'فشل تحديث أرصدة المستخدم بعد الموافقة. تم التراجع عن العملية.']);
            return;
        }
    }

    $wpdb->query('COMMIT');

    $admin_info = get_userdata($admin_id);
    $admin_username = $admin_info ? $admin_info->user_login : __('N/A', 'smc');
    $status_text = ($new_status === 'approved') ? __('موافقة', 'smc') : __('رفض', 'smc');

    wp_send_json_success([
        'message' => __('تم تحديث حالة طلب سحب الوديعة بنجاح.', 'smc'),
        'new_status' => $new_status,
        'new_status_text' => $status_text,
        'approval_date' => date_i18n('Y-m-d H:i', strtotime($current_time_mysql)),
        'admin_username' => $admin_username,
        'fee_amount' => $fee_amount
    ]);
}

// Handle Profit Withdrawal Action (Approve/Reject)
add_action('wp_ajax_smc_handle_profit_withdrawal_action', 'smc_ajax_handle_profit_withdrawal_action');
function smc_ajax_handle_profit_withdrawal_action() {
    check_ajax_referer('smc_withdraw_profit_approval_action', 'nonce');
    if (!current_user_can('administrator')) { wp_send_json_error(['message' => 'ليس لديك الصلاحيات الكافية.']); }
    if (!function_exists('smc_get_default_reward_settings')) { wp_send_json_error(['message' => 'Helper function not found.']); }

    $withdrawal_id = isset($_POST['withdrawal_id']) ? intval($_POST['withdrawal_id']) : 0;
    $action = isset($_POST['withdrawal_action']) ? sanitize_key($_POST['withdrawal_action']) : '';
    $admin_id = get_current_user_id();

    if (!$withdrawal_id || !in_array($action, ['approve', 'reject'])) { wp_send_json_error(['message' => 'بيانات الطلب غير صالحة.']); }

    global $wpdb;
    $table_name = $wpdb->prefix . 'user_profit_withdrawals';
    $current_time_mysql = current_time('mysql');
    $reward_settings = get_option(SMC_REWARD_SETTINGS_OPTION, smc_get_default_reward_settings());

    $withdrawal = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $withdrawal_id));
    if (!$withdrawal) { wp_send_json_error(['message' => 'لم يتم العثور على طلب السحب.']); }
    if ($withdrawal->status !== 'pending') { wp_send_json_error(['message' => 'لا يمكن تغيير حالة هذا الطلب (ليس قيد الانتظار).']); }

    $user_id_to_update = $withdrawal->user_id;
    $amount_to_withdraw = (float) $withdrawal->amount;
    $new_status = ($action === 'approve') ? 'approved' : 'rejected';
    $fee_amount = 0.00;

    $wpdb->query('START TRANSACTION');

    if ($new_status === 'approved') {
        $fee_config = $reward_settings['profit_withdrawal_fee'] ?? null;
        if ($fee_config && $fee_config['type'] === 'percentage_plus_fixed' && isset($fee_config['value']['percentage']) && isset($fee_config['value']['fixed'])) {
            $fee_percentage = (float) $fee_config['value']['percentage'];
            $fee_fixed = (float) $fee_config['value']['fixed'];
            $fee_amount = ($amount_to_withdraw * $fee_percentage) + $fee_fixed;
            $fee_amount = max(0, $fee_amount);
        }

        $current_profit_balance = (float) get_user_meta($user_id_to_update, SMC_PROFIT_BALANCE, true);
        $total_deduction = $amount_to_withdraw + $fee_amount;

        if ($current_profit_balance < $total_deduction) {
            $new_status = 'rejected';
            $fee_amount = 0.00;
            $update_data = ['status' => $new_status, 'approval_date' => $current_time_mysql, 'approved_by' => $admin_id, 'fee_amount' => $fee_amount];
            $update_formats = ['%s', '%s', '%d', '%f'];
            $wpdb->update($table_name, $update_data, ['id' => $withdrawal_id], $update_formats, ['%d']);
            $wpdb->query('COMMIT');
            error_log("SMC Profit Withdraw Action Error: Insufficient profit balance ($current_profit_balance) for user $user_id_to_update to approve withdrawal $withdrawal_id (Total Deduction: $total_deduction). Auto-rejected.");
            wp_send_json_error(['message' => 'خطأ: رصيد أرباح المستخدم غير كافٍ للموافقة على هذا المبلغ مع الرسوم (' . number_format($total_deduction, 2) . ' دج). تم رفض الطلب تلقائياً.']);
            return;
        }
    }

    $update_data = ['status' => $new_status, 'approval_date' => $current_time_mysql, 'approved_by' => $admin_id, 'fee_amount' => $fee_amount];
    $update_formats = ['%s', '%s', '%d', '%f'];
    $update_result = $wpdb->update($table_name, $update_data, ['id' => $withdrawal_id], $update_formats, ['%d']);

    if (false === $update_result) {
        $wpdb->query('ROLLBACK');
        error_log("SMC Profit Withdraw Action Error: Failed to update status/fee for ID $withdrawal_id. Rolled back. Error: " . $wpdb->last_error);
        wp_send_json_error(['message' => 'فشل تحديث حالة الطلب أو الرسوم في قاعدة البيانات.']);
    }

    if ($new_status === 'approved') {
        $new_profit_balance_after_deduction = $current_profit_balance - $total_deduction;
        $update_balance_result = update_user_meta($user_id_to_update, SMC_PROFIT_BALANCE, $new_profit_balance_after_deduction);

        if (!$update_balance_result) {
            $wpdb->query('ROLLBACK');
            error_log("SMC Profit Withdraw Action Error: Failed to update profit balance for user $user_id_to_update after approving withdrawal $withdrawal_id (Total Deduction: $total_deduction). Rolled back.");
            wp_send_json_error(['message' => 'فشل تحديث رصيد الأرباح بعد الموافقة. تم التراجع عن العملية.']);
            return;
        }
    }

    $wpdb->query('COMMIT');

    $admin_info = get_userdata($admin_id);
    $admin_username = $admin_info ? $admin_info->user_login : __('N/A', 'smc');
    $status_text = ($new_status === 'approved') ? __('موافقة', 'smc') : __('رفض', 'smc');

    wp_send_json_success([
        'message' => __('تم تحديث حالة طلب سحب الأرباح بنجاح.', 'smc'),
        'new_status' => $new_status,
        'new_status_text' => $status_text,
        'approval_date' => date_i18n('Y-m-d H:i', strtotime($current_time_mysql)),
        'admin_username' => $admin_username,
        'fee_amount' => $fee_amount
    ]);
}


// --- User Withdrawal Cancellation ---
add_action('wp_ajax_smc_cancel_withdrawal_request', 'smc_ajax_cancel_withdrawal_request');
function smc_ajax_cancel_withdrawal_request() {
    check_ajax_referer('smc_cancel_withdrawal_nonce', 'nonce');
    if (!is_user_logged_in()) { wp_send_json_error(['message' => 'User not logged in.']); }

    $user_id = get_current_user_id();
    $withdrawal_id = isset($_POST['withdrawal_id']) ? intval($_POST['withdrawal_id']) : 0;
    $withdrawal_type = isset($_POST['withdrawal_type']) ? sanitize_key($_POST['withdrawal_type']) : '';

    if (!$withdrawal_id || !in_array($withdrawal_type, ['deposit_withdrawal', 'profit_withdrawal'])) {
        wp_send_json_error(['message' => 'بيانات الطلب غير صالحة.']);
    }

    global $wpdb;
    $table_name = '';
    if ($withdrawal_type === 'deposit_withdrawal') {
        $table_name = $wpdb->prefix . 'user_withdrawals';
    } elseif ($withdrawal_type === 'profit_withdrawal') {
        $table_name = $wpdb->prefix . 'user_profit_withdrawals';
    } else {
         wp_send_json_error(['message' => 'نوع السحب غير معروف.']);
    }

    $withdrawal = $wpdb->get_row($wpdb->prepare(
        "SELECT id, user_id, status FROM $table_name WHERE id = %d",
        $withdrawal_id
    ));

    if (!$withdrawal) {
        wp_send_json_error(['message' => 'لم يتم العثور على طلب السحب.']);
    }
    if ($withdrawal->user_id != $user_id) {
        wp_send_json_error(['message' => 'ليس لديك الصلاحية لإلغاء هذا الطلب.']);
    }
    if ($withdrawal->status !== 'pending') {
        wp_send_json_error(['message' => 'لا يمكن إلغاء هذا الطلب لأنه ليس قيد الانتظار.']);
    }

    $update_result = $wpdb->update(
        $table_name,
        ['status' => 'cancelled'],
        ['id' => $withdrawal_id, 'user_id' => $user_id],
        ['%s'],
        ['%d', '%d']
    );

    if (false === $update_result) {
        error_log("SMC Cancel Withdrawal Error: Failed to update status for ID $withdrawal_id in table $table_name. User: $user_id. Error: " . $wpdb->last_error);
        wp_send_json_error(['message' => 'فشل تحديث حالة الطلب في قاعدة البيانات.']);
    }

    wp_send_json_success(['message' => 'تم إلغاء طلب السحب بنجاح.']);
}


// --- AJAX Handler for User Deposit Request ---
add_action('wp_ajax_smc_handle_user_deposit_request', 'smc_ajax_handle_user_deposit_request');
function smc_ajax_handle_user_deposit_request() {
    check_ajax_referer(SMC_USER_DEPOSIT_NONCE, 'nonce');

    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'يرجى تسجيل الدخول أولاً.']);
    }

    $user_id = get_current_user_id();
    global $wpdb;
    $deposits_table = $wpdb->prefix . 'user_deposits';
    $current_time_mysql = current_time('mysql');

    // --- Sanitize and Validate Inputs ---
    $deposit_type = isset($_POST['deposit_type']) ? sanitize_text_field($_POST['deposit_type']) : null;
    $payment_method = isset($_POST['payment_method']) ? sanitize_text_field($_POST['payment_method']) : null;
    $deposit_amount = 0;
    // $investment_package = null; // Old way, replaced by selected_plan_index
    $investment_shares = null;
    $investment_duration_in_days = null; // Will be calculated from plan
    $expected_daily_roi_from_plan = null; // Will be taken from plan's avg_roi
    $investment_start_datetime_for_db = null;
    $selected_plan_index = isset($_POST['selected_plan_index']) && is_numeric($_POST['selected_plan_index']) ? absint($_POST['selected_plan_index']) : null;
    $investment_package_name_for_db = null; // e.g., 'project_key_plan_0'

    $all_investment_types_config = get_option('smc_investment_types_settings', []);
    $valid_dynamic_investment_keys = is_array($all_investment_types_config) ? array_keys($all_investment_types_config) : [];
    $valid_deposit_types = array_merge(['daily_tasks'], $valid_dynamic_investment_keys);

    if (empty($deposit_type) || !in_array($deposit_type, $valid_deposit_types, true)) {
        wp_send_json_error(['message' => 'الرجاء اختيار نوع إيداع صالح.']);
    }
    if (empty($payment_method)) {
        wp_send_json_error(['message' => 'الرجاء اختيار طريقة دفع صالحة.']);
    }

    if ($deposit_type === 'daily_tasks') {
        $deposit_amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
        if ($deposit_amount < 2000 || $deposit_amount > 500000) {
            wp_send_json_error(['message' => 'مبلغ إيداع المهام اليومية يجب أن يكون بين 2000 و 500000 دج.']);
        }
        $investment_duration_in_days = 90; // Default for daily tasks
        $investment_package_name_for_db = 'daily_tasks_package'; // Or similar identifier
    } elseif (isset($all_investment_types_config[$deposit_type])) {
        $current_investment_config = $all_investment_types_config[$deposit_type];
        $investment_share_price = (float) ($current_investment_config['share_price'] ?? 0);

        if ($investment_share_price <= 0) {
            wp_send_json_error(['message' => 'خطأ: سعر الحصة للاستثمار المختار (' . esc_html($current_investment_config['title'] ?? $deposit_type) . ') غير صالح.']);
            return;
        }

        if ($selected_plan_index === null || !isset($current_investment_config['roi_plans'][$selected_plan_index])) {
            wp_send_json_error(['message' => 'الرجاء اختيار خطة استثمار صالحة.']);
            return;
        }
        $selected_plan_details = $current_investment_config['roi_plans'][$selected_plan_index];
        $investment_package_name_for_db = $deposit_type . '_plan_' . $selected_plan_index;

        // Calculate duration in days from selected plan
        $plan_duration_value = intval($selected_plan_details['duration_value']);
        $plan_duration_unit = sanitize_text_field($selected_plan_details['duration_unit']);
        if ($plan_duration_unit === 'minutes') {
            $investment_duration_in_days = $plan_duration_value / (60 * 24);
        } elseif ($plan_duration_unit === 'hours') {
            $investment_duration_in_days = $plan_duration_value / 24;
        } else { // 'days'
            $investment_duration_in_days = $plan_duration_value;
        }

        // Get average ROI from selected plan (store as decimal)
        $expected_daily_roi_from_plan = floatval($selected_plan_details['avg_roi']) / 100;

        $investment_shares = isset($_POST['investment_shares']) ? intval($_POST['investment_shares']) : 0;
        $deposit_amount = $investment_shares * $investment_share_price;

        if (!isset($_POST['accept_contract']) || $_POST['accept_contract'] !== '1') {
            wp_send_json_error(['message' => 'يجب الموافقة على عقد الاستثمار للمتابعة.']);
            return;
        }

        $is_globally_open = isset($current_investment_config['is_active']) ? (bool)$current_investment_config['is_active'] : false;
        $acceptance_end_datetime_str = $current_investment_config['investment_acceptance_end_datetime'] ?? null;

        if (!$is_globally_open) {
            wp_send_json_error(['message' => 'الاستثمار في "' . esc_html($current_investment_config['title']) . '" مغلق حاليًا بأمر الإدارة.']);
            return;
        }
        if ($acceptance_end_datetime_str) {
            if (current_time('timestamp') >= strtotime($acceptance_end_datetime_str)) {
                wp_send_json_error(['message' => 'عذرًا، لقد انتهت فترة قبول الاستثمارات لمشروع "' . esc_html($current_investment_config['title']) . '".']);
                return;
            }
        }

        $user_deposits_table_for_shares_check = $wpdb->prefix . 'user_deposits';
        $purchased_shares_sql_check = $wpdb->prepare(
            "SELECT SUM(investment_shares) FROM {$user_deposits_table_for_shares_check} WHERE deposit_type = %s AND (status = 'approved' OR status = 'pending_admin_approval' OR status = 'pending_user_confirmation')", // Include all non-final states
            $deposit_type
        );
        $purchased_shares_check = (int) $wpdb->get_var($purchased_shares_sql_check);
        $total_shares_config_check = isset($current_investment_config['total_shares']) ? (int)$current_investment_config['total_shares'] : 0;
        $company_shares_config_check = isset($current_investment_config['company_shares']) ? (int)$current_investment_config['company_shares'] : 0;
        $available_for_users_check = $total_shares_config_check - $company_shares_config_check;
        $remaining_for_purchase_check = max(0, $available_for_users_check - $purchased_shares_check);

        if ($investment_shares <= 0) {
             wp_send_json_error(['message' => 'الرجاء إدخال عدد حصص صالح (أكبر من صفر).']);
             return;
        }
        if ($investment_shares > $remaining_for_purchase_check) {
            wp_send_json_error(['message' => 'عذرًا، عدد الحصص المطلوبة (' . $investment_shares . ') يتجاوز الحصص المتاحة للشراء (' . $remaining_for_purchase_check . ' متبقية).']);
            return;
        }
        // Min shares validation (assuming min_shares_overall is part of project config, or plan-specific min_shares)
        $min_shares_for_project = $current_investment_config['min_shares_overall'] ?? 1; // Example, adjust as needed
        if ($investment_shares < $min_shares_for_project) {
            wp_send_json_error(['message' => "الحد الأدنى لعدد الحصص لهذا المشروع هو {$min_shares_for_project}."]);
            return;
        }

        $investment_start_datetime_for_db = $current_investment_config['investment_start_datetime'] ?? current_time('mysql');
    }

    $deposit_proof_path = null;
    if ($payment_method !== 'profit_balance') {
        if (isset($_FILES['deposit_proof']) && $_FILES['deposit_proof']['error'] == UPLOAD_ERR_OK) {
            if (!function_exists('wp_handle_upload')) {
                require_once(ABSPATH . 'wp-admin/includes/file.php');
            }
            $uploaded_file = $_FILES['deposit_proof'];
            $upload_overrides = array('test_form' => false);
            $movefile = wp_handle_upload($uploaded_file, $upload_overrides);

            if ($movefile && !isset($movefile['error'])) {
                $deposit_proof_path = $movefile['file'];
            } else {
                wp_send_json_error(['message' => 'خطأ في رفع ملف إثبات الدفع: ' . ($movefile['error'] ?? 'خطأ غير معروف')]);
                return;
            }
        } else {
            wp_send_json_error(['message' => 'الرجاء إرفاق ملف إثبات الدفع.']);
            return;
        }
    }

    $status = 'pending_admin_approval'; // Default status for new deposits needing admin check
    $admin_id_for_approval = null; // Will be set by admin later

    $wpdb->query('START TRANSACTION');

    if ($payment_method === 'profit_balance') {
        $profit_balance = (float) (get_user_meta($user_id, SMC_PROFIT_BALANCE, true) ?? 0.0);
        if ($profit_balance >= $deposit_amount) {
            $new_profit_balance = $profit_balance - $deposit_amount;
            $update_profit_meta = update_user_meta($user_id, SMC_PROFIT_BALANCE, $new_profit_balance);

            if ($update_profit_meta) {
                $status = 'approved'; // Auto-approve if paid from profit balance
                $admin_id_for_approval = $user_id; // Or a system ID like 0

                $current_deposit_balance = (float) (get_user_meta($user_id, SMC_DEPOSIT_BALANCE, true) ?? 0.0);
                $new_deposit_balance = $current_deposit_balance + $deposit_amount;
                update_user_meta($user_id, SMC_DEPOSIT_BALANCE, $new_deposit_balance);

                $current_timestamp_unix = current_time('timestamp');
                update_user_meta($user_id, SMC_LAST_DEPOSIT_DATE, $current_time_mysql);
                update_user_meta($user_id, SMC_LAST_DEPOSIT_TIMESTAMP, $current_timestamp_unix);

                // For daily_tasks, the end date is fixed (e.g., 90 days from now)
                // For investments, the end date is calculated based on its specific duration from its start_datetime
                if ($deposit_type === 'daily_tasks') {
                    $deposit_end_timestamp = strtotime("+{$investment_duration_in_days} days", $current_timestamp_unix);
                    $deposit_end_date_mysql = date('Y-m-d H:i:s', $deposit_end_timestamp);
                    update_user_meta($user_id, SMC_DEPOSIT_END_DATE, $deposit_end_date_mysql); // This meta is more for tasks
                }
                // Note: Investment end date is implicitly defined by its start_datetime and duration in the user_deposits table.

                // Update daily ad limit if it's a daily_tasks deposit
                if ($deposit_type === 'daily_tasks') {
                    $new_daily_ad_limit = 10; // Default
                    $ad_settings = function_exists('smc_get_ad_deal_settings') ? smc_get_ad_deal_settings() : [];
                    foreach ($ad_settings as $key_plan => $plan_data) {
                        if (strpos($key_plan, 'plan_') === 0 && is_array($plan_data) && isset($plan_data['deposit_min'], $plan_data['deposit_max'], $plan_data['daily_limit'])) {
                            if ($new_deposit_balance >= $plan_data['deposit_min'] && $new_deposit_balance <= $plan_data['deposit_max']) {
                                $new_daily_ad_limit = (int)$plan_data['daily_limit'];
                                break;
                            }
                        }
                    }
                    update_user_meta($user_id, SMC_DAILY_AD_LIMIT, $new_daily_ad_limit);
                }

                if (function_exists('smc_award_referral_bonuses')) {
                    smc_award_referral_bonuses($user_id, $deposit_amount, $deposit_type);
                }

            } else {
                $wpdb->query('ROLLBACK');
                wp_send_json_error(['message' => 'فشل تحديث رصيد الأرباح. لم يتم الإيداع.']);
            }
        } else {
            $wpdb->query('ROLLBACK');
            wp_send_json_error(['message' => 'رصيد الأرباح غير كافٍ لإتمام عملية الإيداع.']);
            return;
        }
    }

    $insert_data = [
        'user_id' => $user_id,
        'amount' => $deposit_amount,
        'payment_method' => $payment_method,
        'status' => $status,
        'deposit_date' => $current_time_mysql,
        'deposit_proof_path' => $deposit_proof_path,
        'deposit_type' => $deposit_type,
        'investment_package' => $investment_package_name_for_db, // Store the generated package name
        'investment_shares' => $investment_shares,
        'investment_duration' => $investment_duration_in_days, // Store duration in days
        'expected_daily_roi' => $expected_daily_roi_from_plan, // Store plan's avg ROI
        'investment_start_datetime' => ($deposit_type !== 'daily_tasks' || $status === 'approved') ? $investment_start_datetime_for_db : null, // Set for investments, or for tasks if auto-approved
        'selected_investment_plan_index' => ($deposit_type !== 'daily_tasks') ? $selected_plan_index : null,
    ];
    $insert_formats = ['%d', '%f', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%f', '%f', '%s', '%d'];

    if ($status === 'approved') {
        $insert_data['approval_date'] = $current_time_mysql;
        $insert_data['approved_by'] = $admin_id_for_approval;
        // If it's a daily_tasks deposit auto-approved from profit, its start_datetime is now.
        if ($deposit_type === 'daily_tasks') {
            $insert_data['investment_start_datetime'] = $current_time_mysql;
        }
        array_push($insert_formats, '%s', '%d'); // For approval_date, approved_by
    }


    $insert_result = $wpdb->insert($deposits_table, $insert_data, $insert_formats);

    if (false === $insert_result) {
        $wpdb->query('ROLLBACK');
        error_log("SMC User Deposit Error: Failed to insert deposit for user $user_id. Type: $deposit_type. Error: " . $wpdb->last_error . " Data: " . print_r($insert_data, true) . " Formats: " . print_r($insert_formats, true));
        wp_send_json_error(['message' => 'فشل تسجيل طلب الإيداع في قاعدة البيانات.']);
        return;
    }

    $wpdb->query('COMMIT');

    $success_message = ($status === 'approved') ? 'تم الإيداع بنجاح من رصيد الأرباح وتمت الموافقة عليه.' : 'تم استلام طلب الإيداع الخاص بك بنجاح وهو قيد المراجعة.';
    wp_send_json_success(['message' => $success_message]);
}

// This handler was duplicated, removing the second one.
// add_action('wp_ajax_smc_fetch_dashboard_data', 'smc_fetch_dashboard_data_handler');
// add_action('wp_ajax_nopriv_smc_fetch_dashboard_data', 'smc_fetch_dashboard_data_handler');

/**
 * AJAX Handler: Admin cancels a scheduled investment withdrawal request.
 */
add_action('wp_ajax_smc_admin_cancel_scheduled_investment_withdrawal', 'smc_ajax_admin_cancel_scheduled_investment_withdrawal');
function smc_ajax_admin_cancel_scheduled_investment_withdrawal() {
    // Ensure a unique nonce for admin actions if different from user nonces
    // For this example, let's assume 'smc_admin_actions_nonce' is defined and passed.
    // You might need to create this nonce in enqueue.php for admin users.
    check_ajax_referer('smc_admin_actions_nonce', 'nonce'); // Or use a more general admin nonce

    if (!current_user_can('manage_options')) { // Or a more specific capability
        wp_send_json_error(['message' => 'ليس لديك الصلاحيات الكافية لهذا الإجراء.']);
        return;
    }

    $request_id = isset($_POST['request_id']) ? intval($_POST['request_id']) : 0;

    if (!$request_id) {
        wp_send_json_error(['message' => 'معرف الطلب غير صالح.']);
        return;
    }

    global $wpdb;
    $investment_withdrawal_requests_table = $wpdb->prefix . 'smc_investment_withdrawal_requests';
    $user_deposits_table = $wpdb->prefix . 'user_deposits';

    $request = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$investment_withdrawal_requests_table} WHERE id = %d",
        $request_id
    ));

    if (!$request) {
        wp_send_json_error(['message' => 'لم يتم العثور على طلب السحب المجدول.']);
        return;
    }

    if ($request->status !== 'scheduled') {
        wp_send_json_error(['message' => 'يمكن فقط إلغاء طلبات السحب المجدولة التي هي في حالة "مجدولة". الحالة الحالية: ' . $request->status]);
        return;
    }

    $wpdb->query('START TRANSACTION');

    $update_request_result = $wpdb->update(
        $investment_withdrawal_requests_table,
        ['status' => 'cancelled_by_admin', 'processed_timestamp' => current_time('mysql'), 'cancellation_reason' => 'Cancelled by Administrator'],
        ['id' => $request_id],
        ['%s', '%s', '%s'],
        ['%d']
    );

    if (false === $update_request_result) {
        $wpdb->query('ROLLBACK');
        wp_send_json_error(['message' => 'فشل تحديث حالة طلب السحب المجدول.']);
        return;
    }

    // Revert the original deposit status to 'approved'
    $revert_deposit_status_result = $wpdb->update(
        $user_deposits_table,
        ['status' => 'approved'],
        ['id' => $request->deposit_id, 'status' => 'withdrawal_scheduled'], // Ensure it was in 'withdrawal_scheduled' state
        ['%s'],
        ['%d', '%s']
    );
    // Log warning if original deposit status couldn't be reverted, but proceed with cancellation success
    if (false === $revert_deposit_status_result) {
        error_log("SMC Admin Cancel Scheduled Withdraw Warning: Failed to revert original deposit status for ID {$request->deposit_id} or it was not in 'withdrawal_scheduled' state. Request cancellation still proceeded.");
    }

    $wpdb->query('COMMIT');
    wp_send_json_success(['message' => 'تم إلغاء طلب سحب الاستثمار المجدول بنجاح بواسطة المسؤول.']);
}


/**
 * AJAX Handler: Admin saves the actual final profit for an investment type.
 */
add_action('wp_ajax_smc_admin_save_actual_final_profit', 'smc_ajax_admin_save_actual_final_profit');
function smc_ajax_admin_save_actual_final_profit() {
    check_ajax_referer('smc_admin_save_profit_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => __('You do not have permission to perform this action.', 'smc')]);
        return;
    }

    $investment_key = isset($_POST['investment_key']) ? sanitize_key($_POST['investment_key']) : null;
    $actual_final_profit_input = isset($_POST['actual_final_profit']) ? $_POST['actual_final_profit'] : null;

    if (empty($investment_key)) {
        wp_send_json_error(['message' => __('Investment key is missing.', 'smc')]);
        return;
    }

    if ($actual_final_profit_input === null || $actual_final_profit_input === '' || !is_numeric($actual_final_profit_input)) {
        wp_send_json_error(['message' => __('Please enter a valid numeric value for the actual final profit.', 'smc')]);
        return;
    }
    $actual_final_profit = floatval($actual_final_profit_input);

    $all_investments = get_option('smc_investment_types_settings', []);
    if (!is_array($all_investments) || !isset($all_investments[$investment_key])) {
        wp_send_json_error(['message' => __('Investment type not found.', 'smc')]);
        return;
    }

    $all_investments[$investment_key]['actual_final_profit'] = $actual_final_profit;
    // The 'final_profit_margin_recorded' checkbox is a separate manual action by admin on the edit page.
    // Setting 'actual_final_profit' here makes it available for the cron job.

    if (update_option('smc_investment_types_settings', $all_investments)) {
        error_log("SMC Admin: Updated actual_final_profit for {$investment_key} to {$actual_final_profit}");
        // Optionally, you could try to trigger the specific cron task for this project if desired,
        // but the daily cron should pick it up.
        // Example: do_action(SMC_CALCULATE_FINAL_MARGIN_CRON_HOOK); // This runs for all, might be too much.
        // A more targeted approach would be needed if immediate processing is required.
        wp_send_json_success(['message' => __('Actual final profit saved successfully. The system will process it.', 'smc')]);
    } else {
        wp_send_json_error(['message' => __('Failed to save actual final profit.', 'smc')]);
    }
}

/**
 * AJAX Handler: Admin deletes an investment project type.
 */
add_action('wp_ajax_smc_admin_delete_investment_project', 'smc_ajax_admin_delete_investment_project');
function smc_ajax_admin_delete_investment_project() {
    check_ajax_referer('smc_admin_delete_investment_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => __('You do not have permission to perform this action.', 'smc')]);
        return;
    }

    $investment_key = isset($_POST['investment_key']) ? sanitize_key($_POST['investment_key']) : null;

    if (empty($investment_key)) {
        wp_send_json_error(['message' => __('Investment key is missing.', 'smc')]);
        return;
    }

    $all_investments = get_option('smc_investment_types_settings', []);
    if (!is_array($all_investments) || !isset($all_investments[$investment_key])) {
        wp_send_json_error(['message' => __('Investment type not found or already deleted.', 'smc')]);
        return;
    }

    unset($all_investments[$investment_key]);

    if (update_option('smc_investment_types_settings', $all_investments)) {
        error_log("SMC Admin: Deleted investment project with key: {$investment_key}");
        wp_send_json_success(['message' => __('Investment project deleted successfully.', 'smc')]);
    } else {
        // This might happen if the option was already the same (e.g., key didn't exist, but we checked above)
        // or if there was a database error during update_option.
        wp_send_json_error(['message' => __('Failed to delete investment project. The option might not have changed or a database error occurred.', 'smc')]);
    }
}

/**
 * AJAX Handler: Admin manually runs a cron job.
 */
add_action('wp_ajax_smc_admin_run_cron_now', 'smc_ajax_admin_run_cron_now');
function smc_ajax_admin_run_cron_now() {
    // Check nonce for security
    check_ajax_referer('smc_run_cron_job_nonce', 'nonce');

    // Check user capabilities
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => __('You do not have permission to perform this action.', 'smc')]);
        return;
    }

    $hook_name = isset($_POST['hook_name']) ? sanitize_text_field($_POST['hook_name']) : null;

    if (empty($hook_name)) {
        wp_send_json_error(['message' => __('Cron hook name is missing.', 'smc')]);
        return;
    }

    // Validate if this is one of our known hooks (optional but good for security)
    $known_smc_hooks = [
        defined('SMC_MONTHLY_SALARY_CRON_HOOK') ? SMC_MONTHLY_SALARY_CRON_HOOK : null,
        defined('SMC_PROCESS_INVESTMENT_PROFIT_CRON_HOOK') ? SMC_PROCESS_INVESTMENT_PROFIT_CRON_HOOK : null,
        defined('SMC_CALCULATE_FINAL_MARGIN_CRON_HOOK') ? SMC_CALCULATE_FINAL_MARGIN_CRON_HOOK : null,
    ];
    $known_smc_hooks = array_filter($known_smc_hooks); // Remove nulls if constants weren't defined

    if (!in_array($hook_name, $known_smc_hooks, true)) {
        wp_send_json_error(['message' => __('Invalid or unknown cron hook specified.', 'smc') . ' Hook: ' . esc_html($hook_name)]);
        return;
    }

    // Execute the action associated with the hook immediately
    // This assumes the callback functions are already hooked to these actions.
    error_log("SMC Admin: Manually triggering cron hook '{$hook_name}' by user ID " . get_current_user_id());
    do_action($hook_name);

    // Note: do_action() doesn't return a value indicating success of the underlying functions.
    // We assume it was triggered. The cron functions themselves should log their success/failure.
    wp_send_json_success(['message' => sprintf(__('Cron job "%s" has been triggered. Check logs for details.', 'smc'), esc_html($hook_name))]);
}


/**
 * AJAX Handler: Admin cancels old investment projects and refunds active deposits.
 * Projects created before 'new_investment_1000011' will be targeted.
 */
add_action('wp_ajax_smc_admin_cancel_old_investments_and_refund', 'smc_ajax_admin_cancel_old_investments_and_refund');
function smc_ajax_admin_cancel_old_investments_and_refund() {
    check_ajax_referer('smc_admin_cancel_old_investments_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => __('You do not have permission to perform this action.', 'smc')]);
        return;
    }

    global $wpdb;
    $user_deposits_table = $wpdb->prefix . 'user_deposits';
    $rewards_table = $wpdb->prefix . 'smc_rewards_log';
    $investment_withdrawal_requests_table = $wpdb->prefix . 'smc_investment_withdrawal_requests';

    $all_investment_settings = get_option('smc_investment_types_settings', []);
    if (!is_array($all_investment_settings)) {
        $all_investment_settings = [];
    }

    $cutoff_numeric_part = 1000011;
    $investment_keys_to_process = [];
    $processed_deposit_count = 0;
    $total_refunded_amount = 0;
    $processed_withdrawal_requests_count = 0;
    $deleted_investment_types_count = 0;
    $current_time_mysql = current_time('mysql');

    // Identify old investment types
    foreach ($all_investment_settings as $key => $config) {
        if (strpos($key, 'new_investment_') === 0) {
            $numeric_part_str = substr($key, strlen('new_investment_'));
            if (is_numeric($numeric_part_str)) {
                $numeric_part_int = intval($numeric_part_str);
                if ($numeric_part_int < $cutoff_numeric_part) {
                    $investment_keys_to_process[] = $key;
                }
            }
        }
    }

    if (empty($investment_keys_to_process)) {
        wp_send_json_success(['message' => __('No old investment projects found to process.', 'smc')]);
        return;
    }

    $log_messages = [];

    foreach ($investment_keys_to_process as $investment_key) {
        $log_messages[] = "Processing investment type: {$investment_key}";

        // Find active deposits for this investment type
        $active_deposits = $wpdb->get_results($wpdb->prepare(
            "SELECT id, user_id, amount FROM {$user_deposits_table} WHERE deposit_type = %s AND status IN ('approved', 'withdrawal_scheduled')",
            $investment_key
        ));

        if (!empty($active_deposits)) {
            foreach ($active_deposits as $deposit) {
                $wpdb->query('START TRANSACTION');

                // 1. Refund amount to user's profit balance
                $current_profit_balance = (float) (get_user_meta($deposit->user_id, SMC_PROFIT_BALANCE, true) ?? 0.0);
                $new_profit_balance = $current_profit_balance + (float) $deposit->amount;
                $update_profit_meta = update_user_meta($deposit->user_id, SMC_PROFIT_BALANCE, $new_profit_balance);

                // 2. Update deposit status
                $update_deposit_status = $wpdb->update(
                    $user_deposits_table,
                    ['status' => 'cancelled_by_admin_refunded'],
                    ['id' => $deposit->id],
                    ['%s'],
                    ['%d']
                );

                // 3. Log the refund
                $log_refund = $wpdb->insert(
                    $rewards_table,
                    [
                        'user_id' => $deposit->user_id,
                        'reward_type' => 'investment_refund_old_project',
                        'amount' => (float) $deposit->amount,
                        'reward_timestamp' => $current_time_mysql,
                        'related_info' => "Refund for cancelled project: {$investment_key}, Deposit ID: {$deposit->id}"
                    ],
                    ['%d', '%s', '%f', '%s', '%s']
                );

                // 4. Cancel any related scheduled withdrawal requests
                $update_withdrawal_requests = $wpdb->update(
                    $investment_withdrawal_requests_table,
                    ['status' => 'cancelled_due_to_project_refund', 'processed_timestamp' => $current_time_mysql, 'cancellation_reason' => 'Project cancelled and refunded by admin'],
                    ['deposit_id' => $deposit->id, 'status' => 'scheduled'], // Only cancel 'scheduled' ones
                    ['%s', '%s', '%s'],
                    ['%d', '%s']
                );
                if ($update_withdrawal_requests !== false && $wpdb->rows_affected > 0) {
                    $processed_withdrawal_requests_count += $wpdb->rows_affected;
                }


                if ($update_profit_meta && $update_deposit_status !== false && $log_refund !== false) {
                    $wpdb->query('COMMIT');
                    $processed_deposit_count++;
                    $total_refunded_amount += (float) $deposit->amount;
                    $log_messages[] = "  - Refunded deposit ID {$deposit->id} (User {$deposit->user_id}, Amount {$deposit->amount}) for project {$investment_key}.";
                } else {
                    $wpdb->query('ROLLBACK');
                    $log_messages[] = "  - FAILED to refund deposit ID {$deposit->id} for project {$investment_key}. Rolled back. ProfitMeta:{$update_profit_meta}, DepositStatus:{$update_deposit_status}, LogRefund:{$log_refund}";
                    error_log("SMC Admin Cancel Old Investments: Failed to process deposit ID {$deposit->id}. ProfitMeta: " . ($update_profit_meta?'OK':'Fail') . ", DepositStatus: " . ($update_deposit_status?'OK':'Fail') . ", LogRefund: " . ($log_refund?'OK':'Fail'));
                }
            }
        } else {
            $log_messages[] = "  - No active deposits found for project {$investment_key}.";
        }

        // Remove the investment type from settings
        unset($all_investment_settings[$investment_key]);
        $deleted_investment_types_count++;
        $log_messages[] = "  - Marked investment type {$investment_key} for deletion from settings.";
    }

    update_option('smc_investment_types_settings', $all_investment_settings);
    $log_messages[] = "Updated smc_investment_types_settings option.";

    $final_message = sprintf(__('Operation completed. Processed %d old investment types. Refunded %d deposits, totaling %s DZD. Cancelled %d scheduled withdrawal requests. Deleted %d investment type configurations.', 'smc'), count($investment_keys_to_process), $processed_deposit_count, number_format($total_refunded_amount, 2), $processed_withdrawal_requests_count, $deleted_investment_types_count);
    error_log("SMC Admin Cancel Old Investments: " . $final_message . " Details: " . implode("\n", $log_messages));
    wp_send_json_success(['message' => $final_message, 'details' => $log_messages]);
}


?>

