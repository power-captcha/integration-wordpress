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
 


// Init core
require plugin_dir_path( __FILE__ ) . 'core/PowerCaptcha.php';
require plugin_dir_path( __FILE__ ) . 'core/functions.php';

// Init integrations
if(powercaptcha()->is_configured()) {
    foreach(powercaptcha()->get_integrations() as $key => $integration) {
        /** @var string $key */
        /** @var PowerCaptcha_WP\PowerCaptchaIntegration $integration */
        if($integration->is_enabled()) {
            $full_path = plugin_dir_path( __FILE__ ) . $integration->get_file_path();
            require_once $full_path;
        }
    }
}

// Init admin settings
require plugin_dir_path( __FILE__ ) . 'admin/settings.php';
require plugin_dir_path( __FILE__ ) . 'admin/plugin.php';