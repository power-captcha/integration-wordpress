<?php

defined('POWER_CAPTCHA_PATH') || exit;

// note: there is only one integration setting for WordPress AND WooCommerce Lost Password.
if(powercaptcha()->is_enabled(powercaptcha()::WORDPRESS_LOST_PASSWORD_INTEGRATION)) {
    
    add_action('woocommerce_lostpassword_form', 'powercaptcha_woocommerce_lost_password_widget');

    // note:
    // the token is verified by wordpress-lost-password.php (powercaptcha_wordpress_lost_password_verification) 
    // which valid for WordPress Lost Password and WooCommerce Lost Password.
    // this is necessary because woocomerce process_lost_password function does not offer a hook to check the captcha token only for WooCommerce.
    // for this reason, there is only one setting for both integrations (WordPress and WooCommerce Lost Password).
}

function powercaptcha_woocommerce_lost_password_widget() {
    if (!powercaptcha()->is_enabled(powercaptcha()::WORDPRESS_LOST_PASSWORD_INTEGRATION)) {
        return;
    }

    echo powercaptcha_widget_html(powercaptcha()::WORDPRESS_LOST_PASSWORD_INTEGRATION, '#user_login', true, 'form-row');

    powercaptcha_enqueue_widget_script();
}