<?php


 // inc/1-constants.php
 
 if (!defined('ABSPATH')) exit;
 
 // User Meta Keys
  if (!defined('SMC_DEPOSIT_BALANCE')) {
    define('SMC_DEPOSIT_BALANCE', 'smc_deposit_balance');
}
if (!defined('SMC_PROFIT_BALANCE')) {
    define('SMC_PROFIT_BALANCE', 'smc_profit_balance');
}
if (!defined('SMC_LAST_DEPOSIT_DATE')) {
    define('SMC_LAST_DEPOSIT_DATE', 'smc_last_deposit_date');
}
if (!defined('SMC_LAST_DEPOSIT_TIMESTAMP')) {
    define('SMC_LAST_DEPOSIT_TIMESTAMP', 'smc_last_deposit_timestamp');
}
if (!defined('SMC_DEPOSIT_END_DATE')) {
    define('SMC_DEPOSIT_END_DATE', 'smc_deposit_end_date');
}
if (!defined('SMC_ADS_WATCHED_TODAY')) {
    define('SMC_ADS_WATCHED_TODAY', 'smc_ads_watched_today');
}
if (!defined('SMC_LAST_AD_WATCH_DATE')) {
    define('SMC_LAST_AD_WATCH_DATE', 'smc_last_ad_watch_date');
}
if (!defined('SMC_DAILY_AD_LIMIT')) {
    define('SMC_DAILY_AD_LIMIT', 'smc_daily_ad_limit');
}
if (!defined('SMC_REFERRAL_CODE')) {
    define('SMC_REFERRAL_CODE', 'smc_referral_code');
}
if (!defined('SMC_REFERRED_BY')) {
    define('SMC_REFERRED_BY', 'smc_referred_by');
}
if (!defined('SMC_POINTS_BALANCE')) {
    define('SMC_POINTS_BALANCE', 'smc_points_balance');
}
if (!defined('SMC_DAILY_EARNINGS_TOTAL')) {
    define('SMC_DAILY_EARNINGS_TOTAL', 'smc_daily_earnings_total');
}
if (!defined('SMC_LAST_EARNING_RESET_DATE')) {
    define('SMC_LAST_EARNING_RESET_DATE', 'smc_last_earning_reset_date');
}
 // Add other constants like click tracking meta keys if needed
if (!defined('SMC_TOTAL_CLICKS')) {
    define('SMC_TOTAL_CLICKS', 'smc_total_clicks');
}
if (!defined('SMC_DAILY_CLICKS')) {
    define('SMC_DAILY_CLICKS', 'smc_daily_clicks');
}
if (!defined('SMC_LAST_CLICK_DATE')) {
    define('SMC_LAST_CLICK_DATE', 'smc_last_click_date');
}

 // Option Names
if (!defined('SMC_REWARD_SETTINGS_OPTION')) {
    define('SMC_REWARD_SETTINGS_OPTION', 'smc_reward_settings');
}
if (!defined('SMC_AD_SETTINGS_OPTION')) {
    define('SMC_AD_SETTINGS_OPTION', 'smc_ad_deal_settings');
}
 
 // Add any other project-wide constants here
if (!defined('SMC_USER_DEPOSIT_NONCE')) {
    define('SMC_USER_DEPOSIT_NONCE', 'smc_user_deposit_action_nonce'); // Nonce for user deposit submission
}
if (!defined('SMC_DB_VERSION')) {
    define('SMC_DB_VERSION', '2.1.8'); // ابدأ بـ 1.0 أو 1.1، وزده عند تغيير هيكل أي جدول - Removed closing parenthesis
}
?>
 
 