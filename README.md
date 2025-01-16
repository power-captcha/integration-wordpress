# POWER CAPTCHA Integration for WordPress

[POWER CAPTCHA](https://power-captcha.com/en/) protects your WordPress website and WordPress forms against bots and unauthorized persons. GDPR compliant!

Supported Plugins and Forms
* WordPress Login
* WordPress Registration
* WordPress Lost Password
* WooCommerce Login
* WooCommerce Registration
* WooCommerce Lost Password
* WooCommerce Checkout
* WPForms
* WPForms lite
* Elementor Pro Forms
* Contact Form 7

## Getting started

For installation instructions, updates, and detailed information about POWER CAPTCHA for WordPress, please visit our [**WordPress Plugin page**](https://wordpress.org/plugins/power-captcha/).

## Development

Note that the plugin itself is located in the `power-captcha` directory and Composer is only used for development to check and fix WordPress Coding Standards.

### Setup for Development

1. **Clone the project**  
   Clone the repository to your local machine.

2. **Install development dependencies**  
   Install the required dependencies via Composer:
   ```
   cd integration-wordpress/power-captcha
   composer install --dev
   ```

3. **Create a symbolic link in the `wp-content/plugins` directory**  
   Create a symbolic link to `integration-wordpress/power-captcha` in the WordPress plugins directory:
   ```
   PS> New-Item -ItemType SymbolicLink -Path "<WordPress>/wp-content/plugins/power-captcha" -Target "<Workspace>/integration-wordpress/power-captcha"
   ```
   *(Example using Windows PowerShell)*

### Development Utilities

#### Test code against WordPress Coding Standards (WPCS)
To check whether your code complies with the WordPress Coding Standards, execute this command to get a report:
```
composer cs
```

#### Fix code to comply with WordPress Coding Standards
To automatically format and fix your code to match the WordPress Coding Standards, use:
```
composer cbf
```

### Translation

#### Update Translation Files

1. After adding or editing translatable strings, you need to regenerate the POT file:
   ```
   cd integration-wordpress
   composer make-pot
   ```

2. Next, update the PO files, which contain the language translations:
   ```
   composer update-po
   ```

3. Open the PO files (e.g. `power-captcha-de_DE.po`) and manually translate each string. Once all strings are translated, you must regenerate the JSON translation files:
   ```
   composer make-json
   ```

4. Finally, regenerate the MO files:
   ```
   composer make-mo
   ```

#### Adding a New Language

To add a new language translation, create a new PO file in `power-captcha/languages/power-captcha/` with the appropriate language code as the suffix. For example:
`power-captcha-es_ES.po`.

After adding the file, follow the steps from *Update Translation Files* (as described above).
