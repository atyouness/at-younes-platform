<?php
/**
 * Template Name: Ad Deal Settings (Admin)
 * Description: Displays the Ad Deal Settings for administrators.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Redirect if not logged in or not an admin
if (!is_user_logged_in() || !current_user_can('administrator')) {
    wp_redirect(home_url('/'));
    exit;
}

// *** تضمين الملفات الإدارية اللازمة ***
if (is_admin() || current_user_can('manage_options')) {
    require_once(ABSPATH . 'wp-admin/includes/template.php'); // لـ settings_errors()
    require_once(ABSPATH . 'wp-admin/includes/plugin.php');   // لـ do_settings_sections()
}

get_header();
?>

<div class="wrap smc-settings-page"> <?php // استخدام فئة wrap القياسية لووردبريس ?>
    <h1><i class="fas fa-sliders-h"></i> إعدادات الصفقة الإعلانية</h1>

    <?php
    // عرض رسائل الخطأ أو النجاح
    if (function_exists('settings_errors')) {
        settings_errors();
    }
    ?>

    <?php // *** تعديل هنا: استخدام admin_url لـ action *** ?>
    <form method="post" action="<?php echo esc_url(admin_url('options.php')); ?>">
        <?php
        // حقول الأمان والربط بمجموعة الإعدادات
        if (function_exists('settings_fields')) {
             settings_fields('smc_ad_deal_options_group');
        } else {
             echo '<p class="smc-error-message">خطأ: دالة settings_fields غير موجودة.</p>';
        }


        // عرض الأقسام والحقول المسجلة
        if (function_exists('do_settings_sections')) {
             do_settings_sections('ad-deal-settings'); // اسم الصفحة (slug)
        } else {
             echo '<p class="smc-error-message">خطأ: دالة do_settings_sections غير موجودة.</p>';
        }


        // زر الحفظ
        if (function_exists('submit_button')) {
             submit_button('حفظ الإعدادات');
        }
        ?>
    </form>
     <p><a href="<?php echo esc_url(home_url('/smc-settings/')); ?>" class="button button-secondary"><i class="fas fa-cog"></i> العودة إلى إعدادات SMC الرئيسية</a></p>
</div>

<?php get_footer(); ?>

<style>
/* ... (CSS styles remain the same) ... */
.smc-error-message {
    color: #dc3545;
    background-color: #f8d7da;
    border: 1px solid #f5c6cb;
    padding: 10px;
    border-radius: 5px;
    margin-bottom: 15px;
}
/* تأكد من تحميل Font Awesome إذا لم يتم تحميله بشكل عام */
@import url("https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css");
</style>
