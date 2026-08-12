<?php
/**
 * Template Name: Advertising Deals Page (Example)
 * Description: Displays the current advertising deal and handles the watching process.
 */

// التأكد من أن WordPress هو من يقوم بتحميل هذا الملف
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// التحقق من تسجيل دخول المستخدم
if (!is_user_logged_in()) {
    // إعادة توجيه المستخدم إلى صفحة تسجيل الدخول أو الصفحة الرئيسية
    wp_redirect(home_url('/')); // توجيه للصفحة الرئيسية
    exit;
}

// تضمين ملف header.php
get_header();

// جلب بيانات المستخدم للتحقق من الوديعة (يمكن تحسينه لاحقًا)
$user_id = get_current_user_id();
$current_deposit = 0; // قيمة افتراضية
if (function_exists('smc_get_user_data')) {
    $user_smc_data = smc_get_user_data($user_id);
    if (is_array($user_smc_data)) {
        $current_deposit = $user_smc_data['current_deposit'] ?? 0;
    } else {
        error_log("SMC Warning: smc_get_user_data() returned non-array in advertising-deal.php for user ID: " . $user_id);
        // Fallback: Try getting meta directly (less efficient)
        $current_deposit = floatval(get_user_meta($user_id, SMC_DEPOSIT_BALANCE, true) ?: 0);
    }
} else {
    error_log("SMC Error: Function smc_get_user_data() not found in advertising-deal.php");
    // Fallback: Try getting meta directly (less efficient)
    $current_deposit = floatval(get_user_meta($user_id, SMC_DEPOSIT_BALANCE, true) ?: 0);
}


// التحقق من وجود وديعة كافية
$min_deposit_required = 2000; // الحد الأدنى المطلوب (اجعله ديناميكيًا إذا لزم الأمر)
if ($current_deposit < $min_deposit_required) {
    echo '<div class="container" style="padding: 20px; text-align: center;">';
    echo '<p class="smc-error-message" style="margin-bottom: 15px;">عذرًا، يجب أن يكون لديك رصيد وديعة كافٍ (' . number_format($min_deposit_required, 2) . ' دج على الأقل) لعرض الصفقات الإعلانية. (رصيدك الحالي: ' . number_format($current_deposit, 2) . ' دج)</p>';
    echo '<a href="' . esc_url(home_url('/smc-daily-tasks/')) . '" class="smc-button smc-button-secondary"><i class="fas fa-arrow-left"></i> العودة للرئيسية</a>';
    echo '</div>';
    get_footer();
    exit;
}


?>

