<?php

namespace PowerCaptcha_WP;

class VerificationResult {

    private bool $success;
    private string|null $error_code;

    public function __construct(bool $success, string|null $error_code) {
        $this->success = $success;
        $this->error_code = $error_code;
    }

    public function is_success() : bool {
        return $this->success;
    }

    public function get_error_code() : string {
        return $this->error_code;
    }

    public function get_user_message($error_prefix = true) : string {
        $output = '';
        if($error_prefix) {
            $output .= __('<strong>Error:</strong>', 'power-captcha').' ';
        }
        
        $output .= __('Submission of the form was blocked by POWER CAPTCHA. Please try again later.' , 'power-captcha');

        if( is_null($this->error_code) ) {
            return $output;
        }

        if($this->error_code === powercaptcha()::ERROR_CODE_NO_TOKEN_FIELD) {
            $error_message = __('The form does not contain a token field.', 'power-captcha');
        } else if($this->error_code === powercaptcha()::ERROR_CODE_MISSING_TOKEN) {
            $error_message = __('The token is empty.', 'power-captcha');
        } else if($this->error_code === powercaptcha()::ERROR_CODE_INVALID_TOKEN) {
            $error_message = __('The token is invalid.', 'power-captcha');
        } else if($this->error_code === powercaptcha()::ERROR_CODE_TOKEN_NOT_VERIFIED) {
            $error_message = __('The token has not been verified.', 'power-captcha');
        } else if($this->error_code === powercaptcha()::ERROR_CODE_INVALID_SECRET) {
            $error_message = __('The secret key is missing or invalid.', 'power-captcha');
        } else if($this->error_code === powercaptcha()::ERROR_CODE_API_ERROR) {
            $error_message = __('Error connecting to the API. API Key maybe invalid.', 'power-captcha');
        } else {
            $error_message = $this->error_code; // fallback
        }

        $output .= ' '.sprintf(
            /** translators %s: The error message */
            __('Error message: %s', 'power-captcha'),
            $error_message
        );
        
        return $output;
    }

}