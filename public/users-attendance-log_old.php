<?php
/**
 * Template Name: Users Attendance Log (Admin View)
 * Description: Displays the attendance log for ALL users (Admin view only).
 */

if (!defined('ABSPATH')) exit; // Exit if accessed directly

// Redirect if not logged in or not an admin
if (!is_user_logged_in() || !current_user_can('administrator')) {
    wp_redirect(home_url('/')); // Or login page
    exit;
}

get_header();
?>

<div class="container admin-attendance-log-container">
    <i class="fas fa-users-cog"></i> سجل حضور جميع المستخدمين (للمسؤول)</h2>

    <!-- Log Controls -->
    <div class="smc-log-controls" style="margin-bottom: 20px; padding: 15px; background-color: #f9f9f9; border: 1px solid #eee; border-radius: 5px;">
        <p><strong>أدوات التحكم بالسجل:</strong></p>
        <p>يمكنك استخدام حقل "بحث" أدناه للبحث عن طريق معرف المستخدم، اسم المستخدم، التاريخ (YYYY-MM-DD)، أو الوقت (HH:MM:SS). يمكنك أيضًا فرز الأعمدة (مثل النقاط) بالضغط على رؤوسها.</p>
        <p>أزرار التصدير (Copy, CSV, Excel, PDF, Print) متاحة أعلى الجدول.</p>
        <p><a href="<?php echo esc_url(home_url('/smc-settings/')); ?>" class="smc-button smc-button-secondary"><i class="fas fa-cog"></i> العودة إلى إعدادات SMC</a></p>
        <?php /*
        <!-- يمكنك إضافة حقول تصفية مخصصة هنا لاحقًا -->
        <label for="filter-date-start">من تاريخ:</label>
        <input type="date" id="filter-date-start">
        <label for="filter-date-end">إلى تاريخ:</label>
        <input type="date" id="filter-date-end">
        <button id="apply-filters">تطبيق الفلتر</button>
        */ ?>
    </div>

    <table id="admin-attendance-table" class="display compact stripe hover smc-log-table" style="width:100%">
        <thead>
            <tr>
                <th>معرف المستخدم</th>
                <th>اسم المستخدم</th>
                <th>تاريخ الحضور</th>
                <th>وقت الحضور</th>
                <th>النقاط الممنوحة</th>
            </tr>
        </thead>
        <tbody>
            <?php
            global $wpdb;
            $attendance_table = $wpdb->prefix . 'smc_attendance_log';
            // جلب بيانات الحضور لجميع المستخدمين
            $all_attendance = $wpdb->get_results("SELECT * FROM {$attendance_table} ORDER BY attendance_timestamp DESC"); // جلب الكل للفرز والبحث في DataTables

            if ($all_attendance) {
                foreach ($all_attendance as $att_admin) {
                    $user_info = get_userdata($att_admin->user_id);
                    $username = $user_info ? $user_info->user_login : 'غير معروف (' . $att_admin->user_id . ')';
                    echo '<tr>';
                    echo '<td>' . esc_html($att_admin->user_id) . '</td>';
                    echo '<td>' . esc_html($username) . '</td>';
                    echo '<td>' . esc_html(date_i18n('Y-m-d', strtotime($att_admin->attendance_date))) . '</td>';
                    echo '<td>' . esc_html(date_i18n('H:i:s', strtotime($att_admin->attendance_timestamp))) . '</td>';
                    echo '<td>' . esc_html($att_admin->points_awarded) . '</td>';
                    echo '</tr>';
                }
            } else {
                echo '<tr><td colspan="5">لا يوجد سجلات حضور لعرضها.</td></tr>';
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
            var adminTable = $('#admin-attendance-table').DataTable({
                responsive: true,
                dom: 'Bfrtip', // يظهر الأزرار (B), البحث (f), معلومات المعالجة (r), الجدول (t), معلومات الجدول (i), وترقيم الصفحات (p)
                buttons: [
                    'copy', 'csv', 'excel', 'pdf', 'print' // تفعيل أزرار التصدير
                ],
                order: [[ 3, "desc" ]], // الترتيب الافتراضي حسب وقت الحضور (العمود الرابع) الأحدث
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/ar.json', // ملف اللغة العربية
                     search: "بحث:" // تخصيص نص البحث
                },
                // تحديد الأعمدة القابلة للبحث (افتراضياً كل الأعمدة قابلة للبحث)
                // columnDefs: [
                //     { targets: [0, 1, 2, 3], searchable: true }, // ID, Username, Date, Time
                //     { targets: [4], searchable: false } // Points not searchable by default text search
                // ]
                // الفرز متاح لجميع الأعمدة بشكل افتراضي بما فيها النقاط
            });

            // --- (اختياري) كود إضافي لفلاتر مخصصة ---
            // مثال لفلترة حسب التاريخ (يتطلب حقول الإدخال في HTML أعلاه)
            /*
            $('#apply-filters').on('click', function() {
                var startDate = $('#filter-date-start').val();
                var endDate = $('#filter-date-end').val();

                // تطبيق الفلترة على عمود التاريخ (افترض أنه العمود الثالث، يبدأ العد من 0)
                $.fn.dataTable.ext.search.push(
                    function( settings, data, dataIndex ) {
                        var date = data[2]; // الحصول على التاريخ من العمود الثالث
                        if (
                            ( startDate === "" || date >= startDate ) &&
                            ( endDate === "" || date <= endDate )
                        ) {
                            return true;
                        }
                        return false;
                    }
                );
                adminTable.draw(); // إعادة رسم الجدول لتطبيق الفلتر
                $.fn.dataTable.ext.search.pop(); // إزالة الفلتر لمنع تداخله مع عمليات البحث الأخرى
            });
            */

        } catch (e) {
            console.error("Error initializing DataTables for admin attendance:", e);
            // يمكنك عرض رسالة خطأ للمستخدم هنا إذا لزم الأمر
            $('.admin-attendance-log-container').prepend('<p class="smc-error-message">حدث خطأ أثناء تحميل جدول السجلات التفاعلي.</p>');
        }
    } else {
        console.warn("DataTables library not found for admin attendance.");
         $('.admin-attendance-log-container').prepend('<p class="smc-error-message">لم يتم تحميل مكتبة الجداول التفاعلية (DataTables).</p>');
    }
});
</script>

