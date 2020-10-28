<?php
namespace Imagineer\Moovin\Plugin\Checkout\Model;
class LayoutProcessorPlugin
{
    public function afterProcess(
        \Magento\Checkout\Block\Checkout\LayoutProcessor $subject,
        array  $jsLayout
    ) {
 
   

		$jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']
		['shippingAddress']['children']['shipping-address-fieldset']['children']['ubicacion']= [
             'component' => 'Magento_Ui/js/form/components/button',
            'config' => [
                'customScope' => 'shippingAddress.custom_attributes',
                'template' => 'ui/form/components/button/simple',
                'elementTmpl' => 'ui/form/element/button',
                'tooltip' => [
                    'description' => 'Ubicación aproximada basada en GPS. Por favor verifique que la ubicación sea correcta',
                ],
                'id' => 'ubicacion',
				'actions' => [],
                'buttonClasses' => 'botonUbicacion',
               	'title' => 'Averiguar mi ubicación'
            ],
            'dataScope' => 'shippingAddress.custom_attributes.ubicacion',
            'provider' => 'checkoutProvider',
            'visible' => true,
            'sortOrder' => 70,
            'id' => 'ubicacion'
        ];

        $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']
        ['children']['shippingAddress']['children']['shipping-address-fieldset']
        ['children']['telephone']['sortOrder'] = 40;
 
        $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']
        ['children']['shippingAddress']['children']['shipping-address-fieldset']
        ['children']['country_id']['sortOrder'] = 80;

        $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']
        ['children']['shippingAddress']['children']['shipping-address-fieldset']
        ['children']['region_id']['sortOrder'] = 90;

        $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']
        ['children']['shippingAddress']['children']['shipping-address-fieldset']
        ['children']['regiond']['sortOrder'] = 90;

        $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']
        ['children']['shippingAddress']['children']['shipping-address-fieldset']
        ['children']['city']['sortOrder'] = 100;
    
        $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']
        ['children']['shippingAddress']['children']['shipping-address-fieldset']
        ['children']['street']['sortOrder'] = 110;
    
        $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']
        ['children']['shippingAddress']['children']['shipping-address-fieldset']
        ['children']['postcode']['sortOrder'] = 120;

        $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']
        ['children']['shippingAddress']['children']['shipping-address-fieldset']
        ['children']['postcode']['tooltip']['description'] = 'El código postal se autocompleta al usar el mapa.';
 
        $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']
        ['children']['shippingAddress']['children']['shipping-address-fieldset']
        ['children']['company']['placeholder'] = 'ej: 116700721';
 
 
 
        return $jsLayout;
    }
}
