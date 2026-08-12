<footer class="smc-footer-nav">
    <nav>
        <!-- تم استبدال زر تسجيل الخروج بزر الحساب -->
        <a href="<?php echo esc_url( home_url( '/smc-daily-tasks/account/' ) ); ?>"><i class="fas fa-user-circle"></i><span>الحساب</span></a>
        <a href="<?php echo esc_url( home_url( '/my-team/' ) ); ?>"><i class="fas fa-users"></i><span>فريقي</span></a>
        <a href="<?php echo esc_url( home_url( '/tasks/' ) ); ?>"><i class="fas fa-tasks"></i><span>المهام</span></a>
        <a href="<?php echo esc_url( home_url( '/smc-daily-tasks/' ) ); ?>"><i class="fas fa-home"></i><span>الرئيسية</span></a>
    </nav>
</footer>
    <?php wp_footer(); ?>
    <style>
        .smc-footer-nav {
            background-color: #333; /* Example background */
            color: white;
            padding: 5px 0;
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            z-index: 1000;
            box-shadow: 0 -2px 5px rgba(0,0,0,0.1);
        }
        .smc-footer-nav nav {
            display: flex;
            justify-content: space-around; /* Distribute buttons evenly */
            align-items: center;
            max-width: 1200px; /* Optional: constrain width on larger screens */
            margin: 0 auto;
        }
        .smc-footer-nav nav a {
            color: white;
            text-decoration: none;
            padding: 10px 5px; /* Adjust padding */
            text-align: center;
            flex-grow: 1; /* Make buttons take equal space */
            font-size: 0.8em; /* Smaller font for footer */
            display: flex; /* For icon and text alignment */
            flex-direction: column; /* Stack icon and text */
            align-items: center;
            transition: background-color 0.2s;
        }
        .smc-footer-nav nav a i { /* If you add icons to footer links */
            font-size: 1.2em;
            margin-bottom: 3px;
        }
        .smc-footer-nav nav a:hover,
        .smc-footer-nav nav a.active { /* Example active state */
            background-color: #555;
        }
        /* Mobile specific adjustments for footer */
        @media (max-width: 768px) {
            .smc-footer-nav nav a {
                font-size: 0.85em; /* تعديل طفيف ليتناسب مع حجم الخط الأساسي الجديد */
                padding: 8px 3px; /* Adjust padding */
            }
            .smc-footer-nav nav a i {
                font-size: 1.2em; /* تعديل طفيف لحجم الأيقونة */
            }
            /* Add some padding to the body to prevent content from being hidden by the fixed footer */
            body {
                padding-bottom: 60px; /* Adjust based on footer height */
            }
        }
    </style>
</body>
</html>