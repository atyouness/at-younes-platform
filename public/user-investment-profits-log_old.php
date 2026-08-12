<?php
/**
 * Template Name: User Investment Profits Log
 * Description: Displays the daily investment profits for the current user.
 */

if (!defined('ABSPATH')) exit; // Exit if accessed directly

// Redirect if not logged in
if (!is_user_logged_in()) {
    wp_redirect(home_url('/'));
    exit;
}

get_header();
$user_id = get_current_user_id();
global $wpdb;
$deposits_table = $wpdb->prefix . 'user_deposits';
$rewards_table = $wpdb->prefix . 'smc_rewards_log';

// جلب إعدادات أنواع الاستثمار مرة واحدة
$all_investment_types_config = get_option('smc_investment_types_settings', []);
?>

<div class="container smc-log-container">
    <h2><i class="fas fa-chart-pie"></i> سجل أرباح الاستثمار اليومية</h2>
    <a href="<?php echo esc_url(home_url('/transactional/')); ?>" class="smc-button smc-button-secondary" style="margin-bottom: 15px; display: inline-block;"><i class="fas fa-arrow-left"></i> العودة إلى معاملاتي</a>

    <div class="smc-log-controls" style="margin-bottom: 20px; padding: 15px; background-color: #f9f9f9; border: 1px solid #eee; border-radius: 5px;">
        <p><strong>أدوات التحكم بالسجل:</strong> يمكنك البحث، فرز الأعمدة، وتصدير البيانات.</p>
    </div>

    <!-- Date Filter Section -->
    <div class="smc-date-filter-section" style="margin-bottom: 20px; padding: 15px; background-color: #f0f8ff; border: 1px solid #d1e7fd; border-radius: 5px;">
        <strong>تصفية حسب التاريخ:</strong>
        <label for="start-date">من:</label> <!-- No style change needed for label here -->
        <input type="date" id="start-date" name="start-date">
        <label for="end-date">إلى:</label> <!-- No style change needed for label here -->
        <input type="date" id="end-date" name="end-date">
        <button id="filter-button" class="smc-button"><i class="fas fa-filter"></i> تطبيق</button>
        <button id="clear-filter-button" class="smc-button smc-button-secondary"><i class="fas fa-times"></i> مسح</button>
    </div>

    <!-- Column Search Section -->
    <div class="smc-column-search-section" style="margin-bottom: 20px; padding: 15px; background-color: #f0f8ff; border: 1px solid #d1e7fd; border-radius: 5px;">
        <p><strong>بحث مخصص:</strong></p>
        <input type="text" placeholder="بحث في التاريخ" class="column-search" data-col-index="0">
        <input type="text" placeholder="بحث في المبلغ" class="column-search" data-col-index="1">
        <input type="text" placeholder="بحث في مصدر الإيداع (ID)" class="column-search" data-col-index="2">
        <input type="text" placeholder="بحث في الحالة" class="column-search" data-col-index="3">
        <input type="text" placeholder="بحث في المشروع" class="column-search" data-col-index="4">
    </div>

    <table id="user-investment-profits-table" class="display compact stripe hover smc-log-table" style="width:100%">
        <thead>
            <tr>
                <th>التاريخ (بدء/ربح)</th>
                <th>المبلغ (دج)</th>
                <th>مصدر الإيداع (ID)</th>
                <th>الحالة</th>
                <th>المشروع</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $combined_log_entries = [];
            try {
                // Fetch approved investment deposits for the user
                $investment_deposits = $wpdb->get_results(
                    $wpdb->prepare(
                        "SELECT id, investment_start_datetime, status, deposit_type, investment_package, investment_shares, investment_duration, expected_daily_roi
                         FROM {$deposits_table}
                         WHERE user_id = %d AND status = 'approved' AND deposit_type != 'daily_tasks'
                         AND investment_start_datetime IS NOT NULL", // Only include if actual start date is set
                        $user_id
                    )
                );

                // Fetch daily investment profit rewards for the user
                $daily_profits = $wpdb->get_results(
                    $wpdb->prepare(
                        "SELECT reward_timestamp, amount, related_info, reward_type FROM {$rewards_table}
                         WHERE user_id = %d AND (reward_type = 'investment_periodic_profit' OR reward_type = 'investment_daily_profit' OR reward_type = 'investment_final_margin')",
                        $user_id
                    )
                );

                // Add investment start entries
                if (is_array($investment_deposits) && !empty($investment_deposits)) {
                    foreach ($investment_deposits as $deposit) {
                        if (!is_object($deposit)) {
                            error_log("User Investment Log Error: Non-object found in \$investment_deposits for user_id: $user_id. Data: " . print_r($deposit, true));
                            continue;
                        }
                        $start_datetime_str = $deposit->investment_start_datetime ?? null;
                        $start_timestamp = $start_datetime_str ? strtotime($start_datetime_str) : false;

                        if ($start_timestamp === false) {
                            error_log("User Investment Log Error: Failed to parse investment_start_datetime '{$start_datetime_str}' for deposit ID: " . ($deposit->id ?? 'N/A'));
                            continue; // Skip this entry if date is invalid
                        }
                        
                        $deposit_type_key_for_start_user = (string)($deposit->deposit_type ?? 'N/A');
                        $project_display_name_for_start_user = isset($all_investment_types_config[$deposit_type_key_for_start_user]['title']) && !empty($all_investment_types_config[$deposit_type_key_for_start_user]['title'])
                                                                ? esc_html($all_investment_types_config[$deposit_type_key_for_start_user]['title'])
                                                                : esc_html($deposit_type_key_for_start_user);

                        $combined_log_entries[] = [
                            'timestamp' => $start_timestamp,
                            'date_display' => date_i18n('Y-m-d H:i', $start_timestamp),
                            'amount' => 0.00, // Amount is 0 for the start entry
                            'source_id' => $deposit->id ?? 'N/A',
                            'status' => $deposit->status ?? 'N/A',
                            'details' => 'بدء الاستثمار', // Event type
                            'investment_details' => sprintf(
                                "النوع: %s%s%s%s", // Removed one %s
                                $project_display_name_for_start_user,
                                (isset($deposit->investment_package) && !empty($deposit->investment_package) ? ", باقة: " . esc_html(str_replace('_', ' ', (string)$deposit->investment_package)) : ""),
                                (isset($deposit->investment_shares) && is_numeric($deposit->investment_shares) ? ", حصص: " . esc_html((string)$deposit->investment_shares) : ""),
                                (isset($deposit->investment_duration) && is_numeric($deposit->investment_duration) ? ", مدة: " . esc_html((string)$deposit->investment_duration) . " يوم" : "")
                            ),
                            'raw_deposit_type' => $deposit_type_key_for_start_user, // Store raw type
                            'type' => 'start' 
                        ];
                    }
                }

                // Add daily profit entries
                if (is_array($daily_profits) && !empty($daily_profits)) {
                    foreach ($daily_profits as $profit) {
                        if (!is_object($profit)) {
                            error_log("User Investment Log Error: Non-object found in \$daily_profits for user_id: $user_id. Data: " . print_r($profit, true));
                            continue;
                        }
                        $reward_timestamp_str = $profit->reward_timestamp ?? null;
                        $profit_entry_timestamp = $reward_timestamp_str ? strtotime($reward_timestamp_str) : false;

                        if ($profit_entry_timestamp === false) {
                            error_log("User Investment Log Error: Failed to parse reward_timestamp '{$reward_timestamp_str}' for profit entry. User ID: $user_id");
                            continue; // Skip this entry
                        }

                        // Extract Investment ID from related_info
                        $source_deposit_id = 'N/A';
                        $related_info_val = (string)($profit->related_info ?? '');
                        if (!empty($related_info_val) && strpos($related_info_val, 'Investment ID: ') !== false) {
                            $parts = explode('Investment ID: ', $related_info_val);
                            if (isset($parts[1])) {
                                $id_part = explode(',', $parts[1])[0];
                                $source_deposit_id = intval(trim($id_part));
                            }
                        }

                        // Determine details text based on reward_type and related_info
                        $profit_details_text_for_entry_user = 'ربح استثمار'; // Default
                        if (isset($profit->reward_type) && $profit->reward_type === 'investment_periodic_profit') {
                            if (strpos($related_info_val, 'per hourly') !== false) {
                                $profit_details_text_for_entry_user = 'ربح ساعي للاستثمار';
                            } elseif (strpos($related_info_val, 'per daily') !== false) {
                                $profit_details_text_for_entry_user = 'ربح يومي للاستثمار';
                            } elseif (strpos($related_info_val, 'per per_minute') !== false) {
                                $profit_details_text_for_entry_user = 'ربح دقيقي للاستثمار';
                            } else {
                                $profit_details_text_for_entry_user = 'ربح دوري للاستثمار';
                            }
                        } elseif (isset($profit->reward_type) && $profit->reward_type === 'investment_final_margin') {
                            $profit_details_text_for_entry_user = 'هامش الربح النهائي للاستثمار';
                        } else if (isset($profit->reward_type)) {
                            $profit_details_text_for_entry_user = esc_html($profit->reward_type);
                        }
                        $combined_log_entries[] = [
                            'timestamp' => $profit_entry_timestamp,
                            'date_display' => date_i18n('Y-m-d H:i', $profit_entry_timestamp),
                            'amount' => (float)($profit->amount ?? 0.0),
                            'source_id' => $source_deposit_id,
                            'status' => 'N/A', // Status doesn't apply to profit entry
                            'details' => $profit_details_text_for_entry_user,
                            'investment_details' => esc_html($related_info_val), // Use related_info for profit details
                            'type' => 'profit' // Internal type
                        ];
                    }
                }

                // Sort combined entries by timestamp descending
                if (!empty($combined_log_entries)) {
                    usort($combined_log_entries, function($a, $b) {
                        $ts_a = isset($a['timestamp']) && is_numeric($a['timestamp']) ? (int)$a['timestamp'] : 0;
                        $ts_b = isset($b['timestamp']) && is_numeric($b['timestamp']) ? (int)$b['timestamp'] : 0;
                        return $ts_b - $ts_a; // Descending order
                    });
                }
            } catch (Throwable $e) { // Catch any throwable error/exception
                error_log("User Investment Log PHP Error: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
                echo '<tr><td colspan="5">حدث خطأ أثناء معالجة البيانات. يرجى المحاولة مرة أخرى لاحقًا أو الاتصال بالدعم.</td></tr>';
            }

            // Display table rows
            if (!empty($combined_log_entries)) {
                foreach ($combined_log_entries as $entry) {
                    echo '<tr>';
                    echo '<td>' . esc_html($entry['date_display'] ?? 'N/A') . '</td>';
                    echo '<td><span dir="ltr">' . esc_html(number_format(floatval($entry['amount'] ?? 0.0), 2, '.', '')) . ' دج</span></td>';
                    echo '<td>' . esc_html($entry['source_id'] ?? 'N/A') . '</td>';                    
                    // Display translated status for 'start' entries
                    $status_display_text_user = esc_html($entry['status'] ?? 'N/A');
                    if ($entry['type'] === 'start') {
                        switch ($entry['status']) {
                            case 'pending_admin_approval': $status_display_text_user = 'انتظار موافقة المسؤول'; break;
                            case 'approved': $status_display_text_user = 'موافقة'; break;
                            case 'rejected': $status_display_text_user = 'رفض'; break;
                            case 'withdrawal_scheduled': $status_display_text_user = 'سحب مجدول'; break;
                            case 'cancelled_by_admin_refunded': $status_display_text_user = 'ملغى (تم الاسترداد)'; break;
                        }
                    }
                    echo '<td>' . $status_display_text_user . '</td>';
                    
                    $final_details_display = '';
                    $entry_type = $entry['type'] ?? '';
                    $entry_details_text = $entry['details'] ?? 'N/A';
                    $entry_investment_details_text = $entry['investment_details'] ?? '';
                    $project_key_for_button_user = 'N/A';
                
                    if ($entry_type === 'start') {
                        $project_key_for_button_user = esc_html($entry['raw_deposit_type'] ?? 'N/A');
                        // Ensure "(مشروع محذوف)" is appended if config is missing
                        $project_title_for_details_user = isset($all_investment_types_config[$entry['raw_deposit_type']]) ? ($all_investment_types_config[$entry['raw_deposit_type']]['title'] ?? $entry['raw_deposit_type']) : ($entry['raw_deposit_type'] . ' (مشروع محذوف)');
                        $entry_investment_details_text = preg_replace('/النوع: [^,]+/', 'النوع: ' . esc_html($project_title_for_details_user), $entry_investment_details_text);
                        $final_details_display = esc_html($entry_details_text) . ' - ' . $entry_investment_details_text;

                    } elseif ($entry_type === 'profit') {
                        $profit_details_str_user = $entry_investment_details_text; // This is raw 'related_info'
                        $parsed_profit_details_user = [];
                        
                        if (preg_match('/Investment ID: (\d+)/', $profit_details_str_user, $matches_id_user)) {
                            $parsed_profit_details_user['id'] = $matches_id_user[1];
                        }
                        // Use a more permissive regex to capture project key, then trim whitespace
                        if (preg_match('/Type: ([^,]+)/', $profit_details_str_user, $matches_type_user)) {
                            $type_key_user = trim($matches_type_user[1]); // Get the matched key and trim whitespace
                            $project_key_for_button_user = esc_html($type_key_user);
                            $parsed_profit_details_user['type_title'] = isset($all_investment_types_config[$type_key_user]['title']) && !empty($all_investment_types_config[$type_key_user]['title'])                                                                    
                                                                    ? esc_html($all_investment_types_config[$type_key_user]['title'])                                                                    
                                                                    : (esc_html($type_key_user) . (isset($all_investment_types_config[$type_key_user]) ? '' : ' (مشروع محذوف)'));
                        }
                        if (preg_match('/Amount: ([0-9,.]+)/', $profit_details_str_user, $matches_amount_user)) {
                            $parsed_profit_details_user['investment_amount'] = $matches_amount_user[1];
                        }
                        if (preg_match('/Daily ROI: ([0-9.]+)%/', $profit_details_str_user, $matches_roi_user)) {
                            $parsed_profit_details_user['roi_percent'] = $matches_roi_user[1] . '%';
                        }
                
                        $display_parts_user = [];
                        if (!empty($parsed_profit_details_user['id'])) $display_parts_user[] = "معرف الاستثمار: " . $parsed_profit_details_user['id'];
                        if (!empty($parsed_profit_details_user['type_title'])) $display_parts_user[] = "النوع: " . $parsed_profit_details_user['type_title'];
                        if (!empty($parsed_profit_details_user['investment_amount'])) $display_parts_user[] = "المبلغ الأصلي للاستثمار: " . $parsed_profit_details_user['investment_amount'];
                        if (!empty($parsed_profit_details_user['roi_percent'])) $display_parts_user[] = "العائد اليومي المقرر: " . $parsed_profit_details_user['roi_percent'];
                        
                        $final_details_display = implode('، ', $display_parts_user);
                        // Prepend the specific profit type description
                        $final_details_display = esc_html($entry_details_text) . (!empty($final_details_display) ? ' - ' . $final_details_display : '');
                        if (empty(trim(str_replace(esc_html($entry_details_text), '', $final_details_display))) || trim($final_details_display) === esc_html($entry_details_text)) {
                            $final_details_display = esc_html($entry_details_text) . ' - ' . esc_html($profit_details_str_user); // Fallback to raw if parsing yields nothing extra
                        }
                    } else {
                        $final_details_display = esc_html($entry_details_text);
                    }
                    echo '<td>';
                    if ($project_key_for_button_user !== 'N/A' && $project_key_for_button_user !== '') {
                        echo '<button class="project-details-button" data-project-key="' . esc_attr($project_key_for_button_user) . '" data-project-details="' . esc_attr($final_details_display) . '">' . esc_html($project_key_for_button_user) . '</button>';
                    } else {
                        echo esc_html($final_details_display); // Fallback
                    }
                    echo '</td>';
                    echo '</tr>';
                }
            } else {
                echo '<tr><td colspan="5">لا توجد سجلات استثمار أو أرباح لعرضها.</td></tr>';
            }
            ?>
        </tbody>
    </table>
    <!-- Summary Section -->
    <div id="summary-investment-profits-results" class="smc-summary-section" style="margin-top: 20px; padding: 15px; background-color: #e9f5e9; border: 1px solid #c8e6c9; border-radius: 5px;">
        <h4><i class="fas fa-calculator"></i> ملخص الفترة المحددة:</h4>
        <div class="summary-grid">
            <div><strong>مجموع المبالغ المعروضة:</strong> <span id="sum-investment-amounts">0.00</span> دج</div>
        </div>
    </div>
</div>

<!-- Modal Structure -->
<div id="project-details-modal" class="smc-modal" style="display:none;">
    <div class="smc-modal-content">
        <span class="smc-modal-close">&times;</span>
        <h4 id="modal-project-title">تفاصيل المشروع</h4>
        <div id="modal-project-body">
            <!-- Project details will be inserted here -->
        </div>
    </div>
</div>

<?php get_footer(); ?>

<script type="text/javascript">
jQuery(document).ready(function($) {
    if ($.fn.DataTable) {
        var table; // Define table variable
        try {
            table = $('#user-investment-profits-table').DataTable({
                responsive: true,
                dom: 'Bfrtip',
                buttons: ['copy', 'csv', 'excel', 'pdf', 'print'],
                order: [[0, "desc"]], // Order by date (first column)
                language: { url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/ar.json' }
            });

            // --- Function to calculate and display summary ---
            function calculateSummary(tableInstance) {
                let sumAmounts = 0;
                tableInstance.rows({ search: 'applied' }).every(function() {
                    const data = this.data(); 
                    const parseCurrency = (value) => parseFloat(String(value).replace(/[^0-9.-]+/g,"")) || 0;
                    const amount = parseCurrency(data[1]);
                    if (amount > 0) { 
                        sumAmounts += amount;
                    }
                });
                $('#sum-investment-amounts').text(sumAmounts.toFixed(2));
            }

            // --- Date Range Filter ---
            $.fn.dataTable.ext.search.push(
                function( settings, data, dataIndex ) {
                    if (settings.nTable.id !== 'user-investment-profits-table') {
                        return true;
                    }
                    const startDateStr = $('#start-date').val();
                    const endDateStr = $('#end-date').val();
                    const dateStr = data[0]; 

                    if (!startDateStr && !endDateStr) { return true; }
                    if (dateStr === 'N/A') return false;

                    const dateParts = dateStr.split(' '); 
                    const cellDate = dateParts.length > 0 ? new Date(dateParts[0]) : null;

                    if (!cellDate) return false;

                    const startDate = startDateStr ? new Date(startDateStr) : null;
                    const endDate = endDateStr ? new Date(endDateStr) : null;

                    if (endDate) { endDate.setHours(23, 59, 59, 999); }

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

            // --- Column Search ---
            $('.smc-column-search-section input.column-search').on('keyup change clear', function() {
                const colIndex = $(this).data('col-index');
                if (table) {
                    table.column(colIndex).search(this.value).draw();
                }
            });

            calculateSummary(table);
            table.on('draw.dt', function() { calculateSummary(table); });

            // --- Project Details Modal ---
            const modal = $('#project-details-modal');
            const modalTitle = $('#modal-project-title');
            const modalBody = $('#modal-project-body');
            const closeModalButton = $('.smc-modal-close');

            $('body').on('click', '.project-details-button', function() {
                const projectKey = $(this).data('project-key');
                const projectDetails = $(this).data('project-details');

                modalTitle.text('تفاصيل المشروع: ' + projectKey);
                modalBody.html(projectDetails.replace(/، /g, '<br>')); 
                modal.show();
            });

            closeModalButton.on('click', function() {
                modal.hide();
            });

            $(window).on('click', function(event) {
                if ($(event.target).is(modal)) {
                    modal.hide();
                }
            });
        } catch (e) { console.error("Error initializing DataTables for user investment profits:", e); }
    }
});
</script>
<style>
/* General Log Table Styles */
.smc-log-container { max-width: 1100px; margin: 20px auto; }
.smc-log-table { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 0.95em; }
.smc-log-table th, .smc-log-table td { border: 1px solid #ddd; padding: 8px 10px; text-align: right; vertical-align: middle; }
.smc-log-table th { background-color: #f2f2f2; font-weight: bold; }
.smc-log-table tbody tr:nth-child(even) { background-color: #f9f9f9; }
.smc-log-table td span[dir="ltr"] { display: inline-block; } /* Ensure LTR for numbers */

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

/* Date Filter Styles */
.smc-date-filter-section {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 10px; /* Space between items */
}
.smc-date-filter-section strong,
.smc-date-filter-section label {
    margin-right: 5px; /* RTL: margin-left */
}
.smc-date-filter-section input[type="date"],
.smc-date-filter-section .smc-button {
    padding: 6px 10px; /* Consistent padding */
    border: 1px solid #ccc;
    border-radius: 4px;
    margin-bottom: 5px; /* For wrapping */
}

/* Column Search Styles */
.smc-column-search-section {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 10px;
}
.smc-column-search-section p {
    width: 100%; /* Make the title take full width before inputs */
    margin-bottom: 5px;
}
.smc-column-search-section input.column-search {
    padding: 6px 10px; /* Consistent padding */
    border: 1px solid #ccc;
    border-radius: 4px;
    width: 180px; /* Default width for desktop */
    margin-bottom: 5px; /* For wrapping */
}

/* Mobile Overrides for Filters */
@media (max-width: 768px) {
    .smc-date-filter-section,
    .smc-column-search-section {
        flex-direction: column; /* Stack items vertically */
        align-items: stretch; /* Make items take full width */
    }
    .smc-date-filter-section strong,
    .smc-date-filter-section label,
    .smc-column-search-section p {
        margin-bottom: 5px;
        text-align: right; /* Ensure text aligns right */
    }
    .smc-date-filter-section input[type="date"],
    .smc-date-filter-section .smc-button,
    .smc-column-search-section input.column-search {
        width: 100%;
        margin-bottom: 10px;
    }
    .smc-date-filter-section .smc-button {
        margin-right: 0; /* Reset margin for buttons on mobile */
    }
}

/* Summary Section Styles */
.smc-summary-section h4 i { margin-left: 8px; color: #28a745; }
.summary-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 10px; }
.summary-grid div { background-color: #fff; padding: 8px; border-radius: 4px; font-size: 0.95em; }
.summary-grid span { font-weight: bold; color: #0056b3; direction: ltr; display: inline-block; }

/* Modal Styles */
.smc-modal {
    position: fixed; z-index: 1001; left: 0; top: 0; width: 100%; height: 100%;
    overflow: auto; background-color: rgba(0,0,0,0.4); display: none;
}
.smc-modal-content {
    background-color: #fefefe; margin: 10% auto; padding: 20px;
    border: 1px solid #888; width: 80%; max-width: 600px; border-radius: 5px;
    position: relative;
}
.smc-modal-close {
    color: #aaa; float: left; font-size: 28px; font-weight: bold;
    position: absolute; top: 5px; left: 15px;
}
.smc-modal-close:hover, .smc-modal-close:focus {
    color: black; text-decoration: none; cursor: pointer;
}
#modal-project-title { margin-top: 0; color: #333; }
#modal-project-body { white-space: pre-wrap; max-height: 60vh; overflow-y: auto; }
.project-details-button {
    background-color: #007bff; color: white; border: none; padding: 5px 10px;
    text-align: center; text-decoration: none; display: inline-block;
    font-size: 0.9em; margin: 2px 1px; cursor: pointer; border-radius: 4px;
}
.project-details-button:hover { background-color: #0056b3; }
@import url("https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css");
</style>
