<?php

namespace Power_Captcha_WP;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly

add_action(
	'powercaptcha_register_integration',
	function ( $power_captcha ) {
		$power_captcha->register_integration( new WordPress_WooCommerce_Lost_Password_Integration() );
	}
);


class WordPress_WooCommerce_Lost_Password_Integration extends Integration {

	// Note: We use a single integration for both the WordPress Lost Password and WooCommerce Lost Password functionality.
	// This is necessary because the WooCommerce process_lost_password function also triggers the WordPress lostpassword_post action.
	// As a result, we cannot distinguish during token verification whether the request originates from the WooCommerce form or the WordPress form.

	public function __construct() {
		$this->id                  = 'wordpress_lost_password';
		$this->setting_title       = __( 'WordPress / WooCommerce Lost Password', 'power-captcha' );
		$this->setting_description = __( 'Enable protection for the WordPress and WooCommerce lost/reset password form.', 'power-captcha' );
	}

	public function init() {
		// WordPress lost password form
		add_action( 'lostpassword_form', array( $this, 'display_widget_wordpress' ) );
		add_action( 'lostpassword_form', array( $this, 'enqueue_script' ) );

		// WooCommerce lost password form
		add_action( 'woocommerce_lostpassword_form', array( $this, 'display_widget_woocomerce' ) );
		add_action( 'woocommerce_lostpassword_form', array( $this, 'enqueue_script' ) );

		// Verification for both WordPress and WooCommerce lost password
		add_action( 'lostpassword_post', array( $this, 'verification' ), 10, 2 );
	}

	public function disable_verification() {
		remove_action( 'lostpassword_post', array( $this, 'verification' ), 10 );
	}

	public function display_widget_wordpress() {
		parent::echo_widget_html( '#user_login', true, '', 'margin-bottom: 16px' );
	}

	public function display_widget_woocomerce() {
		parent::echo_widget_html( '#user_login', true, 'form-row' );
	}

	public function enqueue_script() {
		parent::enqueue_scripts();
	}

	public function verification( \WP_Error $errors, \WP_User|false $user_data ) {
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Reason: Nonce generation and verification are handled by WordPress.
		$username     = isset( $_POST['user_login'] ) ? sanitize_text_field( wp_unslash( $_POST['user_login'] ) ) : null;
		$verification = $this->verify_token( $username );
		if ( false === $verification->is_success() ) {
			$errors->add( $verification->get_error_code(), $verification->get_user_message() );
		}
	}
}
