<?php
/**
 * Template Name: Transactional
 * Description: This is a custom template for the Transactional page.
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
    // التحقق من تسجيل دخول المستخدم
    if (is_user_logged_in()) {
        // الحصول على معلومات المستخدم الحالي
        $current_user = wp_get_current_user();
        $user_id = $current_user->ID;
        ?>

        <h2>⚙️ معاملاتي</h2>

    
            <!-- *** بداية التعديل: قسم أزرار السجلات المعاد تصميمه *** -->
            <section class="smc-admin-section smc-log-cards-section">
                <h4><i class="fas fa-history"></i> الوصول السريع للسجلات</h4>
                <p>انقر على البطاقة أدناه لعرض السجل التفصيلي:</p>
                <div class="smc-log-cards-grid"> <?php // استخدام grid لعرض البطاقات ?>

                    <?php // 1. سجل الإيداع ?>
                    <div class="smc-log-card">
                        <a href="/user-deposit-log/">
                            <i class="fas fa-money-check-alt card-icon deposit-icon"></i>
                            <span>سجل الإيداع</span>
                        </a>
                    </div>
                    <?php // 2. سجل المكافآت ?>
                    <div class="smc-log-card">
                        <a href="/user-rewards-log/">
                            <i class="fas fa-trophy card-icon rewards-icon"></i>
                            <span>سجل المكافآت</span>
                        </a>
                    </div>
                    <?php // 3. سجل صفقات الإعلانية ?>
                    <div class="smc-log-card">
                        <a href="/user-advertising-deals-record/">
                            <i class="fas fa-chart-line card-icon daily-earn-icon"></i>
                            <span>سجل صفقات الإعلانية</span>
                        </a>
                    </div>
                    <?php // 4. سجل أرباح الاستثمار ?>
                    <div class="smc-log-card">
                        <a href="/user-investment-profits-log/">
                            <i class="fas fa-chart-pie card-icon investment-profits-icon"></i> <?php // تغيير الأيقونة والفئة ?>
                            <span>سجل أرباح الاستثمار</span>
                        </a>
                    </div>
                    <?php // 5. إدارة سحب الاستثمار ?>
                    <div class="smc-log-card">
                        <a href="<?php echo esc_url(home_url('/template-scheduled-investment-withdrawals/')); ?>">
                            <i class="fas fa-calendar-alt card-icon scheduled-withdrawals-icon"></i>
                            <span>إدارة سحب الاستثمار</span>
                        </a>
                    </div>
                    <?php // 6. سجل الحضور ?>
                    <div class="smc-log-card">
                        <a href="/user-attendance-log/"> <?php // تم تغيير الرابط إلى سجل المسؤول ?>
                            <i class="fas fa-calendar-check card-icon attendance-icon"></i>
                            <span>سجل الحضور</span>
                        </a>
                    </div>
                    <?php // 7. سجل سحب الودائع ?>
                    <div class="smc-log-card">
                        <a href="/user-deposit-withdrawal-log/">
                            <i class="fas fa-undo-alt card-icon deposit-withdraw-icon"></i>
                            <span>سجل سحب الودائع</span>
                        </a>
                    </div>
                    <?php // 8. سجل سحب الأرباح ?>
                    <div class="smc-log-card">
                        <a href="/user-profit-withdrawal-log/">
                            <i class="fas fa-hand-holding-usd card-icon profit-withdraw-icon"></i>
                            <span>سجل سحب الأرباح</span>
                        </a>
                    </div>
                     <?php // 9. رابط الدعوة ?>
                    <div class="smc-log-card">
                        <a href="/invitation-link/">
                            <i class="fas fa-user-friends card-icon referral-icon"></i>
                            <span>رابط الدعوة </span>
                        </a>
                    </div>
                    <?php // 10. شجرة الإحالات ?>
                    <div class="smc-log-card">
                        <a href="/user-referral-tree/">
                            <i class="fas fa-sitemap card-icon tree-icon"></i>
                            <span>شجرة الإحالات</span>
                        </a>
                    </div>
                    <?php // 11. عرض شجرة الآبلاينات ?>
                    <div class="smc-log-card">
                        <a href="/view-the-uplines-tree/">
                            <i class="fas fa-network-wired card-icon view-uplines-tree-icon"></i> <?php // تغيير الأيقونة والفئة ?>
                            <span>عرض شجرة الآبلاينات </span>
                        </a>
                    </div>                    
                </div>
            </section>
            <!-- *** نهاية التعديل *** -->

         <?php
    } else {
        // إذا لم يكن المستخدم مسجلاً دخوله، يمكنك عرض رسالة أو إعادة توجيهه
        echo '<p>يرجى تسجيل الدخول لعرض هذه الصفحة.</p>';
        // أو إعادة التوجيه إلى صفحة تسجيل الدخول
        // wp_redirect(wp_login_url());
        // exit;
    }
    ?>
</div>

<?php
// تضمين ملف footer.php
get_footer();
?>

<?php // --- إضافة CSS للتنسيقات الجديدة --- ?>
<style>
/* تأكد من تحميل Font Awesome */
@import url("https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css");

