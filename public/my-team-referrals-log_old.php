<?php
/**
 * Template Name: My Team Referrals Log
 * Description: Displays the referral log for the current user's team.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

get_header();

global $wpdb;
$table_name_extended = $wpdb->prefix . 'smc_user_extended_data'; // Define custom table name
$meta_key_referred_by = defined('SMC_REFERRED_BY') ? SMC_REFERRED_BY : 'smc_referred_by'; // Use constant if defined

?>

<div class="container smc-log-container">
    <?php if ( is_user_logged_in() ) :
        $current_user_id = get_current_user_id();
        $user_info = get_userdata( $current_user_id );
        $user_data = null; // Initialize as null
        $referral_code = null; // Initialize as null
        $referral_link = 'N/A'; // Initialize as N/A
        $referrer_of_current_user = null; // Initialize referrer info

        // Fetch SMC specific data only if function exists
        if (function_exists('smc_get_user_data')) {
            $fetched_data = smc_get_user_data( $current_user_id ); // Fetch into temp variable

            // *** بداية التعديل: التحقق من أن البيانات المسترجعة هي مصفوفة ***
            if (is_array($fetched_data)) {
                $user_data = $fetched_data; // Assign if it's an array
                $referral_code = $user_data['referral_code'] ?? null; // Use null coalescing
                $referral_link = ! empty( $referral_code ) ? home_url( '/register/?ref=' . $referral_code ) : 'N/A';
            } else { // *** إذا لم تكن البيانات مصفوفة ***
                 error_log("SMC Warning: smc_get_user_data() returned non-array in my-team-referrals-log.php for user ID: " . $current_user_id);
                 // Fallback: Try getting meta directly
                 $referral_code = get_user_meta($current_user_id, SMC_REFERRAL_CODE, true);
                 if (empty($referral_code)) {
                     $referral_code = null; // Ensure it's null if empty
                     $referral_link = 'N/A';
                 } else {
                     $referral_link = home_url( '/register/?ref=' . $referral_code );
                 }
            }

            // *** نهاية التعديل ***
        } else {
            error_log("SMC Error: Function smc_get_user_data() not found in my-team-referrals-log.php");
             // Fallback: Try getting meta directly
             $referral_code = get_user_meta($current_user_id, SMC_REFERRAL_CODE, true);
             if (empty($referral_code)) {
                 $referral_code = null; // Ensure it's null if empty
                 $referral_link = 'N/A';
             } else {
                 $referral_link = home_url( '/register/?ref=' . $referral_code );
             }
        }

        // --- Fetch the referrer of the current user ---
        $referred_by_id = get_user_meta($current_user_id, SMC_REFERRED_BY, true);
        if ($referred_by_id && is_numeric($referred_by_id) && $referred_by_id > 0) {
            $referrer_of_current_user = get_userdata($referred_by_id);
        }
        // --- End Fetching referrer ---




        // Get current user's full name
        $first_name = get_user_meta( $current_user_id, 'first_name', true );
        $last_name = get_user_meta( $current_user_id, 'last_name', true );
        $full_name = trim( $first_name . ' ' . $last_name );
        if ( empty( $full_name ) && $user_info ) { // Check if $user_info exists
            $full_name = $user_info->display_name ?: $user_info->user_login;
        } elseif (empty($full_name)) {
            $full_name = 'المستخدم الحالي'; // Fallback name
        }


        // Fetch users referred by the current user
        $args_referred = array(
            'meta_key'   => $meta_key_referred_by,
            'meta_value' => $current_user_id,
            'fields'     => 'all', // Get full user objects
            'orderby'    => 'user_registered', // Order by registration date
            'order'      => 'DESC' // Newest first
        );
        $referred_users = get_users( $args_referred );

        // Get the referrer object (current user) once before the loop
        $referrer = $user_info; // Already fetched as $user_info

    ?>
        <h2><i class="fas fa-users"></i> سجل إحالات فريقي</h2>
        <a href="<?php echo esc_url( home_url( '/transactional/' ) ); ?>" class="smc-button smc-button-secondary" style="margin-bottom: 15px; display: inline-block;"><i class="fas fa-arrow-left"></i> العودة إلى معاملاتي</a>


        <!-- User Info Section -->
        <div class="smc-user-info-section" style="margin-bottom: 20px; padding: 15px; background-color: #e9f5e9; border: 1px solid #c8e6c9; border-radius: 5px;">
            <h4>معلومات الداعي (أنت)</h4>
            <p><strong>الاسم الكامل:</strong> <?php echo esc_html( $full_name ); ?></p>
            <?php if ($user_info): // Check if user_info is valid ?>
                <p><strong>اسم المستخدم:</strong> <?php echo esc_html( $user_info->user_login ); ?></p>
            <?php endif; ?>
            <p><strong>معرف الدعوة الخاص بك:</strong>
                <?php if ($referral_code): ?>
                    <strong style="color: #007bff;"><?php echo esc_html( $referral_code ); ?></strong>
                <?php else: ?>
                    <span style="color: #dc3545;">لم يتم إنشاؤه بعد</span>
                    <?php // Optionally add a button/link to generate it if needed
                          // echo ' <button id="generate-ref-code">إنشاء الكود</button>';
                    ?>
                <?php endif; ?>
            </p>
            <?php if ( $referral_link !== 'N/A' ) : ?>
                <?php // *** إضافة: عرض معلومات الداعي الحالي *** ?>
                <p><strong>معرف الشخص الذي دعاك:</strong>
                    <?php if ($referrer_of_current_user): ?>
                        <strong style="color: #007bff;"><?php echo esc_html($referrer_of_current_user->user_login); ?> (ID: <?php echo esc_html($referrer_of_current_user->ID); ?>)</strong>
                    <?php else: ?>
                        <span style="color: #6c757d;">لا يوجد داعي مسجل لك.</span>
                    <?php endif; ?></p>
                <p><strong>رابط الدعوة:</strong></p>
                <div style="display: flex; align-items: center; gap: 5px;">
                    <input type="text" id="referral-link-input" value="<?php echo esc_attr( $referral_link ); ?>" readonly style="flex-grow: 1; padding: 8px; border: 1px solid #ccc; border-radius: 4px; background-color: #f8f9fa;">
                    <button id="copy-referral-link-button" class="smc-button smc-button-secondary" style="padding: 8px 12px; white-space: nowrap;">
                        <i class="fas fa-copy"></i> نسخ
                    </button>
                </div>
                <span id="copy-status" style="font-size: 0.9em; color: green; display: none; margin-top: 5px;">تم النسخ!</span>
            <?php endif; ?>
        </div>

        <!-- Referrals Table Section -->
        <section class="smc-log-section">
            <h4>الأعضاء الذين دعوتهم (<?php echo count($referred_users); ?>)</h4> <?php // Display count ?>

            <?php
            // Removed require_once( 'wp-load.php' ); - Not needed here
            // Removed the separate user count query as we already have the count from get_users()
            ?>

            <div class="table-responsive">
                <table id="my-team-referrals-table" class="display compact stripe hover order-column smc-log-table" style="width:100%">
                    <thead>
                        <tr>
                            <th>اسم المدعو</th>
                            <th>المدعو (اسم المستخدم)</th>
                            <th>معرف المدعو</th>
                            <th>تاريخ التسجيل</th> <?php // Column 4 (Index 3) ?>
                            <th>الرتبة</th> <?php // Column 5 (Index 4) - Added ?>
                            <th>عرض</th> <?php // Column 6 (Index 5) - Added ?>
                            <th>عدد أعضاء الفريق</th> <?php // Column 7 (Index 6) - New ?>
                            <th>فريق</th> <?php // Column 7 (Index 6) - Added ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ( ! empty( $referred_users ) ) { // Check the correct variable
                            foreach ( $referred_users as $referred_user ) {
                                // Fetch extended data for the referred user
                                // Keep fetching extended data for Gender and Country, even if not displayed in the main table,
                                // as they are still in the HTML structure (though hidden by DataTables column visibility if needed).
                                $extended_data = $wpdb->get_row(
                                    $wpdb->prepare(
                                        "SELECT place_birth, mobile_number, gender, country FROM {$table_name_extended} WHERE user_id = %d",
                                        $referred_user->ID
                                    ),
                                    ARRAY_A
                                );

                                // Prepare data for the row
                                $referred_full_name = trim( ($referred_user->first_name ?? '') . ' ' . ($referred_user->last_name ?? '') );
                                if (empty($referred_full_name)) {
                                    $referred_full_name = $referred_user->display_name ?: $referred_user->user_login;
                                }

                                $reg_date = $referred_user->user_registered ? date_i18n('Y-m-d', strtotime($referred_user->user_registered)) : 'N/A';
                                // $mobile = $extended_data['mobile_number'] ?? '';
                                $gender = $extended_data['gender'] ?? '';
                                $country = $extended_data['country'] ?? '';
                                // $email = $referred_user->user_email ?? '';
                                $invitee_id = $referred_user->ID; // Get the ID of the referred user
                                $invitee_username = $referred_user->user_login;

                                // *** Get Rank using helper function ***
                                $referred_user_rank = 'VIP0'; // Default
                                if (function_exists('smc_get_user_rank')) {
                                    $referred_user_rank = smc_get_user_rank($referred_user->ID);
                                }
                                
                                // *** Get Team Member Count for the referred user (up to 3 levels) ***
                                $team_member_count = 0;
                                if (function_exists('smc_get_referral_downline_recursive') && function_exists('smc_count_downline_members_recursive')) {
                                    $referred_user_downline = smc_get_referral_downline_recursive($referred_user->ID, 3);
                                    $team_member_count = smc_count_downline_members_recursive($referred_user_downline);
                                }

                                // --- Get the specific invitation code used by *this* referred user ---
                                // This requires a different approach, maybe storing the code used during registration
                                // For now, we'll display the referrer's code, but ideally, you'd store the used code.
                                // $invitation_code_used = get_user_meta($referred_user->ID, 'smc_invitation_code_used', true) ?: 'N/A';


                                echo '<tr>';
                                echo '<td>' . esc_html( $referred_full_name ) . '</td>';
                                echo '<td>' . esc_html( $invitee_username ) . '</td>';
                                echo '<td>' . esc_html( $invitee_id ) . '</td>'; // Display Invitee ID
                                echo '<td>' . esc_html( $reg_date ) . '</td>'; // Index 3
                                echo '<td>' . esc_html( $referred_user_rank ) . '</td>'; // Index 4 - Added Rank
                                // Index 5 - Added View Button
                                echo '<td>' . esc_html( $team_member_count ) . '</td>'; // Index 6 - New Team Count Column
                                echo '<td><a href="' . esc_url(home_url('/user/' . $invitee_username . '/')) . '" class="smc-button smc-button-view" target="_blank"><i class="fas fa-eye"></i> عرض</a></td>';
                                // Index 6 - Added Team Button
                                // Note: The target page for "Team" needs to be implemented or modified
                                // Link to the new user-facing downline tree page
                                $team_link_url = esc_url(home_url('/user-downline-tree/?view_user_id=' . $invitee_id));
                                echo '<td><a href="' . $team_link_url . '" class="smc-button smc-button-team" target="_blank"><i class="fas fa-users"></i> فريق</a></td>';

                                // Keep these columns in HTML but hide them via DataTables columnDefs if not needed
                                // This allows them to be included in exports if desired.
                                // echo '<td>' . esc_html( $mobile ) . '</td>'; // Removed from display
                                // echo '<td>' . esc_html( $email ) . '</td>'; // Removed from display
                                // echo '<td>' . esc_html( $gender ) . '</td>';
                                // echo '<td>' . esc_html( $country ) . '</td>';
                            }
                        } else {
                            $column_count = 8; // Adjusted column count (7 visible + 1 new)
                            echo '<tr><td colspan="' . $column_count . '">لم تقم بدعوة أي أعضاء بعد.</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </section>

    <?php else : ?>
        <p>يرجى تسجيل الدخول لعرض سجل إحالات فريقك.</p>
        <?php // Optional: Display login form
              echo do_shortcode('[ultimatemember form_id="217"]'); // Make sure 217 is your login form ID
        ?>
    <?php endif; ?>
</div>

<?php // Add DataTables and Copy Button JS ?>
<script type="text/javascript">
jQuery(document).ready(function($) {
    // DataTables Initialization
    if ($.fn.DataTable) {
        try {
            $('#my-team-referrals-table').DataTable({
                responsive: true,
                // Add Buttons if needed (ensure JS libraries are loaded via functions.php)
                dom: 'Bfrtip', // Show Buttons, Filter, length change, processing, table, info, pagination
                buttons: [
                    'copy', 'csv', 'excel', 'pdf', 'print'
                ], // Ensure these JS files are enqueued in functions.php
                 language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/ar.json',
                    search: "بحث في الأعضاء:"
                },
                order: [[ 3, "desc" ]], // Default sort by registration date descending (Index 3)
                columnDefs: [
                    { targets: [5, 6, 7], orderable: false, searchable: false }, // Disable sorting/searching on 'عرض', 'عدد أعضاء الفريق', and 'فريق' columns (Indices 5, 6, 7)
                    // Optional: Hide columns that were removed from display but kept in HTML
                    // { targets: [7, 8], visible: false, searchable: false } // Assuming Gender (7) and Country (8) are the last two columns
                    // Adjust indices based on final HTML structure
                    // { targets: [8, 9], visible: false } // No longer applicable as Gender/Country removed from HTML output
                },
            });
        } catch (e) {
            console.error("Error initializing DataTables:", e);
        }
    } else {
        console.warn("DataTables library not found.");
    }

    // Copy Referral Link Button
    const copyButton = document.getElementById('copy-referral-link-button');
    const linkInput = document.getElementById('referral-link-input');
    const copyStatus = document.getElementById('copy-status');

    if (copyButton && linkInput) {
        copyButton.addEventListener('click', function() {
            linkInput.select(); // Select the text
            linkInput.setSelectionRange(0, 99999); // For mobile devices

            try {
                // Use Clipboard API
                navigator.clipboard.writeText(linkInput.value).then(function() {
                    // Success feedback
                    if (copyStatus) {
                        copyStatus.style.display = 'inline';
                        setTimeout(() => { copyStatus.style.display = 'none'; }, 2000); // Hide after 2 seconds
                    }
                    // Optional: Change button text temporarily
                    const originalText = copyButton.innerHTML;
                    copyButton.innerHTML = '<i class="fas fa-check"></i> تم النسخ';
                    setTimeout(() => { copyButton.innerHTML = originalText; }, 2000);

                }, function(err) {
                    // Error feedback (less common with modern browsers)
                    console.error('Async: Could not copy text: ', err);
                    alert('فشل نسخ الرابط تلقائيًا. يرجى نسخه يدويًا.');
                });
            } catch (err) {
                 // Fallback for older browsers (less reliable)
                try {
                    document.execCommand('copy');
                    if (copyStatus) {
                        copyStatus.style.display = 'inline';
                        setTimeout(() => { copyStatus.style.display = 'none'; }, 2000);
                    }
                     const originalText = copyButton.innerHTML;
                    copyButton.innerHTML = '<i class="fas fa-check"></i> تم النسخ';
                    setTimeout(() => { copyButton.innerHTML = originalText; }, 2000);
                } catch (execErr) {
                    console.error('Fallback: Oops, unable to copy', execErr);
                    alert('فشل نسخ الرابط تلقائيًا. يرجى نسخه يدويًا.');
                }
            }
        });
    }
});
</script>

<?php get_footer(); ?>

<style>
/* Add styles if needed, or ensure they are in your main stylesheet */
.smc-button-secondary {
    background-color: #6c757d;
    border-color: #6c757d;
    color: white;
    padding: 5px 10px;
    text-decoration: none;
    border-radius: 4px;
    display: inline-flex; /* Use flex for icon alignment */
    align-items: center;
    justify-content: center;
    vertical-align: middle;
    cursor: pointer;
    transition: background-color 0.3s ease;
}
.smc-button-secondary:hover {
    background-color: #5a6268;
    border-color: #545b62;
    color: white;
}
.smc-button-secondary i {
    margin-left: 5px; /* Adjust icon spacing */
}
/* Styles for new buttons */
.smc-button-view, .smc-button-team {
    background-color: #007bff; /* Blue */
    border-color: #007bff;
    color: white;
    padding: 5px 10px;
    text-decoration: none;
    border-radius: 4px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 0.9em;
    margin: 2px; /* Add margin between buttons */
}




/* DataTables styles if not globally defined */
.dt-buttons .dt-button { /* ... */ }
.dataTables_filter label { /* ... */ }
.dataTables_filter input { /* ... */ }
</style>
