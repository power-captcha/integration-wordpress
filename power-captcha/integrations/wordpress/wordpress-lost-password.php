<?php

namespace Power_Captcha_WP;

defined('POWER_CAPTCHA_PATH') || exit;

add_action('powercaptcha_register_integration', function ($powerCaptcha) {
    $powerCaptcha->register_integration(new Integration_WordPress_WooCommerce_Lost_Password());
});


class Integration_WordPress_WooCommerce_Lost_Password extends Integration {

    // Note: We use a single integration for both the WordPress Lost Password and WooCommerce Lost Password functionality.
    // This is necessary because the WooCommerce process_lost_password function also triggers the WordPress lostpassword_post action.
    // As a result, we cannot distinguish during token verification whether the request originates from the WooCommerce form or the WordPress form.

    public function __construct() {
        $this->id = 'wordpress_lost_password';
        $this->setting_title = __('WordPress / WooCommerce Lost Password', 'power-captcha');
        $this->setting_description = __('Enable protection for the WordPress and WooCommerce lost/reset password form.', 'power-captcha');
    }

    public function init() {
        // WordPress lost password form
        add_action('lostpassword_form', [$this, 'display_widget_wordpress']);
        add_action('lostpassword_form', [$this, 'enqueue_script']);

        // WooCommerce lost password form
        add_action('woocommerce_lostpassword_form', [$this, 'display_widget_woocomerce']);
        add_action('woocommerce_lostpassword_form', [$this, 'enqueue_script']);

        // Verification for both WordPress and WooCommerce lost password
        add_action('lostpassword_post', [$this, 'verification'], 10, 2);
    }

    public function display_widget_wordpress() {
        parent::echo_widget_html('#user_login', true, '', 'margin-bottom: 16px');
    }

    public function display_widget_woocomerce() {
        parent::echo_widget_html('#user_login', true, 'form-row');
    }

    public function enqueue_script() {
        parent::enqueue_scripts();
    }

    public function verification(\WP_Error $errors, \WP_User|false $user_data) {
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification.Missing -- Reason: The raw input is necessary and only used to verify the request via the POWER CAPTCHA API. Nonce generation and verification are handled by WordPress.
        $verification = $this->verify_token($_POST['user_login'] ?? null); 
        if(FALSE === $verification->is_success()) {
            $errors->add($verification->get_error_code(), $verification->get_user_message());
        }
    }

}