.smc-settings-container h2, .smc-admin-section h3, .smc-log-cards-section h4 {
    display: flex;
    align-items: center;
    color: #343a40;
    margin-bottom: 15px;
}
.smc-settings-container h2 i, .smc-admin-section h3 i, .smc-log-cards-section h4 i {
    margin-left: 10px; /* أيقونة على يسار النص */
    color: #007bff;
}
.smc-log-cards-section h4 i { color: #6f42c1; } /* لون مختلف لأيقونة السجلات */
.smc-admin-section h3 i { color: #17a2b8; } /* لون مختلف لأيقونة الطلبات */

.smc-log-cards-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); /* أعمدة متجاوبة */
    gap: 15px; /* مسافة بين البطاقات */
    margin-top: 10px;
}

.smc-log-card {
    background-color: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.08);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    border-left: 4px solid #007bff; /* شريط لوني على اليسار */
}
.smc-log-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.12);
}

.smc-log-card a {
    display: flex;
    flex-direction: column; /* أيقونة فوق النص */
    align-items: center;
    justify-content: center;
    padding: 20px 15px;
    text-decoration: none;
    color: #343a40;
    text-align: center;
    min-height: 120px; /* ارتفاع أدنى للبطاقة */
}

.smc-log-card .card-icon {
    font-size: 2.2em; /* حجم أكبر للأيقونة */
    margin-bottom: 10px;
    /* ألوان مختلفة لكل أيقونة */
}
.smc-log-card .deposit-icon { color: #17a2b8; }
.smc-log-card .rewards-icon { color: #ffc107; }
.smc-log-card .daily-earn-icon { color: #fd7e14; }
.smc-log-card .investment-profits-icon { color: #28a745; }
.smc-log-card .scheduled-withdrawals-icon { color: #4682B4; } /* لون جديد لـ scheduled withdrawals */
.smc-log-card .attendance-icon { color: #20c997; }
.smc-log-card .deposit-withdraw-icon { color: #e83e8c; }
.smc-log-card .profit-withdraw-icon { color: #28a745; }
.smc-log-card .referral-icon { color: #007bff; }
.smc-log-card .tree-icon { color: #6610f2; }
.smc-log-card .view-uplines-tree-icon { color: #6f42c1; }
.smc-log-card span {
    font-weight: 600;
    font-size: 0.95em;
}

/* تخصيص ألوان الشريط الأيسر للبطاقات */
.smc-log-card:nth-child(11n+1) { border-left-color: #17a2b8; }  /* 1. سجل الإيداع */
.smc-log-card:nth-child(11n+2) { border-left-color: #ffc107; }  /* 2. سجل المكافآت */
.smc-log-card:nth-child(11n+3) { border-left-color: #fd7e14; }  /* 3. سجل صفقات الإعلانية */
.smc-log-card:nth-child(11n+4) { border-left-color: #28a745; }  /* 4. سجل أرباح الاستثمار */
.smc-log-card:nth-child(11n+5) { border-left-color: #4682B4; }  /* 5. إدارة سحب الاستثمار */
.smc-log-card:nth-child(11n+6) { border-left-color: #20c997; }  /* 6. سجل الحضور */
.smc-log-card:nth-child(11n+7) { border-left-color: #e83e8c; }  /* 7. سجل سحب الودائع */
.smc-log-card:nth-child(11n+8) { border-left-color: #28a745; }  /* 8. سجل سحب الأرباح */
.smc-log-card:nth-child(11n+9) { border-left-color: #007bff; }  /* 9. رابط الدعوة */
.smc-log-card:nth-child(11n+10) { border-left-color: #6610f2; } /* 10. شجرة الإحالات */
.smc-log-card:nth-child(11n+11) { border-left-color: #6f42c1; } /* 11. عرض شجرة الآبلاينات */

/* تنسيق جداول الموافقة */
.smc-approval-table { margin-top: 15px; }
.smc-approval-table th, .smc-approval-table td {
    vertical-align: middle; /* محاذاة رأسية للمحتوى */
}
.smc-approval-table .action-cell {
    text-align: center;
    white-space: nowrap; /* منع التفاف الأزرار */
}
.smc-approval-table .smc-button {
    padding: 5px 10px;
    font-size: 0.9em;
    margin: 2px;
}
.smc-approval-table .smc-button i {
    margin-left: 4px;
}
.approve-button {
    background-color: #28a745;
    border-color: #28a745;
    color: white;
}
.approve-button:hover {
    background-color: #218838;
    border-color: #1e7e34;
}
.reject-button {
    background-color: #dc3545;
    border-color: #dc3545;
    color: white;
}
.reject-button:hover {
    background-color: #c82333;
    border-color: #bd2130;
}
.smc-button:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}
.action-status {
    font-weight: bold;
    padding: 5px;
    border-radius: 4px;
}
.action-status.approved { color: #155724; background-color: #d4edda; }
.action-status.rejected { color: #721c24; background-color: #f8d7da; }

</style>

<?php // --- إضافة JavaScript لمعالجة أزرار الموافقة/الرفض --- ?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> <?php // تضمين SweetAlert2 ?>
<script type="text/javascript">


jQuery(document).ready(function($) {
    // ... (الكود السابق لمعالجة أزرار الموافقة/الرفض إذا كنت ستبقيه في مكان آخر) ...
    // حاليًا لا يوجد أزرار موافقة/رفض في هذه الصفحة بعد الإزالة
});
</script>