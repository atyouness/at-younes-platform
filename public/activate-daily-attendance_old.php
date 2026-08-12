<?php
/**
 * Template Name: Daily Attendance Page
 * Description: Allows users to check in daily and view their attendance calendar.
 */

if (!defined('ABSPATH')) exit; // Exit if accessed directly

// Redirect if not logged in
if (!is_user_logged_in()) {
    wp_redirect(home_url('/')); // Or login page
    exit;
}

get_header();

// --- Start: Add empty loop to prevent default editor content ---
if ( have_posts() ) :
    while ( have_posts() ) : the_post();
        // Do nothing here - we don't want the editor content
    endwhile;
endif;
// --- End: Add empty loop ---

$user_id = get_current_user_id();
$today_date_str = date('Y-m-d'); // تاريخ اليوم بصيغة YYYY-MM-DD
$current_year = date('Y');
$current_month = date('m');
$points_balance = 0; // Default value
if (function_exists('smc_get_user_data')) {
    $user_smc_data_att = smc_get_user_data($user_id);
    if (is_array($user_smc_data_att)) {
        $points_balance = $user_smc_data_att['points_balance'] ?? 0;
    } else {
        error_log("SMC Warning: smc_get_user_data returned non-array in activate-daily-attendance.php for user ID: " . $user_id);
        // Fallback: Try getting meta directly
        $points_balance = intval(get_user_meta($user_id, SMC_POINTS_BALANCE, true) ?: 0);
    }
} else {
     error_log("SMC Error: Function smc_get_user_data() not found in activate-daily-attendance.php");
     // Fallback: Try getting meta directly
     $points_balance = intval(get_user_meta($user_id, SMC_POINTS_BALANCE, true) ?: 0);
}


// --- جلب بيانات الحضور لهذا الشهر ---
$attendance_data_this_month = []; // Initialize as empty array
if (function_exists('smc_get_user_attendance_for_month')) {
    $fetched_attendance = smc_get_user_attendance_for_month($user_id, $current_year, $current_month);
    // *** بداية التعديل: التأكد من أن الناتج مصفوفة ***
    if (is_array($fetched_attendance)) {
        $attendance_data_this_month = $fetched_attendance;
    } else {
         error_log("SMC Warning: smc_get_user_attendance_for_month returned non-array. Defaulting to empty array. User ID: " . $user_id);
         // $attendance_data_this_month remains an empty array
    }
    // *** نهاية التعديل ***
} else {
    error_log("SMC Error: Function smc_get_user_attendance_for_month() not found.");
    // يمكنك جلب البيانات هنا مباشرة كحل بديل مؤقت
    global $wpdb;
    $attendance_table = $wpdb->prefix . 'smc_attendance_log';
    $start_of_month = $current_year . '-' . $current_month . '-01';
    $end_of_month = $current_year . '-' . $current_month . '-' . cal_days_in_month(CAL_GREGORIAN, $current_month, $current_year);
    $results = $wpdb->get_results($wpdb->prepare(
        "SELECT attendance_date FROM {$attendance_table} WHERE user_id = %d AND attendance_date BETWEEN %s AND %s",
        $user_id,
        $start_of_month,
        $end_of_month
    ));
    if ($results) {
        foreach ($results as $row) {
            $attendance_data_this_month[] = $row->attendance_date;
        }
    }
    // Ensure it's an array even if no results
    if (!is_array($attendance_data_this_month)) {
        $attendance_data_this_month = [];
    }
}
// --- نهاية جلب البيانات ---

// --- التحقق مما إذا كان المستخدم قد حضر اليوم بالفعل ---
// Now $attendance_data_this_month is guaranteed to be an array
$attended_today = in_array($today_date_str, $attendance_data_this_month); // This line (original line 57) is now safe
$can_attend_today = !$attended_today;
// --- نهاية التحقق ---

// --- إعدادات التقويم ---
$days_in_month = cal_days_in_month(CAL_GREGORIAN, $current_month, $current_year);
$first_day_of_month_timestamp = strtotime("{$current_year}-{$current_month}-01");
$first_day_of_month_day_of_week = date('w', $first_day_of_month_timestamp); // 0 (الأحد) إلى 6 (السبت) - تعديل ليلائم البداية من السبت
$first_day_of_month_day_of_week = ($first_day_of_month_day_of_week + 1) % 7; // 0 (السبت) إلى 6 (الجمعة)

