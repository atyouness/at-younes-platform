<?php
/**
 * Astra Child Theme functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package Astra Child
 */

/**
 * Define Constants
 */
define( 'CHILD_THEME_ASTRA_CHILD_VERSION', '1.0.0' );

/**
 * Enqueue styles and scripts.
 */
function child_enqueue_styles_scripts() {
	// Enqueue parent theme styles
	wp_enqueue_style( 'astra-parent-theme-css', get_template_directory_uri() . '/style.css', array(), wp_get_theme()->parent()->get('Version'), 'all' );

    // Enqueue child theme styles
	wp_enqueue_style( 'astra-child-theme-css', get_stylesheet_directory_uri() . '/style.css', array('astra-parent-theme-css'), CHILD_THEME_ASTRA_CHILD_VERSION, 'all' );

    // ١. تسجيل وتحميل ملف JavaScript الرئيسي الخاص بك
    //    استخدم معرفاً فريداً مثل 'smc-main-script'.
    //    تأكد من صحة المسار إلى ملف script.js.
    //    'jquery' كتبيعة (dependency).
    //    true لتحميل السكربت في الفوتر (مُستحسن).
    wp_enqueue_script( 'smc-main-script', get_stylesheet_directory_uri() . '/script.js', array('jquery'), CHILD_THEME_ASTRA_CHILD_VERSION, true );

    // ٢. تعريف مصفوفة روابط الصور في PHP
    $ad_images_urls = array(
        "https://blogger.googleusercontent.com/img/b/R29vZ2xl/AVvXsEhExmfssiq0ZUNLEE6yZ4OKAvp1jP7qBrQuAtefLVRmPQuBoUeTTaGsXO0YUV0SNnLA0qvYtqv92VocD2MXPey0YFsjyRH53_dsljiwSScvbLdsCQcIePt3Xqai6EOuZpuEvpuTztlrgqNU1sFKJNdHG6Mw1-BQm9AgL_kvzQz4oQkuzG9xQBQSiOoPMy_y/s320/Screenshot_20250210_114916_Chrome.jpg",
        "https://blogger.googleusercontent.com/img/b/R29vZ2xl/AVvXsEjqI64BwWB4fgop_xbQTBG-IK4aBOd9iioYo5nSyWAN0yq3ktm77l8cmR4SNACGS68X1petXQTEv9xLL8jYMTuQ4nQ_Wajw2y0r6LZQsDUv2hvRl4rPUx1fG_vve9HshqLV1HXuxcOcngJTdw_ZlhT7HH2El9hRzHLLd6XhZAmJMvo_NpHiCmstIs6RXEYl/s320/Screenshot_20250210_114805_Chrome.jpg",
        "https://blogger.googleusercontent.com/img/b/R29vZ2xl/AVvXsEj-Ql7hg4aPAH3kGUZRrOALkzEXMIKFKx-GOdWrpiPkK5AcGrcuW2_vxT3l5LplU6niSVG_YRH_JvmFJDfZrUmTrxZmawiGO0m4gf3qolz6JMy7SZRanEukOm0W0eg8qMVRCGfph2RNjCV38BxOPmny2XU8QpzO_7AVLvOtg-wZwNTLhuPB4samwkMDKZ1d/s320/Screenshot_20250210_114650_Chrome.jpg",
        "https://blogger.googleusercontent.com/img/b/R29vZ2xl/AVvXsEh99U9lXGqx1ThMOusc5eNP_qGyrWBWrh5eXn8yT5_VJnQuGcwBpe2fZKV_3ODbq1_dNH2RPrgJLTZdFIDTOk2lomYR6dDpin4HEyQz2-i4twthOnxODM9q-nLfWTDSLRbNK5qrZty7KdXxOuZSCU7dMjySXNB4i50IOZAtMcTKAn2SnMLPCuvxW1nrgyhu/s320/Screenshot_20250210_113340_Chrome.jpg",
        "https://blogger.googleusercontent.com/img/b/R29vZ2xl/AVvXsEgT3CzlV0SX7T06LpweQaf6RPv8r-SIMMMz_ikFgc3IoHi3s6Z6t1DG0PFgWQ-A-4Z5nfw7Q2yFaLTD7SwtnkWOCSJdR1lR6jCCSz6GUYzoSCTzBkEKi1wjanOdrPHcBpDh0v7AcTTMLc9Porj3MSq8-xMv5OpH4UpNZIHPBuIDgRjCNs3JaydxoP4H8mZ7/s320/Screenshot_20250210_113236_Chrome.jpg",
        "https://blogger.googleusercontent.com/img/b/R29vZ2xl/AVvXsEjWkDeg6fOQgjCcGfozg3HCOW3uKHweKlpne6lHIU8fcNbyMRdhJfPCgl6KreXqoX5hYh732mUKTjyeLkf41jcsaJwUnqx45FDu-WMK2a3r5Bo9slPrXQpmvdyUSorbpDS6EwOp943_ncY1mhczzBuSUm_EckAAwlbngzXyFZqvBPu55-AxgNKLk9NAg4y4/s320/Screenshot_20250210_113157_Chrome.jpg",
        "https://blogger.googleusercontent.com/img/b/R29vZ2xl/AVvXsEgijbIUJl8xzLPOPJiPWUaaYVyjD80e8Oy2_4vukZGFhuV4RcO6jN7op4RX4oCq3jteryRqrxLeoDYrEXpt7iFb2eGySkaC6slOMOyJlBTyg42E29xmL-nwUxe3ICOESlBkKpDj9waq7INmSa5gGUZ3aD-BoOQpH64SMLSq495w9q0aA_NfOYcn7MvHIkjs/s320/Screenshot_20250210_113045_Chrome.jpg",
        "https://blogger.googleusercontent.com/img/b/R29vZ2xl/AVvXsEjRUAPebiXgr3qlwl-OYjwju35n2KhWiPN7yphlnzw5BBleSbRw5XNqqIZa5sUVCyrSUc1XfxlDUJKusJ0QqOFoNF7clg2SOB4hHLeMlliQGE_e43ZIgo7gl4zDtuHCFkQS93JgOfzyI3Orb4fNSefEjNZg2UJAXLcsy7hYhV8WzSSUnXdseYXDtrUGv3vI/s320/Screenshot_20250210_112927_Chrome.jpg",
        "https://blogger.googleusercontent.com/img/b/R29vZ2xl/AVvXsEhdQXZxogJkf3H08VzKVOTEeRRxrfZ-KGQgbMsF7eSpnhdWQz9OUst7bHXz6P17AssLIrwKZ4PiBaIbZ7dY3uhURujVFjUcyhqN2MWICbQxCzsrDHnvC1ge5RgqvBjRB_tSwEkAZQLA0_WWuw2p_TMrDLamvUKGsCBzRzy7ZDeaco0YFeGdaGTvbn2seWnj/s320/Screenshot_20250210_112808_Chrome.jpg",
        "https://blogger.googleusercontent.com/img/b/R29vZ2xl/AVvXsEgetOqMR_YfUrgBn2XhFpikcX6ALxgiafGAWxE2VVxkFszNvDSGlGjwxzoJz0UprwoYn8BmBgevo1Fd25oAfGLyr2OpWSf0ZQkh4cA05MDY1LQX7wYfPvnuxOQK4gdguKiLdjBtoIP_Sp5xrT9wdiRFn6yY6TCxT2DUHTpQXst_xixrRxFWkwb2eC6pRLQd/s320/Screenshot_20250210_112623_Chrome.jpg",
        "https://blogger.googleusercontent.com/img/b/R29vZ2xl/AVvXsEivIpkG-5BteLfOPVKQbJ133SIuTMge2rtNs2sUbHtrs1bp60ORNLaUzfpBrwRRKz3RxyCESGNGbLEe3yUjv2dGmWjDBVmiS6CNC6_MEFYzBbNd0RXY3DcN70xj8oVNUfmP-c247O4niQLP6ft9tqxHJOZS4S5M1NDe0VnbHtroKsbDJqmwPANHuHrWfRCE/s320/Screenshot_20250210_105354_Chrome.jpg",
        "https://blogger.googleusercontent.com/img/b/R29vZ2xl/AVvXsEiQ95hY_Km0d2cxNpR6bSNQXXnutf13Hg_8H5E8-m7_rD2pGKsFmaQ4Mfgo1W1XL2Z4pptAM4mnBpk0a1Nuuuu4WAPCv7KCUOD2o0SdgI1dp6V-lxoL1lW3ZyY0m-mGWaeVfRz08pLM21ogryRAeXfL8X_ceow0HyeAOn-UYAdCl-uJ125mBL_5H0-aJZHO/s320/Screenshot_20250210_105243_Chrome.jpg",
        "https://blogger.googleusercontent.com/img/b/R29vZ2xl/AVvXsEjtXuMZbC5EPMcmQ85WsaA1ET1d0_F4b71pEPkK8naXj_G1ZfAAmEkvuEjF1NNWlJcK8ABOzcSntE7Q5LNI7WcszuiIiL8XiXtK_UaMa14JaneKuFHJIpLzke0Es0u3ayXMvQ5CbnNjtMWbvCGsX6jpfdNLiQhSQNH2OCm4Iy93ogSmTSe0D9aTW8fjp1PI/s320/Screenshot_20250210_105000_Chrome.jpg",
        "https://blogger.googleusercontent.com/img/b/R29vZ2xl/AVvXsEgdb-gCpddk6ERxEFWMvLpOBUlz5sy6T56my9mcNRdwSPBZtfTgo-q9O-_g36DuY1DW8dr8iYYzRmXz2rYO7WkCI90hH8N3YDSlXCxxz72HniyDmRAkgzWhoIR4MEQQqR7ZdLunQSgeKR26-jmhR0kKQTSoArlf5i8SOkZTzxzyCHxHRywBJCruim8m85jL/s320/Screenshot_20250210_135411_Chrome.jpg",
        "https://blogger.googleusercontent.com/img/b/R29vZ2xl/AVvXsEg2o7IJrSyEPGVye2Wnm9wCHwYffxXLCZf7-VDPnSkIbtTjbf4urWDyfPjMRQzZD5WUKOmQQomCSRScXzAXZQcDdMnBy7MDPvdWQR-umuiqOhY2QycR4jFnw_o_V4i9j1InVuxpluCUNISw57-7IWwekduRJoIA8xLBFZJI2eb4dj5tjtGRBn_RXsiM4UEG/s320/Screenshot_20250210_135256_Chrome.jpg",
        "https://blogger.googleusercontent.com/img/b/R29vZ2xl/AVvXsEgi48RBr94W59z8ngYXdELOEqVTo2mJ5vsqNCvurZt9AO6Xer4HkWUqNDaBy0hBep1j0rzhRUjKABmwC7HFpT_EvX6h7M1Mzu5poTErg8-5i9WSMWs0Eh_v2L_LTAcRVkXwpZGadvE_QfJV7tjZ1OitvT455OWdjtGWEqaxaKdfkGS1GN478RWVen4U-5Ez/s320/Screenshot_20250210_135143_Chrome.jpg",
        "https://blogger.googleusercontent.com/img/b/R29vZ2xl/AVvXsEh3_ngy3h4nQp2D_wgP1f1ACWbk09lXANkIjehLDZPDtjzz5jWJ3KFvms84C1dWT34et46EoMlsjZAG_woaqARxWDuDvsZwe32n5AvEx5-DaGaFJjmRD-GJNoK5C9fbfrGpxvUD9UxxXeD_QZi2EzEUUzG82KkTCwvldkurTemlwkt3ZCqfspw6E5-5rvcB/s320/Screenshot_20250210_134845_Chrome.jpg",
        "https://blogger.googleusercontent.com/img/b/R29vZ2xl/AVvXsEhJ7q9UE7GBJSgf2q2u5OZKfO6U1FP0YmfKKfxMMPn0c4KF2gTi3QjYY-g6EoXz-SrZTplYpeY-j0xQjuWvEg3RiUPMnRR2bJlUZXuDZQLaz0TPR24UQ3h9AtapAb81xrfbnwGA8y68dk3PPLESU87QaZwyeux-9wFDTLKCTx4N894XOLBZA7YhG6KlBSCM/s320/Screenshot_20250210_124254_Chrome.jpg",
        "https://blogger.googleusercontent.com/img/b/R29vZ2xl/AVvXsEiFmPjrwTcTYftKUVo9AXmlU687YuOPDZqswfDTfSBcqFgI7FEBH48rsWmZXiys_tWhtK0kjpwY9YzyQQR4nFom5a41y9ZjHiw7r5vKXHst34dN08IMTu3LmDriYVRSIW1ZYV8mvwRXp4WJIc8OHZGOKJCE-T4z31jJgC8xAz_K3vKUi3_KY-rx31I04KaR/s320/Screenshot_20250210_124135_Chrome.jpg",
        "https://blogger.googleusercontent.com/img/b/R29vZ2xl/AVvXsEii3A5o7Pf2iQ8Nxg8SKaKvwL0MxvG6OoBlgbCxdNrgNhWMfs3ixMjPvY0AzaNFHWm0iYN-d0vBiW3INCPlysUff2y6HvRgVDuMXJc2rWXZBRLcmvz-wiOGioKJmmLbfWqg-fgDhUIcHiMyeDEmvznFKfTtkzwev9iQRjlf_AN5_jVmzVztg8d9ATJGyfX0/s320/Screenshot_20250210_124028_Chrome.jpg",
        "https://blogger.googleusercontent.com/img/b/R29vZ2xl/AVvXsEio9G3pYqWKFFp-pPiWcYWjML6FP0BSs9c-G-tDPDAejY6k2oPSuwWT1w-zzmzUwJCFFXhBtqJ9hnHPkEOpaL-3T12WbjqWepGnmP7t9wbCxBE9u9IXHaUWf3LeSyWp-vJbQYWIfokGf-2pliwTX4_uYtX6q2eSzkyFRC9FNslgnck-dRKjLLaC1QoCed2e/s320/Screenshot_20250210_123837_Chrome.jpg",
        "https://blogger.googleusercontent.com/img/b/R29vZ2xl/AVvXsEiHrlm8GkLCmzM8mU8sqCZ1dcnQqg4upS2-qBwn9DF8LNcmlWYx7btdteMY_hWvolDQ44D3fgef4eX91ztW4lDWbcRrpXo83-iJtkmJki9J0-4IS_8mpo-QmPBQXGliR0dtj0FsokxE4_b10cZ36y0KPMf9M_cxKLJF2aidKHFUTxFNjSs9JGsd604oY9Vr/s320/Screenshot_20250210_123640_Chrome.jpg",
        "https://blogger.googleusercontent.com/img/b/R29vZ2xl/AVvXsEhEk0UF7d0vGM_wbClqH5SOUADrQtWrWLh-gGfpEQBoFkv_zh1dCwCdEDZtwWohg_5SsT7GcyDC-8AW3VhcxlGarnLFhGn_A0A9S2uzeEZxAv-M20V5w53fjSRotKbXDfcJvvkOcGaXf3j-FiRvoPc-vEuBR8K0PXQt8kCjxUP7sfMW2S3f_kz4PLm7yZNR/s320/Screenshot_20250210_123534_Chrome.jpg",
        "https://blogger.googleusercontent.com/img/b/R29vZ2xl/AVvXsEiJcyWCz2ArjEjVSaocxyNkrP6qtS00AdW60VMPC8PKAl2MBuoFV_8nHUNhT_w1-3tdSPn_7Ad0WB_xZelYJpD9TfHy81KjpMz60udmxO2b_25QQr_h_Jq8-gW9lJwEsFbMUrrnn3zilla11v28EQO4VP6odVT_Eat0UwsaqTzLTRh0y_8nCTZ5AJg4-geK/s320/Screenshot_20250210_123417_Chrome.jpg",
        "https://blogger.googleusercontent.com/img/b/R29vZ2xl/AVvXsEiAk_9KdUbT5K7kxSTbViVKo5w31EkHu_cFld0bW_PxuRHanhp06znZH5MvbGcr5ljxSrc4jKqDjqHkcwFL7dJCXm6a5czG88o8vMuzZZREcP6U0V4EZ-JmlfXkjBhP7w5snb45RU_YL__IJsCuhN7DrY0vwcaPB8eTrTiu3Qhs309HiMm1lIQ2UunzkNeL/s320/Screenshot_20250210_123255_Chrome.jpg",
        "https://blogger.googleusercontent.com/img/b/R29vZ2xl/AVvXsEiAgBXsKQMkpGE335QObG-a5NSGsrL8_oMTb31RzAhfMce0VAkdD8uu4J8DFOBJWVvg7Plc-6oBU5Snlwhh6OAAtuiiY-x8mcc0DdN1JBGmbewBK7KJR4CZYAP0WfAbdlGXCB0Ub86P4PRUDWPSqdWvu6APc6QmXymU352y0TRj-XBOT8UwAnY3SQ5dafZJ/s320/Screenshot_20250210_123048_Chrome.jpg",
        "https://blogger.googleusercontent.com/img/b/R29vZ2xl/AVvXsEidxgsrvjQQuupv40eEOB38ixbxV8s-u7hwgGk1u6TdjY28n5o5nTuMq_F94azVUR2y04AIMH8c6EaCgXN3p1cSKjgYhQ-dnAoZVB4gBwoWVYMPVrT4-Gk7-5h6a4nb6mNYg6gUkCOLce0FiZTAMMmc3A_NWaGOoQdUmZsHuDThdq11eqD4ZvIl6Itf-5DD/s320/Screenshot_20250210_122947_Chrome.jpg",
        "https://blogger.googleusercontent.com/img/b/R29vZ2xl/AVvXsEjQvAE0GsvKASVYsYpTtNgL_mq-HT50NjXtXVEFp-L2bCvbCIop5R_ksQbrbOiwcm1sabCZiruyH9s_ruI9z9CznDUhBrfv3L8v4TvPfFX2Q0nvlQcpZXxI6G1e8hqBoW_4nNzStdq8I6kQXuUyPV559JWOYRAURPP54siygKcVfyM8KuJ5rqqiQQGW5kvt/s320/Screenshot_20250210_122913_Chrome.jpg",
        "https://blogger.googleusercontent.com/img/b/R29vZ2xl/AVvXsEhiqpyaUOicQe8F9cCEzXkwCy-mNEj8SalEyfsUj9b_3etarLPNsotwHNvRIBQXWZeES7wqtZfu1Xwcea4nBzEf05ykIIuAAUJas7ZDTqGtYUu14t1L6O-B2ESXwcED41IXTPngz8-TjWc6ckzjUDf8AlS-uwfeLYl09LnsiTwt2nTbL29PSo1Wyzi3hyMQ/s320/Screenshot_20250210_122746_Chrome.jpg",
        "https://blogger.googleusercontent.com/img/b/R29vZ2xl/AVvXsEjgajwCwNv4X_Uj86po-XF6e1KYDD3wwI7uOY9TOq8LSNYd3nzhoLJq_XdXI2aU5fLsQBIyJdEM0Ve_Us_gkQr3yYq0BCG0zYdOQP4xklQmFXncfa7jBadphNai7V3M89klKZgFaIHyHFsWcfl5DncLc6EqdIj5rFXVyWGpr_6RJNB4Rb12JQXOcGkdLkDK/s320/Screenshot_20250210_122630_Chrome.jpg",
        "https://blogger.googleusercontent.com/img/b/R29vZ2xl/AVvXsEgRps7ppd_gA3Ye75W76NqYPMde_cFYoxPK6vyMg_mxmzRTUPLINOWx9YDOKiTlCTmCh8sIAlhG5J-KLk4hwwvBcp0_ULEHI5tDj9zFRmBFShEMxb2IQKJ6CgrlfD_zp9sZ2q4NFagnBeI69pUFC_SuVu8lEWxs3L6j1pIbaWY4BR7RoINcXEott2OO53R_/s320/Screenshot_20250212_160705_Chrome.jpg",
        "https://blogger.googleusercontent.com/img/b/R29vZ2xl/AVvXsEjupf9KmDoNPFnbuCeCDk70lBTIOkLNsvTZr1fw-J6unhe4IyJDoUfR0DZCTB1Wy_5AtnyvgP7PEocbBBTucugVGPt6ufUKHZgtpScWpi4uLhEQjpL69cEAFzse1px7x2Fkaqs15lB-iFo4jP-dIe53ikb_JZEuLQf5ULI_iFzqDq0TpidAp_tDr4nFT9jC/s320/Screenshot_20250211_192026_Chrome.jpg",
        // يمكنك إضافة المزيد من الروابط هنا إذا لزم الأمر
    );

    // ٣. استخدام wp_localize_script لتمرير مصفوفة الصور وبيانات أخرى إلى JavaScript
    //    تأكد من استخدام نفس المعرف ('smc-main-script') المستخدم في wp_enqueue_script.
    //    'smc_data' هو اسم الكائن الذي سيحتوي على البيانات في JavaScript (كما هو مستخدم في script.js).
    //    'imageList' هو المفتاح داخل الكائن smc_data الذي سيحمل مصفوفة الروابط.
    //    نمرر أيضاً ajax_url و nonce و حالة تسجيل الدخول.
    wp_localize_script( 'smc-main-script', 'smc_data', array(
        'ajax_url'          => admin_url( 'admin-ajax.php' ),
        'nonce'             => wp_create_nonce( 'smc_ajax_nonce' ), // تأكد من استخدام نفس النونس عند التحقق في PHP
        'is_user_logged_in' => is_user_logged_in(),
        'imageList'         => $ad_images_urls, // تمرير مصفوفة الصور
    ) );

}
// ربط الدالة مع الأكشن المناسب لتحميل الستايلات والسكربتات في الواجهة الأمامية
add_action( 'wp_enqueue_scripts', 'child_enqueue_styles_scripts' );


