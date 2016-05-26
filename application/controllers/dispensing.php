<?php
/*
 * @author Karsan
 */
if (!defined('BASEPATH'))
	exit('No direct script access allowed');
class Dispensing extends MY_Controller {
	function __construct() 
	{
		parent::__construct();
		$this -> load -> helper(array('form', 'url'));
		$this -> load -> library(array('hcmp_functions', 'form_validation'));
	}

	public function index(){
		$graph_data = $this->get_service_point_graph_data();
		// echo "<pre>";print_r($graph_data);exit;
		$data['service_point_dashboard_notifications'] = $graph_data;
		$facility_code = $this -> session -> userdata('facility_id');
		$service_point_stock = facility_stocks::get_service_point_stocks($facility_code,$service_point);
		
		$data['banner_text'] = "Pharmacy";
		// $data['sidebar'] = "facility/facility_dispensing/sidebar_dispensing";
		// $data['report_view'] = "facility/facility_dispensing/dispensing_home_v";
		// $commodities = Commodities::get_all();
		$data['content_view'] = "facility/facility_dispensing/service_point_home";
		$view = 'shared_files/template/template';
		$data['active_panel'] = 'dispensing';
		$data['title'] = "Dispensing";
		$data['service_point_stock'] = $service_point_stock;

		// $patients = patients::get_all();
		// $commodities_in_facility = facility_stocks::get_facility_stock_amc($facility_code);
		// $data['commodities'] = 	$commodities_in_facility;
		// echo "<pre>";print_r($commodities_in_facility);exit;

		// $data['patients'] = $patients;
		$this -> load -> view($view, $data);		
	}
	public function get_patient_data(){
		$id = $this->input->post('id');
		// all variable names inspired by Game of Thrones
		// Season 2 Episode 4

		$imp = Patients::get_patient_details($id);
		$winterfell = Patients::get_patient_commodity_info($id);

		// echo "<pre>";print_r($winterfell);echo "</pre>";
		$table = NULL;
		$table .= '<h4>Patient Details</h4>

		<p>Name:'.$imp[0]['patient_names'].'</p> <p>Age: '.$imp[0]['age'].'</p>
		<h4>Recent History</h4>
		<table class="table table-bordered">
			<thead>
				<th>Commodity Code</th>
				<th>Commodity Description</th>
				<th>Issue type</th>
				<th>Amount</th>
				<th>Date of Issue</th>
			</thead>

			<tbody>';
				foreach ($winterfell as $key) {
					$table .='<tr>
					<td>'.$key['commodity_code'].'</td>
					<td>'.$key['commodity_name'].'</td>
					<td>'.$key['issue_type_desc'].'</td>
					<td>'.$key['amount'].'</td>
					<td>'.date('Y m d',$key['date_issued']).'</td>
				</tr>';
			}

			$table.='
				</tbody>
				</table>';
		echo $table;
	}

	public function patients(){
		$p_data = Patients::get_all();
		// $p_data = Patients::get_patient_data();		
		$data['patient_data'] = $p_data;
		$facility_code = $this -> session -> userdata('facility_id');
		$data['title'] = "Patient Management";
		$data['banner_text'] = "Patient Management";
		$data['sidebar'] = "facility/facility_dispensing/sidebar_dispensing";
		$view = 'shared_files/template/template';
		$data['content_view'] = 'facility/facility_dispensing/patients_create_v';
		$this->load->view($view,$data);
	}


	public function setup_commodities(){
		$facility_code = $this -> session -> userdata('facility_id');
		$data['title'] = "Dispensing - Set up Service Point stock";
		$data['content_view'] = "facility/facility_dispensing/setup_facility_stock_v";
		$data['banner_text'] = "Set up service point stock";
		$data['commodities'] = commodities::set_facility_service_data($facility_code,2);
		$this -> load -> view("shared_files/template/template", $data);
	}

