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

    public function widget_html($userInputField = '', $userInputFieldRequried = false, $cssClass = '', $style = '') {
        $widgetHtml =   '<div';
        $widgetHtml .=      ' data-pc-wp-check-mode="'.powercaptcha()->get_check_mode().'"';
        $widgetHtml .=      ' data-pc-wp-integration="'.esc_attr($this->id).'"';
        if(!empty($userInputField)) {
            $widgetHtml .=  ' data-pc-wp-user-field="'.esc_attr($userInputField).'"';
            if($userInputFieldRequried) {
                $widgetHtml .= ' data-pc-wp-user-field-required ="1"';
            }
        }
        $widgetHtml .=      ' class="'.esc_attr($cssClass).'"';
        $widgetHtml .=      ' style="'.esc_attr($style).'"';
        $widgetHtml .=  '></div>';
        return $widgetHtml;
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
                $log_message = "The form does not contain a token field.";
                if(WP_DEBUG) {
                    trigger_error($log_message, E_USER_NOTICE);
                }
                return new VerificationResult(false, powercaptcha()::ERROR_CODE_NO_TOKEN_FIELD);
            }
        }

        if( empty ( $token ) ) {
            $log_message = "POWER CAPTCHA token is empty.";
            if(WP_DEBUG) {
                trigger_error($log_message, E_USER_NOTICE);
            }
            return new VerificationResult(false, powercaptcha()::ERROR_CODE_MISSING_TOKEN);
        } 
    
        $request_url = powercaptcha()->get_token_verification_url();
        $request_body = array(
            'secret' => powercaptcha()->get_secret_key($this->get_id()),
            'token' => $token,
            'clientUid' => powercaptcha()->get_client_uid(),
            'name' => $username ?? ''
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
                    'X-API-Key' => powercaptcha()->get_api_key($this->get_id())
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
            return new VerificationResult($verified,  $error_code);
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
    
        return new VerificationResult(false,  $error_code);
    }

    public abstract function init();
}