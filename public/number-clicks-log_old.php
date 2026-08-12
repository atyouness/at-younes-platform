<?php
/**
 * Template Name: User Clicks Log
 * Description: Displays a log of specific user button clicks (Admin view).
 */

if (!defined('ABSPATH')) exit; // Exit if accessed directly

// Redirect if not logged in or not an admin
if (!is_user_logged_in() || !current_user_can('administrator')) {
    wp_redirect(home_url('/'));
    exit;
}

get_header();
?>

<div class="container clicks-log-container">
    <h2><i class="fas fa-mouse-pointer"></i> سجل ضغطات المستخدمين على الأزرار</h2>

    <!-- Log Controls -->
    <div class="smc-log-controls" style="margin-bottom: 20px; padding: 15px; background-color: #f9f9f9; border: 1px solid #eee; border-radius: 5px;">
        <p><strong>أدوات التحكم بالسجل:</strong></p>
        <p>يمكنك استخدام حقل "بحث" أدناه للبحث عن طريق اسم المستخدم، معرف الزر/الإجراء، أو الصفحة. يمكنك أيضًا فرز الأعمدة.</p>
        <p>أزرار التصدير (Copy, CSV, Excel, PDF, Print) متاحة أعلى الجدول.</p>
        <p><a href="<?php echo esc_url(home_url('/smc-settings/')); ?>" class="smc-button smc-button-secondary"><i class="fas fa-cog"></i> العودة إلى إعدادات SMC</a></p>
    </div>

    <?php
    // --- تنبيه: يتطلب إنشاء جدول جديد وتتبع النقرات ---
    echo '<div class="smc-info-message" style="margin-bottom:15px;"><strong>ملاحظة:</strong> هذه الصفحة تتطلب إنشاء جدول جديد (`wpwn_smc_click_log` مثلاً)، وتعديل JavaScript الخاص بكل زر مستهدف لإرسال طلب AJAX، وإنشاء معالج AJAX في `functions.php` لتسجيل النقرات. البيانات المعروضة أدناه هي مجرد مثال هيكلي.</div>';
    ?>

    <table id="admin-clicks-log-table" class="display compact stripe hover smc-log-table" style="width:100%">
        <thead>
            <tr>
                <th>اسم المستخدم</th>
                <th>معرف المستخدم</th>
                <th>الزر/الإجراء</th>
                <th>الصفحة</th>
                <th>تاريخ/وقت الضغطة</th>
                <th>معلومات إضافية (IP, etc.)</th>
            </tr>
        </thead>
        <tbody>
            <?php
            /*
            // --- مثال لكود جلب البيانات (بعد إنشاء الجدول وآلية التتبع) ---
            global $wpdb;
            $clicks_table = $wpdb->prefix . 'smc_click_log'; // اسم الجدول المقترح

            if($wpdb->get_var("SHOW TABLES LIKE '$clicks_table'") == $clicks_table) {
                $clicks = $wpdb->get_results("SELECT * FROM {$clicks_table} ORDER BY click_timestamp DESC LIMIT 1000"); // Limit for performance

                if ($clicks) {
                    foreach ($clicks as $click) {
                        $user_info = get_userdata($click->user_id);
                        $username = $user_info ? $user_info->user_login : 'غير معروف (' . $click->user_id . ')';

                        echo '<tr>';
                        echo '<td>' . esc_html($username) . '</td>';
                        echo '<td>' . esc_html($click->user_id) . '</td>';
                        echo '<td>' . esc_html($click->button_id) . '</td>'; // أو action_name
                        echo '<td>' . esc_html($click->page_url) . '</td>';
                        echo '<td>' . esc_html(date_i18n('Y-m-d H:i:s', strtotime($click->click_timestamp))) . '</td>';
                        echo '<td>' . esc_html($click->ip_address ?? '') . '</td>'; // مثال
                        echo '</tr>';
                    }
                } else {
                    echo '<tr><td colspan="6">لا توجد سجلات ضغطات لعرضها.</td></tr>';
                }
            } else {
                 echo '<tr><td colspan="6" class="smc-error-message">خطأ: جدول سجل الضغطات (`' . $clicks_table . '`) غير موجود.</td></tr>';
            }
            */
             echo '<tr><td colspan="6">مثال: لا توجد سجلات ضغطات لعرضها (تحتاج لتطبيق آلية التتبع).</td></tr>'; // مثال حالي
            ?>
        </tbody>
    </table>
</div>

<?php get_footer(); ?>

<?php // تفعيل DataTables ?>
<script type="text/javascript">
jQuery(document).ready(function($) {
    if ($.fn.DataTable) {
        try {
            $('#admin-clicks-log-table').DataTable({
                responsive: true,
                dom: 'Bfrtip',
                buttons: ['copy', 'csv', 'excel', 'pdf', 'print'],
                order: [[ 4, "desc" ]], // الترتيب حسب تاريخ الضغطة
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/ar.json',
                    search: "بحث في السجلات:"
                }
            });
        } catch (e) { console.error("Error initializing DataTables for clicks log:", e); }
    } else { console.warn("DataTables library not found for clicks log."); }
});
</script>

<style>
/* ... (تنسيقات مشابهة للصفحات الأخرى) ... */
.smc-button-secondary { /* ... */ }
.smc-error-message { /* ... */ }
.smc-info-message { color: #0c5460; background-color: #d1ecf1; border: 1px solid #bee5eb; padding: 10px; border-radius: 5px; }
.dt-buttons .dt-button { /* ... */ }
.dataTables_filter label { /* ... */ }
.dataTables_filter input { /* ... */ }
@import url("https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css");
</style>
