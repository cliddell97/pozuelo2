<?php
/**
 * Copyright © 2015 Vexsoluciones. All rights reserved.
 */

namespace Vexsoluciones\Checkout\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\DB\Adapter\AdapterInterface;


class UpgradeSchema implements  UpgradeSchemaInterface
{
    private $dbStructure = null;

    public function __construct(dbStructureUpgrade $dbStructure){

        $this->dbStructure = $dbStructure->get();
    }

    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
    
        $setup->startSetup();
        
        $connection = $setup->getConnection();
        foreach ($this->dbStructure as $tableName => $structure) {

            $columns = $structure['columns'];
        
            if (!$setup->tableExists($tableName)) {

                $table = $setup->getConnection()->newTable(
                    $setup->getTable($tableName)
                );
 
                foreach ($columns as $columnName => $definition) {  
                    
                    $options = $definition;
                    unset($options['type']);
                    unset($options['length']);
                    unset($options['comment']);
                     
                    $table->addColumn( $columnName, 
                                       $definition['type'], 
                                       ($definition['length']!='' ? $definition['length'] : null), 
                                       $options, 
                                       $definition['comment']);
        
                }
                
                $connection->createTable($table); 

            }
            else{
            
                $table = $setup->getTable($tableName); 
        
                foreach ($columns as $columnName => $definition) {  

                    if ($connection->tableColumnExists($table, $columnName) === false) {
                
                        $connection->addColumn( $table, $columnName, $definition);

                    } 

                }
            
            }
        
        }


        $setup->endSetup();
    }
}
