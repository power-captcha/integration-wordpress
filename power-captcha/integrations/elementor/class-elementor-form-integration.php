<?php

namespace Power_Captcha_WP;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly

add_action(
	'powercaptcha_register_integration',
	function ( $power_captcha ) {
		$power_captcha->register_integration( new Elementor_Form_Integration() );
	}
);

class Elementor_Form_Integration extends Integration {

	private bool $verification_disabled = false;

	public function __construct() {
		$this->id = 'elementor_form';
	}

	public function init() {
		add_action( 'elementor/frontend/before_register_scripts', array( $this, 'register_field_script' ) );
		add_action( 'elementor_pro/forms/fields/register', array( $this, 'register_field' ) );
	}

	public function textdomain_loaded() {
		$this->setting_title       = __( 'Elementor Pro Forms', 'power-captcha' );
		$this->setting_description =
			__( 'Enable protection for <a href="https://elementor.com/pro/" target="_blank">Elementor Pro</a> Forms.', 'power-captcha' )
			. '<br/>'
			. __( 'After enabling, you need to add a \'POWER CAPTCHA\'-field to your desired Elementor form.', 'power-captcha' );
	}

	public function disable_verification() {
		$this->verification_disabled = true;
	}

	public function is_verification_disabled(): bool {
		return $this->verification_disabled;
	}

	public function register_field_script() {
		// The method get_script_depends() in power-captcha-field.php does not work reliably
		// when the "Element Caching" feature in Elementor is enabled.
		// Therefore the JavaScript is also enqueued in register_field() via the elementor/frontend/before_enqueue_scripts hook.
		wp_register_script(
			'powercaptcha-elementor',
			plugin_dir_url( __FILE__ ) . 'public/power-captcha-elementor.js',
			array( 'jquery', 'powercaptcha-wp' ),
			POWER_CAPTCHA_PLUGIN_VERSION,
			true
		);

		// register preview script
		// the script is enqueued via enqueue_editor_preview_scripts in power-captcha-field.php
		wp_register_script(
			'powercaptcha-elementor-preview',
			plugin_dir_url( __FILE__ ) . 'public/power-captcha-elementor-preview.js',
			array( 'powercaptcha-elementor' ),
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
		add_action( 'elementor/frontend/before_enqueue_scripts', array( $this, 'enqueue_field_script' ) );

		require_once __DIR__ . '/class-elementor-form-power-captcha-field.php';

		$form_fields_registrar->register( new Elementor_Form_Power_Captcha_Field( $this ) );
	}

	public function enqueue_field_script() {
		wp_enqueue_script( 'powercaptcha-elementor' );
	}
}
