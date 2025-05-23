<?php

namespace Power_Captcha_WP;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly

final class Power_Captcha {

	const API_VERSION = 'v1';
	const JS_VERSION  = '1.2.8';

	const DEFAULT_ENDPOINT_BASE_URL = 'https://api.power-captcha.com';
	const DEFAULT_JAVASCRIPT_URL    = 'https://cdn.power-captcha.com';

	const ERROR_CODE_API_ERROR  = 'powercaptcha_api_error';
	const ERROR_CODE_USER_ERROR = 'powercaptcha_user_error';

	// settings
	const SETTING_PAGE       = 'powercaptcha_admin';
	const SETTING_GROUP_NAME = 'powercaptcha_admin_settings';

	// general settings
	const SETTING_SECTION_GENERAL = 'powercaptcha_setting_section_general';
	const SETTING_NAME_API_KEY    = 'powercaptcha_api_key';
	const SETTING_NAME_SECRET_KEY = 'powercaptcha_secret_key';

	// captcha settings
	const SETTING_SECTION_CAPTCHA       = 'powercaptcha_setting_section_captcha';
	const SETTING_NAME_CHECK_MODE       = 'powercaptcha_check_mode';
	const SETTING_NAME_API_ERROR_POLICY = 'powercaptcha_api_error_policy';
	const ERROR_POLICY_GRANT_ACCESS     = 'grant_access';
	const ERROR_POLICY_BLOCK_ACCESS     = 'block_access';
	const SETTING_NAME_LANGUAGE_MODE    = 'powercaptcha_language_mode';
	const LANGUAGE_MODE_WORDPRESS       = 'wordpress';
	const LANGUAGE_MODE_BROWSER         = 'browser';

	// integration settings
	const SETTING_SECTION_INTEGRATION = 'powercaptcha_setting_section_integration';

	const WPFORMS_INTEGRATION                 = 'wpforms';
	const WORDPRESS_LOGIN_INTEGRATION         = 'wordpress_login';
	const WORDPRESS_REGISTER_INTEGRATION      = 'wordpress_register';
	const WORDPRESS_LOST_PASSWORD_INTEGRATION = 'wordpress_lost_password';

	const WOOCOMMERCE_LOGIN_INTEGRATION    = 'woocommerce_login';
	const WOOCOMMERCE_REGISTER_INTEGRATION = 'woocommerce_register';
	const WOOCOMMERCE_CHECKOUT_INTEGRATION = 'woocommerce_checkout';

	const ELEMENTOR_FORM_INTEGRATION = 'elementor_form';

	// TODO Setting for exluding forms!

	// on premises settings
	const SETTING_SECTION_ON_PREMISES      = 'powercaptcha_setting_section_on_premises';
	const SETTING_NAME_ENDPOINT_BASE_URL   = 'powercaptcha_endpoint_base_url';
	const SETTING_NAME_JAVASCRIPT_BASE_URL = 'powercaptcha_javascript_base_url';

	const AJAX_ACTION_NAME_INTEGRATION_SETTING = 'powercaptcha_ajax_integration_setting';

	/**
	 * Singelton instance
	 */
	protected static $instance = null;

	/**
	 * @var Integration[] $integrations
	 */
	private array $integrations = array();

	private array $key_overwrites = array();

	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {

		// Load dependencies
		$this->load_dependencies();
		$this->load_integrations();

		// Register and init integrations
		add_action( 'plugins_loaded', array( $this, 'do_register_integrations' ) );
		add_action( 'plugins_loaded', array( $this, 'init_integrations' ) );

		// Load textdomain
		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

		// Init admin
		$admin_settings = new Admin_Settings();
		add_action( 'init', array( $admin_settings, 'init' ) );

		// Third party compatibility
		$third_party_compatibility = new Third_Party_Compatibility();
		$third_party_compatibility->init();

		// Register scripts
		add_action( 'wp_enqueue_scripts', array( $this, 'register_scripts' ) );
		// note: The 'wp_enqueue_scripts' hook is not executed on WordPress login, registration and lost-password pages.
		// Instead, we use the 'login_enqueue_scripts' hook, which is executed on all login and registration related screens.
		add_action( 'login_enqueue_scripts', array( $this, 'register_scripts' ) );

		// Ajax callback
		add_action( 'wp_ajax_' . self::AJAX_ACTION_NAME_INTEGRATION_SETTING, array( $this, 'integration_settings_ajax_callback' ) );
		add_action( 'wp_ajax_nopriv_' . self::AJAX_ACTION_NAME_INTEGRATION_SETTING, array( $this, 'integration_settings_ajax_callback' ) );
	}

