document.addEventListener('PowerCaptchaReady', (e) => {
    const pc = e.detail.captcha;
    const elementorForm = pc.formElement; 
    if(!elementorForm.classList.contains('elementor-form')) {
        return; // not a elementor form
    }

    jQuery(document).ready(function(){
        // elementor will trigger events after the form is submitted via ajax.
        // we need to reset the captcha after these events, so the use can request a new token for the next submit.
        // see plugins\elementor-pro\assets\js\preloaded-elements-handlers.js:
        // $form.trigger('error');
        // $form.trigger('submit_success', response.data);
		
        jQuery(elementorForm).on('error', (event) => {
			pc.reset();
		});
        jQuery(elementorForm).on('submit_success', (event, response) => {
			pc.reset();
		});
    });
});