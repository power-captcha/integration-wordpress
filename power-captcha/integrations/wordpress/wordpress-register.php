<?php

if(powercaptcha()->is_enabled(powercaptcha()::WORDPRESS_REGISTER_INTEGRATION)) {
    // power captcha js
    // note: Despite the name, 'login_enqueue_scripts' is used for enqueuing both scripts and styles, on all login and registration related screens.
    add_action('login_enqueue_scripts', 'powercaptcha_enqueue_javascript' );

    // integration js
    // note: Despite the name, 'login_enqueue_scripts' is used for enqueuing both scripts and styles, on all login and registration related screens.
    add_action('login_enqueue_scripts', 'powercaptcha_enqueue_jquery' );
    add_action('register_form', 'powercaptcha_wordpress_register_integration_javascript');

    // token verification
    add_action('register_post', 'powercaptcha_wordpress_register_verification', 10, 3);
    //add_filter('registration_errors', 'powercaptcha_wordpress_register_verification', 1, 3);
}


function powercaptcha_wordpress_register_integration_javascript() {
    if (!powercaptcha()->is_enabled(powercaptcha()::WORDPRESS_REGISTER_INTEGRATION)) {
        return;
    }

?>
<script type="text/javascript">
// TODO move this script to javascript file. note parameters like apiKey and secretKey must be injected
jQuery(function($){
    (function ($) {
        const wpRegisterForm = $('#registerform');
        const wpRegisterFormId = wpRegisterForm.attr('id');
        
        // append hidden input for token
        wpRegisterForm.append('<input type="hidden" name="pc-token" value =""/>');
        
        // create instance for the login form
        const captchaInstance = window.uiiCaptcha.captcha({idSuffix: wpRegisterFormId});

        // register submit listener
        wpRegisterForm.on('submit', function (event) {
            console.debug('submitEvent for wpRegisterForm', '#'+wpRegisterFormId);

            const tokenField = wpRegisterForm.find('input[name="pc-token"]').eq(0);
            if(tokenField.length === 0) {
                console.warn('no pc-token field found in wpRegisterForm.');
                return; // exit
            }

            if(tokenField.val() === "") {
                console.debug('pc-token field empty. preventing form submit and requesting token.');
                event.preventDefault();

                const userNameField = wpRegisterForm.find('#user_email').eq(0);
                console.debug('userNameField val', userNameField.val());
                const userName = userNameField.val();

                // requesting token
                captchaInstance.check({
                    apiKey: '<?php echo powercaptcha()->get_api_key(); ?>',
                    backendUrl: '<?php echo powercaptcha()->get_token_request_url() ; ?>', 
                    user: userName,
                    callback: ''
                }, 
                function(token) {
                    console.debug('captcha solved with token: '+token+'. setting value to tokenField.');
                    tokenField.val(token);
                    console.debug('resubmitting login form.');
                    wpRegisterForm.trigger("submit");
                });
            } else {
                console.debug('pc-token already set. no token has to be requested. form can be submitted.');
            }
        });
    }($));
});
</script>
<?php
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
        $verification = powercaptcha_verify_token($pcToken, $user_email);
        if($verification['success'] !== TRUE) {
            $errors->add($verification['error_code'], powercaptcha_user_error_message($verification['error_code']));
        }
    }

    return $errors;
}