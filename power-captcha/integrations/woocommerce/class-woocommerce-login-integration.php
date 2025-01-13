<?php

namespace Power_Captcha_WP;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly

add_action(
	'powercaptcha_register_integration',
	function ( $power_captcha ) {
		$power_captcha->register_integration( new WooCommerce_Login_Integration() );
	}
);

class WooCommerce_Login_Integration extends Integration {

	public function __construct() {
		$this->id                  = 'woocommerce_login';
		$this->setting_title       = __( 'WooCommerce Login', 'power-captcha' );
		$this->setting_description = __( 'Enable protection for the WooCommerce My Account login form.', 'power-captcha' );
	}

	public function init() {
		add_action( 'woocommerce_login_form', array( $this, 'display_widget' ), 10, 0 );
		add_action( 'woocommerce_login_form', array( $this, 'enqueue_script' ), 10, 0 );

		add_filter( 'woocommerce_process_login_errors', array( $this, 'verification' ), 20, 3 );
	}

	public function disable_verification() {
		remove_filter( 'woocommerce_process_login_errors', array( $this, 'verification' ), 20 );
	}

	public function display_widget() {
		parent::echo_widget_html( '#username', true, 'form-row' );
	}

	public function enqueue_script() {
		parent::enqueue_scripts();
	}

	public function verification( \WP_Error $validation_error, string $user_login, string $user_passsword ) {
		// WooCommerce will later call the wp_signon method, which triggers the 'authenticate' filter.
		// This 'authenticate' filter is also used to verifiy the token for the wordpress_login integration.
		// To avoid double verification, we disable the wordpress_login verification here.
		powercaptcha()->disable_integration_verification( 'wordpress_login' );

        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Reason: Nonce generation and verification are handled by WooCommerce.
		$username     = isset( $_POST['username'] ) ? sanitize_text_field( wp_unslash( $_POST['username'] ) ) : null;
		$verification = $this->verify_token( $username );
		if ( false === $verification->is_success() ) {
			$validation_error->add( $verification->get_error_code(), $verification->get_user_message( false ) );
		}

		return $validation_error;
	}
}
