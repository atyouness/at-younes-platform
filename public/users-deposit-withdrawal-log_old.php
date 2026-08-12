<?php
/**
 * Template Name: Users Deposit Withdrawal Log (Admin)
 * Description: Displays the deposit withdrawal Log for administrators.
 */

// Ensure WordPress loads this file
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Check if user is logged in and is an administrator
if (!is_user_logged_in() || !current_user_can('administrator')) {
    wp_redirect(home_url('/'));
    exit;
}

// Include header.php
get_header();
?>

<div class="container smc-log-container">
    <h2><i class="fas fa-undo-alt"></i> سجل سحب الودائع (للمسؤول)</h2>
    <a href="<?php echo esc_url(home_url('/smc-settings/')); ?>" class="smc-button smc-button-secondary" style="margin-bottom: 15px; display: inline-block;"><i class="fas fa-cog"></i> العودة إلى إعدادات SMC</a>

    <!-- Log Controls -->
    <div class="smc-log-controls" style="margin-bottom: 20px; padding: 15px; background-color: #f9f9f9; border: 1px solid #eee; border-radius: 5px;">
        <p><strong>أدوات التحكم بالسجل:</strong></p>
        <p>يمكنك استخدام حقل "بحث" أدناه للبحث في السجلات (باستثناء الطلبات الملغاة). يمكنك أيضًا فرز الأعمدة وتصدير البيانات باستخدام الأزرار.</p>
    </div>

    <table id="admin-deposit-withdrawals-table" class="display compact stripe hover smc-log-table" style="width:100%">
        <thead>
            <tr>
                <th>تاريخ الطلب</th>
                <th>معرف العملية</th>
                <th>اسم المستخدم</th>
                <th>المبلغ (دج)</th>
                <th>رسوم السحب (دج)</th>
                <th>طريقة السحب</th>
                <th>تفاصيل الحساب</th>
                <th>الحالة</th>
                <th>تاريخ الموافقة/الرفض</th>
                <th>المسؤول</th>
                <th>الإجراء</th>
            </tr>
        </thead>
        <tbody>
            <?php
            global $wpdb;
            $table_name = $wpdb->prefix . 'user_withdrawals'; // Correct table name

            // Fetch withdrawals EXCLUDING 'cancelled' status for admin view
            $withdrawals = $wpdb->get_results("SELECT id, user_id, withdrawal_date, amount, fee_amount, payment_method, withdrawal_details, status, approval_date, approved_by FROM $table_name WHERE status != 'cancelled' ORDER BY withdrawal_date DESC");

            if ($withdrawals) {
                foreach ($withdrawals as $withdrawal) {
                    $user_info = get_userdata($withdrawal->user_id);
                    $username = $user_info ? $user_info->user_login : __('مستخدم غير معروف', 'smc');

                    // Fetch admin username who approved/rejected
                    $admin_username = __('N/A', 'smc');
                    if (!empty($withdrawal->approved_by)) {
                        $admin_info_wd = get_userdata($withdrawal->approved_by);
                        $admin_username = $admin_info_wd ? $admin_info_wd->user_login : __('مسؤول غير معروف', 'smc');
                    }

                    // Determine status text and class
                    $status_text = '';
                    $status_class = '';
                    switch ($withdrawal->status) {
                        case 'pending': $status_text = __('انتظار', 'smc'); $status_class = 'status-pending'; break;
                        case 'approved': $status_text = __('موافقة', 'smc'); $status_class = 'status-approved'; break;
                        case 'rejected': $status_text = __('رفض', 'smc'); $status_class = 'status-rejected'; break;
                        // 'cancelled' is excluded by the query, but handle just in case
                        case 'cancelled': $status_text = __('ملغى', 'smc'); $status_class = 'status-cancelled'; break;
                        default: $status_text = esc_html($withdrawal->status); $status_class = 'status-unknown';
                    }

                    echo '<tr>';
                    echo '<td>' . esc_html(date_i18n('Y-m-d H:i', strtotime($withdrawal->withdrawal_date))) . '</td>';
                    echo '<td>' . esc_html($withdrawal->id) . '</td>';
                    echo '<td>' . esc_html($username) . '</td>';
                    echo '<td><span dir="ltr">' . esc_html(number_format((float)$withdrawal->amount, 2, '.', '')) . ' دج</span></td>';
                    echo '<td><span dir="ltr">' . esc_html(number_format((float)$withdrawal->fee_amount, 2, '.', '')) . ' دج</span></td>';
                    echo '<td>' . esc_html($withdrawal->payment_method) . '</td>';
                    echo '<td>' . nl2br(esc_html($withdrawal->withdrawal_details)) . '</td>';
                    echo '<td class="' . esc_attr($status_class) . '">' . esc_html($status_text) . '</td>';
                    echo '<td>' . ($withdrawal->approval_date ? esc_html(date_i18n('Y-m-d H:i', strtotime($withdrawal->approval_date))) : __('N/A', 'smc')) . '</td>';
                    echo '<td>' . esc_html($admin_username) . '</td>';

                    // Action Column
                    echo '<td class="action-cell">';
                    if ($withdrawal->status == 'pending') {
                        // Add data-type for unified JS handler
                        echo '<button class="smc-button approve-button" data-id="' . esc_attr($withdrawal->id) . '" data-type="deposit_withdrawal"><i class="fas fa-check"></i> ' . __('موافقة', 'smc') . '</button>';
                        echo '<button class="smc-button reject-button" data-id="' . esc_attr($withdrawal->id) . '" data-type="deposit_withdrawal"><i class="fas fa-times"></i> ' . __('رفض', 'smc') . '</button>';
                    } else {
                        // Display final status info
                        $action_status_class = $withdrawal->status === 'approved' ? 'approved' : ($withdrawal->status === 'rejected' ? 'rejected' : '');
                        echo '<span class="action-status ' . $action_status_class . '">' . sprintf(__('تم %s', 'smc'), esc_html($status_text)) . '</span>';
                        if ($withdrawal->approval_date) {
                             echo '<br><small>' . __('في:', 'smc') . ' ' . date_i18n('Y-m-d H:i', strtotime($withdrawal->approval_date)) . '</small>';
                        }
                         if ($withdrawal->approved_by && $admin_username !== __('N/A', 'smc')) {
                             echo '<br><small>' . __('بواسطة:', 'smc') . ' ' . esc_html($admin_username) . '</small>';
                         }
                    }
                    echo '</td>';

                    echo '</tr>';
                }
            } else {
                echo '<tr><td colspan="11">' . __('لا توجد طلبات سحب ودائع (غير ملغاة) لعرضها.', 'smc') . '</td></tr>'; // Updated colspan
            }
            ?>
        </tbody>
    </table>
