<?php
/**
 * Power Captcha
 * 
 * Plugin Name: Power Captcha
 * Plugin URI:  https://power-captcha.com/TODO
 * Description: TODO
 * Version:     0.0.1-alpha
 * Author:      Uniique AG
 * Author URI:  https://power-captcha.com
 * Text Domain: power-captcha
 * License:     (TODO)
 * License URI: (TODO)
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