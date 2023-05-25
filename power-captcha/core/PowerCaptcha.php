<?php
namespace PowerCaptcha_WP {

    use RuntimeException;

    final class PowerCaptcha {
    
        const API_VERSION = 'v1';
        const JS_VERSION = 'v1';

        // Singelton instance
        private static $instance;

        const SHOP_URL = 'https://power-captcha.com/power-captcha-shop/';
        const API_KEY_MANAGEMENT_URL = 'https://power-captcha.com/mein-konto/api-keys/';

        const DEFAULT_ENDPOINT_BASE_URL = 'https://api.power-captcha.com';
        const DEFAULT_JAVASCRIPT_URL = 'https://cdn.power-captcha.com';
        const JAVASCRIPT_HANDLE = 'powercaptcha-js';

        const ERROR_CODE_NO_TOKEN_FIELD = 'powercaptcha_error_no_token_field';
        const ERROR_CODE_MISSING_TOKEN = 'powercaptcha_error_missing_token';
        const ERROR_CODE_INVALID_TOKEN = 'powercaptcha_error_invalid_token';
        const ERROR_CODE_TOKEN_NOT_VERIFIED = 'powercaptcha_error_token_not_verified';
        const ERROR_CODE_INVALID_SECRET = 'powercaptcha_error_invalid_secret';
        const ERROR_CODE_API_ERROR = 'powercaptcha_api_error';
    
        // settings
        const SETTING_PAGE = 'powercaptcha_admin';
        const SETTING_GROUP_NAME = 'powercaptcha_admin_settings';
    
        // general settings
        const SETTING_SECTION_GENERAL = 'powercaptcha_setting_section_general';
        const SETTING_NAME_API_KEY = 'powercaptcha_api_key';
        const SETTING_NAME_SECRET_KEY = 'powercaptcha_secret_key';
        
        // integration settings
        const SETTING_SECTION_INTEGRATION = 'powercaptcha_setting_section_integration';
        
        const WPFORMS_INTEGRATION = 'wpforms';
        const WORDPRESS_LOGIN_INTEGRATION = 'wordpress_login';
        const WORDPRESS_REGISTER_INTEGRATION = 'wordpress_register';

        // TODO Setting for exluding forms!
    
        // on premises settings
        const SETTING_SECTION_ON_PREMISES = 'powercaptcha_setting_section_on_premises';
        const SETTING_NAME_ENDPOINT_BASE_URL = 'powercaptcha_endpoint_base_url';
        const SETTING_NAME_JAVASCRIPT_BASE_URL = 'powercaptcha_javascript_base_url';

        /**
         * @var PowerCaptchaIntegration[] $integrations
         */
        private array $integrations = array();
    
        public static function instance() {
            if(self::$instance === null || !self::$instance instanceof self) {
                self::$instance = new self();
            }
    
            return self::$instance;
        }

        private function __construct() {
            $this->register_integration(
                self::WPFORMS_INTEGRATION,
                __('WPForms', 'power-captcha'),
                __('Enable protection for <a href="https://wordpress.org/plugins/wpforms/" target="_blank">WPForms</a> and <a href="https://wordpress.org/plugins/wpforms-lite/" target="_blank">WPForms lite</a> plugin.', 'power-captcha'),
                'integrations/wpforms/wpforms.php'
            );

            $this->register_integration(
                self::WORDPRESS_LOGIN_INTEGRATION,
                __('WordPress Login', 'power-captcha'),
                __('Enable protection for the WordPress login form.', 'power-captcha'), 
                'integrations/wordpress/wordpress-login.php'
            );

            $this->register_integration(
                self::WORDPRESS_REGISTER_INTEGRATION,
                __('WordPress Registration', 'power-captcha'),
                __('Enable protection for the WordPress registration form.', 'power-captcha'), 
                'integrations/wordpress/wordpress-register.php'
            );
        }

        private function register_integration(string $key, string $setting_title, string $setting_description, string $file_path) {
            if(array_key_exists($key, $this->integrations)) {
                throw new \RuntimeException("Integration with key '$key' was already registered. Integration keys have to be unique.");
            }

            $this->integrations[$key] = new PowerCaptchaIntegration(
                $key, 
                $setting_title, 
                $setting_description, 
                $file_path
            );
        }

        /**
         * @return PowerCaptchaIntegration[] $integrations
         */
        public function get_integrations() : array {
            return $this->integrations;
        }
    
        public function is_configured() {
            // only configured if api key and secret key are not empty
            return !empty($this->get_api_key()) && !empty($this->get_secret_key());
        }
    
        public function get_api_key() {
            return self::get_setting_text(self::SETTING_NAME_API_KEY);
        }
    
        public function get_secret_key() {
            return self::get_setting_text(self::SETTING_NAME_SECRET_KEY);
        }
    
        private function get_endpoint_base_url() {
            $endpoint_base_url = self::get_setting_text(self::SETTING_NAME_ENDPOINT_BASE_URL);  
            if(empty($endpoint_base_url)) {
                // using default
                $endpoint_base_url = self::DEFAULT_ENDPOINT_BASE_URL;
            }
            return untrailingslashit($endpoint_base_url); // return without trailing slash
        }
    
        public function get_token_request_url() {
            return $this->get_endpoint_base_url() . '/pc/'. self::API_VERSION;
        }
    
        public function get_token_verification_url() {
            return $this->get_endpoint_base_url() . '/pcu/' . self::API_VERSION . '/verify'; 
        }
    
        private function get_javascript_base_url() {
            $javascript_url = self::get_setting_text(self::SETTING_NAME_JAVASCRIPT_BASE_URL);
            if(empty($javascript_url)) {
                // using default
                $javascript_url = self::DEFAULT_JAVASCRIPT_URL;
            }
            return untrailingslashit($javascript_url); // return without trailing slash
        }

        public function get_javascript_url() {
            return $this->get_javascript_base_url() . '/' . self::JS_VERSION . '/uii-catpcha-lib.iife.js';
        }

        public function is_enabled(string $key) {
            if(!$this->is_configured()) {
                return false;
            }

            if(!array_key_exists($key, $this->integrations)) {
                throw new \RuntimeException("Integration with key '$key' was not registered.");
            }
            $integration = $this->integrations[$key];
            return $integration->is_enabled();
        }
    
        private static function get_setting_bool($setting_name) {
            return (get_option($setting_name) == 1);
        }
    
        private static function get_setting_text($setting_name) {
            return trim(get_option($setting_name));
        }
    }

    class PowerCaptchaIntegration {
        const SETTING_ENABLED_NAME_PREFIX = 'powercaptcha_integration_enabled_';

        private string $integration_key;
        private string $setting_title;
        private string $setting_description;

        private string $file_path;

        public function __construct(string $integration_key, string $setting_title, string $setting_description, string $file_path) {
            $this->integration_key = $integration_key;
            $this->setting_title = $setting_title;
            $this->setting_description = $setting_description;
            $this->file_path = $file_path;
        }

        public function get_key() : string {
            return $this->integration_key;
        }

        public function get_setting_name() : string {
            return self::SETTING_ENABLED_NAME_PREFIX . $this->get_key();
        }

        public function get_setting_title() : string {
            return $this->setting_title;
        }

        public function get_setting_description() : string {
            return $this->setting_description;
        }

        public function get_file_path() : string {
            return $this->file_path;
        }

        public function is_enabled() {
            return (get_option($this->get_setting_name()) == 1);
        }
    }
}

namespace {
    function powercaptcha() {
        return PowerCaptcha_WP\PowerCaptcha::instance();
    }
}
