
<?php

class redistribution_data extends Doctrine_Record {
	public function setTableDefinition() {
		$this -> hasColumn('id', 'int');
		$this -> hasColumn('source_facility_code', 'int');
		$this -> hasColumn('receive_facility_code', 'int');
		$this -> hasColumn('commodity_id', 'int');
		$this -> hasColumn('quantity_sent',  'int');
		$this -> hasColumn('quantity_received',  'int');
		$this -> hasColumn('sender_id',  'int');
		$this -> hasColumn('receiver_id',  'int');
		$this -> hasColumn('manufacturer', 'varchar', 100);
		$this -> hasColumn('batch_no', 'varchar', 100);
		$this -> hasColumn('expiry_date', 'date');
		$this -> hasColumn('date_sent', 'date');
		$this -> hasColumn('date_received', 'date');
		$this -> hasColumn('facility_stock_ref_id', 'int');
		$this -> hasColumn('status', 'int');
	}

	public function setUp() {
		$this -> setTableName('redistribution_data');
		$this->hasMany('facility_stocks as stock_detail', array('local' => 'facility_stock_ref_id', 'foreign' => 'id'));
		$this->hasMany('facilities as facility_detail_source', array('local' => 'source_facility_code', 'foreign' => 'facility_code'));
		$this->hasMany('facilities as facility_detail_receive', array('local' => 'receive_facility_code', 'foreign' => 'facility_code'));
	}

	public static function get_all() {
		$query = Doctrine_Query::create() -> select("*") -> from("redistribution_data");
		$redistribution_data = $query -> execute();
		return $redistribution_data;
	}
	public static function get_all_active($facility_code,$option=null) {
		$and=($option=='to-me')? " receive_facility_code=$facility_code" : " source_facility_code=$facility_code";
	
		$query = Doctrine_Query::create() -> select("*") -> from("redistribution_data") -> where("$and and status=0");
		$redistribution_data = $query -> execute();
		return $redistribution_data;
	}
  public function get_all_received($facility_code) {  
    $sql = "select f.facility_name as facility_name,c.commodity_name,c.commodity_code,c.total_commodity_units, r.* from receive_redistributions r, facilities f,commodities c where f.facility_code = r.sending_facility and c.id = r.commodity_id and r.status = 1 and r.facility_code = '$facility_code'";
    $received_data = $this->db->query($sql)->result_array();
    return $received_data;
  }

    public function get_one($id){
        $sql = "SELECT quantity_sent, facility_stock_ref_id as stock_id FROM redistribution_data where id ='$id'";
        return $this->db->query($sql)->result_array();
    }

    public  function get_all_active_edit($facility_code,$option=null) {
        $and=($option=='to-me')? " receive_facility_code=$facility_code" : " source_facility_code=$facility_code";
    
        $sql = "SELECT r.*, c.commodity_name,c.total_commodity_units,f.facility_name as receiving_facility,c.commodity_code,c.unit_size,fs.current_balance,d.district as district_name, d.id as receiver_district_id   FROM redistribution_data r, facility_stocks fs, commodities c,facilities f, districts d  WHERE fs.commodity_id = r.commodity_id AND r.source_facility_code = '$facility_code'
                AND fs.facility_code = '$facility_code'  AND r.commodity_id = c.id AND d.id = f.district AND f.facility_code = r.source_facility_code  AND r.batch_no = fs.batch_no  AND r.status = 0 GROUP BY r.batch_no, r.id";        
        return $this->db->query($sql)->result_array();
    }

