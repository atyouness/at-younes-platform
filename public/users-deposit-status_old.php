<?php
/**
 * Template Name: Users Deposit Status (Admin)
 * Description: Displays user deposit status, rank, and other details for administrators.
 */

if (!defined('ABSPATH')) exit; // Exit if accessed directly

// Redirect if not logged in or not an admin
if (!is_user_logged_in() || !current_user_can('administrator')) {
    wp_redirect(home_url('/'));
    exit;
}

get_header();
global $wpdb;
?>

<div class="container smc-log-container">
    <h2><i class="fas fa-users-cog"></i> وضعية الودائع والمستخدمين (للمسؤول)</h2>
    <a href="<?php echo esc_url(home_url('/smc-settings/')); ?>" class="smc-button smc-button-secondary" style="margin-bottom: 15px; display: inline-block;"><i class="fas fa-cog"></i> العودة إلى إعدادات SMC</a>

    <!-- Log Controls -->
    <div class="smc-log-controls" style="margin-bottom: 20px; padding: 15px; background-color: #f9f9f9; border: 1px solid #eee; border-radius: 5px;">
        <p><strong>أدوات التحكم بالسجل:</strong></p>
        <p>يمكنك استخدام حقل "بحث" أدناه للبحث عن طريق اسم المستخدم، معرف المستخدم، الرتبة، أو معرف الداعي. يمكنك أيضًا فرز الأعمدة وتصدير البيانات باستخدام الأزرار.</p>
    </div>

    <!-- Date Filter Section -->
    <div class="smc-date-filter-section" style="margin-bottom: 20px; padding: 15px; background-color: #f0f8ff; border: 1px solid #d1e7fd; border-radius: 5px;">
        <strong>تصفية حسب تاريخ آخر إيداع:</strong>
        <label for="start-date">من:</label>
        <input type="date" id="start-date" name="start-date" style="padding: 5px; border: 1px solid #ccc; border-radius: 4px;">
        <label for="end-date" style="margin-right: 10px;">إلى:</label>
        <input type="date" id="end-date" name="end-date" style="padding: 5px; border: 1px solid #ccc; border-radius: 4px;">
        <button id="filter-button" class="smc-button" style="padding: 5px 10px; margin-right: 5px;"><i class="fas fa-filter"></i> تطبيق</button>
        <button id="clear-filter-button" class="smc-button smc-button-secondary" style="padding: 5px 10px;"><i class="fas fa-times"></i> مسح</button>
    </div>

    <table id="admin-user-deposit-status-table" class="display compact stripe hover smc-log-table" style="width:100%">
        <thead>
            <tr>
                <th>اسم المستخدم</th>
                <th>معرف المستخدم</th>
                <th>الوديعة الحالية (دج)</th> <?php // *** عمود جديد: الوديعة الحالية *** ?>                
                <th>تاريخ آخر إيداع</th>
                <th>حالة الوديعة</th>
                <th>الرتبة</th>
                <th>عرض الملف الشخصي</th>
                <th>عدد أعضاء الفريق</th>
                <th>فريق</th> <?php // *** عمود جديد: فريق *** ?>                
                <th>معرف الداعي</th>
                <th>اسم مستخدم الداعي</th>
            </tr>
        </thead>
        <tfoot> <?php // Added footer for column search ?>
            <tr>
                <th><input type="text" placeholder="بحث في اسم المستخدم" class="column-search"/></th>
                <th><input type="text" placeholder="بحث في معرف المستخدم" class="column-search"/></th>
                <th><input type="text" placeholder="بحث في الوديعة الحالية" class="column-search"/></th>
                <th><input type="text" placeholder="بحث في تاريخ آخر إيداع" class="column-search"/></th>
                <th><input type="text" placeholder="بحث في حالة الوديعة" class="column-search"/></th>
                <th><input type="text" placeholder="بحث في الرتبة" class="column-search"/></th>
                <th></th> <?php // No search for profile button ?>
                <th><input type="text" placeholder="بحث في عدد أعضاء الفريق" class="column-search"/></th>
                <th></th> <?php // No search for team button ?>
                <th><input type="text" placeholder="بحث في معرف الداعي" class="column-search"/></th>
                <th><input type="text" placeholder="بحث في اسم مستخدم الداعي" class="column-search"/></th>
            </tr>
        </tfoot>
        <tbody>
            <?php
            $users = get_users(['fields' => 'all_with_meta']); // Get all users

            if ($users) {
                foreach ($users as $user) {
                    $user_id = $user->ID;
                    $user_smc_data = [];
                    // *** بداية التعديل: التحقق من وجود الدالة ***
                    if (function_exists('smc_get_user_data')) {
                        $user_smc_data = smc_get_user_data($user_id);
                    } else {
                        error_log("SMC User Deposit Status Error: Function smc_get_user_data() not found for user ID: " . $user_id);
                        // يمكنك إضافة قيم افتراضية هنا إذا لزم الأمر أو عرض رسالة خطأ في الصف
                    }
                    // *** نهاية التعديل ***                    
                    // --- Use current_tasks_deposit_balance for display ---
                    $current_deposit_display = $user_smc_data['current_tasks_deposit_balance'] ?? 0.0;
                    // $current_deposit = $user_smc_data['current_deposit'] ?? (float)get_user_meta($user_id, SMC_DEPOSIT_BALANCE, true); // This is total deposit
                    $last_deposit_timestamp = $user_smc_data['last_deposit_timestamp'] ?? (int)get_user_meta($user_id, SMC_LAST_DEPOSIT_TIMESTAMP, true);

                    $deposit_status = ($current_deposit_display >= 2000) ? '<span class="status-active">نشط</span>' : '<span class="status-inactive">غير نشط</span>';
                    $last_deposit_date_str = $last_deposit_timestamp ? date_i18n('Y-m-d H:i', $last_deposit_timestamp) : 'N/A';

                    // *** بداية التعديل: التحقق من وجود الدالة ***
                    $rank = 'VIP0'; // Default
                    if (function_exists('smc_get_user_rank')) {
                        $rank = smc_get_user_rank($user_id);
                    } else {
                        error_log("SMC User Deposit Status Error: Function smc_get_user_rank() not found for user ID: " . $user_id);
                    }
                    // *** نهاية التعديل ***

                    $team_member_count = 0;
                    if (function_exists('smc_get_referral_downline_recursive') && function_exists('smc_count_downline_members_recursive')) {
                        $downline_data = smc_get_referral_downline_recursive($user_id, 3); // Max 3 levels
                        $team_member_count = smc_count_downline_members_recursive($downline_data);
                    }

                    $referrer_id = get_user_meta($user_id, SMC_REFERRED_BY, true);
                    $referrer_username = 'N/A';
                    if ($referrer_id) {
                        $referrer_info = get_userdata($referrer_id);
                        $referrer_username = $referrer_info ? $referrer_info->user_login : 'ID: ' . $referrer_id;
                    }

                    $profile_link = esc_url(home_url('/user/' . $user->user_login . '/')); // Ultimate Member profile link
                    // *** إضافة: رابط لصفحة فريق المستخدم ***
                    $team_link_url = esc_url(home_url('/user-downline-tree/?view_user_id=' . $user_id));


                    echo '<tr>';
                    echo '<td>' . esc_html($user->user_login) . '</td>';
                    echo '<td>' . esc_html($user_id) . '</td>';
                    // --- Display current_tasks_deposit_balance ---
                    echo '<td><span dir="ltr">' . esc_html(number_format($current_deposit_display, 2, '.', '')) . ' دج</span></td>';                   
                    echo '<td>' . esc_html($last_deposit_date_str) . '</td>';
                    echo '<td>' . $deposit_status . '</td>';
                    echo '<td>' . esc_html($rank) . '</td>';
                    echo '<td><a href="' . $profile_link . '" class="smc-button smc-button-view" target="_blank"><i class="fas fa-eye"></i> عرض</a></td>';
                    echo '<td>' . esc_html($team_member_count) . '</td>';
                    // *** إضافة: خلية زر الفريق ***
                    echo '<td><a href="' . $team_link_url . '" class="smc-button smc-button-team" target="_blank"><i class="fas fa-users"></i> فريق</a></td>';                   
                    echo '<td>' . esc_html($referrer_id ?: 'N/A') . '</td>';
                    echo '<td>' . esc_html($referrer_username) . '</td>';
                    echo '</tr>';
                }
            } else {
                // *** تعديل: زيادة colspan ليناسب العمود الجديد ***
                echo '<tr><td colspan="11">لا يوجد مستخدمون لعرضهم.</td></tr>';
            }
            ?>
        </tbody>
    </table>

    <!-- Summary Section -->
    <div id="summary-deposit-status-results" class="smc-summary-section" style="margin-top: 20px; padding: 15px; background-color: #e9f5e9; border: 1px solid #c8e6c9; border-radius: 5px;">
        <h4><i class="fas fa-calculator"></i> ملخص الفترة المحددة:</h4>
        <div class="summary-grid">
            <div><strong>مجموع الودائع الحالية:</strong> <span id="sum-current-deposits">0.00</span> دج</div>
        </div>
    </div>

