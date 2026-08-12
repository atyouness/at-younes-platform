<?php
/**
 * Template Name: Tasks Info Page
 * Description: Displays information about tasks, deposit levels, and provides action buttons.
 */

// التأكد من أن WordPress هو من يقوم بتحميل هذا الملف
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// التحقق من تسجيل دخول المستخدم
if (!is_user_logged_in()) {
    wp_redirect(home_url('/'));
    exit;
}

// تضمين ملف header.php
get_header();

// --- جلب بيانات المستخدم لحساب الإعلانات المتبقية ---
$user_id = get_current_user_id();
$ads_watched = 0; // Default
$ad_limit = 0;    // Default
$remaining_ads = 0; // Default
$current_deposit_tasks = 0; // Default

// التأكد من وجود الدالة قبل استدعائها (احتياطي)
if (function_exists('smc_get_user_data')) {
    $user_smc_data = smc_get_user_data($user_id);

    // *** بداية التعديل: التحقق من أن البيانات مصفوفة ***
    if (is_array($user_smc_data)) {
        $ads_watched = $user_smc_data['ads_watched_today'] ?? 0; // Use default if key missing
        $ad_limit = $user_smc_data['daily_ad_limit'] ?? 0;    // Use default if key missing
        $current_deposit_tasks = $user_smc_data['current_deposit'] ?? 0; // Get deposit too
        $remaining_ads = max(0, $ad_limit - $ads_watched); // حساب الإعلانات المتبقية
    } else {
        // Log error if data is not an array
        error_log("SMC Warning: smc_get_user_data returned non-array in template-tasks-info.php for user ID: " . $user_id);
        // Keep default values (0)
        // Fallback: Try getting meta directly for critical values
        $ads_watched = intval(get_user_meta($user_id, SMC_ADS_WATCHED_TODAY, true) ?: 0);
        $ad_limit = intval(get_user_meta($user_id, SMC_DAILY_AD_LIMIT, true) ?: 0);
        $current_deposit_tasks = floatval(get_user_meta($user_id, SMC_DEPOSIT_BALANCE, true) ?: 0);
        $remaining_ads = max(0, $ad_limit - $ads_watched);
    }
    // *** نهاية التعديل ***
} else {
    // قيمة افتراضية أو رسالة خطأ إذا لم تكن الدالة موجودة
    error_log("SMC Error: Function smc_get_user_data() not found in template-tasks-info.php");
    // Fallback: Try getting meta directly
    $ads_watched = intval(get_user_meta($user_id, SMC_ADS_WATCHED_TODAY, true) ?: 0);
    $ad_limit = intval(get_user_meta($user_id, SMC_DAILY_AD_LIMIT, true) ?: 0);
    $current_deposit_tasks = floatval(get_user_meta($user_id, SMC_DEPOSIT_BALANCE, true) ?: 0);
    $remaining_ads = max(0, $ad_limit - $ads_watched);
}
// --- نهاية جلب بيانات المستخدم ---

?>

