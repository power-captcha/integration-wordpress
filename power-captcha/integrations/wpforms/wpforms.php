<?php

defined('POWER_CAPTCHA_PATH') || exit;

if (powercaptcha()->is_enabled(powercaptcha()::WPFORMS_INTEGRATION)) {
    // add widget div
    add_action( 'wpforms_display_submit_before', 'powercaptcha_wpforms_verification_add_widget', 10, 1 );

    // integration js
    add_action( 'wpforms_wp_footer_end', 'powercaptcha_wpforms_integration_javascript', 10, 0 );

    // token verification
    add_action( 'wpforms_process', 'powercaptcha_wpforms_verification', 10, 3 );
}

function powercaptcha_wpforms_integration_javascript( ) {
    if (!powercaptcha()->is_enabled(powercaptcha()::WPFORMS_INTEGRATION)) {
        return;
    }

    powercaptcha_javascript_tags();
?>


<script type="text/javascript">
// TODO move this script to javascript file. note parameters like apiKey and secretKey must be injected
jQuery(function($){
    // based on https://causier.co.uk/2021/02/04/hooking-into-wpforms-ajax-submission-workflow-for-custom-event-handling/

    // prefetch details
    powerCaptchaWp.prefetchFrontendDetails('wpforms');

    // for each form
    $('form.wpforms-form').each(function () {
        const wpform = $(this);
        const wpformId = wpform.attr('id');

        let usernameField = undefined;
        // find check if there is a username field and find it
        const fieldContainer = wpform.find('[class*="pc-user-"]').eq(0);
        if(fieldContainer.length === 0) {
            console.debug('no container found with pc-user-* class in wpform #'+wpformId);
            usernameField = undefined;
        } else {
            const fieldPosition = fieldContainer.attr('class').match(/pc-user-([0-9]+)/)[1];
            usernameField = fieldContainer.find('input').eq(fieldPosition);
            if(usernameField.length === 0) {
                console.warn(`username field not found with index ${fieldPosition} in container #${fieldContainer.attr('id')} of wpform #${wpformId}`);
                usernameField = undefined;
            } else {
                usernameField = usernameField[0];
            }
        }


        powerCaptchaWp.withFrontendDetails('wpforms', function(details) {
            console.log('details: ', details);
            // create instance for the wpfrom
            const pc = PowerCaptcha.init({
                idSuffix: wpformId, 
                apiKey: details.apiKey,
                backendUrl: details.backendUrl, 
                widgetTarget: wpform.find('.pc-widget-target')[0],//document.querySelector('.my-widget'), // widgetElement (div)
                
                userInputField: usernameField[0], //document.querySelector('#fname'),
                
                // unique client id (e.g. hashed client ip address)
                clientUid: details.clientUid,
                lang: powerCaptchaWp.getLang(),

                invisibleMode: false, // TODO make invisibleMode configurable 
                debug: true // TODO turn off debug or make debug configurable 
            });

            // TODO prevent sumbit if captcha is not valid: seems that wpForms ignores browser invalid marking.

            // clear captcha after ajax submit failed
            wpform.on('wpformsAjaxSubmitFailed wpformsAjaxSubmitActionRequired wpformsAjaxSubmitError', function (event) {
                pc.reset();
            });
        });

        // add after submit failed listners
        wpform.on('wpformsAjaxSubmitFailed wpformsAjaxSubmitActionRequired wpformsAjaxSubmitError', function (event) {
            console.log('wpforms submit failed', event);

            // clear token field after submit failed
            /* const wpform = $(this);
            console.debug('clearing token field after submit failed');
            const tokenField = wpform.find('input[name="pc-token"]').eq(0);
            if(tokenField.length === 0) {
                console.warn('no token field found in wpform #'+wpform.attr('id')+' after submit failed.');
                return; // exit
            }
            tokenField.val("");*/
        });
    });

});
 </script>
<?php
}

/**
 * Action that fires immediately before the submit button element is displayed.
 * 
 * @link  https://wpforms.com/developers/wpforms_display_submit_before/
 * 
 * @param array  $form_data Form data and settings
 */
 
function powercaptcha_wpforms_verification_add_widget( $form_data ) {
    if (!powercaptcha()->is_enabled(powercaptcha()::WPFORMS_INTEGRATION)) {
        return;
    }
    // TODO control margin via css variables
    echo '<div class="pc-widget-target wpforms-field" style="margin-top: -10px; margin-bottom: 10px"></div>';
}


/**
 * Action that fires during form entry processing after initial field validation.
 *
 * @link   https://wpforms.com/developers/wpforms_process/
 *
 * @param  array  $fields    Sanitized entry field. values/properties.
 * @param  array  $entry     Original $_POST global.
 * @param  array  $form_data Form data and settings.
 */
function powercaptcha_wpforms_verification( $fields, $entry, $form_data ) {
    if (!powercaptcha()->is_enabled(powercaptcha()::WPFORMS_INTEGRATION)) {
        return;
    }

    $pcUsername = null;
    // find the username field by css class
    foreach($form_data['fields'] as $field_id => $settings) {
        if(isset($settings['css']) && !empty($settings['css'])) {
            // check if css has pc-user-* class
            $matches = array();
            if(preg_match('/pc-user-([0-9]+)/', $settings['css'], $matches)) {
                $field_position = $matches[1];
                $field_value = $entry['fields'][$field_id];
            
                if(is_array($field_value)) {
                    $pcUsername = array_values($field_value)[$field_position];
                } else {
                    $pcUsername = $field_value;
                }
                break;
            }
        }
    }

    $form_id = $form_data['id'];
    $pcToken = powercaptcha_get_token_from_post_request();

    if($pcToken === FALSE) {
        wpforms()->process->errors[ $form_id ] [ 'header' ] = powercaptcha_user_error_message(powercaptcha()::ERROR_CODE_NO_TOKEN_FIELD); 
        wpforms_log( // TODO better message for wpforms_log
            //@param string $title   Title of a log error_message.
            esc_html__( 'POWER CAPTCHA: Spam detected' , 'power-captcha' ) . uniqid(), 
            //@param mixed  $error_message Content of a log error_message.
            ["POWER CAPTCHA token was not present in post request.", $entry], 
            // @param array  $args    Expected keys: form_id, meta, parent.
            [ 
                'type'    => ['spam'],
                'form_id' => $form_id,
            ]
        );
    } else {
        $verification = powercaptcha_verify_token($pcToken, $pcUsername, null, powercaptcha()::WPFORMS_INTEGRATION);
        if($verification['success'] !== TRUE) {
            wpforms()->process->errors[ $form_id ] [ 'header' ] = powercaptcha_user_error_message($verification['error_code']); 
            $entry['pc_token'] = "";
            wpforms_log( // TODO better message for wpforms_log
                esc_html__( 'POWER CAPTCHA: Spam detected' , 'power-captcha' ) . uniqid(),
                [ "POWER CAPTCHA token invalid.", $verification , $entry],
                [
                    'type'    => ['spam'],
                    'form_id' => $form_id,
                ]
            );
        }
    }
}