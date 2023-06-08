<?php
/**
 * If it is necessary to override the API Key and Secret Key for a specific integration, 
 * you can copy this sample file to 'config.php' and customize it.
 * 
 * Note: You still need to assign a global API and Secret Key via the 'POWER CAPTCHA Settings' in the WordPress dashboard.
 */

// Example of overriding the API Key and Secret Keys for WPForms integration.
powercaptcha()->overwrite_keys(powercaptcha()::WPFORMS_INTEGRATION, 'your_api_key', 'your_secret_key');