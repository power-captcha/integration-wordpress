<?php

if(powercaptcha()->is_enabled(powercaptcha()::WOOCOMMERCE_REGISTER_INTEGRATION)) {
    // integration js
    add_action('woocommerce_register_form_end', 'powercaptcha_woocommerce_register_integration_javascript');

    // token verification
    add_action('woocommerce_register_post', 'powercaptcha_woocommerce_register_verification', 10, 3);
    // TODO we could also use the woocommerce_registration_errors hook. but woocommerce_register_post is executed earlier.
}


function powercaptcha_woocommerce_register_integration_javascript() {
    if (!powercaptcha()->is_enabled(powercaptcha()::WOOCOMMERCE_REGISTER_INTEGRATION)) {
        return;
    }

    powercaptcha_javascript_tags();
?>
<script type="text/javascript">
    (function (window, document) {
        document.querySelectorAll('form.woocommerce-form-register').forEach((wcRegisterForm) => {
            // generate id for each form since woocommerce does not provide an element id
            const wcRegisterFormId = 'wc-' + Math.random().toString(16).slice(2);

            // append hidden input for token
            const tokenField = document.createElement("input");
            tokenField.name = "pc-token";
            tokenField.type = "hidden";
            wcRegisterForm.appendChild(tokenField);

            // workaround for WooCommerce: adding register field 
            //      (the register field is in the submit button of the WC register form and is somehow not submitted when submitting via form.submit()).
            const loginField = document.createElement("input");
            loginField.name = "register";
            loginField.value = "Registrieren";
            loginField.type = "hidden";
            wcRegisterForm.appendChild(loginField);

            // create instance for the register form
            const captchaInstance = window.uiiCaptcha.captcha({idSuffix: wcRegisterFormId});

            wcRegisterForm.addEventListener('submit', event => {
                console.debug('submitEvent for wcRegisterForm', '#'+wcRegisterFormId);

                if(tokenField.value === "") {
                    console.debug('pc-token field empty. preventing form submit and requesting token.');
                    event.preventDefault();

                    const userNameField = wcRegisterForm.querySelector('#reg_email');
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
                        console.debug('resubmitting wcRegisterForm form.');

                        wcRegisterForm.submit();
                    });
                } else {
                    console.debug('pc-token already set. no token has to be requested. wcRegisterForm can be submitted.');
                }
            });
        });
    }(window, document));
</script>
<?php
}

function powercaptcha_woocommerce_register_verification(string $username, string $user_ermail, WP_Error $errors) {
    if (!powercaptcha()->is_enabled(powercaptcha()::WOOCOMMERCE_REGISTER_INTEGRATION)) {
        return $errors;
    }

    if(empty($_POST)) {
        return $errors;
    }

    $pcToken = powercaptcha_get_token_from_post_request();
    if($pcToken === FALSE) {
        $errors->add(powercaptcha()::ERROR_CODE_NO_TOKEN_FIELD, powercaptcha_user_error_message(powercaptcha()::ERROR_CODE_NO_TOKEN_FIELD, false));
    } else {
        $verification = powercaptcha_verify_token($pcToken, $_POST['email']);
        if($verification['success'] !== TRUE) {
            $errors->add($verification['error_code'], powercaptcha_user_error_message($verification['error_code'], false));
        }
    }

    return $errors;
}