<div class="container ad-deal-page-container">
    <h2><i class="fas fa-ad"></i> الصفقة الإعلانية الحالية</h2>

    <div id="ad-deal-loading" style="text-align: center; padding: 30px;">
        <p><i class="fas fa-spinner fa-spin fa-2x"></i> جاري تحميل تفاصيل الصفقة...</p>
    </div>

    <div id="ad-deal-content" style="display: none;"> <?php // سيتم إظهاره بعد تحميل البيانات ?>

        <div class="ad-deal-main-layout">
            <!-- الحاوية الأولى: الصورة -->
            <div class="ad-display-section">
                <h4>الإعلان</h4>
                <?php // *** تعديل: تمرير رابط الصورة الافتراضية مباشرة في onerror *** ?>
                <img id="ad-image" src="" alt="Ad Image" style="max-width: 100%; height: auto; border: 1px solid #ddd; border-radius: 5px;"
                onerror="this.onerror=null; this.src='<?php echo esc_url(get_stylesheet_directory_uri() . '/images/default-ad.png'); ?>'; console.error('SMC Ad Deal: Failed to load primary image. Using default.');">
                <hr style="border-top: 1px dashed #ccc;">
                <p><strong>إسم الصفقة الإعلانية:</strong> <span id="ad-deal-name">...</span></p>
                <p><strong>معرف الصفقة الإعلانية:</strong> <span id="ad-deal-id" style="font-family: monospace; letter-spacing: 1px; color: #dc3545; font-weight: bold;">...</span></p>
            <div class="countdown-container">
                <p id="countdown-timer" style="font-size: 1.5em; font-weight: bold; color: #007bff; margin-top: 10px; display: none;">
                    الوقت المتبقي: <span id="timer-seconds">--</span> ثانية
                </p>
                 <p id="ad-completed-message" style="font-size: 1.2em; color: green; display: none;">
                    <i class="fas fa-check-circle"></i> تم إكمال المشاهدة بنجاح!
                 </p>
            </div>
            </div>

            <!-- الحاوية الثانية: تفاصيل السعر والربح -->
            <div class="ad-details-section">
                <h4>تفاصيل الصفقة</h4>
                <p>سعر الإعلان: <strong id="ad-price">...</strong> دج</p>
                <p>ق.م للإعلان (19%): <strong id="ad-tax">...</strong> دج</p>
                <p>سعر صافي للإعلان: <strong id="net-ad-price">...</strong> دج</p>
                <p style="font-weight: bold; color: #28a745; margin-top: 10px; border-top: 1px solid #eee; padding-top: 10px;">يمكنك تغيير الصفقة الحالية واختيار عرض أحسن منها، وفر رصيدك للاستفادة على أفضل عروضنا، بحيث يبدأ سعر الإعلان من : <strong id="ad-price-min">...</strong> دج</p>
                <hr style="border-top: 1px dashed #ccc;">
                <p>ربح من الإعلان: <strong id="ad-profit-value">...</strong> دج</p>
                <p>ق.م للربح (19%): <strong id="profit-tax">...</strong> دج</p>
                <p>ربح صافي للإعلان: <strong id="net-ad-profit">...</strong> دج</p>
                <hr style="border-top: 1px dashed #ccc;">
        <!--  النسبة، المدة، الضريبة الإجمالية،  -->
            <div class="timing-details">
                <span>نسبة الربح: <strong id="profit-percentage">...</strong> %</span>
                <span>مدة الإعلان: <strong id="ad-duration">...</strong> ثانية</span>
                <span>ق.م للصفقة (19%): <strong id="deal-tax">...</strong> دج</span>
            </div>
                <hr style="border-top: 1px dashed #ccc;">
                <p style="font-weight: bold; color: #28a745; margin-top: 10px; border-top: 1px solid #eee; padding-top: 10px;">فائدتك من الصفقة: <strong id="deal-benefit">...</strong> دج</p>
            </div>
        </div>

        <!-- أزرار الإجراءات -->
        <div class="ad-buttons">
            <button id="start-watch-button" class="smc-button"><i class="fas fa-play-circle"></i> بدء المشاهدة</button>
            <button id="cancel-ad-button" class="smc-button smc-button-secondary"><i class="fas fa-times-circle"></i> إلغاء (صفقة أخرى)</button>
        </div>

    </div> <?php // نهاية ad-deal-content ?>

    <?php // عنصر لعرض رسائل الخطأ من AJAX ?>
    <div id="ad-deal-error-message" class="smc-error-message" style="display: none; margin-top: 15px;"></div>

</div>

<?php
// تضمين ملف footer.php
get_footer();
?>

<?php // إضافة CSS الخاص بالصفحة ?>
<style>
.ad-deal-page-container {
    max-width: 900px;
    margin: 20px auto;
    padding: 20px;
    background-color: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}
.ad-deal-main-layout {
    display: flex;
    gap: 20px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}
.ad-display-section {
    flex: 1;
    min-width: 250px;
    padding: 15px;
    border: 1px solid #eee;
    border-radius: 5px;
    background-color: #f9f9f9;
    text-align: center;
}
.ad-details-section {
    flex: 1.5;
    min-width: 300px;
    padding: 15px;
    border: 1px solid #eee;
    border-radius: 5px;
    background-color: #f9f9f9;
}
.ad-details-section h4, .ad-display-section h4 {
    margin-top: 0;
    margin-bottom: 15px;
    color: #333;
    border-bottom: 1px solid #ddd;
    padding-bottom: 5px;
}
.ad-details-section p {
    margin: 8px 0;
    font-size: 0.95em;
}
.ad-details-section strong {
    color: #0056b3;
}

.ad-timing-section {
    background-color: #e9ecef;
    padding: 15px;
    border-radius: 5px;
    margin-bottom: 20px;
    text-align: center;
}
.timing-details {
    display: flex;
    justify-content: space-around;
    flex-wrap: wrap;
    gap: 15px;
    margin-bottom: 10px;
}
.timing-details span {
    font-size: 0.9em;
    color: #555;
}
.timing-details strong {
    color: #28a745;
}
.countdown-container {
    min-height: 40px;
}

