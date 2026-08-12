<?php
/**
 * Template Name: User Ranks Tally (Admin)
 * Description: Displays a list of users who qualify for ranks based on active referrals, for administrators.
 */

if (!defined('ABSPATH')) exit; // Exit if accessed directly

// Redirect if not logged in or not an admin
if (!is_user_logged_in() || !current_user_can('administrator')) {
    wp_redirect(home_url('/'));
    exit;
}

get_header();
global $wpdb;
// Ensure smc_get_default_reward_settings is available
$default_settings_func_ranks = function_exists('smc_get_default_reward_settings') ? 'smc_get_default_reward_settings' : (function_exists('smc_get_default_reward_settings_local_fallback') ? 'smc_get_default_reward_settings_local_fallback' : null);
$reward_settings = $default_settings_func_ranks ? get_option(SMC_REWARD_SETTINGS_OPTION, $default_settings_func_ranks()) : [];

?>

<div class="container smc-log-container">
    <h2><i class="fas fa-medal"></i> حصيلة رتب المستخدمين (للمسؤول)</h2>
    <a href="<?php echo esc_url(home_url('/smc-settings/')); ?>" class="smc-button smc-button-secondary" style="margin-bottom: 15px; display: inline-block;"><i class="fas fa-cog"></i> العودة إلى إعدادات SMC</a>

    <!-- Log Controls -->
    <div class="smc-log-controls" style="margin-bottom: 20px; padding: 15px; background-color: #f9f9f9; border: 1px solid #eee; border-radius: 5px;">
        <p><strong>أدوات التحكم بالسجل:</strong></p>
        <p>يمكنك استخدام حقل "بحث" أدناه للبحث عن طريق اسم المستخدم، معرف المستخدم، أو الرتبة. يمكنك أيضًا فرز الأعمدة وتصدير البيانات باستخدام الأزرار.</p>
        <p>يتم عرض المستخدمين الذين لديهم <?php
            // Dynamically get the minimum referrals for VIP1 for the listing message
            $vip1_min_referrals_for_message = 7; // Default
            if (isset($reward_settings['rank_vip1']['required_referrals_min'])) {
                $vip1_min_referrals_for_message = (int) $reward_settings['rank_vip1']['required_referrals_min'];
            } elseif (isset($reward_settings['rank_vip1']['required_referrals'])) { // Fallback to old key
                $vip1_min_referrals_for_message = (int) $reward_settings['rank_vip1']['required_referrals'];
            }
            // The page itself lists users with AT LEAST 7 active referrals, this message can be more generic or reflect VIP1 min
            echo '7'; // Keeping it at 7 for now as per previous logic for listing
            ?> أعضاء نشطين مباشرين على الأقل في فريقهم.</p>
    </div>

    <table id="admin-user-ranks-table" class="display compact stripe hover smc-log-table" style="width:100%">
        <thead>
            <tr>
                <th>اسم المستخدم</th>
                <th>معرف المستخدم</th>
                <th>الرتبة</th>
                <th>الراتب الشهري المحتمل (دج)</th>
                <th>عدد الأعضاء النشطين المباشرين</th>
                <th>إجمالي أعضاء الفريق (حتى 3 مستويات)</th>
                <th>تاريخ آخر دفع المكافئة الشهرية</th> <?php // Changed header ?>
                <th>عرض الملف الشخصي</th>
                <th>معرف الداعي</th> <?php // Added Referrer ID ?>
            </tr>
        </thead>
        <tbody>
            <?php
            $all_users = get_users(['fields' => 'all_with_meta']);
            $min_active_referrals_for_listing = 7; // Minimum active referrals to be listed on this page

            // Get the minimum required active referrals for VIP1 from settings
            $vip1_settings = $reward_settings['rank_vip1'] ?? null;
            if (isset($vip1_settings['required_referrals_min'])) {
                $min_active_referrals_for_listing = (int) $vip1_settings['required_referrals_min'];
            } elseif (isset($vip1_settings['required_referrals'])) { // Fallback to old key
                 $min_active_referrals_for_listing = (int) $vip1_settings['required_referrals'];
            }

            if ($all_users) {
                foreach ($all_users as $user) {
                    $user_id = $user->ID;
                    $active_direct_referrals = function_exists('smc_count_active_direct_referrals') ? smc_count_active_direct_referrals($user_id) : 0;

                    // Filter users based on the minimum requirement for listing
                    if ($active_direct_referrals < $min_active_referrals_for_listing) {
                        continue; // Skip user if they don't have enough active referrals for listing
                    }

                    $current_rank = function_exists('smc_get_user_rank') ? smc_get_user_rank($user_id) : 'VIP0';
                    
                    $monthly_salary = 0;
                    $rank_setting_key_raw = strtolower($current_rank); 

                    $rank_setting_key_for_lookup = '';
                    if (strpos($rank_setting_key_raw, 'vip') === 0) {
                        $rank_setting_key_for_lookup = 'rank_' . $rank_setting_key_raw; 
                    } elseif (strpos($rank_setting_key_raw, 'agent_') === 0) {
                        $rank_setting_key_for_lookup = $rank_setting_key_raw; 
                    }

                    if (!empty($rank_setting_key_for_lookup) && 
                        isset($reward_settings[$rank_setting_key_for_lookup]) && 
                        isset($reward_settings[$rank_setting_key_for_lookup]['type']) &&
                        $reward_settings[$rank_setting_key_for_lookup]['type'] === 'fixed_monthly' &&
                        isset($reward_settings[$rank_setting_key_for_lookup]['value'])) {
                        // 'value' directly holds the salary for rank types
                        $monthly_salary = (float) $reward_settings[$rank_setting_key_for_lookup]['value'];
                    }


                    // *** Changed: Get last monthly salary payment date instead of last deposit date ***
                    $last_salary_timestamp = (int) get_user_meta($user_id, 'smc_last_monthly_salary_date', true); // Assuming this meta key stores timestamp
                    $last_salary_date_str = $last_salary_timestamp ? date_i18n('Y-m-d H:i', $last_salary_timestamp) : 'N/A';

                    $total_team_members = 0;
                    if (function_exists('smc_get_referral_downline_recursive') && function_exists('smc_count_downline_members_recursive')) {
                        $downline_data = smc_get_referral_downline_recursive($user_id, 3);
                        $total_team_members = smc_count_downline_members_recursive($downline_data);
                    }
                    
                    $profile_link = esc_url(home_url('/user/' . $user->user_login . '/'));
                    
                    // Get referrer ID
                    $referrer_id = get_user_meta($user_id, SMC_REFERRED_BY, true);
                    $referrer_username = 'N/A';
                    if ($referrer_id) {
                        $referrer_info = get_userdata($referrer_id);
                        $referrer_username = $referrer_info ? $referrer_info->user_login : 'ID: ' . $referrer_id;
                    }

                    echo '<tr>';
                    echo '<td>' . esc_html($user->user_login) . '</td>';
                    echo '<td>' . esc_html($user_id) . '</td>';
                    echo '<td>' . esc_html($current_rank) . '</td>';
                    echo '<td><span dir="ltr">' . esc_html(number_format($monthly_salary, 2, '.', '')) . ' دج</span></td>';
                    echo '<td>' . esc_html($active_direct_referrals) . '</td>'; // Keep this column for active direct referrals
                    echo '<td>' . esc_html($total_team_members) . '</td>';
                    echo '<td>' . esc_html($last_salary_date_str) . '</td>'; // Display last salary date
                    echo '<td><a href="' . $profile_link . '" class="smc-button smc-button-view" target="_blank"><i class="fas fa-eye"></i> عرض</a></td>';
                    echo '<td>' . esc_html($referrer_id ?: 'N/A') . '</td>'; // Display Referrer ID
                    echo '</tr>';
                }
            } else {
                echo '<tr><td colspan="8">لا يوجد مستخدمون لعرضهم.</td></tr>';
            }
            ?>
        </tbody>
    </table>
