function DHLParcel_Refresh_Options(onSuccessHandler) {
    var refreshData = new Ajax.Request(DHLShipping_refreshOptionsUrl, {
        method:     'post',
        parameters: {
            'optionKey': 'DOOR'
        },
        onSuccess: function(data){
            onSuccessHandler(data.responseJSON);
        },
        onError: function() {
            return false;
        }
    });

    return refreshData;
}

function DHLParcel_Save_Options(checkoutForm, onSuccessHandler) {
    var refreshData = new Ajax.Request(DHLShipping_saveOptionsUrl, {
        method:     'post',
        parameters: checkoutForm.serialize(),
        onSuccess: function(){
            onSuccessHandler();
        },
        onError: function() {
            return false;
        }
    });

    return refreshData;
}

function DHLParcel_Show_Active_Options() {
    if (jQuery('input[name="shipping_method"]').length == 1) {
        jQuery('ul.dhlparcel-shipping-delivery-options-wrapper').show();

        return true;
    }

    // Show right options
    jQuery('ul.dhlparcel-shipping-delivery-options-wrapper').hide();
    activeSelection = jQuery('input[name="shipping_method"]:checked').val();
    jQuery('ul.dhlparcel-shipping-delivery-options-wrapper-' + activeSelection).show();

    return true;
}

function DHLParcel_Select_Selected_Options(optionsSelected) {
    for (let [index, currentValue] of Object.entries(optionsSelected)) {
        if (currentValue === true || currentValue === false) {
            // Checkbox
            document.getElementById(index).checked = currentValue;
        } else {
            // Select box
            selectBox = document.getElementById(index);
            for(var i=0; i<selectBox.options.length; i++) {
                if ( selectBox.options[i].value == currentValue ) {
                    selectBox.selectedIndex = i;
                    break;
                }
            }
        }
    }
}
