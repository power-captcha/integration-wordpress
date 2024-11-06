<?php

namespace Power_Captcha_WP;

defined( 'POWER_CAPTCHA_PATH' ) || exit;

add_action(
	'powercaptcha_register_integration',
	function ( $power_captcha ) {
		$power_captcha->register_integration( new Integration_WooCommerce_Checkout() );
	}
);

class Integration_WooCommerce_Checkout extends Integration {

	public function __construct() {
		$this->id                  = 'woocommerce_checkout';
		$this->setting_title       = __( 'WooCommerce Checkout', 'power-captcha' );
		$this->setting_description = __( 'Enable protection for the WooCommerce checkout form.', 'power-captcha' );
	}

	public function init() {
		add_action( 'woocommerce_after_checkout_billing_form', array( $this, 'display_widget' ), 10, 0 );
		add_action( 'woocommerce_after_checkout_billing_form', array( $this, 'enqueue_script' ), 11, 0 );

		add_action( 'woocommerce_after_checkout_validation', array( $this, 'verification' ), 10, 2 );
		// Note: We can't use the woocommerce_before_checkout_validation hook because it is executed multiple times during the checkout.
		// Another hook alternative could be woocommerce_checkout_process, but woocommerce_after_checkout_validation seems to be the most suitable,
		// as it is executed after the address and payment method have been validated.
	}

	public function disable_verification() {
		remove_action( 'woocommerce_after_checkout_validation', array( $this, 'verification' ), 10 );
	}

	public function display_widget() {
		parent::echo_widget_html( '#billing_email', true, 'form-row' );
	}

	public function enqueue_script() {
		// enqueue additional javascript for woocommerce checkout
		wp_enqueue_script(
			'powercaptcha-woocommerce-checkout',
			plugin_dir_url( __FILE__ ) . 'public/power-captcha-woocommerce-checkout.js',
			array( 'jquery', 'powercaptcha-wp' ),
			POWER_CAPTCHA_PLUGIN_VERSION,
			true
		);
	}

	public function verification( array $fields, \WP_Error $errors ) {
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification.Missing -- Reason: The raw input is necessary and only used to verify the request via the POWER CAPTCHA API. Nonce generation and verification are handled by WooCommerce.
		$verification = $this->verify_token( $_POST['billing_email'] ?? null );
		if ( false === $verification->is_success() ) {
			$errors->add( $verification->get_error_code(), $verification->get_user_message() );
		}
	}
}
