<?php
/**
 * Template part for displaying a single referral log row.
 *
 * Expected variables:
 * @var WP_User|WP_Error|false|null $referrer      The referrer user object (can be error/false/null if not found/deleted).
 * @var WP_User|null                $referred_user The referred user object.
 * @var array|null                  $extended_data An array containing extended data for the referred user (can be null).
 * @var bool                        $is_admin_view Whether this is being displayed in the admin log view.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Ensure $args is available (passed by get_template_part)
$referrer = isset( $args['referrer'] ) ? $args['referrer'] : null;
$referred_user = isset( $args['referred_user'] ) ? $args['referred_user'] : null;
$extended_data = isset( $args['extended_data'] ) ? $args['extended_data'] : null;
$is_admin_view = isset( $args['is_admin_view'] ) ? $args['is_admin_view'] : false;

// Exit if referred_user is not a valid WP_User object, as it's essential
if ( ! $referred_user instanceof WP_User ) {
    // Optionally log an error or return an empty row to avoid breaking the table structure
    // error_log('Missing or invalid referred_user object in referral-log-row.php');
    $column_count = $is_admin_view ? 13 : 10; // Adjust based on view
    echo '<tr><td colspan="' . $column_count . '">خطأ: بيانات المستخدم المدعو غير متوفرة.</td></tr>';
    return;
}


// --- Prepare Referrer Data ---
$referrer_full_name       = 'N/A';
$referrer_user_login      = 'N/A';
$referrer_referral_code   = 'N/A';

// Check if $referrer is a valid WP_User object before accessing its properties
if ( $referrer instanceof WP_User ) {
    $referrer_first_name = get_user_meta( $referrer->ID, 'first_name', true );
    $referrer_last_name  = get_user_meta( $referrer->ID, 'last_name', true );
    $referrer_full_name  = trim( $referrer_first_name . ' ' . $referrer_last_name );
    if ( empty( $referrer_full_name ) ) {
        $referrer_full_name = $referrer->display_name ?: $referrer->user_login; // Fallback
    }
    $referrer_user_login = $referrer->user_login;
    // Use defined constant if available, otherwise fallback to 'invitation_id' meta key
    $ref_code_meta_key = defined('SMC_REFERRAL_CODE') ? SMC_REFERRAL_CODE : 'invitation_id';
    $referrer_referral_code = get_user_meta( $referrer->ID, $ref_code_meta_key, true ) ?: 'N/A';
} elseif ( is_wp_error( $referrer ) ) {
    $referrer_full_name = 'خطأ في جلب الداعي';
    $referrer_user_login = 'خطأ';
    $referrer_referral_code = 'خطأ';
} elseif ( $referrer === false ) {
	$referrer_full_name = 'المستخدم محذوف';
	$referrer_user_login = 'محذوف';
	$referrer_referral_code = 'N/A';
} // else remains 'N/A' if $referrer is null

// --- Prepare Referred User Data ---
$referred_first_name = get_user_meta( $referred_user->ID, 'first_name', true );
$referred_last_name  = get_user_meta( $referred_user->ID, 'last_name', true );
$referred_full_name  = trim( $referred_first_name . ' ' . $referred_last_name );
if ( empty( $referred_full_name ) ) {
    $referred_full_name = $referred_user->display_name ?: $referred_user->user_login; // Fallback
}
// Use defined constant if available, otherwise fallback to 'invitation_id' meta key
$ref_code_meta_key_referred = defined('SMC_REFERRAL_CODE') ? SMC_REFERRAL_CODE : 'invitation_id';
$referred_user_referral_code = get_user_meta( $referred_user->ID, $ref_code_meta_key_referred, true ) ?: 'N/A';

// --- Prepare Extended Data (with defaults) ---
// Use null coalescing operator for cleaner defaults and check if $extended_data is an array
$mobile_number   = ( is_array($extended_data) && ! empty( $extended_data['mobile_number'] ) ) ? $extended_data['mobile_number'] : ' ';
$gender          = ( is_array($extended_data) && ! empty( $extended_data['gender'] ) ) ? $extended_data['gender'] : ' ';
$country         = ( is_array($extended_data) && ! empty( $extended_data['country'] ) ) ? $extended_data['country'] : ' ';
$whatsapp_number = $mobile_number; // Assuming WhatsApp is same as mobile for now

// --- Prepare Registration Date ---
$registration_date = 'N/A';
if ( ! empty( $referred_user->user_registered ) ) {
    $timestamp = strtotime( $referred_user->user_registered );
    if ( $timestamp !== false ) {
        $registration_date = date_i18n( 'Y-m-d', $timestamp );
    }
}
?>
<tr>
    <?php if ( $is_admin_view ) : // Show referrer info only in admin view ?>
        <td><?php echo esc_html( $referrer_full_name ); ?></td>
        <td><?php echo esc_html( $referrer_user_login ); ?></td>
        <td><?php echo esc_html( $referrer_referral_code ); ?></td>
    <?php endif; ?>
    <td><?php echo esc_html( $referred_full_name ); ?></td>
    <td><?php echo esc_html( $referred_user->user_login ); ?></td>
    <td><?php echo esc_html( $referred_user_referral_code ); ?></td>
    <td><?php echo esc_html( $registration_date ); ?></td>
    <td><?php echo esc_html( $mobile_number ); ?></td>
    <td><?php echo esc_html( $referred_user->user_email ); ?></td>
    <td><?php echo esc_html( $whatsapp_number ); ?></td>
    <td><?php echo esc_html( $gender ); ?></td>
    <td><?php echo esc_html( $country ); ?></td>
    <?php if ( $is_admin_view ) : // Only show bonus column for admin ?>
        <td>3%</td> <?php // Consider making this dynamic if needed ?>
    <?php endif; ?>
</tr>