	public function setup_physical_count(){
		$facility_code = $this -> session -> userdata('facility_id');
		$data['title'] = "Dispensing - Set up Physical Stock Count";
		$data['content_view'] = "facility/facility_dispensing/setup_physical_count_v";
		$data['banner_text'] = "Set Up Physical Stock Count";
		$data['commodities'] = facility_stocks::get_service_point_stocks($facility_code,2,NULL,NULL);
		// var_dump($data);

		// echo "<pre>";
		// print_r($data);
		// echo "</pre>";
		// exit;
		$this -> load -> view("shared_files/template/template", $data);
	}

	public function update_physical_count(){
		// echo "<pre>"; print_r($_POST); echo "</pre>";
		$count = count($this->input->post('commodity_id'));
		$c_count = null;
		$counts = array_values($this->input->post('physical_count'));
		// echo "<pre>"; print_r($counts); echo "</pre>";
		// echo "Count: " . count($counts) . "<br />"; 
		// echo "Count Not Empty: " . count(!empty($counts)) . "<br />";
		// for($i = 1; $i < count($counts); $i++) {
		// 	$ac_count = count(!empty($counts[$i]));
		// }
		// echo "Actual Count: " . $ac_count;
		// exit;
		// for($i = 0; $i < $count; $i++){
		// $c_count = count($this->input->post('physical_count') !== null);
		// echo "Count: " . $c_count;
		// }
		// exit;
		$facility_code =  $this -> session -> userdata('facility_id');
		$service_point_id = $this -> input -> post('service_point_id');
		// $commodity_id = $this->input->post('commodity_id');
		$commodity_id = $this->input->post('commodity_id');
		$physical_count =  $this->input->post('physical_count');
		$type_of_adjustment = $this->input->post('type_of_adjustment'); // Positive or Negative 
		$count_difference = $this->input->post('count_difference');
		$reason = $this->input->post('reason');

		for ($i=0; $i < $count; $i++) { 
			// $physical_count = isset($this->input->post('physical_count')[$i])? $this->input->post('physical_count')[$i] : NULL;
			// $reason = isset($this->input->post('reason')[$i])? $this->input->post('reason')[$i] : NULL;
			// echo $id .": " .$physical_count ." ".$reason ."<br>";
			$insert = Doctrine_Manager::getInstance() -> getCurrentConnection();
			$insert -> execute("INSERT INTO service_point_stock_physical(facility_id, service_point_id, commodity_id, batch_no, type_of_adjustment, 
				count_difference, reason) VALUES('$facility_code', '$service_point_id[$i]','$commodity_id[$i]','$batch_no[$i]','$type_of_adjustment[$i]','$count_difference[$i]','$reason[$i]')");
			// $stock = facility_stocks::update_service_point_physical_count($id, $physical_count, $reason);
		}
		$this->session->set_flashdata('system_success_message', 'Service Point Physical Count updated');
		redirect('dispensing');

	}

	public function issue(){
		$facility_code = $this -> session -> userdata('facility_id');
		$service_point = 2;//hard coded for pharmacy,until requested for :]

		$selected_service_point = Selected_service_points::get_dispensing_point($facility_code);
		foreach ($selected_service_point as $sp) {
			$service_point = $sp->service_point_id;
		}
		$p_data = Patients::get_all();
		$service_point_stock = facility_stocks::get_service_point_stocks($facility_code,$service_point);		
		$commodity_details = array();		
		foreach ($service_point_stock as $key => $value) {
			$commodity_id = $value['commodity_id'];
			$current_balance = $value['current_balance'];
			$commodity_name = $value['commodity_name'];
			$price = $value['price'];
			$commodity_details[] = array('commodity_id'=>$commodity_id,'commodity_name'=>$commodity_name,'current_balance'=>$current_balance,'price'=>$price);
		}
		// echo "<pre>";
		// print_r($commodity_details);die;
		// echo "<pre>";print_r($service_point_stock);exit;
		$data['sp_commodities'] = $service_point_stock;
		$data['raw_sp_commodities'] = json_encode($commodity_details);
		// $p_data = Patients::get_patient_data();		
		$data['patient_data'] = $p_data;
		$facility_code = $this -> session -> userdata('facility_id');
		$data['title'] = "Patient Dispensing";
		$data['banner_text'] = "Patient Dispensing";		
		$view = 'shared_files/template/template';
		$data['content_view'] = 'facility/facility_dispensing/dispense';
		// $data['content_view'] = 'facility/facility_dispensing/dispensing_commodities_prt';
		$this->load->view($view,$data);
	}

