<?php

namespace Power_Captcha_WP;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly

abstract class Integration {
	const SETTING_ENABLED_NAME_PREFIX = 'powercaptcha_integration_enabled_';

	protected string $id;
	protected string $setting_title;
	protected string $setting_description;

	public function __construct() {}

	abstract public function init();

	abstract public function disable_verification();

	public function get_id(): string {
		return $this->id;
	}

	public function get_setting_name(): string {
		return self::SETTING_ENABLED_NAME_PREFIX . $this->get_id();
	}

	public function get_setting_title(): string {
		return $this->setting_title;
	}

	public function get_setting_description(): string {
		return $this->setting_description;
	}

	public function is_enabled(): bool {
		return boolval( get_option( $this->get_setting_name() ) );
	}

	public function enqueue_scripts() {
		wp_enqueue_script( 'powercaptcha-wp' );
	}

	public function echo_widget_html( $user_input_field = '', $user_input_field_required = false, $css_class = '', $style = '' ) {
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Reason: Output is escaped by widget_html.
		echo $this->widget_html( $user_input_field, $user_input_field_required, $css_class, $style );
	}

	public function widget_html( $user_input_field = '', $user_input_field_required = false, $css_class = '', $style = '' ) {
		$result  = '<div ';
		$result .= ' data-pc-wp-check-mode="' . esc_attr( powercaptcha()->get_check_mode() ) . '"';
		$result .= ' data-pc-wp-integration="' . esc_attr( $this->id ) . '"';
		if ( ! empty( $user_input_field ) ) {
			$result .= ' data-pc-wp-user-field="' . esc_attr( $user_input_field ) . '"';
			if ( $user_input_field_required ) {
				$result .= ' data-pc-wp-user-field-required="1"';
			}
		}
		$result .= ' class="' . esc_attr( $css_class ) . '"';
		$result .= ' style="' . esc_attr( $style ) . '"';
		$result .= '></div>';
		return $result;
	}

	public function get_username_hash( ?string $field_name ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Reason: Nonce generation and verification are handled by the respective form plugin.
		if ( ( empty( $field_name ) ) || ( false === isset( $_POST[ $field_name ] ) ) ) {
			return null;
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification.Missing -- Reason: The raw input is hashed using sha256 and sanitized after. Nonce generation and verification are handle by the respective form plugin.
		return $this->hash_username( $_POST[ $field_name ] );
	}

	public function hash_username( ?string $username ) {
		if ( empty( $username ) ) {
			return null;
		}

		return sanitize_text_field( hash( 'sha256', $username ) );
	}

	public function verify_token( string $username_hash = null, string $token = null, string $client_uid = null ): Verification_Result {
		try {
			if ( is_null( $token ) ) {
				// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Reason: The token input is only used to verify the request via the POWER CAPTCHA API. Nonce generation and verification are handled by the respective form plugin.
				if ( false === isset( $_POST['pc-token'] ) ) {
					throw new User_Error( 'The user request does not contain a token field.' );
				}
				// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Reason: The token input is only used to verify the request via the POWER CAPTCHA API. Nonce generation and verification are handled by the respective form plugin.
				$token = sanitize_text_field( wp_unslash( $_POST['pc-token'] ) );
			}

			if ( empty( $token ) ) {
				throw new User_Error( 'The user request contains an empty token.' );
			}

			$request_url  = powercaptcha()->get_token_verification_url();
			$request_body = array(
				'secret'    => powercaptcha()->get_secret_key( $this->get_id() ),
				'token'     => $token,
				'clientUid' => $client_uid ?? powercaptcha()->get_client_uid(),
				'name'      => $username_hash ?? '',
			);

			$this->debug_log(
				'Token verification API request.',
				array(
					'API URL'      => $request_url,
					'Request Body' => $request_body,
				)
			);

			$request_body = wp_json_encode( $request_body );

			$response = wp_remote_request(
				$request_url,
				array(
					'method'  => 'POST',
					'headers' => array(
						'Content-Type' => 'application/json',
						'X-API-Key'    => powercaptcha()->get_api_key( $this->get_id() ),
					),
					'body'    => $request_body,
				)
			);

			if ( is_wp_error( $response ) ) {
				throw new Api_Error( 'Could not connnect to POWER CAPTCHA API. WordPress error message: ' . $response->get_error_message() ); // connection error
			}

			// parse the response
			$response_code = wp_remote_retrieve_response_code( $response );
			$response_body = json_decode( wp_remote_retrieve_body( $response ), true );

			$this->debug_log(
				'Token verifcation API result.',
				array(
					'Response code' => $response_code,
					'Response Body' => $response_body,
				)
			);

			if ( 200 === $response_code ) {
				if ( isset( $response_body['success'] ) && boolval( $response_body['success'] ) ) {
					$this->debug_log( 'Token verification: Token successfully verified.', array( 'Token' => $token ) );
					return new Verification_Result( true, null );
				}

				throw new User_Error( 'Token was not solved by user or mismatch of clientUid or username.' );
			}

			if ( 400 === $response_code && is_array( $response_body['errors'] ) ) {
				$errors = $response_body['errors'];
				if ( ( in_array( 'MISSING_SECRET', $errors, true ) || in_array( 'INVALID_SECRET', $errors, true ) ) ) {
					throw new Api_Error( 'Secret Key is invalid or missing in request. Please check your Secret Key!' );
				}

				throw new User_Error( 'User sent invalid or expired token.' );
			}

			throw new Api_Error( 'Unknown response from POWER CAPTCHA API. Response code:' . $response_code . ' / Response body: ' . $response_body );

		} catch ( User_Error $error ) {
			$this->debug_log( 'Token verification failed due to user input. Access is blocked. Reason: ' . $error->getMessage() );
			return new Verification_Result( false, powercaptcha()::ERROR_CODE_USER_ERROR );
		} catch ( Api_Error $error ) {
			if ( powercaptcha()::ERROR_POLICY_BLOCK_ACCESS === powercaptcha()->get_api_error_policy() ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log(
					'[ERROR] POWER CAPTCHA (Integration ' . $this->get_id() . '): Token verification failed due to an API error. Access is blocked based on API Error Policy. Error: ' . $error->getMessage()
				);
				return new Verification_Result( false, powercaptcha()::ERROR_CODE_API_ERROR );
			} else {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log(
					'[ERROR] POWER CAPTCHA (Integration ' . $this->get_id() . '): Token verification failed due to an API error. Access nevertheless is granted based on API Error Policy. Error: ' . $error->getMessage()
				);
				return new Verification_Result( true, powercaptcha()::ERROR_CODE_API_ERROR );
			}
		}
	}

	protected function debug_log( string $message, array|object $data = null ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			if ( isset( $data ) ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_print_r
				error_log( '[DEBUG] POWER CAPTCHA (Integration ' . $this->get_id() . ') ' . $message . PHP_EOL . 'Details: ' . print_r( $data, true ) );
			} else {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( '[DEBUG] POWER CAPTCHA (Integration ' . $this->get_id() . ') ' . $message );
			}
		}
	}
}
