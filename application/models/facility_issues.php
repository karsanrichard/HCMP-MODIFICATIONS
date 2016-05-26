<?php
/**
 * @author Kariuki
 */
class facility_issues extends Doctrine_Record {
		
	public function setTableDefinition()
	{
		$this -> hasColumn('id', 'int');
		$this -> hasColumn('facility_code', 'int'); 
		$this -> hasColumn('s11_No', 'int'); 
		$this -> hasColumn('commodity_id', 'int'); 
	    $this -> hasColumn('batch_no', 'varchar',20); 
		$this -> hasColumn('expiry_date', 'date'); 
		$this -> hasColumn('qty_issued', 'int');
		$this -> hasColumn('balance_as_of', 'int'); 
		$this -> hasColumn('adjustmentpve', 'int');
		$this -> hasColumn('adjustmentnve', 'int');
		$this -> hasColumn('date_issued', 'date'); 
		$this -> hasColumn('issued_to', 'varchar', 200);
		$this -> hasColumn('issued_by', 'int', 12);
		$this -> hasColumn('status', 'int');
			
	}
	public function setUp() {
		$this -> setTableName('facility_issues');		
		$this -> hasMany('commodities as commodity_detail', array('local' => 'commodity_id', 'foreign' => 'id'));
	
	}//get all the data from the db
	public static function get_all() {
		$query = Doctrine_Query::create() -> select("*") -> from("facility_issues");
		$commodities = $query -> execute();
		return $commodities;
	}

	public function get_all_issue_data() {
		$query = Doctrine_Manager::getInstance()->getCurrentConnection()->fetchAll("SELECT id,issued_to,facility_code FROM facility_issues ORDER BY id");

		return $query;
	}

	public function get_all_service_points() {
		$query = Doctrine_Manager::getInstance()->getCurrentConnection()->fetchAll("SELECT id,service_point_name,facility_code FROM service_points ORDER BY id");

		return $query;
	}

	public function get_one_service_points($id) {
		$query = Doctrine_Manager::getInstance()->getCurrentConnection()->fetchAll("SELECT id,service_point_name,facility_code FROM service_points where id='$id' ORDER BY id");

		return $query;
	}
	
