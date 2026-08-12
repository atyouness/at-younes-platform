<?php
/**
 * Template Name: Withdraw Deposit Form
 * Description: Allows users to request deposit withdrawal.
 */

if (!defined('ABSPATH')) exit; // Exit if accessed directly

// Redirect if not logged in
if (!is_user_logged_in()) {
    wp_redirect(wp_login_url(home_url('/withdraw-deposit/')));
    exit;
}

get_header();
$user_id = get_current_user_id();
$user_data = [];
$tasks_deposit_original_sum = 0.0; // مجموع ودائع المهام النشطة الأصلية
$tasks_deposit_end_date_str = 'N/A';
$tasks_deposit_withdrawable_amount = 0.0;

$active_investments_details = [];
$total_active_investment_amount = 0.0;
$withdrawable_investment_amount = 0.0;

$overall_withdrawable_deposit_amount = 0.0; // المبلغ الإجمالي القابل للسحب (مهام + استثمار)
$message = '';
$calculated_fee = 0.00; // Initialize fee
// جلب بيانات المستخدم
if (function_exists('smc_get_user_data')) {
    $fetched_data = smc_get_user_data($user_id);

    if (is_array($fetched_data)) {
        $user_data = $fetched_data;
        $tasks_deposit_original_sum = $user_data['current_tasks_deposit_balance'] ?? 0.0;
        $tasks_deposit_end_date_str = $user_data['tasks_deposit_end_date_str'] ?? 'N/A';
        $tasks_deposit_withdrawable_amount = $user_data['tasks_deposit_withdrawable_amount'] ?? 0.0;

        $active_investments_details = $user_data['active_investments_details'] ?? [];
        $total_active_investment_amount = $user_data['total_active_investment_amount'] ?? 0.0;
        $withdrawable_investment_amount = $user_data['withdrawable_investment_amount'] ?? 0.0;

        $overall_withdrawable_deposit_amount = $tasks_deposit_withdrawable_amount + $withdrawable_investment_amount;

        if ($tasks_deposit_original_sum <= 0 && $total_active_investment_amount <= 0) {
            $message = '<p class="smc-error-message">ليس لديك رصيد وديعة قابل للسحب حاليًا.</p>';
        } elseif ($overall_withdrawable_deposit_amount <= 0) {
             $message = '<p class="smc-info-message">لا توجد ودائع مستحقة للسحب حاليًا.</p>';
        } else {
            // Fee calculation will be based on the amount being withdrawn, which is currently the tasks deposit if eligible
            if ($tasks_deposit_withdrawable_amount > 0) {
                $reward_settings = get_option(SMC_REWARD_SETTINGS_OPTION, smc_get_default_reward_settings());
                $fee_config = $reward_settings['deposit_withdrawal_fee'] ?? null;
                if ($fee_config && $fee_config['type'] === 'percentage_plus_fixed' && isset($fee_config['value']['percentage']) && isset($fee_config['value']['fixed'])) {
                    $fee_percentage = (float) $fee_config['value']['percentage'];
                    $fee_fixed = (float) $fee_config['value']['fixed'];
                    $calculated_fee = ($tasks_deposit_withdrawable_amount * $fee_percentage) + $fee_fixed;
                }
            }
        }
    } else {
         error_log("SMC Warning: smc_get_user_data returned non-array in withdraw_deposit_form.php for user ID: " . $user_id);
         $message = '<p class="smc-error-message">خطأ: لا يمكن جلب بيانات رصيد الوديعة.</p>';
         // Fallback logic...
         // Basic fallback, might not be fully accurate without the helper
        $tasks_deposit_original_sum = floatval(get_user_meta($user_id, SMC_DEPOSIT_BALANCE, true) ?: 0); // This is not ideal as SMC_DEPOSIT_BALANCE is spendable
        $tasks_deposit_end_date_str = get_user_meta($user_id, SMC_DEPOSIT_END_DATE, true) ? date_i18n('Y-m-d H:i', strtotime(get_user_meta($user_id, SMC_DEPOSIT_END_DATE, true))) : 'N/A';
        // Cannot easily calculate withdrawable amounts without full logic from helper
    }
} else {
    $message = '<p class="smc-error-message">خطأ: دالة جلب بيانات المستخدم غير موجودة.</p>';
    // Fallback logic...
    $tasks_deposit_original_sum = floatval(get_user_meta($user_id, SMC_DEPOSIT_BALANCE, true) ?: 0);
    $tasks_deposit_end_date_str = get_user_meta($user_id, SMC_DEPOSIT_END_DATE, true) ? date_i18n('Y-m-d H:i', strtotime(get_user_meta($user_id, SMC_DEPOSIT_END_DATE, true))) : 'N/A';
}

