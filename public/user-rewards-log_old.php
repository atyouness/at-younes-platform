<?php
/**
 * Template Name: User Rewards Log
 * Description: Displays the rewards log for the current user. // تم تعديل الوصف
 */

if (!defined('ABSPATH')) exit; // Exit if accessed directly

// Redirect if not logged in
if (!is_user_logged_in()) {
    wp_redirect(home_url('/')); // Or login page
    exit;
}

get_header();
$user_id = get_current_user_id();
// $is_admin = current_user_can('administrator'); // لا نحتاج للتحقق من المسؤول هنا
?>

<div class="container rewards-log-container">
    <h2><i class="fas fa-trophy"></i> سجل المكافآت الخاص بك</h2> <?php // تعديل العنوان ?>
    <a href="<?php echo esc_url( home_url( '/transactional/' ) ); ?>" class="smc-button smc-button-secondary" style="margin-bottom: 15px; display: inline-block;"><i class="fas fa-arrow-left"></i> العودة إلى معاملاتي</a>
    
    <!-- Log Controls -->
    <div class="smc-log-controls" style="margin-bottom: 10px; padding: 15px; background-color: #f9f9f9; border: 1px solid #eee; border-radius: 5px;">
        <p><strong>أدوات التحكم بالسجل:</strong></p>
        <p>يمكنك استخدام حقل "بحث" أدناه للبحث في سجلاتك. يمكنك أيضًا فرز الأعمدة بالضغط على رؤوسها وتصدير البيانات باستخدام الأزرار.</p>
    </div>

    <!-- Date Filter Section -->
    <div class="smc-date-filter-section" style="margin-bottom: 20px; padding: 15px; background-color: #f0f8ff; border: 1px solid #d1e7fd; border-radius: 5px;">
        <strong>تصفية حسب التاريخ:</strong>
        <label for="start-date">من:</label>
        <input type="date" id="start-date" name="start-date" style="padding: 5px; border: 1px solid #ccc; border-radius: 4px;">
        <label for="end-date" style="margin-right: 10px;">إلى:</label>
        <input type="date" id="end-date" name="end-date" style="padding: 5px; border: 1px solid #ccc; border-radius: 4px;">
        <button id="filter-button" class="smc-button" style="padding: 5px 10px; margin-right: 5px;"><i class="fas fa-filter"></i> تطبيق</button>
        <button id="clear-filter-button" class="smc-button smc-button-secondary" style="padding: 5px 10px;"><i class="fas fa-times"></i> مسح</button>
    </div>

    <table id="user-rewards-log-table" class="display compact stripe hover smc-log-table" style="width:100%"> <?php // تغيير ID الجدول ?>
        <thead>
            <tr>
                <?php // إزالة أعمدة المسؤول ?>
                <th>تاريخ المكافأة</th>
                <th>نوع المكافأة</th>
                <th>المبلغ (دج)</th>
                <th>المصدر/معلومات إضافية</th>
            </tr>
        </thead>
        <tfoot> <?php // *** إضافة تذييل الجدول *** ?>
            <tr>
                <th>تاريخ المكافأة</th>
                <th>نوع المكافأة</th>
                <th>المبلغ (دج)</th>
                <th>المصدر/معلومات إضافية</th>
            </tr>
        </tfoot>        
        <tbody>
            <?php
            global $wpdb;
            $rewards_table = $wpdb->prefix . 'smc_rewards_log';

            // *** تعديل الاستعلام: جلب سجلات المستخدم الحالي فقط ***
            $rewards_query = $wpdb->prepare(
                "SELECT * FROM {$rewards_table} WHERE user_id = %d ORDER BY reward_timestamp DESC",
                $user_id
            );

            $rewards_log = $wpdb->get_results($rewards_query);

            if ($rewards_log) {
                foreach ($rewards_log as $reward) {
                    // لا حاجة لجلب اسم المستخدم هنا

                    // يمكنك إضافة منطق لترجمة أنواع المكافآت إلى نصوص مفهومة
                    $reward_type_display = esc_html($reward->reward_type);
                    $related_info_display = esc_html($reward->related_info);
                    if ($reward->source_user_id) {
                        $source_user_info = get_userdata($reward->source_user_id);
                        $source_username = $source_user_info ? $source_user_info->user_login : $reward->source_user_id;
                        $related_info_display = 'المصدر: ' . $source_username . ($related_info_display ? ' (' . $related_info_display . ')' : '');
                    }

                    echo '<tr>';
                    // إزالة أعمدة المسؤول
                    echo '<td>' . esc_html(date_i18n('Y-m-d H:i', strtotime($reward->reward_timestamp))) . '</td>';
                    echo '<td>' . $reward_type_display . '</td>';
                    // *** تعديل تنسيق المبلغ ***
                    echo '<td><span dir="ltr">' . esc_html(number_format((float)$reward->amount, 2, '.', '')) . ' دج</span></td>';
                    echo '<td>' . $related_info_display . '</td>';
                    echo '</tr>';
                }
            } else {
                $colspan = 4; // عدد الأعمدة للمستخدم
                echo '<tr><td colspan="' . $colspan . '">لا توجد مكافآت مسجلة لعرضها.</td></tr>';
            }
            ?>
        </tbody>
    </table>

    <!-- Summary Section -->
    <div id="summary-rewards-results" class="smc-summary-section" style="margin-top: 20px; padding: 15px; background-color: #e9f5e9; border: 1px solid #c8e6c9; border-radius: 5px;">
        <h4><i class="fas fa-calculator"></i> ملخص الفترة المحددة:</h4>
        <div class="summary-grid">
            <div><strong>مجموع قيمة المكافأة:</strong> <span id="sum-reward-amount">0.00</span> دج</div>
        </div>
    </div>
