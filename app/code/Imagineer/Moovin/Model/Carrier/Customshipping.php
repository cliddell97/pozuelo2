<?php

namespace Imagineer\Moovin\Model\Carrier;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DataObject;
use Magento\Shipping\Model\Carrier\AbstractCarrier;
use Magento\Shipping\Model\Carrier\CarrierInterface;
use Magento\Shipping\Model\Config;
use Magento\Shipping\Model\Rate\ResultFactory;
use Magento\Store\Model\ScopeInterface;
use Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory;
use Magento\Quote\Model\Quote\Address\RateResult\Method;
use Magento\Quote\Model\Quote\Address\RateResult\MethodFactory;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Psr\Log\LoggerInterface;
 
class Customshipping extends AbstractCarrier implements CarrierInterface
{

    private $cantonesEnGAM = array(
        '101','102','103','106','107','108','109','110','111','113','114','115','118',/*SJ*/
        '201','202','205', /*ALAJUELA*/
        '301','302','303','306','307','308', /*CARTAGO*/
        '401','402','403','404','405','406','407','408','409' /*HEREDIA*/
    );
	
    protected $scopeConfig;
    /**
     
    * Carrier's code
    *
    * @var string
    */
    
    protected $_code = 'moovin';
    /**
    * Whether this carrier has fixed rates calculation
    *
    * @var bool
    */
    
    protected $_isFixed = true;
    /**
    * @var ResultFactory
    */
    
    protected $_rateResultFactory;
    /**
    * @var MethodFactory
    */
    
    protected $_rateMethodFactory;
    /**
    * @param ScopeConfigInterface $scopeConfig
    * @param ErrorFactory $rateErrorFactory
    * @param LoggerInterface $logger
    * @param ResultFactory $rateResultFactory
    * @param MethodFactory $rateMethodFactory
    * @param array $data
    */

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        ErrorFactory $rateErrorFactory,
        LoggerInterface $logger,
        ResultFactory $rateResultFactory,
        MethodFactory $rateMethodFactory,
        array $data = []
    ) {
        $this->_rateResultFactory = $rateResultFactory;
        $this->_rateMethodFactory = $rateMethodFactory;
        parent::__construct($scopeConfig, $rateErrorFactory, $logger, $data);
    	$this->scopeConfig = $scopeConfig;
    }
    
    /**
    * Generates list of allowed carrier`s shipping methods
    * Displays on cart price rules page
    *
    * @return array
    * @api
    */
    
