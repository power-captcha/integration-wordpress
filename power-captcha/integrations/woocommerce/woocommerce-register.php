<?php

defined('POWER_CAPTCHA_PATH') || exit;

if(powercaptcha()->is_enabled(powercaptcha()::WOOCOMMERCE_REGISTER_INTEGRATION)) {
    
    add_action('woocommerce_register_form', 'powercaptcha_woocommerce_register_widget', 100, 0);

    add_filter('woocommerce_process_registration_errors', 'powercaptcha_woocommerce_register_verification', 10, 4);
    // Note: We can't use the woocommerce_register_post hook because it is also executed during checkout when registering. 
    // This would lead to problems if the captcha is also enabled for the WooCommerce checkout.
}


function powercaptcha_woocommerce_register_widget() {
    if (!powercaptcha()->is_enabled(powercaptcha()::WOOCOMMERCE_REGISTER_INTEGRATION)) {
        return;
    }

    echo powercaptcha_widget_html(powercaptcha()::WOOCOMMERCE_REGISTER_INTEGRATION, '#reg_email', true, 'form-row');

    powercaptcha_enqueue_widget_script();
}

function powercaptcha_woocommerce_register_verification(WP_Error $validation_error, $username, $password, $email) {
    if (!powercaptcha()->is_enabled(powercaptcha()::WOOCOMMERCE_REGISTER_INTEGRATION)) {
        return $validation_error;
    }

    if(empty($_POST)) {
        return $validation_error;
    }

    $pcToken = powercaptcha_get_token_from_post_request();
    if($pcToken === FALSE) {
        $validation_error->add(powercaptcha()::ERROR_CODE_NO_TOKEN_FIELD, powercaptcha_user_error_message(powercaptcha()::ERROR_CODE_NO_TOKEN_FIELD, false));
    } else {
        $verification = powercaptcha_verify_token($pcToken, $_POST['email'], null, powercaptcha()::WOOCOMMERCE_REGISTER_INTEGRATION);
        if($verification['success'] !== TRUE) {
            $validation_error->add($verification['error_code'], powercaptcha_user_error_message($verification['error_code'], false));
        }
    }

    return $validation_error;
}