<?php
/**
 * Copyright © 2020 remmote.com
 * */

namespace Remmote\Credomaticpayment\Plugin\Sales\Order\Email\Container;

/**
 * Disable emails after order are placed. Email are sent only in success page (via observer...)
 */
class OrderIdentityPlugin
{
    /**
     * @var \Magento\Checkout\Model\Session $checkoutSession
     */
    protected $checkoutSession;
 
    /**
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Framework\App\ProductMetadataInterface $productMetadata
     * @param \Magento\Framework\App\State $state
     *
     * @codeCoverageIgnore
     */
    public function __construct(
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\App\ProductMetadataInterface $productMetadata,
        \Magento\Framework\App\State $state
    )
    {
        $this->checkoutSession = $checkoutSession;
        $this->productMetadata = $productMetadata;
        $this->state = $state;
    }
 
    /**
     * Comes to this method 2 times when placing an order.
     * The first time after placing the order and the second time when the customer lands to the success page view. 
     * Method returns false in the first call and true in the second call
     * If emails are not being sent properly, it might be because the observer is not being triggered (i.e. custom checkouts)
     * Check file "app/code/Remmote/Credomaticpayment/etc/frontend/events.xml" for references
     * @param \Magento\Sales\Model\Order\Email\Container\OrderIdentity $subject
     * @param callable $proceed
     * @return bool
     */
    public function afterIsEnabled(\Magento\Sales\Model\Order\Email\Container\OrderIdentity $subject, $result)
    {
        //Ignore this code for backend (interestingly default order email sender runs under are code "webapi_rest")
        if($this->state->getAreaCode() == \Magento\Framework\App\Area::AREA_ADMINHTML){
            return $result;       
        }

        $result = false; // Ignoring admin panel setting (Admin -> Stores -> Configuration -> Sales -> Sales Email -> Order -> Enabled)
        $forceOrderMailSentOnSuccess = $this->checkoutSession->getForceOrderMailSentOnSuccess();
        if(isset($forceOrderMailSentOnSuccess) && $forceOrderMailSentOnSuccess)
        {
            $result = true;

            //Only unset flags after second call (this is because from M2.3.2, the method IsEnabled() is called 2 times)
            $count = $this->checkoutSession->getForceOrderMailSentOnSuccessCount();
            if($count > 1 || $this->productMetadata->getVersion() < "2.3.2"){
                $this->checkoutSession->unsForceOrderMailSentOnSuccess();
                $this->checkoutSession->unsForceOrderMailSentOnSuccessCount();
            } else {
                $count++;
                $this->checkoutSession->setForceOrderMailSentOnSuccessCount($count);
            }
        }
 
        return $result;
    }
}