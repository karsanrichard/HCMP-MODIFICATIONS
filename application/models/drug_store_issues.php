<?php 
class drug_store_issues extends Doctrine_Record {
		
	public function setTableDefinition()
	{
		$this -> hasColumn('id', 'int');
		$this -> hasColumn('facility_code', 'int'); 
		$this -> hasColumn('district_id', 'int'); 
		$this -> hasColumn('commodity_id', 'int'); 
		$this -> hasColumn('s11_No', 'int'); 
	    $this -> hasColumn('batch_no', 'varchar',20); 
		$this -> hasColumn('expiry_date', 'date'); 
		$this -> hasColumn('balance_as_of', 'int'); 
		$this -> hasColumn('adjustmentpve', 'int');
		$this -> hasColumn('adjustmentnve', 'int');
		$this -> hasColumn('qty_issued', 'int');
		$this -> hasColumn('date_issued', 'date'); 
		$this -> hasColumn('issued_to', 'varchar', 200);
		$this -> hasColumn('issued_by', 'int', 12);
		$this -> hasColumn('status', 'int');
			
	}

	////dumbing data into the issues table
	//dude...dumbing? really???
	public static function update_drug_store_issues_table($data_array){
		$s = new drug_store_issues();
	    $s->fromArray($data_array);
		$s->save();
		return TRUE;
	}

	public static function get_store_commodity_total($district_id,$commodity_id=null){
		$commodity_id=isset($commodity_id)?"dst.commodity_id=$commodity_id" : null;
		$total_store_comm = Doctrine_Manager::getInstance()->getCurrentConnection()
		->fetchAll("SELECT
		c.id,ifnull(sum(dst.total_balance),0) as commodity_balance
		from commodities c,drug_store_totals dst
		where dst.district_id= '$district_id' and dst.commodity_id = c.id and dst.commodity_id = $commodity_id and dst.status = 1 group by dst.commodity_id
		");
		return $total_store_comm;
	}

	public static function check_drug_existence($commodity_id=NULL,$district_id = NULL){
		$commodity_info = $commodity_id;
		$existence = Doctrine_Manager::getInstance()->getCurrentConnection()->
		fetchAll("SELECT COUNT(commodity_id) AS present FROM drug_store_totals where commodity_id = '$commodity_id' AND district_id = '$district_id'");
		return $existence;
	}

	public static function update_redistribution_data($commodity_id=NULL,$district_id = NULL){
		$commodity_info = $commodity_id;
		$existence = Doctrine_Manager::getInstance()->getCurrentConnection()->
		fetchAll("SELECT COUNT(commodity_id) AS present FROM drug_store_totals where commodity_id = $commodity_id AND district_id = $district_id");
		return $existence;
	}

	public static function check_transaction_existence($commodity_id=NULL,$facility_code = NULL){
		$commodity_info = $commodity_id;
		$existence = Doctrine_Manager::getInstance()->getCurrentConnection()->
		fetchAll("SELECT COUNT(commodity_id) AS present FROM drug_store_transaction_table where commodity_id = $commodity_id AND facility_code = $facility_code");
		return $existence;
	}

	public static function check_transaction_existence_county($commodity_id=NULL,$facility_code = NULL){
		$commodity_info = $commodity_id;
		$existence = Doctrine_Manager::getInstance()->getCurrentConnection()->
		fetchAll("SELECT COUNT(commodity_id) AS present FROM county_drug_store_transaction_table where commodity_id = $commodity_id AND facility_code = $facility_code");
		return $existence;
	}

	public static function check_internal_transaction_existence($commodity_id=NULL,$district_id = NULL){
		$commodity_info = $commodity_id;
		$existence = Doctrine_Manager::getInstance()->getCurrentConnection()->
		fetchAll("SELECT COUNT(commodity_id) AS present FROM drug_store_internal_transaction_table where commodity_id = $commodity_id AND district_id = $district_id");
		return $existence;
	}

	public static function get_commodities_for_district($district_id = NULL){
		$commodities = Doctrine_Manager::getInstance()->getCurrentConnection()->
		fetchAll("SELECT * FROM `drug_store_totals` WHERE `district_id` = '$district_id'");
		return $commodities;
	}
}

 ?>