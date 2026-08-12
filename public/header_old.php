<!DOCTYPE html>
<html <?php language_attributes(); ?> dir="rtl">
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php wp_title(); ?></title>
    <?php // Font Awesome for hamburger icon - ensure it's loaded, ideally via functions.php ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <?php wp_head(); ?>
    <style>
        /* تنسيقات مخصصة للوجو والعنوان وشريط الأزرار */
        .smc-header-container {
            display: flex; /* For both desktop and mobile */
            align-items: center; /* For both desktop and mobile */
            justify-content: space-between; /* For both desktop and mobile */
            padding: 10px 15px; /* Default padding */
            flex-direction: row; /* Default direction */
        }

        .smc-logo-title {
            display: flex;
            align-items: center;
        }

        .smc-logo-container {
            width: 80px;
            height: 80px;
            margin-left: 10px; /* Changed from margin-right for RTL */
        }

        .smc-logo-container img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .smc-header-title {
            font-size: 1.5em;
            margin: 0;
        }

        /* --- Desktop Styles for Navigation --- */
        .smc-header-nav { /* This is the <header> element containing hamburger and nav */
            position: relative; /* For dropdown positioning */
            /* In RTL, this will be on the right if it's the first child of .smc-header-container */
        }

        .smc-hamburger-menu {
            display: none; /* Hidden on desktop by default */
            font-size: 1.8em;
            color: #333;
            background: none;
            border: none;
            cursor: pointer;
        }

        .smc-header-nav nav { /* The <nav> element itself */
            display: flex; /* Show links horizontally on desktop */
            flex-direction: row;
        }

        .smc-header-nav nav a { /* Styling for individual desktop navigation links/buttons */
            color: #fff !important; /* White text */
            text-decoration: none;
            padding: 8px 15px;
            margin: 0 5px;
            border-radius: 5px;
            transition: background-color 0.3s, color 0.3s;
            background-color: #28a745; /* Green background */
            border: 1px solid #28a745;
        }

        .smc-header-nav nav a:hover { /* Hover state for desktop links */
            background-color: #fff; /* White background on hover */
            color: #218838 !important; /* Green text on hover */
        }

        /* Mobile specific adjustments for header */
        @media (max-width: 768px) {
            .smc-header-container {
                padding: 8px 10px; /* Slightly reduce padding for mobile */
                /* flex-direction: row; is inherited and correct */
                /* justify-content: space-between; is inherited and correct */
            }
            .smc-logo-title {
                /* Will be on the left due to HTML order and RTL */
                margin-bottom: 0;
            }
            .smc-logo-container {
                width: 40px; /* Smaller logo */
                height: 40px; /* Smaller logo */
                margin-left: 8px;
            }
            .smc-header-title {
                font-size: 1.0em; /* Smaller title font */
                white-space: nowrap;
            }

            .smc-hamburger-menu {
                display: block; /* Show hamburger on mobile */
                font-size: 1.7em; /* Adjust hamburger icon size */
                /* Will be on the right due to HTML order and RTL */
            }

            /* Adjust nav menu position if hamburger is on the right */
            .smc-header-nav nav {
                display: none; /* Hide nav links by default on mobile */
                flex-direction: column;
                position: absolute;
                top: 100%; /* Position below the header */
                right: 0;   /* Dropdown from the right */
                left: auto; /* Ensure it doesn't stretch to left */
                width: 200px; /* Or a specific width like 100% or auto */
                background-color: #f9f9f9;
                border: 1px solid #ddd;
                border-top: none;
                box-shadow: 0 4px 8px rgba(0,0,0,0.1);
                z-index: 999;
                padding: 10px 0;
            }

            .smc-header-nav nav.smc-mobile-nav-active {
                display: flex; /* Show nav links when active */
            }

            .smc-header-nav nav a {
                padding: 10px 15px; /* Full width padding */
                font-size: 0.95em;
                margin: 0;       /* Remove individual margins */
                border-bottom: 1px solid #eee; /* Separator for links */
                text-align: right; /* Align text to the right for RTL */
                background-color: transparent; /* Override desktop button background */
                color: #333 !important; /* Override desktop button color */
                border: none; /* Remove desktop button border */
                border-radius: 0; /* Remove desktop button border-radius */
            }
            .smc-header-nav nav a:last-child {
                border-bottom: none;
            }
            .smc-header-nav nav a:hover {
                background-color: #e9e9e9; /* Hover effect for mobile links */
                color: #007bff !important;
            }

            /* --- Global Mobile Size Adjustments --- */
            /* Base font size for mobile */
            body {
                font-size: 14px; /* This was already here, ensure it's what you want */
                 line-height: 1.5;
            }

            /* Headings */
            h1 { font-size: 1.5em; } /* .smc-header-title is handled above for mobile */
            h2 { font-size: 1.3em; }
            h3 { font-size: 1.15em; }
            h4 { font-size: 1.05em; }

            /* Paragraphs and general text */
            p, li, span, label, td, th, a { /* شملت الروابط العامة هنا */
                font-size: 0.95em; /* تصغير نسبي للنصوص العامة */
            }

            /* Icons (general Font Awesome) */
            .fas, .far, .fal, .fab, [class*="fa-"] { /* استهداف أكثر شمولاً للأيقونات */
                font-size: 0.9em; /* تصغير الأيقونات بشكل عام */
            }

            /* Images - ensure they are responsive */
            img {
                max-width: 100%;
                height: auto;
            }

            /* Buttons (general styling) */
            .smc-button, button, input[type="submit"], input[type="button"] {
                font-size: 0.9em;
                padding: 7px 10px; /* تقليل الحشو الداخلي للأزرار */
            }
            .smc-button i, button i {
                font-size: 0.9em; /* تصغير الأيقونات داخل الأزرار */
                margin-left: 3px;
            }

            /* Input fields */
            input[type="text"], input[type="number"], input[type="email"],
            input[type="password"], input[type="date"], select, textarea {
                font-size: 0.95em;
                padding: 7px; /* تقليل الحشو الداخلي لحقول الإدخال */
            }

            /* DataTables specific font size reduction */
            .dataTables_wrapper, .dataTables_filter input, .dataTables_length select, .dt-buttons .dt-button {
                font-size: 0.85em !important; /* تصغير خطوط DataTables وعناصر التحكم */
            }
            /* --- End Global Mobile Size Adjustments --- */
        }
