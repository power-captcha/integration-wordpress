<?php

defined('POWER_CAPTCHA_PATH') || exit;

// adding link to settings page on the plugin list overview
add_filter( 'plugin_action_links_power-captcha/power-captcha.php', 'powercaptcha_settings_link' );
function powercaptcha_settings_link( $links ) {
	// Build and escape the URL.
	$url = esc_url( add_query_arg(
		'page',
		powercaptcha()::SETTING_PAGE,
		get_admin_url() . 'admin.php'
	) );
	// Create the link.
	$settings_link = "<a href='$url'>" . __( 'Settings' ) . '</a>';
	// Adds the link to the end of the array.
	array_push(
		$links,
		$settings_link
	);
	return $links;
}
