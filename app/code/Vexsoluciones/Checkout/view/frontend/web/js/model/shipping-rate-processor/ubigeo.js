/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define(
    [
        'Magento_Checkout/js/model/resource-url-manager',
        'Magento_Checkout/js/model/quote',
        'mage/storage',
        'Magento_Checkout/js/model/shipping-service',
        'Magento_Checkout/js/model/shipping-rate-registry',
        'Magento_Checkout/js/model/error-processor'
    ],
    function (resourceUrlManager, quote, storage, shippingService, rateRegistry, errorProcessor) {
        'use strict';

        return {
 
            getRates: function (address) {
                
                shippingService.isLoading(true);
                
                // Obtenemos el 

                var cache = rateRegistry.get(address.getCacheKey()),
                    serviceUrl = resourceUrlManager.getUrlForEstimationShippingMethodsForNewAddress(quote),
                    payload = JSON.stringify({
                            address: {
                                'street': address.street,
                                'city': address.city,
                                'region_id': address.regionId,
                                'region': address.region,
                                'country_id': address.countryId,
                                'postcode': address.postcode,
                                'regionCode': address.regionCode,
                                'email': address.email,
                                'customer_id': address.customerId,
                                'firstname': address.firstname,
                                'lastname': address.lastname,
                                'middlename': address.middlename,
                                'prefix': address.prefix,
                                'suffix': address.suffix,
                                'vat_id': address.vatId,
                                'company': address.company,
                                'telephone': address.telephone,
                                'fax': address.fax, 
                                'custom_attributes': address.customAttributes,
                                'save_in_address_book': address.saveInAddressBook
                            }
                        }
                    );

               /* if (cache) {
                    
                    shippingService.setShippingRates(cache);
                    shippingService.isLoading(false);

                } else { */
                    /*var aux3 = '';
                    var validatoraux3 = true;
                    jQuery.each( cache, function( key, value ) {
                        if(aux3 == ''){
                            aux3 = value.carrier_code;
                        }
                        if(jQuery("#valorshippingactual").val() == value.carrier_code){
                            validatoraux3 = false;
                        }
                    });
                    if(validatoraux3){
                        jQuery("#valorshippingactual").val(aux3);

                    }

                    if(jQuery("#valorshippingactual").val()=='chazkiprogramado'){
                        jQuery("#envio-programado-div").show();
                    }else{
                        jQuery("#envio-programado-div").hide();
                    }*/

                    return storage.post(
                        serviceUrl, payload, false
                    ).done(
                        function (result) {
                            rateRegistry.set(address.getCacheKey(), result);
                            shippingService.setShippingRates(result);
                        }
                    ).fail(
                        function (response) {
                            shippingService.setShippingRates([]);
                            errorProcessor.process(response);
                        }
                    ).always(
                        function () {
                            shippingService.isLoading(false);
                        }
                    );
                // }
            }
        };
    }
);
