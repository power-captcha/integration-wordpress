<?php

namespace Power_Captcha_WP;

defined( 'POWER_CAPTCHA_PATH' ) || exit;

add_action(
	'powercaptcha_register_integration',
	function ( $power_captcha ) {
		$power_captcha->register_integration( new WordPress_Register_Integration() );
	}
);

class WordPress_Register_Integration extends Integration {

	public function __construct() {
		$this->id                  = 'wordpress_register';
		$this->setting_title       = __( 'WordPress Registration', 'power-captcha' );
		$this->setting_description = __( 'Enable protection for the WordPress registration form.', 'power-captcha' );
	}

	public function init() {
		add_action( 'register_form', array( $this, 'display_widget' ) );
		add_action( 'register_form', array( $this, 'enqueue_script' ) );

		add_action( 'register_post', array( $this, 'verification' ), 10, 3 );
	}

	public function disable_verification() {
		remove_action( 'register_post', array( $this, 'verification' ), 10 );
	}

	public function display_widget() {
		parent::echo_widget_html( '#user_email', true, '', 'margin-bottom: 16px' );
	}

	public function enqueue_script() {
		parent::enqueue_scripts();
	}

	public function verification( string $sanitized_user_login, string $user_email, \WP_Error $errors ) {
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification.Missing -- Reason: The raw input is necessary and only used to verify the request via the POWER CAPTCHA API. Nonce generation and verification are handled by WordPress.
		$verification = $this->verify_token( $_POST['user_email'] ?? null );
		if ( false === $verification->is_success() ) {
			$errors->add( $verification->get_error_code(), $verification->get_user_message() );
		}
	}
}
