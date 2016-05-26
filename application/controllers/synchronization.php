<?php 
/**
 * @author Karsan
 */
if (!defined('BASEPATH'))
	exit('No direct script access allowed');

class Synchronization extends MY_Controller {

	function __construct() {
		parent::__construct();
		// ini_set('display_errors', 1);
		// ini_set('display_startup_errors', 1);
		// error_reporting(E_ALL);
	}

	public function index($sync_status = NULL){
		// echo $sync_status;exit;
		$data['title'] = "System Database Updates";
		$time = sync_model::get_latest_timestamp();
		$sync_data = sync_model::get_sync_data();
		$update_status = $this->system_update_status();

		$local_timestamp = update_model::get_latest_local_timestamp();
		 // echo "<pre>";print_r($update_status);exit();
		// echo "<pre>";print_r($time);
		// echo "<pre>";print_r(date('F, d Y H:i:s',strtotime($time)));exit;
		$data['sync_data'] = $sync_data;
		$data['sync_status'] = $sync_status;
		$data['update_status'] = $update_status;
		// echo $status;exit;
		$data['last_sync'] = $time;
		$data['banner_text'] = "Database Synchronization";		
		$template = 'shared_files/template/template';			
		$data['content_view'] = 'facility/facility_db';			
		
		$this -> load -> view($template, $data);
	}

	public function latest_update_time($return_type = NULL)//respective to local and server copy
	{
		$update_time = update_model::get_latest_local_timestamp($return_type);

		// echo "<pre>";print_r($update_time);exit();
		// echo $update_time;
		// $update_time = json_encode($update_time,JSON_PRETTY_PRINT);

		echo json_encode($update_time);
		// $update_time = json_decode($update_time,true);
		// print_r($update_time);
	}
	
	public function last_sync_time(){
		$time = sync_model::get_latest_timestamp();
		return $time[0]['last_sync_date'];
	}

	public function tester(){
		$file_name = "14582_Sat16_130414.txt";
		$file = FCPATH.'sync_files\\'.$file_name;
		// $file = "C:/xampp/htdocs/HCMP/sync_files/14582_Sat16_130414.txt";
		echo $file;
		$status = $this->ftp_upload($file,$file_name);

		// echo "<pre>";print_r($status);exit;
		$data['content_view'] = "testers/sync";
		$template = 'shared_files/template/template';
		$this -> load -> view($template, $data);

	}

	public function sync(){
		$facility_code = $this->session->userdata('facility_id');
		// echo $facility_code;exit;
	    $all_tables = $this->db->list_tables();
	    // $fields = $this->db->field_data('dispensing_records');
	    // echo "<pre>";print_r($fields);

	    $tables_to_be_ignored = array('access_level','assignments','comments','commodities','commodity_category','commodity_division_details','commodity_source','commodity_sub_category','counties','county_drug_store_issues','county_drug_store_totals','county_drug_store_transaction_table','districts','drug_commodity_map','drug_store_issues','drug_store_totals','drug_store_transaction_table','email_listing','email_listing_new','facilities','git_log','issue_type','menu','malaria_drugs','rca_county','recepients','sub_menu','service_points','log','log_monitor','db_sync','facility_loggins','facility_rollout_status','inventory','status','sync_updates','tuberculosis_data','commodity_source_sub_category','malaria_data','update_log');//add tables to be ignored by database sync

	    foreach ($tables_to_be_ignored as $key => $value) {
		    if(($key = array_search($value, $all_tables)) !== false) {
		    	unset($all_tables[$key]);
		    }
		}
	    // echo "<pre>";print_r($all_tables);
	    $all_tables_amped = array();
	    $all_tables_data = array();

	    foreach ($all_tables as $key => $table_name) {
			$timestamp_column = $this->get_column_by_type($table_name,'timestamp');
	    	// echo $timestamp_column."<pre>";
	    	$all_tables_data = array($table_name,$timestamp_column);
	    	// echo "<pre>";print_r($all_tables_data);
	    	array_push($all_tables_amped,$all_tables_data);
			// $key['timestamp_column'] = $timestamp_column;
	    }
	    // echo "<pre>";print_r($all_tables); exit;
	    // echo "<pre>";print_r($all_tables_amped);exit;
		// $all_tables = array_values($all_tables);//index reset

	    $latest_sync_time = sync_model::get_latest_timestamp();
	    $latest_sync_time = empty($latest_sync_time)?'':$latest_sync_time;

	    // echo "<pre>";print_r($latest_sync_time);exit;
	    $final_data_from_table = array();
	    $table_count = count($all_tables);

		
	    $data_from_table['facility_code'] = $facility_code;
	    $ts = array();
		ini_set("memory_limit","-1");
		// $timestamp_column = $this->get_column_by_type('facility_stocks','timestamp');
		// echo $timestamp_column;exit;

	    foreach ($all_tables_amped as $key => $table_data) {
			// $ts[$table_name] = $timestamp_column;
			$table_name = $table_data[0];
			$timestamp_column = $table_data[1];
			// echo "<pre>";print_r($timestamp_column);
			$pulled_data = Sync_model::get_new_data($table_name,$latest_sync_time,$timestamp_column);
		    $data_from_table[$table_name] = json_encode($pulled_data);
	   		// echo "<pre>";print_r(json_encode($pulled_data));
        }
        // exit;
	   		// echo "<pre>";print_r($data_from_table);exit;

		// echo "<pre>";print_r($ts);exit;
		// $write_to_file = $this->receive_data($facility_code,$data_from_table);
		
		// echo FCPATH;exit;
		// echo $write_to_file;exit;
		/*
		ini_set('display_errors', 1);
		ini_set('display_startup_errors', 1);
		error_reporting(E_ALL);
		*/
		// $stringify = print_r($data_from_table,true);

		// $queried_data = http_build_query($data_from_table);
		$data_from_table = json_encode($data_from_table);
		// echo "<pre>";print_r($data_from_table);exit;
		// echo "<pre> I reach here ";echo json_encode($data_from_table);exit;
		$file_write = $this->receive_data($facility_code,$data_from_table);

		// echo "<pre>";print_r($file_write);
		$sync_status = (isset($file_write) && $file_write == 1)? 1 : 0;
		redirect(base_url()."synchronization/index/".$sync_status);
		// $this->index($sync_status);
		// echo "<pre>";print_r($queried_data);exit;
		// $url = "41.89.6.209/hcmp_demo/synchronization/receive_data/?facility_code=".$facility_code.'?data='.$queried_data;
		
		// $local_url = base_url().'synchronization/receive_post';
		// $result = $this->post_data($local_url,$data_from_table);
		// echo $result;exit;
		// // echo $url;exit;
		// $ch = curl_init($url);
		// $status = curl_exec($ch);
		// echo "<pre>";print_r($status);exit;
	        // var_dump($data_from_table);

	}

