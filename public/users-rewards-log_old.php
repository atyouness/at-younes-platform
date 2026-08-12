<?php
/**
 * Template Name: Users Rewards Log (Admin)
 * Description: Displays the rewards log for all users (Admin view). // تم تعديل الوصف
 */

if (!defined('ABSPATH')) exit; // Exit if accessed directly

// Redirect if not logged in or not an admin
if (!is_user_logged_in() || !current_user_can('administrator')) { // التأكد من أنه مسؤول
    wp_redirect(home_url('/')); // Or login page
    exit;
}

get_header();
// $user_id = get_current_user_id(); // لا نحتاج ID المستخدم الحالي هنا
$is_admin = true; // التأكيد أنه عرض للمسؤول
?>

<div class="container rewards-log-container">
    <h2><i class="fas fa-coins"></i> سجل إجمالي الأرباح والمكافآت (للمسؤول)</h2> <?php // تعديل العنوان ليعكس الدمج ?>

    <!-- Log Controls -->
    <div class="smc-log-controls" style="margin-bottom: 10px; padding: 15px; background-color: #f9f9f9; border: 1px solid #eee; border-radius: 5px;">
        <p><a href="<?php echo esc_url(home_url('/smc-settings/')); ?>" class="smc-button smc-button-secondary"><i class="fas fa-cog"></i> العودة إلى إعدادات SMC</a></p>
        <p><strong>أدوات التحكم بالسجل:</strong></p>
        <p>يمكنك استخدام حقل "بحث" أدناه للبحث عن طريق معرف المستخدم، اسم المستخدم، نوع المكافأة، أو المصدر. يمكنك أيضًا فرز الأعمدة بالضغط على رؤوسها.</p>
        <p>أزرار التصدير (Copy, CSV, Excel, PDF, Print) متاحة أعلى الجدول.</p>
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

    <table id="admin-rewards-log-table" class="display compact stripe hover smc-log-table" style="width:100%"> <?php // تغيير ID الجدول ?>
        <thead>
            <tr>
                <?php // التأكد من وجود أعمدة المسؤول ?>
                <th>معرف المستخدم</th>
                <th>اسم المستخدم</th>
                <th>تاريخ المكافأة</th>
                <th>نوع المكافأة</th>
                <th>المبلغ (دج)</th> <?php // المبلغ الفعلي للمكافأة أو الربح ?>
                <th>تفاصيل</th> <?php // New column ?>
                <th>إسم مستخدم المحال/المصدر</th> <?php // New column ?>
                <th>مستوي</th> <?php // New column ?>
            </tr>
        </thead>
        <tfoot> <?php // *** إضافة تذييل الجدول *** ?>
            <tr>
                <th>معرف المستخدم</th>
                <th>اسم المستخدم</th>
                <th>تاريخ المكافأة</th>
                <th>نوع المكافأة</th>
                <th>المبلغ (دج)</th>
                <th>تفاصيل</th>
                <th>إسم مستخدم المحال/المصدر</th>
                <th>مستوي</th>
            </tr>
        </tfoot>
        <tbody>
            <?php
            global $wpdb;
            $ad_deals_log_table = $wpdb->prefix . 'smc_ad_deals_log';
            $rewards_log_table = $wpdb->prefix . 'smc_rewards_log';

            // بناء الاستعلام المدمج
            $earnings_query_sql = "
                (SELECT
                    l.user_id, u.user_login,
                    l.completion_timestamp AS event_timestamp,
                    l.net_profit AS earned_amount,
                    CONVERT('Ad Task Profit' USING utf8mb4) AS earning_type,
                    CONVERT(l.ad_name USING utf8mb4) AS details,
                    NULL AS source_user_id,      -- No direct source user for ad profit in this context
                    NULL AS reward_related_info  -- No reward-specific related_info
                FROM {$ad_deals_log_table} l
                LEFT JOIN {$wpdb->users} u ON l.user_id = u.ID)

                UNION ALL

                (SELECT
                    r.user_id, u.user_login,
                    r.reward_timestamp AS event_timestamp,
                    r.amount AS earned_amount,
                    CONVERT(r.reward_type USING utf8mb4) AS earning_type,
                    CONVERT(r.related_info USING utf8mb4) AS details, -- Use related_info for details from rewards
                    r.source_user_id,
                    r.related_info AS reward_related_info -- Keep original related_info for rewards
                FROM {$rewards_log_table} r
                LEFT JOIN {$wpdb->users} u ON r.user_id = u.ID)

                ORDER BY event_timestamp DESC
                LIMIT 1000"; // Limit for performance, consider pagination for very large datasets

            $combined_log = $wpdb->get_results($earnings_query_sql);

            if ($combined_log) {
                foreach ($combined_log as $log_entry) {

                    $username = 'N/A';
                    if (isset($log_entry->user_login) && $log_entry->user_login) {
                        $username = $log_entry->user_login;
                    } elseif (isset($log_entry->user_id)) {
                        $user_info = get_userdata($log_entry->user_id);
                        $username = $user_info ? $user_info->user_login : 'غير معروف (' . $log_entry->user_id . ')';
                    }

                    $earning_type_display = esc_html($log_entry->earning_type);
                    $details_display = esc_html($log_entry->details);
                    $source_username_display = 'N/A';
                    $level_display = 'N/A';

                    // Logic for rewards specific columns
                    if ($log_entry->earning_type !== 'Ad Task Profit') {
                        // Extract level from reward_type (e.g., referral_deposit_l1)
                        if (preg_match('/_l(\d)$/', $log_entry->earning_type, $matches)) {
                            $level_display = $matches[1];
                        }

                        // Get source user if exists (from rewards log)
                        if (isset($log_entry->source_user_id) && $log_entry->source_user_id) {
                            $source_user_info = get_userdata($log_entry->source_user_id);
                            $source_username_display = $source_user_info ? $source_user_info->user_login : ('ID: ' . $log_entry->source_user_id);
                            // Prepend source to details if not already there (for rewards)
                            $reward_details_from_log = isset($log_entry->reward_related_info) ? esc_html($log_entry->reward_related_info) : '';
                            if (strpos($reward_details_from_log, 'المصدر:') === false && strpos($reward_details_from_log, 'Invitee Net Profit:') === false && strpos($reward_details_from_log, 'Deposit Amount:') === false) {
                                $details_display = 'المصدر: ' . $source_username_display . ($reward_details_from_log ? ' - ' . $reward_details_from_log : '');
                            } else {
                                $details_display = $reward_details_from_log;
                            }
                        }

                        // Specific details for certain reward types
                        if ($log_entry->earning_type === 'monthly_salary' && !empty($log_entry->reward_related_info)) {
                            $details_display = 'راتب شهري لـ ' . esc_html($log_entry->reward_related_info);
                        }
                    } else { // For 'Ad Task Profit'
                        // $details_display is already ad_name
                        $source_username_display = 'N/A'; // No direct source user for ad profit
                        $level_display = 'N/A';         // No level for ad profit
                    }

                    echo '<tr>';
                    echo '<td>' . esc_html($log_entry->user_id) . '</td>';
                    echo '<td>' . esc_html($username) . '</td>';
                    echo '<td>' . esc_html(date_i18n('Y-m-d H:i', strtotime($log_entry->event_timestamp))) . '</td>';
                    echo '<td>' . $earning_type_display . '</td>';
                    echo '<td><span dir="ltr">' . esc_html(number_format((float)$log_entry->earned_amount, 2, '.', '')) . ' دج</span></td>';
                    echo '<td>' . $details_display . '</td>';
                    echo '<td>' . esc_html($source_username_display) . '</td>';
                    echo '<td>' . esc_html($level_display) . '</td>';
                    echo '</tr>';
                }
            } else {
                $colspan = 8; // Updated colspan for the new columns
                echo '<tr><td colspan="' . $colspan . '">لا توجد أرباح أو مكافآت مسجلة لعرضها.</td></tr>';
            }
            ?>
        </tbody>
    </table>

    <!-- Summary Section -->
    <div id="summary-rewards-results" class="smc-summary-section" style="margin-top: 20px; padding: 15px; background-color: #e9f5e9; border: 1px solid #c8e6c9; border-radius: 5px;">
        <h4><i class="fas fa-calculator"></i> ملخص الفترة المحددة:</h4>
        <div class="summary-grid">
