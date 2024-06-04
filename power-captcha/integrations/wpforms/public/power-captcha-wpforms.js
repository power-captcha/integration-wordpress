document.addEventListener('PowerCaptchaReady', (e) => {
    const pc = e.detail.captcha;
    const wpform = pc.formElement; 
    if(!wpform.classList.contains('wpforms-form')) {
        return; // not a wpform
    }
    
    const wpformId = wpform.id;

    let usernameField = undefined;
    // check if there is a username field and find it
    const fieldContainer = wpform.querySelector('[class*="pc-user-"]');
    if(fieldContainer === null) {
        console.debug('no container found with pc-user-* class in wpform #'+wpformId);
        usernameField = undefined;
    } else {
        const fieldPosition = fieldContainer.className.match(/pc-user-([0-9]+)/)[1];
        usernameField = fieldContainer.querySelectorAll('input')[fieldPosition];
        if(!usernameField) {
            console.warn(`username field not found with index ${fieldPosition} in container #${fieldContainer.id} of wpform #${wpformId}`);
            usernameField = undefined;
        } else {
            pc.setUserInputField(usernameField);
        }
    }

    wpform.addEventListener('wpformsBeforeFormSubmit', (event) => {
        // check captcha validity before submit
        return pc.checkValidity();
    });

    wpform.addEventListener('wpformsAjaxSubmitFailed wpformsAjaxSubmitActionRequired wpformsAjaxSubmitError', (event) => {
        // clear captcha after ajax submit failed
        pc.reset();
    });
    
});