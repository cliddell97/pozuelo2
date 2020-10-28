<?PHP 
 
namespace Vexsoluciones\Checkout\Setup;

/*
	Tablas a crear
	Departamento dep_id | nombre | observacion | estado | shipping_activo 
	Provincia    prov_id | dep_id | observacion | estado | shipping_activo 
	Distrito    prov_id | dep_id | dist_id | observacion | estado | shipping_activo 
*/  

class dbStructureUpgrade{

	public function get(){
 
		$dbStructure = [
  
			'quote_address' => [

				'columns' => [

				 	'programado' => [
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
 
			 		
				 	'programado' => [
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

  