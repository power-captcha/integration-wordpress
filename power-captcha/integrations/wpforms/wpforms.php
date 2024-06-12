<?php

defined('POWER_CAPTCHA_PATH') || exit;

if (powercaptcha()->is_enabled(powercaptcha()::WPFORMS_INTEGRATION)) {

    add_action('wpforms_frontend_js', 'powercaptcha_wpforms_enqueue_script', 10, 1);

    add_action( 'wpforms_display_submit_before', 'powercaptcha_wpforms_widget', 10, 1 );

    add_action( 'wpforms_process', 'powercaptcha_wpforms_verification', 10, 3 );
}

function powercaptcha_wpforms_enqueue_script($forms) {
    wp_enqueue_script(
        'powercaptcha-wpforms', 
        plugin_dir_url( __FILE__ )  . 'public/power-captcha-wpforms.js',  
        ['jquery', 'powercaptcha-wp'], 
        POWER_CAPTCHA_PLUGIN_VERSION, 
        false 
    );
}

/**
 * Action that fires immediately before the submit button element is displayed.
 * 
 * @link  https://wpforms.com/developers/wpforms_display_submit_before/
 * 
 * @param array  $form_data Form data and settings
 */
 
function powercaptcha_wpforms_widget( $form_data ) {
    if (!powercaptcha()->is_enabled(powercaptcha()::WPFORMS_INTEGRATION)) {
        return;
    }

    echo powercaptcha_widget_html(powercaptcha()::WPFORMS_INTEGRATION, '', '', 'wpforms-field', 'margin-top: -10px; margin-bottom: 10px');
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