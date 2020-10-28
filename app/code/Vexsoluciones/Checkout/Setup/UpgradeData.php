<?PHP

namespace Vexsoluciones\Checkout\Setup;

use Magento\Framework\Module\Setup\Migration;
use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Catalog\Setup\CategorySetupFactory;
use Magento\Customer\Setup\CustomerSetupFactory;
use Magento\Eav\Model\Entity\Attribute\Set as AttributeSet;
use Magento\Eav\Model\Entity\Attribute\SetFactory as AttributeSetFactory;


class UpgradeData implements UpgradeDataInterface
{

    protected $customerSetupFactory;
 
    private $attributeSetFactory;
 
    public function __construct(
        CustomerSetupFactory $customerSetupFactory,
        AttributeSetFactory $attributeSetFactory
    ) {
        $this->customerSetupFactory = $customerSetupFactory;
        $this->attributeSetFactory = $attributeSetFactory;
    }


    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context){

       if (version_compare($context->getVersion(), '1.0.2') < 0) {
	       
	       $datosCustomerAddress = [

	            'departamento_id' => [
	                'config' => [
	                     'type' => 'varchar',
	                     'label' => 'Departamento_id',
	                     'input' => 'text',
	                     'required' => false,
	                     'visible' => true,
	                     'user_defined' => false,
	                     'sort_order' => 1001,
	                     'position' => 1001,
	                     'system' => 0,
	                ],
	                'used_in_forms' => ['adminhtml_customer_address']
	            ],

	            'provincia_id' => [
	                'config' => [
	                    'type' => 'varchar',
	                    'label' => 'Provincia_id',
	                    'input' => 'text',
	                    'required' => false,
	                    'visible' => true,
	                    'user_defined' => false,
	                    'sort_order' => 1002,
	                    'position' => 1002,
	                    'system' => 0,
	                ],
	                'used_in_forms' => ['adminhtml_customer_address']
	            ],

	            'distrito_id' => [
	                'config' => [
	                   'type' => 'varchar',
	                   'label' => 'Distrito_id',
	                   'input' => 'text',
	                   'required' => false,
	                   'visible' => true,
	                   'user_defined' => false,
	                   'sort_order' => 1003,
	                   'position' => 1003,
	                   'system' => 0,
	                ],
	                'used_in_forms' => ['adminhtml_customer_address']
	            ],

	            'departamento_label' => [
	                'config' => [
	                     'type' => 'varchar',
	                     'label' => 'Departamento',
	                     'input' => 'text',
	                     'required' => false,
	                     'visible' => true,
	                     'user_defined' => false,
	                     'sort_order' => 1004,
	                     'position' => 1004,
	                     'system' => 0,
	                ],
	                'used_in_forms' => []
	            ],

	            'provincia_label' => [
	                'config' => [
	                    'type' => 'varchar',
	                    'label' => 'Provincia',
	                    'input' => 'text',
	                    'required' => false,
	                    'visible' => true,
	                    'user_defined' => false,
	                    'sort_order' => 1005,
	                    'position' => 1005,
	                    'system' => 0,
	                ],
	                'used_in_forms' => []
	            ],

	            'distrito_label' => [
	                'config' => [
	                    'type' => 'varchar',
	                    'label' => 'Distrito',
	                    'input' => 'text',
	                    'required' => false,
	                    'visible' => true,
	                    'user_defined' => false,
	                    'sort_order' => 1006,
	                    'position' => 1006,
	                    'system' => 0,
	                ],
	                'used_in_forms' => []
	            ]

	        ];


	        $customerSetup = $this->customerSetupFactory->create(['setup' => $setup]);
	        
	        $customerEntity = $customerSetup->getEavConfig()->getEntityType('customer');
	        $attributeSetId = $customerEntity->getDefaultAttributeSetId();
	        
	        $attributeSet = $this->attributeSetFactory->create();
	        $attributeGroupId = $attributeSet->getDefaultGroupId($attributeSetId);
	         

	        foreach ($datosCustomerAddress as $key => $value) {
	            
	            $customerSetup->addAttribute('customer_address', $key, $value['config']);
	        
	            $attribute = $customerSetup->getEavConfig()->getAttribute('customer_address', $key)
	                ->addData([
	                    'attribute_set_id' => $attributeSetId,
	                    'attribute_group_id' => $attributeGroupId,
	                    'used_in_forms' => $value['used_in_forms'],
	                ]);
	        
	            $attribute->save();

	        }
   		}
    
    }
}
