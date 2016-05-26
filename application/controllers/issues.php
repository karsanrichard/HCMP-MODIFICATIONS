<?php
/**
 * @author Kariuki
 */
if (!defined('BASEPATH'))
	exit('No direct script access allowed');
class issues extends MY_Controller {
	function __construct() {
		parent::__construct();

		$this -> load -> helper(array('form', 'url'));
		$this -> load -> library(array('hcmp_functions', 'form_validation'));
		$this -> load -> database();
	}

	/*
	 |--------------------------------------------------------------------------
	 | facility issuing to service points
	 |--------------------------------------------------------------------------
	 |1. load the view/ determine if its a redistribution/ internal issue
	 |2. check if the facility has commodity data
	 |4. save the data in the facility stock, facility transaction , issues table
	 */
	 public function index($checker = NULL) {
	 	$facility_code = $this -> session -> userdata('facility_id');
	 	switch ($checker) :
	 	case 'internal' :
	 	$data['content_view'] = "facility/facility_issues/facility_issues_service_points_v";
	 	$data['title'] = "Issues to service points";
	 	$data['banner_text'] = "Issues to service points";
	 	$data['district_store'] = 0;
	 	break;
	 	case 'external' :
	 	$data['content_view'] = "facility/facility_issues/facility_redistribute_items_v";
	 	$data['subcounties'] = districts::getAll();
	 	$data['banner_text'] = "Redistribute Commodities";
	 	$data['title'] = "Redistribute Commodities";
	 	$data['district_store'] = 0;
	 	break;
	 	case 'district_store':	
	 	$district_id = $this -> session -> userdata('district_id');
	 	$dist = districts::get_district_name_($district_id);
	 	$data['district_id'] = $this -> session -> userdata('district_id');
	 	$data['district_data'] = districts::get_district_name_($district_id);
	 	$data['content_view'] = "subcounty/drug_store/drug_store_recieve_items_v";
	 	$data['donate_destination'] = "district";
	 	$data['subcounties']=districts::getAll();
	 	$data['banner_text'] = "Redistribute Commodities";
	 	$data['title'] ="Redistribute Commodities";						
	 	$data['district_store'] = 1;
					//$data['store_commodities'] = drug_store_issues::get_commodities_for_district($district_id);
	 	break;	
	 	default :
	 	endswitch;
	 	$data['service_point'] = service_points::get_all_active($facility_code);
	 	$data['commodities'] = facility_stocks::get_distinct_stocks_for_this_facility($facility_code, 1);

	 	// echo "<pre>";
	 	// print_r($data['commodities']);
	 	// echo "</pre>";
	 	// exit;
	 	
	 	$data_ = facility_stocks::get_distinct_stocks_for_this_facility($facility_code, "batch_data");
	 	foreach ($data_ as $key => $data_1) {
	 		$data_[$key]['commodity_name'] = preg_replace('/[^A-Za-z0-9\-]/', ' ', $data_1['commodity_name']);
	 	}

	 	// echo "<pre>";
	 	// print_r($data_);
	 	// echo "</pre>";
	 	// exit;
	 	$data['facility_stock_data'] = json_encode($data_);
		// echo "<pre>";print_r($data_);echo "</pre>";//exit;
		// echo "<pre>";print_r($data['commodities']);echo "</pre>";exit;
		// echo $district_id;exit;
	 	$this -> load -> view("shared_files/template/template", $data);
	 }


	public function reversals(){
		ini_set('memory_limit', '-1');	
		$data['title'] = "Reversals";
		$data['content_view'] = "facility/reversals_v";			
		$data['title'] = "System Home";
		$data['banner_text'] = "Reversals";
		$view = 'shared_files/template/template';
		$this -> load -> view($view, $data);
	}

