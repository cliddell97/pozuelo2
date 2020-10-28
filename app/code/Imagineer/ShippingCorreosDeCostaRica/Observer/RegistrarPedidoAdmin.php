<?php
namespace Imagineer\ShippingCorreosDeCostaRica\Observer;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\ObserverInterface; 
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Captcha\Observer\CaptchaStringResolver;
use Magento\Quote\Api\Data\ShippingMethodInterface;

class RegistrarPedidoAdmin implements ObserverInterface { 
	private $productRepository;	
	private $storeManager;
	private $scopeConfig;
	protected $checkoutSession;
  /*
      ws pruebas: http://amistad.correos.go.cr:82/wserPruebas/wsAppCorreos.wsAppCorreos.svc
      ws produccion: http://amistadpro.correos.go.cr:88/wserproduccion/wsAppCorreos.wsAppCorreos.svc
  */
  private $ws;
private $method;


/**
* @var \Magento\Sales\Model\Order\Shipment\TrackFactory
*/
protected $_shipmentTrackFactory;
 
/**
* @var \Magento\Sales\Model\Order\ShipmentFactory
*/
protected $_shipmentFactory;
 
/**
 * @var \Magento\Framework\DB\TransactionFactory
 */
protected $_transactionFactory;
 
/**
* @var \Magento\Sales\Api\OrderRepositoryInterface
*/
protected $_orderRepository;
 

public function __construct(ShippingMethodInterface $interf, \Magento\Catalog\Api\ProductRepositoryInterface $productRepository, \Magento\Store\Model\StoreManagerInterface $storeManager, ScopeConfigInterface $scopeConfig, \Magento\Catalog\Model\Session $checkoutSession, \Magento\Sales\Model\Order\Shipment\TrackFactory $shipmentTrackFactory, \Magento\Sales\Model\Order\ShipmentFactory $shipmentFactory, \Magento\Framework\DB\TransactionFactory $transactionFactory, \Magento\Sales\Api\OrderRepositoryInterface $orderRepository){
	$this->productRepository = $productRepository;
	$this->storeManager = $storeManager;
	$this->method = $interf;
        $this->scopeConfig = $scopeConfig;
        $this->checkoutSession = $checkoutSession;
      $this->_shipmentTrackFactory = $shipmentTrackFactory;
      $this->_shipmentFactory = $shipmentFactory;
      $this->_transactionFactory = $transactionFactory;
      $this->_orderRepository = $orderRepository;
}

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $shipment = $observer->getEvent()->getShipment();

        $order = $shipment->getOrder();
      $ws=$this->scopeConfig->getValue('carriers/imagineer_shippingcorreosdecostarica/webservice',\Magento\Store\Model\ScopeInterface::SCOPE_STORE);
 
