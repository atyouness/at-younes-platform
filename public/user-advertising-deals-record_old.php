<?php
/**
 * Template Name: User Advertising Deals Record
 * Description: Displays the User Advertising Deals Record.
 */

if (!defined('ABSPATH')) exit; // Exit if accessed directly
if (!is_user_logged_in()) { wp_redirect(home_url('/')); exit; }

get_header();
$user_id = get_current_user_id();
?>

<div class="container smc-log-container">
    <h2><i class="fas fa-chart-line"></i> سجل صفقات الإعلانات المكتملة</h2>
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

    <table id="user-ad-deals-table" class="display compact stripe hover smc-log-table" style="width:100%">
        <thead>
            <tr>
                <th>تاريخ الإكمال</th>
                <th>اسم الإعلان</th>
                <th>معرف الصفقة</th> <?php // *** إضافة عمود جديد *** ?>
                <th>سعر الإعلان (دج)</th>
                <th>ق.م للإعلان (دج)</th>
                <th>سعر صافي (دج)</th>
                <th>ربح الإعلان (دج)</th>
                <th>ق.م للربح (دج)</th>
                <th>ربح صافي (دج)</th>
                <th>فائدتك (دج)</th>
                <th>نسبة الربح (%)</th>
                <th>المدة (ث)</th>
                <th>ق.م للصفقة (دج)</th>
            </tr>
        </thead>
        <tbody>
            <?php
            global $wpdb;
            $ad_deals_log_table = $wpdb->prefix . 'smc_ad_deals_log';

            if($wpdb->get_var("SHOW TABLES LIKE '$ad_deals_log_table'") == $ad_deals_log_table) {
                // *** تعديل: جلب deal_id أيضًا ***
                $deals = $wpdb->get_results($wpdb->prepare(
                    "SELECT * FROM {$ad_deals_log_table} WHERE user_id = %d ORDER BY completion_timestamp DESC",
                    $user_id
                ));

                if ($deals) {
                    foreach ($deals as $deal) {
                        echo '<tr>';
                        echo '<td>' . esc_html(date_i18n('Y-m-d H:i', strtotime($deal->completion_timestamp))) . '</td>';
                        echo '<td>' . esc_html($deal->ad_name) . '</td>';
                        // *** إضافة: عرض deal_id ***
                        echo '<td style="font-family: monospace; color: #dc3545;">' . esc_html($deal->deal_id ?? 'N/A') . '</td>';
                        echo '<td><span dir="ltr">' . esc_html(number_format((float)$deal->ad_price, 2, '.', '')) . '</span></td>';
                        echo '<td><span dir="ltr">' . esc_html(number_format((float)$deal->ad_tax, 2, '.', '')) . '</span></td>';
                        echo '<td><span dir="ltr">' . esc_html(number_format((float)$deal->net_ad_price, 2, '.', '')) . '</span></td>';
                        echo '<td><span dir="ltr">' . esc_html(number_format((float)$deal->profit_value, 2, '.', '')) . '</span></td>';
                        echo '<td><span dir="ltr">' . esc_html(number_format((float)$deal->profit_tax, 2, '.', '')) . '</span></td>';
                        echo '<td><span dir="ltr">' . esc_html(number_format((float)$deal->net_profit, 2, '.', '')) . '</span></td>';
                        echo '<td><span dir="ltr">' . esc_html(number_format((float)$deal->user_benefit, 2, '.', '')) . '</span></td>';
                        echo '<td><span dir="ltr">' . esc_html(number_format((float)$deal->profit_percentage * 100, 3, '.', '')) . '%</span></td>'; // *** تعديل: ضرب النسبة في 100 ***
                        echo '<td>' . esc_html($deal->ad_duration) . '</td>';
                        echo '<td><span dir="ltr">' . esc_html(number_format((float)$deal->deal_tax, 2, '.', '')) . '</span></td>';
                        echo '</tr>';
                    }
                } else {
                    echo '<tr><td colspan="13">لا توجد سجلات صفقات إعلانية مكتملة لعرضها.</td></tr>'; // *** تعديل colspan ***
                }
            } else {
                 echo '<tr><td colspan="13" class="smc-error-message">خطأ: جدول سجل الصفقات غير موجود.</td></tr>'; // *** تعديل colspan ***
            }
            ?>
        </tbody>
    </table>

    <!-- Summary Section -->
    <div id="summary-results" class="smc-summary-section" style="margin-top: 20px; padding: 15px; background-color: #e9f5e9; border: 1px solid #c8e6c9; border-radius: 5px;">
        <h4><i class="fas fa-calculator"></i> ملخص الفترة المحددة:</h4>
        <div class="summary-grid">
            <div><strong>مجموع سعر الإعلان:</strong> <span id="sum-ad-price">0.00</span> دج</div>
            <div><strong>مجموع ق.م للإعلان:</strong> <span id="sum-ad-tax">0.00</span> دج</div>
            <div><strong>مجموع السعر الصافي:</strong> <span id="sum-net-ad-price">0.00</span> دج</div>
            <div><strong>مجموع ربح الإعلان:</strong> <span id="sum-profit-value">0.00</span> دج</div>
            <div><strong>مجموع ق.م للربح:</strong> <span id="sum-profit-tax">0.00</span> دج</div>
            <div><strong>مجموع الربح الصافي:</strong> <span id="sum-net-profit">0.00</span> دج</div>
            <div><strong>مجموع فائدتك:</strong> <span id="sum-user-benefit">0.00</span> دج</div>
            <div><strong>مجموع ق.م للصفقة:</strong> <span id="sum-deal-tax">0.00</span> دج</div>
            <hr style="grid-column: 1 / -1; border-top: 1px dashed #ccc;">
            <div><strong>معدل نسبة الربح:</strong> <span id="avg-profit-percentage">0.000</span> %</div>
            <div><strong>معدل المدة:</strong> <span id="avg-duration">0</span> ثانية</div>
        </div>
    </div>

