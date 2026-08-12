<?php
/**
 * Template Name: Admin Scheduled Investment Withdrawals Log
 * Description: Displays all scheduled investment withdrawal requests for administrators.
 */

if (!defined('ABSPATH')) exit; // Exit if accessed directly

// Redirect if not logged in or not an admin
if (!is_user_logged_in() || !current_user_can('administrator')) {
    wp_redirect(home_url('/'));
    exit;
}

get_header();
global $wpdb;
$requests_table = $wpdb->prefix . 'smc_investment_withdrawal_requests';
$all_requests = $wpdb->get_results("SELECT * FROM {$requests_table} ORDER BY request_timestamp DESC");

?>

<div class="container smc-page-container admin-scheduled-log-container">
    <h2><i class="fas fa-calendar-check"></i> سجل طلبات سحب الاستثمار المجدولة (للمسؤول)</h2>
    <a href="<?php echo esc_url(home_url('/smc-settings/')); ?>" class="smc-button smc-button-secondary" style="margin-bottom: 20px; display: inline-block;">
        <i class="fas fa-cog"></i> العودة إلى إعدادات SMC
    </a>

    <div class="smc-log-controls" style="margin-bottom: 20px; padding: 15px; background-color: #f9f9f9; border: 1px solid #eee; border-radius: 5px;">
        <p><strong>أدوات التحكم بالسجل:</strong> يمكنك البحث، فرز الأعمدة، وتصدير البيانات.</p>
    </div>

    <table id="admin-scheduled-withdrawals-table" class="display compact stripe hover smc-log-table" style="width:100%">
        <thead>
            <tr>
                <th>معرف الطلب</th>
                <th>معرف المستخدم</th>
                <th>اسم المستخدم</th>
                <th>معرف وديعة الاستثمار</th>
                <th>نوع الاستثمار</th>
                <th>المبلغ المطلوب (دج)</th>
                <th>الحصص</th>
                <th>تاريخ الطلب</th>
                <th>تاريخ المعالجة المجدول</th>
                <th>الحالة</th>
                <th>الإجراء</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($all_requests)): ?>
                <?php foreach ($all_requests as $request):
                    $user_info = get_userdata($request->user_id);
                    $username = $user_info ? $user_info->user_login : 'غير معروف';
                    ?>
                    <tr>
                        <td><?php echo esc_html($request->id); ?></td>
                        <td><?php echo esc_html($request->user_id); ?></td>
                        <td><?php echo esc_html($username); ?></td>
                        <td><?php echo esc_html($request->deposit_id); ?></td>
                        <td><?php echo esc_html($request->investment_type); ?></td>
                        <td><span dir="ltr"><?php echo number_format($request->amount_requested, 2); ?></span></td>
                        <td><?php echo esc_html($request->shares_to_release); ?></td>
                        <td><?php echo esc_html(date_i18n('Y-m-d H:i', strtotime($request->request_timestamp))); ?></td>
                        <td><?php echo esc_html(date_i18n('Y-m-d H:i', strtotime($request->scheduled_process_date))); ?></td>
                        <td class="status-<?php echo esc_attr($request->status); ?>"><?php echo esc_html(ucfirst(str_replace('_', ' ', $request->status))); ?></td>
                        <td class="action-cell">
                            <?php if ($request->status === 'scheduled'): ?>
                                <button class="smc-button smc-button-danger admin-cancel-scheduled-btn"
                                        data-request-id="<?php echo esc_attr($request->id); ?>">
                                    <i class="fas fa-times-circle"></i> إلغاء الطلب
                                </button>
                            <?php elseif ($request->status === 'processing_due'): ?>
                                <button class="smc-button smc-button-primary admin-process-scheduled-btn"
                                        data-request-id="<?php echo esc_attr($request->id); ?>">
                                    <i class="fas fa-cogs"></i> معالجة الآن (يدوي)
                                </button>
                            <?php else: ?>
                                <span>-</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="11">لا توجد طلبات سحب مجدولة حاليًا.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php get_footer(); ?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script type="text/javascript">
