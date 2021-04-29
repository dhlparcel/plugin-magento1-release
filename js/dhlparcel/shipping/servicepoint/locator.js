jQuery(document).ready(function($) {

    var dhlparcel_parcelshop_selection_modal_loading_busy = false;
    var dhlparcel_parcelshop_selection_modal_loaded = false;
    var dhlparcel_parcelshop_selection_modal_connected = false;

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

            $(document.body).trigger("dhlparcel:servicepoint_selection_sync", [event]);
        };

        // Disable getScript from adding a custom timestamp
        $.ajaxSetup({cache: true});
        $.getScript("https://static.dhlparcel.nl/components/servicepoint-locator-component@latest/servicepoint-locator-component.js").done(function() {
          // Load ServicePoint Locator
            var options = {
                language: DHLShipping_Language,
                country: '',
                header: true,
                resizable: true,
                onSelect: window.dhlparcel_shipping_select_servicepoint
            };

            if (typeof DHLShipping_ServicePointMapsAPIKey !== "undefined" && DHLShipping_ServicePointMapsAPIKey.length > 0) {
                options.googleMapsApiKey = DHLShipping_ServicePointMapsAPIKey;
            }

            window.dhlparcel_shipping_servicepoint_locator = new dhl.servicepoint.Locator(document.getElementById("dhl-servicepoint-locator-component"), options);

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

        if (typeof window.dhlparcel_shipping_servicepoint_locator !== 'undefined') {
            window.dhlparcel_shipping_servicepoint_locator.setCountry($('#dhl-servicepoint-locator-component').attr('data-country-code'))
            window.dhlparcel_shipping_servicepoint_locator.setQuery($('#dhl-servicepoint-locator-component').attr('data-zip-code'))
        }
    }).on('dhlparcel:hide_parcelshop_selection_modal', function(e) {
        $('span.dhlparcel-modal-title').text('');
        $('div.dhlparcel-modal').hide();
    }).on('click', '.dhlparcel-servicepoint-component-confirm-button', function(e) {
        e.preventDefault();
        $(document.body).trigger('dhlparcel:hide_servicepoint_selection_modal');

    }).on('dhlparcel:hide_servicepoint_selection_modal', function(e) {
        $('div.dhlparcel-modal').hide();

    });

    $(document.body).trigger("dhlparcel:load_servicepoint_selection_modal");
});
