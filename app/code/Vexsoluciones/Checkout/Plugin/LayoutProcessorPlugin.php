<?php
namespace Vexsoluciones\Checkout\Plugin;

class LayoutProcessorPlugin
{
 
    public function afterProcess(
        \Magento\Checkout\Block\Checkout\LayoutProcessor $subject,
        array  $jsLayout
    ) {
 
        /*$customAttributeCodeDNI = 'dni';

        $customFieldDNI = [
            'component' => 'Magento_Ui/js/form/element/abstract',
            'config' => [
                // customScope is used to group elements within a single form (e.g. they can be validated separately)
                'customScope' => 'shippingAddress.custom_attributes',
                'customEntry' => null,
                'template' => 'ui/form/field',
                'elementTmpl' => 'ui/form/element/input',
                'tooltip' => [
                    'description' => 'Documento de identidad de quien hace el pedido',
                ],
            ],
            'dataScope' => 'shippingAddress.custom_attributes' . '.' . $customAttributeCodeDNI,
            'label' => 'DNI',
            'provider' => 'checkoutProvider',
            'sortOrder' => 41,
            'validation' => [
               'required-entry' => true
            ],
            'options' => [],
            'filterBy' => null,
            'customEntry' => null,
            'visible' => true,
        ];

        $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']['children'][$customAttributeCodeDNI] = $customFieldDNI;
*/
        unset($jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']['children']['region_id']);
        

        //unset($jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']['children']['city']);
        //print_r($jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']['children'])


        /*$jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']
        ['shippingAddress']['children']['shipping-address-fieldset']['children']['street'] = [
            'component' => 'Magento_Ui/js/form/components/group',
           // 'label' => __('Street Address'), // I removed main label
          //  'required' => true, //turn false because I removed main label
            'dataScope' => 'shippingAddress.street',
            'provider' => 'checkoutProvider',
            'sortOrder' => 70,
            'type' => 'group',
            'additionalClasses' => 'form-street',
            'children' => [
                [
                    'label' => __('Calle y número'),
                    'placeholder' => 'Ej: Urbanización Villa Fortuna G-30',
                    'additionalClasses' => 'vex-checkout-street',
                    'component' => 'Magento_Ui/js/form/element/abstract',
                    'config' => [
                        'customScope' => 'shippingAddress',
                        'template' => 'ui/form/field',
                        'elementTmpl' => 'ui/form/element/input'
                    ],
                    'dataScope' => '0',
                    'provider' => 'checkoutProvider',
                    'validation' => ['required-entry' => true, "min_text_len‌​gth" => 1, "max_text_length" => 255],
                ],
                [
                    'label' => __('Dept, Ofi'),
                    'placeholder' => 'Ej: Departamento 322',
                    'component' => 'Magento_Ui/js/form/element/abstract',
                    'config' => [
                        'customScope' => 'shippingAddress',
                        'template' => 'ui/form/field',
                        'elementTmpl' => 'ui/form/element/input'
                    ],
                    'dataScope' => '1',
                    'provider' => 'checkoutProvider',
                    'validation' => ['required-entry' => false, "min_text_len‌​gth" => 1, "max_text_length" => 255],
                ],
                [
                    'label' => __('Referencia'),
                    'placeholder' => 'A espaldas del colegio Simón Bolivar',
                    'component' => 'Magento_Ui/js/form/element/abstract',
                    'config' => [
                        'customScope' => 'extensionAttributes',
                        'template' => 'ui/form/field',
                        'elementTmpl' => 'ui/form/element/input'
                    ],
                    'dataScope' => '2',
                    'provider' => 'checkoutProvider',
                    'validation' => ['required-entry' => false, "min_text_len‌​gth" => 1, "max_text_length" => 255],
                ] 
            ]
        ];*/

/*
	$jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']
      ['shippingAddress']['children']['shipping-address-fieldset']['children']['latitud'] =
      ['component' => 'Magento_Ui/js/form/element/abstract','config' => 
      [
        'customScope' => 'shippingAddress.custom_attributes',
        'template' => 'ui/form/field',
        'elementTmpl' => 'ui/form/element/input',
     ],
        'dataScope' => 'shippingAddress.custom_attributes.latitud',
        'label' => 'Latitud',
        'provider' => 'checkoutProvider',
        'visible' => true,
	'validation' => ['required-entry' => true,'validate-number' => true, 'min_text_length' => 1, 'max_text_length' => 15],
        'sortOrder' => 250,
        'id' => 'latitud',
	'placeholder' => '9.934618',
	'value' => '9.934618' 
      ];

      $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']
      ['shippingAddress']['children']['shipping-address-fieldset']['children']['longitud'] =
      [
        'component' => 'Magento_Ui/js/form/element/abstract','config' =>
        [
          'customScope' => 'shippingAddress.custom_attributes',
          'template' => 'ui/form/field',
          'elementTmpl' => 'ui/form/element/input',
        ],
        'dataScope' => 'shippingAddress.custom_attributes.longitud',
        'label' => 'Longitud',
        'provider' => 'checkoutProvider',
        'visible' => true,
	'validation' => ['required-entry' => true,'validate-number' => true, 'min_text_length' => 1, 'max_text_length' => 15], 
        'sortOrder' => 251,
        'id' => 'longitud',
	'placeholder' => '-84.078715',
	'value' => '-84.078715'
      ];*/
	  

      $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']
      ['shippingAddress']['children']['shipping-address-fieldset']['children']['ubicacion'] =
      [
        'component' => 'Magento_Ui/js/form/element/abstract',
        'config' =>
        [
          'customScope' => 'shippingAddress.custom_attributes',
          'template' => 'ui/form/field',
          'elementTmpl' => 'ui/form/element/checkbox',
          'options' => [],
          'tooltip' => [
                    'description' => 'Deje vacío si va a enviar a otra ubicación.',
                ],
          'id' => 'ubicacion',
		  'description' => ''
        ],
        'dataScope' => 'shippingAddress.custom_attributes.ubicacion',
        'provider' => 'checkoutProvider',
        'label' => '¿Usar mi ubicación actual?',
        'visible' => true,
        'checked' => false,
        'validation' => [],
        'sortOrder' => 251,
        'id' => 'ubicacion'
      ];
	  
		$jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']['children']['departamento_id']['sortOrder'] = 200;
		$jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']['children']['provincia_id']['sortOrder'] = 201;
		$jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']['children']['distrito_id']['sortOrder'] = 202;
        return $jsLayout;
    }
}