.ad-buttons {
    text-align: center;
    margin-top: 20px;
}
.ad-buttons .smc-button {
    margin: 0 10px;
    padding: 10px 20px;
    font-size: 1em;
}
.smc-button {
    background-color: #007bff;
    color: white;
    padding: 10px 15px;
    border: none;
    border-radius: 5px;
    text-decoration: none;
    cursor: pointer;
    transition: background-color 0.3s ease, opacity 0.3s ease;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    vertical-align: middle;
}
.smc-button:hover {
    background-color: #0056b3;
}
.smc-button i {
    margin-left: 5px;
}
.smc-button-secondary {
    background-color: #6c757d;
    border-color: #6c757d;
}
.smc-button-secondary:hover {
    background-color: #5a6268;
    border-color: #545b62;
}
.smc-button:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}
.smc-error-message {
    color: #dc3545;
    background-color: #f8d7da;
    border: 1px solid #f5c6cb;
    padding: 10px;
    border-radius: 5px;
    text-align: center;
}

/* Mobile-specific styles for watch mode */
@media (max-width: 768px) {
    .ad-deal-main-layout.mobile-watch-mode .ad-details-section {
        display: none; /* Hide details section during watch mode on mobile */
    }
    .ad-deal-main-layout.mobile-watch-mode .ad-display-section {
        flex-basis: 100%; /* Make image section take full width */
        order: -1; /* Bring image to top */
    }
}

/* أيقونات Font Awesome */
@import url("https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css");

</style>

