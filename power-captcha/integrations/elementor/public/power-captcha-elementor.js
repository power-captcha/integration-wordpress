document.addEventListener(
	'PowerCaptchaReady',
	function (e) {

		const pc            = e.detail.captcha;
		const elementorForm = pc.formElement;

		if ( ! elementorForm.classList.contains( 'elementor-form' )) {
			return; // not a elementor form
		}

		jQuery( document ).ready(
			function () {

				// we add powercaptcha_elementor_verificiation_success field to the response,
				// to check the verification result and reset the captcha for a next submit.
				jQuery( document ).ajaxSuccess(
					function ( event, request, settings, response ) {
						if ( 'data' in response &&
						'data' in response.data &&
						'powercaptcha_elementor_verificiation_success' in response.data.data) {
							const verification_success = response.data.data.powercaptcha_elementor_verificiation_success;
							pc.reset(); // always reset captcha if a verification happend, regardless of the result.
							if (false === verification_success) {
								pc.showInvalid();
							}
						}
					}
				);

				// elementor will also trigger events after the form is submitted via ajax.
				// we need to reset the captcha after these events, so the captcha is ready for the next submit.
				// see plugins\elementor-pro\assets\js\preloaded-elements-handlers.js:
				// $form.trigger('error');
				// $form.trigger('submit_success', response.data);

				jQuery( elementorForm ).on(
					'error',
					function (event) {
						pc.reset();
					}
				);

				jQuery( elementorForm ).on(
					'submit_success',
					function (event, response) {
						pc.reset();
					}
				);
			}
		);
	}
);