   ////dumbing data into the issues table
	public static function update_issues_table($data_array){
		$o = new facility_issues();
	    $o->fromArray($data_array);
		$o->save();
		return TRUE;
	}
   public static function get_last_time_facility_issued($facility_code){
        $transaction = Doctrine_Manager::getInstance()->getCurrentConnection()->fetchAll("
        SELECT 
		    u.fname,
		    u.lname,
		    max(f_i.`created_at`) as last_seen,
		    DATEDIFF(now(), max(f_i.`created_at`)) as days_
		from
		    user u,
		    facility_issues f_i
		where
		    f_i.`issued_by` = u.id
		        and f_i.facility_code = $facility_code"); 

    return $transaction[0];    
   }
   //The function gets the consumption for the email consumption report
   public static function get_consumption_report_facility($facility_code)
   {
   	$date_ = date("m Y");
   	  $consumption = Doctrine_Manager::getInstance()->getCurrentConnection()->fetchAll("
   	  SELECT 
		    cms.commodity_name,
		    cms.total_commodity_units as unit_size,
		    cms.unit_cost,
		    cs.source_name,
		    f.facility_name,
		    f.facility_code,
		    di.district,
		    c.county,
		    MONTHNAME(fs.date_issued) as month,
		    fs.qty_issued AS total_units,
		    (fs.qty_issued * cms.unit_cost) as total_cost,
		    CEIL(fs.qty_issued / cms.total_commodity_units) AS total_packs
		FROM
		    facility_issues fs,
		    commodities cms,
		    facilities f,
		    districts di,
		    commodity_source cs,
		    counties c
		WHERE
		    fs.facility_code = f.facility_code
		        AND f.facility_code = $facility_code
		        AND fs.qty_issued > 0
		        AND f.district = di.id
		        AND di.county = c.id
		        AND cs.id = cms.commodity_source_id
		        AND fs.status = '1'
		        AND DATE_FORMAT(fs.date_issued, '%m %Y') = '$date_'
		        AND cms.id = fs.commodity_id
		GROUP BY cms.commodity_name asc"); 

    return $consumption; 
	   
   }

   public function get_issues_for_reversals(){   		
   	  $issues = Doctrine_Manager::getInstance()->getCurrentConnection()->fetchAll("
   	  select f.facility_name,  f_i.facility_code,f_i.created_at,f_i.issued_by,u.fname, u.lname from facilities f,facility_issues f_i,user u where f.facility_code = f_i.facility_code 
   	  and f_i.s11_No ='internal issue'  AND u.id = f_i.issued_by
   	  group by created_at, issued_by, facility_code order by created_at desc limit 0,15");

        return $issues;
   }

   public function get_facility_issues_for_reversals($facility_code,$start_date){
   	 $issues = Doctrine_Manager::getInstance()->getCurrentConnection()->fetchAll("SELECT distinct f.facility_name, f_i.issued_to,f_i.s11_No,f_i.facility_code,f_i.issued_by,f_i.created_at, u.fname, u.lname, f_i.commodity_id,c.commodity_name,f_i.batch_no,f_i.qty_issued,f_i.date_issued
		FROM  facilities f, facility_issues f_i, user u, commodities c WHERE   f.facility_code = f_i.facility_code AND f_i.s11_No = 'internal issue'
        AND u.id = f_i.issued_by  AND f.facility_code = '$facility_code'  AND c.id = f_i.commodity_id  and f_i.status = '1' 
        and f_i.created_at between '$start_date' and NOW()
		ORDER BY f_i.date_issued DESC");
        return $issues;
   }

    public function get_facility_issues_reversals($facility_code,$start_date){
   	 $issues = Doctrine_Manager::getInstance()->getCurrentConnection()->fetchAll("SELECT distinct f.facility_name, f_i.issued_to,f_i.facility_code,f_i.issued_by,f_i.created_at, u.fname, u.lname, f_i.commodity_id,c.commodity_name,f_i.batch_no,f_i.qty_issued,f_i.date_issued
		FROM  facilities f, facility_issues f_i, user u, commodities c WHERE   f.facility_code = f_i.facility_code AND f_i.s11_No = 'reversed issue'
        AND u.id = f_i.issued_by  AND f.facility_code = '$facility_code'  AND c.id = f_i.commodity_id  and f_i.status = '1' 
        and f_i.created_at between '$start_date' and NOW()
		ORDER BY f_i.created_at DESC");
        return $issues;
   }

	public function get_redistributions_for_reversals(){   		
   	  $redistributions = Doctrine_Manager::getInstance()->getCurrentConnection()->fetchAll("SELECT f.facility_name,r.source_facility_code as facility_code,r.receive_facility_code,r.date_sent, 
   	  	u.fname, u.lname,r.sender_id FROM  facilities f,  redistribution_data r, user u WHERE  f.facility_code = r.source_facility_code AND u.id = r.sender_id
		GROUP BY date_sent , sender_id ,r.source_facility_code ORDER BY date_sent DESC;");

        return $redistributions;
   }

    public function get_issue_details_for_reversals($facility_code,$time,$issuer){   		
   	  $issues = Doctrine_Manager::getInstance()->getCurrentConnection()->fetchAll("SELECT f.facility_name,f_i.*, c.commodity_name FROM facility_issues f_i, commodities c,facilities f 
   	  	where f_i.facility_code = '$facility_code' and f.facility_code = f_i.facility_code and f_i.created_at='$time' and f_i.issued_by='$issuer' and f_i.s11_No ='internal issue' and f_i.commodity_id = c.id");

      return $issues;
   }
   public function get_facility_issue_details_for_reversals($facility_code,$commodity_id,$time,$issuer){   		
   	  $issues = Doctrine_Manager::getInstance()->getCurrentConnection()->fetchAll("SELECT distinct* FROM hcmp_rtk.facility_issues 
   	  	where facility_code = '$facility_code' and commodity_id = '$commodity_id' and created_at = '$time' and issued_by = '$issuer' and status='1' LIMIT 0,1");
      return $issues;
   }

   public function get_reversal_details_for_reversals($facility_code,$time,$issuer){   		
   	  $redistributions = Doctrine_Manager::getInstance()->getCurrentConnection()->fetchAll("SELECT f.facility_name,r.*, c.commodity_name FROM redistribution_data r, commodities c,facilities f 
	  	where r.source_facility_code = '$facility_code' and f.facility_code = r.source_facility_code and r.date_sent='$time' and r.sender_id='$issuer' and r.commodity_id = c.id");
      return $redistributions;
   }

   public function get_reversed_issues(){   		
   	  $reversals = Doctrine_Manager::getInstance()->getCurrentConnection()->fetchAll("SELECT f.facility_name, r.facility_code,r.created_at, u.fname, u.lname,r.issued_by FROM
    	facilities f, reversals r, user u WHERE    f.facility_code = r.facility_code AND r.s11 = 'internal issue' AND u.id = r.issued_by GROUP BY created_at , issued_by , facility_code ORDER BY created_at DESC");
       return $reversals;
   }

   public function get_undo_issue_details_for_reversals($facility_code,$time,$issuer){   		
   	  $reversed_details = Doctrine_Manager::getInstance()->getCurrentConnection()->fetchAll("SELECT f.facility_name,r.*, c.commodity_name FROM reversals r, commodities c,facilities f 
   	  	where r.facility_code = '$facility_code' and f.facility_code = r.facility_code and r.created_at='$time' 
        and r.issued_by='$issuer' and r.s11 ='internal issue' and r.commodity_id = c.id and r.reversal_status = '1'");

      return $reversed_details;
   }


    //The function gets the consumption for the email consumption report
    public static function get_consumption_report_ors_zinc($commodities)
    {
        $date_ = date("Y");
        $consumption = Doctrine_Manager::getInstance()->getCurrentConnection()->fetchAll("
   	  SELECT
            cms.commodity_name,
            cms.total_commodity_units AS unit_size,
            cms.unit_cost,
            fs.batch_no,
            cs.source_name,
            f.facility_name,
            f.facility_code,
            di.district,
            c.county,
            MONTHNAME(fs.date_issued) AS month,
            fs.qty_issued AS total_units,
            (fs.qty_issued * cms.unit_cost) AS total_cost,
            CEIL(fs.qty_issued / cms.total_commodity_units) AS total_packs
        FROM
            facility_issues fs,
            commodities cms,
            facilities f,
            districts di,
            commodity_source cs,
            counties c
        WHERE
            cms.id = $commodities
                AND fs.facility_code = f.facility_code
                AND fs.qty_issued > 0
                AND f.district = di.id
                AND di.county = c.id
                AND cs.id = cms.commodity_source_id
                AND fs.status = '1'
                AND DATE_FORMAT(fs.date_issued, '%Y') = '$date_'
                AND cms.id = fs.commodity_id
        GROUP BY fs.batch_no , cms.id , f.facility_code
        ORDER BY c.county ASC , di.district ASC");

        return $consumption;

    }
	public static function get_bin_card($facility_code,$commodity_id,$from,$to){

		$convertfrom=date('Y-m-d',strtotime($from ));
		$convertto=date('Y-m-d',strtotime($to ));

// 		echo "SELECT f.date_issued, f.expiry_date, f.batch_no, c.unit_size, f.s11_No, f.balance_as_of,
//  f.adjustmentnve, f.adjustmentpve, f.qty_issued, u.fname, u.lname, f.issued_to AS service_point_name
// FROM facility_issues f
// INNER JOIN user u on f.issued_by = u.id
// INNER JOIN commodities c on c.id = f.commodity_id
// WHERE f.facility_code = $facility_code AND f.status = 1 
// AND f.commodity_id = $commodity_id AND f.date_issued 
// BETWEEN '$convertfrom' AND '$convertto' ORDER BY f.created_at ASC";exit;
	$transaction = Doctrine_Manager::getInstance()->getCurrentConnection()
	-> fetchAll("SELECT f.date_issued, f.expiry_date, f.batch_no, c.unit_size, f.s11_No, f.balance_as_of,
 f.adjustmentnve, f.adjustmentpve, f.qty_issued, u.fname, u.lname, f.issued_to AS service_point_name
FROM facility_issues f
INNER JOIN user u on f.issued_by = u.id
INNER JOIN commodities c on c.id = f.commodity_id
WHERE f.facility_code = $facility_code AND f.status in (1,3) 
AND f.commodity_id = $commodity_id AND f.date_issued 
BETWEEN '$convertfrom' AND '$convertto' ORDER BY f.date_issued ASC"); 
		


			return $transaction;	
	}
	public static function get_distinct_batch($facility_code,$commodity_id,$from,$to){

		$convertfrom=date('Y-m-d',strtotime($from ));
		$convertto=date('Y-m-d',strtotime($to ));

	$transaction = Doctrine_Manager::getInstance()->getCurrentConnection()
	-> fetchAll("SELECT DISTINCT f.batch_no
FROM facility_issues f
INNER JOIN user u on f.issued_by = u.id
INNER JOIN commodities c on c.id = f.commodity_id
WHERE f.facility_code = $facility_code AND f.status = 1 
AND f.commodity_id = $commodity_id AND f.date_issued 
BETWEEN '$convertfrom' AND '$convertto' ORDER BY f.created_at ASC"); 
		


			return $transaction;	
	}
	public static function get_active_facilities_in_district($district)
	{
	 	$query = Doctrine_Manager::getInstance()->getCurrentConnection()
		->fetchAll("SELECT f.`facility_name` , count( fi.`facility_code` ) AS issue_count
			FROM facilities f, facility_issues fi
			WHERE fi.`facility_code` = f.`facility_code`
			AND fi.`availability` =1
			AND f.`district` = '$district'
			GROUP BY fi.`facility_code`
			ORDER BY issue_count DESC , f.`facility_name` ASC
			LIMIT 0 , 5 ");
        return $query ;
	}
	
	public static function get_inactive_facilities_in_district($district)
	{
		$query = Doctrine_Manager::getInstance()->getCurrentConnection()
		->fetchAll("SELECT f.`facility_name`
			FROM facilities f, user u
			WHERE u.`facility` != f.`facility_code`
			AND f.`district` = '$district'
			GROUP BY f.`facility_code`
			ORDER BY f.`facility_name` ASC");
		
		return $query ;
	}

	public static function get_service_point_stocks($facility_code,$service_point,$commodity_id){
		$query = Doctrine_Manager::getInstance()->getCurrentConnection()->fetchAll(
			"SELECT * FROM service_point_stocks WHERE facility_code = $facility_code AND service_point_id = $service_point AND commodity_id = $commodity_id"
			);

		return $query;
	}
}