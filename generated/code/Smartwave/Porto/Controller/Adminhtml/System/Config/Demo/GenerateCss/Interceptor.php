<?php
namespace Smartwave\Porto\Controller\Adminhtml\System\Config\Demo\GenerateCss;

/**
 * Interceptor class for @see \Smartwave\Porto\Controller\Adminhtml\System\Config\Demo\GenerateCss
 */
class Interceptor extends \Smartwave\Porto\Controller\Adminhtml\System\Config\Demo\GenerateCss implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Backend\App\Action\Context $context, \Magento\Framework\ObjectManagerInterface $objectManager)
    {
        $this->___init();
        parent::__construct($context, $objectManager);
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
