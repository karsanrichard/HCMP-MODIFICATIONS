<?php
/**
 * @author Mureithi
 */
if (!defined('BASEPATH'))
	exit('No direct script access allowed');

class Admin extends MY_Controller {

	function __construct() {
		parent::__construct();

		$this -> load -> helper(array('form', 'url'));
		$this -> load -> library(array('hcmp_functions', 'form_validation'));
	}


	public function index() {
		$data['title'] = "Commodities";
		$this -> load -> view("", $data);
	}
	
	public function manage_commodities() {
		$data['title'] = "Commodities";
		$data['content_view'] = "Admin/commodities_v";
		$data['commodity_list'] = commodity_sub_category::get_all();
		$this -> load -> view("shared_files/template/dashboard_v", $data);
	}
	public function commodities_upload() {
		
	}

	public function offline(){		
		$data['title'] = "Offline Management";
		$data['content_view'] = "Admin/offline_management";
		$data['banner_text'] = "Offline Management";	
		$data['system_updates'] = Update_model::get_system_files(1);	
		$data['system_setup'] = Update_model::get_system_files(2);
		$data['offline_facilities']	= Facilities::get_offline_facilities(null,null);
		// echo "<pre>";print_r($data);die;	
		$this -> load -> view("shared_files/template/dashboard_v", $data);
	}

	public function reactivate_facility($facility_code) {
		$reactivate = Doctrine_Manager::getInstance()->getCurrentConnection();
		$reactivate->execute("UPDATE facilities SET using_hcmp = 1 WHERE facility_code = '$facility_code'");
	}

	public function upload_update() {
		$upload_type = $this->input->post('upload_type');

		$upload_path = ($upload_type=='1') ? 'system_updates/' :'system_setups/' ;
		$file_path = $upload_path;
		// $file_path = 'system_updates/';
		$zip_name =  MY_Controller::generate_filestamp(); //Generate Zip Name from My_Controller for Standardization

		// $config['upload_path'] = 'system_updates/';
		$config['upload_path'] = $upload_path;
		$config['allowed_types'] = '*';//  
		$config['file_name'] = $zip_name;

		$this->load->library('upload');//Upload  
		$this->upload->initialize($config);		
		if($this->upload->do_upload())
		{
			$this->session->set_flashdata('system_success_message', 'File Upload Successful');
		}
		else
		{
			echo "<pre>"; print_r($this->upload->display_errors()); echo "</pre>"; exit;
		   	$this->session->set_flashdata('error', 'File Upload Not Successful');
		}

		
		$description = $this->input->post('description');
		$user_id = $this -> session -> userdata('user_id');				
		$filename = $zip_name.'.zip';

		$file_data = array('update_name'=>$filename,'description'=>$description,'user_id'=>$user_id,'status'=>0,'type'=>$upload_type);
		// $file_data_old = array('update_name'=>$filename);
		// $this -> db -> insert('system_uploads', $file_data);
		$this -> db -> insert('update_log', $file_data);


		redirect('admin/offline');
		
	}

