<?php

defined('POWER_CAPTCHA_PATH') || exit;

if(powercaptcha()->is_enabled(powercaptcha()::WOOCOMMERCE_CHECKOUT_INTEGRATION)) {
    // integration js
    add_action('woocommerce_after_checkout_billing_form', 'powercaptcha_woocommerce_checkout_integration_javascript');

    // token verification
    add_action('woocommerce_after_checkout_validation', 'powercaptcha_woocommerce_checkout_verification', 10, 2);
    // TODO: maybe use woocommerce_checkout_process or woocommerce_before_checkout_process hook?
}


function powercaptcha_woocommerce_checkout_integration_javascript() {
    if (!powercaptcha()->is_enabled(powercaptcha()::WOOCOMMERCE_CHECKOUT_INTEGRATION)) {
        return;
    }

    powercaptcha_javascript_tags();
?>
<script type="text/javascript">
    (function($){
        // based on https://stackoverflow.com/a/68699215
        let wcCheckoutForm, tokenField, captchaInstance;

        function canSubmit( e ) {
            if(tokenField.length === 0) {
                console.warn('no pc-token field found in wcCheckoutForm.');
                return true; // exit
            }

            if(tokenField.val() === "") {
                console.debug('pc-token field empty. preventing form submit and requesting token.');

                const userNameField = wcCheckoutForm.find('#billing_email').eq(0);
                let userName = '';
                if(userNameField.length === 0) {
                    console.warn('no billing_email field found in wcCheckoutForm.');
                } else {
                    console.debug('userNameField val', userNameField.val());
                    userName = userNameField.val();
                }
                
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

                    console.debug('resubmitting wcCheckoutForm form.');
                    wcCheckoutForm.trigger('submit');
                });

                return false; // stop woocommerce form submit
            } else {
                console.debug('pc-token already set. no token has to be requested. wcCheckoutForm can be submitted.');
                return true; // proceed woocommerce from submit
            }
        }

        function initPowerCaptcha() {
            wcCheckoutForm = $( 'form.checkout' );
            // generate id for each form since woocommerce does not provide an element id
            const wcCheckoutFormId = 'wc-' + Math.random().toString(16).slice(2);

            // append hidden input for token
            wcCheckoutForm.append('<input type="hidden" name="pc-token" value =""/>');

            tokenField = wcCheckoutForm.find('input[name="pc-token"]').eq(0);

            // create instance for the form
            captchaInstance = window.uiiCaptcha.captcha({idSuffix: wcCheckoutFormId});

            $( document.body ).on( 'checkout_error' , function () {
                // reset token field after error
                tokenField.val('');
                console.debug('token field was resetted.');
                return true;
            } );
        }

        function init() {
            // Use set timeout to ensure our $( document ).ready call fires after WC
            setTimeout( () => {

                initPowerCaptcha();

                // Get JQuery bound events
                var events = $._data( wcCheckoutForm[0], 'events' );
                if( !events || !events.submit ) {
                    return;
                }

                // Save Submit Events to be called later then Disable Them
                var submitEvents = $.map( events.submit, event => event.handler );
                $( submitEvents ).each( event => wcCheckoutForm.off( 'submit', null, event ) );  

                // Now Setup our Event Relay
                wcCheckoutForm.on( 'submit', function( e )  {
                    e.preventDefault();
                    var self = this;

                    if( !canSubmit( ...arguments ) ) {
                        return;
                    }

                    // Trigger Event
                    $( submitEvents ).each( ( i, event ) => {
                        var doEvent = event.bind( self );
                        doEvent( ...arguments );
                    } );

                } );

            }, 10);
        }

        $( document ).ready( () => init() );
    })( jQuery );

</script>
<?php
}


function powercaptcha_woocommerce_checkout_verification(array $fields, WP_Error $errors) {
    if (!powercaptcha()->is_enabled(powercaptcha()::WOOCOMMERCE_CHECKOUT_INTEGRATION)) {
        return;
    }

    $pcToken = powercaptcha_get_token_from_post_request();
    if($pcToken === FALSE) {
        $errors->add(powercaptcha()::ERROR_CODE_NO_TOKEN_FIELD, powercaptcha_user_error_message(powercaptcha()::ERROR_CODE_NO_TOKEN_FIELD));
    } else {
        $verification = powercaptcha_verify_token($pcToken, $_POST['username']);
        if($verification['success'] !== TRUE) {
            $errors->add($verification['error_code'], powercaptcha_user_error_message($verification['error_code']));
        }
    }
}