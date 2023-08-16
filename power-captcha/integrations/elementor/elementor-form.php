<?php

defined('POWER_CAPTCHA_PATH') || exit;

if(powercaptcha()->is_enabled(powercaptcha()::ELEMENTOR_FORM_INTEGRATION)) {

    add_action('wp_enqueue_scripts', function() {
        // register field javascript (field javascript is loaded in power-captcha-field.php)
        wp_register_script(
            'power-captcha-elementor-field-js', 
            plugin_dir_url( __FILE__ )  . 'public/power-captcha-field.js',  
            [ 'elementor-frontend', 'jquery' ], 
            '1.0', 
            true 
        );
    });


    // add field to elementor
    add_action( 'elementor_pro/forms/fields/register', 'powercaptcha_elementor_form_integration_register_field' );
}

// source: https://developers.elementor.com/docs/form-fields/simple-example/
//         and https://developers.elementor.com/docs/form-fields/advanced-example/
/**
 * Add POWER CAPTCHA field to Elementor form widget.
 *
 * @param \ElementorPro\Modules\Forms\Registrars\Form_Fields_Registrar $form_fields_registrar
 * @return void
 */
function powercaptcha_elementor_form_integration_register_field( $form_fields_registrar) {

	require_once( __DIR__ . '/power-captcha-field.php' );

	$form_fields_registrar->register( new \Elementor_Form_Power_Captcha_Field() );
}
    
