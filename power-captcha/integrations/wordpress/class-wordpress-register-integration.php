<?php

namespace Power_Captcha_WP;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly

add_action(
	'powercaptcha_register_integration',
	function ( $power_captcha ) {
		$power_captcha->register_integration( new WordPress_Register_Integration() );
	}
);

class WordPress_Register_Integration extends Integration {

	public function __construct() {
		$this->id                  = 'wordpress_register';
	}

	public function init() {
		add_action( 'register_form', array( $this, 'display_widget' ) );
		add_action( 'register_form', array( $this, 'enqueue_script' ) );

		add_action( 'register_post', array( $this, 'verification' ), 10, 3 );
	}
	
	public function textdomain_loaded() {
		$this->setting_title       = __( 'WordPress Registration', 'power-captcha' );
		$this->setting_description = __( 'Enable protection for the WordPress registration form.', 'power-captcha' );
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
		$username_hash = $this->get_username_hash( 'user_email' );
		$verification  = $this->verify_token( $username_hash );
		if ( false === $verification->is_success() ) {
			$errors->add( $verification->get_error_code(), $verification->get_user_message() );
		}
	}
}
