<?php

defined('POWER_CAPTCHA_PATH') || exit;

if (powercaptcha()->is_enabled(powercaptcha()::WPFORMS_INTEGRATION)) {
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

    // for each form
    $('form.wpforms-form').each(function () {
        const wpform = $(this);
        const wpformId = wpform.attr('id');

        // append hidden input for token
        wpform.append('<input type="hidden" name="pc-token" value =""/>');

        // create instance for the wpfrom
        const captchaInstance = window.uiiCaptcha.captcha({idSuffix: wpformId});

        // register before submit listener
        wpform.on('wpformsBeforeFormSubmit', function(event) {
            console.debug('beforeSubmitEvent for wpform #'+wpformId);

            const tokenField = wpform.find('input[name="pc-token"]').eq(0);
            if(tokenField.length === 0) {
                console.warn('No pc-token field found in wpform #'+wpformId+' before submit.');
                return; // exit
            }

            if(tokenField.val() === "") {
                console.debug('pc-token field empty. preventing form submit and requesting token.');
                event.preventDefault();

                let userName = '';
                const fieldContainer = wpform.find('[class*="pc-user-"]').eq(0);
                if(fieldContainer.length === 0) {
                    console.debug('no container found with pc-user-* class in wpform #'+wpformId);
                } else {
                    var fieldPosition = fieldContainer.attr('class').match(/pc-user-([0-9]+)/)[1];
                    const userNameField = fieldContainer.find('input').eq(fieldPosition);
                    if(userNameField.length === 0) {
                        console.debug('no pc-user field found with index '+fieldPosition+' in container #'+fieldContainer.attr('id')+' of wpform #'+wpformId);
                    } else {
                        userName = userNameField.val();
                        console.debug('userName val: '+userNameField.val());

                        const pcUserName = wpform.find('input[name="pc-username"]').eq(0);
                        if(pcUserName === 0) {
                            console.warn('no pc-username field found in wpform #'+wpformId);
                        } else {
                            pcUserName.val(userName);
                        }
                    }
                }

                captchaInstance.check({
                    apiKey: '<?php echo powercaptcha()->get_api_key(); ?>',
                    backendUrl: '<?php echo powercaptcha()->get_token_request_url() ; ?>', 
                    user: userName,
                    callback: ''
                }, 
                function(token) {
                    console.debug('captcha solved with token: '+token+'. setting value to tokenField.');
                    tokenField.val(token);
                    console.debug('resubmitting form.');
                    wpform.submit(); // TODO jquery
                    //$('#wpforms-form-9').submit();
                });
            } else {
                console.debug('token already exists. no captcha has to be shown. form can be submitted.');
            }
        });


        // add after submit failed listners
        wpform.on('wpformsAjaxSubmitFailed wpformsAjaxSubmitActionRequired wpformsAjaxSubmitError', function (event) {
            // clear token field after submit failed
            const wpform = $(this);
            console.debug('clearing token field after submit failed');
            const tokenField = wpform.find('input[name="pc-token"]').eq(0);
            if(tokenField.length === 0) {
                console.warn('no token field found in wpform #'+wpform.attr('id')+' after submit failed.');
                return; // exit
            }
            tokenField.val("");
        });
    });

});
 </script>
<?php
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
        $verification = powercaptcha_verify_token($pcToken, $pcUsername);
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



/**
 * This action fires almost immediately after the form’s submit button is clicked, before validation takes place for the entry.
 *
 * @link  https://wpforms.com/developers/wpforms_process_before/
 *
 * @param array  $entry     Unvalidated entry data.
 * @param array  $form_data Form data and settings.
 */
 
 /*
function wpf_dev_process_before( $entry, $form_data ) {
 
    // Only run on my form with ID = 5
    if ( absint( $form_data[ 'id' ] ) !== 5 ) {
        return;
    } 
 
    // place your custom code here
}
add_action( 'wpforms_process_before', 'wpf_dev_process_before', 10, 2 );*/


/**
 * Alter default action of form submission.
 *
 * @link    https://wpforms.com/developers/wpforms_frontend_form_action/
 *
 * @param   array  $action     Returning action to be taken on form submit.
 * @param   array  $form_data  Form data.
 *
 * @return  array
 */
 /*
 function wpf_custom_form_action( $action, $form_data ) {
    echo "<pre>".var_export($action, true)."</pre>";
      
}
     
add_filter( 'wpforms_frontend_form_action', 'wpf_custom_form_action', 10, 2 );*/
    