<?php

namespace Power_Captcha_WP;

defined('POWER_CAPTCHA_PATH') || exit;


add_action('powercaptcha_register_integration', function ($powerCaptcha) {
    $powerCaptcha->register_integration(new Integration_WooCommerce_Login());
});

class Integration_WooCommerce_Login extends Integration {

    public function __construct() {
        $this->id = 'woocommerce_login';
        $this->setting_title = __('WooCommerce Login', 'power-captcha');
        $this->setting_description = __('Enable protection for the WooCommerce My Account login form.', 'power-captcha');
    }

    public function init() {
        add_action('woocommerce_login_form', [$this, 'display_widget'], 10, 0);
        add_action('woocommerce_login_form', [$this, 'enqueue_script'], 10, 0);

        add_filter('woocommerce_process_login_errors', [$this, 'verification'], 20, 3);
    }

    public function disable_verification() {
        remove_filter('woocommerce_process_login_errors', [$this, 'verification'], 20);
    }

    public function display_widget() {
        parent::echo_widget_html('#username', true, 'form-row');
    }

    public function enqueue_script() {
        parent::enqueue_scripts();
    }

    public function verification(\WP_Error $validation_error, string $user_login, string $user_passsword) {
        // WooCommerce will later call the wp_signon method, which triggers the 'authenticate' filter.
        // This 'authenticate' filter is also used to verifiy the token for the wordpress_login integration.
        // To avoid double verification, we disable the wordpress_login verification here.
        powercaptcha()->disable_integration_verification('wordpress_login');

        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification.Missing -- Reason: The raw input is necessary and only used to verify the request via the POWER CAPTCHA API. Nonce generation and verification are handled by WooCommerce.
        $verification = $this->verify_token($_POST['username'] ?? null);
        if(FALSE === $verification->is_success()) {
            $validation_error->add($verification->get_error_code(), $verification->get_user_message(false));
        }
    
        return $validation_error; 
    }

}
