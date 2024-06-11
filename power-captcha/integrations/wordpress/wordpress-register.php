<?php

defined('POWER_CAPTCHA_PATH') || exit;

if(powercaptcha()->is_enabled(powercaptcha()::WORDPRESS_REGISTER_INTEGRATION)) {
    
    add_action('register_form', 'powercaptcha_wordpress_register_widget');

    add_action('register_post', 'powercaptcha_wordpress_register_verification', 10, 3);
}


function powercaptcha_wordpress_register_widget() {
    if (!powercaptcha()->is_enabled(powercaptcha()::WORDPRESS_REGISTER_INTEGRATION)) {
        return;
    }

    echo powercaptcha_widget_html(powercaptcha()::WORDPRESS_REGISTER_INTEGRATION, '#user_email', true, '', 'margin-bottom: 16px');

    powercaptcha_javascript();
}


function powercaptcha_wordpress_register_verification(string $sanitized_user_login, string $user_email, WP_Error $errors) {
    if (!powercaptcha()->is_enabled(powercaptcha()::WORDPRESS_REGISTER_INTEGRATION)) {
        return;
    }

    if(empty($_POST)) {
        return;
    }

    $pcToken = powercaptcha_get_token_from_post_request();
    if($pcToken === FALSE) {
        $errors->add(powercaptcha()::ERROR_CODE_NO_TOKEN_FIELD, powercaptcha_user_error_message(powercaptcha()::ERROR_CODE_NO_TOKEN_FIELD));
    } else {
        $verification = powercaptcha_verify_token($pcToken, $user_email, null, powercaptcha()::WORDPRESS_REGISTER_INTEGRATION);
        if($verification['success'] !== TRUE) {
            $errors->add($verification['error_code'], powercaptcha_user_error_message($verification['error_code']));
        }
    }

    return $errors;
}