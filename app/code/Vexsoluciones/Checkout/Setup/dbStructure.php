<?PHP 
 
namespace Vexsoluciones\Checkout\Setup;

/*
	Tablas a crear
	Departamento dep_id | nombre | observacion | estado | shipping_activo 
	Provincia    prov_id | dep_id | observacion | estado | shipping_activo 
	Distrito    prov_id | dep_id | dist_id | observacion | estado | shipping_activo 
*/  

class dbStructure{

	public function get(){
 
		$dbStructure = [
  
			'quote_address' => [

				'columns' => [

					'departamento_id' => [
					    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
					    'length' => 11, 
					    'nullable' => false,
					    'unsigned' => true,
					    'comment' => '.'
					], 
					'provincia_id' => [
					    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
					    'length' => 11, 
					    'nullable' => false,
					    'unsigned' => true, 
					    'comment' => '.'
					], 
					'distrito_id' => [
					    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
					    'length' => 11, 
					    'nullable' => false,
					    'unsigned' => true, 
					    'comment' => '.'
					], 
			
				 	'delivery_location' => [
					   'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
					   'length' => 30,
					   'default' => '',
					   'nullable' => false,
					   'comment' => '.'
					],
				  
				]
			],

			'sales_order_address' => [

				'columns' => [
 
			 		'departamento_id' => [
			 		    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
			 		    'length' => 11, 
			 		    'nullable' => false,
			 		    'unsigned' => true,
			 		    'comment' => '.'
			 		], 
			 		'provincia_id' => [
			 		    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
			 		    'length' => 11, 
			 		    'nullable' => false,
			 		    'unsigned' => true, 
			 		    'comment' => '.'
			 		], 
			 		'distrito_id' => [
			 		    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
			 		    'length' => 11, 
			 		    'nullable' => false,
			 		    'unsigned' => true, 
			 		    'comment' => '.'
			 		],
			 		
				 	'delivery_location' => [
					   'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
					   'length' => 30,
					   'default' => '',
					   'nullable' => false,
					   'comment' => '.'
					],
 
				]
			]



		];


		return $dbStructure;


	}

}

  