	public function receive_data($facility_code,$data)
	{
		// echo "<pre>";print_r($this->input->post());exit;
		// echo "<pre>";print_r($data);exit;
		// ini_set("memory_limit","900M");
		// ini_set('display_errors', 1);
		// ini_set('display_startup_errors', 1);
		// error_reporting(E_ALL);
		$stringify = print_r($data,true);
		$filestamp = $this->generate_filestamp();
		// echo FCPATH."<\br>";
		// echo $_SERVER['DOCUMENT_ROOT']."<br>";
		// if(!file_exists(dirname(FCPATH.'sync_files'))): 
		//     mkdir(dirname(FCPATH.'sync_files'));
		// endif;
		$file_name = $filestamp.'_'.$facility_code;
		$file_name_ = $filestamp.'_'.$facility_code.'.txt';
		$file_path = FCPATH.'sync_files\\'.$file_name.'.txt';
		$file = trim($file_path);
		$fp = fopen($file, 'w') or die('Cannot open file: '.$file);;

		fwrite($fp, $stringify);
		chmod($file, 0777); 

		/*zipping the file*/
		$zip = new ZipArchive();
		$zip_name = '/sync_files/'.$file_name.'.zip';
		$zip->open(ltrim($zip_name,'/'), ZipArchive::CREATE);
		// $expected_filepath = "";
		$zip->addFile($file_path, ltrim($file_name_,'/'));

		$zip->close();

		$zip_file_name = $file_name.'.zip';
		$zip_file_path = FCPATH.'sync_files\\'.$zip_file_name;
		// $zip_file_path = trim($zip_file_path);

		//upload file to ftp folder on the server
		$transfer = $this->ftp_upload($zip_file_path,$zip_file_name);
		// echo $transfer;
		$remote_file_ = 'http://41.89.6.209/HCMP/synchronization/insert_filename/'.$zip_file_name.'/'.$facility_code;
		file($remote_file_);
		$query = $this->db->query("INSERT INTO `db_sync` (`facility_code`) VALUES ('$facility_code')");
		// echo $query;exit;
		return $transfer;
	}

	public function insert_filename($zip_file_name,$facility_code){
		$query = $this->db->query("INSERT INTO `ftp_uploads` (`facility_code`,`file_name`) VALUES ('$facility_code','$zip_file_name')");
	}
	public function days_from_last_sync()
	{
	    // echo date("Dd_Hms");
	    
		$date_from_last_sync = sync_model::get_latest_timestamp();
		$date_from_last_sync = (!empty($date_from_last_sync) && $date_from_last_sync!='')?$date_from_last_sync:NULL;
		if (!empty($date_from_last_sync)) {
			//Our dates
			$now = time();
			$now_days = floor($now / (60*60*24) );
		    $days = (time() - strtotime($date_from_last_sync)) / (60 * 60 * 24);
			echo abs(round($days,0));
		}else{
			echo "FIRST";
		}

	}

	public function retrieve_column_data()
	{
		$fields = $this->db->list_fields('access_level');		
		echo "<pre>";print_r($fields);exit;
	}

	public function timestamps()//it's all shits and giggles until someone giggles and shits
	{
		$res = $this->add_timestamps_to_all_tables();
		echo $res;
	}

}
?>