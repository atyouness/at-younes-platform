<?php
/**
 * Template Name: User Deposit Log
 * Description: Displays the deposit log for the current user.
 */

// التأكد من أن WordPress هو من يقوم بتحميل هذا الملف
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// التحقق من تسجيل دخول المستخدم
if (!is_user_logged_in()) {
    // إعادة توجيه المستخدم إلى صفحة تسجيل الدخول الرئيسية أو صفحة أخرى مناسبة
    // تأكد من أن الرابط '/login/' صحيح أو استخدم wp_login_url()
    wp_redirect(home_url('/')); // توجيه للصفحة الرئيسية كمثال
    exit;
}

// تضمين ملف header.php
get_header();
?>

<div class="container">
    <h2>📜 سجل عمليات الإيداع الخاص بك</h2>
     <a href="<?php echo esc_url( home_url( '/transactional/' ) ); ?>" class="smc-button smc-button-secondary" style="margin-bottom: 15px; display: inline-block;"><i class="fas fa-arrow-left"></i> العودة إلى صفحة معاملاتي</a>

    <!-- Log Controls -->
    <div class="smc-log-controls" style="margin-bottom: 20px; padding: 15px; background-color: #f9f9f9; border: 1px solid #eee; border-radius: 5px;">
        <p><strong>أدوات التحكم بالسجل:</strong></p>
        <p>يمكنك استخدام حقل "بحث" أدناه للبحث في سجلات إيداعاتك حسب التاريخ، المبلغ، طريقة الدفع، أو الحالة. يمكنك أيضًا فرز الأعمدة وتصدير البيانات باستخدام الأزرار.</p>
    </div>

    <table id="user-deposit-log-table" class="display compact stripe hover smc-log-table" style="width:100%">
        <thead>
            <tr>
                <th>تاريخ العملية</th>
                <th>قيمة الإيداع</th>
                <th>طريقة الإيداع</th>
                <th>نوع الوديعة</th> <?php // *** عمود جديد *** ?>            
                <th>الحالة</th>
            </tr>
        </thead>
        <tfoot>
            <tr>
                <th>تاريخ العملية</th>
                <th>قيمة الإيداع</th>
                <th>طريقة الإيداع</th>
                <th>نوع الوديعة</th> <?php // *** عمود جديد *** ?>            
                <th>الحالة</th>
            </tr>
        </tfoot>
        <tbody>
            <?php
            global $wpdb;
            $table_name = $wpdb->prefix . 'user_deposits';
            $user_id = get_current_user_id();
            $deposits = $wpdb->get_results(
                $wpdb->prepare(
                    // *** تعديل: جلب حقول نوع الإيداع والاستثمار ***
                    "SELECT deposit_date, amount, payment_method, status, deposit_type, investment_package, investment_shares, investment_duration, expected_daily_roi FROM $table_name WHERE user_id = %d ORDER BY deposit_date DESC",
                    $user_id
                )
            );

            // التحقق من وجود نتائج
            if ($deposits) {
                // المرور على كل سجل إيداع
                foreach ($deposits as $deposit) {
                    // تحديد نص الحالة بناءً على القيمة المخزنة
                    $status_text = '';
                    switch ($deposit->status) {
                        case 'pending':
                            $status_text = 'انتظار';
                            break;
                        case 'approved':
                            $status_text = 'موافقة';
                            break;
                        case 'rejected':
                            $status_text = 'رفض';                            break;
                            break;
                        case 'cancelled_by_admin_refunded': // *** إضافة حالة جديدة ***
                            $status_text = 'ملغى (تم الاسترداد)';
                            break;
                        default:
                            // في حالة وجود قيمة غير متوقعة، يتم عرضها كما هي مع التأمين
                            $status_text = esc_html($deposit->status);
                    }

                    // *** بداية: تحديد نص نوع الوديعة ***
                    $deposit_type_display = '';
                    $raw_deposit_type_key = $deposit->deposit_type;
                    $all_investment_configs_log = get_option('smc_investment_types_settings', []);

                    if ($raw_deposit_type_key === 'daily_tasks') {
                        $deposit_type_display = 'مهام يومية';
                    } elseif (isset($all_investment_configs_log[$raw_deposit_type_key])) {
                        $investment_config = $all_investment_configs_log[$raw_deposit_type_key];
                        $deposit_type_display = esc_html($investment_config['title'] ?? $raw_deposit_type_key);
                        // Append package details if available
                        $package_name = $deposit->investment_package ? str_replace('_', ' ', $deposit->investment_package) : 'غير محدد';
                        $shares = $deposit->investment_shares ?? 'N/A';
                        $deposit_type_display .= " (باقة: " . esc_html($package_name) . " | حصص: " . esc_html($shares) . ")";
                    } else {
                        $deposit_type_display = esc_html($raw_deposit_type_key) . ' (مشروع محذوف)';
                    }
                    // *** نهاية: تحديد نص نوع الوديعة ***

                    // عرض صف الجدول بالبيانات المنسقة
                    echo '<tr>';
                    // تنسيق التاريخ والوقت باستخدام date_i18n
                    echo '<td>' . esc_html(date_i18n('Y-m-d H:i', strtotime($deposit->deposit_date))) . '</td>';
                    // تنسيق المبلغ باستخدام number_format
                    echo '<td>' . esc_html(number_format($deposit->amount, 2)) . ' دج</td>';
                    echo '<td>' . esc_html(function_exists('translate_payment_method_smc') ? translate_payment_method_smc($deposit->payment_method) : $deposit->payment_method) . '</td>'; // استخدام دالة الترجمة
                    echo '<td>' . $deposit_type_display . '</td>'; // *** إضافة خلية نوع الوديعة ***
                    // عرض نص الحالة المترجم
                    echo '<td>' . esc_html($status_text) . '</td>';
                    echo '</tr>';
                } // نهاية حلقة foreach

            } else {
                // في حالة عدم وجود أي سجلات إيداع للمستخدم
                echo '<tr><td colspan="5">لا توجد بيانات إيداع حالية لعرضها.</td></tr>'; // *** تعديل colspan ***
            }
            ?>
        </tbody>
    </table>

    <!-- Summary Section -->
    <div id="summary-deposit-log-results" class="smc-summary-section" style="margin-top: 20px; padding: 15px; background-color: #e9f5e9; border: 1px solid #c8e6c9; border-radius: 5px;">
        <h4><i class="fas fa-calculator"></i> ملخص الفترة المحددة:</h4>
        <div class="summary-grid">
            <div><strong>مجموع الودائع:</strong> <span id="sum-total-deposits">0.00</span> دج</div>
        </div>
    </div>

