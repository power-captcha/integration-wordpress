<?php

defined('POWER_CAPTCHA_PATH') || exit;

if(powercaptcha()->is_enabled(powercaptcha()::WOOCOMMERCE_LOGIN_INTEGRATION)) {
    
    add_action('woocommerce_login_form', 'powercaptcha_woocommerce_login_widget');

    add_filter('woocommerce_process_login_errors', 'powercaptcha_woocommerce_login_verification', 20, 3);
}

function powercaptcha_woocommerce_login_widget() {
    if (!powercaptcha()->is_enabled(powercaptcha()::WOOCOMMERCE_LOGIN_INTEGRATION)) {
        return;
    }

    echo powercaptcha_widget_html(powercaptcha()::WOOCOMMERCE_LOGIN_INTEGRATION, '#username', true, 'form-row');

    powercaptcha_javascript();
}

function powercaptcha_woocommerce_login_verification(WP_Error $validation_error, string $user_login, string $user_passsword) {
    if (!powercaptcha()->is_enabled(powercaptcha()::WOOCOMMERCE_LOGIN_INTEGRATION)) {
        return $validation_error;
    }

    if(powercaptcha()->is_enabled(powercaptcha()::WORDPRESS_LOGIN_INTEGRATION)) {
        // If the WordPress login integration is enabled, the token will be verified later in the 'authenticate' filter, 
        // because WoCommerce uses the wp_signon method that calls the 'authenticate' filter.
        // Therefore, we can stop at this point.
        return $validation_error;
    }

    if(empty($_POST)) {
        return $validation_error;
    }

    $pcToken = powercaptcha_get_token_from_post_request();
    if($pcToken === FALSE) {
        $validation_error->add(powercaptcha()::ERROR_CODE_NO_TOKEN_FIELD, powercaptcha_user_error_message(powercaptcha()::ERROR_CODE_NO_TOKEN_FIELD, false));
    } else {
        $verification = powercaptcha_verify_token($pcToken, $_POST['username'], null, powercaptcha()::WOOCOMMERCE_LOGIN_INTEGRATION);
        if($verification['success'] !== TRUE) {
            $validation_error->add($verification['error_code'], powercaptcha_user_error_message($verification['error_code'], false));
        }
    }

    return $validation_error;
}