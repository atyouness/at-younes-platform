<?php
/**
 * Shortcodes
 *
 * @package SMC Group DZ Child
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// --- Deposit Balance Shortcode ---
function smc_deposit_balance_shortcode() {
    if (!is_user_logged_in()) return 'N/A';
    $user_id = get_current_user_id();
    $balance = get_user_meta($user_id, SMC_DEPOSIT_BALANCE, true) ?: 0.0;
    return number_format((float)$balance, 2) . ' دج';
}
add_shortcode('smc_user_deposit_balance', 'smc_deposit_balance_shortcode');

// --- Profit Balance Shortcode ---
function smc_profit_balance_shortcode() {
    if (!is_user_logged_in()) return 'N/A';
    $user_id = get_current_user_id();
    $balance = get_user_meta($user_id, SMC_PROFIT_BALANCE, true) ?: 0.0;
    return number_format((float)$balance, 2) . ' دج';
}
add_shortcode('smc_user_profit_balance', 'smc_profit_balance_shortcode');


// --- Referral Code Shortcode ---
function smc_referral_code_shortcode() {
    if (!is_user_logged_in()) return 'N/A';
    $user_id = get_current_user_id();
    $code = get_user_meta($user_id, SMC_REFERRAL_CODE, true);
    return $code ? esc_html($code) : 'لم يتم إنشاؤه';
}
add_shortcode('smc_user_referral_code', 'smc_referral_code_shortcode');

// --- Referral Link Shortcode ---
function smc_referral_link_shortcode() {
    if (!is_user_logged_in()) return 'N/A';
    $user_id = get_current_user_id();
    $code = get_user_meta($user_id, SMC_REFERRAL_CODE, true);
    if ($code) {
        $link = home_url('/register/?ref=' . $code); // Adjust '/register/' if your registration page URL is different
        return esc_url($link);
    }
    return 'لا يوجد رابط دعوة';
}
add_shortcode('smc_user_referral_link', 'smc_referral_link_shortcode');


// Add more shortcodes here as needed...

?>
