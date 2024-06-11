<?php

defined('POWER_CAPTCHA_PATH') || exit;

if(powercaptcha()->is_enabled(powercaptcha()::WORDPRESS_LOGIN_INTEGRATION)) {
    
    add_action('login_form', 'powercaptcha_wordpress_login_widget', 10, 0);

    add_filter('authenticate', 'powercaptcha_wordpress_login_verification', 20, 3);
}


function powercaptcha_wordpress_login_widget() {
    if (!powercaptcha()->is_enabled(powercaptcha()::WORDPRESS_LOGIN_INTEGRATION)) {
        return;
    }

    echo powercaptcha_widget_html(powercaptcha()::WOOCOMMERCE_LOGIN_INTEGRATION, '#username', false, '', 'margin-bottom: 16px');

    powercaptcha_javascript();
}

function powercaptcha_wordpress_login_verification(null|WP_User|WP_Error $user, string $username, string $password) {
    if (!powercaptcha()->is_enabled(powercaptcha()::WORDPRESS_LOGIN_INTEGRATION)) {
        return $user;
    }

    if(empty($_POST)) {
        return $user;
    }

    $pcToken = powercaptcha_get_token_from_post_request();
    if($pcToken === FALSE) {
        return new WP_Error(powercaptcha()::ERROR_CODE_NO_TOKEN_FIELD, powercaptcha_user_error_message(powercaptcha()::ERROR_CODE_NO_TOKEN_FIELD));
    } else {
        $verification = powercaptcha_verify_token($pcToken, $username, null, powercaptcha()::WORDPRESS_LOGIN_INTEGRATION);
        if($verification['success'] !== TRUE) {
            return new WP_Error($verification['error_code'], powercaptcha_user_error_message($verification['error_code']));
        } else {
            return $user;
        }
    }
}