<style>
/* أضف تنسيقات DataTables إذا لم تكن موجودة بشكل عام */
.smc-error-message { color: #dc3545; background-color: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; border-radius: 5px; margin-bottom: 15px; }
/* تحسين مظهر أزرار DataTables */
.dt-buttons .dt-button {
    background-color: #007bff !important;
    color: white !important;
    border: 1px solid #007bff !important;
    border-radius: 4px !important;
    padding: 5px 10px !important;
    margin: 0 2px 5px 2px !important; /* إضافة هامش سفلي */
    transition: background-color 0.3s ease !important;
}
.dt-buttons .dt-button:hover {
    background-color: #0056b3 !important;
    border-color: #0056b3 !important;
}
/* تحسين مظهر حقل البحث */
.dataTables_filter label {
    font-weight: bold;
}
.dataTables_filter input {
    margin-left: 5px;
    border: 1px solid #ccc;
    border-radius: 4px;
    padding: 5px;
}
.smc-button-secondary { /* تنسيق زر العودة للإعدادات */
    background-color: #6c757d;
    border-color: #6c757d;
    color: white;
    padding: 5px 10px;
    text-decoration: none;
    border-radius: 4px;
    display: inline-block;
    margin-top: 5px;
}
.smc-button-secondary:hover {
    background-color: #5a6268;
    border-color: #545b62;
    color: white;
}
.smc-button-secondary i {
    margin-left: 5px;
}
</style>
