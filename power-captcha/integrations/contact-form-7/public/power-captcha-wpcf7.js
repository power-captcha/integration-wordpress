document.addEventListener(
	'PowerCaptchaReady',
	function (e) {
		const pc        = e.detail.captcha;
		const wpcf7form = pc.formElement;

		if ( ! wpcf7form.classList.contains( 'wpcf7-form' )) {
			return; // not a contact form 7 form.
		}

		if (pc.userInputField && pc.userInputField.getAttribute( 'aria-required' ) === 'true') {
			pc.userInputField.required = true;
		}

		// widget.mainElement acts as wpcf7-form-control element
		const widgetElement = pc.widget.mainElement;
		widgetElement.classList.add( 'wpcf7-form-control' );

		// delegate validation to widgetElement
		widgetElement.setCustomValidity = function (message) {
			if (message) {
				pc.showInvalid();
				pc.widget.setVisible( true );
			}
		};

		// the wpcf7-form-control-wrap element
		const wrapperElement = pc.widgetContainer.parentElement;

		// remove invalidity class and message
		pc.addEventListener(
			'statechange',
			function (event) {
				if (event.detail === 'success') {
					widgetElement.classList.remove( 'wpcf7-not-valid' );
					// error text is placed inside the wrapperElement
					const errorText = wrapperElement.querySelector( 'span.wpcf7-not-valid-tip' );
					if (errorText) {
						errorText.remove();
					}
				}
			}
		);

		// reset on various wpcf7 events
		document.addEventListener(
			'wpcf7reset',
			function (event) {
				pc.reset();
			}
		);
		document.addEventListener(
			'wpcf7invalid',
			function (event) {
				pc.reset();
			}
		);
		document.addEventListener(
			'wpcf7spam',
			function (event) {
				pc.reset();
			}
		);
		document.addEventListener(
			'wpcf7mailsent',
			function (event) {
				pc.reset();
			}
		);
		document.addEventListener(
			'wpcf7mailfailed',
			function (event) {
				pc.reset();
			}
		);
		document.addEventListener(
			'wpcf7submit',
			function (event) {
				pc.reset();
			}
		);
	}
);
