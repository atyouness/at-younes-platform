<?php
/**
 * Template Name: Admin Investment Profits Log
 * Description: Displays daily investment profits for all users (Admin view).
 */

if (!defined('ABSPATH')) exit; // Exit if accessed directly

// Redirect if not logged in or not an admin
if (!is_user_logged_in() || !current_user_can('administrator')) {
    wp_redirect(home_url('/'));
    exit;
}

get_header();
global $wpdb;
$deposits_table = $wpdb->prefix . 'user_deposits';
$rewards_table = $wpdb->prefix . 'smc_rewards_log';

// جلب إعدادات أنواع الاستثمار مرة واحدة
$all_investment_types_config = get_option('smc_investment_types_settings', []);
?>


<div class="container smc-log-container">
    <h2><i class="fas fa-chart-pie"></i> سجل أرباح الاستثمار اليومية (للمسؤول)</h2>
    <a href="<?php echo esc_url(home_url('/smc-settings/')); ?>" class="smc-button smc-button-secondary" style="margin-bottom: 15px; display: inline-block;"><i class="fas fa-cog"></i> العودة إلى إعدادات SMC</a>

     <div class="smc-log-controls" style="margin-bottom: 20px; padding: 15px; background-color: #f9f9f9; border: 1px solid #eee; border-radius: 5px;">
        <p><strong>أدوات التحكم بالسجل:</strong> يمكنك البحث، فرز الأعمدة، وتصدير البيانات.</p>
    </div>

    <!-- Date Filter Section -->
    <div class="smc-date-filter-section" style="margin-bottom: 20px; padding: 15px; background-color: #f0f8ff; border: 1px solid #d1e7fd; border-radius: 5px;">
        <label for="start-date">من:</label> <!-- No style change needed for label here if controlled by CSS -->
        <input type="date" id="start-date" name="start-date">
        <label for="end-date">إلى:</label> <!-- No style change needed for label here -->
        <input type="date" id="end-date" name="end-date">
        <button id="filter-button" class="smc-button"><i class="fas fa-filter"></i> تطبيق</button>
        <button id="clear-filter-button" class="smc-button smc-button-secondary"><i class="fas fa-times"></i> مسح</button>
    </div>

    <!-- Column Search Section -->
    <div class="smc-column-search-section" style="margin-bottom: 20px; padding: 15px; background-color: #f0f8ff; border: 1px solid #d1e7fd; border-radius: 5px;">
        <p><strong>بحث مخصص:</strong></p>
        <input type="text" placeholder="بحث في اسم المستخدم" class="column-search" data-col-index="0">
        <input type="text" placeholder="بحث في معرف المستخدم" class="column-search" data-col-index="1">
        <input type="text" placeholder="بحث في التاريخ" class="column-search" data-col-index="2">
        <input type="text" placeholder="بحث في المبلغ" class="column-search" data-col-index="3">
        <input type="text" placeholder="بحث في مصدر الإيداع (ID)" class="column-search" data-col-index="4">
        <input type="text" placeholder="بحث في الحالة" class="column-search" data-col-index="5">
        <input type="text" placeholder="بحث في المشروع" class="column-search" data-col-index="6">
    </div>

    <table id="admin-investment-profits-table" class="display compact stripe hover smc-log-table" style="width:100%">
        <thead>
            <tr>
                <th>اسم المستخدم</th>
                <th>معرف المستخدم</th>
                <th>تاريخ الربح</th>
                <th>مبلغ الربح (دج)</th>
                <th>مصدر الإيداع (ID)</th>
                <th>الحالة</th>
                <th>المشروع</th>
                <th>عرض الملف الشخصي</th>
                <th>فريق</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $combined_log_entries = [];
            try {
                // Fetch all approved investment deposits
                $investment_deposits = $wpdb->get_results(
                    "SELECT id, user_id, investment_start_datetime, status, deposit_type, investment_package, investment_shares, investment_duration, expected_daily_roi
                     FROM {$deposits_table}
                     WHERE status = 'approved' AND deposit_type != 'daily_tasks'
                     AND investment_start_datetime IS NOT NULL" // Only include if actual start date is set
                );

                // Fetch all daily investment profit rewards
                $daily_profits = $wpdb->get_results(
                    "SELECT user_id, reward_timestamp, amount, related_info, reward_type FROM {$rewards_table}
                     WHERE reward_type IN ('investment_periodic_profit', 'investment_daily_profit', 'investment_final_margin')"
                );

                // Add investment start entries
                if (is_array($investment_deposits) && !empty($investment_deposits)) {
                    foreach ($investment_deposits as $deposit) {
                        if (!is_object($deposit)) {
                            error_log("Admin Investment Log Error: Non-object found in \$investment_deposits. Data: " . print_r($deposit, true));
                            continue;
                        }
                        $start_datetime_str = $deposit->investment_start_datetime ?? null;
                        $start_timestamp = $start_datetime_str ? strtotime($start_datetime_str) : false;

                        if ($start_timestamp === false) {
                            error_log("Admin Investment Log Error: Failed to parse investment_start_datetime '{$start_datetime_str}' for deposit ID: " . ($deposit->id ?? 'N/A'));
                            continue;
                        }
                        
                        $deposit_type_key_for_start = (string)($deposit->deposit_type ?? 'N/A');
                        $project_display_name_for_start = isset($all_investment_types_config[$deposit_type_key_for_start]['title']) && !empty($all_investment_types_config[$deposit_type_key_for_start]['title'])
                                                        ? esc_html($all_investment_types_config[$deposit_type_key_for_start]['title'])
                                                        : esc_html($deposit_type_key_for_start);


                        $combined_log_entries[] = [
                            'user_id' => $deposit->user_id,
                            'timestamp' => $start_timestamp,
                            'date_display' => date_i18n('Y-m-d H:i', $start_timestamp),
                            'amount' => 0.00,
                            'source_id' => $deposit->id ?? 'N/A',
                            'status' => $deposit->status ?? 'N/A',
                            'details' => 'بدء الاستثمار',
                            'investment_details' => sprintf(
                                "النوع: %s%s%s%s", // Removed one %s as ROI is not for start
                                $project_display_name_for_start,
                                (isset($deposit->investment_package) && !empty($deposit->investment_package) ? ", باقة: " . esc_html(str_replace('_', ' ', (string)$deposit->investment_package)) : ""),
                                (isset($deposit->investment_shares) && is_numeric($deposit->investment_shares) ? ", حصص: " . esc_html((string)$deposit->investment_shares) : ""),
                                (isset($deposit->investment_duration) && is_numeric($deposit->investment_duration) ? ", مدة: " . esc_html((string)$deposit->investment_duration) . " يوم" : "")
                                // expected_daily_roi is part of the deposit record but typically shown with profit, not start.
                            ),
                            'raw_deposit_type' => $deposit_type_key_for_start, // Store raw type if needed later
                            'type' => 'start'
                        ];
                    }
                }

                // Add daily profit entries
                if (is_array($daily_profits) && !empty($daily_profits)) {
                    foreach ($daily_profits as $profit) {
                        if (!is_object($profit)) {
                            error_log("Admin Investment Log Error: Non-object found in \$daily_profits. Data: " . print_r($profit, true));
                            continue;
                        }
                        $reward_timestamp_str = $profit->reward_timestamp ?? null;
                        $profit_entry_timestamp = $reward_timestamp_str ? strtotime($reward_timestamp_str) : false;

                        if ($profit_entry_timestamp === false) {
                            error_log("Admin Investment Log Error: Failed to parse reward_timestamp '{$reward_timestamp_str}' for profit entry. User ID: " . ($profit->user_id ?? 'N/A'));
                            continue;
                        }

                        $source_deposit_id = 'N/A';
                        $related_info_val = (string)($profit->related_info ?? '');
                        if (!empty($related_info_val) && strpos($related_info_val, 'Investment ID: ') !== false) {
                            $parts = explode('Investment ID: ', $related_info_val);
                            if (isset($parts[1])) {
                                $id_part = explode(',', $parts[1])[0];
                                $source_deposit_id = intval(trim($id_part));
                            }
                        }

                        // Determine details text based on reward_type
                        $profit_details_text_for_entry = 'ربح استثمار'; // Default
                        if (isset($profit->reward_type) && $profit->reward_type === 'investment_periodic_profit') {
                            if (strpos($related_info_val, 'per hourly') !== false) {
                                $profit_details_text_for_entry = 'ربح ساعي للاستثمار';
                            } elseif (strpos($related_info_val, 'per daily') !== false) {
                                $profit_details_text_for_entry = 'ربح يومي للاستثمار';
                            } elseif (strpos($related_info_val, 'per per_minute') !== false) {
                                $profit_details_text_for_entry = 'ربح دقيقي للاستثمار';
                            } else {
                                $profit_details_text_for_entry = 'ربح دوري للاستثمار'; // Fallback for periodic
                            }
                        } elseif (isset($profit->reward_type) && $profit->reward_type === 'investment_final_margin') {
                            $profit_details_text_for_entry = 'هامش الربح النهائي للاستثمار';
                        } else if (isset($profit->reward_type)) { // Other specific reward types if any
                            $profit_details_text_for_entry = esc_html($profit->reward_type);
                        }

                        $combined_log_entries[] = [
                            'user_id' => $profit->user_id,
                            'timestamp' => $profit_entry_timestamp,
                            'date_display' => date_i18n('Y-m-d H:i', $profit_entry_timestamp),
                            'amount' => (float)($profit->amount ?? 0.0),
                            'source_id' => $source_deposit_id,
                            'status' => 'N/A', // Status doesn't directly apply to individual profit entries in the same way as deposits
                            'details' => $profit_details_text_for_entry,
                            'investment_details' => esc_html($related_info_val),
                            'type' => 'profit'
                        ];
                    }
                }

                // Sort combined entries by timestamp descending
                if (!empty($combined_log_entries)) {
                    usort($combined_log_entries, function($a, $b) {
                        $ts_a = isset($a['timestamp']) && is_numeric($a['timestamp']) ? (int)$a['timestamp'] : 0;
                        $ts_b = isset($b['timestamp']) && is_numeric($b['timestamp']) ? (int)$b['timestamp'] : 0;
                        return $ts_b - $ts_a;
                    });
                }
            } catch (Throwable $e) {
                error_log("Admin Investment Log PHP Error: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
                echo '<tr><td colspan="9">حدث خطأ أثناء معالجة البيانات. يرجى المحاولة مرة أخرى لاحقًا أو الاتصال بالدعم.</td></tr>';
            }

            // Display table rows
            if (!empty($combined_log_entries)) {
                foreach ($combined_log_entries as $entry) {
                    $user_info = get_userdata($entry['user_id']);
                    $username = $user_info ? $user_info->user_login : 'غير معروف (' . $entry['user_id'] . ')';
                    $profile_link = $user_info ? esc_url(home_url('/user/' . $user_info->user_login . '/')) : '#';
                    $team_link_url = esc_url(home_url('/user-downline-tree/?view_user_id=' . $entry['user_id']));
                    
                    echo '<tr>';
                    echo '<td>' . esc_html($username) . '</td>';
                    echo '<td>' . esc_html($entry['user_id']) . '</td>';
                    echo '<td>' . esc_html($entry['date_display'] ?? 'N/A') . '</td>';
                    echo '<td><span dir="ltr">' . esc_html(number_format(floatval($entry['amount'] ?? 0.0), 2, '.', '')) . ' دج</span></td>';
                    echo '<td>' . esc_html($entry['source_id'] ?? 'N/A') . '</td>';                    
                    // Display translated status for 'start' entries
                    $status_display_text = esc_html($entry['status'] ?? 'N/A');
                    if ($entry['type'] === 'start') {
                        switch ($entry['status']) {
                            case 'pending_admin_approval': $status_display_text = 'انتظار موافقة المسؤول'; break;
                            case 'approved': $status_display_text = 'موافقة'; break;
                            case 'rejected': $status_display_text = 'رفض'; break;
                            case 'withdrawal_scheduled': $status_display_text = 'سحب مجدول'; break;
                            case 'cancelled_by_admin_refunded': $status_display_text = 'ملغى (تم الاسترداد)'; break;
                        }
                    }
                    echo '<td>' . $status_display_text . '</td>';
                    
                    $final_details_display = '';
                    $entry_type = $entry['type'] ?? '';
                    $entry_details_text = $entry['details'] ?? 'N/A';
                    $entry_investment_details_text = $entry['investment_details'] ?? '';
                    $project_key_for_button = 'N/A';

                
                    if ($entry_type === 'start') {
                        $project_key_for_button = esc_html($entry['raw_deposit_type'] ?? 'N/A');
                        // Ensure "(مشروع محذوف)" is appended if config is missing
                        $project_title_for_details = isset($all_investment_types_config[$entry['raw_deposit_type']]) ? ($all_investment_types_config[$entry['raw_deposit_type']]['title'] ?? $entry['raw_deposit_type']) : ($entry['raw_deposit_type'] . ' (مشروع محذوف)');
                        $entry_investment_details_text = preg_replace('/النوع: [^,]+/', 'النوع: ' . esc_html($project_title_for_details), $entry_investment_details_text);
                        $final_details_display = esc_html($entry_details_text) . ' - ' . $entry_investment_details_text;
                
                    } elseif ($entry_type === 'profit') {
                        $profit_details_str = $entry_investment_details_text; // This is raw 'related_info'
                        // Use a more permissive regex to capture project key, then trim whitespace
                        if (preg_match('/Type: ([^,]+)/', $profit_details_str, $matches_type)) {
                            $project_key_for_button = esc_html(trim($matches_type[1]));
                        }
                        $parsed_profit_details = [];
                        
                        if (preg_match('/Investment ID: (\d+)/', $profit_details_str, $matches_id)) {
                            $parsed_profit_details['id'] = $matches_id[1];
                        }
                        if (preg_match('/Type: ([a-zA-Z0-9_]+)/', $profit_details_str, $matches_type)) {
                            $type_key = $matches_type[1];
                            $parsed_profit_details['type_title'] = isset($all_investment_types_config[$type_key]['title']) && !empty($all_investment_types_config[$type_key]['title'])
                                                                    ? esc_html($all_investment_types_config[$type_key]['title'])                                                                    
                                                                    : (esc_html($type_key) . (isset($all_investment_types_config[$type_key]) ? '' : ' (مشروع محذوف)'));
                        }
                        if (preg_match('/Amount: ([0-9,.]+)/', $profit_details_str, $matches_amount)) {
                            $parsed_profit_details['investment_amount'] = $matches_amount[1];
                        }
                        if (preg_match('/Daily ROI: ([0-9.]+)%/', $profit_details_str, $matches_roi)) {
                            $parsed_profit_details['roi_percent'] = $matches_roi[1] . '%';
                        }
                
                        $display_parts = [];
                        if (!empty($parsed_profit_details['id'])) $display_parts[] = "معرف الاستثمار: " . $parsed_profit_details['id'];
                        if (!empty($parsed_profit_details['type_title'])) $display_parts[] = "النوع: " . $parsed_profit_details['type_title'];
                        if (!empty($parsed_profit_details['investment_amount'])) $display_parts[] = "المبلغ الأصلي للاستثمار: " . $parsed_profit_details['investment_amount'];
                        if (!empty($parsed_profit_details['roi_percent'])) $display_parts[] = "العائد اليومي المقرر: " . $parsed_profit_details['roi_percent'];
                        
                        $final_details_display = implode('، ', $display_parts);
                        // Prepend the specific profit type description
                        $final_details_display = esc_html($entry_details_text) . (!empty($final_details_display) ? ' - ' . $final_details_display : '');
                        if (empty(trim(str_replace(esc_html($entry_details_text), '', $final_details_display))) || trim($final_details_display) === esc_html($entry_details_text)) {
                             $final_details_display = esc_html($entry_details_text) . ' - ' . esc_html($profit_details_str); // Fallback to raw if parsing yields nothing extra
                        }
                
                    } else {
                        $final_details_display = esc_html($entry_details_text);
                    }

                    echo '<td>';
                    if ($project_key_for_button !== 'N/A' && $project_key_for_button !== '') {
                        echo '<button class="project-details-button" data-project-key="' . esc_attr($project_key_for_button) . '" data-project-details="' . esc_attr($final_details_display) . '">' . esc_html($project_key_for_button) . '</button>';
                    } else {
                        echo esc_html($final_details_display); // Fallback if no key
                    }
                    echo '</td>';
                    echo '<td><a href="' . $profile_link . '" class="smc-button smc-button-view" target="_blank"><i class="fas fa-eye"></i> عرض</a></td>';
                    echo '<td><a href="' . $team_link_url . '" class="smc-button smc-button-team" target="_blank"><i class="fas fa-users"></i> فريق</a></td>';
                    echo '</tr>';
                }
            } else {
                echo '<tr><td colspan="9">لا توجد سجلات استثمار أو أرباح لعرضها.</td></tr>';
            }
            ?>
        </tbody>
    </table>
    <!-- يمكن إضافة قسم ملخص هنا -->
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
            table = $('#admin-investment-profits-table').DataTable({
                responsive: true,
                dom: 'Bfrtip',
                buttons: ['copy', 'csv', 'excel', 'pdf', 'print'],
                order: [[2, "desc"]], // Order by date (third column)
                language: { url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/ar.json' }
            });

            // --- Function to calculate and display summary ---
            function calculateSummary(tableInstance) {
                let sumAmounts = 0;
                tableInstance.rows({ search: 'applied' }).every(function() {
                    const data = this.data();
                    const parseCurrency = (value) => parseFloat(String(value).replace(/[^0-9.-]+/g,"")) || 0;
                    // Only sum amounts if it's a profit entry (amount > 0)
                    const amount = parseCurrency(data[3]); // Column index for "مبلغ الربح (دج)" is 3
                    if (amount > 0) {
                        sumAmounts += amount;
                    }
                 });
                $('#sum-investment-amounts').text(sumAmounts.toFixed(2));
            }

            // --- Date Range Filter ---
            $.fn.dataTable.ext.search.push(
                function( settings, data, dataIndex ) {
                    if (settings.nTable.id !== 'admin-investment-profits-table') {
                        return true;
                    }
                    const startDateStr = $('#start-date').val();
                    const endDateStr = $('#end-date').val();
                    const dateStr = data[2]; // Column 2 is "التاريخ (بدء/ربح)"

                    if (!startDateStr && !endDateStr) { return true; }
                    if (dateStr === 'N/A') return false;

                    const dateParts = dateStr.split(' '); // Assuming format "YYYY-MM-DD HH:MM"
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

            // Calculate summary on initial load and after each draw
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
                modalBody.html(projectDetails.replace(/، /g, '<br>')); // Replace commas with line breaks for readability
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
        } catch (e) { console.error("Error initializing DataTables for admin investment profits:", e); }
    }
});
</script>
<style>
/* General Log Table Styles */
.smc-log-container { max-width: 1200px; margin: 20px auto; }
.smc-log-table { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 0.9em; }
.smc-log-table th, .smc-log-table td { border: 1px solid #ddd; padding: 6px 8px; text-align: right; vertical-align: middle; }
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
    padding: 6px 10px; /* Consistent padding, will be affected by global mobile styles */
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

/* View and Team Button Styles */
.smc-button-view, .smc-button-team {
    background-color: #007bff; border-color: #007bff; color: white !important; padding: 4px 8px;
    text-decoration: none; border-radius: 4px; display: inline-flex; align-items: center; font-size: 0.85em; margin: 0 2px;
}
.smc-button-view:hover, .smc-button-team:hover { background-color: #0056b3; border-color: #0056b3; color: white !important; }
.smc-button-view i, .smc-button-team i { margin-left: 3px; }

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
