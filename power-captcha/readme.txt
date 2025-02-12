=== POWER CAPTCHA ===
Contributors: powercaptcha
Tags: captcha, security, protection, bot, spam
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 8.0
Stable tag: 1.2.4
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0

POWER CAPTCHA protects your WordPress website and WordPress forms against bots and unauthorized persons. GDPR compliant!

== Description ==

= STOP BOT AND HACKER ATTACKS =

Safeguard your website’s forms and login areas from misuse, spam, and data theft caused by both bots AND hackers. POWER CAPTCHA uses customizable parameters to verify whether access is legitimate, either granting or denying access based on your chosen criteria.

= MORE THAN JUST BOT PROTECTION =

While many captchas only focus on stopping bots, they often fail to guard against unauthorized access by real users, such as someone repeatedly trying to guess login credentials or submitting forms multiple times. POWER CAPTCHA goes beyond traditional bot detection by evaluating all interactions, regardless of whether they're human or automated. It uses flexible criteria — e.g. usernames, email addresses, IP addresses, and/or login attempts within specific timeframes — to assess if access is genuinely authorized.

Each interaction generates an encrypted code, or "client footprint", which POWER CAPTCHA stores and analyzes. For future attempts, it can, for example, increase the challenge level or temporarily block further actions. With customizable settings based on your license, POWER CAPTCHA adapts to your specific security needs. You can also configure it as a no-captcha option. 

= GDPR-COMPLIANT, ACCESSIBLE, AND BUILT IN GERMANY =

POWER CAPTCHA is cookie-free, GDPR-compliant, and easy to integrate with most systems. For added security and accessibility, the Enterprise Plan includes features like two-factor authentication.

= Supported Plugins and Forms =

* **WordPress** Login 
* **WordPress** Registration 
* **WordPress** Lost Password 
* **WooCommerce** Login
* **WooCommerce** Registration
* **WooCommerce** Lost Password
* **WooCommerce** Checkout
* **WPForms**
* **WPForms lite**
* **Elementor Pro Forms**
* **Contact Form 7**

== Installation ==

To use POWER CAPTCHA, you need an API Key. Select a plan on <a href="https://power-captcha.com/en/power-captcha-shop-licenses/">power-captcha.com</a>, add your domain in the customer area on our website, and obtain your API Key.

### Installation from within WordPress 

1. Go to *Plugins* > *Add New* in your WordPress dashboard.
2. Search for "POWER CAPTCHA".
3. Install and activate the POWER CAPTCHA plugin
4. Follow the configuration instructions below.

### Manual Installation

1. Upload the `power-captcha` folder to your `/wp-content/plugins/` directory
2. Activate the POWER CAPTCHA plugin through the Plugins menu in your WordPress dashboard.
3. Follow the configuration instructions below.

### Configuration

1. Go to *Settings* > *POWER CAPTCHA* in your WordPress dashboard.
2. Enter your **API Key** and **Secret Key** in the *General settings* section.
3. Enable the desired integrations under *Integration settings* based on the forms you want to protect.
4. Test your configuration to ensure everything is working as expected.

== Frequently Asked Questions ==

= How to start with POWER CAPTCHA =

To install the application, first select a license on <a href="https://power-captcha.com/en/power-captcha-plans-and-additional-options/">POWER CAPTCHA</a>. With your license, you’ll automatically receive an API key to access our interfaces. Then, follow the step-by-step instructions in the "Installation" tab to integrate POWER CAPTCHA into your website.

= How to configure POWER CAPTCHA to your needs =

Depending on your license, you can adapt POWER CAPTCHA to your individual needs. Your can find examples and configuration options to effectively protect your forms and login areas on <a href="https://power-captcha.com/en/power-captcha-feature-details/#Konfiguration">our website</a>.

= How to get support =

You use POWER CAPTCHA and have questions about the installation or a technical problem? You can contact our support team <a href="https://power-captcha.com/en/contact-support/">here</a>. 

== Screenshots ==

1. WordPress Login protected by POWER CAPTCHA
2. Display of a CAPTCHA
3. Individual integration settings
4. Individual configuration of the widget display

== Changelog ==

= 1.2.4 =
* Updated JavaScript library to v1.2.6
* Introduced SHA-256 hashing of username  
* Fixed a WordPress warning due to loading text domain too early  

= 1.2.3 =
* Improved preview rendering in the Elementor Editor
* Minor improvements for better stability and performance

= 1.2.2 =
* Added integration for Contact From 7

= 1.2.1 =
* Added missing translation and adjusted plugin action links

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