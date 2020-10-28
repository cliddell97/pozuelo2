<?PHP

namespace Vexsoluciones\Checkout\Plugin;
 
class ShippingAddressManagementPlugin
{
    protected $logger;

    public function __construct(
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->logger = $logger;
    }

    public function beforeAssign(
        \Magento\Quote\Model\ShippingAddressManagement $subject,
        $cartId,
        \Magento\Quote\Api\Data\AddressInterface $address
    ) {

        $extAttributes = $address->getExtensionAttributes();

        if (!empty($extAttributes)) {

            try {
                $address->setCarrierOffice($address->getCarrierOffice());
            } catch (\Exception $e) {
                $this->logger->critical($e->getMessage());
            }

        }

    }
}