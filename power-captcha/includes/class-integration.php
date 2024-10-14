<?php

namespace PowerCaptcha_WP;

abstract class Integration {
    const SETTING_ENABLED_NAME_PREFIX = 'powercaptcha_integration_enabled_';

    protected string $id;
    protected string $setting_title;
    protected string $setting_description;

    public function __construct() {}

    public function get_id() : string {
        return $this->id;
    }

    public function get_setting_name() : string {
        return self::SETTING_ENABLED_NAME_PREFIX . $this->get_id();
    }

    public function get_setting_title() : string {
        return $this->setting_title;
    }

    public function get_setting_description() : string {
        return $this->setting_description;
    }

    // public function get_file_paths() : array {
    //     return $this->file_paths;
    // }

    public function is_enabled() {
        return (get_option($this->get_setting_name()) == 1);
    }

    public function enqueue_scripts() {
        wp_enqueue_script('powercaptcha-wp');
    }

    public function echo_widget_html($userInputField = '', $userInputFieldRequried = false, $cssClass = '', $style = '') {
        echo '<div ';
        echo ' data-pc-wp-check-mode="'.esc_attr(powercaptcha()->get_check_mode()).'"';
        echo ' data-pc-wp-integration="'.esc_attr($this->id).'"';
        if(!empty($userInputField)) {
            echo ' data-pc-wp-user-field="'.esc_attr($userInputField).'"';
            if($userInputFieldRequried) {
                echo ' data-pc-wp-user-field-required ="1"';
            }
        }
        echo ' class="'.esc_attr($cssClass).'"';
        echo ' style="'.esc_attr($style).'"';
        echo '></div>';
    }

    public function fetch_token_from_post_request() {
        if(isset($_POST["pc-token"])) {
            return $_POST["pc-token"];
        } else {
            return FALSE;
        }
    }

    public function verify_token(string $username = null, string $token = null, string $clientUid = null) : VerificationResult {
        if( is_null($token) ) {
            $token = $this->fetch_token_from_post_request();
            if($token === FALSE) {
                $this->log('Token verification: The request does not contain a token field.');
                return new VerificationResult(false, powercaptcha()::ERROR_CODE_NO_TOKEN_FIELD);
            }
        }

        if( empty ( $token ) ) {
            $this->log('Token verification: The request contains an empty token.');
            return new VerificationResult(false, powercaptcha()::ERROR_CODE_MISSING_TOKEN);
        } 
    
        $request_url = powercaptcha()->get_token_verification_url();
        $request_body = array(
            'secret' => powercaptcha()->get_secret_key($this->get_id()),
            'token' => $token,
            'clientUid' => powercaptcha()->get_client_uid(),
            'name' => $username ?? ''
        );
        $request_body = wp_json_encode($request_body);
            
        $this->log('Token verification: Starting verification API request.', ['API URL' => $request_url, 'Request Body' => $request_body ]);
    
        // https://developer.wordpress.org/reference/functions/wp_remote_request/
        $response = wp_remote_request(
            $request_url, 
            [
                'method' => 'POST', 
                'headers' => [
                    'Content-Type' => 'application/json',
                    'X-API-Key' => powercaptcha()->get_api_key($this->get_id())
                ],
                'body' => $request_body
            ]
        );
    
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = json_decode(wp_remote_retrieve_body($response), true);
    
        if($response_code == 200 && isset($response_body['success'])) {
            $verified = boolval($response_body['success']);
            if($verified) {
                $this->log('Token verification: Token successfully verified.', ['Token' => $token ]);
                return new VerificationResult(TRUE, NULL);
            } else {
                $this->log('Token verification: Token not verified. Token was not solved by user or mismatch of clientUid or username.', ['Token' => $token ]);
                return new VerificationResult(FALSE, powercaptcha()::ERROR_CODE_TOKEN_NOT_VERIFIED);
            }
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
                $error_code = powercaptcha()::ERROR_CODE_INVALID_SECRET;
                $this->log('Token verification: Error verifiying the token.', ['Reason' => 'Secret Key is invalid or missing. Please check your Secret Key!']);
            } else {
                $error_code = powercaptcha()::ERROR_CODE_INVALID_TOKEN;
                $this->log('Token verification: Token not valid.', ['Reason' => 'User sent invalid or expired token.']);
            } 
        } else if( is_wp_error ( $response )) {
            $error_code = powercaptcha()::ERROR_CODE_API_ERROR;
            $this->log('Token verification: Error verifiying the token.', ['Reason' => 'Could not connnect to POWER CAPTCHA API.', 'WordPress error message' => $response->get_error_message()]);
        } else {
            $error_code = powercaptcha()::ERROR_CODE_API_ERROR;
            $this->log('Token verification: Error verifiying the token.', ['Reason' => 'Unknown response from POWER CAPTCHA API.', 'Response code' => $response_code, 'Response body' => $response_body]);
        }
    
        return new VerificationResult(false,  $error_code);
    }

    protected function log(string $message, array|object $data = null) {
        if(FALSE === WP_DEBUG) {
            return;
        }

        if( isset($data) ) {
            error_log('POWER CAPTCHA (Integration '.$this->get_id().') ' . $message . PHP_EOL . 'Details: '. print_r($data, true));
        } else {
            error_log('POWER CAPTCHA (Integration '.$this->get_id().') ' . $message);
        }

    }

    public abstract function init();
}