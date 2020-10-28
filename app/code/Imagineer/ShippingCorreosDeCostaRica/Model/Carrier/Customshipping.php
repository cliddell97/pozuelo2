<?php

namespace Imagineer\ShippingCorreosDeCostaRica\Model\Carrier;

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
    private $maxWeight = 30000;
    private $cantonesEnGAM = array(
	'101','102','103','106','107','108','109','110','111','113','114','115','118',/*SJ*/
	'201','202','205', /*ALAJUELA*/
	'301','302','303','306','307','308', /*CARTAGO*/
	'401','402','403','404','405','406','407','408','409' /*HEREDIA*/
    );
	protected $scopeConfig;    
    /*
        ws pruebas: http://amistad.correos.go.cr:82/wserPruebas/wsAppCorreos.wsAppCorreos.svc
        ws produccion: http://amistadpro.correos.go.cr:88/wserproduccion/wsAppCorreos.wsAppCorreos.svc
    */
    private $ws;

    /**
     
    * Carrier's code
    *
    * @var string
    */
    
    protected $_code = 'shippingcorreosdecostarica';
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
        parent::__construct($scopeConfig, $rateErrorFactory, $logger, $data);
        $this->_rateResultFactory = $rateResultFactory;
        $this->_rateMethodFactory = $rateMethodFactory;
 	$this->scopeConfig = $scopeConfig;
	}
    
    /**
    * Generates list of allowed carrier`s shipping methods
    * Displays on cart price rules page
    *
    * @return array
    * @api
    */
    
    public function getAllowedMethods()
    {
        return [$this->_code => $this->scopeConfig->getValue('carriers/imagineer_shippingcorreosdecostarica/name',\Magento\Store\Model\ScopeInterface::SCOPE_STORE)];
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
        /**
        * Make sure that Shipping method is enabled
        */
	$scope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
	$ws=$this->scopeConfig->getValue('carriers/imagineer_shippingcorreosdecostarica/webservice',$scope);
        $writer = new \Zend\Log\Writer\Stream(BP.'/var/log/aproxEnvio.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
	$logger->info("activo:".$this->scopeConfig->getValue('carriers/imagineer_shippingcorreosdecostarica/active',$scope)." ws:".$ws);
    
        if (!($this->scopeConfig->getValue('carriers/imagineer_shippingcorreosdecostarica/active',$scope))) {
            return false;
        }
	


        $zip = $request->getDestPostcode();
        
        $logger ->info("getDestPostcode:".$zip);
	//Correos calcula los pesos en gramos
	$weight = 0;
	//$addressData = "Pais: ".$request->getDestCountryId()." Ciudad: ".$request->getDestRegionId()." Calle: ".$request->getSestStreet()." Zip: ".$request->getDestPostcode();
	switch ($this->scopeConfig->getValue('carriers/imagineer_shippingcorreosdecostarica/unidadpeso',$scope)) {
    		case "kilogramos":
       			$weight = $request->getPackageWeight()*1000;
        	break;
    		case "libras":
        		$weight = $request->getPackageWeight()*454;
        	break;
	    	case "gramos":
        		$weight = $request->getPackageWeight();
        	break;
	}
	$logger->info("weight: ".$weight." zipDest: ".$zip);
	if($weight > $this->maxWeight) return false;

        if($zip != "" && $weight != ""){
            $shippingRate = $this->averiguarPrecio($weight, $zip, $ws);
	$logger->info("respuesta: ".$shippingRate);
	   // if(strpos($shippingRate, '<a:Cod_Respuesta>00') !== true) return false;
            $shippingRate = explode('<', explode('CostoColones>', $shippingRate)[1])[0]; //<a:CostoColones>XXXX,XX</a:CostoColones> a XXXX,XX
            if($shippingRate =="0" || $shippingRate == 0) return false; 
            $logger->info("Costo de envio: ".print_r($shippingRate, true)." peso: ".$weight." zip: ".$zip);
        }else{
	        return false;

        }
	
	if($this->scopeConfig->getValue('carriers/imagineer_shippingcorreosdecostarica/moneda',$scope) == "USD"){
		$shippingRate = floatval($shippingRate)/$this->ventaColones();

	}

        /** @var \Magento\Shipping\Model\Rate\Result $result */
        $result = $this->_rateResultFactory->create();
        $shippingPrice = $this->scopeConfig->getValue('carriers/imagineer_shippingcorreosdecostarica/price',$scope);
        $method = $this->_rateMethodFactory->create();

	if($shippingPrice != null && $shippingPrice != 0){
		$shippingRate = (float)$shippingRate+(float)$shippingPrice;
	}
        $logger->info("shippingprice: ". $shippingPrice. "shippingRate: ".$shippingRate);

        /**
        * Set carrier's method data
        */
        $method->setCarrier($this->_code);
        $method->setCarrierTitle($this->scopeConfig->getValue('carriers/imagineer_shippingcorreosdecostarica/title',$scope));
          
        /**
        * Displayed as shipping method under Carrier
        */

       	$method->setMethod($this->_code);

  	$method->setMethodTitle($this->scopeConfig->getValue('carriers/imagineer_shippingcorreosdecostarica/name',$scope));
	if(in_array(substr($zip, 0, 3), $this->cantonesEnGAM) && $this->scopeConfig->getValue('carriers/imagineer_shippingcorreosdecostarica/gratisGAM',$scope) == "1"){ 
	        $method->setPrice("0");
	}else{
	        $method->setPrice($shippingRate);
	}
	$method->setCost($shippingRate);

        $result->append($method);

            $logger->info("carrier name: ".$this->scopeConfig->getValue('carriers/imagineer_shippingcorreosdecostarica/name',$scope)."enGAM: ".($this->scopeConfig->getValue('carriers/imagineer_shippingcorreosdecostarica/gratisGAM',$scope)));

        return $result;
    } 

    public function averiguarPrecio(int $peso, string $zip, string $ws){
	$scope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;;
        $writer = new \Zend\Log\Writer\Stream(BP.'/var/log/aproxEnvio.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);

        $user = $this->scopeConfig->getValue('carriers/imagineer_shippingcorreosdecostarica/user',$scope);
        $pass = $this->scopeConfig->getValue('carriers/imagineer_shippingcorreosdecostarica/password',$scope);
        $zipRemitente = $this->scopeConfig->getValue('carriers/imagineer_shippingcorreosdecostarica/codigopostalremitente',$scope);
        $puerto = $this->scopeConfig->getValue('carriers/imagineer_shippingcorreosdecostarica/puerto',$scope);
	$msg = '<s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/"><s:Body><ccrMovilTarifaCCR xmlns="http://tempuri.org/"><resTarifa xmlns:a="http://schemas.datacontract.org/2004/07/wsAppCorreos" xmlns:i="http://www.w3.org/2001/XMLSchema-instance"><a:Cantidad>1</a:Cantidad><a:Pais>CR</a:Pais><a:Peso>'.$peso.'</a:Peso><a:Prioridad i:nil="true"/><a:Servicio>PYMEXPRESS</a:Servicio><a:TipoEnvio>1</a:TipoEnvio><a:ZonDestino>'.$zip.'</a:ZonDestino><a:ZonUbicacion>'.$zipRemitente.'</a:ZonUbicacion></resTarifa><User>'.$user.'</User><Pass>'.$pass.'</Pass></ccrMovilTarifaCCR></s:Body></s:Envelope>';
  	$logger->info("user:".$user. " pass:".$pass." puerto:".$puerto." zip:".$zipRemitente." ws:".$ws." msg:".$msg);

//return '<s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/"><s:Body><ccrMovilTarifaCCRResponse xmlns="http://tempuri.org/"><ccrMovilTarifaCCRResult xmlns:a="http://schemas.datacontract.org/2004/07/wsAppCorreos" xmlns:i="http://www.w3.org/2001/XMLSchema-instance"><a:Cod_Respuesta>00</a:Cod_Respuesta><a:CostoColones>1280,00</a:CostoColones><a:CostoDolares>0</a:CostoDolares><a:Mensaje_Respuesta>Importe generado para EMS PAQUETE</a:Mensaje_Respuesta></ccrMovilTarifaCCRResult></ccrMovilTarifaCCRResponse></s:Body></s:Envelope>';

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
              "Postman-Token: 3190431f-0e2f-4a3c-be0c-9f3cbef9a294",
              "SOAPAction: http://tempuri.org/IwsAppCorreos/ccrMovilTarifaCCR",
              "cache-control: no-cache"
            ),
        ));

        $response = curl_exec($curl); 
        $err = curl_error($curl); 
        curl_close($curl); 
        if ($err) {
            return "cURL Error #:" . $err;
        } else {
            return $response;
        }
    }

	public function compraColones(){
		$date = date("d/m/Y");
		
 $curl = curl_init(); curl_setopt_array($curl, array(
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
		
 $curl = curl_init(); curl_setopt_array($curl, array(
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
