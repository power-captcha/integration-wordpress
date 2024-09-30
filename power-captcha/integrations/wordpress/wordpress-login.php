<?php

namespace PowerCaptcha_WP;

defined('POWER_CAPTCHA_PATH') || exit;

add_action('powercaptcha_register_integration', function ($powerCaptcha) {
    $powerCaptcha->register_integration(new Integration_WordPress_Login());
});

class Integration_WordPress_Login extends Integration {

    public function __construct() {
        $this->id = 'wordpress_login';
        $this->setting_title = __('WordPress Login', 'power-captcha');
        $this->setting_description = __('Enable protection for the WordPress login form.', 'power-captcha');
    }

    public function init() {
        add_action('login_form', [$this, 'display_widget'], 10, 0);
        add_action('login_form', [$this, 'enqueue_script'], 11, 0);

        add_filter('authenticate', [$this, 'verification'], 20, 3);
    }

    public function display_widget() {
        echo $this->widget_html('#username', false, '', 'margin-bottom: 16px');
    }

    public function enqueue_script() {
        parent::enqueue_scripts();
    }

    public function verification(null|\WP_User|\WP_Error $user, string $username, string $password) {
        if(empty($_POST)) {
            return $user;
        }

        // TODO use $_POST['log'] instead of $user to get the raw user input.
        // TODO merge this verification with WooCoommerce Login integration. note: WooCommerce login uses the field $_POST['username'] for username.
        $verification = $this->verify_token($username); 
        if(FALSE === $verification->is_success()) {
            return new \WP_Error($verification->get_error_code(), $verification->get_user_message());
        }

        return $user;
    }

}