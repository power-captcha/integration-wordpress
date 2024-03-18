<?php

defined('POWER_CAPTCHA_PATH') || exit;

if ( ! class_exists( '\ElementorPro\Modules\Forms\Fields\Field_Base' ) ) {
	die();
}

// source: https://developers.elementor.com/docs/form-fields/simple-example/
//         and https://developers.elementor.com/docs/form-fields/advanced-example/
/**
 * Elementor Form Field - POWER CAPTCHA Field
 *
 * Add a POWER CAPTCHA field to Elementor form widget.
 */
class Elementor_Form_Power_Captcha_Field extends \ElementorPro\Modules\Forms\Fields\Field_Base {

	const FIELD_CONTROL_PC_USERNAME_ID = 'field_pc_username_id';

    public $depended_scripts = [ \PowerCaptcha_WP\PowerCaptcha::JAVASCRIPT_HANDLE, 'power-captcha-elementor-field-js' ];

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
		return esc_html__( 'POWER CAPTCHA', 'elementor-form-power-captcha-field' );
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
        if(!powercaptcha()->is_enabled(powercaptcha()::ELEMENTOR_FORM_INTEGRATION)) {
            return;
        }

		$form->add_render_attribute(
			'input' . $item_index,
			[
                'class' => 'elementor-form-power-captcha',
                'style' => 'display: none;', // visually hidden
				'data-pc-username-id' => $item[self::FIELD_CONTROL_PC_USERNAME_ID]
			]
		);
		echo '<input ' . $form->get_render_attribute_string( 'input' . $item_index ) . '>';
		echo '<div class="pc-widget-target"></div>'; 
		// echo '<div data-pc-sitekey="' . powercaptcha()->get_api_key(powercaptcha()::ELEMENTOR_FORM_INTEGRATION) . '"'
		// 	 .' data-pc-user-selector="#form-field-'.$item[self::FIELD_CONTROL_PC_USERNAME_ID].'"'
		// 	 .' data-pc-endpoint="'.powercaptcha()->get_token_request_url().'"'
		// 	 .'></div>'; // TODO -> Möglich, aber Submit-Problem
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
        if(!powercaptcha()->is_enabled(powercaptcha()::ELEMENTOR_FORM_INTEGRATION)) {
            return;
        }

        $pcToken = powercaptcha_get_token_from_post_request();
        if($pcToken === FALSE) {
            $ajax_handler->add_error(
                $field['id'],
                __( powercaptcha_user_error_message(powercaptcha()::ERROR_CODE_NO_TOKEN_FIELD), 'elementor-form-power-captcha-field' )
            );
            return;
        }

		// find username field id
		$form = $ajax_handler->get_current_form();
		$username_field_id = false;
		$username = '';
		// find the power captcha field in form and get the pc user name field id
		foreach($form['settings']['form_fields'] as $index => $field_meta) {
			if($field_meta['field_type'] == $this->get_type() && isset($field_meta[self::FIELD_CONTROL_PC_USERNAME_ID])) {
				$username_field_id = $field[self::FIELD_CONTROL_PC_USERNAME_ID];
				break;
			}
		}

		if($username_field_id) {
			// now find the username field
			foreach($record->get('fields') as $name => $field_data) {
				if($field_data['id'] == $username_field_id) {
					$username = $field_data['raw_value'];
					break;
				}
			}
		}
        
        $verification = powercaptcha_verify_token($pcToken, $username, null, powercaptcha()::ELEMENTOR_FORM_INTEGRATION);
        if($verification['success'] !== TRUE) {
            $ajax_handler->add_error(
                $field['id'],
                __( powercaptcha_user_error_message($verification['error_code']), 'elementor-form-power-captcha-field' )
            );
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
		$control_data['fields']['required']['conditions']['terms'][] = [
			'name' => 'field_type',
			'operator' => '!in',
			'value' => [$this->get_type()]
		];

		// dont show 'field_label' for power captcha field
		$control_data['fields']['field_label']['conditions']['terms'][] = [
			'name' => 'field_type',
			'operator' => '!in',
			'value' => [$this->get_type()]
		];

		// dont show 'column width' control for power captcha field
		$control_data['fields']['width']['conditions']['terms'][] = [
			'name' => 'field_type',
			'operator' => '!in',
			'value' => [$this->get_type()]
		];

		// add control for username field id
		$control_data['fields'][self::FIELD_CONTROL_PC_USERNAME_ID] = [
			'name' => self::FIELD_CONTROL_PC_USERNAME_ID,
			'label' => esc_html__( 'PC Username Field ID', 'power-captcha' ),
			'type' => \Elementor\Controls_Manager::TEXT,
			'description' => 
				esc_html__('(optional) Specify the ID of the field that should be additionally protected with POWER CAPTCHA (e.g. user name or e-mail address). You can find the field ID in the "Advanced" tab of the corresponding field.', 
					'power-captcha'),
			'default' => '',
			'required' => false,
			'dynamic' => [
				'active' => false,
			],
			'condition' => [
				'field_type' => $this->get_type(),
			],
			'tab'          => 'content',
			'inner_tab'    => 'form_fields_content_tab',
			'tabs_wrapper' => 'form_fields_tabs',
		];
		
		$widget->update_control( 'form_fields', $control_data );
	}

	/**
	 * Field constructor.
	 *
	 * Used to add a script to the Elementor editor preview.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return void
	 */
	public function __construct() {
		parent::__construct();
		add_action( 'elementor/preview/init', [ $this, 'editor_preview_footer' ] );
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
		add_action( 'wp_footer', [ $this, 'content_template_script' ] );
	}

	/**
	 * Content template script.
	 *
	 * Add content template alternative, to display the field in Elementor editor.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return void
	 */
	public function content_template_script() {
		// TODO display widget in elementor editor!
		?>
		<script>
		jQuery( document ).ready( () => {

			elementor.hooks.addFilter(
				'elementor_pro/forms/content_template/field/<?php echo $this->get_type(); ?>',
				function ( inputField, item, i ) {
					const fieldId    = `form_field_${i}`;
					// just display that the form is protected by power captcha
					return `<div id="${fieldId}" style="position: relative; padding: 10px; width: 100%; color: #000; background-color: #fff; text-align: center;">
						<?php _e('This form is protected by POWER CAPTCHA. This message is visible only in Elementor editor.', 'power-captcha') ?>
					</div>`;
				}, 10, 3
			);

		});
		</script>
		<?php
	}

}
