<?php
/**
 * Template Name: Withdraw Profit Form
 * Description: Allows users to request profit withdrawal.
 */

if (!defined('ABSPATH')) exit; // Exit if accessed directly

// Redirect if not logged in
if (!is_user_logged_in()) {
    wp_redirect(wp_login_url(home_url('/withdraw-profits/')));
    exit;
}

get_header();
$user_id = get_current_user_id();
$user_data = [];
$profit_balance = 0; // Initialize with default
$min_withdraw_profit = 600; // الحد الأدنى للسحب (Consider making this dynamic from settings)
$can_request_withdrawal = false; // Changed from $can_withdraw to reflect it's just requesting
$message = '';

// Fetch user data using the helper function
if (function_exists('smc_get_user_data')) {
    $fetched_data = smc_get_user_data($user_id);

    if (is_array($fetched_data)) {
        $user_data = $fetched_data;
        // Ensure the key is correct based on smc_get_user_data function
        $profit_balance = $user_data['profit_balance'] ?? 0;

        // For now, allow requesting withdrawal if total profit meets minimum
        if ($profit_balance >= $min_withdraw_profit) {
            $can_request_withdrawal = true;
        } else {
             $message = '<p class="smc-info-message">يجب أن تصل أرباحك الإجمالية إلى ' . number_format($min_withdraw_profit, 2, '.', '') . ' دج على الأقل لتتمكن من تقديم طلب سحب.</p>';
        }
    } else {
        error_log("SMC Warning: smc_get_user_data returned non-array in withdraw_profit_form.php for user ID: " . $user_id);
        $message = '<p class="smc-error-message">خطأ: لا يمكن جلب بيانات رصيد الأرباح.</p>';
        // Fallback logic... attempt to get meta directly
        $profit_balance = floatval(get_user_meta($user_id, SMC_PROFIT_BALANCE, true) ?: 0);
        if ($profit_balance < $min_withdraw_profit) {
             $message = '<p class="smc-info-message">يجب أن تصل أرباحك الإجمالية إلى ' . number_format($min_withdraw_profit, 2, '.', '') . ' دج على الأقل لتتمكن من تقديم طلب سحب.</p>';
        } else {
             $can_request_withdrawal = true;
        }
    }
} else {
    // Function doesn't exist, rely on fallback
    $message = '<p class="smc-error-message">خطأ: دالة جلب بيانات المستخدم غير موجودة.</p>';
    error_log("SMC Error: function smc_get_user_data() does not exist in withdraw_profit_form.php");
    $profit_balance = floatval(get_user_meta($user_id, SMC_PROFIT_BALANCE, true) ?: 0);
    if ($profit_balance >= $min_withdraw_profit) {
         $can_request_withdrawal = true;
    } else {
         $message = '<p class="smc-info-message">يجب أن تصل أرباحك الإجمالية إلى ' . number_format($min_withdraw_profit, 2, '.', '') . ' دج على الأقل لتتمكن من تقديم طلب سحب.</p>';
    }
}

// Placeholder values for locked and available profit (will be calculated dynamically later)
$locked_investment_profit = 0.00; // This needs actual calculation logic
$available_profit_for_withdrawal = $profit_balance - $locked_investment_profit; // This depends on the locked amount
?>

