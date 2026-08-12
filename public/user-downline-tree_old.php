<?php
/**
 * Template Name: User Downline Tree (شجرة المحالين للمستخدم)
 * Description: Displays the referral downline (referred users) for a specific user or the current user.
 */


if (!defined('ABSPATH')) exit; // Exit if accessed directly

// Redirect if not logged in
if (!is_user_logged_in()) {
    wp_redirect(home_url('/')); // Or login page
    exit;
}

get_header();

$current_user_id = get_current_user_id();
$user_id_to_display = $current_user_id; // Default to current user
$can_view = true;
$page_title_user_name = 'الخاص بك';

if (isset($_GET['view_user_id']) && !empty($_GET['view_user_id'])) {
    $target_user_id = intval($_GET['view_user_id']);
    if ($target_user_id !== $current_user_id) { // If trying to view someone else's downline
        $target_user_data = get_userdata($target_user_id);
        if ($target_user_data) {
            if (current_user_can('administrator')) {
                $user_id_to_display = $target_user_id;
                $page_title_user_name = 'للعضو ' . esc_html($target_user_data->user_login);
            } else {
                // Check if current user is the sponsor of the target user
                $target_user_sponsor_id = get_user_meta($target_user_id, SMC_REFERRED_BY, true);
                if ($target_user_sponsor_id == $current_user_id) {
                    $user_id_to_display = $target_user_id;
                    $page_title_user_name = 'للعضو ' . esc_html($target_user_data->user_login);
                } else {
                    $can_view = false;
                }
            }
        } else {
            $can_view = false; // Target user does not exist
        }
    }
}

/**
 * Displays the downline list recursively.
 *
 * @param array $downline_data The nested array of downline users.
 * @param int   $level         The current level of recursion.
 */
function smc_display_downline_list_recursive($downline_data, $level = 0) {
    global $current_user_id; // Access the global variable

    if (empty($downline_data)) {
        if ($level === 0) { // Only show "no referrals" message for the top level
            // This message will be handled outside if the table is empty
        }
        return;
    }

    foreach ($downline_data as $item) {
        $member = $item['user'];
        $member_name = $member->display_name ?: $member->user_login;
        $member_reg_date = date_i18n('Y-m-d', strtotime($member->user_registered));
        $member_rank = function_exists('smc_get_user_rank') ? smc_get_user_rank($member->ID) : 'VIP0';
        $member_level = $level + 1;
        // $team_count = count($item['downline']); // Old: Count of direct referrals for this member
        $team_count = 0;
        if (function_exists('smc_count_downline_members_recursive')) {
            $team_count = smc_count_downline_members_recursive($item['downline']); // New: Count all members in their sub-downline
        }
        $view_link = esc_url(home_url('/user/' . $member->user_login . '/'));
        $team_link = esc_url(home_url('/user-downline-tree/?view_user_id=' . $member->ID));

        echo '<tr>';
        echo '<td>' . esc_html($member_name) . '</td>';
        echo '<td>' . esc_html($member->user_login) . '</td>';
        echo '<td>' . esc_html($member->ID) . '</td>';
        echo '<td>' . esc_html($member_reg_date) . '</td>';
        echo '<td>' . esc_html($member_level) . '</td>';
        echo '<td>' . esc_html($member_rank) . '</td>';
        echo '<td><a href="' . $view_link . '" class="smc-button smc-button-view" target="_blank"><i class="fas fa-eye"></i> عرض</a></td>';
        echo '<td><a href="' . $team_link . '" class="smc-button smc-button-team" target="_blank"><i class="fas fa-users"></i> فريق</a></td>';
        echo '<td>' . esc_html($team_count) . '</td>';
        echo '</tr>';

        // Recursively display their downline
        if (!empty($item['downline'])) {
            smc_display_downline_list_recursive($item['downline'], $level + 1);
        }
    }
}

?>

<div class="container referral-downline-container">
    <h2><i class="fas fa-sitemap"></i> شجرة المحالين (Downline) <?php echo esc_html($page_title_user_name); ?></h2>
    <a href="<?php echo esc_url( home_url( '/transactional/' ) ); ?>" class="smc-button" style="margin-bottom: 15px; display: inline-block;">العودة إلى صفحة معاملاتي</a>

    <div class="downline-list">
        <?php
        if (!$can_view) {
            echo '<p class="smc-error-message">ليس لديك الصلاحية لعرض فريق هذا العضو.</p>';
        } elseif (function_exists('smc_get_referral_downline_recursive')) {
            $downline_tree = smc_get_referral_downline_recursive($user_id_to_display, 3); // Fetch up to 3 levels
            if (empty($downline_tree)) {
                echo '<p>لم يقم ' . ($user_id_to_display == $current_user_id ? 'أنت' : 'هذا المستخدم') . ' بدعوة أي أعضاء بعد.</p>';
            } else {
                echo '<p>هذه هي قائمة المستخدمين الذين تم دعوتهم (حتى 3 مستويات للأسفل):</p>';
                ?>
                <div class="table-responsive">
                    <table class="smc-downline-table">
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
                            <?php smc_display_downline_list_recursive($downline_tree, 0); ?>
                        </tbody>
                    </table>
                </div>
                <?php
            }
        } else {
            echo '<p class="smc-error-message">خطأ: دالة جلب شجرة المحالين غير متوفرة.</p>';
        }
        ?>
    </div>
</div>

<?php get_footer(); ?>

<style>
.referral-downline-container h2 i {
    margin-left: 10px;
}
.downline-list {
    margin-top: 20px;
    padding: 15px;
    background-color: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 5px;
}
.table-responsive {
    overflow-x: auto;
}
.smc-downline-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 15px;
    font-size: 0.9em;
}
.smc-downline-table th,
.smc-downline-table td {
    border: 1px solid #ddd;
    padding: 8px 10px;
    text-align: right; /* RTL default */
    vertical-align: middle;
}
.smc-downline-table thead th {
    background-color: #e9ecef;
    font-weight: bold;
    color: #495057;
}
.smc-downline-table tbody tr:nth-child(even) {
    background-color: #f8f9fa;
}
.smc-downline-table tbody tr:hover {
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
/* Styles for new buttons from my-team-referrals-log.php, ensure consistency */
.smc-button-view, .smc-button-team {
    background-color: #007bff; /* Blue */
    border-color: #007bff;
    color: white !important; /* Ensure text is white */
    padding: 5px 10px;
    text-decoration: none;
    border-radius: 4px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 0.9em;
    margin: 2px; /* Add margin between buttons */
}
.smc-button-view:hover, .smc-button-team:hover { background-color: #0056b3; border-color: #0056b3; color: white !important; }
.smc-button-view i, .smc-button-team i { margin-left: 4px; }
</style>
