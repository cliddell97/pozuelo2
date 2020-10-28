<?php
namespace Remmote\Credomaticpayment\Controller\Notification\Response;

/**
 * Interceptor class for @see \Remmote\Credomaticpayment\Controller\Notification\Response
 */
class Interceptor extends \Remmote\Credomaticpayment\Controller\Notification\Response implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Framework\App\Action\Context $context, \Magento\Framework\View\Result\PageFactory $resultPageFactory, \Magento\Sales\Model\OrderFactory $orderFactory, \Magento\Sales\Model\Convert\OrderFactory $convertOrderFactory, \Magento\Shipping\Model\ShipmentNotifier $shipmentNotifier, \Magento\Sales\Model\Service\InvoiceService $invoiceService, \Magento\Sales\Model\Order\Email\Sender\InvoiceSender $invoiceSender, \Magento\Framework\DB\Transaction $transaction, \Remmote\Credomaticpayment\Helper\Data $credomaticHelperData, \Magento\Checkout\Model\Session $checkoutSession, \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder, \Magento\Store\Model\StoreManagerInterface $storeManager, \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig)
    {
        $this->___init();
        parent::__construct($context, $resultPageFactory, $orderFactory, $convertOrderFactory, $shipmentNotifier, $invoiceService, $invoiceSender, $transaction, $credomaticHelperData, $checkoutSession, $transportBuilder, $storeManager, $scopeConfig);
    }

    /**
     * {@inheritdoc}
     */
    public function dispatch(\Magento\Framework\App\RequestInterface $request)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'dispatch');
        if (!$pluginInfo) {
            return parent::dispatch($request);
        } else {
            return $this->___callPlugins('dispatch', func_get_args(), $pluginInfo);
        }
    }
}
