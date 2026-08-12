<?php
/**
 * Template Name: My Invitation Link
 * Description: Displays the user's invitation link, their rank, and their direct referrals with their ranks.
 */

if (!defined('ABSPATH')) exit; // Exit if accessed directly

// Redirect if not logged in
if (!is_user_logged_in()) {
    wp_redirect(home_url('/'));
    exit;
}

get_header();

$current_user_id = get_current_user_id();
$current_user_info = get_userdata($current_user_id);
$user_smc_data = [];
$referral_code = '';
$referral_link = 'N/A';
$current_user_rank = 'VIP0'; // Default rank

if (function_exists('smc_get_user_data')) {
    $user_smc_data = smc_get_user_data($current_user_id);
    if (is_array($user_smc_data)) {
        $referral_code = $user_smc_data['referral_code'] ?? '';
        if (!empty($referral_code)) {
            $referral_link = home_url('/register/?ref=' . $referral_code);
        }
    } else {
        // Fallback if smc_get_user_data doesn't return array
        $referral_code = get_user_meta($current_user_id, SMC_REFERRAL_CODE, true);
        if (!empty($referral_code)) {
            $referral_link = home_url('/register/?ref=' . $referral_code);
        }
    }
} else {
    // Fallback if smc_get_user_data function doesn't exist
    $referral_code = get_user_meta($current_user_id, SMC_REFERRAL_CODE, true);
    if (!empty($referral_code)) {
        $referral_link = home_url('/register/?ref=' . $referral_code);
    }
}

// Get current user's rank
if (function_exists('smc_get_user_rank')) {
    $current_user_rank = smc_get_user_rank($current_user_id);
}

// Fetch direct referrals
$direct_referrals = get_users([
    'meta_key' => SMC_REFERRED_BY, // Make sure SMC_REFERRED_BY is defined
    'meta_value' => $current_user_id,
    'fields' => 'all_with_meta',
    'orderby' => 'user_registered',
    'order' => 'DESC'
]);

// Fetch the referrer of the current user
$referrer_of_current_user_id = get_user_meta($current_user_id, SMC_REFERRED_BY, true);
$referrer_of_current_user_info = null;
if ($referrer_of_current_user_id && is_numeric($referrer_of_current_user_id) && $referrer_of_current_user_id > 0) {
    $referrer_of_current_user_info = get_userdata($referrer_of_current_user_id);
}

$current_user_full_name = trim($current_user_info->first_name . ' ' . $current_user_info->last_name);
if (empty($current_user_full_name)) {
    $current_user_full_name = $current_user_info->display_name ?: $current_user_info->user_login;
}

?>

