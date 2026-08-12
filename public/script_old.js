jQuery(document).ready(function($) {
    // --- General AJAX Setup ---
    // Ensure smc_data is available (localized from PHP)
    if (typeof smc_data === 'undefined' || !smc_data.ajax_url) {
        console.error("SMC Script Error: smc_data or ajax_url is not defined. AJAX functionality will be impaired.");
        // Optionally display a user-facing error on critical pages
        // $('.smc-dashboard-container').prepend('<p style="color:red;">خطأ في تحميل إعدادات السكربت. قد لا تعمل بعض الميزات.</p>');
        // return; // Stop further execution if critical data is missing
    }

    const ajaxUrl = smc_data.ajax_url;
    const generalNonce = smc_data.nonce; // General nonce, ensure it's correctly named in PHP

    // --- Dashboard UI Update Function ---
    function updateDashboardUI(data) {
        console.log("SMC Script: Attempting to update dashboard UI with data:", data);

        // Helper function to format numbers
        const formatCurrency = (value) => {
            const num = parseFloat(value);
            return isNaN(num) ? '0.00 دج' : num.toFixed(2) + ' دج';
        };
        const formatNumber = (value) => {
            const num = parseInt(value, 10);
            return isNaN(num) ? '0' : num.toString();
        };

        // --- Update "الوديعة الحالية" (Spendable Tasks Deposit) ---
        const currentDepositValueEl = $('#current-tasks-deposit-value-display');
        if (currentDepositValueEl.length) {
            // data.current_deposit from AJAX is the spendable tasks balance
            const spendableDeposit = data.current_deposit !== undefined ? data.current_deposit : 0;
            currentDepositValueEl.text(formatCurrency(spendableDeposit));
            console.log(`SMC Script: Updated #current-tasks-deposit-value-display with spendable balance: ${spendableDeposit} دج from data.current_deposit.`);
        } else {
            console.warn("SMC Script: Element #current-tasks-deposit-value-display not found for update.");
        }

        // --- Update "تاريخ الإيداع الأخير" (Tasks Specific) ---
        const lastDepositDateEl = $('#last-deposit-date-display');
        if (lastDepositDateEl.length && data.last_tasks_deposit_timestamp) {
            try {
                const date = new Date(data.last_tasks_deposit_timestamp * 1000);
                // More robust date formatting, consider timezone if issues arise
                const options = { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true, timeZone: 'Africa/Algiers' };
                lastDepositDateEl.text(date.toLocaleDateString('ar-DZ', options));
            } catch (e) {
                console.error("SMC Script: Error formatting last_tasks_deposit_timestamp:", e);
                lastDepositDateEl.text(data.last_tasks_deposit_timestamp || 'غير محدد'); // Fallback
            }
        } else if (lastDepositDateEl.length) {
            lastDepositDateEl.text('لا يوجد إيداع مهام نشط');
        }

        // --- Update "تاريخ نهاية وديعة المهام" ---
        const depositEndDateEl = $('#deposit-end-date-display');
        if (depositEndDateEl.length) {
            depositEndDateEl.text(data.tasks_deposit_end_date_str || 'N/A');
        }

        // --- Update "الأيام المتبقية للمهام" ---
        const daysRemainingEl = $('#days-remaining-display');
        if (daysRemainingEl.length && data.tasks_deposit_end_timestamp_for_calc) {
            const now = new Date();
            const endDate = new Date(data.tasks_deposit_end_timestamp_for_calc * 1000);
            if (now < endDate) {
                const diffTime = Math.abs(endDate - now);
                const diffDays = Math.floor(diffTime / (1000 * 60 * 60 * 24));
                const diffHours = Math.floor((diffTime % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const diffMinutes = Math.floor((diffTime % (1000 * 60 * 60)) / (1000 * 60));
                daysRemainingEl.text(`${diffDays} يوم : ${diffHours} ساعة : ${diffMinutes} دقيقة`);
            } else {
                daysRemainingEl.text("0 يوم : 0 ساعة : 0 دقيقة");
            }
        } else if (daysRemainingEl.length) {
            daysRemainingEl.text('N/A');
        }

        // --- Update "الحد اليومي للإعلانات" ---
        const adsWatchedDisplay = $('#ads-watched-display');
        const adLimitDisplay = $('#ad-limit-display');
        const adsProgressBar = $('#ads-progress-bar');
        const remainingAdsDisplay = $('#remaining-ads-display'); // For the "Start Tasks" button

        if (adsWatchedDisplay.length) adsWatchedDisplay.text(formatNumber(data.ads_watched_today));
        if (adLimitDisplay.length) adLimitDisplay.text(formatNumber(data.daily_ad_limit));

        if (adsProgressBar.length && data.daily_ad_limit > 0) {
            const progress = Math.round(( (data.ads_watched_today || 0) / data.daily_ad_limit) * 100);
            adsProgressBar.css('width', progress + '%');
        } else if (adsProgressBar.length) {
            adsProgressBar.css('width', '0%');
        }
        if (remainingAdsDisplay.length) {
            const remaining = Math.max(0, (data.daily_ad_limit || 0) - (data.ads_watched_today || 0));
            remainingAdsDisplay.text(formatNumber(remaining));
        }


        // --- Update "الأرباح اليومية" ---
        const dailyProfitEl = $('#daily-profit-display');
        if (dailyProfitEl.length) {
            dailyProfitEl.text(formatCurrency(data.daily_profit));
        }

        // --- Update "الأرباح الإجمالية" ---
        const totalProfitEl = $('#total-profit-display');
        if (totalProfitEl.length) {
            totalProfitEl.text(formatCurrency(data.total_profit));
        }

        // --- Update "نقاط الحضور" ---
        const pointsBalanceEl = $('#smc-dashboard-points-balance');
        if (pointsBalanceEl.length) {
            pointsBalanceEl.text(formatNumber(data.points_balance));
        }

        // --- Update Attendance Button/Status ---
        const attendanceStatusDiv = $('#smc-dashboard-attendance-status');
        if (attendanceStatusDiv.length) {
            if (data.attended_today) {
                attendanceStatusDiv.html('<p class="attendance-success-msg"><i class="fas fa-check-circle"></i> تم تسجيل حضورك لهذا اليوم.</p>');
            } else {
                // Ensure the button is present if not attended
                if (!$('#smc-dashboard-attend-button').length) {
                    attendanceStatusDiv.html(`
                        <button id="smc-dashboard-attend-button" class="smc-button attend-button-enhanced">
                            <i class="fas fa-calendar-plus"></i> تسجيل الحضور اليومي <span class="points-badge">(+10 نقاط)</span>
                        </button>
                    `);
                    // Re-attach event listener if button was re-created
                    attachAttendanceButtonListener();
                }
            }
        }
        console.log("SMC Script: Dashboard UI update complete.");
    }

    // --- Function to Fetch Dashboard Data via AJAX ---
    function fetchDashboardData() {
        if (!ajaxUrl || !generalNonce) { // Use generalNonce for dashboard data
            console.error("SMC Script: AJAX URL or Nonce for dashboard data is missing.");
            // Display a user-friendly error message on the dashboard
            if ($('.smc-dashboard-container').length > 0) {
                $('.smc-dashboard-container .smc-alert-danger').remove();
                $('.smc-dashboard-container').prepend('<div class="smc-alert smc-alert-danger" style="color: red; border: 1px solid red; padding: 10px; margin-bottom: 15px;">خطأ في تحميل إعدادات التحديث. قد لا يتم تحديث البيانات تلقائيًا.</div>');
            }
            return;
        }

        console.log("SMC Script: Fetching dashboard data...");
        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: 'fetch_dashboard_data', // Make sure this action hook exists in PHP
                nonce: generalNonce // Use the general nonce
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    console.log("SMC Script: Raw AJAX response.data for dashboard:", response.data);
                    console.log("SMC Script: Value of current_deposit from AJAX:", response.data.current_deposit);
                    console.log("SMC Script: Dashboard data received for updateDashboardUI:", response.data);
                    updateDashboardUI(response.data);
                } else {
                    console.error("SMC Script: Error fetching dashboard data from server:", response.data.message);
                    if ($('.smc-dashboard-container').length > 0) {
                        $('.smc-dashboard-container .smc-alert-danger').remove();
                        $('.smc-dashboard-container').prepend(`<div class="smc-alert smc-alert-danger" style="color: red; border: 1px solid red; padding: 10px; margin-bottom: 15px;">فشل تحديث بيانات لوحة التحكم: ${response.data.message || 'خطأ غير معروف.'}</div>`);
                    }
                     const currentDepositValueEl = $('#current-tasks-deposit-value-display');
                     if (currentDepositValueEl.length) {
                         currentDepositValueEl.html('<span style="color: red;">خطأ في التحديث</span>');
                     }
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error("SMC Script: AJAX Error fetching dashboard data:", textStatus, errorThrown, jqXHR.responseText);
                if ($('.smc-dashboard-container').length > 0) {
                    $('.smc-dashboard-container .smc-alert-danger').remove();
                    $('.smc-dashboard-container').prepend('<div class="smc-alert smc-alert-danger" style="color: red; border: 1px solid red; padding: 10px; margin-bottom: 15px;">حدث خطأ أثناء تحديث بيانات لوحة التحكم. قد تكون البيانات المعروضة غير دقيقة. يرجى المحاولة مرة أخرى لاحقاً.</div>');
                }
                const currentDepositValueEl = $('#current-tasks-deposit-value-display');
                if (currentDepositValueEl.length) {
                    currentDepositValueEl.html('<span style="color: red;">خطأ في التحميل</span>');
                }
            }
        });
    }

    // --- Initial Data Load and Periodic Update (if on dashboard page) ---
    // Check if we are on a page that needs dashboard updates
    if ($('.smc-dashboard-container').length > 0 || $('#current-tasks-deposit-value-display').length > 0) {
        console.log("SMC Script: Dashboard container found. Initializing data fetch.");
        fetchDashboardData(); // Fetch data on page load

        // Optional: Set an interval to periodically update dashboard data
        // setInterval(fetchDashboardData, 60000); // Update every 60 seconds (adjust as needed)
    } else {
        console.log("SMC Script: Not on a dashboard page, skipping initial data fetch for dashboard.");
    }


    // --- Daily Attendance Button Handler ---
    function attachAttendanceButtonListener() {
        const attendButton = $('#smc-dashboard-attend-button'); // Use jQuery selector
        if (attendButton.length) {
            attendButton.off('click').on('click', function() { // Use .off to prevent multiple bindings if re-attached
                const button = $(this); // Use jQuery object

                button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> جارٍ التسجيل...');

                $.ajax({
                    url: ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'smc_handle_daily_attendance',
                        nonce: smc_data.attendance_nonce // Ensure this nonce is correctly set in smc_data
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success', title: 'تم!', text: response.data.message,
                                timer: 2500, showConfirmButton: false
                            });
                            // Update UI directly or by re-fetching dashboard data
                            fetchDashboardData(); // Re-fetch to update all relevant parts
                        } else {
                            Swal.fire({ icon: 'error', title: 'خطأ!', text: response.data.message || 'حدث خطأ غير متوقع.' });
                            button.prop('disabled', false).html('<i class="fas fa-calendar-plus"></i> تسجيل الحضور اليومي <span class="points-badge">(+10 نقاط)</span>');
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                         Swal.fire({ icon: 'error', title: 'خطأ اتصال!', text: 'حدث خطأ في الاتصال بالخادم. يرجى المحاولة مرة أخرى.' });
                        console.error("SMC Script: AJAX Error (Attendance):", textStatus, errorThrown, jqXHR.responseText);
                        button.prop('disabled', false).html('<i class="fas fa-calendar-plus"></i> تسجيل الحضور اليومي <span class="points-badge">(+10 نقاط)</span>');
                    }
                });
            });
        }
    }
    attachAttendanceButtonListener(); // Attach listener on initial load


    // --- Date & Time Update for Dashboard ---
    function updateLiveDateTime() {
        const dateTimeElement = document.getElementById('date-time');
        if (dateTimeElement) {
            const now = new Date();
            const optionsDate = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric', timeZone: 'Africa/Algiers' };
            const optionsTime = { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true, timeZone: 'Africa/Algiers' };
            const formattedDate = now.toLocaleDateString('ar-DZ', optionsDate);
            const formattedTime = now.toLocaleTimeString('ar-DZ', optionsTime);
            dateTimeElement.textContent = `${formattedDate} - ${formattedTime}`;
        }
    }
    if ($('#date-time').length) {
        updateLiveDateTime();
        setInterval(updateLiveDateTime, 1000);
    }

    // --- Other Page-Specific Scripts (e.g., deposit_info.php, ad_deal.php) ---
    // Make sure their specific nonces are available in smc_data if they use AJAX

    // Example for deposit_info.php form submission (if it uses AJAX)
    const depositForm = $('#smc-deposit-form');
    if (depositForm.length && typeof smc_data.user_deposit_nonce !== 'undefined') {
        // ... (Your existing AJAX submission logic for deposit_info.php) ...
        // Ensure it uses smc_data.user_deposit_nonce
        // Remember to re-enable submit button in complete/error callbacks
    }

    // Example for ad_deal.php (if it uses AJAX)
    const startWatchButton = $('#start-watch-button');
    if (startWatchButton.length && typeof smc_data.start_watch_nonce !== 'undefined') {
        // ... (Your existing AJAX logic for ad_deal.php) ...
        // Ensure it uses the correct nonces like smc_data.start_watch_nonce, smc_data.complete_watch_nonce
    }

    // ... (Other existing JavaScript code from your script.js file) ...

}); // End jQuery(document).ready
