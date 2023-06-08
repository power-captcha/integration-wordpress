(function($) {

    function isSubmitAllowed(elementorForm, tokenField, captchaInstance) {
        if(tokenField.val() === "") {
            console.debug('pc-token field empty. preventing form submit and requesting token.');

            let userName = "";
            const userNameFieldId = elementorForm.find('input[type=power-captcha]').data('pc-username-id');
            if(userNameFieldId != "") {
                const userNameField = elementorForm.find('#form-field-'+userNameFieldId);
                if(userNameField) {
                    userName = userNameField.val();
                    console.debug('userName', userName);
                }
            }

            // requesting token
            captchaInstance.check({
                apiKey: POWER_CAPTCHA_API_KEY,
                backendUrl: POWER_CAPTCHA_ENDPOINT_URL, 
                user: userName,
                callback: ''
            }, 
            function(token) {
                console.debug('captcha solved with token: '+token+'. setting value to tokenField.');
                tokenField.val(token);
                console.debug('resubmitting elementorForm form.');

                elementorForm.trigger('submit');
            });
            return false; // stop form submit
        } else {
            console.debug('pc-token already set. no token has to be requested. elementorForm can be submitted.');
            return true; // proceed from submit
        }
    }

    function init(elementorFormWidget) {
        // Ensure our init call fires after Elementor by setting timeout
        setTimeout( () => {

            const elementorForm = elementorFormWidget.find('form.elementor-form');

            // generate id
            const elementorFormId = 'elementor-' + Math.random().toString(16).slice(2);

            // append hidden input for token
            const tokenField = $('<input type="hidden" name="pc-token" value =""/>');
            elementorForm.append(tokenField);

            // create instance for the elementor form
            const captchaInstance = window.uiiCaptcha.captcha({idSuffix: elementorFormId});

            // Get JQuery bound events
            var events = $._data( elementorForm[0], 'events' );
            if( !events || !events.submit ) {
                console.error('init POWER CAPTCHA for Elementor form failed. maybe elementor js was not loaded completely? form: ', elementorForm[0]);
                return;
            }
            // Save Submit Events to be called later then Disable Them
            var submitEvents = $.map( events.submit, event => event.handler );
            $( submitEvents ).each( event => elementorForm.off( 'submit', null, event ) );  

            // Now Setup our Event Relay
            elementorForm.on( 'submit', function( e )  {
                e.preventDefault();
                var self = this;

                if( !isSubmitAllowed(elementorForm, tokenField, captchaInstance) ) {
                    return;
                }

                // Trigger Event
                $( submitEvents ).each( ( i, event ) => {
                    var doEvent = event.bind( self );
                    doEvent( ...arguments );
                } );

            } );

            // register listeners to clear token field
            elementorForm.on('error reset', function () {
                console.debug('token field cleared.');
                tokenField.val('');
            });

        }, 500); // little delay to ensure that elementor form.js is loaded
    }

    $(window).on('elementor/frontend/init', function() {
        window.elementorFrontend.hooks.addAction( 'frontend/element_ready/widget', 
            function( $scope ) {
                if($scope.is('.elementor-widget-form')) {
                    if($scope.has('input[type=power-captcha]')) {
                        // elementor form containing our field selector is ready
                        init($scope);
                    }
                }
            } 
        );
    });
}(jQuery));