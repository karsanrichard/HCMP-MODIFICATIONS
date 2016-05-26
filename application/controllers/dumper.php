s<?php 
/**
 * @author Karsan
 */
if (!defined('BASEPATH'))
	exit('No direct script access allowed');

class Dumper extends MY_Controller {

	function __construct() {
		parent::__construct();
		//this comment is for you nigga
		//erase it 
		//hahaha
		// $this->load->model('dumper_model','dumper_model');
	}

	public function create_zip($facility_code)
	{
		$this->update_rollout_status($facility_code);
		$this->create_core_tables($facility_code);
		$this->create_bat($facility_code);

		$sql_filepath = 'tmp/'.$facility_code.'.sql';
		$expected_zip_sql_filepath = $facility_code.'/'.$facility_code.'.sql';

		$bat_filepath = 'tmp/'.$facility_code.'_install_db.bat';
		$expected_zip_bat_filepath = $facility_code.'/'.$facility_code.'_install_db.bat';

		$ini_filepath = 'offline/my.ini';
		$expected_ini_filepath = $facility_code.'/'.'my.ini';

		$expected_old_filepath = $facility_code.'/old/';

		$zip = new ZipArchive();
		$zip_name = $facility_code.'.zip';
		$zip->open($zip_name, ZipArchive::CREATE);

		$zip->addFile($sql_filepath, ltrim($expected_zip_sql_filepath,'/'));
		$zip->addFile($bat_filepath, ltrim($expected_zip_bat_filepath,'/'));
		$zip->addEmptyDir($expected_old_filepath);

		$zip->addFile($ini_filepath, ltrim($expected_ini_filepath,'/'));

		$zip->close();
		// ob_end_clean();
		header("Cache-Control: public");
		header("Content-Description: File Transfer");
		// header("Content-Length: ". filesize("$zip_name").";");
		header("Content-Disposition: attachment; filename=$zip_name");
		header("Content-type: application/zip"); 
		header("Content-Transfer-Encoding: binary");

		readfile($zip_name);

		unlink($sql_filepath);
		unlink($bat_filepath);
		unlink($zip_name);
		// echo "$final_output_bat";

		// echo "I worked";
		// echo "Expecto patronum";
	}


	public function update_rollout_status($facility_code){
		$date = date('Y-m-d h:i:s');
		$sql ="update facilities set using_hcmp = '2',date_of_activation= '$date' where facility_code = '$facility_code'";
		$this->db->query($sql);
	}


	public function dump_db($facility_code,$db){
		$this->create_core_tables($facility_code,$db);		
	}

	public function gen_bat($facility_code){
		$db = 'hcmp_rtk';
		$this->create_bat($facility_code);
		$this->dump_db($facility_code,$db);
	}