<div class="container smc-invitation-link-container">
    <h2><i class="fas fa-link"></i> رابط الدعوة الخاص بك</h2>
    <a href="<?php echo esc_url(home_url('/transactional/')); ?>" class="smc-button smc-button-secondary" style="margin-bottom: 15px; display: inline-block;">
        <i class="fas fa-arrow-left"></i> العودة إلى معاملاتي
    </a>

    <div class="smc-user-info-section" style="margin-bottom: 20px; padding: 15px; background-color: #e9f5e9; border: 1px solid #c8e6c9; border-radius: 5px;">
        <h4><i class="fas fa-user-circle"></i> معلومات الداعي (أنت):</h4>
        <p><strong>الاسم الكامل:</strong> <?php echo esc_html($current_user_full_name); ?></p>
        <p><strong>اسم المستخدم:</strong> <?php echo esc_html($current_user_info->user_login); ?></p>
        <p><strong>رتبتك الحالية:</strong> <strong style="color: #007bff;"><?php echo esc_html($current_user_rank); ?></strong></p>
        <p><strong>معرف الدعوة الخاص بك:</strong>
            <?php if ($referral_code): ?>
                <strong style="color: #007bff; font-family: monospace;"><?php echo esc_html($referral_code); ?></strong>
            <?php else: ?>
                <span style="color: #dc3545;">لم يتم إنشاؤه بعد</span>
            <?php endif; ?>
        </p>
        <p><strong>معرف الشخص الذي دعاك:</strong>
            <?php if ($referrer_of_current_user_info): ?>
                <strong style="color: #007bff;"><?php echo esc_html($referrer_of_current_user_info->user_login); ?> (ID: <?php echo esc_html($referrer_of_current_user_info->ID); ?>)</strong>
            <?php else: ?>
                <span style="color: #6c757d;">لا يوجد داعي مسجل لك.</span>
            <?php endif; ?>
        </p>
        <?php if ($referral_link !== 'N/A'): ?>
            <p><strong>رابط الدعوة الخاص بك:</strong></p>
            <div style="display: flex; align-items: center; gap: 5px; margin-bottom: 10px;">
                <input type="text" id="referral-link-input" value="<?php echo esc_attr($referral_link); ?>" readonly style="flex-grow: 1; padding: 8px; border: 1px solid #ccc; border-radius: 4px; background-color: #f8f9fa; font-family: monospace;">
                <button id="copy-referral-link-button" class="smc-button smc-button-action" style="padding: 8px 12px; white-space: nowrap;">
                    <i class="fas fa-copy"></i> نسخ
                </button>
            </div>
            <span id="copy-status" style="font-size: 0.9em; color: green; display: none;">تم النسخ!</span>
        <?php endif; ?>
    </div>

    <hr style="margin: 25px 0;">

    <h4><i class="fas fa-users"></i> الأعضاء الذين دعوتهم مباشرة (<?php echo count($direct_referrals); ?>)</h4>
    <?php if (!empty($direct_referrals)): ?>
        <div class="smc-log-controls" style="margin-bottom: 15px; padding: 10px; background-color: #f9f9f9; border: 1px solid #eee; border-radius: 5px;">
            <p>يمكنك استخدام حقل "بحث" أدناه للبحث في قائمة المدعوين. يمكنك أيضًا فرز الأعمدة وتصدير البيانات.</p>
        </div>
        <div class="table-responsive">
            <table id="direct-referrals-table" class="display compact stripe hover smc-log-table" style="width:100%">
                <thead>
                    <tr>
                        <th>اسم المدعو</th>
                        <th>المدعو (اسم المستخدم)</th>
                        <th>معرف المدعو</th>
                        <th>تاريخ التسجيل</th>
                        <th>الرتبة</th>
                        <th>عرض الملف الشخصي</th>
                        <th>عدد أعضاء الفريق (للمدعو)</th>
                        <th>فريق المدعو</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    foreach ($direct_referrals as $referral) {
                        $referral_name = trim($referral->first_name . ' ' . $referral->last_name);
                        if (empty($referral_name)) {
                            $referral_name = $referral->display_name ?: $referral->user_login;
                        }
                        $referral_reg_date = date_i18n('Y-m-d', strtotime($referral->user_registered));
                        $referral_rank = function_exists('smc_get_user_rank') ? smc_get_user_rank($referral->ID) : 'VIP0';
                        $profile_link = esc_url(home_url('/user/' . $referral->user_login . '/'));

                        $referred_user_team_count = 0;
                        if (function_exists('smc_get_referral_downline_recursive') && function_exists('smc_count_downline_members_recursive')) {
                            $referred_user_downline = smc_get_referral_downline_recursive($referral->ID, 3); // Get their downline up to 3 levels
                            $referred_user_team_count = smc_count_downline_members_recursive($referred_user_downline);
                        }
                        $team_link_url = esc_url(home_url('/user-downline-tree/?view_user_id=' . $referral->ID));


                        echo '<tr>';
                        echo '<td>' . esc_html($referral_name) . '</td>';
                        echo '<td>' . esc_html($referral->user_login) . '</td>';
                        echo '<td>' . esc_html($referral->ID) . '</td>';
                        echo '<td>' . esc_html($referral_reg_date) . '</td>';
                        echo '<td>' . esc_html($referral_rank) . '</td>';
                        echo '<td><a href="' . $profile_link . '" class="smc-button smc-button-view" target="_blank"><i class="fas fa-eye"></i> عرض</a></td>';
                        echo '<td>' . esc_html($referred_user_team_count) . '</td>';
                        echo '<td><a href="' . $team_link_url . '" class="smc-button smc-button-team" target="_blank"><i class="fas fa-users"></i> فريق</a></td>';
                        echo '</tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p>لم تقم بدعوة أي أعضاء بشكل مباشر حتى الآن.</p>
    <?php endif; ?>

</div>

<?php get_footer(); ?>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // DataTables Initialization for direct referrals
    if ($.fn.DataTable && $('#direct-referrals-table').length) {
        try {
            $('#direct-referrals-table').DataTable({
                responsive: true,
                dom: 'Bfrtip',
                buttons: ['copy', 'csv', 'excel', 'pdf', 'print'],
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/ar.json',
                    search: "بحث في المدعوين:"
                },
                order: [[ 3, "desc" ]], // Default sort by registration date (index 3)
                columnDefs: [
                    { targets: [5, 7], orderable: false, searchable: false } // View profile & Team buttons
                ]
            });
        } catch (e) {
            console.error("Error initializing DataTables for direct referrals:", e);
            $('.smc-invitation-link-container').prepend('<p class="smc-error-message">حدث خطأ أثناء تحميل جدول المدعوين التفاعلي.</p>');
        }
    } else if ($('#direct-referrals-table').length) {
        console.warn("DataTables library not found for direct referrals table.");
        $('.smc-invitation-link-container').prepend('<p class="smc-error-message">لم يتم تحميل مكتبة الجداول التفاعلية (DataTables) لجدول المدعوين.</p>');
    }

    // Copy Referral Link Button
    const copyButton = document.getElementById('copy-referral-link-button');
    const linkInput = document.getElementById('referral-link-input');
    const copyStatus = document.getElementById('copy-status');

    if (copyButton && linkInput) {
        copyButton.addEventListener('click', function() {
            linkInput.select();
            linkInput.setSelectionRange(0, 99999); // For mobile devices

            try {
                navigator.clipboard.writeText(linkInput.value).then(function() {
                    if (copyStatus) {
                        copyStatus.textContent = 'تم النسخ بنجاح!';
                        copyStatus.style.display = 'inline';
                        setTimeout(() => { copyStatus.style.display = 'none'; }, 2500);
                    }
                    const originalText = copyButton.innerHTML;
                    copyButton.innerHTML = '<i class="fas fa-check"></i> تم النسخ';
                    setTimeout(() => { copyButton.innerHTML = originalText; }, 2500);
                }, function(err) {
                    console.error('Async: Could not copy text: ', err);
                    if (copyStatus) {
                        copyStatus.textContent = 'فشل النسخ. يرجى النسخ يدويًا.';
                        copyStatus.style.color = 'red';
                        copyStatus.style.display = 'inline';
                        setTimeout(() => { copyStatus.style.display = 'none'; copyStatus.style.color = 'green'; }, 3000);
                    }
                });
            } catch (err) {
                console.error('Fallback: Oops, unable to copy', err);
                 if (copyStatus) {
                    copyStatus.textContent = 'فشل النسخ. يرجى النسخ يدويًا.';
                    copyStatus.style.color = 'red';
                    copyStatus.style.display = 'inline';
                    setTimeout(() => { copyStatus.style.display = 'none'; copyStatus.style.color = 'green'; }, 3000);
                }
            }
        });
    }
});
</script>

