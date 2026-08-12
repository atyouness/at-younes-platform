<?php
/**
 * Template Name: Advertiser Ads Log
 * Description: Displays ad impression statistics for advertisers (Admin view).
 */

if (!defined('ABSPATH')) exit; // Exit if accessed directly

// Redirect if not logged in or not an admin
if (!is_user_logged_in() || !current_user_can('administrator')) {
    wp_redirect(home_url('/'));
    exit;
}

get_header();
?>

<div class="container ads-log-container">
    <h2><i class="fas fa-chart-bar"></i> سجل ظهور الإعلانات (للمعلنين/المسؤول)</h2>

    <!-- Log Controls -->
    <div class="smc-log-controls" style="margin-bottom: 20px; padding: 15px; background-color: #f9f9f9; border: 1px solid #eee; border-radius: 5px;">
        <p><strong>أدوات التحكم بالسجل:</strong></p>
        <p>يمكنك استخدام حقل "بحث" أدناه للبحث عن طريق معرف الإعلان، المعلن، أو المستخدم. يمكنك أيضًا فرز الأعمدة.</p>
        <p>أزرار التصدير (Copy, CSV, Excel, PDF, Print) متاحة أعلى الجدول.</p>
        <p><a href="<?php echo esc_url(home_url('/smc-settings/')); ?>" class="smc-button smc-button-secondary"><i class="fas fa-cog"></i> العودة إلى إعدادات SMC</a></p>
    </div>

    <?php
    // --- تنبيه: يتطلب إنشاء جدول جديد وتتبع المشاهدات ---
    echo '<div class="smc-info-message" style="margin-bottom:15px;"><strong>ملاحظة:</strong> هذه الصفحة تتطلب إنشاء جدول جديد في قاعدة البيانات (`wpwn_smc_ad_impressions` مثلاً) وتحديث آلية عرض الإعلانات لتسجيل كل مشاهدة في هذا الجدول. البيانات المعروضة أدناه هي مجرد مثال هيكلي.</div>';
    ?>

    <table id="admin-ads-log-table" class="display compact stripe hover smc-log-table" style="width:100%">
        <thead>
            <tr>
                <th>معرف الإعلان</th>
                <th>اسم الإعلان/المنتج</th>
                <th>المعلن</th>
                <th>المستخدم المشاهد</th>
                <th>تاريخ/وقت المشاهدة</th>
                <th>معلومات إضافية (IP, etc.)</th>
            </tr>
        </thead>
        <tbody>
            <?php
            /*
            // --- مثال لكود جلب البيانات (بعد إنشاء الجدول وآلية التتبع) ---
            global $wpdb;
            $impressions_table = $wpdb->prefix . 'smc_ad_impressions'; // اسم الجدول المقترح

            if($wpdb->get_var("SHOW TABLES LIKE '$impressions_table'") == $impressions_table) {
                $impressions = $wpdb->get_results("SELECT * FROM {$impressions_table} ORDER BY impression_timestamp DESC LIMIT 1000"); // Limit for performance

                if ($impressions) {
                    foreach ($impressions as $impression) {
                        // جلب معلومات الإعلان، المعلن، والمستخدم
                        // $ad_info = get_post($impression->ad_id); // مثال إذا كانت الإعلانات posts
                        // $advertiser_info = get_userdata($impression->advertiser_id);
                        $viewer_info = get_userdata($impression->user_id);

                        echo '<tr>';
                        echo '<td>' . esc_html($impression->ad_id) . '</td>';
                        echo '<td>' . esc_html(get_the_title($impression->ad_id)) . '</td>'; // مثال
                        echo '<td>' . esc_html($advertiser_info->user_login ?? 'N/A') . '</td>'; // مثال
                        echo '<td>' . esc_html($viewer_info->user_login ?? 'N/A') . '</td>';
                        echo '<td>' . esc_html(date_i18n('Y-m-d H:i:s', strtotime($impression->impression_timestamp))) . '</td>';
                        echo '<td>' . esc_html($impression->ip_address ?? '') . '</td>'; // مثال
                        echo '</tr>';
                    }
                } else {
                    echo '<tr><td colspan="6">لا توجد سجلات مشاهدة إعلانات لعرضها.</td></tr>';
                }
            } else {
                 echo '<tr><td colspan="6" class="smc-error-message">خطأ: جدول سجل مشاهدات الإعلانات (`' . $impressions_table . '`) غير موجود.</td></tr>';
            }
            */
             echo '<tr><td colspan="6">مثال: لا توجد سجلات مشاهدة إعلانات لعرضها (تحتاج لتطبيق آلية التتبع).</td></tr>'; // مثال حالي
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
            $('#admin-ads-log-table').DataTable({
                responsive: true,
                dom: 'Bfrtip',
                buttons: ['copy', 'csv', 'excel', 'pdf', 'print'],
                order: [[ 4, "desc" ]], // الترتيب حسب تاريخ المشاهدة
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/ar.json',
                    search: "بحث في السجلات:"
                }
            });
        } catch (e) { console.error("Error initializing DataTables for ads log:", e); }
    } else { console.warn("DataTables library not found for ads log."); }
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