    public static function get_all_active_edit_old($facility_code) {        
        $query = Doctrine_Query::create() -> select("r.*, c.commodity_name,fs.current_balance") -> from("redistribution_data r, facility_stocks fs, commodities c") -> where("fs.commodity_id = r.commodity_id AND r.source_facility_code = '$facility_code' AND fs.facility_code = '$facility_code' AND r.commodity_id = c.id  AND r.batch_no = fs.batch_no AND r.status = 0");
        
         $query=Doctrine_Manager::getInstance()->getCurrentConnection()->fetchAll("SELECT 
                r.*, c.commodity_name,fs.current_balance  FROM redistribution_data r, facility_stocks fs, commodities c
                WHERE fs.commodity_id = r.commodity_id AND r.source_facility_code = '$facility_code'
                AND fs.facility_code = '$facility_code'
                AND r.commodity_id = c.id
                AND r.batch_no = fs.batch_no
                    AND r.status = 0
                    GROUP By r.batch_no");  

            return $query;  
    }

		public static function get_all_active_drug_store($district_id,$option=null) {
		$and=($option=='to-me')? " receive_facility_code=2":" source_facility_code=2";
		$query = Doctrine_Query::create() -> select("*") -> from("redistribution_data") -> where("$and and status=0 and source_district_id = $district_id");
		$redistribution_data = $query -> execute();
		return $redistribution_data;
	}
	
    public function get_all_active_drug_store_county($county_id, $option = null){
        $and_data = ($option == 'to-me') ? "receive_facility_code = 4" : "source_facility_code = 4";
        $query = Doctrine_Query::create() -> select("*") -> from("redistribution_data") -> where("$and and status=0 and source_county_id = $county_id");
        return $in_county_donations;
    }
	public static function get_redistribution_data($facility_code,$district_id,$county_id,$year){
	 $and_data .=(isset($district_id)&& ($district_id>0)) ?"AND d.id = '$district_id'" : null;
	 $and_data .=(isset($facility_code)&& ($facility_code>0)) ?" AND f.facility_code = '$facility_code'" : null;
     $and_data .=($county_id>0 && $district_id<1) ?" AND d.county='$county_id'" : null;
	 $and_data .=isset($year) ?" AND year(r_d.`date_sent`)='$year'" : null;

     $query=Doctrine_Manager::getInstance()->getCurrentConnection()->fetchAll("
     select temp1.fname as sender_name, temp1.fname as receiver_name, 
     r_d.`source_facility_code`, f.facility_name as source_facility_name,d.district as source_district, temp3.facility_name as receiver_facility_name, 
     temp3.facility_code as receiver_facility_code,temp4.district as receiver_district,  c.`id`, c.commodity_name,c.commodity_code, c.unit_size, c.unit_cost, 
     c.total_commodity_units, r_d.`quantity_sent`, r_d.`quantity_received`, r_d.`manufacturer`,
     r_d.`batch_no`, r_d.`expiry_date`, r_d.`status`, r_d.`date_sent`, r_d.`date_received` 
     from facilities f, commodities c, districts d, redistribution_data r_d  
     left join user  as temp1 on temp1.id=r_d.`sender_id` 
     left join user  as temp2 on temp2.id=r_d.`receiver_id` 
     left join facilities as temp3 on temp3.facility_code=r_d.`receive_facility_code`  
     left join districts as temp4 ON temp4.id = temp3.district
      where r_d.commodity_id=c.id and r_d.`source_facility_code`=f.facility_code and f.district=d.id $and_data ");	

	return $query;	
	}
    public static function get_redistribution_data_ors_zinc($commodities){
        $query=Doctrine_Manager::getInstance()->getCurrentConnection()
            ->fetchAll("SELECT
                            temp1.fname AS sender_name_fname,
                            temp1.lname AS sender_name_lname,
                            temp2.fname AS receiver_name_fname,
                            temp2.lname AS receiver_name_lname,
                            r_d.`source_facility_code`,
                            f.facility_name AS source_facility_name,
                            d.district AS source_district,
                            temp3.facility_name AS receiver_facility_name,
                            temp3.facility_code AS receiver_facility_code,
                            temp4.district AS receiver_district,
                            c.`id`,
                            c.commodity_name,
                            c.commodity_code,
                            c.unit_size,
                            c.unit_cost,
                            c.total_commodity_units,
                            r_d.`quantity_sent`,
                            r_d.`quantity_received`,
                            r_d.`manufacturer`,
                            r_d.`batch_no`,
                            r_d.`expiry_date`,
                            r_d.`status`,
                            r_d.`date_sent`,
                            r_d.`date_received`
                        FROM
                            facilities f,
                            commodities c,
                            districts d,
                            redistribution_data r_d
                                LEFT JOIN
                            user AS temp1 ON temp1.id = r_d.`sender_id`
                                LEFT JOIN
                            user AS temp2 ON temp2.id = r_d.`receiver_id`
                                LEFT JOIN
                            facilities AS temp3 ON temp3.facility_code = r_d.`receive_facility_code`
                                LEFT JOIN
                            districts AS temp4 ON temp4.id = temp3.district
                        WHERE
                            r_d.commodity_id = c.id AND c.id = 51
                                AND r_d.`source_facility_code` = f.facility_code
                                AND f.district = d.id
                                            ");

        return $query;
    }
	public static function get_redistribution_pending($facility_code,$district_id,$county_id,$year){
	 $and_data .=(isset($district_id)&& ($district_id>0)) ?"AND d.id = '$district_id'" : null;
	 $and_data .=(isset($facility_code)&& ($facility_code>0)) ?" AND f.facility_code = '$facility_code'" : null;
     $and_data .=($county_id>0) ?" AND d.county='$county_id'" : null;
	 $and_data .=isset($year) ?" AND year(r_d.`date_sent`)='$year'" : null;
	
     $query=Doctrine_Manager::getInstance()->getCurrentConnection()->fetchAll("
     select temp1.fname as sender_name, temp1.fname as receiver_name, 
     r_d.`source_facility_code`, f.facility_name as source_facility_name,d.district as source_district, temp3.facility_name as receiver_facility_name, 
     temp3.facility_code as receiver_facility_code,temp4.district as receiver_district,  c.`id`, c.commodity_name,c.commodity_code, c.unit_size, c.unit_cost, 
     c.total_commodity_units, r_d.`quantity_sent`, r_d.`quantity_received`, r_d.`manufacturer`,
     r_d.`batch_no`, r_d.`expiry_date`, r_d.`status`, r_d.`date_sent`, r_d.`date_received` 
     from facilities f, commodities c, districts d, redistribution_data r_d  
     left join user  as temp1 on temp1.id=r_d.`sender_id` 
     left join user  as temp2 on temp2.id=r_d.`receiver_id` 
     left join facilities as temp3 on temp3.facility_code=r_d.`receive_facility_code`  
     left join districts as temp4 ON temp4.id = temp3.district
      where r_d.commodity_id=c.id and r_d.`source_facility_code`=f.facility_code and f.district=d.id and r_d.`status`=0 and temp3.facility_name IS NOT NULL $and_data ");	

	return $query;	
	}

    public static function get_redistribution_mismatches_count($facility_code){
        $mismatch_count = Doctrine_Manager::getInstance()->getCurrentConnection()->fetchAll("
            SELECT COUNT(*) AS ms_count
            FROM redistribution_data rd
            WHERE (rd.quantity_received < rd.quantity_sent OR rd.quantity_received > rd.quantity_sent)
            AND rd.source_facility_code = '$facility_code';
        ");
        $count = 0;
        foreach ($mismatch_count as $key => $value) {
            $count = $value['ms_count'];
        }
        return $count;
    }

    public static function get_redistribution_mismatches_source($facility_code){
        $mismatch_data = Doctrine_Manager::getInstance()->getCurrentConnection()->fetchAll("
            SELECT * 
            FROM redistribution_data rd
            WHERE (rd.quantity_received < rd.quantity_sent OR rd.quantity_received > rd.quantity_sent)
            AND rd.source_facility_code = '$facility_code';
        ");
        return $mismatch_data;
    }

    public static function get_redistribution_mismatches_receive($facility_code){
        $mismatch_data = Doctrine_Manager::getInstance()->getCurrentConnection()->fetchAll("
            SELECT * 
            FROM redistribution_data rd
            WHERE (rd.quantity_received < rd.quantity_sent OR rd.quantity_received > rd.quantity_sent)
            AND rd.receive_facility_code = '$facility_code';
        ");
        return $mismatch_data;
    }
}
?>
