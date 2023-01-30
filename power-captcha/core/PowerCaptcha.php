<?php
namespace PowerCaptcha_WP {
    final class PowerCaptcha {
    
        const API_VERSION = 'v1';
        const JS_VERSION = 'v1';

        // Singelton instance
        private static $instance;
    
        const PLUGIN_NAME = 'powercaptcha'; 
        const DEFAULT_ENDPOINT_BASE_URL = 'https://api.power-captcha.com/';
        const DEFAULT_JAVASCRIPT_URL = 'https://cdn.power-captcha.com/' . self::JS_VERSION . '/uii-catpcha-lib.iife.js';
    
        // settings
        const SETTING_PAGE = 'powercaptcha_admin';
        const SETTING_GROUP_NAME = 'powercaptcha_admin_settings';
    
        // general settings
        const SETTING_SECTION_GENERAL = 'powercaptcha_setting_section_general';
        const SETTING_NAME_API_KEY = 'powercaptcha_api_key';
        const SETTING_NAME_SECRET_KEY = 'powercaptcha_secret_key';
        
        // integration settings
        const SETTING_SECTION_INTEGRATION = 'powercaptcha_setting_section_integration';
        const SETTING_NAME_WPFORMS_INTEGRATION = 'powercaptcha_wpforms_integration';
    
        // enterprise settings
        const SETTING_SECTION_ENTERPRISE = 'powercaptcha_setting_section_enterprise';
        const SETTING_NAME_ENDPOINT_BASE_URL = 'powercaptcha_endpoint_base_url';
    
        public static function instance() {
            if(self::$instance === null || !self::$instance instanceof self) {
                self::$instance = new self();
            }
    
            return self::$instance;
        }
    
        public function is_configured() {
            // only configured if api key is not empty
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
    
        public function get_javascript_url() {
            $javascript_url = ''; // TODO let the user customize javascript url via settings?
            if(empty($javascript_url)) {
                return self::DEFAULT_JAVASCRIPT_URL;
            }
            return $javascript_url;
        }
    
        public function is_wpforms_integration_enabled() {
            return $this->is_configured() && self::get_setting_bool(self::SETTING_NAME_WPFORMS_INTEGRATION);
        }
    
        private static function get_setting_bool($setting_name) {
            return (get_option($setting_name) == 1);
        }
    
        private static function get_setting_text($setting_name) {
            return trim(get_option($setting_name));
        }
    }
    
}

namespace {
    function powercaptcha() {
        return PowerCaptcha_WP\PowerCaptcha::instance();
    }
}
