<?php
/**
 * Copyright © 2017 remmote.com
 * */

namespace Remmote\Credomaticpayment\Controller\Notification;
 
use Magento\Framework\App\Action\Context;
use Magento\Store\Model\ScopeInterface;
 
class Response extends \Magento\Framework\App\Action\Action
{
    protected $_resultPageFactory;
 
    public function __construct(
        Context $context, 
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Sales\Model\Convert\OrderFactory $convertOrderFactory,
        \Magento\Shipping\Model\ShipmentNotifier $shipmentNotifier,
        \Magento\Sales\Model\Service\InvoiceService $invoiceService,
        \Magento\Sales\Model\Order\Email\Sender\InvoiceSender $invoiceSender,
        \Magento\Framework\DB\Transaction $transaction,
        \Remmote\Credomaticpayment\Helper\Data $credomaticHelperData,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ){
        $this->_resultPageFactory       = $resultPageFactory;
        $this->_orderFactory            = $orderFactory;
        $this->_convertOrderFactory     = $convertOrderFactory;
        $this->_shipmentNotifier        = $shipmentNotifier;
        $this->_invoiceService          = $invoiceService;
        $this->_invoiceSender           = $invoiceSender;
        $this->_transaction             = $transaction;
        $this->_credomaticHelperData    = $credomaticHelperData;
        $this->_checkoutSession         = $checkoutSession;
        $this->_transportBuilder        = $transportBuilder;
        $this->_storeManager            = $storeManager;    
        $this->_scopeConfig             = $scopeConfig;
        parent::__construct($context);
    }

    public function execute() {
        //Getting GET data
        $response                       = $this->getRequest()->getParams();
        $orderId                        = $this->_checkoutSession->getLastOrderId();
        $orderIncrementId               = $this->_checkoutSession->getLastRealOrderId();

        //Loading order from Magento
        $orderModel = $this->_orderFactory->create();
        $lastOrder  = $orderModel->load($orderId);

        $response['orderIncrementId']   = $lastOrder->getIncrementId();
        
        //Validate hash
        if(!$this->_credomaticHelperData->validHash($response, $response['hash'])) {
            $this->_credomaticHelperData->logTransaction('Invalid hash for order #'.$orderIncrementId, $response);
            $this->_redirect('404');
            return;
        }

        //Check if reponse correspond to last order Id
        if($response['orderid'] != $lastOrder->getIncrementId()) {
            $this->_credomaticHelperData->logTransaction('Different order IDs for order #'.$orderIncrementId, $response);
            $this->_redirect('404');
            return;
        }

        try {

            // Process response
            $model          = $this->_objectManager->create('\Remmote\Credomaticpayment\Model\Payment\Credomatic');
            $result         = $model->processPaymentResponse($response, $lastOrder->getPayment(), true);

            if($result['success']){
                
                $lastOrder->save();

                // //Notify customer and merchant about failed payment
                // $this->_notifySuccessTransaction($lastOrder->getIncrementId(), $lastOrder->getCustomerEmail());

                //Redirecting to success payment..
                $this->_redirect('checkout/onepage/success', array('_secure'=>true));
            } else {
                $this->_credomaticHelperData->logTransaction('Transaction failed for order #'.$orderIncrementId.' / 3DSecure', $response);

                $gateway_msg_response =  __('Credomatic Response: '). ' '.$result['msg'];
                $lastOrder->addStatusHistoryComment($gateway_msg_response);

                $lastOrder->getPayment()->setIsTransactionDenied(true);
                $lastOrder->getPayment()->update();
                $lastOrder->getPayment()->save();
                $lastOrder->save();

                // //Notify customer and merchant about failed payment
                // $this->_notifyFailedTransaction($lastOrder->getIncrementId(), $lastOrder->getCustomerEmail());

                //Redirecting to failure payment..
                // $this->_redirect('checkout/onepage/failure', array('_secure'=>true));
                
                //Attempt to recover cart...
                $this->messageManager->addError(__('It was not possible to process your payment. Please verify your card details and try again.'));
                $this->_recoverOrder($lastOrder);
                
            }
            
        } catch (\Exception $e) {
            
            $this->_redirect('checkout/onepage/failure', array('_secure'=>true));
        }

        return;
    }

