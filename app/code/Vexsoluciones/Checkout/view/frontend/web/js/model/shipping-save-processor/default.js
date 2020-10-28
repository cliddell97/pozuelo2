/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
/*global define,alert*/
define(
    [
	'jquery',
        'ko',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/resource-url-manager',
        'mage/storage',
        'Magento_Checkout/js/model/payment-service',
        'Magento_Checkout/js/model/payment/method-converter',
        'Magento_Checkout/js/model/error-processor',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Checkout/js/action/select-billing-address',
        'Vexsoluciones_Checkout/js/model/ubigeo',
        'Magento_Customer/js/model/customer',
        'Magento_Customer/js/customer-data'
    ],
    function (
	$,
        ko,
        quote,
        resourceUrlManager,
        storage,
        paymentService,
        methodConverter,
        errorProcessor,
        fullScreenLoader,
        selectBillingAddressAction,
        ubigeoCheckout,
        recojoentiendaDatos,
        customer,
        customerData
    ) {
        'use strict';

        return {
            saveShippingInformation: function () {
                let payload;

                if (!quote.billingAddress()) {
                    selectBillingAddressAction(quote.shippingAddress());
                }

                let shippingAddress = quote.shippingAddress();
                let billingInfo = quote.billingAddress();

                let ciudad = '';


                let departamento_id = ubigeoCheckout.departamento_id_actual();
                let provincia_id    = ubigeoCheckout.provincia_id_actual();
                let distrito_id     = ubigeoCheckout.distrito_id_actual();
                let devdis     = ubigeoCheckout.distancia();
                let dev     = ubigeoCheckout.deliveryLocation_actual();
                let regionCode = distrito_id;//+ '-' + devdis+'-'+ubigeoCheckout.movilidad()+'-'+ubigeoCheckout.deliveryLocation_actual().lat + ',' + ubigeoCheckout.deliveryLocation_actual().lng; 

                if(shippingAddress.countryId != "CR"){
                    regionCode = "0-0-0";
                }

                shippingAddress.postcode = regionCode;
                shippingAddress.regionCode = regionCode;

                billingInfo.postcode = regionCode;
                billingInfo.regionCode = regionCode;
                billingInfo.countryId = shippingAddress.countryId;

                let deliveryLocation = '';

                if( 'lat' in ubigeoCheckout.deliveryLocation_actual() ){

                    deliveryLocation = ubigeoCheckout.deliveryLocation_actual().lat + ',' + ubigeoCheckout.deliveryLocation_actual().lng; 
                }

		var Latitud = $('[name="custom_attributes[latitud]"]').val();
		var Longitud = $('[name="custom_attributes[longitud]"]').val();
			if(Latitud == "" || Longitud == ""){
				alert("Por favor seleccione una ubicación válida en el mapa.");
			}
			payload = {
                    addressInformation: {
                        shipping_address: shippingAddress,
                        billing_address: billingInfo,
                        shipping_method_code: quote.shippingMethod().method_code,
                        shipping_carrier_code: quote.shippingMethod().carrier_code,
                        extension_attributes: {
                            departamento_id : ubigeoCheckout.departamento_id_actual(),
                            provincia_id : ubigeoCheckout.provincia_id_actual(),
                            distrito_id :  ubigeoCheckout.distrito_id_actual(),
                            delivery_location : deliveryLocation,
                            programado : ubigeoCheckout.programado(),
                            latitud: Latitud,
                            longitud: Longitud
                        }
                    }
                };

                fullScreenLoader.startLoader();

                return storage.post(
                    resourceUrlManager.getUrlForSetShippingInformation(quote),
                    JSON.stringify(payload)
                ).done(
                    function (response) {
                        quote.setTotals(response.totals);
                        paymentService.setPaymentMethods(methodConverter(response.payment_methods));
                        fullScreenLoader.stopLoader();
                    }
                ).fail(
                    function (response) {
                        errorProcessor.process(response);
                        fullScreenLoader.stopLoader();
                    }
                );
			}
        };
    }
);
