<?php
/**
 * Template Name: Record Total User Earnings Types
 * Description: Displays a consolidated log of all earnings types. Shows current user's log by default, or all users' logs for administrators.
 */

// التأكد من أن WordPress هو من يقوم بتحميل هذا الملف
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// التحقق من تسجيل دخول المستخدم
if (!is_user_logged_in()) {
    // إعادة توجيه المستخدم إلى صفحة تسجيل الدخول أو الصفحة الرئيسية
    wp_redirect(home_url('/'));
    exit;
}

// تضمين ملف header.php
get_header();

$current_user_id = get_current_user_id();
$is_admin = current_user_can('administrator'); // التحقق من صلاحيات المسؤول
global $wpdb;

// تحديد أسماء الجداول
$ad_deals_log_table = $wpdb->prefix . 'smc_ad_deals_log';
$rewards_log_table = $wpdb->prefix . 'smc_rewards_log';

// بناء الاستعلام بناءً على دور المستخدم
if ($is_admin) {
    // استعلام المسؤول: جلب البيانات لجميع المستخدمين
    // ملاحظة: قد يكون هذا الاستعلام ثقيلاً جداً على الأنظمة الكبيرة. يجب التفكير في pagination أو حدود زمنية.
    // *** تعديل: استخدام get_results مباشرة بدون prepare لعدم وجود متغيرات ***
// استعلام المسؤول
$earnings_query_sql = "
    (SELECT
        l.user_id, u.user_login,
        l.completion_timestamp AS event_timestamp,
        l.net_profit AS earned_amount,
        CONVERT('Ad Task Profit' USING utf8mb4) AS earning_type, /* <-- إضافة CONVERT */
        CONVERT(l.ad_name USING utf8mb4) AS details             /* <-- إضافة CONVERT */
    FROM {$ad_deals_log_table} l
    LEFT JOIN {$wpdb->users} u ON l.user_id = u.ID)

    UNION ALL

    (SELECT
        r.user_id, u.user_login,
        r.reward_timestamp AS event_timestamp,
        r.amount AS earned_amount,
        CONVERT(r.reward_type USING utf8mb4) AS earning_type,   /* <-- إضافة CONVERT */
        CONVERT(r.related_info USING utf8mb4) AS details        /* <-- إضافة CONVERT */
    FROM {$rewards_log_table} r
    LEFT JOIN {$wpdb->users} u ON r.user_id = u.ID)

    ORDER BY event_timestamp DESC
    LIMIT 1000
";
// استعلام المستخدم العادي
 $earnings_query_prepared = $wpdb->prepare(
    "
    (SELECT
        completion_timestamp AS event_timestamp,
        net_profit AS earned_amount,
        CONVERT('Ad Task Profit' USING utf8mb4) AS earning_type, /* <-- إضافة CONVERT */
        CONVERT(ad_name USING utf8mb4) AS details             /* <-- إضافة CONVERT */
    FROM {$ad_deals_log_table}
    WHERE user_id = %d)

    UNION ALL

    (SELECT
        reward_timestamp AS event_timestamp,
        amount AS earned_amount,
        CONVERT(reward_type USING utf8mb4) AS earning_type,   /* <-- إضافة CONVERT */
        CONVERT(related_info USING utf8mb4) AS details        /* <-- إضافة CONVERT */
    FROM {$rewards_log_table}
    WHERE user_id = %d)

    ORDER BY event_timestamp DESC
    LIMIT 500
    ",
    $current_user_id,
    $current_user_id
);

     // *** تنفيذ الاستعلام المُعد ***
     $earnings_log = $wpdb->get_results($earnings_query_prepared);
}

// *** لا حاجة لتنفيذ الاستعلام مرة أخرى هنا ***
// $earnings_log = $wpdb->get_results($earnings_query); // <--- تم حذف هذا السطر

?>

