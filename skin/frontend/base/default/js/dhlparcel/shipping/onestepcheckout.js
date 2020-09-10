jQuery(document).ready(function ($) {

    var dhlparcel_ready_checkout_timeout = null;
    var dhlparcel_ready_checkout_loaded= null;
    var dhlparcel_onestepcheckout_observer = null;
    var dhlShippingOptionsSelected = [];

    $(document.body).on('dhlparcel:connect_checkout_shipping_service_options', function(e) {
        if (typeof oscUpdateCartCall !== 'undefined' && $.isFunction(oscUpdateCartCall) || $('.onestepcheckout-index-index').length > 0) {
            // Set onestepcheckout observer
            $(document.body).trigger('dhlparcel:set_onestepcheckout_observer');
            // Trigger once
            $(document.body).trigger('dhlparcel:ready_checkout_buffer');
        }

    }).on('dhlparcel:set_onestepcheckout_observer', function() {
        onestepcheckout_shipping_method_block = $$('div.onestepcheckout-shipping-method-block')[0];
        if (onestepcheckout_shipping_method_block !== null) {
            // Check this hasn't been called already
            if (dhlparcel_onestepcheckout_observer === null) {
                // Setup observer
                dhlparcel_onestepcheckout_observer = new MutationObserver(function (mutations) {
                    if (dhlparcel_ready_checkout_loaded === null) {
                        return;
                    }

                    if (mutations[0].target == onestepcheckout_shipping_method_block) {
                        // Trigger reload
                        dhlparcel_ready_checkout_loaded = null;
                        $(document.body).trigger('dhlparcel:ready_checkout_buffer');

                    }
                });
                dhlparcel_onestepcheckout_observer.observe(onestepcheckout_shipping_method_block, {childList: true, subtree: true});

            } else {
                // Just reset observer
                dhlparcel_onestepcheckout_observer.observe(onestepcheckout_shipping_method_block, {childList: true, subtree: true});
            }
        }
    }).on('dhlparcel:changed_shipping_options', function(){
        DHLParcel_Save_Options($('form#onestepcheckout-form'), function() {
            $('input[name="shipping_method"]:checked').click();

            // Reset Selected options
            dhlShippingOptionsSelected = [];

            $('input.dhlparcel-shippingoption:checked').each(function(index, inputField) {
                dhlShippingOptionsSelected[$(inputField).attr('id')] = true;
            });

            $('select.dhlparcel-shippingoption option:selected').each(function(index, inputField) {
                dhlShippingOptionsSelected[$(inputField).closest('select').attr('id')] = $(inputField).val();
            });
        });
    }).on('dhlparcel:ready_checkout_buffer', function() {
        clearTimeout(dhlparcel_ready_checkout_timeout);

        if ($('.onestepcheckout-shipping-method-block').length > 0) {
            dhlparcel_ready_checkout_timeout = setTimeout(function () {
                $(document.body).trigger('dhlparcel:ready_checkout');
            }, 100);
        }

    }).on('dhlparcel:ready_checkout', function() {
        // Check if 'Homedelivery' is available
        if ($('input#s_method_dhlparcel_DOOR').length == 0) {
            dhlparcel_ready_checkout_loaded = true;

            $(document.body).trigger('dhlparcel:servicepoint_set_triggers');

            dhlparcel_ready_checkout_loaded = true;

            return;
        }

        DHLParcel_Refresh_Options(function(refreshData){
            // Check again for a loaded block
            if (dhlparcel_ready_checkout_loaded !== null) {
                return;
            }

            dhlparcel_ready_checkout_timeout = null;
            $('input#s_method_dhlparcel_DOOR').parent().append(refreshData.html);
            dhlparcel_ready_checkout_loaded = true;

            DHLParcel_Show_Active_Options();

            // Select options
            DHLParcel_Select_Selected_Options(dhlShippingOptionsSelected);

            // Post shipping options and recalculate totals
            $('.dhlparcel-shippingoption').change(function(){
                $(document.body).trigger('dhlparcel:changed_shipping_options');
            });

            // Bind onchange shipping methods
            $('input[name="shipping_method"]').change(function(){
                // Show the active options
                DHLParcel_Show_Active_Options();
            });

            $(document.body).trigger('dhlparcel:servicepoint_set_triggers');

            dhlparcel_ready_checkout_loaded = true;
        });
    });

    $(document.body).trigger("dhlparcel:connect_checkout_shipping_service_options");
});
