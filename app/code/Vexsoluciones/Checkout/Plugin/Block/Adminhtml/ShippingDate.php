<?PHP

namespace Vexsoluciones\Checkout\Plugin\Block\Adminhtml;

use Magento\Framework\Exception\LocalizedException;     


class ShippingDate
{
  
    public function afterToHtml(\Magento\Sales\Block\Adminhtml\Order\View\Info $subject, $result) {
 
        
        $order = $subject->getOrder();
         
        $blockShippingDate = $subject->getLayout()->createBlock(
            'Vexsoluciones\Checkout\Block\Adminhtml\Order\ShippingDate'
        );
 
        $blockShippingDate->setTemplate('Vexsoluciones_Checkout::order/ShippingDate.phtml'); // FeFacturacionFieldsView
    
        if ($blockShippingDate !== false) {
  
            $result = $result.$blockShippingDate->toHtml();
        }
 
        return $result;
    }
}