jQuery(document).ready(function($) {
    const ajaxUrl = smc_data.ajax_url;
    // Note: We'll need a new nonce for admin actions if it's different from user nonces.
    // For now, assuming a general admin nonce might be available or we create one.
    // Let's assume 'smc_admin_actions_nonce' will be localized.
    const adminActionNonce = (typeof smc_data !== 'undefined' && smc_data.admin_actions_nonce) ? smc_data.admin_actions_nonce : null;


    if ($.fn.DataTable) {
        try {
            $('#admin-scheduled-withdrawals-table').DataTable({
                responsive: true,
                dom: 'Bfrtip',
                buttons: ['copy', 'csv', 'excel', 'pdf', 'print'],
                order: [[7, "desc"]], // Order by request date
                language: { url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/ar.json' }
            });
        } catch (e) { console.error("Error initializing DataTables:", e); }
    }

    // Admin Cancel Scheduled Withdrawal
    $('.admin-cancel-scheduled-btn').on('click', function() {
        const button = $(this);
        const requestId = button.data('request-id');

        if (!adminActionNonce) {
            Swal.fire('خطأ!', 'فشل التحقق الأمني (Nonce للمسؤول).', 'error');
            return;
        }

        Swal.fire({
            title: 'تأكيد إلغاء الطلب (مسؤول)',
            text: "هل أنت متأكد من إلغاء طلب السحب المجدول هذا؟ ستعود وديعة المستخدم إلى الحالة النشطة.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'نعم، قم بالإلغاء!',
            cancelButtonText: 'تراجع'
        }).then((result) => {
            if (result.isConfirmed) {
                button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> جارٍ الإلغاء...');
                $.ajax({
                    url: ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'smc_admin_cancel_scheduled_investment_withdrawal',
                        nonce: adminActionNonce, // Use admin-specific nonce
                        request_id: requestId
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            Swal.fire('تم الإلغاء!', response.data.message, 'success').then(() => location.reload());
                        } else {
                            Swal.fire('خطأ!', response.data.message || 'فشل إلغاء الطلب.', 'error');
                            button.prop('disabled', false).html('<i class="fas fa-times-circle"></i> إلغاء الطلب');
                        }
                    },
                    error: function() {
                        Swal.fire('خطأ!', 'حدث خطأ في الاتصال بالخادم.', 'error');
                        button.prop('disabled', false).html('<i class="fas fa-times-circle"></i> إلغاء الطلب');
                    }
                });
            }
        });
    });

    // Placeholder for Admin Process Scheduled Withdrawal (Manual Trigger)
    // This would typically be handled by a cron job, but a manual trigger can be useful.
    $('.admin-process-scheduled-btn').on('click', function() {
        const button = $(this);
        const requestId = button.data('request-id');
        Swal.fire({
            title: 'معالجة يدوية',
            text: "هذه الميزة قيد التطوير. عادةً ما تتم معالجة هذه الطلبات تلقائيًا بواسطة مهمة مجدولة. هل تريد المتابعة بالمعالجة اليدوية الآن؟ (سيتم تنفيذ نفس منطق المهمة المجدولة)",
            icon: 'info',
            showCancelButton: true,
            confirmButtonText: 'نعم، عالج يدويًا',
            cancelButtonText: 'إلغاء'
        }).then((result) => {
            if (result.isConfirmed) {
                // Here you would call an AJAX action that triggers the processing logic
                // similar to what the cron job would do for this specific request_id.
                // For now, just a placeholder:
                Swal.fire('قيد التطوير', 'سيتم هنا استدعاء دالة المعالجة اليدوية.', 'info');
                // Example AJAX call (needs backend handler):
                /*
                button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> جارٍ المعالجة...');
                $.ajax({
                    url: ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'smc_admin_manual_process_scheduled_withdrawal',
                        nonce: adminActionNonce, // Ensure this nonce is set up
                        request_id: requestId
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            Swal.fire('تمت المعالجة!', response.data.message, 'success').then(() => location.reload());
                        } else {
                            Swal.fire('خطأ!', response.data.message || 'فشل المعالجة اليدوية.', 'error');
                            button.prop('disabled', false).html('<i class="fas fa-cogs"></i> معالجة الآن (يدوي)');
                        }
                    },
                    error: function() {
                        Swal.fire('خطأ!', 'حدث خطأ في الاتصال بالخادم.', 'error');
                        button.prop('disabled', false).html('<i class="fas fa-cogs"></i> معالجة الآن (يدوي)');
                    }
                });
                */
            }
        });
    });

});
</script>

<style>
/* Styles similar to template-scheduled-investment-withdrawals.php but for admin context */
.smc-page-container { max-width: 1200px; margin: 20px auto; } /* Wider for admin */
.smc-page-container h2 i { margin-left: 10px; color: #007bff; }
.smc-log-table { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 0.9em; }
.smc-log-table th, .smc-log-table td { border: 1px solid #ddd; padding: 6px 8px; text-align: right; vertical-align: middle; }
.smc-log-table th { background-color: #f2f2f2; font-weight: bold; }
.smc-log-table tbody tr:nth-child(even) { background-color: #f9f9f9; }
.smc-log-table td span[dir="ltr"] { display: inline-block; }
.action-cell { text-align: center; white-space: nowrap; }
.action-cell .smc-button { margin: 2px; }
.smc-button { padding: 6px 12px; font-size: 0.9em; border-radius: 4px; cursor: pointer; transition: background-color 0.3s; text-decoration: none; display: inline-flex; align-items: center; }
.smc-button i { margin-left: 5px; }
.smc-button-primary { background-color: #007bff; color: white; border: 1px solid #007bff; }
.smc-button-primary:hover { background-color: #0056b3; }
.smc-button-danger { background-color: #dc3545; color: white; border: 1px solid #dc3545; }
.smc-button-danger:hover { background-color: #c82333; }
.smc-button-secondary { background-color: #6c757d; color: white; border: 1px solid #6c757d; }
.smc-button-secondary:hover { background-color: #5a6268; }
.smc-button:disabled { background-color: #e9ecef; color: #6c757d; border-color: #ced4da; cursor: not-allowed; }
.status-scheduled { color: #ffc107; font-weight: bold; }
.status-processing_due { color: #fd7e14; font-weight: bold; }
.status-completed { color: #28a745; font-weight: bold; }
.status-cancelled_by_user, .status-cancelled_by_admin, .status-failed { color: #6c757d; font-weight: bold; text-decoration: line-through; }
/* DataTables Controls */
.dt-buttons .dt-button { background-color: #007bff !important; color: white !important; border: 1px solid #007bff !important; border-radius: 4px !important; padding: 5px 10px !important; margin: 0 2px 5px 2px !important; transition: background-color 0.3s ease !important; font-size: 0.9em !important; }
.dt-buttons .dt-button:hover { background-color: #0056b3 !important; border-color: #0056b3 !important; }
.dataTables_filter label { font-weight: bold; font-size: 0.95em; }
.dataTables_filter input { margin-left: 5px; border: 1px solid #ccc; border-radius: 4px; padding: 5px; font-size: 0.95em; }
</style>
