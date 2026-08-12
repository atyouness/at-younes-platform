<?php
/**
 * Template Name: Users Deposit Log
 * Description: Displays the deposit Log for administrators. // تم تعديل الوصف
 */

// التأكد من أن WordPress هو من يقوم بتحميل هذا الملف
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// تضمين ملف header.php
get_header();
?>

<div class="container smc-log-container"> <?php // Added class for consistent styling ?>
    <?php
    // التحقق من تسجيل دخول المستخدم وصلاحيات المسؤول
    if (is_user_logged_in() && current_user_can('administrator')) {
    ?>

        <h2>📜 سجل عمليات الإيداع</h2>
        <a href="<?php echo esc_url(home_url('/smc-settings/')); ?>" class="smc-button smc-button-secondary" style="margin-bottom: 15px; display: inline-block;"><i class="fas fa-cog"></i> العودة إلى إعدادات SMC</a>

        <!-- منطقة لأدوات البحث والفرز والتصفية والتصدير (سيتم إضافتها لاحقًا) -->
        <div class="smc-log-controls" style="margin-bottom: 20px; padding: 15px; background-color: #f9f9f9; border: 1px solid #eee; border-radius: 5px;">
            <p><strong>أدوات التحكم بالسجل:</strong></p>
            <p>يمكنك استخدام حقل "بحث" أدناه للبحث في السجلات. يمكنك أيضًا فرز الأعمدة وتصدير البيانات باستخدام الأزرار.</p>
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

          <!-- Column Search Section -->
    <div class="smc-column-search-section" style="margin-bottom: 20px; padding: 15px; background-color: #f0f8ff; border: 1px solid #d1e7fd; border-radius: 5px;">
        <p><strong>بحث مخصص:</strong></p>
        <input type="text" id="username-search" placeholder="بحث في اسم المستخدم" style="margin-bottom: 5px; width: 200px; padding: 5px; border: 1px solid #ccc; border-radius: 4px;">
        <input type="text" id="user-id-search" placeholder="بحث في معرف المستخدم" style="margin-bottom: 5px; width: 200px; padding: 5px; border: 1px solid #ccc; border-radius: 4px;">
        <input type="text" id="deposit-amount-search" placeholder="بحث في قيمة الإيداع" style="margin-bottom: 5px; width: 200px; padding: 5px; border: 1px solid #ccc; border-radius: 4px;">
        <input type="text" id="last-deposit-date-search" placeholder="بحث في تاريخ آخر إيداع" style="margin-bottom: 5px; width: 200px; padding: 5px; border: 1px solid #ccc; border-radius: 4px;">
        <input type="text" id="deposit-status-search" placeholder="بحث في حالة الوديعة" style="margin-bottom: 5px; width: 200px; padding: 5px; border: 1px solid #ccc; border-radius: 4px;">
        <input type="text" id="deposit-type-search" placeholder="بحث في نوع الوديعة" style="margin-bottom: 5px; width: 200px; padding: 5px; border: 1px solid #ccc; border-radius: 4px;">
    </div>

        <table id="admin-deposits-log-table" class="display compact stripe hover smc-log-table" style="width:100%"> <?php // Added ID and classes ?>
        <thead>
            <tr>
                <th>اسم المستخدم</th>
                <th>معرف المستخدم</th>
                <th>تاريخ الطلب</th>
                <th>قيمة الإيداع</th>
                <th>طريقة الإيداع</th>
                <th>الحالة</th>
                <th>نوع الوديعة</th>
                <th>عرض الملف الشخصي</th>
                <th>فريق</th>

            </tr>
        </thead>
        <tbody>
            <?php
            global $wpdb;
            $table_name = $wpdb->prefix . 'user_deposits';
            // جلب جميع الإيداعات مرتبة حسب التاريخ الأحدث أولاً
            // Fetch all configured investment types to display their titles
            $all_investment_types_config_log = get_option('smc_investment_types_settings', []);

            $deposits = $wpdb->get_results("SELECT * FROM $table_name ORDER BY deposit_date DESC LIMIT 1000"); // Limit for performance

            if ($deposits) {
                foreach ($deposits as $deposit) {
                    $user_info = get_userdata($deposit->user_id);
                    $username = $user_info ? $user_info->user_login : 'مستخدم غير معروف';

                    // تحديد نص الحالة بناءً على القيمة
                    $status_text = '';
                    switch ($deposit->status) {
                        case 'pending':
                            $status_text = 'انتظار';
                            break;
                        case 'approved':
                            $status_text = 'موافقة';
                            break;
                        case 'rejected':
                            $status_text = 'رفض';
                            break;
                        case 'cancelled_by_admin_refunded': // *** إضافة حالة جديدة ***
                            $status_text = 'ملغى (تم الاسترداد)';
                            break;
                        default:
                            $status_text = esc_html($deposit->status); // عرض القيمة كما هي إذا كانت غير متوقعة
                    }

                    // --- Correctly display deposit type ---
                    $deposit_type_display = '';
                    if ($deposit->deposit_type === 'daily_tasks') {
                        $deposit_type_display = 'مهام يومية';                    
                    } elseif (isset($all_investment_types_config_log[$deposit->deposit_type])) {
                        $investment_config = $all_investment_types_config_log[$deposit->deposit_type];
                        $deposit_type_display = esc_html($investment_config['title'] ?? $deposit->deposit_type); // Display title or key

                        // Append package details if available
                        if ($deposit->investment_package) { // Check if it's an investment with package details
                            $package_name = str_replace('_', ' ', $deposit->investment_package);
                            $shares = $deposit->investment_shares ?? 'N/A';
                            $deposit_type_display .= " (باقة: " . esc_html($package_name) . " | حصص: " . esc_html($shares) . ")";
                        }
                    } else {
                        $deposit_type_display = esc_html($deposit->deposit_type) . ' (مشروع محذوف)'; // Fallback to raw key if not found
                    }

                    $profile_link = esc_url(home_url('/user/' . $username . '/'));
                    $team_link_url = esc_url(home_url('/user-downline-tree/?view_user_id=' . $deposit->user_id));

                    echo "<tr>";
                    echo "<td>" . esc_html($username) . "</td>";
                    echo "<td>" . esc_html($deposit->user_id) . "</td>";
                    echo "<td>" . esc_html(date_i18n('Y-m-d H:i', strtotime($deposit->deposit_date))) . "</td>"; // تنسيق التاريخ
                    echo "<td><span dir='ltr'>" . esc_html(number_format($deposit->amount, 2)) . " دج</span></td>"; // تنسيق المبلغ
                    // --- Re-enable translation with check ---
                    if (function_exists('translate_payment_method_smc')) {
                        echo "<td>" . esc_html(translate_payment_method_smc($deposit->payment_method)) . "</td>";
                    } else {
                        echo "<td>" . esc_html($deposit->payment_method) . " (Helper N/A)</td>";
                        error_log("SMC Users Deposit Log Error: Function translate_payment_method_smc() not found.");
                    }
                    // --- End re-enable ---
                    echo "<td>" . esc_html($status_text) . "</td>"; // عرض نص الحالة - تم إصلاح خطأ PHP هنا
                    echo "<td>" . esc_html($deposit_type_display) . "</td>";
                    echo '<td><a href="' . $profile_link . '" class="smc-button smc-button-view" target="_blank"><i class="fas fa-eye"></i> عرض</a></td>';
                    echo '<td><a href="' . $team_link_url . '" class="smc-button smc-button-team" target="_blank"><i class="fas fa-users"></i> فريق</a></td>';

                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='9'>لا توجد عمليات إيداع مسجلة لعرضها.</td></tr>"; // Updated colspan
            }
            ?>
        </tbody>
    </table>

    <!-- Summary Section -->
    <div id="summary-admin-deposits-results" class="smc-summary-section" style="margin-top: 20px; padding: 15px; background-color: #e9f5e9; border: 1px solid #c8e6c9; border-radius: 5px;">
        <h4><i class="fas fa-calculator"></i> ملخص الفترة المحددة:</h4>
        <div class="summary-grid">
            <div><strong>مجموع الودائع:</strong> <span id="sum-admin-total-deposits">0.00</span> دج</div>
        </div>
    </div>

    <?php
    } else {
        echo '<p>ليس لديك الصلاحيات الكافية لعرض هذه الصفحة. يرجى تسجيل الدخول كمسؤول.</p>';
    }
    ?>
</div> <?php // نهاية smc-log-container ?>

<?php // إضافة JavaScript لمعالجة الأزرار ?>
<script type="text/javascript">
jQuery(document).ready(function($) {
    if ($.fn.DataTable) {
        try {
            var table = $('#admin-deposits-log-table').DataTable({
                responsive: true,
                dom: 'Bfrtip',
                buttons: ['copy', 'csv', 'excel', 'pdf', 'print'],
                order: [[2, "desc"]], // Order by deposit date
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/ar.json',
                    search: "بحث في الإيداعات:"
                },
                columnDefs: [
                    // 0: Username, 1: User ID, 2: Date, 3: Amount, 4: Method, 5: Status,
                    // 6: Deposit Type, 7: Profile, 8: Team
                    { targets: [7, 8], orderable: false, searchable: false } // Profile and Team buttons (Indices 7 and 8)
                 ]
            });

            function calculateAdminDepositSummary(tableInstance) {
                let sumTotalDeposits = 0;
                tableInstance.rows({ search: 'applied' }).every(function() {
                    const data = this.data();
                    const parseCurrency = (value) => parseFloat(String(value).replace(/[^0-9.-]+/g,"")) || 0;
                    sumTotalDeposits += parseCurrency(data[3]); // Column index for "قيمة الإيداع" is 3
                });
                $('#sum-admin-total-deposits').text(sumTotalDeposits.toFixed(2));
            }

            calculateAdminDepositSummary(table);
            table.on('draw.dt', function() {
                calculateAdminDepositSummary(table);
            });

        } catch (e) {
            console.error("Error initializing DataTables for admin deposits log:", e);
            $('.smc-log-container').prepend('<p class="smc-error-message">حدث خطأ أثناء تحميل جدول السجلات التفاعلي.</p>');
        }
    } else {
        console.warn("DataTables library not found for admin deposits log.");
        $('.smc-log-container').prepend('<p class="smc-error-message">لم يتم تحميل مكتبة الجداول التفاعلية (DataTables).</p>');
    }

    // The approve/reject buttons were removed from this page as per the request.
    // If they were to be re-added, the AJAX handler would be similar to proof-payment-record.php
    // or the original users-deposit-log.php before this modification.

      // --- Add custom search to column based search ---
        $('#username-search').keyup(function() { table.column(0).search($(this).val()).draw(); });
        $('#user-id-search').keyup(function() { table.column(1).search($(this).val()).draw(); });
        $('#deposit-amount-search').keyup(function() { table.column(3).search($(this).val()).draw(); });
        $('#last-deposit-date-search').keyup(function() { table.column(2).search($(this).val()).draw(); }); // Assuming date is the 2nd column now!
        $('#deposit-status-search').keyup(function() { table.column(5).search($(this).val()).draw(); }); // حالة الوديعة - العمود السادس (الفهرس 5)
        $('#deposit-type-search').keyup(function() { table.column(6).search($(this).val()).draw(); }); // نوع الوديعة - العمود السابع (الفهرس 6)
});
</script>

