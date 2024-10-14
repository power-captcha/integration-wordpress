<?php

namespace PowerCaptcha_WP;

defined('POWER_CAPTCHA_PATH') || exit;

add_action('powercaptcha_register_integration', function ($powerCaptcha) {
    $powerCaptcha->register_integration(new Integration_WordPress_Register());
});

class Integration_WordPress_Register extends Integration {

    public function __construct() {
        $this->id = 'wordpress_register';
        $this->setting_title = __('WordPress Registration', 'power-captcha');
        $this->setting_description = __('Enable protection for the WordPress registration form.', 'power-captcha');
    }

    public function init() {
        add_action('register_form', [$this, 'display_widget']);
        add_action('register_form', [$this, 'enqueue_script']);

        add_action('register_post', [$this, 'verification'], 10, 3);
    }

    public function display_widget() {
        parent::echo_widget_html('#user_email', true, '', 'margin-bottom: 16px');
    }

    public function enqueue_script() {
        parent::enqueue_scripts();
    }

    public function verification(string $sanitized_user_login, string $user_email, \WP_Error $errors) {
        if(empty($_POST)) {
            return;
        }

        $verification = $this->verify_token($_POST['user_email']); 
        if(FALSE === $verification->is_success()) {
            $errors->add($verification->get_error_code(), $verification->get_user_message());
        }
    }

}