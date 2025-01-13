<?php

namespace Power_Captcha_WP;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly

add_action(
	'powercaptcha_register_integration',
	function ( $power_captcha ) {
		$power_captcha->register_integration( new Contact_Form_7_Integration() );
	}
);

class Contact_Form_7_Integration extends Integration {

	private string $wpcf7_tag_type = 'powercaptcha';

	public function __construct() {
		$this->id                  = 'contact_form_7';
		$this->setting_title       = __( 'Contact Form 7', 'power-captcha' );
		$this->setting_description =
			__( 'Enable protection for Contact Form 7.', 'power-captcha' )
			. '<br/>'
			. __( 'Note: After enabling, you need to add a \'POWER CAPTCHA\'-field to your desired Contact Form 7.', 'power-captcha' );
	}

	public function init() {
		add_action( 'wpcf7_init', array( $this, 'add_form_tag' ), 10 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_script' ) );

		// verification
		add_filter( "wpcf7_validate_{$this->wpcf7_tag_type}", array( $this, 'verification' ), 10, 2 );

		// add tag to contact form 7 editor
		add_action( 'wpcf7_admin_init', array( $this, 'add_tag_generator' ), 30, 0 );
	}

	public function add_form_tag() {
		wpcf7_add_form_tag(
			$this->wpcf7_tag_type, // field tag
			array( $this, 'display_widget' ), // callback
			array(
				'display-block'           => true,
				'name-attr'               => true,
				'do-not-store'            => true,
				'not-for-mail'            => true,
				'singular'                => true,
				'zero-controls-container' => true,
			)
		);
	}

	public function add_tag_generator() {
		$tag_generator = \WPCF7_TagGenerator::get_instance();
		$tag_generator->add(
			$this->wpcf7_tag_type,
			__( 'POWER CAPTCHA', 'power-captcha' ), // title
			array( $this, 'tag_generator_content' ), // callback function for form tag settings (f.e. username field)
			array( 'version' => '2' )
		);
	}

	public function tag_generator_content( $contact_form, $options ) {

		$tgg = new \WPCF7_TagGeneratorGenerator( $options['content'] );

		$field_info = array(
			'display_name' => __( 'POWER CAPTCHA', 'power-captcha' ),
			'heading'      => __( 'POWER CAPTCHA form-tag generator', 'power-captcha' ),
			'description'  => __( 'Generates a form-tag to protect the form with POWER CAPTCHA.', 'power-captcha' ),
		)
		?>
<header class="description-box">
	<h3><?php echo esc_html( $field_info['heading'] ); ?></h3>
	<p><?php echo esc_html( $field_info['description'] ); ?></p>
</header>

<div class="control-box">
		<?php
		$tgg->print(
			'field_type',
			array(
				'select_options' => array(
					'powercaptcha' => $field_info['display_name'], // field name
				),
			)
		);

		$tgg->print( 'field_name' );
		?>

	<fieldset>
		<legend id="<?php echo esc_attr( $tgg->ref( 'userfield-option-legend' ) ); ?>">
			<?php
			echo esc_html__( 'Additional field protection (optional, Enterprise only)', 'power-captcha' );
			?>
		</legend>
		<label>
		<?php
		printf(
			'<span %1$s>%2$s<br/>%3$s</span><br />',
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Reason: wpcf7_format_atts will escape the output
			wpcf7_format_atts(
				array(
					'id' => $tgg->ref( 'userfield-option-description' ),
				)
			),
			esc_html__( 'Provide the name of the field which should additionally be protected by POWER CAPTCHA (e.g. user name or email address).', 'power-captcha' ),
			wp_kses(
				__( 'The field-name can be found in the second argument of the form-tag (see <a href="https://contactform7.com/tag-syntax/#form_tag" target="_blank">Form-tag syntax</a>).', 'power-captcha' ),
				array(
					'a' => array(
						'href'   => true,
						'target' => true,
					),
				),
				array( 'http', 'https' )
			)
		);

		printf(
			'<input %s />',
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Reason: wpcf7_format_atts will escape the output
			wpcf7_format_atts(
				array(
					'type'             => 'text',
					'pattern'          => '.*',
					'value'            => '',
					'aria-labelledby'  => $tgg->ref( 'userfield-option-legend' ),
					'aria-describedby' => $tgg->ref( 'userfield-option-description' ),
					'data-tag-part'    => 'option',
					'data-tag-option'  => 'userfield:',
				)
			)
		);
		?>
		</label>
	</fieldset>
</div>

<footer class="insert-box">
		<?php
		$tgg->print( 'insert_box_content' );
		?>
</footer>
		<?php
	}

	public function disable_verification() {
		remove_filter( "wpcf7_validate_{$this->wpcf7_tag_type}", array( $this, 'verification' ), 10 );
	}

	public function display_widget( \WPCF7_FormTag $tag ) {
		$userfield = $tag->get_option( 'userfield', '', true );
		if ( ! empty( $userfield ) ) {
			$user_input_field = '[name=\'' . $userfield . '\']';
		} else {
			$user_input_field = '';
		}

		$validation_error = wpcf7_get_validation_error( $tag->name );

		$html = sprintf(
			'<span class="wpcf7-form-control-wrap" data-name="%1$s" aria-required="true">%2$s %3$s</span>',
			esc_attr( $tag->name ),
			parent::widget_html( $user_input_field ),
			$validation_error
		);
		return $html;
	}

	public function enqueue_script() {
		wp_register_script(
			'powercaptcha-wpcf7',
			plugin_dir_url( __FILE__ ) . 'public/power-captcha-wpcf7.js',
			array( 'powercaptcha-wp' ),
			POWER_CAPTCHA_PLUGIN_VERSION,
			true
		);

		wp_enqueue_script( 'powercaptcha-wpcf7' );
	}

	public function verification( \WPCF7_Validation $result, \WPCF7_FormTag $tag ) {
		$username_field = $tag->get_option( 'userfield', '', true );
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Reason: Nonce generation and verification are handled by Contact Form 7.
		$username     = ( ! empty( $username_field ) && isset( $_POST[ $username_field ] ) ) ? sanitize_text_field( wp_unslash( $_POST[ $username_field ] ) ) : null;
		$verification = $this->verify_token( $username );
		if ( false === $verification->is_success() ) {
			$result->invalidate( $tag, $verification->get_user_message( false ) );
		}

		return $result;
	}
}