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
    let captchaInstance;
    $(document).ready(function(){
        if(window.uiiCaptcha && window.uiiCaptcha.autoInstance) {
            window.uiiCaptcha.autoInstance.destroy();
            console.log('auto instance destroyed');
        } else {
            console.log('no auto instance is present');
        }
        captchaInstance = window.uiiCaptcha.captcha({});
    });

    // based on https://causier.co.uk/2021/02/04/hooking-into-wpforms-ajax-submission-workflow-for-custom-event-handling/
    $('form.wpforms-form').append('<input type="hidden" name="pc-token" value =""/>');
    $('form.wpforms-form').on('wpformsBeforeFormSubmit', function(event) {
        const wpform = $(this);
        console.log('beforeSubmitEvent for form:'+wpform.attr('id'));
        const tokenField = wpform.children('input[name="pc-token"]:first');
        if(tokenField.val() === "") {
            console.log('tokenField is empty. preventing submit and showing captcha.');
            event.preventDefault();
            captchaInstance.check({
                apiKey: '<?php echo powercaptcha()->get_api_key(); ?>',
                backendUrl: '<?php echo powercaptcha()->get_token_request_url() ; ?>', 
                user: '',
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
    $('form.wpforms-form').on('wpformsAjaxSubmitFailed wpformsAjaxSubmitActionRequired wpformsAjaxSubmitError', function (event) {
        // clear token field after submit failed
        const wpform = $(this);
        console.log('clearing token field after submit failed');
        const tokenField = wpform.children('input[name="pc-token"]:first');
        tokenField.val("");
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

    $form_id = $form_data['id'];
    $pcToken = powercaptcha_get_token_from_post_request();

    if($pcToken === FALSE) {
        wpforms()->process->errors[ $form_id ] [ 'header' ] = esc_html__(powercaptcha_user_error_message(), 'powercaptcha' ); 
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
        $verification = powercaptcha_verify_token($pcToken);
        if($verification['success'] !== TRUE) {
            wpforms()->process->errors[ $form_id ] [ 'header' ] = esc_html__(powercaptcha_user_error_message(), 'powercaptcha' ); 
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
    