$month_name = date_i18n('F', $first_day_of_month_timestamp); // اسم الشهر الحالي باللغة العربية
$days_of_week_arabic = ['سبت', 'أحد', 'إثنين', 'ثلاثاء', 'أربعاء', 'خميس', 'جمعة'];

?>

<div class="container daily-attendance-container">
    <h2><i class="fas fa-calendar-check"></i> الحضور اليومي</h2>

    <?php // *** قسم الملخص *** ?>
    <div class="data-card attendance-summary-card">
        <div class="card-header">
             <i class="fas fa-star card-icon"></i>
             <h4>ملخص النقاط والحضور</h4>
        </div>
        <div class="card-content">
            <p>رصيد نقاطك الحالي: <strong id="smc-points-balance" class="card-value highlight"><?php echo esc_html($points_balance); ?></strong> نقطة</p>
            <?php if ($can_attend_today): ?>
                <p>لم تقم بتسجيل حضورك لهذا اليوم.</p>
                <button class="smc-button smc-attend-button-calendar" data-day="<?php echo date('j'); ?>">
                    <i class="fas fa-hand-point-up"></i> تسجيل الحضور (10 نقاط)
                </button>
            <?php else: ?>
                <p style="color: green; font-weight: bold;"><i class="fas fa-check-circle"></i> لقد قمت بتسجيل حضورك لهذا اليوم بنجاح!</p>
            <?php endif; ?>
            <div id="smc-attendance-message" style="margin-top: 15px; display: none;"></div>
        </div>
    </div>
    <?php // *** نهاية قسم الملخص *** ?>

    <hr>

    <div class="attendance-calendar-section">
         <h3>تقويم الحضور لشهر <?php echo esc_html($month_name . ' ' . $current_year); ?></h3>
        <table class="attendance-calendar">
            <thead>
                <tr>
                    <?php foreach ($days_of_week_arabic as $day_name): ?>
                        <th><?php echo esc_html($day_name); ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <?php
                    // خلايا فارغة قبل بداية الشهر
                    for ($i = 0; $i < $first_day_of_month_day_of_week; $i++) {
                        echo '<td class="empty-cell"></td>';
                    }

                    $current_day_in_week = $first_day_of_month_day_of_week;
                    // عرض أيام الشهر
                    for ($day = 1; $day <= $days_in_month; $day++) {
                        $current_cell_date_str = $current_year . '-' . $current_month . '-' . str_pad($day, 2, '0', STR_PAD_LEFT);
                        $is_today = ($current_cell_date_str === $today_date_str);
                        $has_attended = in_array($current_cell_date_str, $attendance_data_this_month); // Safe now
                        $is_past = ($current_cell_date_str < $today_date_str);

                        $cell_classes = ['day-cell'];
                        if ($is_today) $cell_classes[] = 'today';
                        if ($has_attended) $cell_classes[] = 'attended';
                        elseif ($is_past && !$has_attended) $cell_classes[] = 'missed'; // تعليم الأيام الماضية التي لم يحضر فيها

                        echo '<td class="' . implode(' ', $cell_classes) . '" id="day-' . $day . '">';
                        echo '<div class="day-number">' . $day . '</div>';
                        echo '<div class="attendance-marker"></div>'; // الدائرة
                        echo '</td>';

                        $current_day_in_week++;
                        if ($current_day_in_week == 7) {
                            echo '</tr>'; // نهاية الصف
                            // Check if it's not the last day before starting a new row
                            if ($day < $days_in_month) {
                                echo '<tr>'; // بداية صف جديد
                            }
                            $current_day_in_week = 0;
                        }
                    }

                    // خلايا فارغة بعد نهاية الشهر
                    while ($current_day_in_week > 0 && $current_day_in_week < 7) {
                        echo '<td class="empty-cell"></td>';
                        $current_day_in_week++;
                    }
                    // Ensure the last row is closed if it wasn't closed by the loop
                    if ($current_day_in_week != 0) {
                         echo '</tr>';
                    }
                    ?>
            </tbody>
        </table>
        <div class="calendar-legend">
            <span class="legend-item"><span class="attendance-marker attended"></span> تم الحضور</span>
            <span class="legend-item"><span class="attendance-marker missed"></span> لم يتم الحضور (أيام ماضية)</span>
            <span class="legend-item"><span class="attendance-marker today-marker"></span> اليوم</span>
        </div>
    </div>

