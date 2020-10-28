<?php
/**
 * @extension   Remmote_Credomaticpayment
 * @author      Remmote
 * @copyright   2017 - Remmote.com
 * @descripion  Payment Action
 */
namespace Remmote\Credomaticpayment\Model\Source;

class Cctype extends \Magento\Payment\Model\Source\Cctype
{
    /**
     * Allowed credit card types
     * @return [type]
     * @author Remmote
     * @date   2017-11-27
     */
    public function getAllowedTypes()
    {
        return ['VI','MC','AE','DI','JCB'];
    }
}