?>

<div class="container withdraw-page-container">
    <h2><i class="fas fa-undo-alt"></i> سحب الوديعة</h2>

    <div class="withdraw-summary">
        <p>الوديعة الحالية (مهام): <strong><span dir="ltr"><?php echo number_format($tasks_deposit_original_sum, 2, '.', ''); ?> دج</span></strong>
            <?php if ($tasks_deposit_original_sum > 0): ?>
                (تاريخ الاستحقاق: <?php echo esc_html($tasks_deposit_end_date_str); ?>)
            <?php endif; ?>
        </p>

        <hr style="border-top: 1px dashed #ccc; margin: 10px 0;">
        <h4>تفاصيل ودائع الاستثمار النشطة:</h4>
        <?php if (!empty($active_investments_details)): ?>
            <ul class="investment-details-list">
            <?php foreach ($active_investments_details as $inv_detail): ?>
                <li>
                    <strong><?php echo esc_html($inv_detail['title']); ?> (<?php echo esc_html($inv_detail['package']); ?>):</strong>
                    <span dir="ltr"><?php echo number_format($inv_detail['amount'], 2, '.', ''); ?> دج</span> -
                    تاريخ البدء: <?php echo esc_html($inv_detail['start_datetime_str']); ?> -
                    تاريخ الاستحقاق: <?php echo esc_html($inv_detail['end_date_str']); ?>
                    <?php if ($inv_detail['is_withdrawable']): ?>
                        <strong style="color: green;">(متاح للسحب)</strong>
                    <?php else: ?>
                        <span style="color: orange;">(غير متاح للسحب بعد)</span>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>لا توجد ودائع استثمار نشطة حاليًا.</p>
        <?php endif; ?>
        <p>مجموع ودائع الاستثمار النشطة: <strong><span dir="ltr"><?php echo number_format($total_active_investment_amount, 2, '.', ''); ?> دج</span></strong></p>
        <hr>
        <p>الوديعة الإجمالية (مهام + استثمار): <strong><span dir="ltr"><?php echo number_format($tasks_deposit_original_sum + $total_active_investment_amount, 2, '.', ''); ?> دج</span></strong></p>
        <hr>
        <p>وديعة المهام القابلة للسحب: <strong><span dir="ltr"><?php echo number_format($tasks_deposit_withdrawable_amount, 2, '.', ''); ?> دج</span></strong></p>
        <p>ودائع الاستثمار القابلة للسحب: <strong><span dir="ltr"><?php echo number_format($withdrawable_investment_amount, 2, '.', ''); ?> دج</span></strong></p>

        <?php echo $message; // عرض رسالة الخطأ أو المعلومات ?>
    </div>

    <?php // The form will target task deposit withdrawal if it's eligible ?>
    <?php if ($tasks_deposit_withdrawable_amount > 0): ?>
        <form id="smc-withdraw-deposit-form" action="<?php echo esc_url(admin_url('admin-ajax.php')); ?>" method="post">
            <?php wp_nonce_field('smc_withdraw_deposit_action', 'smc_withdraw_deposit_nonce'); ?>
            <input type="hidden" name="action" value="handle_withdraw_deposit">
            <input type="hidden" name="user_id" value="<?php echo esc_attr($user_id); ?>">
            <input type="hidden" name="withdraw_amount" value="<?php echo esc_attr($tasks_deposit_withdrawable_amount); ?>">

            <p>أنت مؤهل لسحب وديعتك للمهام البالغة <span dir="ltr"><?php echo number_format($tasks_deposit_withdrawable_amount, 2, '.', ''); ?> دج</span>. يرجى ملء التفاصيل أدناه لتقديم الطلب.</p>

             <div class="form-group">
                 <label for="withdraw-deposit-method">طريقة السحب المفضلة:</label>
                 <select id="withdraw-deposit-method" name="withdraw_method" required>
                     <option value="">-- اختر طريقة السحب --</option>
                     <option value="bank">تحويل بنكي</option>
                     <option value="baridimob">BaridiMob</option>
                     <option value="usdt_trc20">USDT (TRC20)</option>
                 </select>
             </div>

             <div class="form-group">
                 <label for="withdraw-deposit-details">تفاصيل الحساب (رقم الحساب، RIP، عنوان USDT، إلخ):</label>
                 <textarea id="withdraw-deposit-details" name="withdraw_details" rows="4" required placeholder="يرجى إدخال تفاصيل حسابك بدقة لتلقي المبلغ"></textarea>
             </div>

             <?php // *** بداية التعديل: عرض الرسوم المحسوبة *** ?>
             <div class="calculated-fee-display" style="margin-bottom: 15px; padding: 10px; background-color: #f0f0f0; border-radius: 5px; border: 1px solid #ddd;">
                 رسوم السحب المقدرة: <strong><span dir="ltr"><?php echo number_format($calculated_fee, 2, '.', ''); ?> دج</span></strong>
                 <br>
                 <small>(سيتم خصم هذه الرسوم من رصيد أرباحك عند الموافقة على الطلب)</small>
             </div>
             <?php // *** نهاية التعديل *** ?>

            <button type="submit" class="smc-button">تأكيد طلب سحب الوديعة</button>
            <div id="smc-withdraw-deposit-message" style="margin-top: 10px;"></div>
        </form>
    <?php endif; ?>