	// public function set_up_folder($facility_code)
	// {
	// 	$offline_path =  FCPATH.'offline/';
	// 	$file_path = $offline_path.$facility_code;
	// 	$file_path_old_cnf = $offline_path.$facility_code.'/old';
	// 	$ini_path = $offline_path.'my.ini';
	// 	$file_path_ini = $offline_path.$facility_code.'/my.ini';
	// 	mkdir ($file_path,0777, true);		
	// 	copy($ini_path, $file_path_ini);
	// 	mkdir ($file_path_old_cnf,0777, true);
	// 	return $file_path;		
	// }

public function create_bat($facility_code)
 	{ 		
		ini_set('memory_limit', '-1');   		
   		$filename = 'tmp/'.$facility_code.'_install_db.bat';
   		$resource_name = $facility_code.'.sql';
   		$header = '@echo OFF';
   		$header .= PHP_EOL;
   		$header .= 'set current=%~dp0';
   		$header .= PHP_EOL;

   		$header .= "set old_cnf=old\\";
   		$header .= PHP_EOL;
   		$header .= "set target_adt=C:\\xampp\\htdocs\\ADT";
   		$header .= PHP_EOL;
   		$header .= "set old_cnf_file=old\\my.ini";
   		$header .= PHP_EOL;
   		//$header .= "set new_cnf=resources\\mysql\\new\\";
   		$header .= PHP_EOL;
   		$header .= "set new_cnf_file=my.ini";
   		$header .= PHP_EOL;

   		$header .= "set target_cnf=C:\\xampp\\mysql\\bin\\";
   		$header .= PHP_EOL;
   		$header .= "set target_cnf_file=C:\\xampp\\mysql\\bin\\my.ini";
   		$header .= PHP_EOL;


   		$header .= "net stop Apache2.4";
   		$header .= PHP_EOL;
   		$header .= "net stop MySQL";
   		$header .= PHP_EOL;   

   		$header .= "move \"%target_cnf_file%\" \"%current%%old_cnf%\"";
   		$header .= PHP_EOL;   

   		$header .= "xcopy /s \"%current%%new_cnf_file%\" \"%target_cnf%\"";
   		$header .= PHP_EOL;   



   		$header .= "net start Apache2.4";
   		$header .= PHP_EOL;
   		$header .= "net start MySQL";
   		$header .= PHP_EOL;
   		$header .= "if not exist \"%target_adt%\" (";
   		$header .= PHP_EOL;
   		$data = "\tC:\\xampp\mysql\bin\mysql.exe -u root test<\"%current%\"$facility_code.sql";
   		$data.=PHP_EOL;
   		$data .= ") else (";
   		$data .= PHP_EOL;
   		$data .= "\tC:\\xampp\mysql\bin\mysql.exe -u root -proot test<\"%current%\"$facility_code.sql";
   		$data .= PHP_EOL;
 		$data .= ")";
 		$data .= PHP_EOL;
 		$header_end = "net stop Apache2.4";
   		$header_end .= PHP_EOL;
   		$header_end .= "net stop MySQL";
   		$header_end .= PHP_EOL;   

   		$header_end .= "del \"%target_cnf_file%\"";
   		$header_end .= PHP_EOL;   

   		$header_end .= "move \"%current%%old_cnf_file%\" \"%target_cnf%\"";
   		$header_end .= PHP_EOL;

   		$header_end .= "net start Apache2.4";
   		$header_end .= PHP_EOL;
   		$header_end .= "net start MySQL";
   		$header_end .= PHP_EOL;

   		$header_end .="pause";

 		$query = $header.$data.$header_end;
		$handle = fopen($filename, 'w');		
		$final_output_bat = $query;
		fwrite($handle, $final_output_bat);
		fclose($handle);
		// echo $handle;exit;	

		// header("Cache-Control: public");
		// header("Content-Description: File Transfer");
		// header("Content-Length: ". filesize("$filename").";");
		// header("Content-Disposition: attachment; filename=$filename");
		// header("Content-Type: application/octet-stream; "); 
		// header("Content-Transfer-Encoding: binary");

		// echo "$final_output_bat";
 	}
   

 	// public function create_bat($facility_code)
 	// { 		
		// ini_set('memory_limit', '-1');   		
  //  		$filename = 'tmp/'.$facility_code.'_install_db.bat';
  //  		$resource_name = $facility_code.'.sql';
  //  		$header = '@echo OFF';
  //  		$header .= PHP_EOL;
  //  		$header .= 'set current=%~dp0';
  //  		$header .= PHP_EOL;

  //  		$header .= "set old_cnf=resources\\mysql\\old\\";
  //  		$header .= PHP_EOL;
  //  		$header .= "set old_cnf_file=resources\\mysql\\old\\my.ini";
  //  		$header .= PHP_EOL;
  //  		$header .= "set new_cnf=resources\\mysql\\new\\";
  //  		$header .= PHP_EOL;
  //  		$header .= "set new_cnf_file=resources\\mysql\\new\\my.ini";
  //  		$header .= PHP_EOL;

  //  		$header .= "set target_cnf=C:\\xampp\\mysql\\bin\\";
  //  		$header .= PHP_EOL;
  //  		$header .= "set target_cnf_file=C:\\xampp\\mysql\\bin\\my.ini";
  //  		$header .= PHP_EOL;


  //  		$header .= "net stop Apache2.4";
  //  		$header .= PHP_EOL;
  //  		$header .= "net stop MySQL";
  //  		$header .= PHP_EOL;   

