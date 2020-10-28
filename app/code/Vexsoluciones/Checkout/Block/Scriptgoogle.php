<?PHP 

namespace Vexsoluciones\Checkout\Block;

class Scriptgoogle extends \Magento\Framework\View\Element\Template
{
	
    public function getApiKey()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $key = $objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface')->getValue('vexsolucionescheckout/general/key');
        return $key;
    }
}