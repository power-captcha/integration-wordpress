<?php

namespace Power_Captcha_WP;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly

class Third_Party_Compatibility {

	public function __construct() {}

	public function init() {

		// WP Rocket
		// Exclude all of our JavaScript from WP Rocket delay and defer, because the POWER CAPTCHA widget should be loaded and executed directly.
		// Its important for additional integration JavaScript like WPForms that all scripts are loaded and executed in the right order.
		// Defer: https://github.com/wp-media/wp-rocket-helpers/blob/master/static-files/wp-rocket-static-exclude-defer-js/wp-rocket-static-exclude-defer-js.php
		// Delay: https://github.com/wp-media/wp-rocket-helpers/blob/master/static-files/wp-rocket-static-exclude-delay-js-per-url/wp-rocket-exclude-delay-js-per-url.php
		add_filter( 'rocket_delay_js_exclusions', array( $this, 'wp_rocket_js_exclusions' ) ); // delay filter
		add_filter( 'rocket_exclude_defer_js', array( $this, 'wp_rocket_js_exclusions' ) ); // defer filter
	}

	public function wp_rocket_js_exclusions( $excluded = array() ) {
		$excluded[] = 'power-captcha(.*)\.js';
		$excluded[] = 'powercaptcha'; // also exclude our inline js (powercaptcha_ajax_conf)
		return $excluded;
	}
}
