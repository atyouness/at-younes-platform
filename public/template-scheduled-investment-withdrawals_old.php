<?php
/**
 * Template Name: Scheduled Investment Withdrawals (User)
 * Description: Allows users to manage scheduled withdrawals for their investments.
 */

if (!defined('ABSPATH')) exit; // Exit if accessed directly

// Redirect if not logged in
if (!is_user_logged_in()) {
    wp_redirect(home_url('/'));
    exit;
}

get_header();
$user_id = get_current_user_id();
$user_smc_data = function_exists('smc_get_user_data') ? smc_get_user_data($user_id) : [];
$active_investments = $user_smc_data['active_investments_details'] ?? [];

?>

<div class="container smc-page-container scheduled-withdrawals-container">
    <h2><i class="fas fa-calendar-alt"></i> إدارة طلبات سحب الاستثمار المجدولة</h2>
    <a href="<?php echo esc_url(home_url('/transactional/')); ?>" class="smc-button smc-button-secondary" style="margin-bottom: 20px; display: inline-block;">
        <i class="fas fa-arrow-left"></i> العودة إلى معاملاتي
    </a>

    <?php if (empty($active_investments)): ?>
        <p class="smc-info-message">ليس لديك أي استثمارات نشطة حاليًا لجدولة سحبها.</p>
    <?php else: ?>
        <p class="smc-info-message">
            <i class="fas fa-info-circle"></i> يمكنك طلب سحب مجدول لوديعة استثمارك قبل <strong>36 ساعة</strong> من تاريخ انتهائها الطبيعي.
            <br>
            <i class="fas fa-info-circle"></i> يمكنك إلغاء طلب السحب المجدول طالما لم يتبقَ أقل من <strong>36 ساعة</strong> على تاريخ انتهاء الاستثمار.
        </p>

        <div class="smc-investments-list">
            <?php foreach ($active_investments as $investment): ?>
                <div class="smc-investment-item card">
                    <div class="card-header">
                        <h4><?php echo esc_html($investment['title']); ?> (<?php echo esc_html($investment['package']); ?>)</h4>
                    </div>
                    <div class="card-body">
                        <p><strong>المبلغ المستثمر:</strong> <span dir="ltr"><?php echo number_format($investment['amount'], 2); ?> دج</span></p>
                        <p><strong>تاريخ بدء الاستثمار:</strong> <?php echo esc_html($investment['start_datetime_str']); ?></p>
                        <p><strong>تاريخ انتهاء الاستثمار الطبيعي:</strong> <?php echo esc_html($investment['end_date_str']); ?></p>
                        <p><strong>حالة الوديعة:</strong>
                            <?php
                            $status_display = 'غير معروف';
                            if ($investment['deposit_status'] === 'approved') {
                                $status_display = 'موافق عليها (نشطة)';
                            } elseif ($investment['deposit_status'] === 'withdrawal_scheduled') {
                                $status_display = 'طلب سحب مجدول قيد الانتظار';
                            } elseif ($investment['deposit_status'] === 'completed') {
                                $status_display = 'مكتملة';
                            } elseif ($investment['deposit_status'] === 'withdrawn') {
                                $status_display = 'مسحوبة';
                            }
                            echo esc_html($status_display);
                            ?>
                        </p>

                        <?php if ($investment['scheduled_withdrawal_request_id']): ?>
                            <p><strong>حالة طلب السحب المجدول:</strong>
                                <span class="status-<?php echo esc_attr($investment['scheduled_withdrawal_status']); ?>">
                                    <?php echo esc_html(ucfirst(str_replace('_', ' ', $investment['scheduled_withdrawal_status']))); ?>
                                </span>
                            </p>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer investment-actions">
                        <?php if ($investment['can_request_scheduled_withdrawal']): ?>
                            <button class="smc-button smc-button-primary request-scheduled-withdrawal-btn"
                                    data-deposit-id="<?php echo esc_attr($investment['id']); ?>">
                                <i class="fas fa-calendar-plus"></i> طلب سحب مجدول
                            </button>
                        <?php elseif ($investment['scheduled_withdrawal_request_id'] && $investment['scheduled_withdrawal_status'] === 'scheduled' && $investment['can_cancel_investment_now']): ?>
                            <button class="smc-button smc-button-danger cancel-scheduled-withdrawal-btn"
                                    data-request-id="<?php echo esc_attr($investment['scheduled_withdrawal_request_id']); ?>"
                                    data-deposit-id="<?php echo esc_attr($investment['id']); ?>">
                                <i class="fas fa-calendar-times"></i> إلغاء طلب السحب المجدول
                            </button>
                        <?php elseif ($investment['is_ending_soon'] && $investment['deposit_status'] !== 'withdrawal_scheduled'): ?>
                             <p class="smc-text-info"><i class="fas fa-hourglass-half"></i> هذا الاستثمار قيد الانتهاء قريبًا (أقل من 36 ساعة).</p>
                        <?php elseif ($investment['deposit_status'] === 'withdrawal_scheduled' && !$investment['can_cancel_investment_now']): ?>
                            <p class="smc-text-warning"><i class="fas fa-lock"></i> لا يمكن إلغاء طلب السحب الآن (أقل من 36 ساعة على نهاية الاستثمار).</p>
                        <?php elseif ($investment['deposit_status'] !== 'approved'): ?>
                             <p class="smc-text-info">لا يمكن اتخاذ إجراء حاليًا على هذه الوديعة (الحالة: <?php echo esc_html($status_display); ?>).</p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php get_footer(); ?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script type="text/javascript">