<?php // إضافة JavaScript الخاص بالصفحة ?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> <?php // تضمين SweetAlert2 ?>
<script type="text/javascript">
jQuery(document).ready(function($) {

    // --- المتغيرات ---
    const ajaxUrl = (typeof smc_data !== 'undefined') ? smc_data.ajax_url : null;
    const nonce = (typeof smc_data !== 'undefined') ? smc_data.nonce : null; // General nonce
    const homeUrl = (typeof smc_data !== 'undefined') ? smc_data.homeUrl : '/';
    const defaultImageUrl = (typeof smc_data !== 'undefined') ? smc_data.default_image_url : null;

    let currentAdDetails = null;
    let countdownInterval = null;
    let remainingTime = 0;
    let isWatching = false;
    let isFetchingDeal = false;

    const loadingDiv = $('#ad-deal-loading');
    const contentDiv = $('#ad-deal-content');
    const errorDiv = $('#ad-deal-error-message');

    const adImageElement = $('#ad-image');
    const adDealNameElement = $('#ad-deal-name');
    const adDealIdElement = $('#ad-deal-id');
    const adPrice = $('#ad-price');
    const adTax = $('#ad-tax');
    const netAdPrice = $('#net-ad-price');
    const adProfitValue = $('#ad-profit-value');
    const profitTax = $('#profit-tax');
    const netAdProfit = $('#net-ad-profit');
    const dealBenefitElement = $('#deal-benefit');
    const profitPercentage = $('#profit-percentage');
    const adDuration = $('#ad-duration');
    const dealTax = $('#deal-tax');
    const countdownTimer = $('#countdown-timer');
    const timerSeconds = $('#timer-seconds');
    const completedMessage = $('#ad-completed-message');
    const startButton = $('#start-watch-button');
    const cancelButton = $('#cancel-ad-button');
    const adPriceMinElement = $('#ad-price-min');

    function showError(message) {
        errorDiv.text(message).show();
        setTimeout(() => { errorDiv.hide().text(''); }, 5000);
    }

    function populateAdData(data) {
        console.log("SMC Ad Deal: Populating UI with data:", data);
        currentAdDetails = data;

        try {
            let imageUrlToSet = data.image_url || defaultImageUrl;
            if (adImageElement.length) {
                if (imageUrlToSet) {
                    adImageElement.attr('src', imageUrlToSet);
                } else {
                    adImageElement.attr('src', '');
                }
            }

            const dealNameText = data.ad_name ?? 'N/A';
            if (adDealNameElement.length) { adDealNameElement.text(dealNameText); }

            const dealIdText = data.deal_id ?? 'N/A';
            if (adDealIdElement.length) { adDealIdElement.text(dealIdText); }

            const priceText = data.price?.toFixed(2) ?? 'N/A';
            if (adPrice.length) { adPrice.text(priceText); }

            const taxText = data.adTax?.toFixed(2) ?? 'N/A';
            if (adTax.length) { adTax.text(taxText); }

            const netPriceText = data.netPrice?.toFixed(2) ?? 'N/A';
            if (netAdPrice.length) { netAdPrice.text(netPriceText); }

            const profitValText = data.profitValue?.toFixed(2) ?? 'N/A';
            if (adProfitValue.length) { adProfitValue.text(profitValText); }

            const profitTaxText = data.profitTax?.toFixed(2) ?? 'N/A';
            if (profitTax.length) { profitTax.text(profitTaxText); }

            const netProfitText = data.netProfit?.toFixed(2) ?? 'N/A';
            if (netAdProfit.length) { netAdProfit.text(netProfitText); }

            const percText = (data.profitPercentage * 100)?.toFixed(3) ?? 'N/A';
            if (profitPercentage.length) { profitPercentage.text(percText); }

            const durationText = data.duration ?? 'N/A';
            if (adDuration.length) { adDuration.text(durationText); }

            const dealTaxText = data.dealTax?.toFixed(2) ?? 'N/A';
            if (dealTax.length) { dealTax.text(dealTaxText); }

            let benefit = (data.netProfit ?? 0) - (data.price ?? 0);
            const benefitText = benefit.toFixed(2);
            if (dealBenefitElement.length) {
                dealBenefitElement.text(benefitText);
                dealBenefitElement.parent().css('color', benefit < 0 ? '#dc3545' : '#28a745');
            }

            const minPriceText = data.ad_price_min?.toFixed(2) ?? '...';
            if (adPriceMinElement.length) { adPriceMinElement.text(minPriceText); }

            resetUIState();
            loadingDiv.hide();
            contentDiv.show();
            errorDiv.hide();

            setTimeout(() => {
                 startButton.prop('disabled', false);
                 cancelButton.prop('disabled', false);
            }, 500);

        } catch (error) {
            console.error("SMC Ad Deal: Error during populateAdData execution:", error);
            showError("حدث خطأ أثناء عرض بيانات الصفقة.");
            loadingDiv.html('<p style="color: red;">فشل عرض الصفقة. <button onclick="location.reload()">إعادة المحاولة</button></p>').show();
            contentDiv.hide();
        }
    }

    function resetUIState() {
        isWatching = false;
        clearInterval(countdownInterval);
        countdownInterval = null;
        countdownTimer.hide();
        completedMessage.hide();
        startButton.prop('disabled', true).html('<i class="fas fa-play-circle"></i> بدء المشاهدة');
        $('.ad-deal-main-layout').removeClass('mobile-watch-mode'); // Ensure class is removed
        cancelButton.prop('disabled', true);
    }

    function loadAdDeal() {
        if (isFetchingDeal) return;
        if (!ajaxUrl || !nonce) {
            showError("خطأ في الإعدادات: لا يمكن جلب الصفقة.");
            loadingDiv.html('<p style="color: red;">فشل تحميل الصفقة (خطأ إعداد). <button onclick="location.reload()">إعادة المحاولة</button></p>');
            return;
        }
        isFetchingDeal = true;
        loadingDiv.show();
        contentDiv.hide();
        errorDiv.hide();
        resetUIState();

        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: { action: 'fetch_ad_details', nonce: nonce },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    if (response.data && typeof response.data.timestamp === 'number' && typeof response.data.deal_id === 'string' && typeof response.data.image_url === 'string') {
                        populateAdData(response.data);
                    } else {
                        showError('خطأ: بيانات الصفقة المستلمة غير كاملة.');
                        loadingDiv.html('<p style="color: red;">فشل تحميل الصفقة. <button onclick="location.reload()">إعادة المحاولة</button></p>');
                    }
                } else {
                    showError('خطأ أثناء جلب تفاصيل الصفقة: ' + response.data.message);
                    loadingDiv.html('<p style="color: red;">فشل تحميل الصفقة. <button onclick="location.reload()">إعادة المحاولة</button></p>');
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                showError('حدث خطأ في الاتصال بالخادم لجلب تفاصيل الصفقة.');
                loadingDiv.html('<p style="color: red;">فشل تحميل الصفقة. <button onclick="location.reload()">إعادة المحاولة</button></p>');
            },
            complete: function() {
                isFetchingDeal = false;
            }
        });
    }

    function startCountdown() {
        if (!currentAdDetails || isNaN(currentAdDetails.duration) || currentAdDetails.duration <= 0) {
            showError("خطأ: مدة الإعلان غير صالحة لبدء العداد.");
            resetUIState();
            return;
        }
        if (!isWatching) {
             resetUIState();
             return;
        }
        if (countdownInterval) return;

        remainingTime = parseInt(currentAdDetails.duration, 10);
        timerSeconds.text(remainingTime);
        countdownTimer.show();

        countdownInterval = setInterval(function() {
            remainingTime--;
            timerSeconds.text(remainingTime);
            if (remainingTime <= 0) {
                clearInterval(countdownInterval);
                countdownInterval = null;
                handleAdCompletion();
            }
        }, 1000);
    }

    function handleAdCompletion() {
        countdownTimer.hide();
        completedMessage.show();

        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: { action: 'complete_ad_watch', nonce: nonce },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'تمت المشاهدة بنجاح!',
                        html: `لقد أحرزت على ربح صافي للإعلان: <strong>${response.data.netProfit.toFixed(2)} دج</strong>.<br>مجموع رصيد أرباحك الآن <strong>${response.data.newTotalProfit.toFixed(2)} دج</strong>.`,
                        confirmButtonText: 'حسناً، العودة للرئيسية'
                    }).then((result) => {
                        if (homeUrl) {
                            window.location.href = homeUrl;
                        } else {
                            window.location.href = '/';
                        }
                    });
                } else {
                    showError('خطأ أثناء تسجيل إكمال المشاهدة: ' + response.data.message);
                    resetUIState();
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                showError('حدث خطأ في الاتصال بالخادم لتسجيل إكمال المشاهدة.');
                resetUIState();
            }
        });
    }

    startButton.on('click', function() {
        if (!currentAdDetails || isWatching || isFetchingDeal) return;

        const dealToConfirm = {
            price: currentAdDetails.price,
            timestamp: currentAdDetails.timestamp,
            ad_name: currentAdDetails.ad_name,
            deal_id: currentAdDetails.deal_id
        };

        if (typeof dealToConfirm.price !== 'number' || typeof dealToConfirm.timestamp !== 'number' || dealToConfirm.timestamp <= 0 || !dealToConfirm.ad_name || typeof dealToConfirm.deal_id !== 'string' || !dealToConfirm.deal_id) {
             showError("خطأ داخلي: بيانات الصفقة غير صالحة قبل التأكيد.");
             return;
        }

        Swal.fire({
            title: 'تأكيد بدء المشاهدة',
            html: `
                <div style="text-align: right; margin-bottom: 15px;">
                    <p style="margin-bottom: 5px;"><strong>إسم الصفقة الإعلانية:</strong> ${dealToConfirm.ad_name || 'N/A'}</p>
                    <p style="margin-bottom: 5px;"><strong>معرف الصفقة الإعلانية:</strong> <span style="font-family: monospace; color: #dc3545; font-weight: bold;">${dealToConfirm.deal_id || 'N/A'}</span></p>
                </div>
                <hr style="border-top: 1px dashed #ccc; margin: 10px 0;">
                <p>عزيزي المستخدم، هل توافق على خصم سعر الإعلان (<strong>${dealToConfirm.price.toFixed(2)} دج</strong>) من وديعتك؟</p>
            `,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fas fa-check"></i> موافق',
            cancelButtonText: '<i class="fas fa-times"></i> إلغاء (صفقة أخرى)',
            allowOutsideClick: false,
            allowEscapeKey: false
        }).then((result) => {
            if (result.isConfirmed) {
                isWatching = true;
                startButton.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> جارٍ البدء...');
                cancelButton.prop('disabled', true);

                $.ajax({
                    url: ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'start_ad_watch',
                        nonce: nonce,
                        adPrice: dealToConfirm.price,
                        dealTimestamp: dealToConfirm.timestamp
                    },
                    beforeSend: function() {
                        // Apply mobile-watch-mode class before starting countdown
                        if (window.innerWidth <= 768) {
                            $('.ad-deal-main-layout').addClass('mobile-watch-mode');
                            // Optional: Scroll to the ad image
                            $('html, body').animate({ scrollTop: $("#ad-image").offset().top - 20 }, 500);
                        }
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            startCountdown();
                        } else {
                            showError('خطأ أثناء بدء المشاهدة: ' + response.data.message);
                            resetUIState();
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        showError('حدث خطأ في الاتصال بالخادم لبدء المشاهدة.');
                        resetUIState();
                    },
                });
            } else {
                loadAdDeal();
            }
        });
    });

    cancelButton.on('click', function() {
        if (isWatching || isFetchingDeal) return;
        loadAdDeal();
    });

    loadAdDeal();

});
</script>
