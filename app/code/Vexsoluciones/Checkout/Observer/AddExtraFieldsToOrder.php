<?php 

namespace Vexsoluciones\Checkout\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer; 

use Vexsoluciones\Checkout\Api\QuoteExtraFields;

class AddExtraFieldsToOrder implements ObserverInterface
{
    
    protected $addressRepository;
    protected $provincia;
    protected $distrito;
    protected $departamento;

    public function __construct(\Vexsoluciones\Checkout\Model\BrandFactoryProvincia $provincia,
        \Vexsoluciones\Checkout\Model\BrandFactoryDistrito $distrito,
        \Vexsoluciones\Checkout\Model\BrandFactoryDepartamento $departamento,
        \Magento\Customer\Api\AddressRepositoryInterface $addressRepository){

        $this->addressRepository = $addressRepository;
        $this->departamento = $departamento;
        $this->distrito = $distrito;
        $this->provincia = $provincia;
    }
 
    public function execute(Observer $observer)
    {

        $order = $observer->getEvent()->getOrder();
        $quote = $observer->getEvent()->getQuote();
  
        // Quote get shipping address
    
        $quoteShippingAddress = $quote->getShippingAddress();
        $orderShippingAddress = $order->getShippingAddress();

        if($quoteShippingAddress && $quoteShippingAddress->getCountryId()=="CR"){
            $depa_id = $quoteShippingAddress->getData(QuoteExtraFields::DEPARTAMENTO_ID);
            $prov_id = $quoteShippingAddress->getData(QuoteExtraFields::PROVINCIA_ID);
            $dist_id = $quoteShippingAddress->getData(QuoteExtraFields::DISTRITO_ID);
            $programado = $quoteShippingAddress->getData(QuoteExtraFields::PROGRAMADO);

            if($quoteShippingAddress->getCustomerAddressId() != '' && is_numeric($quoteShippingAddress->getCustomerAddressId()) ){
                
                
                    $customerAddress = $this->addressRepository->getById($quoteShippingAddress->getCustomerAddressId());
                    
                    if($depa_id==0){
                        if($customerAddress->getCustomAttribute('departamento_id')){
                            $depa_id = $customerAddress->getCustomAttribute('departamento_id')->getValue();
                        }
                        if($customerAddress->getCustomAttribute('provincia_id')){
                            $prov_id = $customerAddress->getCustomAttribute('provincia_id')->getValue();
                        }
                        if($customerAddress->getCustomAttribute('distrito_id')){
                            $dist_id = $customerAddress->getCustomAttribute('distrito_id')->getValue();
                        }   
                    }

                    $customerAddress->setCustomAttribute('departamento_id',$depa_id); 
                    $customerAddress->setCustomAttribute('provincia_id',$prov_id); 
                    $customerAddress->setCustomAttribute('distrito_id',$dist_id);

                    $collection1 = $this->departamento->create()->load($depa_id,'idDepa');
                    $collection2 = $this->provincia->create()->load($prov_id,'idProv');
                    $collection3 = $this->distrito->create()->load($dist_id,'idDist');

                    $departamento = ($collection1)?$collection1->getData('departamento'):"";
                    $provincia = ($collection2)?$collection2->getData('provincia'):"";
                    $distrito = ($collection3)?$collection3->getData('distrito'):"";

                    $customerAddress->setCustomAttribute('departamento_label',$departamento); 
                    $customerAddress->setCustomAttribute('provincia_label',$provincia); 
                    $customerAddress->setCustomAttribute('distrito_label',$distrito);

                  //  $customerAddress->setPostCode($depa_id."-".$prov_id."-".$dist_id);
				    $customerAddress->setPostCode($dist_id);
                    $customerAddress->setCity($distrito.", ".$provincia.", ".$departamento);
                

                if( trim($quoteShippingAddress->getData(QuoteExtraFields::DELIVERY_LOCATION)) != ''){

                    $customerAddress->setCustomAttribute('address_location', $quoteShippingAddress->getData(QuoteExtraFields::DELIVERY_LOCATION) ); 
                }
      
                /*$postCode = $depa_id.''.$prov_id.''.$dist_id;

                if($customerAddress->getPostCode($postCode) == ''){

                    $customerAddress->setPostCode($postCode);
                }*/

                $this->addressRepository->save($customerAddress);

            }

            if($orderShippingAddress){ 
                $orderShippingAddress->setData(
                    QuoteExtraFields::DEPARTAMENTO_ID,
                    $depa_id
                ); 

                $orderShippingAddress->setData(
                    QuoteExtraFields::PROVINCIA_ID,
                    $prov_id
                ); 

                $orderShippingAddress->setData(
                    QuoteExtraFields::DISTRITO_ID,
                    $dist_id
                );

                $orderShippingAddress->setData(
                    QuoteExtraFields::PROGRAMADO,
                    $programado
                ); 
         
                $orderShippingAddress->setData(
                    QuoteExtraFields::DELIVERY_LOCATION,
                    $quoteShippingAddress->getData(QuoteExtraFields::DELIVERY_LOCATION)
                ); 

            }





        $order->setData('latitud', $quote->getLatitud());
        $order->setData('longitud', $quote->getLongitud());

        }
    }
}
