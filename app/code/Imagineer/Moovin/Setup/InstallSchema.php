<?php
namespace Imagineer\Moovin\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;

class InstallSchema implements InstallSchemaInterface
{
    public function install(
                SchemaSetupInterface $setup,
                ModuleContextInterface $context
        ){
        $setup->startSetup();

        $quote = $setup->getTable('quote');
        $salesOrder = $setup->getTable('sales_order');

                $setup->getConnection()->addColumn(
                        $quote,
                        'latitud',
                        [
                                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                                'nullable' => true,
                                'comment' =>'Latitud'
                        ]
                );

                $setup->getConnection()->addColumn(
                        $salesOrder,
                        'latitud',
                        [
                                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                                'nullable' => true,
                                'comment' =>'Latitud'
                        ]
                );
                $setup->getConnection()->addColumn(
                        $quote,
                        'longitud',
                        [
                                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                                'nullable' => true,
                                'comment' =>'Longitud'
                        ]
                );

                $setup->getConnection()->addColumn(
                        $salesOrder,
                        'longitud',
                        [
                                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                                'nullable' => true,
                                'comment' =>'Longitud'
                        ]
                );
                $setup->endSetup();
    }
}


