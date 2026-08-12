<?php
require_once( 'wp-load.php' );

global $wpdb;
$meta_key_referred_by = defined('SMC_REFERRED_BY') ? SMC_REFERRED_BY : 'smc_referred_by';

$args = array(
    'meta_key'   => $meta_key_referred_by,
    'meta_compare' => 'EXISTS',
    'count_total' => true,
);

$user_count = count( get_users( $args ) );

echo "عدد المستخدمين المحالين: " . $user_count;
?>