	public function manage_users() {
		$permissions='super_permissions';
		$data['title'] = "Users";
		$data['content_view'] = "Admin/users_v";
		$data['listing']= Users::get_user_list_all();
		// echo "<pre>";print_r($data['listing']);echo "</pre>";exit;
		$data['counts']=Users::get_users_count();
		$data['counties']=Counties::getAll();
		$data['facilities']=Facilities::getAll();
		$data['sub_counties']=Districts::getAll();
		$data['user_types']=Access_level::get_access_levels($permissions);
		$this -> load -> view("shared_files/template/dashboard_v", $data);
	}
	public function manage_facilities() {
		$permissions='super_permissions';
		$data['title'] = "Users";
		$data['content_view'] = "Admin/facilities_v";
		$data['facilities_listing']= Users::get_facilities_list_all();
		$data['facilities_listing_active']= Users::get_facilities_list_all_active(1);
		$data['facilities_listing_inactive']= Users::get_facilities_list_all_active(0);
		$data['active_count']= Facilities::get_all_facilities_active_no();
		$data['facility_count']=Facilities::get_all_facilities_no();
		$data['counties']=Counties::getAll();
		$data['facilities']=Facilities::getAll();
		$data['sub_counties']=Districts::getAll();
		$data['user_types']=Access_level::get_access_levels($permissions);
		$data['activation_logs'] = Facilities::get_activation_logs();
		$this -> load -> view("shared_files/template/dashboard_v", $data);
	}
	public function report_management() {
		$permissions='super_permissions';
		$data['title'] = "Users";
		$data['content_view'] = "Admin/report_management_v";
		$data['listing']= Users::get_user_list_all();
		$data['counts']=Users::get_users_count();
		$data['facilities_listing']= Users::get_facilities_list_all();
		$data['users_listing_active']= Users::get_user_list_all();
		$data['facilities_listing_inactive']= Users::get_facilities_list_all_active(0);
		$data['active_count']= Facilities::get_all_facilities_active_no();
		$data['facility_count']=Facilities::get_all_facilities_no();
		$data['counties']=Counties::getAll();
		$data['facilities']=Facilities::getAll();
		$data['sub_counties']=Districts::getAll();
		$data['user_types']=Access_level::get_access_levels($permissions);
		$this -> load -> view("shared_files/template/dashboard_v", $data);
	}
	public function tester(){
	}
	public function send_email(){
		$q = "SELECT email FROM user WHERE usertype_id in (0,7,8,11,13,14,15) and status=1 ORDER BY id DESC";
		$count = $this->db->query($q)->num_rows();
		$a = 0;
		$b = 98;
		$increment = 98;
		for ($i=$a; $a <=$count ; $i+$increment) { 
			$sql = "SELECT email FROM user WHERE usertype_id in (0,7,8,11,13,14,15) and status=1 ORDER BY id DESC LIMIT $a,$b";                    
			$res = $this->db->query($sql)->result_array();                                      
			$to ="";
			foreach ($res as $key => $value) {
				$one = $value['email'];
				$to.= $one.',';                        
			} 
			$newmail->send_email($to, $message, $subject, $attach_file, $bcc_email);
			$a +=$increment;
			$b += $increment;
		}
		die();
	}
	public function reversals(){
		ini_set('memory_limit', '-1');
		$permissions='super_permissions';
		$data['title'] = "Reversals";
		$data['content_view'] = "Admin/reversals_home_v";	
		$current_issues = Facility_issues::get_issues_for_reversals();
		$data['current_issues'] = $current_issues;		
		// $data['user_types']=Access_level::get_access_levels($permissions);
		$this -> load -> view("shared_files/template/dashboard_v", $data);
	}
	public function get_reversal_table(){
		$graph_data = array();
		$current_issues = Facility_issues::get_issues_for_reversals();
		foreach ($current_issues as $key => $value) {
			$facility_name = $value['facility_name'];
			$facility_code = $value['facility_code'];
			$issue_date_raw = $value['created_at'];
			$issue_date = date('F, m Y', strtotime($issue_date_raw));
			$issuer = $value['fname'].' '.$value['lname'];                          
			$issuer_id = $value['issued_by'];
			$issue_date_timestamp = strtotime($issue_date_raw); 
			$data_id = $facility_code.'/'.$issue_date_timestamp.'/'.$issuer_id;
			$button_dets_link = '<button class="btn btn-success status_btn form-control" style="width:98%"  data-id="'.$data_id.'" id="'.$data_id.'" data-attr="'.$data_id.'" data-value="'.$data_id.'">View Details</button>';
			$button_reverse_link = "<a href=\"".base_url().'admin/reverse_issue/'.$facility_code.'/'.$issue_date_timestamp.'/'.$issuer_id.'/reverse'."\"><button class=\"btn btn-danger  form-control\" style=\"width:98%\">Reverse Issue</button></a>";
			$output[] = array($facility_code,$facility_name,$issue_date,$issuer,$button_reverse_link);
		}
		$category_data = array( array("Facility Code", "Facility Name", "Date of Issue", "Name of Issuer", "Action"));
		$graph_data = array_merge($graph_data, array("table_id" => 'issues_tbl'));
		$graph_data = array_merge($graph_data, array("table_header" => $category_data));
		$graph_data = array_merge($graph_data, array("table_body" => $output));
		$data = array();
		$data['table'] = $this -> hcmp_functions -> create_data_table($graph_data);
		// $data['table_id'] = "issues_tbl";
		return $this -> load -> view("shared_files/report_templates/data_table_template_v", $data);
        // echo json_encode($output);
	}
	public function get_redistribution_reverse_table(){
		$graph_data = array();
		$current_redistributions = Facility_issues::get_redistributions_for_reversals();
		foreach ($current_redistributions as $key => $value) {
			$facility_name = $value['facility_name'];
			$facility_code = $value['facility_code'];
			$redistribution_date_raw = $value['date_sent'];
			$redistribution_date = date('F, m Y', strtotime($redistribution_date_raw));
			$issuer = $value['fname'].' '.$value['lname'];                          
			$issuer_id = $value['sender_id'];
			$redistribution_date_timestamp = strtotime($redistribution_date_raw); 
			$data_id = $facility_code.'/'.$redistribution_date_timestamp.'/'.$issuer_id;
			$button_dets_link = '<button class="btn btn-success status_btn form-control" style="width:98%"  data-id="'.$data_id.'" id="'.$data_id.'" data-attr="'.$data_id.'" data-value="'.$data_id.'">View Details</button>';
			$button_reverse_link = "<a href=\"".base_url().'admin/reverse_redistribution/'.$facility_code.'/'.$redistribution_date_timestamp.'/'.$issuer_id.'/reverse'."\"><button class=\"btn btn-danger  form-control\" style=\"width:98%\">Reverse Redistribution</button></a>";
			$output[] = array($facility_code,$facility_name,$redistribution_date,$issuer,$button_reverse_link);
		}
		$category_data = array( array("Facility Code", "Facility Name", "Redistribution Date", "Name of Issuer","Action"));
		$graph_data = array_merge($graph_data, array("table_id" => 'redistributions_tbl'));
		$graph_data = array_merge($graph_data, array("table_header" => $category_data));
		$graph_data = array_merge($graph_data, array("table_body" => $output));
		$data = array();
		$data['table'] = $this -> hcmp_functions -> create_data_table($graph_data);
		// $data['table_id'] = "issues_tbl";
		return $this -> load -> view("shared_files/report_templates/data_table_template_v", $data);
        // echo json_encode($output);
	}

