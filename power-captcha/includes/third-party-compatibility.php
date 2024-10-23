<?php

defined('POWER_CAPTCHA_PATH') || exit;

// Exclude all of our JavaScript from WP Rocket delay and defer, because the POWER CAPTCHA widget should be loaded and executed directly. 
// Its important for additional integration JavaScript like WPForms that all scripts are loaded and executed in the right order.
// WP Rocket Code examples:
// Defer: https://github.com/wp-media/wp-rocket-helpers/blob/master/static-files/wp-rocket-static-exclude-defer-js/wp-rocket-static-exclude-defer-js.php
// Delay: https://github.com/wp-media/wp-rocket-helpers/blob/master/static-files/wp-rocket-static-exclude-delay-js-per-url/wp-rocket-exclude-delay-js-per-url.php
function powercaptcha_wp_rocket_js_exclusions ( $excluded = array() ) {
    $excluded[] = 'power-captcha(.*)\.js';
    return $excluded;
}
add_filter( 'rocket_delay_js_exclusions', 'powercaptcha_wp_rocket_js_exclusions'); // delay filter
add_filter( 'rocket_exclude_defer_js', 'powercaptcha_wp_rocket_js_exclusions' ); // defer filter