+            <div><strong>مجموع قيمة الأرباح/المكافآت:</strong> <span id="sum-reward-amount">0.00</span> دج</div>
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
            table = $('#admin-rewards-log-table').DataTable({ // استخدام ID الجدول الجديد وإسناده
                responsive: true,
                dom: 'Bfrtip', // Buttons, filter, processing, table, info, pagination
                buttons: [
                    'copy', 'csv', 'excel', 'pdf', 'print'
                ],
                order: [[ 2, "desc" ]], // الترتيب الافتراضي حسب التاريخ الأحدث (العمود الثالث الآن)
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/ar.json',
                    search: "بحث في السجلات:" // تخصيص نص البحث
                },
                // *** إضافة: تعريف الأعمدة لتحسين الفرز والبحث ***
                columnDefs: [
                    // 0: User ID, 1: Username, 2: Date, 3: Reward Type, 4: Amount, 5: Details, 6: Source User, 7: Level
                    { targets: [0, 1, 3, 5, 6, 7], type: 'string' }, // String types
                    { targets: 2, type: 'date' },     // Date type
                    { targets: 4, type: 'num-fmt' }   // Numeric formatted for amount
                ],
                search: {
                    caseInsensitive: true // جعل البحث غير حساس لحالة الأحرف
                }
            });

            // --- دالة حساب ملخص المكافآت ---
            function calculateRewardSummary(table) {
                let sumRewardAmount = 0;

                table.rows({ search: 'applied' }).every(function() {
                    const data = this.data();
                    const parseCurrency = (value) => parseFloat(String(value).replace(/[^0-9.-]+/g,"")) || 0;
                    sumRewardAmount += parseCurrency(data[4]); // العمود الخامس (الفهرس 4) هو "المبلغ (دج)"
                });

                $('#sum-reward-amount').text(sumRewardAmount.toFixed(2));
            }

            // --- فلترة التاريخ ---
            $.fn.dataTable.ext.search.push(
                function( settings, data, dataIndex ) {
                    const startDateStr = $('#start-date').val();
                    const endDateStr = $('#end-date').val();
                    const dateStr = data[2]; // العمود الثالث (الفهرس 2) هو تاريخ المكافأة

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
            $('#admin-rewards-log-table tfoot th').each(function(i) {
                var title = $(this).text();
                // لا نضف فلتر لعمود المبلغ أو معرف المستخدم حاليًا بهذا المثال البسيط
                // الفهارس: 0:ID, 1:Username, 2:Date, 3:Type, 4:Amount, 5:Details, 6:Source User, 7:Level
                // لا نضيف فلتر لعمود ID أو التاريخ (له فلتر عام)
                if (i === 0 || i === 2) {
                    $(this).html(''); 
                    return;
                }
                // الأعمدة التي نريد لها بحث: اسم المستخدم (1)، نوع المكافأة (3)، المبلغ (4)، تفاصيل (5)، اسم مستخدم المحال (6)، المستوى (7)
                // if (i === 1 || i === 3 || i === 4 || i === 5 || i === 6 || i === 7) {                
                $(this).html('<input type="text" placeholder="بحث في ' + title + '" class="column-search" style="width:100%;"/>');

                $('input', this).on('keyup change clear', function() {
                    if (table.column(i).search() !== this.value) {
                        table
                            .column(i)
                            .search(this.value)
                            .draw();
                    }
                });
                // } else {
                //     $(this).html(''); // اترك الأعمدة الأخرى بدون فلتر
                // }
            });
            // --- نهاية إضافة فلاتر لكل عمود ---

        } catch (e) {
            console.error("Error initializing DataTables for admin rewards log:", e);
        }
    } else {
        console.warn("DataTables library not found for admin rewards log.");
    }
});
</script>

<style>
/* يمكنك إضافة تنسيقات DataTables هنا إذا لزم الأمر */
.smc-button-secondary { /* تنسيق زر العودة للإعدادات */
    background-color: #6c757d;
    border-color: #6c757d;
    color: white;
    padding: 5px 10px;
    text-decoration: none;
    border-radius: 4px;
    display: inline-block;
    font-size: 0.9em;
    /* margin-top: 5px; // تم نقله إلى الرابط مباشرة */
}
.smc-button-secondary:hover {
    background-color: #5a6268;
    border-color: #545b62;
    color: white;
}
.smc-button-secondary i {
    margin-left: 5px;
}
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
