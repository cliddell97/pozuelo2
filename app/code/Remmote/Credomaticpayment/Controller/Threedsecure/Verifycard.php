<?php
/**
 * Copyright © 2017 remmote.com
 * */

namespace Remmote\Credomaticpayment\Controller\Threedsecure;
 
use Magento\Framework\App\Action\Context;
 
class Verifycard extends \Magento\Framework\App\Action\Action
{
    /** @var  \Magento\Framework\View\Result\Page */
    protected $resultPageFactory;

    public function __construct(
        \Magento\Framework\App\Action\Context $context, 
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Checkout\Model\Session $checkoutSession
    ){  
        $this->resultPageFactory        = $resultPageFactory;
        $this->_checkoutSession         = $checkoutSession;

        parent::__construct($context);
    }

    public function execute() {
    
        if(!$this->_checkoutSession->getSecureData()){
            $this->_redirect('404');
            return;
        }

        $resultPage = $this->resultPageFactory->create();
        return $resultPage;
       
    }
}