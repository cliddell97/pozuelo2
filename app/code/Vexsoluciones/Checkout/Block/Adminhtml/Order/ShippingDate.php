<?php

namespace Vexsoluciones\Checkout\Block\Adminhtml\Order;

use Magento\Sales\Model\Order;  

class ShippingDate extends \Magento\Backend\Block\Template
{
 
    protected $coreRegistry = null;
    protected $provincia;
    protected $distrito;
    protected $departamento;

    //protected $_template = 'order/view/facturacion_fields.phtml';
  
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Vexsoluciones\Checkout\Model\BrandFactoryProvincia $provincia,
        \Vexsoluciones\Checkout\Model\BrandFactoryDistrito $distrito,
        \Vexsoluciones\Checkout\Model\BrandFactoryDepartamento $departamento,
        array $data = []
    ) {
        $this->coreRegistry = $registry;
        $this->departamento = $departamento;
        $this->distrito = $distrito;
        $this->provincia = $provincia;
        $this->_isScopePrivate = true;  
        parent::__construct($context, $data);
    }


    public function getOrderId() 
    {
        
        $order = $this->coreRegistry->registry('current_order');
        
        $order_id = '';

        if(!$order){
 
        }
        else
        {
            $order_id = $order->getId();
        } 
        
        return $order_id;
    } 
 
    public function getQuoteId() 
    {
        
        $order = $this->coreRegistry->registry('current_order');
        
        $quote_id = '';

        if(!$order){
 
        }
        else
        {
            $quote_id = $order->getQuoteId();
        } 
        
        return $quote_id;
    } 

    public function getShippingDate(){

        $order = $this->coreRegistry->registry('current_order');
        
        if($order){

                $departamento = $order->getShippingAddress()->getData('departamento_id');
                $provincia = $order->getShippingAddress()->getData('provincia_id');
                $distrito = $order->getShippingAddress()->getData('distrito_id');
                $location = $order->getShippingAddress()->getData('delivery_location');

                $collection1 = $this->departamento->create()->load($departamento,"idDepa");
                $collection2 = $this->provincia->create()->load($provincia);
                $collection3 = $this->distrito->create()->load($distrito,"idDist");

                $departamento = ($collection1)?$collection1->getData('departamento'):"";
                $provincia = ($collection2)?$collection2->getData('provincia'):"";
                $distrito = ($collection3)?$collection3->getData('distrito'):"";

                return [
                    'departamento' => $departamento,
                    'provincia' => $provincia,
                    'distrito' => $distrito,
                    'location' => $location
                    ];
            


        }    

        return false;
    }
      
}