</div>

<?php get_footer(); ?>

<script type="text/javascript">
jQuery(document).ready(function($) {
    if ($.fn.DataTable) { // Check if DataTables is loaded
        try {
            var table = $('#admin-user-deposit-status-table').DataTable({ // Assign DataTable instance to a variable
                responsive: true,
                dom: 'Bfrtip',
                buttons: ['copy', 'csv', 'excel', 'pdf', 'print'],
                order: [[ 0, "asc" ]], // Default sort by username
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/ar.json',
                    search: "بحث:"
                },
                columnDefs: [
                    // *** تعديل: تحديث فهارس الأعمدة غير القابلة للفرز/البحث ***
                    { targets: [6, 8], orderable: false, searchable: false } // View profile button (now index 6), Team button (now index 8)
                ]
            });

            // --- Date Range Filter for "تاريخ آخر إيداع" (Column index 3) ---
            $.fn.dataTable.ext.search.push(
                function( settings, data, dataIndex ) {
                    if (settings.nTable.id !== 'admin-user-deposit-status-table') {
                        return true;
                    }
                    const startDateStr = $('#start-date').val();
                    const endDateStr = $('#end-date').val();
                    const dateStr = data[3]; // Column 3 is "تاريخ آخر إيداع"

                    if (!startDateStr && !endDateStr) { return true; }
                    if (dateStr === 'N/A') return false; // Exclude rows with N/A date if filtering

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

            // --- Footer Column Search ---
            $('#admin-user-deposit-status-table tfoot th .column-search').on('keyup change clear', function() {
                if (table) {
                    table.column($(this).parent().index() + ':visible')
                         .search(this.value)
                         .draw();
                }
            });

            // --- Function to calculate and display summary ---
            function calculateDepositSummary(tableInstance) {
                let sumCurrentDeposits = 0;

                tableInstance.rows({ search: 'applied' }).every(function() { // Iterate over filtered/searched rows
                    const data = this.data();
                    // Helper to parse currency string like "1,234.56 دج" to float
                    const parseCurrency = (value) => parseFloat(String(value).replace(/[^0-9.-]+/g,"")) || 0;
                    
                    sumCurrentDeposits += parseCurrency(data[2]); // This should now correctly sum the displayed tasks deposit
                });

                $('#sum-current-deposits').text(sumCurrentDeposits.toFixed(2));
            }

            // Calculate summary on initial load
            calculateDepositSummary(table);

            // Recalculate summary on table draw (e.g., after search, sort, pagination)
            table.on('draw.dt', function() {
                calculateDepositSummary(table);
            });

        } catch (e) {
            console.error("Error initializing DataTables for user deposit status:", e);
            $('.smc-log-container').prepend('<p class="smc-error-message">حدث خطأ أثناء تحميل جدول السجلات التفاعلي.</p>');
        }
    } else {
        console.warn("DataTables library not found for user deposit status.");
        $('.smc-log-container').prepend('<p class="smc-error-message">لم يتم تحميل مكتبة الجداول التفاعلية (DataTables).</p>');
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
.smc-log-table tfoot input.column-search { width: 100%; padding: 3px; box-sizing: border-box; font-size: 0.9em; border: 1px solid #ccc; }
.smc-log-table tfoot th { padding: 5px; }

.smc-date-filter-section label { margin-left: 5px; margin-right: 5px;}
.smc-date-filter-section input[type="date"] { padding: 5px; border: 1px solid #ccc; border-radius: 4px; margin-bottom: 5px;}

/* Status specific styles */
.status-active { color: #28a745; font-weight: bold; }
.status-inactive { color: #dc3545; font-weight: bold; }

/* Button Styles */
.smc-button-secondary {
    background-color: #6c757d; border-color: #6c757d; color: white; padding: 5px 10px;
    text-decoration: none; border-radius: 4px; display: inline-block; font-size: 0.9em;
}
.smc-button-secondary:hover { background-color: #5a6268; border-color: #545b62; color: white; }
.smc-button-secondary i { margin-left: 5px; }

.smc-button-view, .smc-button-team { /* *** تعديل: تطبيق نفس النمط على زر الفريق *** */
    background-color: #007bff; border-color: #007bff; color: white !important; padding: 4px 8px;
    text-decoration: none; border-radius: 4px; display: inline-flex; align-items: center;
    font-size: 0.85em;
    margin: 0 2px; /* إضافة هامش بسيط بين الأزرار إذا كانت متجاورة */
}
.smc-button-view:hover, .smc-button-team:hover { /* *** تعديل: تطبيق نفس النمط على زر الفريق *** */
    background-color: #0056b3; border-color: #0056b3; color: white !important;
}
.smc-button-view i, .smc-button-team i { /* *** تعديل: تطبيق نفس النمط على زر الفريق *** */
    margin-left: 3px;
}

/* Error message style */
.smc-error-message { color: #dc3545; background-color: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; border-radius: 5px; margin-bottom: 15px; }

/* DataTables Controls */
.dt-buttons .dt-button { /* ... (existing styles) ... */ }
.dataTables_filter label { /* ... (existing styles) ... */ }
.dataTables_filter input { /* ... (existing styles) ... */ }

/* Summary Section Styles (can be shared with other log pages) */
.smc-summary-section h4 i { margin-left: 8px; color: #28a745; }
.summary-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 10px; }
.summary-grid div { background-color: #fff; padding: 8px; border-radius: 4px; font-size: 0.95em; }
.summary-grid span { font-weight: bold; color: #0056b3; direction: ltr; display: inline-block; }

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
