jQuery(document).ready(function($) {

    $(document.body).on('dhlparcel:servicepoint_set_triggers', function() {

        var hostElement = document.getElementById('dhl-servicepoint-locator-component');

        $("button.dhlparcel-change-parcelshop-button").click(function (e) {
            $('#dhl-servicepoint-locator-component').attr('data-zip-code', DHLShipping_ServicePointPostcode);
            $('#dhl-servicepoint-locator-component').attr('data-country-code', DHLShipping_ServicePointCountryCode);

            $(document.body).trigger("dhlparcel:show_parcelshop_selection_modal", [hostElement]);
            $('div.dhlparcel-modal').show();
        });

    }).on('dhlparcel:servicepoint_selection_sync', function(e, event) {
        new Ajax.Request(DHLShipping_ServicePointSelectUrl, {
            method:     'post',
            parameters: {
                'id': event.id,
                countryCode: DHLShipping_ServicePointCountryCode
            },
            onSuccess: function(data){
                $('.dhlparcel-shipping-ps-details-address').html(data.responseJSON.parcelshopFormatted);
                $('.dhlparcel-servicepoint-selected-id').val(data.responseJSON.parcelshopId);
            }
        });

    });

});
