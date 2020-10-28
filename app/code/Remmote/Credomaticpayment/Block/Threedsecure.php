<?php
/**
 * Copyright © 2017 remmote.com
 * */
namespace Remmote\Credomaticpayment\Block;

class Threedsecure extends \Magento\Framework\View\Element\Template
{

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        array $data = []
    ) {
        $this->_checkoutSession = $checkoutSession;
        parent::__construct($context, $data);
    }

    public function getCheckoutSession(){
        return $this->_checkoutSession;
    }
}