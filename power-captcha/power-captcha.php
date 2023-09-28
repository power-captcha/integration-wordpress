<?php
/**
 * Plugin Name: POWER CAPTCHA WordPress Integration
 * Plugin URI:  https://power-captcha.com/
 * Description: POWER CAPTCHA protects your WordPress website and WordPress forms against bots and unauthorized persons. GDPR compliant!
 * Version:     0.1.6
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * Author:      POWER CAPTCHA
 * Author URI:  https://power-captcha.com/
 * Text Domain: power-captcha
 */
 
define('POWER_CAPTCHA_PLUGIN_VERSION', '0.1.6'); 
define('POWER_CAPTCHA_PLUGIN_FILE', __FILE__ );
define('POWER_CAPTCHA_PLUGIN_DIR', __DIR__ );
define('POWER_CAPTCHA_PATH', plugin_dir_path( POWER_CAPTCHA_PLUGIN_FILE ));
define('POWER_CAPTCHA_URL', plugin_dir_url( POWER_CAPTCHA_PLUGIN_FILE ));

// Init core
require POWER_CAPTCHA_PATH . 'core/PowerCaptcha.php';

if(file_exists(WP_CONTENT_DIR . '/power-captcha-config.php')) {
    require_once WP_CONTENT_DIR . '/power-captcha-config.php';
}

require POWER_CAPTCHA_PATH . 'core/functions.php';

// Init integrations
if(powercaptcha()->is_configured()) {
    foreach(powercaptcha()->get_integrations() as $key => $integration) {
        /** @var string $key */
        /** @var PowerCaptcha_WP\PowerCaptchaIntegration $integration */
        if($integration->is_enabled()) {
            foreach($integration->get_file_paths() as $file_path) {
                $full_path = POWER_CAPTCHA_PATH . $file_path;
                require_once $full_path;
            }
        }
    }
}

// Init admin settings
require POWER_CAPTCHA_PATH . 'admin/settings.php';
require POWER_CAPTCHA_PATH . 'admin/plugin.php';