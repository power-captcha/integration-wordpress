(function($) {

    // prefetch details
    powerCaptchaWp.prefetchFrontendDetails('elementor');

    function init(elementorFormWidget) {
        console.log('elementorFormWidget', elementorFormWidget);
        const elementorForm = elementorFormWidget.find('form.elementor-form');
        
        if(elementorForm.length < 0) {
            return; // abbort if widget does not contain elementor form
        }

        const userNameFieldId = elementorForm.find('input[type=power-captcha]').data('pc-username-id');
        let usernameField = undefined;
        if(typeof userNameFieldId !== 'undefined' && userNameFieldId != "") {
            usernameField = elementorForm.find('#form-field-'+userNameFieldId);
            console.log('username field: ',usernameField);
        }
        powerCaptchaWp.withFrontendDetails('elementor', function(details) {
            const captcha = window.PowerCaptcha.init({
                apiKey: details.apiKey,
                backendUrl: details.backendUrl, 
                widgetTarget: elementorFormWidget.find('.pc-widget-target')[0],//document.querySelector('.my-widget'), // widgetElement (div)
                
                userInputField: usernameField[0], //document.querySelector('#fname'),
      
                // unique client id (e.g. hashed client ip address)
                clientUid: details.clientUid,
                lang: powerCaptchaWp.getLang(),

                invisibleMode: false, // TODO make invisibleMode configurable 
                debug: true // TODO turn off debug or make debug configurable 
            });


        });
        
    }


    $(window).on('elementor/frontend/init', function() {
        window.elementorFrontend.hooks.addAction( 'frontend/element_ready/widget', 
            function( $scope ) {
                if($scope.is('.elementor-widget-form:has(input[type=power-captcha])')) {
                    // elementor form containing our field selector is ready
                    init($scope);
                }
            } 
        );
    });
    
}(jQuery));