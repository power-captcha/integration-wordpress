
<?php
function powercaptcha_get_token_from_post_request() {
    if(isset($_POST["pc-token"])) {
        return $_POST["pc-token"];
    } else {
        return FALSE;
    }
}

function powercaptcha_get_username_from_post_request() {
    if(isset($_POST["pc-username"])) {
        return $_POST["pc-username"];
    } else {
        return FALSE;
    }
}

function powercaptcha_verify_token($token, $username = null, $ip = null) {
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
        'token' => $token,
        'name' => $username ?? '',
        'ip' => $ip ?? $_SERVER['REMOTE_ADDR']
    );
    $request_body = json_encode($request_body);
        
    if(WP_DEBUG) {
        trigger_error("POWER CAPTCHA token verification request. Request URL: $request_url / Request body: $request_body", E_USER_NOTICE);
    }

    // https://developer.wordpress.org/reference/functions/wp_remote_request/
    $response = wp_remote_request(
        $request_url, 
        [
            'method' => 'POST', 
            'headers' => [
                'Content-Type' => 'application/json',
                'X-API-Key' => powercaptcha()->get_api_key()
            ],
            'body' => $request_body
        ]
    );

    $response_code = wp_remote_retrieve_response_code($response);
    $response_body = json_decode(wp_remote_retrieve_body($response), true);

    if($response_code == 200 && isset($response_body['success'])) {
        $verified = boolval($response_body['success']);
        if(WP_DEBUG) {
            $verified_text = $verified ? 'Token is valid' : 'Token is invalid';
            trigger_error("POWER CAPTCHA token was verified sucessfully. Result: $verified_text / Token: $token", E_USER_NOTICE);
        }
        return [
            'success' => $verified,
            'reason' => "Token validtated succesfully."
        ];
    } 
  
    // error handling 
    if($response_code == 400) {
        // backend error codes:
        // MISSING_TOKEN = There was no token provided in your request
        // MISSING_SECRET = There was no secret provided in your request
        // INVALID_SECRET = The secret you provided was incorrect
        // INVALID_TOKEN = The token you provided was incorrect
        // PC_OFFLINE = POWER CAPTCHA API is offline
        if(isset($response_body['errors']) 
            && (in_array('MISSING_SECRET', $response_body['errors']) || in_array('INVALID_SECRET', $response_body['errors'])) ) {
            $error_message = "POWER CAPTCHA secret key is invalid or missing.";
            trigger_error($error_message, E_USER_ERROR);
        } else {
            $error_message = "POWER CAPTCHA token is invalid or empty.";
            if(WP_DEBUG) {
                trigger_error($error_message, E_USER_NOTICE);
            }
        } 
    } else if( is_wp_error ( $response )) {
        $error_message = "Could not connnect to POWER CAPTCHA API. WordPress error errormessage: {$response->get_error_message()}";
        trigger_error($error_message, E_USER_ERROR);
    } else {
        $error_message = "Unknown response from POWER CAPTCHA API. Response code: $response_code / Response body: $response_body";
        trigger_error($error_message, E_USER_ERROR);
    }

    return [
        'success' => FALSE,
        'reason' => $error_message
    ];
}

function powercaptcha_user_error_message() {
    return __('Die Übertragung des Formulars wurde von POWER CAPTCHA verhindert. Bitte versuchen Sie es zu einem späteren Zeitpunkt erneut.'); // TODO erromessage?
    // The anti-spam protection has prevented the form from being submitted. Please try again at a later time.
}


function powercaptcha_javascript_tags($display = true) {
    if(!powercaptcha()->is_configured()) {
        return;
    }
    $javascript_tag = '<script src="'. powercaptcha()->get_javascript_url() .'" type="text/javascript"></script>';
    if($display) {
        echo $javascript_tag;
    } else {
        return $javascript_tag;
    }
}