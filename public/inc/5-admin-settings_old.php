<?php
/**
 * Handles the SMC Admin Settings Page, including Reward/Fee settings.
 */

if (!defined('ABSPATH')) exit; // Exit if accessed directly

// --- Constants ---
// Defined in constants.php now: SMC_REWARD_SETTINGS_OPTION

// --- Default Settings ---
// Defined in helpers.php now: smc_get_default_reward_settings()

// --- Admin Menu ---
/**
 * Adds the SMC Settings page to the admin menu.
 */
function smc_add_admin_menu() {
    // Ensure constants/functions are available before adding menus/settings
    if (!defined('SMC_REWARD_SETTINGS_OPTION') || !function_exists('smc_get_default_reward_settings')) {
        // Log an error or add an admin notice if dependencies are missing
        error_log("SMC Error: Missing dependencies (constants/helpers) in smc_add_admin_menu.");
        add_action('admin_notices', function() {
            echo '<div class="notice notice-error"><p>SMC Plugin Error: Missing core dependencies. Settings page may not function correctly.</p></div>';
        });
        // Optionally prevent adding the menu if dependencies are critical
        // return;
    }

    add_menu_page(
        __('SMC Settings', 'smc'), // Page title
        __('SMC Settings', 'smc'), // Menu title
        'manage_options',          // Capability required
        'smc-settings',            // Menu slug
        'smc_render_settings_page', // Function to render the page
        'dashicons-admin-generic', // Icon
        25                         // Position
    );

    // Sub-menu for Reward Settings
    add_submenu_page(
        'smc-settings',
        __('Reward & Fee Settings', 'smc'),
        __('Rewards & Fees', 'smc'),
        'manage_options',
        'smc-reward-settings', // Slug for the reward settings page
        'smc_render_reward_settings_page' // Callback for the reward settings page
    );

    // Sub-menu for Ad Deal Settings
    add_submenu_page(
        'smc-settings', // Parent slug
        'إعدادات الصفقة الإعلانية', // Page title
        'صفقات الإعلانات', // Menu title
        'manage_options', // Capability
        'ad-deal-settings', // Menu slug
        'smc_render_ad_settings_page' // Callback function
    );

    // Sub-menu for General Investment Settings
    add_submenu_page(
        'smc-settings',
        __('Investment Types Settings', 'smc'),
        __('Investment Types', 'smc'), // عنوان القائمة الفرعية
        'manage_options',
        'smc-investment-types-settings', // Slug for the investment types settings page
        'smc_render_investment_types_settings_page' // Callback
    );

    // Sub-menu for Cron Jobs Status
    add_submenu_page(
        'smc-settings',
        __('Cron Jobs Status', 'smc'),
        __('Cron Jobs', 'smc'),
        'manage_options',
        'smc-cron-jobs-status', // Slug for the cron jobs status page
        'smc_render_cron_jobs_status_page' // Callback
    );
    // Add other submenus if needed
}
add_action('admin_menu', 'smc_add_admin_menu');

// --- Register Settings ---
/**
 * Registers the settings group and the specific setting option.
 */
function smc_register_settings() {
    // Ensure constants/functions are available
    if (!defined('SMC_REWARD_SETTINGS_OPTION') || !function_exists('smc_sanitize_reward_settings')) {
         error_log("SMC Error: Missing dependencies (constants/sanitizer) in smc_register_settings.");
         // Add admin notice
         add_action('admin_notices', function() {
             echo '<div class="notice notice-error"><p>SMC Plugin Error: Missing core dependencies. Settings may not save correctly.</p></div>';
         });
         return; // Prevent registration if dependencies missing
    }

    // Register the main setting group used in the form for Rewards & Fees
    register_setting(
        'smc-settings-group',             // Option group (used in settings_fields())
        SMC_REWARD_SETTINGS_OPTION,       // Option name (database key)
        'smc_sanitize_reward_settings'    // Sanitization callback function
    );

    // --- Reward Settings Section ---
    add_settings_section(
        'smc_reward_settings_section',        // Section ID
        __('Reward & Fee Configuration', 'smc'), // Section title
        'smc_rewards_section_callback',     // Callback for description
        'smc-reward-settings'               // Page slug where this section appears
    );
    
    add_settings_field(
        'smc_rewards_table_field',          // Field ID
        __('Configure Rewards & Fees', 'smc'), // Field title (label)
        'smc_rewards_table_field_callback', // Callback to render the table
        'smc-reward-settings',              // Page slug
        'smc_reward_settings_section'       // Section ID this field belongs to
    );
    
    // Register Ad Deal Settings
    register_setting(
        'smc_ad_deal_options_group', 
        SMC_AD_SETTINGS_OPTION, 
        'smc_sanitize_ad_deal_settings'
    );
    add_settings_section(
        'smc_ad_deal_plans_section', 
        'تعديل خطط الصفقات الإعلانية', 
        'smc_ad_deal_plans_section_callback', 
        'ad-deal-settings'
    );
    add_settings_field(
        'smc_ad_deal_table_field', 
        'جدول الخطط', 
        'smc_ad_deal_table_field_callback', 
        'ad-deal-settings', 
        'smc_ad_deal_plans_section'
    );
    add_settings_field(
        'smc_ad_deal_tax_rate_field', 
        'معدل الضريبة العام (%)', 
        'smc_ad_deal_tax_rate_field_callback', 
        'ad-deal-settings', 
        'smc_ad_deal_plans_section'
    );

    // Register the option for general investment types
    register_setting(
        'smc-investment-types-group',          // New option group
        'smc_investment_types_settings',       // New option name
        'smc_sanitize_investment_types_settings' // Sanitization callback
    );
}
add_action('admin_init', 'smc_register_settings');

// --- Callback Functions ---

/**
 * Renders the main SMC Settings page container (Overview).
 */
function smc_render_settings_page() {
    ?>
    <div class="wrap smc-settings-wrap">
        <h1><i class="dashicons dashicons-admin-generic"></i> <?php _e('Supermarket Chains (SMC) Settings', 'smc'); ?></h1>
        
        <h2 class="nav-tab-wrapper">
            <a href="<?php echo admin_url('admin.php?page=smc-settings'); ?>" class="nav-tab nav-tab-active"><?php _e('Overview', 'smc'); ?></a>
            <a href="<?php echo admin_url('admin.php?page=smc-reward-settings'); ?>" class="nav-tab"><?php _e('Rewards & Fees', 'smc'); ?></a>
            <a href="<?php echo admin_url('admin.php?page=ad-deal-settings'); ?>" class="nav-tab"><?php _e('Ad Deal Settings', 'smc'); ?></a>
            <a href="<?php echo admin_url('admin.php?page=smc-investment-types-settings'); ?>" class="nav-tab"><?php _e('Investment Types', 'smc'); ?></a>
            <a href="<?php echo admin_url('admin.php?page=smc-cron-jobs-status'); ?>" class="nav-tab"><?php _e('Cron Jobs', 'smc'); ?></a>
        </h2>
        <p><?php _e('This is the main overview page for SMC settings. Select a tab above to manage specific configurations or use the quick access links below.', 'smc'); ?></p>

        <?php
        if (function_exists('smc_display_admin_dashboard_sections')) {
            smc_display_admin_dashboard_sections();
        } else {
            echo '<p class="smc-error-message">Error: Overview display function is missing.</p>';
        }
        ?>
    </div>
    <?php
}

/**
 * Renders the dedicated Investment Types Settings page.
 */
function smc_render_investment_types_settings_page() {
    $current_view = isset($_GET['view']) ? sanitize_key($_GET['view']) : 'list_configs'; // Default to list_configs view
    $project_key_to_edit = isset($_GET['edit_project_key']) ? sanitize_key($_GET['edit_project_key']) : null;

    ?>
    <div class="wrap smc-settings-wrap">
        <h1><i class="dashicons dashicons-money-alt"></i> <?php _e('Manage Investment Types', 'smc'); ?></h1>

        <h2 class="nav-tab-wrapper">
            <a href="<?php echo admin_url('admin.php?page=smc-settings'); ?>" class="nav-tab"><?php _e('Overview', 'smc'); ?></a>
            <a href="<?php echo admin_url('admin.php?page=smc-reward-settings'); ?>" class="nav-tab"><?php _e('Rewards & Fees', 'smc'); ?></a>
            <a href="<?php echo admin_url('admin.php?page=ad-deal-settings'); ?>" class="nav-tab"><?php _e('Ad Deal Settings', 'smc'); ?></a>
            <a href="<?php echo admin_url('admin.php?page=smc-investment-types-settings'); ?>" class="nav-tab nav-tab-active"><?php _e('Investment Types', 'smc'); ?></a>
            <a href="<?php echo admin_url('admin.php?page=smc-cron-jobs-status'); ?>" class="nav-tab"><?php _e('Cron Jobs', 'smc'); ?></a>
        </h2>
        <div style="margin-bottom: 20px; margin-top:15px;">
            <a href="<?php echo admin_url('admin.php?page=smc-investment-types-settings&view=list_configs'); ?>" class="button <?php echo ($current_view === 'list_configs' && !$project_key_to_edit) ? 'button-primary' : ''; ?>">
                <i class="dashicons dashicons-list-view" style="vertical-align: middle; margin-top: -2px;"></i> <?php _e('Investment Configurations List', 'smc'); ?>
            </a>
            <a href="<?php echo admin_url('admin.php?page=smc-investment-types-settings&view=add_new'); ?>" class="button <?php echo ($current_view === 'add_new') ? 'button-primary' : ''; ?>">
                <i class="dashicons dashicons-plus-alt" style="vertical-align: middle; margin-top: -2px;"></i> <?php _e('Add New Investment Type', 'smc'); ?>
            </a>
        </div>
        
        <hr>

        <?php
        settings_errors('smc-investment-types-group'); // Show errors for the forms on this page

        if ($current_view === 'add_new') {
            smc_render_add_investment_form();
        } elseif ($current_view === 'edit_project' && $project_key_to_edit) {
            smc_render_edit_investment_form($project_key_to_edit);
        } else { // Default to 'list_configs'
            smc_render_investment_configurations_list();
        }

        // Output the ROI calculation script if we are on add/edit view
        if ($current_view === 'add_new' || ($current_view === 'edit_project' && $project_key_to_edit)):
        ?>
        <script type="text/javascript">
            jQuery(document).ready(function($) {
                // Calculate average ROI for edit/add forms
                $('form[action="options.php"]').on('input change', 
                    '.roi-input, input[name*="[share_price]"], input[name*="[avg_roi]"], input[name*="[duration_value]"], select[name*="[duration_unit]"], select[name*="[unit]"]', 
                    function() {
                    const planItem = $(this).closest('.roi-plan-item');
                    const investmentItem = $(this).closest('.smc-investment-type-item');
                    
                    // Auto-calculate Average ROI if min_roi or max_roi changed
                    const minRoiInput = planItem.find('input[name*="[min_roi]"]');
                    const maxRoiInput = planItem.find('input[name*="[max_roi]"]');
                    const avgRoiInputField = planItem.find('input[name*="[avg_roi]"]'); 
                    
                    if ($(this).hasClass('roi-input')) { // If min_roi or max_roi triggered this
                        if (minRoiInput.val() !== '' && maxRoiInput.val() !== '') {
                             const minRoi = parseFloat(minRoiInput.val()) || 0;
                             const maxRoi = parseFloat(maxRoiInput.val()) || 0;
                             const avgRoi = (minRoi + maxRoi) / 2;
                             avgRoiInputField.val(avgRoi.toFixed(5)); 
                             // The example calculation below will now use this updated avg_roi
                        } else if (minRoiInput.val() === '' && maxRoiInput.val() === '') {
                            // If both min/max are empty, don't clear avg_roi. Let it be manually entered or retain its value.
                        }
                    }

                    // Example Calculation
                    const sharePriceInput = investmentItem.find('input[name*="[share_price]"]');
                    const planDurationValueInput = planItem.find('input[name*="[duration_value]"]');
                    const planDurationUnitSelect = planItem.find('select[name*="[duration_unit]"]');
                    const planRoiUnitSelect = planItem.find('select[name*="[unit]"]');
                    
                    const exampleDiv = planItem.find('.roi-plan-example');
                    const calcProfitPerUnitSpan = exampleDiv.find('.calc-profit-per-unit');
                    const calcDailyProfitSpan = exampleDiv.find('.calc-daily-profit');
                    const calcTotalProfitSpan = exampleDiv.find('.calc-total-profit');

                    const sharePrice = parseFloat(sharePriceInput.val()) || 0;
                    const planAvgRoiPercent = parseFloat(avgRoiInputField.val()) || 0; 
                    const planDurationValue = parseInt(planDurationValueInput.val()) || 0;
                    const planDurationUnit = planDurationUnitSelect.val(); // 'minutes', 'hours', 'days'
                    const planRoiUnit = planRoiUnitSelect.val(); // 'per_minute', 'hourly', 'daily'

                    if (sharePrice > 0 && planAvgRoiPercent > 0 && planDurationValue > 0) {
                        const planAvgRoiDecimal = planAvgRoiPercent / 100;
                        let profitPerPlanUnit = sharePrice * planAvgRoiDecimal;
                        calcProfitPerUnitSpan.text(profitPerPlanUnit.toFixed(5));

                        let estimatedDailyProfit = 0;
                        if (planRoiUnit === 'daily') {
                            estimatedDailyProfit = profitPerPlanUnit;
                        } else if (planRoiUnit === 'hourly') {
                            estimatedDailyProfit = profitPerPlanUnit * 24;
                        } else if (planRoiUnit === 'per_minute') {
                            estimatedDailyProfit = profitPerPlanUnit * 60 * 24;
                        }
                        calcDailyProfitSpan.text(estimatedDailyProfit.toFixed(5));

                        let totalUnitsInPlanDuration = 0;
                        let planDurationInSeconds = 0;
                        if (planDurationUnit === 'days') planDurationInSeconds = planDurationValue * 86400;
                        else if (planDurationUnit === 'hours') planDurationInSeconds = planDurationValue * 3600; // 60 * 60
                        else if (planDurationUnit === 'minutes') planDurationInSeconds = planDurationValue * 60; // 60

                        if (planDurationInSeconds > 0) { // Avoid division by zero
                            if (planRoiUnit === 'daily') totalUnitsInPlanDuration = planDurationInSeconds / 86400;
                            else if (planRoiUnit === 'hourly') totalUnitsInPlanDuration = planDurationInSeconds / 3600;
                            else if (planRoiUnit === 'per_minute') totalUnitsInPlanDuration = planDurationInSeconds / 60;
                        }
                        
                        let totalEstimatedProfit = profitPerPlanUnit * totalUnitsInPlanDuration;
                        calcTotalProfitSpan.text(totalEstimatedProfit.toFixed(2));
                    } else {
                        calcProfitPerUnitSpan.text('0.00');
                        calcDailyProfitSpan.text('0.00');
                        calcTotalProfitSpan.text('0.00');
                    }
                });
                // Trigger calculation on page load for existing values in edit form
                if ('<?php echo $current_view; ?>' === 'edit_project') {
                    // Trigger for share price first as it affects all plans
                     $('input[name*="[share_price]"]').trigger('input');
                    
                    $('.roi-plan-item').each(function() {
                        $(this).find('input[name*="[min_roi]"]').trigger('input'); // This will calc avg_roi if needed, then example runs
                        // If avg_roi is pre-filled and min/max are not, ensure its example is calculated
                        if (!$(this).find('input[name*="[min_roi]"]').val() && !$(this).find('input[name*="[max_roi]"]').val() && $(this).find('input[name*="[avg_roi]"]').val()) {
                            $(this).find('input[name*="[avg_roi]"]').trigger('input');
                        }
                        $(this).find('input[name*="[duration_value]"]').trigger('input');
                        $(this).find('select[name*="[duration_unit]"]').trigger('change');
                        $(this).find('select[name*="[unit]"]').trigger('change');
                    });
                }
            });
        </script>
        <?php
        endif;
        ?>
    </div>
    <?php // Script for Investment Types page (list_configs view buttons) ?>
    <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Cancel Old Investments Button
            $('#smc-admin-cancel-old-investments-btn').on('click', function() {
                const button = $(this);
                Swal.fire({
                    title: '<?php echo esc_js(__('تأكيد الإجراء الخطير!', 'smc')); ?>',
                    html: '<?php echo esc_js(__('هل أنت متأكد تمامًا من رغبتك في إلغاء جميع أنواع الاستثمار التي تم إنشاؤها قبل <code>new_investment_1000011</code>؟<br>سيتم استرداد مبالغ الودائع النشطة لهذه المشاريع إلى أرصدة أرباح المستخدمين، وسيتم حذف أنواع الاستثمار هذه نهائيًا.<br><strong>هذا الإجراء لا يمكن التراجع عنه.</strong>', 'smc')); ?>',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: '<?php echo esc_js(__('نعم، قم بالإلغاء والاسترداد!', 'smc')); ?>',
                    cancelButtonText: '<?php echo esc_js(__('تراجع', 'smc')); ?>'
                }).then((result) => {
                    if (result.isConfirmed) {
                        button.prop('disabled', true).html('<span class="dashicons dashicons-update-alt spin"></span> <?php echo esc_js(__('جاري المعالجة...', 'smc')); ?>');
                        $.ajax({
                            url: ajaxurl,
                            type: 'POST',
                            data: {
                                action: 'smc_admin_cancel_old_investments_and_refund',
                                nonce: smc_data.admin_cancel_old_investments_nonce // Make sure this nonce is localized
                            },
                            dataType: 'json',
                            success: function(response) {
                                if (response.success) {
                                    Swal.fire('<?php echo esc_js(__('تم بنجاح!', 'smc')); ?>', response.data.message, 'success').then(() => location.reload());
                                } else {
                                    Swال.fire('<?php echo esc_js(__('خطأ!', 'smc')); ?>', response.data.message || '<?php echo esc_js(__('فشل تنفيذ الإجراء.', 'smc')); ?>', 'error');
                                    button.prop('disabled', false).html('<i class="dashicons dashicons-trash"></i> <?php echo esc_js(__('إلغاء جميع الاستثمارات ما قبل new_investment_1000011 وإعادة الأموال', 'smc')); ?>');
                                }
                            },
                            error: function() {
                                Swal.fire('<?php echo esc_js(__('خطأ اتصال!', 'smc')); ?>', '<?php echo esc_js(__('لا يمكن الاتصال بالخادم.', 'smc')); ?>', 'error');
                                button.prop('disabled', false).html('<i class="dashicons dashicons-trash"></i> <?php echo esc_js(__('إلغاء جميع الاستثمارات ما قبل new_investment_1000011 وإعادة الأموال', 'smc')); ?>');
                            }
                        });
                    }
                });
            });
        });
    </script>
    <?php
}

