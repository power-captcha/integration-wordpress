<?php
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
            add_settings_section( powercaptcha()::SETTING_SECTION_GENERAL, __('General settings', 'power-captcha'), 'powercaptcha_setting_section_general_description', powercaptcha()::SETTING_PAGE );
        
            // integration setting section
            add_settings_section( powercaptcha()::SETTING_SECTION_INTEGRATION, __('Integration settings', 'power-captcha'), 'powercaptcha_setting_section_integration_description', powercaptcha()::SETTING_PAGE );
    
            // enterprise settings section
            add_settings_section( powercaptcha()::SETTING_SECTION_ON_PREMISES, __('On-premises settings', 'power-captcha'), 'powercaptcha_setting_section_on_premises_description', powercaptcha()::SETTING_PAGE );

            // https://developer.wordpress.org/reference/functions/add_settings_field/

            // general settings fields
            powercaptcha_setting_add_default_field(
                powercaptcha()::SETTING_SECTION_GENERAL,
                powercaptcha()::SETTING_NAME_API_KEY,
                'text',
                __('API Key', 'power-captcha'),
                sprintf(
                    /** translators %s: url to power captcha API Key management page */
                    __('Enter your POWER CAPTCHA API Key. You can find your API Key in the <a href="%s" target="_blank">API Key management</a> page.', 'power-captcha'),
                    powercaptcha()::API_KEY_MANAGEMENT_URL
                )
            );

            powercaptcha_setting_add_default_field(
                powercaptcha()::SETTING_SECTION_GENERAL,
                powercaptcha()::SETTING_NAME_SECRET_KEY,
                'text',
                __('Secret Key', 'power-captcha'),
                sprintf(
                    /** translators %s: url to power captcha API Key management page */
                    __('Enter your POWER CAPTCHA Secret Key. You can find your Secret Key on the <a href="%s" target="_blank">API Key Management</a> page.', 'power-captcha'),
                    powercaptcha()::API_KEY_MANAGEMENT_URL
                )
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
                powercaptcha_setting_add_default_field(
                    powercaptcha()::SETTING_SECTION_INTEGRATION,
                    $integration->get_setting_name(),
                    'checkbox',
                    $integration->get_setting_title(),
                    $integration->get_setting_description() 
                );
            }

            // on premise settings fields
            powercaptcha_setting_add_default_field( //TODO we have to validate if the endpoint url is valid, before saving the setting!
                powercaptcha()::SETTING_SECTION_ON_PREMISES,
                powercaptcha()::SETTING_NAME_ENDPOINT_BASE_URL,
                'text',
                __('Endpoint base URL (optional)', 'power-captcha'),
                __('Only required if you have an on-premises version with self-hosted POWER CAPTCHA endpoint.')
            );

            powercaptcha_setting_add_default_field( //TODO we have to validate if the endpoint url is valid, before saving the setting!
                powercaptcha()::SETTING_SECTION_ON_PREMISES,
                powercaptcha()::SETTING_NAME_JAVASCRIPT_BASE_URL,
                'text',
                __('JavaScript base URL (optional)', 'power-captcha'),
                __('Only required if you have an on-premises version with self-hosted POWER CAPTCHA JavaScript.', 'power-captcha')
            );
        }
        
        function powercaptcha_setting_section_general_description() {
            echo '<p>'.
                sprintf(
                    /** translators %s: url to power captcha API Key management page */
                    __('The API Key and the Secret Key must be provided for the POWER CAPTCHA to activate. Both keys can be found on the <a href="%s" target="_blank">API Key Management</a> page.', 'power-captcha'),
                    powercaptcha()::API_KEY_MANAGEMENT_URL
                ).'</p>';
            echo '<p>'.sprintf(
                /** translators %s: url to power captcha Shop page */
                __('If you don\'t have an API Key yet, you can create one for free on <a href="%s" target="_blank">POWER CAPTCHA</a>.', 'power-captcha'),
                powercaptcha()::SHOP_URL
            ).'</p>';
        }

        function powercaptcha_setting_section_integration_description() {
            echo '<p>'.
                __('Specify which sections or plugins should be protected with POWER CAPTCHA.', 'power-captcha')
                .'</p>';
        }
        
        function powercaptcha_setting_section_on_premises_description() {
            echo '<p>'.
                __('These settings are only relevant if you are running a self-hosted POWER CAPTCHA instance. Otherwise you can leave these settings empty.', 'power-captcha')
            .'</p>';
        }

        // util
        function powercaptcha_setting_add_default_field($section, $setting_name, $type, $title, $description) {
            $field_id = $setting_name."_field";
            $render_args = [
                'setting_name' => $setting_name,
                'field_type' => $type,
                'field_label' => $description,
            ];

            add_settings_field(
                $field_id, 
                $title, 
                'powercatpcha_setting_render_default_field', // callback function to display the field
                powercaptcha()::SETTING_PAGE, 
                $section,
                $render_args
            );
        }

        function powercatpcha_setting_render_default_field(array $render_args) {
            $setting_name = $render_args['setting_name'];
            
            $field_name = $setting_name;
            $field_id = $setting_name.'_id'; 
            $field_type = $render_args['field_type'];
            $field_label = $render_args['field_label'];

            // get the setting value
            $setting_value = get_option($setting_name);
            $field_value = isset($setting_value) ? esc_attr($setting_value) : '';

            if ($field_type == 'checkbox'):
                $checked = checked(1, $setting_value, false);
?>
    <input type="checkbox" id="<?php echo $field_id; ?>" name="<?php echo $field_name; ?>" value="1" <?php echo $checked ?>>
    <label for="<?php echo $field_id; ?>" class="description"><?php echo $field_label ?></label>
<?php
            elseif ($field_type == 'text'):
?>
    <input type="text" id="<?php echo $field_id; ?>" name="<?php echo $field_name; ?>" value="<?php echo $field_value; ?>" autcomplete="none"> 
    <label for="<?php echo $field_id; ?>" class="description"><?php echo $field_label ?></label>
<?php
            endif;
            // TODO more field types!
        }

    }
?>