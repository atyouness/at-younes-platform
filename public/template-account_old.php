<?php
/**
 * Template Name: Account Page
 * Description: Displays user account options and quick links.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

get_header();
?>

<div class="container smc-account-page-container">
    <?php if (is_user_logged_in()):
        $current_user = wp_get_current_user();
        $user_profile_url = '#'; // Default placeholder

        if (function_exists('um_user_profile_url')) { // Check if Ultimate Member function exists
            $user_profile_url = um_user_profile_url($current_user->ID);
        } else {
            // Fallback if UM is not active - link to a page with slug 'profile'
            $profile_page = get_page_by_path('profile');
            if ($profile_page) {
                $user_profile_url = get_permalink($profile_page->ID);
            } else {
                // If no /profile page, link to WordPress author archive as a last resort for a "profile" view
                $user_profile_url = get_author_posts_url($current_user->ID);
            }
        }
    ?>
        <h2><i class="fas fa-user-circle"></i> الحساب</h2>

        <section class="smc-quick-access-section">
            <h4><i class="fas fa-th-large"></i> الوصول السريع</h4>
            <p>انقر على البطاقة أدناه للانتقال إلى القسم المطلوب:</p>
            <div class="smc-log-cards-grid">

                <!-- Card 1: Profile -->
                <div class="smc-log-card">
                    <a href="<?php echo esc_url($user_profile_url); ?>">
                        <i class="fas fa-id-card card-icon profile-icon"></i>
                        <span>ملفي الشخصي</span>
                    </a>
                </div>

                <!-- Card 2: Transactions -->
                <div class="smc-log-card">
                    <a href="<?php echo esc_url(home_url('/transactional/')); ?>">
                        <i class="fas fa-exchange-alt card-icon transactions-icon"></i>
                        <span>⚙️ معاملاتي</span>
                    </a>
                </div>

                <!-- Card 3: SMC Settings (Admin Only) -->
                <?php if (current_user_can('administrator')): ?>
                <div class="smc-log-card">
                    <a href="<?php echo esc_url(home_url('/smc-settings/')); ?>">
                        <i class="fas fa-cogs card-icon settings-icon"></i>
                        <span>⚙️ إعدادات SMC</span>
                    </a>
                </div>
                <?php endif; ?>

                <!-- Card 4: Logout -->
                <div class="smc-log-card">
                    <a href="<?php echo esc_url(wp_logout_url(home_url('/'))); ?>">
                        <i class="fas fa-sign-out-alt card-icon logout-icon"></i>
                        <span>تسجيل الخروج</span>
                    </a>
                </div>

            </div>
        </section>

    <?php else: ?>
        <div style="text-align: center; padding: 30px;">
            <p>يرجى تسجيل الدخول لعرض صفحة حسابك.</p>
            <?php
            // عرض نموذج تسجيل الدخول إذا كان المستخدم غير مسجل دخوله
            // تأكد من أن لديك نموذج تسجيل دخول مناسب، يمكنك استخدام شورت كود Ultimate Member إذا كان مفعلاً
            if (shortcode_exists('ultimatemember_login')) {
                echo do_shortcode('[ultimatemember_login]');
            } elseif (shortcode_exists('ultimatemember')) { // Fallback for general UM shortcode if login specific one isn't there
                 // You might need to specify a form ID for UM's general shortcode if it doesn't default to login
                 // echo do_shortcode('[ultimatemember form_id="YOUR_LOGIN_FORM_ID"]');
                 // For now, let's assume a general login form or the user will be redirected by other means.
            } else {
                echo '<a href="' . esc_url(wp_login_url(get_permalink())) . '" class="smc-button">تسجيل الدخول</a>';
            }
            ?>
        </div>
    <?php endif; ?>
</div>

<?php get_footer(); ?>

<style>
/* تأكد من تحميل Font Awesome */
@import url("https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css");

