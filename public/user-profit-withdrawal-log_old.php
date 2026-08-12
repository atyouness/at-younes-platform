<?php
/**
 * Template Name: User Profit Withdrawal Log
 * Description: Displays the profit withdrawal log for the current user.
 */

// Ensure WordPress loads this file
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Check if user is logged in
if (!is_user_logged_in()) {
    wp_redirect(wp_login_url(home_url('/my-profit-withdrawals/'))); // Redirect to login, then back here
    exit;
}

// Include header.php
get_header();
$user_id = get_current_user_id();
?>

<div class="container smc-log-container">
    <h2><i class="fas fa-hand-holding-usd"></i> سجل عمليات سحب الأرباح الخاص بك</h2>
     <a href="<?php echo esc_url( home_url( '/transactional/' ) ); ?>" class="smc-button smc-button-secondary" style="margin-bottom: 15px; display: inline-block;"><i class="fas fa-arrow-left"></i> العودة إلى معاملاتي</a>

    <!-- Log Controls -->
    <div class="smc-log-controls" style="margin-bottom: 20px; padding: 15px; background-color: #f9f9f9; border: 1px solid #eee; border-radius: 5px;">
        <p><strong>أدوات التحكم بالسجل:</strong></p>
        <p>يمكنك استخدام حقل "بحث" أدناه للبحث في سجلاتك. يمكنك أيضًا فرز الأعمدة وتصدير البيانات باستخدام الأزرار.</p>
    </div>

    <table id="user-profit-withdrawals-table" class="display compact stripe hover smc-log-table" style="width:100%">
        <thead>
            <tr>
                <th>تاريخ الطلب</th>
                <th>معرف العملية</th>
                <th>قيمة السحب (دج)</th>
                <th>رسوم السحب (دج)</th>
                <th>طريقة السحب</th>
                <th>الحالة</th>
                <th>الإجراء</th> <?php // New Action column ?>
            </tr>
        </thead>
        <tbody>
            <?php
            global $wpdb;
            $table_name = $wpdb->prefix . 'user_profit_withdrawals'; // Correct table name

            // Fetch withdrawals for the current user only
            $withdrawals = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT id, withdrawal_date, amount, fee_amount, payment_method, status FROM $table_name WHERE user_id = %d ORDER BY withdrawal_date DESC",
                    $user_id
                )
            );

            if ($withdrawals) {
                foreach ($withdrawals as $withdrawal) {
                    // Determine status text
                    $status_text = '';
                    $status_class = ''; // For potential styling
                    switch ($withdrawal->status) {
                        case 'pending': $status_text = 'انتظار'; $status_class = 'status-pending'; break;
                        case 'approved': $status_text = 'موافقة'; $status_class = 'status-approved'; break;
                        case 'rejected': $status_text = 'رفض'; $status_class = 'status-rejected'; break;
                        case 'cancelled': $status_text = 'ملغى'; $status_class = 'status-cancelled'; break; // Added cancelled status
                        default: $status_text = esc_html($withdrawal->status); $status_class = 'status-unknown';
                    }

                    echo '<tr>';
                    echo '<td>' . esc_html(date_i18n('Y-m-d H:i', strtotime($withdrawal->withdrawal_date))) . '</td>';
                    echo '<td>' . esc_html($withdrawal->id) . '</td>';
                    echo '<td><span dir="ltr">' . esc_html(number_format((float)$withdrawal->amount, 2, '.', '')) . ' دج</span></td>';
                    echo '<td><span dir="ltr">' . esc_html(number_format((float)$withdrawal->fee_amount, 2, '.', '')) . ' دج</span></td>';
                    echo '<td>' . esc_html($withdrawal->payment_method) . '</td>';
                    echo '<td class="' . esc_attr($status_class) . '">' . esc_html($status_text) . '</td>';

                    // Action Column
                    echo '<td class="action-cell">';
                    if ($withdrawal->status == 'pending') {
                        // Add Cancel button for pending requests
                        echo '<button class="smc-button smc-button-danger cancel-withdrawal-button" data-id="' . esc_attr($withdrawal->id) . '" data-type="profit_withdrawal"><i class="fas fa-times"></i> إلغاء</button>';
                    } else {
                        echo '<span>-</span>'; // No action for non-pending
                    }
                    echo '</td>';

                    echo '</tr>';
                }
            } else {
                echo '<tr><td colspan="7">لا توجد بيانات سحب أرباح حالية لعرضها.</td></tr>'; // Updated colspan
            }
            ?>
        </tbody>
    </table>
</div>

<?php
// Include footer.php
get_footer();
?>

