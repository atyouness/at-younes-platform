<?php
/**
 * Template Name: My Referral Team (Downline)
 * Description: Displays the current user's referral downline (team).
 */

if (!defined('ABSPATH')) exit; // Exit if accessed directly

// Redirect if not logged in
if (!is_user_logged_in()) {
    wp_redirect(home_url('/')); // Or login page
    exit;
}

get_header();
$current_user_id = get_current_user_id(); // Renamed for clarity

/**
 * Displays the current user's team (downline) in a table, recursively.
 *
 * @param array $downline_data The nested array of downline users.
 * @param int   $level         The current level of recursion (0 for direct referrals).
 */
function smc_display_my_team_table_recursive($downline_data, $level = 0) {
    if (empty($downline_data)) {
        return;
    }

    foreach ($downline_data as $item) {
        $member = $item['user'];
        $member_name = $member->display_name ?: $member->user_login;
        $member_reg_date = date_i18n('Y-m-d', strtotime($member->user_registered));
        $member_rank = function_exists('smc_get_user_rank') ? smc_get_user_rank($member->ID) : 'VIP0';
        $member_level_display = $level + 1; // Level relative to the current user

        $team_member_count = 0;
        if (function_exists('smc_count_downline_members_recursive')) {
            $team_member_count = smc_count_downline_members_recursive($item['downline']);
        }

        $view_profile_link = esc_url(home_url('/user/' . $member->user_login . '/'));
        $view_team_link = esc_url(home_url('/user-downline-tree/?view_user_id=' . $member->ID));

        echo '<tr>';
        echo '<td>' . esc_html($member_name) . '</td>';
        echo '<td>' . esc_html($member->user_login) . '</td>';
        echo '<td>' . esc_html($member->ID) . '</td>';
        echo '<td>' . esc_html($member_reg_date) . '</td>';
        echo '<td>' . esc_html($member_level_display) . '</td>';
        echo '<td>' . esc_html($member_rank) . '</td>';
        echo '<td><a href="' . $view_profile_link . '" class="smc-button smc-button-view" target="_blank"><i class="fas fa-eye"></i> عرض</a></td>';
        echo '<td><a href="' . $view_team_link . '" class="smc-button smc-button-team" target="_blank"><i class="fas fa-users"></i> فريق</a></td>';
        echo '<td>' . esc_html($team_member_count) . '</td>';
        echo '</tr>';

        // Recursively display their downline
        if (!empty($item['downline'])) {
            smc_display_my_team_table_recursive($item['downline'], $level + 1);
        }
    }
}
?>

<div class="container my-referral-team-container">
    <h2><i class="fas fa-users"></i> فريق الإحالات الخاص بي</h2>
    <a href="<?php echo esc_url( home_url( '/transactional/' ) ); ?>" class="smc-button" style="margin-bottom: 15px; display: inline-block;">العودة إلى صفحة معاملاتي</a>

    <?php
    // إحصاء إجمالي الفريق (فقط إذا كانت الدالة موجودة)
    $total_team_members_count = 0;
    if (function_exists('smc_get_referral_downline_recursive') && function_exists('smc_count_downline_members_recursive')) {
        $full_team_data_for_count = smc_get_referral_downline_recursive($current_user_id, 3);
        $total_team_members_count = smc_count_downline_members_recursive($full_team_data_for_count);
    }
    ?>
    <div class="smc-log-controls" style="margin-bottom: 20px; padding: 15px; background-color: #f9f9f9; border: 1px solid #eee; border-radius: 5px;">
        <p><strong>أدوات التحكم بالسجل:</strong></p>
        <p>يمكنك استخدام حقل "بحث" أدناه للبحث في فريقك. يمكنك أيضًا فرز الأعمدة وتصدير البيانات باستخدام الأزرار.</p>
        <p><strong>إجمالي أعضاء فريقك (حتى 3 مستويات): <?php echo esc_html($total_team_members_count); ?></strong></p>
    </div>


    <p>هذه هي قائمة المستخدمين الذين دعوتهم مباشرة وفريقهم (حتى 3 مستويات للأسفل):</p>

    <div class="my-team-table-container">

        <?php
        if (function_exists('smc_get_referral_downline_recursive')) {
            $my_team_data = smc_get_referral_downline_recursive($current_user_id, 3); // Fetch up to 3 levels of your downline

            if (!empty($my_team_data)) {
                ?>
                <div class="table-responsive">
                <table id="my-team-display-table" class="smc-my-team-table display compact stripe hover order-column"> <?php // Added ID and DataTables classes ?>
                    <thead>
                            <tr>
                                <th>اسم المدعو</th>
                                <th>المدعو (اسم المستخدم)</th>
                                <th>معرف المدعو</th>
                                <th>تاريخ التسجيل</th>
                                <th>رقم المستوى</th>
                                <th>الرتبة</th>
                                <th>عرض</th>
                                <th>فريق</th>
                                <th>عدد أعضاء الفريق</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php smc_display_my_team_table_recursive($my_team_data, 0); ?>
                        </tbody>
                    </table>
                </div>
                <?php
        } else {
            echo '<p>لم تقم بدعوة أي أعضاء بعد.</p>';
        }
        } else {
            echo '<p class="smc-error-message">خطأ: دالة جلب فريق الإحالات غير متوفرة.</p>';
        }
        ?>
    </div>
