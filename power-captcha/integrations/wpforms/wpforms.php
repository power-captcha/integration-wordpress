<?php

add_action( 'wpforms_wp_footer_end', 'powercaptcha_wpforms_enqueue_scripts', 10, 0 );
function powercaptcha_wpforms_enqueue_scripts( ) {
    if (!powercaptcha()->is_wpforms_integration_enabled()) {
        return;
    }

    powercaptcha_echo_javascript_tags();
?>


<script type="text/javascript">

jQuery(function($){

    $(document).ready(function(){
        // destory auto instance
        if(window.uiiCaptcha && window.uiiCaptcha.autoInstance) {
            window.uiiCaptcha.autoInstance.destroy();
            console.log('auto instance destroyed');
        } else {
            console.log('no auto instance is present');
        }
    });

    // based on https://causier.co.uk/2021/02/04/hooking-into-wpforms-ajax-submission-workflow-for-custom-event-handling/

    // for each form
    $('form.wpforms-form').each(function () {
        const wpform = $(this);
        const wpformId = wpform.attr('id');

        // append hidden input for token
        wpform.append('<input type="hidden" name="pc-token" value =""/>');

        // create instance for the wpfrom
        const captchaInstance = window.uiiCaptcha.captcha({id: 'pc-'+wpformId});

        // register before submit listener
        wpform.on('wpformsBeforeFormSubmit', function(event) {
            console.log('beforeSubmitEvent for wpform #'+wpformId);

            const tokenField = wpform.find('input[name="pc-token"]').eq(0);
            if(tokenField.length === 0) {
                console.warn('no token field found in wpform #'+wpformId+' before submit.');
                return; // exit
            }

            if(tokenField.val() === "") {
                console.log('tokenField is empty. preventing submit and showing captcha.');
                event.preventDefault();

                let userName = '';
                const fieldContainer = wpform.find('[class*="pc-user-"]').eq(0);
                if(fieldContainer.length === 0) {
                    console.log('no container found with pc-user-* class in wpform #'+wpformId);
                } else {
                    var fieldPosition = fieldContainer.attr('class').match(/pc-user-([0-9]+)/)[1];
                    const userNameField = fieldContainer.find('input').eq(fieldPosition);
                    if(userNameField.length === 0) {
                        console.log('no pc-user field found with index '+fieldPosition+' in container #'+fieldContainer.attr('id')+' of wpform #'+wpformId);
                    } else {
                        userName = userNameField.val();
                        console.log('username val: '+userNameField.val());

                        const pcUserName = wpform.find('input[name="pc-username"]').eq(0);
                        if(pcUserName === 0) {
                            console.log('no pc-username field found in wpform #'+wpformId);
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
                    console.log('captcha solved with token: '+token+'. setting value to tokenField.');
                    tokenField.val(token);
                    console.log('resubmitting form.');
                    wpform.submit(); // TODO jquery
                    //$('#wpforms-form-9').submit();
                });
            } else {
                console.log('token already exists. no captcha has to be shown. form can be submitted.');
            }
        });


        // add after submit failed listners
        wpform.on('wpformsAjaxSubmitFailed wpformsAjaxSubmitActionRequired wpformsAjaxSubmitError', function (event) {
            // clear token field after submit failed
            const wpform = $(this);
            console.log('clearing token field after submit failed');
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
add_action( 'wpforms_process', 'powercaptcha_wpforms_verification', 10, 3 );
function powercaptcha_wpforms_verification( $fields, $entry, $form_data ) {
    if (!powercaptcha()->is_wpforms_integration_enabled()) {
        return;
    }


    trigger_error("Form Data: ".var_export($form_data, true));
    trigger_error("Fields: ".var_export($fields, true));
    trigger_error("Entry: ".var_export($entry, true));
    trigger_error("Post: ".var_export($_POST, true));


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
    //$pcUsername = powercaptcha_get_username_from_post_request();

    if($pcToken === FALSE) {
        wpforms()->process->errors[ $form_id ] [ 'header' ] = esc_html__(powercaptcha_user_error_message(), 'power-captcha' ); 
        wpforms_log( //TODO wpforms_log
            //@param string $title   Title of a log error_message.
            esc_html__( 'POWER CAPTCHA: Spam detected' ) . uniqid(), 
            //@param mixed  $error_message Content of a log error_message.
            ["POWER CAPTCHA token was not present in post request.", $entry], 
            // @param array  $args    Expected keys: form_id, meta, parent.
            [ 
                'type'    => ['spam'], // types: spam, security ? TODO
                'form_id' => $form_id,
            ]
        );
    } else {
        $verification = powercaptcha_verify_token($pcToken, $pcUsername);
        if($verification['success'] !== TRUE) {
            wpforms()->process->errors[ $form_id ] [ 'header' ] = esc_html__(powercaptcha_user_error_message(), 'power-captcha' ); 
            $entry['pc_token'] = "";
            wpforms_log( //TODO wpforms_log
                esc_html__( 'POWER CAPTCHA: Spam detected' ) . uniqid(),
                [ "POWER CAPTCHA token verification failed.", $verification , $entry],
                [
                    'type'    => [ 'spam' ], // TODO type?
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
    