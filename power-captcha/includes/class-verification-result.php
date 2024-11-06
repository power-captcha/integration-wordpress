<?php

namespace Power_Captcha_WP;

class Verification_Result {

	private bool $success;
	private string|null $error_code;

	public function __construct( bool $success, string|null $error_code ) {
		$this->success    = $success;
		$this->error_code = $error_code;
	}

	public function is_success(): bool {
		return $this->success;
	}

	public function get_error_code(): string|null {
		return $this->error_code;
	}

	public function get_user_message( $error_prefix = true ): string {
		if ( is_null( $this->error_code ) ) {
			return '';
		}

		$output = '';
		if ( $error_prefix ) {
			$output .= '<strong>' . __( 'Error:', 'power-captcha' ) . '</strong> ';
		}

		if ( powercaptcha()::ERROR_CODE_USER_ERROR === $this->error_code ) {
			$output .= __( 'The POWER CAPTCHA security check was not confirmed.', 'power-captcha' );
		} elseif ( powercaptcha()::ERROR_CODE_API_ERROR === $this->error_code ) {
			$output .= __( 'An internal error occurred during the POWER CAPTCHA security check. Please try again later.', 'power-captcha' );
		} else {
			$output .= __( 'An unkown error occurred during the POWER CAPTCHA security check. Please try again later.', 'power-captcha' );
		}

		return $output;
	}
}
