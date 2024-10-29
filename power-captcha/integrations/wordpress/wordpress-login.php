<?php

namespace Power_Captcha_WP;

defined('POWER_CAPTCHA_PATH') || exit;

add_action('powercaptcha_register_integration', function ($powerCaptcha) {
    $powerCaptcha->register_integration(new Integration_WordPress_Login());
});

class Integration_WordPress_Login extends Integration {

    public function __construct() {
        $this->id = 'wordpress_login';
        $this->setting_title = __('WordPress Login', 'power-captcha');
        $this->setting_description = __('Enable protection for the WordPress login form.', 'power-captcha');
    }

    public function init() {
        add_action('login_form', [$this, 'display_widget'], 10, 0);
        add_action('login_form', [$this, 'enqueue_script'], 11, 0);

        add_filter('authenticate', [$this, 'verification'], 20, 3);
    }

    public function display_widget() {
        parent::echo_widget_html('#user_login', false, '', 'margin-bottom: 16px');
    }

    public function enqueue_script() {
        parent::enqueue_scripts();
    }

    public function verification(null|\WP_User|\WP_Error $user, string $username, string $password) {
        // TODO merge this verification with WooCoommerce Login integration. note: WooCommerce login uses the field $_POST['username'] for username.

        if( is_wp_error($user) ) {
            return $user;
        }

        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification.Missing -- Reason: The raw input is necessary and only used to verify the request via the POWER CAPTCHA API. Nonce generation and verification are handled by WordPress.
        $verification = $this->verify_token($_POST['log'] ?? null); 
        if(FALSE === $verification->is_success()) {
            return new \WP_Error($verification->get_error_code(), $verification->get_user_message());
        }

        return $user;
    }

}