	public function init_integrations(): void {
		if ( $this->is_configured() ) {
			foreach ( $this->integrations as $integration ) {
				/** @var Integration $integration */
				if ( $integration->is_enabled() ) {
					$integration->init();
				}
			}
		}
	}

	public function register_scripts() {
		wp_register_script(
			'powercaptcha-library',
			$this->get_javascript_url(),
			array(),
			POWER_CAPTCHA_PLUGIN_VERSION,
			true
		);

		wp_register_script(
			'powercaptcha-wp',
			POWER_CAPTCHA_URL . 'public/power-captcha-wp.js',
			array( 'powercaptcha-library', 'jquery' ),
			POWER_CAPTCHA_PLUGIN_VERSION,
			true
		);

		wp_add_inline_script(
			'powercaptcha-wp',
			'const powercaptcha_ajax_conf = ' .
			wp_json_encode(
				array(
					'ajaxurl'                    => admin_url( 'admin-ajax.php' ),
					'action_integration_setting' => self::AJAX_ACTION_NAME_INTEGRATION_SETTING,
					'wp_locale'                  => $this->is_use_wordpress_locale() ? get_locale() : 'browser',
					'is_debug'                   => ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ),
				)
			) . ';',
			'before'
		);
	}

	private function load_dependencies() {
		require_once plugin_dir_path( __DIR__ ) . 'admin/class-admin-settings.php';
		require_once plugin_dir_path( __DIR__ ) . 'includes/class-api-error.php';
		require_once plugin_dir_path( __DIR__ ) . 'includes/class-user-error.php';
		require_once plugin_dir_path( __DIR__ ) . 'includes/class-integration.php';
		require_once plugin_dir_path( __DIR__ ) . 'includes/class-verification-result.php';
		require_once plugin_dir_path( __DIR__ ) . 'includes/class-third-party-compatibility.php';
	}

	private function load_integrations() {
		require_once plugin_dir_path( __DIR__ ) . 'integrations/wordpress/class-wordpress-login-integration.php';
		require_once plugin_dir_path( __DIR__ ) . 'integrations/wordpress/class-wordpress-register-integration.php';
		require_once plugin_dir_path( __DIR__ ) . 'integrations/wordpress/class-wordpress-woocommerce-lost-password-integration.php';
		require_once plugin_dir_path( __DIR__ ) . 'integrations/woocommerce/class-woocommerce-checkout-integration.php';
		require_once plugin_dir_path( __DIR__ ) . 'integrations/woocommerce/class-woocommerce-login-integration.php';
		require_once plugin_dir_path( __DIR__ ) . 'integrations/woocommerce/class-woocommerce-register-integration.php';
		require_once plugin_dir_path( __DIR__ ) . 'integrations/wpforms/class-wpforms-integration.php';
		require_once plugin_dir_path( __DIR__ ) . 'integrations/elementor/class-elementor-form-integration.php';
		require_once plugin_dir_path( __DIR__ ) . 'integrations/contact-form-7/class-contact-form-7-integration.php';
	}

	public function register_integration( Integration $integration ) {
		$this->integrations[ $integration->get_id() ] = $integration;
	}

	public function do_register_integrations() {
		do_action( 'powercaptcha_register_integration', $this );
	}

	public function load_plugin_textdomain() {
		load_plugin_textdomain(
			'power-captcha',
			false,
			plugin_basename( POWER_CAPTCHA_PLUGIN_DIR ) . '/languages/'
		);

		foreach ( $this->integrations as $integration ) {
			/** @var Integration $integration */
			$integration->textdomain_loaded();
		}
	}

	public function integration_settings_ajax_callback() {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reason: Nonce verification intentionally omitted to avoid caching problems. This action does not change any data.
		$integration = isset( $_GET['integration'] ) ? sanitize_text_field( wp_unslash( $_GET['integration'] ) ) : null;
		wp_send_json(
			array(
				'apiKey'     => $this->get_api_key( $integration ),
				'backendUrl' => $this->get_token_request_url(),
				'clientUid'  => $this->get_client_uid(),
			)
		);
	}

	/**
	 * @return Integration[] $integrations
	 */
	public function get_integrations(): array {
		return $this->integrations;
	}

	public function overwrite_keys( $integration_id, $api_key, $secret_key ) {
		$this->key_overwrites[ $integration_id ] = array(
			'api_key'    => $api_key,
			'secret_key' => $secret_key,
		);
	}

	public function is_configured() {
		// only configured if api id and secret id are not empty
		return ! empty( $this->get_api_key() ) && ! empty( $this->get_secret_key() );
	}

	public function get_api_key( $integration_id = null ) {
		$api_key = self::get_setting_text( self::SETTING_NAME_API_KEY );
		if ( null !== $integration_id && isset( $this->key_overwrites[ $integration_id ]['api_key'] ) ) {
			$api_key = $this->key_overwrites[ $integration_id ]['api_key'];
		}
		return $api_key;
	}

	public function get_secret_key( $integration_id = null ) {
		$secret_key = self::get_setting_text( self::SETTING_NAME_SECRET_KEY );
		if ( null !== $integration_id && isset( $this->key_overwrites[ $integration_id ]['secret_key'] ) ) {
			$secret_key = $this->key_overwrites[ $integration_id ]['secret_key'];
		}
		return $secret_key;
	}

	public function get_language_mode(): string {
		return get_option( self::SETTING_NAME_LANGUAGE_MODE, self::LANGUAGE_MODE_WORDPRESS );
	}

	public function is_use_wordpress_locale(): bool {
		return self::LANGUAGE_MODE_WORDPRESS === $this->get_language_mode();
	}

	public function get_check_mode(): string {
		return get_option( self::SETTING_NAME_CHECK_MODE, 'auto' );
	}

	public function get_api_error_policy(): string {
		return get_option( self::SETTING_NAME_API_ERROR_POLICY, 'grant_access' );
	}

	private function get_endpoint_base_url() {
		$endpoint_base_url = self::get_setting_text( self::SETTING_NAME_ENDPOINT_BASE_URL );
		if ( empty( $endpoint_base_url ) ) {
			// using default
			$endpoint_base_url = self::DEFAULT_ENDPOINT_BASE_URL;
		}
		return untrailingslashit( $endpoint_base_url ); // return without trailing slash
	}

	public function get_token_request_url() {
		return $this->get_endpoint_base_url() . '/pc/' . self::API_VERSION;
	}

	public function get_token_verification_url() {
		return $this->get_endpoint_base_url() . '/pcu/' . self::API_VERSION . '/verify';
	}

	private function get_javascript_base_url() {
		$javascript_url = self::get_setting_text( self::SETTING_NAME_JAVASCRIPT_BASE_URL );
		if ( empty( $javascript_url ) ) {
			// using default
			$javascript_url = self::DEFAULT_JAVASCRIPT_URL;
		}
		return untrailingslashit( $javascript_url ); // return without trailing slash
	}

	public function get_javascript_url() {
		return $this->get_javascript_base_url() . '/' . self::API_VERSION . '/power-captcha-' . self::JS_VERSION . '.min.js';
	}

	public function get_client_uid() {
		$client_ip  = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		$client_uid = empty( $client_ip ) ? '' : hash( 'sha256', $client_ip );
		return $client_uid;
	}

	public function disable_integration_verification( $integration_id ) {
		if ( array_key_exists( $integration_id, $this->integrations ) ) {
			$this->integrations[ $integration_id ]->disable_verification();
		}
	}

	public function is_integration_enabled( string $id ) {
		if ( ! $this->is_configured() ) {
			return false;
		}

		if ( ! array_key_exists( $id, $this->integrations ) ) {
			return false;
		}

		return $this->integrations[ $id ]->is_enabled();
	}

	private static function get_setting_bool( $setting_name ) {
		return ( get_option( $setting_name ) === 1 );
	}

	private static function get_setting_text( $setting_name ) {
		return trim( get_option( $setting_name ) );
	}
}
