const powerCaptchaWp = (function($, settings) {
    // private access
    const frontendDetailPromises = {};

    function fetchFrontendDetails(integration) {
        if(frontendDetailPromises[integration] === undefined) {
            frontendDetailPromises[integration] = $.ajax({
                url: settings.ajaxurl,
                method: 'GET',
                data: { action: settings.actionFrontendDetails, integration: integration },
                dataType: 'json'
            });
        }
        
        return frontendDetailPromises[integration];
    }
    
    return {
        // public access
        prefetchFrontendDetails: function(integration) {
            fetchFrontendDetails(integration);
        },

        withFrontendDetails: function(integration, callbackFn) {
            fetchFrontendDetails(integration).done(function(details) {
                callbackFn(details);
            });
        }
    };

})(jQuery, powercaptcha_settings);



