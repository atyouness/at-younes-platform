<?php
/**
 * Template Name: SMC Daily Tasks
 * Description: This is a custom template for the SMC Daily Tasks page.
 */

// التأكد من أن WordPress هو من يقوم بتحميل هذا الملف
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// تضمين ملف header.php
get_header();
?>

    <?php
    // التحقق من تسجيل دخول المستخدم
    if (is_user_logged_in()) {
        $current_user = wp_get_current_user();
        $user_id = $current_user->ID;

        $user_smc_data = [];
        $current_deposit_for_display = 0.0; // This will be the spendable tasks deposit
        $last_tasks_deposit_timestamp = null;
        $last_deposit_date_display_str = 'لا يوجد إيداع نشط';
        $tasks_deposit_end_date_for_display = 'N/A';
        $days_remaining_for_deposit_str = 'N/A';
        $ads_watched_on_page = 0;
        $ad_limit_on_page = 0;
        $current_tasks_deposit_active_original_sum = 0.0; // Sum of original active task deposits
        $daily_profit = 0.0;
        $total_profit = 0;
        $points_balance = 0;
        $attended_today = false;
        $tasks_deposit_end_timestamp_for_calc = null;

        $wp_timezone_obj = wp_timezone();

        if (function_exists('smc_get_user_data')) {
            $fetched_data = smc_get_user_data($user_id);

            if (is_array($fetched_data)) {
                $user_smc_data = $fetched_data;
                $current_deposit_for_display = $user_smc_data['current_deposit'] ?? 0.0;
                $current_tasks_deposit_active_original_sum = $user_smc_data['current_tasks_deposit_balance'] ?? 0.0;
                $last_tasks_deposit_timestamp = $user_smc_data['last_tasks_deposit_timestamp'] ?? null;
                $tasks_deposit_end_date_for_display = $user_smc_data['tasks_deposit_end_date_str'] ?? 'N/A';
                $tasks_deposit_end_timestamp_for_calc = $user_smc_data['tasks_deposit_end_timestamp_for_calc'] ?? null;
                $ads_watched_on_page = $user_smc_data['ads_watched_today'] ?? 0;
                $ad_limit_on_page = $user_smc_data['daily_ad_limit'] ?? 0;
                $daily_profit = $user_smc_data['daily_profit'] ?? 0;
                $total_profit = $user_smc_data['total_profit'] ?? 0;
                $points_balance = $user_smc_data['points_balance'] ?? 0;
                $attended_today = $user_smc_data['attended_today'] ?? false;


                if ($last_tasks_deposit_timestamp) {
                    $last_deposit_date_display_str = date_i18n('j F Y \ف\ي h:i:s A', $last_tasks_deposit_timestamp);
                } else {
                    $last_deposit_date_display_str = 'لا يوجد إيداع مهام نشط';
                }

                if ($tasks_deposit_end_timestamp_for_calc) {
                    $now_dt = new DateTime('now', $wp_timezone_obj);
                    $tasks_end_dt = new DateTime("@$tasks_deposit_end_timestamp_for_calc");
                    $tasks_end_dt->setTimezone($wp_timezone_obj);

                    if ($now_dt < $tasks_end_dt) {
                        $interval = $now_dt->diff($tasks_end_dt);
                        $days_php = $interval->days;
                        $hours_php = $interval->h;
                        $minutes_php = $interval->i;
                        $days_remaining_for_deposit_str = "{$days_php} يوم : {$hours_php} ساعة : {$minutes_php} دقيقة";
                     } else {
                        $days_remaining_for_deposit_str = "0 يوم : 0 ساعة : 0 دقيقة";
                     }
                }
            } else {
                error_log("SMC Error: smc_get_user_data() returned non-array in smc-daily-tasks.php for user ID: " . $user_id);
            }
        } else {
            error_log("SMC Error: Function smc_get_user_data() not found in smc-daily-tasks.php");
            $current_deposit_for_display = floatval(get_user_meta($user_id, SMC_DEPOSIT_BALANCE, true) ?: 0);
            $current_tasks_deposit_active_original_sum = $current_deposit_for_display;
            $total_profit = floatval(get_user_meta($user_id, SMC_PROFIT_BALANCE, true) ?: 0);
            $points_balance = intval(get_user_meta($user_id, SMC_POINTS_BALANCE, true) ?: 0);
        }

        $formatted_deposit = number_format((float)$current_deposit_for_display, 2, '.', '');
        $formatted_daily_profit = number_format((float)$daily_profit, 2, '.', '');
        $formatted_total_profit = number_format((float)$total_profit, 2, '.', '');
        ?>
<div class="container smc-dashboard-container">
    <div class="dashboard-header">
        <h3>👋 مرحباً بك <span class="username"><?php echo esc_html($current_user->display_name); ?></span> في منصة SMC</h3>
        <div class="date-time" id="date-time"></div>
    </div>

    <div class="dashboard-grid">
        <div class="data-card deposit-card">
            <div class="card-header">
                <i class="fas fa-wallet card-icon"></i>
                <h4>الوديعة الحالية</h4>
            </div>
            <p class="card-value highlight"><span id="current-tasks-deposit-value-display" dir="ltr"><i class="fas fa-spinner fa-spin"></i></span></p>
         </div>

        <div class="data-card date-card">
            <div class="card-header">
                <i class="fas fa-calendar-alt card-icon"></i>
                <h4>تاريخ الإيداع الأخير</h4>
            </div>
            <p class="card-value" id="last-deposit-date-display"><?php echo esc_html($last_deposit_date_display_str); ?></p>
            <?php if ($last_tasks_deposit_timestamp): ?>
                <span id="last-deposit-timestamp" style="display:none;"><?php echo esc_html($last_tasks_deposit_timestamp); ?></span>
            <?php endif; ?>
        </div>

        <div class="data-card date-card">
            <div class="card-header">
                <i class="fas fa-hourglass-end card-icon"></i>
                <h4>تاريخ نهاية وديعة المهام</h4>
            </div>
            <p class="card-value" id="deposit-end-date-display"><?php echo esc_html($tasks_deposit_end_date_for_display); ?></p>
            <?php if ($tasks_deposit_end_timestamp_for_calc): ?>
                 <span id="deposit-end-timestamp" style="display:none;"><?php echo esc_html($tasks_deposit_end_timestamp_for_calc); ?></span>
            <?php endif; ?>
        </div>

        <div class="data-card days-card">
            <div class="card-header">
                <i class="fas fa-check-circle card-icon"></i>
                <h4>الأيام المتبقية للمهام</h4>
            </div>
            <p class="card-value" id="days-remaining-display"><?php echo esc_html($days_remaining_for_deposit_str); ?></p>
        </div>

        <div class="data-card ads-card">
            <div class="card-header">
                <i class="fas fa-video card-icon"></i>
                <h4>الحد اليومي للإعلانات</h4>
            </div>
            <p class="card-value">
                أنجزت <span id="ads-watched-display"><?php echo esc_html($ads_watched_on_page); ?></span> من <span id="ad-limit-display"><?php echo esc_html($ad_limit_on_page); ?></span> إعلانات
            </p>
            <?php if ($ad_limit_on_page > 0): ?>
            <div class="progress-bar-container">
                <div class="progress-bar" id="ads-progress-bar" style="width: <?php echo ($ad_limit_on_page > 0 ? round(($ads_watched_on_page / $ad_limit_on_page) * 100) : 0); ?>%;"></div>
            </div>
            <?php endif; ?>
        </div>

        <div class="data-card profit-card">
            <div class="card-header">
                <i class="fas fa-hand-holding-usd card-icon"></i>
                <h4>الأرباح اليومية</h4>
            </div>
            <p class="card-value"><span id="daily-profit-display" dir="ltr"><?php echo esc_html($formatted_daily_profit) . ' دج'; ?></span></p>
        </div>

        <div class="data-card total-profit-card">
            <div class="card-header">
                <i class="fas fa-university card-icon"></i>
                <h4>الأرباح الإجمالية</h4>
            </div>
            <p class="card-value"><span id="total-profit-display" dir="ltr"><?php echo esc_html($formatted_total_profit) . ' دج'; ?></span></p>
        </div>

        <div class="data-card attendance-points-card">
            <div class="card-header">
                <i class="fas fa-star card-icon points-icon"></i>
                <h4>نقاط الحضور اليومي</h4>
            </div>
            <div class="card-content" style="text-align: center;">
                <p>رصيد نقاطك: <strong id="smc-dashboard-points-balance" class="card-value highlight"><?php echo esc_html($points_balance); ?></strong> نقطة</p>
                <div id="smc-dashboard-attendance-status">
                    <?php if (!$attended_today): ?>
                        <button id="smc-dashboard-attend-button" class="smc-button attend-button-enhanced">
                            <i class="fas fa-calendar-plus"></i> تسجيل الحضور اليومي <span class="points-badge">(+10 نقاط)</span>
                        </button>
                    <?php else: ?>
                        <p class="attendance-success-msg"><i class="fas fa-check-circle"></i> تم تسجيل حضورك لهذا اليوم.</p>
                    <?php endif; ?>
                </div>
                <div id="smc-dashboard-attendance-message" style="margin-top: 10px; display: none;"></div>
                <p style="margin-top: 15px; font-size: 0.9em;"><a href="<?php echo esc_url(home_url('/activate-daily-attendance/')); ?>">عرض تقويم الحضور</a></p>
            </div>
        </div>

        <?php
        $remaining_ads_today_real = max(0, $ad_limit_on_page - $ads_watched_on_page);
        if ($current_tasks_deposit_active_original_sum >= 2000 && $remaining_ads_today_real > 0) :
        ?>
        <div class="data-card action-card start-tasks-card">
            <a href="<?php echo esc_url(home_url('/advertising-deal/')); ?>" class="smc-button start-tasks-button-main">
                <i class="fas fa-rocket"></i> ابدأ المهام (<span id="remaining-ads-display"><?php echo $remaining_ads_today_real; ?></span> متبقية)
            </a>
        </div>
        <?php elseif ($current_tasks_deposit_active_original_sum < 2000): ?>
          <div class="data-card info-card tasks-blocked-card">
            <p><i class="fas fa-exclamation-triangle"></i> يجب إيداع 2000 دج على الأقل لبدء المهام.</p>
         </div>
        <?php else: ?>
        <div class="data-card info-card tasks-complete-card">
            <p><i class="fas fa-check-double"></i> لقد أكملت جميع مهام الإعلانات لهذا اليوم!</p>
        </div>
        <?php endif; ?>

        <div class="data-card action-card info-tasks-card">
            <a href="<?php echo esc_url(home_url('/tasks-info/')); ?>" class="smc-button smc-button-secondary">
                <i class="fas fa-info-circle"></i> معلومات المهام
            </a>
        </div>

    </div>

</div>

<?php
    } else {
        echo '<div class="container">';
        echo '<section class="login-section" id="login-section">';
        echo '<h2>نظام المصادقة</h2>';
        echo do_shortcode('[ultimatemember form_id="217"]');
        echo '</section>';
        echo '</div>';
    }
    ?>

