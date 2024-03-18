
// prefetch details
powerCaptchaWp.prefetchFrontendDetails('wpforms');

jQuery( document ).on('wpformsReady', () => {
    const $ = jQuery;

    $('form.wpforms-form').each(function () {
        const wpform = $(this);
        const wpformId = wpform.attr('id');

        let usernameField = undefined;
        // find check if there is a username field and find it
        const fieldContainer = wpform.find('[class*="pc-user-"]').eq(0);
        if(fieldContainer.length === 0) {
            console.debug('no container found with pc-user-* class in wpform #'+wpformId);
            usernameField = undefined;
        } else {
            const fieldPosition = fieldContainer.attr('class').match(/pc-user-([0-9]+)/)[1];
            usernameField = fieldContainer.find('input').eq(fieldPosition);
            if(usernameField.length === 0) {
                console.warn(`username field not found with index ${fieldPosition} in container #${fieldContainer.attr('id')} of wpform #${wpformId}`);
                usernameField = undefined;
            } else {
                usernameField = usernameField[0];
            }
        }

        powerCaptchaWp.withFrontendDetails('wpforms', (details) => {
            // create instance for the wpfrom
            const pc = PowerCaptcha.init({
                idSuffix: wpformId, 
                apiKey: details.apiKey,
                backendUrl: details.backendUrl, 
                widgetTarget: wpform.find('.pc-widget-target')[0],
                userInputField: usernameField,
                
                // unique client id (e.g. hashed client ip address)
                clientUid: details.clientUid,
                lang: powerCaptchaWp.getLang(),

                invisibleMode: false, // TODO make invisibleMode configurable 
                debug: true // TODO turn off debug or make debug configurable 
            });

            wpform.on('wpformsBeforeFormSubmit', (event) => {
                // check captcha validity before submit
                return pc.checkValidity();
            });

            wpform.on('wpformsAjaxSubmitFailed wpformsAjaxSubmitActionRequired wpformsAjaxSubmitError', (event) => {
                // clear captcha after ajax submit failed
                pc.reset();
            });
        });
    });
});