<?php

namespace Vexsoluciones\Checkout\Plugin;

class ShippingInformationManagementPlugin
{

    protected $quoteRepository;

    public function __construct(
        \Magento\Quote\Model\QuoteRepository $quoteRepository
    ) {
        $this->quoteRepository = $quoteRepository;
    }

 
    public function beforeSaveAddressInformation(
        \Magento\Checkout\Model\ShippingInformationManagement $subject,
        $cartId,
        \Magento\Checkout\Api\Data\ShippingInformationInterface $addressInformation
    ) {

        $extensionAttributes = $addressInformation->getExtensionAttributes();
        $departamento_id = $extensionAttributes->getDepartamentoId();
        $provincia_id = $extensionAttributes->getProvinciaId();
        $distrito_id = $extensionAttributes->getDistritoId();
        $delivery_location = $extensionAttributes->getDeliveryLocation();
        $programado = $extensionAttributes->getProgramado();

 
        $billingAddress = $addressInformation->getBillingAddress();
        $billingAddress->setDepartamentoId($departamento_id);
        $billingAddress->setProvinciaId($provincia_id);
        $billingAddress->setDistritoId($distrito_id); 
        $billingAddress->setDeliveryLocation($delivery_location);
        $billingAddress->setProgramado($programado);


        $shippingAddress = $addressInformation->getShippingAddress();
        $shippingAddress->setDepartamentoId($departamento_id);
        $shippingAddress->setProvinciaId($provincia_id);
        $shippingAddress->setDistritoId($distrito_id); 
        $shippingAddress->setDeliveryLocation($delivery_location);
        $shippingAddress->setProgramado($programado);



        $quote = $this->quoteRepository->getActive($cartId);


        $quote->setLatitud($extensionAttributes->getLatitud());
        $quote->setLongitud($extensionAttributes->getLongitud());

        /*
        $quote = $this->quoteRepository->getActive($cartId);
        $quote->setDeliveryDate($deliveryDate);
        */
    }
}
