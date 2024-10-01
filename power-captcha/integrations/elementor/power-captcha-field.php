<?php

namespace PowerCaptcha_WP;

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

    public $depended_scripts = [ 'powercaptcha-wp', 'powercaptcha-elementor' ];

	private Integration_Elementor_Forms $power_captcha_integration;

	public function __construct(Integration_Elementor_Forms $power_captcha_integration) {
		parent::__construct();
		$this->power_captcha_integration = $power_captcha_integration;

		// Used to add a script to the Elementor editor preview.
		add_action( 'elementor/preview/init', [ $this, 'editor_preview_footer' ] );
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
		$userInputFieldSelector = '';
		if(!empty($item[self::FIELD_CONTROL_PC_USERNAME_ID])) {
			$userInputFieldSelector = '#form-field-'.$item[self::FIELD_CONTROL_PC_USERNAME_ID];
		}

		echo $this->power_captcha_integration->widget_html($userInputFieldSelector);
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
		$form = $ajax_handler->get_current_form();

		$power_captcha_field_meta = array();
		// find the field_meta of power captcha field in form
		foreach($form['settings']['form_fields'] as $index => $field_meta) {
			if($field_meta['field_type'] == $this->get_type() && $field_meta['custom_id'] == $field['id']) {
				$power_captcha_field_meta = $field_meta;
				break;
			}
		}

		$username_field_id = $power_captcha_field_meta[self::FIELD_CONTROL_PC_USERNAME_ID] ?? '';
		$username_value = null;
		if(!empty($username_field_id)) {
			// get the raw field value which contains the username protected by POWER CAPTCHA
			foreach($record->get('fields') as $name => $field_data) {
				if($field_data['id'] == $username_field_id) {
					$username_value = $field_data['raw_value'];
					break;
				}
			}
		}

        $verification = $this->power_captcha_integration->verify_token($username_value);
        if(FALSE === $verification->is_success()) {
			// TODO find a way to display the error message in frontend
            $ajax_handler->add_error($field['id'], $verification->get_user_message());
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
			// TODO better label
			'label' => esc_html__( 'PC Username Field ID', 'power-captcha' ),
			'type' => \Elementor\Controls_Manager::TEXT,
			'description' => 
				// TODO better description
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
		?>
		<script>
		jQuery( document ).ready( () => {
			elementor.hooks.addFilter(
				'elementor_pro/forms/content_template/field/<?php echo $this->get_type(); ?>',
				function ( inputField, item, i ) {
					
					// delay PowerCaptchaWp.setup() method, so the div is already rendered
					setTimeout(function () {
						if(window.PowerCaptchaWp) {
							window.PowerCaptchaWp.destroyAll();
							window.PowerCaptchaWp.setup();
						} else {
							console.warn('PowerCaptchaWp not found');
						}
					}, 1000);

					return `<?php echo $this->power_captcha_integration->widget_html() ?>`;
				}, 10, 3
			);
		});
		</script>
		<?php
	}

}
