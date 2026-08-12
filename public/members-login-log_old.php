<?php
/**
 * Template Name: Members Login Log
 * Description: Displays the referral log for users.
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

        <h2>📜 سجل حركة الدخول</h2>

        <!-- منطقة لأدوات البحث والفرز والتصفية والتصدير (سيتم إضافتها لاحقًا) -->
        <div class="smc-log-controls" style="margin-bottom: 20px; padding: 15px; background-color: #f9f9f9; border: 1px solid #eee; border-radius: 5px;">
            <p><strong>أدوات التحكم بالسجل:</strong></p>
            <!-- عناصر التحكم ستوضع هنا (مثل حقل البحث، قوائم منسدلة للتصفية، أزرار الفرز والتصدير) -->
            <p style="color: #888;">(سيتم تفعيل وظائف البحث، الفرز، التصفية، والتصدير في تحديث قادم)</p>
        </div>

        <section class="smc-admin-section">
      <table class="smc-log-table">
        <thead>
            <tr>
                <th>اسم المستخدم</th>
                <th>معرف المستخدم</th>
                <th>وقت الدخول</th>
                <th>عنوان IP</th>
                <th>وكيل المستخدم (المتصفح/النظام)</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // هنا سيتم إضافة الكود الخاص بجلب وعرض بيانات سجل حركة الدخول
            // مثال لصف بيانات (يجب استبداله ببيانات حقيقية)
            /*
            <tr>
                <td>اسم مستخدم مثال</td>
                <td>123</td>
                <td>2025-04-09 21:30:00</td>
                <td>192.168.1.1</td>
                <td>Mozilla/5.0 (...)</td>
            </tr>
            */
            echo "<tr><td colspan='5'>لا توجد بيانات تسجيل دخول حالية لعرضها.</td></tr>";
            ?>
        </tbody>
    </table>
    <hr> <!-- فاصل بعد أزرار السجلات -->
             <!-- قسم أزرار السجلات - تم نقله للأعلى -->
             <section class="smc-admin-section smc-log-buttons-section" style="border-top: none; margin-top: 10px; padding-top: 0;">
                <!-- <h3>روابط سجلات SMC</h3> --> <!-- يمكن إزالة العنوان إذا كانت الأزرار واضحة -->
                <p>انقر للعودة إلى صفحة ⚙️ إعدادات SMC:</p>
                <div class="smc-log-buttons">
                <a href="/smc-settings/" class="smc-button"><h3>⚙️ إعدادات SMC</h3></a>
                </div>
            </section>
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
