<?php
/**
 * Template Name: Deposit Page
 * Description: Allows users to make deposits for daily tasks or investments.
 */

if (!defined('ABSPATH')) exit; // Exit if accessed directly

// Redirect if not logged in
if (!is_user_logged_in()) {
    wp_redirect(wp_login_url(get_permalink())); // Redirect to login, then back to this page
    exit;
}

get_header();
$user_id = get_current_user_id();
$profit_balance = 0;
if (function_exists('smc_get_user_data')) {
    $user_smc_data = smc_get_user_data($user_id);
    if (is_array($user_smc_data)) {
        $profit_balance = $user_smc_data['profit_balance'] ?? 0;
    }
} else {
    $profit_balance = (float) (get_user_meta($user_id, SMC_PROFIT_BALANCE, true) ?? 0.0);
}

// --- Get All Configured Investment Types and Augment with Purchased Shares Data ---
global $wpdb;
$user_deposits_table = $wpdb->prefix . 'user_deposits';
$all_investment_types_from_option = get_option('smc_investment_types_settings', []);
$active_investment_options_html = '';
$js_investment_data = []; // To pass data to JavaScript

if (is_array($all_investment_types_from_option)) {
    foreach ($all_investment_types_from_option as $key => $investment) {
        if (isset($investment['is_active']) && $investment['is_active'] && !empty($investment['title'])) {
            $is_currently_accepting = false;
            // Use new key 'investment_acceptance_end_datetime', fallback to old 'start_datetime'
            $acceptance_end_datetime_str = $investment['investment_acceptance_end_datetime'] ?? ($investment['start_datetime'] ?? '');

            if ($acceptance_end_datetime_str) {
                $acceptance_end_timestamp = strtotime($acceptance_end_datetime_str); // Renamed for clarity
                if (current_time('timestamp') < $acceptance_end_timestamp) {
                    $is_currently_accepting = true;
                }
            } else { // If no end date for acceptance, assume it's open if active
                $is_currently_accepting = true;
            }

            $disabled_attr = $is_currently_accepting ? '' : 'disabled';
            $status_text = $is_currently_accepting ? '' : ' (مغلق حاليًا)';
            $active_investment_options_html .= '<option value="' . esc_attr($key) . '" ' . $disabled_attr . '>' . esc_html($investment['title']) . $status_text . '</option>';

            // Augment data for JS
            $total_shares_config = isset($investment['total_shares']) ? (int)$investment['total_shares'] : 0;
            $company_shares_config = isset($investment['company_shares']) ? (int)$investment['company_shares'] : 0;
            $share_price_config = isset($investment['share_price']) ? (float)$investment['share_price'] : 0;
            $min_shares_overall_config = isset($investment['min_shares_overall']) ? (int)$investment['min_shares_overall'] : 1;


            // Calculate purchased shares for this investment type
            $purchased_shares_sql = $wpdb->prepare(
                "SELECT SUM(investment_shares) FROM {$user_deposits_table} WHERE deposit_type = %s AND (status = 'approved' OR status = 'pending_admin_approval' OR status = 'pending_user_confirmation')",
                $key
            );
            $purchased_shares = (int) $wpdb->get_var($purchased_shares_sql);

            $available_for_users = $total_shares_config - $company_shares_config;
            $remaining_for_purchase = $available_for_users - $purchased_shares;
            $remaining_for_purchase = max(0, $remaining_for_purchase); // Ensure not negative

            $js_investment_data[$key] = [
                'title' => $investment['title'],
                'description' => $investment['description'] ?? '',
                'contract_text' => $investment['contract_text'] ?? 'يرجى مراجعة المسؤول لإضافة نص عقد الاستثمار.',
                'is_accepting' => $is_currently_accepting,
                'acceptance_end_datetime_formatted' => $acceptance_end_datetime_str ? date_i18n('j F Y \ع\ن\د \ت\م\ا\م H:i', strtotime($acceptance_end_datetime_str)) : 'غير محدد بعد',
                'project_cost' => $investment['project_cost'] ?? 0,
                'share_price' => $share_price_config,
                'min_shares_overall' => $min_shares_overall_config,
                'total_shares' => $total_shares_config,
                'company_shares' => $company_shares_config,
                'investment_acceptance_end_datetime' => $acceptance_end_datetime_str, // Raw value for JS logic
                'investment_start_datetime' => $investment['investment_start_datetime'] ?? '',
                'remaining_shares_for_purchase' => $remaining_for_purchase,
                'available_shares_for_users' => $available_for_users,
                'roi_plans' => $investment['roi_plans'] ?? [] // Pass ROI plans to JS
            ];
        }
    }
}

