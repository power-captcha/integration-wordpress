=== POWER CAPTCHA ===
Contributors: power-captcha
Tags: captcha, security, protection, bot, spam
Requires at least: 6.0
Tested up to: 6.6.2
Requires PHP: 8.0
Stable tag: 1.2.0
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0

POWER CAPTCHA protects your WordPress website and WordPress forms against bots and unauthorized persons. GDPR compliant!

== Description ==

= Bot and hacker protection =

POWER CAPTCHA does not primarily differentiate between human and automated interactions, but uses individually adjustable parameters to check whether access is fundamentally authorized – and is GDPR-compliant! You have control over the balance between user-friendliness, accessibility and security level.

= Accessible - GDPR-compliant - developed in Germany =

= Supported Plugins and Forms =

* **WordPress Core:** Login, Registration, Lost Password
* **WooCommerce:** Login, Registration, Lost Password, Checkout
* **WPForms / WPForms lite**
* **Elementor Pro Forms**

== Installation ==

To use POWER CAPTCHA, you’ll need an API Key. Select a plan on [power-captcha.com](https://power-captcha.com/en/power-captcha-shop-licenses/), add your domain in the API Key Management, and obtain your unique key.

### Installation from within WordPress 

1. Go to *Plugins* > *Add New* in your WordPress dashboard.
2. Search for "POWER CAPTCHA".
3. Install and activate the POWER CAPTCHA plugin
4. Follow the configuration instructions below.

### Manual Installation

1. Upload the `power-catcha` folder to your `/wp-content/plugins/` directory
2. Activate the POWER CAPTCHA plugin through the Plugins menu in your WordPress dashboard.
3. Follow the configuration instructions below.

### Configuration

1. Go to *Settings* > *POWER CAPTCHA Settings* in your WordPress dashboard.
2. Enter your **API Key** and **Secret Key** in the *General settings* section.
3. Enable the desired integrations under *Integration settings* based on the forms you want to protect.
4. Test your configuration to ensure everything is working as expected.

== Frequently Asked Questions ==
TODO

== Screenshots ==
TODO

== Changelog ==

= 1.2.0 =
* Refactored and overhauled the code and plugin structure to an object-oriented approach
* Improved code style to comply with WordPress Coding Standards
* Added German translation
* Improved token verification process
* Introduced the "API Error Policy" setting
* Release on the WordPress Plugin Directory

= 0.2.3 =
* Fixed an issue with script registration in the Elementor Forms integration.

= 0.2.2 =
* Fixed an issue in the WooCommerce checkout integration
* Fixed an issue in the Elementor Forms integration

= 0.2.1 =
* Resolved compatibility issues with the WP Rocket plugin

= 0.2.0 =
* Updated JavaScript library to v1.2.2
* Added "Check mode" setting to the administration

= 0.1.7 =
* Enabled automatic language detection for the captcha modal based on WordPress locale settings

= 0.1.6 =
* Fixed a bug related to asynchronous loading of integration settings

= 0.1.5 =
* Added asynchronous loading for client UID and integration settings to improve compatibility with caching plugins

= 0.1.4 =
* Added support for client UID verification

= 0.1.3 =
* Fixed integration issues with Elementor Pro Forms
* Updated path and naming for `config.php`

= 0.1.2 =
* Fixed various bugs in WooCommerce Register and Checkout integrations
* Corrected a typo in the code

= 0.1.1 =
* Added support for automatic plugin updates

= 0.1.0 =
* Initial release of the plugin