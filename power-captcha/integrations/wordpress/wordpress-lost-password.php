<?php

defined('POWER_CAPTCHA_PATH') || exit;

if(powercaptcha()->is_enabled(powercaptcha()::WORDPRESS_LOST_PASSWORD_INTEGRATION)) {

    add_action('lostpassword_form', 'powercaptcha_wordpress_lost_password_widget');

    add_action('lostpassword_post', 'powercaptcha_wordpress_lost_password_verification', 10, 2);
}


function powercaptcha_wordpress_lost_password_widget() {
    if (!powercaptcha()->is_enabled(powercaptcha()::WORDPRESS_LOST_PASSWORD_INTEGRATION)) {
        return;
    }

    echo powercaptcha_widget_html(powercaptcha()::WORDPRESS_LOST_PASSWORD_INTEGRATION, '#user_login', true, '', 'margin-bottom: 16px');

    powercaptcha_enqueue_widget_script();
}


function powercaptcha_wordpress_lost_password_verification(WP_Error $errors, WP_User|false $user_data) {
    if (!powercaptcha()->is_enabled(powercaptcha()::WORDPRESS_LOST_PASSWORD_INTEGRATION)) {
        return;
    }

    if(empty($_POST)) {
        return;
    }

    $pcToken = powercaptcha_get_token_from_post_request();
    if($pcToken === FALSE) {
        $errors->add(powercaptcha()::ERROR_CODE_NO_TOKEN_FIELD, powercaptcha_user_error_message(powercaptcha()::ERROR_CODE_NO_TOKEN_FIELD));
    } else {
        $pcUserName = $_POST['user_login'];
        $verification = powercaptcha_verify_token($pcToken, $pcUserName, null, powercaptcha()::WORDPRESS_LOST_PASSWORD_INTEGRATION);
        if($verification['success'] !== TRUE) {
            $errors->add($verification['error_code'], powercaptcha_user_error_message($verification['error_code']));
        }
    }
}