/**
 * Renders the form for adding a new investment type.
 */
function smc_render_add_investment_form() {
    ?>
    <h3><?php _e('Add New Investment Type', 'smc'); ?></h3>
    <form method="post" action="options.php">
        <?php
        settings_fields('smc-investment-types-group');
        // Pass a unique key for the new item, e.g., based on timestamp or a counter
        // For simplicity, we'll use a generic placeholder and expect the 'key' field to be filled by admin
        $new_item_form_key = 'new_investment_' . time(); 
        echo smc_get_investment_form_fields_html($new_item_form_key, [], true); // true for is_new
        submit_button(__('Add Investment Type', 'smc'));
        ?>
    </form>
    <?php
}

/**
 * Renders the form for editing an existing investment type.
 * @param string $project_key The key of the project to edit.
 */
function smc_render_edit_investment_form($project_key) {
    $all_investments = get_option('smc_investment_types_settings', []);
    $investment_data = $all_investments[$project_key] ?? null;

    if (!$investment_data) {
        echo '<div class="notice notice-error"><p>' . __('Investment type not found.', 'smc') . '</p></div>';
        return;
    }
    ?>
    <h3><?php printf(__('Edit Investment Type: %s', 'smc'), esc_html($investment_data['title'] ?? $project_key)); ?></h3>
    <form method="post" action="options.php">
        <?php
        settings_fields('smc-investment-types-group');
        echo smc_get_investment_form_fields_html($project_key, $investment_data, false); // false for is_new
        submit_button(__('Save Changes', 'smc'));
        ?>
    </form>
    <?php
}


/**
 * Generates HTML for investment form fields for a single investment.
 *
 * @param string $item_key The key for the investment item (e.g., 'project_alpha' or 'new_investment_xxx').
 * @param array  $investment The investment data array.
 * @param bool   $is_new True if this is for a new investment, false for editing.
 * @return string HTML for the form fields.
 */