    if($order->getShippingMethod() == 'shippingcorreosdecostarica_shippingcorreosdecostarica' && !$this->scopeConfig->getValue('carriers/imagineer_shippingcorreosdecostarica/automatico',\Magento\Store\Model\ScopeInterface::SCOPE_STORE)){
      $customerId = $order->getCustomerId();
      $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/registroEnviosCCR.log');
      $logger = new \Zend\Log\Logger();
      $logger->addWriter($writer);

      $logger->info("ENVIO MANUAL Method: ".$this->method->getMethodTitle());

      //llama ws de correos para generar guia
      $numGuia = 0;
      $user=$this->scopeConfig->getValue('carriers/imagineer_shippingcorreosdecostarica/user',\Magento\Store\Model\ScopeInterface::SCOPE_STORE);
      $pass=$this->scopeConfig->getValue('carriers/imagineer_shippingcorreosdecostarica/password',\Magento\Store\Model\ScopeInterface::SCOPE_STORE);
      $puerto=$this->scopeConfig->getValue('carriers/imagineer_shippingcorreosdecostarica/puerto',\Magento\Store\Model\ScopeInterface::SCOPE_STORE);
      $tipoCliente=$this->scopeConfig->getValue('carriers/imagineer_shippingcorreosdecostarica/tipocliente',\Magento\Store\Model\ScopeInterface::SCOPE_STORE);
      $servicioID=$this->scopeConfig->getValue('carriers/imagineer_shippingcorreosdecostarica/servicioid',\Magento\Store\Model\ScopeInterface::SCOPE_STORE);
      $usuarioID=$this->scopeConfig->getValue('carriers/imagineer_shippingcorreosdecostarica/usuarioid',\Magento\Store\Model\ScopeInterface::SCOPE_STORE);
      $CodCliente=$this->scopeConfig->getValue('carriers/imagineer_shippingcorreosdecostarica/clienteid',\Magento\Store\Model\ScopeInterface::SCOPE_STORE);
      $telR = $this->scopeConfig->getValue('carriers/imagineer_shippingcorreosdecostarica/telefonoremitente',\Magento\Store\Model\ScopeInterface::SCOPE_STORE);
      $dirR =$this->scopeConfig->getValue('carriers/imagineer_shippingcorreosdecostarica/direccionremitente',\Magento\Store\Model\ScopeInterface::SCOPE_STORE);
      $zipR = $this->scopeConfig->getValue('carriers/imagineer_shippingcorreosdecostarica/zipremitente',\Magento\Store\Model\ScopeInterface::SCOPE_STORE);
      $nomTienda = $this->scopeConfig->getValue('carriers/imagineer_shippingcorreosdecostarica/nombretienda',\Magento\Store\Model\ScopeInterface::SCOPE_STORE);
      $montoFlete = $this->scopeConfig->getValue('carriers/imagineer_shippingcorreosdecostarica/montoflete',\Magento\Store\Model\ScopeInterface::SCOPE_STORE);

      $msg='<s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/"><s:Body><ccrGenerarGuia xmlns="http://tempuri.org/"><Datos xmlns:a="http://schemas.datacontract.org/2004/07/wsAppCorreos" xmlns:i="http://www.w3.org/2001/XMLSchema-instance"><a:CodCliente>'.$CodCliente.'</a:CodCliente><a:TipoCliente>'.$tipoCliente.'</a:TipoCliente></Datos><User>'.$user.'</User><Pass>'.$pass.'</Pass></ccrGenerarGuia></s:Body></s:Envelope>';
      $msg = str_replace("&","y",$msg);
      $logger->info($msg);
      $curl = curl_init(); 
      curl_setopt_array($curl, array(
      CURLOPT_PORT => $puerto,
      CURLOPT_URL => $ws,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 30,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "POST",
      CURLOPT_POSTFIELDS => $msg,
      CURLOPT_HTTPHEADER => array(
        "Content-Type: text/xml",
        "Postman-Token: 30ba7515-f9c7-4f5e-99a2-82e1856cb86b,91d7e6fd-db51-4c2b-a772-b74e02094815",
        "SOAPAction: http://tempuri.org/IwsAppCorreos/ccrGenerarGuia",
        "cache-control: no-cache"
        ),
      ));
      $response = curl_exec($curl);

 
      $err = curl_error($curl);
      curl_close($curl); 
      if ($err) {
        $numGuia = "error";
      } else {
        $numGuia =  $response;
      }
      if($numGuia != "error"){
        $logger->info("Respuesta: ".$numGuia);
        $numGuia = explode('<', explode('ListadoXML>', $numGuia)[1])[0];
        $logger->info("numGuia: ".$numGuia);
//	$this->sessionCheckout->setGuia($numGuia);

        //generar pedido con correos de costa rica
        $billingAddress = $order->getBillingAddress();
        $dir = implode(", ",$billingAddress->getStreet());
        $nomCliente = $billingAddress->getFirstname() . ' ' .  $billingAddress->getLastname();
  
      $tel = $billingAddress->getTelephone();
        $zip = $billingAddress->getPostcode();
        $obs = ""; 
        $peso = 0;
        $items = $order->getAllItems();

        foreach($items as $item){
          $obs .= ($item->getName()) . ", ";
          $peso += $this->productRepository->get($item->getSku())->getWeight(); //pasarlo a gramos
        }
	$peso = $this->calcularPeso($peso);

        $fecha = date('d/m/Y');
        $logger->info("datos: ".$dir." ".$nomCliente." ".$tel." ".$zip." ".$obs." ".$peso);	
        $msg ='<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:tem="http://tempuri.org/" xmlns:wsap="http://schemas.datacontract.org/2004/07/wsAppCorreos"><soapenv:Header/><soapenv:Body><tem:ccrRegistroEnvio><tem:ccrReqEnvio>'
                                .'<wsap:Cliente>'.$CodCliente.'</wsap:Cliente><wsap:Envio><wsap:DEST_APARTADO>'.$zip.'</wsap:DEST_APARTADO><wsap:DEST_DIRECCION>'.$dir.'</wsap:DEST_DIRECCION>'
                                .'<wsap:DEST_NOMBRE>'.$nomCliente.'</wsap:DEST_NOMBRE><wsap:DEST_PAIS>CR</wsap:DEST_PAIS><wsap:DEST_TELEFONO>'.$tel.'</wsap:DEST_TELEFONO>'
                                .'<wsap:DEST_ZIP>'.$zip.'</wsap:DEST_ZIP>'
                                .'<wsap:ENVIO_ID>'.$numGuia.'</wsap:ENVIO_ID><wsap:ID_DISTRITO_DESTINO>'.$zip.'</wsap:ID_DISTRITO_DESTINO><wsap:MONTO_FLETE>'.$montoFlete.'</wsap:MONTO_FLETE>'
                                .'<wsap:OBSERVACIONES>'.$obs.'</wsap:OBSERVACIONES><wsap:PESO>'.$peso.'</wsap:PESO><wsap:SEND_DIRECCION>'.$dirR.'</wsap:SEND_DIRECCION>'
                                .'<wsap:SEND_NOMBRE>'.$nomTienda.'</wsap:SEND_NOMBRE><wsap:SEND_TELEFONO>'.$telR.'</wsap:SEND_TELEFONO><wsap:SEND_ZIP>'.$zipR.'</wsap:SEND_ZIP>'
                                .'<wsap:SERVICIO>2.3.2</wsap:SERVICIO><wsap:USUARIO_ID>'.$usuarioID.'</wsap:USUARIO_ID></wsap:Envio></tem:ccrReqEnvio>'
                                .'<tem:User>'.$user.'</tem:User><tem:Pass>'.$pass.'</tem:Pass></tem:ccrRegistroEnvio></soapenv:Body></soapenv:Envelope>';

        $msg = str_replace("&","y",$msg);    
	$logger->info($msg);	    
	$curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_PORT => $puerto,
        CURLOPT_URL => $ws,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => $msg,
        CURLOPT_HTTPHEADER => array(
            "Content-Type: text/xml",
            "Postman-Token: c5be9fd8-42be-45e8-aa45-0e9fc9928386,0f945dec-3cd3-4daa-8c69-c6bcf4686c29",
            "SOAPAction: http://tempuri.org/IwsAppCorreos/ccrRegistroEnvio",
            "cache-control: no-cache"
          ),
        ));
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        if ($err) {
          $respuesta = "cURL Error #:" . $err;
        } else {
          $respuesta = $response;
        }
        $logger->info($respuesta);
      }
	if(explode('<', explode('Cod_Respuesta>', $respuesta)[1])[0] == "00"){

$data = array(
'carrier_code' => 'CCR',
'title' => 'Correos de Costa Rica',
'number' => $numGuia, 
);

$shipment->getOrder()->setIsInProcess(true);

try {
// Save created shipment and order
$objectManager = \Magento\Framework\App\ObjectManager::getInstance();

$track = $objectManager ->create('Magento\Sales\Model\Order\Shipment\TrackFactory')->create()->addData($data);
$shipment->addTrack($track)->save();
$shipment->save();
$shipment->getOrder()->save();

// Send email
$objectManager->create('Magento\Shipping\Model\ShipmentNotifier')
->notify($shipment);

$shipment->save();
} catch (\Exception $e) {
throw new \Magento\Framework\Exception\LocalizedException(
__($e->getMessage())
);
}

	}
}
  }

   function calcularPeso($peso){
	if($peso == "0" || $peso == 0) return 0;
        switch ($this->scopeConfig->getValue('carriers/imagineer_shippingcorreosdecostarica/unidadpeso', \Magento\Store\Model\ScopeInterface::SCOPE_STORE)){
                case "kilogramos":
                        return $peso*1000;
                break;
                case "libras":
                        return $peso*454;
                break;
                case "gramos":
                        return $peso;
                break;
        }
   }


	public function ventaColones(){
                $date = date("d/m/Y");

 $curl = curl_init(); curl_setopt_array($curl, array(
  CURLOPT_URL =>"https://gee.bccr.fi.cr/Indicadores/Suscripciones/WS/wsindicadoreseconomicos.asmx?op=ObtenerIndicadoresEconomicosXML%0A",
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => "",
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 0,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => "POST",
  CURLOPT_POSTFIELDS =>'<?xml version="1.0" encoding="utf-8"?><soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/"><soap:Body><ObtenerIndicadoresEconomicosXML xmlns="http://ws.sdde.bccr.fi.cr"><Indicador>318</Indicador><FechaInicio>'.$date.'</FechaInicio><FechaFinal>'.$date.'</FechaFinal> <Nombre>Chris</Nombre><SubNiveles>N</SubNiveles><CorreoElectronico>cliddell@imagineercx.com</CorreoElectronico><Token>OI2RNLI00I</Token></ObtenerIndicadoresEconomicosXML></soap:Body></soap:Envelope>',
  CURLOPT_HTTPHEADER => array(
    "Content-Type: text/xml"
  ), ));
                $response = curl_exec($curl);
                curl_close($curl);
                return floatval(explode("NUM_VALOR&gt;",explode("&lt;/NUM_VALOR",$response)[0])[1]);
        }



	protected function createShipment($order, $trackingNumber)
	{
	    try {
	        if ($order){
	            $data = array(array(
	                'carrier_code' => $order->getShippingMethod(),
	                'title' => $order->getShippingDescription(),
	                'number' => $trackingNumber,
	            ));
	            $shipment = $this->prepareShipment($order, $data);
	            if ($shipment) {
	                $order->setIsInProcess(true);
	                $order->addStatusHistoryComment('Automatically SHIPPED', false);
	                $transactionSave =  $this->_transactionFactory->create()->addObject($shipment)->addObject($shipment->getOrder());
	                $transactionSave->save();
	            }
	            return $shipment;
	        }
	    } catch (\Exception $e) {
	        throw new \Magento\Framework\Exception\LocalizedException(
	            __($e->getMessage())
	        );
	    }
	}
 
	/**
	* @param $order \Magento\Sales\Model\Order
	* @param $track array
	* @return $this
	*/
	protected function prepareShipment($order, $track)
	{
	   $shipment = $this->_shipmentFactory->create(
	       $order,
	       $this->prepareShipmentItems($order),
	       $track
	   );
	   return $shipment->getTotalQty() ? $shipment->register() : false;
	}
	 
	/**
	* @param $order \Magento\Sales\Model\Order
	* @return array
	*/
	protected function prepareShipmentItems($order)
	{
	   $items = [];
	 
	   foreach($order->getAllItems() as $item) {
	       $items[$item->getItemId()] = $item->getQtyOrdered();
	   }
	   return $items;
	}
}