  //  		$header .= "move \"%target_cnf_file%\" \"%current%%old_cnf%\"";
  //  		$header .= PHP_EOL;   

  //  		$header .= "xcopy /s \"%current%%new_cnf_file%\" \"%target_cnf%\"";
  //  		$header .= PHP_EOL;   



  //  		$header .= "net start Apache2.4";
  //  		$header .= PHP_EOL;
  //  		$header .= "net start MySQL";
  //  		$header .= PHP_EOL;
  //  		$data = "C:\\xampp\mysql\bin\mysql.exe -u root hcmp_rtk<\"%current%\"$facility_code.sql";
  //  		$data.=PHP_EOL;
 	// 	// $query = "C:\\xampp\mysql\bin\mysql.exe -u root -p hcmp_rtk<".$resource_name;
 	// 	$header_end .= "net stop Apache2.4";
  //  		$header_end .= PHP_EOL;
  //  		$header_end .= "net stop MySQL";
  //  		$header_end .= PHP_EOL;   

  //  		$header_end .= "del \"%target_cnf_file%\"";
  //  		$header_end .= PHP_EOL;   

  //  		$header_end .= "move \"%current%%old_cnf_file%\" \"%target_cnf%\"";
  //  		$header_end .= PHP_EOL;

  //  		$header_end .= "net start Apache2.4";
  //  		$header_end .= PHP_EOL;
  //  		$header_end .= "net start MySQL";
  //  		$header_end .= PHP_EOL;

  //  		$header_end .="pause";

 	// 	$query = $header.$data.$header_end;
		// $handle = fopen($filename, 'w');		
		// $final_output_bat = $query;
		// fwrite($handle, $final_output_bat);
		// fclose($handle);
		// // echo $handle;exit;	

		// // header("Cache-Control: public");
		// // header("Content-Description: File Transfer");
		// // header("Content-Length: ". filesize("$filename").";");
		// // header("Content-Disposition: attachment; filename=$filename");
		// // header("Content-Type: application/octet-stream; "); 
		// // header("Content-Transfer-Encoding: binary");

		// // echo "$final_output_bat";
 	// }
 	
   public function create_core_tables($facility_code,$database=null){
   		$database = (isset($database)) ? $database : 'hcmp_rtk';
   		$mysqli = new mysqli("localhost", "root", "", "hcmp_rtk");
	   	 if (mysqli_connect_errno()) {
		    printf("Connect failed: %s", mysqli_connect_error());
		    exit();
		}
	  	ini_set('memory_limit', '-1');
   		// $filename ='db_hcmp.sql';	
   		$filename = 'tmp/'.$facility_code.'.sql';	
   		$header = "DROP DATABASE IF EXISTS `$database`;\n\nCREATE DATABASE `$database`;\n\nUSE `$database`;\n\n";
   		$query = '';
		$handle = fopen($filename, 'w');
		$core_tables = array('access_level','assignments','comments','commodities','commodity_category','commodity_division_details','commodity_source','commodity_source_other','commodity_source_sub_category','commodity_sub_category','counties','county_drug_store_issues','county_drug_store_totals','county_drug_store_transaction_table','districts','drug_commodity_map','drug_store_issues','drug_store_totals','drug_store_transaction_table','email_listing','email_listing_new','facilities','git_log','issue_type','menu','malaria_drugs','rca_county','recepients','sub_menu','service_points','redistribution_data','receive_redistributions','log','log_monitor','db_sync','facility_order_status','facility_order_details_rejects','facility_order_details','dispensing_records','facility_loggins','facility_rollout_status','inventory','patient_issues','patients','selected_service_points','service_point_stock_physical','status','sync_updates','update_log','dispensing_totals');



		$facility_specific_tables = array('facility_issues'=>'facility_code',
										  'commodity_source_other_prices'=>'facility_code',
										  'dispensing_stock_prices'=>'facility_code',
										  'facility_amc'=>'facility_code',
										  'facility_amc1'=>'facility_code',
										  'facility_stocks_temp'=>'facility_code',
										  'facility_transaction_table'=>'facility_code',
										  'malaria_data'=>'facility_id',
										  'patient_details'=>'facility_code',
										  'reversals'=>'facility_code',
										  'rh_drugs_data'=>'facility_code',
										  'service_point_stocks'=>'facility_code',
										  'facility_evaluation'=>'facility_code',
										  'facility_impact_evaluation'=>'facility_code',
										  'facility_monthly_stock'=>'facility_code',
										  'facility_orders'=>'facility_code',
										  'facility_stock_out_tracker'=>'facility_code',
										  'user'=>'facility',
										  'tuberculosis_data'=>'facility_code',
										  'requisitions'=>'facility',
										  'facility_stock_out_tracker'=>'facility_code',
										  'facility_stocks'=>'facility_code');
		
		
		for ($i=0; $i <count($core_tables) ; $i++) { 
			$query .= $this->create_inserts($core_tables[$i],null,$mysqli);
		}
		
		foreach ($facility_specific_tables as $key => $value) {
			$table_name = $key;
			$where = "WHERE $value = '$facility_code'";			
			$query .= $this->create_inserts($table_name,$where,$mysqli);
		}


		$query.=$this->show_views($mysqli,'hcmp_rtk');
		$query.=$this->show_procedures($mysqli);

		$mysqli->close();//die();
		
		$final_output = $header.$query;
		fwrite($handle, $final_output);
		fclose($handle);

		// header("Cache-Control: public");
		// header("Content-Description: File Transfer");
		// header("Content-Length: ". filesize("$filename").";");
		// header("Content-Disposition: attachment; filename=$filename");
		// header("Content-Type: application/octet-stream; "); 
		// header("Content-Transfer-Encoding: binary");

		// echo "$final_output";
   }
  
