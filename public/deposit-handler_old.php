<?php
/**
 * Handles deposit form submissions.
 */

// تأكد من تحميل بيئة ووردبريس
$wp_load_path = dirname(__FILE__, 5) . '/wp-load.php'; // Adjust path if needed
if (file_exists($wp_load_path)) {
    require_once($wp_load_path);
} else {
    $wp_load_path = dirname(__FILE__, 4) . '/wp-load.php'; // Adjust path if needed
    if (file_exists($wp_load_path)) {
        require_once($wp_load_path);
    } else {
        die('Could not load WordPress environment.');
    }
}

if (!defined('ABSPATH')) exit;

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['deposit_form'])) {

    if (!is_user_logged_in()) {
        wp_redirect(wp_login_url(home_url('/deposit/')));
        exit;
    }

    // *** بداية التعديل: تضمين ملفات الرفع ***
    if (!function_exists('wp_handle_upload')) {
        require_once(ABSPATH . 'wp-admin/includes/file.php');
    }
    // *** نهاية التعديل ***

    // Get amount based on deposit type
    $deposit_type = isset($_POST["deposit_type"]) ? sanitize_text_field($_POST["deposit_type"]) : "daily_tasks";
    $amount = 0;
    $investment_package = null;
    $investment_shares = null;

    if ($deposit_type === 'daily_tasks') {
        $amount = isset($_POST["amount_daily_tasks"]) ? floatval(sanitize_text_field($_POST["amount_daily_tasks"])) : 0;
    } elseif ($deposit_type === 'investment_sugar') {
        $investment_shares = isset($_POST["investment_shares"]) ? intval($_POST["investment_shares"]) : 0;
        $investment_package = isset($_POST["investment_package"]) ? sanitize_text_field($_POST["investment_package"]) : "";
        if ($investment_shares > 0) {
            $amount = $investment_shares * 100000; // Calculate amount based on shares
        }
    }
    $payment_method = isset($_POST["payment_method"]) ? sanitize_text_field($_POST["payment_method"]) : "";
    $user_id = isset($_POST["user_id"]) ? intval($_POST["user_id"]) : get_current_user_id(); // Get user_id from form or current user
    $current_time_mysql = current_time('mysql');
    $uploaded_file_path = null; // *** تعديل: متغير لتخزين مسار الملف ***

    global $wpdb;
    $table_name = $wpdb->prefix . 'user_deposits';

    if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
         wp_redirect(add_query_arg('deposit_error', 'table_not_found', home_url('/deposit/')));
         exit;
    }

    // Validate amount based on type
    if ($deposit_type === 'daily_tasks') {
        if ($amount < 2000 || $amount > 500000) {
            wp_redirect(add_query_arg('deposit_error', 'invalid_data', home_url('/deposit/')));
            exit;
        }
    } elseif ($deposit_type === 'investment_sugar') {if (empty($investment_package) || $investment_shares <= 0 || $amount <= 0) {
            wp_redirect(add_query_arg('deposit_error', 'invalid_data', home_url('/deposit/')));
            exit;
        }
    }
    if (empty($payment_method)) {
            // Use a more specific error for investment
            wp_redirect(add_query_arg('deposit_error', 'invalid_investment_data', home_url('/deposit/')));
            exit;
    }

    // --- تحديد ما إذا كانت الطريقة خارجية وتتطلب إثباتًا ---
    $is_external_method = in_array($payment_method, ['bank', 'baridimob', 'usdt_trc20']);

    // --- معالجة رفع الملف للطرق الخارجية ---
    if ($is_external_method) {
        if (isset($_FILES['deposit_proof']) && !empty($_FILES['deposit_proof']['name'])) {
            $uploadedfile = $_FILES['deposit_proof'];
            $upload_overrides = array('test_form' => false); // مهم لـ wp_handle_upload

            // *** تعديل: استخدام wp_handle_upload ***
            $movefile = wp_handle_upload($uploadedfile, $upload_overrides);

            if ($movefile && !isset($movefile['error'])) {
                // تم الرفع بنجاح
                $uploaded_file_path = $movefile['file']; // مسار الملف في نظام الملفات
                // $uploaded_file_url = $movefile['url']; // رابط الملف (يمكن استخدامه للعرض)
                error_log("SMC Deposit Proof Uploaded: User ID $user_id, Path: $uploaded_file_path"); // تسجيل للمتابعة
            } else {
                // فشل الرفع
                $upload_error = isset($movefile['error']) ? $movefile['error'] : 'Unknown upload error.';
                error_log("SMC Deposit Proof Upload Error: User ID $user_id, Error: $upload_error");
                wp_redirect(add_query_arg('deposit_error', 'upload_failed', home_url('/deposit/')));
                exit;
            }
        } else {
            // لم يتم رفع ملف إثبات وهو مطلوب
            wp_redirect(add_query_arg('deposit_error', 'proof_required', home_url('/deposit/')));
            exit;
        }
    }
    // --- نهاية معالجة رفع الملف ---


    if ($payment_method === 'profit_balance') {
        // --- معالجة الإيداع من رصيد الأرباح (الكود السابق يبقى كما هو) ---
        if (!function_exists('smc_get_user_data')) {
             wp_redirect(add_query_arg('deposit_error', 'internal_error', home_url('/deposit/')));
             exit;
        }
        $user_data = smc_get_user_data($user_id);
        $profit_balance = $user_data['profit_balance'];

        if ($amount > $profit_balance) {
            wp_redirect(add_query_arg('deposit_error', 'insufficient_profit', home_url('/deposit/')));
            exit;
        }

        $new_profit_balance = $profit_balance - $amount;
        $current_deposit_balance = $user_data['current_deposit'];
        $new_deposit_balance = $current_deposit_balance + $amount;

        update_user_meta($user_id, SMC_PROFIT_BALANCE, $new_profit_balance);
        update_user_meta($user_id, SMC_DEPOSIT_BALANCE, $new_deposit_balance);
        update_user_meta($user_id, SMC_LAST_DEPOSIT_DATE, $current_time_mysql);
        $deposit_end_timestamp = strtotime('+90 days', strtotime($current_time_mysql));
        $investment_duration_days = 90; // Default for daily tasks
        $expected_roi_val = null;

        if ($deposit_type === 'investment_sugar') {
            $package_details_map = [
                '30_days' => ['duration' => 30, 'roi' => 0.012], // 1.2%
                '60_days' => ['duration' => 60, 'roi' => 0.018], // 1.8%
                '90_days' => ['duration' => 90, 'roi' => 0.023], // 2.3%
            ];
            if (isset($package_details_map[$investment_package])) {
                $investment_duration_days = $package_details_map[$investment_package]['duration'];
                $expected_roi_val = $package_details_map[$investment_package]['roi'];
                $deposit_end_timestamp = strtotime("+$investment_duration_days days", strtotime($current_time_mysql));
            }
        }
        $deposit_end_date_mysql = date('Y-m-d H:i:s', $deposit_end_timestamp);
        update_user_meta($user_id, SMC_DEPOSIT_END_DATE, $deposit_end_date_mysql);
        // Also update last deposit timestamp for consistency if this is the latest deposit
        $last_recorded_deposit_ts = (int)get_user_meta($user_id, SMC_LAST_DEPOSIT_TIMESTAMP, true);
        if (strtotime($current_time_mysql) > $last_recorded_deposit_ts) {
            update_user_meta($user_id, SMC_LAST_DEPOSIT_TIMESTAMP, strtotime($current_time_mysql));
        }

        // إعادة حساب الحد اليومي (الكود السابق يبقى كما هو)
        // For daily tasks deposits, update ad limit. For investment, ad limit might not apply or be different.
        if ($deposit_type === 'daily_tasks') {
            $new_daily_ad_limit = 10;
            // This logic should ideally come from smc_get_ad_deal_settings()
            if ($new_deposit_balance >= 500000) $new_daily_ad_limit = 17;
            elseif ($new_deposit_balance >= 250000) $new_daily_ad_limit = 16;
            elseif ($new_deposit_balance >= 100000) $new_daily_ad_limit = 15;
            elseif ($new_deposit_balance >= 50000) $new_daily_ad_limit = 14;
            elseif ($new_deposit_balance >= 25000) $new_daily_ad_limit = 13;
            elseif ($new_deposit_balance >= 10000) $new_daily_ad_limit = 12;
            elseif ($new_deposit_balance >= 5000) $new_daily_ad_limit = 11;
            update_user_meta($user_id, SMC_DAILY_AD_LIMIT, $new_daily_ad_limit);
        }

        // Prepare data for insertion, including investment details if applicable
        $insert_data_approved = array(
            'user_id' => $user_id,
            'amount' => $amount,
            'payment_method' => $payment_method,
            'status' => 'approved',
            'deposit_date' => $current_time_mysql,
            'approval_date' => $current_time_mysql,
            'approved_by' => 0, // 0 for system/auto approval
            'deposit_type' => $deposit_type // Store deposit type
            // investment_package, investment_shares, investment_duration, expected_daily_roi will be added below if applicable       
        );
        $insert_formats_approved = array('%d', '%f', '%s', '%s', '%s', '%s', '%d', '%s');

        if ($deposit_type === 'investment_sugar') {
            $insert_data_approved['investment_package'] = $investment_package;
            $insert_data_approved['investment_shares'] = $investment_shares;
            $insert_data_approved['investment_duration'] = $investment_duration_days;
            $insert_data_approved['expected_daily_roi'] = $expected_roi_val;
            array_push($insert_formats_approved, '%s', '%d', '%d', '%f');
        }

        $insert_result = $wpdb->insert($table_name, $insert_data_approved, $insert_formats_approved);

        if (false === $insert_result) {
            error_log("SMC Error: Failed to insert APPROVED deposit from profit for user $user_id. Error: " . $wpdb->last_error);
            wp_redirect(add_query_arg('deposit_error', 'db_insert_failed', home_url('/deposit/')));
            exit;
        }

        // Award referral bonuses for auto-approved deposit from profit balance
        if (function_exists('smc_award_referral_bonuses')) {
            smc_award_referral_bonuses($user_id, $amount, $deposit_type);
        }

        wp_redirect(add_query_arg('deposit_success', 'profit_transfer', home_url('/transactional/')));
        exit;

    } else {
        // --- المنطق للطرق الخارجية (تحويل بنكي، BaridiMob, USDT) ---
        $status = "pending";

        // *** تعديل: إضافة مسار الملف إلى بيانات الإدراج ***
        $insert_data = array(
            'user_id' => $user_id,
            'amount' => $amount,
            'payment_method' => $payment_method,
            'status' => $status,
            'deposit_date' => $current_time_mysql,
            'deposit_proof_path' => $uploaded_file_path, // إضافة المسار هنا
            'deposit_type' => $deposit_type // Store deposit type
        );
        $insert_formats = array('%d', '%f', '%s', '%s', '%s', '%s', '%s'); // إضافة تنسيق للمسار و deposit_type

        if ($deposit_type === 'investment_sugar') {
            $insert_data['investment_package'] = $investment_package;
            $insert_data['investment_shares'] = $investment_shares;
            // Get duration and ROI for pending investment deposits as well
            $investment_duration_days_pending = 90; // Default
            $expected_roi_val_pending = null;
            $package_details_map_pending = [ /* same as above */ '30_days' => ['duration' => 30, 'roi' => 0.012], '60_days' => ['duration' => 60, 'roi' => 0.018], '90_days' => ['duration' => 90, 'roi' => 0.023]];
            if (isset($package_details_map_pending[$investment_package])) {
                $investment_duration_days_pending = $package_details_map_pending[$investment_package]['duration'];
                $expected_roi_val_pending = $package_details_map_pending[$investment_package]['roi'];
            }
            $insert_data['investment_duration'] = $investment_duration_days_pending;
            $insert_data['expected_daily_roi'] = $expected_roi_val_pending;
            array_push($insert_formats, '%s', '%d', '%d', '%f');
        }
        $insert_result = $wpdb->insert($table_name, $insert_data, $insert_formats);


        if (false === $insert_result) {
            error_log("SMC Error: Failed to insert PENDING deposit for user $user_id. Error: " . $wpdb->last_error);
            // *** اختياري: محاولة حذف الملف المرفوع إذا فشل الإدراج ***
            if ($uploaded_file_path && file_exists($uploaded_file_path)) {
                unlink($uploaded_file_path);
            }
            wp_redirect(add_query_arg('deposit_error', 'db_insert_failed', home_url('/deposit/')));
            exit;
        }

        wp_redirect(add_query_arg('deposit_success', 'pending', home_url('/transactional/')));
        exit;
    }

} else {
    wp_redirect(home_url('/deposit/'));
    exit;
}
?>
