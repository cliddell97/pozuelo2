define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (Component, rendererList) {
        'use strict';
        rendererList.push(
            {
                type: 'credomatic',
                component: 'Remmote_Credomaticpayment/js/view/payment/method-renderer/credomatic-method'
            }
        );
        return Component.extend({});
    }
);