<div class="container tasks-info-page-container">
    <h2><i class="fas fa-info-circle"></i> معلومات المهام اليومية</h2>

    <section class="tasks-intro-section">
        <h4><i class="fas fa-bullhorn"></i> مرحبًا بك في منصة SMC للمهام اليومية!</h4>
        <ul>
            <li><strong><i class="fas fa-coins"></i> الإيداع:</strong> يمكنك إيداع مبلغ يتراوح ما بين 2300 دج و 500000 دج لبدء أداء المهام اليومية.</li>
            <li><strong><i class="fas fa-tasks"></i> المهام:</strong> مهمتك اليومية هي مشاهدة عدد محدد من الإعلانات بناءً على مستوى وديعتك.</li>
            <li><strong><i class="fas fa-chart-line"></i> الأرباح:</strong> ستحصل على نسبة ربح معينة عن كل إعلان تشاهده، تضاف إلى رصيد أرباحك.</li>
            <li><strong><i class="fas fa-hand-holding-usd"></i> سحب الأرباح:</strong> يمكنك سحب أرباحك عندما تصل إلى 600 دج على الأقل.</li>
            <li><strong><i class="fas fa-undo-alt"></i> سحب الوديعة:</strong> يمكنك سحب وديعتك الأصلية بعد مرور 90 يومًا على تاريخ آخر إيداع قمت به.</li>
            <li><strong><i class="fas fa-users"></i> فريقي:</strong> يمكنك دعوة أصدقائك للانضمام باستخدام رابط الدعوة الخاص بك والحصول على مكافآت.</li>
        </ul>
        <p class="tasks-alert"><i class="fas fa-exclamation-triangle"></i> <strong>تنبيه:</strong> لا يمكنك إيداع رصيد جديد إلا بعد 24 ساعة من أي مصدر كان.</p>
        <p class="tasks-note"><i class="fas fa-sticky-note"></i> <strong>ملاحظة:</strong> كل الأسعار تشمل القيمة المضافة 19%.</p>
    </section>

    <hr>

    <section class="tasks-details-section">
        <h4><i class="fas fa-list-ol"></i> تفاصيل مستويات الإيداع والمهام:</h4>
        <p>كل يوم لديك الحق في مشاهدة عدد معين من الإعلانات، حسب قيمة وديعتك، كما يلي:</p>

        <div class="deposit-levels-table-container">
            <table class="deposit-levels-table">
                <thead>
                    <tr>
                        <th>مستوى الإيداع (دج)</th>
                        <th>سعر الإعلان (دج)</th>
                        <th>نسبة الربح (%)</th>
                        <th>عدد الإعلانات اليومية</th>
                    </tr>
                </thead>
                <tbody>
                     <tr><td>2,000 - 4,999</td><td>1 - 2</td><td>0.134 - 0.2</td><td>10</td></tr>
                     <tr><td>5,000 - 9,999</td><td>2.5 - 5</td><td>0.144 - 0.21</td><td>11</td></tr>
                     <tr><td>10,000 - 24,999</td><td>6 - 12</td><td>0.154 - 0.22</td><td>12</td></tr>
                     <tr><td>25,000 - 49,999</td><td>18 - 36</td><td>0.164 - 0.23</td><td>13</td></tr>
                     <tr><td>50,000 - 99,999</td><td>40 - 80</td><td>0.174 - 0.24</td><td>14</td></tr>
                     <tr><td>100,000 - 249,999</td><td>90 - 180</td><td>0.184 - 0.25</td><td>15</td></tr>
                     <tr><td>250,000 - 499,999</td><td>240 - 450</td><td>0.194 - 0.26</td><td>16</td></tr>
                     <tr><td>500,000+</td><td>470 - 700</td><td>0.204 - 0.27</td><td>17</td></tr>
                </tbody>
            </table>
        </div>
    </section>

    <hr>

    <section class="tasks-action-buttons">
        <p>
            <span class="remaining-ads-info" style="display: block; margin-bottom: 10px;">
                <i class="fas fa-hourglass-half"></i> لديك <strong><?php echo esc_html($remaining_ads); ?></strong> إعلانات متبقية لهذا اليوم.
                (شاهدت <?php echo esc_html($ads_watched); ?> من <?php echo esc_html($ad_limit); ?>) <?php // الآن يجب أن تعرض القيم الصحيحة ?>
            </span>
            <?php // عرض رسالة البدء فقط إذا كان مؤهلاً ?>
            <?php if ($current_deposit_tasks >= 2000 && $remaining_ads > 0): ?>
                هل أنت مستعد لبدء مهامك اليومية؟
            <?php endif; ?>
        </p>
        <?php // *** تعديل الشرط: التأكد من وجود وديعة كافية *و* إعلانات متبقية *** ?>
        <?php if ($current_deposit_tasks >= 2000 && $remaining_ads > 0) : ?>
            <a href="<?php echo esc_url(home_url('/advertising-deal/')); ?>" class="smc-button start-tasks-button">
                <i class="fas fa-rocket"></i> ابدأ المهام
            </a>
        <?php elseif ($current_deposit_tasks < 2000): ?>
             <p style="color: #dc3545; font-weight: bold;"><i class="fas fa-exclamation-triangle"></i> يجب إيداع 2000 دج على الأقل لبدء المهام.</p>
        <?php else: // تم إكمال المهام ?>
             <p style="color: green; font-weight: bold;"><i class="fas fa-check-double"></i> لقد أكملت جميع مهامك لهذا اليوم!</p>
        <?php endif; ?>
        <a href="<?php echo esc_url(home_url('/smc-daily-tasks/')); ?>" class="smc-button smc-button-secondary cancel-tasks-button">
            <i class="fas fa-times"></i> إلغاء (العودة للرئيسية)
        </a>
    </section>