  public function show_procedures($mysqli){
  	$sql_select_procedures = "show procedure status;";  
	$create_proc = '';
    $create_statements = '';
   	$rows_total = null;   	 
	if ($result = $mysqli->query($sql_select_procedures)) {
		$result_select = mysqli_fetch_all($result,MYSQLI_ASSOC);
		foreach ($result_select as $key => $value) {
		 	$proc_name = $value['Name'];
		 	$create_statements.="\nDROP PROCEDURE IF EXISTS $proc_name;\n";		 	
		 	$sql_creates = "SHOW CREATE PROCEDURE $proc_name";		 	
		 	if ($result_sql = $mysqli->query($sql_creates)) {		    	
		    	$result_creates = mysqli_fetch_all($result_sql,MYSQLI_ASSOC);	
		    	foreach ($result_creates as $proc => $procvalue) {
		    		$proc_create_stmt = $procvalue['Create Procedure'];
		    		$create_statements.="DELIMITER $$ \n";
		    		$create_statements.=$proc_create_stmt.';';
		    		$create_statements.="$$ \nDELIMITER;\n";
		    	}
		    }
		} 
	}

	return $create_statements;
  }

public function show_views($mysqli,$database){
	$sql_select_procedures = "SELECT TABLE_SCHEMA, TABLE_NAME FROM information_schema.tables WHERE TABLE_TYPE LIKE 'VIEW' AND TABLE_SCHEMA LIKE '$database'";    	
	$create_proc = '';
	$create_statements = '';
		$rows_total = null;   	 
	if ($result = $mysqli->query($sql_select_procedures)) {
		$result_select = mysqli_fetch_all($result,MYSQLI_ASSOC);
		foreach ($result_select as $key => $value) {
		 	$proc_name = $value['TABLE_NAME'];
		 	$create_statements.="\nDROP PROCEDURE IF EXISTS $proc_name;\n";		 	
		 	$sql_creates = "SHOW CREATE VIEW $proc_name";		 	
		 	if ($result_sql = $mysqli->query($sql_creates)) {		    	
		    	$result_creates = mysqli_fetch_all($result_sql,MYSQLI_ASSOC);	
		    	foreach ($result_creates as $proc => $procvalue) {
		    		$proc_create_stmt = $procvalue['Create View'];
		    		$create_statements.=$proc_create_stmt.';';
		    	}
		    }
		} 
	}

	return $create_statements;
}
public function create_inserts($table_name= null,$where=null,$mysqli)
   {
   	 
   	$condition = ($where!=null) ? $where : '' ;
	$sql_create = "SHOW CREATE TABLE `$table_name`";  

	$fields_total = null;
	$create_table = '';
    $inserts = '';
   	$rows_total = null;   	 
	if ($result = $mysqli->query($sql_create)) {
		$column_types_array = array();
		$sql_column_types = "SHOW COLUMNS FROM `$table_name`";
		if ($result_sql_columns = $mysqli->query($sql_column_types)) {
			$result_column_types = mysqli_fetch_all($result_sql_columns,MYSQLI_ASSOC);
			foreach ($result_column_types as $rows => $types) {
				$type = $types['Type'];
			    $column_types_array[] = $type;
			}
		}

		/* determine number of rows result set */
	    $row_cnt = $result->num_rows;
	    $result_create = $result->fetch_array(MYSQLI_ASSOC);
	    $create_table = $result_create['Create Table'].";\n\n";	   
	    $sql = "select distinct * FROM $table_name  $condition";	
	    $multi_values = '';
	    if ($result_sql = $mysqli->query($sql)) {
	    	// $result_fields = $result_sql->mysqli_fetch_all(MYSQLI_ASSOC);
	    	$result_fields = mysqli_fetch_all($result_sql,MYSQLI_ASSOC);				
	    	 if(count($result_fields)>0){
		   	 	$fields = array_keys($result_fields[0]); 
		   	 	$string = '';
				foreach ($fields as $key => $value) {
				    $string .= ",`$value`";
				}
				$string = substr($string, 1); // remove leading ","
				$fields_total = '('.$string.')';

				
				for ($i=0; $i <count($result_fields) ; $i++) { 			
					$data = array_values($result_fields[$i]);   
					// echo "<pre>";print_r($data);die;		 	
			   	 	$values = '';
			   	 	$count = 0;
					foreach ($data as $keys => $row) {
						$row_types = $column_types_array[$keys];
						if (strpos($row_types, 'int') !== false) {
							$row = ($row!='') ?$row : 0 ;
						    $values .= ",$row";				
						}else{
							$row = ($row!='') ?$row : '' ;
							$row = addslashes($row);
					    	$values .= ",'$row'";
						}
						// if ($count==0) {							
					 //    	$values .= ",$row";						
						// }else{
						// 	$row = addslashes($row);
					 //    	$values .= ",'$row'";
					 //    }
					    // $count++;
					}
					$values = substr($values, 1); // remove leading ","
					$rows_total = '('.$values.')';
					$multi_values .= ','.$rows_total;
		   	 		
		   	 		
				}
				$multi_values = substr($multi_values, 1); // remove leading ","				
				$inserts .= 'INSERT INTO '.$table_name.' VALUES '.$multi_values.";\n";
				
		   	 }
	    }

	    /* close result set */
	    $result->close();
	}
   	 return $create_table.$inserts;

   }
   public function create_inserts1($table_name= null,$where=null,$mysqli)
   {
   	 
   	$condition = ($where!=null) ? $where : '' ;
	$sql_create = "SHOW CREATE TABLE `$table_name`";  

	$fields_total = null;
	$create_table = '';
    $inserts = '';
   	$rows_total = null;   	 
	if ($result = $mysqli->query($sql_create)) {
		$column_types_array = array();
		$sql_column_types = "SHOW COLUMNS FROM `$table_name`";
		if ($result_sql_columns = $mysqli->query($sql_column_types)) {
			$result_column_types = mysqli_fetch_all($result_sql_columns,MYSQLI_ASSOC);
			foreach ($result_column_types as $rows => $types) {
				$type = $types['Type'];
			    $column_types_array[] = $type;
			}
		}

		/* determine number of rows result set */
	    $row_cnt = $result->num_rows;
	    $result_create = $result->fetch_array(MYSQLI_ASSOC);
	    $create_table = $result_create['Create Table'].";\n\n";	   
	    $sql = "select distinct * FROM $table_name  $condition";	
	    if ($result_sql = $mysqli->query($sql)) {
	    	// $result_fields = $result_sql->mysqli_fetch_all(MYSQLI_ASSOC);
	    	$result_fields = mysqli_fetch_all($result_sql,MYSQLI_ASSOC);				
	    	 if(count($result_fields)>0){
		   	 	$fields = array_keys($result_fields[0]); 
		   	 	$string = '';
				foreach ($fields as $key => $value) {
				    $string .= ",`$value`";
				}
				$string = substr($string, 1); // remove leading ","
				$fields_total = '('.$string.')';


				for ($i=0; $i <count($result_fields) ; $i++) { 			
					$data = array_values($result_fields[$i]);   
					// echo "<pre>";print_r($data);die;		 	
			   	 	$values = '';
			   	 	$count = 0;
					foreach ($data as $keys => $row) {
						$row_types = $column_types_array[$keys];
						if (strpos($row_types, 'int') !== false) {
							$row = ($row!='') ?$row : 0 ;
						    $values .= ",$row";				
						}else{
							$row = ($row!='') ?$row : '' ;
							$row = addslashes($row);
					    	$values .= ",'$row'";
						}
						// if ($count==0) {							
					 //    	$values .= ",$row";						
						// }else{
						// 	$row = addslashes($row);
					 //    	$values .= ",'$row'";
					 //    }
					    // $count++;
					}
					$values = substr($values, 1); // remove leading ","
					$rows_total = '('.$values.');';
		   	 		$inserts .= 'INSERT INTO '.$table_name.$fields_total.' VALUES '.$rows_total."\n";
				}

				
		   	 }
	    }

	    /* close result set */
	    $result->close();
	}
   	 return $create_table.$inserts;

   }

