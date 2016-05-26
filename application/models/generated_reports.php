<?php
/**
 * @author Karsan
 */

class Generated_reports extends Doctrine_Record {

public function county_order_reports_by_year($year = NULL,$month = NULL,$order = NULL){
	// echo $commodity_id;exit;
	$and_vibe = isset($year)? " AND DATE_FORMAT(f_o.order_date, '%Y') = $year":NULL;
	$and_vibe .= isset($month)? " AND DATE_FORMAT(f_o.order_date, '%M') = '$month'":NULL;

	$order_vibe = (isset($order) && ($order = 'months'))? "ORDER BY FIELD(`month_of_order`,'January','February','March','April','May','June','July','August','September','October','November','December'),`year_of_order` ASC": "ORDER BY `year_of_order` DESC";

	$query = Doctrine_Manager::getInstance()->getCurrentConnection()->fetchAll(
		"
		SELECT 
		f.district AS district_id,
		d.district AS district_name,
		DATE_FORMAT(f_o.order_date, '%M') AS month_of_order,
        DATE_FORMAT(f_o.order_date, '%Y') AS year_of_order,
        f_o.order_date AS latest_order_date,
		SUM(f_o.order_total) AS order_totals
        FROM facilities f,districts d,facility_orders f_o
		LEFT JOIN facility_order_details f_o_d	
		ON f_o_d.order_number_id = f_o.id
        
        WHERE d.id = f.district AND f.facility_code = f_o.facility_code $and_vibe
        
       	GROUP BY d.district
        
        $order_vibe
		"
		);

	return $query; 
}//end of get order reports by year

public function county_order_reports_count($district_id = NULL,$year = NULL){
	// echo $commodity_id;exit;
	$and_vibe = isset($district_id)? " AND f.district = $district_id":NULL;
	$and_vibe .= isset($year)? " AND DATE_FORMAT(f_o.order_date, '%Y') = $year":NULL;
	$query = Doctrine_Manager::getInstance()->getCurrentConnection()->fetchAll(
		"
		SELECT 	COUNT(f_o.id) AS order_count,
		f.district AS district_id,
		d.district AS district_name,
        DATE_FORMAT(f_o.order_date, '%Y') AS year_of_order
        FROM facilities f,districts d,facility_orders f_o
		LEFT JOIN facility_order_details f_o_d	
		ON f_o_d.order_number_id = f_o.id
        WHERE d.id = f.district AND f.facility_code = f_o.facility_code $and_vibe
        
       	GROUP BY d.district
        
        ORDER BY `year_of_order` DESC
		
		"
		);

	return $query; 
}//end of get order reports

/*
public function redistribution_reports($commodity_id){
	$and_vibe = isset($commodity_id)? "":NULL;

	$query = Doctrine_Manager::getInstance()->getCurrentCOnnection->fetchAll("
		");

	$result = $query->execute();

	return $result;
}//end of redistribution

*/
}//end of class generated reports