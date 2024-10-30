<?php
/**
 * If it is necessary to overwrite the API Key and Secret Key for a specific integration, 
 * you can customize this sample file and copy it to the /wp-content folder.
 * The full path has to be: /wp-content/power-captcha-config.php
 * 
 * Note: You still need to assign a global API and Secret Key via the 'POWER CAPTCHA Settings' in the WordPress dashboard.
 */

defined('POWER_CAPTCHA_PATH') || exit;

// Example of overriding the API Key and Secret Keys for WPForms integration.
// powercaptcha()->overwrite_keys('wpforms', 'your_api_key', 'your_secret_key');
