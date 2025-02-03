<?php

namespace Power_Captcha_WP;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly

add_action(
	'powercaptcha_register_integration',
	function ( $power_captcha ) {
		$power_captcha->register_integration( new WooCommerce_Checkout_Integration() );
	}
);

class WooCommerce_Checkout_Integration extends Integration {

	public function __construct() {
		$this->id = 'woocommerce_checkout';
	}

	public function init() {
		add_action( 'woocommerce_after_checkout_billing_form', array( $this, 'display_widget' ), 10, 0 );
		add_action( 'woocommerce_after_checkout_billing_form', array( $this, 'enqueue_script' ), 11, 0 );

		add_action( 'woocommerce_after_checkout_validation', array( $this, 'verification' ), 10, 2 );
		// Note: We can't use the woocommerce_before_checkout_validation hook because it is executed multiple times during the checkout.
		// Another hook alternative could be woocommerce_checkout_process, but woocommerce_after_checkout_validation seems to be the most suitable,
		// as it is executed after the address and payment method have been validated.
	}

	public function textdomain_loaded() {
		$this->setting_title       = __( 'WooCommerce Checkout', 'power-captcha' );
		$this->setting_description = __( 'Enable protection for the WooCommerce checkout form.', 'power-captcha' );
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
		$username_hash = $this->get_username_hash( 'billing_email' );
		$verification  = $this->verify_token( $username_hash );
		if ( false === $verification->is_success() ) {
			$errors->add( $verification->get_error_code(), $verification->get_user_message() );
		}
	}
}