	public function reverse_issue($commodity_id,$time,$issuer){
		$current_time = date("Y-m-d H:i:s", time());	
		$current_date = date("Y-m-d", time());	
		$created_at = date("Y-m-d H:i:s", $time);	
		$facility_code = $this -> session -> userdata('facility_id');		
		$current_user = $this -> session -> userdata('user_id');		
		$issue_details = Facility_issues::get_facility_issue_details_for_reversals($facility_code,$commodity_id,$created_at,$issuer);
		foreach ($issue_details as $key => $value) {
			$id = $value['id'];
			$facility_code = $value['facility_code'];
			$commodity_id = $value['commodity_id'];
			$s11_No = $value['s11_No'];
			$batch_no = $value['batch_no'];
			$expiry_date = $value['expiry_date'];
			$balance_as_of = intval($value['balance_as_of']);
			$qty_issued = intval($value['qty_issued']);
			$issued_to = $value['issued_to'];
			$new_balance = $balance_as_of - $qty_issued;
			$new_quantity = $qty_issued*-1;			
			$insert = Doctrine_Manager::getInstance() -> getCurrentConnection();
					$insert -> execute("INSERT INTO facility_issues
					 (`id`, `facility_code`, `commodity_id`, `s11_No`, `batch_no`, `expiry_date`, `balance_as_of`, `adjustmentpve`,`adjustmentnve`, `qty_issued`, `date_issued`, `issued_to`,`created_at`,`issued_by`, `status`)
     			   VALUES (null,'$facility_code','$commodity_id','reversed issue', '$batch_no','$expiry_date','$new_balance', '0','0','$new_quantity','$current_date', 'N/A','$current_time','$current_user','1')");
			$update = Doctrine_Manager::getInstance() -> getCurrentConnection();
			$update -> execute("update facility_issues set status = '3'	where `id`= $id");
			$update -> execute("update facility_stocks set current_balance = (current_balance+'$qty_issued'), date_modified='$current_time' where `facility_code`= '$facility_code' and `commodity_id`= '$commodity_id' and `batch_no`= '$batch_no' ");
		}
		redirect('issues/reversals');
	}
	public function get_reversal_table(){
		$graph_data = array();
		$facility_code = $this -> session -> userdata('facility_id');
		$start_date=date('Y-m-d', strtotime("-30 days"));
		// $start_date = date('Y-m-01',strtotime('-0 month'));		
		$current_issues = Facility_issues::get_facility_issues_for_reversals($facility_code,$start_date);
		// echo "<pre>";
		// print_r($current_issues);die;
		foreach ($current_issues as $key => $value) {
              $commodity_id = $value['commodity_id'];
              $commodity_name = $value['commodity_name'];
              $batch_no = $value['batch_no'];
              $s11_No = $value['s11_No'];
              $qty_issued = $value['qty_issued'];
              $issued_to = $value['issued_to'];
              if($s11_No=='internal issue'){
					if (preg_match('/[A-Za-z]/i', $issued_to)) {
						$issued_to = $value['issued_to'];
					}else{
						$service_point_name = intval($issued_to);
						$service_point_details = Facility_issues::get_one_service_points($service_point_name);
						foreach ($service_point_details as $keys => $values) {
							$issued_to = $values['service_point_name'];
						}

					}
				}else{
              		$issued_to = $value['issued_to'];
				}
              $issue_date = $value['date_issued'];
              $create_date_raw = $value['created_at'];
              $create_date = date('F, d Y', strtotime($create_date_raw));
              $issue_date = date('F, d Y', strtotime($issue_date));
              $issuer = $value['fname'].' '.$value['lname'];                          
              $issuer_id = $value['issued_by'];
              $create_date_timestamp = strtotime($create_date_raw); 
              $data_id = $commodity_id.'/'.$create_date_timestamp.'/'.$issuer_id;
              $button_reverse_link = '<button class="btn btn-danger status_btn form-control" style="width:98%"  data-id="'.$data_id.'" id="'.$data_id.'" data-attr="'.$data_id.'" data-value="'.$data_id.'">Reverse</button>';
              // $button_dets_link = '<button class="btn btn-danger status_btn form-control" style="width:98%"  data-id="'.$data_id.'" id="'.$data_id.'" data-attr="'.$data_id.'" data-value="'.$data_id.'">Reverse</button>';
              // $button_reverse_link = "<a href=\"".base_url().'issues/reverse_issue/'.$commodity_id.'/'.$create_date_timestamp.'/'.$issuer_id.'/reverse'."\"><button class=\"btn btn-danger  form-control\" style=\"width:98%\">Reverse Issue</button></a>";
              $output[] = array($commodity_name,$batch_no,$qty_issued,$issue_date,$issued_to,$issuer,$create_date,$button_reverse_link);
        }
  //       $category_data = array( array("Commodity Name", "Batch Number","Quantity Issued (Units)", "Date of Issue",  "Issued To", "Name of Issuer", "Action"));
		// $graph_data = array_merge($graph_data, array("table_id" => 'issues_tbl'));
		// $graph_data = array_merge($graph_data, array("table_header" => $category_data));
		// $graph_data = array_merge($graph_data, array("table_body" => $output));
		// $data = array();
		// $data['table'] = $this -> hcmp_functions -> create_data_table($graph_data);	
		// return $this -> load -> view("shared_files/report_templates/data_table_template_v", $data);  
		echo json_encode($output); 
	}

	public function get_reversed_table(){
		$graph_data = array();
		$facility_code = $this -> session -> userdata('facility_id');
		$start_date = date('Y-m-01',strtotime('-0 month'));		
		$current_reversals = Facility_issues::get_facility_issues_reversals($facility_code,$start_date);		
		foreach ($current_reversals as $key => $value) {
              $commodity_id = $value['commodity_id'];
              $commodity_name = $value['commodity_name'];
              $batch_no = $value['batch_no'];
              $qty_issued = intval($value['qty_issued'])*-1;
              $issued_to = $value['issued_to'];
              // $issue_date = $value['date_issued'];
              $create_date_raw = $value['date_issued'];
              $create_date = date('F, d Y', strtotime($create_date_raw));
              // $issue_date = date('F, m Y', strtotime($issue_date));
              $issuer = $value['fname'].' '.$value['lname'];                          
              $issuer_id = $value['issued_by'];
              $create_date_timestamp = strtotime($create_date_raw); 
              // $data_id = $facility_code.'/'.$create_date_timestamp.'/'.$issuer_id;
              // $button_dets_link = '<button class="btn btn-success status_btn form-control" style="width:98%"  data-id="'.$data_id.'" id="'.$data_id.'" data-attr="'.$data_id.'" data-value="'.$data_id.'">View Details</button>';
              // $button_reverse_link = "<a href=\"".base_url().'issues/reverse_issue/'.$commodity_id.'/'.$create_date_timestamp.'/'.$issuer_id.'/reverse'."\"><button class=\"btn btn-danger  form-control\" style=\"width:98%\">Reverse Issue</button></a>";
              $output[] = array($commodity_name,$batch_no,$qty_issued,$issued_to,$create_date,$issuer);
        }
        echo json_encode($output); 
        // $category_data = array( array("Commodity Name", "Batch Number","Quantity Reversed (Units)", "Date of Reversal",  "Issued To", "Person Reversing"));
		// $graph_data = array_merge($graph_data, array("table_id" => 'reversed_issues_tbl	'));
		// $graph_data = array_merge($graph_data, array("table_header" => $category_data));
		// $graph_data = array_merge($graph_data, array("table_body" => $output));
		// $data = array();
		// $data['table'] = $this -> hcmp_functions -> create_data_table($graph_data);	
		// return $this -> load -> view("shared_files/report_templates/data_table_template_v", $data);   
	}


	 public function generate_issue_excel()
	 {
	 	$this -> hcmp_functions -> clone_facility_issues_sp_template('download_file');
	 	
	 }
	 public function county_store_home()
	 {
	 	$county_id = $this -> session -> userdata('county_id');

	 	$data['content_view'] = "county/county_drug_store_home";
	 	// echo "I get here";exit;
	 	$data['county_dashboard_notifications'] = $this -> get_county_dashboard_notifications_graph_data();
	 	// echo "<pre>";print_r($data['county_dashboard_notifications']);exit;
	 	$data['title'] = "County Store Home";
	 	$data['banner_text'] = "Drug Store";

	 	$this-> load -> view("shared_files/template/template", $data);
	 }

	 /* Function For Donations to Districts*/
	 public function county_store()
	 {
	 	$county_id = $this -> session -> userdata('county_id');
	 	$data['county_id'] = $this -> session -> userdata('county_id');
	 	// echo "<pre>";print_r($this->session->all_userdata());exit;
	 	$data['county_data'] = counties::get_county_info($county_id);
	 	$county = counties::get_county_name($county_id);
	 	$data['county_id'] = $county_id;
	 	// $data['county_data'] = $county;
	 	$data['content_view'] = "county/county_drug_store";
	 	$data['donate_destination'] = "facility";
	 	$data['subcounties'] = districts::getAll();
	 	$data['banner_text'] = "Redistribute Commodities To Facilities";
	 	$data['title'] = "Redistribute Commodities";
	 	$data['commodities'] = facility_stocks::get_distinct_stocks_for_this_county_store($county_id);

	 	$data['facility_stock_data'] = json_encode(facility_stocks::get_distinct_stocks_for_this_county_store($county_id,"batch_data"));
	 	$this -> load -> view("shared_files/template/template", $data);
	 }

	 public function county_store_facilities()
	 {
	 	$county_id = $this -> session -> userdata('county_id');

	 }

	 public function county_store_internal()
	 {
	 	$county_id = $this -> session -> userdata('county_id');
	 	$county = counties::get_county_name($county_id);
	 	$data['county_id'] = $county_id;
	 	$data['county_data'] = $county;
	 	$data['content_view'] = "county/drug_store/drug_store_internal";
	 	$data['donate_destination'] = "county";
	 	$data['counties'] = counties::getAll();
	 	$data['banner_text'] = "Redistribute Commodities To Counties";
	 	$data['title'] = "Redistribute Commodities";

	 	$data['commodities'] = "";
	 	$data['county_stock_data'] = "";
	 	echo "<pre>"; print_r($data); echo "</pre>";
	 	$this -> load -> view("shared_files/template/template", $data);
	 }

	 public function store_home(){
	 	$district_id = $this -> session -> userdata('district_id');	
		// echo $district_id;exit;
		//$data['expiry_data'] = Facility_stocks::drug_store_commodity_expiries($district_id);
		// echo "<pre>";print_r($data['expiry_data']);echo "</pre>";exit;

	 	$data['content_view'] = "subcounty/subcounty_drug_store_home";	
	 	$data['district_dashboard_notifications']=$this->get_district_dashboard_notifications_graph_data();
		//echo $district_id;exit;
	 	$data['title'] = "Drug Store Home";
	 	$data['banner_text'] = "Drug Store";
	 	$this -> load -> view("shared_files/template/template", $data);
	 }

	 public function district_store(){
	 	$district_id = $this -> session -> userdata('district_id');	
	 	$dist = districts::get_district_name_($district_id);	
	 	$data['district_id'] = $this -> session -> userdata('district_id');
	 	$data['district_data'] = districts::get_district_name_($district_id);
	 	$data['content_view'] = "subcounty/subcounty_drug_store";
	 	$data['donate_destination'] = "facility";
	 	$data['subcounties']=districts::getAll();
	 	$data['banner_text'] = "Redistribute Commodities to Facilities";
	 	$data['title'] ="Redistribute Commodities";		
		//$data['service_point']=service_points::get_all_active($facility_code);		
	 	$data['commodities'] = facility_stocks::get_distinct_stocks_for_this_district_store($district_id,1);
		// echo "<pre>";print_r($data['commodities']);echo "</pre>";exit;
	 	$data['facility_stock_data']=json_encode(facility_stocks::get_distinct_stocks_for_this_district_store($district_id,"batch_data"));	
	 	$this -> load -> view("shared_files/template/template", $data);

	 }

	 public function district_store_internal(){
	//THIS FUNCTION VANISHES WHEN A COLLINS RELATED PULL GOES DOWN
	//#collins_repo #my_function_my_choice #hahahaha # this was some random thing so that i can commit this function. Cheers
	 	$district_id = $this -> session -> userdata('district_id');	
	 	$dist = districts::get_district_name_($district_id);	
	 	$data['district_id'] = $this -> session -> userdata('district_id');
	 	$data['district_data'] = districts::get_district_name_($district_id);
	 	$data['content_view'] = "subcounty/drug_store/drug_store_internal";
	 	$data['donate_destination'] = "facility";
	 	$data['subcounties']=districts::getAll();
	 	$data['banner_text'] = "Redistribute Commodities to District Stores";
	 	$data['title'] ="Redistribute Commodities";		
		//$data['service_point']=service_points::get_all_active($facility_code);		
	 	$data['commodities'] = facility_stocks::get_distinct_stocks_for_this_district_store($district_id,1);
		// echo "<pre>";print_r($data['commodities']);echo "</pre>";exit;
	 	$data['facility_stock_data']=json_encode(facility_stocks::get_distinct_stocks_for_this_district_store($district_id,"batch_data"));	
	 	$this -> load -> view("shared_files/template/template", $data);

	 }

	 public function district_store_internal_issue()
	 {
			// echo "<pre>";print_r($this -> input -> post());echo "</pre>";exit;
			//security check
		 	$facility_code = $this -> session -> userdata('facility_id');
		 	$district_id = $this -> session -> userdata('district_id');
			$service_point = 2;//predefined since 2 is the official code for all district stores
			$destined_district = array_values($this->input->post('district'));
			$commodity_id = array_values($this -> input -> post('desc'));
			$commodity_balance_before = array_values($this -> input -> post('commodity_balance'));
			$facility_stock_id = array_values($this -> input -> post('facility_stock_id'));
			$batch_no = array_values($this -> input -> post('batch_no'));
			$expiry_date = array_values($this -> input -> post('expiry_date'));
			$commodity_unit_of_issue = array_values($this -> input -> post('commodity_unit_of_issue'));
			$quantity_issued = array_values($this -> input -> post('quantity_issued'));
			$clone_datepicker_normal_limit_today = array_values($this -> input -> post('clone_datepicker_normal_limit_today'));
			$manufacture = array_values($this -> input -> post('manufacture'));

			$total_units = array_values($this -> input -> post('total_units'));
			$total_items = count($facility_stock_id);
			//var_dump($total_units);exit;
			$data_array_issues_table = array();
			$data_array_redistribution_table = array();

			$new_transaction_entry = array();
			$new_drug_store_entry = array();

			$digested_expiry = $expiry_date[0] ;
			for ($i = 0; $i < $total_items; $i++) ://compute the actual stock

			$total_items_issues = ($commodity_unit_of_issue[$i] == 'Pack_Size') ? $quantity_issued[$i] * $total_units[$i] : $quantity_issued[$i];
			$mydata = array('facility_code' => $facility_code, 's11_No' => '(-ve Adj) Stock Deduction', 'batch_no' => $batch_no[$i], 'commodity_id' => $commodity_id[$i], 'expiry_date' => date('y-m-d', strtotime($expiry_date[$i])), 'qty_issued' => $total_items_issues, 'issued_to' => "inter-facility donation:" . $facility_name, 'balance_as_of' => $commodity_balance_before[$i], 'date_issued' => date('y-m-d', strtotime($clone_datepicker_normal_limit_today[$i])), 'issued_by' => $this -> session -> userdata('user_id'));

			$mydata_2 = array('manufacturer' => $manufacture[$i],'source_district_id'=> $district_id, 'source_facility_code' => $facility_code, 'batch_no' => $batch_no[$i], 'commodity_id' => $commodity_id[$i], 'expiry_date' => date('y-m-d', strtotime($expiry_date[$i])), 'quantity_sent' => $total_items_issues, 'receive_facility_code' => $service_point[$i], 'facility_stock_ref_id' => $facility_stock_id[$i], 'date_sent' => date('y-m-d'), 'sender_id' => $this -> session -> userdata('user_id'));
				// update the issues table
			array_push($data_array_issues_table, $mydata);
			array_push($data_array_redistribution_table, $mydata_2);
			$existence = drug_store_issues::check_internal_transaction_existence($commodity_id[$i],$destined_district[$i]);
			$store_existence = drug_store_issues::check_drug_existence($commodity_id[$i],$district_id);
					// echo "<pre>"; print_r($existence);echo "</pre>";;exit;
			if ($existence[0]['present'] >= 0) {
				$update_transactions = Doctrine_Manager::getInstance() -> getCurrentConnection();
				$update_transactions -> execute("UPDATE `drug_store_internal_transaction_table` SET `total_issues` = `total_issues`+$total_items_issues,
					`closing_stock`=`closing_stock`-$total_items_issues
					WHERE `commodity_id`= '$commodity_id[$i]' and status='1' and district_id='$district_id'"
					);
		            // echo $update_transactions;exit;
			}else{
				$new_entry_details = array(
					'district_id'=>$destined_district[$i],
					'commodity_id'=>$commodity_id[$i],
					'opening_balance'=>$total_items_issues,
					'total_issues'=> $total_items_issues,
					'closing_stock'=>$total_items_issues,
					'status'=> 1
					);

				array_push($new_transaction_entry, $new_entry_details);
				$data = $this->db->insert_batch('drug_store_internal_transaction_table',$new_transaction_entry);

			}
			if ($store_existence[0]['present'] >= 1) {
				$update_totals = Doctrine_Manager::getInstance() -> getCurrentConnection();
				$update_totals -> execute("UPDATE `drug_store_totals` SET `total_balance` = `total_balance`-$total_items_issues
					WHERE `commodity_id`= '$commodity_id[$i]' and district_id='$district_id';");
		            // echo $update_totals;exit;
			}else{
				$new_entry_details = array(
					'district_id'=>$district_id,
					'commodity_id'=>$commodity_id[$i],
					'total_balance'=>$total_items_issues,
					'expiry_date'=>$expiry_date[$i]
					);

				array_push($new_drug_store_entry, $new_entry_details);
				$data = $this->db->insert_batch('drug_store_totals',$new_drug_store_entry);
			}

			endfor;

			$user = $this -> session -> userdata('user_id');
			$user_action = "redistribute";
			//updates the log table accordingly based on the action carried out by the user involved
			$update = Doctrine_Manager::getInstance()->getCurrentConnection();
			$update -> execute("update log set $user_action = 1  
				where `user_id`= $user 
				AND action = 'Logged In' 
				and UNIX_TIMESTAMP( `end_time_of_event`) = 0");
			
			// $this -> db -> insert_batch('facility_issues', $data_array_issues_table);
			// $this -> db -> insert_batch('redistribution_data', $data_array_redistribution_table);
			$this -> session -> set_flashdata('system_success_message', "You have issued $total_items item(s)");
			redirect(home);
		// redirect(home);		
	}//district store internal issue

	public function get_county_dashboard_notifications_graph_data()
	{
		$county_id = $this -> session -> userdata('county_id');
		$county_stock = facility_stocks::get_county_stock_amc($county_id);
		// echo "<pre> Here";print_r($county_stock);exit;
		$county_stock_count = count($county_stock);
		$graph_id = "container";
		$graph_data = array();
		$graph_data = array_merge($graph_data, array("graph_id" => $graph_id));
		$graph_data = array_merge($graph_data, array("graph_title" => "County Store Stock Level"));
		$graph_data = array_merge($graph_data, array("graph_type" => "bar"));
		$graph_data = array_merge($graph_data, array("graph_yaxis_title" => "Total Stock Level"));
		$graph_data = array_merge($graph_data, array("graph_categories" => array()));
		$graph_data = array_merge($graph_data, array("series_data" => array("Current Pack Balance" => array(), "Current Unit Balance" => array())));
		$graph_data['stacking'] = 'normal';
		
		foreach($county_stock as $county_stock):
			$graph_data['graph_categories'] = array_merge($graph_data['graph_categories'], array($county_stock['commodity_name']));
		$graph_data['series_data']['Current Pack Balance'] = array_merge($graph_data['series_data']['Current Pack Balance'],array((float) $county_stock['pack_balance']));
		$graph_data['series_data']['Current Unit Balance'] = array_merge($graph_data['series_data']['Current Unit Balance'],array((float) $county_stock['commodity_balance']));
		endforeach;
		
		$county_stock_data = $this -> hcmp_functions -> create_high_chart_graph($graph_data);
		//echo "<pre>"; print_r($county_stock_data); echo "</pre>"; exit;
		$loading_icon = base_url('assets/img/no-record-found.png');
		$county_stock_data = ($county_stock_count > 0) ? $county_stock_data : "$('#container').html('<img src=$loading_icon>');";

		$actual_expiries = count(facility_stocks::county_drug_store_act_expiries($county_id));
		$potential_expiries = count(facility_stocks::county_drug_store_pte_expiries($county_id));
		// echo "<pre> This";print_r($actual_expiries);exit;
		$county_donations = count(redistribution_data::get_all_active_drug_store_county($county_id));
		//$real_county_donations = county(redistribution_data::get_all_active_drug_store($county_id, "to-me"));
		return array(
			"county_stock_count" => $county_stock_count,
			"county_stock_graph" => $county_stock_data,
			"potential_expiries" => $potential_expiries,
			"actual_expiries" => $actual_expiries,
			"county_donations" => $county_donations
			);
	}

	public function get_district_dashboard_notifications_graph_data()
	{
     //format the graph here
     //$facility_code=$this -> session -> userdata('facility_id');
		$district_id = $this -> session -> userdata('district_id');
		$district_stock_=facility_stocks::get_district_stock_amc($district_id);
		$district_stock_count=count($district_stock_);
		$graph_data=array();
		$graph_data=array_merge($graph_data,array("graph_id"=>'container'));
		$graph_data=array_merge($graph_data,array("graph_title"=>'District Store Stock level'));
		$graph_data=array_merge($graph_data,array("graph_type"=>'bar'));
		$graph_data=array_merge($graph_data,array("graph_yaxis_title"=>'Total stock level  (values in packs)'));
		$graph_data=array_merge($graph_data,array("graph_categories"=>array()));
		$graph_data=array_merge($graph_data,array("series_data"=>array("Current Pack Balance"=>array(),"Current Unit Balance"=>array())));
		$graph_data['stacking']='normal';
		foreach($district_stock_ as $district_stock_):
			$graph_data['graph_categories']=array_merge($graph_data['graph_categories'],array($district_stock_['commodity_name']));	
		$graph_data['series_data']['Current Pack Balance']=array_merge($graph_data['series_data']['Current Pack Balance'],array((float) $district_stock_['pack_balance']));	
		$graph_data['series_data']['Current Unit Balance']=array_merge($graph_data['series_data']['Current Unit Balance'],array((float) $district_stock_['commodity_balance']));	
			// $graph_data['series_data'] = array_merge($graph_data['series_data'], array("Potential Expiries" => $series_data2, "Actual Expiries" => $series_data));

		endforeach;
 	//create the graph here
    //echo "I WORK";exit;

		$district_stock_data=$this->hcmp_functions->create_high_chart_graph($graph_data);
		$loading_icon=base_url('assets/img/no-record-found.png'); 
		$district_stock_data=($district_stock_count>0)? $district_stock_data : "$('#container').html('<img src=$loading_icon>');" ;

     //get potential expiries info here
		$potential_expiries_=count(Facility_stocks::drug_store_commodity_expiries($district_id));
     //get actual Expiries info here
		$actual_expiries=count(Facility_stocks::drug_store_commodity_expiries($district_id));
 	//get items they have been donated for
		$facility_donations=redistribution_data::get_all_active_drug_store($district_id,"to-me")->count();
    // echo "<pre>";print_r($facility_donations);echo "</pre>";exit;
    //seth
 	//get stocks from v1
		$stocks_from_v1=0;
		return array('district_stock_count'=>$district_stock_count,
			'district_stock_graph'=>$district_stock_data,
			'potential_expiries'=>$potential_expiries_,
			'actual_expiries'=>$actual_expiries,
			'facility_donations'=>$facility_donations,
			'stocks_from_v1'=>$stocks_from_v1
			);	
	}

	public function district_store_issue()
	{
		//security check
		if($this->input->post('mfl')):
			$facility_code=$this -> session -> userdata('facility_id');
		$district_id = $this -> session -> userdata('district_id');	
		$service_point=array_values($this->input->post('mfl'));
		$commodity_id=array_values($this->input->post('desc'));
		$commodity_balance_before=array_values($this->input->post('commodity_balance'));
		$facility_stock_id=array_values($this->input->post('facility_stock_id'));
		$batch_no=array_values($this->input->post('batch_no'));
		$expiry_date=array_values($this->input->post('expiry_date'));
		$commodity_unit_of_issue=array_values($this->input->post('commodity_unit_of_issue'));
		$quantity_issued=array_values($this->input->post('quantity_issued'));
		$clone_datepicker_normal_limit_today=array_values($this->input->post('clone_datepicker_normal_limit_today'));
		$manufacture=array_values($this->input->post('manufacture'));
		$total_units=array_values($this->input->post('total_units'));
		$total_items=count($facility_stock_id);
		$data_array_issues_table=array();
		$data_array_redistribution_table=array();

		$commodity_ids=facility_stocks::get_district_store_commodities($district_id);
		
		$commodity_total = facility_stocks::get_district_store_total_commodities($district_id);

		$comm_count = count($commodity_total);
		// echo "<pre>";print_r($commodity_total);echo "</pre>";exit;

		// insertion of data to totals table
		$totals_data =array();
		$totals_data_ =array();
		
		
		// update the issues table 
		        for($i=0;$i<$total_items;$i++)://compute the actual stock

		        $updated_ckecker = 1;
		        foreach ($commodity_total as $comm) {
		        	if (in_array(($district_id), $comm)){
		        		if (in_array(($commodity_id[$i]), $comm)){
		        			$b = Doctrine_Manager::getInstance()->getCurrentConnection();
		        			$b->execute("UPDATE `drug_store_totals` SET `total_amount` = `total_amount`+$quantity_issued[$i] where commodity_id ='$commodity_id[$i]' and district_id = '$district_id'");
		        			$updated_ckecker = 2;
		        		}
		    }//end of dist if
		}

		switch ($updated_ckecker) {
			case 1:
			$totals_data =array();
			$totals_data_ =array();

			$totals_data_ = array(
				'district_id'=>$district_id,
				'commodity_id' => $commodity_id[$i],
				'total_amount' => $quantity_issued[$i]
				);
			array_push($totals_data,$totals_data_);
			$this->db->insert_batch('drug_store_totals', $totals_data);
			break;
			case 2:
			break;
			default:
			break;
		}
		
		$total_items_issues=($commodity_unit_of_issue[$i]=='Pack_Size')? 
		$quantity_issued[$i]*$total_units[$i] : $quantity_issued[$i]; 

	     //prepare the issues data
		$facility_name=isset($service_point[$i]) ? Facilities::get_facility_name2($service_point[$i]) : null;
		$facility_name=isset($facility_name)? $facility_name['facility_name']: 'N/A';
		$mydata = array('facility_code' => $facility_code,	 
			's11_No'=>'(-ve Adj) Stock Deduction', 'batch_no' => $batch_no[$i] ,'commodity_id' => $commodity_id[$i],
			'expiry_date' => date('y-m-d',strtotime($expiry_date[$i])),'qty_issued'=> $total_items_issues ,
			'issued_to'=>"inter-facility donation:".$facility_name,'balance_as_of'=>$commodity_balance_before[$i], 
			'date_issued'=>date('y-m-d',strtotime($clone_datepicker_normal_limit_today[$i])),'issued_by'=>$this -> session -> userdata('user_id'));

		$mydata_2 = array('manufacturer'=>$manufacture[$i],
			'district_id' => $district_id,
			'source_facility_code' => $facility_code,	 
			'batch_no' => $batch_no[$i] ,'commodity_id' => $commodity_id[$i],
			'expiry_date' => date('y-m-d',strtotime($expiry_date[$i])),'quantity_sent'=> $total_items_issues ,
			'receive_facility_code'=>$service_point[$i],'facility_stock_ref_id'=>$facility_stock_id[$i], 
			'date_sent'=>date('y-m-d'),'sender_id'=>$this -> session -> userdata('user_id'));				
				// update the issues table 
		array_push($data_array_issues_table,$mydata); 
		array_push($data_array_redistribution_table,$mydata_2); 
	            // reduce the stock levels 
		$a = Doctrine_Manager::getInstance()->getCurrentConnection();
		$a->execute("UPDATE `facility_stocks` SET `current_balance` = `current_balance`-$total_items_issues where id='$facility_stock_id[$i]'");
	            //update the transaction table here 
		$inserttransaction = Doctrine_Manager::getInstance()->getCurrentConnection();
		$inserttransaction->execute("UPDATE `facility_transaction_table` SET `total_issues` = `total_issues`+$total_items_issues,
			`closing_stock`=`closing_stock`-$total_items_issues
			WHERE `commodity_id`= '$commodity_id[$i]' and status='1' and facility_code='$facility_code';");	

		endfor;
		$user = $this -> session -> userdata('user_id');
		$user_action = "redistribute";
				//updates the log table accordingly based on the action carried out by the user involved
		$update = Doctrine_Manager::getInstance()->getCurrentConnection();
		$update -> execute("update log set $user_action = 1  
			where `user_id`= $user 
			AND action = 'Logged In' 
			and UNIX_TIMESTAMP( `end_time_of_event`) = 0");
		$this->db->insert_batch('facility_issues', $data_array_issues_table); 
		$this->db->insert_batch('redistribution_data', $data_array_redistribution_table); 
		$this->session->set_flashdata('system_success_message', "You have issued $total_items item(s)");
		redirect();
		endif;
		redirect();	
	}//confirm distribution to district store

	public function county_store_external_issue(){//karsan
			// echo "<pre>";print_r($this -> input -> post());echo "</pre>";exit;
			//security check
		if ($this -> input -> post('mfl')) :
			$facility_code = $this -> session -> userdata('facility_id');
		$district_id = $this -> session -> userdata('district_id');
		$county_id = $this -> session -> userdata('county_id');
		$service_point = array_values($this -> input -> post('mfl'));
		$commodity_id = array_values($this -> input -> post('desc'));
		$commodity_balance_before = array_values($this -> input -> post('commodity_balance'));
		$facility_stock_id = array_values($this -> input -> post('facility_stock_id'));
		$batch_no = array_values($this -> input -> post('batch_no'));
		$expiry_date = array_values($this -> input -> post('expiry_date'));
		$commodity_unit_of_issue = array_values($this -> input -> post('commodity_unit_of_issue'));
		$quantity_issued = array_values($this -> input -> post('quantity_issued'));
		$clone_datepicker_normal_limit_today = array_values($this -> input -> post('clone_datepicker_normal_limit_today'));
		$manufacture = array_values($this -> input -> post('manufacture'));

		$total_units = array_values($this -> input -> post('total_units'));
		$total_items = count($facility_stock_id);
			//var_dump($total_units);exit;
		$data_array_issues_table = array();
		$data_array_redistribution_table = array();

		$new_transaction_entry = array();
		$digested_expiry = $expiry_date[0] ;
			for ($i = 0; $i < $total_items; $i++) ://compute the actual stock

			$total_items_issues = ($commodity_unit_of_issue[$i] == 'Pack_Size') ? $quantity_issued[$i] * $total_units[$i] : $quantity_issued[$i];

				//prepare the issues data
			$facility_name = isset($service_point[$i]) ? Facilities::get_facility_name2($service_point[$i]) : null;
			$facility_name = isset($facility_name) ? $facility_name['facility_name'] : 'N/A';
			$mydata = array('facility_code' => $facility_code, 's11_No' => '(-ve Adj) Stock Deduction', 'batch_no' => $batch_no[$i], 'commodity_id' => $commodity_id[$i], 'expiry_date' => date('y-m-d', strtotime($expiry_date[$i])), 'qty_issued' => $total_items_issues, 'issued_to' => "inter-facility donation:" . $facility_name, 'balance_as_of' => $commodity_balance_before[$i], 'date_issued' => date('y-m-d', strtotime($clone_datepicker_normal_limit_today[$i])), 'issued_by' => $this -> session -> userdata('user_id'));

			$mydata_2 = array('manufacturer' => $manufacture[$i],'source_district_id'=> $district_id, 'source_facility_code' => 2, 'batch_no' => $batch_no[$i], 'commodity_id' => $commodity_id[$i], 'expiry_date' => date('y-m-d', strtotime($expiry_date[$i])), 'quantity_sent' => $total_items_issues, 'receive_facility_code' => $service_point[$i], 'facility_stock_ref_id' => $facility_stock_id[$i], 'date_sent' => date('y-m-d'), 'sender_id' => $this -> session -> userdata('user_id'));
				// update the issues table
			array_push($data_array_issues_table, $mydata);
			array_push($data_array_redistribution_table, $mydata_2);
				// reduce the stock levels
				//var_dump($mydata);exit;

				// $a = Doctrine_Manager::getInstance() -> getCurrentConnection();
				// $a -> execute("UPDATE `facility_stocks` SET `current_balance` = `current_balance`+$total_items_issues where id='$facility_stock_id[$i]'");
				//update the transaction table here
			$inserttransaction = Doctrine_Manager::getInstance() -> getCurrentConnection();
			$inserttransaction -> execute("UPDATE `facility_transaction_table` SET `total_issues` = `total_issues`+$total_items_issues,
				`closing_stock`=`closing_stock`-$total_items_issues
				WHERE `commodity_id`= '$commodity_id[$i]' and status='1' and facility_code='$facility_code';");

			$existence = drug_store_issues::check_transaction_existence_county($commodity_id[$i],$service_point[$i]);
					// echo "<pre>"; print_r($existence);echo "</pre>";exit;
			if ($existence[0]['present'] >= 0) {
				$update_transactions = Doctrine_Manager::getInstance() -> getCurrentConnection();
				$update_transactions -> execute("UPDATE `county_drug_store_transaction_table` SET `total_issues` = `total_issues`+$total_items_issues,
					`closing_stock`=`closing_stock`-$total_items_issues
					WHERE `commodity_id`= '$commodity_id[$i]' and status='1' and facility_code='$service_point[$i]'"
					);
		            // echo $update_transactions;exit;
			}else{
				$new_entry_details = array(
					'facility_code'=>$service_point[$i],
					'commodity_id'=>$commodity_id[$i],
					'opening_balance'=>$total_items_issues,
					'total_issues'=> $total_items_issues,
					'closing_stock'=>$total_items_issues,
					'status'=> 1
					);

				array_push($new_transaction_entry, $new_entry_details);
				$data = $this->db->insert_batch('county_drug_store_transaction_table',$new_transaction_entry);
					// echo $data;exit;
				}//end of existence update/entry if

				$update_totals = Doctrine_Manager::getInstance() -> getCurrentConnection();
				$update_totals -> execute("UPDATE `county_drug_store_totals` SET `total_balance` = `total_balance`-$total_items_issues
					WHERE `commodity_id`= '$commodity_id[$i]' AND `county_id` ='$district_id'");
			//echo $update_totals;exit;

				endfor;

				$user = $this -> session -> userdata('user_id');
				$user_action = "redistribute";
			//updates the log table accordingly based on the action carried out by the user involved
				$update = Doctrine_Manager::getInstance()->getCurrentConnection();
				$update -> execute("update log set $user_action = 1  
					where `user_id`= $user 
					AND action = 'Logged In' 
					and UNIX_TIMESTAMP( `end_time_of_event`) = 0");

			// $this -> db -> insert_batch('facility_issues', $data_array_issues_table);
				$this -> db -> insert_batch('redistribution_data', $data_array_redistribution_table);
				$this -> session -> set_flashdata('system_success_message', "You have issued $total_items item(s)");
				redirect(home);
				endif;
		// redirect(home);		
	}//district store external issue
	public function district_store_external_issue()
		{//karsan
			// echo "<pre>";print_r($this -> input -> post());echo "</pre>";exit;
			//security check
			if ($this -> input -> post('mfl')) :
				$facility_code = $this -> session -> userdata('facility_id');
			$district_id = $this -> session -> userdata('district_id');
			$service_point = array_values($this -> input -> post('mfl'));
			$commodity_id = array_values($this -> input -> post('desc'));
			$commodity_balance_before = array_values($this -> input -> post('commodity_balance'));
			$facility_stock_id = array_values($this -> input -> post('facility_stock_id'));
			$batch_no = array_values($this -> input -> post('batch_no'));
			$expiry_date = array_values($this -> input -> post('expiry_date'));
			$commodity_unit_of_issue = array_values($this -> input -> post('commodity_unit_of_issue'));
			$quantity_issued = array_values($this -> input -> post('quantity_issued'));
			$clone_datepicker_normal_limit_today = array_values($this -> input -> post('clone_datepicker_normal_limit_today'));
			$manufacture = array_values($this -> input -> post('manufacture'));

			$total_units = array_values($this -> input -> post('total_units'));
			$total_items = count($facility_stock_id);
			//var_dump($total_units);exit;
			$data_array_issues_table = array();
			$data_array_redistribution_table = array();

			$new_transaction_entry = array();
			$digested_expiry = $expiry_date[0] ;
			for ($i = 0; $i < $total_items; $i++) ://compute the actual stock

			$total_items_issues = ($commodity_unit_of_issue[$i] == 'Pack_Size') ? $quantity_issued[$i] * $total_units[$i] : $quantity_issued[$i];

				//prepare the issues data
			$facility_name = isset($service_point[$i]) ? Facilities::get_facility_name2($service_point[$i]) : null;
			$facility_name = isset($facility_name) ? $facility_name['facility_name'] : 'N/A';
			$mydata = array('facility_code' => $facility_code, 's11_No' => '(-ve Adj) Stock Deduction', 'batch_no' => $batch_no[$i], 'commodity_id' => $commodity_id[$i], 'expiry_date' => date('y-m-d', strtotime($expiry_date[$i])), 'qty_issued' => $total_items_issues, 'issued_to' => "inter-facility donation:" . $facility_name, 'balance_as_of' => $commodity_balance_before[$i], 'date_issued' => date('y-m-d', strtotime($clone_datepicker_normal_limit_today[$i])), 'issued_by' => $this -> session -> userdata('user_id'));

			$mydata_2 = array('manufacturer' => $manufacture[$i],'source_district_id'=> $district_id, 'source_facility_code' => 2, 'batch_no' => $batch_no[$i], 'commodity_id' => $commodity_id[$i], 'expiry_date' => date('y-m-d', strtotime($expiry_date[$i])), 'quantity_sent' => $total_items_issues, 'receive_facility_code' => $service_point[$i], 'facility_stock_ref_id' => $facility_stock_id[$i], 'date_sent' => date('y-m-d'), 'sender_id' => $this -> session -> userdata('user_id'));
				// update the issues table
			array_push($data_array_issues_table, $mydata);
			array_push($data_array_redistribution_table, $mydata_2);
				// reduce the stock levels
				//var_dump($mydata);exit;

				// $a = Doctrine_Manager::getInstance() -> getCurrentConnection();
				// $a -> execute("UPDATE `facility_stocks` SET `current_balance` = `current_balance`+$total_items_issues where id='$facility_stock_id[$i]'");
				//update the transaction table here
			$inserttransaction = Doctrine_Manager::getInstance() -> getCurrentConnection();
			$inserttransaction -> execute("UPDATE `facility_transaction_table` SET `total_issues` = `total_issues`+$total_items_issues,
				`closing_stock`=`closing_stock`-$total_items_issues
				WHERE `commodity_id`= '$commodity_id[$i]' and status='1' and facility_code='$facility_code';");

			$existence = drug_store_issues::check_transaction_existence($commodity_id[$i],$service_point[$i]);
					// echo "<pre>"; print_r($existence);echo "</pre>";;exit;
			if ($existence[0]['present'] >= 0) {
				$update_transactions = Doctrine_Manager::getInstance() -> getCurrentConnection();
				$update_transactions -> execute("UPDATE `drug_store_transaction_table` SET `total_issues` = `total_issues`+$total_items_issues,
					`closing_stock`=`closing_stock`-$total_items_issues
					WHERE `commodity_id`= '$commodity_id[$i]' and status='1' and facility_code='$service_point[$i]'"
					);
		            // echo $update_transactions;exit;
			}else{
				$new_entry_details = array(
					'facility_code'=>$service_point[$i],
					'commodity_id'=>$commodity_id[$i],
					'opening_balance'=>$total_items_issues,
					'total_issues'=> $total_items_issues,
					'closing_stock'=>$total_items_issues,
					'status'=> 1
					);

				array_push($new_transaction_entry, $new_entry_details);
				$data = $this->db->insert_batch('drug_store_transaction_table',$new_transaction_entry);
					// echo $data;exit;
				}//end of existence update/entry if

				$update_totals = Doctrine_Manager::getInstance() -> getCurrentConnection();
				$update_totals -> execute("UPDATE `drug_store_totals` SET `total_balance` = `total_balance`-$total_items_issues
					WHERE `commodity_id`= '$commodity_id[$i]' AND `district_id` ='$district_id'");
			//echo $update_totals;exit;

				endfor;

				$user = $this -> session -> userdata('user_id');
				$user_action = "redistribute";
			//updates the log table accordingly based on the action carried out by the user involved
				$update = Doctrine_Manager::getInstance()->getCurrentConnection();
				$update -> execute("update log set $user_action = 1  
					where `user_id`= $user 
					AND action = 'Logged In' 
					and UNIX_TIMESTAMP( `end_time_of_event`) = 0");

			// $this -> db -> insert_batch('facility_issues', $data_array_issues_table);
				$this -> db -> insert_batch('redistribution_data', $data_array_redistribution_table);
				$this -> session -> set_flashdata('system_success_message', "You have issued $total_items item(s)");
				redirect(home);
				endif;
		// redirect(home);		
	}//district store external issue


	public function district_store_drug_recieval()
	{
			//security check
		if ($this -> input -> post('mfl')) :
			$facility_code = $this -> session -> userdata('facility_id');
		$district_id = $this -> session -> userdata('district_id');
		$service_point = array_values($this -> input -> post('mfl'));
		$commodity_id = array_values($this -> input -> post('desc'));
		$commodity_balance_before = array_values($this -> input -> post('commodity_balance'));
		$facility_stock_id = array_values($this -> input -> post('facility_stock_id'));
		$batch_no = array_values($this -> input -> post('batch_no'));
		$expiry_date = array_values($this -> input -> post('expiry_date'));
		$commodity_unit_of_issue = array_values($this -> input -> post('commodity_unit_of_issue'));
		$quantity_issued = array_values($this -> input -> post('quantity_issued'));
		$clone_datepicker_normal_limit_today = array_values($this -> input -> post('clone_datepicker_normal_limit_today'));
		$manufacture = array_values($this -> input -> post('manufacture'));

		$total_units = array_values($this -> input -> post('total_units'));
		$total_items = count($facility_stock_id);
			//var_dump($total_units);exit;
		$data_array_issues_table = array();
		$data_array_redistribution_table = array();

		$new_drug_store_entry = array();
		$digested_expiry = $expiry_date[0] ;
			for ($i = 0; $i < $total_items; $i++) ://compute the actual stock

			$total_items_issues = ($commodity_unit_of_issue[$i] == 'Pack_Size') ? $quantity_issued[$i] * $total_units[$i] : $quantity_issued[$i];

				//prepare the issues data
			$facility_name = isset($service_point[$i]) ? Facilities::get_facility_name2($service_point[$i]) : null;
			$facility_name = isset($facility_name) ? $facility_name['facility_name'] : 'N/A';
			$mydata = array('facility_code' => $facility_code, 's11_No' => '(-ve Adj) Stock Deduction', 'batch_no' => $batch_no[$i], 'commodity_id' => $commodity_id[$i], 'expiry_date' => date('y-m-d', strtotime($expiry_date[$i])), 'qty_issued' => $total_items_issues, 'issued_to' => "inter-facility donation:" . $facility_name, 'balance_as_of' => $commodity_balance_before[$i], 'date_issued' => date('y-m-d', strtotime($clone_datepicker_normal_limit_today[$i])), 'issued_by' => $this -> session -> userdata('user_id'));

			$mydata_2 = array('manufacturer' => $manufacture[$i],'source_district_id'=> $district_id, 'source_facility_code' => $facility_code, 'batch_no' => $batch_no[$i], 'commodity_id' => $commodity_id[$i], 'expiry_date' => date('y-m-d', strtotime($expiry_date[$i])), 'quantity_sent' => $total_items_issues, 'receive_facility_code' => $service_point[$i], 'facility_stock_ref_id' => $facility_stock_id[$i], 'date_sent' => date('y-m-d'), 'sender_id' => $this -> session -> userdata('user_id'));
				// update the issues table
			array_push($data_array_issues_table, $mydata);
			array_push($data_array_redistribution_table, $mydata_2);
				// reduce the stock levels
				//var_dump($mydata);exit;
			$a = Doctrine_Manager::getInstance() -> getCurrentConnection();
			$a -> execute("UPDATE `facility_stocks` SET `current_balance` = `current_balance`-$total_items_issues where id='$facility_stock_id[$i]'");
				//update the transaction table here
			$inserttransaction = Doctrine_Manager::getInstance() -> getCurrentConnection();
			$inserttransaction -> execute("UPDATE `facility_transaction_table` SET `total_issues` = `total_issues`+$total_items_issues,
				`closing_stock`=`closing_stock`-$total_items_issues
				WHERE `commodity_id`= '$commodity_id[$i]' and status='1' and facility_code='$facility_code';");

			$existence = drug_store_issues::check_drug_existence($commodity_id[$i],$district_id);
					// echo "<pre>"; print_r($existence);echo "</pre>";;exit;
			if ($existence[0]['present'] >= 0) {
				$update_totals = Doctrine_Manager::getInstance() -> getCurrentConnection();
				$update_totals -> execute("UPDATE `drug_store_totals` SET `total_balance` = `total_balance`+$total_items_issues
					WHERE `commodity_id`= '$commodity_id[$i]' and district_id='$district_id';");
		            //echo $update_totals;exit;
			}else{
				$new_entry_details = array(
					'district_id'=>$district_id,
					'commodity_id'=>$commodity_id[$i],
					'total_balance'=>$total_items_issues,
					'expiry_date'=>$digested_expiry
					);

				array_push($new_drug_store_entry, $new_entry_details);
				$data = $this->db->insert_batch('drug_store_totals',$new_drug_store_entry);
					//echo $data;exit;
				}//end of existence update/entry if
				endfor;

				$user = $this -> session -> userdata('user_id');
				$user_action = "redistribute";
			//updates the log table accordingly based on the action carried out by the user involved
				$update = Doctrine_Manager::getInstance()->getCurrentConnection();
				$update -> execute("update log set $user_action = 1  
					where `user_id`= $user 
					AND action = 'Logged In' 
					and UNIX_TIMESTAMP( `end_time_of_event`) = 0");

				$this -> db -> insert_batch('facility_issues', $data_array_issues_table);
				$this -> db -> insert_batch('redistribution_data', $data_array_redistribution_table);
				$this -> session -> set_flashdata('system_success_message', "You have issued $total_items item(s)");
				redirect(home);
				endif;
		// redirect(home);		
	}//district store external issue


	public function tester(){
		$district_id = $this -> session -> userdata('district_id');
		$sth = drug_store_issues::get_commodities_for_district($district_id);
		echo"<pre>"; print_r($sth); echo "</pre>";
	}

	public function confirm_store_external_issue($editable_ = NULL){
		//seth
		$district_id = $this -> session -> userdata('district_id');
		$data['title'] ="Confirm Redistribution";	
		$data['banner_text'] = "Confirm Redistribution";
		$data['redistribution_data']=redistribution_data::get_all_active_drug_store($district_id,$editable_);
		// echo "<pre>";print_r($data['redistribution_data']);echo "</pre>";exit;
		$data['editable']=$editable_;
		$data['content_view'] = "subcounty/drug_store/drug_store_redistribute_items_confirmation_v";
		$this -> load -> view("shared_files/template/template", $data);		
	}

	public function county_confirm_store_external_issue($editable_ = null){
		$county_id = $this -> session -> userdata('county_id');
		$data['title'] = "Confirm Redistribution";
		$data['banner_text'] = "Confirm Redistribution";
		$data['redistribution_data'] = redistribution_data::get_all_active_drug_store_county($county_id);
		$data['editable'] = $editable_;

		$data['content_view'] = "county/drug_store/drug_store_redistribute_items_confirmation_v";
		$this -> load -> view("shared_files/template/template", $data);
	}

	// facility internal issue
	public function internal_issue() {
		//security check
		// echo "<pre>";print_r($this->input->post());echo "</pre>";exit;
		if ($this -> input -> post('service_point')) :
		$facility_code = $this -> session -> userdata('facility_id');
		$service_points = array_values($this -> input -> post('service_point'));
		$commodity_id = array_values($this -> input -> post('desc'));
		$commodity_balance_before = array_values($this -> input -> post('commodity_balance'));
		$facility_stock_id = array_values($this -> input -> post('facility_stock_id'));
		$batch_no = array_values($this -> input -> post('batch_no'));
		$expiry_date = array_values($this -> input -> post('expiry_date'));
		$commodity_unit_of_issue = array_values($this -> input -> post('commodity_unit_of_issue'));
		$quantity_issued = array_values($this -> input -> post('quantity_issued'));
		$clone_datepicker_normal_limit_today = array_values($this -> input -> post('clone_datepicker_normal_limit_today'));
		$total_units = array_values($this -> input -> post('total_units'));
		$total_items = count($facility_stock_id);
			//print_r($total_units);
			for ($i = 0; $i < $total_items; $i++) ://compute the actual stock
			$total_items_issues = ($commodity_unit_of_issue[$i] == 'Pack_Size') ? $quantity_issued[$i] * $total_units[$i] : $quantity_issued[$i];
				//prepare the issues data

			$mydata = array('facility_code' => $facility_code, 's11_No' => 'internal issue', 'batch_no' => $batch_no[$i], 'commodity_id' => $commodity_id[$i], 'expiry_date' => date('Y-m-d', strtotime($expiry_date[$i])), 'qty_issued' => $total_items_issues, 'issued_to' => $service_points[$i], 'balance_as_of' => $commodity_balance_before[$i], 'date_issued' => date('y-m-d', strtotime($clone_datepicker_normal_limit_today[$i])), 'issued_by' => $this -> session -> userdata('user_id'));

				// update the issues table
			facility_issues::update_issues_table($mydata);
				// reduce the stock levels
			$a = Doctrine_Manager::getInstance() -> getCurrentConnection();
			$a -> execute("UPDATE `facility_stocks` SET `current_balance` = `current_balance`-$total_items_issues where id='$facility_stock_id[$i]'");
				//update the transaction table here

			$inserttransaction = Doctrine_Manager::getInstance() -> getCurrentConnection();
			$inserttransaction -> execute(" UPDATE `facility_transaction_table` SET `total_issues` = `total_issues`+$total_items_issues,
				`closing_stock`=`closing_stock`-$total_items_issues
				WHERE `commodity_id`= '$commodity_id[$i]' and status='1' and facility_code='$facility_code';");

			//insertion to service points stock table
			$sp_checker = facility_issues::get_service_point_stocks($facility_code,$service_points[$i],$commodity_id[$i]);

			// $current_balance = $commodity_balance_before[$i] - $total_items_issues;
			
			if (count($sp_checker)>0) {
				$update = Doctrine_Manager::getInstance()->getCurrentConnection()->execute(
					"UPDATE service_point_stocks SET current_balance = current_balance + $total_items_issues WHERE facility_code = $facility_code AND service_point_id = $service_points[$i]"
					);
			}else{
				$sp_stocks_array= array();
				$sp_stocks = array(
					'facility_code' => $facility_code,
					'batch_no' => $batch_no[$i],
					'commodity_id' => $commodity_id[$i],
					'expiry_date' => date('Y-m-d', strtotime($expiry_date[$i])),
					'service_point_id' => $service_points[$i],
					'current_balance' => $total_items_issues
					);


				array_push($sp_stocks_array, $sp_stocks);
				// echo "<pre>";print_r($sp_stocks);exit;
				$this->db->insert_batch('service_point_stocks',$sp_stocks_array);
			}
			
			endfor;
			// echo "Insertion done";exit;
			//updates the log tables with the action
			$user = $this -> session -> userdata('user_id');
			$user_action = "issued";
			//updates the log table accordingly based on the action carried out by the user involved
			$update = Doctrine_Manager::getInstance()->getCurrentConnection();
			$update -> execute("update log set $user_action = 1  
				where `user_id`= $user 
				AND action = 'Logged In' 
				and UNIX_TIMESTAMP( `end_time_of_event`) = 0");
			$this -> session -> set_flashdata('system_success_message', "You have issued $total_items item(s)");
			redirect(home);
			endif;
			redirect(home);
		}

		public function external_issue() {
		//security check
			if ($this -> input -> post('mfl')) :
				$facility_code = $this -> session -> userdata('facility_id');
			$district_id = $this -> session -> userdata('district_id');
			$service_point = array_values($this -> input -> post('mfl'));
			$commodity_id = array_values($this -> input -> post('desc'));
			$commodity_balance_before = array_values($this -> input -> post('commodity_balance'));
			$facility_stock_id = array_values($this -> input -> post('facility_stock_id'));
			$batch_no = array_values($this -> input -> post('batch_no'));
			$expiry_date = array_values($this -> input -> post('expiry_date'));
			$commodity_unit_of_issue = array_values($this -> input -> post('commodity_unit_of_issue'));
			$quantity_issued = array_values($this -> input -> post('quantity_issued'));
			$clone_datepicker_normal_limit_today = array_values($this -> input -> post('clone_datepicker_normal_limit_today'));
			$manufacture = array_values($this -> input -> post('manufacture'));

			$total_units = array_values($this -> input -> post('total_units'));
			$total_items = count($facility_stock_id);
			//var_dump($total_units);exit;
			$data_array_issues_table = array();
			$data_array_redistribution_table = array();
			
			//loops through the data collected from the forms first
			for ($i = 0; $i < $total_items; $i++) :
				//compute the actual stock

				$total_items_issues = ($commodity_unit_of_issue[$i] == 'Pack_Size') ? $quantity_issued[$i] * $total_units[$i] : $quantity_issued[$i];

				//prepare the issues data
			$facility_name = isset($service_point[$i]) ? Facilities::get_facility_name2($service_point[$i]) : null;
			$facility_name = isset($facility_name) ? $facility_name['facility_name'] : 'N/A';
			$mydata = array('facility_code' => $facility_code, 's11_No' => '(-ve Adj) Stock Deduction', 'batch_no' => $batch_no[$i], 'commodity_id' => $commodity_id[$i], 'expiry_date' => date('y-m-d', strtotime($expiry_date[$i])), 'qty_issued' => $total_items_issues, 'issued_to' => "inter-facility donation:" . $facility_name, 'balance_as_of' => $commodity_balance_before[$i], 'date_issued' => date('y-m-d', strtotime($clone_datepicker_normal_limit_today[$i])), 'issued_by' => $this -> session -> userdata('user_id'));

			$mydata_2 = array('manufacturer' => $manufacture[$i],'source_district_id'=> $district_id, 'source_facility_code' => $facility_code, 'batch_no' => $batch_no[$i], 'commodity_id' => $commodity_id[$i], 'expiry_date' => date('y-m-d', strtotime($expiry_date[$i])), 'quantity_sent' => $total_items_issues, 'receive_facility_code' => $service_point[$i], 'facility_stock_ref_id' => $facility_stock_id[$i], 'date_sent' => date('y-m-d'), 'sender_id' => $this -> session -> userdata('user_id'));
				// update the issues table
			array_push($data_array_issues_table, $mydata);
			array_push($data_array_redistribution_table, $mydata_2);
				// reduce the stock levels
				//var_dump($mydata);exit;
			$a = Doctrine_Manager::getInstance() -> getCurrentConnection();
			$a -> execute("UPDATE `facility_stocks` SET `current_balance` = `current_balance`-$total_items_issues where id='$facility_stock_id[$i]'");
				//update the transaction table here
			$inserttransaction = Doctrine_Manager::getInstance() -> getCurrentConnection();
			$inserttransaction -> execute("UPDATE `facility_transaction_table` SET `total_issues` = `total_issues`+$total_items_issues,
				`closing_stock`=`closing_stock`-$total_items_issues
				WHERE `commodity_id`= '$commodity_id[$i]' and status='1' and facility_code='$facility_code';");
			endfor;
			
			$user = $this -> session -> userdata('user_id');
			$user_action = "redistribute";
			//updates the log table accordingly based on the action carried out by the user involved
			$update = Doctrine_Manager::getInstance()->getCurrentConnection();
			$update -> execute("update log set $user_action = 1  
				where `user_id`= $user 
				AND action = 'Logged In' 
				and UNIX_TIMESTAMP( `end_time_of_event`) = 0");
			
			$send_sms = $this->hcmp_functions ->send_system_text($user_action);
			
			
			
			$this -> db -> insert_batch('facility_issues', $data_array_issues_table);
			$this -> db -> insert_batch('redistribution_data', $data_array_redistribution_table);
			$this -> session -> set_flashdata('system_success_message', "You have issued $total_items item(s)");
			// redirect(home);
			endif;
			redirect(home);
	}//confirm the external issue
	public function external_issue_offline() {
		//security check
			if ($this -> input -> post('mfl')) :
				$facility_code = $this -> session -> userdata('facility_id');
			$district_id = $this -> session -> userdata('district_id');
			$service_point = array_values($this -> input -> post('mfl'));
			$commodity_id = array_values($this -> input -> post('desc'));
			$commodity_balance_before = array_values($this -> input -> post('commodity_balance'));
			$facility_stock_id = array_values($this -> input -> post('facility_stock_id'));
			$batch_no = array_values($this -> input -> post('batch_no'));
			$expiry_date = array_values($this -> input -> post('expiry_date'));
			$commodity_unit_of_issue = array_values($this -> input -> post('commodity_unit_of_issue'));
			$quantity_issued = array_values($this -> input -> post('quantity_issued'));
			$clone_datepicker_normal_limit_today = array_values($this -> input -> post('clone_datepicker_normal_limit_today'));
			$manufacture = array_values($this -> input -> post('manufacture'));

			$total_units = array_values($this -> input -> post('total_units'));
			$total_items = count($facility_stock_id);
			//var_dump($total_units);exit;
			$data_array_issues_table = array();
			$data_array_redistribution_table = array();
			
			//loops through the data collected from the forms first
			for ($i = 0; $i < $total_items; $i++) :
				//compute the actual stock

				$total_items_issues = ($commodity_unit_of_issue[$i] == 'Pack_Size') ? $quantity_issued[$i] * $total_units[$i] : $quantity_issued[$i];

				//prepare the issues data
			$facility_name = isset($service_point[$i]) ? Facilities::get_facility_name2($service_point[$i]) : null;
			$facility_name = isset($facility_name) ? $facility_name['facility_name'] : 'N/A';
			$mydata = array('facility_code' => $facility_code, 's11_No' => '(-ve Adj) Stock Deduction', 'batch_no' => $batch_no[$i], 'commodity_id' => $commodity_id[$i], 'expiry_date' => date('y-m-d', strtotime($expiry_date[$i])), 'qty_issued' => $total_items_issues, 'issued_to' => "inter-facility donation:" . $facility_name, 'balance_as_of' => $commodity_balance_before[$i], 'date_issued' => date('y-m-d', strtotime($clone_datepicker_normal_limit_today[$i])), 'issued_by' => $this -> session -> userdata('user_id'));

			$mydata_2 = array('manufacturer' => $manufacture[$i],'source_district_id'=> $district_id, 'source_facility_code' => $facility_code, 'batch_no' => $batch_no[$i], 'commodity_id' => $commodity_id[$i], 'expiry_date' => date('y-m-d', strtotime($expiry_date[$i])), 'quantity_sent' => $total_items_issues, 'receive_facility_code' => $service_point[$i], 'facility_stock_ref_id' => $facility_stock_id[$i], 'date_sent' => date('y-m-d'), 'sender_id' => $this -> session -> userdata('user_id'),'status'=>1);
				// update the issues table
			array_push($data_array_issues_table, $mydata);
			array_push($data_array_redistribution_table, $mydata_2);
				// reduce the stock levels
				//var_dump($mydata);exit;
			$a = Doctrine_Manager::getInstance() -> getCurrentConnection();
			$a -> execute("UPDATE `facility_stocks` SET `current_balance` = `current_balance`-$total_items_issues where id='$facility_stock_id[$i]'");
				//update the transaction table here
			$inserttransaction = Doctrine_Manager::getInstance() -> getCurrentConnection();
			$inserttransaction -> execute("UPDATE `facility_transaction_table` SET `total_issues` = `total_issues`+$total_items_issues,
				`closing_stock`=`closing_stock`-$total_items_issues
				WHERE `commodity_id`= '$commodity_id[$i]' and status='1' and facility_code='$facility_code';");
			endfor;
			
			$user = $this -> session -> userdata('user_id');
			$user_action = "redistribute";
			//updates the log table accordingly based on the action carried out by the user involved
			$update = Doctrine_Manager::getInstance()->getCurrentConnection();
			$update -> execute("update log set $user_action = 1  
				where `user_id`= $user 
				AND action = 'Logged In' 
				and UNIX_TIMESTAMP( `end_time_of_event`) = 0");
			
			$send_sms = $this->hcmp_functions ->send_system_text($user_action);
			
			
			
			$this -> db -> insert_batch('facility_issues', $data_array_issues_table);
			$this -> db -> insert_batch('redistribution_data', $data_array_redistribution_table);
			$this -> session -> set_flashdata('system_success_message', "You have issued $total_items item(s)");
			// redirect(home);
			endif;
			redirect(home);
	}//confirm the external issue

	public function confirm_external_issue($editable = null) {
		$facility_code = $this -> session -> userdata('facility_id');		
		$data['title'] = ($editable!='') ? 'Receive Redistribution' :'Confirm Redistribution' ;
		// $data['title'] = "Confirm Redistribution";
		$data['banner_text']= ($editable!='') ? 'Receive Redistribution' :'Confirm Redistribution' ;
		// $data['banner_text'] = "Confirm Redistribution";
		$data['redistribution_data'] = redistribution_data::get_all_active($facility_code, $editable);
		$data['editable'] = $editable;
		$data['content_view'] = "facility/facility_issues/facility_redistribute_items_confirmation_v";
		$this -> load -> view("shared_files/template/template", $data);
	}


	public function confirm_external_issue_offline($type = null) {
		$facility_code = $this -> session -> userdata('facility_id');		
		$data['title'] = 'Receive Redistribution' ;		
		$data['banner_text']= ($editable=='') ? 'Receive Redistribution' :'Confirm Redistribution' ;		
		$data['redistribution_data'] = redistribution_data::get_all_received($facility_code);

		$data['commodities'] = commodities::get_all();
		
		if ($type==null) {
			$data['content_view'] = "facility/facility_issues/facility_redistribute_items_confirmation_v";
		}else{
			$data['content_view'] = "facility/facility_issues/facility_redistribute_items_confirmation_v_online";
		}		
		$this -> load -> view("shared_files/template/template", $data);
	}

	public function upload_redistribution_excel(){
		// echo "<pre>";print_r($this->input->post());echo "</pre>";exit;
		// error_reporting(E_ALL);
		$config['upload_path'] = './print_docs/excel/uploaded_files/';
		$config['allowed_types'] = 'xls|xlsx';
		$config['max_size']	= '2048';
		$name = 'redistribution'.date('d-m-Y').'_';
		$config['file_name'] = $name;
		$this->upload->initialize($config);
		if ( ! $this->upload->do_upload("redistribution_excel"))
		{
			echo "<pre>";print_r($this->upload->display_errors());echo "</pre>";			
		}
		else
		{
			$result = $this->upload->data();
			$file_name = $result['file_name'];
			$this->upload_redistribution($file_name);
			// echo "I worked";
			$this->session->set_flashdata('message', 'The File upload was successful');
			// redirect('admin/report_listing');
		}
	}//end of upload excel
	public function upload_redistribution($file_name){
		$inputFileName = 'print_docs/excel/uploaded_files/'.$file_name;
		$objReader = new PHPExcel_Reader_Excel2007();
		$objReader->setReadDataOnly(true);
		$objPHPExcel = $objReader->load($inputFileName);
		$sheet = $objPHPExcel->getSheet(0); 
		$highestRow = $sheet->getHighestRow()+1; 
		$highestColumn = $sheet->getHighestColumn();

		$sending_facility = $sheet->getCell('C8')->getValue();
		$facility_code = $this -> session -> userdata('facility_id');
		$current_date = date($format = "Y-m-d h:i:s", PHPExcel_Shared_Date::ExcelToPHP($sheet->getCell('C9')->getValue()));
		// echo "$current_date";die;
		$today = date('Y-m-d h:i:s');
		$rowData = array();
		for ($row = 12; $row < $highestRow; $row++){ 
		    //  Read a row of data into an array
		    $rowData_ = $sheet->rangeToArray('B' . $row . ':G' . $row);		
		    array_push($rowData, $rowData_[0]);		    
		}		

		foreach ($rowData as $r_data) {

			$commodity_name = $r_data[0];
			$batch_no = $r_data[1];
			$manufacturer = $r_data[2];
			$expiry_date = date($format = "Y-m-d h:i:s", PHPExcel_Shared_Date::ExcelToPHP($r_data[3]));
			// $expiry_date =  date($format = "Y-m-d h:i:s", PHPExcel_Shared_Date::ExcelToPHP($expiry_date)); 
			
			$quantity_packs = $r_data[4];
			$quantity_units = $r_data[5];			
			//Get commodity details from the name
			$commodity_details = commodities::get_details_name($commodity_name);
			foreach ($commodity_details as $key => $value) {
				$commodity_id = $value['id'];
				$commodity_code = $value['commodity_code'];
				$unit_size = $value['pack_size'];
				$commodity_source_id = $value['commodity_source_id'];

				//Convert the Packs to Units
				$received_quantity = null;
				if($quantity_units!=''||$quantity_units!=null){
					$received_quantity = $quantity_units;
				}else{
					$unit_size = ($unit_size==0) ? 1 : $unit_size ;
					$received_quantity = $quantity_packs * $unit_size;
				}

				//Save the Data in a temporary table 
				$received_data = array(array('facility_code' =>$facility_code,
													'sending_facility'=>$sending_facility,
													'commodity_id'=>$commodity_id,
													'batch_no'=>$batch_no,
													'manufacturer'=>$manufacturer,													
													'quantity_received'=>$received_quantity,
													'expiry_date'=>$expiry_date,
													'date_received'=>$current_date,													
													'status'=>1));
				//Save to receive redistributions table
				$this -> db -> insert_batch('receive_redistributions', $received_data);
				
			}
		}

		$this -> session -> set_flashdata('system_success_message', "File Upload Successful");
		redirect('issues/confirm_external_issue_offline');
			

	}//end of recepient upload
	public function confirm_offline_issue(){
		
		
		$today = date('Y-m-d h:i:s');
		$count =count($this->input->post('commodity_id'));	
		$commodity_name = $this->input->post('commodity_name');	
		$commodity_id = $this->input->post('commodity_id');	
		$expiry_date = $this->input->post('expiry_date');	
		$manufacturer = $this->input->post('manufacturer');	
		$batch_no = $this->input->post('batch_no');	
		$total_commodity_units = $this->input->post('total_commodity_units');
		$facility_code = $this -> session -> userdata('facility_id');

		for ($i=0; $i <$count ; $i++) { 			
			$commodity_details = commodities::get_details_name($commodity_name[$i]);
			foreach ($commodity_details as $key => $value) {
				$new_commodity_id = $value['id'];
				$commodity_code = $value['commodity_code'];
				$unit_size = $value['pack_size'];
				$commodity_source_id = $value['commodity_source_id'];

				//Convert the Packs to Units
				$received_quantity = $total_commodity_units[$i];
				

				//Check if batch exists in the facility stocks table
				$batch_details = facility_stocks::get_batch_details($batch_no[$i],$facility_code);

				//If the batch does not exits
				if(count($batch_details)<=0){
					$facility_stocks_data = array(array('facility_code' =>$facility_code,
													'commodity_id'=>$new_commodity_id,
													'batch_no'=>$batch_no[$i],
													'manufacture'=>$manufacturer[$i],
													'initial_quantity'=>$received_quantity,
													'current_balance'=>$received_quantity,
													'date_added'=>$today,
													'date_modified'=>$today,
													'source_of_commodity'=>$commodity_source_id,
													'status'=>1,
													'expiry_date'=>$expiry_date[$i]
													 ));
					// echo "<pre>";print_r($facility_stocks_data);die;
					$this -> db -> insert_batch('facility_stocks', $facility_stocks_data);
				}else{
					// echo "<pre>";print_r($batch_details);die;
					$current_balance = intval($batch_details[0]['current_balance']);
					$quantity_units = $total_commodity_units[$i];
					$current_balance = $current_balance + intval($quantity_units);
					$facility_stocks_data_update = array('batch_no'=>$batch_no[$i],								
													'current_balance'=>$current_balance,													
													'date_modified'=>$today,																
													'expiry_date'=>$expiry_date[$i]
													 );
					// echo "<pre>";print_r($facility_stocks_data_update);die;
					$this->db->where('batch_no', $batch_no[$i]);
					$this->db->where('facility_code', $facility_code);
					$this->db->update('facility_stocks', $facility_stocks_data_update); 
					//Above code updates the facility Stocks table if the batch exists
				}

			}
			$receive_redistribution_data_update = array('batch_no'=>$batch_no[$i],								
													'status'=>0
													 );
			$this->db->where('batch_no', $batch_no[$i]);
			$this->db->where('status',1);
			$this->db->where('facility_code', $facility_code);
			$this->db->update('receive_redistributions', $receive_redistribution_data_update); 
		}

		$this -> session -> set_flashdata('system_success_message', "Redistribution Data Has Been Updated");
		redirect('home');
			

	}//end of recepient upload

	public function delete_redistribution($id){
		$redistribution_data = redistribution_data::get_one($id);
		foreach ($redistribution_data as $key => $value) {
			$quantity_sent = intval($value['quantity_sent']);
			$stock_id = $value['stock_id'];
			$stock_data = facility_stocks::get_facilty_stock_id($stock_id);			
			foreach ($stock_data as $keys => $values) {
				$current_balance = intval($values['current_balance']);
				$new_balance = $current_balance+$quantity_sent;
				$update_array = array('id'=>$stock_id,'current_balance'=>$new_balance);
				$inserttransaction = Doctrine_Manager::getInstance() -> getCurrentConnection();
				$inserttransaction -> execute("UPDATE `facility_stocks` SET `current_balance` = '$new_balance' WHERE id= '$stock_id'");
				$inserttransaction -> execute("UPDATE `redistribution_data` SET `status` = '5' WHERE id= '$id'");
				$this -> session -> set_flashdata('system_success_message', "The Issue has been Deleted");
				redirect('issues/confirm_external_issue_edit');
			}

		}
	}

	public function confirm_external_issue_edit($editable = null) {
		$facility_code = $this -> session -> userdata('facility_id');
		$subcounty_id = $this -> session -> userdata('district_id');		
		$data['title'] = "Edit Redistribution";
		$data['banner_text'] = "Edit Redistribution";
		$data['redistribution_data'] = redistribution_data::get_all_active_edit($facility_code, $editable);
		// $data['redistribution_data'] = redistribution_data::get_all_active($facility_code, $editable);
		$districts_data = districts::get_district_name($subcounty_id);
		$district_name = '';
		foreach ($districts_data as $value) {
			$district_name = $value->district;
		}
		$data['district_name']=$district_name;		
		$data['district_id']=$subcounty_id;		
		$data['editable'] = $editable;
		$data['subcounties'] = districts::getAll();
		$data['content_view'] = "facility/facility_issues/facility_redistribute_items_confirmation_edit_v";
		$this -> load -> view("shared_files/template/template", $data);
	}

	public function add_service_points() {
		$facility_code = $this -> session -> userdata('facility_id');
		$data['title'] = "Facility Service Points";
		$data['banner_text'] = "Facility Service Points";
		$data['service_point'] = service_points::get_all_active($facility_code, 'all');
		$data['content_view'] = "facility/facility_issues/add_service_points_v";
		$this -> load -> view("shared_files/template/template", $data);
	}// save service points

	public function save_service_points() {
		//security check
		if ($this -> input -> post('service_point')) :
			$service_point_name = $this -> input -> post('service_point');
		$service_for_all_facilities = 0;
		$date_of_entry = date('y-m-d H:i:s');
			// check if the user is super admin so that to set the item to all people
		if ($this -> session -> userdata('user_indicator') == 'super_admin') {$service_for_all_facilities = 1;
		}
		foreach ($service_point_name as $service_point_name) :
			$myarray = array('facility_code' => $this -> session -> userdata('facility_id'), 'service_point_name' => $service_point_name, 'for_all_facilities' => $service_for_all_facilities, 'date_added' => $date_of_entry, 'added_by' => $this -> session -> userdata('user_id'));
				//update the service points
		service_points::update_service_points($myarray);
		endforeach;
		$this -> session -> set_flashdata('system_success_message', "service points Have Been Updated");
		redirect('issues/add_service_points');
		endif;
		redirect(home);
	}

	//update the service point incase smthin changes
	public function update_service_point() {
		//security check
		if ($this -> input -> post('id')) :
			$service_point_id = $this -> input -> post('id');
		$service_point_name = $this -> input -> post('name');
		$service_status = $this -> input -> post('status');
		$service_for_all_facilities = $this -> input -> post('all_facilities');
		service_points::edit_service_point($service_point_id, $service_point_name, $service_for_all_facilities, $this -> session -> userdata('user_id'), $service_status);
		$this -> session -> set_flashdata('system_success_message', "service points Have Been Updated");
		redirect('issues/add_service_points');
		endif;
		redirect(home);
	}

	//The function below was made to solve the problem where instead of the issue point ID's being stored,their descriptions were being stored
	//A string as a relationship definer in database tables is senseless and prone to errors
	//
	public function db_service_pts(){//Karsan
		// $all_issues = facility_issues::get_all();
		$alpha_data = facility_issues::get_all_issue_data();
		$beta_data = facility_issues::get_all_service_points();

		// echo "<pre>";print_r($beta_data);exit;
		$alphacount = count($alpha_data);
		$betacount = count($beta_data);
		$omega_data = array();
		$epsilon_data = array();//for generic service points
		for ($a=0; $a < $alphacount; $a++) { 
			for ($b=0; $b < $betacount; $b++) { 
				if (($alpha_data[$a]['issued_to'] == $beta_data[$b]['service_point_name']) && ($alpha_data[$a]['facility_code'] == $beta_data[$b]['facility_code'])) {
					$omega_data[$a]['issue_id'] = $alpha_data[$a]['id'];
					$omega_data[$a]['sp_id'] = $beta_data[$b]['id'];
				}
			}
			for ($b=0; $b < $betacount; $b++) { 
				if ($alpha_data[$a]['issued_to'] == $beta_data[$b]['service_point_name'] && ($alpha_data[$a]['facility_code'] != $beta_data[$b]['facility_code'])) {
					$epsilon_data[$a]['issue_id'] = $alpha_data[$a]['id'];
					$epsilon_data[$a]['sp_id'] = $beta_data[$b]['id'];
					break;
					echo "<pre>";echo $beta_data[$b]['id'].'  '.$b;echo "</pre>";
				}
			}
		}

		

		// echo "<pre>";print_r($omega_data);exit;
		// echo "<pre>";print_r($epsilon_data);exit;
		foreach ($omega_data as $omegakey => $omegavalue) {
			$issue_id = $omegavalue['issue_id'];
			$service_point_id = $omegavalue['sp_id'];

			$updater = Doctrine_Manager::getInstance()->getCurrentConnection()->execute("
				UPDATE facility_issues SET issued_to = $service_point_id WHERE id = $issue_id
				");
		}//end of foreach
		$omega_affected = $this->db->affected_rows();
		echo "OMEGA AFFECTED: ".$omega_affected;
		echo "<br>THE -OMEGA- UPDATE WAS SUCCESSFUL. </br>-EPSILON DATA- UPDATE COMMENCING</br>";

		foreach ($epsilon_data as $epsilonkey => $epsilonvalue) {
			$issue_id = $epsilonvalue['issue_id'];
			$service_point_id = $epsilonvalue['sp_id'];

			$updater = Doctrine_Manager::getInstance()->getCurrentConnection()->execute("UPDATE facility_issues SET issued_to = $service_point_id WHERE id = $issue_id");
		}//end of foreach
		$epsilon_affected = $this->db->affected_rows();
		echo "EPSILON AFFECTED: ".$epsilon_affected;

		echo "<br>THE -EPSILON- UPDATE WAS SUCCESSFUL </br>";
		echo "THE UPDATE WAS SUCCESSFUL. GOD SPEED. </br>";

	}

}