</style>
</head>
<body <?php body_class(); ?>>
<div class="smc-header-container">    
    <header class="smc-header-nav"> <!-- Nav container now wraps hamburger for correct dropdown positioning -->
        <button class="smc-hamburger-menu" aria-label="Toggle navigation" aria-expanded="false">
            <i class="fas fa-bars"></i>
        </button>
        <nav>
            <a href="<?php echo esc_url( home_url( '/instructions/' ) ); ?>">📜 الإرشادات</a>
            <a href="<?php echo esc_url( home_url( '/deposit/' ) ); ?>">💰 إيداع</a>
            <a href="<?php echo esc_url( home_url( '/withdraw-deposit/' ) ); ?>">🏆 سحب الوديعة</a>
            <a href="<?php echo esc_url( home_url( '/withdraw-profits/' ) ); ?>">💸 سحب الأرباح</a>
        </nav>
    </header>
    <div class="smc-logo-title">
        <div class="smc-logo-container">
            <img src="https://blogger.googleusercontent.com/img/b/R29vZ2xl/AVvXsEhu31YTxUiqvsNuz7XlKR-XIaOSSREqcYxZhs2N3DUIOuFp2iqgW5bQjVa0lWzMQXqZYtWCuk5qN-f6quL60-nTaUPdOuxcyddVpJ2bzTPCRTySjcip7r6y6GadUNv_7Xq-Fxp10sEaZ1jenxerbez_XpAMY04U4UzovNp1A69fN6g_K9eZNyG39qQz0F4/s480/orig_480x480.png" alt="شعار المنصة">
        </div>        
        <h1 class="smc-header-title">منصة SMC للمهام اليومية</h1>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const hamburgerButton = document.querySelector('.smc-hamburger-menu');
    const navMenu = document.querySelector('.smc-header-nav nav');

    if (hamburgerButton && navMenu) {
        hamburgerButton.addEventListener('click', function() {
            navMenu.classList.toggle('smc-mobile-nav-active');
            const isExpanded = navMenu.classList.contains('smc-mobile-nav-active');
            hamburgerButton.setAttribute('aria-expanded', isExpanded);
            // Optional: Change hamburger icon to close icon
            hamburgerButton.querySelector('i').classList.toggle('fa-bars', !isExpanded);
            hamburgerButton.querySelector('i').classList.toggle('fa-times', isExpanded);
        });
    }
});
</script>