</div>

<?php get_footer(); ?>

<?php // تفعيل DataTables ?>
<script type="text/javascript">
jQuery(document).ready(function($) {
    var table; // تعريف المتغير table هنا ليكون متاحًا في النطاق الأوسع
    if ($.fn.DataTable) {
        try {
            table = $('#user-rewards-log-table').DataTable({ // استخدام ID الجدول الجديد وإسناده
                responsive: true,
                dom: 'Bfrtip', // التأكد من أن هذا السطر موجود وغير معلق لإظهار الأزرار والبحث
                buttons: [
                    'copy', 'csv', 'excel', 'pdf', 'print'
                ],
                order: [[ 0, "desc" ]], // الترتيب الافتراضي حسب التاريخ الأحدث (العمود الأول الآن)
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/ar.json',
                    search: "بحث في مكافآتك:" // تخصيص نص البحث
                },
                // *** إضافة: تعريف الأعمدة لتحسين الفرز والبحث ***
                columnDefs: [
                    { targets: [1, 3], type: 'string' }, // نوع المكافأة، المصدر كنص
                    { targets: 0, type: 'date' },   // تاريخ المكافأة كتاريخ
                    { targets: 2, type: 'num-fmt' }  // المبلغ كـ numeric formatted
                ]
            });

            // --- دالة حساب ملخص المكافآت ---
            function calculateRewardSummary(table) {
                let sumRewardAmount = 0;

                table.rows({ search: 'applied' }).every(function() {
                    const data = this.data();
                    const parseCurrency = (value) => parseFloat(String(value).replace(/[^0-9.-]+/g,"")) || 0;
                    sumRewardAmount += parseCurrency(data[2]); // العمود الثالث (الفهرس 2) هو "المبلغ (دج)"
                });

                $('#sum-reward-amount').text(sumRewardAmount.toFixed(2));
            }

            // --- فلترة التاريخ ---
            $.fn.dataTable.ext.search.push(
                function( settings, data, dataIndex ) {
                    const startDateStr = $('#start-date').val();
                    const endDateStr = $('#end-date').val();
                    const dateStr = data[0]; // العمود الأول هو تاريخ المكافأة

                    if (!startDateStr && !endDateStr) { return true; }

                    const dateParts = dateStr.split(' ');
                    const cellDate = dateParts.length > 0 ? new Date(dateParts[0]) : null;

                    if (!cellDate) return false;

                    const startDate = startDateStr ? new Date(startDateStr) : null;
                    const endDate = endDateStr ? new Date(endDateStr) : null;

                    if (endDate) { endDate.setHours(23, 59, 59, 999); }

                    if ( (startDate && cellDate < startDate) || (endDate && cellDate > endDate) ) {
                        return false;
                    }
                    return true;
                }
            );

            // --- ربط الأحداث ---
            $('#filter-button').on('click', function() {
                if (table) table.draw();
            });

            $('#clear-filter-button').on('click', function() {
                $('#start-date').val('');
                $('#end-date').val('');
                if (table) table.draw();
            });

            if (table) {
                table.on('draw.dt', function() {
                    calculateRewardSummary(table);
                });
                calculateRewardSummary(table); // الحساب الأولي
            }

            // --- إضافة فلاتر لكل عمود (مثال لفلاتر النصوص) ---
            $('#user-rewards-log-table tfoot th').each(function(i) {
                var title = $(this).text();
                // لا نضف فلتر لعمود المبلغ حاليًا بهذا المثال البسيط
                // الفهرس 2 هو عمود المبلغ (تاريخ المكافأة 0, نوع المكافأة 1, المبلغ 2, المصدر 3)
                if (i === 2) { 
                    $(this).html(''); // اترك خانة المبلغ فارغة في التذييل أو ضع رسالة
                    return;
                }
                // لا نضيف فلتر لعمود التاريخ حاليًا بهذا المثال البسيط، حيث يوجد فلتر تاريخ عام
                if (i === 0) {
                     $(this).html('');
                     return;
                }
                $(this).html('<input type="text" placeholder="بحث في ' + title + '" class="column-search" style="width:100%;"/>');

                $('input', this).on('keyup change clear', function() {
                    if (table.column(i).search() !== this.value) {
                        table
                            .column(i)
                            .search(this.value)
                            .draw();
                    }
                });
            });
            // --- نهاية إضافة فلاتر لكل عمود ---

        } catch (e) {
            console.error("Error initializing DataTables for user rewards log:", e);
        }
    } else {
        console.warn("DataTables library not found for user rewards log.");
    }
});
</script>