 //   public function get_fields($table_name = null){
 //   	 $sql = "Select * FROM access_level";
 //   	 $result = $this->db->query($sql)->result_array();   
 //   	 $fields_total = null;
 //   	 if(count($result)>0){
 //   	 	$fields = array_keys($result[0]);
 //   	 	// echo "<pre>";print_r($fields);die;
 //   	 	$string = '';
	// 	foreach ($fields as $key => $value) {
	// 	    $string .= ",$value";
	// 	}
	// 	$string = substr($string, 1); // remove leading ","
	// 	$fields_total = '('.$string.')';
 //   	 }	 
 //   	 echo "$fields_total";die;
 //   	 return $fields_total;
 //   }

 //   public function get_data($table_name = null){
 //   	 $sql = "Select * FROM access_level";
 //   	 $result = $this->db->query($sql)->result_array();   
 //   	 $fields_total = null;
 //   	 if(count($result)>0){
 //   	 	$fields = array_values($result[0]);   	 	
 //   	 	$string = '';
	// 	foreach ($fields as $key => $value) {
	// 	    $string .= ",`$value`";
	// 	}
	// 	$string = substr($string, 1); // remove leading ","
	// 	$fields_total = '('.$string.')';
 //   	 }	 
 //   	 echo "$fields_total";die;
 //   	 return $fields_total;
 //   }
 //   public function show_insert(){
 //   	 $sql = "Select * FROM access_level";
 //   	 $result = $this->db->query($sql)->result_array();   	 
 //   	 for ($i=0; $i < count($result); $i++) {    	 	
 //   	 	$fields = array_keys($result[$i]);
 //   	 	$string = '';
	// 	foreach ($fields as $key => $value) {
	// 	    $string .= ",$value";
	// 	}
	// 	$string = substr($string, 1); // remove leading ","
	// 	$fields = '('.$string.')';
   	 	

