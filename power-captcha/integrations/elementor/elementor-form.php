<?php

namespace Power_Captcha_WP;

defined( 'POWER_CAPTCHA_PATH' ) || exit;

add_action(
	'powercaptcha_register_integration',
	function ( $power_captcha ) {
		$power_captcha->register_integration( new Integration_Elementor_Forms() );
	}
);

class Integration_Elementor_Forms extends Integration {

	private bool $verification_disabled = false;

	public function __construct() {
		$this->id                  = 'elementor_form';
		$this->setting_title       = __( 'Elementor Pro Forms', 'power-captcha' );
		$this->setting_description =
			__( 'Enable protection for <a href="https://elementor.com/pro/" target="_blank">Elementor Pro</a> Forms.', 'power-captcha' )
			. '<br/>'
			. __( 'After enabling, you need to add a \'POWER CAPTCHA\'-field to your desired Elementor form.', 'power-captcha' );
	}

	public function init() {

		add_action( 'wp_enqueue_scripts', array( $this, 'register_field_script' ) );
		add_action( 'elementor_pro/forms/fields/register', array( $this, 'register_field' ) );
	}

	public function disable_verification() {
		$this->verification_disabled = true;
	}

	public function is_verification_disabled(): bool {
		return $this->verification_disabled;
	}

	public function register_field_script() {
		// register addditional javascript for elementor forms
		// note: the javascript is enqueued via the elementor field ($depended_scripts in power-captcha-field.php)
		wp_register_script(
			'powercaptcha-elementor',
			plugin_dir_url( __FILE__ ) . 'public/power-captcha-elementor.js',
			array( 'jquery', 'powercaptcha-wp' ),
			POWER_CAPTCHA_PLUGIN_VERSION,
			true
		);
	}

	/**
	 * Register POWER CAPTCHA field to Elementor form.
	 *
	 * @param \ElementorPro\Modules\Forms\Registrars\Form_Fields_Registrar $form_fields_registrar
	 * @return void
	 */
	public function register_field( $form_fields_registrar ) {
		require_once __DIR__ . '/power-captcha-field.php';

		$form_fields_registrar->register( new Elementor_Form_Power_Captcha_Field( $this ) );
	}
}
