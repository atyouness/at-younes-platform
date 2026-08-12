<?php
/**
 * Template Name: Users Referral Tree (Admin List)
 * Description: Displays a list of referrals (who referred whom) for the admin. // تم تعديل الوصف
 */

if (!defined('ABSPATH')) exit; // Exit if accessed directly

// Redirect if not logged in or not an admin
if (!is_user_logged_in() || !current_user_can('administrator')) {
    wp_redirect(home_url('/')); // Or login page
    exit;
}

get_header();
// $user_id = get_current_user_id(); // لا نحتاج ID المستخدم الحالي هنا
$is_admin = true; // التأكيد أنه عرض للمسؤول
?>

<div class="container referral-list-container">
    <h2><i class="fas fa-users"></i> قائمة الإحالات (من دعا من) - للمسؤول</h2> <?php // تعديل العنوان ?>

    <!-- Log Controls -->
    <div class="smc-log-controls" style="margin-bottom: 20px; padding: 15px; background-color: #f9f9f9; border: 1px solid #eee; border-radius: 5px;">
        <p><strong>أدوات التحكم بالسجل:</strong></p>
        <p>يمكنك استخدام حقل "بحث" أدناه للبحث عن طريق اسم الداعي أو المدعو. يمكنك أيضًا فرز الأعمدة.</p>
        <p>أزرار التصدير (Copy, CSV, Excel, PDF, Print) متاحة أعلى الجدول.</p>
        <p><a href="<?php echo esc_url(home_url('/smc-settings/')); ?>" class="smc-button smc-button-secondary"><i class="fas fa-cog"></i> العودة إلى إعدادات SMC</a></p>
    </div>

    <table id="admin-referral-list-table" class="display compact stripe hover smc-log-table" style="width:100%"> <?php // تغيير ID الجدول ?>
        <thead>
            <tr>
                <th>الداعي (اسم المستخدم)</th>
                <th>المدعو (اسم المستخدم)</th>
                <th>تاريخ تسجيل المدعو</th>
                <th>رمز الدعوة المستخدم</th>
            </tr>
        </thead>
        <tbody>
            <?php
            global $wpdb;
            $referrals_table = $wpdb->prefix . 'smc_referrals'; // استخدام جدول الإحالات الجديد المقترح

            // التحقق من وجود جدول الإحالات
            if($wpdb->get_var("SHOW TABLES LIKE '$referrals_table'") == $referrals_table) {
                // جلب جميع سجلات الإحالات
                $all_referrals = $wpdb->get_results("SELECT * FROM {$referrals_table} ORDER BY referral_timestamp DESC");

                if ($all_referrals) {
                    foreach ($all_referrals as $referral) {
                        $referrer_username = 'N/A';
                        $invitee_username = 'N/A';
                        $invitee_reg_date = 'N/A';

                        // جلب بيانات الداعي
                        $referrer_info = get_userdata($referral->referrer_user_id);
                        $referrer_username = $referrer_info ? $referrer_info->user_login : 'غير معروف (' . $referral->referrer_user_id . ')';

                        // جلب بيانات المدعو
                        $invitee_info = get_userdata($referral->invitee_user_id);
                        if ($invitee_info) {
                            $invitee_username = $invitee_info->user_login;
                            $invitee_reg_date = date_i18n('Y-m-d', strtotime($invitee_info->user_registered)); // استخدام تاريخ تسجيل المستخدم الفعلي
                        } else {
                             $invitee_username = 'غير معروف (' . $referral->invitee_user_id . ')';
                             // يمكن استخدام referral_timestamp كبديل إذا لم يتم العثور على المستخدم
                             $invitee_reg_date = date_i18n('Y-m-d', strtotime($referral->referral_timestamp));
                        }


                        echo '<tr>';
                        echo '<td>' . esc_html($referrer_username) . '</td>';
                        echo '<td>' . esc_html($invitee_username) . '</td>';
                        echo '<td>' . esc_html($invitee_reg_date) . '</td>';
                        echo '<td>' . esc_html($referral->invitation_code_used) . '</td>';
                        echo '</tr>';
                    }
                } else {
                    echo '<tr><td colspan="4">لا توجد إحالات مسجلة لعرضها.</td></tr>';
                }
            } else {
                 echo '<tr><td colspan="4" class="smc-error-message">خطأ: جدول سجل الإحالات (`' . $referrals_table . '`) غير موجود.</td></tr>';
            }
            ?>
        </tbody>
    </table>
