jQuery(document).ready(function($) {
    $(document.body).on('dhlparcel:servicepoint_modal_closed', function() {
        // Check if a ServicePoint is selected. If not, uncheck the ServicePoint method

    }).on('dhlparcel:servicepoint_set_triggers', function() {
        // Check if the regular Checkout is being used.
        if (typeof Checkout !== 'undefined' && Checkout.prototype !== undefined && Checkout.prototype.gotoSection !== undefined) {
            // Check this hasn't been called already
            if (Checkout.prototype.baseGotoSection === undefined) {

                // Make ref to default function
                Checkout.prototype.baseGotoSection = Checkout.prototype.gotoSection;
                // "Rewrite" function
                Checkout.prototype.gotoSection = function (section, reloadProgressBlock) {
                    if (section === 'shipping_method') {
                        // Add a hidden servicepoint input select
                        if (!$('#dhlparcel-servicepoint-select').length) {
                            $('<input>').attr({
                                type: 'hidden',
                                id: 'dhlparcel-servicepoint-select',
                                name: 'dhlparcel-servicepoint-select'
                            }).appendTo('#co-shipping-method-form');
                        }

                        var hostElement = document.getElementById('dhl-servicepoint-locator-component');

                        if ($('#dhlparcel_servicepoint_change_button').length == 0) {
                            $("label[for='s_method_dhlparcel_PS'] > span:first").after('' +
                                '<div id="dhlparcel_servicepoint_change_button">' +
                                '<button type="button" class="button dhlparcel_servicepoint_change"><span><span>' + DHLShipping_Texts_SelectPs + '</span></span</button>' +
                                '</div>'
                            );
                            $("label[for='s_method_dhlparcel_PS'] #dhlparcel_servicepoint_change_button").before('<div id="dhlparcel_servicepoint_name_info">&nbsp;</div>');

                            $(".dhlparcel_servicepoint_change").click(function() {
                                if ($('input[name="billing[use_for_shipping]"]').length > 0 && $('input[name="billing[use_for_shipping]"]').is(":checked")) {
                                    $('#dhl-servicepoint-locator-component').attr('data-zip-code', $('input[name="billing[postcode]"]').val().trim());
                                    $('#dhl-servicepoint-locator-component').attr('data-country-code', $('select[name="billing[country_id]"]').val());
                                } else {
                                    $('#dhl-servicepoint-locator-component').attr('data-zip-code', $('input[name="shipping[postcode]"]').val().trim());
                                    $('#dhl-servicepoint-locator-component').attr('data-country-code', $('select[name="shipping[country_id]"]').val());
                                }

                                $(document.body).trigger("dhlparcel:show_parcelshop_selection_modal", [hostElement]);
                                $('div.dhlparcel-modal').show();
                            });
                        }

                        // Select Closest PS
                        $(document.body).trigger('dhlparcel:servicepoint_get_closest', [{
                            'zipCode': $('input[name="shipping[postcode]"]').val().trim(),
                            'countryCode': $('select[name="shipping[country_id]"]').val()
                        }]);

                        $("input[name='shipping_method']").change(function (e) {
                            if ($(this).val() === 'dhlparcel_PS') {
                                $('#dhlparcel-servicepoint-select').addClass('required-entry');
                            } else {
                                $('#dhlparcel-servicepoint-select').removeClass('required-entry');
                            }
                        });

                        $("input[name='shipping_method']:checked").change();
                    }

                    // call default function
                    return this.baseGotoSection(section, reloadProgressBlock);
                }
            }
        } else if ($('#co-shipping-method-form').length > 0) {
            // Add a hidden servicepoint input select
            if (!$('#dhlparcel-servicepoint-select').length) {
                $('<input>').attr({
                    type: 'hidden',
                    id: 'dhlparcel-servicepoint-select',
                    name: 'dhlparcel-servicepoint-select'
                }).appendTo('#co-shipping-method-form');
            }

            var hostElement = document.getElementById('dhl-servicepoint-locator-component');

            $("input[name='estimate_method']").change(function (e) {
                if ($(this).val() === 'dhlparcel_PS') {
                    $('#dhl-servicepoint-locator-component').attr('data-zip-code', $('input[name="estimate_postcode"]').val().trim());
                    $('#dhl-servicepoint-locator-component').attr('data-country-code', $('[name="country_id"]').val());

                    // Select Closest PS
                    $(document.body).trigger('dhlparcel:servicepoint_get_closest', [{
                        'zipCode': $('input[name="estimate_postcode"]').val().trim(),
                        'countryCode': $('[name="country_id"]').val()
                    }]);

                    $(document.body).trigger("dhlparcel:show_parcelshop_selection_modal", [hostElement]);
                    $('div.dhlparcel-modal').show();

                } else {
                    $(document.body).trigger("dhlparcel:hide_parcelshop_selection_modal");

                }
            });

            $("input[name='shipping_method']:checked").change();

        } else if ((typeof oscUpdateCartCall !== 'undefined' && $.isFunction(oscUpdateCartCall)) || $('.onestepcheckout-index-index').length > 0) {
            // Add a hidden servicepoint input select
            if (!$('#dhlparcel-servicepoint-select').length) {
                $('<input>').attr({
                    type: 'hidden',
                    id: 'dhlparcel-servicepoint-select',
                    name: 'dhlparcel-servicepoint-select'
                }).appendTo('#onestepcheckout-form');
            }

            // Select Closest PSc
            if ($('input[name="billing[use_for_shipping]"]').length > 0 && $('input[name="billing[use_for_shipping]"]').is(":checked")) {
                var dhlparcel_postalcode = $('input[name="billing[postcode]"]').val().trim();
                var dhlparcel_country_code = $('select[name="billing[country_id]"]').val();
            } else {
                var dhlparcel_postalcode = $('input[name="shipping[postcode]"]').val().trim();
                var dhlparcel_country_code = $('select[name="shipping[country_id]"]').val();
            }

            var hostElement = document.getElementById('dhl-servicepoint-locator-component');

            if ($('#dhlparcel_servicepoint_change_button').length == 0) {
                $("label[for='s_method_dhlparcel_PS']").append('' +
                    '<div id="dhlparcel_servicepoint_change_button">' +
                    '<button type="button" class="button dhlparcel_servicepoint_change"><span><span>' + DHLShipping_Texts_SelectPs + '</span></span</button>' +
                    '</div>'
                );
                $("label[for='s_method_dhlparcel_PS'] #dhlparcel_servicepoint_change_button").before('<div id="dhlparcel_servicepoint_name_info">&nbsp;</div>');


                $(".dhlparcel_servicepoint_change").click(function() {
                    $('#dhl-servicepoint-locator-component').attr('data-zip-code', dhlparcel_postalcode);
                    $('#dhl-servicepoint-locator-component').attr('data-country-code', dhlparcel_country_code);

                    $(document.body).trigger("dhlparcel:show_parcelshop_selection_modal", [hostElement]);
                    $('div.dhlparcel-modal').show();
                });
            }

            $("input[name='shipping_method']").change(function (e) {
                if ($(this).val() === 'dhlparcel_PS') {
                    $('#dhlparcel-servicepoint-select').addClass('required-entry');
                } else {
                    $('#dhlparcel-servicepoint-select').removeClass('required-entry');
                }
            });

            $(document.body).trigger('dhlparcel:servicepoint_get_closest', [{
                'zipCode': dhlparcel_postalcode,
                'countryCode': dhlparcel_country_code
            }]);
        } else if (typeof quicklogin !== 'undefined') {
            // Add a hidden servicepoint input select
            if (!$('#dhlparcel-servicepoint-select').length) {
                $('<input>').attr({
                    type: 'hidden',
                    id: 'dhlparcel-servicepoint-select',
                    name: 'dhlparcel-servicepoint-select'
                }).appendTo('.main_checkout');
            }

            var hostElement = document.getElementById('dhl-servicepoint-locator-component');

            // Select Closest PSc
            if ($('input[name="billing[use_for_shipping]"]').length > 0 && $('input[name="billing[use_for_shipping]"]').is(":checked")) {
                var dhlparcel_postalcode = $('input[name="billing[postcode]"]').val().trim();
                var dhlparcel_country_code = $('select[name="billing[country_id]"]').val();
            } else {
                var dhlparcel_postalcode = $('input[name="shipping[postcode]"]').val().trim();
                var dhlparcel_country_code = $('select[name="shipping[country_id]"]').val();
            }

            if ($('#dhlparcel_servicepoint_change_button').length == 0) {
                $("label[for='s_method_dhlparcel_PS']").append('' +
                    '<div id="dhlparcel_servicepoint_change_button">' +
                    '<button type="button" class="button dhlparcel_servicepoint_change"><span><span>' + DHLShipping_Texts_SelectPs + '</span></span</button>' +
                    '</div>'
                );
                $("label[for='s_method_dhlparcel_PS'] #dhlparcel_servicepoint_change_button").before('<div id="dhlparcel_servicepoint_name_info">&nbsp;</div>');


                $(".dhlparcel_servicepoint_change").click(function() {
                    $('#dhl-servicepoint-locator-component').attr('data-zip-code', dhlparcel_postalcode);
                    $('#dhl-servicepoint-locator-component').attr('data-country-code', dhlparcel_country_code);

                    $(document.body).trigger("dhlparcel:show_parcelshop_selection_modal", [hostElement]);
                    $('div.dhlparcel-modal').show();
                });
            }

            $("input[name='shipping_method']").change(function (e) {
                if ($(this).val() === 'dhlparcel_PS') {
                    $('#dhlparcel-servicepoint-select').addClass('required-entry');
                } else {
                    $('#dhlparcel-servicepoint-select').removeClass('required-entry');
                }
            });

            // Select Closest PSc
            $(document.body).trigger('dhlparcel:servicepoint_get_closest', [{
                'zipCode': dhlparcel_postalcode,
                'countryCode': dhlparcel_country_code
            }]);
        } else if (typeof AWOnestepcheckoutLogin !== 'undefined') {
            // Add a hidden servicepoint input select
            if (!$('#dhlparcel-servicepoint-select').length) {
                $('<input>').attr({
                    type: 'hidden',
                    id: 'dhlparcel-servicepoint-select',
                    name: 'dhlparcel-servicepoint-select'
                }).appendTo('#aw-onestepcheckout-general-form');
            }

            // Select Closest PSc
            if ($('input[name="billing[use_for_shipping]"]').length > 0 && $('input[name="billing[use_for_shipping]"]').is(":checked")) {
                var dhlparcel_postalcode = $('input[name="billing[postcode]"]').val().trim();
                var dhlparcel_country_code = $('select[name="billing[country_id]"]').val();
            } else {
                var dhlparcel_postalcode = $('input[name="shipping[postcode]"]').val().trim();
                var dhlparcel_country_code = $('select[name="shipping[country_id]"]').val();
            }

            var hostElement = document.getElementById('dhl-servicepoint-locator-component');

            if ($('#dhlparcel_servicepoint_change_button').length == 0) {
                $("label[for='s_method_dhlparcel_PS']").append('' +
                    '<div id="dhlparcel_servicepoint_change_button">' +
                    '<button type="button" class="button dhlparcel_servicepoint_change"><span><span>' + DHLShipping_Texts_SelectPs + '</span></span</button>' +
                    '</div>'
                );
                $("label[for='s_method_dhlparcel_PS'] #dhlparcel_servicepoint_change_button").before('<div id="dhlparcel_servicepoint_name_info">&nbsp;</div>');


                $(".dhlparcel_servicepoint_change").click(function() {
                    $('#dhl-servicepoint-locator-component').attr('data-zip-code', dhlparcel_postalcode);
                    $('#dhl-servicepoint-locator-component').attr('data-country-code', dhlparcel_country_code);

                    $(document.body).trigger("dhlparcel:show_parcelshop_selection_modal", [hostElement]);
                    $('div.dhlparcel-modal').show();
                });
            }

            $("input[name='shipping_method']").change(function (e) {
                if ($(this).val() === 'dhlparcel_PS') {
                    $('#dhlparcel-servicepoint-select').addClass('required-entry');
                } else {
                    $('#dhlparcel-servicepoint-select').removeClass('required-entry');
                }
            });

            // Select Closest PSc
            $(document.body).trigger('dhlparcel:servicepoint_get_closest', [{
                'zipCode': dhlparcel_postalcode,
                'countryCode': dhlparcel_country_code
            }]);
        }
    }).on('dhlparcel:servicepoint_get_closest', function(e, event) {
        if (typeof event.zipCode === 'undefined' || !event.zipCode) {
            return;
        }

        if (typeof event.countryCode === 'undefined' || !event.countryCode) {
            return;
        }

        if (event.zipCode in DHLShipping_FetchedPs) {
            $(document.body).trigger("dhlparcel:servicepoint_selection_sync", DHLShipping_FetchedPs[event.zipCode]);

            return;
        }

        $.get(DHLShipping_ServicePointAPIUrl + '/parcel-shop-locations/' + event.countryCode, {
            'limit' : 13,
            'zipCode': event.zipCode
        }, function (data) {
            $.each(data, function (key, parcelShop) {
                if (event.countryCode !== 'DE' || parcelShop.shopType !== 'packStation') {
                    DHLShipping_FetchedPs[event.zipCode] = parcelShop;
                    $(document.body).trigger("dhlparcel:servicepoint_selection_sync", [parcelShop, false]);
                    return false;
                }
            });
        }, 'json');
    }).on('dhlparcel:servicepoint_selection_sync', function(e, event, selectParcelShop) {
        $('span.dhlparcel-modal-title').text(event.name);
        $('#dhlparcel_servicepoint_name_info').remove();

        $('.dhlparcel_servicepoint_change > span > span').text(DHLShipping_Texts_ChangePs);

        if (event.distance < 1000) {
            var DistanceAsText = event.distance + 'M';
        } else {

            var DistanceAsText = Math.round( event.distance/100) / 10 + 'KM';
        }

        if (typeof event.address.addition === 'undefined') {
            event.address.addition = '';
        }

        $("label[for='s_method_dhlparcel_PS'] #dhlparcel_servicepoint_change_button").before('<div id="dhlparcel_servicepoint_name_info" title="' + event.name + ' | ' + event.address.street + ' ' + event.address.number + event.address.addition + ' | ' + event.address.postalCode + ' ' + event.address.city + '">' + event.name + ' | ' + DHLShipping_Texts_Distance + ': ' + DistanceAsText + '</div>');

        let servicePointId = event.id;
        if (event.additional_servicepoint_id != null && event.additional_servicepoint_id != '') {
            servicePointId += '|' + event.additional_servicepoint_id;
        }

        $('#dhlparcel-servicepoint-select').val(servicePointId);

        if (selectParcelShop === true) {
            $('#s_method_dhlparcel_PS').click();
        }
    });

});
