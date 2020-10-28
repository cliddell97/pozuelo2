<?php
namespace Magento\Paypal\Block\Adminhtml\Order\View;

/**
 * Interceptor class for @see \Magento\Paypal\Block\Adminhtml\Order\View
 */
class Interceptor extends \Magento\Paypal\Block\Adminhtml\Order\View implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Backend\Block\Widget\Context $context, \Magento\Framework\Registry $registry, \Magento\Sales\Model\Config $salesConfig, \Magento\Sales\Helper\Reorder $reorderHelper, \Magento\Paypal\Model\Adminhtml\Express $express, array $data = [])
    {
        $this->___init();
        parent::__construct($context, $registry, $salesConfig, $reorderHelper, $express, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function setLayout(\Magento\Framework\View\LayoutInterface $layout)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'setLayout');
        if (!$pluginInfo) {
            return parent::setLayout($layout);
        } else {
            return $this->___callPlugins('setLayout', func_get_args(), $pluginInfo);
        }
    }
}
