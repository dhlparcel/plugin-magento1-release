document.observe('dom:loaded', function() {
    if ($('create_shipping_label') !== null) {
        $('create_shipping_label').up('p').remove()
    };

    DHLShipping_InitShippingForm();
});

function DHLShipping_InitShippingForm() {
    jQuery('.order-totals-bottom p:last').after('<p>' +
        '              <label class="normal" for="create_dhl_label">Create DHL Label</label>' +
        '              <input id="create_dhl_label" name="shipment[create_dhl_label]" value="1" type="checkbox">' +
        '          </p>');


    jQuery('#create_dhl_label').change(function(){
       if (jQuery(this).is(':checked')) {
           if (!jQuery('#dhl_shipping_options').is(':visible')) {
               jQuery('#dhl_shipping_options').slideDown();
           }
       } else {
           if (jQuery('#dhl_shipping_options').is(':visible')) {
               jQuery('#dhl_shipping_options').slideUp();
           }
       }
    });

    if (DHLShipping_CreateDHLLabel == true) {
        jQuery('#create_dhl_label').attr('checked', 'checked');
    } else {
        jQuery('#dhl_shipping_options').hide();
        jQuery('#create_dhl_label').attr('checked', false);
    }
    jQuery('#create_dhl_label').change();

    $$('.order-totals-bottom div.a-right')[0].insert({
        before: $('dhl_shipping_options')
    });

    $$('input#dhlparcel-shipping-business-switch').invoke('on', 'change', function(e, t) {
        var form = $j('#dhl_shipping_options');
        if ($j('#dhl_shipping_options_container').length === 0) {
            form.wrap("<div id='dhl_shipping_options_container'></div>");
        }

        var toBusiness = $j("input#dhlparcel-shipping-business-switch").is(':checked') ? 'true' : 'false';

        new Ajax.Request(DHLShipping_ShippingFormUrl, {
            method:     'post',
            parameters: {
                'orderId': DHLShipping_OrderId,
                'toBusiness': toBusiness
            },
            onSuccess: function(data){
                responseData = data.responseJSON;
                // Replace form
                form.remove();
                $j('#dhl_shipping_options_container').append(responseData.form);
                // Reload scripts
                DHLShipping_InitShippingForm();
            }
        });
        return false;
    });

    $$(".dhlparcel-shippingoption").invoke('observe', 'change', function() {
        DHLShipping_HandleShippingExclusions();
        DHLShipping_ReloadParcelTypes();
    });

    $$('.dhlparcel-shipping-delivery-options input').invoke('on', 'change', function(e, t) {
        DHLShipping_ShowShipmentOptions(t);
        DHLShipping_ReloadParcelTypes();
        return false;
    });

    // Handle default exclusions
    DHLShipping_HandleShippingExclusions();

    // Show shipment options
    if (jQuery('.dhl-delivery-option-selected input:checked').length == 0) {
        jQuery('.dhlparcel-shipping-delivery-options input').first().click();
    }

    DHLShipping_ShowShipmentOptions($$('.dhl-delivery-option-selected input')[0]);

    // Init Packages
    DHLShipping_PackageButton($$('.dhl_shipping_add_package'));

    // Initialize shipping methods
    DHLShipping_ReloadParcelTypes();
}

function DHLShipping_PackageButton(e) {
    e.invoke('on', 'click', function() {
        var packageCount = jQuery('.dhlparcel-shipping-package-row').length;

        // Original Package Row
        var packageRow = jQuery('.dhlparcel-shipping-package-row').last();

        // Cloned Package Row
        var packageRowClone = packageRow.clone();

        // Handle changes
        jQuery('.package_number', packageRowClone).html(packageCount+1);

        // Place clone after last row
        packageRow.after(packageRowClone);
        DHLShipping_PackageButton($$('.dhl_shipping_add_package:last'));
    });
}

function DHLShipping_ReloadParcelTypes() {
    new Ajax.Request(DHLShipping_ParcelTypesUrl, {
        method:     'post',
        parameters: $('edit_form').serialize(true),
        onSuccess: function(data){
            responseData = data.responseJSON;

            // Remove All Options
            $$('.dhl_shipping_parcel_type').each(function(parcelTypeSelect) {
                var i = 0;
                if ($(parcelTypeSelect).selectedIndex !== -1) {
                    var selectedValue = $(parcelTypeSelect).options[$(parcelTypeSelect).selectedIndex].value;
                } else {
                    var selectedValue = '';
                }

                for(i = $(parcelTypeSelect).options.length - 1 ; i >= 0 ; i--)
                {
                    $(parcelTypeSelect).remove(i);
                }

                // Add new options
                let options_added = 0;
                for (let [parcelTypeKey, parcelTypeValue] of Object.entries(responseData.parceltypes)) {
                    var newOption = new Element('option', {value: parcelTypeKey}).update(parcelTypeValue);

                    if (selectedValue !== '' && selectedValue == parcelTypeKey) {
                        newOption.selected = 'selected';
                    }

                    $(parcelTypeSelect).insert(newOption);
                    options_added++;
                }

                $j('.dhlparcel_hide_parcel_types_message', $j(parcelTypeSelect).parent().parent()).remove();
                if (options_added === 0) {
                    // Show error message when no parceltypes is empty
                    $j(parcelTypeSelect).addClass('dhlparcel_hide_parcel_types');
                    $j(parcelTypeSelect).parent().append('<div class="dhlparcel_hide_parcel_types_message">' + responseData.errormessage + '</div>');
                } else {
                    $j(parcelTypeSelect).removeClass('dhlparcel_hide_parcel_types');
                }
            });
        }
    });

    return true;
}

function DHLShipping_ShowShipmentOptions(selectedOption) {
    $$('.dhl-select-delivery-method').each(function(e){
        e.up().removeClassName('dhl-delivery-option-selected');
    });

    if (typeof selectedOption === 'undefined') {
        return;
    }

    selectedOption.up().addClassName('dhl-delivery-option-selected');

    if (selectedOption.readAttribute('data-delivery-method') == 'PS') {
        $j('.dhlparcel-servicepoint-selected-id').addClass('required-entry');
    } else {
        $j('.dhlparcel-servicepoint-selected-id').removeClass('required-entry');
    }

    $$('.dhl_shipment_options_wrapper').invoke('hide');
    $('dhl_shipment_options_' + selectedOption.readAttribute('data-delivery-method')).show();
}

function DHLShipping_EnableOption(option) {
    // Enable Shipment Options
    option.removeClassName('dhlparcel-shippingoption-disabled');
    option.up().removeClassName('dhlparcel-shippingoption-disabled');
    option.disabled=false;

    return option;
}

function DHLShipping_DisableOption(option) {
    // Disable Shipment Option
    option.addClassName('dhlparcel-shippingoption-disabled');
    option.up().addClassName('dhlparcel-shippingoption-disabled');

    option.disabled=true;
    option.checked=false;

    return option;
}

function DHLShipping_HandleShippingExclusions() {
    var totalExclusions = [];

    $$('.dhlparcel-shippingoption:checked').each(function (shippingOption) {
        var exlusions = shippingOption.readAttribute('data-exclusion').split(',');

        exlusions.forEach(function( value ) {
            if(!totalExclusions. include(value)) {
                totalExclusions.push(value);
            }
        });
    });

    $$('.dhlparcel-shippingoption').each(function (shippingOption) {
        DHLShipping_EnableOption(shippingOption)
    });


    totalExclusions.each(function( value ) {
        $$('.dhl_shipping_option_' + value).each(function (shippingOption){
            DHLShipping_DisableOption(shippingOption)
        });
    });
}
