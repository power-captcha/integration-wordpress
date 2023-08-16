<?php

defined('POWER_CAPTCHA_PATH') || exit;

// note: there is only one integration setting for WordPress AND WooCommerce Lost Password.
if(powercaptcha()->is_enabled(powercaptcha()::WORDPRESS_LOST_PASSWORD_INTEGRATION)) {
    // integration js
    add_action('woocommerce_after_lost_password_form', 'powercaptcha_woocommerce_lost_password_integration_javascript');

    // note:
    // the token is verified by wordpress-lost-password.php (powercaptcha_wordpress_lost_password_verification) 
    // which valid for WordPress Lost Password and WooCommerce Lost Password.
    // this is necessary because woocomerce process_lost_password function does not offer a hook to check the captcha token only for WooCommerce.
    // for this reason, there is only one setting for both integrations (WordPress and WooCommerce Lost Password).
}

function powercaptcha_woocommerce_lost_password_integration_javascript() {
    if (!powercaptcha()->is_enabled(powercaptcha()::WORDPRESS_LOST_PASSWORD_INTEGRATION)) {
        return;
    }

    powercaptcha_javascript_tags();
?>
<script type="text/javascript">
    (function (window, document) {

        powerCaptchaWp.prefetchFrontendDetails('wordpress_lost_password');

        document.querySelectorAll('form.woocommerce-ResetPassword').forEach((wcLostPasswordForm) => {
            // generate id for each form since woocommerce does not provide an element id
            const wcLostPassowrdFormId = 'wc-' + Math.random().toString(16).slice(2);

            // append hidden input for token
            const tokenField = document.createElement("input");
            tokenField.name = "pc-token";
            tokenField.type = "hidden";
            wcLostPasswordForm.appendChild(tokenField);

            // create instance for the register form
            const captchaInstance = window.uiiCaptcha.captcha({idSuffix: wcLostPassowrdFormId});

            wcLostPasswordForm.addEventListener('submit', event => {
                console.debug('submitEvent for wcLostPasswordForm', '#'+wcLostPassowrdFormId);

                if(tokenField.value === "") {
                    console.debug('pc-token field empty. preventing form submit and requesting token.');
                    event.preventDefault();

                    const userNameField = wcLostPasswordForm.querySelector('#user_login');
                    console.debug('userNameField val', userNameField.value);
                    const userName = userNameField.value;

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
                            tokenField.value = token;
                            console.debug('resubmitting wcLostPasswordForm form.');

                            wcLostPasswordForm.submit();
                        });
                    });
                } else {
                    console.debug('pc-token already set. no token has to be requested. wcLostPasswordForm can be submitted.');
                }
            });
        });
    }(window, document));
</script>
<?php
}