<?php

namespace Power_Captcha_WP;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly

add_action(
	'powercaptcha_register_integration',
	function ( $power_captcha ) {
		$power_captcha->register_integration( new WPForms_Integration() );
	}
);

class WPForms_Integration extends Integration {

	public function __construct() {
		$this->id = 'wpforms';
		// TODO add a notice to setting_description, that captcha is automatacally added to all WPForms forms
		// TODO add a notice to setting_description, how username field is defined via css-classes in WPForms
		// TODO replace hardcoded urls with placeholdes in setting_description
	}

	public function init() {
		add_action( 'wpforms_frontend_js', array( $this, 'enqueue_script' ), 10, 1 );

		// Action that fires immediately before the submit button element is displayed. (see https://wpforms.com/developers/wpforms_display_submit_before/)
		add_action( 'wpforms_display_submit_before', array( $this, 'display_widget' ), 10, 1 );

		// Action that fires during form entry processing after initial field validation. (see https://wpforms.com/developers/wpforms_process/)
		add_action( 'wpforms_process', array( $this, 'verification' ), 10, 3 );
	}

	public function textdomain_loaded() {
		$this->setting_title       = __( 'WPForms', 'power-captcha' );
		$this->setting_description = __( 'Enable protection for <a href="https://wordpress.org/plugins/wpforms/" target="_blank">WPForms</a> and <a href="https://wordpress.org/plugins/wpforms-lite/" target="_blank">WPForms lite</a>.', 'power-captcha' );
	}

	public function disable_verification() {
		remove_action( 'wpforms_process', array( $this, 'verification' ), 10 );
	}

	public function display_widget() {
		// $userInputField is selected in frontend via custom javascript
		parent::echo_widget_html( '', false, 'wpforms-field', 'margin-top: -10px; margin-bottom: 10px' );
	}

	public function enqueue_script() {
		// additional javascript for WPForms to find the right the userInputField via css-classes
		wp_enqueue_script(
			'powercaptcha-wpforms',
			plugin_dir_url( __FILE__ ) . 'public/power-captcha-wpforms.js',
			array( 'jquery', 'powercaptcha-wp' ),
			POWER_CAPTCHA_PLUGIN_VERSION,
			true
		);
	}

	public function verification( array $fields, array $entry, array $form_data ) {
		$username_hash = $this->hash_username( $this->find_user_field_value( $form_data, $entry ) );
		$verification  = $this->verify_token( $username_hash );

		if ( false === $verification->is_success() ) {

			wpforms()->process->errors[ $form_data['id'] ] ['header'] = $verification->get_user_message();

			$this->report_to_wpforms_log( $verification, $form_data, $entry );
		}
	}

	private function find_user_field_value( array $wpforms_form_data, array $wpforms_entry ) {
		foreach ( $wpforms_form_data['fields'] as $field_id => $settings ) {
			if ( isset( $settings['css'] ) && ! empty( $settings['css'] ) ) {
				// check if css has pc-user-* class
				$matches = array();
				if ( preg_match( '/pc-user-([0-9]+)/', $settings['css'], $matches ) ) {
					$field_position = $matches[1];
					$field_value    = $wpforms_entry['fields'][ $field_id ];

					if ( is_array( $field_value ) ) {
						return array_values( $field_value )[ $field_position ];
					} else {
						return $field_value;
					}
				}
			}
		}

		return null;
	}

	private function report_to_wpforms_log( Verification_Result $verification, array $wpforms_form_data, array $wpforms_entry ) {
		// Create a log entry in WPForms
		if ( powercaptcha()::ERROR_CODE_USER_ERROR === $verification->get_error_code() ) {
			$error_message = 'POWER CAPTCHA security check was not confirmed by user.';
		} elseif ( powercaptcha()::ERROR_CODE_API_ERROR === $verification->get_error_code() ) {
			$error_message = 'An internal error occurred during the POWER CAPTCHA token verification. Please check you API Key and Secret Key.';
		} else {
			$error_message = 'An unkown error occurred during the POWER CAPTCHA token verification.';
		}

		// TODO better message for wpforms_log
		wpforms_log(
			// @param string $title   Title of a log error_message.
			esc_html__( 'POWER CAPTCHA: Spam detected', 'power-captcha' ) . uniqid(),
			// @param mixed  $error_message Content of a log error_message.
			array( esc_html( $error_message ), $wpforms_entry ),
			// @param array  $args    Expected keys: form_id, meta, parent.
			array(
				'type'    => array( 'spam' ),
				'form_id' => $wpforms_form_data['id'],
			)
		);
	}
}