<?php
get_footer();
?>

<style>
@import url("https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css");

.smc-dashboard-container {
    padding: 20px;
    background-color: #f4f7f6;
}

.dashboard-header {
    background-color: #fff;
    padding: 15px 20px;
    border-radius: 8px;
    margin-bottom: 25px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
}
.dashboard-header h3 {
    margin: 0;
    color: #333;
    font-size: 1.4em;
}
.dashboard-header .username {
    color: #007bff;
    font-weight: bold;
}
.dashboard-header .date-time {
    font-size: 0.9em;
    color: #666;
    margin-top: 5px;
}

.dashboard-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 20px;
}

.data-card {
    background-color: #ffffff;
    border-radius: 10px;
    padding: 20px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
    display: flex;
    flex-direction: column;
    border-left: 5px solid #007bff;
}
.deposit-card { border-left-color: #17a2b8; }
.date-card { border-left-color: #6c757d; }
.days-card { border-left-color: #ffc107; }
.ads-card { border-left-color: #fd7e14; }
.profit-card { border-left-color: #28a745; }
.total-profit-card { border-left-color: #20c997; }
.attendance-points-card { border-left-color: #6f42c1; }
.action-card { border-left-color: #dc3545; }
.info-card { border-left-color: #ffc107; }


.data-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
}

.card-header {
    display: flex;
    align-items: center;
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
    color: #495057;
}

.card-header h4 {
    margin: 0 10px 0 0;
    font-size: 1.1em;
    font-weight: 600;
    flex-grow: 1;
}

.card-icon {
    font-size: 1.6em;
    color: #007bff;
    width: 35px;
    text-align: center;
    margin-left: 10px;
}
.deposit-card .card-icon { color: #17a2b8; }
.date-card .card-icon { color: #6c757d; }
.days-card .card-icon { color: #ffc107; }
.ads-card .card-icon { color: #fd7e14; }
.profit-card .card-icon { color: #28a745; }
.total-profit-card .card-icon { color: #20c997; }
.attendance-points-card .card-icon { color: #6f42c1; }
.action-card .card-icon { color: #dc3545; }
.info-card .card-icon { color: #ffc107; }


.card-content {
    flex-grow: 1;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    text-align: center;
}

.card-value {
    font-size: 1.4em;
    font-weight: bold;
    color: #333;
    margin: 10px 0;
}
.card-value.highlight {
    color: #007bff;
    font-size: 1.8em;
}
.card-value span[dir="ltr"] {
    direction: ltr;
    display: inline-block;
}

.progress-bar-container {
    height: 8px;
    background-color: #e9ecef;
    border-radius: 4px;
    overflow: hidden;
    margin-top: 10px;
    width: 80%;
    margin-left: auto;
    margin-right: auto;
}

.progress-bar {
    height: 100%;
    background-color: #28a745;
    width: 0%;
    transition: width 0.5s ease-in-out;
    border-radius: 4px;
}

.attend-button-enhanced {
    background-color: #6f42c1;
    color: white;
    padding: 10px 18px;
    border: none;
    border-radius: 20px;
    cursor: pointer;
    transition: background-color 0.3s ease, transform 0.2s ease;
    font-size: 0.95em;
    margin-top: 10px;
}
.attend-button-enhanced:hover {
    background-color: #5a32a3;
    transform: scale(1.05);
}
.attend-button-enhanced:disabled {
    background-color: #aaa;
    cursor: not-allowed;
    opacity: 0.7;
    transform: none;
}
.attend-button-enhanced i {
    margin-left: 5px;
}
.points-badge {
    background-color: rgba(255, 255, 255, 0.2);
    padding: 2px 6px;
    border-radius: 10px;
    font-size: 0.8em;
    margin-right: 5px;
}
.attendance-success-msg {
    color: #28a745;
    font-weight: bold;
    margin-top: 10px;
}
.attendance-success-msg i {
    margin-left: 5px;
}

.start-tasks-button-main, .smc-button-secondary {
    padding: 12px 25px;
    font-size: 1.1em;
    border-radius: 25px;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
    margin-top: 15px;
}
.start-tasks-button-main {
    background-color: #dc3545;
    border: 1px solid #dc3545;
    color: white;
}
.start-tasks-button-main:hover {
    background-color: #c82333;
    border-color: #bd2130;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}
.smc-button-secondary {
    background-color: #6c757d;
    border: 1px solid #6c757d;
    color: white;
}
.smc-button-secondary:hover {
    background-color: #5a6268;
    border-color: #545b62;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}
.start-tasks-button-main i, .smc-button-secondary i {
    margin-left: 8px;
}

.info-card p {
    color: #856404;
    background-color: #fff3cd;
    border: 1px solid #ffeeba;
    padding: 10px 15px;
    border-radius: 5px;
    font-weight: 500;
    margin-top: 15px;
}
.info-card p i {
    margin-left: 5px;
}
.tasks-complete-card p {
    color: #155724;
    background-color: #d4edda;
    border: 1px solid #c3e6cb;
}

@media (max-width: 768px) {
    .dashboard-grid {
        grid-template-columns: repeat(auto-fit, minmax(230px, 1fr)); /* تصغير عرض البطاقة الأدنى */
    }
    .card-value {
        font-size: 1.1em; /* تصغير قيمة البطاقة */
    }
    .card-value.highlight {
        font-size: 1.3em; /* تصغير القيمة المميزة */
    }
    .start-tasks-button-main, .smc-button-secondary {
        padding: 10px 20px;
        font-size: 0.95em; /* تصغير خط الأزرار الرئيسية */
    }
    .dashboard-header {
        flex-direction: column;
        align-items: flex-start;
    }
     .dashboard-header .date-time {
         margin-top: 10px;
     }
    .card-header h4 {
        font-size: 1em; /* تصغير عنوان البطاقة */
    }
    .card-icon {
        font-size: 1.4em; /* تصغير أيقونة البطاقة */
    }
}

</style>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    function updateDateTime() {
        const now = new Date();
        const optionsDate = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric', timeZone: 'Africa/Algiers' };
        const optionsTime = { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true, timeZone: 'Africa/Algiers' };
        const formattedDate = now.toLocaleDateString('ar-DZ', optionsDate);
        const formattedTime = now.toLocaleTimeString('ar-DZ', optionsTime);
        const dateTimeString = `${formattedDate} - ${formattedTime}`;
        const dateTimeElement = document.getElementById('date-time');
        if (dateTimeElement) {
            dateTimeElement.textContent = dateTimeString;
        }
    }
    updateDateTime();
    setInterval(updateDateTime, 1000);

    const attendButton = document.getElementById('smc-dashboard-attend-button');
    const attendanceStatusDiv = document.getElementById('smc-dashboard-attendance-status');
    const pointsBalanceSpan = document.getElementById('smc-dashboard-points-balance');

    if (attendButton) {
        attendButton.addEventListener('click', function() {
            const button = this;
            button.disabled = true;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> جارٍ التسجيل...';

            jQuery.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: {
                    action: 'smc_handle_daily_attendance',
                    nonce: '<?php echo wp_create_nonce('smc_attendance_nonce'); ?>'
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success', title: 'تم!', text: response.data.message,
                            timer: 2500, showConfirmButton: false
                        });
                        if (pointsBalanceSpan) { pointsBalanceSpan.textContent = response.data.new_points_balance; }
                        if (attendanceStatusDiv) { attendanceStatusDiv.innerHTML = '<p class="attendance-success-msg"><i class="fas fa-check-circle"></i> تم تسجيل حضورك لهذا اليوم.</p>'; }
                    } else {
                        Swal.fire({ icon: 'error', title: 'خطأ!', text: response.data.message || 'حدث خطأ غير متوقع.' });
                        button.disabled = false;
                        button.innerHTML = '<i class="fas fa-calendar-plus"></i> تسجيل الحضور اليومي <span class="points-badge">(+10 نقاط)</span>';
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                     Swal.fire({ icon: 'error', title: 'خطأ اتصال!', text: 'حدث خطأ في الاتصال بالخادم. يرجى المحاولة مرة أخرى.' });
                    console.error("AJAX Error:", textStatus, errorThrown, jqXHR.responseText);
                    button.disabled = false;
                    button.innerHTML = '<i class="fas fa-calendar-plus"></i> تسجيل الحضور اليومي <span class="points-badge">(+10 نقاط)</span>';
                }
            });
        });
    }
});
</script>

