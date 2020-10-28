<?php
namespace Imagineer\Moovin\Observer;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\ObserverInterface; 
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Captcha\Observer\CaptchaStringResolver;
use Magento\Quote\Api\Data\ShippingMethodInterface;

class RegistrarPedido implements ObserverInterface { 
	private $productRepository;	
	private $storeManager;
	private $scopeConfig;
	protected $checkoutSession;
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


	public function execute(\Magento\Framework\Event\Observer $observer){
		$order = $observer->getEvent()->getOrder();

		if($order->getShippingMethod() == 'moovin_moovin' || $order->getShippingMethod() == 'moovin_moovinexpress'){
		$scope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
		$customerId = $order->getCustomerId();
		$writer = new \Zend\Log\Writer\Stream(BP . '/var/log/registroEnviosMoovin.log');
		$logger = new \Zend\Log\Logger();
		$logger->addWriter($writer);
		$logger->info("Method: ".$this->method->getMethodTitle()."   ".$order->getShippingMethod());

	
		//*************************************************//
		//primero consigue el token de moovin 
		//*************************************************//
		$userInt = $this->scopeConfig->getValue('carriers/imagineer_moovin/userInt',$scope);
		$passwordInt = $this->scopeConfig->getValue('carriers/imagineer_moovin/passwordInt',$scope);
		$ws = $this->scopeConfig->getValue('carriers/imagineer_moovin/sandbox',$scope) ? "https://developer.moovin.me" : "https://moovin.me";
		$curl = curl_init();
		curl_setopt_array($curl, array(
			CURLOPT_URL => $ws."/moovinApiWebServices-1/rest/api/moovinEnterprise/partners/login",
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => "",
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 0,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => "POST",
			CURLOPT_POSTFIELDS =>'{"username":"'.$userInt.'","password":"'.$passwordInt.'"}',
			CURLOPT_HTTPHEADER => array(
					"Content-Type: application/json"
			),
		));
		$response = curl_exec($curl);
		$err = curl_error($curl);
		curl_close($curl); 
		//TODO mejorar el manejo de errores en los observers para que al cliente no le salga la pantalla blanca
		if ($err) {
			return false;
		}
		$token = explode('"',$response)[3];

		//*************************************************//
		//Luego se obtiene el ID de la estimacion 
		//*************************************************//

		//obtener coordenadas del 3er campo de la direccion
		$latitud = explode(',', explode('||',$order->getShippingAddress()->getStreet()[2])[1])[0];
		$longitud = explode(',', explode('||',$order->getShippingAddress()->getStreet()[2])[1])[1];
		
		//TODO mejorar el manejo de errores en los observers para que al cliente no le salga la pantalla blanca
		if($latitud == "" || $longitud == "") return false;
		
		$msg = 
		'{
			"pointCollect": {
				"latitude": '.$this->scopeConfig->getValue('carriers/imagineer_moovin/lat',$scope).',
				"longitude": '.$this->scopeConfig->getValue('carriers/imagineer_moovin/lon',$scope).'
			},
			"pointDelivery": {
				"latitude": '.$latitud.',
				"longitude": '.$longitud.'
			},
			"listProduct": [';
				
			$listProduct = "";
			$items = $order->getAllItems();
			foreach($items as $item){
				$listProduct .= '
					{
						"quantity": '.((int)$item->getQtyOrdered()).',
						"nameProduct": "'.$item->getName().'",
						"description": "'.$item->getName().'",
						"weight": '.$this->getWeight($this->scopeConfig->getValue('carriers/imagineer_moovin/unidadpeso',$scope), $item->getWeight()).',
						"price": '.$this->getPrice($this->scopeConfig->getValue('carriers/imagineer_moovin/moneda',$scope), $item->getPrice()).',
						"codeProduct": "'.$item->getSku().'"
					},';  
			}

			$msg .= substr($listProduct, 0, -1).'		],
				"ensure": true
			}';
	
			$logger->info("msg:".$msg.' lat: '. $order->getLatitud().' lon: '. $order->getLongitud());

			$curl = curl_init();
			curl_setopt_array($curl, array(
				CURLOPT_URL => $ws."/moovinApiWebServices-1/rest/api/ecommerceExternal/estimation",
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_ENCODING => "",
				CURLOPT_MAXREDIRS => 10,
				CURLOPT_TIMEOUT => 30,
				CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				CURLOPT_CUSTOMREQUEST => "POST",
				CURLOPT_POSTFIELDS => $msg,
				CURLOPT_HTTPHEADER => array(
				"Content-Type: application/json",
				"token: " . $token
				),
			));

			$response = curl_exec($curl);
			$err = curl_error($curl);

			curl_close($curl);

			$logger->info("res:".$response);
			$logger->info("error:".$err);

			//TODO mejorar el manejo de errores en los observers para que al cliente no le salga la pantalla blanca
			if($err != "") return false;

			//averiguar el mas barato dependiendo de si es moovin ruta o express
			$prices = explode('id":', $response);
			$metodo = $order->getShippingMethod() == 'moovin_moovin' ? 'route' : 'Ondemand';
			$menorPrecio = 999999; //limite de precio arbitrario
			$deliveryId = 0;
			for($x = 0; $x < count($prices); $x++){
				if(strpos($prices[$x],$metodo) !== false){
					$amount = explode(',',explode('amount":', $prices[$x])[1])[0];
					if(floatval($amount) < $menorPrecio){
						$menorPrecio = floatval($amount);
						$deliveryId = $x;
					}
				}

			}
			
			//TODO mejorar el manejo de errores en los observers para que al cliente no le salga la pantalla blanca
			if($menorPrecio == 999999 || $deliveryId == 0) return false;	
			$logger->info("MENSAJE: ".$response);
			$estimationId = explode(',', explode('idEstimation":', $response)[1])[0];


			/*************************************************/
			//Poner la orden
			/*************************************************/
			$fuente = $this->scopeConfig->getValue('carriers/imagineer_moovin/ubicacion',$scope);
			$destino = $order->getShippingAddress()->getCountryId()." - ".$province = $order->getShippingAddress()->getRegion()." - ".$county = $order->getShippingAddress()->getCity();
			$msg = '
			{
				"idEstimation":'.$estimationId.',
				"idDelivery":'.$deliveryId.',
				"idOrder":"'.$order->getIncrementId().'",
				"email":"'.$this->scopeConfig->getValue('carriers/imagineer_moovin/email',$scope).'",
				"emailAccount":"'.$this->scopeConfig->getValue('carriers/imagineer_moovin/email',$scope).'",
				"prepared":false,
				"pointCollect":{
					"latitude":'.$this->scopeConfig->getValue('carriers/imagineer_moovin/lat',$scope).',
					"longitude":'.$this->scopeConfig->getValue('carriers/imagineer_moovin/lon',$scope).',
					"locationAlias":"'.$fuente.'",
					"name":"'.$this->scopeConfig->getValue('carriers/imagineer_moovin/nombre',$scope).'",
						"phone":"'.$this->scopeConfig->getValue('carriers/imagineer_moovin/tel',$scope).'",
					"notes":""
				},
				"pointDelivery":{
					"latitude":'.$latitud.',
					"longitude":'.$longitud.',
					"locationAlias":"'.$destino.'",
					"name":"'.$order->getCustomerFirstName().' '.$order->getCustomerLastName().'",
						"phone":"'.$order->getShippingAddress()->getTelephone().'",
					"notes":""
				},
				"listProduct": ['.substr($listProduct, 0, -1).'		],
				"ensure": true
			}';
			
			$logger->info("msg:".$msg);

			$curl = curl_init();

			curl_setopt_array($curl, array(
				CURLOPT_URL => $ws."/moovinApiWebServices-1/rest/api/ecommerceExternal/createOrder",
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_ENCODING => "",
				CURLOPT_MAXREDIRS => 10,
				CURLOPT_TIMEOUT => 0,
				CURLOPT_FOLLOWLOCATION => true,
				CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				CURLOPT_CUSTOMREQUEST => "POST",
				CURLOPT_POSTFIELDS => $msg,
				CURLOPT_HTTPHEADER => array(
					"Content-Type: application/json",
					"token:" .$token
				),
			));

			$response = curl_exec($curl);
			curl_close($curl);

			//Poner Codigo QR como comentario en la orden
			$codigoQR = explode('","status',explode('orderQR":"',$response)[1])[0];
			$logger->info("respuesta: ".$response);
			$history = $order->addStatusHistoryComment($codigoQR);
            $history->save();
            $order->save();

		}   
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
	
	public function getWeight($unidad, $peso){
		$weight = 0;
		switch ($unidad) {
				case "kilogramos":
					$weight = $peso;
				break;
				case "libras":
					$weight = $peso*0.45;
				break;
				case "gramos":
					$weight = $peso*0.001;
				break;
		}
		return $weight;
	}
	public function getPrice($moneda, $precio){
		if ($moneda == "USD") {
			return $precio*$this->ventaColones();
		}
		return $precio;
	}

	public function compraColones(){
		$date = date("d/m/Y");	
		$curl = curl_init(); 
		curl_setopt_array($curl, array(
			CURLOPT_URL => "https://gee.bccr.fi.cr/Indicadores/Suscripciones/WS/wsindicadoreseconomicos.asmx?op=ObtenerIndicadoresEconomicosXML%0A",
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => "",
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 0,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => "POST",
			CURLOPT_POSTFIELDS =>'<?xml version="1.0" encoding="utf-8"?><soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/"><soap:Body><ObtenerIndicadoresEconomicosXML xmlns="http://ws.sdde.bccr.fi.cr"><Indicador>317</Indicador><FechaInicio>'.$date.'</FechaInicio><FechaFinal>'.$date.'</FechaFinal> <Nombre>Chris</Nombre><SubNiveles>N</SubNiveles><CorreoElectronico>cliddell@imagineercx.com</CorreoElectronico><Token>OI2RNLI00I</Token></ObtenerIndicadoresEconomicosXML></soap:Body></soap:Envelope>',
			CURLOPT_HTTPHEADER => array(
			"Content-Type: text/xml"
			), )); 
		$response = curl_exec($curl); 
		curl_close($curl); 
		return floatval(explode("NUM_VALOR&gt;",explode("&lt;/NUM_VALOR",$response)[0])[1]);
	}


	public function ventaColones(){
		$date = date("d/m/Y");		
		$curl = curl_init(); 
		curl_setopt_array($curl, array(
			CURLOPT_URL => "https://gee.bccr.fi.cr/Indicadores/Suscripciones/WS/wsindicadoreseconomicos.asmx?op=ObtenerIndicadoresEconomicosXML%0A",
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => "",
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 0,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => "POST",
			CURLOPT_POSTFIELDS =>'<?xml version="1.0" encoding="utf-8"?><soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/"><soap:Body><ObtenerIndicadoresEconomicosXML xmlns="http://ws.sdde.bccr.fi.cr"><Indicador>318</Indicador><FechaInicio>'.$date.'</FechaInicio><FechaFinal>'.$date.'</FechaFinal> <Nombre>Chris</Nombre><SubNiveles>N</SubNiveles><CorreoElectronico>cliddell@imagineercx.com</CorreoElectronico><Token>OI2RNLI00I</Token></ObtenerIndicadoresEconomicosXML></soap:Body></soap:Envelope>',
			CURLOPT_HTTPHEADER => array(
			"Content-Type: text/xml"
			), )); 
		$response = curl_exec($curl); 
		curl_close($curl); 
		return floatval(explode("NUM_VALOR&gt;",explode("&lt;/NUM_VALOR",$response)[0])[1]);
	}
}
