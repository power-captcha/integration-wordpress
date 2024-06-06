<?php

defined('POWER_CAPTCHA_PATH') || exit;

    if(is_admin()) {

        add_action( 'admin_menu', 'powercaptcha_admin_menu' );
        function powercaptcha_admin_menu() {
            // https://codex.wordpress.org/Administration_Menus
            add_options_page(
                __('POWER CAPTCHA Settings', 'power-captcha'), // page_title
                __('POWER CAPTCHA Settings', 'power-captcha'), // menu_title
                'manage_options', // capability
                powercaptcha()::SETTING_PAGE, // menu_slug
                'powercaptcha_admin_page_content',
                30 // icon_url (or icon id?) TODO
            );
        }

        function powercaptcha_admin_page_content() {
            /* if ( !current_user_can( 'manage_options' ) )  { // TODO berechtigungen?
                wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
            } */
            ?>
            <div class="wrap">
                <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
                <form action="options.php" method="post">
                    <?php
                    settings_errors($hide_on_update = true); 
                    // TODO hide on update? https://developer.wordpress.org/reference/functions/settings_errors/
                    // if not hide on update the success message is displayed twice.
                    
                    settings_fields(powercaptcha()::SETTING_GROUP_NAME);
                    do_settings_sections(powercaptcha()::SETTING_PAGE);
                    submit_button();
                    ?>
                </form>
            </div>
            <?php
        }


        add_action( 'admin_init', 'powercaptcha_init_admin_settings' );
        function powercaptcha_init_admin_settings() {
            
            // https://developer.wordpress.org/reference/functions/register_setting/

            // general settings
            register_setting(
                powercaptcha()::SETTING_GROUP_NAME,
                powercaptcha()::SETTING_NAME_API_KEY,
                ['default' => ''] // args (data used to describe settings when registered) type, description, santizize_callback, show_in_rest, default; TODO is this needed? also for the other options?
            );
            
            register_setting(
                powercaptcha()::SETTING_GROUP_NAME,
                powercaptcha()::SETTING_NAME_SECRET_KEY
            );

            // captcha settings
            register_setting(
                powercaptcha()::SETTING_GROUP_NAME,
                powercaptcha()::SETTING_NAME_CHECK_MODE,
            );

            // enterprise settings
            register_setting(
                powercaptcha()::SETTING_GROUP_NAME,
                powercaptcha()::SETTING_NAME_ENDPOINT_BASE_URL
            );

            register_setting(
                powercaptcha()::SETTING_GROUP_NAME,
                powercaptcha()::SETTING_NAME_JAVASCRIPT_BASE_URL
            );
            
            // https://developer.wordpress.org/reference/functions/add_settings_section/

            // general settings section
            add_settings_section( 
                powercaptcha()::SETTING_SECTION_GENERAL, 
                __('General settings', 'power-captcha'), 
                function() { // description
                    echo '<p>'.sprintf(
                        /** translators %s: url to power captcha API Key management page */
                        __('The API Key and the Secret Key must be provided for the POWER CAPTCHA to activate. Both keys can be found in the <a href="%s" target="_blank">API Key Management</a>.', 'power-captcha'),
                        powercaptcha()::API_KEY_MANAGEMENT_URL
                        ).'</p>';
                    echo '<p>'.sprintf(
                        /** translators %s: url to power captcha Shop page */
                        __('If you don\'t have an API Key yet, you can create one for free on <a href="%s" target="_blank">POWER CAPTCHA</a>.', 'power-captcha'),
                        powercaptcha()::SHOP_URL
                    ).'</p>';
                },
                powercaptcha()::SETTING_PAGE 
            );
            
            // captcha settings sections
            add_settings_section(
                powercaptcha()::SETTING_SECTION_CAPTCHA,
                __('Captcha settings', 'power-captcha'), 
                function () { // description
                    echo '<p>'.
                        __('You can configure the functionality and display of the captcha or widget here.', 'power-captcha')
                    .'</p>';
                },
                powercaptcha()::SETTING_PAGE 
            );
            
            // integration setting section
            add_settings_section(
                powercaptcha()::SETTING_SECTION_INTEGRATION, 
                __('Integration settings', 'power-captcha'), 
                function () { // description
                    echo '<p>'.
                    __('Specify which sections or plugins should be protected with POWER CAPTCHA.', 'power-captcha')
                    .'</p>';
                },
                powercaptcha()::SETTING_PAGE
            );
    
            // enterprise settings section
            add_settings_section(
                powercaptcha()::SETTING_SECTION_ON_PREMISES,
                __('On-premises settings', 'power-captcha'),
                function () { // description
                    echo '<p>'.
                    __('These settings are only relevant if you are running a self-hosted POWER CAPTCHA instance. Otherwise you can leave these settings empty.', 'power-captcha')
                    .'</p>';
                },
                powercaptcha()::SETTING_PAGE
            );

            // https://developer.wordpress.org/reference/functions/add_settings_field/

            // general settings fields
            powercaptcha_setting_add_text_field(
                powercaptcha()::SETTING_SECTION_GENERAL,
                powercaptcha()::SETTING_NAME_API_KEY,
                '',
                __('API Key', 'power-captcha'),
                sprintf(
                    /** translators %s: url to power captcha API Key management page */
                    __('Enter your POWER CAPTCHA API Key. You can find your API Key in the <a href="%s" target="_blank">API Key Management</a>.', 'power-captcha'),
                    powercaptcha()::API_KEY_MANAGEMENT_URL
                )
            );

            powercaptcha_setting_add_text_field(
                powercaptcha()::SETTING_SECTION_GENERAL,
                powercaptcha()::SETTING_NAME_SECRET_KEY,
                '',
                __('Secret Key', 'power-captcha'),
                sprintf(
                    /** translators %s: url to power captcha API Key management page */
                    __('Enter your POWER CAPTCHA Secret Key. You can find your Secret Key in the <a href="%s" target="_blank">API Key Management</a>.', 'power-captcha'),
                    powercaptcha()::API_KEY_MANAGEMENT_URL
                )
            );

            // widget settings fields 
            powercaptcha_setting_add_radio_field(
                powercaptcha()::SETTING_SECTION_CAPTCHA,
                powercaptcha()::SETTING_NAME_CHECK_MODE,
                [
                    'auto' => [
                        'label' => __('Automatic', 'power-captcha'),
                        'description' => __('The widget is always displayed and the security check is started automatically as soon as the form is filled in or after the corresponding field (e.g. user name or email address) has been filled in.  A click on the widget is only necessary if it is required to solve a captcha.', 'power-captcha'),
                    ],
                    'hidden' => [
                        'label' => __('Hidden', 'power-captcha'),
                        'description' => __('The widget is not displayed initially and the security check is started automatically as soon as the form is filled in or after the corresponding field (e.g. user name or e-mail address) has been filled in. The widget is only displayed if it is required to solve a captcha.', 'power-captcha'),
                    ],
                    'manu' => [
                        'label' => __('Manual', 'power-captcha'),
                        'description' => __('The widget is always displayed and the security check is only started when the widget is clicked. The click is always required.', 'power-captcha'),
                    ]
                ],
                'auto',
                __('Check mode', 'power-captcha'),
                ''
            );

            // integration settings fields
            // integration settings
            foreach(powercaptcha()->get_integrations() as $key => $integration) {
                /** @var string $key */
                /** @var PowerCaptcha_WP\PowerCaptchaIntegration $integration */
                
                // register integration setting
                register_setting(
                    powercaptcha()::SETTING_GROUP_NAME,
                    $integration->get_setting_name()
                );

                // add setting field
                powercaptcha_setting_add_checkbox_field(
                    powercaptcha()::SETTING_SECTION_INTEGRATION,
                    $integration->get_setting_name(),
                    0,
                    $integration->get_setting_title(),
                    $integration->get_setting_description() 
                );
            }

            // on premise settings fields
            powercaptcha_setting_add_text_field( //TODO we have to validate if the endpoint url is valid, before saving the setting!
                powercaptcha()::SETTING_SECTION_ON_PREMISES,
                powercaptcha()::SETTING_NAME_ENDPOINT_BASE_URL,
                '',
                __('Endpoint base URL (optional)', 'power-captcha'),
                __('Only required if you have an on-premises version with self-hosted POWER CAPTCHA endpoint.', 'power-captcha')
            );

            powercaptcha_setting_add_text_field( //TODO we have to validate if the endpoint url is valid, before saving the setting!
                powercaptcha()::SETTING_SECTION_ON_PREMISES,
                powercaptcha()::SETTING_NAME_JAVASCRIPT_BASE_URL,
                '',
                __('JavaScript base URL (optional)', 'power-captcha'),
                __('Only required if you have an on-premises version with self-hosted POWER CAPTCHA JavaScript.', 'power-captcha')
            );
        }


        // util
        function powercaptcha_setting_add_text_field($section, $setting_name, $default_value, $title, $description) {
            $field_id = $setting_name."_field";
            $setting_value = get_option($setting_name, $default_value);

            $render_args = [
                'name' => $setting_name,
                'value' => $setting_value,
                'id' => $field_id,
                'label' => $description,
            ];

            add_settings_field(
                $field_id, 
                $title, 
                'powercaptcha_setting_render_text_field', // callback function to display the field
                powercaptcha()::SETTING_PAGE, 
                $section,
                $render_args
            );
        }

        function powercaptcha_setting_add_checkbox_field($section, $setting_name, $default_value, $title, $description) {
            $field_id = $setting_name."_field";
            $setting_value = get_option($setting_name, $default_value);
            
            $render_args = [
                'name' => $setting_name,
                'value' => $setting_value,
                'id' => $field_id,
                'label' => $description,
            ];

            add_settings_field(
                $field_id, 
                $title, 
                'powercaptcha_setting_render_checkbox_field', // callback function to display the field
                powercaptcha()::SETTING_PAGE, 
                $section,
                $render_args
            );
        }

        function powercaptcha_setting_add_radio_field($section, $setting_name, $options, $default_value, $title, $description) {
            $field_id = $setting_name."_field";
            $setting_value = get_option($setting_name, $default_value);
            
            $render_args = [
                'name' => $setting_name,
                'value' => $setting_value,
                'id' => $field_id,
                'label' => $description,
                'options' => $options
            ];

            add_settings_field(
                $field_id, 
                $title, 
                'powercaptcha_setting_render_radio_field', // callback function to display the field
                powercaptcha()::SETTING_PAGE, 
                $section,
                $render_args
            );
        }

        function powercaptcha_setting_render_text_field(array $render_args) {
?>
    <input type="text" id="<?php echo esc_attr($render_args['id']); ?>" name="<?php echo esc_attr($render_args['name']); ?>" value="<?php echo esc_attr($render_args['value']); ?>" autocomplete="none"> 
    <label for="<?php echo esc_attr($render_args['id']); ?>" class="description"><?php echo $render_args['label']; ?></label>
<?php
    }

    function powercaptcha_setting_render_checkbox_field(array $render_args) {
        $checked = checked(1, $render_args['value'], false);
        ?>
            <input type="checkbox" 
                id="<?php echo esc_attr($render_args['id']); ?>" 
                name="<?php echo esc_attr($render_args['name']); ?>" value="1" <?php echo $checked ?>>
            <label for="<?php echo esc_attr($render_args['id']); ?>" class="description">
                <?php echo $render_args['label']; ?>
            </label>
<?php
    }

    function powercaptcha_setting_render_radio_field(array $render_args) {
?>
        <fieldset>
        <legend><?php echo esc_html($render_args['label']) ?></legend>
<?php
                foreach($render_args['options'] as $option_value => $option_details):
                    $option_checked = checked($option_value, $render_args['value'], false);
?>
            <div>
                <label for="<?php echo esc_attr($option_value); ?>">
                    <input type="radio" 
                        id="<?php echo esc_attr($option_value); ?>" 
                        name="<?php echo esc_attr($render_args['name']) ?>" 
                        value="<?php echo esc_attr($option_value) ?>" 
                        <?php echo $option_checked ?>
                    >
                    <strong><?php echo $option_details['label']; ?></strong>
                    <p class="description">
                        <?php echo $option_details['description']; ?>
                    </p>
                </label>
            </div>
                <?php endforeach; ?>
    </fieldset>
<?php
    }
}
?>