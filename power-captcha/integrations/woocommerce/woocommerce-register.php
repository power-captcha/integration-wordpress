<?php

namespace PowerCaptcha_WP;

defined('POWER_CAPTCHA_PATH') || exit;

add_action('powercaptcha_register_integration', function ($powerCaptcha) {
    $powerCaptcha->register_integration(new Integration_WooCommerce_Register());
});

class Integration_WooCommerce_Register extends Integration {

    public function __construct() {
        $this->id = 'woocommerce_register';
        $this->setting_title = __('WooCommerce Registration', 'power-captcha');
        $this->setting_description = __('Enable protection for the WooCommerce My Account register form.', 'power-captcha');
    }

    public function init() {
        add_action('woocommerce_register_form', [$this, 'display_widget'], 100, 0);
        add_action('woocommerce_register_form', [$this, 'enqueue_script'], 100, 0);

        add_filter('woocommerce_process_registration_errors', [$this, 'verification'], 10, 4);
        // Note: We can't use the woocommerce_register_post hook because it is also executed during checkout when registering. 
        // This would lead to problems if the captcha is also enabled for the WooCommerce checkout.
    }

    public function display_widget() {
        parent::echo_widget_html('#reg_email', true, 'form-row');
    }

    public function enqueue_script() {
        parent::enqueue_scripts();
    }

    public function verification(\WP_Error $validation_error,  string $username, string $password, string $email) {
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification.Missing -- Reason: The raw input is necessary and only used to verify the request via the POWER CAPTCHA API. Nonce generation and verification are handled by WooCommerce.
        $verification = $this->verify_token($_POST['email'] ?? null);
        if(FALSE === $verification->is_success()) {
            $validation_error->add($verification->get_error_code(), $verification->get_user_message(false));
        }
    
        return $validation_error; 
    }

}