 //   	 }
 //   	 foreach ($variable as $key => $value) {
 //   	 	# code...
 //   	 }
 //   	 echo "<pre>";print_r($result);die;
 //   }
 //   public function dump_test(){
 //   	$sql = "SHOW TABLES";
	// // connect_db();
	// $retval = $this->db->query($sql);
	// echo "<pre>";print_r($retval);die;
	// if(! $retval )
	// {
	//   die('Could not retrive tables: ' . mysql_error());
	// }else{
	// 	while($row = mysql_fetch_array($retval)){
	// 		$tables[] = $row[0];
	// 	}
	// }
	// $starttime = microtime(true);
	// $headers = "-- MySql Data Dump\n\n";
	// $headers .= "-- Database : " . db_name . "\n\n";
	// $headers .= "-- Dumping started at : ". date("Y-m-d-h-i-s") .  "\n\n";

	// for($t=0;$t<3;$t++){
	// 	$outputdata .= "\n\n-- Dumping data for table : $tables[$t]\n\n";
	// 	$sql = "SELECT * FROM $tables[$t]";
	// 	$result = mysql_query($sql);
	// 	while($row = mysql_fetch_assoc($result)){
	// 		$nor = count($row);
	// 		$datas = array();
	// 		foreach($row as $r){
	// 			$datas[] = $r;
	// 		}
	// 		$lines .= "INSERT INTO $tables[$t] VALUES (";
	// 		for($i=0;$i<$nor;$i++){
	// 			if($datas[$i]===NULL){
	// 				$lines .= "NULL";
	// 			}else if((string)$datas[$i] == "0"){
	// 				$lines .= "0";
	// 			}else if(filter_var($datas[$i],FILTER_VALIDATE_INT) || filter_var($datas[$i],FILTER_VALIDATE_FLOAT)){
	// 				$lines .= $datas[$i];
	// 			}else{
	// 				$lines .= "'" . str_replace("\n","\\n",$datas[$i]) . "'";
	// 			}
	// 			if($i==$nor-1){
	// 				$lines .= ");\n";
	// 			}else{
	// 				$lines .= ",";
	// 			}
	// 		}
	// 		$outputdata .= $lines;
	// 		$lines = "";
	// 	}
	// }
	// $headers .= "-- Dumping finished at : ". date("Y-m-d-h-i-s") .  "\n\n";
	// $endtime = microtime(true);
	// $diff = $endtime - $starttime;
	// $headers .= "-- Dumping data of $db_name took : ". $diff .  " Sec\n\n";
	// $headers .= "-- --------------------------------------------------------";
	// $datadump = $headers . $outputdata;