<div class="container withdraw-page-container">
    <h2><i class="fas fa-hand-holding-usd"></i> سحب الأرباح</h2>

    <div class="withdraw-summary">
        <p>الأرباح المتاحة للسحب: <strong><span dir="ltr"><?php echo number_format((float)$profit_balance, 2, '.', ''); ?> دج</span></strong></p>
        <!-- Add display for locked and available profit -->
        <p class="smc-info-message" style="margin-top: 10px;">
             <i class="fas fa-lock"></i> أرباح الاستثمار غير قابلة للسحب حتى نهاية مدة الاستثمار.
             <br>
             <!-- Placeholder for actual locked amount -->
             الأرباح الاستثمارية المقفلة حاليًا: <strong><span dir="ltr"><?php echo number_format((float)$locked_investment_profit, 2, '.', ''); ?> دج</span></strong>
        </p>
         <p style="margin-top: 10px;">
             الأرباح المتاحة للسحب: <strong><span dir="ltr"><?php echo number_format((float)$available_profit_for_withdrawal, 2, '.', ''); ?> دج</span></strong>
         </p>
        <?php echo $message; // Display error or info message ?>
    </div>
    <?php if ($can_request_withdrawal): ?>
        <form id="smc-withdraw-profit-form" action="<?php echo esc_url(admin_url('admin-ajax.php')); ?>" method="post">
             <?php wp_nonce_field('smc_withdraw_profit_action', 'smc_withdraw_profit_nonce'); ?>
             <input type="hidden" name="action" value="handle_withdraw_profit"> <?php // Ensure this matches the action hook in ajax-handlers.php ?>
             <input type="hidden" name="user_id" value="<?php echo esc_attr($user_id); ?>">

             <div class="form-group">
                 <label for="withdraw-profit-amount">المبلغ المراد سحبه (دج):</label>
                 <input type="number" id="withdraw-profit-amount" name="withdraw_amount" required
                        min="<?php echo esc_attr($min_withdraw_profit); ?>" <?php // Min is still the global minimum ?>
                        max="<?php echo esc_attr($available_profit_for_withdrawal); ?>" <?php // Max should be available profit ?>
                        step="0.01"
                        placeholder="أدخل المبلغ (بين <?php echo $min_withdraw_profit; ?> دج و <?php echo number_format($available_profit_for_withdrawal, 2, '.', ''); ?> دج)">
                 <small>الحد الأدنى: <?php echo number_format($min_withdraw_profit, 2, '.', ''); ?> دج، الحد الأقصى: <?php echo number_format($profit_balance, 2, '.', ''); ?> دج</small>
             </div>

             <?php // Dynamic fee calculation display area ?>
             <div id="calculated-fee-display" class="calculated-fee-display" style="margin-bottom: 15px; padding: 10px; background-color: #f0f0f0; border-radius: 5px; border: 1px solid #ddd; display: none;">
                 رسوم السحب المقدرة: <strong><span id="fee-amount-value" dir="ltr">0.00 دج</span></strong>
                 <br>
                 <small>(سيتم خصم هذه الرسوم من رصيد أرباحك عند الموافقة على الطلب)</small>
             </div>

             <div class="form-group">
                 <label for="withdraw-profit-method">طريقة السحب المفضلة:</label>
                 <select id="withdraw-profit-method" name="withdraw_method" required>
                     <option value="">-- اختر طريقة السحب --</option>
                     <option value="bank">تحويل بنكي</option>
                     <option value="baridimob">BaridiMob</option>
                     <option value="usdt_trc20">USDT (TRC20)</option>
                     <?php // Add more methods if needed ?>
                 </select>
             </div>

             <div class="form-group">
                 <label for="withdraw-profit-details">تفاصيل الحساب (رقم الحساب، RIP، عنوان USDT، إلخ):</label>
                 <textarea id="withdraw-profit-details" name="withdraw_details" rows="4" required placeholder="يرجى إدخال تفاصيل حسابك بدقة لتلقي المبلغ"></textarea>
             </div>

            <button type="submit" class="smc-button">تأكيد طلب سحب الأرباح</button>
             <div id="smc-withdraw-profit-message" style="margin-top: 10px; display: none;"></div> <?php // Initially hidden ?>
        </form>
    <?php endif; ?>
</div>

