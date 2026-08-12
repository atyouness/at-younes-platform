<?php
/**
 * Template Name: Proof of Payment Record (Admin)
 * Description: Displays deposit proofs for administrators.
 */

if (!defined('ABSPATH')) exit; // Exit if accessed directly

// Redirect if not logged in or not an admin
if (!is_user_logged_in() || !current_user_can('administrator')) {
    wp_redirect(home_url('/'));
    exit;
}

get_header();
?>

<div class="container smc-log-container">
    <h2><i class="fas fa-receipt"></i> سجل إثباتات الدفع (للمسؤول)</h2>
    <a href="<?php echo esc_url(home_url('/smc-settings/')); ?>" class="smc-button smc-button-secondary" style="margin-bottom: 15px; display: inline-block;"><i class="fas fa-cog"></i> العودة إلى إعدادات SMC</a>

    <!-- Log Controls -->
    <div class="smc-log-controls" style="margin-bottom: 20px; padding: 15px; background-color: #f9f9f9; border: 1px solid #eee; border-radius: 5px;">
        <p><strong>أدوات التحكم بالسجل:</strong></p>
        <p>يمكنك استخدام حقل "بحث" أدناه للبحث عن طريق اسم المستخدم، طريقة الدفع، أو الحالة. يمكنك أيضًا فرز الأعمدة وتصدير البيانات.</p>
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

    <table id="admin-proof-payment-table" class="display compact stripe hover smc-log-table" style="width:100%">
        <thead>
            <tr>
                <th>تاريخ الطلب</th>
                <th>اسم المستخدم</th>
                <th>المبلغ (دج)</th>
                <th>طريقة الدفع</th>
                <th>نوع الوديعة</th> <?php // New column ?>
                <th>الحالة</th>
                <th>إثبات الدفع</th>
                <th>الإجراء</th> <?php // نفس عمود الإجراءات من سجل الإيداع ?>
            </tr>
        </thead>
        <tfoot> <?php // Added footer for column search ?>
            <tr>
                <th><input type="text" placeholder="بحث في تاريخ الطلب" class="column-search"/></th>
                <th><input type="text" placeholder="بحث في اسم المستخدم" class="column-search"/></th>
                <th><input type="text" placeholder="بحث في المبلغ" class="column-search"/></th>
                <th><input type="text" placeholder="بحث في طريقة الدفع" class="column-search"/></th>
                <th><input type="text" placeholder="بحث في نوع الوديعة" class="column-search"/></th>
                <th><input type="text" placeholder="بحث في الحالة" class="column-search"/></th>
                <th></th> <?php // No search for proof column ?>
                <th></th> <?php // No search for action column ?>
            </tr>
        </tfoot>      
        <tbody>
            <?php
            global $wpdb;
            $table_name = $wpdb->prefix . 'user_deposits';
            $upload_dir = wp_upload_dir(); // للحصول على رابط مجلد الرفع
            
            // Fetch all configured investment types to display their titles
            $all_investment_types_config_log = get_option('smc_investment_types_settings', []);

            // جلب الإيداعات التي لها مسار إثبات وغير فارغ، مرتبة حسب الأحدث
            $deposits_with_proof = $wpdb->get_results(
                "SELECT * FROM {$table_name}
                 WHERE deposit_proof_path IS NOT NULL AND deposit_proof_path != ''
                 ORDER BY deposit_date DESC"
            );

            if ($deposits_with_proof) {
                foreach ($deposits_with_proof as $deposit) {
                    $user_info = get_userdata($deposit->user_id);
                    $username = $user_info ? $user_info->user_login : 'مستخدم غير معروف';

                    // تحديد نص الحالة
                    $status_text = '';
                    switch ($deposit->status) {
                        case 'pending_admin_approval': $status_text = 'انتظار موافقة المسؤول'; break;
                        case 'approved': $status_text = 'موافقة'; break;
                        case 'rejected': $status_text = 'رفض'; break;
                        case 'withdrawal_scheduled': $status_text = 'سحب مجدول'; break;
                        case 'cancelled_by_admin_refunded': // *** إضافة حالة جديدة ***
                            $status_text = 'ملغى (تم الاسترداد)';
                            break;
                        default: $status_text = esc_html($deposit->status);
                    }

                    // بناء رابط الصورة
                    $proof_url = '';
                    if ($deposit->deposit_proof_path) {
                        // استبدال مسار النظام بالجزء المتعلق بالرابط
                        $relative_path = str_replace($upload_dir['basedir'], '', $deposit->deposit_proof_path);
                        $proof_url = $upload_dir['baseurl'] . $relative_path;
                    }

                    // --- Correctly display deposit type ---
                    $deposit_type_display = '';
                    if ($deposit->deposit_type === 'daily_tasks') {
                        $deposit_type_display = 'مهام يومية';
                    } elseif (isset($all_investment_types_config_log[$deposit->deposit_type])) {
                        $investment_config = $all_investment_types_config_log[$deposit->deposit_type];
                        $deposit_type_display = esc_html($investment_config['title'] ?? $deposit->deposit_type);
                        // Append package details if available for investments
                        if ($deposit->investment_package) {
                            $package_name = str_replace('_', ' ', $deposit->investment_package);
                            $deposit_type_display .= ' (' . esc_html($package_name) . ' - ' . esc_html($deposit->investment_shares ?? 'N/A') . ' حصص)';
                        }
                    } else {
                        $deposit_type_display = esc_html($deposit->deposit_type) . ' (مشروع محذوف)';
                    }

                    echo '<tr>';
                    echo '<td>' . esc_html(date_i18n('Y-m-d H:i', strtotime($deposit->deposit_date))) . '</td>';
                    echo '<td>' . esc_html($username) . '</td>';
                    echo '<td><span dir="ltr">' . esc_html(number_format((float)$deposit->amount, 2)) . ' دج</span></td>';
                    // *** بداية التعديل: التحقق من وجود الدالة ***
                    if (function_exists('translate_payment_method_smc')) {
                        echo '<td>' . esc_html(translate_payment_method_smc($deposit->payment_method)) . '</td>';
                    } else {
                        echo '<td>' . esc_html($deposit->payment_method) . ' (Helper N/A)</td>'; // عرض القيمة الخام إذا لم تكن الدالة موجودة
                        error_log("SMC Proof Payment Log Error: Function translate_payment_method_smc() not found.");
                    }
                    // *** نهاية التعديل ***

                    echo '<td>' . $deposit_type_display . '</td>'; // Display corrected deposit type

                    echo '<td>' . esc_html($status_text) . '</td>';

                    // عرض رابط الصورة (مع صورة مصغرة اختيارية)
                    echo '<td>';
                    if ($proof_url && $deposit->payment_method !== 'profit_balance') { // Don't show proof link for profit_balance                        
                        echo '<a href="' . esc_url($proof_url) . '" target="_blank" title="عرض الإثبات بالحجم الكامل">';
                        echo '<img src="' . esc_url($proof_url) . '" alt="إثبات الدفع" style="max-width: 80px; max-height: 50px; border: 1px solid #ccc; vertical-align: middle;">';
                        echo ' عرض';
                        echo '</a>';
                    } else {
                        echo 'لا يوجد';
                    }
                    echo '</td>';

                    // عمود الإجراءات (نفس منطق users-deposit-log.php)
                    echo '<td class="action-cell">'; // إضافة فئة لتسهيل التحديد
                    if ($deposit->status == 'pending_admin_approval') { // *** تعديل الشرط هنا ***
                        echo '<button class="smc-button approve-button" data-id="' . esc_attr($deposit->id) . '"><i class="fas fa-check"></i> موافقة</button>';
                        echo '<button class="smc-button reject-button" data-id="' . esc_attr($deposit->id) . '"><i class="fas fa-times"></i> رفض</button>';
                    } elseif ($deposit->status == 'withdrawal_scheduled') {
                        // عرض نص الحالة فقط، حيث أن إدارة السحب المجدول تتم من صفحة أخرى
                        echo '<span class="action-status">تم ' . esc_html($status_text) . '</span>';
                    } else {
                        // عرض الحالة النهائية كرسالة نصية
                        $action_status_class = $deposit->status === 'approved' ? 'approved' : ($deposit->status === 'rejected' ? 'rejected' : '');
                        echo '<span class="action-status ' . esc_attr($action_status_class) . '">تم ' . esc_html($status_text) . '</span>';
                    }
                    echo '</td>';

                    echo '</tr>';
                }
            } else {
                echo '<tr><td colspan="8">لا توجد إيداعات مع إثباتات دفع لعرضها.</td></tr>'; // Updated colspan
            }
            ?>
        </tbody>
    </table>

    <!-- Summary Section -->
    <div id="summary-proof-payment-results" class="smc-summary-section" style="margin-top: 20px; padding: 15px; background-color: #e9f5e9; border: 1px solid #c8e6c9; border-radius: 5px;">
        <h4><i class="fas fa-calculator"></i> ملخص الفترة المحددة:</h4>
        <div class="summary-grid">
            <div><strong>مجموع الودائع:</strong> <span id="sum-pending-deposits-proof">0.00</span> دج</div>
        </div>
    </div>