	public function get_reversed_table(){
		$graph_data = array();
		$current_reversed = Facility_issues::get_reversed_issues();
		foreach ($current_reversed as $key => $value) {
			$facility_name = $value['facility_name'];
			$facility_code = $value['facility_code'];
			$issue_date_raw = $value['created_at'];
			$issue_date = date('F, m Y', strtotime($issue_date_raw));
			$issuer = $value['fname'].' '.$value['lname'];                          
			$issuer_id = $value['issued_by'];
			$issue_date_timestamp = strtotime($issue_date_raw); 
			$data_id = $facility_code.'/'.$issue_date_timestamp.'/'.$issuer_id;
			$button_dets_link = '<button class="btn btn-success reverse_status_btn form-control" style="width:98%"  data-id="'.$data_id.'" id="'.$data_id.'" data-attr="'.$data_id.'" data-value="'.$data_id.'">View Details</button>';
			$button_reverse_link = "<a href=\"".base_url().'admin/undo_reverse_issue/'.$facility_code.'/'.$issue_date_timestamp.'/'.$issuer_id.'/reverse'."\"><button class=\"btn btn-danger  form-control\" style=\"width:98%\">Undo Reverse Issue</button></a>";
			$output[] = array($facility_code,$facility_name,$issue_date,$issuer,$button_dets_link,$button_reverse_link);
		}
		$category_data = array( array("Facility Code", "Facility Name", "Date of Issue", "Name of Issuer","Details", "Action"));
		$graph_data = array_merge($graph_data, array("table_id" => 'reversed_issues_tbl'));
		$graph_data = array_merge($graph_data, array("table_header" => $category_data));
		$graph_data = array_merge($graph_data, array("table_body" => $output));
		$data = array();
		$data['table'] = $this -> hcmp_functions -> create_data_table($graph_data);
		// $data['table_id'] = "issues_tbl";
		return $this -> load -> view("shared_files/report_templates/data_table_template_v", $data);
        // echo json_encode($output);
	}
	public function reverse_issue($facility_code,$raw_date, $issued_by, $type){
		$current_time = date("Y-m-d H:i:s", time());	
		$created_at = date("Y-m-d H:i:s", $raw_date);	
		$issue_details = Facility_issues::get_issue_details_for_reversals($facility_code,$created_at,$issued_by);
		if($type=='view'){
			foreach ($issue_details as $key => $value) {
				$facility_code = $value['facility_code'];
				$facility_name = $value['facility_name'];
				$commodity_name = $value['commodity_name'];
				$batch_no = $value['batch_no'];
				$qty_issued = $value['qty_issued'];
				$date_issued_raw = $value['date_issued'];
				$issued_by = $value['issued_by'];
				$date_issued = date('d F Y',strtotime($date_issued_raw));
				$output[] = array($facility_name,$facility_code,$commodity_name,$batch_no,$qty_issued,$date_issued);
			}
			echo json_encode($output);
		}else if($type=='reverse'){
			foreach ($issue_details as $key => $value) {
				$reverse_data = array(
					'reversed_id'=>$value['id'],
					'facility_code'=>$value['facility_code'],
					's11'=>$value['s11_No'],
					'commodity_id'=>$value['commodity_id'],
					'batch_no'=>$value['batch_no'],
					'expiry_date'=>$value['expiry_date'],
					'balance_as_of'=>$value['balance_as_of'],
					'adjustmentpve'=>$value['adjustmentpve'],
					'adjustmentnve'=>$value['adjustmentnve'],
					'qty_issued'=>$value['qty_issued'],
					'date_issued'=>$value['date_issued'],
					'issued_to'=>$value['issued_to'],
					'created_at'=>$value['created_at'],
					'issued_by'=>$value['issued_by'],
					'status'=>$value['status'],
					'reversal_type'=>'1',
					'reversal_time'=>$current_time,
					'reversal_status'=>'1',

					);
				
				$this->db->insert('reversals', $reverse_data); 
				$facility_stocks_data = facility_stocks::get_current_stock_for_reversal($value['facility_code'],$value['commodity_id'],$value['batch_no']);
				foreach ($facility_stocks_data as $keys => $values) {					
					$id = $values['id'];
					$initial_quantity = $values['initial_quantity'];
					$current_balance = intval($values['current_balance']);
					$new_balance = $current_balance+intval($value['qty_issued']);
					$update_data = array('current_balance'=>$new_balance);
					$this->db->where('id', $id);					
					$this->db->update('facility_stocks',$update_data);
				}				
				$this->db->where('id', $value['id']);
				$this->db->delete('facility_issues'); 


			}
			redirect('admin/reversals');
		}
		
	}	
	public function reverse_redistribution($facility_code,$raw_date, $issued_by, $type){
		$current_time = date("Y-m-d H:i:s", time());	
		$created_at = date("Y-m-d", $raw_date);			
		$redistribution_details = Facility_issues::get_reversal_details_for_reversals($facility_code,$created_at,$issued_by);		
		if($type=='view'){
			foreach ($issue_details as $key => $value) {
				$facility_code = $value['facility_code'];
				$facility_name = $value['facility_name'];
				$commodity_name = $value['commodity_name'];
				$batch_no = $value['batch_no'];
				$qty_issued = $value['qty_issued'];
				$date_issued_raw = $value['date_issued'];
				$issued_by = $value['issued_by'];
				$date_issued = date('d F Y',strtotime($date_issued_raw));
				$output[] = array($facility_name,$facility_code,$commodity_name,$batch_no,$qty_issued,$date_issued);
			}
			echo json_encode($output);
		}else if($type=='reverse'){
			foreach ($redistribution_details as $key => $value) {
				$reverse_data = array(
					'redistribution_id'=>$value['id'],
					'source_facility_code'=>$value['source_facility_code'],
					'receive_facility_code'=>$value['receive_facility_code'],
					'commodity_id'=>$value['commodity_id'],
					'batch_no'=>$value['batch_no'],
					'quantity_sent'=>$value['quantity_sent'],
					'quantity_received'=>$value['quantity_received'],
					'sender_id'=>$value['sender_id'],
					'receiver_id'=>$value['receiver_id'],
					'manufacturer'=>$value['manufacturer'],
					'expiry_date'=>$value['expiry_date'],
					'status'=>$value['status'],
					'facility_stock_ref_id'=>$value['facility_stock_ref_id'],
					'date_sent'=>$value['date_sent'],
					'date_received'=>$value['date_received'],
					'source_district_id'=>$value['source_district_id'],
					'reversal_time'=>$current_time,
					'reversal_status'=>'1',

					);
				echo "<pre>";
				print_r($reverse_data);die;
				$this->db->insert('reverse_redistributions', $reverse_data); 
				$facility_stocks_data = facility_stocks::get_current_stock_for_reversal($value['facility_code'],$value['commodity_id'],$value['batch_no']);
				foreach ($facility_stocks_data as $keys => $values) {					
					$id = $values['id'];
					$initial_quantity = $values['initial_quantity'];
					$current_balance = intval($values['current_balance']);
					$new_balance = $current_balance+intval($value['quantity_sent']);
					$update_data = array('current_balance'=>$new_balance);
					$this->db->where('id', $id);					
					$this->db->update('facility_stocks',$update_data);
				}				
				$this->db->where('id', $value['id']);
				$this->db->delete('redistribution_data'); 


			}
			redirect('admin/reversals');
		}
		
	}	
	public function undo_reverse_issue($facility_code,$raw_date, $issued_by, $type){
		$current_time = date("Y-m-d H:i:s", time());	
		$created_at = date("Y-m-d H:i:s", $raw_date);	
		$issue_details = Facility_issues::get_undo_issue_details_for_reversals($facility_code,$created_at,$issued_by);		
		if($type=='view'){
			foreach ($issue_details as $key => $value) {
				$facility_code = $value['facility_code'];
				$facility_name = $value['facility_name'];
				$commodity_name = $value['commodity_name'];
				$batch_no = $value['batch_no'];
				$qty_issued = $value['qty_issued'];
				$date_issued_raw = $value['date_issued'];
				$issued_by = $value['issued_by'];
				$date_issued = date('d F Y',strtotime($date_issued_raw));
				$output[] = array($facility_name,$facility_code,$commodity_name,$batch_no,$qty_issued,$date_issued);
			}
			echo json_encode($output);
		}else if($type=='reverse'){
			foreach ($issue_details as $key => $value) {				
				$undo_reversed_data = array(
					'facility_code'=>$value['facility_code'],
					's11_No'=>$value['s11'],
					'commodity_id'=>$value['commodity_id'],
					'batch_no'=>$value['batch_no'],
					'expiry_date'=>$value['expiry_date'],
					'balance_as_of'=>$value['balance_as_of'],
					'adjustmentpve'=>$value['adjustmentpve'],
					'adjustmentnve'=>$value['adjustmentnve'],
					'qty_issued'=>$value['qty_issued'],
					'date_issued'=>$value['date_issued'],
					'issued_to'=>$value['issued_to'],
					'created_at'=>$value['created_at'],
					'issued_by'=>$value['issued_by'],
					'status'=>$value['status']
					);
				
				$this->db->insert('facility_issues', $undo_reversed_data); 
				$facility_stocks_data = facility_stocks::get_current_stock_for_reversal($value['facility_code'],$value['commodity_id'],$value['batch_no']);
				foreach ($facility_stocks_data as $keys => $values) {					
					$id = $values['id'];
					$initial_quantity = $values['initial_quantity'];
					$current_balance = intval($values['current_balance']);
					$new_balance = $current_balance-intval($value['qty_issued']);
					$update_data = array('current_balance'=>$new_balance);
					$this->db->where('id', $id);					
					$this->db->update('facility_stocks',$update_data);
				}				
				$this->db->where('id', $value['id']);
				$this->db->delete('reversals'); 


			}
			redirect('admin/reversals');
		}
		
	}	

