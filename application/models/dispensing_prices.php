<?php 
class Dispensing_prices extends Doctrine_Record{

	public function setTableDefinition(){
		$this -> hasColumn('id','int');
		$this -> hasColumn('facility_code','int');
		$this -> hasColumn('commodity_id', 'int');
		$this -> hasColumn('commodity_name', 'varchar', 200);
		$this -> hasColumn('price', 'int');
		$this -> hasColumn('price_per','varchar', 45);
	}

	public function setUp(){
		$this -> setTableName('dispensing_stock_prices');
	}

	public static function save_prices($data_array){
		$o = new Dispensing_prices();
		$o -> fromArray($data_array);
		$o -> save();
		return TRUE;
	}
}
?>