</div>

<?php get_footer(); ?>

<?php // --- JavaScript لتفعيل DataTables ومعالجة الأزرار --- ?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> <?php // تأكد من تضمين SweetAlert ?>
<script type="text/javascript">
jQuery(document).ready(function($) {
    var table; // Define table variable in a broader scope
    if ($.fn.DataTable) {
        try {
            table = $('#admin-proof-payment-table').DataTable({ // Assign to table variable
                responsive: true,
                dom: 'Bfrtip',
                buttons: ['copy', 'csv', 'excel', 'pdf', 'print'],
                order: [[ 0, "desc" ]], // الترتيب حسب تاريخ الطلب
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/ar.json',
                    search: "بحث في السجلات:"
                }
                // Removed initComplete for footer search, will add it differently if needed or use DataTables built-in
            });
        } catch (e) { console.error("Error initializing DataTables for proof payment log:", e); }
    } else { console.warn("DataTables library not found for proof payment log."); }

    // --- Function to calculate and display summary ---
    function calculateSummary(tableInstance) {
        let sumTotalDeposits = 0;
        tableInstance.rows({ search: 'applied' }).every(function() {
            const data = this.data();
            const parseCurrency = (value) => parseFloat(String(value).replace(/[^0-9.-]+/g,"")) || 0;
            sumTotalDeposits += parseCurrency(data[2]); // Column index for "المبلغ (دج)" is 2
        });
        $('#sum-pending-deposits-proof').text(sumTotalDeposits.toFixed(2));
    }

    // --- Date Range Filter ---
    $.fn.dataTable.ext.search.push(
        function( settings, data, dataIndex ) {
            if (settings.nTable.id !== 'admin-proof-payment-table') { // Ensure this filter applies only to this table
                return true;
            }
            const startDateStr = $('#start-date').val();
            const endDateStr = $('#end-date').val();
            const dateStr = data[0]; // Column 0 is "تاريخ الطلب"

            if (!startDateStr && !endDateStr) { return true; }

            const dateParts = dateStr.split(' '); // Assuming format "YYYY-MM-DD HH:MM"
            const cellDate = dateParts.length > 0 ? new Date(dateParts[0]) : null;

            if (!cellDate) return false;

            const startDate = startDateStr ? new Date(startDateStr) : null;
            const endDate = endDateStr ? new Date(endDateStr) : null;

            if (endDate) { endDate.setHours(23, 59, 59, 999); } // Include the whole end day

            if ( (startDate && cellDate < startDate) || (endDate && cellDate > endDate) ) {
                return false;
            }
            return true;
        }
    );

    $('#filter-button').on('click', function() {
        if (table) table.draw();
    });

    $('#clear-filter-button').on('click', function() {
        $('#start-date').val('');
        $('#end-date').val('');
        if (table) table.draw();
    });

    // --- Footer Column Search ---
    $('#admin-proof-payment-table tfoot th .column-search').on('keyup change clear', function() {
        if (table) {
            table.column($(this).parent().index() + ':visible') // Get column index
                 .search(this.value)
                 .draw();
        }
    });

    // معالجة أزرار الموافقة/الرفض (نفس الكود من users-deposit-log.php)
    $('#admin-proof-payment-table tbody').on('click', '.approve-button, .reject-button', function() { // Target tbody for event delegation
        var button = $(this);
        var depositId = button.data('id');
        var action = button.hasClass('approve-button') ? 'approve_deposit' : 'reject_deposit';
        var row = button.closest('tr');
        var actionCell = button.closest('td'); // خلية الإجراءات

        button.prop('disabled', true).siblings('button').prop('disabled', true);
        button.html('<i class="fas fa-spinner fa-spin"></i> جارٍ...'); // تغيير الأيقونة والنص

        $.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: {
                action: 'smc_handle_deposit_action', // نفس الأكشن
                nonce: '<?php echo wp_create_nonce('smc_deposit_action_nonce'); ?>',
                deposit_id: depositId,
                deposit_action: action
            },
            dataType: 'json', // نتوقع استجابة JSON
            success: function(response) {
                if (response.success) {
                    var statusCell = row.find('td').eq(5); // خلية الحالة (تأكد من الترتيب - index 5)
                    // var dateCell = row.find('td:nth-child(X)'); // خلية تاريخ الموافقة (إذا أضفتها)
                    // var adminCell = row.find('td:nth-child(Y)'); // خلية المسؤول (إذا أضفتها)

                    statusCell.text(response.data.new_status_text);
                    // dateCell.text(response.data.approval_date);
                    // adminCell.text(response.data.admin_username);

                    // تحديث خلية الإجراء
                    var actionStatusClass = action === 'approve_deposit' ? 'approved' : 'rejected';
                    actionCell.html('<span class="action-status ' + actionStatusClass + '">تم ' + response.data.new_status_text + '</span>');

                    // تأثير بصري
                    row.css('background-color', action === 'approve_deposit' ? '#d4edda' : '#f8d7da');
                    setTimeout(function() { row.css('background-color', ''); }, 2000);

                    // استخدام SweetAlert للإشعار
                    Swal.fire({
                        icon: 'success',
                        title: 'تم بنجاح!',
                        text: 'تم ' + (action === 'approve_deposit' ? 'الموافقة على' : 'رفض') + ' الإيداع.',
                        timer: 2000,
                        showConfirmButton: false
                    });

                } else {
                    // خطأ من الخادم
                    Swal.fire({
                        icon: 'error',
                        title: 'خطأ!',
                        text: response.data.message || 'حدث خطأ غير متوقع من الخادم.'
                    });
                    // إعادة تفعيل الأزرار
                    button.prop('disabled', false).siblings('button').prop('disabled', false);
                    button.html(button.hasClass('approve-button') ? '<i class="fas fa-check"></i> موافقة' : '<i class="fas fa-times"></i> رفض');
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                // خطأ اتصال AJAX
                 Swal.fire({
                     icon: 'error',
                     title: 'خطأ اتصال!',
                     text: 'حدث خطأ في الاتصال بالخادم. يرجى المحاولة مرة أخرى.'
                 });
                console.error("AJAX Error:", textStatus, errorThrown, jqXHR.responseText);
                // إعادة تفعيل الأزرار
                button.prop('disabled', false).siblings('button').prop('disabled', false);
                button.html(button.hasClass('approve-button') ? '<i class="fas fa-check"></i> موافقة' : '<i class="fas fa-times"></i> رفض');
            }
        });
    });

    // Calculate summary on initial load and after each draw
    if (table) {
        calculateSummary(table);
        table.on('draw.dt', function() {
            calculateSummary(table);
        });
    }
});
</script>

