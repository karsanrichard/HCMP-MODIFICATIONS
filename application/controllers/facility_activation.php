<?php
/*
 * @author Karsan
 */
if (!defined('BASEPATH'))
	exit('No direct script access allowed');
class Facility_activation extends MY_Controller 
{
	function __construct() 
	{
		// echo "I WORK";exit;
		// echo md5(123456);exit;
		parent::__construct();
		$this -> load -> helper(array('form', 'url'));
		$this -> load -> library(array('hcmp_functions', 'form_validation'));
	}

	public function index(){
		$this->facility_dash();
	}
	public function facility_dash(){
		$identifier = $this -> session -> userdata('user_indicator');
		$user_type_id = $this -> session -> userdata('user_type_id');
		$district = $this -> session -> userdata('district_id');
		// echo $identifier;exit;
		$county = $this -> session -> userdata('county_id');

		// $data['facilities'] = isset($district) ? Facilities::get_facility_details($district) : Facilities::get_facilities_per_county($county);

		if ($identifier == "district") {
			$data['facilities'] = Facilities::get_facility_details($district);
			$data['identifier'] = $identifier;
		}elseif ($identifier == "county") {
			$data['facilities'] = Facilities::get_facility_details(NULL,$county);
			$data['identifier'] = $identifier;
			$data['district_info'] = Districts::get_districts($county);

			// echo "<pre>";print_r($districts);echo "</pre>";exit;

		}
		$permissions='district_permissions';
		// $data['facilities']=Facilities::get_facility_details($district);
		// $data['facilities']=Facilities::get_facilities_per_county($county);
		// echo "<pre>";print_r($data['facilities']);echo "</pre>";exit;
		$data['title'] = "Facility Management";
		$data['banner_text'] = "Facility Management";
		$template = 'shared_files/template/template';
		// $data['sidebar'] = "shared_files/report_templates/side_bar_sub_county_v";
		$data['active_panel'] = 'system_usage';
		// $data['report_view'] = "shared_files/Facility_activation_v";
		$data['content_view'] = "shared_files/facility_activation_v";
		$this -> load -> view($template, $data);
	}


	public function facility_offline(){
		$identifier = $this -> session -> userdata('user_indicator');
		$user_type_id = $this -> session -> userdata('user_type_id');
		$district = $this -> session -> userdata('district_id');		
		$county = $this -> session -> userdata('county_id');

		if ($identifier == "district") {
			$data['facilities'] = Facilities::get_facility_details($district,NULL,1);
			$data['identifier'] = $identifier;
		}elseif ($identifier == "county") {
			$data['facilities'] = Facilities::get_facility_details(NULL,$county,1);
			$data['identifier'] = $identifier;
			$data['district_info'] = Districts::get_districts($county);
		}

		$permissions='district_permissions';
		$data['user_types']=Access_level::get_access_levels($permissions);	
		
		$data['title'] = "Offline Facility Setup";
		$data['district_id'] = $district;
		$data['banner_text'] = "Setup Facility Offline";
		$template = 'shared_files/template/template';
		// $data['sidebar'] = "shared_files/report_templates/side_bar_sub_county_v";
		$data['active_panel'] = 'system_usage';
		// $data['report_view'] = "shared_files/Facility_activation_v";
		$data['content_view'] = "shared_files/facility_offline";
		$this -> load -> view($template, $data);

	}

	public function change_status($facility_code = NULL,$status = NULL){
		$facility_code = $_POST['facility_code'];
		$status = $_POST['status'];

		$update_user = Doctrine_Manager::getInstance()->getCurrentConnection();
			$update_user->execute("UPDATE `facilities` SET using_hcmp = '$status' WHERE `facility_code`= '$facility_code'");
			echo $update_user." success";
	}

	public function get_facility_user_data($facility_code){
		// $facility_code = $_POST['facility_code'];
		$facility_data = Facilities::get_facilities_user_activation_data($facility_code);
		// echo "<pre>";print_r($facility_data);echo "</pre>";exit;		
		foreach ($facility_data as $key => $value) {
			$name = $value['fname'].' '.$value['lname'];
			$created_at = $value['created_at'];
			$last_login = $value['end_time_of_event'];
			$created = date('d F Y',strtotime($created_at));
			$last_login = date('d F Y',strtotime($last_login));
			$output[] = array($name,$created,$last_login);
		}
		echo json_encode($output);
	}

	public function get_facility_details($facility_code) {
		$facility_data = Facilities::get_more_stats($facility_code);
		$final_data = array();
		foreach ($facility_data as $key => $value) {
			$date_created = $value['date_of_activation'];
			$last_order = $value['last_order'];
			$last_activity = $value['last_activity'];
			$last_order_date = date('d F y', strtotime($last_order));
			$final_data = array('date_created' => $date_created, "last_order" => $last_order, "last_activity" => $last_activity);
		}
		// echo "<pre>"; print_r($final_data); echo "</pre>"; exit;
		echo json_encode($final_data);
	}