</div>

<?php get_footer(); ?>

<?php // تفعيل DataTables ?>
<script type="text/javascript">
jQuery(document).ready(function($) {
    if ($.fn.DataTable) {
        try {
            $('#admin-referral-list-table').DataTable({ // استخدام ID الجدول الجديد
                responsive: true,
                dom: 'Bfrtip', // Buttons, filter, processing, table, info, pagination
                buttons: [
                    'copy', 'csv', 'excel', 'pdf', 'print'
                ],
                order: [[ 2, "desc" ]], // الترتيب الافتراضي حسب تاريخ التسجيل الأحدث
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/ar.json',
                    search: "بحث في القائمة:" // تخصيص نص البحث
                }
            });
        } catch (e) {
            console.error("Error initializing DataTables for admin referral list:", e);
        }
    } else {
        console.warn("DataTables library not found for admin referral list.");
    }
});
</script>

<style>
/* يمكنك إضافة تنسيقات DataTables هنا إذا لزم الأمر */
.smc-button-secondary { /* تنسيق زر العودة للإعدادات */
    background-color: #6c757d;
    border-color: #6c757d;
    color: white;
    padding: 5px 10px;
    text-decoration: none;
    border-radius: 4px;
    display: inline-block;
    margin-top: 5px;
}
.smc-button-secondary:hover {
    background-color: #5a6268;
    border-color: #545b62;
    color: white;
}
.smc-button-secondary i {
    margin-left: 5px;
}
.smc-error-message {
    color: #dc3545; background-color: #f8d7da; border: 1px solid #f5c6cb;
    padding: 10px; border-radius: 5px; text-align: center;
}
/* تنسيقات DataTables (يمكن نسخها من ملفات أخرى) */
.dt-buttons .dt-button { /* ... */ }
.dataTables_filter label { /* ... */ }
.dataTables_filter input { /* ... */ }
</style>
<?php
/**
 * Template Name: Users Referral Tree (Admin List)
 * Description: Displays a list of referrals (who referred whom) for the admin. // تم تعديل الوصف
 */

if (!defined('ABSPATH')) exit; // Exit if accessed directly

// Redirect if not logged in or not an admin
if (!is_user_logged_in() || !current_user_can('administrator')) {
    wp_redirect(home_url('/')); // Or login page
    exit;
}

get_header();
// $user_id = get_current_user_id(); // لا نحتاج ID المستخدم الحالي هنا
$is_admin = true; // التأكيد أنه عرض للمسؤول
?>