?>

<div class="container deposit-page-container">
    <h2><i class="fas fa-piggy-bank"></i> إيداع رصيد جديد</h2>

    <form id="smc-deposit-form" method="post" enctype="multipart/form-data">
        <?php // Nonce and action fields will be added by JavaScript if submitting via AJAX.
              // The 'action' hidden input is for AJAX.
        ?>
        <input type="hidden" name="action" value="smc_handle_user_deposit_request"> <?php // AJAX action hook ?>

        <div class="form-group">
            <label for="deposit-type">اختر نوع الإيداع:</label>
            <select id="deposit-type" name="deposit_type" required>
                <option value="" disabled selected>اختر نوع الإيداع:</option>
                <option value="daily_tasks">وديعة للمهام اليومية (90 يومًا)</option>
                <?php echo $active_investment_options_html; // Dynamic investment options ?>
            </select>
        </div>

        <!-- Daily Tasks Deposit Section -->
        <div id="daily-tasks-section" class="deposit-section" style="display:none;">
            <h4><i class="fas fa-tasks"></i> وديعة المهام اليومية</h4>
            <div class="form-group">
                <label for="daily-tasks-amount">المبلغ المراد إيداعه (دج):</label>
                <input type="number" id="daily-tasks-amount" name="amount" min="2000" max="500000" step="100" placeholder="أدخل المبلغ (بين 2000 و 500000)">
                <small>الوديعة مخصصة للمهام اليومية لمدة 90 يومًا.</small>
            </div>
        </div>

        <!-- Investment Deposit Section (Common structure for all dynamic investments) -->
        <div id="investment-section" class="deposit-section" style="display:none;">
            <h4 id="dynamic-investment-title"><i class="fas fa-industry"></i> تفاصيل الاستثمار</h4>

            <div id="dynamic-investment-details" class="project-details">
                <h5 id="selected-investment-title-display" style="font-size: 1.3em; color: #007bff;"></h5>
                <p id="selected-investment-description-display" style="white-space: pre-wrap;"></p>
                <p><strong>ميزانية المشروع:</strong> <span id="project-cost-display" dir="ltr">0.00 دج</span></p>
                <p><strong>سعر الحصة:</strong> <span id="share-price-display" dir="ltr">0.00 دج</span></p>
                <p><strong>الحد الأدنى لعدد الحصص للشراء في هذا المشروع:</strong> <span id="min-shares-overall-display">1</span> حصة</p>

                <p><strong>إجمالي حصص المشروع:</strong> <span id="total-shares-display">0</span> حصة</p>
                <p><strong>حصص الشركة:</strong> <span id="company-shares-display">0</span> حصص</p>
                <p><strong>الحصص المتاحة للمستخدمين:</strong> <span id="available-shares-display">0</span> حصة</p>
                <p><strong>الحصص المتبقية للشراء:</strong> <span id="remaining-shares-display" style="font-weight:bold; color: #28a745;">0</span> حصة</p>
                <p><strong>تاريخ انتهاء قبول طلبات الاستثمار:</strong> <span id="acceptance-end-date-display">N/A</span></p>
                <p><strong>تاريخ بدء الاستثمار الفعلي:</strong> <span id="actual-start-date-display">N/A</span></p>
            </div>
            <p id="selected-investment-acceptance-message" class="smc-info-message"></p>

            <!-- ROI Plans Selection Section -->
            <div id="roi-plans-selection-section" class="form-group" style="display:none; border: 1px solid #007bff; padding: 15px; border-radius: 5px; background-color: #f0f8ff; margin-top:15px;">
                <label style="font-weight: bold; color: #007bff; font-size: 1.1em; margin-bottom: 10px; display:block;">اختر خطة العائد المناسبة لك من هذا المشروع:</label>
                <div id="roi-plans-container">
                    <!-- Plans will be dynamically inserted here by JavaScript -->
                </div>
                <input type="hidden" id="selected-plan-index-input" name="selected_plan_index" value="">
            </div>

            <div class="form-group">
                <label for="investment-shares">عدد الحصص الاستثمارية (سعر الحصة <span id="dynamic-share-price-label" dir="ltr">0.00 دج</span>):</label>
                <input type="number" id="investment-shares" name="investment_shares" min="1" max="1000" placeholder="أدخل عدد الحصص (مثال: 1 أو أكثر)">
                 <small>الحد الأدنى للحصص: 1. <span id="max-shares-note">الحد الأقصى: 1000 (حسب توفر الحصص).</span></small>
            </div>

            <div class="form-group">
                <label>المبلغ الإجمالي للاستثمار (دج):</label>
                <p id="calculated-investment-amount" style="font-weight: bold; font-size: 1.2em; color: #28a745;" dir="ltr">0.00 دج</p>
            </div>
        </div>

        <!-- Investment Contract Section -->
        <div id="investment-contract-section" class="deposit-section" style="display:none;">
            <h4><i class="fas fa-file-contract"></i> عقد الاستثمار</h4>
            <div id="dynamic-contract-text-area" class="contract-text-area" style="height: 150px; overflow-y: auto; border: 1px solid #ccc; padding: 10px; margin-bottom: 10px; background-color: #f9f9f9; white-space: pre-wrap;">
                <!-- سيتم ملء نص العقد هنا بواسطة JavaScript -->
            </div>
            <div class="form-group">
                <input type="checkbox" id="accept-contract" name="accept_contract" value="1">
                <label for="accept-contract" style="display: inline; font-weight: normal;">أوافق على شروط عقد الاستثمار المذكورة أعلاه.</label>
            </div>
        </div>

        <!-- Common Fields -->
        <div class="form-group">
            <label for="payment-method">اختر طريقة الإيداع:</label>
            <select id="payment-method" name="payment_method" required>
                <option value="">-- اختر طريقة --</option>
                <option value="ccp">CCP (حساب بريدي جار)</option>
                <option value="baridimob">BaridiMob</option>
                <option value="usdt_trc20">USDT (TRC20)</option>
                <option value="profit_balance">من رصيد الأرباح (المتاح: <span dir="ltr"><?php echo number_format($profit_balance, 2, '.', ''); ?> دج</span>)</option>
            </select>
        </div>

        <div id="deposit-proof-section" class="form-group" style="display:none;">
            <label for="deposit-proof">إرفاق إثبات الدفع (صورة أو PDF):</label>
            <input type="file" id="deposit-proof" name="deposit_proof" accept="image/*,application/pdf">
            <small>مطلوب إذا لم يكن الدفع من رصيد الأرباح.</small>
        </div>

        <button type="submit" id="smc-submit-deposit" class="smc-button">تأكيد الإيداع</button>
        <div id="smc-deposit-message" style="margin-top: 15px; display: none;"></div>
    </form>
    <p style="margin-top:20px; font-size:0.9em; color:#555;">بعد إرسال النموذج، سيتم مراجعة طلب الإيداع الخاص بك (أو تنفيذه فورًا إذا كان من الأرباح).</p>
