<?php

namespace Power_Captcha_WP;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly

if ( ! class_exists( '\ElementorPro\Modules\Forms\Fields\Field_Base' ) ) {
	die();
}

// source: https://developers.elementor.com/docs/form-fields/simple-example/
// and https://developers.elementor.com/docs/form-fields/advanced-example/
/**
 * Elementor Form Field - POWER CAPTCHA Field
 *
 * Add a POWER CAPTCHA field to Elementor form widget.
 */
class Elementor_Form_Power_Captcha_Field extends \ElementorPro\Modules\Forms\Fields\Field_Base {

	const FIELD_CONTROL_PC_USERNAME_ID = 'field_pc_username_id';

	/**
	 * For compability with elementor before v3.28.0.
	 */
	public $depended_scripts = array( 'powercaptcha-elementor' );

	private Elementor_Form_Integration $power_captcha_integration;

	public function __construct( Elementor_Form_Integration $power_captcha_integration ) {
		parent::__construct();
		$this->power_captcha_integration = $power_captcha_integration;

		// Used to add a script to the Elementor editor preview.
		add_action( 'elementor/preview/init', array( $this, 'enqueue_editor_preview_scripts' ) );
	}

	/**
	 * Get field type.
	 *
	 * @return string Field type.
	 */
	public function get_type() {
		return 'power-captcha';
	}

	/**
	 * Get field name.
	 *
	 * @return string Field name.
	 */
	public function get_name() {
		return esc_html__( 'POWER CAPTCHA', 'power-captcha' );
	}

	/**
	 * Render field output on the frontend.
	 *
	 * @param mixed $item
	 * @param mixed $item_index
	 * @param mixed $form
	 * @return void
	 */
	public function render( $item, $item_index, $form ) {
		$user_input_field_selector = '';
		if ( ! empty( $item[ self::FIELD_CONTROL_PC_USERNAME_ID ] ) ) {
			$user_input_field_selector = '#form-field-' . $item[ self::FIELD_CONTROL_PC_USERNAME_ID ];
		}

		$this->power_captcha_integration->echo_widget_html( $user_input_field_selector );
	}

	/**
	 * Field validation.
	 *
	 * @param \ElementorPro\Modules\Forms\Classes\Field_Base   $field
	 * @param \ElementorPro\Modules\Forms\Classes\Form_Record  $record
	 * @param \ElementorPro\Modules\Forms\Classes\Ajax_Handler $ajax_handler
	 * @return void
	 */
	public function validation( $field, $record, $ajax_handler ) {

		if ( $this->power_captcha_integration->is_verification_disabled() ) {
			return; // skip validation if verification is disabled
		}

		$form = $ajax_handler->get_current_form();

		$power_captcha_field_meta = array();
		// find the field_meta of power captcha field in form
		foreach ( $form['settings']['form_fields'] as $index => $field_meta ) {
			if ( $field_meta['field_type'] === $this->get_type() && $field_meta['custom_id'] === $field['id'] ) {
				$power_captcha_field_meta = $field_meta;
				break;
			}
		}

		$username_field_id = $power_captcha_field_meta[ self::FIELD_CONTROL_PC_USERNAME_ID ] ?? '';
		$username_hash     = null;
		if ( ! empty( $username_field_id ) ) {
			// get the raw field value which contains the username protected by POWER CAPTCHA
			foreach ( $record->get( 'fields' ) as $name => $field_data ) {
				if ( $field_data['id'] === $username_field_id ) {
					$username_hash = $this->power_captcha_integration->hash_username( $field_data['raw_value'] );
					break;
				}
			}
		}

		$verification = $this->power_captcha_integration->verify_token( $username_hash );

		// add information to response, that the token was verified.
		// this information is used in power-captcha-elementor.js to reset the captcha.
		$ajax_handler->add_response_data( 'powercaptcha_elementor_verificiation_success', $verification->is_success() );

		if ( false === $verification->is_success() ) {
			$ajax_handler->add_error_message( $verification->get_user_message() ); // message is displayed below the form
			$ajax_handler->add_error( $field['id'], $verification->get_user_message() ); // stops the submission
			return;
		}
	}

	/**
	 * Update form widget controls.
	 *
	 * @param \Elementor\Widget_Base $widget The form widget instance.
	 * @return void
	 */
	public function update_controls( $widget ) {
		$elementor = \ElementorPro\Plugin::elementor();

		$control_data = $elementor->controls_manager->get_control_from_stack( $widget->get_unique_name(), 'form_fields' );

		if ( is_wp_error( $control_data ) ) {
			return;
		}

		// dont show 'required' control for power captcha field
		$control_data['fields']['required']['conditions']['terms'][] = array(
			'name'     => 'field_type',
			'operator' => '!in',
			'value'    => array( $this->get_type() ),
		);

		// dont show 'field_label' for power captcha field
		$control_data['fields']['field_label']['conditions']['terms'][] = array(
			'name'     => 'field_type',
			'operator' => '!in',
			'value'    => array( $this->get_type() ),
		);

		// dont show 'column width' control for power captcha field
		$control_data['fields']['width']['conditions']['terms'][] = array(
			'name'     => 'field_type',
			'operator' => '!in',
			'value'    => array( $this->get_type() ),
		);

		// add control for username field id
		$control_data['fields'][ self::FIELD_CONTROL_PC_USERNAME_ID ] = array(
			'name'         => self::FIELD_CONTROL_PC_USERNAME_ID,
			'label'        => esc_html__( 'ID of the field to be protected', 'power-captcha' ),
			'type'         => \Elementor\Controls_Manager::TEXT,
			'description'  =>
				esc_html__(
					'(optional, Enterprise only) Provide the ID of the field which should additionally be protected by POWER CAPTCHA (e.g. user name or email address). You can find the ID in the corresponding field under \'Advanced\'.',
					'power-captcha'
				),
			'default'      => '',
			'required'     => false,
			'dynamic'      => array(
				'active' => false,
			),
			'condition'    => array(
				'field_type' => $this->get_type(),
			),
			'tab'          => 'content',
			'inner_tab'    => 'form_fields_content_tab',
			'tabs_wrapper' => 'form_fields_tabs',
		);

		$widget->update_control( 'form_fields', $control_data );
	}

	public function get_script_depends(): array {
		return array( 'powercaptcha-elementor' );
	}

	/**
	 * Elementor editor preview.
	 *
	 * Add a script to the footer of the editor preview screen.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return void
	 */
	public function editor_preview_footer() {
		add_action( 'wp_footer', array( $this, 'content_template_script' ) );
	}

	public function enqueue_editor_preview_scripts() {
		wp_enqueue_script( 'powercaptcha-elementor-preview' );
	}
}