</div>

<?php get_footer(); ?>

<style>
/* --- نسخ تنسيقات data-card من smc-daily-tasks.php --- */
.data-card {
    background-color: #ffffff; /* خلفية بيضاء للحاويات */
    border-radius: 8px; /* حواف دائرية */
    padding: 20px;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.08); /* ظل خفيف */
    transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
    margin-bottom: 20px; /* إضافة هامش سفلي */
}
.data-card:hover {
    transform: translateY(-3px); /* تأثير رفع عند المرور */
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.12);
}
.card-header {
    display: flex;
    align-items: center;
    margin-bottom: 15px;
    color: #495057;
    border-bottom: 1px solid #eee; /* خط فاصل تحت العنوان */
    padding-bottom: 10px;
}
.card-header h4 {
    margin: 0 0 0 10px; /* تعديل الهامش ليكون على يسار الأيقونة */
    font-size: 1.1em;
    font-weight: 600; /* خط أثقل قليلاً */
}
.card-icon {
    font-size: 1.5em; /* حجم الأيقونة */
    color: #fd7e14; /* لون برتقالي للنقاط */
    width: 30px; /* تحديد عرض ثابت للأيقونة */
    text-align: center;
}
.card-content {
    text-align: center; /* توسيط المحتوى */
}
.card-content p {
    margin-bottom: 10px;
    font-size: 1.1em;
}
.card-value.highlight {
    font-weight: bold;
    color: #007bff; /* تمييز قيمة النقاط */
    font-size: 1.4em; /* تكبير حجم الرقم */
    display: inline-block; /* للسماح بالهامش */
    margin: 0 5px;
}
.smc-attend-button-calendar { /* زر الحضور داخل البطاقة */
    padding: 8px 15px;
    font-size: 1em;
    margin-top: 10px;
    background-color: #28a745; /* Green */
    color: white;
    border: 1px solid #28a745;
    border-radius: 5px;
    cursor: pointer;
    transition: background-color 0.3s ease;
}
.smc-attend-button-calendar:hover {
    background-color: #218838;
    border-color: #1e7e34;
}
.smc-attend-button-calendar:disabled {
    background-color: #aaa;
    cursor: not-allowed;
    opacity: 0.7;
}
.smc-attend-button-calendar i {
    margin-left: 5px;
}