function smc_get_investment_form_fields_html($item_key, $investment = [], $is_new = false) {
    // Ensure all expected keys exist to avoid notices, using defaults from an empty structure
    $defaults = [
        'title' => '', 'description' => '', 'project_cost' => '', 'share_price' => '',
        'total_shares' => '', 'company_shares' => '',
        'investment_acceptance_end_datetime' => '', 'investment_start_datetime' => '',
        'creation_date' => $is_new ? current_time('mysql') : ($investment['creation_date'] ?? ''),
        'contract_text' => '', 'roi_plans' => [],
        'final_profit_margin_recorded' => false, 'is_active' => true,
        'project_duration_value' => 90,
        'project_duration_unit' => 'days',
        'daily_production_hours' => 21,
        'production_days_in_project' => 78,
        'production_expenses' => [], // For the new table
        'final_margin_inputs' => [], // For the second new table inputs
        'min_shares_overall' => 1, // Default min shares
    ];
    $investment = wp_parse_args($investment, $defaults);

    ob_start();
    ?>
    <div class="smc-investment-type-item" data-key="<?php echo esc_attr($item_key); ?>">
        <?php if ($is_new): ?>
            <input type="hidden" name="smc_investment_types_settings[<?php echo esc_attr($item_key); ?>][is_new_marker]" value="1">
            <p><label><strong><?php _e('Unique Key:', 'smc'); ?></strong> (e.g., project_alpha, lowercase, underscores)<br>
                <input type="text" name="smc_investment_types_settings[<?php echo esc_attr($item_key); ?>][key_input]" value="" class="regular-text smc-investment-key-input" required style="width: 95%;">
            </label></p>
        <?php else: ?>
            <p><label><strong><?php _e('Unique Key:', 'smc'); ?></strong><br>
                <input type="text" name="smc_investment_types_settings[<?php echo esc_attr($item_key); ?>][key_input]" value="<?php echo esc_attr($item_key); ?>" class="regular-text smc-investment-key-input" readonly style="background:#eee; width: 95%;">
            </label></p>
        <?php endif; ?>

        <p><label><strong><?php _e('Creation Date:', 'smc'); ?></strong><br>
            <input type="text" name="smc_investment_types_settings[<?php echo esc_attr($item_key); ?>][creation_date]" value="<?php echo esc_attr($investment['creation_date']); ?>" class="regular-text" readonly style="background:#eee; width: 95%;">
        </label></p>
        
        <p><label><strong><?php _e('Investment Title:', 'smc'); ?></strong><br>
            <input type="text" name="smc_investment_types_settings[<?php echo esc_attr($item_key); ?>][title]" value="<?php echo esc_attr($investment['title']); ?>" class="regular-text smc-investment-title-input" required>
        </label></p>
        <p><label><strong><?php _e('Descriptive Text:', 'smc'); ?></strong><br>
            <textarea name="smc_investment_types_settings[<?php echo esc_attr($item_key); ?>][description]" rows="5" class="large-text"><?php echo esc_textarea($investment['description']); ?></textarea>
        </label></p>
        <p><label><strong><?php _e('Share Price (DZD):', 'smc'); ?></strong><br>
            <input type="number" step="any" name="smc_investment_types_settings[<?php echo esc_attr($item_key); ?>][share_price]" value="<?php echo esc_attr($investment['share_price']); ?>" class="regular-text" placeholder="<?php esc_attr_e('e.g., 100000', 'smc'); ?>">
        </label></p>
         <p><label><strong><?php _e('Minimum Shares to Purchase (Overall Project):', 'smc'); ?></strong><br>
            <input type="number" step="1" min="1" name="smc_investment_types_settings[<?php echo esc_attr($item_key); ?>][min_shares_overall]" value="<?php echo esc_attr($investment['min_shares_overall']); ?>" class="regular-text" placeholder="<?php esc_attr_e('e.g., 1', 'smc'); ?>">
        </label></p>
        <p><label><strong><?php _e('Project Cost (DZD):', 'smc'); ?></strong><br>
            <input type="number" step="any" name="smc_investment_types_settings[<?php echo esc_attr($item_key); ?>][project_cost]" value="<?php echo esc_attr($investment['project_cost']); ?>" class="regular-text" placeholder="<?php esc_attr_e('e.g., 1000000', 'smc'); ?>">
        </label></p>
        <p><label><strong><?php _e('Total Shares for Project:', 'smc'); ?></strong><br>
            <input type="number" step="1" name="smc_investment_types_settings[<?php echo esc_attr($item_key); ?>][total_shares]" value="<?php echo esc_attr($investment['total_shares']); ?>" class="regular-text" placeholder="<?php esc_attr_e('e.g., 100', 'smc'); ?>">
        </label></p>
        <p><label><strong><?php _e('Company Shares:', 'smc'); ?></strong><br>
            <input type="number" step="1" name="smc_investment_types_settings[<?php echo esc_attr($item_key); ?>][company_shares]" value="<?php echo esc_attr($investment['company_shares']); ?>" class="regular-text" placeholder="<?php esc_attr_e('e.g., 10', 'smc'); ?>">
        </label></p>
        <p><label><strong><?php _e('Investment Acceptance End Date & Time:', 'smc'); ?></strong><br>
            <input type="datetime-local" name="smc_investment_types_settings[<?php echo esc_attr($item_key); ?>][investment_acceptance_end_datetime]" value="<?php echo esc_attr($investment['investment_acceptance_end_datetime']); ?>" class="regular-text">
        </label></p>
        <p><label><strong><?php _e('Investment Actual Start Date & Time:', 'smc'); ?></strong><br>
            <input type="datetime-local" name="smc_investment_types_settings[<?php echo esc_attr($item_key); ?>][investment_start_datetime]" value="<?php echo esc_attr($investment['investment_start_datetime']); ?>" class="regular-text">
        </label></p>

        <p><label><strong><?php _e('Project Duration:', 'smc'); ?></strong><br>
            <input type="number" min="1" name="smc_investment_types_settings[<?php echo esc_attr($item_key); ?>][project_duration_value]" value="<?php echo esc_attr($investment['project_duration_value']); ?>" class="small-text" placeholder="<?php esc_attr_e('e.g., 90', 'smc'); ?>">
            <select name="smc_investment_types_settings[<?php echo esc_attr($item_key); ?>][project_duration_unit]">
                <option value="hours" <?php selected($investment['project_duration_unit'], 'hours'); ?>><?php _e('Hours', 'smc'); ?></option>
                <option value="days" <?php selected($investment['project_duration_unit'], 'days'); ?>><?php _e('Days', 'smc'); ?></option>
            </select>
        </label></p>
        <p><label><strong><?php _e('Daily Production Hours:', 'smc'); ?></strong><br>
            <input type="number" min="0" max="24" step="1" name="smc_investment_types_settings[<?php echo esc_attr($item_key); ?>][daily_production_hours]" value="<?php echo esc_attr($investment['daily_production_hours']); ?>" class="small-text" placeholder="<?php esc_attr_e('e.g., 21', 'smc'); ?>"> <?php _e('hours', 'smc'); ?>
        </label></p>
        <p><label><strong><?php _e('Production Days during Project Duration:', 'smc'); ?></strong><br>
            <input type="number" min="0" step="1" name="smc_investment_types_settings[<?php echo esc_attr($item_key); ?>][production_days_in_project]" value="<?php echo esc_attr($investment['production_days_in_project']); ?>" class="small-text" placeholder="<?php esc_attr_e('e.g., 78', 'smc'); ?>"> <?php _e('days', 'smc'); ?>
        </label></p>

        <hr>
        <h4><?php _e('Production Expenses Table', 'smc'); ?> (<?php _e('جدول لإدخال البيانات', 'smc'); ?>)</h4>
        <?php echo smc_get_production_expenses_table_html($item_key, $investment['production_expenses']); ?>

        <div class="roi-plans-section">
            <h4><?php _e('ROI Plans (up to 4):', 'smc'); ?></h4>
            <?php for ($i = 0; $i < 4; $i++):
                $plan = $investment['roi_plans'][$i] ?? [
                    'duration_value' => 90, 'duration_unit' => 'days',
                    'min_roi' => '', 'max_roi' => '', 'avg_roi' => '', 'unit' => 'daily'
                ];
            ?>
            <div class="roi-plan-item" style="border: 1px solid #eee; padding: 10px; margin-bottom: 10px;">
                <h5><?php printf(__('Plan %d', 'smc'), $i + 1); ?></h5>
                <p><label><strong><?php _e('Plan Duration:', 'smc'); ?></strong><br>
                    <input type="number" min="1" name="smc_investment_types_settings[<?php echo esc_attr($item_key); ?>][roi_plans][<?php echo $i; ?>][duration_value]" value="<?php echo esc_attr($plan['duration_value']); ?>" class="small-text" placeholder="<?php esc_attr_e('e.g., 90', 'smc'); ?>">
                    <select name="smc_investment_types_settings[<?php echo esc_attr($item_key); ?>][roi_plans][<?php echo $i; ?>][duration_unit]">
                        <option value="minutes" <?php selected($plan['duration_unit'], 'minutes'); ?>><?php _e('Minutes', 'smc'); ?></option>
                        <option value="hours" <?php selected($plan['duration_unit'], 'hours'); ?>><?php _e('Hours', 'smc'); ?></option>
                        <option value="days" <?php selected($plan['duration_unit'], 'days'); ?>><?php _e('Days', 'smc'); ?></option>
                    </select>
                </label></p>
                <label><?php _e('Min ROI (%):', 'smc'); ?>
                    <input type="number" step="any" name="smc_investment_types_settings[<?php echo esc_attr($item_key); ?>][roi_plans][<?php echo $i; ?>][min_roi]" value="<?php echo esc_attr($plan['min_roi']); ?>" class="small-text roi-input" placeholder="e.g., 1.0">
                </label>
                <label><?php _e('Max ROI (%):', 'smc'); ?>
                    <input type="number" step="any" name="smc_investment_types_settings[<?php echo esc_attr($item_key); ?>][roi_plans][<?php echo $i; ?>][max_roi]" value="<?php echo esc_attr($plan['max_roi']); ?>" class="small-text roi-input" placeholder="e.g., 1.4">
                </label>
                <label><?php _e('Average ROI (%):', 'smc'); ?>
                    <input type="number" step="any" name="smc_investment_types_settings[<?php echo esc_attr($item_key); ?>][roi_plans][<?php echo $i; ?>][avg_roi]" value="<?php echo esc_attr($plan['avg_roi']); ?>" class="small-text roi-avg-output" readonly style="background:#eee;" placeholder="<?php esc_attr_e('Auto-calculated', 'smc'); ?>">
                </label>
                <label><?php _e('ROI Unit:', 'smc'); ?>
                    <select name="smc_investment_types_settings[<?php echo esc_attr($item_key); ?>][roi_plans][<?php echo $i; ?>][unit]">
                        <option value="per_minute" <?php selected($plan['unit'], 'per_minute'); ?>><?php _e('Per Minute', 'smc'); ?></option>
                        <option value="hourly" <?php selected($plan['unit'], 'hourly'); ?>><?php _e('Hourly', 'smc'); ?></option>
                        <option value="daily" <?php selected($plan['unit'], 'daily'); ?>><?php _e('Daily', 'smc'); ?></option>
                    </select>
                
                <div class="roi-plan-example" id="roi-plan-example-<?php echo esc_attr($item_key) . '-' . $i; ?>" style="font-size: 0.9em; margin-top: 10px; padding: 8px; background-color: #f0f8ff; border: 1px solid #d1e7fd; border-radius: 4px;">
                    <p style="margin:0 0 5px 0;"><strong><?php _e('Example Calculation (for 1 share):', 'smc'); ?></strong></p>
                    <p style="margin:0 0 5px 0;"><?php _e('Profit per Plan Unit:', 'smc'); ?> <span class="calc-profit-per-unit">0.00</span> <?php _e('DZD', 'smc'); ?></p>
                    <p style="margin:0 0 5px 0;"><?php _e('Estimated Daily Profit:', 'smc'); ?> <span class="calc-daily-profit">0.00</span> <?php _e('DZD', 'smc'); ?></p>
                    <p style="margin:0;"><?php _e('Total Estimated Profit over Plan Duration:', 'smc'); ?> <span class="calc-total-profit">0.00</span> <?php _e('DZD', 'smc'); ?></p>
                </div>
                </label>
            </div>
            <?php endfor; ?>
        </div>

        <hr>
        <h4><?php _e('Final Margin Calculation Table', 'smc'); ?> (<?php _e('جدول لحساب هامش الربح النهائي للحصة', 'smc'); ?>)</h4>
        <?php echo smc_get_final_margin_calculation_table_html($item_key, $investment['final_margin_inputs']); ?>

        <p><label><strong><?php _e('Investment Contract Text:', 'smc'); ?></strong><br>
            <textarea name="smc_investment_types_settings[<?php echo esc_attr($item_key); ?>][contract_text]" rows="5" class="large-text"><?php echo esc_textarea($investment['contract_text']); ?></textarea>
        </label></p>
        <p><label>
            <input type="checkbox" name="smc_investment_types_settings[<?php echo esc_attr($item_key); ?>][is_active]" value="1" <?php checked(true, $investment['is_active']); ?>>
            <?php _e('Is Active (available to users)', 'smc'); ?>
        </label></p>
        <p><label>
            <input type="checkbox" name="smc_investment_types_settings[<?php echo esc_attr($item_key); ?>][final_profit_margin_recorded]" value="1" <?php checked(true, $investment['final_profit_margin_recorded']); ?> disabled>
            <?php _e('Final Profit Margin Recorded (System Set)', 'smc'); ?>
        </label> <small>(<?php _e('This flag is informative; actual processing depends on cron and user deposit flags.', 'smc'); ?>)</small></p>
        <hr>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Generates HTML for the production expenses table.
 */
function smc_get_production_expenses_table_html($item_key, $expenses_data = []) {
    $expense_definitions = [
        'electricity' => ['label' => __('Electricity', 'smc') . ' (كهرباء)', 'input_type' => 'qty_price'],
        'rent' => ['label' => __('Rent', 'smc') . ' (كراء محل)', 'input_type' => 'qty_price'],
        'workers' => ['label' => __('Number of Workers', 'smc') . ' (عدد عمال)', 'input_type' => 'qty_price'],
        'sugar_qty' => ['label' => __('Sugar Quantity', 'smc') . ' (كمية سكر)', 'input_type' => 'qty_price'],
        'sugar_bags_5g' => ['label' => __('5g Sugar Bags', 'smc') . ' (أكياس سكر 5 غرام)', 'input_type' => 'qty_price'],
        'cardboard_5kg' => ['label' => __('Cardboard (5kg)', 'smc') . ' (كرتون ( 5 كلغ ))', 'input_type' => 'qty_price'],
        'plastic_wrap' => ['label' => __('Plastic Wrap', 'smc') . ' (غلاف بلاستيكي)', 'input_type' => 'qty_price'],
        'other_expenses_total' => ['label' => __('Other Expenses (Total)', 'smc') . ' (مصاريف أخرى)', 'input_type' => 'total_only'],
        'machine_cost_total' => ['label' => __('Machine Cost (Total)', 'smc') . ' (كلفة الجهاز)', 'input_type' => 'total_only'],
        'transport_goods_total' => ['label' => __('Goods Transport Truck Cost (Total)', 'smc') . ' (كلفة شاحنة نقل السلع)', 'input_type' => 'total_only'],
        // Calculated rows
        'production_cost_for_days_calc' => ['label' => __('Production Cost for Duration (Calculated)', 'smc') . ' (كلفة إنتاج عدد أيام)', 'input_type' => 'calculated_display'],
        'total_project_cost_calc' => ['label' => __('Total Project Cost (Calculated from details)', 'smc') . ' (كلفة المشروع)', 'input_type' => 'calculated_display'],
    ];

    ob_start();
    ?>
    <table class="wp-list-table widefat striped smc-production-expenses-table" style="margin-bottom: 20px;">
        <thead>
            <tr>
                <th><?php _e('Production Expense Item', 'smc'); ?> (<?php _e('مصاريف الإنتاج', 'smc'); ?>)</th>
                <th><?php _e('Quantity', 'smc'); ?> (<?php _e('العدد', 'smc'); ?>)</th>
                <th><?php _e('Unit Price (DZD)', 'smc'); ?> (<?php _e('سعر الوحدة', 'smc'); ?>)</th>
                <th><?php _e('Total Price (DZD)', 'smc'); ?> (<?php _e('سعر الإجمالي', 'smc'); ?>)</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($expense_definitions as $expense_key => $def):
                $expense_values = $expenses_data[$expense_key] ?? ['quantity' => '', 'unit_price' => '', 'total' => ''];
            ?>
            <tr data-expense-key="<?php echo esc_attr($expense_key); ?>">
                <td><?php echo esc_html($def['label']); ?></td>
                <td>
                    <?php if ($def['input_type'] === 'qty_price'): ?>
                        <input type="number" step="any" name="smc_investment_types_settings[<?php echo esc_attr($item_key); ?>][production_expenses][<?php echo esc_attr($expense_key); ?>][quantity]" value="<?php echo esc_attr($expense_values['quantity']); ?>" class="small-text expense-quantity">
                    <?php elseif ($def['input_type'] === 'total_only' || $def['input_type'] === 'calculated_display'): ?>
                        N/A
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($def['input_type'] === 'qty_price'): ?>
                        <input type="number" step="any" name="smc_investment_types_settings[<?php echo esc_attr($item_key); ?>][production_expenses][<?php echo esc_attr($expense_key); ?>][unit_price]" value="<?php echo esc_attr($expense_values['unit_price']); ?>" class="small-text expense-unit-price">
                    <?php elseif ($def['input_type'] === 'total_only' || $def['input_type'] === 'calculated_display'): ?>
                         N/A
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($def['input_type'] === 'total_only'): ?>
                        <input type="number" step="any" name="smc_investment_types_settings[<?php echo esc_attr($item_key); ?>][production_expenses][<?php echo esc_attr($expense_key); ?>][total]" value="<?php echo esc_attr($expense_values['total']); ?>" class="small-text expense-total-input">
                    <?php elseif ($def['input_type'] === 'qty_price' || $def['input_type'] === 'calculated_display'): ?>
                        <input type="text" value="<?php echo esc_attr($expense_values['total']); ?>" class="small-text expense-total-display" readonly style="background-color: #eee;">
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <script type="text/javascript">
        // Add JavaScript here to handle auto-calculation for this table
        // Example: jQuery('.expense-quantity, .expense-unit-price').on('input', function() { /* calculate total */ });
        // Calculate 'production_cost_for_days_calc' and 'total_project_cost_calc'
    </script>
    <?php
    return ob_get_clean();
}

/**
 * Generates HTML for the final margin calculation table.
 */
function smc_get_final_margin_calculation_table_html($item_key, $margin_inputs = []) {
     $final_margin_definitions = [
        'transport_load' => ['label' => __('Transport (Load Capacity)', 'smc') . ' (نقل (حمولة الشحنة))', 'input_type' => 'qty_price'],
        'sale_no_vat' => ['label' => __('Sale without VAT', 'smc') . ' (بيع بدون القيمة المضافة)', 'input_type' => 'qty_price'],
        'vat_percentage_input' => ['label' => __('VAT Percentage (%)', 'smc') . ' (ضريبة القيمة المضافة)', 'input_type' => 'percentage_input'],
        'sale_with_invoice_calc' => ['label' => __('Sale with Invoice (Calculated)', 'smc') . ' (البيع بالفاتورة)', 'input_type' => 'calculated_display'],
        'total_cost_for_profit_calc' => ['label' => __('Total Cost for Profit Calc (Calculated)', 'smc') . ' (كلفة)', 'input_type' => 'calculated_display'],
        'profit_calc' => ['label' => __('Profit (Calculated)', 'smc') . ' (الربح)', 'input_type' => 'calculated_display'],
        'company_profit_percentage_input' => ['label' => __('Company Profit Percentage (%)', 'smc') . ' (ربح الشركة)', 'input_type' => 'percentage_input'],
        'users_profit_calc' => ['label' => __('Users Profit (Calculated)', 'smc') . ' (ربح المستخدمين)', 'input_type' => 'calculated_display'],
        'company_profit_share_value_calc' => ['label' => __('Company Share Profit Value (Calculated)', 'smc') . ' (ربح حصص الشركة)', 'input_type' => 'calculated_display'],
        'users_profit_share_value_calc' => ['label' => __('Users Share Profit Value (Calculated)', 'smc') . ' (ربح حصص المستخدمين)', 'input_type' => 'calculated_display'],
        'share_installments_total_calc' => ['label' => __('Total Share Installments from ROI (Calculated)', 'smc') . ' (دفعات الحصة)', 'input_type' => 'calculated_display'],
        'final_margin_per_share_calc' => ['label' => __('Final Profit Margin per Share (Calculated)', 'smc') . ' (هامش الربح النهائي للحصة)', 'input_type' => 'calculated_display'],
    ];
    ob_start();
    ?>
    <table class="wp-list-table widefat striped smc-final-margin-table">
        <thead>
            <tr>
                <th><?php _e('Detail Item', 'smc'); ?> (<?php _e('تفاصيل المحاسبة', 'smc'); ?>)</th>
                <th><?php _e('Quantity / Percentage', 'smc'); ?> (<?php _e('العدد / النسبة', 'smc'); ?>)</th>
                <th><?php _e('Unit Price (DZD)', 'smc'); ?> (<?php _e('سعر الوحدة', 'smc'); ?>)</th>
                <th><?php _e('Total Value (DZD)', 'smc'); ?> (<?php _e('سعر الإجمالي', 'smc'); ?>)</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($final_margin_definitions as $fm_key => $def):
                $fm_values = $margin_inputs[$fm_key] ?? ['quantity' => '', 'unit_price' => '', 'total' => '', 'percentage' => ''];
            ?>
            <tr data-fm-key="<?php echo esc_attr($fm_key); ?>">
                <td><?php echo esc_html($def['label']); ?></td>
                <td>
                    <?php if ($def['input_type'] === 'qty_price'): ?>
                        <input type="number" step="any" name="smc_investment_types_settings[<?php echo esc_attr($item_key); ?>][final_margin_inputs][<?php echo esc_attr($fm_key); ?>][quantity]" value="<?php echo esc_attr($fm_values['quantity']); ?>" class="small-text fm-quantity">
                    <?php elseif ($def['input_type'] === 'percentage_input'): ?>
                        <input type="number" step="any" name="smc_investment_types_settings[<?php echo esc_attr($item_key); ?>][final_margin_inputs][<?php echo esc_attr($fm_key); ?>][percentage]" value="<?php echo esc_attr($fm_values['percentage']); ?>" class="small-text fm-percentage"> %
                    <?php else: ?>
                        N/A
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($def['input_type'] === 'qty_price'): ?>
                        <input type="number" step="any" name="smc_investment_types_settings[<?php echo esc_attr($item_key); ?>][final_margin_inputs][<?php echo esc_attr($fm_key); ?>][unit_price]" value="<?php echo esc_attr($fm_values['unit_price']); ?>" class="small-text fm-unit-price">
                    <?php else: ?>
                        N/A
                    <?php endif; ?>
                </td>
                <td>
                     <input type="text" value="<?php echo esc_attr($fm_values['total']); ?>" class="small-text fm-total-display" readonly style="background-color: #eee;">
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <script type="text/javascript">
        // Add JavaScript here to handle auto-calculation for this table
        // This will be complex, involving values from the production expenses table,
        // share price, total shares, company shares, and ROI plans.
        // Example: jQuery('.fm-quantity, .fm-unit-price, .fm-percentage').on('input', function() { /* calculate final margin details */ });
    </script>
    <?php
    return ob_get_clean();
}

/**
 * Renders the dedicated Reward & Fee Settings page.
 */
function smc_render_reward_settings_page() {
     ?>
    <div class="wrap smc-settings-wrap">
        <h1><i class="dashicons dashicons-awards"></i> <?php _e('SMC Rewards & Fees', 'smc'); ?></h1>

         <h2 class="nav-tab-wrapper">
            <a href="<?php echo admin_url('admin.php?page=smc-settings'); ?>" class="nav-tab"><?php _e('Overview', 'smc'); ?></a>
            <a href="<?php echo admin_url('admin.php?page=smc-reward-settings'); ?>" class="nav-tab nav-tab-active"><?php _e('Rewards & Fees', 'smc'); ?></a>
            <a href="<?php echo admin_url('admin.php?page=ad-deal-settings'); ?>" class="nav-tab"><?php _e('Ad Deal Settings', 'smc'); ?></a>
            <a href="<?php echo admin_url('admin.php?page=smc-investment-types-settings'); ?>" class="nav-tab"><?php _e('Investment Types', 'smc'); ?></a>
            <a href="<?php echo admin_url('admin.php?page=smc-cron-jobs-status'); ?>" class="nav-tab"><?php _e('Cron Jobs', 'smc'); ?></a>
        </h2>

        <?php settings_errors('smc-settings-group'); // Display errors specific to this settings group ?>

        <form method="post" action="options.php">
            <?php
            settings_fields('smc-settings-group');
            do_settings_sections('smc-reward-settings');
            submit_button(__('Save Reward & Fee Settings', 'smc'));
            ?>
        </form>
    </div>
    <?php
}

/**
 * Renders the Cron Jobs Status page.
 */
function smc_render_cron_jobs_status_page() {
    ?>
    <div class="wrap smc-settings-wrap">
        <h1><i class="dashicons dashicons-clock"></i> <?php _e('SMC Cron Jobs Status', 'smc'); ?></h1>

        <h2 class="nav-tab-wrapper">
            <a href="<?php echo admin_url('admin.php?page=smc-settings'); ?>" class="nav-tab"><?php _e('Overview', 'smc'); ?></a>
            <a href="<?php echo admin_url('admin.php?page=smc-reward-settings'); ?>" class="nav-tab"><?php _e('Rewards & Fees', 'smc'); ?></a>
            <a href="<?php echo admin_url('admin.php?page=ad-deal-settings'); ?>" class="nav-tab"><?php _e('Ad Deal Settings', 'smc'); ?></a>
            <a href="<?php echo admin_url('admin.php?page=smc-investment-types-settings'); ?>" class="nav-tab"><?php _e('Investment Types', 'smc'); ?></a>
            <a href="<?php echo admin_url('admin.php?page=smc-cron-jobs-status'); ?>" class="nav-tab nav-tab-active"><?php _e('Cron Jobs', 'smc'); ?></a>
        </h2>

        <p><?php _e('This page displays the status of scheduled tasks (Cron Jobs) for the SMC system.', 'smc'); ?></p>
        <p><?php 
            if (defined('DISABLE_WP_CRON') && DISABLE_WP_CRON) {
                echo '<strong>' . __('Note:', 'smc') . '</strong> ' . __('WP-Cron is disabled via `DISABLE_WP_CRON` constant. Ensure you have a server-side cron job configured to call `wp-cron.php` regularly.', 'smc');
            } else {
                echo '<strong>' . __('Note:', 'smc') . '</strong> ' . __('WP-Cron is enabled. For reliable execution, especially on low-traffic sites, consider setting up a server-side cron job and disabling the default WP-Cron behavior.', 'smc');
            }
        ?></p>

        <table class="widefat striped" style="margin-top: 20px;">
            <thead>
                <tr>
                    <th><?php _e('Cron Hook Name', 'smc'); ?></th>
                    <th><?php _e('Description', 'smc'); ?></th>
                    <th><?php _e('Next Run (Site Time)', 'smc'); ?></th>
                    <th><?php _e('Recurrence', 'smc'); ?></th>
                    <th><?php _e('Status', 'smc'); ?></th>
                    <th><?php _e('Actions', 'smc'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php
                $cron_jobs_to_display = [
                    ['hook_constant' => 'SMC_MONTHLY_SALARY_CRON_HOOK', 'description' => __('Monthly Salary Payment', 'smc')],
                    ['hook_constant' => 'SMC_PROCESS_INVESTMENT_PROFIT_CRON_HOOK', 'description' => __('Investment Profit Distribution', 'smc')],
                    ['hook_constant' => 'SMC_CALCULATE_FINAL_MARGIN_CRON_HOOK', 'description' => __('Final Margin Calculation', 'smc')],
                ];

                $schedules = wp_get_schedules();

                foreach ($cron_jobs_to_display as $job) {
                    $hook_name = defined($job['hook_constant']) ? constant($job['hook_constant']) : strtolower($job['hook_constant']); // Fallback if constant not defined
                    $timestamp = wp_next_scheduled($hook_name);
                    $status = $timestamp ? __('Scheduled', 'smc') : '<span style="color:red;">' . __('Not Scheduled', 'smc') . '</span>';
                    $next_run_display = $timestamp ? date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $timestamp + (get_option('gmt_offset') * HOUR_IN_SECONDS)) : 'N/A';
                    
                    $recurrence_key = '';
                    $recurrence_display = 'N/A';
                    if ($timestamp) {
                        $event_schedule = wp_get_schedule($hook_name);
                        if ($event_schedule && isset($schedules[$event_schedule])) {
                            $recurrence_display = esc_html($schedules[$event_schedule]['display']);
                        } elseif ($event_schedule) {
                            $recurrence_display = esc_html($event_schedule); // Fallback to key if display name not found
                        }
                    }
                    echo '<tr>';
                    echo '<td><code>' . esc_html($hook_name) . '</code></td>';
                    echo '<td>' . esc_html($job['description']) . '</td>';
                    echo '<td>' . $next_run_display . '</td>';
                    echo '<td>' . $recurrence_display . '</td>';
                    echo '<td>' . $status . '</td>';
                    echo '<td>';
                    if ($timestamp) { // Only show run now if it's scheduled
                        echo '<button type="button" class="button button-secondary smc-run-cron-now-btn" data-hook="' . esc_attr($hook_name) . '" title="' . esc_attr__('Run this cron job now', 'smc') . '">';
                        echo '<span class="dashicons dashicons-controls-play" style="vertical-align: text-bottom;"></span> ' . __('Run Now', 'smc');
                        echo '</button>';
                    } else {
                        echo __('N/A', 'smc');
                    }
                    echo '</td>';
                    echo '</tr>';
                }
                ?>
            </tbody>
        </table>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <?php // JavaScript for "Run Now" button will be added here or enqueued separately
    smc_cron_jobs_status_page_js(); // Call a new function to output JS
    ?>
    <?php
}

/**
 * Renders the Ad Deal Settings page.
 */
function smc_render_ad_settings_page() {
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }
    ?>
    <div class="wrap smc-settings-wrap">
        <h1><i class="fas fa-sliders-h"></i> <?php echo esc_html(get_admin_page_title()); ?></h1>

        <h2 class="nav-tab-wrapper">
            <a href="<?php echo admin_url('admin.php?page=smc-settings'); ?>" class="nav-tab"><?php _e('Overview', 'smc'); ?></a>
            <a href="<?php echo admin_url('admin.php?page=smc-reward-settings'); ?>" class="nav-tab"><?php _e('Rewards & Fees', 'smc'); ?></a>
            <a href="<?php echo admin_url('admin.php?page=ad-deal-settings'); ?>" class="nav-tab nav-tab-active"><?php _e('Ad Deal Settings', 'smc'); ?></a>
            <a href="<?php echo admin_url('admin.php?page=smc-investment-types-settings'); ?>" class="nav-tab"><?php _e('Investment Types', 'smc'); ?></a>
        </h2>

        <?php settings_errors('smc_ad_deal_options_group'); ?>

        <form method="post" action="options.php">
            <?php
            settings_fields('smc_ad_deal_options_group');
            do_settings_sections('ad-deal-settings');
            submit_button('حفظ الإعدادات');
            ?>
        </form>
    </div>
    <?php
}


