<?php
/**
 * Template Name: Check Referrals 1
 * Description: Displays the check referrals 1.
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

        <h2>📜 عدد الإحالات</h2>

        <!-- منطقة لأدوات البحث والفرز والتصفية والتصدير (سيتم إضافتها لاحقًا) -->
        <div class="smc-log-controls" style="margin-bottom: 20px; padding: 15px; background-color: #f9f9f9; border: 1px solid #eee; border-radius: 5px;">
            <p><strong>أدوات التحكم بالسجل:</strong></p>
            <!-- عناصر التحكم ستوضع هنا (مثل حقل البحث، قوائم منسدلة للتصفية، أزرار الفرز والتصدير) -->
            <p style="color: #888;">(سيتم تفعيل وظائف البحث، الفرز، التصفية، والتصدير في تحديث قادم)</p>
        </div>

        <?php
require_once( 'wp-load.php' );

global $wpdb;
$meta_key_referred_by = defined('SMC_REFERRED_BY') ? SMC_REFERRED_BY : 'smc_referred_by';

$args = array(
    'meta_key'   => $meta_key_referred_by,
    'meta_compare' => 'EXISTS',
    'count_total' => true,
);

$user_count = count( get_users( $args ) );

echo "عدد المستخدمين المحالين: " . $user_count;
?>

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
