<?php
/**
 * Plugin Name: POWER CAPTCHA WordPress Integration
 * Plugin URI:  https://power-captcha.com/
 * Description: POWER CAPTCHA protects your WordPress website and WordPress forms against bots and unauthorized people. GDPR compliant!
 * Version:     0.0.1-alpha
 * Author:      POWER CAPTCHA
 * Author URI:  https://power-captcha.com/
 * Text Domain: power-captcha
 */
 
define('POWER_CAPTCHA_PATH', plugin_dir_path( __FILE__ ));
define('POWER_CAPTCHA_URL', plugin_dir_url( __FILE__ ));

// Init core
require POWER_CAPTCHA_PATH . 'core/PowerCaptcha.php';
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