	public function deactivate_facility(){
		$user_id = $this -> session -> userdata('user_id');
		$le_facility = $_POST['ndata'];
		$le_status = $_POST['status'];
		//echo "This  ".$le_status;exit;
		//something
		$double_tap = Users::deactivate_facility($le_facility,$le_status, $user_id);
		//echo "Success";
	}

	public function edit_user(){
		
		$identifier = $this -> session -> userdata('user_indicator');

		$fname = $_POST['fname_edit'];
		$lname = $_POST['lname_edit'];
		$status = $_POST['status'];
		$telephone_edit= $_POST['telephone_edit'];
		$email_edit = $_POST['email_edit'];
		$username_edit = $_POST['username_edit'];
		$facility_id_edit_district = $_POST['facility_id_edit_district'];
		$user_type_edit_district = $_POST['user_type_edit_district'];
		$district_name_edit = $_POST['district_name_edit'];
		$facility_id_edit= $_POST['facility_id_edit'];
		$user_id= $_POST['user_id'];
		$county = $_POST['county_edit'];
		
		if ($status=="true") {
			
			$status=1;
			
		} elseif($status=="false") {
			
			$status=0;
		}
		
		
		//update user
		$update_user = Doctrine_Manager::getInstance()->getCurrentConnection();
		$update_user->execute("UPDATE `user` SET fname ='$fname' ,lname ='$lname',email ='$email_edit',usertype_id =$user_type_edit_district,telephone ='$telephone_edit',
			district ='$district_name_edit',facility ='$facility_id_edit',
									-- status ='$status',
									county_id ='$county'
									WHERE `id`= '$user_id'");
		
	}

	public function change_status(){
		$user_id = $_POST['user_id'];
		$status = $_POST['status'];

		// echo $status. " " . $member_id;exit;
		// echo "UPDATE `user` SET status = '$status' WHERE `id`= '$user_id'";exit;
		$update_user = Doctrine_Manager::getInstance()->getCurrentConnection();
		$update_user->execute("UPDATE `user` SET status = '$status' WHERE `id`= '$user_id'");
		echo $update_user." success";
	}

	public function update_stock_prices(){
		$inputFileName = 'print_docs/excel/excel_template/KEMSA Customer Order Form.xls';

		// echo 'Loading file ',pathinfo($inputFileName,PATHINFO_BASENAME),' using IOFactory to identify the format<br />';
		$objPHPExcel = PHPExcel_IOFactory::load($inputFileName);


		$commodities = commodities::get_all_2();
		$system_commodity_details = array();
		$excel_commodity_details = array();
		$ate_hwat = array();
		$temp = array();
		$keys_na_si_alishia = array();

		// echo "<pre>";print_r($commodities);echo "</pre>";exit;

		foreach ($commodities as $commodity) {
			$system_commodity_details[] = array(
				'id'=> $commodity['id'],
				'commodity_code'=> $commodity['commodity_code'],
				'commodity_name'=> $commodity['commodity_name']
				);
		}//end of foreach


		$sheetData = $objPHPExcel->getActiveSheet()->toArray(null,true,true,true);
		$sheet = $objPHPExcel->getSheet(0); 
		$highestRow = $sheet->getHighestRow(); 
		$highestColumn = $sheet->getHighestColumn();

		// echo "<pre>";print_r($sheetData);echo "</pre>";exit;

		for ($row=17; $row < $highestRow; $row++) { 
			$rowData = $sheet->rangeToArray('A' . $row . ':' . $highestColumn . $row,NULL,TRUE,FALSE); 
			$excel_commodity_details[] = array(
				'commodity_name' => $rowData[0][3], 
				'price' => $rowData[0][6],
				'commodity_code' => $rowData[0][2]
				);
		}
		// echo "<pre>";print_r($excel_commodity_details);echo "</pre>";exit;

		// echo "<pre>";print_r($system_commodity_details);echo "</pre>";exit;
		
		$synthesised = array();
		$unsynthesised = array();
		foreach ($excel_commodity_details as $excels) {
			foreach ($system_commodity_details as $systems) {
				if ($excels['commodity_name'] == $systems['commodity_name']){
					$synthesised[] = array(
						'id' => $systems['id'], 
						'commodity_name' => $excels['name'], 
						'new_unit_price' => $excels['price'],
						'commodity_code'=> $excels['commodity_code']
						);
				}
			}

		}//end of foreach

		// echo "<pre>";print_r($synthesised);echo "</pre>";exit;

		$counter = 0;
		foreach ($synthesised as $synthesis) {
			if (isset($synthesis['commodity_code']) && $synthesis['commodity_code'] != '') {
				$details = array(
					'commodity_code' => $synthesis['commodity_code'], 
					);
				$this->db->where('commodity_name',$synthesis['commodity_name']);
				$result=$this->db->update('commodities',$details);
				$counter = $counter + 1;
				echo "<pre>Commodity: ".$synthesis['id']."  ".$synthesis['commodity_name']." Price: ".$synthesis['new_unit_price']." updated successfully</pre>";
			}
		}
		$handler = array('unit_cost' => 0);
		$this->db->where('unit_cost','N/A');
		$result=$this->db->update('commodities',$handler);
		echo "Total Queries: ".$counter;
	}//end of function
	
	public function user_logs(){
		$currently_logged_in_all = user::get_currently_logged_in_users();
		$currently_logged_in_county = user::get_currently_logged_in_users('county');
		$currently_logged_in_subcounty = user::get_currently_logged_in_users('subcounty');
		$currently_logged_in_facility = user::get_currently_logged_in_users('facility');

		/*echo "<pre>";print_r($currently_logged_in_county);echo "</pre> END OF C";
		echo "<pre>";print_r($currently_logged_in_subcounty);echo "</pre> END OF SC";
		echo "<pre>";print_r($currently_logged_in_facility);exit;*/
		$data['currently'] = "currently";

		$data['all_users'] = $currently_logged_in_all;
		$data['county_users'] = $currently_logged_in_county;
		$data['subcounty_users'] = $currently_logged_in_subcounty;
		$data['facility_users'] = $currently_logged_in_facility;
		
		$data['currently_logged_in'] = $currently_logged_in;
		$data['title'] = "User Logs";
		$data['content_view'] = "Admin/logging";
		$this -> load -> view("shared_files/template/dashboard_v", $data);
	}

	public function previous_user_logs(){

		// $previous_logged_in_all = user::get_previous_logs();
		$previous_logged_in_county = user::get_previous_logs('county');
		$previous_logged_in_subcounty = user::get_previous_logs('subcounty');
		$previous_logged_in_facility = user::get_previous_logs('facility');

		// echo "<pre>";print_r($previous_logged_in_county);echo "</pre> END OF C";
		// echo "<pre>";print_r($previous_logged_in_subcounty);echo "</pre> END OF SC";
		// echo "<pre>";print_r($previous_logged_in_facility);exit;

		$data['all_users'] = $previous_logged_in_all;
		$data['county_users'] = $previous_logged_in_county;
		$data['subcounty_users'] = $previous_logged_in_subcounty;
		$data['facility_users'] = $previous_logged_in_facility;
		
		$data['currently_logged_in'] = $currently_logged_in;
		$data['title'] = "User Logs";
		$data['active'] = "county";
		$data['content_view'] = "Admin/logging";
		$this -> load -> view("shared_files/template/dashboard_v", $data);
	}


	public function show_online_users(){
		// $this->load->library('OnlineUsers');
		$all_online = $this->onlineusers->total_users();

		echo "<pre> Here I am  ";print_r($all_online);echo "</pre>";
	}

	public function github_update_status(){
		echo "I WAS HERE";
		$res = $this->github_updater->get_update_comments();

		echo "<pre>"; print_r($res);
	}
	/*INVENTORY*/
	public function inventory()
	{
		//to move query functions to model,wanna complete this today so...why worry
		$inventory = $this->get_inventory();

		// echo "<pre>";print_r($inventory);exit;
		$data['inventory_data'] = $inventory;
		$data['title'] = "Inventory";
		$data['banner_text'] = "inventory Management";
		$view = 'shared_files/template/dashboard_v';
		$data['content_view'] = "Admin/inventory_v";
		$this->load->view($view,$data);
	}
	public function upload_inventory_excel(){
		// echo "<pre>";print_r($this->input->post());echo "</pre>";exit;
		// error_reporting(E_ALL);
		$config['upload_path'] = 'print_docs/excel/uploaded_files/';
		$config['allowed_types'] = 'xls|xlsx';
		$config['max_size']	= '2048';
		$name = 'inventory_'.date('d-m-Y').'_';
		$config['file_name'] = $name;
		$this->upload->initialize($config);
		if ( ! $this->upload->do_upload("inventory_excel"))
		{
			echo "<pre>";print_r($this->upload->display_errors());echo "</pre>";
			// echo "I didnt work";
		}
		else
		{
			// echo "I work";exit;
			// $data = array('upload_data' => $this->upload->data());

			$result = $this->upload->data();
			$file_name = $result['file_name'];
			$this->upload_inventory($file_name);
			// echo "I worked";
		}
	}//end of upload excel
	public function upload_inventory($file_name){
		//  Include PHPExcel_IOFactory
		// include 'PHPExcel/IOFactory.php';
		// include 'PHPExcel/PHPExcel.php';

		// $inputFileName = 'excel_files/garissa_sms_recepients_updated.xlsx';
		// echo $category;exit;
		$inputFileName = 'print_docs/excel/uploaded_files/'.$file_name;

		$objReader = new PHPExcel_Reader_Excel2007();
		$objReader->setReadDataOnly(true);
		$objPHPExcel = $objReader->load($inputFileName);

		// echo "<pre>";print_r($inputFileName);exit;

		$sheet = $objPHPExcel->getSheet(0); 
		$highestRow = $sheet->getHighestRow()+1; 
		$highestColumn = $sheet->getHighestColumn();

		// echo "<pre>";print_r($highestRow);echo "</pre>";exit;
		$rowData = array();
		for ($row = 3; $row < $highestRow; $row++){ 
		    //  Read a row of data into an array
		    $rowData_ = $sheet->rangeToArray('A' . $row . ':D' . $row);
		// echo "<pre>";print_r($rowData_);echo "</pre>";
		    array_push($rowData, $rowData_[0]);
		    //  Insert row data array into your database of choice here
		}
		// echo "<pre>";print_r($rowData);exit;//Titus,comment this out to proceed and see the sanitization. It selects the district id based on the district in the excel,same for county.
		foreach ($rowData as $r_data) {
			// echo "<pre>";print_r($r_data);echo "</pre>";
			$status = 1;
			$county_id = $district_id = $facility_code = 0;
			
			$county_name = strtolower($r_data[1]);//lower case
			$county_name = str_replace(" ", "", $county_name);
			$county_name = str_replace("-", " ", $county_name);
			$county_name = ucwords($county_name);//upper first character
			
			$district = strtolower($r_data[2]);
			$district = ucfirst($district);

			$phone = preg_replace('/\s+/', '', $r_data[0]);
			// echo "<pre>";print_r($phone);
			// echo "<pre>";print_r($county_name);
			$facility_code = $r_data[3];
			
			$fault_index = NULL;
			// echo $district;


			$query = "SELECT * FROM facilities WHERE facility_code = '$facility_code'";
			$result = $this->db->query($query)->result_array();//FACILITY CODE SEARCH
			// echo "<pre>";print_r($result);echo "</pre>";

			if (empty($result)){//if no facility code then district
				$queryy = "SELECT * FROM districts WHERE district = '$district'";
				$resultt = $this->db->query($queryy)->result_array();
				
				if (empty($resultt)) {//no district then county
					$queryyy = "SELECT * FROM counties WHERE county = '$county_name'";
					$resulttt = $this->db->query($queryyy)->result_array();
					if (empty($resulttt)){
						// echo "Empty county,subcounty and facility";
					}else{
						$county_id = $resulttt[0]['id'];
						// echo "\t Only County ".$phone;
					}
				}else{//if district matches
					$district_id = $resultt[0]['id'];
					$county_id = $this->get_county_id_for_district($district_id);
					echo "\t District ".$phone;

				}//district name match

			}//if no facility code match

			else{//if facility_code_match
				// echo "<pre>";print_r($result);exit;
				$district_id = $result[0]['district'];
				$county_id = $this->get_county_id_for_district($district_id);
				echo "\t Facility: ".$phone;
				// echo "<pre>"; print_r($county_id);exit;
			}
					/*
					//code for appending 254 to phone numbers
					if (isset($phone)) {
						$phone = preg_replace('/\s+/', '', $phone);
						$phone = ltrim($phone, '0');
						// echo "<pre>".substr($phone, 0,3);
						if (substr($phone, 0,3) != '254') {
							$phone = '254'.$phone;
						}
					}else{
						$phone = NULL;
					}

					*/
					$number_length = isset($phone)?strlen($phone):0;
					// echo "Number Length:  ".$number_length;
					if ($number_length != 12) {
						if (isset($fault_index)) {
							$fault_index = 3;//both error in phone and district
							// $status = 2;
						}else{
							$fault_index = 2;
						}
							$fault_index = 2;//overriding both district and phone error as district is not necessarily necessary
							$status = 2;
					}

					$inv = array();
					$inv_data = array(
						'phone' => $phone,
						'county' => $county_id,
						'subcounty' => $district_id,
						'facility_code' => $facility_code
						);

					array_push($inv, $inv_data);
					// echo "<pre>";print_r($inv);

					$similarity_query = "SELECT * FROM inventory WHERE phone = '$phone'";
					$similarity = $this->db->query($similarity_query)->result_array();//FACILITY CODE SEARCH
					if (empty($similarity)){
						$insertion = $this->db->insert_batch('inventory',$inv);
						// echo "QUERY SUCCESSFUL. ".$insertion." ".mysql_insert_id()."</br>";
					}else{
						// echo "<pre> Dab on em";
					}
		}
					// echo "<pre>";print_r($inv);
				
		// unlink($inputFileName);
		// echo "QUERY SUCCESSFUL. LAST ID INSERTED: ".mysql_insert_id(); exit;
		redirect(base_url().'admin/inventory');

	}//end of recepient upload
	public function download_inventory_excel() {
	 	// echo "<pre>";print_r($this->input->get());exit;
		$filepath = "print_docs/excel/excel_template/inventory_upload_excel.xlsx";
	 	$this -> hcmp_functions -> download_file($filepath);
	 }

	public function get_county_id_for_district($district_id)
	{
		$query = "SELECT county FROM districts WHERE id = '$district_id'";
		$result = $this->db->query($query)->result_array();//FACILITY CODE SEARCH
		$county_id = $result[0]['county'];

		return $county_id;
	}

	public function get_inventory()
	{
		$query = "
		SELECT 
		    i.phone, i.added_on, c.county, d.district
		FROM
		    inventory i
		        LEFT JOIN
		    counties c ON i.county = c.id
		        LEFT JOIN
		    districts d ON d.id = i.subcounty";
		$result = $this->db->query($query)->result_array();//FACILITY CODE SEARCH
		// echo "<pre>";print_r($result);exit;	
		return $result;
	}

	public function download_excel(){
		// We'll be outputting an excel file

		$filepath = "print_docs/excel/excel_template/inventory_upload_excel.xlsx";

		$excel2 = PHPExcel_IOFactory::createReader('Excel2007');
    	$excel2=$objPHPExcel= $excel2->load($filename);
    	$objWriter = PHPExcel_IOFactory::createWriter($excel2, 'Excel2007');

		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
        header("Cache-Control: no-store, no-cache, must-revalidate");
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");
		// It will be called file.xls
		header("Content-Disposition: attachment; filename=$filename");
		// Write file to the browser
        $objWriter -> save('php://output');
       $objPHPExcel -> disconnectWorksheets();
       unset($objPHPExcel);
	}
	/*END OF INVENTORY*/

	/*REPORT LISTING*/
	public function report_listing()
	{
		//to move query functions to model,wanna complete this today so...why worry
		// same conundrum as when making inventory haha
		$report_listing_data = $this->get_report_listing_data();

		// echo "<pre>";print_r($inventory);exit;
		$data['report_listing_data'] = $report_listing_data;
		$data['title'] = "Report Listing";
		$data['banner_text'] = "Report Listing Management";
		$view = 'shared_files/template/dashboard_v';
		$data['content_view'] = "Admin/report_listing_v";
		$this->load->view($view,$data);
	}

	public function upload_report_listing_excel(){
		// echo "<pre>";print_r($this->input->post());echo "</pre>";exit;
		// error_reporting(E_ALL);
		$config['upload_path'] = './print_docs/excel/uploaded_files/';
		$config['allowed_types'] = 'xls|xlsx';
		$config['max_size']	= '2048';
		$name = 'inventory_'.date('d-m-Y').'_';
		$config['file_name'] = $name;
		$this->upload->initialize($config);
		if ( ! $this->upload->do_upload("report_listing_excel"))
		{
			echo "<pre>";print_r($this->upload->display_errors());echo "</pre>";
			// echo "I didnt work";
		}
		else
		{
			// echo "I work";exit;
			// $data = array('upload_data' => $this->upload->data());

			$result = $this->upload->data();
			$file_name = $result['file_name'];
			$this->upload_report_listing($file_name);
			// echo "I worked";
			$this->session->set_flashdata('message', 'The File upload was successful');
			redirect('admin/report_listing');
		}
	}//end of upload excel
	public function upload_report_listing($file_name){
		//  Include PHPExcel_IOFactory
		// include 'PHPExcel/IOFactory.php';
		// include 'PHPExcel/PHPExcel.php';

		// $inputFileName = 'excel_files/garissa_sms_recepients_updated.xlsx';
		// echo $category;exit;
		$inputFileName = 'print_docs/excel/uploaded_files/'.$file_name;

		$objReader = new PHPExcel_Reader_Excel2007();
		$objReader->setReadDataOnly(true);
		$objPHPExcel = $objReader->load($inputFileName);

		// echo "<pre>";print_r($inputFileName);exit;

		$sheet = $objPHPExcel->getSheet(0); 
		$highestRow = $sheet->getHighestRow()+1; 
		$highestColumn = $sheet->getHighestColumn();

		// echo "<pre>";print_r($highestRow);echo "</pre>";exit;
		$rowData = array();
		for ($row = 4; $row < $highestRow; $row++){ 
		    //  Read a row of data into an array
		    $rowData_ = $sheet->rangeToArray('A' . $row . ':F' . $row);
		// echo "<pre>";print_r($rowData_);echo "</pre>";
		    array_push($rowData, $rowData_[0]);
		    //  Insert row data array into your database of choice here
		}
		
		// echo "<pre>";print_r($rowData);exit;//echo's array

		foreach ($rowData as $r_data) {
			// echo "<pre>";print_r($r_data);echo "</pre>";
			/*
			Result array key
			0 = name
			1 = phone
			2 = email
			3 = county
			4 = subcounty
			5 = mfl
			*/
			$status = 1;
			$county_id = $district_id = $facility_code = 0;
			$new_county_id = $new_district_id = $new_facility_code = $usertype =  null;
			
			$county_name = strtolower($r_data[3]);//lower case
			$county_name = str_replace(" ", "", $county_name);
			$county_name = str_replace("-", " ", $county_name);
			$county_name = ucwords($county_name);//upper first character
			
			$district = strtolower($r_data[4]);
			$district = ucfirst($district);

			$phone = preg_replace('/\s+/', '', $r_data[1]);
			// echo "<pre>";print_r($phone);
			// echo "<pre>";print_r($county_name);
			$facility_code = (!empty($r_data[5]))? $r_data[5]:NULL;

			$name = (!empty($r_data[0]))? $r_data[0]:NULL;

			$email = (!empty($r_data[2]))? $r_data[2]:NULL;
				
			$date_uploaded = date('Y-m-d h:i:s');

			$fault_index = NULL;
			// echo $district;


			$query = "SELECT * FROM facilities WHERE facility_code = '$facility_code'";
			$result = $this->db->query($query)->result_array();//FACILITY CODE SEARCH
			// echo "<pre>";print_r($result);echo "</pre>";
			$sql = null;
			if (empty($result)){//if no facility code then district
				$queryy = "SELECT * FROM districts WHERE district = '$district'";
				$resultt = $this->db->query($queryy)->result_array();
				
				if (empty($resultt)) {//no district then county
					$queryyy = "SELECT * FROM counties WHERE county = '$county_name'";
					$resulttt = $this->db->query($queryyy)->result_array();
					if (empty($resulttt)){
						echo "Empty county,subcounty and facility";
					}else{
						$county_id = $resulttt[0]['id'];
						// echo "<pre>\t Only County ".$phone;
						$new_county_id = $county_id;	
						$usertype = 10;					

					}
				}else{//if district matches
					$district_id = $resultt[0]['id'];
					$county_id = $this->get_county_id_for_district($district_id);
					// echo "<pre>\t District ".$phone;
					$new_county_id = $county_id;
					$new_district_id = $district_id;

					$usertype = 3;

				}//district name match

			}//if no facility code match

			else{//if facility_code_match
				// echo "<pre>";print_r($result);exit;
				$district_id = $result[0]['district'];
				$county_id = $this->get_county_id_for_district($district_id);
				// echo "<pre>\t Facility: ".$phone;

				$new_county_id = $county_id;
				$new_district_id = $district_id;
				$new_facility_code = $facility_code;
				$usertype = 5;
				// echo "<pre>"; print_r($county_id);exit;
			}
					/*
					//code for appending 254 to phone numbers
					if (isset($phone)) {
						$phone = preg_replace('/\s+/', '', $phone);
						$phone = ltrim($phone, '0');
						// echo "<pre>".substr($phone, 0,3);
						if (substr($phone, 0,3) != '254') {
							$phone = '254'.$phone;
						}
					}else{
						$phone = NULL;
					}

					*/
					$number_length = isset($phone)?strlen($phone):0;
					// echo "Number Length:  ".$number_length;
					if ($number_length != 12) {
						if (isset($fault_index)) {
							$fault_index = 3;//both error in phone and district
							// $status = 2;
						}else{
							$fault_index = 2;
						}
							$fault_index = 2;//overriding both district and phone error as district is not necessarily necessary
							$status = 2;
					}
					/*commented out insertion below*/
					
					$listing = array();
					$listing_data = array(
						'name' => $name,
						'email' => $email,
						'phone_number' => $phone,
						'facility_code' => $new_facility_code,						
						'sub_county' => $new_district_id,
						'county' => $new_county_id,						
						'usertype' => $usertype,
						'date_uploaded' => $date_uploaded,
						'status'=>'0'						
						);
					array_push($listing, $listing_data);
					// echo "<pre>";print_r($inv);

					$similarity_query = "SELECT * FROM email_listing_new WHERE phone_number = '$phone'";
					$similarity = $this->db->query($similarity_query)->result_array();//FACILITY CODE SEARCH
					if (empty($similarity)){
						$insertion = $this->db->insert_batch('email_listing_new',$listing);
						// echo "QUERY SUCCESSFUL. ".$insertion." ".mysql_insert_id()."</br>";
					}else{
						// echo "<pre> Dab on em";
					}
		}
					// echo "<pre>";print_r($inv);
				
		// unlink($inputFileName);
		// echo "QUERY SUCCESSFUL. LAST ID INSERTED: ".mysql_insert_id(); exit;
		// redirect(base_url().'admin/report_listing');
		// exit;

	}//end of recepient upload
	public function download_report_listing_excel() {
	 	// echo "<pre>";print_r($this->input->get());exit;
		$filepath = "print_docs/excel/excel_template/report_listing_excel.xlsx";
	 	$this -> hcmp_functions -> download_file($filepath);
	}
	public function download_redistribution_receival_excel() {
	 	// echo "<pre>";print_r($this->input->get());exit;
		$filename = "Facility_redistributions.xlsx";		
	 	$this -> hcmp_functions -> clone_redistribution_template($filename);
	 	// $this -> hcmp_functions -> download_file($filepath);

	}
	public function get_report_listing_data()
	{
		$query = "SELECT distinct   el.name, el.email, el.phone_number AS phone, al.level as usertype,el.date_uploaded,
    				case when el.facility_code != '' then (select f.facility_name from facilities f where f.facility_code = el.facility_code) else 'N/A' end as facility,
    				case when el.sub_county != '' then (select d.district from districts d where d.id = el.sub_county) else 'N/A' end as district,
    				case when el.county != '' then (select c.county from counties c where c.id = el.county) else 'N/A' end as county    
					FROM   email_listing_new el, access_level al where al.id = el.usertype and el.status = '0'";

		$result = $this->db->query($query)->result_array();//FACILITY CODE SEARCH
		// echo "<pre>";print_r($result);exit;	
		return $result;
	}

	public function set_log_facility(){
		$sql = "select distinct user_id from log where user_id in (select distinct id from user)";
		$result = $this->db->query($sql)->result_array();	
		// echo count($result);die;	
			// echo "<pre>";print_r($result_facilities);die;

		foreach ($result as $key => $value) {
			$user_id = $value['user_id'];
			$sql_get_facility = "select facility from user where id='$user_id'";
			$result_facilities = $this->db->query($sql_get_facility)->result_array();
			foreach ($result_facilities as $keys => $values) {
				$facility_code = $values['facility'];
				if($facility_code!=0){
					$sql_update  = "update log set facility_code='$facility_code' where user_id='$user_id'";
					$this->db->query($sql_update);
				}
			}


		}
	}

}