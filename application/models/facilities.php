<?php
class Facilities extends Doctrine_Record {
	public function setTableDefinition() {
		$this -> hasColumn('facility_code', 'varchar',30);
		$this -> hasColumn('targetted', 'int');
		$this -> hasColumn('facility_name', 'varchar',30);
		$this -> hasColumn('district', 'varchar',30);
		$this -> hasColumn('partner', 'int',11);
		$this -> hasColumn('owner', 'varchar',30);
		$this -> hasColumn('level', 'varchar',30);
		$this -> hasColumn('drawing_rights','text');
		$this -> hasColumn('using_hcmp','int');
		$this -> hasColumn('date_of_activation','date');
		$this -> hasColumn('contactperson','varchar',50);
		$this -> hasColumn('cellphone','int',15);
	}

	public function setUp() {
		$this -> setTableName('facilities');
		$this -> hasOne('facility_code as Code', array('local' => 'facility_code', 'foreign' => 'facilityCode'));
		$this -> hasOne('facility_code as Coder', array('local' => 'facility_code', 'foreign' => 'facility_code'));
		$this -> hasOne('facility_code as Codes', array('local' => 'facility_code', 'foreign' => 'facility'));
		$this -> hasMany('districts as facility_subcounty', array('local' => 'district', 'foreign' => 'id'));
	}

	public static function getAll() {
		$query = Doctrine_Query::create() -> select("*") -> from("facilities");
		$drugs = $query -> execute();
		return $drugs;
	}

