<?php
/**
 * Template Name: Users Referral Log (Admin)
 * Description: Displays the complete referral log for administrators.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

get_header();

global $wpdb;
$referrals_table_name = $wpdb->prefix . 'smc_referrals'; // The main table for referral events

?>

<div class="container smc-log-container">
    <?php if ( is_user_logged_in() && current_user_can( 'administrator' ) ) : ?>
        <h2><i class="fas fa-list-alt"></i> سجل الإحالات العام (للمسؤول)</h2>

        <div class="smc-log-controls" style="margin-bottom: 20px; padding: 15px; background-color: #f9f9f9; border: 1px solid #eee; border-radius: 5px;">
            <p><strong>أدوات التحكم بالسجل:</strong></p>
            <p>يمكنك استخدام حقل "بحث" أدناه للبحث في السجلات. يمكنك أيضًا فرز الأعمدة وتصدير البيانات باستخدام الأزرار.</p>
            <p><a href="<?php echo esc_url(home_url('/smc-settings/')); ?>" class="smc-button smc-button-secondary"><i class="fas fa-cog"></i> العودة إلى إعدادات SMC</a></p>
        </div>

        <section class="smc-admin-section smc-log-section">
            <?php
            $all_referral_events = [];
            if ($wpdb->get_var("SHOW TABLES LIKE '$referrals_table_name'") == $referrals_table_name) {
                $all_referral_events = $wpdb->get_results("SELECT * FROM {$referrals_table_name} ORDER BY referral_timestamp DESC");
            }
            ?>
            <h4>قائمة الإحالات (<?php echo count($all_referral_events); ?>)</h4>
            <div class="table-responsive">
                <table id="admin-all-referrals-table" class="display compact stripe hover order-column smc-log-table" style="width:100%">
                    <thead>
                        <tr>
                            <th>اسم الداعي</th>
                            <th>الداعي (اسم المستخدم)</th>
                            <th>معرف الدعوة المستخدم</th>
                            <th>اسم المدعو</th>
                            <th>المدعو (اسم المستخدم)</th>
                            <th>معرف المدعو</th>
                            <th>تاريخ التسجيل</th>
                            <th>البريد الإلكتروني (المدعو)</th>
                            <th>الرتبة (المدعو)</th>
                            <th>عرض</th>
                            <th>فريق</th>
                            <th>عدد أعضاء الفريق (المدعو)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ( ! empty( $all_referral_events ) ) {
                            foreach ( $all_referral_events as $referral_event ) {
                                $referrer_info = get_userdata($referral_event->referrer_user_id);
                                $invitee_info = get_userdata($referral_event->invitee_user_id);

                                // Referrer Data
                                $referrer_full_name = __('مستخدم محذوف', 'smc');
                                $referrer_username = __('محذوف', 'smc');
                                if ($referrer_info) {
                                    $referrer_full_name_temp = trim(($referrer_info->first_name ?? '') . ' ' . ($referrer_info->last_name ?? ''));
                                    $referrer_full_name = !empty($referrer_full_name_temp) ? $referrer_full_name_temp : ($referrer_info->display_name ?: $referrer_info->user_login);
                                    $referrer_username = $referrer_info->user_login;
                                } else {
                                    // error_log("SMC Admin Referral Log: Missing referrer data for Referrer ID {$referral_event->referrer_user_id} in referral event ID {$referral_event->id}.");
                                }

                                // Invitation Code Used
                                $invitation_code_used = $referral_event->invitation_code_used ?: 'N/A';

                                // Invitee Data
                                $invitee_full_name = __('مستخدم محذوف', 'smc');
                                $invitee_username_display = __('محذوف', 'smc');
                                $invitee_id_display = $referral_event->invitee_user_id;
                                $invitee_reg_date = 'N/A';
                                $invitee_email = 'N/A';
                                $invitee_rank = 'N/A';
                                $invitee_team_count = 0;
                                $view_profile_link = '#';
                                $team_link_url = '#';
                                $can_show_invitee_actions = false;

                                if ($invitee_info) {
                                    $invitee_full_name_temp = trim(($invitee_info->first_name ?? '') . ' ' . ($invitee_info->last_name ?? ''));
                                    $invitee_full_name = !empty($invitee_full_name_temp) ? $invitee_full_name_temp : ($invitee_info->display_name ?: $invitee_info->user_login);
                                    $invitee_username_display = $invitee_info->user_login;
                                    $invitee_reg_date = date_i18n('Y-m-d', strtotime($invitee_info->user_registered));
                                    $invitee_email = $invitee_info->user_email;
                                    $can_show_invitee_actions = true;

                                    if (function_exists('smc_get_user_rank')) {
                                        $invitee_rank = smc_get_user_rank($invitee_info->ID);
                                    } else {
                                        $invitee_rank = 'VIP0';
                                    }

                                    if (function_exists('smc_get_referral_downline_recursive') && function_exists('smc_count_downline_members_recursive')) {
                                        $invitee_downline = smc_get_referral_downline_recursive($invitee_info->ID, 3);
                                        $invitee_team_count = smc_count_downline_members_recursive($invitee_downline);
                                    }
                                    $view_profile_link = esc_url(home_url('/user/' . $invitee_info->user_login . '/'));
                                    $team_link_url = esc_url(home_url('/user-downline-tree/?view_user_id=' . $invitee_info->ID));
                                } else {
                                     // error_log("SMC Admin Referral Log: Missing invitee data for Invitee ID {$referral_event->invitee_user_id} in referral event ID {$referral_event->id}.");
                                }

                                echo '<tr>';
                                echo '<td>' . esc_html($referrer_full_name) . '</td>';
                                echo '<td>' . esc_html($referrer_username) . '</td>';
                                echo '<td>' . esc_html($invitation_code_used) . '</td>';
                                echo '<td>' . esc_html($invitee_full_name) . '</td>';
                                echo '<td>' . esc_html($invitee_username_display) . '</td>';
                                echo '<td>' . esc_html($invitee_id_display) . '</td>';
                                echo '<td>' . esc_html($invitee_reg_date) . '</td>';
                                echo '<td>' . esc_html($invitee_email) . '</td>';
                                echo '<td>' . esc_html($invitee_rank) . '</td>';

                                if ($can_show_invitee_actions) {
                                    echo '<td><a href="' . $view_profile_link . '" class="smc-button smc-button-view" target="_blank"><i class="fas fa-eye"></i> عرض</a></td>';
                                    echo '<td><a href="' . $team_link_url . '" class="smc-button smc-button-team" target="_blank"><i class="fas fa-users"></i> فريق</a></td>';
                                } else {
                                    echo '<td><button class="smc-button smc-button-view" disabled><i class="fas fa-eye"></i> عرض</button></td>';
                                    echo '<td><button class="smc-button smc-button-team" disabled><i class="fas fa-users"></i> فريق</button></td>';
                                }
                                echo '<td>' . esc_html($invitee_team_count) . '</td>';
                                echo '</tr>';
                            }
                        } else {
                            $column_count = 12; // Number of columns in the header
                            echo '<tr><td colspan="' . $column_count . '">لا توجد إحالات مسجلة في النظام حاليًا.</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </section>

    <?php else : ?>
        <p>ليس لديك الصلاحيات الكافية لعرض هذه الصفحة. يرجى تسجيل الدخول كمسؤول.</p>
    <?php endif; ?>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    if ($.fn.DataTable) {
        try {
            $('#admin-all-referrals-table').DataTable({
                responsive: true,
                dom: 'Bfrtip',
                buttons: [
                    'copy', 'csv', 'excel', 'pdf', 'print'
                ],
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/ar.json',
                    search: "بحث في السجلات:"
                },
                order: [[ 6, "desc" ]], 
                columnDefs: [
                    { targets: [9, 10], orderable: false, searchable: false } 
                ]
            });
        } catch (e) {
            console.error("Error initializing DataTables for admin all referrals log:", e);
        }
    } else {
        console.warn("DataTables library not found for admin all referrals log.");
    }
});
</script>

<?php get_footer(); ?>

<style>
/* ... (CSS styles as before) ... */
.smc-button-view, .smc-button-team {
    background-color: #007bff;
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
.smc-button-view[disabled], .smc-button-team[disabled] {
    background-color: #6c757d;
    border-color: #6c757d;
    cursor: not-allowed;
    opacity: 0.65;
}
.smc-button-view i, .smc-button-team i {
    margin-left: 4px;
}
.smc-button-secondary {
    background-color: #6c757d;
    border-color: #6c757d;
    color: white;
    padding: 5px 10px;
    text-decoration: none;
    border-radius: 4px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    vertical-align: middle;
}
.smc-button-secondary:hover {
    background-color: #5a6268;
    border-color: #545b62;
}
.smc-button-secondary i {
    margin-left: 5px;
}
</style>
