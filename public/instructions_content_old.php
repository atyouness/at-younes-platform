<?php
/**
 * Template Name: Instructions Page
 * Description: Displays instructions and guidelines for the platform.
 */

if (!defined('ABSPATH')) exit; // Exit if accessed directly

// لا حاجة للتحقق من تسجيل الدخول هنا، قد تكون الإرشادات عامة
// if (!is_user_logged_in()) { ... }

get_header();
?>

<div class="container instructions-page-container">
    <h2><i class="fas fa-book-open"></i> الإرشادات والإرشادات</h2>

    <section class="instructions-section">
        <h4><i class="fas fa-bullhorn"></i> مرحبًا بك في منصة SMC للمهام اليومية!</h4>
        <ul>
            <li><strong><i class="fas fa-coins"></i> الإيداع:</strong> يمكنك إيداع مبلغ يتراوح ما بين 2300 دج و 500000 دج لبدء أداء المهام اليومية.</li>
            <li><strong><i class="fas fa-tasks"></i> المهام:</strong> مهمتك اليومية هي مشاهدة عدد محدد من الإعلانات بناءً على مستوى وديعتك.</li>
            <li><strong><i class="fas fa-chart-line"></i> الأرباح:</strong> ستحصل على نسبة ربح معينة عن كل إعلان تشاهده، تضاف إلى رصيد أرباحك.</li>
            <li><strong><i class="fas fa-hand-holding-usd"></i> سحب الأرباح:</strong> يمكنك سحب أرباحك عندما تصل إلى 600 دج على الأقل.</li>
            <li><strong><i class="fas fa-undo-alt"></i> سحب الوديعة:</strong> يمكنك سحب وديعتك الأصلية بعد مرور 90 يومًا على تاريخ آخر إيداع قمت به.</li>
            <li><strong><i class="fas fa-users"></i> فريقي:</strong> يمكنك دعوة أصدقائك للانضمام باستخدام رابط الدعوة الخاص بك والحصول على مكافآت.</li>
            <li><strong><i class="fas fa-calendar-check"></i> الحضور اليومي:</strong> سجل حضورك يوميًا للحصول على نقاط إضافية.</li>
        </ul>
        <p class="tasks-alert"><i class="fas fa-exclamation-triangle"></i> <strong>تنبيه:</strong> لا يمكنك إيداع رصيد جديد إلا بعد 24 ساعة من أي مصدر كان.</p>
        <p class="tasks-note"><i class="fas fa-sticky-note"></i> <strong>ملاحظة:</strong> كل الأسعار تشمل القيمة المضافة 19%.</p>
        <p>تواصل مع الدعم الفني إذا كانت لديك أي أسئلة أخرى.</p>
    </section>

    <?php
    // يمكنك إضافة المزيد من الأقسام أو المحتوى هنا حسب الحاجة
    ?>

</div>

<?php get_footer(); ?>

<style>
.instructions-page-container { max-width: 800px; margin: 20px auto; padding: 20px; background: #fff; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
.instructions-section h4 { margin-top: 0; color: #007bff; border-bottom: 1px solid #eee; padding-bottom: 5px; }
.instructions-section ul { list-style: none; padding-right: 0; /* إزالة المسافة البادئة الافتراضية */ }
.instructions-section ul li { margin-bottom: 12px; line-height: 1.6; }
.instructions-section ul li i { color: #17a2b8; /* لون مختلف للأيقونات */ margin-left: 8px; width: 20px; /* محاذاة الأيقونات */ text-align: center; }
.tasks-alert { color: #856404; background-color: #fff3cd; border: 1px solid #ffeeba; padding: 10px; border-radius: 5px; margin-top: 15px; }
.tasks-note { color: #0c5460; background-color: #d1ecf1; border: 1px solid #bee5eb; padding: 10px; border-radius: 5px; margin-top: 10px; }
.tasks-alert i, .tasks-note i { margin-left: 5px; }
@import url("https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css");
</style>
