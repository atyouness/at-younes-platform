<?php
/**
 * Template Name: php Error Log
 * Description: Displays the php error log.
 */

// التأكد من أن WordPress هو من يقوم بتحميل هذا الملف
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// تضمين ملف header.php
get_header();
?>

<div class="container">
    <?php
    // التحقق من تسجيل دخول المستخدم وصلاحيات المسؤول
    if (is_user_logged_in() && current_user_can('administrator')) {
    ?>

        <h2>📜 سجل الأخطاء</h2>
        <p>انقر للعودة إلى صفحة ⚙️ إعدادات SMC:</p>
             <div class="smc-log-buttons">
                <a href="<?php echo esc_url(home_url('/smc-settings/')); ?>" class="smc-button"><h3>⚙️ إعدادات SMC</h3></a>
             </div>
        <!-- منطقة لأدوات البحث والفرز والتصفية والتصدير (سيتم إضافتها لاحقًا) -->
        <div class="smc-log-controls" style="margin-bottom: 20px; padding: 15px; background-color: #f9f9f9; border: 1px solid #eee; border-radius: 5px;">
            <p><strong>أدوات التحكم بالسجل:</strong></p>
            <!-- عناصر التحكم ستوضع هنا (مثل حقل البحث، قوائم منسدلة للتصفية، أزرار الفرز والتصدير) -->
            <p style="color: #888;">(سجل الأخطاء في قسم إعدادات SMC مؤقتا من أجل تتبع أخطاء البرمجة)</p>
        </div>
<?php phpinfo(); ?>

            <hr> <!-- فاصل بعد أزرار السجلات -->
            <!-- منطقة لترقيم الصفحات (Pagination) إذا لزم الأمر -->
        </section>

    <?php
    } else {
        // إذا لم يكن المستخدم مسجلاً دخوله أو ليس مسؤولاً
        echo '<p>ليس لديك الصلاحيات الكافية لعرض هذه الصفحة. يرجى تسجيل الدخول كمسؤول.</p>';
    }
    ?>
</div>

<?php
// تضمين ملف footer.php
get_footer();
?>