</div>

<?php get_footer(); ?>

<?php // --- JavaScript for DataTables and Approve/Reject Buttons --- ?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script type="text/javascript">
jQuery(document).ready(function($) {
    // Initialize DataTables
    if ($.fn.DataTable) {
        try {
            $('#admin-deposit-withdrawals-table').DataTable({
                responsive: true,
                dom: 'Bfrtip',
                buttons: ['copy', 'csv', 'excel', 'pdf', 'print'],
                order: [[ 0, "desc" ]], // Default sort by request date
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/ar.json', // Arabic language file
                    search: "<?php esc_attr_e('بحث في السجلات:', 'smc'); ?>"
                },
                 columnDefs: [
                    { targets: 10, orderable: false, searchable: false } // Disable sorting/searching on Action column
                ]
            });
        } catch (e) {
            console.error("Error initializing DataTables for admin deposit withdrawals log:", e);
            $('.smc-log-container').prepend('<p class="smc-error-message">حدث خطأ أثناء تحميل جدول السجلات التفاعلي.</p>');
        }
    } else {
        console.warn("DataTables library not found for admin deposit withdrawals log.");
         $('.smc-log-container').prepend('<p class="smc-error-message">لم يتم تحميل مكتبة الجداول التفاعلية (DataTables).</p>');
    }

    // Unified Approve/Reject Button Handler (Checks data-type)
    $('body').on('click', '.smc-button.approve-button, .smc-button.reject-button', function() {
        var button = $(this);
        var withdrawalId = button.data('id');
        var withdrawalType = button.data('type'); // 'deposit_withdrawal' or 'profit_withdrawal'
        var actionType = button.hasClass('approve-button') ? 'approve' : 'reject';
        var row = button.closest('tr');
        var actionCell = button.closest('td.action-cell');

        if (!withdrawalId || !withdrawalType) {
            console.error("Missing data-id or data-type on withdrawal button.");
            return;
        }

        // Determine AJAX action and nonce based on type
        var ajaxAction = '';
        var ajaxNonce = '';
        if (withdrawalType === 'deposit_withdrawal') {
            ajaxAction = 'smc_handle_deposit_withdrawal_action';
            ajaxNonce = smc_data.withdraw_deposit_approval_nonce; // Ensure this nonce exists in smc_data
        } else if (withdrawalType === 'profit_withdrawal') {
            ajaxAction = 'smc_handle_profit_withdrawal_action';
            ajaxNonce = smc_data.withdraw_profit_approval_nonce; // Ensure this nonce exists in smc_data
        } else {
            console.error("Invalid withdrawal type:", withdrawalType);
            Swal.fire({ icon: 'error', title: '<?php esc_attr_e('خطأ!', 'smc'); ?>', text: '<?php esc_attr_e('نوع سحب غير صالح.', 'smc'); ?>'});
            return;
        }

        if (!ajaxNonce) {
             Swal.fire({ icon: 'error', title: '<?php esc_attr_e('خطأ!', 'smc'); ?>', text: '<?php esc_attr_e('فشل التحقق الأمني (Nonce).', 'smc'); ?>'});
             return;
        }

        // Disable buttons and show spinner
        button.prop('disabled', true).siblings('button').prop('disabled', true);
        button.html('<i class="fas fa-spinner fa-spin"></i> <?php esc_attr_e('جارٍ...', 'smc'); ?>');

        $.ajax({
            url: smc_data.ajax_url, // Global ajax url
            type: 'POST',
            data: {
                action: ajaxAction,
                nonce: ajaxNonce,
                withdrawal_id: withdrawalId,
                withdrawal_action: actionType // 'approve' or 'reject'
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Update Status cell (Column 8, index 7)
                    var statusCell = row.find('td').eq(7);
                    statusCell.text(response.data.new_status_text)
                              .removeClass('status-pending status-approved status-rejected')
                              .addClass('status-' + response.data.new_status);

                    // Update Fee cell (Column 5, index 4)
                    var feeCell = row.find('td').eq(4);
                    if (response.data.fee_amount !== undefined) {
                         feeCell.find('span').text(parseFloat(response.data.fee_amount).toFixed(2) + ' دج');
                    }

                    // Update Approval Date cell (Column 9, index 8)
                    var dateCell = row.find('td').eq(8);
                    dateCell.text(response.data.approval_date || '<?php esc_attr_e('N/A', 'smc'); ?>');

                    // Update Admin cell (Column 10, index 9)
                    var adminCell = row.find('td').eq(9);
                    adminCell.text(response.data.admin_username || '<?php esc_attr_e('N/A', 'smc'); ?>');

                    // Update Action cell (Column 11, index 10)
                    var actionStatusClass = actionType === 'approve' ? 'approved' : 'rejected';
                    var adminText = response.data.admin_username && response.data.admin_username !== '<?php esc_attr_e('N/A', 'smc'); ?>' ? '<br><small><?php esc_attr_e('بواسطة:', 'smc'); ?> ' + response.data.admin_username + '</small>' : '';
                    var dateText = response.data.approval_date ? '<br><small><?php esc_attr_e('في:', 'smc'); ?> ' + response.data.approval_date + '</small>' : '';
                    actionCell.html('<span class="action-status ' + actionStatusClass + '"><?php esc_attr_e('تم', 'smc'); ?> ' + response.data.new_status_text + '</span>' + dateText + adminText);

                    // Visual feedback
                    row.css('background-color', actionType === 'approve' ? '#d4edda' : '#f8d7da');
                    setTimeout(function() { row.css('background-color', ''); }, 2500);

                    Swal.fire({
                        icon: 'success', title: '<?php esc_attr_e('تم بنجاح!', 'smc'); ?>', text: response.data.message,
                        timer: 2000, showConfirmButton: false
                    });

                } else {
                    // Show error message
                    Swal.fire({ icon: 'error', title: '<?php esc_attr_e('خطأ!', 'smc'); ?>', text: response.data.message || '<?php esc_attr_e('حدث خطأ غير متوقع.', 'smc'); ?>' });
                    // Re-enable buttons
                    button.prop('disabled', false).siblings('button').prop('disabled', false);
                    button.html(button.hasClass('approve-button') ? '<i class="fas fa-check"></i> <?php esc_attr_e('موافقة', 'smc'); ?>' : '<i class="fas fa-times"></i> <?php esc_attr_e('رفض', 'smc'); ?>');
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                Swal.fire({ icon: 'error', title: '<?php esc_attr_e('خطأ اتصال!', 'smc'); ?>', text: '<?php esc_attr_e('حدث خطأ في الاتصال بالخادم.', 'smc'); ?>' });
                console.error("AJAX Error:", textStatus, errorThrown, jqXHR.responseText);
                // Re-enable buttons
                button.prop('disabled', false).siblings('button').prop('disabled', false);
                button.html(button.hasClass('approve-button') ? '<i class="fas fa-check"></i> <?php esc_attr_e('موافقة', 'smc'); ?>' : '<i class="fas fa-times"></i> <?php esc_attr_e('رفض', 'smc'); ?>');
            }
        });
    });
});
</script>

