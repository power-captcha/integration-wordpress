<?php

defined('POWER_CAPTCHA_PATH') || exit;

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

function powercaptcha_verify_token($token, $username = null, $ip = null, $integration = null) {
    if( empty ( $token ) ) {
        $log_message = "POWER CAPTCHA token is empty.";
        $error_code = powercaptcha()::ERROR_CODE_MISSING_TOKEN;
        if(WP_DEBUG) {
            trigger_error($log_message, E_USER_NOTICE);
        }
        return array(
            "success" => FALSE,
            "error_code" => $error_code
        );
    } 

    $request_url = powercaptcha()->get_token_verification_url();
    $request_body = array(
        'secret' => powercaptcha()->get_secret_key($integration),
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
                'X-API-Key' => powercaptcha()->get_api_key($integration)
            ],
            'body' => $request_body
        ]
    );

    $response_code = wp_remote_retrieve_response_code($response);
    $response_body = json_decode(wp_remote_retrieve_body($response), true);

    if($response_code == 200 && isset($response_body['success'])) {
        $verified = boolval($response_body['success']);
        $error_code = ($verified ? NULL : powercaptcha()::ERROR_CODE_TOKEN_NOT_VERIFIED);
        if(WP_DEBUG) {
            $verified_text = $verified ? 'Token is valid' : 'Token is invalid';
            trigger_error("POWER CAPTCHA token was verified sucessfully. Result: $verified_text / Token: $token", E_USER_NOTICE);
        }
        return [
            'success' => $verified,
            'error_code' => $error_code
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
            $log_message = "POWER CAPTCHA secret key is invalid or missing.";
            $error_code = powercaptcha()::ERROR_CODE_INVALID_SECRET;
            trigger_error($log_message, E_USER_WARNING);
        } else {
            $log_message = "POWER CAPTCHA token is invalid or empty.";
            $error_code = powercaptcha()::ERROR_CODE_INVALID_TOKEN;
            if(WP_DEBUG) {
                trigger_error($log_message, E_USER_NOTICE);
            }
        } 
    } else if( is_wp_error ( $response )) {
        $log_message = "Could not connnect to POWER CAPTCHA API. WordPress error errormessage: {$response->get_error_message()}";
        $error_code = powercaptcha()::ERROR_CODE_API_ERROR;
        trigger_error($log_message, E_USER_WARNING);
    } else {
        $log_message = "Unknown response from POWER CAPTCHA API. Response code: $response_code / Response body: $response_body";
        $error_code = powercaptcha()::ERROR_CODE_API_ERROR;
        trigger_error($log_message, E_USER_WARNING);
    }

    return [
        'success' => FALSE,
        'error_code' => $error_code
    ];
}

function powercaptcha_user_error_message($error_code = NULL, $prefix = true) {
    $output = '';
    if($prefix) {
        $output .= __('<strong>Error:</strong>', 'power-captcha').' ';
    }
    
    $output .= __('Submission of the form was blocked by POWER CAPTCHA. Please try again later.' , 'power-captcha');
    if($error_code !== NULL) {
        if($error_code === powercaptcha()::ERROR_CODE_NO_TOKEN_FIELD) {
            $error_message = __('The form does not contain a token field.', 'power-captcha');
        } else if($error_code === powercaptcha()::ERROR_CODE_MISSING_TOKEN) {
            $error_message = __('The token is empty.', 'power-captcha');
        } else if($error_code === powercaptcha()::ERROR_CODE_INVALID_TOKEN) {
            $error_message = __('The token is invalid.', 'power-captcha');
        } else if($error_code === powercaptcha()::ERROR_CODE_TOKEN_NOT_VERIFIED) {
            $error_message = __('The token has not been verified.', 'power-captcha');
        } else if($error_code === powercaptcha()::ERROR_CODE_INVALID_SECRET) {
            $error_message = __('The secret key is missing or invalid.', 'power-captcha');
        } else if($error_code === powercaptcha()::ERROR_CODE_API_ERROR) {
            $error_message = __('Error connecting to the API. API Key maybe invalid.', 'power-captcha');
        } else {
            $error_message = $error_code; // fallback
        }

        $output .= ' '.sprintf(
            /** translators %s: The error message */
            __('Error message: %s', 'power-captcha'),
            $error_message
        );
    }
    return $output;
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


function powercaptcha_register_javascript() {
    wp_register_script(powercaptcha()::JAVASCRIPT_HANDLE, powercaptcha()->get_javascript_url());
}
add_action( 'wp_enqueue_scripts', 'powercaptcha_register_javascript' );

function powercaptcha_enqueue_javascript() {
    if(!powercaptcha()->is_configured()) {
        return;
    }
    wp_enqueue_script(powercaptcha()::JAVASCRIPT_HANDLE);
}

function powercaptcha_enqueue_jquery() {
    wp_enqueue_script('jquery');
}