/**
 * Renders the description for the Reward Settings section.
 */
function smc_rewards_section_callback() {
    echo '<p>' . __('Adjust the values and types for various rewards and fees. Use unique English keys (lowercase, underscores) to define each type.', 'smc') . '</p>';
    echo '<h4>' . __('Value Types:', 'smc') . '</h4>';
    echo '<ul style="list-style: disc; margin-right: 20px;">';
    echo '<li><strong>percentage:</strong> ' . __('Percentage value (enter as decimal, e.g., 0.03 for 3%). Used for bonuses based on an amount.', 'smc') . '</li>';
    echo '<li><strong>fixed:</strong> ' . __('Fixed amount (e.g., points for attendance, fixed bonus amount).', 'smc') . '</li>';
    echo '<li><strong>fixed_monthly:</strong> ' . __('Fixed monthly salary (requires separate cron job/mechanism for awarding).', 'smc') . '</li>';
    echo '<li><strong>percentage_plus_fixed:</strong> ' . __('Percentage + Fixed amount (used for fees). The value field will show two inputs.', 'smc') . '</li>';
    echo '</ul>';
    echo '<p><strong>' . __('Important:', 'smc') . '</strong> ' . __('Changing keys might require code updates where these settings are used.', 'smc') . '</p>';
    echo '<hr>';
}


/**
 * Renders the table for configuring rewards and fees.
 */
