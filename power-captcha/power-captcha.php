<?php
/**
 * Plugin Name: POWER CAPTCHA
 * Plugin URI:  https://power-captcha.com/
 * Description: POWER CAPTCHA protects your WordPress website and WordPress forms against bots and unauthorized persons. GDPR compliant!
 * Version:     0.3.0-dev
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * Author:      POWER CAPTCHA
 * Author URI:  https://power-captcha.com/
 * Text Domain: power-captcha
 */
 
define('POWER_CAPTCHA_PLUGIN_VERSION', '0.3.0-dev'); 
define('POWER_CAPTCHA_PLUGIN_FILE', __FILE__ );
define('POWER_CAPTCHA_PLUGIN_DIR', __DIR__ );
define('POWER_CAPTCHA_PATH', plugin_dir_path( POWER_CAPTCHA_PLUGIN_FILE ));
define('POWER_CAPTCHA_URL', plugin_dir_url( POWER_CAPTCHA_PLUGIN_FILE ));

// Init core
require POWER_CAPTCHA_PATH . 'includes/class-power-captcha.php';
Power_Captcha_WP\Power_Captcha::instance();

function powercaptcha() : Power_Captcha_WP\Power_Captcha {
    return Power_Captcha_WP\Power_Captcha::instance();
}

if(file_exists(WP_CONTENT_DIR . '/power-captcha-config.php')) {
    require_once WP_CONTENT_DIR . '/power-captcha-config.php';
}



require POWER_CAPTCHA_PATH . 'admin/update-check.php';