</div>

<?php get_footer(); ?>
<style>
.my-referral-team-container h2 i {
    margin-left: 10px;
}
.my-team-table-container {
    margin-top: 20px;
    padding: 15px;
    background-color: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 5px;
}
.table-responsive {
    overflow-x: auto;
}
.smc-my-team-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 15px;
    font-size: 0.9em;
}
.smc-my-team-table th,
.smc-my-team-table td {
    border: 1px solid #ddd;
    padding: 8px 10px;
    text-align: right; /* RTL default */
    vertical-align: middle;
}
.smc-my-team-table thead th {
    background-color: #e9ecef;
    font-weight: bold;
    color: #495057;
}
.smc-my-team-table tbody tr:nth-child(even) {
    background-color: #f8f9fa;
}
.smc-my-team-table tbody tr:hover {
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
/* Styles for view and team buttons (can be shared with user-downline-tree.php) */
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
.smc-button-view:hover, .smc-button-team:hover { background-color: #0056b3; border-color: #0056b3; color: white !important; }
.smc-button-view i, .smc-button-team i { margin-left: 4px; }

/* DataTables styles if not globally defined */
.dt-buttons .dt-button {
    background-color: #007bff !important; color: white !important; border: 1px solid #007bff !important;
    border-radius: 4px !important; padding: 5px 10px !important; margin: 0 2px 5px 2px !important;
    transition: background-color 0.3s ease !important; font-size: 0.9em !important;
}
.dt-buttons .dt-button:hover { background-color: #0056b3 !important; border-color: #0056b3 !important; }
.dataTables_filter label { font-weight: bold; font-size: 0.95em; margin-bottom: 10px; display: block; }
.dataTables_filter input { margin-left: 5px; border: 1px solid #ccc; border-radius: 4px; padding: 5px; font-size: 0.95em; width: calc(100% - 120px); max-width: 300px;}
 </style>

<?php // JavaScript لتفعيل DataTables ?>
<script type="text/javascript">
jQuery(document).ready(function($) {
    if ($.fn.DataTable) {
        try {
            $('#my-team-display-table').DataTable({
                responsive: true,
                dom: 'Bfrtip',
                buttons: ['copy', 'csv', 'excel', 'pdf', 'print'],
                order: [[ 3, "asc" ]], // Default sort by registration date (index 3)
                language: { url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/ar.json', search: "بحث في فريقك:" },
                columnDefs: [ { targets: [6, 7], orderable: false, searchable: false } ] // View and Team buttons
            });
        } catch (e) { console.error("Error initializing DataTables for my team display:", e); }
    } else { console.warn("DataTables library not found for my team display."); }
});
</script>
