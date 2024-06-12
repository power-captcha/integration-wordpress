<?php

defined('POWER_CAPTCHA_PATH') || exit;

if(powercaptcha()->is_enabled(powercaptcha()::WOOCOMMERCE_CHECKOUT_INTEGRATION)) {
    
    add_action('woocommerce_after_checkout_billing_form', 'powercaptcha_woocommerce_checkout_widget', 10, 0);

    add_action('woocommerce_after_checkout_validation', 'powercaptcha_woocommerce_checkout_verification', 10, 2);
    // Note: We can't use the woocommerce_before_checkout_validation hook because it is executed multiple times during the checkout.
    // Another hook alternative could be woocommerce_checkout_process, but woocommerce_after_checkout_validation seems to be the most suitable, 
    // as it is executed after the address and payment method have been validated.
}

function powercaptcha_woocommerce_checkout_widget() {
    if (!powercaptcha()->is_enabled(powercaptcha()::WOOCOMMERCE_CHECKOUT_INTEGRATION)) {
        return;
    }

    echo powercaptcha_widget_html(powercaptcha()::WOOCOMMERCE_CHECKOUT_INTEGRATION, '#billing_email', true, 'form-row');

    powercaptcha_enqueue_widget_script();
}

function powercaptcha_woocommerce_checkout_verification(array $fields, WP_Error $errors) {
    if (!powercaptcha()->is_enabled(powercaptcha()::WOOCOMMERCE_CHECKOUT_INTEGRATION)) {
        return;
    }

    $pcToken = powercaptcha_get_token_from_post_request();
    if($pcToken === FALSE) {
        $errors->add(powercaptcha()::ERROR_CODE_NO_TOKEN_FIELD, powercaptcha_user_error_message(powercaptcha()::ERROR_CODE_NO_TOKEN_FIELD));
    } else {
        $verification = powercaptcha_verify_token($pcToken, $_POST['billing_email'], null, powercaptcha()::WOOCOMMERCE_CHECKOUT_INTEGRATION);
        if($verification['success'] !== TRUE) {
            $errors->add($verification['error_code'], powercaptcha_user_error_message($verification['error_code']));
        }
    }
}