	public static function getAll_($county = NULL,$district = NULL) {
		$and = isset($district)? "AND d.id = $district" : NULL;
		$and .= (isset($county) && !isset($district))? " AND c.id = $county" : NULL;
		
		$query = Doctrine_Manager::getInstance()->getCurrentConnection()->fetchAll("
			SELECT 
		    c.county, d.district as subcounty, f.facility_name,f.facility_code, f.`level`, f.type,f.date_of_activation
		from
		    facilities f,
		    districts d,
		    counties c
		where
		    f.district = d.id and d.county = c.id
		        and f.`using_hcmp` = 1
		        $and
		group by f.facility_name
			");
		$facilities = $query;
		return $facilities;
	}

	public static function get_detailed_listing($county_id) {
		$facilities = Doctrine_Manager::getInstance()->getCurrentConnection()
		->fetchAll("SELECT 
					    f.facility_name,
					    f.facility_code,
					    f.owner,
					    f.type,
					    f.level,
					    f.date_of_activation,
					    f.using_hcmp,
					    f.contactperson,
					    f.cellphone,
					    d.district,
					    c.county
					    
					FROM
					    facilities f,
					    districts d,
					    counties c
					WHERE
					    f.district = d.id 
					    AND d.county = c.id
					        AND c.id = $county_id");
		
		return $facilities;
	}

    public static function check_active_facility($facility)
    {
        $active = Doctrine_Manager::getInstance()->getCurrentConnection()
            ->fetchAll("SELECT
                            (CASE
                                WHEN using_hcmp = 1 THEN 'Yes'
                                WHEN using_hcmp = 0 THEN 'No'
                            END) AS 'HCMP Supported'
                        FROM
                            facilities
                        WHERE
                            facility_code = $facility");

        return $active;
    }
	//get the number of facilities using HCMP in the country
	public static function get_all_on_HCMP()
	{
		$q = Doctrine_Manager::getInstance()->getCurrentConnection()
		->fetchAll("SELECT 
					    f.`using_hcmp`
					FROM
					    facilities f,
					    districts d,
					    counties c
					WHERE
					    f.district = d.id AND d.county = c.id
					        AND f.`using_hcmp` = 1");
		
		return count($q);
	}
	public static function get_all_facilities_on_HCMP()
	{
		$q = Doctrine_Manager::getInstance()->getCurrentConnection()
		->fetchAll("SELECT 
					    f.facility_code,
					    f.owner,
					    f.type,
					    f.level,
					    f.facility_name,
					    f.date_of_activation,
					    d.district as sub_county,
					    c.county
					FROM
					    facilities f,
					    districts d,
					    counties c
					WHERE
					    f.using_hcmp = 1 AND f.district = d.id
					        AND d.county = c.id");
		
		return $q;
	}
	public static function getAll_json() {
		$query = Doctrine_Query::create() -> select("*") -> from("facilities");
		$drugs = $query -> execute(array(), Doctrine::HYDRATE_ARRAY);
		return $drugs;
	}
	public static function getFacilities($district){
		
		$query = Doctrine_Query::create() -> select("*") -> from("facilities")->where("district='$district'")->OrderBy("facility_name asc");
		$drugs = $query -> execute();
		return $drugs;
	}
	public static function getFacilities_json($district){
		
		$query = Doctrine_Query::create() -> select("*") -> from("facilities")->where("district='$district'")->OrderBy("facility_name asc");
		$drugs = $query -> execute(array(), Doctrine::HYDRATE_ARRAY);
		return $drugs;
	}
	public static function getFacilities_for_email($district){
		
		$query = Doctrine_Query::create() -> select("*") -> from("facilities")->where("district='$district' AND using_hcmp=1")->OrderBy("facility_name asc");
		$drugs = $query -> execute();
		return $drugs;
	}

	public static function getFacilities_from_facility_code($facility_code){
		
		$query = Doctrine_Query::create() -> select("*") -> from("facilities")->where("facility_code='$facility_code' AND using_hcmp=1")->OrderBy("facility_name asc");
		$drugs = $query -> execute();
		return $drugs;
	}
	public static function get_Facilities_using_HCMP($county_id = null, $district_id = null)
	{
		$and_data =(isset($county_id)&& ($county_id>0)) ?" AND d.county = $county_id" : null;
	    $and_data .=(isset($district_id)&& ($district_id>0)) ?" AND f.district = $district_id" : null;

	    $and_data .= " AND using_hcmp = 1 ";
		
	   	$query = Doctrine_Query::create() ->select("*") ->from("facilities f, districts d")->where("f.district = d.id $and_data")->OrderBy("facility_name asc");
		// $drugs = $query -> execute();
		$drugs = $query -> execute(array(), Doctrine::HYDRATE_ARRAY);
		return $drugs;
	}

	public static function get_facilities_per_county($county_id = null, $district_id = null)
	{
		$and_data =(isset($county_id)&& ($county_id>0)) ?" AND d.county = $county_id" : null;
	    $and_data .=(isset($district_id)&& ($district_id>0)) ?" AND f.district = $district_id" : null;

	    // $and_data .= " AND using_hcmp = 1 ";
		
	   	$query = Doctrine_Query::create() ->select("*") ->from("facilities f, districts d")->where("f.district = d.id $and_data")->OrderBy("facility_name asc");
		// $drugs = $query -> execute();
		$drugs = $query -> execute(array(), Doctrine::HYDRATE_ARRAY);
		return $drugs;
	}
	
	//gets all the facilitites using HCMP in the system
	//For weekly email updates
	public static function get_all_using_HCMP($county_id)
	{
		$and_data = (isset($county_id)&&$county_id>0)?" AND d.county = $county_id" :null;
		$q = Doctrine_Manager::getInstance()->getCurrentConnection()->fetchAll("
		SELECT DISTINCT
		    (f.district) as district, d.district as name
		from
		    facilities f,
		    districts d
		where
		    f.district = d.id 
		    $and_data
		    AND using_hcmp = 1");
			
		return $q;
	}
	public static function get_counties_all_using_HCMP()
	{
		$q = Doctrine_Manager::getInstance()->getCurrentConnection()->fetchAll("
		SELECT DISTINCT
		    (d.county) as county, c.county as county_name
		from
		    facilities f,
		    districts d,
		    counties c
		where
		c.id = d.county
		AND f.district = d.id 
		AND using_hcmp = 1");
			
		return $q;
	}

	public static function get_activation_logs() {
		$q = Doctrine_Manager::getInstance()->getCurrentConnection()->fetchAll("
			SELECT
				u.id AS 'user_id',
				CONCAT(u.fname, ' ', u.lname) AS 'full_name',
				f.facility_code AS 'facility_code',
			    f.facility_name AS 'facility_name',
			    fl.action AS 'action',
			    fl.date AS 'date_of_action'
			FROM
			    user u JOIN
			    facilities f
			        JOIN
			    facility_activation_logs fl ON f.facility_code = fl.facility_code
			        AND u.id = fl.user_id;
						    facility_activation_logs;
			");
		return $q;
	}

	// This is the function
	public static function get_offline_facilities($county_id, $district_id) {
		$and_data = "";
		if(isset($county_id)) {
			$and_data = " AND d.county = '$county_id'";
		}
		if(isset($district_id)) {
			$and_data = " AND f.district = '$district_id' ";
		}
		$q = Doctrine_Manager::getInstance()->getCurrentConnection()->fetchAll("
			SELECT 
			    f.facility_code,
			    f.facility_name,
			    d.district,
			    c.county
			FROM
			    facilities f
			    JOIN districts d JOIN 
			    counties c ON f.district = d.id AND 
			    d.county = c.id
			WHERE f.using_hcmp = 2 $and_data");
		return $q;
	}

	//In case the stock outs or expiries for Taita Taveta are not sent out
	public static function get_Taita()
	{
		$q = Doctrine_Manager::getInstance()->getCurrentConnection()->fetchAll("
		SELECT DISTINCT
		    id as county, c.county as county_name
		from
		     counties c
		where
		c.id = 39");
			
		return $q;
	}
	//get the facility codes of facilities in a particular district
	public static function get_district_facilities($district)
	{
		$q = Doctrine_Manager::getInstance()->getCurrentConnection()->fetchAll("SELECT DISTINCT (facility_code)
			FROM  `facilities` 
			WHERE district =$district");
		return $q;	
	}

	public static function get_district_facilities_using_hcmp($district)
	{
		$q = Doctrine_Manager::getInstance()->getCurrentConnection()->fetchAll("SELECT DISTINCT (facility_code)
			FROM  `facilities` 
			WHERE district =$district AND using_hcmp = 1");
		return $q;	
	}
	// getting facilities which are using the system
	public static function get_facilities_which_are_online($county_id)
	{
		$q = Doctrine_Manager::getInstance()->getCurrentConnection()->fetchAll("SELECT facility_code, facility_name,f.district
		FROM facilities f, districts d
		WHERE f.district = d.id
		AND d.id =$county_id
		AND UNIX_TIMESTAMP(  `date_of_activation` ) >0
		ORDER BY  `facility_name` ASC ");
		return $q;	
	}
   public static function get_tragetted_rolled_out_facilities($facility_code=null,$district_id=null,$county_id=null,$identifier=null){
        switch ($identifier){
        	case county:
			 $where_clause= "d.county=$county_id " ;	
			break;	
			case district:
			 $where_clause= "d.id=$district_id" ;	
			break;	
			case facility:
			case facility_admin:
			 $where_clause= "f.facility_code=$facility_code " ;	
			break;	
           default:
			 $where_clause=isset($facility_code)? "f.facility_code=$facility_code ":
			  (isset($district_id) && !isset($county_id)? "d.id=$district_id ": "d.county=$county_id ") ;
			break;	
        }
		
        $q = Doctrine_Manager::getInstance()->getCurrentConnection()->fetchAll("
        SELECT SUM( targetted ) AS targetted, SUM( using_hcmp ) AS using_hcmp, COUNT( facility_code ) AS total
        FROM districts d, facilities f
        WHERE f.district = d.id
        and $where_clause");
        return $q;    
   }
	public static function get_facility_name_($facility_code)
	{			
		if($facility_code!=NULL)
		{
			$query = Doctrine_Query::create() -> select("*") -> from("facilities")->where("facility_code='$facility_code'");
			$drugs = $query -> execute();
			return $drugs;	
		}	
		else{
			return NULL;
		}	
			
	}

	public static function get_facilities_user_activation_data($facility_code){
		$q = Doctrine_Manager::getInstance()->getCurrentConnection()->fetchAll("SELECT distinct user.fname,user.lname,user.created_at ,log.end_time_of_event 
		    	FROM user,log where user.facility = '$facility_code' and user.status = 1 and user.id = log.user_id and log.action = 'Logged Out' GROUP BY user_id order by log.end_time_of_event desc"); 
		return $q;  
	}

	public static function get_facilities_users_data($facility_code){
		$q = Doctrine_Manager::getInstance()->getCurrentConnection()->fetchAll("SELECT distinct user.id,user.fname,user.lname,user.email,user.created_at from user where user.facility = '$facility_code' and user.status = 1 group by user.id order by user.fname ASC"); 
		return $q;  
	}

   public static function get_facilities_monitoring_data($facility_code=null,$district_id=null,$county_id=null,$identifier=null)
   {
        switch ($identifier)
        {
        	case county:
			 $where_clause= "d.county=$county_id " ;	
			break;	
			case district:
			 $where_clause= "d.id=$district_id" ;	
			break;	
			case facility:
			case facility_admin:
			 $where_clause= "f.facility_code=$facility_code " ;	
			break;	
           default:
			 $where_clause=isset($facility_code)? "f.facility_code=$facility_code ":
			  (isset($district_id) && !isset($county_id)? "d.id=$district_id ": "d.county=$county_id ") ;
			break;	
        }
	    $q = Doctrine_Manager::getInstance()->getCurrentConnection()->fetchAll("
	    SELECT u.fname, u.lname,f.facility_name, f.facility_code,d.district,
	    MAX( f_i.`created_at` ) AS last_issued, ifnull(DATEDIFF( NOW( ) , MAX( f_i.`created_at` ) ),0) AS days_last_issued, 
	    MAX( l.end_time_of_event ) AS last_seen, ifnull(DATEDIFF( NOW( ) , MAX( l.end_time_of_event ) ),0) AS days_last_seen
	    FROM user u, log l, facilities f, districts d, facility_issues f_i
	    WHERE f_i.`issued_by` = u.id
	    and l.user_id=u.id
	    AND u.facility = f.facility_code
	    AND f.district = d.id
	    and $where_clause
	    GROUP BY f.facility_code
	 "); 
		return $q;  
    
	}

	public static function get_facilities_not_logged_in_count() {
		$data = Doctrine_Manager::getInstance()->getCurrentConnection()->fetchAll("
			SELECT DISTINCT
			    (COUNT(l.facility_code)) AS 'not_logged_in'
			FROM
			    facilities f
			        JOIN
			    log l ON f.facility_code = l.facility_code
			WHERE
			    f.facility_code NOT IN (l.facility_code) 
			    AND f.using_hcmp = 1;");
		return $data[0];
		
	}

	public static function facilities_not_logged_in_two_weeks() {
		$data = Doctrine_Manager::getInstance()->getCurrentConnection()->fetchAll("
			SELECT 
			    COUNT(DISTINCT (l.facility_code)) AS 'last_two_weeks'
			FROM
			    log l
			WHERE
			    l.end_time_of_event < (CURDATE() < 14);");
		return $data[0];
	}

	public static function facilities_not_logged_in_one_month() {
		$data = Doctrine_Manager::getInstance()->getCurrentConnection()->fetchAll("
			SELECT 
			    COUNT(DISTINCT (l.facility_code)) AS 'last_one_month'
			FROM
			    log l
			WHERE
			    l.end_time_of_event < (CURDATE() < 30);");
		return $data[0];
	}

	public static function get_facility_data_specific($report_type,$county,$district_id = NULL,$facility_code = NULL,$scope = NULL){
		/*
		@author karsan AS AT 2015-08-04
		*/
		if (isset($county)) {
			if (isset($district_id)) {
				$data = Doctrine_Manager::getInstance()->getCurrentConnection()->fetchAll("
		             CALL facility_monitoring_new1('district','$district_id','$report_type');
					");
			}else{//if district id isnt set
				$data = Doctrine_Manager::getInstance()->getCurrentConnection()->fetchAll("
		             CALL facility_monitoring_new1('county','$county','$report_type');
					");
			}
		}else{
			if ($scope = 'all') {
					// echo "I WORK";exit;
					$data = Doctrine_Manager::getInstance()->getCurrentConnection()->fetchAll("
		             CALL facility_monitoring_new1('all','$county','$report_type');
					");
				}else{
				$data = Doctrine_Manager::getInstance()->getCurrentConnection()->fetchAll("
		             CALL facility_monitoring_new1('county','$county','$report_type');
					");
		}
	}
		return $data;
		
		
		/*
		//PRIOR OF PROCEDURISATION OF BELOW QUERY
		$facility_filter = NULL;
		$district_id = isset($district_id)? $district_id : NULL;
		$county_filter = (isset($county))? "AND c.id = $county" : NULL ;
		$district_filter = (isset($district_id))? "AND d.id = $district_id" : NULL ;
		// echo $district_id;exit;
		$addition = NULL;
		if (isset($report_type)) {
			if ($report_type == 'last_issued') {
				$addition .= "AND l.issued = 1";
				// $addition = "AND l.issued = 1 AND l.add_stock = 0 AND l.ordered = 0 AND l.decommissioned = 0 AND l.redistribute = 0";
				// $col_title = "days_from_last_issue";
			}elseif ($report_type == 'last_ordered') {
				$addition .= "AND l.ordered = 1";
				// $addition = "AND l.ordered = 1 AND l.issued = 0 AND l.add_stock = 0 AND l.decommissioned = 0 AND l.redistribute = 0";
				// $col_title = "days_from_last_ordered";
			}elseif ($report_type == 'last_decommissioned') {
				$addition .= "AND l.decommissioned = 1";
				// $addition = "AND l.decommissioned = 1 AND l.issued = 0 AND l.ordered = 0 AND l.add_stock = 0 AND l.redistribute = 0";
				// $col_title = "days_from_last_ordered";
			}elseif ($report_type == 'last_redistributed') {
				$addition .= "AND l.redistribute = 1";
				// $addition = "AND l.redistribute = 1 AND l.issued = 0 AND l.ordered = 0 AND l.decommissioned = 0 AND l.add_stock = 0";
				$col_title = "days_from_last_redistributed";
			}elseif ($report_type == 'last_added_stock') {
				$addition .= "AND l.add_stock = 1";
				// $addition = "AND l.add_stock = 1 AND l.issued = 0 AND l.ordered = 0 AND l.decommissioned = 0 AND l.redistribute = 0";
				// $col_title = "days_from_last_stock_addition";
			}
			else{
				$addition = NULL;
			}
		}

		$query = Doctrine_Manager::getInstance()->getCurrentConnection()->fetchAll(
			"
			SELECT 
			    u.id,
			    u.username,
			    d.district,
			    c.county,
			    f.facility_name,
			    f.facility_code AS facility_code,
			    c.county,
			    d.district,
			    d.id AS district_id,
			    c.id AS county_id,
			    l.action,
			    l.end_time_of_event AS last_seen,
			    l.issued,
			    l.ordered,
			    l.decommissioned,
			    l.redistribute,
			    l.add_stock,
			    DATEDIFF(NOW(), l.end_time_of_event) AS difference_in_days
			FROM
			    facilities f,
			    districts d,
			    counties c,
			    user u,
			    log l
			WHERE
			    f.district = d.id AND d.county = c.id
			        AND u.facility = f.facility_code
			        AND u.id = l.user_id
			        AND d.county = c.id
			        $county_filter
			        $district_filter
			        $facility_filter
			        $addition
			");

		return $query;
		*/
		
		//PRIOR TO PROCEDURISATION OF THE ABOVE QUERY
	}//get facility data specific

	//gets the monitoring data for all the facilities using HCMP
	public static function facility_monitoring($county_id, $district_id, $facility_code=null)
	{
		if(isset($facility_code)&&($facility_code>0)):
			$data = Doctrine_Manager::getInstance()->getCurrentConnection()->fetchAll("
				CALL facility_monitoring('facility','$facility_code');
			");
			//return the monitoring data
			//echo "<pre>";print_r($data) ;exit;
			return $data;
		
		elseif(isset($district_id)&&!isset($facility_code)):
			$data = Doctrine_Manager::getInstance()->getCurrentConnection()->fetchAll("
				CALL facility_monitoring('district','$district_id');
			");
			//return the monitoring data
			return $data; 
		else:
			$data = Doctrine_Manager::getInstance()->getCurrentConnection()->fetchAll("
				CALL facility_monitoring('county','$county_id');
			");
			//return the monitoring data
			return $data; 
		endif;
		
		
	}
//gets the date of last issue for the ORS Zinc report only
	//it is a mini hack for that purpose

	public static function get_last_issue($facility_code)
	{
		
		$data = Doctrine_Manager::getInstance()->getCurrentConnection()
		->fetchAll("SELECT 
                        (CASE
                            WHEN l.issued = 1 THEN MAX(l.end_time_of_event)
                            WHEN l.issued = 0 THEN 0
                        END) AS 'Date Last Issued',
                        (CASE
                            WHEN
                                l.issued = 1
                            THEN
                                IFNULL(DATEDIFF(NOW(), MAX(l.end_time_of_event)),
                                        0)
                            WHEN l.issued = 0 THEN 0
                        END) AS 'Days From Last Issue'
                    FROM
                        log l,
                        user u,
                        facilities f,
                        districts d,
                        counties c
                    WHERE
                        l.issued = 1 AND f.using_hcmp = 1
                            AND f.facility_code = $facility_code
                            AND l.user_id = u.id
                            AND u.facility = f.facility_code
                            AND f.district = d.id
                            AND d.county = c.id
                                        ");
			//return the monitoring data
		
			return $data;
		
		
		
	}

	public function get_facilities_logged_in_month($start_date,$last_date,$issued=null,$county_id=null,$district_id=null){		
		$and_data = '';
		if ($county_id!=null) {
			$and_data.= " and c.id = $county_id ";
		}
		if ($district_id!=null) {
			$and_data.= " and d.id = $district_id ";
		}		

		$sql = "SELECT DISTINCT f.facility_code, c.county, d.district, f.facility_name 
				FROM facilities f, districts d, counties c, log l
				WHERE  f.facility_code = l.facility_code AND f.using_hcmp = '1'
		        AND d.id = f.district AND c.id = d.county $and_data AND f.date_of_activation < '$last_date'
		        AND l.end_time_of_event BETWEEN '$start_date' AND '$last_date' AND l.action = 'Logged Out'
				GROUP BY l.facility_code";
		
		$data = Doctrine_Manager::getInstance()->getCurrentConnection()->fetchAll($sql);
		return $data;
	}

	// public function new_get_facility_logged_in_count($start_date,$last_date,$number,$county_id=null,$district_id = null){
	// 	$final_array = array();
	// 	$sql = "SELECT DISTINCT   f.facility_code, c.county, d.district,f.facility_name
	// 			FROM facilities f,districts d, counties c,facility_user_log_new fl
	// 			WHERE f.facility_code = fl.facility_code AND f.using_hcmp = '1'
 //        		AND d.id = f.district AND c.id = d.county AND f.date_of_activation < '$last_date'
 //        		AND fl.end_time_of_event BETWEEN '$start_date' AND '$last_date' AND fl.action = 'Logged Out'";
 //        $result = $this->db->query($sql)->result_array();
 //        foreach ($result as $key => $value) {
 //        	$facility_code =$value['facility_code'];
 //        	$district =$value['district'];
 //        	$county =$value['county'];      	

 //        	$sql_count = "SELECT DISTINCT DATE(fl.end_time_of_event) mydate, COUNT(DISTINCT fl.facility_code) AS times
	// 					FROM facility_user_log_new fl WHERE fl.end_time_of_event BETWEEN '$start_date' AND '$last_date'
 //    					AND fl.action = 'Logged Out' AND fl.facility_code = '$facility_code' GROUP BY mydate 
 //    					HAVING COUNT(fl.end_time_of_event) >= '$number'";
 //    		$result_count = $this->db->query($sql_count)->result_array();
 //    		$count = count($result_count);
 //    		if($count>0){    			
 //    			$final_array[] = array('facility_code' => $facility_code,'facility_name'=>$facility_name,'district'=>$district,
 //    									'county'=>$county,'days'=>$count);
 //    		}
    		
 //        }
 //        return $final_array;
        
	// }
	public function get_facilities_count($start_date,$last_date,$number,$county_id=null,$district_id = null,$type=null){		
		$and_data = '';
		if ($county_id!=null) {
			$and_data.= " and c.id = $county_id ";
		}
		if ($district_id!=null) {
			$and_data.= " and d.id = $district_id ";
		}

		if(isset($type)){
			$and_data.= ($type='issued') ? 'and issued = 1' : null;
		}
		
		$final_array = array();
		$sql = "SELECT DISTINCT f.facility_code, c.county, d.district,f.facility_name
				FROM facilities f,districts d, counties c,log fl
				WHERE f.facility_code = fl.facility_code AND f.using_hcmp = '1'
        		AND d.id = f.district AND c.id = d.county AND f.date_of_activation < '$last_date'
        		AND fl.end_time_of_event $and_data BETWEEN '$start_date' AND '$last_date' AND fl.action = 'Logged Out'";

        $result = $this->db->query($sql)->result_array();
        foreach ($result as $key => $value) {
        	$facility_code =$value['facility_code'];
        	$facility_name =$value['facility_name'];
        	$district =$value['district'];
        	$county =$value['county'];      	

        	$sql_count = "SELECT DISTINCT DATE(fl.end_time_of_event) mydate, COUNT(DISTINCT fl.facility_code) AS times
						FROM log fl WHERE fl.end_time_of_event BETWEEN '$start_date' AND '$last_date'
    					AND fl.action = 'Logged Out' AND fl.facility_code = '$facility_code' $and_data GROUP BY mydate 
    					HAVING COUNT(fl.end_time_of_event) >= '$number'";
    		// echo "$sql_count";die;
    		$result_count = $this->db->query($sql_count)->result_array();    		
    		$count = count($result_count);
    		if($count>0){    			
    			$final_array[] = array('facility_code' => $facility_code,'facility_name'=>$facility_name,'district'=>$district,
    									'county'=>$county,'days'=>$count);
    		}
    		
        }	        
        return $final_array;
	}
	public function get_facilities_issued_in_month($start_date,$last_date,$issued=null,$county_id=null,$district_id=null){
		$and_data = '';
		if ($county_id!=null) {
			$and_data.= " and c.id = $county_id ";
		}
		if ($district_id!=null) {
			$and_data.= " and d.id = $district_id ";
		}
		
		$sql = "SELECT DISTINCT
				    f.facility_code,c.county, d.district, f.facility_name
				FROM
				    facilities f,
				    districts d,
				    counties c,
				    log l
				WHERE
						f.facility_code = l.facility_code
				        AND f.using_hcmp = '1'
						AND d.id = f.district
				        AND c.id = d.county
				        $and_data
				        AND f.date_of_activation < '$last_date'
				        AND l.end_time_of_event BETWEEN '$start_date' AND '$last_date'
				        AND l.action = 'Logged Out'
				        AND l.issued = '1'";
				
		// echo "$sql";die;
		$data = Doctrine_Manager::getInstance()->getCurrentConnection()->fetchAll($sql);
		return $data;
	}


	public function get_facilities_issued_in_count($start_date,$last_date,$number,$county_id=null,$district_id=null){
		$and_data = '';
		if ($county_id!=null) {
			$and_data.= " and c.id = $county_id ";
		}
		if ($district_id!=null) {
			$and_data.= " and d.id = $district_id ";
		}
		$data = Doctrine_Manager::getInstance()->getCurrentConnection()->fetchAll("SELECT DISTINCT
			    f.facility_code,c.county, d.district, f.facility_name, count(l.end_time_of_event) as days
				FROM
				    facilities f,
				    districts d,
				    counties c,
				    facility_user_log l
				WHERE
						f.facility_code = l.facility_code
				        AND f.using_hcmp = '1'
						AND d.id = f.district
				        AND c.id = d.county
				        $and_data
				        AND f.date_of_activation < '$start_date'
				        AND l.end_time_of_event BETWEEN '$start_date' AND '$last_date'
				        AND l.action = 'Logged Out'
				        AND l.issued = '1'
				group by f.facility_code,l.user_id,DAY(l.end_time_of_event) HAVING count(l.end_time_of_event) >='$number'");
		return $data;
	}
	public function get_facilities_not_logged_in_month($start_date,$last_date,$county_id=null,$district_id=null,$type){
		$and_data = '';
		$and_data_logs = '';
		if ($county_id!=null) {
			$and_data.= " and c.id = $county_id ";
		}
		if ($district_id!=null) {
			$and_data.= " and d.id = $district_id ";
		}
		if(isset($type)){
			$and_data_logs.= ($type='issued') ? 'and issued = 1' : null;
		}
		$final_array = array();
		$array_exists = array();
		$sql = "SELECT DISTINCT f.facility_code, c.county, d.district,f.facility_name
				FROM facilities f,districts d, counties c
				WHERE f.using_hcmp = '1'
        		AND d.id = f.district AND c.id = d.county 
        		$and_data";

        $result = $this->db->query($sql)->result_array();
        $sql_exists = "SELECT DISTINCT l.facility_code FROM log l,facilities f, districts d, counties c
							WHERE l.end_time_of_event BETWEEN '$start_date' AND '$last_date'  
							and l.facility_code = f.facility_code AND f.district = d.id AND c.id = d.county
							$and_data $and_data_logs
						        AND l.action = 'Logged Out'";
    	// echo "$sql_exists";die;
    	$result_exists = $this->db->query($sql_exists);  
    	foreach ($result_exists as $key => $value) {
    		array_push($array_exists, $value['facility_code']);  			# code...
    	}  		
        foreach ($result as $key => $value) {
        	$facility_code =$value['facility_code'];
        	$facility_name =$value['facility_name'];
        	$district =$value['district'];
        	$county =$value['county'];      	
        	if(!in_array($facility_code, $array_exists)){
        		$final_array[] = array('facility_code' => $facility_code,'facility_name'=>$facility_name,'district'=>$district,
    									'county'=>$county);
        	}
        	
    		
        }	        
        return $final_array;
        // $data = Doctrine_Manager::getInstance()->getCurrentConnection()->fetchAll($sql);
		// return $data;
	}
	public function get_facilities_not_issued_in_month($start_date,$last_date,$county_id=null,$district_id=null){
		$and_data = '';
		if ($county_id!=null) {
			$and_data.= " and c.id = $county_id ";
		}
		if ($district_id!=null) {
			$and_data.= " and d.id = $district_id ";
		}
		$sql = "SELECT DISTINCT f.facility_code, c.county, d.district, f.facility_name FROM
    			facilities f, districts d,counties c, log l 
    			WHERE f.using_hcmp = '1' AND d.id = f.district AND c.id = d.county $and_data
        		and f.facility_code !=l.facility_code and l.end_time_of_event BETWEEN '$start_date' AND '$last_date'
                AND l.action = 'Logged Out' and l.issued='1'";
        $data = Doctrine_Manager::getInstance()->getCurrentConnection()->fetchAll($sql);

		// $data = Doctrine_Manager::getInstance()->getCurrentConnection()->fetchAll("SELECT DISTINCT
		// 		    f.facility_code,c.county, d.district, f.facility_name
		// 		FROM
		// 		    facilities f,
		// 		    districts d,
		// 		    counties c
		// 		WHERE f.using_hcmp = '1'
		// 				AND d.id = f.district
		// 		        AND c.id = d.county
		// 		        $and_data
		// 				AND f.facility_code not in (SELECT DISTINCT
		// 		    l.facility_code from  facility_user_log l
		// 		WHERE l.end_time_of_event BETWEEN '$start_date' AND '$last_date' and l.issued='1'
		// 		        AND l.action = 'Logged Out')");
		return $data;
	}
    public static function get_last_redistribution($facility_code)
    {

        $data = Doctrine_Manager::getInstance()->getCurrentConnection()
            ->fetchAll("SELECT
                        (CASE
                            WHEN l.redistribute = 1 THEN MAX(l.end_time_of_event)
                            WHEN l.redistribute = 0 THEN 0
                        END) AS 'Date Last Redistributed',
                        (CASE
                            WHEN
                                l.issued = 1
                            THEN
                                IFNULL(DATEDIFF(NOW(), MAX(l.end_time_of_event)),
                                        0)
                            WHEN l.issued = 0 THEN 0
                        END) AS 'Days From Last Redistribution'
                    FROM
                        log l,
                        user u,
                        facilities f,
                        districts d,
                        counties c
                    WHERE
                        l.redistribute = 1 AND f.using_hcmp = 1
                            AND f.facility_code = $facility_code
                            AND l.user_id = u.id
                            AND u.facility = f.facility_code
                            AND f.district = d.id
                            AND d.county = c.id
                                        ");
        //return the monitoring data

        return $data;



    }

	public static function facility_ordered($type, $county_id = NULL, $district_id = NULL, $facility_code=null)
	{
		switch ($type) {
			case 'facility':
			$data = Doctrine_Manager::getInstance()->getCurrentConnection()->fetchAll("
               CALL facility_orders('facility','".$facility_code."');
             
			");
            foreach ($data as $key => $part) {
				//echo '<pre>';print_r($data);echo "</pre>";die();
               $sort[$key] = $part['Days From Last Order'];
             }
              array_multisort($sort, SORT_DESC, $data);
			//return the monitoring data
			break;
		
			case 'district':
			$data = Doctrine_Manager::getInstance()->getCurrentConnection()->fetchAll("
				CALL facility_orders('district','".$district_id."');
			");

			foreach ($data as $key => $part) {
				//echo '<pre>';print_r($data);echo "</pre>";die();
               $sort[$key] = $part['Days From Last Order'];
             }
              array_multisort($sort, SORT_DESC, $data);
			//return the monitoring data
			return $data;
		
			case 'county':
			$data = Doctrine_Manager::getInstance()->getCurrentConnection()->fetchAll("
		   CALL facility_orders('county','".$county_id."');
              
              ");

			foreach ($data as $key => $part) {
				//echo '<pre>';print_r($data);echo "</pre>";die();
               $sort[$key] = $part['Days From Last Order'];
             }
              array_multisort($sort, SORT_DESC, $data);
			
			break;
		}
		//return the monitoring data
			return $data;
		
		
	}

	public static function facility_issued($type, $county_id = NULL, $district_id = NULL, $facility_code=null)
	{
		switch ($type) {
			case 'facility':
				$data = Doctrine_Manager::getInstance()->getCurrentConnection()->fetchAll("
             CALL facility_issues('facility','".$facility_code."');
            
             
			");
			//return the monitoring data
			//echo '<pre>';print_r($data);echo "</pre>";die();
				foreach ($data as $key => $part) {
				//echo '<pre>';print_r($data);echo "</pre>";die();
               $sort[$key] = $part['Days from last issue'];
             }
              array_multisort($sort, SORT_DESC, $data);
				break;

			case 'county':
				$data = Doctrine_Manager::getInstance()->getCurrentConnection()->fetchAll("
		    CALL facility_issues('county','".$county_id."');
              
              ");
				foreach ($data as $key => $part) {
				//echo '<pre>';print_r($data);echo "</pre>";die();
               $sort[$key] = $part['Days from last issue'];
             }
              array_multisort($sort, SORT_DESC, $data);
		//return the monitoring data
			break;	
			case 'district':
				$data = Doctrine_Manager::getInstance()->getCurrentConnection()->fetchAll("
				CALL facility_issues('district','".$district_id."');
			");
				//$data = sortrows($data,'RowNames')
			//return the monitoring data
				foreach ($data as $key => $part) {
				//echo '<pre>';print_r($data);echo "</pre>";die();
               $sort[$key] = $part['Days from last issue'];
             }
              array_multisort($sort, SORT_DESC, $data);
			break;
			default:
				# code...
				break;
		}

		//echo "<pre>";print_r($data);die;
		return $data;
		
	}


	 


	public static function facility_loggins($type, $county_id = NULL, $district_id = NULL, $facility_code=null)
	{
		switch ($type) {
			case 'facility':
				$data = Doctrine_Manager::getInstance()->getCurrentConnection()->fetchAll("
              CALL facility_loggins('facility','".$facility_code."');
            
             
			");
				foreach ($data as $key => $part) {
				//echo '<pre>';print_r($data);echo "</pre>";die();
               $sort[$key] = $part['Days From Last Seen'];
             }
              array_multisort($sort, SORT_DESC, $data);
			//return the monitoring data
			//echo '<pre>';print_r($data);echo "</pre>";die();
				$data = sort($data,'descend');
				break;

			case 'county':
				$data = Doctrine_Manager::getInstance()->getCurrentConnection()->fetchAll("
		    CALL facility_loggins('county','".$county_id."');
              
              ");
				foreach ($data as $key => $part) {
				//echo '<pre>';print_r($data);echo "</pre>";die();
               $sort[$key] = $part['Days From Last Seen'];
             }
              array_multisort($sort, SORT_DESC, $data);
		//return the monitoring data
			break;	
			case 'district':
				$data = Doctrine_Manager::getInstance()->getCurrentConnection()->fetchAll("
				CALL facility_loggins('district','".$district_id."');
			");
				foreach ($data as $key => $part) {
				//echo '<pre>';print_r($data);echo "</pre>";die();
               $sort[$key] = $part['Days From Last Seen'];
             }
              array_multisort($sort, SORT_DESC, $data);
			//return the monitoring data
			break;
			default:
				# code...
				break;
		}

		//echo "<pre>";print_r($data);die;
		return $data;
		
	}

	//Used by facility_mapping function in reports controller
	//Used to get the dates that facilities went online
	public static function get_facilities_online_per_district($county_id)
	{
		$q = Doctrine_Manager::getInstance()->getCurrentConnection()->fetchAll("
		SELECT 
		    d.id,
		    d.district,
		    f.facility_name,
		    f.type,
		    f.facility_code,
		    f.level,
		    f.owner,
		    DATE_FORMAT(`date_of_activation`, '%d %b %y') AS date
		FROM
		    facilities f,
		    districts d
		WHERE
		    f.district = d.id AND f.using_hcmp = 1
		        AND d.county = '$county_id'
		        AND UNIX_TIMESTAMP(f.`date_of_activation`) > 0
		ORDER BY d.district ASC , f.`date_of_activation` ASC
	 	");
		
		return $q;	
	
	}

	public static function get_facilities_online_per_district_other($district_id,$mfl)
	{
		$q = Doctrine_Manager::getInstance()->getCurrentConnection()->fetchAll("
		SELECT 
		    distinct
		    f.facility_name,
		    f.type,
		    f.facility_code,
		    f.level,
		    f.owner,
		    DATE_FORMAT(`date_of_activation`, '%d %b %y') AS date
		FROM
		    facilities f
		WHERE
		    f.facility_code !='$mfl' AND f.using_hcmp = 1	
		    AND f.district = '$district_id'	       
		    AND UNIX_TIMESTAMP(f.`date_of_activation`) > 0
		ORDER BY f.facility_name ASC");
		
		return $q;	
	
	}
public static function get_facilities_all_per_district($county_id,$option=null){
	$new=isset($option)  ? "and f.facility_code not in (select f.facility_code from facilities f, districts d 
where f.district=d.id and d.id=$county_id and using_hcmp=1)" : null;
	$q = Doctrine_Manager::getInstance()->getCurrentConnection()->fetchAll("
select d.id, d.district,f.facility_name,f.facility_code
from facilities f, districts d 
where f.district=d.id and d.id='$county_id'  $new
order by f.facility_name asc
 ");
return $q;	
	
}
	
	public static function get_d_facility($district){
		'SELECT unique facilities.facility_name, user.fname, user.lname, user.telephone
			FROM facilities, user
			WHERE facilities.district ='.$district.'
			AND user.district ='.$district.'
			AND user.facility = facilities.facility_code
			AND user.usertype_id =2';
		
		$q = Doctrine_Manager::getInstance()->getCurrentConnection();
		$result = $q-> execute(" 
		SELECT user.email, 
			facilities.facility_code, facilities.facility_name, user.fname, user.lname, 
			user.telephone
				FROM facilities, user
				WHERE facilities.district =  '$district'
				AND user.district =  '$district'
				AND user.facility = facilities.facility_code
				AND user.status =  '1'
				AND (
				user.usertype_id =5
				OR user.usertype_id =2
				)
				GROUP BY user.facility");

		return $result;
		
	}
	
	/*************************getting the facility name *******************/
	public static function get_facility_name($facility_code)
	{
		$query = Doctrine_Query::create()->select('*')->from('facilities')->where("facility_code='$facility_code'");
		$result = $query -> execute();
		return $result;
	}	
	public static function get_facility_name2($facility_code)
	{
	$query = Doctrine_Query::create()->select('facility_name')->from('facilities')->where("facility_code='$facility_code'");
	$result = $query -> execute(array(), Doctrine::HYDRATE_ARRAY);
	return $result[0];
	}
	/********************getting the facility owners  count*************/
	public static function get_owner_count() {
		$query = Doctrine_Query::create() -> select("COUNT(facility_code) as count , owner ") 
		-> from("facilities")->where("owner !=''")->groupby("owner")->ORDERBY ('count ASC' );
		$drugs = $query -> execute();
		return $drugs;
	}
	
public static function get_no_of_facilities($category){
		$district = $category;
		$q = Doctrine_Manager::getInstance()->getCurrentConnection()->fetchAll("
SELECT COUNT(f.facility_code) as total_facilities
FROM facilities f, counties c, districts d
WHERE f.`district` = d.id
AND d.county = c.id
AND c.id =  $district ");
return $q;

}


public static function get_total_facilities_in_district($county_id){
	
		$q = Doctrine_Manager::getInstance()->getCurrentConnection()->fetchAll("
SELECT COUNT( f.facility_code ) as total_facilities
FROM facilities f, districts d
WHERE f.`district` = d.id
AND d.id =  '$county_id'
");
return $q;
}
public static function get_all_facilities_in_county($county_id){
	
		$q = Doctrine_Manager::getInstance()->getCurrentConnection()->fetchAll("
SELECT DISTINCT( f.facility_code ) as facilities
FROM facilities f, districts d
WHERE f.`district` = d.id
AND d.county =  '$county_id'
");
return $q;
}

public static function get_all_facilities_no(){
	$query = Doctrine_Manager::getInstance()->getCurrentConnection()->fetchAll("
		SELECT COUNT( f.id ) AS total_no_of_facilities
	FROM facilities f,districts d
	 where f.`district` = d.id
		");

	return $query;
}

public static function get_all_facilities_active_no(){
	$query = Doctrine_Manager::getInstance()->getCurrentConnection()->fetchAll("
		SELECT COUNT( f.id ) AS total_active_facilities
	FROM facilities f,districts d
	 where f.`district` = d.id AND f.using_hcmp = 1
		");

	return $query;
}

public static function get_total_facilities_district_ownership($county_id,$owner_type){
	
		$q = Doctrine_Manager::getInstance()->getCurrentConnection()->fetchAll("
SELECT COUNT( f.facility_code ) AS ownership_count
FROM facilities f, counties c, districts d
WHERE rtk_enabled =1
AND f.`district` = d.id
AND d.county = c.id
AND d.id =  '$county_id'
AND f.owner like '%$owner_type%'
");
return $q;
}

public static function get_facility_details($district = NULL,$county = NULL,$offline=null){
		$district = isset($district)? "AND d.id= $district": NULL;
		$county = isset($county)? "AND d.county = $county": NULL;
		$offline = isset($offline)? "AND f.using_hcmp != 2":NULL;
		$q = Doctrine_Manager::getInstance()->getCurrentConnection()->fetchAll("
SELECT DISTINCT f.id, f.facility_code, f.date_of_activation, f.facility_name, f.district, f.owner, c.county, d.district as district_name,f.using_hcmp
FROM facilities f, districts d, counties c
WHERE f.district = d.id
AND d.county=c.id
$district $county $offline");
return $q;
}
////////////////////////////////////////////////
public static function get_one_facility_details($facility_code){
	$facility_code = $facility_c;
		$q = Doctrine_Manager::getInstance()->getCurrentConnection()->fetchAll("
SELECT DISTINCT f.id, f.facility_code,f.date_of_activation, f.facility_name, f.district, f.owner, c.county, d.district as district_name
FROM facilities f, districts d, counties c
WHERE f.facility_code='$facility_code'
AND f.district=d.id
AND d.county=c.id");
return $q;

}
public static function get_drawingR_county_by_district(){
	
		$q = Doctrine_Manager::getInstance()->getCurrentConnection()->fetchAll("
SELECT * 
FROM (SELECT  `facility_code` ,  `facility_name` , facilities.`district` , SUM(  `drawing_rights` ) AS drawingR, districts.district AS districtName
FROM facilities, districts
WHERE facilities.district = districts.id
AND districts.county =1
GROUP BY facilities.`district`
) AS temp
WHERE temp.drawingR !=  'NULL'
");
return $q;
}

public static function get_county_drawing_rights($county_id){
	
		$q = Doctrine_Manager::getInstance()->getCurrentConnection()->fetchAll("
SELECT SUM( f.`drawing_rights` ) AS initial_drawing_rights, SUM( f.`drawing_rights_balance` ) AS drawing_rights_balance, d.`district` 
FROM facilities f, districts d, counties c
WHERE f.district = d.id
AND d.county = c.id
AND c.id =$county_id
GROUP BY d.id
order by d.district asc
");
return $q;	

}

public static function get_orders_made_in_district($district_id){
		$q_1 = Doctrine_Manager::getInstance()->getCurrentConnection()->fetchAll("SELECT count( f.`id` ) AS total_no_of_facilities
FROM facilities f, districts d
WHERE f.district = d.id
AND d.id =$district_id
");


		$q = Doctrine_Manager::getInstance()->getCurrentConnection()->fetchAll("SELECT COUNT( f.`id` ) AS orders_made_data
FROM facilities f, districts d,ordertbl o
WHERE f.district = d.id
AND d.id =$district_id
AND o.facilityCode=f.facility_code
");


return array('total_no_of_facilities'=>$q_1[0]['total_no_of_facilities'],'orders_made_data'=>$q[0]['orders_made_data']);	
}
public static function get_no_of_facilities_hcmp($county_id=NULL,$district_id=NULL)
{
	if($district_id!=NULL){
		$q_1 = Doctrine_Manager::getInstance()->getCurrentConnection()->fetchAll("
	SELECT COUNT( f.id ) AS total_no_of_facilities
	FROM facilities f, districts d
	WHERE f.district = d.id
	AND d.id ='$district_id'
	");

		$q = Doctrine_Manager::getInstance()->getCurrentConnection()->fetchAll("
		SELECT COUNT( DISTINCT u.`facility` ) AS total_no_of_facilities
		FROM facilities f, districts d, user u
		WHERE f.district = d.id
		AND u.facility = f.facility_code
		AND u.status =  '1'
		AND d.id= '$district_id'
		");
	
	}else{
		$q_1 = Doctrine_Manager::getInstance()->getCurrentConnection()->fetchAll("
	SELECT count(f.`id`) AS total_no_of_facilities
	FROM facilities f, districts d, counties c
	WHERE f.district = d.id
	AND d.county =c.id
	AND c.id= '$county_id'
	");

	$q = Doctrine_Manager::getInstance()->getCurrentConnection()->fetchAll("
	SELECT COUNT( DISTINCT u.`facility` ) AS total_no_of_facilities
	FROM facilities f, districts d, counties c, user u
	WHERE f.district = d.id
	AND u.facility = f.facility_code
	AND d.county = c.id
	AND u.status =  '1'
	AND c.id= '$county_id'
	");
}

return array('total_no_of_facilities'=>$q_1[0]['total_no_of_facilities'],'total_no_of_facilities_using_hcmp'=>$q[0]['total_no_of_facilities']);
}

public static function get_facility_status_no_users_status($facility_code){
$q = Doctrine_Manager::getInstance()->getCurrentConnection()->fetchAll('
SELECT COUNT( DISTINCT u.id ) AS number_of_users, COUNT(DISTINCT l.user_id ) AS number_of_users_online, 
CASE 
WHEN EXISTS (
SELECT u.id 
FROM user
WHERE user.facility ="'.$facility_code.'")
THEN "Active"
ELSE "Inactive"
END AS 
status
 FROM user u
LEFT JOIN log l ON u.id = l.user_id AND l.action = "Logged In"
WHERE u.facility ="'.$facility_code.'"
');	

return $q;
}
	//Used by facility_mapping function reports controller
	//Used to get the months facilities went online
	//Limits to the last 3 months of activation
	public static function get_dates_facility_went_online($county_id, $district_id = null)
	{
		$addition = (isset($district_id)&& ($district_id>0)) ?"AND f.district = $district_id" : null;
		
		$q = Doctrine_Manager::getInstance()->getCurrentConnection()->fetchAll("
		SELECT DISTINCT
		    DATE_FORMAT(`date_of_activation`, '%M %Y') AS date_when_facility_went_online
		FROM
		    facilities f,
		    districts d
		WHERE
		    f.district = d.id
		        AND d.county = $county_id
		        $addition
		        AND UNIX_TIMESTAMP(`date_of_activation`) > 0
		ORDER BY `date_of_activation` desc
		");
		return $q;
			
	}
	

		public static function get_dates_facility_went_online_cleaned($county_id)
	{
		$data = Doctrine_Manager::getInstance()->getCurrentConnection()->fetchAll("
		SELECT DISTINCT
		    DATE_FORMAT(`date_of_activation`, '%M %Y') AS date_when_facility_went_online,
		    YEAR(`date_of_activation`) 
		FROM
		    facilities f,
		    districts d
		WHERE
		    f.district = d.id
		        AND d.county = $county_id
		        $addition
		        AND UNIX_TIMESTAMP(`date_of_activation`) > 0
		ORDER BY `date_of_activation` desc
		");
		$cleaned_data = array();
		foreach ($data as $key => $value) {
			$year = $value['YEAR(`date_of_activation`)'];
			if($year = $value['YEAR(`date_of_activation`)'])
			{
				$cleaned_data[$year][] = $value['date_when_facility_went_online'];
			}
		}
		
		return $cleaned_data;
			
	}
	//used by facility mapping function
	//used to get the distinct years facilities went online
	//used when building data for the facilities that went online in a particular district in a particular county
	public static function get_facilities_which_went_online_($district_id = null, $date_of_activation)
	{
		$q = Doctrine_Manager::getInstance()->getCurrentConnection()->fetchAll("
		SELECT 
			(select count(targetted) from facilities f 	where f.district='$district_id' and f.targetted = 1) as total_facilities_targetted, 
			(select count(facility_name) from facilities f where f.district = $district_id and using_hcmp=1) as total_using_hcmp,
			count(facility_code) as total ,
			(select count(id) from facilities f where f.district='$district_id') as total_facilities 
		from 
			facilities f, 
			districts d 
		where 
			f.district = d.id 
			and d.id = $district_id 
			and DATE_FORMAT(  `date_of_activation` ,  '%M %Y' ) = '$date_of_activation' AND using_hcmp=1");
			
			return $q;
	}
public static function get_targetted_facilities($district_id = null)
{
	$q = Doctrine_Manager::getInstance()->getCurrentConnection()->fetchAll("
	SELECT 
    	count(targetted) as total_facilities_targetted
	from
	    facilities f
	where
	    f.district = '$district_id' and f.targetted = 1
		");
				
		return $q;		
}
public static function get_facilities_reg_on_($district_id,$date_of_activation){
	
		$query = Doctrine_Query::create() -> select("*") -> from("facilities f, districts d")->where("f.district=d.id and d.id ='$district_id'")
		
		->andwhere("date_format(`date_of_activation`, '%M %Y')='$date_of_activation' and unix_timestamp(`date_of_activation`) >0");
		
		$facilities = $query -> execute();
		
		return $facilities;
	}

// getting facilities which are using the system

public static function get_total_facilities_rtk_in_district($district_id){
	
		$q = Doctrine_Manager::getInstance()->getCurrentConnection()->fetchAll("
		SELECT  f.facility_code , f.owner as facility_owner,f.facility_name
		FROM facilities f, districts d
		WHERE rtk_enabled =1
		AND d.id='$district_id'
		AND f.`district` = '$district_id'");
return $q;
}

//getting facility name without Doctrine
public static function get_facility_details_simple($facility_code){
	$mbegu = Doctrine_Manager::getInstance()->getCurrentConnection->fetchAll("
		SELECT * from facilities WHERE facility_code = $facility_code
		");
	return $mbegu;
	}

//get facility county and district4
public static function get_facility_district_county_level($facility_code)
{
	// echo $facility_code;die;
	$q = Doctrine_Manager::getInstance()->getCurrentConnection()->fetchAll("
		SELECT d.district as district, c.county as county, f.type as type FROM
		facilities f, districts d, counties c
		WHERE f.facility_code = '$facility_code'
		AND f.district = d.id
		AND c.id = d.county");

	return $q[0];
}

public static function model_tester(){
	$data = Doctrine_Manager::getInstance()->getCurrentConnection()->fetchAll("
             CALL facility_monitoring_new1('county','23','last_seen');
			");
	return $data;
}

}


