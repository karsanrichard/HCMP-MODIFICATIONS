<?php
class Sync_model extends Doctrine_Record {
	public static function get_latest_timestamp(){
		// $query = $this->db->query("SELECT * FROM db_sync WHERE added_on=(SELECT MAX(added_on) FROM db_sync)");
		$query = Doctrine_Manager::getInstance()->getCurrentConnection()->fetchAll("SELECT * FROM db_sync WHERE last_sync=(SELECT MAX(last_sync) FROM db_sync)");

		// echo "<pre>This ";print_r($query);exit;
		// $result = $query->result_array();
		if (!empty($query)) {
			return $query[0]['last_sync'];
		}
		else{
			return $query;
		}
	}

	public static function get_sync_data(){
		$query = Doctrine_Manager::getInstance()->getCurrentConnection()->fetchAll("SELECT * FROM db_sync ORDER BY last_sync DESC");
		// $result = $query->result_array();
		return $query;
	}

	public static function get_new_data($table_name,$last_sync_time = NULL,$timestamp_column_name = NULL){
		$ci =& get_instance();
		
		$time_constraint = (isset($last_sync_time) && $last_sync_time!='')? "WHERE $timestamp_column_name BETWEEN '$last_sync_time' and NOW()" :NULL;
		// echo "SELECT * FROM $table_name $time_constraint<pre>";
		// $query = $ci->db->query("SELECT * FROM $table_name $time_constraint");
		// echo "I run";exit;
		$query = Doctrine_Manager::getInstance()->getCurrentConnection()->fetchAll("SELECT * FROM $table_name $time_constraint");

		// $result = $query->result_array();
		// echo "<pre>";print_r($query);exit;
		return $query;
	}

	public static function update_last_sync($facility_code){
		$query = $this->db->query("UPDATE sync_data SET last_sync_date = NOW() WHERE facility_code = '$facility_code'");
		$run_result = $query->result_array();
		if($run_result) return true;
		else return false;
	}

}
?>