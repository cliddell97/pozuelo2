<?php
/**
 * @extension   Remmote_Credomaticpayment
 * @author      Remmote
 * @copyright   2017 - Remmote.com
 * @descripion  Header extension info block
 */
namespace Remmote\Credomaticpayment\Block\System\Config;

class Info extends \Magento\Config\Block\System\Config\Form\Fieldset
{
    /**
     * Override render function
     * @param AbstractElement $element
     * @return string
     * @author Remmote
     * @date   2017-07-18
     */
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $credomaticHelper = \Magento\Framework\App\ObjectManager::getInstance()->create('\Remmote\Credomaticpayment\Helper\Data');

        if($credomaticHelper->isValidLicense()) {

            $msg = '<fieldset style="border: 1px solid #ccc;">'.
                        '<table>'.
                            '<tr>'.
                                '<td style="padding:0;">'.
                                    '<h2 style="margin-bottom: 1em;">Hello '.$this->_authSession->getUser()->getFirstname().
                                        ', thanks for using our extension!</h2>'.
                                    '<p style="margin: 0;">The extension is enabled for the domain <b>'.$_SERVER['HTTP_HOST'].'</b>. If you are changing to a different domain, please request a new license key to enable the extension again.</p>'.
                                '</td>'.
                            '</tr>'.
                        '</table>'.
                    '</fieldset>';

        } else {
            $msg = '<fieldset style="border: 1px solid #ccc;">'.
                        '<table>'.
                            '<tr>'.
                                '<td style="padding:0;">'.
                                    '<h2 style="margin-bottom: 1em;">Hello '.$this->_authSession->getUser()->getFirstname().
                                        ', thanks for using our extension!</h2>'.
                                    '<p style="margin: 0; color:#eb5202;">This extension is not activated yet. Please send us an email to <b>info@remmote.com</b> requesting your License Key, include your Remmote Order ID and your domain name. We will provide you a valid License Key to enable this extension. Your domain name is <strong>'.$_SERVER['HTTP_HOST'].'</strong></p>'.
                                '</td>'.
                            '</tr>'.
                        '</table>'.
                    '</fieldset>';
        }

        return $msg;
    }

}