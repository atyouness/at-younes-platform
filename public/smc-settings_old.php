<?php
/**
 * Template Name: SMC Settings
 * Description: This is a custom template for the SMC Settings page.
 */

// التأكد من أن WordPress هو من يقوم بتحميل هذا الملف
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// تضمين ملف header.php
get_header();
?>

<div class="container smc-settings-container"> <?php // إضافة فئة رئيسية ?>
    <?php
    // التحقق من تسجيل دخول المستخدم
    if (is_user_logged_in()) {
        // الحصول على معلومات المستخدم الحالي
        $current_user = wp_get_current_user();
        $user_id = $current_user->ID;
        ?>

        <h2><i class="fas fa-cogs"></i> إعدادات SMC (خاص بالمسؤول)</h2>

        <?php
        // التحقق مما إذا كان المستخدم الحالي هو مسؤول
        if (current_user_can('administrator')) {
        ?>
            <!-- *** بداية التعديل: قسم أزرار السجلات المعاد تصميمه *** -->
            <section class="smc-admin-section smc-log-cards-section">
                <h4><i class="fas fa-history"></i> الوصول السريع للسجلات</h4>
                <p>انقر على البطاقة أدناه لعرض السجل التفصيلي:</p>
                <div class="smc-log-cards-grid"> <?php // استخدام grid لعرض البطاقات ?>

                    <?php // 1. سجل عمليات الإيداع ?>                
					<div class="smc-log-card">
                        <a href="/users-deposit-log/">
                            <i class="fas fa-money-check-alt card-icon deposit-icon"></i>
                            <span>سجل عمليات الإيداع</span>
                        </a>
                    </div>
                    <?php // 2. سجل إثبات الدفع ?>
                    <div class="smc-log-card">
                        <a href="/proof-payment-record/">
                            <i class="fas fa-receipt card-icon proof-payment-icon"></i> <?php // أيقونة جديدة ?>
                            <span>سجل إثبات الدفع</span>
                        </a>
                    </div>
                    <?php // 3. وضعية الودائع ?>
                    <div class="smc-log-card">
                        <a href="/users-deposit-status/"> <?php // تم تغيير الرابط إلى سجل المسؤول ?>
                            <i class="fas fa-sliders-h card-icon attendance-icon"></i> <?php // تغيير الأيقونة ?>
                            <span>وضعية الودائع </span>
                        </a>
                    </div>                    
                    <?php // 4. سجل المكافآت ?>
                    <div class="smc-log-card">
                        <a href="/users-rewards-log/">
                            <i class="fas fa-trophy card-icon rewards-icon"></i>
                            <span>سجل المكافآت</span>
                        </a>
                    </div>
                    <?php // 5. سجل صفقات الإعلانية ?>
                    <div class="smc-log-card">
                        <a href="/users-advertising-deals-record/">
                            <i class="fas fa-chart-line card-icon daily-earn-icon"></i>
                            <span>سجل صفقات الإعلانية</span>
                        </a>
                    </div>
                    <?php // 6. سجل أرباح الاستثمار ?>
                    <div class="smc-log-card">
                        <a href="/admin-investment-profits-log/">
                            <i class="fas fa-coins card-icon total-earn-icon"></i>
                            <span>سجل أرباح الاستثمار</span>
                        </a>
                    </div>
                    <?php // 7. سجل سحب الاستثمار المجدول ?>
                    <div class="smc-log-card">
                        <a href="<?php echo esc_url(home_url('/admin-scheduled-investment-withdrawals-log/')); ?>">
                            <i class="fas fa-calendar-check card-icon scheduled-withdrawals-icon"></i>
                            <span>سجل سحب الاستثمار المجدول</span>
                        </a>
                    </div>                   
                    <?php // 8. الراتب الشهري ?>
                    <div class="smc-log-card">
                        <a href="/users-ranks/">
                            <i class="fas fa-money-bill-wave card-icon monthly-salary-icon"></i> <?php // أيقونة جديدة للراتب الشهري ?>
                            <span>الراتب الشهري</span>
                        </a>
                    </div>
                    <?php // 9. سجل سحب الودائع ?>
                    <div class="smc-log-card">
                        <a href="/users-deposit-withdrawal-log/">
                            <i class="fas fa-undo-alt card-icon deposit-withdraw-icon"></i>
                            <span>سجل سحب الودائع</span>
                        </a>
                    </div>
                    <?php // 10. سجل سحب الأرباح ?>
                    <div class="smc-log-card">
                        <a href="/users-profit-withdrawal-log/">
                            <i class="fas fa-hand-holding-usd card-icon profit-withdraw-icon"></i>
                            <span>سجل سحب الأرباح</span>
                        </a>
                    </div>
                     <?php // 11. سجل ظهور الإعلانات ?>
                    <div class="smc-log-card">
                       <a href="/displaying-ads-log/"> <?php // تأكد من صحة الرابط ?>
                             <i class="fas fa-eye card-icon ads-view-icon"></i> <?php // أيقونة جديدة ?>
                             <span>سجل ظهور الإعلانات</span>
                         </a>
                     </div>
                     <?php // 12. سجل الضغطات ?>
                     <div class="smc-log-card">
                         <a href="/number-clicks-log/"> <?php // تأكد من صحة الرابط ?>
                             <i class="fas fa-mouse-pointer card-icon clicks-icon"></i> <?php // أيقونة جديدة ?>
                             <span>سجل الضغطات</span>
                         </a>
                     </div>
                      <?php // 13. سجل الإحالات ?>
                    <div class="smc-log-card">
                        <a href="/users-referral-log/">
                            <i class="fas fa-user-friends card-icon referral-icon"></i>
                            <span>سجل الإحالات</span>
                        </a>
                    </div>
                    <?php // 14. شجرة الإحالات ?>
                    <div class="smc-log-card">
                        <a href="/users-referral-tree/">
                            <i class="fas fa-sitemap card-icon tree-icon"></i>
                            <span>شجرة الإحالات</span>
                        </a>
                    </div>
                    <?php // 15. سجل الحضور ?>
                    <div class="smc-log-card">
                        <a href="/users-attendance-log/"> <?php // تم تغيير الرابط إلى سجل المسؤول ?>
                            <i class="fas fa-calendar-check card-icon attendance-icon"></i>
                            <span>سجل الحضور</span>
                        </a>
                    </div>
                    <?php // 16. سجل حركة الدخول ?>
                    <div class="smc-log-card">
                        <a href="/members-login-log/">
                            <i class="fas fa-sign-in-alt card-icon login-icon"></i>
                            <span>سجل حركة الدخول</span>
                        </a>
                    </div>

                </div>
            </section>
            <!-- *** نهاية التعديل *** -->

            <hr> <!-- فاصل بعد بطاقات السجلات -->



        <?php
        } else {
            // إذا لم يكن المستخدم مسؤولاً، عرض رسالة مناسبة
            echo "<p>ليس لديك الصلاحيات لعرض هذه الصفحة.</p>";
        }
        ?>

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
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); /* أعمدة متجاوبة */
    gap: 15px; /* مسافة بين البطاقات */
    margin-top: 10px;
}

