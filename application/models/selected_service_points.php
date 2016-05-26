<?php
/**
 * @author Kariuki
 */
class Selected_service_points extends Doctrine_Record {
		
	public function setTableDefinition()
	{
		        $this->hasColumn('id', 'int');
				$this->hasColumn('facility_code', 'varchar',255);
				$this->hasColumn('service_point_id', 'int');
				$this->hasColumn('status', 'int');	
	}

	public function setUp() {
		$this -> setTableName('selected_service_points');				
	}
   
	public static function get_dispensing_point($facility_code) {
   	
		$query = Doctrine_Query::create() 
		-> select("*") -> from("selected_service_points")->where("facility_code=$facility_code");
		$service_points = $query -> execute();
		return $service_points;
	}//save the data on to the table 
	
	
	
}
?>
