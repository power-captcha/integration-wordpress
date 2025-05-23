window.PowerCaptchaWp = (function (conf) {
	// private access
	let autoCaptchas                 = [];
	const wp_locale                  = conf.wp_locale === 'browser' ? undefined : conf.wp_locale;
	const wp_script_debug            = conf.wp_script_debug || false;
	const integrationSettingPromises = {};

	function fetchSettings(integration) {
		if (integrationSettingPromises[integration] === undefined) {
			integrationSettingPromises[integration] =
				fetch(
					conf.ajaxurl + '?action=' + conf.action_integration_setting + '&integration=' + integration,
					{
						method: 'GET',
						headers: {
							'Content-Type': 'application/json'
						}
					}
				)
				.then(
					function (response) {
						if ( ! response.ok) {
							throw new Error( '[POWER CAPTCHA WordPress] Could not fetch the integration settings.' );
						}
						return response.json();
					}
				);
		}
		return integrationSettingPromises[integration];
	}

	function internalInit(captchaSettings) {
		if ( ! window.PowerCaptcha) {
			throw new Error( '[POWER CAPTCHA WordPress] POWER CAPTCHA library was not loaded.' );
		}
		if (wp_script_debug) {
			console.debug( '[POWER CAPTCHA WordPress] Init ', captchaSettings );
		}
		const integrationSettings = fetchSettings( captchaSettings.integration );
		return window.PowerCaptcha.init(
			{
				apiKey: async function () {
					return (await integrationSettings).apiKey;
				},
				backendUrl: async function () {
					return (await integrationSettings).backendUrl;
				},
				clientUid: async function () {
					return (await integrationSettings).clientUid;
				},
				lang: wp_locale,
				debug: wp_script_debug,

				idSuffix: captchaSettings.idSuffix || undefined,
				formElement: captchaSettings.formElement || undefined,
				widgetContainer: captchaSettings.widgetContainer,
				userInputField: captchaSettings.userInputField || undefined,
				hashUserInput: true,
				checkMode: captchaSettings.checkMode || undefined,
			}
		);
	}

	function internalDestroyAll() {
		autoCaptchas.forEach(
			function (captcha) {
				console.log( 'cleared captcha: ', captcha );
				captcha.destroy();
			}
		);
		autoCaptchas = [];
		if (wp_script_debug) {
			console.debug( 'collected captcha: ', captcha );
		}
	}

	function internalSetup() {
		document.querySelectorAll( "form div[data-pc-wp-integration]" ).forEach(
			function (widgetContainer) {

				const integration = widgetContainer.dataset['pcWpIntegration'];
				const checkMode   = widgetContainer.dataset['pcWpCheckMode'];
				const formElement = widgetContainer.closest( 'form' );
				if ( ! formElement) {
					console.error( '[POWER CAPTCHA WordPress] Widget container is not within a form element.', '/ Integration: ', integration, ' / Container: ', widgetContainer ); // TODO better exception
					return;
				}

				let userInputField     = undefined;
				const userFieldSelecor = widgetContainer.dataset['pcWpUserField'] || false;
				if (userFieldSelecor) {
					// search for the user input field
					userInputField = formElement.querySelector( userFieldSelecor );
					if ( ! userInputField || ! userInputField.nodeName
					|| (userInputField.nodeName.toLowerCase() != 'input' && userInputField.nodeName.toLowerCase() != 'textarea')
					) {
							console.warn( '[POWER CAPTCHA WordPress] No input or textarea can be found by selector.', '/ Integration:', integration, '/ Selector: ', userFieldSelecor, '/ Found: ', userInputField );
							userInputField = undefined;
					} else {
						const userInputFieldRequired = (widgetContainer.dataset['pcWpUserFieldRequired'] == '1') || false;
						userInputField.required      = userInputFieldRequired;
					}
				}

				const captcha = internalInit(
					{
						integration: integration,
						// idSuffix: captchaSettings.idSuffix || undefined,
						widgetContainer: widgetContainer,
						userInputField: userInputField,
						checkMode: checkMode
					}
				);
				autoCaptchas.push( captcha );
				if (wp_script_debug) {
					console.debug( 'collected captcha: ', captcha );
				}
			}
		);
	}

	// startup
	if (document.readyState !== "loading") {
		internalSetup();
	} else {
		document.addEventListener( "DOMContentLoaded", internalSetup );
	}

	return {
		init: function (captchaSettings) {
			if ( ! window.PowerCaptcha) {
				throw new Error( 'POWER CAPTCHA library was not loaded.' );
			}
			return internalInit( captchaSettings );
		},

		destroyAll: function () {
			internalDestroyAll();
		},

		setup: function () {
			if ( ! window.PowerCaptcha) {
				throw new Error( 'POWER CAPTCHA library was not loaded.' );
			}
			internalSetup();
		}
	};
})( powercaptcha_ajax_conf );
