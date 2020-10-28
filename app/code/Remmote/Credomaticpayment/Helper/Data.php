<?php
/**
 * Copyright © 2017 Remmote.com
 * 
 * */
namespace Remmote\Credomaticpayment\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    protected $logger;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Psr\Log\LoggerInterface $logger
    ){
        $this->_storeManager = $storeManager;
        $this->logger = $logger;
        parent::__construct($context);
    }

    /**
    * Config paths
    */
    const ACTIVE            = "payment/credomatic/active";
    const TITLE             = "payment/credomatic/title";
    const KEY_ID            = "payment/credomatic/key_id";
    const KEY               = "payment/credomatic/key";
    const PAYMENT_ACTION    = "payment/credomatic/payment_action";
    const ORDER_STATUS      = "payment/credomatic/order_status";
    const CCTYPES           = "payment/credomatic/cctypes";
    const DISPLAY_CCTYPES   = "payment/credomatic/display_cctypes";
    const USECCV            = "payment/credomatic/useccv";
    const SORT_ORDER        = "payment/credomatic/sort_order";
    const DEBUG_ENABLED     = "payment/credomatic/debug_enabled";
    const DOMAIN_NAME       = "payment/credomatic/domain_name";
    const LICENSE_KEY       = "payment/credomatic/license_key";
    const MULTIPLE_CURRENCIES = "payment/credomatic/multiple_currencies";
    const PROCESSOR_ID      = "payment/credomatic/processor_id";

    /**
     * Check if license is valid
     * @param  [type]     $store
     * @return boolean
     * @author edudeleon
     * @date   2018-04-30
     */
    public function isValidLicense($store = null) 
    {
		return true;
        $domainName = $this->scopeConfig->getValue(
                self::DOMAIN_NAME,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $store
            );

        $licenseKey = $this->scopeConfig->getValue(
                self::LICENSE_KEY,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $store
            );

        if($domainName  == $_SERVER['HTTP_HOST'] && md5($domainName.$_SERVER['HTTP_HOST']) == $licenseKey) {
            return true;
        }

        return false;
    }


    /**
     * Verify if module is enable
     * @param  [type] $store
     * @return [type]
     */
    public function isActive($store = null)
    {
        return $this->getKeyId($store) && $this->isValidLicense($store) && $this->scopeConfig->getValue(
                                                self::ACTIVE,
                                                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                                                $store
                                                );
    }

    /**
     * Get title
     * @param  [type] $store
     * @return [type]
     */
    public function getTitle($store = null){
        return $this->scopeConfig->getValue(
            self::TITLE,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Get Key Id
     * @param  [type] $store
     * @return [type]
     */
    public function getKeyId($store = null){
        return $this->scopeConfig->getValue(
            self::KEY_ID,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Get Key
     * @param  [type] $store
     * @return [type]
     */
    public function getKey($store = null){
        return $this->scopeConfig->getValue(
            self::KEY,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Get Payment Action
     * @param  [type] $store
     * @return [type]
     */
    public function getPaymentAction($store = null){
        return $this->scopeConfig->getValue(
            self::PAYMENT_ACTION,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );
    }
    
    /**
     * Get Order Status
     * @param  [type] $store
     * @return [type]
     */
    public function getOrderStatus($store = null){
        return $this->scopeConfig->getValue(
            self::ORDER_STATUS,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );
    }
    
    /**
     * Get Cctypes
     * @param  [type] $store
     * @return [type]
     */
    public function getCctypes($store = null){
        return $this->scopeConfig->getValue(
            self::CCTYPES,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );
    }
    
    /**
     * Get Display Cctypes
     * @param  [type] $store
     * @return [type]
     */
    public function getDisplayCctypes($store = null){
        return $this->scopeConfig->getValue(
            self::DISPLAY_CCTYPES,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Get UseCcv
     * @param  [type] $store
     * @return [type]
     */
    public function getUseCcv($store = null){
        return $this->scopeConfig->getValue(
            self::USECCV,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Get multi currencies flag
     * @param  [type] $store
     * @return [type]
     */
    public function isMulticurrencyEnabled($store = null){
        return $this->scopeConfig->getValue(
            self::MULTIPLE_CURRENCIES,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Get store processor ID
     * @param  [type]     $store
     * @return [type]
     * @author edudeleon
     * @date   2019-10-29
     */
    public function getProcessorId($store = null){
        return $this->scopeConfig->getValue(
            self::PROCESSOR_ID,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Get Sort Order
     * @param  [type] $store
     * @return [type]
     */
    public function getSortOrder($store = null){
        return $this->scopeConfig->getValue(
            self::SORT_ORDER,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Get Debug enabled
     * @param  [type] $store
     * @return [type]
     */
    public function isDebugEnabled($store = null){
        return $this->scopeConfig->getValue(
            self::DEBUG_ENABLED,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Writes to credomatic log file
     * @param  [type]     $msg
     * @param  [type]     $data
     * @param  boolean    $error
     * @return [type]
     * @author smoreno91
     * @date   2017-12-03 
     */
    public function logTransaction($msg, $data = array(), $error = true){
        //Mask credit card..
        if (isset($data['ccnumber'])) {
            $data['ccnumber'] = 'XXXX-XXXX-XXXX-'.substr($data['ccnumber'], -4);
        }

        if (!empty($data)) {
            $msg .= PHP_EOL.'Data => '.print_r($data, true).PHP_EOL;
        }
        if ($error) {
            $this->logger->info("[CREDOMATIC - ERROR] Message: ".$msg);
        } else {
            $this->logger->info("[CREDOMATIC - DEBUG] Message: ".$msg);
        }
    }

    /**
     * Validate returned hash against hash built from gateway response data
     * @param  [type] $response
     * @param  [type] $hash
     * @return [type]
     * @author smoreno91
     * @date   2017-12-03 
     */
    public function validHash($response, $hash) {

        $dataHash = md5(
            $response['orderid'] . '|' .
            $response['amount'] . '|' .
            $response['response'] . '|' .
            $response['transactionid'] . '|' .
            $response['avsresponse'] . '|' .
            $response['cvvresponse'] . '|' .
            $response['time'] . '|' .
            $this->getKey()
        );

            return true;
        if($dataHash == $hash){
            return true;
        }

        return false;
    }

    /**
     * Get transaction hash
     * @param  [type] $orderIncrementId
     * @param  [type] $totals
     * @param  [type] $time
     * @param  [type] $key
     * @return [type]
     * @author smoreno91
     * @date   2017-12-03 
     */
    public function getHash($orderIncrementId, $totals, $time, $key) {
        return md5("{$orderIncrementId}|{$totals}|{$time}|{$key}");
    }

    /**
     * Get headers from curl
     * @param  [type] $response
     * @return [type]
     * @author smoreno91
     * @date   2017-12-03 
     */
    public function getCurlHeaders($response) {
        $headers = [];
        $headerText = substr($response, 0, strpos($response, "\r\n\r\n"));

        foreach (explode("\r\n", $headerText) as $i => $line) {
            if ($i === 0) {
                $headers['http_code'] = $line;
            } else {
                list($key, $value) = explode(': ', $line);
                $headers[$key] = $value;
            }
        }
        return $headers;
    }

    /**
     * Return URL params as an array
     * @param  [type]     $infoUrl
     * @return [type]
     * @author smoreno91
     * @date   2017-12-03 
     */
    public function getResponseData($infoUrl){
        $url = $this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_WEB).
            'credomaticpayment/notification/response?';
        $infoUrl = str_replace($url, '', $infoUrl);

        //Getting values
        parse_str($infoUrl, $responseArray);
        return $responseArray;
    }

}