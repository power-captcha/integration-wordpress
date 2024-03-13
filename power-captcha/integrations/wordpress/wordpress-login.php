<?php

defined('POWER_CAPTCHA_PATH') || exit;

if(powercaptcha()->is_enabled(powercaptcha()::WORDPRESS_LOGIN_INTEGRATION)) {

    
    // add div for the widget after password field
    add_action('login_form', 'powercaptcha_wordpress_login_add_widget_div', 10, 2);


    // integration js
    add_action('login_form', 'powercaptcha_wordpress_login_integration_javascript');

    // token verification
    add_filter('authenticate', 'powercaptcha_wordpress_login_verification', 20, 3);
}


function powercaptcha_wordpress_login_integration_javascript() {
    if (!powercaptcha()->is_enabled(powercaptcha()::WORDPRESS_LOGIN_INTEGRATION)) {
        return;
    }

    powercaptcha_javascript_tags();
?>
<script type="text/javascript">
// TODO move this script to javascript file. note parameters like apiKey and secretKey must be injected
(function($){
    
    powerCaptchaWp.prefetchFrontendDetails('wordpress_login');

    const wpLoginForm = $('#loginform');
    const wpLoginFormId = wpLoginForm.attr('id');
        
    const usernameField = wpLoginForm.find('#user_login');

    powerCaptchaWp.withFrontendDetails('wordpress_login', function(details) {
        const captchaInstance = PowerCaptcha.init({
            apiKey: details.apiKey,
            backendUrl: details.backendUrl,
            clientUid: details.clientUid,
            widgetTarget: wpLoginForm.find('.power-captcha-widget')[0],

            userInputField: wpLoginForm.find('#user_login')[0],

            idSuffix: wpLoginFormId,
            lang: details.lang,

            invisibleMode: false, // TODO make invisibleMode configurable 
            debug: true // TODO turn off debug or make debug configurable 
        });
    });
    
})(jQuery);
</script>
<?php
}

function powercaptcha_wordpress_login_add_widget_div() { 
    // TODO control margin via css variables
?>
    <div class="power-captcha-widget" style="margin-bottom: 16px"></div>
<?php
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