<div class="container referral-list-container">
    <h2><i class="fas fa-users"></i> قائمة الإحالات (من دعا من) - للمسؤول</h2> <?php // تعديل العنوان ?>

    <!-- Log Controls -->
    <div class="smc-log-controls" style="margin-bottom: 20px; padding: 15px; background-color: #f9f9f9; border: 1px solid #eee; border-radius: 5px;">
        <p><strong>أدوات التحكم بالسجل:</strong></p>
        <p>يمكنك استخدام حقل "بحث" أدناه للبحث عن طريق اسم الداعي أو المدعو. يمكنك أيضًا فرز الأعمدة.</p>
        <p>أزرار التصدير (Copy, CSV, Excel, PDF, Print) متاحة أعلى الجدول.</p>
        <p><a href="<?php echo esc_url(home_url('/smc-settings/')); ?>" class="smc-button smc-button-secondary"><i class="fas fa-cog"></i> العودة إلى إعدادات SMC</a></p>
    </div>

    <table id="admin-referral-list-table" class="display compact stripe hover smc-log-table" style="width:100%"> <?php // تغيير ID الجدول ?>
        <thead>
            <tr>
                <th>الداعي (اسم المستخدم)</th>
                <th>المدعو (اسم المستخدم)</th>
                <th>تاريخ تسجيل المدعو</th>
                <th>رمز الدعوة المستخدم</th>
            </tr>
        </thead>
        <tbody>
            <?php
            global $wpdb;
            $referrals_table = $wpdb->prefix . 'smc_referrals'; // استخدام جدول الإحالات الجديد المقترح

            // التحقق من وجود جدول الإحالات
            if($wpdb->get_var("SHOW TABLES LIKE '$referrals_table'") == $referrals_table) {
                // جلب جميع سجلات الإحالات
                $all_referrals = $wpdb->get_results("SELECT * FROM {$referrals_table} ORDER BY referral_timestamp DESC");

                if ($all_referrals) {
                    foreach ($all_referrals as $referral) {
                        $referrer_username = 'N/A';
                        $invitee_username = 'N/A';
                        $invitee_reg_date = 'N/A';

                        // جلب بيانات الداعي
                        $referrer_info = get_userdata($referral->referrer_user_id);
                        $referrer_username = $referrer_info ? $referrer_info->user_login : 'غير معروف (' . $referral->referrer_user_id . ')';

                        // جلب بيانات المدعو
                        $invitee_info = get_userdata($referral->invitee_user_id);
                        if ($invitee_info) {
                            $invitee_username = $invitee_info->user_login;
                            $invitee_reg_date = date_i18n('Y-m-d', strtotime($invitee_info->user_registered)); // استخدام تاريخ تسجيل المستخدم الفعلي
                        } else {
                             $invitee_username = 'غير معروف (' . $referral->invitee_user_id . ')';
                             // يمكن استخدام referral_timestamp كبديل إذا لم يتم العثور على المستخدم
                             $invitee_reg_date = date_i18n('Y-m-d', strtotime($referral->referral_timestamp));
                        }


                        echo '<tr>';
                        echo '<td>' . esc_html($referrer_username) . '</td>';
                        echo '<td>' . esc_html($invitee_username) . '</td>';
                        echo '<td>' . esc_html($invitee_reg_date) . '</td>';
                        echo '<td>' . esc_html($referral->invitation_code_used) . '</td>';
                        echo '</tr>';
                    }
                } else {
                    echo '<tr><td colspan="4">لا توجد إحالات مسجلة لعرضها.</td></tr>';
                }
            } else {
                 echo '<tr><td colspan="4" class="smc-error-message">خطأ: جدول سجل الإحالات (`' . $referrals_table . '`) غير موجود.</td></tr>';
            }
            ?>
        </tbody>
    </table>
</div>

<?php get_footer(); ?>

<?php // تفعيل DataTables ?>
<script type="text/javascript">
jQuery(document).ready(function($) {
    if ($.fn.DataTable) {
        try {
            $('#admin-referral-list-table').DataTable({ // استخدام ID الجدول الجديد
                responsive: true,
                dom: 'Bfrtip', // Buttons, filter, processing, table, info, pagination
                buttons: [
                    'copy', 'csv', 'excel', 'pdf', 'print'
                ],
                order: [[ 2, "desc" ]], // الترتيب الافتراضي حسب تاريخ التسجيل الأحدث
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/ar.json',
                    search: "بحث في القائمة:" // تخصيص نص البحث
                }
            });
        } catch (e) {
            console.error("Error initializing DataTables for admin referral list:", e);
        }
    } else {
        console.warn("DataTables library not found for admin referral list.");
    }
});
</script>

<style>
/* يمكنك إضافة تنسيقات DataTables هنا إذا لزم الأمر */
.smc-button-secondary { /* تنسيق زر العودة للإعدادات */
    background-color: #6c757d;
    border-color: #6c757d;
    color: white;
    padding: 5px 10px;
    text-decoration: none;
    border-radius: 4px;
    display: inline-block;
    margin-top: 5px;
}
.smc-button-secondary:hover {
    background-color: #5a6268;
    border-color: #545b62;
    color: white;
}
.smc-button-secondary i {
    margin-left: 5px;
}
.smc-error-message {
    color: #dc3545; background-color: #f8d7da; border: 1px solid #f5c6cb;
    padding: 10px; border-radius: 5px; text-align: center;
}
/* تنسيقات DataTables (يمكن نسخها من ملفات أخرى) */
.dt-buttons .dt-button { /* ... */ }
.dataTables_filter label { /* ... */ }
.dataTables_filter input { /* ... */ }
</style>
