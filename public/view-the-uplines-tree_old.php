<?php
/**
 * Template Name: View The Uplines Tree
 * Description: Displays the referral upline (referrers) for the current user in a table.
 */

if (!defined('ABSPATH')) exit; // Exit if accessed directly

// Redirect if not logged in
if (!is_user_logged_in()) {
    wp_redirect(home_url('/')); // Or login page
    exit;
}

get_header();
$user_id = get_current_user_id();
?>

<div class="container referral-upline-container">
    <h2><i class="fas fa-sitemap fa-rotate-180"></i> شجرة الداعين (الأعلى)</h2>
    <a href="<?php echo esc_url( home_url( '/transactional/' ) ); ?>" class="smc-button" style="margin-bottom: 15px; display: inline-block;">العودة إلى صفحة معاملاتي</a>

    <p>هذه هي قائمة المستخدمين الذين قاموا بدعوتك (حتى 3 مستويات للأعلى):</p>

    <div class="upline-table-container"> <?php // Changed class for clarity ?>
        <?php
        if (function_exists('smc_get_referral_upline')) {
            $upline_users = smc_get_referral_upline($user_id, 3); // جلب حتى 3 مستويات

            if (!empty($upline_users)) {
                ?>
                <div class="table-responsive">
                    <table class="smc-upline-table">
                        <thead>
                            <tr>
                                <th>اسم الداعي</th>
                                <th>الداعي (اسم المستخدم)</th>
                                <th>معرف الداعي</th>
                                <th>تاريخ التسجيل</th>
                                <th>رقم المستوى</th>
                                <th>الرتبة</th>
                                <th>عرض</th>
                                <th>عدد أعضاء الفريق</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            foreach ($upline_users as $level => $referrer) {
                                $referrer_name = $referrer->display_name ?: $referrer->user_login;
                                $referrer_username = $referrer->user_login;
                                $referrer_id_display = $referrer->ID;
                                $referrer_reg_date = date_i18n('Y-m-d', strtotime($referrer->user_registered));
                                $level_display = $level + 1;
                                $referrer_rank = function_exists('smc_get_user_rank') ? smc_get_user_rank($referrer_id_display) : 'VIP0';

                                $referrer_team_count = 0;
                                if (function_exists('smc_get_referral_downline_recursive') && function_exists('smc_count_downline_members_recursive')) {
                                    $referrer_downline = smc_get_referral_downline_recursive($referrer_id_display, 3); // Get their downline
                                    $referrer_team_count = smc_count_downline_members_recursive($referrer_downline);
                                }

                                $view_profile_link = esc_url(home_url('/user/' . $referrer_username . '/'));
                                $view_team_link = esc_url(home_url('/user-downline-tree/?view_user_id=' . $referrer_id_display));

                                echo '<tr>';
                                echo '<td>' . esc_html($referrer_name) . '</td>';
                                echo '<td>' . esc_html($referrer_username) . '</td>';
                                echo '<td>' . esc_html($referrer_id_display) . '</td>';
                                echo '<td>' . esc_html($referrer_reg_date) . '</td>';
                                echo '<td>' . esc_html($level_display) . '</td>';
                                echo '<td>' . esc_html($referrer_rank) . '</td>';
                                echo '<td><a href="' . $view_profile_link . '" class="smc-button smc-button-view" target="_blank"><i class="fas fa-eye"></i> عرض</a></td>';
                                echo '<td>' . esc_html($referrer_team_count) . '</td>';
                                echo '</tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
                <?php
            } else {
                echo '<p>لم يتم العثور على أي داعين لك في النظام.</p>'; // No colspan needed here as it's a <p>
            }
        } else {
            echo '<p class="smc-error-message">خطأ: دالة جلب شجرة الداعين غير متوفرة.</p>';
        }
        ?>
    </div>
</div>

<?php get_footer(); ?>

<style>
.referral-upline-container h2 i {
    margin-left: 10px;
}
.upline-table-container { /* Changed from .upline-list */
    margin-top: 20px;
    padding: 15px;
    background-color: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 5px;
}
.table-responsive {
    overflow-x: auto;
}
.smc-upline-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 15px;
    font-size: 0.9em;
}
.smc-upline-table th,
.smc-upline-table td {
    border: 1px solid #ddd;
    padding: 8px 10px;
    text-align: right; /* RTL default */
    vertical-align: middle;
}
.smc-upline-table thead th {
    background-color: #e9ecef;
    font-weight: bold;
    color: #495057;
}
.smc-upline-table tbody tr:nth-child(even) {
    background-color: #f8f9fa;
}
.smc-upline-table tbody tr:hover {
    background-color: #e2e6ea;
}

.smc-button { /* تنسيق زر العودة */
    background-color: #6c757d;
    border-color: #6c757d;
    color: white;
    padding: 5px 10px;
    text-decoration: none;
    border-radius: 4px;
    display: inline-block;
    margin-top: 5px;
    margin-bottom: 15px; /* إضافة هامش سفلي */
}
.smc-button:hover {
    background-color: #5a6268;
    border-color: #545b62;
    color: white;
}
.smc-error-message {
    color: #dc3545; background-color: #f8d7da; border: 1px solid #f5c6cb;
    padding: 10px; border-radius: 5px;
}
/* Styles for view and team buttons (can be shared) */
.smc-button-view, .smc-button-team {
    background-color: #007bff; /* Blue */
    border-color: #007bff;
    color: white !important;
    padding: 5px 10px;
    text-decoration: none;
    border-radius: 4px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 0.9em;
    margin: 2px;
}
.smc-button-view:hover, .smc-button-team:hover {
    background-color: #0056b3;
    border-color: #0056b3;
    color: white !important;
}
.smc-button-view i, .smc-button-team i {
    margin-left: 4px; /* أيقونة على يسار النص (لأن الأزرار نفسها قد تكون LTR بسبب الأيقونة) */
}
</style>
