<?php

namespace Power_Captcha_WP;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly

add_action(
	'powercaptcha_register_integration',
	function ( $power_captcha ) {
		$power_captcha->register_integration( new WordPress_Login_Integration() );
	}
);

class WordPress_Login_Integration extends Integration {

	public function __construct() {
		$this->id = 'wordpress_login';
	}

	public function init() {
		add_action( 'login_form', array( $this, 'display_widget' ), 10, 0 );
		add_action( 'login_form', array( $this, 'enqueue_script' ), 11, 0 );

		add_filter( 'authenticate', array( $this, 'verification' ), 20, 3 );
	}

	public function textdomain_loaded() {
		$this->setting_title       = __( 'WordPress Login', 'power-captcha' );
		$this->setting_description = __( 'Enable protection for the WordPress login form.', 'power-captcha' );
	}

	public function disable_verification() {
		remove_filter( 'authenticate', array( $this, 'verification' ), 20 );
	}

	public function display_widget() {
		parent::echo_widget_html( '#user_login', false, '', 'margin-bottom: 16px' );
	}

	public function enqueue_script() {
		parent::enqueue_scripts();
	}

	public function verification( null|\WP_User|\WP_Error $user, string $username, string $password ) {
		if ( is_wp_error( $user ) ) {
			return $user;
		}

		$username_hash = $this->get_username_hash( 'log' );
		$verification  = $this->verify_token( $username_hash );
		if ( false === $verification->is_success() ) {
			return new \WP_Error( $verification->get_error_code(), $verification->get_user_message() );
		}

		return $user;
	}
}