	public function add_multiple_patients(){		
		$facility_code = $this -> session -> userdata('facility_id');
		$data['title'] = "Patient Management";
		$data['banner_text'] = "Add Multiple Patients";
		$data['sidebar'] = "facility/facility_dispensing/sidebar_dispensing";
		$view = 'shared_files/template/template';
		$data['content_view'] = 'facility/facility_dispensing/patients_multiple_create_v';
		$this->load->view($view,$data);
	}

		
	public function save_patient_multiple(){
		$facility_code = $this -> session -> userdata('facility_id');
		$count = count($this->input->post('first_name'));		
		for ($i=0; $i < $count; $i++) { 
			$patient_number = $this->input->post('patient_number')[$i];
			$dob = date('y-m-d', strtotime($this->input->post('clone_datepicker_normal_limit_today')[$i]));
			$date_created = date('Y-m-d',strtotime('NOW'));
			$data_array = array('firstname' =>$this->input->post('first_name')[$i] ,
								'lastname' =>$this->input->post('last_name')[$i] ,
								'date_of_birth' =>$dob ,
								'gender' =>$this->input->post('gender')[$i] ,
								'telephone' =>$this->input->post('telephone')[$i] ,
								'email' =>$this->input->post('email')[$i] ,
								'home_address' =>$this->input->post('physical_address')[$i] ,
								'work_address' =>$this->input->post('work_address')[$i] ,
								'patient_number' =>$this->input->post('patient_number')[$i],
								'facility_code' =>$facility_code ,
								'date_created' =>$date_created ,
								'status' =>1);		

			$patients = Patients::save_patient($data_array,$patient_number,$date_created,$facility_code);			
		}
		$this->session->set_flashdata('system_success_message', 'Patients added successfully');
		redirect('dispensing/patients');
		
	}
	public function save_patient(){
		$facility_code = $this -> session -> userdata('facility_id');
		$patient_number = $this->input->post('patient_number');
		$date_created = date('Y-m-d',strtotime('NOW'));
		$data_array = array('firstname' =>$this->input->post('first_name') ,
							'lastname' =>$this->input->post('last_name') ,
							'date_of_birth' =>$this->input->post('dob') ,
							'gender' =>$this->input->post('gender') ,
							'telephone' =>$this->input->post('telephone') ,
							'email' =>$this->input->post('email') ,
							'home_address' =>$this->input->post('physical_address') ,
							'work_address' =>$this->input->post('work_address') ,
							'patient_number' =>$patient_number,
							'facility_code' =>$facility_code ,
							'date_created' =>$date_created ,
							'status' =>1);		

		$patients = Patients::save_patient($data_array,$patient_number,$date_created,$facility_code);
	}
	public function get_patient_detail($type=NULL){
		$patient_number = $this->input->post('patient_number');
		if ($type==NULL) {
			$patient_details = Patients::filter_patient($patient_number);			
		}else{
			$patient_details = Patients::get_one_patient($patient_number);
		}
		if (count($patient_details)  < 1) {
			echo 0;
		}else{
		$p_dets = array();
		foreach ($patient_details as $key => $value) {
			$id = $value['id'];
			$firstname = $value['firstname'];
			$patient_number = $value['patient_number'];
			$lastname = $value['lastname'];
			$date_of_birth = $value['date_of_birth'];
			$date_of_birth_string = date('d F Y', strtotime($date_of_birth));
			$gender = $value['gender'];
			$name = $firstname.' '.$lastname;			
			$gender = ($gender=='1') ? 'Male' : 'Female';
			$name_and_number = $name .' | '.$patient_number;
			$p_dets[] = array($patient_number,$name,$gender,$date_of_birth_string,$name_and_number,$id);		
		}
		echo json_encode($p_dets);
	}
	}
	public function get_service_point_graph_data(){
		// $graph_id = "container";
		$facility_code = $this -> session -> userdata('facility_id');
		$service_point = 2;//hard coded for pharmacy,until requested for :]

		$selected_service_point = Selected_service_points::get_dispensing_point($facility_code);
		foreach ($selected_service_point as $sp) {
			$service_point = $sp->service_point_id;
		}

		$service_point_stock = facility_stocks::get_service_point_stocks($facility_code,$service_point);
		$service_point_name = $service_point_stock[0]['service_point_name'];

		// echo "<pre> Here";print_r($service_point_stock);exit;
		$service_point_stock_count = count($service_point_stock);
		$graph_id = "sp_graph";
		$graph_data = array();
		$graph_data = array_merge($graph_data, array("graph_id" => $graph_id));
		$graph_data = array_merge($graph_data, array("graph_title" => "$service_point_name Stock Level"));
		$graph_data = array_merge($graph_data, array("graph_type" => "bar"));
		$graph_data = array_merge($graph_data, array("graph_yaxis_title" => "Total Stock Level"));
		$graph_data = array_merge($graph_data, array("graph_categories" => array()));
		$graph_data = array_merge($graph_data, array("series_data" => array("Current Pack Balance" => array(), "Current Unit Balance" => array())));
		$graph_data['stacking'] = 'normal';

		foreach($service_point_stock as $service_point_stock):
			$pack_balance = $service_point_stock['current_balance']/$service_point_stock['total_commodity_units'];
			$graph_data['graph_categories'] = array_merge($graph_data['graph_categories'], array($service_point_stock['commodity_name']));
			$graph_data['series_data']['Current Pack Balance'] = array_merge($graph_data['series_data']['Current Pack Balance'],array((float) $pack_balance));
			$graph_data['series_data']['Current Unit Balance'] = array_merge($graph_data['series_data']['Current Unit Balance'],array((float) $service_point_stock['total_commodity_units']));
		endforeach;

		$service_point_stock_data = $this -> hcmp_functions -> create_high_chart_graph($graph_data);
				// echo "<pre>"; print_r($service_point_stock_data); echo "</pre>"; exit;
		$loading_icon = base_url('assets/img/no-record-found.png');
		$service_point_stock_data = ($service_point_stock_count > 0) ? $service_point_stock_data : "$('#container').html('<img src=$loading_icon>');";


		$actual_expiries = count(facility_stocks::get_service_point_stocks($facility_code,$service_point,NULL,1));
		$potential_expiries = count(facility_stocks::get_service_point_stocks($facility_code,$service_point,1,NULL));

		// echo "<pre>";print_r($potential_expiries);exit;
		return array(
			"service_point_stock_count" => $service_point_stock_count,
			"service_point_stock_graph" => $service_point_stock_data,
			"service_point_name" => $service_point_name,
			"potential_expiries" => $potential_expiries,
			"actual_expiries" => $actual_expiries
			);
			}//end of service point dashboard notifications graph data function
			//damn that was a long name
	public function dispense_commodities(){
		// echo "<pre>";print_r($this->input->post());echo "</pre>";

		$facility_code = $this -> session -> userdata('facility_id');		
		$patient_id = $this->input->post("form_patient_id");
		$quantity = $this->input->post("quantity");
		$commodity_id_main = $this->input->post("id");
		$unit_price = $this->input->post("price");		
		$date_created = date('Y-m-d',strtotime('NOW'));
		$id_count = count($commodity_id_main) + 1;
		$id_count1 = count($commodity_id_main) + 1;
		// echo $id_count;//exit;
		$info = array();
		$total_price = 0;
		for ($i=1; $i <$id_count; $i++) { 
			$info_array= array(
			'patient_id'=> $patient_id,
			'facility_code'=> $facility_code,
			'units_dispensed'=> $quantity[$i],
			'commodity_id'=> $commodity_id_main[$i],
			'unit_price'=> $unit_price[$i]
			);				
			$interim_price = $quantity[$i]*$unit_price[$i];
			$total_price+=$interim_price;
			array_push($info, $info_array);

		}
		
		$totals_array = array('facility_code'=>$facility_code,'patient_id'=>$patient_id,'date_created'=>$date_created,'total'=>$total_price,'isdeleted'=>'1');
		
		$res = $this->db->insert_batch("dispensing_records",$info);
		for ($i=1; $i <$id_count1; $i++) { 			
			$quantity_sub = $quantity[$i];	
			$commodity_id = $commodity_id_main[$i];	
			$facility_code = $facility_code;	
			$sql = "update service_point_stocks set current_balance = (current_balance-$quantity_sub) where facility_code  = '$facility_code' and commodity_id = '$commodity_id' and service_point_id = '2'";								
			$this->db->query($sql);
		}		
		$totals_db = $this->db->insert("dispensing_totals",$totals_array);
		$this->session->set_flashdata('system_success_message', 'Commodity Dispensed Successfully');
		redirect('dispensing/issue');
		// echo $res;
	}
	/*REPORTS SECTION START*/
	public function service_point_store_reports(){
		$facility_code = $this -> session -> userdata('facility_id');
			$data['title'] = "Dispensing Reports";
			$data['banner_text'] = "Facility Consumption";
			$data['c_data'] = Commodities::get_facility_commodities($facility_code);
			$data['sidebar'] = "facility/facility_dispensing/dispensing_side_bar_v";
			$data['report_view'] = "facility/facility_dispensing/dispensing_reports_ajax";
			$data['content_view'] = "facility/facility_reports/reports_v";
			$view = 'shared_files/template/template';
			$data['active_panel'] = 'consumption';

		$this -> load -> view($view, $data);
			
	}

