<?php
namespace Vexsoluciones\Checkout\Model;

use Vexsoluciones\Checkout\Api\CheckoutInterface;

use Magento\Directory\Model\ResourceModel\Country\CollectionFactory as CountryCollectionFactory;
 
class CheckoutApi implements CheckoutInterface
{
    protected $provincia;
    protected $distrito;
    protected $departamento;
    protected $scopeConfig;
    protected $_countryCollectionFactory;

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Vexsoluciones\Checkout\Model\BrandFactoryProvincia $provincia,
        \Vexsoluciones\Checkout\Model\BrandFactoryDistrito $distrito,
        CountryCollectionFactory $countryCollectionFactory,
        \Vexsoluciones\Checkout\Model\BrandFactoryDepartamento $departamento
    )
    {
        $this->departamento = $departamento;
        $this->distrito = $distrito;
        $this->provincia = $provincia;
        $this->scopeConfig = $scopeConfig;
        $this->_countryCollectionFactory = $countryCollectionFactory;
    }
    /**
     * Returns greeting message to user
     *
     * @api
     * @param string $name Users name.
     * @return string Greeting message with users name.
     */
    public function listardepartamentos($pais='') {
        $array = array();
        $collection = $this->departamento->create()->getCollection();
        $collection->addFieldToFilter('pais' , $pais);
        foreach ($collection as $key) {
            $array[] = array("id"=>$key->getData('idDepa'),"nombre"=>$key->getDepartamento());
        }
        return $array;
    }
    public function listarprovincias($departamento=''){
        $array = array();
        $collection = $this->provincia->create()->getCollection();
        $collection->addFieldToFilter('idDepa' , $departamento);
        foreach ($collection as $key) {
            $array[] = array("id"=>$key->getData('idProv'),"nombre"=>$key->getProvincia());
        }
        return $array;
    }
    
    public function listardistritos($provincia=''){
        $array = array();
        $collection = $this->distrito->create()->getCollection();
        $collection->addFieldToFilter('idProv' , $provincia);
        foreach ($collection as $key) {
            $array[] = array("id"=>$key->getData('idDist'),"nombre"=>$key->getDistrito());
        }
        return $array;
    }
    public function listartiendas() {
        $tienda = $this->scopeConfig->getValue('vexsolucionescheckout/general/tienda');
        $array = array("tienda"=>$tienda);
        return $array;
    }
    public function listarpaises(){
        $array = array();
        $collection = $this->_countryCollectionFactory->create()->loadByStore();
        $collection->addFieldToSelect('*');
        foreach ($collection as $country) {
            if($country->getName()!="") {
                $array[] = array("id"=>$country->getCountryId(),"nombre"=>$country->getName());
            }
        }
        return $array;
    }
    
    
}