	//Titus 

	public function get_facility_stats($facility_code){
		$facility_data = Facilities::get_facilities_users_data($facility_code);
		foreach ($facility_data as $key => $value) {
			$user_id = $value['id'];
			$name = $value['fname'].' '.$value['lname'];
			$created_at = $value['created_at'];			
			$email = $value['email'];			
			$created = date('d F Y',strtotime($created_at));			
			$output[] = array($user_id,$name,$email,$created);
		}
		$final_output = array('number' =>count($facility_data) ,'list'=>$output );		
		echo json_encode($final_output);
	}
	//Titus
	public function change_status_new($facility_code = NULL,$status = NULL){
		$facility_code = $_POST['facility_code'];
		$status = $_POST['status'];
		$user_id = $this -> session -> userdata('user_id');
		$new_status =null;
		if($status==0){
			$new_status = 1;
			$current_date = date("Y-m-d");
			$update_activation_log = Doctrine_Manager::getInstance()->getCurrentConnection();
			$update_activation_log->execute("INSERT INTO facility_activation_logs (user_id, facility_code, action) VALUES ('$user_id', '$facility_code','Activation')");
		}else{
			$new_status = 0;
			$current_date = '0000-00-00 00:00:00';
			$update_activation_log->execute("INSERT INTO facility_activation_logs (user_id, facility_code, action) VALUES ('$user_id', '$facility_code','Deactivation')");
		}
		$update_user = Doctrine_Manager::getInstance()->getCurrentConnection();
		$update_user->execute("UPDATE `facilities` SET using_hcmp = '$new_status',date_of_activation='$current_date' WHERE `facility_code`= '$facility_code'");

		$sql = "select DISTINCT f.id, f.facility_code,f.date_of_activation,f.using_hcmp, f.facility_name, f.district, f.owner, c.county, d.district as district_name
		FROM facilities f, districts d, counties c
		WHERE f.facility_code='$facility_code'
		AND f.district=d.id
		AND d.county=c.id";
		$facility_details = $this->db->query($sql)->result_array();
		$date_of_activation = $facility_details[0]['date_of_activation'];
		$using_hcmp = $facility_details[0]['using_hcmp'];
		$date_of_activation = date('D ,d F Y',strtotime($date_of_activation));
		$output = array('date_of_activation'=>$date_of_activation,'using_hcmp'=>$using_hcmp);
		echo json_encode($output);
	}

	public function add_facility(){
		// echo"<pre>"; var_dump($this->input->post()); echo "</pre>"; exit;
		
		$facility_code = $this->input->post('facility_code');
		$facility_name = $this->input->post('facility_name');
		$district = $this->input->post('district');
		$owner = $this->input->post('owner');
		$facility_type = $this->input->post('facility_type');
		$facility_level = $this->input->post('facility_level');
		$contact_name = $this->input->post('contact_name');
		$contact_phone = $this->input->post('contact_phone');
		$activation_status = $this->input->post('activation_status');
		
		// echo $facility_code;exit;
		/*
		$facility_code = 1234;
		$facility_name = "asdfasdf";
		$district = 5;
		$owner = "asdfasdfasdfasdf";
		$facility_type = "weqwerqwer";
		$facility_level = "1";
		$contact_name = "asdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdf";
		$contact_phone = "788787878787";
		*/

		$facility_details = array();
		$facility_details_ = array(
			'facility_code' => $facility_code, 
			'facility_name' => $facility_name, 
			'district' => $district, 
			'owner' => $owner, 
			'type' => $facility_type, 
			'level' => $facility_level, 
			'contactperson' => $contact_name, 
			'cellphone' => $contact_phone,
			'drawing_rights'=> 0,
			'rtk_enabled'=> 0,
			'cd4_enabled'=> 0,
			'drawing_rights_balance'=> 0,
			'using_hcmp'=> $activation_status,
			'date_of_activation'=> date('Y m D'),
			'targetted'=> 0
			);

		array_push($facility_details, $facility_details_);

		$result = $this->db->insert_batch('facilities',$facility_details);
		$facility_id = $this->db->insert_id(); 
		$q = $this->db->get_where('facilities', array('id' => $facility_id));
		foreach ($q->result() as $row)
		{
		        echo $row->facility_code;
		}
		// echo "This: ".$facility_id;exit;
		// $savefacility = new Facilities();

		// $savefacility -> facility_code = $facility_code;
		// $savefacility -> facility_name = $facility_name;
		// $savefacility -> district = $district;
		// $savefacility -> owner = $owner;
		// $savefacility -> facility_type = $facility_type;
		// $savefacility -> facility_level = $facility_level;
		// $savefacility -> contact_name = $contact_name;
		// $savefacility -> contact_phone = $contact_phone;
		// $savefacility -> save(); 

	}

	public function download_system_zip()
	{	
		ini_set('memory_limit', '-1');
		$path = FCPATH.'tmp/HCMP.zip';
		$this->hcmp_functions->download_file($path);
	}
}

?>