	public function patient_history(){
		$facility_code = $this -> session -> userdata('facility_id');
			$data['title'] = "Dispensing Reports";
			$data['banner_text'] = "Patient History";
			$data['c_data'] = Commodities::get_facility_commodities($facility_code);
			$data['sidebar'] = "facility/facility_dispensing/dispensing_side_bar_v";
			$data['report_view'] = "facility/facility_dispensing/patient_history_report";
			$data['content_view'] = "facility/facility_reports/reports_v";
			$view = 'shared_files/template/template';
			$data['active_panel'] = 'patient_history';

		$this -> load -> view($view, $data);	
	}

	public function consumption(){
			$facility_code = $this -> session -> userdata('facility_id');
			$data['title'] = "Dispensing Reports";
			$data['banner_text'] = "Facility Consumption";
			$data['commodities'] = Commodities::get_facility_commodities($facility_code);
			$data['sidebar'] = "facility/facility_dispensing/dispensing_side_bar_v";
			$data['report_view'] = "facility/facility_dispensing/consumption_report";
			$data['content_view'] = "facility/facility_reports/reports_v";
			$view = 'shared_files/template/template';
			$data['active_panel'] = 'consumption';

		$this -> load -> view($view, $data);	
	}


	public function malaria_commodities(){
			$facility_code = $this -> session -> userdata('facility_id');
			$data['title'] = "Category Commodities Reports";
			$data['banner_text'] = "Facility Commodity Consumption by Category";
			// $data['commodities'] = Commodities::get_facility_commodities($facility_code);
			$data['categories'] = commodity_sub_category::get_all_pharm();
			$data['sidebar'] = "facility/facility_dispensing/dispensing_side_bar_v";
			$data['report_view'] = "facility/facility_dispensing/malaria_consumption_report";
			$data['content_view'] = "facility/facility_reports/reports_v";
			$view = 'shared_files/template/template';
			$data['active_panel'] = 'malaria';

		$this -> load -> view($view, $data);	
	}

