<?php

if(powercaptcha()->is_enabled(powercaptcha()::WOOCOMMERCE_LOGIN_INTEGRATION)) {
    // integration js
    add_action('woocommerce_login_form_end', 'powercaptcha_woocommerce_login_integration_javascript');

    // token verification
    add_filter('woocommerce_process_login_errors', 'powercaptcha_woocommerce_login_verification', 20, 3);
}


function powercaptcha_woocommerce_login_integration_javascript() {
    if (!powercaptcha()->is_enabled(powercaptcha()::WOOCOMMERCE_LOGIN_INTEGRATION)) {
        return;
    }

    powercaptcha_javascript_tags();
?>
<script type="text/javascript">
    (function (window, document) {
        document.querySelectorAll('form.woocommerce-form-login').forEach((wcLoginForm) => {
            // generate id for each form since woocommerce does not provide an element id
            const wcLoginFormId = 'wc-' + Math.random().toString(16).slice(2);

            // append hidden input for token
            const tokenField = document.createElement("input");
            tokenField.name = "pc-token";
            tokenField.type = "hidden";
            wcLoginForm.appendChild(tokenField);

            // workaround for WooCommerce: adding login field 
            //      (the login field is in the submit button of the WC login form and is somehow not submitted when submitting via form.submit()).
            const loginField = document.createElement("input");
            loginField.name = "login";
            loginField.value = "Anmelden";
            loginField.type = "hidden";
            wcLoginForm.appendChild(loginField);

            // create instance for the login form
            const captchaInstance = window.uiiCaptcha.captcha({idSuffix: wcLoginFormId});

            wcLoginForm.addEventListener('submit', event => {
                console.debug('submitEvent for wcLoginForm', '#'+wcLoginFormId);

                if(tokenField.value === "") {
                    console.debug('pc-token field empty. preventing form submit and requesting token.');
                    event.preventDefault();

                    const userNameField = wcLoginForm.querySelector('#username');
                    console.debug('userNameField val', userNameField.value);
                    const userName = userNameField.value;

                    // requesting token
                    captchaInstance.check({
                        apiKey: '<?php echo powercaptcha()->get_api_key(); ?>',
                        backendUrl: '<?php echo powercaptcha()->get_token_request_url() ; ?>', 
                        user: userName,
                        callback: ''
                    }, 
                    function(token) {
                        console.debug('captcha solved with token: '+token+'. setting value to tokenField.');
                        tokenField.value = token;
                        console.debug('resubmitting wcLoginForm form.');

                        wcLoginForm.submit();
                    });
                } else {
                    console.debug('pc-token already set. no token has to be requested. wcLoginForm can be submitted.');
                }
            });
        });
    }(window, document));
</script>
<?php
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
        $verification = powercaptcha_verify_token($pcToken, $_POST['username']);
        if($verification['success'] !== TRUE) {
            $validation_error->add($verification['error_code'], powercaptcha_user_error_message($verification['error_code'], false));
        }
    }

    return $validation_error;
}