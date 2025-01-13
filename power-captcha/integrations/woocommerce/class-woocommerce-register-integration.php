<?php

namespace Power_Captcha_WP;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly

add_action(
	'powercaptcha_register_integration',
	function ( $power_captcha ) {
		$power_captcha->register_integration( new WooCommerce_Register_Integration() );
	}
);

class WooCommerce_Register_Integration extends Integration {

	public function __construct() {
		$this->id                  = 'woocommerce_register';
		$this->setting_title       = __( 'WooCommerce Registration', 'power-captcha' );
		$this->setting_description = __( 'Enable protection for the WooCommerce My Account register form.', 'power-captcha' );
	}

	public function init() {
		add_action( 'woocommerce_register_form', array( $this, 'display_widget' ), 100, 0 );
		add_action( 'woocommerce_register_form', array( $this, 'enqueue_script' ), 100, 0 );

		add_filter( 'woocommerce_process_registration_errors', array( $this, 'verification' ), 10, 4 );
		// Note: We can't use the woocommerce_register_post hook because it is also executed during checkout when registering.
		// This would lead to problems if the captcha is also enabled for the WooCommerce checkout.
	}

	public function disable_verification() {
		remove_filter( 'woocommerce_process_registration_errors', array( $this, 'verification' ), 10 );
	}

	public function display_widget() {
		parent::echo_widget_html( '#reg_email', true, 'form-row' );
	}

	public function enqueue_script() {
		parent::enqueue_scripts();
	}

	public function verification( \WP_Error $validation_error, string $username, string $password, string $email ) {
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Reason: Nonce generation and verification are handled by WooCommerce.
		$username     = isset( $_POST['email'] ) ? sanitize_text_field( wp_unslash( $_POST['email'] ) ) : null;
		$verification = $this->verify_token( $username );
		if ( false === $verification->is_success() ) {
			$validation_error->add( $verification->get_error_code(), $verification->get_user_message( false ) );
		}

		return $validation_error;
	}
}
