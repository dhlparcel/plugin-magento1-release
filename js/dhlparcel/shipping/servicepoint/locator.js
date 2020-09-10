jQuery(document).ready(function($) {

    var dhlparcel_parcelshop_selection_modal_loading_busy = false;
    var dhlparcel_parcelshop_selection_modal_loaded = false;
    var dhlparcel_parcelshop_selection_modal_connected = false;

    var dhlparcelTranslations = function (i) {
        var translations = {
            'distance': 'Distance',
            'search': 'Search',
            'monday': 'Monday',
            'tuesday': 'Tuesday',
            'wednesday': 'Wednesday',
            'thursday': 'Thursday',
            'friday': 'Friday',
            'saturday': 'Saturday',
            'sunday': 'Sunday',
            'parcelshop_search': 'Zoeken'
        };
        return translations[i.toLowerCase()];
    };

    $(document.body).on('click', 'span.dhlparcel-modal-close', function (e) {
        e.preventDefault();
        $('div.dhlparcel-modal').hide();

    }).on('click', 'div.dhlparcel-modal', function (e) {
        if (!$('div.dhlparcel-modal').find(e.target).length) {
            // When clicking anywhere outside of the modal, hide everything about modals
            $(this).hide();
            // Send a modal closed event
            $(document.body).trigger("dhlparcel:servicepoint_modal_closed");
        }

    }).on('dhlparcel:load_servicepoint_selection_modal', function() {
        if (dhlparcel_parcelshop_selection_modal_loaded === true) {
            return;
        }

        if (dhlparcel_parcelshop_selection_modal_loading_busy === true) {
            return;
        }

        dhlparcel_parcelshop_selection_modal_loading_busy = true;

        // Create selection function
        window.dhlparcel_shipping_select_servicepoint = function(event)
        {
            event.additional_servicepoint_id = null;
            if (typeof event.shopType !== 'undefined' && event.shopType === 'packStation' && event.address.countryCode === 'DE') {
                var dhlparcel_additional_servicepoint_id = prompt("Add your 'postnumber' for delivery at a DHL Packstation:");

                if (dhlparcel_additional_servicepoint_id != null && dhlparcel_additional_servicepoint_id != '') {
                    $(document.body).trigger("dhlparcel:add_servicepoint_component_confirm_button");

                    event.name = event.keyword + ' ' + event.id;
                    event.additional_servicepoint_id = dhlparcel_additional_servicepoint_id;

                    $(document.body).trigger("dhlparcel:servicepoint_selection_sync", [event]);
                } else {
                    $(document.body).trigger("dhlparcel:servicepoint_selection_sync", [event]);
                    $(document.body).trigger('dhlparcel:hide_parcelshop_selection_modal');
                }
            } else {
                $(document.body).trigger("dhlparcel:add_servicepoint_component_confirm_button");
                $(document.body).trigger("dhlparcel:servicepoint_selection_sync", [event]);
            }
        };

        // Disable getScript from adding a custom timestamp
        $.ajaxSetup({cache: true});
        $.getScript("https://servicepoint-locator.dhlparcel.nl/servicepoint-locator.js").done(function() {
            dhlparcel_parcelshop_selection_modal_loaded = true;
            dhlparcel_parcelshop_selection_modal_loading_busy = false;
            $(document.body).trigger("dhlparcel:connect_servicepoint_selection_modal");
        });

    }).on('dhlparcel:connect_servicepoint_selection_modal', function(e) {
        if (dhlparcel_parcelshop_selection_modal_connected === true) {
            return;
        }
        dhlparcel_parcelshop_selection_modal_connected = true;

        // Send an after connected event
        $(document.body).trigger("dhlparcel:servicepoint_set_triggers");

    }).on('dhlparcel:servicepoint_selection_sync', function(e, event) {
        // Dummy event (recommended to use a different handle for specific pages)

    }).on('dhlparcel:show_parcelshop_selection_modal', function(e, hostElement) {
        // Do nothing if the base modal hasn't been loaded yet.
        if (dhlparcel_parcelshop_selection_modal_loaded === false) {
            return;
        }

        if (typeof  window.dhlparcel_shipping_reset_servicepoint === "function") {
            var options = {
                host: DHLShipping_ServicePointAPIUrl,
                apiKey: DHLShipping_ServicePointMapsAPIKey,
                query: hostElement.getAttribute('data-zip-code'),
                countryCode: hostElement.getAttribute('data-country-code'),
                limit: 7,
                tr: dhlparcelTranslations
            };

            // Use the generated function provided by the component to load the ServicePoints
            window.dhlparcel_shipping_reset_servicepoint(options);
        } else {
            console.log('An unexpected error occured. ServicePoint functions were not loaded.');
        }

    }).on('dhlparcel:hide_parcelshop_selection_modal', function(e) {
        $('span.dhlparcel-modal-title').text('');
        $('div.dhlparcel-modal').hide();

    }).on('dhlparcel:add_servicepoint_component_confirm_button', function() {
        if ($('.dhl-parcelshop-locator .dhl-parcelshop-locator-desktop ul .dhlparcel-servicepoint-component-confirm-button').length === 0) {
            $('.dhl-parcelshop-locator .dhl-parcelshop-locator-desktop ul').prepend(
                '<li class="md-list-item">' +
                '<div role="button" class="dhlparcel-servicepoint-component-confirm-button md-fake-btn md-pointer--hover md-fake-btn--no-outline md-list-tile md-list-tile--three-lines md-text" tabindex="0" aria-pressed="false">' +
                'Select' +
                '</div>' +
                '</li>'
            );
        }

    }).on('click', '.dhlparcel-servicepoint-component-confirm-button', function(e) {
        e.preventDefault();
        $(document.body).trigger('dhlparcel:hide_servicepoint_selection_modal');

    }).on('dhlparcel:hide_servicepoint_selection_modal', function(e) {
        $('div.dhlparcel-modal').hide();

    });

    $(document.body).trigger("dhlparcel:load_servicepoint_selection_modal");
});