//Define the allowed methods
public function getAllowedMethods()
{
    //Fetch the methods from the config.
    $allowed = array(
        0 => array(
            'code' => 'moovin',
            'title' => 'Moovin'
        ),
        1 => array(
            'code' => 'moovinexpress',
            'title' => 'Moovin Express'
        ),
    );
    $arr = array();
    foreach ($allowed as $rate) {
        $arr[$rate['code']] = $rate['title'];
    }
    return $arr;
}

    /**
    * Collect and get rates for storefront
    *
    * @SuppressWarnings(PHPMD.UnusedFormalParameter)
    * @param RateRequest $request
    * @return DataObject|bool|null
    * @api
    */

    public function collectRates(RateRequest $request)
    {

        /* Make sure that Shipping method is enabled
        */
    	$scope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        if (!$this->isActive()) {
            return;
        }
		if (($this->scopeConfig->getValue('carriers/imagineer_moovin/active',$scope))) {

			$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
			$cart = $objectManager->get('\Magento\Checkout\Model\Cart'); 

			$writer = new \Zend\Log\Writer\Stream(BP.'/var/log/aproxEnvioMoovin.log');
			$logger = new \Zend\Log\Logger();
			$logger->addWriter($writer);
			
			//TODO cambiar return false por el una respuesta vacia y que despliegue mensaje de error (opcional)
			if(!$this->contains( '||',$request->getDestStreet())) return false;
				
			//Obtener coordenadas de la 3ra linea de address
			$latitud = explode(',', explode('||',$request->getDestStreet())[1])[0];
			$longitud = explode(',', explode('||',$request->getDestStreet())[1])[1];
			$logger->info("lat:".$latitud);
			$logger->info("lon:".$longitud);
			
			//Si el usuario no ha marcado un punto en el mapa entonces que no esté disponible
			if($latitud == "" || $longitud == "") return false;
			
									$logger->info("*********************:".$this->scopeConfig->getValue('carriers/imagineer_moovin/sandbox',$scope) );
			//Obtener credenciales de la configuracion
			$userInt = $this->scopeConfig->getValue('carriers/imagineer_moovin/userInt',$scope);
			$passwordInt = $this->scopeConfig->getValue('carriers/imagineer_moovin/passwordInt',$scope);
			$ws = $this->scopeConfig->getValue('carriers/imagineer_moovin/sandbox',$scope) ? "https://developer.moovin.me" : "https://moovin.me";

			//Conseguir token de Moovin
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
			$logger->info("res: ".$response);

			$err = curl_error($curl);
			curl_close($curl); 
			//TODO poner mensaje de error en Magento
			if ($err) {
				return false;
			}
			$token = explode('"',$response)[3];
			$logger->info("token: ".$token);

			//Validar que las coordenadas esten en area de cobertura
			//TODO validar las coordenadas del punto de origen
			$curl = curl_init();

			curl_setopt_array($curl, array(
				CURLOPT_URL => $ws."/moovinApiWebServices-1/rest/api/moovinEnterprise/partners/insideZoneCoverage?latitude=".$latitud."&longitude=".$longitud."&=",
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_ENCODING => "",
				CURLOPT_MAXREDIRS => 10,
				CURLOPT_TIMEOUT => 0,
				CURLOPT_FOLLOWLOCATION => true,
				CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				CURLOPT_CUSTOMREQUEST => "GET",
				CURLOPT_HTTPHEADER => array(
					"token: " . $token
				),
			));

			$response = curl_exec($curl);
			curl_close($curl);

			if(strpos($response,'This area is correct') === false) return false;

			//Estimar costo
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

			$items = $cart->getQuote()->getAllItems();
			foreach($items as $item) {
				$listProduct .= '
					{
						"quantity": '.$item->getQty().',
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
			$logger->info("msg:".$msg);

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

			//TODO mostrar error en Magento
			if($err != "") return false;

			//Averiguar el mas barato de cada metodo (express o ruta)
			$prices = explode('type":"', $response);
			$precioMax = 999999;
			$menorPrecio = $precioMax;
			$menorPrecioExpress = $precioMax;
			$deliveryTime = '0';
			$deliveryTimeExpress = '0';
			for($x = 0; $x < count($prices); $x++){

				$hileras = explode('"',$prices[$x]);

				if($hileras[0] == 'route'){
					$amount = explode(',',explode('amount":', $prices[$x])[1])[0];
					$deliveryTime = explode(',',explode('deliveryTime":', $prices[$x])[1])[0];
					if(floatval($amount) < $menorPrecio){
						$menorPrecio = floatval($amount);
					}
				}
				if($hileras[0] == 'Ondemand'){
					$amountExpress = explode(',',explode('amount":', $prices[$x])[1])[0];
					$deliveryTimeExpress = explode(',',explode('deliveryTime":', $prices[$x])[1])[0];
					if(floatval($amountExpress) < $menorPrecioExpress){
						$menorPrecioExpress = floatval($amountExpress);
					}
				}

			}
			$result = $this->_rateResultFactory->create();

			//Express

			if($menorPrecioExpress != $precioMax){
				
				if($this->scopeConfig->getValue('carriers/imagineer_moovin/moneda',$scope) == "USD"){
					$menorPrecioExpress = $menorPrecioExpress/$this->ventaColones();
				}

				$shippingRate = $this->scopeConfig->getValue('carriers/imagineer_moovin/price',$scope);
				$methodExpress = $this->_rateMethodFactory->create();


				$methodExpress->setCarrier('moovin');
				$methodExpress->setCarrierTitle($this->scopeConfig->getValue('carriers/imagineer_moovin/titleExpress',$scope));



				$methodExpress->setMethod('moovinexpress');
				$horas = $deliveryTimeExpress == '1' ? ' hora' : ' horas';
				$methodExpress->setMethodTitle($this->scopeConfig->getValue('carriers/imagineer_moovin/nameExpress',$scope)."\n aprox: ".$deliveryTimeExpress.$horas);
				$methodExpress->setPrice($menorPrecioExpress);
				$methodExpress->setCost($menorPrecioExpress);
				$result->append($methodExpress);	
			}
			//Ruta

			if($menorPrecio != $precioMax){
				if($this->scopeConfig->getValue('carriers/imagineer_moovin/moneda',$scope) == "USD"){
					$menorPrecio = $menorPrecio/$this->ventaColones();

				}

				$shippingRate = $this->scopeConfig->getValue('carriers/imagineer_moovin/price',$scope);
				$method = $this->_rateMethodFactory->create();

				$method->setCarrier('moovin');
				$method->setCarrierTitle($this->scopeConfig->getValue('carriers/imagineer_moovin/title',$scope));



				$method->setMethod('moovin');
				$horas = $deliveryTime == '1' ? ' hora' : ' horas';
				$method->setMethodTitle($this->scopeConfig->getValue('carriers/imagineer_moovin/name',$scope)."\n aprox: ".$deliveryTime.$horas);
				$method->setPrice($menorPrecio);
				$method->setCost($menorPrecio);
				$result->append($method);
			}

			return $result;
	    }
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
	
	//devuelve el equivalente de $precio en la moneda $moneda
	public function getPrice($moneda, $precio){
		if ($moneda == "USD") {
			return $precio*$this->ventaColones();
		}
		return $precio;
	}
	
	// returns true if $needle is a substring of $haystack
	function contains($needle, $haystack)
	{
		return strpos($haystack, $needle) !== false;
	}
	
	//retorna el costo de compra de CRC
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

	//retorna el costo de venta de CRC
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
