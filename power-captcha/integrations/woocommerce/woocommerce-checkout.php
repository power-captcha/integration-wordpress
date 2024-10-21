<?php

namespace PowerCaptcha_WP;

defined('POWER_CAPTCHA_PATH') || exit;

add_action('powercaptcha_register_integration', function ($powerCaptcha) {
    $powerCaptcha->register_integration(new Integration_WooCommerce_Checkout());
});

class Integration_WooCommerce_Checkout extends Integration {

    public function __construct() {
        $this->id = 'woocommerce_checkout';
        $this->setting_title = __('WooCommerce Checkout', 'power-captcha');
        $this->setting_description = __('Enable protection for the WooCommerce checkout form.', 'power-captcha');
    }

    public function init() {
        add_action('woocommerce_after_checkout_billing_form', [$this, 'display_widget'], 10, 0);
        add_action('woocommerce_after_checkout_billing_form', [$this, 'enqueue_script'], 11, 0);

        add_action('woocommerce_after_checkout_validation', [$this, 'verification'], 10, 2);
        // Note: We can't use the woocommerce_before_checkout_validation hook because it is executed multiple times during the checkout.
        // Another hook alternative could be woocommerce_checkout_process, but woocommerce_after_checkout_validation seems to be the most suitable, 
        // as it is executed after the address and payment method have been validated.
    }

    public function display_widget() {
        parent::echo_widget_html('#billing_email', true, 'form-row');
    }

    public function enqueue_script() {
        parent::enqueue_scripts(); // TODO is this needed?? 
        // because is 'powercaptcha-woocommerce-checkout' depends on powercaptcha-wp 

        // enqueue additional javascript for woocommerce checkout
        wp_enqueue_script(
            'powercaptcha-woocommerce-checkout', 
            plugin_dir_url( __FILE__ )  . 'public/power-captcha-woocommerce-checkout.js',  
            ['jquery', 'powercaptcha-wp'], 
            POWER_CAPTCHA_PLUGIN_VERSION, 
            false 
        );
    }

    public function verification(array $fields, \WP_Error $errors) {
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Reason: Input is only used to verify the request via POWER CAPTCHA API. Nonce generation and verification are managed by WooCommerce.
        $verification = $this->verify_token($_POST['billing_email'] ?? null);
        if(FALSE === $verification->is_success()) {
            $errors->add($verification->get_error_code(), $verification->get_user_message());
        }
    }

}