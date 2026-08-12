<?php
/**
 * General Hooks (Registration, Login, etc.)
 *
 * @package SMC Group DZ Child
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// ==============================================
// User Registration Hook
// ==============================================
function smc_handle_user_registration($user_id) {
    global $wpdb;
    $table_name_extended = $wpdb->prefix . 'smc_user_extended_data';
    $referrals_table = $wpdb->prefix . 'smc_referrals';

    // --- Save Extended Data ---
    $extended_data = [];
    $fields_to_save = ['father_name', 'birth_date', 'place_birth', 'gender', 'country', 'mobile_number', 'invitation_id'];
    foreach ($fields_to_save as $field) {
        if (isset($_POST[$field])) {
            $extended_data[$field] = sanitize_text_field($_POST[$field]);
        }
    }
    if (isset($_POST['privacy_policy']) && $_POST['privacy_policy'] == 'accepted') {
        $extended_data['privacy_policy_accepted_at'] = current_time('mysql');
    }
    if (!empty($extended_data)) {
        $existing_data = $wpdb->get_var($wpdb->prepare("SELECT user_id FROM $table_name_extended WHERE user_id = %d", $user_id));
        if ($existing_data) {
            $wpdb->update($table_name_extended, $extended_data, ['user_id' => $user_id]);
        } else {
            $extended_data['user_id'] = $user_id;
            $wpdb->insert($table_name_extended, $extended_data);
        }
    }

    // --- Handle Referral ---
    $referral_code_used = null;
    if (isset($_GET['ref']) && !empty($_GET['ref'])) {
        $referral_code_used = sanitize_text_field($_GET['ref']);
    }
    elseif (isset($extended_data['invitation_id']) && !empty($extended_data['invitation_id'])) {
        $referral_code_used = $extended_data['invitation_id'];
    }

    if ($referral_code_used) {
        $referrer_id = $wpdb->get_var($wpdb->prepare(
            "SELECT user_id FROM $wpdb->usermeta WHERE meta_key = %s AND meta_value = %s",
            SMC_REFERRAL_CODE, $referral_code_used
        ));

        if ($referrer_id && $referrer_id != $user_id) {
            update_user_meta($user_id, SMC_REFERRED_BY, $referrer_id);
            $wpdb->insert(
                $referrals_table,
                array(
                    'referrer_user_id' => $referrer_id, 'invitee_user_id' => $user_id,
                    'invitation_code_used' => $referral_code_used, 'referral_timestamp' => current_time('mysql')
                ), // Missing closing parenthesis was here
                array('%d', '%d', '%s', '%s')
            );
                // Set a transient to show a success message to the new user
                set_transient(
                    'smc_referral_success_user_' . $user_id,
                    sprintf(__('تم تسجيلك بنجاح عن طريق دعوة من المستخدم %s باستخدام الكود %s.', 'smc'), $referrer_id, $referral_code_used),
                    60 // Store for 60 seconds
            );
            error_log("SMC Referral: User $user_id registered using code $referral_code_used from user $referrer_id.");
        } else {
            error_log("SMC Referral Warning: Invalid or self-referral code '$referral_code_used' used by user $user_id.");
        }
    }

    // --- Generate Referral Code for the New User ---
    smc_generate_referral_code($user_id); // Assumes smc_generate_referral_code is loaded

    // --- Initialize User Meta Fields ---
    add_user_meta($user_id, SMC_DEPOSIT_BALANCE, 0.0, true);
    add_user_meta($user_id, SMC_PROFIT_BALANCE, 0.0, true);
    add_user_meta($user_id, SMC_ADS_WATCHED_TODAY, 0, true);
    add_user_meta($user_id, SMC_DAILY_AD_LIMIT, 10, true); // Default limit
    add_user_meta($user_id, SMC_POINTS_BALANCE, 0, true);
    add_user_meta($user_id, SMC_DAILY_EARNINGS_TOTAL, 0.0, true);
    $today_date = current_time('Y-m-d');
    add_user_meta($user_id, SMC_LAST_AD_WATCH_DATE, $today_date, true);
    add_user_meta($user_id, SMC_LAST_EARNING_RESET_DATE, $today_date, true);
}
add_action('user_register', 'smc_handle_user_registration');

// ==============================================
// Ultimate Member Integration (Example)
// ==============================================
function smc_add_um_register_fields($args) {
    $args['fields']['invitation_id'] = array(
        'title' => __('معرف الدعوة (اختياري)', 'ultimate-member'), 'label' => __('معرف الدعوة (إذا كان لديك)', 'ultimate-member'),
        'type' => 'text', 'required' => 0, 'public' => 0, 'editable' => 1, 'icon' => 'um-faicon-user-plus',
        'validate' => 'alpha_numeric', 'max_length' => 10,
    );
     $args['fields']['privacy_policy'] = array(
         'title' => __('سياسة الخصوصية', 'ultimate-member'), 'label' => __('أوافق على <a href="/privacy-policy/" target="_blank">سياسة الخصوصية</a> وشروط الاستخدام.', 'ultimate-member'),
         'type' => 'checkbox', 'required' => 1, 'public' => 0, 'editable' => 0, 'icon' => 'um-faicon-check-square-o',
         'options' => array('accepted' => __('نعم، أوافق', 'ultimate-member')),
     );
    return $args;
}
// add_filter('um_register_form_fields_hook', 'smc_add_um_register_fields'); // Hook might be incorrect, check UM documentation

/**
 * Validate the invitation ID during Ultimate Member registration.
 * This function hooks into UM's validation process.
 */
