<?php

class PowerCaptcha_WP {
    
    // Singelton instance
    private static $instance;

    private static $plugin_name = "powercaptcha"; 
    public static $default_endpoint_url = "https://api.power-captcha.com/";

    public static $setting_page = "powercaptcha_admin";
    public static $setting_group_name = "powercaptcha_admin_settings";

    // setting sections
    public static $setting_section_general = "powercaptcha_setting_section_general";
    // general settings
    public static $setting_name_api_key = "powercaptcha_api_key";

    public static $setting_section_enterprise = "powercaptcha_setting_section_enterprise";
    // enterprise settings
    public static $setting_name_endpoint_url = "powercaptcha_endpoint_url";

    public static $setting_section_integration = "powercaptcha_setting_section_integration";
    // integration settings
    public static $setting_name_wpforms_integration = "powercaptcha_wpforms_integration";

    public static function instance() {
        if(!self::$instance) {
            self::$instance = new PowerCaptcha_WP();
        }
        return self::$instance;
    }

    public function is_configured() {
        // only configured if api key is not empty
        return !empty($this->get_api_key());
    }

    public function get_api_key() {
        return $this->get_setting_text(self::$setting_name_api_key);
    }

    public function get_endpoint_url() {
        $endpoint_url = $this->get_setting_text(self::$setting_name_endpoint_url);
        if(empty($endpoint_url)) {
            // return default endpoint
            return self::$default_endpoint_url;
        }
        return $endpoint_url;
    }

    public function is_wpforms_integration_enabled() {
        return $this->get_setting_bool(self::$setting_name_wpforms_integration);
    }

    private function get_setting_bool($setting_name) {
        return (get_option($setting_name) == 1);
    }

    private function get_setting_text($setting_name) {
        return trim(get_option($setting_name));
    }
}



//if (FriendlyCaptcha_Plugin::$instance->get_wpforms_active()) {
    if(PowerCaptcha_WP::instance()->is_configured()) {
        if (PowerCaptcha_WP::instance()->is_wpforms_integration_enabled()) {
            require plugin_dir_path( __FILE__ ) . 'integrations/wpforms/wpforms.php';
        }
    }


    // Init admin settings
    require plugin_dir_path( __FILE__ ) . 'admin.php';