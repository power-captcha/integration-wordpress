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
//require plugin_dir_path( __FILE__ ) . 'widgets.php';

// Init integrations
if(powercaptcha()->is_configured()) {
    if (powercaptcha()->is_wpforms_integration_enabled()) {
        require plugin_dir_path( __FILE__ ) . 'integrations/wpforms/wpforms.php';
    }

    if(powercaptcha()->is_wordpress_integration_enabled()) {
        require plugin_dir_path( __FILE__ ) . 'integrations/wordpress/wordpress-login.php';
    }
}

// Init admin settings
require plugin_dir_path( __FILE__ ) . 'admin/settings.php';
require plugin_dir_path( __FILE__ ) . 'admin/plugin.php';