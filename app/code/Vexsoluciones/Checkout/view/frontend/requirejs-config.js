 
var config = {
 	config:{
 		mixins: {
                'Magento_Checkout/js/view/shipping': {
                    'Vexsoluciones_Checkout/js/view/shippingmethod': true
                }
    }},
    map: {
        '*': {
            'Magento_Checkout/template/shipping-address/form.html': 'Vexsoluciones_Checkout/template/formularioshipping.html',
            'Magento_Checkout/js/view/shipping': 'Vexsoluciones_Checkout/js/view/shipping',
            'Magento_Checkout/js/model/address-converter': 'Vexsoluciones_Checkout/js/model/address-converter',
            "Magento_Checkout/js/model/shipping-save-processor/default" : "Vexsoluciones_Checkout/js/model/shipping-save-processor/default",
            'Magento_Checkout/template/shipping-address/address-renderer/default.html': 'Vexsoluciones_Checkout/template/checkout/shipping-address/address-renderer/default.html',
            'Magento_Checkout/template/shipping-information/address-renderer/default.html': 'Vexsoluciones_Checkout/template/checkout/shipping-information/address-renderer/default.html',
        }
    }
};
 