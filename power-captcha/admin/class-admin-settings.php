<?php

namespace Power_Captcha_WP;

defined( 'POWER_CAPTCHA_PATH' ) || exit;

class Admin_Settings {

	public function __construct() {}

	public function init() {
		if ( ! is_admin() ) {
			return;
		}

		add_action( 'admin_menu', array( $this, 'init_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_init', array( $this, 'init_settings_sections' ) );
		add_action( 'admin_init', array( $this, 'init_settings_fields' ) );

		add_filter( 'plugin_action_links_power-captcha/power-captcha.php', array( $this, 'add_plugin_action_links' ) );
	}

	public function add_plugin_action_links( array $actions ) {
		// adding link to settings page on the plugin list overview

		// Build and escape the URL.
		$url = esc_url(
			add_query_arg(
				'page',
				powercaptcha()::SETTING_PAGE,
				get_admin_url() . 'admin.php'
			)
		);
		// Create the link.
		$settings_link = '<a href="' . $url . '">' . __( 'Settings' ) . '</a>';
		// Adds the link to the end of the array.
		array_push(
			$actions,
			$settings_link
		);
		return $actions;
	}

	public function init_admin_menu() {
		// https://codex.wordpress.org/Administration_Menus
		add_options_page(
			__( 'POWER CAPTCHA Settings', 'power-captcha' ), // page_title
			__( 'POWER CAPTCHA Settings', 'power-captcha' ), // menu_title
			'manage_options', // capability
			powercaptcha()::SETTING_PAGE, // menu_slug
			array( $this, 'admin_page_content' ),
			30 // icon_url (or icon id?) TODO
		);
	}

	public function admin_page_content() {
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<form action="options.php" method="post">
				<?php
				settings_errors( hide_on_update: true );
				// TODO hide on update? https://developer.wordpress.org/reference/functions/settings_errors/
				// if not hide on update the success message is displayed twice.

				settings_fields( powercaptcha()::SETTING_GROUP_NAME );
				do_settings_sections( powercaptcha()::SETTING_PAGE );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	public function register_settings() {
		// https://developer.wordpress.org/reference/functions/register_setting/

		// general settings
		register_setting(
			powercaptcha()::SETTING_GROUP_NAME,
			powercaptcha()::SETTING_NAME_API_KEY,
			array( 'default' => '' ) // args (data used to describe settings when registered) type, description, santizize_callback, show_in_rest, default; TODO is this needed? also for the other options?
		);
		register_setting(
			powercaptcha()::SETTING_GROUP_NAME,
			powercaptcha()::SETTING_NAME_SECRET_KEY
		);

		// integration settings
		foreach ( Power_Captcha::instance()->get_integrations() as $key => $integration ) {
			/** @var Integration $integration */
			register_setting(
				powercaptcha()::SETTING_GROUP_NAME,
				$integration->get_setting_name()
			);
		}

		// captcha settings
		register_setting(
			powercaptcha()::SETTING_GROUP_NAME,
			powercaptcha()::SETTING_NAME_CHECK_MODE,
		);
		register_setting(
			powercaptcha()::SETTING_GROUP_NAME,
			powercaptcha()::SETTING_NAME_API_ERROR_POLICY,
		);

		// on premises settings
		register_setting(
			powercaptcha()::SETTING_GROUP_NAME,
			powercaptcha()::SETTING_NAME_ENDPOINT_BASE_URL
		);
		register_setting(
			powercaptcha()::SETTING_GROUP_NAME,
			powercaptcha()::SETTING_NAME_JAVASCRIPT_BASE_URL
		);
	}

	public function init_settings_sections() {
		// https://developer.wordpress.org/reference/functions/add_settings_section/

		// general settings section
		add_settings_section(
			powercaptcha()::SETTING_SECTION_GENERAL,
			__( 'General settings', 'power-captcha' ),
			array( $this, 'general_setting_section_description_content' ),
			powercaptcha()::SETTING_PAGE
		);

		// integration setting section
		add_settings_section(
			powercaptcha()::SETTING_SECTION_INTEGRATION,
			__( 'Integration settings', 'power-captcha' ),
			array( $this, 'integration_setting_section_description_content' ),
			powercaptcha()::SETTING_PAGE
		);

		// captcha settings section
		add_settings_section(
			powercaptcha()::SETTING_SECTION_CAPTCHA,
			__( 'Captcha settings', 'power-captcha' ),
			array( $this, 'captcha_setting_section_description_content' ),
			powercaptcha()::SETTING_PAGE
		);

		// on premises settings section
		add_settings_section(
			powercaptcha()::SETTING_SECTION_ON_PREMISES,
			__( 'On-premises settings', 'power-captcha' ),
			array( $this, 'on_premises_setting_section_description_content' ),
			powercaptcha()::SETTING_PAGE
		);
	}

	public function init_settings_fields() {

		// general settings
		$this->add_setting_text_field(
			powercaptcha()::SETTING_SECTION_GENERAL,
			powercaptcha()::SETTING_NAME_API_KEY,
			'',
			__( 'API Key', 'power-captcha' ),
			sprintf(
				/* translators: %s: link to power captcha customer area */
				__( 'Enter your POWER CAPTCHA API Key. You can find your API Key in our %s.', 'power-captcha' ),
				'<a href="' . esc_attr__( 'https://power-captcha.com/en/my-account/api-keys/', 'power-captcha' ) . '" target="_blank">' . esc_html__( 'customer area', 'power-captcha' ) . '</a>'
			)
		);
		$this->add_setting_text_field(
			powercaptcha()::SETTING_SECTION_GENERAL,
			powercaptcha()::SETTING_NAME_SECRET_KEY,
			'',
			__( 'Secret Key', 'power-captcha' ),
			sprintf(
				/* translators: %s link to power captcha customer area */
				__( 'Enter your POWER CAPTCHA Secret Key. You can find your Secret Key in our %s.', 'power-captcha' ),
				'<a href="' . esc_attr__( 'https://power-captcha.com/en/my-account/api-keys/', 'power-captcha' ) . '" target="_blank">' . esc_html__( 'customer area', 'power-captcha' ) . '</a>'
			)
		);

		// captcha settings
		$this->add_setting_radio_field(
			powercaptcha()::SETTING_SECTION_CAPTCHA,
			powercaptcha()::SETTING_NAME_CHECK_MODE,
			array(
				'auto'   => array(
					'label'       => __( 'Automatic (default)', 'power-captcha' ),
					'description' => __( 'The widget is always displayed and the security check starts automatically as soon as the form is filled in or after the corresponding field (e.g. user name or email address) has been filled in.  A click on the widget is only necessary if it is required to solve a captcha.', 'power-captcha' ),
				),
				'hidden' => array(
					'label'       => __( 'Hidden', 'power-captcha' ),
					'description' => __( 'The widget is not displayed initially and the security check starts automatically as soon as the form is filled in or after the corresponding field (e.g. user name or e-mail address) has been filled in. The widget is only displayed if it is required to solve a captcha.', 'power-captcha' ),
				),
				'manu'   => array(
					'label'       => __( 'Manual', 'power-captcha' ),
					'description' => __( 'The widget is always displayed and the security check is only starts when the widget is clicked. The click is always required.', 'power-captcha' ),
				),
			),
			'auto',
			__( 'Check mode', 'power-captcha' ),
			__( 'Configure the display of the widget and the behaviour of the security check.', 'power-captcha' )
		);

		$this->add_setting_radio_field(
			powercaptcha()::SETTING_SECTION_CAPTCHA,
			powercaptcha()::SETTING_NAME_API_ERROR_POLICY,
			array(
				powercaptcha()::ERROR_POLICY_GRANT_ACCESS => array(
					'label'       => __( 'Grant access (default)', 'power-captcha' ),
					'description' => __( 'Access is granted if an API error occurs.', 'power-captcha' ),
				),
				powercaptcha()::ERROR_POLICY_BLOCK_ACCESS => array(
					'label'       => __( 'Block access', 'power-captcha' ),
					'description' => __( 'Access is blocked if an API error occurs. An error message is displayed requesting the user to try again later.', 'power-captcha' ),
				),
			),
			powercaptcha()::ERROR_POLICY_GRANT_ACCESS,
			__( 'API Error Policy', 'power-captcha' ),
			__( 'Configure the behaviour in the case of errors during token verification via the POWER CAPTCHA API (e.g. connection problems to the API or incorrect configuration).', 'power-captcha' )
		);

		// integration settings
		foreach ( Power_Captcha::instance()->get_integrations() as $key => $integration ) {
			/** @var Integration $integration */
			$this->add_setting_checkbox_field(
				powercaptcha()::SETTING_SECTION_INTEGRATION,
				$integration->get_setting_name(),
				0,
				$integration->get_setting_title(),
				$integration->get_setting_description()
			);
		}

		// on-premises settings
		$this->add_setting_text_field(
			// TODO we have to validate if the endpoint url is valid, before saving the setting!
			powercaptcha()::SETTING_SECTION_ON_PREMISES,
			powercaptcha()::SETTING_NAME_ENDPOINT_BASE_URL,
			'',
			__( 'Endpoint base URL (optional)', 'power-captcha' ),
			__( 'Only required if you have an on-premises version with self-hosted POWER CAPTCHA endpoint.', 'power-captcha' )
		);
		$this->add_setting_text_field(
			powercaptcha()::SETTING_SECTION_ON_PREMISES,
			powercaptcha()::SETTING_NAME_JAVASCRIPT_BASE_URL,
			'',
			__( 'JavaScript base URL (optional)', 'power-captcha' ),
			__( 'Only required if you have an on-premises version with self-hosted POWER CAPTCHA JavaScript.', 'power-captcha' )
		);
	}

	private function add_setting_text_field( $section, $setting_name, $default_value, $title, $description ) {
		$field_id      = $setting_name . '_field';
		$setting_value = get_option( $setting_name, $default_value );

		$render_args = array(
			'name'  => $setting_name,
			'value' => $setting_value,
			'id'    => $field_id,
			'label' => $description,
		);

		add_settings_field(
			$field_id,
			$title,
			array( $this, 'render_text_field' ),
			powercaptcha()::SETTING_PAGE,
			$section,
			$render_args
		);
	}

	private function add_setting_checkbox_field( $section, $setting_name, $default_value, $title, $description ) {
		$field_id      = $setting_name . '_field';
		$setting_value = get_option( $setting_name, $default_value );

		$render_args = array(
			'name'  => $setting_name,
			'value' => $setting_value,
			'id'    => $field_id,
			'label' => $description,
		);

		add_settings_field(
			$field_id,
			$title,
			array( $this, 'render_checkbox_field' ),
			powercaptcha()::SETTING_PAGE,
			$section,
			$render_args
		);
	}

	private function add_setting_radio_field( $section, $setting_name, $options, $default_value, $title, $description ) {
		$field_id      = $setting_name . '_field';
		$setting_value = get_option( $setting_name, $default_value );

		$render_args = array(
			'name'    => $setting_name,
			'value'   => $setting_value,
			'id'      => $field_id,
			'label'   => $description,
			'options' => $options,
		);

		add_settings_field(
			$field_id,
			$title,
			array( $this, 'render_radio_field' ),
			powercaptcha()::SETTING_PAGE,
			$section,
			$render_args
		);
	}

	public function render_text_field( array $render_args ) {
		?>
		<input type="text" id="<?php echo esc_attr( $render_args['id'] ); ?>" name="<?php echo esc_attr( $render_args['name'] ); ?>" value="<?php echo esc_attr( $render_args['value'] ); ?>" autocomplete="none"> 
		<label for="<?php echo esc_attr( $render_args['id'] ); ?>" class="description"><?php echo wp_kses_post( $render_args['label'] ); ?></label>
		<?php
	}

	public function render_checkbox_field( array $render_args ) {
		$checked = checked( 1, $render_args['value'], false );
		?>
		<input type="checkbox" 
			id="<?php echo esc_attr( $render_args['id'] ); ?>" 
			name="<?php echo esc_attr( $render_args['name'] ); ?>" value="1" <?php echo esc_attr( $checked ); ?>>
		<label for="<?php echo esc_attr( $render_args['id'] ); ?>" class="description">
			<?php echo wp_kses_post( $render_args['label'] ); ?>
		</label>
		<?php
	}

	public function render_radio_field( array $render_args ) {
		?>
		<p style="margin-bottom: 4px"><?php echo esc_html( $render_args['label'] ); ?></p>
		<fieldset>
		<?php
		foreach ( $render_args['options'] as $option_value => $option_details ) :
			$option_checked = checked( $option_value, $render_args['value'], false );
			?>
			<div>
				<label for="<?php echo esc_attr( $option_value ); ?>">
					<input type="radio" 
						id="<?php echo esc_attr( $option_value ); ?>" 
						name="<?php echo esc_attr( $render_args['name'] ); ?>" 
						value="<?php echo esc_attr( $option_value ); ?>" 
				<?php echo esc_attr( $option_checked ); ?>
					>
					<strong><?php echo esc_html( $option_details['label'] ); ?></strong>
					<p class="description">
				<?php echo wp_kses_post( $option_details['description'] ); ?>
					</p>
				</label>
			</div>
		<?php endforeach; ?>
		</fieldset>
		<?php
	}

	public function general_setting_section_description_content() {
		echo '<p>' . sprintf(
			/* translators: %s: link to power captcha customer area */
			esc_html__( 'To activate POWER CAPTCHA, an API Key and a Secret Key are required. You can find both in our %s.', 'power-captcha' ),
			'<a href="' . esc_attr__( 'https://power-captcha.com/en/my-account/api-keys/', 'power-captcha' ) . '" target="_blank">' . esc_html__( 'customer area', 'power-captcha' ) . '</a>'
		) . '</p>';
		echo '<p>' . sprintf(
			/* translators: %s: link to power captcha Shop page */
			esc_html__( 'If you don\'t have an API Key yet, you can create one on %s.', 'power-captcha' ),
			'<a href="' . esc_attr__( 'https://power-captcha.com/en/power-captcha-shop-licenses/', 'power-captcha' ) . '" target="_blank">' . esc_html__( 'POWER CAPTCHA', 'power-captcha' ) . '</a>'
		) . '</p>';
	}

	public function captcha_setting_section_description_content() {
		echo '<p>' .
			esc_html__( 'In these settings, you can adjust the widget display, the security check behavior, and how errors during token verification are handled.', 'power-captcha' )
			. '</p>';
	}

	public function integration_setting_section_description_content() {
		echo '<p>' .
			esc_html__( 'Specify which sections or plugins should be protected with POWER CAPTCHA.', 'power-captcha' )
			. '</p>';
	}

	public function on_premises_setting_section_description_content() {
		echo '<p>' .
			esc_html__( 'These settings are only relevant if you are running a self-hosted POWER CAPTCHA instance. Otherwise you can leave these settings empty.', 'power-captcha' )
			. '</p>';
	}
}
