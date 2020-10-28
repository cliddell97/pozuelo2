define(
    [
        'Magento_Payment/js/view/payment/cc-form',
        'jquery',
        'mage/url',
        'Magento_Checkout/js/action/place-order',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Payment/js/model/credit-card-validation/validator'
    ],
    function (Component, $, url) {
        'use strict';
        return Component.extend({
            defaults: {
                template: 'Remmote_Credomaticpayment/payment/credomatic',
                redirectAfterPlaceOrder: false 
            },
            isShowLegend: function () {

                return true;
            },

            getCode: function() {
                return 'credomatic';
            },

            isActive: function () {
                return true;
            },

            afterPlaceOrder: function () {


                $.post(url.build('credomaticpayment/threedsecure/checkflag'))
                    .done(function( data ) {
                        
                        if(data.threedsecure){
                            window.location.replace(url.build('credomaticpayment/threedsecure/verifycard'));
                        } else {
                            window.location.replace(url.build(window.checkoutConfig.defaultSuccessPageUrl));
                        }
                });
            },

        });
    }
);