<?php

defined('POWER_CAPTCHA_PATH') || exit;

if(powercaptcha()->is_enabled(powercaptcha()::WORDPRESS_LOGIN_INTEGRATION)) {
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
jQuery(function($){
    
    powerCaptchaWp.prefetchFrontendDetails('wordpress_login');

    (function ($) {
        const wpLoginForm = $('#loginform');
        const wpLoginFormId = wpLoginForm.attr('id');
        
        // append hidden input for token
        wpLoginForm.append('<input type="hidden" name="pc-token" value =""/>');
        
        // create instance for the login form
        const captchaInstance = window.uiiCaptcha.captcha({idSuffix: wpLoginFormId, lang: powerCaptchaWp.getLang()});

        // register submit listener
        wpLoginForm.on('submit', function (event) {
            console.debug('submitEvent for wpLoginForm', '#'+wpLoginFormId);

            const tokenField = wpLoginForm.find('input[name="pc-token"]').eq(0);
            if(tokenField.length === 0) {
                console.warn('no pc-token field found in wpLoginForm.');
                return; // exit
            }

            if(tokenField.val() === "") {
                console.debug('pc-token field empty. preventing form submit and requesting token.');
                event.preventDefault();

                const userNameField = wpLoginForm.find('#user_login').eq(0);
                console.debug('userNameField val', userNameField.val());
                const userName = userNameField.val();

                powerCaptchaWp.withFrontendDetails('wordpress_login', function(details) {
                    // requesting token
                    captchaInstance.check({
                        apiKey: details.apiKey,
                        backendUrl: details.backendUrl,
                        clientUid: details.clientUid,
                        user: userName,
                        callback: ''
                    }, 
                    function(token) {
                        console.debug('captcha solved with token: '+token+'. setting value to tokenField.');
                        tokenField.val(token);
                        console.debug('resubmitting wpLoginForm.');
                        wpLoginForm.trigger("submit");
                    });
                });
            } else {
                console.debug('pc-token already set. no token has to be requested. wpLoginForm can be submitted.');
            }
        });
    }($));
});
</script>
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