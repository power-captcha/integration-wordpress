<?php
/**
 * Plugin Name: POWER CAPTCHA
 * Description: POWER CAPTCHA protects your WordPress website and WordPress forms against bots and unauthorized persons. GDPR compliant!
 * Version:     1.2.3
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * Author:      POWER CAPTCHA
 * Author URI:  https://power-captcha.com/en/
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0
 * Text Domain: power-captcha
 * Domain Path: /languages
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly

define( 'POWER_CAPTCHA_PLUGIN_VERSION', '1.2.3' );
define( 'POWER_CAPTCHA_PLUGIN_FILE', __FILE__ );
define( 'POWER_CAPTCHA_PLUGIN_DIR', __DIR__ );
define( 'POWER_CAPTCHA_PATH', plugin_dir_path( POWER_CAPTCHA_PLUGIN_FILE ) );
define( 'POWER_CAPTCHA_URL', plugin_dir_url( POWER_CAPTCHA_PLUGIN_FILE ) );

// Init core
require POWER_CAPTCHA_PATH . 'includes/class-power-captcha.php';
Power_Captcha_WP\Power_Captcha::instance();

function powercaptcha(): Power_Captcha_WP\Power_Captcha {
	return Power_Captcha_WP\Power_Captcha::instance();
}

if ( file_exists( WP_CONTENT_DIR . '/power-captcha-config.php' ) ) {
	require_once WP_CONTENT_DIR . '/power-captcha-config.php';
}
