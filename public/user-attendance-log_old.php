<?php
/**
 * Template Name: User Attendance Log
 * Description: Displays the attendance log for the current user. // تم تعديل الوصف
 */

if (!defined('ABSPATH')) exit; // Exit if accessed directly

// Redirect if not logged in
if (!is_user_logged_in()) { // إزالة التحقق من المسؤول
    wp_redirect(home_url('/')); // Or login page
    exit;
}

get_header();
$user_id = get_current_user_id(); // الحصول على ID المستخدم الحالي
?>

<div class="container user-attendance-log-container"> <?php // تغيير الفئة إذا أردت تمييزها ?>
    <h2><i class="fas fa-calendar-check"></i> سجل الحضور الخاص بك</h2> <?php // تغيير العنوان ?>

    <!-- Log Controls -->
    <div class="smc-log-controls" style="margin-bottom: 20px; padding: 15px; background-color: #f9f9f9; border: 1px solid #eee; border-radius: 5px;">
    <p><a href="<?php echo esc_url(home_url('/transactional/')); ?>" class="smc-button smc-button-secondary"><i class="fas fa-tachometer-alt"></i> العودة إلى صفحة ⚙️ معاملاتي</a></p> <?php // تغيير الرابط والنص ?>
        <p><strong>أدوات التحكم بالسجل:</strong></p>
        <p>يمكنك استخدام حقل "بحث" أدناه للبحث في سجل حضورك حسب التاريخ أو الوقت. يمكنك أيضًا فرز الأعمدة (مثل النقاط) بالضغط على رؤوسها.</p>
        <p>أزرار التصدير (Copy, CSV, Excel, PDF, Print) متاحة أعلى الجدول لحفظ سجلاتك.</p>      
    </div>

    <table id="user-attendance-table" class="display compact stripe hover smc-log-table" style="width:100%"> <?php // تغيير ID الجدول ?>
        <thead>
            <tr>
                <?php /* إزالة أعمدة ID واسم المستخدم */ ?>
                <th>تاريخ الحضور</th>
                <th>وقت الحضور</th>
                <th>النقاط الممنوحة</th>
            </tr>
        </thead>
        <tbody>
            <?php
            global $wpdb;
            $attendance_table = $wpdb->prefix . 'smc_attendance_log';
            // *** تعديل الاستعلام: جلب بيانات الحضور للمستخدم الحالي فقط ***
            $user_attendance = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$attendance_table} WHERE user_id = %d ORDER BY attendance_timestamp DESC",
                $user_id
            ));

            if ($user_attendance) {
                foreach ($user_attendance as $att_user) {
                    // لا حاجة لجلب بيانات المستخدم هنا
                    echo '<tr>';
                    // إزالة خلايا ID واسم المستخدم
                    echo '<td>' . esc_html(date_i18n('Y-m-d', strtotime($att_user->attendance_date))) . '</td>';
                    echo '<td>' . esc_html(date_i18n('H:i:s', strtotime($att_user->attendance_timestamp))) . '</td>';
                    echo '<td>' . esc_html($att_user->points_awarded) . '</td>';
                    echo '</tr>';
                }
            } else {
                echo '<tr><td colspan="3">لا يوجد سجلات حضور لعرضها.</td></tr>'; // تعديل colspan
            }
            ?>
        </tbody>
    </table>
</div>

<?php get_footer(); ?>

<?php // --- JavaScript لتفعيل DataTables --- ?>
<script type="text/javascript">
jQuery(document).ready(function($) {
    // التأكد من تحميل مكتبة DataTables
    if ($.fn.DataTable) {
        try {
            var userTable = $('#user-attendance-table').DataTable({ // استخدام ID الجدول الجديد
                responsive: true,
                // تبسيط الـ dom للمستخدم (يمكن إبقاء الأزرار إذا أردت)
                // dom: 'frtip', // Filter, processing, table, info, pagination
                dom: 'Bfrtip', // إبقاء الأزرار للبحث والتصدير الشخصي
                buttons: [
                    'copy', 'csv', 'excel', 'pdf', 'print'
                ],
                order: [[ 1, "desc" ]], // الترتيب الافتراضي حسب وقت الحضور (العمود الثاني الآن)
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/ar.json',
                     search: "بحث في سجلك:" // تخصيص نص البحث
                },
                // لا حاجة لـ columnDefs هنا لأن الأعمدة المتبقية قابلة للبحث والفرز
            });

        } catch (e) {
            console.error("Error initializing DataTables for user attendance:", e);
            $('.user-attendance-log-container').prepend('<p class="smc-error-message">حدث خطأ أثناء تحميل جدول السجلات التفاعلي.</p>');
        }
    } else {
        console.warn("DataTables library not found for user attendance.");
         $('.user-attendance-log-container').prepend('<p class="smc-error-message">لم يتم تحميل مكتبة الجداول التفاعلية (DataTables).</p>');
    }
});
</script>

<style>
/* يمكن نسخ نفس التنسيقات من ملف admin log أو تخصيصها */
.smc-error-message { color: #dc3545; background-color: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; border-radius: 5px; margin-bottom: 15px; }
.dt-buttons .dt-button {
    background-color: #007bff !important; color: white !important; border: 1px solid #007bff !important;
    border-radius: 4px !important; padding: 5px 10px !important; margin: 0 2px 5px 2px !important;
    transition: background-color 0.3s ease !important;
}
.dt-buttons .dt-button:hover { background-color: #0056b3 !important; border-color: #0056b3 !important; }
.dataTables_filter label { font-weight: bold; }
.dataTables_filter input { margin-left: 5px; border: 1px solid #ccc; border-radius: 4px; padding: 5px; }
.smc-button-secondary {
    background-color: #6c757d; border-color: #6c757d; color: white; padding: 5px 10px;
    text-decoration: none; border-radius: 4px; display: inline-block; margin-top: 5px;
}
.smc-button-secondary:hover { background-color: #5a6268; border-color: #545b62; color: white; }
.smc-button-secondary i { margin-left: 5px; }
</style>