	public function patient_history_ajax(){
		$patient_id = $this->input->post('patient_id');
		$patient_details = Patients::get_patient_history($patient_id);
		$result_table = "";
		$total = 0;
		$result_table .= '
		<table class="table table-bordered row-fluid datatable" id="ajax_history_table">
			<thead>
				<th>Patient Name</th>
				<th>Commodity Name</th>
				<th>Units Dispensed</th>
				<th>Date Dispensed</th>
			</thead>

			<tbody>';
			if (count($patient_details) > 0 ) {
				foreach ($patient_details as $data) {
					$total+= intval($data['units_dispensed']);
					$result_table .='<tr>
						<td>'.$data['firstname'].' '.$data['lastname'].'</td>
						<td>'.$data['commodity_name'].'</td>
						<td>'.$data['units_dispensed'].'</td>
						<td>'.date('Y-M-d',strtotime($data['date_created'])).'</td>
						</tr>';
					}
			}else{
				$result_table .= '<tr><td><b>There is no history on this patient</b></td><td></td><td></td><td></td></tr>';
			}
			$result_table.='<tr><td>--</td><td><b>Total:</b></td><td><b>'.$total.'</b></td><td></td></tr>';
		
		$result_table.= '</tbody></table>';

		echo $result_table;
	}
	public function consumption_ajax($sub_category = null){
		$commodity_id = $this->input->post('commodity_id');
		$from = $this->input->post('from');
		$to = $this->input->post('to');
		$total = 0;		
		$to = ($to == "NULL" || !isset($to)) ? date('Y-m-d 23:59:59') : date('Y-m-d 23:59:59', strtotime(urldecode($to)));
		$from = ($from == "NULL" || !isset($from)) ? date('Y-m-d 00:00:00') : date('Y-m-d 00:00:00', strtotime(urldecode($from)));
		$consumption = facility_stocks::get_dispensing_consumption($commodity_id,$from,$to,$sub_category);
		$result_table = "";
		$result_table .= '
		<table class="table table-bordered row-fluid datatable" id="ajax_commodity_table">
			<thead>				
				<th>Commodity Name</th>
				<th>Units Consumed</th>				
			</thead>

			<tbody>';
			if (count($consumption) > 0 ) {
				foreach ($consumption as $data) {
					$total+= intval($data['total']);
					$result_table .='<tr>						
						<td>'.$data['commodity'].'</td>
						<td>'.$data['total'].'</td>						
						</tr>';
					}
			}else{
				$result_table .= '<tr><td><b>There is no consumption history on this Commodity</b></td><td>N/A</td></tr>';
			}
		$result_table.='<tr><td><b>Total:</b></td><td><b>'.$total.'</b></td></tr>';
		$result_table.= '</tbody></table>';

		echo $result_table;
	}