/* --- تنسيقات التقويم (تبقى كما هي) --- */
.attendance-calendar-section { margin-top: 20px; }
.attendance-calendar { width: 100%; border-collapse: collapse; table-layout: fixed; }
.attendance-calendar th { background-color: #007bff; color: white; padding: 10px 5px; text-align: center; font-size: 0.9em; }
.attendance-calendar td {
    border: 1px solid #ddd;
    height: 80px; /* ارتفاع الخلية */
    text-align: center;
    vertical-align: top;
    padding: 5px;
    position: relative; /* للسماح بتموضع العناصر الداخلية */
    background-color: #fff;
}
.attendance-calendar td.empty-cell { background-color: #f8f9fa; border: none; }

.day-number {
    font-size: 0.9em;
    color: #666;
    position: absolute;
    top: 5px;
    right: 5px;
}
.attendance-calendar td.today .day-number { font-weight: bold; color: #007bff; }

.attendance-marker {
    display: block;
    width: 15px;
    height: 15px;
    border-radius: 50%;
    background-color: #ccc; /* اللون الافتراضي (مظلم) */
    margin: 10px auto 5px auto; /* توسيط الدائرة */
    position: absolute;
    top: 25px; /* تعديل التموضع */
    left: 0;
    right: 0;
}

.attendance-calendar td.attended .attendance-marker {
    background-color: #28a745; /* أخضر للحضور */
    box-shadow: 0 0 8px rgba(40, 167, 69, 0.7); /* توهج */
}
.attendance-calendar td.missed .attendance-marker {
    background-color: #dc3545; /* أحمر لعدم الحضور في الماضي */
}
.attendance-calendar td.today .attendance-marker {
     border: 2px solid #007bff; /* تحديد اليوم الحالي */
     /* يبقى اللون حسب حالة الحضور */
}
/* إخفاء الدائرة الافتراضية لليوم الحالي إذا لم يحضر بعد */
.attendance-calendar td.today:not(.attended) .attendance-marker {
    background-color: transparent;
    border: 2px dashed #007bff; /* دائرة متقطعة لليوم الحالي غير المسجل */
}

.calendar-legend { margin-top: 15px; text-align: center; font-size: 0.9em; }
.legend-item { margin: 0 10px; display: inline-flex; align-items: center; }
.legend-item .attendance-marker {
    display: inline-block; /* تغيير ليكون بجانب النص */
    position: static; /* إزالة التموضع المطلق */
    margin: 0 5px 0 0; /* هامش يمين */
    vertical-align: middle;
}
.legend-item .today-marker {
    background-color: transparent;
    border: 2px solid #007bff;
}

/* --- تنسيقات رسائل الحالة --- */
#smc-attendance-message.smc-error-message { color: #dc3545; background-color: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; border-radius: 5px; }
#smc-attendance-message.smc-success-message { color: #28a745; background-color: #d4edda; border: 1px solid #c3e6cb; padding: 10px; border-radius: 5px; }

/* تأكد من تحميل Font Awesome */
@import url("https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css");
</style>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> <?php // Use Swal from CDN ?>
<script type="text/javascript">
jQuery(document).ready(function($) {
    // --- معالجة زر الحضور داخل البطاقة ---
    $('.smc-attend-button-calendar').on('click', function() { // استخدام نفس الفئة للزر
        var button = $(this);
        var cardContent = button.closest('.card-content');
        var messageDiv = $('#smc-attendance-message');

        button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> جارٍ التسجيل...'); // تغيير الأيقونة والنص
        messageDiv.text('').removeClass('smc-error-message smc-success-message').hide();

        $.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: {
                action: 'smc_handle_daily_attendance',
                nonce: '<?php echo wp_create_nonce('smc_attendance_nonce'); ?>'
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Use SweetAlert for success
                    Swal.fire({
                        icon: 'success',
                        title: 'تم!',
                        text: response.data.message,
                        timer: 2500,
                        showConfirmButton: false
                    });

                    // تحديث واجهة البطاقة
                    button.remove(); // إزالة الزر بعد النجاح
                    cardContent.find('p:contains("لم تقم بتسجيل حضورك")').replaceWith('<p style="color: green; font-weight: bold;"><i class="fas fa-check-circle"></i> لقد قمت بتسجيل حضورك لهذا اليوم بنجاح!</p>');

                    // تحديث رصيد النقاط المعروض
                    $('#smc-points-balance').text(response.data.new_points_balance);

                    // تحديث التقويم (تعليم اليوم الحالي كـ attended)
                    var todayCell = $('.attendance-calendar td.today');
                    if(todayCell.length > 0) {
                        todayCell.addClass('attended');
                        todayCell.find('.attendance-marker')
                                 .css('background-color', '#28a745')
                                 .css('box-shadow', '0 0 8px rgba(40, 167, 69, 0.7)')
                                 .css('border', 'none'); // Remove dashed border
                    }

                } else {
                    // Use SweetAlert for error
                    Swal.fire({
                        icon: 'error',
                        title: 'خطأ!',
                        text: response.data.message || 'حدث خطأ غير متوقع.'
                    });
                    button.prop('disabled', false).html('<i class="fas fa-hand-point-up"></i> تسجيل الحضور (10 نقاط)'); // إعادة تفعيل الزر
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                 // Use SweetAlert for AJAX error
                 Swal.fire({
                     icon: 'error',
                     title: 'خطأ اتصال!',
                     text: 'حدث خطأ في الاتصال بالخادم. يرجى المحاولة مرة أخرى.'
                 });
                console.error("AJAX Error:", textStatus, errorThrown, jqXHR.responseText);
                button.prop('disabled', false).html('<i class="fas fa-hand-point-up"></i> تسجيل الحضور (10 نقاط)');
            }
            // No need for complete block if using Swal
        });
    });

});
</script>