</div>

<?php // إضافة CSS الخاص بالصفحة ?>
<style>
.tasks-info-page-container { max-width: 900px; margin: 20px auto; padding: 20px; background: #fff; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
.tasks-info-page-container h2, .tasks-intro-section h4, .tasks-details-section h4 { display: flex; align-items: center; color: #343a40; margin-bottom: 15px; }
.tasks-info-page-container h2 i, .tasks-intro-section h4 i, .tasks-details-section h4 i { margin-left: 10px; color: #007bff; }
.tasks-intro-section ul { list-style: none; padding-right: 0; margin-bottom: 15px; }
.tasks-intro-section ul li { margin-bottom: 10px; line-height: 1.6; }
.tasks-intro-section ul li i { color: #17a2b8; margin-left: 8px; width: 20px; text-align: center; }
.tasks-alert { color: #856404; background-color: #fff3cd; border: 1px solid #ffeeba; padding: 10px; border-radius: 5px; margin-top: 15px; }
.tasks-note { color: #0c5460; background-color: #d1ecf1; border: 1px solid #bee5eb; padding: 10px; border-radius: 5px; margin-top: 10px; }
.tasks-alert i, .tasks-note i { margin-left: 5px; }
.deposit-levels-table-container { overflow-x: auto; margin-top: 15px; }
.deposit-levels-table { width: 100%; border-collapse: collapse; text-align: center; }
.deposit-levels-table th, .deposit-levels-table td { border: 1px solid #dee2e6; padding: 10px 8px; font-size: 0.95em; }
.deposit-levels-table thead th { background-color: #e9ecef; color: #495057; font-weight: 600; }
.deposit-levels-table tbody tr:nth-child(even) { background-color: #f8f9fa; }
.remaining-ads-info {
    font-size: 1.1em;
    color: #007bff; /* لون أزرق مميز */
    background-color: #e7f3ff;
    padding: 10px 15px;
    border-radius: 5px;
    border: 1px solid #b8daff;
    margin-bottom: 15px;
    display: inline-block; /* لجعل الخلفية تلتف حول النص */
}
.remaining-ads-info i { margin-left: 5px; }
.tasks-action-buttons { text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; }
.tasks-action-buttons p { margin-bottom: 15px; font-size: 1.1em; color: #333; }
.tasks-action-buttons .smc-button { margin: 5px 10px; padding: 12px 25px; font-size: 1.1em; border-radius: 25px; transition: all 0.3s ease; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; }
.start-tasks-button { background-color: #28a745; border-color: #28a745; color: white; }
.start-tasks-button:hover { background-color: #218838; border-color: #1e7e34; transform: translateY(-2px); box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
.cancel-tasks-button { background-color: #6c757d; border-color: #6c757d; color: white; }
.cancel-tasks-button:hover { background-color: #5a6268; border-color: #545b62; transform: translateY(-2px); box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
.tasks-action-buttons .smc-button i { margin-left: 8px; }
@import url("https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css");
</style>

<?php
// تضمين ملف footer.php
get_footer();
?>
