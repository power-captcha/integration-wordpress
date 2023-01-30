
<?php

function powercaptcha_get_token_from_post_request() {
    if(isset($_POST["pc-token"])) {
        return sanitize_text_field($_POST["pc-token"]);
    } else {
        return FALSE;
    }
}

function powercaptcha_verify_token($token) {
    if( empty ( $token ) ) {
        $error_message = "POWER CAPTCHA token is empty.";
        if(WP_DEBUG) {
            trigger_error($error_message, E_USER_NOTICE);
        }
        return array(
            "success" => FALSE,
            "reason" => $error_message
        );
    } 

    $request_url = powercaptcha()->get_token_verification_url();
    $request_body = array(
        'secret' => powercaptcha()->get_secret_key(),
        'token' => $token
    );
    $request_body = json_encode($request_body);
        
    if(WP_DEBUG) {
        trigger_error("POWER CAPTCHA token verification request. Request URL: $request_url / Request body: $request_body", E_USER_NOTICE);
    }

    // https://developer.wordpress.org/reference/functions/wp_remote_request/
    $response = wp_remote_request(
        $request_url, 
        array(
            'method' => 'POST', 
            'headers' => ['Content-Type' => 'application/json'],
            'body' => $request_body
        )
    );

    $response_code = wp_remote_retrieve_response_code($response);
    $response_body = json_decode(wp_remote_retrieve_body($response), true);

    if($response_code == 200 && isset($response_body['success']) && $response_body['success'] == true) {
        if(WP_DEBUG) {
            trigger_error("POWER CAPTCHA token was verified sucessfully. Token: $token", E_USER_NOTICE);
        }
        return array(
            'success' => TRUE,
            'reason' => "Token validtated succesfully."
        );
    } 
  
    // error handling 
    if($response_code == 400 && isset($response_body['errors']) && is_array($response_body['errors'])) {
        // backend error codes:
        // missing_token = There was no token provided in your request
        // missing_secret = There was no secret provided in your request
        // invalid_secret = The secret you provided was incorrect
        // invalid_token = The token you provided was incorrect
        if( in_array('missing_secret', $response_body['errors']) || in_array('invalid_secret', $response_body['errors']) ) {
            $error_message = "POWER CAPTCHA secret key is invalid or missing.";
            trigger_error($error_message, E_USER_ERROR);
        } else {
            $error_message = "POWER CAPTCHA token is invalid or empty.";
            if(WP_DEBUG) {
                trigger_error($error_message, E_USER_NOTICE);
            }
        } 
    } else if( is_wp_error ( $response )) {
        $error_message = "Could not connnect to POWER CAPTCHA API. WordPress error errormessage: $response->get_error_message()";
        trigger_error($error_message, E_USER_ERROR);
    } else {
        $error_message = "Unknown response from POWER CAPTCHA API. Response code: $response_code / Response body: $response_body";
        trigger_error($error_message, E_USER_ERROR);
    }

    return array(
        'success' => FALSE,
        'reason' => $error_message
    );
}

function powercaptcha_user_error_message() {
    return __('Die Übertragung des Formulars wurde von POWER CAPTCHA verhindert. Bitte versuchen Sie es zu einem späteren Zeitpunkt erneut.'); // TODO erromessage?
    // The anti-spam protection has prevented the form from being submitted. Please try again at a later time.
}


function powercaptcha_echo_javascript_tags() {
    if(!powercaptcha()->is_configured()) {
        return;
    }

    echo '<script src="'. powercaptcha()->get_javascript_url() .'" type="text/javascript"></script>';
}