<?php get_footer(); ?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script type="text/javascript">
jQuery(document).ready(function($) {

    // --- Dynamic Fee Calculation ---
    const amountInput = $('#withdraw-profit-amount');
    const feeDisplayDiv = $('#calculated-fee-display');
    const feeAmountSpan = $('#fee-amount-value');
    // Ensure smc_data and profit_withdrawal_fee are correctly passed via wp_localize_script
    const feeSettings = (typeof smc_data !== 'undefined' && smc_data.profit_withdrawal_fee) ? smc_data.profit_withdrawal_fee : null;

    // Debugging: Log fee settings on page load
    console.log("Profit Fee Settings:", feeSettings);

    amountInput.on('input', function() {
        const amount = parseFloat($(this).val());
        let calculatedFee = 0;

        // Debugging: Log amount entered
        console.log("Amount entered:", amount);

        // Check if amount is valid and fee settings are correctly structured
        if (!isNaN(amount) && amount > 0 && feeSettings && typeof feeSettings.percentage === 'number' && typeof feeSettings.fixed === 'number') {
            calculatedFee = (amount * feeSettings.percentage) + feeSettings.fixed;
            // Ensure fee is not negative (precautionary)
            calculatedFee = Math.max(0, calculatedFee);
            feeAmountSpan.text(calculatedFee.toFixed(2) + ' دج');
            feeDisplayDiv.slideDown(); // Show the fee display
            // Debugging: Log calculated fee
            console.log("Calculated Fee:", calculatedFee);
        } else {
            feeAmountSpan.text('0.00 دج');
            feeDisplayDiv.slideUp(); // Hide the fee display
            // Debugging: Log why fee wasn't calculated
            if(isNaN(amount) || amount <= 0) console.log("Fee not calculated: Invalid or zero amount.");
            if(!feeSettings || typeof feeSettings.percentage !== 'number' || typeof feeSettings.fixed !== 'number') console.log("Fee not calculated: Invalid fee settings in smc_data.", feeSettings);
        }
    });

    // --- Form Submission Handling ---
    $('#smc-withdraw-profit-form').on('submit', function(e) {
        e.preventDefault(); // Prevent default form submission

        var form = $(this);
        var button = form.find('button[type="submit"]');
        var messageDiv = $('#smc-withdraw-profit-message');
        // var amountInput = $('#withdraw-profit-amount'); // Already defined above
        var amount = parseFloat(amountInput.val());
        var maxAmount = parseFloat(amountInput.attr('max'));
        var minAmount = parseFloat(amountInput.attr('min'));

        // Basic client-side validation
        if (isNaN(amount) || amount < minAmount || amount > maxAmount) {
            Swal.fire({
                icon: 'error',
                title: 'خطأ في المبلغ!',
                text: 'يرجى إدخال مبلغ صحيح بين ' + minAmount.toFixed(2) + ' دج و ' + maxAmount.toFixed(2) + ' دج.'
            }); // The text here should be updated to reflect "available profit"
            return; // Stop submission
        }

        // Disable button and clear messages
        button.prop('disabled', true).text('جاري الإرسال...');
        messageDiv.text('').removeClass('smc-error-message smc-success-message').hide();

        // AJAX request
        $.ajax({
            url: form.attr('action'), // admin-ajax.php
            type: 'POST',
            data: form.serialize(), // Includes action, nonce, user_id, amount, method, details
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'تم بنجاح!',
                        text: response.data.message,
                        confirmButtonText: 'حسناً'
                    }).then(() => {
                         // Redirect to transactional page after success
                         window.location.href = '<?php echo esc_url(home_url("/transactional/")); ?>';
                    });
                    form[0].reset(); // Reset form fields
                    feeDisplayDiv.slideUp(); // Hide fee display after successful submission
                } else {
                    // Show error message from server using SweetAlert
                    Swal.fire({
                        icon: 'error',
                        title: 'خطأ!',
                        text: response.data.message || 'حدث خطأ غير متوقع أثناء معالجة طلبك.'
                    });
                    button.prop('disabled', false).text('تأكيد طلب سحب الأرباح'); // Re-enable button
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                // Handle AJAX communication errors using SweetAlert
                Swal.fire({
                    icon: 'error',
                    title: 'خطأ اتصال!',
                    text: 'حدث خطأ في الاتصال بالخادم. يرجى المحاولة مرة أخرى.'
                });
                console.error("AJAX Error:", textStatus, errorThrown, jqXHR.responseText);
                button.prop('disabled', false).text('تأكيد طلب سحب الأرباح'); // Re-enable button
            }
        });
    });

    // --- (Code for cancelling requests will be added in user log files) ---

}); // End jQuery(document).ready
</script>

<style>
/* Use same styles as withdraw_deposit_form.php */
.withdraw-page-container { max-width: 600px; margin: 20px auto; padding: 20px; background: #fff; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
.withdraw-summary { background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin-bottom: 20px; border: 1px solid #dee2e6; }
.withdraw-summary p { margin: 5px 0; font-size: 1.1em; }
.withdraw-summary strong { color: #28a745; } /* Green for profit */
.form-group { margin-bottom: 15px; }
.form-group label { display: block; margin-bottom: 5px; font-weight: bold; text-align: right; }
.form-group input[type="number"],
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 10px;
    border: 1px solid #ced4da;
    border-radius: 4px;
    box-sizing: border-box; /* Include padding and border in the element's total width and height */
}
/* Ensure number input aligns correctly */
.form-group input[type="number"] {
    direction: ltr !important; /* Force LTR for numbers */
    text-align: left; /* Align numbers left */
}
.form-group select {
    text-align: right; /* Align select text right for Arabic */
}
.form-group textarea {
    resize: vertical;
    direction: rtl; /* RTL for textarea */
    text-align: right !important;
}
.form-group small { display: block; margin-top: 5px; color: #6c757d; font-size: 0.9em; }
.smc-button {
    background-color: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; transition: background-color 0.3s ease; font-size: 1em; display: block; width: 100%; text-align: center;
}
.smc-button:hover { background-color: #218838; }
.smc-button:disabled { background-color: #aaa; cursor: not-allowed; }
#smc-withdraw-profit-message { margin-top: 15px; } /* Add margin for messages */
/* General error/success/info message styles (can be used by messageDiv or directly) */
.smc-error-message {
    color: #721c24; background-color: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; border-radius: 5px; margin-top: 10px;
}
.smc-success-message {
    color: #155724; background-color: #d4edda; border: 1px solid #c3e6cb; padding: 10px; border-radius: 5px; margin-top: 10px;
}
.smc-info-message {
    color: #0c5460; background-color: #d1ecf1; border: 1px solid #bee5eb; padding: 10px; border-radius: 5px;
}
/* Fee display styling */
.calculated-fee-display { font-size: 0.95em; color: #555; }
.calculated-fee-display strong { color: #dc3545; } /* Red for fee */
.calculated-fee-display strong span[dir="ltr"] { direction: ltr !important; } /* Ensure fee amount is LTR */
.calculated-fee-display small { color: #6c757d; font-size: 0.9em; }
/* Ensure Font Awesome is loaded (usually done globally) */
@import url("https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css");
</style>