<?php // --- JavaScript for DataTables and Cancel Button --- ?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script type="text/javascript">
jQuery(document).ready(function($) {
    // Initialize DataTables
    if ($.fn.DataTable) {
        try {
            $('#user-profit-withdrawals-table').DataTable({
                responsive: true,
                dom: 'Bfrtip', // Buttons, filter, processing, table, info, pagination
                buttons: ['copy', 'csv', 'excel', 'pdf', 'print'],
                order: [[ 0, "desc" ]], // Default sort by date descending
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/ar.json',
                    search: "بحث في سجلاتك:"
                },
                 columnDefs: [
                    { targets: 6, orderable: false, searchable: false } // Disable sorting/searching on Action column
                ]
            });
        } catch (e) {
            console.error("Error initializing DataTables for user profit withdrawals:", e);
            $('.smc-log-container').prepend('<p class="smc-error-message">حدث خطأ أثناء تحميل جدول السجلات التفاعلي.</p>');
        }
    } else {
        console.warn("DataTables library not found for user profit withdrawals.");
        $('.smc-log-container').prepend('<p class="smc-error-message">لم يتم تحميل مكتبة الجداول التفاعلية (DataTables).</p>');
    }

    // Handle Cancel Button Click (Uses the same AJAX handler as deposit withdrawal)
    $('body').on('click', '.cancel-withdrawal-button', function() {
        var button = $(this);
        var withdrawalId = button.data('id');
        var withdrawalType = button.data('type'); // Should be 'profit_withdrawal'
        var row = button.closest('tr');
        var actionCell = button.closest('td.action-cell');
        var statusCell = row.find('td').eq(5); // 6th column (index 5) is Status

        if (!withdrawalId || !withdrawalType) {
            console.error("Missing data-id or data-type on cancel button.");
            return;
        }

        // Use the specific nonce for cancellation
        var ajaxNonce = smc_data.cancel_withdrawal_nonce; // Make sure this nonce is added in enqueue.php

        if (!ajaxNonce) {
             Swal.fire({ icon: 'error', title: 'خطأ!', text: 'فشل التحقق الأمني (Nonce).'});
             return;
        }

        // Confirmation dialog
        Swal.fire({
            title: 'هل أنت متأكد؟',
            text: "هل تريد بالتأكيد إلغاء طلب السحب هذا؟ لا يمكن التراجع عن هذا الإجراء.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'نعم، قم بالإلغاء!',
            cancelButtonText: 'تراجع'
        }).then((result) => {
            if (result.isConfirmed) {
                // Proceed with cancellation
                button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> جارٍ...');

                $.ajax({
                    url: smc_data.ajax_url, // Global ajax url from wp_localize_script
                    type: 'POST',
                    data: {
                        action: 'smc_cancel_withdrawal_request', // The new AJAX action handler
                        nonce: ajaxNonce,
                        withdrawal_id: withdrawalId,
                        withdrawal_type: withdrawalType
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            // Update UI on success
                            statusCell.text('ملغى').removeClass('status-pending').addClass('status-cancelled');
                            actionCell.html('<span>تم الإلغاء</span>'); // Remove button, show text

                            Swal.fire({
                                icon: 'success', title: 'تم الإلغاء!', text: response.data.message,
                                timer: 2000, showConfirmButton: false
                            });
                            // Optionally, fade out the row or apply different styling
                            row.css('opacity', 0.5);

                        } else {
                            Swal.fire({ icon: 'error', title: 'خطأ!', text: response.data.message || 'حدث خطأ غير متوقع.' });
                            button.prop('disabled', false).html('<i class="fas fa-times"></i> إلغاء'); // Re-enable button
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        Swal.fire({ icon: 'error', title: 'خطأ اتصال!', text: 'حدث خطأ في الاتصال بالخادم.' });
                        console.error("AJAX Error:", textStatus, errorThrown, jqXHR.responseText);
                        button.prop('disabled', false).html('<i class="fas fa-times"></i> إلغاء'); // Re-enable button
                    }
                });
            }
        }); // End Swal confirmation
    }); // End cancel button click handler

});
</script>

<?php // Use the same styles as user-deposit-withdrawal-log.php ?>
<style>
/* General Log Table Styles (if not already global) */
.smc-log-container { max-width: 1100px; margin: 20px auto; }
.smc-log-table { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 0.95em; }
.smc-log-table th, .smc-log-table td { border: 1px solid #ddd; padding: 8px 10px; text-align: right; vertical-align: middle; }
.smc-log-table th { background-color: #f2f2f2; font-weight: bold; }
.smc-log-table tbody tr:nth-child(even) { background-color: #f9f9f9; }
.smc-log-table td span[dir="ltr"] { display: inline-block; } /* Ensure LTR for numbers */

/* Status specific styles */
.status-pending { color: #ffc107; font-weight: bold; }
.status-approved { color: #28a745; font-weight: bold; }
.status-rejected { color: #dc3545; font-weight: bold; }
.status-cancelled { color: #6c757d; font-weight: bold; text-decoration: line-through; }

/* Action cell styles */
.action-cell { text-align: center; white-space: nowrap; }
.action-cell .smc-button { padding: 4px 8px; font-size: 0.85em; margin: 0; }
.action-cell .smc-button i { margin-left: 3px; }
.smc-button-danger { background-color: #dc3545; border-color: #dc3545; color: white; }
.smc-button-danger:hover { background-color: #c82333; border-color: #bd2130; }
.smc-button-danger:disabled { background-color: #aaa; border-color: #aaa; cursor: not-allowed; }
.action-cell span { color: #6c757d; font-style: italic; }

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
