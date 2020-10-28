<?php
/**
 * @extension   Remmote_Credomaticpayment
 * @author      Remmote
 * @copyright   2017 - Remmote.com
 * @descripion  Payment Action
 */
namespace Remmote\Credomaticpayment\Model\Source;

class PaymentAction implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Payment Action
     * @return [type]
     * @author Remmote
     * @date   2017-11-27
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => \Magento\Payment\Model\Method\AbstractMethod::ACTION_AUTHORIZE,
                'label' => 'Authorize'
            ],
            [
                'value' => \Magento\Payment\Model\Method\AbstractMethod::ACTION_AUTHORIZE_CAPTURE,
                'label' => 'Authorize & Capture'
            ]
        ];
    }
}