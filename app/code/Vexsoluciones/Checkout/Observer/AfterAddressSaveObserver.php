<?php

namespace Vexsoluciones\Checkout\Observer;

class AfterAddressSaveObserver implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var Customer
     */
    protected $_customerRepository;

    /**
     * @var \Magento\Customer\Api\Data\AddressInterface
     */
    protected $_addressFactory;

    /**
     * $_addressRepository
     * @var \Magento\Customer\Api\AddressRepository
     */
    protected $_addressRepository;
    protected $_request;
    protected $provincia;
    protected $distrito;
    protected $departamento;

    public function __construct(
        \Vexsoluciones\Checkout\Model\BrandFactoryProvincia $provincia,
        \Vexsoluciones\Checkout\Model\BrandFactoryDistrito $distrito,
        \Vexsoluciones\Checkout\Model\BrandFactoryDepartamento $departamento,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Customer\Model\AddressFactory $addressFactory,
        \Magento\Customer\Api\AddressRepositoryInterface $addressRepository,
        \Magento\Framework\App\RequestInterface $request
    )
    {
        $this->_customerRepository = $customerRepository;
        $this->_addressFactory     = $addressFactory;
        $this->_addressRepository  = $addressRepository;
        $this->_request  = $request;
        $this->departamento = $departamento;
        $this->distrito = $distrito;
        $this->provincia = $provincia;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $observer->getCustomerAddress();
        $id = $observer->getCustomerAddress()->getEntityId();
        $depa_id = $this->_request->getParam('departamento_id');
        $prov_id = $this->_request->getParam('provincia_id');
        $dist_id = $this->_request->getParam('distrito_id');
        $validar = $this->_request->getParam('validar');
        $pais = $this->_request->getParam('country_id');



        $depa_label = '';
        $prov_label = '';
        $dist_label = '';

        $email = $this->_request->getParam('email');

        $newCustomerCustomDataAddress = $this->_addressRepository->getById($id);

        if($email!=""){
            $arraypost = $newCustomerCustomDataAddress->getPostcode();
            $arraypost = explode('-', $arraypost);
            $depa_id = isset($arraypost[0])?$arraypost[0]:"0";
            $prov_id = isset($arraypost[1])?$arraypost[1]:"0";
            $dist_id = isset($arraypost[2])?$arraypost[2]:"0";

            $validar = 1;
        }


        if($validar==1){

            if($pais=="CR"){

                if($depa_id!="" && $depa_id!=null){
                    $newCustomerCustomDataAddress->setCustomAttribute('departamento_id',$depa_id); 
                    $newCustomerCustomDataAddress->setCustomAttribute('provincia_id',$prov_id); 
                    $newCustomerCustomDataAddress->setCustomAttribute('distrito_id',$dist_id);

                    $collection1 = $this->departamento->create()->load($depa_id,'idDepa');
                    $collection2 = $this->provincia->create()->load($prov_id,'idProv');
                    $collection3 = $this->distrito->create()->load($dist_id,'idDist');

                    $depa_label = ($collection1)?$collection1->getData('departamento'):"";
                    $prov_label = ($collection2)?$collection2->getData('provincia'):"";
                    $dist_label = ($collection3)?$collection3->getData('distrito'):"";

                    $newCustomerCustomDataAddress->setCustomAttribute('departamento_label',$depa_label); 
                    $newCustomerCustomDataAddress->setCustomAttribute('provincia_label',$prov_label); 
                    $newCustomerCustomDataAddress->setCustomAttribute('distrito_label',$dist_label);

                  //  $newCustomerCustomDataAddress->setPostcode($depa_id."-".$prov_id."-".$dist_id);
					$newCustomerCustomDataAddress->setPostcode($dist_id);
                    $newCustomerCustomDataAddress->setCity($dist_label.", ".$prov_label.", ".$depa_label);
                }
            }else{
                $newCustomerCustomDataAddress->setCustomAttribute('departamento_id',0); 
                $newCustomerCustomDataAddress->setCustomAttribute('provincia_id',0); 
                $newCustomerCustomDataAddress->setCustomAttribute('distrito_id',0);
                $newCustomerCustomDataAddress->setCustomAttribute('departamento_label',""); 
                $newCustomerCustomDataAddress->setCustomAttribute('provincia_label',""); 
                $newCustomerCustomDataAddress->setCustomAttribute('distrito_label',"");

                $newCustomerCustomDataAddress->setPostcode("0-0-0");
                $newCustomerCustomDataAddress->setCity($this->_request->getParam('citydefault'));
            }

            $this->_request->setParams(array('validar'=>0));
            $this->_addressRepository->save($newCustomerCustomDataAddress);

        }

    }

    
}
