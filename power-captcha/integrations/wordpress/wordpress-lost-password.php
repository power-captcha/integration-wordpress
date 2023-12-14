<?php

defined('POWER_CAPTCHA_PATH') || exit;

if(powercaptcha()->is_enabled(powercaptcha()::WORDPRESS_LOST_PASSWORD_INTEGRATION)) {
    // integration js
    add_action('lostpassword_form', 'powercaptcha_wordpress_lost_password_integration_javascript');

    // token verification
    add_action('lostpassword_post', 'powercaptcha_wordpress_lost_password_verification', 10, 2);
}


function powercaptcha_wordpress_lost_password_integration_javascript() {
    if (!powercaptcha()->is_enabled(powercaptcha()::WORDPRESS_LOST_PASSWORD_INTEGRATION)) {
        return;
    }

    powercaptcha_javascript_tags();
?>
<script type="text/javascript">
// TODO move this script to javascript file. note parameters like apiKey and secretKey must be injected
jQuery(function($){

    powerCaptchaWp.prefetchFrontendDetails('wordpress_lost_password');

    (function ($) {
        const wpLostPasswordForm = $('#lostpasswordform');
        const wpLostPasswordFormId = wpLostPasswordForm.attr('id');
        
        // append hidden input for token
        wpLostPasswordForm.append('<input type="hidden" name="pc-token" value =""/>');
        
        // create instance for the lost password form
        const captchaInstance = window.uiiCaptcha.captcha({idSuffix: wpLostPasswordFormId, lang: powerCaptchaWp.getLang()});

        // register submit listener
        wpLostPasswordForm.on('submit', function (event) {
            console.debug('submitEvent for wpLostPasswordForm', '#'+wpLostPasswordFormId);

            const tokenField = wpLostPasswordForm.find('input[name="pc-token"]').eq(0);
            if(tokenField.length === 0) {
                console.warn('no pc-token field found in wpLostPasswordForm.');
                return; // exit
            }

            if(tokenField.val() === "") {
                console.debug('pc-token field empty. preventing form submit and requesting token.');
                event.preventDefault();

                const userNameField = wpLostPasswordForm.find('#user_login').eq(0);
                console.debug('userNameField val', userNameField.val());
                const userName = userNameField.val();

                powerCaptchaWp.withFrontendDetails('wordpress_lost_password', function(details) {
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
                        console.debug('resubmitting wpLostPasswordForm.');
                        wpLostPasswordForm.trigger("submit");
                    });
                });
            } else {
                console.debug('pc-token already set. no token has to be requested. wpLostPasswordForm can be submitted.');
            }
        });
    }($));
});
</script>
<?php
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