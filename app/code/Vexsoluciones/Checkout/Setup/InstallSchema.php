<?php
/**
 * Copyright © 2015 Vexsoluciones. All rights reserved.
 */

namespace Vexsoluciones\Checkout\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\DB\Adapter\AdapterInterface;


class InstallSchema implements InstallSchemaInterface
{
    private $dbStructure = null;

    public function __construct(dbStructure $dbStructure){

        $this->dbStructure = $dbStructure->get();
    }

    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
    
        $setup->startSetup();
        
        $table  = $setup->getConnection()
            ->newTable($setup->getTable('vexsoluciones_ubigeo_departamento_costarica'))
            ->addColumn(
                'id',
                Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                'Id'
            )
            ->addColumn(
                'idDepa',
                Table::TYPE_INTEGER,
                null,
                ['default' => 0],
                'id depa'
            )
            ->addColumn(
                'departamento',
                Table::TYPE_TEXT,
                null,
                ['default' => null],
                'departamento'
            )
            ->addColumn(
                'pais',
                Table::TYPE_TEXT,
                null,
                ['default' => null],
                'pais'
            )
			->setComment(
				'Departamento'
			);
        $setup->getConnection()->createTable($table);


        $table  = $setup->getConnection()
            ->newTable($setup->getTable('vexsoluciones_ubigeo_provincia_costarica'))
            ->addColumn(
                'id',
                Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                'Id'
            )
            ->addColumn(
                'idProv',
                Table::TYPE_INTEGER,
                null,
                ['default' => 0],
                'id idProv'
            )
            ->addColumn(
                'provincia',
                Table::TYPE_TEXT,
                null,
                ['default' => null],
                'provincia'
            )
            ->addColumn(
                'idDepa',
                Table::TYPE_INTEGER,
                null,
                ['default' => 0],
                'id depa'
            )
            ->setComment(
                'Provincia'
            );
        $setup->getConnection()->createTable($table);


        $table  = $setup->getConnection()
            ->newTable($setup->getTable('vexsoluciones_ubigeo_distrito_costarica'))
            ->addColumn(
                'id',
                Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                'Id'
            )
            ->addColumn(
                'idDist',
                Table::TYPE_TEXT,
                null,
                ['default' => null],
                'id idDist'
            )
            ->addColumn(
                'distrito',
                Table::TYPE_TEXT,
                null,
                ['default' => null],
                'distrito'
            )
            ->addColumn(
                'idProv',
                Table::TYPE_INTEGER,
                null,
                ['default' => 0],
                'id prov'
            )
            ->setComment(
                'Distrito'
            );
        $setup->getConnection()->createTable($table);


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
