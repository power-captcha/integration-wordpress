<?php
/**
 * POWER CAPTCHA WordPress Integration
 * 
 * Plugin Name: POWER CAPTCHA
 * Plugin URI:  https://power-captcha.com/
 * Description: POWER CAPTCHA protects WordPress and several WordPress form plugins against bots and hackers.
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
}

// Init admin settings
require plugin_dir_path( __FILE__ ) . 'admin/settings.php';