jQuery(document).ready(function($) {
    const ajaxUrl = smc_data.ajax_url;
    const scheduledWithdrawalNonce = smc_data.scheduled_withdrawal_nonce;
    const cancelScheduledNonce = smc_data.cancel_scheduled_withdrawal_nonce;

    // Request Scheduled Withdrawal
    $('.request-scheduled-withdrawal-btn').on('click', function() {
        const button = $(this);
        const depositId = button.data('deposit-id');

        Swal.fire({
            title: 'تأكيد طلب السحب المجدول',
            text: "سيتم جدولة سحب هذه الوديعة ليتم معالجته قبل 36 ساعة من تاريخ انتهائها. هل أنت متأكد؟",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'نعم، قم بالجدولة!',
            cancelButtonText: 'إلغاء'
        }).then((result) => {
            if (result.isConfirmed) {
                button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> جارٍ الطلب...');
                $.ajax({
                    url: ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'smc_request_scheduled_investment_withdrawal',
                        nonce: scheduledWithdrawalNonce,
                        deposit_id: depositId
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            Swal.fire('تم بنجاح!', response.data.message, 'success').then(() => location.reload());
                        } else {
                            Swal.fire('خطأ!', response.data.message || 'فشل تقديم الطلب.', 'error');
                            button.prop('disabled', false).html('<i class="fas fa-calendar-plus"></i> طلب سحب مجدول');
                        }
                    },
                    error: function() {
                        Swal.fire('خطأ!', 'حدث خطأ في الاتصال بالخادم.', 'error');
                        button.prop('disabled', false).html('<i class="fas fa-calendar-plus"></i> طلب سحب مجدول');
                    }
                });
            }
        });
    });

    // Cancel Scheduled Withdrawal
    $('.cancel-scheduled-withdrawal-btn').on('click', function() {
        const button = $(this);
        const requestId = button.data('request-id');
        // const depositId = button.data('deposit-id'); // Not strictly needed for cancellation by request_id

        Swal.fire({
            title: 'تأكيد إلغاء طلب السحب',
            text: "هل أنت متأكد من إلغاء طلب السحب المجدول هذا؟ ستعود وديعتك إلى الحالة النشطة.",
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
                        action: 'smc_user_cancel_scheduled_investment_withdrawal',
                        nonce: cancelScheduledNonce,
                        request_id: requestId
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            Swal.fire('تم الإلغاء!', response.data.message, 'success').then(() => location.reload());
                        } else {
                            Swal.fire('خطأ!', response.data.message || 'فشل إلغاء الطلب.', 'error');
                            button.prop('disabled', false).html('<i class="fas fa-calendar-times"></i> إلغاء طلب السحب المجدول');
                        }
                    },
                    error: function() {
                        Swal.fire('خطأ!', 'حدث خطأ في الاتصال بالخادم.', 'error');
                        button.prop('disabled', false).html('<i class="fas fa-calendar-times"></i> إلغاء طلب السحب المجدول');
                    }
                });
            }
        });
    });
});
</script>

<style>
.smc-page-container { max-width: 900px; margin: 20px auto; }
.smc-page-container h2 { display: flex; align-items: center; margin-bottom: 20px; }
.smc-page-container h2 i { margin-left: 10px; color: #007bff; }
.smc-info-message { background-color: #e7f3ff; border-left: 5px solid #007bff; padding: 15px; margin-bottom: 20px; border-radius: 4px; font-size: 0.95em; }
.smc-info-message i { margin-left: 8px; }
.smc-text-info { color: #007bff; font-size: 0.9em; margin-top: 10px; }
.smc-text-warning { color: #ffc107; font-size: 0.9em; margin-top: 10px; }
.smc-investments-list .card { margin-bottom: 20px; border: 1px solid #ddd; border-radius: 5px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
.smc-investments-list .card-header { background-color: #f8f9fa; padding: 10px 15px; border-bottom: 1px solid #ddd; }
.smc-investments-list .card-header h4 { margin: 0; font-size: 1.2em; color: #333; }
.smc-investments-list .card-body { padding: 15px; }
.smc-investments-list .card-body p { margin-bottom: 8px; font-size: 1em; }
.smc-investments-list .card-body strong { color: #555; }
.smc-investments-list .card-footer.investment-actions { padding: 10px 15px; background-color: #f8f9fa; border-top: 1px solid #ddd; text-align: left; }
.smc-button { padding: 8px 15px; font-size: 0.95em; border-radius: 4px; cursor: pointer; transition: background-color 0.3s; text-decoration: none; display: inline-flex; align-items: center; }
.smc-button i { margin-left: 5px; }
.smc-button-primary { background-color: #007bff; color: white; border: 1px solid #007bff; }
.smc-button-primary:hover { background-color: #0056b3; }
.smc-button-danger { background-color: #dc3545; color: white; border: 1px solid #dc3545; }
.smc-button-danger:hover { background-color: #c82333; }
.smc-button-secondary { background-color: #6c757d; color: white; border: 1px solid #6c757d; }
.smc-button-secondary:hover { background-color: #5a6268; }
.smc-button:disabled { background-color: #e9ecef; color: #6c757d; border-color: #ced4da; cursor: not-allowed; }
.status-scheduled { color: #ffc107; font-weight: bold; }
.status-completed { color: #28a745; font-weight: bold; }
.status-cancelled_by_user, .status-cancelled_by_admin { color: #6c757d; font-weight: bold; text-decoration: line-through; }
</style>
