<?php
/**
 * Copyright © 2017 remmote.com
 * */

namespace Remmote\Credomaticpayment\Model\Payment;

class Credomatic extends \Magento\Payment\Model\Method\Cc
{
    const METHOD_CODE = 'credomatic';

    /**
     * Payment code
     *
     * @var string
     */
    protected $_code = self::METHOD_CODE;

    /**
     * @var \Remmote\Credomaticpayment\Helper\Data
     */
    public $helper;

    protected $_isGateway               = false;
    protected $_canOrder                = false;
    protected $_canAuthorize            = true;
    protected $_canCapture              = true;
    protected $_canCapturePartial       = false;
    protected $_canRefund               = false;
    protected $_canRefundInvoicePartial = false;
    protected $_canReviewPayment        = true;
    protected $_canUseInternal          = false;
    protected $_canUseCheckout          = true;
    
    public function __construct(\Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \Magento\Framework\Module\ModuleListInterface $moduleList,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Remmote\Credomaticpayment\Helper\Data $helper,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\UrlInterface $urlBuilder,
        array $data = array()
    ) {
        $this->_code = 'credomatic';
        $this->helper = $helper;
        $this->_objectManager = $objectManager;
        $this->_messageManager = $messageManager;
        $this->_storeManager = $storeManager;
        $this->_checkoutSession = $checkoutSession;
        $this->_urlBuilder = $urlBuilder;
        parent::__construct(
            $context, $registry, $extensionFactory, $customAttributeFactory,
            $paymentData, $scopeConfig, $logger, $moduleList, $localeDate, null,
            null, $data
        );
    }