<?php // CSS (يمكن نسخ التنسيقات من صفحات أخرى وتعديلها) ?>
<style>
/* General Log Table Styles */
.smc-log-container { max-width: 1200px; margin: 20px auto; }
.smc-log-table { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 0.9em; }
.smc-log-table th, .smc-log-table td { border: 1px solid #ddd; padding: 6px 8px; text-align: right; vertical-align: middle; }
.smc-column-search-section input { width: 150px; margin: 5px; }
.smc-log-table th { background-color: #f2f2f2; font-weight: bold; }
.smc-log-table tbody tr:nth-child(even) { background-color: #f9f9f9; }
.smc-log-table td span[dir="ltr"] { display: inline-block; }

.smc-button-secondary { background-color: #6c757d; border-color: #6c757d; color: white !important; padding: 5px 10px; text-decoration: none; border-radius: 4px; display: inline-flex; align-items: center; font-size: 0.9em; }
.smc-button-secondary:hover { background-color: #5a6268; border-color: #545b62; color: white !important; }
.smc-button-secondary i { margin-left: 5px; }

.smc-date-filter-section label { margin-left: 5px;}
.smc-date-filter-section input {
        padding: 5px; border: 1px solid #ccc; border-radius: 4px;
        margin-bottom: 5px;
}

.smc-button-view, .smc-button-team { background-color: #007bff; border-color: #007bff; color: white !important; padding: 4px 8px; text-decoration: none; border-radius: 4px; display: inline-flex; align-items: center; font-size: 0.85em; margin: 0 2px; }
.smc-button-view:hover, .smc-button-team:hover { background-color: #0056b3; border-color: #0056b3; color: white !important; }
.smc-button-view i, .smc-button-team i { margin-left: 3px; }

.smc-error-message { color: #dc3545; background-color: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; border-radius: 5px; margin-bottom: 15px; }

/* DataTables Controls */
.dt-buttons .dt-button { background-color: #007bff !important; color: white !important; border: 1px solid #007bff !important; border-radius: 4px !important; padding: 5px 10px !important; margin: 0 2px 5px 2px !important; transition: background-color 0.3s ease !important; font-size: 0.9em !important; }
.dt-buttons .dt-button:hover { background-color: #0056b3 !important; border-color: #0056b3 !important; }
.dataTables_filter label { font-weight: bold; font-size: 0.95em; }
.dataTables_filter input { margin-left: 5px; border: 1px solid #ccc; border-radius: 4px; padding: 5px; font-size: 0.95em; }

/* Summary Section Styles */
.smc-summary-section h4 i { margin-left: 8px; color: #28a745; }
.summary-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 10px; }
.summary-grid div { background-color: #fff; padding: 8px; border-radius: 4px; font-size: 0.95em; }
.summary-grid span { font-weight: bold; color: #0056b3; direction: ltr; display: inline-block; }

@import url("https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css");
</style>

<?php
get_footer();
?>