// --- أضف أي كود PHP إضافي من ملفك الأصلي هنا ---
// مثال: مكان إضافة معالجات AJAX الخاصة بك
/*
add_action('wp_ajax_fetch_dashboard_data', 'smc_fetch_dashboard_data_handler');
add_action('wp_ajax_nopriv_fetch_dashboard_data', 'smc_fetch_dashboard_data_handler'); // إذا كان مسموحاً لغير المسجلين

function smc_fetch_dashboard_data_handler() {
    // تحقق من النونس والأمان
    check_ajax_referer('smc_ajax_nonce', 'nonce');

    // الكود الخاص بجلب بيانات لوحة التحكم
    // ...

    // إرسال الاستجابة كـ JSON
    wp_send_json_success($data); // أو wp_send_json_error($error_message);
}

add_action('wp_ajax_fetch_ad_details', 'smc_fetch_ad_details_handler');
function smc_fetch_ad_details_handler() {
     // تحقق من النونس والأمان
    check_ajax_referer('smc_ajax_nonce', 'nonce');

    // الكود الخاص بجلب تفاصيل الإعلان التالي
    // !!! هنا قد يكون مكان حساب الربح الخاطئ !!!
    // ابحث عن السطر الذي يحسب $profit أو $ad_profit
    // تأكد من أنه يستخدم $current_deposit * $profit_rate بدلاً من $ad_price * $profit_rate

    // ... جلب بيانات الإعلان ...
    $ad_details = [
        'imageUrl' => '...',
        'duration' => 39, // مثال
        'profitValue' => 0 // !!! يجب حساب القيمة الصحيحة هنا !!!
        // ... بيانات أخرى ...
    ];

    wp_send_json_success($ad_details);
}

add_action('wp_ajax_complete_ad_watch', 'smc_complete_ad_watch_handler');
function smc_complete_ad_watch_handler() {
     // تحقق من النونس والأمان
    check_ajax_referer('smc_ajax_nonce', 'nonce');

    // الكود الخاص بإكمال مشاهدة الإعلان وتحديث بيانات المستخدم (الأرباح، العدادات)
    // !!! هنا أيضاً قد يتم حساب الربح أو تحديثه بناءً على قيمة خاطئة تم حسابها سابقاً !!!
    // تأكد من أن الربح المضاف للمستخدم يعتمد على (الوديعة * النسبة)

    // ... تحديث بيانات المستخدم ...

    wp_send_json_success(['message' => 'Ad completed successfully']);
}

// أضف باقي معالجات AJAX هنا (deposit, withdraw, etc.)

*/

// --- نهاية الكود الإضافي ---

// لا تضع ?> في نهاية الملف إذا كان يحتوي فقط على كود PHP.