    /**
     * Check whether there are CC types set in configuration
     *
     * @param \Magento\Quote\Api\Data\CartInterface|null $quote
     * @return bool
     */
    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
        return \Magento\Payment\Model\Method\AbstractMethod::isAvailable($quote) && $this->helper->isActive();
    }

    /**
     * Authorize payment abstract method
     *
     * @param \Magento\Framework\DataObject|InfoInterface $payment
     * @param float $amount
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     * @author smoreno91
     * @date   2017-12-03
     * 
     */
    public function authorize(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        if ($amount <= 0) {
            throw new \Magento\Framework\Exception\LocalizedException(__('The authorize action is not available.'));
        }

        //Attempt to authorize payment
        $result = $this->_processPayment($payment, $amount, \Magento\Payment\Model\Method\AbstractMethod::ACTION_AUTHORIZE);

        if(!$result['success']){
            // throw new \Exception($result['msg']);
            throw new \Magento\Framework\Exception\LocalizedException(__($result['msg']));
        }

        //If card needs verifcation (3D secure), set payment as pending
        if($this->_checkoutSession->getSecureData()) {
            $payment->setIsTransactionPending(true);
        }
        return $this;
    }

    /**
     * Capture payment abstract method
     *
     * @param \Magento\Framework\DataObject|InfoInterface $payment
     * @param float $amount
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     * @author smoreno91
     * @date   2017-12-03
     */
    public function capture(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {

        if (!$this->canCapture()) {
            throw new \Magento\Framework\Exception\LocalizedException(__('The capture action is not available.'));
        }
        if ($amount <= 0) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Invalid amount for authorization.'));
        }
        // Has the payment already been authorized?
        if ($payment->getLastTransId()) {
            
            $this->_processTransaction($payment, $amount, $payment->getLastTransId(), 'capture');

        //Attempt to create sale transaction...
        } else {

            //Attempt to create a 'sale' transaction
            $result = $this->_processPayment($payment, $amount, \Magento\Payment\Model\Method\AbstractMethod::ACTION_AUTHORIZE_CAPTURE);

            if (!$result['success']) {
                // throw new \Exception($result['msg']);
                throw new \Magento\Framework\Exception\LocalizedException(__($result['msg']));
            }

            // If card needs verifcation (3D secure), set payment as pending
            if($this->_checkoutSession->getSecureData()) {
                $payment->setIsTransactionPending(true);
            }
        }
        // exit;
        return $this;
    }

    /**
     * Process existing transaction
     * Used to capture an existing transaction
     * @param  Varien_Object $payment
     * @param  [type] $amount
     * @param  [type] $transactionId
     * @param  [type] $transactionType
     * @return [type]
     * @author smoreno91
     * @date   2017-12-03
     */
    public function _processTransaction($payment, $amount, $transactionId, $transactionType) {
        $order = $payment->getOrder();

        //Preparing data    
        $amount             = number_format($amount, 2, '.', '');
        $orderIncrementId   = $order->getIncrementId();
        $time               = time();
        $keyId              = $this->getConfigData('key_id');
        $key                = $this->getConfigData('key');
        $hash               = $this->helper->getHash($orderIncrementId, $amount, $time, $key);

        $transactionData = array(
            'type'          => $transactionType,
            'key_id'        => $keyId,
            'hash'          => $hash,
            'time'          => $time,
            'redirect'      => $this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_WEB).'credomaticpayment/notification/response',
            'Transactionid' => $transactionId,
            'amount'        => $amount,
            'orderid'       => $orderIncrementId,
        );

        // Preparing params
        $queryString = "";
        foreach($transactionData as $key => $value) {
            $queryString .= $key.'='.urlencode($value).'&';
        } 

        // Calling credomatic API and processing response
        try {

            $response = $this->_apiCall($queryString, $transactionData); 

            return $this->processPaymentResponse($response, $payment);

        } catch (\Exception $e) {
            $msg = $e->getMessage();
            $this->helper->logTransaction($msg);
            throw new \Exception($msg);
        }
    }

    /**
     * Process checkout payment
     * @param  [type] $payment
     * @param  [type] $amount
     * @param  [type] $transactionType
     * @return [type]
     * @author smoreno91
     * @date   2017-12-03 
     */
    public function _processPayment($payment, $amount, $transactionType) {
        
        //Define credomatic transaction type
        switch ($transactionType) {
            case \Magento\Payment\Model\Method\AbstractMethod::ACTION_AUTHORIZE_CAPTURE:
                $transactionType = 'sale';
                break;
            case \Magento\Payment\Model\Method\AbstractMethod::ACTION_AUTHORIZE:
                $transactionType = 'auth';
                break;
            default:
                $transactionType = 'auth';
                break;
        }

        // Preparing data for API request
        $order              = $payment->getOrder();
        $billingaddress     = $order->getBillingAddress();
        $amount             = number_format($amount, 2, '.', '');
        $orderIncrementId   = $order->getIncrementId();
        $time               = time();
        $ccexp              = $payment->getCcExpMonth().'-'.substr($payment->getCcExpYear(), -2);
        $keyId              = $this->getConfigData('key_id');
        $key                = $this->getConfigData('key');
        $hash               = $this->helper->getHash($orderIncrementId, $amount, $time, $key);

        $transactionData = array(
            'type'            => $transactionType,
            'key_id'          => $keyId,
            'hash'            => $hash,
            'time'            => $time,
            'orderid'         => $orderIncrementId,
            'redirect'        => $this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_WEB).'credomaticpayment/notification/response',
            'ccnumber'        => $payment->getCcNumber(),
            'ccexp'           => $ccexp,
            'amount'          => $amount,
            'cvv'             => $payment->getCcCid(),
            'payment'         => 'creditcard',
            'ipaddress'       => $_SERVER['REMOTE_ADDR'],
            'firstname'       => $payment->getCcOwner(),
            'address1'        => $billingaddress->getData('street'),
            'city'            => $billingaddress->getData('city'),
            'state'           => $billingaddress->getData('region'),
            'zip'             => $billingaddress->getData('postcode'),
            'country'         => $billingaddress->getData('country_id'),
            'phone'           => $billingaddress->getData('telephone'),
            'email'           => $billingaddress->getData('email'),
            'bill_firstname'  => $billingaddress->getData('firstname'),
            'bill_lastname'   => $billingaddress->getData('lastname'),
        );

        //Getting processor ID if merchant has more than one accout with Credomatic
        if($this->helper->isMulticurrencyEnabled()){
            $transactionData['processor_id'] = $this->helper->getProcessorId();
        }

        // Preparing params
        $queryString = "";
        foreach ($transactionData as $key => $value) {
            $queryString .= $key.'='.urlencode($value).'&';
        }

        // Calling credomatic API and processing response
        try {

            $response = $this->_apiCall($queryString, $transactionData); 

            //Check if 3D secure (Verify by Visa and secure Mastercard)
            if (!empty($response['threedsecure'])) {

                //Set verify Form in session
                $this->_checkoutSession->setSecureData($response['threedsecure']);
            
                $result['success'] = true;

            } else {

                // Handle Credomatic response
                $result = $this->processPaymentResponse($response, $payment);
            }

            return $result;

        } catch (\Exception $e) {
            $msg = $e->getMessage();
            $this->helper->logTransaction($msg);
            throw new \Exception($msg);
        }
    }

    /**
    * Calling Credomatic API
    * @param  [type]     $queryString
    * @return [type]
    * @author edudeleon
    * @date   2017-03-31
    */
    public function _apiCall($queryString, $transactionData) {
        $url = $this->getConfigData('credomatic_endpoint') ? $this->getConfigData('credomatic_endpoint') : 'https://credomatic.compassmerchantsolutions.com/api/transact.php';
        $ch  = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $queryString);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        //Execute post and getting API result
        $curlOutput  = curl_exec($ch);
        $curlHeaders = $this->helper->getCurlHeaders($curlOutput);
        $info        = curl_getinfo($ch);
        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE); 
        curl_close($ch);
        
        //Write information about the API Call
        if ($this->getConfigData('debug_enabled')) {
            $this->helper->logTransaction('API REQUEST', $transactionData, false);
            $this->helper->logTransaction('API RESPONSE', $curlOutput, false);
            $this->helper->logTransaction('CURL HEADERS', $curlHeaders, false);
            $this->helper->logTransaction('CURL INFO', $info, false);
        }

        //Check if there is response from Credomatic API
        if (!isset($info['url'])) {
            $msg = 'There was no connection with Credomatic. Please try again.';
            $this->helper->logTransaction($msg, $info);
            throw new \Exception($msg);
        }

        if (!empty($curlHeaders['Location'])) {
            $response = $this->helper->getResponseData($curlHeaders['Location']);
        } else if(!empty($curlHeaders['location'])) { //just in case servers returns in lower case..
            $response = $this->helper->getResponseData($curlHeaders['location']);
        } else if(!empty($info['redirect_url'])) { //Getting response from redirect_url via Curl Info (AMEX)
            $response = $this->helper->getResponseData($info['redirect_url']);
        } else {
            $response['threedsecure'] = $curlOutput;
        }

        return $response;
    }

    /**
     * Process Credomatic response
     * @param  [type]  $response
     * @param  [type]  $payment
     * @param  boolean $threedsecure
     * @param  boolean $closeTransaction
     * @return [type]
     * @author smoreno91
     * @date   2017-12-03 
     */
    public function processPaymentResponse($response, $payment, $threedsecure = false) {

        // Validate payment response
        if (empty($response['response']) || empty($response['response_code']) || empty($response['orderid'])
            || empty($response['hash'])) {
            $msg = 'Payment invalid response';
            $this->helper->logTransaction($msg, $response);
            throw new \Exception($msg);
        }

        //Check transaction type
        if($response['type'] == 'sale' || $response['type'] == 'capture'){
            $type              = 'capture';
            $closeTransaction  = 1;
        } elseif ($response['type'] == 'auth' ) {
            $type              = 'authorization';
            $closeTransaction  = 0;
        } else {
            $type              = 'authorization';
            $closeTransaction  = 0;
        }

        $this->helper->logTransaction('Processing Payment Response...', array(), false);
        $this->helper->logTransaction('Response: ', $response, false);
        $this->helper->logTransaction('Payment for order #: ', array($payment->getOrder()->getIncrementId()), false);

                
        //Check if transation was approved
        $result = array();
        if ($response['response_code'] == 100) {

            $payment->setTransactionId($response['transactionid']);
            $payment->setIsTransactionClosed($closeTransaction);

            $this->helper->logTransaction('Leaves response code 100', array(), false);
            
            // Handle 3D secure response..
            if ($threedsecure) {

                $this->helper->logTransaction('Enters 3d secure', array(), false);

                $payment->setIsTransactionPending(false);
                $payment->getOrder()->setState("processing")->setStatus("processing");

                if ($type == 'authorization') {
                    $payment->registerAuthorizationNotification($response['amount']);
                } elseif($type == 'capture') {
                    $payment->registerCaptureNotification($response['amount']);
                }

                $payment->save();
            }

            $result['success'] = true;
           
        } else {

            $this->helper->logTransaction('Response code is different from 100');

            $result['success'] = false;
            $result['msg']     = !empty($response["responsetext"]) ? $response["responsetext"] : 'Transaction declined by Bank';
            $result['msg']    .= !empty($response["cvvresponse"]) ? '. CVV Response: '. $response["cvvresponse"] : '';
        }

        return $result;
    }

    /**
     * Refund specified amount for payment
     *
     * @param \Magento\Framework\DataObject|InfoInterface $payment
     * @param float $amount
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     * @author smoreno91
     * @date   2017-12-03 
     */
    public function refund(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        $this->helper->logTransaction('Refund method is not available');
        if (!$this->canRefund()) {
            throw new \Magento\Framework\Exception\LocalizedException(__('The refund action is not available.'));
        }
        return $this;
    }

}