function smc_validate_um_invitation_code( $args ) {
    // Ensure this function only runs during the 'register' form submission
    if ( ! isset( $args['mode'] ) || $args['mode'] !== 'register' ) {
        return;
    }

    global $wpdb;
    if ( ! class_exists( 'UM' ) || ! isset( UM()->form()->errors ) ) {
        return; // UM not active or form object not ready
    }

    // Check if the constant SMC_REFERRAL_CODE is defined
    if (!defined('SMC_REFERRAL_CODE')) {
        error_log("SMC Validate Hook: Constant SMC_REFERRAL_CODE is NOT defined. Cannot validate invitation code.");
        // Optionally, add a generic error to stop registration if this critical constant is missing
        // UM()->form()->add_error( 'invitation_id', __( 'خطأ في نظام الدعوة. يرجى الاتصال بالدعم.', 'smc' ) );
        return;
    } // No need to log the constant value every time


    if ( isset( $_POST['invitation_id'] ) ) {
        $submitted_code = sanitize_text_field( trim( $_POST['invitation_id'] ) );

        error_log("SMC Validate Hook: Submitted invitation_id: '" . $submitted_code . "'");

        if ( ! empty( $submitted_code ) ) {
            $meta_key_to_check = SMC_REFERRAL_CODE; // Use the constant

            error_log("SMC Validate Hook: Querying for meta_key '{$meta_key_to_check}' with meta_value '{$submitted_code}'");

            $referrer_id = $wpdb->get_var( $wpdb->prepare(
                "SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = %s AND meta_value = %s",
                $meta_key_to_check,
                $submitted_code
            ) );

            error_log("SMC Validate Hook: Referrer ID found from DB: " . ($referrer_id ? $referrer_id : 'NONE'));

            if ( ! $referrer_id ) {
                error_log("SMC Validate Hook: Referrer ID not found for code '{$submitted_code}'. Adding UM error.");
                UM()->form()->add_error( 'invitation_id', __( 'معرف الدعوة الذي أدخلته غير صالح أو غير موجود.', 'smc' ) );
            } else {
                error_log("SMC Validate Hook: Valid referral code '{$submitted_code}' found for referrer ID: {$referrer_id}. No error added.");
            }
        } else {
            error_log("SMC Validate Hook: Submitted invitation_id was empty after trim. No validation performed for empty code.");
            // If an empty code is submitted, and the field is optional, this is fine.
            // If the field should be mandatory if filled, or always mandatory, different logic is needed.
            // The current setup implies the field is optional. If filled, it must be valid.
        }
    } else {
        error_log("SMC Validate Hook: 'invitation_id' not set in _POST array.");
    }
}
// Hook into Ultimate Member's registration form validation.
// 'register' is the mode for the registration form.
add_action( 'um_submit_form_errors_hook_register', 'smc_validate_um_invitation_code', 10, 1 );


// ==============================================
// Login/Registration Redirects
// ==============================================
function smc_custom_login_redirect($redirect_to, $request, $user) {
    if (isset($user->roles) && is_array($user->roles)) {
        if (in_array('administrator', $user->roles)) {
            return admin_url(); // Admin goes to dashboard
        } else {
            return home_url('/smc-daily-tasks/'); // Others go to daily tasks
        }
    }
    return $redirect_to; // Default redirect if roles not set
}
add_filter('login_redirect', 'smc_custom_login_redirect', 10, 3);

function smc_custom_registration_redirect( $user_id ) {
    // Redirect new users to the homepage or a welcome page
    wp_redirect( home_url( '/' ) );
    exit;
}
add_action( 'user_register', 'smc_custom_registration_redirect', 99 ); // High priority to run after other registration actions

/**
 * Display referral success message after registration if set.
 */
function smc_display_referral_success_message() {
    // Only proceed if a user is logged in and not in admin area
    if (is_user_logged_in() && !is_admin()) {
        $user_id = get_current_user_id();
        $transient_key = 'smc_referral_success_user_' . $user_id;
        $message = get_transient($transient_key);

        if ($message) {
            // Ensure this doesn't output on AJAX requests or specific pages where it's not desired
            // A more robust check might involve checking the current page template or slug
            // For now, we'll display it broadly on the frontend.
            echo '<div class="smc-referral-success-message" style="background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; padding: 15px; margin: 20px auto; border-radius: 5px; text-align: center; max-width: 800px;">' . esc_html($message) . '</div>';
            delete_transient($transient_key); // Delete after displaying
        }
    }
}
// Using 'wp_footer' is broad. If you know the specific page users are redirected to,
// it's better to hook into an action on that page's template or a general content hook.
add_action('wp_footer', 'smc_display_referral_success_message', 20); // Added priority

?>