	// $file = fopen($backupFile,"w");
	// $len = fwrite($file,$datadump);
	// fclose($file);
	// if($len != 0){
	// 	return true;
	// }else{
	// 	return false;
	// }
 //   }
 //   public function mysql_dump1($facility_code) {
	//   ini_set('memory_limit', '-1');
 // 	  $database = 'hcmp_rtk';
 //      $query = '';
 
 //      $tables = @mysql_list_tables($database);

 //      $query .= "DROP TABLE IF EXISTS ` $database.user`;\n";
 //         $query .= "\n" . 'CREATE TABLE `' . $database . '.user` ('."\n";
 
 //         $tmp = ''; 
         
 
 //         $results = mysql_query('SELECT * FROM ' . $database . '.user where facility=' . $facility_code);
 
 //         while ($row = @mysql_fetch_assoc($results)) {
 
 //            $query .= 'INSERT INTO `' . $database . '.user` (';
 
 //            $data = Array();
 
 //            while (list($key, $value) = @each($row)) { $databaseata['keys'][] = $key; $data['values'][] = addslashes($value); }
 
 //            $query .= join($data['keys'], ', ') . ')' . "\n" . 'VALUES (\'' . join($data['values'], '\', \'') . '\');' . "\n";
 
 //         }
 
 //         $query .= str_repeat("\n", 2);
 
 //      	// echo "<pre>";print_r($query);
 //    	$filename = 'test.sql';		
	// 	!$handle = fopen($filename, 'w');
	// 	fwrite($handle, $query);
	// 	fclose($handle);


	// 	header("Cache-Control: public");
	// 	header("Content-Description: File Transfer");
	// 	header("Content-Length: ". filesize("$filename").";");
	// 	header("Content-Disposition: attachment; filename=$filename");
	// 	header("Content-Type: application/octet-stream; "); 
	// 	header("Content-Transfer-Encoding: binary");

	// 	echo "$query";
 
 //      // return $query;
 
 //   }
 

}

 ?>