	public function get_consumption_chart_ajax($sub_category = null){
		$commodity_id = $this->input->post('commodity_id');
		$from = $this->input->post('from');
		$to = $this->input->post('to');
		$total = 0;		
		$to = ($to == "NULL" || !isset($to)) ? date('Y-m-d 23:59:59') : date('Y-m-d 23:59:59', strtotime(urldecode($to)));
		$from = ($from == "NULL" || !isset($from)) ? date('Y-m-d 00:00:00') : date('Y-m-d 00:00:00', strtotime(urldecode($from)));
		$consumption = facility_stocks::get_dispensing_consumption_by_age($commodity_id,$from,$to,$sub_category);
		$commodity_name = '';
		if ($commodity_id!='') {
			$commodity_details = Commodities::get_commodity_name($commodity_id);
			$commodity_name = $commodity_details[0]['commodity_name'];

		}else{
			$commodity_details = commodity_sub_category::get_dets_one($sub_category);
			
		}
		$val1 = intval($consumption['5']);
		$val2 = intval($consumption['above']);
		$graph_id = 'dem_graph_';
		$data['graph_id'] = 'dem_graph_';
		$text_title = $commodity_name.' consumption by Age between '.date('F d Y', strtotime($from)).' and '.date('F d Y', strtotime($to));
		$result_chart = " $('#$graph_id').highcharts({
            chart: {
                plotBackgroundColor: null,
                plotBorderWidth: null,
                plotShadow: false,
                type: 'pie'
            },
            title: {
                text: '$text_title'
            },
            tooltip: {
                pointFormat: '{series.name}: <b>{point.percentage:.1f}%</b>'
            },
            plotOptions: {
                pie: {
                    allowPointSelect: false,
                    cursor: 'pointer',
                    dataLabels: {
                        enabled: true
                    },
                    showInLegend: true
                }
            },
            series: [{
                name: '$commodity_name',
                colorByPoint: true,
                data: [{
                    name: 'Under 5 years ($val1 units)',
                    y: $val1
                }, {
                    name: 'Over 5 Years ($val2 units)',
                    y:$val2,
                    sliced: true,
                    selected: true
                }]
            }]
        });";
		// $result_table .= '		
		$data['high_graph'] = $result_chart;
		return $this -> load -> view("shared_files/report_templates/high_charts_template_v", $data);
		echo $result_chart;
	}

	public function get_consumption_chart_ajax_all($sub_category = null){
		$commodity_id = $this->input->post('commodity_id');
		$from = $this->input->post('from');
		$to = $this->input->post('to');
		$total = 0;		
		$to = ($to == "NULL" || !isset($to)) ? date('Y-m-d 23:59:59') : date('Y-m-d 23:59:59', strtotime(urldecode($to)));
		$from = ($from == "NULL" || !isset($from)) ? date('Y-m-d 00:00:00') : date('Y-m-d 00:00:00', strtotime(urldecode($from)));
		$consumption = facility_stocks::get_dispensing_consumption_by_commodity($from,$to,$sub_category);
		// echo "<pre>";
		// print_r($consumption);die;

		// $commodity_details = Commodities::get_commodity_name($commodity_id);		
		$graph_id = 'dem_graph_';
		$data['graph_id'] = 'dem_graph_';
		// $commodity_name = $commodity_details[0]['commodity_name'];
		$text_title = 'Top 10 Highest Consumption by Commodity between '.date('F d Y', strtotime($from)).' and '.date('F d Y', strtotime($to));
		$result_chart = " $('#$graph_id').highcharts({
            chart: {
                plotBackgroundColor: null,
                plotBorderWidth: null,
                plotShadow: false,
                type: 'pie'
            },
            title: {
                text: '$text_title'
            },
            tooltip: {
                pointFormat: '{series.name}: <b>{point.percentage:.1f}%</b>'
            },
            plotOptions: {
                pie: {
                    allowPointSelect: false,
                    cursor: 'pointer',
                    dataLabels: {
                        enabled: true
                    },
                    showInLegend: true
                }
            },
            series: [{
                name: 'Test',
                colorByPoint: true,
                data:[";           
	            foreach($consumption as $key=>$raw_data):
	            	$name = $raw_data['commodity_name'];
	            	$val = intval($raw_data['total']);				
					$result_chart .="{ name: '$name ($val units)', y:$val";					
					$result_chart .= "},";				  
				endforeach;
        //     series: [{
        //         name: '$commodity_name',
        //         colorByPoint: true,
        //         data: [{
        //             name: 'Under 5 years ($val1 units)',
        //             y: $val1
        //         }, {
        //             name: 'Over 5 Years ($val2 units)',
        //             y:$val2,                    
        //             selected: true
        //         }]
        //     }]
        // });";
		$result_chart .="] }] })";
		$data['high_graph'] = $result_chart;
		return $this -> load -> view("shared_files/report_templates/high_charts_template_v", $data);
		// echo $result_chart;
	}

	public function delete_patient($patient_id){
		// echo "DELETE FROM patients WHERE id='$patient_id'";exit;
		$deletion = Doctrine_Manager::getInstance() -> getCurrentConnection() -> execute("DELETE FROM patients WHERE id= $patient_id;");
		$this -> session -> set_flashdata('system_success_message', "Patient has been deleted");

		// echo $deletion;
		$this-> patients();
	}

	public function update_service_point_prices() {
		//security check
		$facility_code = $this -> session -> userdata('facility_id');
		$service_point = 2;

		$selected_service_point = Selected_service_points::get_dispensing_point($facility_code);
		foreach ($selected_service_point as $sp) {
			$service_point = $sp->service_point_id;
		}
		$commodity_id = $this->input->post('commodity_id');
		$price = $this->input->post('price');
		$count = count($commodity_id);
		for ($i=0; $i <$count ; $i++) { 
			$c_id = $commodity_id[$i];
			$c_price = $price[$i];
			$insert = Doctrine_Manager::getInstance() -> getCurrentConnection();
			$insert -> execute("update service_point_stocks set price='$c_price' where facility_code='$facility_code' AND service_point_id='$service_point' AND commodity_id='$c_id'");
		}
		
		$this->session->set_flashdata('system_success_message', 'The Commodity Prices have been Updated');
		redirect('dispensing');
		
	}

	}//end of dispense class
?>