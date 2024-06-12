<?php

defined('POWER_CAPTCHA_PATH') || exit;

// Exclude all of our JavaScript from WP Rocket delay, because the POWER CAPTCHA widget should be executed directly. 
// This is very important for additional integration JavaScript like WPForms.
// example: // https://github.com/wp-media/wp-rocket-helpers/blob/master/static-files/wp-rocket-static-exclude-delay-js-per-url/wp-rocket-exclude-delay-js-per-url.php
function powercaptcha_rocket_delay_js_exclusions ( $excluded = array() ) {
    $excluded[] = 'power-captcha(.*)\.js';
    return $excluded;
}
add_filter( 'rocket_delay_js_exclusions', 'powercaptcha_rocket_delay_js_exclusions');