<style>
.smc-invitation-link-container { max-width: 900px; margin: 20px auto; padding: 20px; background-color: #fff; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
.smc-invitation-link-container h2, .smc-invitation-link-container h4 { display: flex; align-items: center; color: #343a40; margin-bottom: 15px; }
.smc-invitation-link-container h2 i, .smc-invitation-link-container h4 i { margin-left: 10px; color: #007bff; }
.smc-user-info-section p { margin-bottom: 8px; line-height: 1.6; }
.smc-user-info-section strong { color: #333; }

.smc-button-secondary {
    background-color: #6c757d; border-color: #6c757d; color: white !important; padding: 6px 12px;
    text-decoration: none; border-radius: 4px; display: inline-flex; align-items: center; font-size: 0.9em;
}
.smc-button-secondary:hover { background-color: #5a6268; border-color: #545b62; color: white !important; }
.smc-button-secondary i { margin-left: 5px; }

.smc-button-action { /* For copy button */
    background-color: #007bff; border-color: #007bff; color: white !important; padding: 6px 12px;
    text-decoration: none; border-radius: 4px; display: inline-flex; align-items: center; font-size: 0.9em; cursor: pointer;
}
.smc-button-action:hover { background-color: #0056b3; border-color: #0056b3; color: white !important; }
.smc-button-action i { margin-left: 5px; }

.smc-button-view, .smc-button-team { /* For view profile & team buttons in table */
    background-color: #17a2b8; border-color: #17a2b8; color: white !important; padding: 4px 8px;
    text-decoration: none; border-radius: 4px; display: inline-flex; align-items: center; font-size: 0.85em; margin: 0 2px;
}
.smc-button-view:hover, .smc-button-team:hover { background-color: #117a8b; border-color: #10707f; color: white !important; }
.smc-button-view i, .smc-button-team i { margin-left: 3px; }

.smc-log-table { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 0.9em; }
.smc-log-table th, .smc-log-table td { border: 1px solid #ddd; padding: 8px 10px; text-align: right; vertical-align: middle; }
.smc-log-table th { background-color: #f2f2f2; font-weight: bold; }
.smc-log-table tbody tr:nth-child(even) { background-color: #f9f9f9; }

.smc-error-message { color: #dc3545; background-color: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; border-radius: 5px; margin-bottom: 15px; }

/* DataTables Controls */
.dt-buttons .dt-button {
    background-color: #007bff !important; color: white !important; border: 1px solid #007bff !important;
    border-radius: 4px !important; padding: 5px 10px !important; margin: 0 2px 5px 2px !important;
    transition: background-color 0.3s ease !important; font-size: 0.9em !important;
}
.dt-buttons .dt-button:hover { background-color: #0056b3 !important; border-color: #0056b3 !important; }
.dataTables_filter label { font-weight: bold; font-size: 0.95em; }
.dataTables_filter input { margin-left: 5px; border: 1px solid #ccc; border-radius: 4px; padding: 5px; font-size: 0.95em; }

/* Mobile specific for referral link input group */
@media (max-width: 480px) {
    #referral-link-input {
        margin-bottom: 5px; /* Add space when stacked */
    }
    #referral-link-input,
    #copy-referral-link-button {
        width: 100%;
        display: block; /* Stack them */
        margin-left: 0; /* Reset margin for button */
    }
}

@import url("https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css");
</style>