function smc_rewards_table_field_callback() {
    $options = get_option(SMC_REWARD_SETTINGS_OPTION, smc_get_default_reward_settings());
    if (!is_array($options)) $options = []; // Ensure it's an array

    $allowed_types = ['percentage', 'fixed', 'fixed_monthly', 'percentage_plus_fixed'];
    ?>
    <table id="smc-rewards-fees-table" class="wp-list-table widefat striped">
        <thead>
            <tr>
                <th><?php _e('Reward/Fee Key', 'smc'); ?></th>
                <th><?php _e('Type', 'smc'); ?></th>
                <th><?php _e('Value', 'smc'); ?></th>
                <th><?php _e('Description (Optional)', 'smc'); ?></th>
                <th><?php _e('Actions', 'smc'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php
            $counter = 0;
            foreach ($options as $key => $setting) :
                // Ensure setting is an array and has a type
                if (!is_array($setting) || !isset($setting['type'])) {
                    // Try to use default if structure is broken
                    $default_settings_for_key = smc_get_default_reward_settings()[$key] ?? ['type' => 'fixed', 'value' => 0, 'description' => ''];
                    $setting = array_merge($default_settings_for_key, (array)$setting); // Merge to ensure type exists
                    if (!isset($setting['type'])) continue; // Skip if still no type
                }
                $type = $setting['type'];
                $value = $setting['value'] ?? (($type === 'percentage_plus_fixed') ? ['percentage' => 0, 'fixed' => 0] : 0);
                $description = $setting['description'] ?? '';
                $is_fixed_monthly_rank = ($type === 'fixed_monthly' && (strpos($key, 'rank_vip') === 0 || strpos($key, 'agent_') === 0));
            ?>
            <tr class="smc-reward-row" data-index="<?php echo $counter; ?>">
                <td>
                    <input type="text" name="<?php echo esc_attr(SMC_REWARD_SETTINGS_OPTION . '[' . $key . '][key_placeholder]'); ?>" value="<?php echo esc_attr($key); ?>" class="regular-text smc-reward-key" readonly style="background-color: #f0f0f0; cursor: not-allowed;">
                </td>
                <td>
                    <select name="<?php echo esc_attr(SMC_REWARD_SETTINGS_OPTION . '[' . $key . '][type]'); ?>" class="smc-reward-type">
                        <?php foreach ($allowed_types as $allowed_type) : ?>
                            <option value="<?php echo esc_attr($allowed_type); ?>" <?php selected($type, $allowed_type); ?>>
                                <?php echo esc_html(ucfirst(str_replace('_', ' ', $allowed_type))); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
                <td class="smc-value-field">
                    <?php if ($type === 'percentage_plus_fixed') : ?>
                        <div class="smc-reward-value-complex-fields">
                            <label><?php _e('Percentage:', 'smc'); ?>
                                <input type="number" step="0.001" name="<?php echo esc_attr(SMC_REWARD_SETTINGS_OPTION . '[' . $key . '][value][percentage]'); ?>" value="<?php echo esc_attr($value['percentage'] ?? 0); ?>" class="small-text">
                            </label>
                            <label><?php _e('Fixed Amount:', 'smc'); ?>
                                <input type="number" step="0.01" name="<?php echo esc_attr(SMC_REWARD_SETTINGS_OPTION . '[' . $key . '][value][fixed]'); ?>" value="<?php echo esc_attr($value['fixed'] ?? 0); ?>" class="small-text">
                            </label>
                        </div>
                    <?php elseif ($is_fixed_monthly_rank) : ?>
                        <div class="smc-reward-value-complex-fields">
                            <label><?php _e('Salary (DZD):', 'smc'); ?>
                                <input type="number" step="0.01" name="<?php echo esc_attr(SMC_REWARD_SETTINGS_OPTION . '[' . $key . '][value]'); ?>" value="<?php echo esc_attr($value); ?>" class="small-text">
                            </label>
                            <?php if (strpos($key, 'rank_vip') === 0) : ?>
                                <label><?php _e('Min Referrals:', 'smc'); ?>
                                    <input type="number" step="1" name="<?php echo esc_attr(SMC_REWARD_SETTINGS_OPTION . '[' . $key . '][required_referrals_min]'); ?>" value="<?php echo esc_attr($setting['required_referrals_min'] ?? 0); ?>" class="small-text">
                                </label>
                                <label><?php _e('Max Referrals:', 'smc'); ?>
                                    <input type="number" step="1" name="<?php echo esc_attr(SMC_REWARD_SETTINGS_OPTION . '[' . $key . '][required_referrals_max]'); ?>" value="<?php echo esc_attr($setting['required_referrals_max'] ?? 0); ?>" class="small-text">
                                </label>
                            <?php elseif (strpos($key, 'agent_') === 0) : ?>
                                <label><?php _e('Min VIP3:', 'smc'); ?>
                                    <input type="number" step="1" name="<?php echo esc_attr(SMC_REWARD_SETTINGS_OPTION . '[' . $key . '][required_vip3_min]'); ?>" value="<?php echo esc_attr($setting['required_vip3_min'] ?? 0); ?>" class="small-text">
                                </label>
                                <label><?php _e('Max VIP3:', 'smc'); ?>
                                    <input type="number" step="1" name="<?php echo esc_attr(SMC_REWARD_SETTINGS_OPTION . '[' . $key . '][required_vip3_max]'); ?>" value="<?php echo esc_attr($setting['required_vip3_max'] ?? 0); ?>" class="small-text">
                                </label>
                                <label><?php _e('Scope:', 'smc'); ?>
                                    <select name="<?php echo esc_attr(SMC_REWARD_SETTINGS_OPTION . '[' . $key . '][location_scope]'); ?>">
                                        <option value="district" <?php selected($setting['location_scope'] ?? '', 'district'); ?>><?php _e('District', 'smc'); ?></option>
                                        <option value="city" <?php selected($setting['location_scope'] ?? '', 'city'); ?>><?php _e('City', 'smc'); ?></option>
                                    </select>
                                </label>
                            <?php endif; ?>
                        </div>
                    <?php else : // For 'percentage', 'fixed', 'fixed_monthly' (non-rank) ?>
                        <input type="number" step="any" name="<?php echo esc_attr(SMC_REWARD_SETTINGS_OPTION . '[' . $key . '][value]'); ?>" value="<?php echo esc_attr($value); ?>" class="small-text">
                    <?php endif; ?>
                </td>
                <td>
                    <input type="text" name="<?php echo esc_attr(SMC_REWARD_SETTINGS_OPTION . '[' . $key . '][description]'); ?>" value="<?php echo esc_attr($description); ?>" class="regular-text">
                </td>
                <td>
                    <button type="button" class="button smc-remove-reward"><span class="dashicons dashicons-trash"></span></button>
                </td>
            </tr>
            <?php $counter++; endforeach; ?>
        </tbody>
    </table>
    <button type="button" id="smc-add-reward-row" class="button button-secondary" style="margin-top: 10px;">
        <span class="dashicons dashicons-plus-alt2"></span> <?php _e('Add New Reward/Fee Type', 'smc'); ?>
    </button>

    <script type="text/javascript">
    jQuery(document).ready(function($) {
        let rewardIndex = <?php echo $counter; ?>; // Start index for new rows

        $('#smc-add-reward-row').on('click', function() {
            const newRowHtml = `
                <tr class="smc-reward-row" data-index="${rewardIndex}">
                    <td>
                        <input type="text" name="<?php echo esc_attr(SMC_REWARD_SETTINGS_OPTION); ?>[__NEW_${rewardIndex}__][key_placeholder]" value="" class="regular-text smc-reward-key" placeholder="<?php esc_attr_e('Enter unique key', 'smc'); ?>" required>
                    </td>
                    <td>
                        <select name="<?php echo esc_attr(SMC_REWARD_SETTINGS_OPTION); ?>[__NEW_${rewardIndex}__][type]" class="smc-reward-type">
                            <?php foreach ($allowed_types as $allowed_type) : ?>
                                <option value="<?php echo esc_attr($allowed_type); ?>">
                                    <?php echo esc_html(ucfirst(str_replace('_', ' ', $allowed_type))); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td class="smc-value-field">
                        <input type="number" step="any" name="<?php echo esc_attr(SMC_REWARD_SETTINGS_OPTION); ?>[__NEW_${rewardIndex}__][value]" value="0" class="small-text">
                    </td>
                    <td>
                        <input type="text" name="<?php echo esc_attr(SMC_REWARD_SETTINGS_OPTION); ?>[__NEW_${rewardIndex}__][description]" value="" class="regular-text" placeholder="<?php esc_attr_e('Description', 'smc'); ?>">
                    </td>
                    <td>
                        <button type="button" class="button smc-remove-reward"><span class="dashicons dashicons-trash"></span></button>
                    </td>
                </tr>
            `;
            $('#smc-rewards-fees-table tbody').append(newRowHtml);
            rewardIndex++;
        });

        $('#smc-rewards-fees-table').on('click', '.smc-remove-reward', function() {
            $(this).closest('tr').remove();
        });

        $('#smc-rewards-fees-table').on('change', '.smc-reward-type', function() {
            const type = $(this).val();
            const row = $(this).closest('tr');
            const valueCell = row.find('.smc-value-field');
            const key = row.find('.smc-reward-key').val() || row.find('input[name*="[key_placeholder]"]').val(); // Get existing key or placeholder
            const namePrefix = `<?php echo esc_attr(SMC_REWARD_SETTINGS_OPTION); ?>[${key}]`;
            const isFixedMonthlyRank = (type === 'fixed_monthly' && (key.startsWith('rank_vip') || key.startsWith('agent_')));


            let valueHtml = '';
            if (type === 'percentage_plus_fixed') {
                valueHtml = `
                    <div class="smc-reward-value-complex-fields">
                        <label><?php _e('Percentage:', 'smc'); ?>
                            <input type="number" step="0.001" name="${namePrefix}[value][percentage]" value="0" class="small-text">
                        </label>
                        <label><?php _e('Fixed Amount:', 'smc'); ?>
                            <input type="number" step="0.01" name="${namePrefix}[value][fixed]" value="0" class="small-text">
                        </label>
                    </div>`;
            } else if (isFixedMonthlyRank) {
                 valueHtml = `<div class="smc-reward-value-complex-fields">
                                <label><?php _e('Salary (DZD):', 'smc'); ?>
                                    <input type="number" step="0.01" name="${namePrefix}[value]" value="0" class="small-text">
                                </label>`;
                if (key.startsWith('rank_vip')) {
                    valueHtml += `<label><?php _e('Min Referrals:', 'smc'); ?>
                                    <input type="number" step="1" name="${namePrefix}[required_referrals_min]" value="0" class="small-text">
                                  </label>
                                  <label><?php _e('Max Referrals:', 'smc'); ?>
                                    <input type="number" step="1" name="${namePrefix}[required_referrals_max]" value="0" class="small-text">
                                  </label>`;
                } else if (key.startsWith('agent_')) {
                     valueHtml += `<label><?php _e('Min VIP3:', 'smc'); ?>
                                    <input type="number" step="1" name="${namePrefix}[required_vip3_min]" value="0" class="small-text">
                                   </label>
                                   <label><?php _e('Max VIP3:', 'smc'); ?>
                                    <input type="number" step="1" name="${namePrefix}[required_vip3_max]" value="0" class="small-text">
                                   </label>
                                   <label><?php _e('Scope:', 'smc'); ?>
                                    <select name="${namePrefix}[location_scope]">
                                        <option value="district"><?php _e('District', 'smc'); ?></option>
                                        <option value="city"><?php _e('City', 'smc'); ?></option>
                                    </select>
                                   </label>`;
                }
                valueHtml += `</div>`;
            } else { // 'percentage', 'fixed', 'fixed_monthly' (non-rank)
                valueHtml = `<input type="number" step="any" name="${namePrefix}[value]" value="0" class="small-text">`;
            }
            valueCell.html(valueHtml);
        });
         // Trigger change on load for existing rows to set up correct value fields
        $('.smc-reward-type').trigger('change');
    });
    </script>
    <?php
}


/**
 * Sanitizes the reward settings before saving.
 */
function smc_sanitize_reward_settings($input) {
    $new_input = [];
    if (!function_exists('smc_get_default_reward_settings') && !function_exists('smc_get_default_reward_settings_local_fallback')) {
         error_log("SMC Sanitize Error: Default settings function missing.");
         return [];
    }
    $defaults_func = function_exists('smc_get_default_reward_settings') ? 'smc_get_default_reward_settings' : 'smc_get_default_reward_settings_local_fallback';
    $defaults = $defaults_func();
    $allowed_types = ['percentage', 'fixed', 'fixed_monthly', 'percentage_plus_fixed'];

    if (empty($input) || !is_array($input)) {
        return $defaults;
    }

    foreach ($input as $temp_key => $setting_data) {
        $sanitized_key = '';
        if (isset($setting_data['key_placeholder']) && !empty(trim($setting_data['key_placeholder']))) {
            $sanitized_key = sanitize_key(trim($setting_data['key_placeholder']));
        } elseif (strpos($temp_key, '__NEW_') !== 0) { // Use original key if not a new item
            $sanitized_key = sanitize_key($temp_key);
        }

        if (empty($sanitized_key)) {
            error_log("SMC Sanitize Warning: Skipping row with empty or invalid key '{$temp_key}'.");
            continue;
        }
        if (isset($new_input[$sanitized_key])) {
            error_log("SMC Sanitize Warning: Duplicate key '{$sanitized_key}' encountered. Skipping subsequent entry.");
            continue;
        }

        if (!is_array($setting_data) || !isset($setting_data['type'])) {
             error_log("SMC Sanitize Warning: Skipping key '{$sanitized_key}' due to invalid structure.");
             continue;
        }

        $type = sanitize_text_field($setting_data['type']);
        if (!in_array($type, $allowed_types)) {
             error_log("SMC Sanitize Warning: Skipping key '{$sanitized_key}' due to invalid type '{$type}'.");
             continue;
        }

        $new_input[$sanitized_key] = ['type' => $type];
        $value_input = $setting_data['value'] ?? null;

        if ($type === 'percentage_plus_fixed') {
            $percentage = (isset($value_input['percentage']) && is_numeric($value_input['percentage'])) ? (float)$value_input['percentage'] : 0.0;
            $fixed = (isset($value_input['fixed']) && is_numeric($value_input['fixed'])) ? (float)$value_input['fixed'] : 0.0;
            $new_input[$sanitized_key]['value'] = ['percentage' => $percentage, 'fixed' => $fixed];
        } elseif ($type === 'fixed_monthly' && (strpos($sanitized_key, 'rank_vip') === 0 || strpos($sanitized_key, 'agent_') === 0)) {
            $new_input[$sanitized_key]['value'] = (isset($value_input) && is_numeric($value_input)) ? (float)$value_input : 0.0; // Salary
            if (strpos($sanitized_key, 'rank_vip') === 0) {
                $new_input[$sanitized_key]['required_referrals_min'] = isset($setting_data['required_referrals_min']) ? absint($setting_data['required_referrals_min']) : 0;
                $new_input[$sanitized_key]['required_referrals_max'] = isset($setting_data['required_referrals_max']) ? absint($setting_data['required_referrals_max']) : PHP_INT_MAX;
            } elseif (strpos($sanitized_key, 'agent_') === 0) {
                $new_input[$sanitized_key]['required_vip3_min'] = isset($setting_data['required_vip3_min']) ? absint($setting_data['required_vip3_min']) : 0;
                $new_input[$sanitized_key]['required_vip3_max'] = isset($setting_data['required_vip3_max']) ? absint($setting_data['required_vip3_max']) : PHP_INT_MAX;
                $new_input[$sanitized_key]['location_scope'] = isset($setting_data['location_scope']) ? sanitize_text_field($setting_data['location_scope']) : ($defaults[$sanitized_key]['location_scope'] ?? 'district');
            }
        } else { // 'percentage', 'fixed', or 'fixed_monthly' (non-rank)
            $new_input[$sanitized_key]['value'] = (isset($value_input) && is_numeric($value_input)) ? (float)$value_input : 0.0;
        }
        $new_input[$sanitized_key]['description'] = isset($setting_data['description']) ? sanitize_text_field($setting_data['description']) : '';
    }
    return $new_input;
}


/**
 * Sanitizes the general investment types settings.
 */
function smc_sanitize_investment_types_settings($input) {
    $existing_options = get_option('smc_investment_types_settings', []);
    if (!is_array($existing_options)) $existing_options = [];
    $new_input = $existing_options; // Initialize with existing options to preserve them

    if (empty($input) || !is_array($input)) {
        // If input is empty, it might mean all items were removed, so return empty.
        // Or, if it's an invalid submission, perhaps return existing options.
        // For now, if form submits empty, we assume all are deleted.
        return [];
    }

    foreach ($input as $form_item_key => $investment_data) {
        if (!is_array($investment_data)) continue;

        $actual_key = '';
        $is_new_item_from_form = isset($investment_data['is_new_marker']) && $investment_data['is_new_marker'] === '1';

        if ($is_new_item_from_form) {
            if (empty($investment_data['key_input'])) {
                add_settings_error('smc-investment-types-group', 'empty_new_key', __('A unique key is required for new investment types.', 'smc'), 'error');
                continue; // Skip this new item if key is not provided
            }
            $actual_key = sanitize_key($investment_data['key_input']);
            if (isset($existing_options[$actual_key]) || isset($new_input[$actual_key])) {
                add_settings_error('smc-investment-types-group', 'duplicate_key', sprintf(__('The key "%s" is already in use or duplicated in this submission. Please use a unique key.', 'smc'), esc_html($actual_key)), 'error');
                // Attempt to use the temporary form key to preserve data if admin wants to correct
                $actual_key = sanitize_key($form_item_key); // Fallback to temp key to avoid data loss on error
            }
        } else {
            // For existing items, the key is the array key from the form submission
            $actual_key = sanitize_key($form_item_key);
             if (empty($investment_data['key_input']) || $actual_key !== sanitize_key($investment_data['key_input'])) {
                // This case should not happen if 'key_input' for existing items is readonly and correctly set.
                // If it does, it's a data integrity issue.
                error_log("SMC Sanitize Warning: Mismatch or empty key_input for existing investment. Form key: {$form_item_key}, Input key: " . ($investment_data['key_input'] ?? 'N/A'));
                // We trust the $form_item_key for existing items.
            }
        }
        
        if (empty($actual_key)) continue;

        $sanitized_item = [];
        $sanitized_item['key'] = $actual_key; // Store the final key
        $sanitized_item['title'] = isset($investment_data['title']) ? sanitize_text_field($investment_data['title']) : '';
        $sanitized_item['description'] = isset($investment_data['description']) ? wp_kses_post($investment_data['description']) : '';
        $sanitized_item['share_price'] = isset($investment_data['share_price']) ? filter_var($investment_data['share_price'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION) : '';
        $sanitized_item['min_shares_overall'] = isset($investment_data['min_shares_overall']) ? absint($investment_data['min_shares_overall']) : 1;
        $sanitized_item['project_cost'] = isset($investment_data['project_cost']) ? filter_var($investment_data['project_cost'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION) : '';
        $sanitized_item['total_shares'] = isset($investment_data['total_shares']) ? absint($investment_data['total_shares']) : 0;
        $sanitized_item['company_shares'] = isset($investment_data['company_shares']) ? absint($investment_data['company_shares']) : 0;
        $sanitized_item['investment_acceptance_end_datetime'] = isset($investment_data['investment_acceptance_end_datetime']) ? sanitize_text_field($investment_data['investment_acceptance_end_datetime']) : '';
        $sanitized_item['investment_start_datetime'] = isset($investment_data['investment_start_datetime']) ? sanitize_text_field($investment_data['investment_start_datetime']) : '';
        $sanitized_item['contract_text'] = isset($investment_data['contract_text']) ? wp_kses_post($investment_data['contract_text']) : '';

        // Sanitize new general fields
        $sanitized_item['project_duration_value'] = isset($investment_data['project_duration_value']) ? absint($investment_data['project_duration_value']) : 90;
        $sanitized_item['project_duration_unit'] = isset($investment_data['project_duration_unit']) && in_array($investment_data['project_duration_unit'], ['hours', 'days']) ? $investment_data['project_duration_unit'] : 'days';
        $sanitized_item['daily_production_hours'] = isset($investment_data['daily_production_hours']) ? absint($investment_data['daily_production_hours']) : 0;
        $sanitized_item['production_days_in_project'] = isset($investment_data['production_days_in_project']) ? absint($investment_data['production_days_in_project']) : 0;

        
        // Preserve creation date for existing items, set for new if not already set by form
        if ($is_new_item_from_form) {
            $sanitized_item['creation_date'] = current_time('mysql');
        } else {
            $sanitized_item['creation_date'] = $existing_options[$actual_key]['creation_date'] ?? current_time('mysql');
        }
        if (isset($investment_data['creation_date']) && !empty($investment_data['creation_date']) && !$is_new_item_from_form) { // If form explicitly sends it for existing
             $sanitized_item['creation_date'] = sanitize_text_field($investment_data['creation_date']);
        }

        // Sanitize production expenses
        $sanitized_item['production_expenses'] = [];
        if (isset($investment_data['production_expenses']) && is_array($investment_data['production_expenses'])) {
            foreach ($investment_data['production_expenses'] as $exp_key => $exp_values) {
                $s_exp_key = sanitize_key($exp_key);
                $sanitized_item['production_expenses'][$s_exp_key] = [
                    'quantity'   => isset($exp_values['quantity']) ? filter_var($exp_values['quantity'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION) : '',
                    'unit_price' => isset($exp_values['unit_price']) ? filter_var($exp_values['unit_price'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION) : '',
                    'total'      => isset($exp_values['total']) ? filter_var($exp_values['total'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION) : '', // For total_only inputs and calculated displays
                ];
            }
        }

        // Sanitize final margin inputs
        $sanitized_item['final_margin_inputs'] = [];
         if (isset($investment_data['final_margin_inputs']) && is_array($investment_data['final_margin_inputs'])) {
            foreach ($investment_data['final_margin_inputs'] as $fm_key_raw => $fm_values) {
                $s_fm_key = sanitize_key($fm_key_raw);
                $sanitized_item['final_margin_inputs'][$s_fm_key] = [
                    'quantity'   => isset($fm_values['quantity']) ? filter_var($fm_values['quantity'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION) : '',
                    'unit_price' => isset($fm_values['unit_price']) ? filter_var($fm_values['unit_price'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION) : '',
                    'percentage' => isset($fm_values['percentage']) ? filter_var($fm_values['percentage'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION) : '',
                    'total'      => isset($fm_values['total']) ? filter_var($fm_values['total'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION) : '', // For calculated displays
                ];
            }
        }






        $sanitized_item['roi_plans'] = [];
        if (isset($investment_data['roi_plans']) && is_array($investment_data['roi_plans'])) {
            foreach ($investment_data['roi_plans'] as $plan_index => $plan_data) {
                if (is_array($plan_data)) {
                    $sanitized_plan = [];
                    $sanitized_plan['duration_value'] = isset($plan_data['duration_value']) ? absint($plan_data['duration_value']) : 90;
                    $sanitized_plan['duration_unit'] = isset($plan_data['duration_unit']) && in_array($plan_data['duration_unit'], ['minutes', 'hours', 'days']) ? $plan_data['duration_unit'] : 'days';
                    $sanitized_plan['min_roi'] = isset($plan_data['min_roi']) ? filter_var($plan_data['min_roi'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION) : '';
                    $sanitized_plan['max_roi'] = isset($plan_data['max_roi']) ? filter_var($plan_data['max_roi'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION) : '';
                    $sanitized_plan['avg_roi'] = isset($plan_data['avg_roi']) ? filter_var($plan_data['avg_roi'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION) : '';
                    $sanitized_plan['unit'] = isset($plan_data['unit']) && in_array($plan_data['unit'], ['per_minute', 'hourly', 'daily']) ? $plan_data['unit'] : 'daily';
                    $sanitized_item['roi_plans'][] = $sanitized_plan; // Add to array with numeric index
                }
            }
        }
        // Preserve existing final_profit_margin_recorded and actual_final_profit unless explicitly changed by another mechanism
        $sanitized_item['final_profit_margin_recorded'] = $existing_options[$actual_key]['final_profit_margin_recorded'] ?? false;
        $sanitized_item['actual_final_profit'] = $existing_options[$actual_key]['actual_final_profit'] ?? null;

        $sanitized_item['is_active'] = isset($investment_data['is_active']);
        
        $new_input[$actual_key] = $sanitized_item;
    }
    return $new_input;
}


/**
 * Renders the list of investment configurations.
 */
function smc_render_investment_configurations_list() {
    $all_investments = get_option('smc_investment_types_settings', []);
    if (!is_array($all_investments)) {
        $all_investments = [];
    }
    ?>
    <h3><?php _e('Search Investments', 'smc'); ?></h3>
    <table class="form-table" style="margin-bottom: 20px;">
        <tr>
            <th scope="row"><label for="smc-search-title-key"><?php _e('Project Title/Key', 'smc'); ?></label></th>
            <td><input type="text" id="smc-search-title-key" class="regular-text smc-investment-search-input"></td>
            <th scope="row"><label for="smc-search-creation-date"><?php _e('Creation Date', 'smc'); ?></label></th>
            <td><input type="text" id="smc-search-creation-date" class="regular-text smc-investment-search-input" placeholder="<?php esc_attr_e('YYYY-MM-DD', 'smc'); ?>"></td>
        </tr>
        <tr>
            <th scope="row"><label for="smc-search-acceptance-end"><?php _e('Acceptance End', 'smc'); ?></label></th>
            <td><input type="text" id="smc-search-acceptance-end" class="regular-text smc-investment-search-input" placeholder="<?php esc_attr_e('YYYY-MM-DD', 'smc'); ?>"></td>
            <th scope="row"><label for="smc-search-project-start"><?php _e('Project Start', 'smc'); ?></label></th>
            <td><input type="text" id="smc-search-project-start" class="regular-text smc-investment-search-input" placeholder="<?php esc_attr_e('YYYY-MM-DD', 'smc'); ?>"></td>
        </tr>
        <tr>
            <th scope="row"><label for="smc-search-status"><?php _e('Status', 'smc'); ?></label></th>
            <td><input type="text" id="smc-search-status" class="regular-text smc-investment-search-input"></td>
            <td colspan="2"><button type="button" id="smc-clear-search-filters" class="button"><?php _e('Clear Filters', 'smc'); ?></button></td>
        </tr>
    </table>
    <h3><?php _e('Investment Projects Overview', 'smc'); ?></h3>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th scope="col"><?php _e('Project Title', 'smc'); ?> (<?php _e('Key', 'smc'); ?>)</th>
                <th scope="col"><?php _e('Creation Date', 'smc'); ?></th>
                <th scope="col"><?php _e('Acceptance End', 'smc'); ?></th>
                <th scope="col"><?php _e('Project Start', 'smc'); ?></th>
                <th scope="col"><?php _e('Status', 'smc'); ?></th>
                <th scope="col"><?php _e('Actions', 'smc'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($all_investments)): ?>
                <tr>
                    <td colspan="6"><?php _e('No investment types configured yet.', 'smc'); ?> <a href="<?php echo admin_url('admin.php?page=smc-investment-types-settings&view=add_new'); ?>"><?php _e('Add New Investment Type', 'smc'); ?></a></td>
                </tr>
            <?php else: ?>
                <?php foreach ($all_investments as $key => $investment_config):
                    $title = $investment_config['title'] ?? $key;
                    $creation_date = $investment_config['creation_date'] ?? 'N/A';
                    $acceptance_end_str = $investment_config['investment_acceptance_end_datetime'] ?? 'N/A';
                    $project_start_str = $investment_config['investment_start_datetime'] ?? 'N/A';

                    $status_text = __('Undefined', 'smc');
                    $status_class = 'status-undefined';
                    $current_time_unix = current_time('timestamp');
                    $project_start_ts = !empty($project_start_str) ? strtotime($project_start_str) : 0;
                    $max_duration_seconds = 0;

                    if (!empty($investment_config['roi_plans']) && is_array($investment_config['roi_plans'])) {
                        foreach ($investment_config['roi_plans'] as $plan) {
                            $duration_value = (int) ($plan['duration_value'] ?? 0);
                            $duration_unit = $plan['duration_unit'] ?? 'days';
                            $plan_duration_in_seconds = 0;
                            if ($duration_value > 0) {
                                switch ($duration_unit) {
                                    case 'minutes': $plan_duration_in_seconds = $duration_value * 60; break;
                                    case 'hours': $plan_duration_in_seconds = $duration_value * 3600; break;
                                    case 'days': default: $plan_duration_in_seconds = $duration_value * 86400; break;
                                }
                            }
                            $max_duration_seconds = max($max_duration_seconds, $plan_duration_in_seconds);
                        }
                    }
                    $project_natural_end_ts = ($project_start_ts && $max_duration_seconds > 0) ? ($project_start_ts + $max_duration_seconds) : 0;
                    $actual_final_profit_is_set = isset($investment_config['actual_final_profit']) && is_numeric($investment_config['actual_final_profit']);
                    $final_profit_margin_recorded_checkbox = !empty($investment_config['final_profit_margin_recorded']);

                    if ($final_profit_margin_recorded_checkbox) {
                        $status_text = __('Finished', 'smc'); $status_class = 'status-finished';
                    } elseif ($project_natural_end_ts > 0 && $current_time_unix < $project_natural_end_ts) {
                        $status_text = __('Active', 'smc'); $status_class = 'status-active';
                    } elseif ($project_natural_end_ts > 0 && $current_time_unix >= $project_natural_end_ts && !$actual_final_profit_is_set) {
                        $status_text = __('Awaiting Final Margin Input', 'smc'); $status_class = 'status-pending-margin';
                    } elseif ($project_natural_end_ts > 0 && $current_time_unix >= $project_natural_end_ts && $actual_final_profit_is_set && !$final_profit_margin_recorded_checkbox) {
                        $status_text = __('Processing Final Margin', 'smc'); $status_class = 'status-processing-margin';
                    } elseif ($project_natural_end_ts === 0) {
                        $status_text = __('Configuration Incomplete', 'smc'); $status_class = 'status-config-incomplete';
                    }
                    ?>
                    <tr>
                        <td>
                            <strong><?php echo esc_html($title); ?></strong><br>
                            <small>(<code><?php echo esc_html($key); ?></code>)</small>
                        </td>
                        <td><?php echo esc_html($creation_date ? date_i18n('Y-m-d H:i', strtotime($creation_date)) : 'N/A'); ?></td>
                        <td><?php echo esc_html($acceptance_end_str ? date_i18n('Y-m-d H:i', strtotime($acceptance_end_str)) : 'N/A'); ?></td>
                        <td><?php echo esc_html($project_start_str ? date_i18n('Y-m-d H:i', strtotime($project_start_str)) : 'N/A'); ?></td>
                        <td><span class="smc-status-badge <?php echo esc_attr($status_class); ?>"><?php echo esc_html($status_text); ?></span></td>
                        <td>
                            <?php if ($status_class === 'status-pending-margin' || $status_class === 'status-active' || $status_class === 'status-processing-margin'): ?>
                                <button type="button" class="button button-small smc-enter-final-profit-btn" data-key="<?php echo esc_attr($key); ?>" data-current-profit="<?php echo esc_attr($investment_config['actual_final_profit'] ?? ''); ?>">
                                    <i class="dashicons dashicons-edit-large" style="vertical-align: text-bottom; font-size: 18px;"></i> <?php echo $actual_final_profit_is_set ? __('Edit Final Profit', 'smc') : __('Enter Final Profit', 'smc'); ?>
                                </button>
                            <?php endif; ?>
                             <a href="<?php echo admin_url('admin.php?page=smc-investment-types-settings&view=edit_project&edit_project_key=' . esc_attr($key)); ?>" class="button button-small">
                                <i class="dashicons dashicons-admin-tools" style="vertical-align: text-bottom; font-size: 18px;"></i> <?php _e('Edit Details', 'smc'); ?>
                            </a>
                            <button type="button" class="button button-small button-danger smc-delete-investment-btn" data-key="<?php echo esc_attr($key); ?>" data-title="<?php echo esc_attr($title); ?>" style="color:red; border-color:red; margin-top: 5px;">
                                <i class="dashicons dashicons-trash" style="vertical-align: text-bottom; font-size: 18px;"></i> <?php _e('Delete', 'smc'); ?>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <div id="smc-final-profit-modal" style="display:none; background: #f1f1f1; padding: 20px; border: 1px solid #ccc; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 1001; box-shadow: 0 0 15px rgba(0,0,0,0.2);">
        <h3><?php _e('Enter/Edit Actual Final Profit for Project', 'smc'); ?> <span id="modal-project-title"></span></h3>
        <p><?php _e('This is the total actual profit generated by the entire project, which will then be distributed among investors based on their shares after deducting already distributed periodic profits.', 'smc'); ?></p>
        <input type="hidden" id="modal-investment-key" value="">
        <p>
            <label for="modal-actual-final-profit"><?php _e('Actual Final Profit (DZD):', 'smc'); ?></label><br>
            <input type="number" step="any" id="modal-actual-final-profit" class="regular-text">
        </p>
        <button type="button" id="smc-save-final-profit-btn" class="button button-primary"><?php _e('Save Final Profit', 'smc'); ?></button>
        <button type="button" id="smc-close-final-profit-modal" class="button button-secondary"><?php _e('Cancel', 'smc'); ?></button>
        <div id="smc-modal-message" style="margin-top:10px;"></div>
    </div>
    <div id="smc-modal-backdrop" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000;"></div>

    <div style="margin-top: 30px; padding: 15px; border: 2px dashed red; background-color: #fff5f5;">
        <h3><i class="dashicons dashicons-warning" style="color:red;"></i> <?php _e('إجراءات خطيرة', 'smc'); ?></h3>
        <p><?php _e('الأزرار الموجودة في هذا القسم تقوم بعمليات كبيرة وقد تكون غير قابلة للعكس. يرجى توخي الحذر الشديد والتأكد قبل النقر.', 'smc'); ?></p>
        <button type="button" id="smc-admin-cancel-old-investments-btn" class="button button-danger">
            <i class="dashicons dashicons-trash"></i> <?php _e('إلغاء جميع الاستثمارات ما قبل new_investment_1000011 وإعادة الأموال', 'smc'); ?>
        </button>
    </div>
    <?php
}


/**
 * Displays other sections on the admin dashboard overview page.
 */
function smc_display_admin_dashboard_sections() {
    global $wpdb;
    if (!($wpdb instanceof wpdb)) {
        echo '<p class="smc-error-message">Error: Database connection object is not available.</p>';
        return;
    }
    echo '<hr>';
    echo '<h2>' . __('Quick Access & Overview', 'smc') . '</h2>';
    echo '<div style="margin-bottom: 20px;">';
    echo '<h3><span class="dashicons dashicons-warning"></span> ' . __('Pending Approvals', 'smc') . '</h3>';
    $pending_deposits = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}user_deposits WHERE status = %s", 'pending_admin_approval'));
    $pending_deposit_withdrawals = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}user_withdrawals WHERE status = %s", 'pending'));
    $pending_profit_withdrawals = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}user_profit_withdrawals WHERE status = %s", 'pending'));

    if (is_wp_error($pending_deposits)) $pending_deposits = 0;
    if (is_wp_error($pending_deposit_withdrawals)) $pending_deposit_withdrawals = 0;
    if (is_wp_error($pending_profit_withdrawals)) $pending_profit_withdrawals = 0;

    echo '<p>' . sprintf(__('Pending Deposits: %s', 'smc'), '<a href="' . esc_url(home_url('/proof-payment-record/')) . '"><strong>' . intval($pending_deposits) . '</strong></a>') . '</p>';
    echo '<p>' . sprintf(__('Pending Deposit Withdrawals: %s', 'smc'), '<a href="' . esc_url(home_url('/users-deposit-withdrawal-log/')) . '"><strong>' . intval($pending_deposit_withdrawals) . '</strong></a>') . '</p>';
    echo '<p>' . sprintf(__('Pending Profit Withdrawals: %s', 'smc'), '<a href="' . esc_url(home_url('/users-profit-withdrawal-log/')) . '"><strong>' . intval($pending_profit_withdrawals) . '</strong></a>') . '</p>';
    echo '</div>';

    echo '<div style="margin-bottom: 20px;">';
    echo '<h3><span class="dashicons dashicons-list-view"></span> ' . __('View Logs', 'smc') . '</h3>';
    echo '<ul style="list-style: disc; margin-right: 20px;">';
    echo '<li><a href="' . esc_url(home_url('/users-deposit-log/')) . '">' . __('All Deposits Log', 'smc') . '</a></li>';
    echo '<li><a href="' . esc_url(home_url('/proof-payment-record/')) . '">' . __('Proof of Payment Log', 'smc') . '</a></li>';
    echo '<li><a href="' . esc_url(home_url('/users-deposit-withdrawal-log/')) . '">' . __('Deposit Withdrawals Log', 'smc') . '</a></li>';
    echo '<li><a href="' . esc_url(home_url('/users-profit-withdrawal-log/')) . '">' . __('Profit Withdrawals Log', 'smc') . '</a></li>';
    echo '<li><a href="' . esc_url(home_url('/users-rewards-log/')) . '">' . __('Rewards Log', 'smc') . '</a></li>';
    echo '<li><a href="' . esc_url(home_url('/users-advertising-deals-record/')) . '">' . __('Ad Deals Log', 'smc') . '</a></li>';
    echo '<li><a href="' . esc_url(home_url('/users-referral-log/')) . '">' . __('Referral Log (Detailed)', 'smc') . '</a></li>';
    echo '<li><a href="' . esc_url(home_url('/users-referral-tree/')) . '">' . __('Referral List (Who referred whom)', 'smc') . '</a></li>';
    echo '<li><a href="' . esc_url(home_url('/users-attendance-log/')) . '">' . __('Attendance Log', 'smc') . '</a></li>';
    echo '<li><a href="' . esc_url(home_url('/members-login-log/')) . '">' . __('Members Login Log', 'smc') . '</a></li>';
    echo '<li><a href="' . esc_url(home_url('/displaying-ads-log/')) . '">' . __('Ad Impressions Log', 'smc') . '</a></li>';
    echo '<li><a href="' . esc_url(home_url('/number-clicks-log/')) . '">' . __('Button Clicks Log', 'smc') . '</a></li>';
    echo '<li><a href="' . esc_url(home_url('/php_error_log/')) . '">' . __('PHP Error Log Viewer', 'smc') . '</a></li>';
    echo '</ul>';
    echo '</div>';
}

function smc_admin_settings_styles() {
    $screen = get_current_screen();
    $target_pages = ['toplevel_page_smc-settings', 'smc-settings_page_smc-reward-settings', 'smc-settings_page_ad-deal-settings', 'smc-settings_page_smc-investment-types-settings', 'smc-settings_page_smc-cron-jobs-status']; // Cron page already here
    if ($screen && in_array($screen->id, $target_pages)) {
        ?>
        <style>
            .smc-settings-wrap .wp-list-table th,
            .smc-settings-wrap .wp-list-table td { vertical-align: middle; }
            .smc-settings-wrap .smc-reward-key[readonly] { cursor: not-allowed; background-color: #f0f0f0; }
            .smc-settings-wrap .smc-value-field input[type="number"] { margin-right: 10px; }
            .smc-settings-wrap .smc-reward-value-complex-fields label { margin-bottom: 5px; display: block; }
            .smc-settings-wrap .code { font-family: monospace; }
            .smc-settings-wrap .button .dashicons { vertical-align: text-top; margin-right: 3px; }
            .smc-settings-wrap .smc-remove-reward .dashicons { margin-right: 0; }
            .smc-settings-wrap .nav-tab-wrapper { margin-bottom: 20px; }
            .smc-error-message { color: #dc3545; background-color: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; border-radius: 5px; margin: 10px 0; }
            .smc-investment-type-item { border: 1px solid #ccd0d4; padding: 15px; margin-bottom: 15px; background-color: #fdfdfd; border-radius: 4px; }
            .smc-investment-type-item h4 { margin-top: 0; margin-bottom: 10px; color: #2271b1; }
            .smc-investment-type-item p { margin: 0 0 10px 0; }
            .smc-investment-type-item label strong { display: inline-block; margin-bottom: 3px; }
            .smc-investment-type-item hr { border: 0; border-top: 1px solid #e0e0e0; margin-top: 15px; }
            .smc-settings-table th, .smc-settings-table td { padding: 8px; text-align: center; vertical-align: middle; }
            .smc-settings-table th:first-child { text-align: right; font-weight: bold; }
            .smc-settings-table input[type="number"] { width: 90px; text-align: center; }
            .wrap h1 i { margin-left: 10px; }
            .smc-status-badge { padding: 3px 8px; border-radius: 12px; font-size: 0.85em; color: #fff; display: inline-block; }
            .status-active { background-color: #28a745; }
            .status-pending-margin { background-color: #ffc107; color: #333; }
            .status-processing-margin { background-color: #17a2b8; }
            .status-finished { background-color: #6c757d; }
            .status-config-incomplete { background-color: #dc3545; }
            .status-undefined { background-color: #adb5bd; }
            .smc-investment-search-input { margin-bottom: 5px; }
            .roi-plan-item label { display: block; margin-bottom: 5px;}
            .roi-plan-item input[type="number"], .roi-plan-item select { margin-right: 5px;}
            .smc-production-expenses-table th, .smc-production-expenses-table td,
            .smc-final-margin-table th, .smc-final-margin-table td { padding: 8px; vertical-align: middle; }
            .smc-production-expenses-table input[type="number"], .smc-final-margin-table input[type="number"] { width: 100px; }
            .smc-production-expenses-table input[readonly], .smc-final-margin-table input[readonly] { background-color: #eee; }
        </style>
        <?php
    }
}
add_action('admin_head', 'smc_admin_settings_styles');

/**
 * Outputs JavaScript for the Cron Jobs Status page.
 */
function smc_cron_jobs_status_page_js() {
    // The nonce should be created directly here for the inline script.
    // The 'smc_data' JavaScript object is localized for 'smc-main-script', not directly accessible as a PHP variable here.
    $run_cron_nonce = '';
    if (is_admin() && current_user_can('manage_options')) {
        $run_cron_nonce = wp_create_nonce('smc_run_cron_job_nonce');
    }
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        $('.smc-run-cron-now-btn').on('click', function() {
            var button = $(this);
            var hookName = button.data('hook');
            var originalButtonText = button.html();

            button.prop('disabled', true).html('<span class="dashicons dashicons-update-alt spin"></span> <?php echo esc_js(__('Running...', 'smc')); ?>');

            Swal.fire({
                title: '<?php echo esc_js(__('Confirm Execution', 'smc')); ?>',
                text: '<?php echo esc_js(__('Are you sure you want to run the cron job', 'smc')); ?> "' + hookName + '" <?php echo esc_js(__('now? This might take a moment.', 'smc')); ?>',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: '<?php echo esc_js(__('Yes, run it!', 'smc')); ?>',
                cancelButtonText: '<?php echo esc_js(__('Cancel', 'smc')); ?>'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: ajaxurl, // WordPress AJAX URL
                        type: 'POST',
                        data: {
                            action: 'smc_admin_run_cron_now', // New AJAX action
                            hook_name: hookName,
                            nonce: '<?php echo esc_js($run_cron_nonce); ?>'
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                Swal.fire('<?php echo esc_js(__('Success!', 'smc')); ?>', response.data.message, 'success');
                                // Optionally, refresh the page or update the "Next Run" time dynamically
                                // location.reload(); // Simple refresh
                            } else {
                                Swal.fire('<?php echo esc_js(__('Error!', 'smc')); ?>', response.data.message || '<?php echo esc_js(__('An unknown error occurred.', 'smc')); ?>', 'error');
                            }
                        },
                        error: function(jqXHR, textStatus, errorThrown) {
                            Swal.fire('<?php echo esc_js(__('AJAX Error!', 'smc')); ?>', '<?php echo esc_js(__('Could not connect to the server to run the cron job.', 'smc')); ?> (' + textStatus + ')', 'error');
                            console.error("AJAX Error:", textStatus, errorThrown, jqXHR.responseText);
                        },
                        complete: function() {
                            button.prop('disabled', false).html(originalButtonText);
                        }
                    });
                } else {
                    button.prop('disabled', false).html(originalButtonText); // Re-enable if cancelled
                }
            });
        });
    });
    </script>
    <?php
}

if (!function_exists('smc_get_default_reward_settings_local_fallback')) {
    function smc_get_default_reward_settings_local_fallback() {
        return [
            'referral_deposit_l1' => ['type' => 'percentage', 'value' => 0.03, 'trigger' => 'deposit', 'level' => 1, 'description' => 'مكافئة الإحالة مستوى الأول (إيداع)'],
            'referral_deposit_l2' => ['type' => 'percentage', 'value' => 0.02, 'trigger' => 'deposit', 'level' => 2, 'description' => 'مكافئة الإحالة مستوى الثاني (إيداع)'],
            'referral_deposit_l3' => ['type' => 'percentage', 'value' => 0.01, 'trigger' => 'deposit', 'level' => 3, 'description' => 'مكافئة الإحالة مستوى الثالث (إيداع)'],
            'daily_task_l1' => ['type' => 'percentage', 'value' => 0.03, 'trigger' => 'ad_completion', 'level' => 1, 'description' => 'مكافئة المهام اليومية مستوى الأول'],
            'daily_task_l2' => ['type' => 'percentage', 'value' => 0.02, 'trigger' => 'ad_completion', 'level' => 2, 'description' => 'مكافئة المهام اليومية مستوى الثاني'],
            'daily_task_l3' => ['type' => 'percentage', 'value' => 0.01, 'trigger' => 'ad_completion', 'level' => 3, 'description' => 'مكافئة المهام اليومية مستوى الثالث'],
            'investment_l1' => ['type' => 'percentage', 'value' => 0.03, 'trigger' => 'deposit', 'level' => 1, 'description' => 'مكافئة الإستثمار مستوى الأول'],
            'investment_l2' => ['type' => 'percentage', 'value' => 0.02, 'trigger' => 'deposit', 'level' => 2, 'description' => 'مكافئة الإستثمار مستوى الثاني'],
            'investment_l3' => ['type' => 'percentage', 'value' => 0.01, 'trigger' => 'deposit', 'level' => 3, 'description' => 'مكافئة الإستثمار مستوى الثالث'],
            'rank_vip1' => ['type' => 'fixed_monthly', 'value' => 3000, 'required_referrals_min' => 2, 'required_referrals_max' => 7],
            'rank_vip2' => ['type' => 'fixed_monthly', 'value' => 7000, 'required_referrals_min' => 3, 'required_referrals_max' => 15],
            'rank_vip3' => ['type' => 'fixed_monthly', 'value' => 20000, 'required_referrals_min' => 4, 'required_referrals_max' => 35],
            'rank_vip4' => ['type' => 'fixed_monthly', 'value' => 45000, 'required_referrals_min' => 5, 'required_referrals_max' => 70],
            'rank_vip5' => ['type' => 'fixed_monthly', 'value' => 100000, 'required_referrals_min' => 7, 'required_referrals_max' => 150],
            'agent_district' => ['type' => 'fixed_monthly', 'value' => 30000, 'required_vip3_min' => 2, 'required_vip3_max' => 10, 'location_scope' => 'district'],
            'agent_city' => ['type' => 'fixed_monthly', 'value' => 100000, 'required_vip3_min' => 5, 'required_vip3_max' => 30, 'location_scope' => 'city'],
            'deposit_withdrawal_fee' => ['type' => 'percentage_plus_fixed', 'value' => ['percentage' => 0.01, 'fixed' => 30], 'trigger' => 'withdrawal_approval', 'level' => null, 'description' => 'رسوم سحب الوديعة (1% + 30 دج)'],
            'profit_withdrawal_fee' => ['type' => 'percentage_plus_fixed', 'value' => ['percentage' => 0.01, 'fixed' => 30], 'trigger' => 'withdrawal_approval', 'level' => null, 'description' => 'رسوم سحب الأرباح (1% + 30 دج)'],
            'signup_bonus' => ['type' => 'fixed', 'value' => 0, 'trigger' => 'registration', 'level' => null, 'description' => 'مكافأة التسجيل (للمستخدم الجديد)'],
            'referrer_signup_bonus' => ['type' => 'fixed', 'value' => 0, 'trigger' => 'referral_signup', 'level' => null, 'description' => 'مكافأة دعوة صديق (للداعي)'],
            'daily_attendance' => ['type' => 'fixed', 'value' => 10],
        ];
    }
}

function smc_get_ad_deal_settings() {
     $defaults = [
        'plan_1' => ['deposit_min' => 2000, 'deposit_max' => 4999.99, 'ad_price_min' => 1, 'ad_price_max' => 2, 'profit_perc_min' => 0.134, 'profit_perc_max' => 0.200, 'duration_min' => 15, 'duration_max' => 16, 'daily_limit' => 10],
        'plan_2' => ['deposit_min' => 5000, 'deposit_max' => 9999.99, 'ad_price_min' => 2.5, 'ad_price_max' => 5, 'profit_perc_min' => 0.144, 'profit_perc_max' => 0.210, 'duration_min' => 16, 'duration_max' => 18, 'daily_limit' => 11],
        'plan_3' => ['deposit_min' => 10000, 'deposit_max' => 24999.99, 'ad_price_min' => 6, 'ad_price_max' => 12, 'profit_perc_min' => 0.154, 'profit_perc_max' => 0.220, 'duration_min' => 18, 'duration_max' => 20, 'daily_limit' => 12],
        'plan_4' => ['deposit_min' => 25000, 'deposit_max' => 49999.99, 'ad_price_min' => 18, 'ad_price_max' => 36, 'profit_perc_min' => 0.164, 'profit_perc_max' => 0.230, 'duration_min' => 20, 'duration_max' => 22, 'daily_limit' => 13],
        'plan_5' => ['deposit_min' => 50000, 'deposit_max' => 99999.99, 'ad_price_min' => 40, 'ad_price_max' => 80, 'profit_perc_min' => 0.174, 'profit_perc_max' => 0.240, 'duration_min' => 22, 'duration_max' => 25, 'daily_limit' => 14],
        'plan_6' => ['deposit_min' => 100000, 'deposit_max' => 249999.99, 'ad_price_min' => 90, 'ad_price_max' => 180, 'profit_perc_min' => 0.184, 'profit_perc_max' => 0.250, 'duration_min' => 25, 'duration_max' => 28, 'daily_limit' => 15],
        'plan_7' => ['deposit_min' => 250000, 'deposit_max' => 499999.99, 'ad_price_min' => 240, 'ad_price_max' => 450, 'profit_perc_min' => 0.194, 'profit_perc_max' => 0.260, 'duration_min' => 28, 'duration_max' => 30, 'daily_limit' => 16],
        'plan_8' => ['deposit_min' => 500000, 'deposit_max' => 999999999.99, 'ad_price_min' => 470, 'ad_price_max' => 700, 'profit_perc_min' => 0.204, 'profit_perc_max' => 0.270, 'duration_min' => 30, 'duration_max' => 35, 'daily_limit' => 17],
        'global_tax_rate' => 0.19,
    ];
    return wp_parse_args(get_option(SMC_AD_SETTINGS_OPTION, []), $defaults);
}

function smc_ad_deal_plans_section_callback() { echo '<p>قم بتعديل قيم كل خطة إعلانية أدناه. تأكد من أن نطاقات الإيداع لا تتداخل.</p>'; }

function smc_ad_deal_table_field_callback() {
    $settings = smc_get_ad_deal_settings();
    ?>
    <div style="overflow-x: auto;">
    <table class="form-table smc-settings-table" border="1" style="border-collapse: collapse; width: 100%;">
        <thead>
            <tr>
                <th>المعلمة</th>
                <?php for ($i = 1; $i <= 8; $i++): ?><th>الخطة <?php echo $i; ?></th><?php endfor; ?>
            </tr>
        </thead>
        <tbody>
            <?php
            $parameters = [
                'deposit_min' => 'حد الإيداع الأدنى (دج)', 'deposit_max' => 'حد الإيداع الأقصى (دج)',
                'ad_price_min' => 'سعر الإعلان الأدنى (دج)', 'ad_price_max' => 'سعر الإعلان الأقصى (دج)',
                'profit_perc_min' => 'نسبة الربح الأدنى (%)', 'profit_perc_max' => 'نسبة الربح الأقصى (%)',
                'duration_min' => 'مدة الإعلان الأدنى (ثا)', 'duration_max' => 'مدة الإعلان الأقصى (ثا)',
                'daily_limit' => 'عدد الإعلانات اليومية',
            ];
            foreach ($parameters as $key => $label): ?>
                <tr>
                    <th><?php echo esc_html($label); ?></th>
                    <?php for ($i = 1; $i <= 8; $i++):
                        $plan_key = 'plan_' . $i;
                        $value = $settings[$plan_key][$key] ?? '';
                        if (strpos($key, 'profit_perc') !== false) { $value = $value * 100; }
                        $step = (strpos($key, 'perc') !== false || strpos($key, 'price') !== false) ? '0.001' : '1';
                        $input_type = ($key === 'daily_limit' || strpos($key, 'duration') !== false) ? 'number' : 'number';
                        $min_val = ($key === 'daily_limit' || strpos($key, 'duration') !== false) ? '0' : '0';
                        ?>
                        <td>
                            <input type="<?php echo $input_type; ?>" step="<?php echo $step; ?>" min="<?php echo $min_val; ?>"
                                   name="<?php echo esc_attr(SMC_AD_SETTINGS_OPTION . '[' . $plan_key . '][' . $key . ']'); ?>"
                                   value="<?php echo esc_attr($value); ?>" class="small-text" required>
                            <?php if (strpos($key, 'profit_perc') !== false) echo '%'; ?>
                        </td>
                    <?php endfor; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <p class="description">ملاحظة: عند إدخال نسب الربح، أدخل القيمة كرقم (مثال: 15.4 وليس 15.4%).</p>
    <?php
}

function smc_ad_deal_tax_rate_field_callback() {
    $settings = smc_get_ad_deal_settings();
    $tax_rate = ($settings['global_tax_rate'] ?? 0.19) * 100;
    ?>
    <input type="number" step="0.01" min="0" max="100" name="<?php echo esc_attr(SMC_AD_SETTINGS_OPTION . '[global_tax_rate]'); ?>" value="<?php echo esc_attr($tax_rate); ?>" class="small-text" required> %
    <p class="description">أدخل معدل الضريبة كنسبة مئوية (مثال: 19).</p>
    <?php
}

function smc_sanitize_ad_deal_settings($input) {
    $output = [];
    $defaults = smc_get_ad_deal_settings();

    if (isset($input['global_tax_rate'])) {
        $tax_rate_percent = floatval($input['global_tax_rate']);
        $output['global_tax_rate'] = max(0, min(100, $tax_rate_percent)) / 100.0;
    } else {
        $output['global_tax_rate'] = $defaults['global_tax_rate'];
    }

    for ($i = 1; $i <= 8; $i++) {
        $plan_key = 'plan_' . $i;
        $output[$plan_key] = [];

        if (isset($input[$plan_key]) && is_array($input[$plan_key])) {
            foreach ($defaults[$plan_key] as $key => $default_value) {
                if (isset($input[$plan_key][$key])) {
                    $value = $input[$plan_key][$key];
                    if ($key === 'daily_limit' || strpos($key, 'duration') !== false) {
                        $output[$plan_key][$key] = absint($value);
                    } elseif (strpos($key, 'profit_perc') !== false) {
                        $perc_value = floatval($value);
                        $output[$plan_key][$key] = max(0, $perc_value) / 100.0;
                    } else {
                        $output[$plan_key][$key] = max(0, floatval($value));
                    }
                } else {
                    $output[$plan_key][$key] = $default_value;
                }
            }
        } else {
            $output[$plan_key] = $defaults[$plan_key];
        }
    }
    return $output;
}
?>
