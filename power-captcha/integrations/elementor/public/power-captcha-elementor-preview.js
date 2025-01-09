jQuery( document ).ready(
	function () {
		let previewReloadDelay = undefined;
		elementor.hooks.addFilter(
			'elementor_pro/forms/content_template/field/power-captcha',
			function ( inputField, item, i ) {

				// clear delay
				if (previewReloadDelay) {
						clearTimeout( previewReloadDelay );
						previewReloadDelay = undefined;
				}

				// delay PowerCaptchaWp.setup() method, so the div is already rendered
				previewReloadDelay = setTimeout(
					function () {
						if (window.PowerCaptchaWp) {
							window.PowerCaptchaWp.destroyAll();
							window.PowerCaptchaWp.setup();
						} else {
							console.warn( 'PowerCaptchaWp not found' );
						}
					},
					1000
				);

				// return widget_html preview
				return '<div data-pc-wp-integration="elementor_form" data-pc-wp-check-mode="manu"></div>';
			},
			10,
			3
		);
	}
);