<?php // Use the same styles as user-deposit-withdrawal-log.php, adding admin-specific ones if needed ?>
<style>
/* General Log Table Styles */
.smc-log-container { max-width: 1200px; margin: 20px auto; } /* Wider container for admin */
.smc-log-table { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 0.9em; } /* Slightly smaller font */
.smc-log-table th, .smc-log-table td { border: 1px solid #ddd; padding: 6px 8px; text-align: right; vertical-align: middle; }
.smc-log-table th { background-color: #f2f2f2; font-weight: bold; }
.smc-log-table tbody tr:nth-child(even) { background-color: #f9f9f9; }
.smc-log-table td span[dir="ltr"] { display: inline-block; }

/* Status specific styles */
.status-pending { color: #ffc107; font-weight: bold; }
.status-approved { color: #28a745; font-weight: bold; }
.status-rejected { color: #dc3545; font-weight: bold; }
.status-cancelled { color: #6c757d; font-weight: bold; text-decoration: line-through; } /* Should not appear */

/* Action cell styles */
.action-cell { text-align: center; white-space: nowrap; }
.action-cell .smc-button { padding: 5px 8px; font-size: 0.85em; margin: 2px; }
.action-cell .smc-button i { margin-left: 3px; }
.approve-button { background-color: #28a745; border-color: #28a745; color: white; }
.approve-button:hover { background-color: #218838; border-color: #1e7e34; }
.reject-button { background-color: #dc3545; border-color: #dc3545; color: white; }
.reject-button:hover { background-color: #c82333; border-color: #bd2130; }
.smc-button:disabled { background-color: #aaa; border-color: #aaa; cursor: not-allowed; opacity: 0.7; }
.action-status { font-weight: bold; padding: 5px; border-radius: 4px; display: inline-block; font-size: 0.9em; }
.action-status.approved { color: #155724; background-color: #d4edda; }
.action-status.rejected { color: #721c24; background-color: #f8d7da; }
.action-status small { display: block; font-weight: normal; color: #666; font-size: 0.9em; margin-top: 3px; }

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