<style>
/* يمكنك إضافة تنسيقات DataTables هنا إذا لزم الأمر */
.smc-button { /* تنسيق زر العودة */
    background-color: #6c757d;
    border-color: #6c757d;
    color: white;
    padding: 5px 10px;
    text-decoration: none;
    border-radius: 4px;
    display: inline-block;
    margin-top: 5px;
    /* margin-bottom: 15px; // تم نقله إلى الرابط مباشرة */
}
.smc-button:hover {
    background-color: #5a6268;
    border-color: #545b62;
    color: white;
}
.smc-button-secondary {
    background-color: #6c757d;
    border-color: #6c757d;
    color: white;
    padding: 5px 10px;
    text-decoration: none;
    border-radius: 4px;
    display: inline-block;
    font-size: 0.9em;
}
.smc-button-secondary:hover { background-color: #5a6268; border-color: #545b62; color: white; }
.smc-button-secondary i { margin-left: 5px; }

/* تنسيقات DataTables (يمكن نسخها من ملفات أخرى) */
.dt-buttons .dt-button { /* ... */ }
.dataTables_filter label { /* ... */ }
.dataTables_filter input { /* ... */ }
/* تنسيق قسم الملخص */
.smc-summary-section h4 i { margin-left: 8px; color: #28a745; }
.summary-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 10px; }
.summary-grid div { background-color: #fff; padding: 8px; border-radius: 4px; font-size: 0.95em; }
.summary-grid span { font-weight: bold; color: #0056b3; direction: ltr; display: inline-block; }
/* تنسيق فلاتر الأعمدة في التذييل */
.smc-log-table tfoot th {
    padding: 5px; /* تقليل الحشو */
}
.smc-log-table tfoot input.column-search {
    font-size: 0.9em; /* تصغير خط حقل البحث */
    padding: 3px;
    box-sizing: border-box;
}
</style>
