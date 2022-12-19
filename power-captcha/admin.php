<?php
    require_once('core.php');

    if(is_admin()) {

        add_action( 'admin_menu', 'powercaptcha_admin_menu' );
        function powercaptcha_admin_menu() {
            // https://codex.wordpress.org/Administration_Menus
            add_options_page(
                'Power-Captcha Settings', // page_title
                'Power-Captcha', // menu_title
                'manage_options', // capability
                PowerCaptcha_WP::$setting_page, // menu_slug
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
                    
                    settings_fields(PowerCaptcha_WP::$setting_group_name);
                    do_settings_sections(PowerCaptcha_WP::$setting_page);
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
                PowerCaptcha_WP::$setting_group_name,
                PowerCaptcha_WP::$setting_name_api_key,
                ['default' => ''] // args (data used to describe settings when registered) type, description, santizize_callback, show_in_rest, default; TODO is this needed?
            );

            // integration settings
            register_setting(
                PowerCaptcha_WP::$setting_group_name, // option group name TODO outsource to class
                PowerCaptcha_WP::$setting_name_wpforms_integration, // option_name TODO outsource to class
                ['default' => ''] // args (data used to describe settings when registered) type, description, santizize_callback, show_in_rest, default; TODO is this needed?
            );

            // enterprise settings

            register_setting(
                PowerCaptcha_WP::$setting_group_name, // option group name TODO outsource to class
                PowerCaptcha_WP::$setting_name_endpoint_url, // option_name TODO outsource to class
                ['default' => 'https://api.power-captcha.com/'] 
                // args (data used to describe settings when registered) type, description, santizize_callback, show_in_rest, default; TODO is this needed?
            );
            
            // https://developer.wordpress.org/reference/functions/add_settings_section/

            // general settings section
            add_settings_section( PowerCaptcha_WP::$setting_section_general, 'General settings', 'powercaptcha_setting_section_general_description', PowerCaptcha_WP::$setting_page );
        
            // integration setting section
            add_settings_section( PowerCaptcha_WP::$setting_section_integration, 'Integration settings', 'powercaptcha_setting_section_integration_description', PowerCaptcha_WP::$setting_page );
    
            // enterprise settings section
            add_settings_section( PowerCaptcha_WP::$setting_section_enterprise, 'Enterpise settings', 'powercaptcha_setting_section_enterprise_description', PowerCaptcha_WP::$setting_page );

            // https://developer.wordpress.org/reference/functions/add_settings_field/

            // general settings fields
            powercaptcha_setting_add_default_field(
                PowerCaptcha_WP::$setting_section_general,
                PowerCaptcha_WP::$setting_name_api_key,
                'text',
                'API Key',
                'Enter your Power-Captcha API Key. You can manage your API keys on <a href="https://power-catpcha.com">power-catpcha.com</a> (TODO better description)' // TODO better description
            );

            // integration settings fields
            powercaptcha_setting_add_default_field(
                PowerCaptcha_WP::$setting_section_integration,
                PowerCaptcha_WP::$setting_name_wpforms_integration,
                'checkbox',
                'WPForms',
                'Secure <a href="https://wordpress.org/plugins/wpforms/" target="_blank">WPForms</a> and <a href="https://wordpress.org/plugins/wpforms-lite/" target="_blank">WPForms lite</a> with Power-Captcha.' // TODO better description
            );

            // enterprise settings fields
            powercaptcha_setting_add_default_field( //TODO we have to validate if the endpoint url is valid, before saving the setting!
                PowerCaptcha_WP::$setting_section_enterprise,
                PowerCaptcha_WP::$setting_name_endpoint_url,
                'text',
                'Endpoint URL',
                '(optional) Only needed if you have a selfhosted Power-Captcha endpoint (TODO better description)' // TODO better description
            );
        }
        
        function powercaptcha_setting_section_general_description() {
            echo "<p>TODO description for general section</p>"; //TODO better description
        }

        function powercaptcha_setting_section_integration_description() {
            echo "<p>Choose for which plugin or part of the website you want to secure with Power-Captcha.</p>"; //TODO better description
        }
        
        function powercaptcha_setting_section_enterprise_description() {
            echo "<p>TODO description for enterprise section</p>"; //TODO better description
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
                PowerCaptcha_WP::$setting_page, 
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