</div>

<?php get_footer(); ?>

<script type="text/javascript">
jQuery(document).ready(function($) {
    if ($.fn.DataTable) {
        try {
            $('#admin-user-ranks-table').DataTable({
                responsive: true,
                dom: 'Bfrtip',
                buttons: ['copy', 'csv', 'excel', 'pdf', 'print'],
                order: [[ 4, "desc" ]], // Default sort by active direct referrals
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/ar.json',
                    search: "بحث:"
                },
                columnDefs: [
                    { targets: [7], orderable: false, searchable: false } // View profile button (Index 7)
                 ]
            });
        } catch (e) {
            console.error("Error initializing DataTables for user ranks:", e);
            $('.smc-log-container').prepend('<p class="smc-error-message">حدث خطأ أثناء تحميل جدول السجلات التفاعلي.</p>');
        }
    } else {
        console.warn("DataTables library not found for user ranks.");
        $('.smc-log-container').prepend('<p class="smc-error-message">لم يتم تحميل مكتبة الجداول التفاعلية (DataTables).</p>');
    }
});
</script>

<style>
/* Styles are similar to users-deposit-status.php, ensure consistency */
.smc-log-container { max-width: 1200px; margin: 20px auto; }
.smc-log-table { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 0.9em; }
.smc-log-table th, .smc-log-table td { border: 1px solid #ddd; padding: 6px 8px; text-align: right; vertical-align: middle; }
.smc-log-table th { background-color: #f2f2f2; font-weight: bold; }
.smc-log-table tbody tr:nth-child(even) { background-color: #f9f9f9; }
.smc-log-table td span[dir="ltr"] { display: inline-block; }

.smc-button-secondary, .smc-button-view { /* Shared styles */
    text-decoration: none; border-radius: 4px; display: inline-flex; align-items: center;
    font-size: 0.9em; padding: 5px 10px; color: white !important;
}
.smc-button-secondary { background-color: #6c757d; border-color: #6c757d; }
.smc-button-secondary:hover { background-color: #5a6268; border-color: #545b62; }
.smc-button-secondary i { margin-left: 5px; }

.smc-button-view { background-color: #007bff; border-color: #007bff; padding: 4px 8px; font-size: 0.85em;}
.smc-button-view:hover { background-color: #0056b3; border-color: #0056b3;}
.smc-button-view i { margin-left: 3px; }

.smc-error-message { color: #dc3545; background-color: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; border-radius: 5px; margin-bottom: 15px; }

.dt-buttons .dt-button {
    background-color: #007bff !important; color: white !important; border: 1px solid #007bff !important;
    border-radius: 4px !important; padding: 5px 10px !important; margin: 0 2px 5px 2px !important;
    transition: background-color 0.3s ease !important; font-size: 0.9em !important;
}
.dt-buttons .dt-button:hover { background-color: #0056b3 !important; border-color: #0056b3 !important; }
.dataTables_filter label { font-weight: bold; font-size: 0.95em; }
.dataTables_filter input { margin-left: 5px; border: 1px solid #ccc; border-radius: 4px; padding: 5px; font-size: 0.95em; }

@import url("https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css");
</style>
