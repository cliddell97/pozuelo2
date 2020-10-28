<?php
/**
 * Copyright © 2020 remmote.com
 * */
 
namespace Remmote\Credomaticpayment\Observer;
 
use Magento\Framework\Event\ObserverInterface;
 
class SendMailOnOrderSuccess implements ObserverInterface
{
    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $orderFactory;
 
    /**
     * @var \Magento\Sales\Model\Order\Email\Sender\OrderSender
     */
    protected $orderSender;
 
    /**
     * @var \Magento\Checkout\Model\Session $checkoutSession
     */
    protected $checkoutSession;
 
    /**
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender
     * @param \Magento\Checkout\Model\Session $checkoutSession
     *
     * @codeCoverageIgnore
     */
    public function __construct(
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender,
        \Magento\Checkout\Model\Session $checkoutSession
    )
    {
        $this->orderFactory = $orderFactory;
        $this->orderSender = $orderSender;
        $this->checkoutSession = $checkoutSession;
    }
 
    /**
     * Send email only when client lands to success checkout page
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $orderIds = $observer->getEvent()->getOrderIds();
        if(count($orderIds)){
            $this->checkoutSession->setForceOrderMailSentOnSuccess(true);
            $this->checkoutSession->setForceOrderMailSentOnSuccessCount(1);
            $order = $this->orderFactory->create()->load($orderIds[0]);
            $this->orderSender->send($order, true);
        }
    }
}