<?php
namespace Imagineer\Moovin\Observer;

use Magento\Framework\Event\ObserverInterface;

class ProcessShipment implements ObserverInterface
{
    /**
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $shipment = $observer->getEvent()->getShipment();
        $order = $shipment->getOrder();

		//verificar si es uno de los metodos de moovin
        if($order->getShippingMethod() == 'moovin_moovin' || $order->getShippingMethod() == 'moovin_moovinexpress'){
			$writer = new \Zend\Log\Writer\Stream(BP . '/var/log/envioQRMoovin.log');
			$logger = new \Zend\Log\Logger();
			$logger->addWriter($writer);
			$logger->info($order->getShippingMethod());

			$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
			$conf = $objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface');
			$scope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;

			$orderComment = [];
			$index = 0;
			$qrCode = "";
			//Buscar el comentario del codigo qr
			//TODO Mejorar este ciclo para hacer match con el comentario del QR ||...
			foreach ($order->getStatusHistoryCollection() as $status) {
    			if ($status->getComment()) {
					if(substr($status->getComment(), 0, 9) == 'idPackage'){
						$qrCode = $status->getComment();
						break;
					}
				}
			}
			$logger->info("QR CODE: " . $qrCode);
			//Preparar la plantilla del correo
			$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $transportBuilder = $objectManager->get('\Magento\Framework\Mail\Template\TransportBuilder');
            $datos = [
                'orden' =>  $order->getIncrementId(),
                'cliente' => $order->getCustomerName(),
                'qr' => $qrCode
            ];

            $postObject = new \Magento\Framework\DataObject();
            $postObject->setData($datos);

            $transport = $transportBuilder
                ->setTemplateIdentifier('moovinqr_template')
                ->setTemplateOptions(['area' => \Magento\Framework\App\Area::AREA_FRONTEND, 'store' => \Magento\Store\Model\Store::DEFAULT_STORE_ID])
                ->setTemplateVars(['data' => $postObject])
                ->setFrom(['name' => 'QR MOOVIN','email' => $conf->getValue('carriers/imagineer_moovin/email',$scope)])
                ->addTo([$conf->getValue('carriers/imagineer_moovin/emailBodega',$scope), 'cliddell@imagineer.co'])
                ->getTransport();
            $transport->sendMessage();
			$logger->info("Correo enviado> ".$qrCode);

			//conseguir token de moovin
			$packageID = explode('>', explode('||',$qrCode)[0])[1];
			$userInt = $conf->getValue('carriers/imagineer_moovin/userInt',$scope);
			$passwordInt = $conf->getValue('carriers/imagineer_moovin/passwordInt',$scope);
			$ws = $conf->getValue('carriers/imagineer_moovin/sandbox',$scope) ? "https://developer.moovin.me" : "https://moovin.me";
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
			if ($err) {
				return false;
			}
			$token = explode('"',$response)[3];

			//Llamar al Moover a que venga a recoger el pedido
			//TODO poner opcion de llamar al Moover en Magento o a traves de la aplicación de Moovin
			//Por ahora solo se llama al Moover cuando se realiza el Shipment en Magento
			$curl = curl_init();

			curl_setopt_array($curl, array(
				CURLOPT_URL => $ws."/moovinApiWebServices-1/rest/api/ecommerceExternal/completeOrder",
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_ENCODING => "",
				CURLOPT_MAXREDIRS => 10,
				CURLOPT_TIMEOUT => 0,
				CURLOPT_FOLLOWLOCATION => true,
				CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				CURLOPT_CUSTOMREQUEST => "POST",
				CURLOPT_POSTFIELDS =>'{"idPackage":'.$packageID.'}',
				CURLOPT_HTTPHEADER => array(
					"token: ".$token,
					"Content-Type: application/json"
				),
			));

			$response = curl_exec($curl);
			curl_close($curl);
			$logger->info($response);

		}
    }
}