<div class="container smc-log-container">
    <?php if ($is_admin): ?>
        <h2><i class="fas fa-coins"></i> سجل إجمالي أنواع الأرباح (جميع المستخدمين)</h2>
        <a href="<?php echo esc_url(home_url('/smc-settings/')); ?>" class="smc-button smc-button-secondary" style="margin-bottom: 15px; display: inline-block;"><i class="fas fa-cog"></i> العودة إلى إعدادات SMC</a>
    <?php else: ?>
        <h2><i class="fas fa-coins"></i> سجل إجمالي أنواع الأرباح الخاص بك</h2>
        <a href="<?php echo esc_url( home_url( '/transactional/' ) ); ?>" class="smc-button smc-button-secondary" style="margin-bottom: 15px; display: inline-block;"><i class="fas fa-arrow-left"></i> العودة إلى معاملاتي</a>
    <?php endif; ?>

    <!-- Log Controls -->
    <div class="smc-log-controls" style="margin-bottom: 20px; padding: 15px; background-color: #f9f9f9; border: 1px solid #eee; border-radius: 5px;">
        <p><strong>أدوات التحكم بالسجل:</strong></p>
        <p>يمكنك استخدام حقل "بحث" أدناه للبحث في سجلات الأرباح <?php echo $is_admin ? ' (بما في ذلك اسم المستخدم)' : ''; ?>. يمكنك أيضًا فرز الأعمدة وتصدير البيانات باستخدام الأزرار.</p>
    </div>

    <table id="earnings-log-table" class="display compact stripe hover smc-log-table" style="width:100%">
        <thead>
            <tr>
                <?php if ($is_admin): ?>
                    <th>اسم المستخدم</th> <?php // عمود إضافي للمسؤول ?>
                <?php endif; ?>
                <th>تاريخ/وقت الربح</th>
                <th>نوع الربح</th>
                <th>المبلغ (دج)</th>
                <th>تفاصيل</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // *** بداية الكود المضاف لعرض البيانات ***
            if ($earnings_log) {
                foreach ($earnings_log as $log_entry) {
                    // ترجمة نوع الربح (يمكن توسيعها)
                    $earning_type_display = esc_html($log_entry->earning_type);
                    switch ($log_entry->earning_type) {
                        case 'Ad Task Profit': $earning_type_display = 'ربح مهمة إعلان'; break;
                        case 'referral_deposit_l1': $earning_type_display = 'مكافأة إحالة إيداع (م1)'; break;
                        case 'referral_deposit_l2': $earning_type_display = 'مكافأة إحالة إيداع (م2)'; break;
                        case 'referral_deposit_l3': $earning_type_display = 'مكافأة إحالة إيداع (م3)'; break;
                        case 'daily_task_l1': $earning_type_display = 'مكافأة مهمة يومية (م1)'; break;
                        case 'daily_task_l2': $earning_type_display = 'مكافأة مهمة يومية (م2)'; break;
                        case 'daily_task_l3': $earning_type_display = 'مكافأة مهمة يومية (م3)'; break;
                        case 'investment_l1': $earning_type_display = 'مكافأة استثمار (م1)'; break;
                        // أضف المزيد من الحالات حسب أنواع المكافآت لديك
                    }

                    echo '<tr>';
                    // عرض اسم المستخدم للمسؤول فقط
                    if ($is_admin) {
                        $username_display = isset($log_entry->user_login) && $log_entry->user_login ? esc_html($log_entry->user_login) : ('مستخدم (' . esc_html($log_entry->user_id) . ')');
                        echo '<td>' . $username_display . '</td>';
                    }
                    // تنسيق التاريخ والوقت
                    echo '<td>' . esc_html(date_i18n('Y-m-d H:i', strtotime($log_entry->event_timestamp))) . '</td>';
                    // عرض نوع الربح (المترجم إن أمكن)
                    echo '<td>' . $earning_type_display . '</td>';
                    // تنسيق المبلغ
                    echo '<td><span dir="ltr">' . esc_html(number_format((float)$log_entry->earned_amount, 2, '.', '')) . ' دج</span></td>';
                    // عرض التفاصيل
                    echo '<td>' . esc_html($log_entry->details) . '</td>';
                    echo '</tr>';
                } // نهاية حلقة foreach
            }
            // لا يوجد جزء else هنا، DataTables ستعرض رسالة "لا توجد بيانات"
            // *** نهاية الكود المضاف لعرض البيانات ***
            ?>
        </tbody>
    </table>
</div>

<?php
// تضمين ملف footer.php
get_footer();
?>

<?php // --- JavaScript لتفعيل DataTables --- ?>
<script type="text/javascript">
jQuery(document).ready(function($) {
    // التأكد من تحميل مكتبة DataTables
    if ($.fn.DataTable) {
        try {
            // تحديد الترتيب الافتراضي بناءً على وجود عمود اسم المستخدم
            const defaultOrderColumnIndex = <?php echo $is_admin ? 1 : 0; ?>; // العمود الثاني (التاريخ) للمسؤول، الأول للمستخدم

            $('#earnings-log-table').DataTable({ // تغيير ID الجدول
                responsive: true,
                dom: 'Bfrtip', // Buttons, filter, processing, table, info, pagination
                buttons: [
                    'copy', 'csv', 'excel', 'pdf', 'print' // تفعيل أزرار التصدير
                ],
                order: [[ defaultOrderColumnIndex, "desc" ]], // الترتيب الافتراضي حسب التاريخ الأحدث
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/ar.json', // ملف اللغة العربية
                    search: "بحث في سجلات الأرباح:" // تخصيص نص البحث
                }
            });
        } catch (e) {
            console.error("Error initializing DataTables for earnings log:", e);
            $('.smc-log-container').prepend('<p class="smc-error-message">حدث خطأ أثناء تحميل جدول السجلات التفاعلي.</p>');
        }
    } else {
        console.warn("DataTables library not found for earnings log.");
        $('.smc-log-container').prepend('<p class="smc-error-message">لم يتم تحميل مكتبة الجداول التفاعلية (DataTables).</p>');
    }
});
</script>

<?php // --- CSS (يمكن نسخ التنسيقات من ملفات السجلات الأخرى) --- ?>
<style>
/* General Log Table Styles (if not already global) */
.smc-log-container { max-width: 1100px; margin: 20px auto; }
.smc-log-table { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 0.95em; }
.smc-log-table th, .smc-log-table td { border: 1px solid #ddd; padding: 8px 10px; text-align: right; vertical-align: middle; }
.smc-log-table th { background-color: #f2f2f2; font-weight: bold; }
.smc-log-table tbody tr:nth-child(even) { background-color: #f9f9f9; }
.smc-log-table td span[dir="ltr"] { display: inline-block; } /* Ensure LTR for numbers */

/* Back button */
.smc-button-secondary {
    background-color: #6c757d; border-color: #6c757d; color: white; padding: 5px 10px;
    text-decoration: none; border-radius: 4px; display: inline-block; font-size: 0.9em;
}
.smc-button-secondary:hover { background-color: #5a6268; border-color: #545b62; color: white; }
.smc-button-secondary i { margin-left: 5px; }

/* Error message style */
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

@import url("https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css");
</style>
