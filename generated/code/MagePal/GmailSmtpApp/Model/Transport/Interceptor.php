<?php
namespace MagePal\GmailSmtpApp\Model\Transport;

/**
 * Interceptor class for @see \MagePal\GmailSmtpApp\Model\Transport
 */
class Interceptor extends \MagePal\GmailSmtpApp\Model\Transport implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Framework\Mail\MessageInterface $message, $parameters = null)
    {
        $this->___init();
        parent::__construct($message, $parameters);
    }

    /**
     * {@inheritdoc}
     */
    public function sendMessage()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'sendMessage');
        if (!$pluginInfo) {
            return parent::sendMessage();
        } else {
            return $this->___callPlugins('sendMessage', func_get_args(), $pluginInfo);
        }
    }
}
