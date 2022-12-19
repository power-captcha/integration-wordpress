<?php

add_action( 'wpforms_wp_footer_end', 'powercaptcha_wpforms_enqueue_scripts', 10, 0 );
function powercaptcha_wpforms_enqueue_scripts( ) {
 //TODO script url! als konstante?
?>
<!-- <script src="https://cdn.power-captcha.com/v1/uii-catpcha-lib.iife.js" type="text/javascript"></script> -->
<script src="http://localhost/captcha-js/uii-catpcha-lib.iife.js" type="text/javascript"></script>
<script type="text/javascript">
// https://causier.co.uk/2021/02/04/hooking-into-wpforms-ajax-submission-workflow-for-custom-event-handling/
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

    $('form.wpforms-form').append('<input type="hidden" name="pc-token" value =""/>');
    $('form.wpforms-form').on('wpformsBeforeFormSubmit', function(event) {
        const wpform = $(this);
        console.log('beforeSubmitEvent for form:'+wpform.attr('id'));
        const tokenField = wpform.children('input[name="pc-token"]:first');
        if(tokenField.val() === "") {
            console.log('tokenField is empty. preventing submit and showing captcha.');
            event.preventDefault();
            captchaInstance.check({
                apiKey: '<?php echo PowerCaptcha_WP::instance()->get_api_key(); ?>',
                backendUrl: '<?php echo PowerCaptcha_WP::instance()->get_endpoint_url(); ?>',
                user: '',
                callback: ''
            }, 
            function(token) {
                console.log('captcha solved with token: '+token+'. setting value to tokenField.');
                tokenField.val(token);
                console.log('resubmitting form.');
                wpform.submit();
                //$('#wpforms-form-9').submit();
            });
        } else {
            console.log('token already exists. no captcha has to be shown. form can be submitted.');
        }
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
    $form_id = $form_data['id'];
    $pcToken = powercaptcha_get_token_from_post_request();

    if($pcToken === false) {
        wpforms()->process->errors[ $form_id ] [ 'header' ] = esc_html__(powercaptcha_error_message(), 'powercaptcha' ); 
        wpforms_log(
            //@param string $title   Title of a log message.
            esc_html__( 'Power-Captcha: Spam detected' ) . uniqid(), 
            //@param mixed  $message Content of a log message.
            ["Power-Captcha token was not present in post request.", $entry], 
            // @param array  $args    Expected keys: form_id, meta, parent.
            [ 
                'type'    => ['spam'], // types: spam, security ? TODO
                'form_id' => $form_id,
            ]
        );
    } else {
        $verification = powercaptcha_verify_token($pcToken);
        if(!$verification["success"]) {
            wpforms()->process->errors[ $form_id ] [ 'header' ] = esc_html__(powercaptcha_error_message(), 'powercaptcha' ); 
            wpforms_log(
                esc_html__( 'Power-Captcha: Spam detected' ) . uniqid(),
                [ "Power-Captcha token verification failed.", $verification , $entry],
                [
                    'type'    => [ 'spam' ], // TODO type?
                    'form_id' => $form_id,
                ]
            );
        }
    }
}

function powercaptcha_get_token_from_post_request() {
    if(isset($_POST["pc-token"])) {
        return sanitize_text_field($_POST["pc-token"]);
    } else {
        return false;
    }
}

//TODO outsource function
function powercaptcha_verify_token($token) {
    $verification_result = array();
    $verification_result["success"] = false;

    if(empty($token)) {
        $verification_result["error"] = "Token was empty.";
    } else {
        // TODO verifiy with captcha api -> see friendly-captcha/includes/verification.php (frcaptcha_verify_captcha_solution)
        $verification_result["success"] = true;
    }
    return $verification_result;
}

function powercaptcha_error_message() {
    return __('Der Anti-Spam-Schutz hat die Übertragung des Formulars verhindert. Bitte versuchen Sie es zu einem späteren Zeitpunkt erneut.');
    // The anti-spam protection has prevented the form from being submitted. Please try again at a later time.
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
    