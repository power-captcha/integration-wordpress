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
                apiKey: ELEMENTOR_POWER_CAPTCHA_API_KEY,
                backendUrl: ELEMENTOR_POWER_CAPTCHA_ENDPOINT_URL, 
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
        const elementorForm = elementorFormWidget.find('form.elementor-form');

        // Wait for JQuery bound events
        awaitJQueryBoundEvents(elementorForm, 500, 20).then((events) => {

            // generate id
            const elementorFormId = 'elementor-' + Math.random().toString(16).slice(2);

            // append hidden input for token
            const tokenField = $('<input type="hidden" name="pc-token" value =""/>');
            elementorForm.append(tokenField);

            // create instance for the elementor form
            const captchaInstance = window.uiiCaptcha.captcha({idSuffix: elementorFormId});

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
        }).catch(() => {
            console.error('init POWER CAPTCHA for Elementor form failed. form: ', elementorForm);
        });
    }

    function awaitJQueryBoundEvents($element, waitTimeMs, retries) {
        return new Promise((resolve, reject) => {
            let leftRetries = retries;

            // wait till we found bound submit events
            const intervalId = setInterval(() => {
                var events = $._data( $element[0], 'events' );
                if( events && events.submit ) {
                    // found bound submit events -> finish
                    clearInterval(intervalId);
                    resolve(events);
                } else {
                    // not found submit events -> not finished
                    if(--leftRetries < 1) {
                        // no retries left -> abbort
                        clearInterval(intervalId);
                        reject();
                    }
                }
            }, waitTimeMs);
        });
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