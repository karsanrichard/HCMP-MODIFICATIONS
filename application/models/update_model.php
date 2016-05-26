<?php
class Update_model extends Doctrine_Record {
	public function setTableDefinition() {
	
	}

	public static function get_latest_local_timestamp($time_only = NULL,$whole_array = NULL)
	{
		$query = Doctrine_Manager::getInstance()->getCurrentConnection()->fetchAll("SELECT * FROM update_log WHERE added_on=(SELECT MAX(added_on) FROM update_log)");

		// $return_type = (isset($time_only) && $time_only == 1)? 1:NULL;
		$return_type = NULL;
		if ($return_type == 1) {
			return $query[0]['added_on'];
		}
		else{
			return $query;
		}
	}

	public static function get_prior_records(){
		$log_data = Doctrine_Manager::getInstance()->getCurrentConnection()
		->fetchAll("SELECT * FROM update_log");
		
		return $log_data;
	}

	public static function get_system_files($type){
		$sql = "select su.*,  concat(u.fname,' ', u.lname) as uploader from update_log su, user u where su.user_id = u.id and su.type = '$type' and su.status = 0 order by su.added_on desc";	
		$file_data = Doctrine_Manager::getInstance()->getCurrentConnection()
		->fetchAll("$sql");
		
		return $file_data;
	}

}