</div>

<?php get_footer(); ?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script type="text/javascript">
jQuery(document).ready(function($) {
    $('#smc-withdraw-deposit-form').on('submit', function(e) {
        e.preventDefault(); // Prevent default form submission

        var form = $(this);
        var button = form.find('button[type="submit"]');
        var messageDiv = $('#smc-withdraw-deposit-message');

        button.prop('disabled', true).text('جاري الإرسال...');
        messageDiv.text('').removeClass('smc-error-message smc-success-message').hide();

        $.ajax({
            url: form.attr('action'), // Get URL from form action attribute
            type: 'POST',
            data: form.serialize(), // Serialize form data
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'تم بنجاح!',
                        text: response.data.message,
                        confirmButtonText: 'حسناً'
                    }).then(() => {
                        window.location.href = '<?php echo esc_url(home_url("/transactional/")); ?>';
                    });
                    form[0].reset();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'خطأ!',
                        text: response.data.message || 'حدث خطأ غير متوقع.'
                    });
                    button.prop('disabled', false).text('تأكيد طلب سحب الوديعة');
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                Swal.fire({
                    icon: 'error',
                    title: 'خطأ اتصال!',
                    text: 'حدث خطأ في الاتصال بالخادم. يرجى المحاولة مرة أخرى.'
                });
                console.error("AJAX Error:", textStatus, errorThrown, jqXHR.responseText);
                button.prop('disabled', false).text('تأكيد طلب سحب الوديعة');
            }
        });
    });
});
</script>
<style>
.withdraw-page-container { max-width: 600px; margin: 20px auto; padding: 20px; background: #fff; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
.withdraw-summary { background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin-bottom: 20px; border: 1px solid #dee2e6; }
.withdraw-summary p { margin: 5px 0; font-size: 1.1em; }
.withdraw-summary strong { color: #007bff; }
.form-group { margin-bottom: 15px; }
.form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
.form-group input[type="number"],
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 10px;
    border: 1px solid #ced4da;
    border-radius: 4px;
    box-sizing: border-box;
}
.form-group textarea { resize: vertical; }
.smc-button {
    background-color: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; transition: background-color 0.3s ease; font-size: 1em;
}
.smc-button:hover { background-color: #218838; }
.smc-button:disabled { background-color: #aaa; cursor: not-allowed; }
#smc-withdraw-deposit-message.smc-error-message { color: #dc3545; background-color: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; border-radius: 5px; margin-top: 10px; }
#smc-withdraw-deposit-message.smc-success-message { color: #28a745; background-color: #d4edda; border: 1px solid #c3e6cb; padding: 10px; border-radius: 5px; margin-top: 10px; }
.smc-info-message { color: #0c5460; background-color: #d1ecf1; border: 1px solid #bee5eb; padding: 10px; border-radius: 5px; }
.smc-error-message { color: #721c24; background-color: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; border-radius: 5px; }
/* *** تعديل: تنسيق عرض الرسوم *** */
.investment-details-list { list-style-type: disc; margin-right: 20px; padding-right: 0; }
.investment-details-list li { margin-bottom: 8px; font-size: 0.95em; }
.investment-details-list strong { color: #333; }
+.investment-details-list span[dir="ltr"] { direction: ltr; display: inline-block; }
.calculated-fee-display { font-size: 0.95em; color: #555; }
.calculated-fee-display strong { color: #dc3545; } /* Red for fee */
.calculated-fee-display small { color: #6c757d; font-size: 0.9em; }
@import url("https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css");
</style>