</div>

<?php
// تضمين ملف footer.php
get_footer();
?>

<script type="text/javascript">
jQuery(document).ready(function($) {
    if ($.fn.DataTable) {
        try {
            var table = $('#user-deposit-log-table').DataTable({
                responsive: true,
                dom: 'Bfrtip',
                buttons: ['copy', 'csv', 'excel', 'pdf', 'print'],
                order: [[0, "desc"]], // الترتيب الافتراضي حسب تاريخ العملية (الأحدث أولاً)
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/ar.json',
                    search: "بحث في إيداعاتك:"
                },
                columnDefs: [
                    { targets: 0, type: 'date' },    // تاريخ العملية
                    { targets: 1, type: 'num-fmt' }, // قيمة الإيداع (الفهرس لم يتغير)
                    { targets: [2, 3, 4], type: 'string' } // طريقة الدفع، نوع الوديعة، الحالة
                 ]
            });

            // --- Function to calculate and display summary ---
            function calculateDepositSummary(tableInstance) {
                let sumTotalDeposits = 0;

                tableInstance.rows({ search: 'applied' }).every(function() { // Iterate over filtered/searched rows
                    const data = this.data();
                    const parseCurrency = (value) => parseFloat(String(value).replace(/[^0-9.-]+/g,"")) || 0;
                    
                    // Column index for "قيمة الإيداع" is 1
                    sumTotalDeposits += parseCurrency(data[1]); 
                });

                $('#sum-total-deposits').text(sumTotalDeposits.toFixed(2));
            }

            // Calculate summary on initial load
            calculateDepositSummary(table);

            // Recalculate summary on table draw (e.g., after search, sort, pagination)
            table.on('draw.dt', function() {
                calculateDepositSummary(table);
            });

        } catch (e) {
            console.error("Error initializing DataTables for user deposit log:", e);
            $('.container').prepend('<p class="smc-error-message">حدث خطأ أثناء تحميل جدول السجلات التفاعلي.</p>');
        }
    } else {
        console.warn("DataTables library not found for user deposit log.");
        $('.container').prepend('<p class="smc-error-message">لم يتم تحميل مكتبة الجداول التفاعلية (DataTables).</p>');
    }
});
</script>

<style>
/* General Log Table Styles (if not already global) */
.smc-log-table { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 0.9em; } /* تصغير الخط العام للجدول */
.smc-log-table th, .smc-log-table td { border: 1px solid #ddd; padding: 6px 8px; text-align: right; vertical-align: middle; } /* تقليل الحشو */
.smc-log-table th { background-color: #f2f2f2; font-weight: bold; }
.smc-log-table tbody tr:nth-child(even) { background-color: #f9f9f9; }
.smc-log-table td span[dir="ltr"] { display: inline-block; }

.smc-button-secondary { background-color: #6c757d; border-color: #6c757d; color: white !important; padding: 5px 10px; text-decoration: none; border-radius: 4px; display: inline-flex; align-items: center; font-size: 0.9em; }
.smc-button-secondary:hover { background-color: #5a6268; border-color: #545b62; color: white !important; }
.smc-button-secondary i { margin-left: 5px; }

.smc-error-message { color: #dc3545; background-color: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; border-radius: 5px; margin-bottom: 15px; }

/* DataTables Controls */
.dt-buttons .dt-button { background-color: #007bff !important; color: white !important; border: 1px solid #007bff !important; border-radius: 4px !important; padding: 5px 10px !important; margin: 0 2px 5px 2px !important; transition: background-color 0.3s ease !important; font-size: 0.9em !important; }
.dt-buttons .dt-button:hover { background-color: #0056b3 !important; border-color: #0056b3 !important; }
.dataTables_filter label { font-weight: bold; font-size: 0.95em; }
.dataTables_filter input { margin-left: 5px; border: 1px solid #ccc; border-radius: 4px; padding: 4px; font-size: 0.9em; } /* تصغير حقل البحث */

/* Summary Section Styles */
.smc-summary-section h4 i { margin-left: 8px; color: #28a745; }
.summary-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 10px; }
.summary-grid div { background-color: #fff; padding: 8px; border-radius: 4px; font-size: 0.95em; }
.summary-grid span { font-weight: bold; color: #0056b3; direction: ltr; display: inline-block; }

@import url("https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css");
</style>