.smc-log-card {
    background-color: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.08);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    border-left: 4px solid #007bff; /* شريط لوني على اليسار */
    min-height: 130px;
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
    min-height: 130px; /* ارتفاع أدنى للبطاقة */
}

.smc-log-card .card-icon {
    font-size: 2.2em; /* حجم أكبر للأيقونة */
    margin-bottom: 10px;
    /* ألوان مختلفة لكل أيقونة */
}
.smc-log-card .login-icon { color: #6f42c1; }
.smc-log-card .deposit-icon { color: #17a2b8; }
.smc-log-card .proof-payment-icon { color: #6610f2; } /* *** تعديل: لون جديد *** */
.smc-log-card .attendance-icon { color: #28a745; }
.smc-log-card .scheduled-withdrawals-icon { color: #4682B4; } /* SteelBlue for Admin Scheduled Withdrawals Icon */
.smc-log-card .rewards-icon { color: #ffc107; }
.smc-log-card .daily-earn-icon { color: #fd7e14; }
.smc-log-card .total-earn-icon { color: #20c997; }
.smc-log-card .deposit-withdraw-icon { color: #e83e8c; }
.smc-log-card .profit-withdraw-icon { color: #28a745; }
.smc-log-card .ads-view-icon { color: #17a2b8; } /* سماوي */
.smc-log-card .clicks-icon { color: #fd7e14; } /* برتقالي */
.smc-log-card .referral-icon { color: #007bff; }
.smc-log-card .tree-icon { color: #6610f2; }
.smc-log-card .monthly-salary-icon { color: #542a84; } /* لون جديد لأيقونة الراتب الشهري */
.smc-log-card .scheduled-withdrawals-icon { color: #4682B4; } /* SteelBlue for Admin Scheduled Withdrawals Icon */
.smc-log-card .monthly-fee-icon { color: #dc3545; }
.smc-log-card .daily-earn-icon { color: #fd7e14; } */ /* مكرر */

.smc-log-card span {
    font-weight: 600;
    font-size: 0.95em;
}

/* تخصيص ألوان الشريط الأيسر للبطاقات */
.smc-log-card:nth-child(16n+1) { border-left-color: #17a2b8; }  /* 1. سجل عمليات الإيداع */
.smc-log-card:nth-child(16n+2) { border-left-color: #6610f2; }  /* 2. سجل إثبات الدفع */
.smc-log-card:nth-child(16n+3) { border-left-color: #28a745; }  /* 3. وضعية الودائع */
.smc-log-card:nth-child(16n+4) { border-left-color: #ffc107; }  /* 4. سجل المكافآت */
.smc-log-card:nth-child(16n+5) { border-left-color: #fd7e14; }  /* 5. سجل صفقات الإعلانية */
.smc-log-card:nth-child(16n+6) { border-left-color: #20c997; }  /* 6. سجل أرباح الاستثمار */
.smc-log-card:nth-child(16n+7) { border-left-color: #4682B4; }  /* 7. سجل سحب الاستثمار المجدول */
.smc-log-card:nth-child(16n+8) { border-left-color: #542a84; }  /* 8. الراتب الشهري */
.smc-log-card:nth-child(16n+9) { border-left-color: #e83e8c; }  /* 9. سجل سحب الودائع */
.smc-log-card:nth-child(16n+10) { border-left-color: #28a745; } /* 10. سجل سحب الأرباح */
.smc-log-card:nth-child(16n+11) { border-left-color: #17a2b8; } /* 11. سجل ظهور الإعلانات */
.smc-log-card:nth-child(16n+12) { border-left-color: #fd7e14; } /* 12. سجل الضغطات */
.smc-log-card:nth-child(16n+13) { border-left-color: #007bff; } /* 13. سجل الإحالات */
.smc-log-card:nth-child(16n+14) { border-left-color: #6610f2; } /* 14. شجرة الإحالات */
.smc-log-card:nth-child(16n+15) { border-left-color: #28a745; } /* 15. سجل الحضور */
.smc-log-card:nth-child(16n+16) { border-left-color: #6f42c1; } /* 16. سجل حركة الدخول */

/* إذا كان لديك أكثر من 16 بطاقة، يمكنك تكرار النمط: */
/* .smc-log-card:nth-child(16n+17) { border-left-color: #some_color; } */


/* تنسيق جداول الموافقة (إذا كانت موجودة في الصفحة) */
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

<?php // --- إضافة JavaScript لمعالجة أزرار الموافقة/الرفض (إذا كانت موجودة) --- ?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> <?php // تضمين SweetAlert2 ?>
<script type="text/javascript">
jQuery(document).ready(function($) {
    // الكود الخاص بمعالجة أزرار الموافقة/الرفض (إذا كانت هناك جداول موافقة في هذه الصفحة)
    // ...
});
</script>