.smc-account-page-container {
    max-width: 900px;
    margin: 20px auto;
    padding: 20px;
    background-color: #f9f9f9; /* خلفية أفتح قليلاً للصفحة */
    border-radius: 8px;
}

.smc-account-page-container h2,
.smc-quick-access-section h4 {
    display: flex;
    align-items: center;
    color: #343a40;
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 1px solid #e0e0e0;
}

.smc-account-page-container h2 i,
.smc-quick-access-section h4 i {
    margin-left: 10px; /* أيقونة على يسار النص */
    color: #007bff; /* لون أزرق رئيسي للأيقونات الرئيسية */
}
.smc-quick-access-section h4 i {
    color: #17a2b8; /* لون مختلف لأيقونة الوصول السريع */
}
.smc-quick-access-section p {
    margin-bottom: 20px;
    color: #555;
}

.smc-log-cards-grid {
    display: grid;
    /* تعديل عدد الأعمدة ليتناسب مع 4 بطاقات أو أقل */
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px; /* مسافة أكبر قليلاً بين البطاقات */
    margin-top: 10px;
}

.smc-log-card {
    background-color: #fff;
    border-radius: 10px; /* حواف أكثر دائرية */
    box-shadow: 0 4px 8px rgba(0,0,0,0.07); /* ظل أنعم */
    transition: transform 0.25s ease, box-shadow 0.25s ease;
    border-left: 5px solid #007bff; /* شريط لوني على اليسار - لون افتراضي */
    min-height: 140px; /* زيادة طفيفة في الارتفاع الأدنى */
    display: flex; /* لجعل الرابط يملأ البطاقة */
}
.smc-log-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 6px 12px rgba(0,0,0,0.1);
}

.smc-log-card a {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 25px 15px; /* زيادة الحشو الداخلي */
    text-decoration: none;
    color: #343a40;
    text-align: center;
    width: 100%; /* تأكد أن الرابط يملأ البطاقة */
}

.smc-log-card .card-icon {
    font-size: 2.5em; /* حجم أكبر للأيقونة */
    margin-bottom: 12px;
}

/* ألوان أيقونات البطاقات */
.smc-log-card .profile-icon { color: #6f42c1; }      /* بنفسجي */
.smc-log-card .transactions-icon { color: #007bff; } /* أزرق */
.smc-log-card .settings-icon { color: #fd7e14; }     /* برتقالي */
.smc-log-card .logout-icon { color: #dc3545; }       /* أحمر */

.smc-log-card span {
    font-weight: 600;
    font-size: 1em; /* حجم خط أكبر قليلاً للنص */
    margin-top: 5px;
}

/* تخصيص ألوان الشريط الأيسر للبطاقات */
.smc-log-card:nth-child(4n+1) { border-left-color: #6f42c1; } /* Profile - بنفسجي */
.smc-log-card:nth-child(4n+2) { border-left-color: #007bff; } /* Transactions - أزرق */
.smc-log-card:nth-child(4n+3) { border-left-color: #fd7e14; } /* Settings - برتقالي */
.smc-log-card:nth-child(4n+4) { border-left-color: #dc3545; } /* Logout - أحمر */

/* زر تسجيل الدخول (إذا تم عرضه) */
.smc-button {
    display: inline-block;
    background-color: #007bff;
    color: white !important; /* Important to override theme link colors */
    padding: 10px 20px;
    text-decoration: none;
    border-radius: 5px;
    transition: background-color 0.3s ease;
    border: none;
    cursor: pointer;
}
.smc-button:hover {
    background-color: #0056b3;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .smc-log-cards-grid {
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    }
    .smc-log-card .card-icon {
        font-size: 2.2em;
    }
    .smc-log-card span {
        font-size: 0.95em;
    }
}
@media (max-width: 480px) {
    .smc-log-cards-grid {
        grid-template-columns: 1fr; /* عمود واحد في الشاشات الصغيرة جداً */
    }
}

</style>
