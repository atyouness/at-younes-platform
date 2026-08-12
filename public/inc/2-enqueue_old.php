<?php
/**
 * Enqueue scripts and styles for the SMC Group DZ theme.
 *
 * @package SMC Group DZ Child
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Enqueue Parent and Child Styles
function child_enqueue_parent_styles() {
    wp_enqueue_style('parent-style', get_template_directory_uri() . '/style.css');
    wp_enqueue_style('astra-child-style', get_stylesheet_uri(), array('parent-style'), wp_get_theme()->get('Version'));
}
add_action('wp_enqueue_scripts', 'child_enqueue_parent_styles');

// Enqueue Custom Scripts and Localize Data
function smc_enqueue_scripts() {
    // Enqueue main script (if you have one, otherwise remove or enqueue specific scripts per page)
    // Ensure 'script.js' exists or change the filename/path if needed.
    wp_enqueue_script('smc-main-script', get_stylesheet_directory_uri() . '/script.js', array('jquery'), '1.1.4', true); // Incremented version


    // Get reward settings for fee calculation (ensure helper function is available)
    $reward_settings_func = function_exists('smc_get_default_reward_settings') ? 'smc_get_default_reward_settings' : 'smc_get_default_reward_settings_local_fallback'; // Use local fallback if helper not loaded yet
    $reward_settings = get_option(SMC_REWARD_SETTINGS_OPTION, $reward_settings_func());

    // Prepare fee settings for JS, ensuring defaults if not set
    $default_fee_structure = ['percentage' => 0.01, 'fixed' => 30]; // Default fee

    $deposit_fee_config = $reward_settings['deposit_withdrawal_fee'] ?? null;
    $deposit_fee_settings_for_js = $default_fee_structure; // Start with default
    if ($deposit_fee_config && $deposit_fee_config['type'] === 'percentage_plus_fixed' && isset($deposit_fee_config['value']['percentage'], $deposit_fee_config['value']['fixed'])) {
        $deposit_fee_settings_for_js = [
            'percentage' => (float) $deposit_fee_config['value']['percentage'],
            'fixed' => (float) $deposit_fee_config['value']['fixed'],
        ];
    }

    $profit_fee_config = $reward_settings['profit_withdrawal_fee'] ?? null;
    $profit_fee_settings_for_js = $default_fee_structure; // Start with default
    if ($profit_fee_config && $profit_fee_config['type'] === 'percentage_plus_fixed' && isset($profit_fee_config['value']['percentage'], $profit_fee_config['value']['fixed'])) {
        $profit_fee_settings_for_js = [
            'percentage' => (float) $profit_fee_config['value']['percentage'],
            'fixed' => (float) $profit_fee_config['value']['fixed'],
        ];
    }

    // التحقق من مسار الصورة الافتراضية
    $default_image_path = get_stylesheet_directory() . '/images/default-ad.png';
    $default_image_url = file_exists($default_image_path) ? get_stylesheet_directory_uri() . '/images/default-ad.png' : null;
    if (!$default_image_url) {
        error_log("SMC Enqueue Warning: Default ad image not found at: " . $default_image_path);
    }


    // Localize script data - Pass ALL necessary data to JavaScript here
    // Use the correct script handle 'smc-main-script'
    $smc_localized_data = array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('smc_nonce'), // General nonce for ad deals
        'is_user_logged_in' => is_user_logged_in(),
        'attendance_nonce' => wp_create_nonce('smc_attendance_nonce'),
        'fetch_ad_nonce'                => wp_create_nonce( 'smc_fetch_ad_nonce' ),
        'start_watch_nonce'             => wp_create_nonce( 'smc_start_watch_nonce' ),
        'complete_watch_nonce'          => wp_create_nonce( 'smc_complete_watch_nonce' ),
        'deposit_action_nonce' => wp_create_nonce('smc_deposit_action_nonce'), // For admin deposit approval
        'withdraw_deposit_nonce' => wp_create_nonce('smc_withdraw_deposit_action'), // For user submitting deposit withdrawal
        'withdraw_profit_nonce' => wp_create_nonce('smc_withdraw_profit_action'), // For user submitting profit withdrawal
        'withdraw_deposit_approval_nonce' => current_user_can('manage_options') ? wp_create_nonce('smc_withdraw_deposit_approval_action') : null, // For admin approving/rejecting deposit withdrawal
        'withdraw_profit_approval_nonce' => current_user_can('manage_options') ? wp_create_nonce('smc_withdraw_profit_approval_action') : null, // For admin approving/rejecting profit withdrawal
        'cancel_withdrawal_nonce' => wp_create_nonce('smc_cancel_withdrawal_nonce'), // Nonce for user cancelling withdrawal
        'scheduled_withdrawal_nonce' => wp_create_nonce('smc_scheduled_withdrawal_nonce'), // For user requesting scheduled investment withdrawal
        'cancel_scheduled_withdrawal_nonce' => wp_create_nonce('smc_cancel_scheduled_withdrawal_nonce'), // For user cancelling scheduled investment withdrawal
        'admin_actions_nonce' => current_user_can('manage_options') ? wp_create_nonce('smc_admin_actions_nonce') : null, // Nonce for general admin AJAX actions
        'user_deposit_nonce' => wp_create_nonce(SMC_USER_DEPOSIT_NONCE), // Nonce for user deposit submission
        'admin_cancel_old_investments_nonce' => current_user_can('manage_options') ? wp_create_nonce('smc_admin_cancel_old_investments_nonce') : null, // Nonce for cancelling old investments
        'run_cron_nonce' => current_user_can('manage_options') ? wp_create_nonce('smc_run_cron_job_nonce') : null, // Nonce for admin running cron job
        // 'imageList' => $ad_images_urls, // Removed - Image selection is server-side now
        'childThemeUri' => get_stylesheet_directory_uri(), // Pass child theme URI for default images etc.
        'homeUrl' => esc_url(home_url('/smc-daily-tasks/')), // Pass the correct home/redirect URL
        'default_image_url'             => $default_image_url, // Pass the verified or null URL
        // Pass fee settings
        'deposit_withdrawal_fee' => $deposit_fee_settings_for_js,
        'profit_withdrawal_fee' => $profit_fee_settings_for_js,
        'all_investment_configs' => get_option('smc_investment_types_settings', []), // إضافة إعدادات الاستثمار
        // Add any other data needed by JS
    );  
    // error_log("SMC Enqueue Debug: Localizing smc_data on page ID " . get_the_ID() . " - Data: " . print_r($smc_localized_data, true)); // Commented out for less verbose logging
    wp_localize_script('smc-main-script', 'smc_data', $smc_localized_data );


    // Enqueue invitation code script on register page (if applicable)
    // if (is_page('register')) { // Adjust page slug/ID if necessary
    //     wp_enqueue_script('smc-invitation-code', get_stylesheet_directory_uri() . '/invitation-code.js', array('jquery'), '1.0', true);
    // }

    // --- DataTables Loading Logic ---
    $load_datatables = false;

    if (is_admin()) { // Admin area
        $screen = get_current_screen();
        $admin_dt_pages = [ // Screen IDs for admin pages that need DataTables
            'toplevel_page_smc-settings', // Main settings page
            'smc-settings_page_smc-reward-settings', // Reward settings sub-page
            'smc-settings_page_ad-deal-settings', // Ad deal settings sub-page
            'smc-settings_page_smc-investment-types-settings', // Investment Types settings sub-page
            'smc-settings_page_smc-cron-jobs-status', // Cron Jobs status page
            // Add screen IDs for admin log pages if they are menu items
            // Example: 'toplevel_page_users-deposit-log',
        ];
        if ($screen && in_array($screen->id, $admin_dt_pages)) {
             $load_datatables = true;
        }
    } else { // Frontend area
        // User-facing log templates
        $user_log_templates = [
            'user-deposit-log.php',
            'user-deposit-withdrawal-log.php',
            'user-profit-withdrawal-log.php',
            'user-rewards-log.php',
            'user-advertising-deals-record.php',
            'my-team-referrals-log.php',
            'user-referral-tree.php', // User upline view
            'user-attendance-log.php',
            'activate-daily-attendance.php', // Calendar view might use it
            'user-investment-profits-log.php', // إضافة سجل أرباح الاستثمار للمستخدم
            // Add other user log template filenames
        ];
        $user_log_templates = array_unique($user_log_templates);

        foreach ($user_log_templates as $template) {
            if (!empty($template) && is_page_template($template)) {
                $load_datatables = true;
                // error_log("SMC Enqueue: Loading DataTables for user template: " . $template);
                break;
            }
        }        
        // Admin-viewed frontend log templates (only if not already loaded for general user)
        if (!$load_datatables && current_user_can('manage_options')) {
            $admin_frontend_log_templates = [
                 'users-deposit-log.php',
                 'users-deposit-withdrawal-log.php',
                 'users-profit-withdrawal-log.php',
                 'users-rewards-log.php',
                 'users-advertising-deals-record.php',                 'record-total-users-earnings-types.php',
                 'users-referral-log.php',
                 'users-referral-tree.php',
                 'users-attendance-log.php',
                 'users-deposit-status.php', // Added for User Deposit Status (Admin)
                 'users-ranks.php',          // Added for User Ranks Tally (Admin)
                 'invitation-link.php', // Added for Invitation Link page with referrals table        
                 'members-login-log.php',
                 'displaying-ads-log.php',
                 'number-clicks-log.php',
                 'admin-investment-profits-log.php', // إضافة سجل أرباح الاستثمار للمسؤول
                 'proof-payment-record.php',
            ];
            foreach ($admin_frontend_log_templates as $template) {
                if (is_page_template($template)) {
                    $load_datatables = true;                    
                    // error_log("SMC Enqueue: Loading DataTables for ADMIN frontend template: " . $template . " on page ID " . get_the_ID());
                    break;
                }
            }
        }
    }

    // Enqueue DataTables assets if needed for the current page
    // error_log("SMC Enqueue: Final check - \$load_datatables is " . ($load_datatables ? 'true' : 'false') . " for page ID " . get_the_ID() . (is_admin() ? " (Admin screen: ".(get_current_screen()->id ?? 'N/A').")" : " (Frontend)")); // Commented out for less verbose logging
     if ($load_datatables) {
        wp_enqueue_style('datatables-css', 'https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css', array(), '1.13.6');
        wp_enqueue_style('datatables-buttons-css', 'https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css', array('datatables-css'), '2.4.1');
        wp_enqueue_script('datatables-js', 'https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js', array('jquery'), '1.13.6', true);
        wp_enqueue_script('datatables-buttons-js', 'https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js', array('datatables-js'), '2.4.1', true);
        wp_enqueue_script('jszip', 'https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js', array('datatables-buttons-js'), '3.10.1', true);
        // pdfmake is relatively large, consider if PDF export is essential
        wp_enqueue_script('pdfmake', 'https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js', array('datatables-buttons-js'), '0.1.53', true);
        wp_enqueue_script('vfs-fonts', 'https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js', array('pdfmake'), '0.1.53', true);
        wp_enqueue_script('datatables-buttons-html5', 'https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js', array('datatables-buttons-js', 'jszip', 'pdfmake'), '2.4.1', true);
        wp_enqueue_script('datatables-buttons-print', 'https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js', array('datatables-buttons-js'), '2.4.1', true);
    }

    // Enqueue SweetAlert2 for specific admin pages that use it
    if (is_admin()) {
        $screen = get_current_screen();
        if ($screen && in_array($screen->id, ['smc-settings_page_smc-investment-types-settings', 'smc-settings_page_smc-cron-jobs-status'])) {
            wp_enqueue_script('sweetalert2', 'https://cdn.jsdelivr.net/npm/sweetalert2@11', array(), '11', true);
        }
    }
}
// Add the action for both frontend and admin area
add_action('wp_enqueue_scripts', 'smc_enqueue_scripts');
add_action('admin_enqueue_scripts', 'smc_enqueue_scripts');


// Fallback function in case helpers.php isn't loaded when enqueue runs
if (!function_exists('smc_get_default_reward_settings_local_fallback')) {
    function smc_get_default_reward_settings_local_fallback() {
        // Paste the content of smc_get_default_reward_settings() from helpers.php here
        return [
            'referral_deposit_l1' => ['type' => 'percentage', 'value' => 0.03],
            'referral_deposit_l2' => ['type' => 'percentage', 'value' => 0.02],
            'referral_deposit_l3' => ['type' => 'percentage', 'value' => 0.01],
            'daily_task_l1' => ['type' => 'percentage', 'value' => 0.03],
            'daily_task_l2' => ['type' => 'percentage', 'value' => 0.02],
            'daily_task_l3' => ['type' => 'percentage', 'value' => 0.01],
            'investment_l1' => ['type' => 'percentage', 'value' => 0.03],
            'investment_l2' => ['type' => 'percentage', 'value' => 0.02],
            'investment_l3' => ['type' => 'percentage', 'value' => 0.01],
            'rank_vip1' => ['type' => 'fixed_monthly', 'value' => 3000],
            'rank_vip2' => ['type' => 'fixed_monthly', 'value' => 7000],
            'rank_vip3' => ['type' => 'fixed_monthly', 'value' => 20000],
            'rank_vip4' => ['type' => 'fixed_monthly', 'value' => 45000],
            'rank_vip5' => ['type' => 'fixed_monthly', 'value' => 100000],
            'agent_district' => ['type' => 'fixed_monthly', 'value' => 30000],
            'agent_city' => ['type' => 'fixed_monthly', 'value' => 100000],
            'deposit_withdrawal_fee' => ['type' => 'percentage_plus_fixed', 'value' => ['percentage' => 0.01, 'fixed' => 30]],
            'profit_withdrawal_fee' => ['type' => 'percentage_plus_fixed', 'value' => ['percentage' => 0.01, 'fixed' => 30]],
            'signup_bonus' => ['type' => 'fixed', 'value' => 0],
            'referrer_signup_bonus' => ['type' => 'fixed', 'value' => 0],
            'daily_attendance' => ['type' => 'fixed', 'value' => 10], // Added daily attendance points
        ];
    }
}

?>

