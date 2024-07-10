document.addEventListener('PowerCaptchaReady', (e) => {
    const pc = e.detail.captcha;
    const woocommerceCheckoutForm = pc.formElement; 
    if(!woocommerceCheckoutForm.classList.contains('woocommerce-checkout')) {
        return; // not a woocommerce-checkout form
    }

    jQuery(document).ready(function(){
        // woocommerce will trigger events when the checkout is submitted via ajax.
        // we need to reset the captcha after these events, so the use can request a new token for the next submit.
        // see plugins\woocommerce\assets\js\frontend\checkout.js
        // $( document.body ).trigger( 'checkout_error' , [ error_message ] );
        // $form.triggerHandler( 'checkout_place_order_success', [ result, wc_checkout_form ] )
		
        jQuery(document.body).on('checkout_error', (event, message) => {
			pc.reset();
		});
        jQuery(woocommerceCheckoutForm).on('checkout_place_order_success', (event, result, wc_checkout_form) => {
			pc.reset();
		});
    });
});