</div>

<?php get_footer(); ?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script type="text/javascript">
jQuery(document).ready(function($) {
    const depositTypeSelect = $('#deposit-type');
    const dailyTasksSection = $('#daily-tasks-section');
    const investmentSection = $('#investment-section');
    const dynamicInvestmentDetailsDiv = $('#dynamic-investment-details');
    const investmentContractSection = $('#investment-contract-section');
    const acceptContractCheckbox = $('#accept-contract');

    const dailyTasksAmountInput = $('#daily-tasks-amount');
    const investmentSharesInput = $('#investment-shares');
    const roiPlansSelectionSection = $('#roi-plans-selection-section');
    const roiPlansContainer = $('#roi-plans-container');
    const calculatedInvestmentAmountP = $('#calculated-investment-amount');
    const paymentMethodSelect = $('#payment-method');
    const depositProofSection = $('#deposit-proof-section');
    const depositProofInput = $('#deposit-proof');
    const depositForm = $('#smc-deposit-form');
    const submitButton = $('#smc-submit-deposit');
    const messageDiv = $('#smc-deposit-message');

    let investmentSharePrice = 0;
    const allInvestmentData = <?php echo json_encode($js_investment_data); ?>;

    const selectedInvestmentTitleDisplay = $('#selected-investment-title-display');
    const selectedInvestmentDescriptionDisplay = $('#selected-investment-description-display');
    const selectedInvestmentAcceptanceMessage = $('#selected-investment-acceptance-message');
    const dynamicContractTextArea = $('#dynamic-contract-text-area');

    const projectCostDisplay = $('#project-cost-display');
    const sharePriceDisplay = $('#share-price-display');
    const totalSharesDisplay = $('#total-shares-display');
    const companySharesDisplay = $('#company-shares-display');
    const availableSharesDisplay = $('#available-shares-display');
    const remainingSharesDisplay = $('#remaining-shares-display');
    const acceptanceEndDateDisplay = $('#acceptance-end-date-display');
    const actualStartDateDisplay = $('#actual-start-date-display');
    const dynamicSharePriceLabel = $('#dynamic-share-price-label');
    const minSharesOverallDisplay = $('#min-shares-overall-display');
    const maxSharesNote = $('#max-shares-note');

    function toggleSections() {
        const selectedType = depositTypeSelect.val();
        dailyTasksSection.hide();
        investmentSection.hide();
        roiPlansSelectionSection.hide(); // Hide ROI plans section
        investmentContractSection.hide();

        dailyTasksAmountInput.prop('required', false);
        $('input[name="selected_roi_plan_radio"]').prop('required', false);
        investmentSharesInput.prop('required', false);
        acceptContractCheckbox.prop('required', false).prop('checked', false);
        submitButton.prop('disabled', true); // Disable by default, enable based on conditions

        if (selectedType === 'daily_tasks') {
            dailyTasksSection.slideDown();
            dailyTasksAmountInput.prop('required', true);
            submitButton.prop('disabled', false); // Enable for daily tasks
        } else if (selectedType && allInvestmentData[selectedType]) {
            const investmentConfig = allInvestmentData[selectedType];
            investmentSection.slideDown();

            selectedInvestmentTitleDisplay.text(investmentConfig.title || 'تفاصيل الاستثمار');
            selectedInvestmentDescriptionDisplay.html(investmentConfig.description ? investmentConfig.description.replace(/\n/g, '<br>') : 'لا يوجد وصف متاح.');

            projectCostDisplay.text((parseFloat(investmentConfig.project_cost) || 0).toLocaleString('fr-DZ', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' دج');
            sharePriceDisplay.text((parseFloat(investmentConfig.share_price) || 0).toLocaleString('fr-DZ', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' دج');
            totalSharesDisplay.text(parseInt(investmentConfig.total_shares) || 0);
            minSharesOverallDisplay.text(parseInt(investmentConfig.min_shares_overall) || 1);
            companySharesDisplay.text(parseInt(investmentConfig.company_shares) || 0);
            availableSharesDisplay.text(parseInt(investmentConfig.available_shares_for_users) || 0);
            remainingSharesDisplay.text(parseInt(investmentConfig.remaining_shares_for_purchase) || 0);
            acceptanceEndDateDisplay.text(investmentConfig.acceptance_end_datetime_formatted || 'N/A');

            const actualStartDate = investmentConfig.investment_start_datetime ? new Date(investmentConfig.investment_start_datetime.replace(/-/g, '/')) : null;
            if (actualStartDate && !isNaN(actualStartDate.getTime())) {
                 actualStartDateDisplay.text(actualStartDate.toLocaleDateString('ar-DZ', { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' }));
            } else {
                 actualStartDateDisplay.text('N/A');
            }
            investmentSharePrice = parseFloat(investmentConfig.share_price) || 0;
            dynamicSharePriceLabel.text(investmentSharePrice.toLocaleString('fr-DZ', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' دج');

            const max_shares_project = parseInt(investmentConfig.remaining_shares_for_purchase) || 0;
            investmentSharesInput.attr('max', max_shares_project);
            maxSharesNote.text(`الحد الأقصى: ${max_shares_project} (حسب توفر الحصص).`);

            let acceptanceMessage = '';
            const projectAvailable = investmentConfig.is_accepting && max_shares_project > 0;

            if (projectAvailable) {
                acceptanceMessage = 'تاريخ انتهاء قبول طلبات الاستثمار لهذا المشروع هو <strong>' + investmentConfig.acceptance_end_datetime_formatted + '</strong>. لا يتم قبول طلبات الاستثمار بعد ذلك التاريخ.';
            } else if (!investmentConfig.is_accepting) {
                acceptanceMessage = 'باب الاستثمار في هذا المشروع مغلق حاليًا. ' + (investmentConfig.acceptance_end_datetime_formatted !== 'غير محدد بعد' ? 'لقد تجاوزنا تاريخ انتهاء قبول الطلبات المحدد (' + investmentConfig.acceptance_end_datetime_formatted + ').' : '');
            } else if (max_shares_project <= 0) {
                acceptanceMessage = 'جميع الحصص في هذا المشروع قد تم شراؤها. لا يمكن شراء المزيد حاليًا.';
            }
            selectedInvestmentAcceptanceMessage.html(acceptanceMessage);

            dynamicContractTextArea.html(investmentConfig.contract_text ? investmentConfig.contract_text.replace(/\n/g, '<br>') : 'لم يتم تحديد نص العقد.');
            investmentContractSection.slideDown();

            investmentSharesInput.prop('required', true);
            acceptContractCheckbox.prop('required', true);
            updateInvestmentAmount();

            // Populate ROI Plans
            roiPlansContainer.empty(); // Clear previous plans
            if (investmentConfig.roi_plans && investmentConfig.roi_plans.length > 0) {
                let hasValidPlans = false;
                investmentConfig.roi_plans.forEach(function(plan, index) {
                    if (plan.duration_value && plan.duration_unit && plan.min_roi && plan.max_roi && plan.avg_roi && plan.unit) {
                        hasValidPlans = true;
                        const planId = `roi_plan_radio_${index}`;
                        let durationUnitText = '';
                        durationUnitText = plan.duration_unit === 'minutes' ? '<?php _e('Minutes', 'smc'); ?>' : (plan.duration_unit === 'hours' ? '<?php _e('Hours', 'smc'); ?>' : '<?php _e('Days', 'smc'); ?>');

                        let roiUnitText = '';
                        if (plan.unit === 'per_minute') roiUnitText = '<?php _e('Per Minute', 'smc'); ?>';
                        else if (plan.unit === 'hourly') roiUnitText = '<?php _e('Hourly', 'smc'); ?>';
                        else if (plan.unit === 'daily') roiUnitText = '<?php _e('Daily', 'smc'); ?>';

                        const planHtml = `
                            <div class="roi-plan-option card mb-2">
                                <div class="card-body">
                                    <input type="radio" name="selected_roi_plan_radio" id="${planId}" value="${index}" required class="form-check-input" data-plan-details='${JSON.stringify(plan)}'>
                                    <label for="${planId}" class="form-check-label" style="font-weight:bold; font-size:1.1em; color:#333;">
                                        <?php _e('Plan', 'smc'); ?> ${index + 1}: ${plan.duration_value} ${durationUnitText}
                                    </label>
                                    <p style="font-size:0.9em; margin-bottom: 5px;">العائد الأدنى: ${parseFloat(plan.min_roi).toFixed(5)}% ${roiUnitText}</p>
                                    <p style="font-size:0.9em; margin-bottom: 5px;">العائد الأقصى: ${parseFloat(plan.max_roi).toFixed(5)}% ${roiUnitText}</p>
                                    <p style="font-size:0.9em; color: #28a745;"><strong>المتوسط المتوقع: ${parseFloat(plan.avg_roi).toFixed(5)}% ${roiUnitText}</strong></p>
                                </div>
                            </div>`;
                        roiPlansContainer.append(planHtml);
                    }
                });
                if (hasValidPlans) {
                    roiPlansSelectionSection.slideDown();
                    $('input[name="selected_roi_plan_radio"]').first().prop('checked', true).trigger('change'); // Select first plan by default
                    $('input[name="selected_roi_plan_radio"]').prop('required', true);
                    $('#selected-plan-index-input').val($('input[name="selected_roi_plan_radio"]:checked').val());
                }
            }

            investmentSharesInput.prop('disabled', !projectAvailable);
            $('input[name="selected_roi_plan_radio"]').prop('disabled', !projectAvailable);
            acceptContractCheckbox.prop('disabled', !projectAvailable);
            submitButton.prop('disabled', !projectAvailable || !acceptContractCheckbox.is(':checked'));

            if (!projectAvailable) {
                depositTypeSelect.find('option[value="' + selectedType + '"]').prop('disabled', true);
            }
        }
    }

    function updateInvestmentAmount() {
        const shares = parseInt(investmentSharesInput.val()) || 0;
        const totalAmount = shares * investmentSharePrice;
        calculatedInvestmentAmountP.text(totalAmount.toLocaleString('fr-DZ', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' دج');
    }

    function toggleDepositProof() {
        const selectedPaymentMethod = paymentMethodSelect.val();
        if (selectedPaymentMethod === 'profit_balance') {
            depositProofSection.slideUp();
            depositProofInput.prop('required', false);
        } else if (selectedPaymentMethod) { // If any other method is selected (and not empty)
            depositProofSection.slideDown();
            depositProofInput.prop('required', true);
        } else { // If no payment method is selected
            depositProofSection.slideUp();
            depositProofInput.prop('required', false);
        }
    }

    depositTypeSelect.on('change', toggleSections);
    investmentSharesInput.on('input', updateInvestmentAmount);

    roiPlansContainer.on('change', 'input[name="selected_roi_plan_radio"]', function() {
        const selectedType = depositTypeSelect.val();
        let max_shares_for_input = 1000; // Default package max

        $('#selected-plan-index-input').val($(this).val()); // Update hidden input

        if (selectedType && allInvestmentData[selectedType] && allInvestmentData[selectedType].min_shares_overall) {
            investmentSharesInput.attr('min', allInvestmentData[selectedType].min_shares_overall);
        } else {
            investmentSharesInput.attr('min', 1);
        }

        if (selectedType && allInvestmentData[selectedType]) {
            const project_remaining_shares = parseInt(allInvestmentData[selectedType].remaining_shares_for_purchase) || 0;
            investmentSharesInput.attr('max', Math.min(max_shares_for_input, project_remaining_shares));
            maxSharesNote.text(`الحد الأقصى: ${Math.min(max_shares_for_input, project_remaining_shares)} (حسب توفر الحصص).`);
        }
    });
    paymentMethodSelect.on('change', toggleDepositProof);
    acceptContractCheckbox.on('change', function() {
        const selectedType = depositTypeSelect.val();
        if (selectedType && allInvestmentData[selectedType] && allInvestmentData[selectedType].is_accepting && (parseInt(allInvestmentData[selectedType].remaining_shares_for_purchase) || 0) > 0) {
            submitButton.prop('disabled', !$(this).is(':checked'));
        } else if (selectedType === 'daily_tasks') {
            const dailyAmount = parseFloat(dailyTasksAmountInput.val());
            const minDaily = parseFloat(dailyTasksAmountInput.attr('min'));
            const maxDaily = parseFloat(dailyTasksAmountInput.attr('max'));
            submitButton.prop('disabled', !(dailyAmount >= minDaily && dailyAmount <= maxDaily && dailyAmount !== 0 && !isNaN(dailyAmount) ));
        }else {
            submitButton.prop('disabled', true);
        }
    });

    dailyTasksAmountInput.on('input', function() {
        if (depositTypeSelect.val() === 'daily_tasks') {
            const dailyAmount = parseFloat($(this).val());
            const minDaily = parseFloat($(this).attr('min'));
            const maxDaily = parseFloat($(this).attr('max'));
            submitButton.prop('disabled', !(dailyAmount >= minDaily && dailyAmount <= maxDaily && dailyAmount !== 0 && !isNaN(dailyAmount)));
        }
    });


    toggleSections();
    toggleDepositProof();

    depositForm.on('submit', function(e) {
        e.preventDefault();
        submitButton.prop('disabled', true).text('جاري الإرسال...');
        messageDiv.hide().removeClass('smc-success-message smc-error-message');

        const formData = new FormData(this);
        if (typeof smc_data !== 'undefined' && smc_data.user_deposit_nonce) {
            formData.append('nonce', smc_data.user_deposit_nonce);
        } else {
            Swal.fire({ icon: 'error', title: 'خطأ فادح', text: 'فشل التحقق الأمني. يرجى تحديث الصفحة والمحاولة مرة أخرى.' });
            submitButton.prop('disabled', false).text('تأكيد الإيداع');
            return;
        }

        const selectedPlanIndexVal = $('#selected-plan-index-input').val();
        if (depositTypeSelect.val() !== 'daily_tasks' && (selectedPlanIndexVal === undefined || selectedPlanIndexVal === "")) {
            Swal.fire({ icon: 'error', title: 'مطلوب', text: 'الرجاء اختيار خطة عائد للاستثمار.' });
            submitButton.prop('disabled', false).text('تأكيد الإيداع');
            return;
        }
        const selectedType = depositTypeSelect.val();
        let amountToValidate = 0;
        let minAmount = 0;
        let maxAmount = Infinity;

        if (selectedType === 'daily_tasks') {
            amountToValidate = parseFloat(dailyTasksAmountInput.val());
            minAmount = parseFloat(dailyTasksAmountInput.attr('min'));
            maxAmount = parseFloat(dailyTasksAmountInput.attr('max'));
            if (isNaN(amountToValidate) || amountToValidate < minAmount || amountToValidate > maxAmount) {
                Swal.fire({ icon: 'error', title: 'خطأ في الإدخال', text: `مبلغ إيداع المهام يجب أن يكون بين ${minAmount} و ${maxAmount} دج.` });
                submitButton.prop('disabled', false).text('تأكيد الإيداع');
                return;
            }
        } else if (selectedType && allInvestmentData[selectedType]) {
            const currentInvestmentConfig = allInvestmentData[selectedType];
            const shares = parseInt(investmentSharesInput.val());

            minAmount = parseInt(currentInvestmentConfig.min_shares_overall) || 1;
            let packageMaxShares = 1000; // Default package max


            const projectRemainingShares = parseInt(currentInvestmentConfig.remaining_shares_for_purchase) || 0;
            maxAmount = Math.min(packageMaxShares, projectRemainingShares);

            if (isNaN(shares) || shares < minAmount || shares > maxAmount) {
                Swal.fire({ icon: 'error', title: 'خطأ في الإدخال', text: `عدد الحصص يجب أن يكون بين ${minAmount} و ${maxAmount} للباقة المختارة والمشروع.` });
                submitButton.prop('disabled', false).text('تأكيد الإيداع');
                return;
            }
            amountToValidate = shares * investmentSharePrice;

            if (!currentInvestmentConfig.is_accepting || projectRemainingShares <= 0) {
                Swal.fire({ icon: 'error', title: 'غير متاح', text: 'الاستثمار في "' + currentInvestmentConfig.title + '" مغلق حاليًا أو لا توجد حصص متبقية.' });
                submitButton.prop('disabled', true).text('تأكيد الإيداع');
                return;
            }
            if (!acceptContractCheckbox.is(':checked')) {
                Swal.fire({ icon: 'error', title: 'مطلوب', text: 'يجب الموافقة على عقد الاستثمار للمتابعة.' });
                submitButton.prop('disabled', false).text('تأكيد الإيداع');
                return;
            }
        }

        if (paymentMethodSelect.val() === 'profit_balance') {
            const profitBalance = <?php echo $profit_balance; ?>;
            if (amountToValidate > profitBalance) {
                 Swal.fire({ icon: 'error', title: 'رصيد غير كافٍ', text: 'رصيد أرباحك غير كافٍ لإتمام هذا الإيداع.' });
                 submitButton.prop('disabled', false).text('تأكيد الإيداع');
                 return;
            }
        }
        if (paymentMethodSelect.val() !== 'profit_balance' && depositProofInput.get(0).files.length === 0) {
            Swal.fire({ icon: 'error', title: 'مطلوب', text: 'يرجى إرفاق ملف إثبات الدفع.' });
            submitButton.prop('disabled', false).text('تأكيد الإيداع');
            return;
        }

        $.ajax({
            url: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'تم بنجاح!',
                        text: response.data.message,
                        confirmButtonText: 'حسناً'
                    }).then(() => {
                        window.location.href = '<?php echo esc_url(home_url("/user-deposit-log/")); ?>';
                    });
                    depositForm[0].reset();
                    toggleSections(); // Reset UI
                    toggleDepositProof();
                    calculatedInvestmentAmountP.text('0.00 دج');
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'خطأ!',
                        text: response.data.message || 'حدث خطأ غير متوقع.'
                    });
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                Swal.fire({
                    icon: 'error',
                    title: 'خطأ اتصال!',
                    text: 'حدث خطأ في الاتصال بالخادم. يرجى المحاولة مرة أخرى.'
                });
                console.error("AJAX Error:", textStatus, errorThrown, jqXHR.responseText);
            },
            complete: function() {
                const currentType = depositTypeSelect.val();
                let enableButton = false;
                if (currentType === 'daily_tasks') {
                    const dailyAmount = parseFloat(dailyTasksAmountInput.val());
                    const minDaily = parseFloat(dailyTasksAmountInput.attr('min'));
                    const maxDaily = parseFloat(dailyTasksAmountInput.attr('max'));
                    enableButton = (dailyAmount >= minDaily && dailyAmount <= maxDaily && dailyAmount !== 0 && !isNaN(dailyAmount));
                } else if (currentType && allInvestmentData[currentType]) {
                    if (allInvestmentData[currentType].is_accepting && (parseInt(allInvestmentData[currentType].remaining_shares_for_purchase) || 0) > 0) {
                        enableButton = acceptContractCheckbox.is(':checked');
                    }
                }
                submitButton.prop('disabled', !enableButton).text('تأكيد الإيداع');
            }
        });
    });
});
</script>

<style>
.deposit-page-container { max-width: 700px; margin: 20px auto; padding: 25px; background-color: #fff; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
.deposit-page-container h2 { text-align: center; color: #333; margin-bottom: 25px; display: flex; align-items: center; justify-content: center; }
.deposit-page-container h2 i { margin-left: 10px; color: #007bff; }
.deposit-page-container h4 { color: #0056b3; margin-top: 20px; margin-bottom: 15px; padding-bottom: 5px; border-bottom: 1px solid #eee; display: flex; align-items: center;}
.deposit-page-container h4 i { margin-left: 8px; }

.form-group { margin-bottom: 20px; }
.form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #555; }
.form-group input[type="number"],
.form-group input[type="file"],
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 12px;
    border: 1px solid #ced4da;
    border-radius: 5px;
    box-sizing: border-box;
    font-size: 1em;
    transition: border-color 0.2s ease-in-out;
}
.form-group input[type="number"]:focus,
.form-group input[type="file"]:focus,
.form-group select:focus,
.form-group textarea:focus {
    border-color: #007bff;
    box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
}
.form-group input[type="number"] { direction: ltr; text-align: left; }
.form-group small { display: block; margin-top: 5px; color: #6c757d; font-size: 0.85em; }

.smc-button {
    background-color: #007bff; color: white; padding: 12px 25px; border: none; border-radius: 5px; cursor: pointer; transition: background-color 0.3s ease; font-size: 1.1em; display: block; width: 100%; text-align: center;
}
.smc-button:hover { background-color: #0056b3; }
.smc-button:disabled { background-color: #aaa; cursor: not-allowed; }

#smc-deposit-message.smc-error-message { color: #721c24; background-color: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; border-radius: 5px; }
#smc-deposit-message.smc-success-message { color: #155724; background-color: #d4edda; border: 1px solid #c3e6cb; padding: 10px; border-radius: 5px; }
.smc-info-message { color: #0c5460; background-color: #d1ecf1; border: 1px solid #bee5eb; padding: 10px; border-radius: 5px; margin-bottom: 15px; }

.project-details {
    background-color: #f8f9fa;
    padding: 15px;
    border-radius: 5px;
    margin-bottom: 20px;
    border: 1px solid #e9ecef;
}
.project-details h5 { color: #343a40; margin-top: 0; margin-bottom: 10px; }
.project-details p { margin-bottom: 8px; font-size: 0.95em; }
.project-details strong { color: #333; }
.project-details span[dir="ltr"] { direction: ltr; display: inline-block; }

.contract-text-area {
    background-color: #f9f9f9;
    border: 1px solid #ccc;
    padding: 10px;
    height: 150px;
    overflow-y: auto;
    margin-bottom: 10px;
    white-space: pre-wrap; /* Preserve line breaks and spaces */
    font-size: 0.9em;
    line-height: 1.5;
}
#accept-contract + label {
    font-weight: normal;
    color: #333;
    cursor: pointer;
}
#accept-contract {
    margin-left: 5px; /* RTL: margin-right */
    vertical-align: middle;
}
.roi-plan-option.card {
    border: 1px solid #ddd;
    border-radius: 5px;
    margin-bottom: 10px;
    transition: box-shadow 0.2s ease-in-out;
}
.roi-plan-option.card:hover {
    box-shadow: 0 0 10px rgba(0,123,255,.25);
}
.roi-plan-option .card-body {
    padding: 15px;
}
.roi-plan-option input[type="radio"] {
    margin-left: 10px; /* RTL: margin-right */
    vertical-align: middle;
}
.roi-plan-option label.form-check-label {
    cursor: pointer;
}


@import url("https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css");
</style>