    /**
     * Creates a new cart based on existing order
     * @param  [type]     $order
     * @return [type]
     * @author edudeleon
     * @date   2019-07-01
     */
    private function _recoverOrder($order)
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $cart           = $this->_objectManager->get('Magento\Checkout\Model\Cart');
        $items          = $order->getItemsCollection();
        foreach ($items as $item) {
            try {
                $cart->addOrderItem($item);
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                if ($this->_objectManager->get('Magento\Checkout\Model\Session')->getUseNotice(true)) {
                    $this->messageManager->addNotice($e->getMessage());
                } else {
                    $this->messageManager->addError($e->getMessage());
                }
                return $resultRedirect->setPath('*/*/history');
            } catch (\Exception $e) {
                $this->messageManager->addException($e, __('We can\'t add this item to your shopping cart right now.'));
                return $resultRedirect->setPath('checkout/cart');
            }
        }

        $cart->save();
        $this->_redirect('checkout/cart', array('_secure'=>true));
    }

     /**
     * Notified custoemr and merchant about failed payment only for 3D Secure transactions
     * @param  [type]     $orderNumber
     * @param  [type]     $customerEmail
     * @return [type]
     * @author edudeleon
     * @date   2018-10-15
     */
    private function _notifyFailedTransaction($orderNumber, $customerEmail)
    {
        //Getting email template
        $emailTempate   = 'payment_credomatic_failedpayment_template';
        $templateParams = array(
            'orderNumber' => $orderNumber,
        );

        $senderName     = $this->_scopeConfig->getValue('trans_email/ident_sales/name', ScopeInterface::SCOPE_STORE);
        $senderEmail    = $this->_scopeConfig->getValue('trans_email/ident_sales/email', ScopeInterface::SCOPE_STORE);

        $sender = array(
            'name'  => $senderName,
            'email' => $senderEmail,
        );

        $merchantEmail = $this->_scopeConfig->getValue('payment/credomatic/failedpayment_email', ScopeInterface::SCOPE_STORE);
        $merchantEmail = $merchantEmail ? $merchantEmail : $senderEmail;

        $transport = $this->_transportBuilder->setTemplateIdentifier($emailTempate)
            ->setTemplateOptions(['area' => \Magento\Framework\App\Area::AREA_FRONTEND, 'store' => $this->_storeManager->getStore()->getId()])
            ->setTemplateVars($templateParams)
            ->setFrom($sender)
            ->addTo($customerEmail)
            ->addBcc($merchantEmail)
            ->getTransport();

        $transport->sendMessage();
    }

     /**
     * Notified customer and merchant about successful transactions
     * @param  [type]     $orderNumber
     * @param  [type]     $customerEmail
     * @return [type]
     * @author edudeleon
     * @date   2019-06-26
     */
    private function _notifySuccessTransaction($orderNumber, $customerEmail)
    {
        //Getting email template
        $emailTempate   = 'payment_credomatic_successpayment_template';
        $templateParams = array(
            'orderNumber' => $orderNumber,
        );

        $senderName     = $this->_scopeConfig->getValue('trans_email/ident_sales/name', ScopeInterface::SCOPE_STORE);
        $senderEmail    = $this->_scopeConfig->getValue('trans_email/ident_sales/email', ScopeInterface::SCOPE_STORE);

        $sender = array(
            'name'  => $senderName,
            'email' => $senderEmail,
        );

        $merchantEmail = $this->_scopeConfig->getValue('payment/credomatic/failedpayment_email', ScopeInterface::SCOPE_STORE);
        $merchantEmail = $merchantEmail ? $merchantEmail : $senderEmail;

        $transport = $this->_transportBuilder->setTemplateIdentifier($emailTempate)
            ->setTemplateOptions(['area' => \Magento\Framework\App\Area::AREA_FRONTEND, 'store' => $this->_storeManager->getStore()->getId()])
            ->setTemplateVars($templateParams)
            ->setFrom($sender)
            ->addTo($customerEmail)
            ->addBcc($merchantEmail)
            ->getTransport();

        $transport->sendMessage();
    }

}