<style>
/* ... (تنسيقات مشابهة للصفحات الأخرى) ... */
.smc-button-secondary { /* ... */ }
.smc-error-message { /* ... */ }
.dt-buttons .dt-button { /* ... */ }
.dataTables_filter label { /* ... */ }
.smc-date-filter-section label { margin-left: 5px; margin-right: 5px;}
.smc-date-filter-section input[type="date"] { padding: 5px; border: 1px solid #ccc; border-radius: 4px; margin-bottom: 5px;}
.smc-log-table tfoot input.column-search { width: 100%; padding: 3px; box-sizing: border-box; font-size: 0.9em; border: 1px solid #ccc; }
.smc-log-table tfoot th { padding: 5px; }
.dataTables_filter input { /* ... */ }
.action-cell { text-align: center; white-space: nowrap; }
.action-cell .smc-button { padding: 5px 8px; font-size: 0.85em; margin: 2px; }
.action-cell .smc-button i { margin-left: 3px; }
.approve-button { background-color: #28a745; border-color: #28a745; color: white; }
.approve-button:hover { background-color: #218838; border-color: #1e7e34; }
.reject-button { background-color: #dc3545; border-color: #dc3545; color: white; }
.reject-button:hover { background-color: #c82333; border-color: #bd2130; }
.action-status { font-weight: bold; padding: 5px; border-radius: 4px; display: inline-block; font-size: 0.9em; }
.action-status.approved { color: #155724; background-color: #d4edda; }
.action-status.rejected { color: #721c24; background-color: #f8d7da; }
/* Summary Section Styles */
.smc-summary-section h4 i { margin-left: 8px; color: #28a745; }
.summary-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 10px; }
.summary-grid div { background-color: #fff; padding: 8px; border-radius: 4px; font-size: 0.95em; }
.summary-grid span { font-weight: bold; color: #0056b3; direction: ltr; display: inline-block; }
@import url("https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css");
</style>
