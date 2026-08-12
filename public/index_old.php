<?php
/*
 * Template Name: SMC Project
 * Description: صفحة مشروع SMC
 */

// التأكد من أن WordPress هو من يقوم بتحميل هذا الملف
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// التحقق من تسجيل دخول المستخدم
if (is_user_logged_in()) {
    // إعادة توجيه المستخدم إلى صفحة المهام اليومية
    wp_redirect(home_url('/smc-daily-tasks/'));
    exit;
} else {
    // عرض نموذج تسجيل الدخول
    get_header();
    echo '<div class="container">';
    echo '<section class="login-section" id="login-section">';
    echo '<h2>نظام المصادقة</h2>';
    echo do_shortcode('[ultimatemember form_id="217"]'); // عرض نموذج تسجيل الدخول من Ultimate Member
    echo '</section>';
    echo '</div>';
    get_footer();

    // تضمين deposit-handler.php إذا تم إرسال النموذج
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['deposit_form'])) {
        include 'deposit-handler.php';
    }
}
?>
