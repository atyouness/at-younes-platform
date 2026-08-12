<?php
/**
 * Theme Functions.
 * Acts as a loader for separated function files.
 *
 * @package SMC Group DZ Child
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Define Theme Directory Path Constant
define('SMC_CHILD_INC_DIR', get_stylesheet_directory() . '/inc/');

// Load separated function files
require_once SMC_CHILD_INC_DIR . '1-constants.php';
require_once SMC_CHILD_INC_DIR . '2-enqueue.php';
require_once SMC_CHILD_INC_DIR . '3-database.php';
require_once SMC_CHILD_INC_DIR . '4-helpers.php'; // Load helpers before files that might use them
require_once SMC_CHILD_INC_DIR . '9-cron.php'; // Include the new cron file
require_once SMC_CHILD_INC_DIR . '5-admin-settings.php';
require_once SMC_CHILD_INC_DIR . '6-ajax-handlers.php';
require_once SMC_CHILD_INC_DIR . '7-hooks.php';
require_once SMC_CHILD_INC_DIR . '8-shortcodes.php';

// Allow shortcodes in text widgets
add_filter('widget_text', 'do_shortcode');

?>
