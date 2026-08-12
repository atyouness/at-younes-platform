<?php
/** Enable W3 Total Cache */
define('WP_CACHE', true); // Added by W3 Total Cache



//Begin Really Simple Security session cookie settings
@ini_set('session.cookie_httponly', true);
@ini_set('session.cookie_secure', true);
@ini_set('session.use_only_cookies', true);
//END Really Simple Security cookie settings
//Begin Really Simple Security key
define('RSSSL_KEY', '9hPgMubCAaKwRbIhIijB7J174VktMZALv6u3KSkQvkRIAXhHXjVld2xfITRfVvQ5');
//END Really Simple Security key
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the web site, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://wordpress.org/documentation/article/editing-wp-config-php/
 *
 * @package WordPress
 */
// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'if0_38432403_969' );
/** Database username */
define( 'DB_USER', '38432403_4' );
/** Database password */
define( 'DB_PASSWORD', '(p8S30j6.S' );
/** Database hostname */
define( 'DB_HOST', 'sql300.byetcluster.com' );
/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );
/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );
/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         'c3qkpja5krstbw74s9aguoxvobqfrqtpz2mh7cgfo9vms7tmfd6xokscycdbbhrg' );
define( 'SECURE_AUTH_KEY',  'piy6omxklkw1eb58brdqypjazx27eekqvqukzgtvryu0cd0ztuqdxqgnvfe5fchy' );
define( 'LOGGED_IN_KEY',    'qkbutflbv3v2hrqcl8tpupmowdmftkflzx3kj8uz2sfqh4zvb1mxaqhhhaelnzdp' );
define( 'NONCE_KEY',        'cebndyiaciyapufxv5zg1v5m31nztoyoxovaen4lmpkpfbgul7z8etvcdzbraudu' );
define( 'AUTH_SALT',        'kialvav5d9tmiwj4we64ru2a6tiv7wtjwtoqu8k8yb8yjezgrfqolfqwhpf6pgau' );
define( 'SECURE_AUTH_SALT', 'hrcdmagmqs2b7nd3fusx9l70ixuq67idvtk0vdndbysyjosrnol7cbipxho8zbl2' );
define( 'LOGGED_IN_SALT',   'sbltxprsoewajc7ln2mpkm1ls657agbiq0h5ne5rdqeujcmyscgowid0fcpsgjc4' );
define( 'NONCE_SALT',       'cwoaakfm3arzic1qj9opxxoj3zgtjtfglfskribmgtzb4cswkzoiabtvag36731k' );
/**#@-*/
/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wpwn_';
/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://wordpress.org/documentation/article/debugging-in-wordpress/
 */
define( 'WP_DEBUG', true ); // تفعيل وضع تصحيح الأخطاء
define( 'WP_DEBUG_LOG', true ); // لتسجيل الأخطاء في ملف
define( 'WP_DEBUG_DISPLAY', false ); // لمنع عرض الأخطاء على الشاشة للزوار
@ini_set( 'display_errors', 0 ); // تأكيد إضافي لمنع العرض

define( 'WP_AUTO_UPDATE_CORE', 'minor' );
/* Add any custom values between this line and the "stop editing" line. */
define( 'SURECART_ENCRYPTION_KEY', 'qkbutflbv3v2hrqcl8tpupmowdmftkflzx3kj8uz2sfqh4zvb1mxaqhhhaelnzdp' );
/* That's all, stop editing! Happy publishing. */
/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}
/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