</div>

<?php get_footer(); ?>

<?php // JavaScript لتفعيل DataTables وحساب الملخص ?>
<script type="text/javascript">
jQuery(document).ready(function($) {
    var table; // تعريف المتغير table هنا ليكون متاحًا في النطاق الأوسع     
    if ($.fn.DataTable) {
        try {
            table = $('#user-ad-deals-table').DataTable({ // إسناد كائن DataTable إلى المتغير table
                responsive: true,
                dom: 'Bfrtip',
                buttons: ['copy', 'csv', 'excel', 'pdf', 'print'],
                order: [[ 0, "desc" ]], // الترتيب حسب تاريخ الإكمال
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/ar.json',
                    search: "بحث في سجلاتك:"
                }
            }); // نهاية تهيئة DataTables

            // --- دالة حساب الملخص ---
            function calculateSummary(table) {
                let sumAdPrice = 0, sumAdTax = 0, sumNetAdPrice = 0, sumProfitValue = 0;
                let sumProfitTax = 0, sumNetProfit = 0, sumUserBenefit = 0, sumDealTax = 0;
                let sumOfPercentageValuesTimes100 = 0, sumDuration = 0, count = 0; 

                table.rows({ search: 'applied' }).every(function() { // المرور على الصفوف المفلترة فقط
                    const data = this.data();
                    count++;

                    // استخلاص القيم الرقمية (إزالة " دج" و "%" والتحويل إلى رقم)
                    const parseCurrency = (value) => parseFloat(String(value).replace(/[^0-9.-]+/g,"")) || 0;
                    const parsePercent = (value) => parseFloat(String(value).replace('%','')) || 0;
                    const parseIntVal = (value) => parseInt(value) || 0;

                    // *** تأكد من أن الفهارس تتطابق مع ترتيب الأعمدة في HTML ***
                    // console.log("Raw data for profit percentage column (user):", data[10]); // *** إضافة تسجيل ***
                    sumAdPrice += parseCurrency(data[3]);        // سعر الإعلان
                    sumAdTax += parseCurrency(data[4]);          // ق.م للإعلان
                    sumNetAdPrice += parseCurrency(data[5]);     // سعر صافي للإعلان
                    sumProfitValue += parseCurrency(data[6]);    // ربح الإعلان
                    sumProfitTax += parseCurrency(data[7]);      // ق.م للربح
                    sumNetProfit += parseCurrency(data[8]);      // ربح صافي
                    sumUserBenefit += parseCurrency(data[9]);    // فائدتك
                    // data[10] هو عمود "نسبة الربح (%)" الذي يعرض القيمة مضروبة في 100 مع علامة %
                    // parsePercent سيزيل % ويعطينا القيمة كما هي معروضة (مثلاً 0.248 إذا كانت 0.248%)
                    let currentPercentage = parsePercent(data[10]); // *** تعديل: تخزين القيمة للتحقق ***
                    // console.log("Parsed percentage (user):", currentPercentage); // *** إضافة تسجيل ***
                    sumOfPercentageValuesTimes100 += currentPercentage;
                    sumDuration += parseIntVal(data[11]);
                    sumDealTax += parseCurrency(data[12]);
                });

                // console.log("Total sum of percentages (user):", sumOfPercentageValuesTimes100, "Count:", count); // *** إضافة تسجيل ***
                const avgProfitPercentage = count > 0 ? (sumOfPercentageValuesTimes100 / count) : 0;
                const avgDuration = count > 0 ? (sumDuration / count) : 0;

                // تحديث عناصر HTML
                $('#sum-ad-price').text(sumAdPrice.toFixed(2));
                $('#sum-ad-tax').text(sumAdTax.toFixed(2));
                $('#sum-net-ad-price').text(sumNetAdPrice.toFixed(2));
                $('#sum-profit-value').text(sumProfitValue.toFixed(2));
                $('#sum-profit-tax').text(sumProfitTax.toFixed(2));
                $('#sum-net-profit').text(sumNetProfit.toFixed(2));
                $('#sum-user-benefit').text(sumUserBenefit.toFixed(2));
                $('#sum-deal-tax').text(sumDealTax.toFixed(2));
                $('#avg-profit-percentage').text(avgProfitPercentage.toFixed(3)); // عرض متوسط نسبة الربح
                $('#avg-duration').text(avgDuration.toFixed(0));
            }

            // --- فلترة التاريخ ---
            $.fn.dataTable.ext.search.push(
                function( settings, data, dataIndex ) {
                    const startDateStr = $('#start-date').val();
                    const endDateStr = $('#end-date').val();
                    const dateStr = data[0]; // العمود الأول هو تاريخ الإكمال

                    if (!startDateStr && !endDateStr) { return true; } // لا يوجد فلتر تاريخ

                    // تحويل التاريخ من YYYY-MM-DD HH:MM إلى كائن Date
                    // يجب التأكد من أن التنسيق في الجدول متوافق
                    const dateParts = dateStr.split(' ');
                    const cellDate = dateParts.length > 0 ? new Date(dateParts[0]) : null; // استخدام جزء التاريخ فقط للمقارنة

                    if (!cellDate) return false; // تجاهل الصف إذا كان التاريخ غير صالح

                    const startDate = startDateStr ? new Date(startDateStr) : null;
                    const endDate = endDateStr ? new Date(endDateStr) : null;

                    // ضبط نهاية اليوم لـ endDate ليشمل اليوم بأكمله
                    if (endDate) { endDate.setHours(23, 59, 59, 999); }

                    if ( (startDate && cellDate < startDate) || (endDate && cellDate > endDate) ) {
                        return false;
                    }
                    return true;
                }
            );

            // --- ربط الأحداث ---
            $('#filter-button').on('click', function() {
                table.draw(); // تطبيق فلتر التاريخ وإعادة رسم الجدول
            });

            $('#clear-filter-button').on('click', function() {
                $('#start-date').val('');
                $('#end-date').val('');
                table.draw(); // إعادة رسم الجدول بدون فلتر التاريخ
            });

            // حساب الملخص عند إعادة رسم الجدول (بسبب الفلترة، البحث، الترقيم)
            table.on('draw.dt', function() {
                calculateSummary(table);
            });

            // حساب الملخص الأولي عند تحميل الصفحة
            calculateSummary(table);

        } catch (e) { console.error("Error initializing DataTables for user ad deals log:", e); }
    } else { console.warn("DataTables library not found for user ad deals log."); }
});
</script>

<style>
/* ... (تنسيقات مشابهة لسجلات أخرى) ... */
.smc-button-secondary { /* ... */ }
.smc-error-message { /* ... */ }
.dt-buttons .dt-button { /* ... */ }
.dataTables_filter label { /* ... */ }
.dataTables_filter input { /* ... */ }
/* تنسيق قسم الملخص */
.smc-summary-section h4 i { margin-left: 8px; color: #28a745; }
.summary-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 10px; }
.summary-grid div { background-color: #fff; padding: 8px; border-radius: 4px; font-size: 0.95em; }
.summary-grid span { font-weight: bold; color: #0056b3; direction: ltr; display: inline-block; }
/* تنسيق قسم فلتر التاريخ */
.smc-date-filter-section {
    display: flex;
    flex-wrap: wrap; /* Allow items to wrap */
    align-items: center; /* Align items vertically */
}
.smc-log-table td span[dir="ltr"] { display: inline-block; }
</style>
