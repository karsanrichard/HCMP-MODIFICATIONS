<?php //echo "<pre>";print_r($service_point);exit; ?>
<style type="text/css">
.row div p,.row-fluid div p{
	padding:10px;

}
.form-control {

font-size: 12px !important;
}
</style>
<link href="<?php echo base_url().'assets/bower_components/intro.js/introjs.css'?>" type="text/css" rel="stylesheet"/>
<div class="container-fluid" style="">

	<div class="row">
		<div class="col-md-6" id=""><p class="bg-info"><span class="badge ">1</span>
		<strong> Enter the S11 if available,select Service point,Commodity you wish to issue then 
		 Batch No,select issue type and quantity issued and input date issued.</strong></p></div>
		<div class="col-md-6" id="">
			<p class="text-danger bg-warning"><span class="badge ">NB</span> Available Batch Stock <strong>(Units)</strong> is for a specific 
	batch, Total Balance <strong>(Units)</strong> is the total for the commodity</p>
		
	</div>
	<span><a href="<?php echo base_url().'issues/generate_issue_excel'?>" target="_blank">Download Facility Issues to Service Points Template</a></span>
	</div>
	
		
	<hr />
<div class="table-responsive" style="min-height:300px; overflow-y: auto;">
 <?php $att=array("name"=>'myform','id'=>'myform'); echo form_open('issues/internal_issue',$att); ?>
<table  class="table table-hover table-bordered table-update" id="facility_issues_table" >
<thead style="background-color: white">
					<tr>
						<th>S11</th>
						<th>Service Point</th>
						<th>Description</th>
						<th>Supplier</th>
						<th>Unit Size</th>
						<th>Batch&nbsp;No</th>
						<th>Expiry Date</th>
						<th>Available Batch Stock</th>
						<th>Issue&nbsp;Type</th>
						<th>Issue Date</th>
						<th>Issued Quantity</th>
						<th>Total Balance</th>
						<th>Action</th>			    
					</tr>
					</thead>
					<tbody>
						<tr row_id='0'>
						<td>
							<input type="text" id="s11_no[]" name="s11_no[]" value="" class="form-control input-small s11_no" placeholder="Enter S11 if available" style="margin-left:6%;width:80px !important;"/></td>
						<td id="step1">
						<select style="width:155px !important;"  name="service_point[0]" class="form-control input-small service_point" >
							<option value="0" >Select service point</option>
								<?php 
foreach ($service_point as $service_point) :						
			$service_point_name=$service_point->service_point_name;			
			$service_point_id=$service_point->id;			
		echo "<option  value='$service_point_id'>$service_point_name</option>";	//this is what its supposed to do	
endforeach;
		?> 
						</select>
						</td>
						<td id="step2">
	<select class="form-control input-small service desc" name="desc[0]" style="width:150px !important;" >
    <option special_data="0" value="0" selected="selected" style="width:auto !important;">Select Commodity</option>
		<?php 
foreach ($commodities as $commodities) :						
			$commodity_name=$commodities['commodity_name'];
			$commodity_id=$commodities['commodity_id'];
			$unit=$commodities['unit_size'];
			$source_name=$commodities['source_name'];
			$total_commodity_units=$commodities['total_commodity_units'];
			$commodity_balance=$commodities['commodity_balance'];		
		echo "<option special_data='$commodity_id^$unit^$source_name^$total_commodity_units^$commodity_balance' value='$commodity_id'>$commodity_name. ($source_name)</option>";		
endforeach;
		?> 		
	</select>
						</td>
						<td>
						<input type="hidden" id="" name="commodity_id[0]" value="" class="commodity_id"/>
						<input type="hidden" id="" name="total_units[0]" value="" class="total_units"/>
						<input type="hidden" name="commodity_balance[0]" value="0" class="commodity_balance"/>
						<input type="hidden" name="stock_balance[0]" value="0" class="stock_balance"/>
						<input type="hidden" name="facility_stock_id[0]" value="0" class="facility_stock_id"/>	
						<input type="hidden" id="" name="first_expiry[0]" value="" class="first_expiry"/>	
						<input type="hidden" name="total_commodity_bal[0]" value="0" class="total_commodity_bal"/>	
						<input type="hidden" name="checker[0]" value="0" class="checker"/>	
						<input type="hidden" name="previous_issues_comm[0]" value="0" class="previous_issues_comm"/>	
						<input type="hidden" name="previous_issues_batch[0]" value="0" class="previous_issues_batch"/>	
						<input style="width:80px !important;" type="text" class="form-control input-small supplier_name" readonly="readonly" name="supplier_name[]"/></td>
			            <td><input style="width:80px !important;"  type="text" class="form-control input-small unit_size" readonly="readonly"  /></td>
						<td id="step3"><select style="width:80px !important;" class="form-control input-small batch_no big" name="batch_no[0]"></select></td>
						<td><input style="width:90px !important;" type='text' class='form-control input-small expiry_date' value="" name='expiry_date[0]' readonly="readonly"  /></td>
						<td><input class='form-control input-small available_stock' type="text" name="available_stock[0]" readonly="readonly" /></td>
						<td id="step5"><select style="width:80px !important;" class="form-control commodity_unit_of_issue big" name="commodity_unit_of_issue[]">
			<option  value="Pack_Size">Pack Size</option>
			<option value="Unit_Size">Unit Size</option>
			</select></td>
			<td id="step4"><input style="position: relative;z-index: 10000;" class='form-control input-small clone_datepicker_normal_limit_today' 
						type="text" name="clone_datepicker_normal_limit_today[0]"  value="" required="required" /></td>
						<td id="step6"><input class='form-control input-small quantity_issued' type="text" value=""  name="quantity_issued[0]"  required="required"/></td>
						<td><input class='form-control input-small balance' type="text" value="" readonly="readonly" /></td>
						
						<td style="width:50px !important;" id="step8" >
							<button type="button" class="remove btn btn-danger btn-xs"><span class="glyphicon glyphicon-minus"></span>row</button>
							<button type="button" id="step7" class="add btn btn-primary btn-xs"><span class="glyphicon glyphicon-plus"></span>row</button>
						</td>
			</tr>
		           </tbody>
		           </table>
</div>
<hr />
<div class="container-fluid">
<div style="float: right">
<button class="save btn btn-success" id="step9"><span class="glyphicon glyphicon-open"></span>Save</button></div>
</div>
</div>
<?php echo form_close();?>
<script>
$(document).ready(function() {	

 var $table = $('table');
//float the headers
  $table.floatThead({ 
	 scrollingTop: 100,
	 zIndex: 1001,
	 scrollContainer: function($table){ return $table.closest('.table-responsive'); }
	});	
//step one load all the facility data here
var facility_stock_data=<?php echo $facility_stock_data;?>;
	//random 
            ///when changing the commodity combobox
      		$(".desc").on('change',function(){
      		var row_id=$(this).closest("tr").index();	
      		var locator=$('option:selected', this);
			var data =$('option:selected', this).attr('special_data'); 
	       	var data_array=data.split("^");	 
	       	
	       	
	        locator.closest("tr").find(".unit_size").val(data_array[1]);
	     	locator.closest("tr").find(".supplier_name").val(data_array[2]);
	     	locator.closest("tr").find(".commodity_id").val(data_array[0]);
	     	locator.closest("tr").find(".available_stock").val("");
	     	locator.closest("tr").find(".total_units").val(data_array[3]);
	     	locator.closest("tr").find(".expiry_date").val("");
	     	locator.closest("tr").find(".quantity_issued").val("0");
	     	locator.closest("tr").find(".clone_datepicker_normal_limit_today").val("");	  	     	    	
			var commodity_id=data_array[0];
			var stock_data=extract_data(data_array[0],commodity_id,'batch_data');			
            var dropdown="<option special_data=''>--select Batch--</option>"+stock_data[0];           
            var facility_stock_id=stock_data[1];
            var total_stock_bal=data_array[4];
            var total_issues_for_this_item=0; 
            var total_issues_for_this_batch=0           
            var total_commodity_bal=stock_data[4];           

           
	        locator.closest("tr").find(".manufacture").val(stock_data[4]);
	        locator.closest("tr").find(".facility_stock_id").val(stock_data[1]);	        
			locator.closest("tr").find(".batch_no").html(dropdown);
			
			// locator.closest("tr").find(".available_stock").val(stock_data[2]-total_issues_for_this_batch);		
			locator.closest("tr").find(".commodity_id").val(commodity_id);
			locator.closest("tr").find(".commodity_balance").val(remaining_items);	
	        locator.closest("tr").find(".expiry_date").val(""+stock_data[3]+"" );

			var new_comm_id = locator.closest("tr").find(".desc").val();
	       	var new_batch_id = locator.closest("tr").find(".batch_no").val();
	        var total_issued  = new_calculate_actual_stock(new_comm_id,new_batch_id);	        
	       	locator.closest("tr").find(".previous_issues_comm").val(total_issued[0]);
	       	locator.closest("tr").find(".previous_issues_batch").val(total_issued[1]);

	       	var remaining_items=total_stock_bal-total_issued[1];	
	        var remaining_comodity_bal=total_commodity_bal-total_issued[0];	
	        locator.closest("tr").find(".first_expiry").val(""+stock_data[3]+"" );
			locator.closest("tr").find(".balance").val(remaining_comodity_bal);
			locator.closest("tr").find(".total_commodity_bal").val(remaining_comodity_bal);
			locator.closest("tr").find(".available_stock").val(remaining_items);		
			locator.closest("tr").find(".stock_balance").val(remaining_items);		
		
		});//entering the values to issue check if you have enough balance
//        $(".quantity_issued").on('keyup',function (){
//         	var bal=parseInt($(this).closest("tr").find(".available_stock").val());
//         	var bal1=parseInt($(this).closest("tr").find(".commodity_balance").val());
//         	var selector_object=$(this);
//         	var data =$('option:selected', selector_object.closest("tr").find('.desc')).attr('special_data') 
// 	       	var data_array=data.split("^");
// 	       	// console.log(data_array);
// 	       	var total_commodity_bal = selector_object.closest("tr").find(".total_commodity_bal").val();
//         	var remainder1=total_commodity_bal-parseInt(calculate_issues(selector_object.closest("tr").find(".quantity_issued").val(),selector_object.closest("tr").find(".commodity_unit_of_issue").val(), selector_object.closest("tr").find(".unit_size").val()));
//     var issue=parseInt(calculate_issues(selector_object.closest("tr").find(".quantity_issued").val(),selector_object.closest("tr").find(".commodity_unit_of_issue").val(), selector_object.closest("tr").find(".unit_size").val()));
//     var remainder=bal-parseInt(calculate_issues(selector_object.closest("tr").find(".quantity_issued").val(),selector_object.closest("tr").find(".commodity_unit_of_issue").val(), selector_object.closest("tr").find(".unit_size").val()));
//         	var form_data=check_if_the_form_has_been_filled_correctly(selector_object);
//         	var alert_message='';
//         	if (remainder<0) {alert_message+="<li>Can not issue beyond available stock</li></br>"+
//         	"<li>You are trying to issue "+issue+" (Units) from "+$(this).closest("tr").find(".available_stock").val()+" (Units)</li>";
    
// //data_array[4]
// }
// 			if (selector_object.val() <0) { alert_message+="<li>Issued value must be above 0</li>";}
// 		    if (selector_object.val().indexOf('.') > -1) {alert_message+="<li>Decimals are not allowed.</li>";}		
// 			if (isNaN(selector_object.val())){alert_message+="<li>Enter only numbers</li>";}				
// 			if(isNaN(alert_message) || isNaN(form_data[0])){
// 	//reset the text field and the message dialog box 
//     selector_object.val(""); var notification='<ol>'+alert_message+form_data[0]+'</ol>&nbsp;&nbsp;&nbsp;&nbsp;';
//     //hcmp custom message dialog
    
//     hcmp_message_box(title='HCMP error message',notification,message_type='error')
//    // dialog_box(notification,'<button type="button" class="btn btn-primary" data-dismiss="modal">Close</button>');
//     //This event is fired immediately when the hide instance method has been called.
//     $('#communication_dialog').on('hide.bs.modal', function (e) { selector_object.focus();	})
//     selector_object.closest("tr").find(".balance").val(selector_object.closest("tr").find(".total_commodity_bal").val());
//     // selector_object.closest("tr").find(".balance").val(selector_object.closest("tr").find(".commodity_balance").val());
//     return;   }// set the balance here
//    	selector_object.closest("tr").find(".balance").val(remainder1);	

//     // selector_object.closest("tr").find(".balance").val(selector_object.closest("tr").find(".total_commodity_bal").val());

//         });// adding a new row 
$(".quantity_issued").on('keyup',function (){
        	var bal=parseInt($(this).closest("tr").find(".available_stock").val());
        	var bal1=parseInt($(this).closest("tr").find(".commodity_balance").val());
        	var selector_object=$(this);
        	var data =$('option:selected', selector_object.closest("tr").find('.desc')).attr('special_data') 
	       	var data_array=data.split("^");
	       	// console.log(data_array);
	       	var total_commodity_bal = selector_object.closest("tr").find(".total_commodity_bal").val();
	       	
	       	var new_comm_id = selector_object.closest("tr").find(".desc").val();
	       	var new_batch_id = selector_object.closest("tr").find(".batch_no").val();
	        var total_issued  = new_calculate_actual_stock(new_comm_id,new_batch_id);	        
	       	selector_object.closest("tr").find(".previous_issues_comm").val(total_issued[0]);
	       	selector_object.closest("tr").find(".previous_issues_batch").val(total_issued[1]);
	       	var available_commodity_bal = selector_object.closest("tr").find(".total_commodity_bal").val();
	       	var available_stock_bal = selector_object.closest("tr").find(".stock_balance").val();
	       	// alert('Commodity Balance: '+total_commodity_bal+' Stock Balance '+total_stock_bal);
	       	var total_issued  = new_calculate_actual_stock(new_comm_id,new_batch_id,selector_object);	       	
	       	var remaining_stock_items=available_stock_bal-total_issued[2];		        
	       	var remaining_commodity_items=available_commodity_bal-total_issued[2];		        
	        // alert(remaining_items);	
	        var form_data=check_if_the_form_has_been_filled_correctly(selector_object);
        	var alert_message='';
        	if (remaining_stock_items<0) {
        		alert_message+="<li>Can not issue beyond available stock</li></br>"+"<li>You are trying to issue "+total_issued[2]+" (Units) from "+$(this).closest("tr").find(".available_stock").val()+" (Units)</li>";
        		selector_object.closest("tr").find(".available_stock").val(available_stock_bal);		
				selector_object.closest("tr").find(".balance").val(available_commodity_bal);}
			// selector_object.closest("tr").find(".balance").val(remaining_comodity_bal);
			// selector_object.closest("tr").find(".total_commodity_bal").val(remaining_comodity_bal);
			selector_object.closest("tr").find(".available_stock").val(remaining_stock_items);		
			selector_object.closest("tr").find(".balance").val(remaining_commodity_items);		

    //     	var remainder1=total_commodity_bal-parseInt(calculate_issues(selector_object.closest("tr").find(".quantity_issued").val(),selector_object.closest("tr").find(".commodity_unit_of_issue").val(), selector_object.closest("tr").find(".unit_size").val()));
    // var issue=parseInt(calculate_issues(selector_object.closest("tr").find(".quantity_issued").val(),selector_object.closest("tr").find(".commodity_unit_of_issue").val(), selector_object.closest("tr").find(".unit_size").val()));
    // var remainder=bal-parseInt(calculate_issues(selector_object.closest("tr").find(".quantity_issued").val(),selector_object.closest("tr").find(".commodity_unit_of_issue").val(), selector_object.closest("tr").find(".unit_size").val()));
//         	var form_data=check_if_the_form_has_been_filled_correctly(selector_object);
//         	var alert_message='';
//         	if (remainder<0) {alert_message+="<li>Can not issue beyond available stock</li></br>"+
//         	"<li>You are trying to issue "+issue+" (Units) from "+$(this).closest("tr").find(".available_stock").val()+" (Units)</li>";
    
// //data_array[4]
// }
			if (selector_object.val() <0) { alert_message+="<li>Issued value must be above 0</li>";}
		    if (selector_object.val().indexOf('.') > -1) {alert_message+="<li>Decimals are not allowed.</li>";}		
			if (isNaN(selector_object.val())){alert_message+="<li>Enter only numbers</li>";}				
			if(isNaN(alert_message) || isNaN(form_data[0])){
	//reset the text field and the message dialog box 
    selector_object.val(""); var notification='<ol>'+alert_message+form_data[0]+'</ol>&nbsp;&nbsp;&nbsp;&nbsp;';
    //hcmp custom message dialog
    
    hcmp_message_box(title='HCMP error message',notification,message_type='error')
   // dialog_box(notification,'<button type="button" class="btn btn-primary" data-dismiss="modal">Close</button>');
    //This event is fired immediately when the hide instance method has been called.
    $('#communication_dialog').on('hide.bs.modal', function (e) { selector_object.focus();	})
    selector_object.closest("tr").find(".balance").val(selector_object.closest("tr").find(".total_commodity_bal").val());
    selector_object.closest("tr").find(".available_stock").val(available_stock_bal);
    // selector_object.closest("tr").find(".balance").val(selector_object.closest("tr").find(".commodity_balance").val());
    return;   }// set the balance here
   	// selector_object.closest("tr").find(".balance").val(remainder1);	

    // selector_object.closest("tr").find(".balance").val(selector_object.closest("tr").find(".total_commodity_bal").val());

        });// adding a new row 
        $(".add").click(function() {
        var selector_object=$('#facility_issues_table tr:last');
        var form_data=check_if_the_form_has_been_filled_correctly(selector_object);
        if(isNaN(form_data[0])){	
        var notification='<ol>'+form_data[0]+'</ol>&nbsp;&nbsp;&nbsp;&nbsp;';
           //hcmp custom message dialog
           hcmp_message_box(title='HCMP error message',notification,message_type='error')
       // dialog_box(notification,'<button type="button" class="btn btn-primary" data-dismiss="modal">Close</button>');
        return;   }// set the balance here
			//set the quantities to readonly  $("#dropdown").prop("disabled", true);
			selector_object.closest("tr").find(".quantity_issued").attr('readonly','readonly');
			selector_object.closest("tr").find(".checker").val(1);
			selector_object.closest("tr").find(".batch_no").attr("disabled", true);
			selector_object.closest("tr").find(".commodity_unit_of_issue").attr("disabled", true);
			selector_object.closest("tr").find(".desc").attr("disabled", true);				
			//reset the values of current element */
		  clone_the_last_row_of_the_table();
		});	/////batch no change event
		$('.batch_no').on('change',function(){
			var row_id=$(this).closest("tr").attr('row_id');
		    var locator=$('option:selected', this);
			var data =$('option:selected', this).attr('special_data'); 
	       	var data_array=data.split("^");

	       	// console.log(data_array);
	       	//Get the date of the currently selected option
	       	var largest_date = $(".batch_no_specific").attr("special_data");
	       	var largest_data_array = data.split("^");
	       	// console.log(largest_data_array);
	       	var large_date = data_array[0];
	       	var new_batch_id = locator.closest("tr").find(".batch_no").val();
	        var total_issued = new_calculate_actual_stock(null,new_batch_id);
	       	// locator.closest("tr").find(".previous_issues_comm").val(total_issued[0]);
	       	locator.closest("tr").find(".previous_issues_batch").val(total_issued[1]);
	       	var total_commodity_bal = $(this).closest("tr").find(".total_commodity_bal").val();
	      	// var total_commodity_bal=stock_data[4]; 
	       	var no_of_batches = $(".batch_no_specific").size();
	       
	       
	       // console.log(data_array);
	       if(data_array[0]!=''){
	       	var first_expiry = $(this).closest("tr").find(".first_expiry").val();
	       	first_expiry = $.datepicker.formatDate('@', new Date(first_expiry));	       		       
	       	var new_date=$.datepicker.formatDate('d M yy', new Date(data_array[0]));
	       	new_date_ts = $.datepicker.formatDate('@', new Date(new_date));	       		       
	       	var timestamp_date = new_date_ts-first_expiry;
	       	var days = Math.floor(timestamp_date / 86400/1000);
	       	var new_date=$.datepicker.formatDate('d M yy', new Date(data_array[0]));
	       	// var new_date=$.datepicker.formatDate('d M yy', new Date(data_array[0]));
	       	// var new_date=$.datepicker.formatDate('d M yy', new Date(data_array[0]));	       	
	      	var total_stock_bal=data_array[1];	
            var commodity_stock_id_old=parseInt($("input[name='commodity_id["+row_id+"]']").val());
            var facility_stock_id_current=parseInt(data_array[2]);   
	        var total_issues = total_issued[1];    
	       
	        locator.closest("tr").find(".available_stock").val(total_stock_bal-total_issued[1]);
	        locator.closest("tr").find(".stock_balance").val(total_stock_bal-total_issued[1]);
	        locator.closest("tr").find(".expiry_date").val(""+new_date+"");	        		
		    locator.closest("tr").find(".quantity_issued").val("0");
		    locator.closest("tr").find(".balance").val(total_commodity_bal);
		    // locator.closest("tr").find(".balance").val(total_commodity_bal-total_issues);
		    // locator.closest("tr").find(".total_commodity_bal").val(total_issued[0]);
		    locator.closest("tr").find(".commodity_balance").val(total_stock_bal-total_issued[0]);
		   }else{
		    var total_commodity_bal = $(this).closest("tr").find(".total_commodity_bal").val();
		    locator.closest("tr").find(".expiry_date").val("");
		    // locator.closest("tr").find(".balance").val("");
		    locator.closest("tr").find(".balance").val(total_commodity_bal);
		    // locator.closest("tr").find(".balance").val(total_commodity_bal-total_issues);
		    locator.closest("tr").find(".available_stock").val("0");
		    locator.closest("tr").find(".stock_balance").val("0");
		     // locator.closest("tr").find(".total_commodity_bal").val(total_commodity_bal);
		    locator.closest("tr").find(".quantity_issued").val("0");	
		   }  	
		   if(days>0){
			var notification='<ol>The batch you are issuing has an expiry date later than the first one. Please issue the first batch</ol>&nbsp;&nbsp;&nbsp;&nbsp;';
           //hcmp custom message dialog
           hcmp_message_box(title='HCMP Notification',notification,message_type='warning')
       // dialog_box(notification,'<button type="button" class="btn btn-primary" data-dismiss="modal">Close</button>');
    	return; 
		}		
      }); // change issue type
        $(".commodity_unit_of_issue").on('change', function(){
          $(this).closest("tr").find(".quantity_issued").val('0');
          $(this).closest("tr").find(".available_stock").val($(this).closest("tr").find(".stock_balance").val());	
          $(this).closest("tr").find(".balance").val($(this).closest("tr").find(".total_commodity_bal").val());	
        })/// remove the row
		$('.remove').on('click',function(){
			var data_ =$('option:selected', $(this).closest("tr").find('.desc')).attr('special_data'); 
	       	var data_array=data_.split("^");
			var row_id=$(this).closest("tr").index();
			var count_rows=0;
			var bal=parseInt(calculate_actual_stock(data_array[3],$(this).closest("tr").find(".commodity_unit_of_issue").val(),
    parseInt($(this).closest("tr").find(".quantity_issued").val()),'return',''))			
            var commodity_stock_id=parseInt($(this).closest("tr").find(".facility_stock_id").val());   
            var total=0;
            var commodity_id=$(this).closest("tr").find(".commodity_id").val();   			
			$("input[name^=commodity_id]").each(function(index, value) {				
			var new_id=$(this).closest("tr").index();
			var total_current_issues=$(this).closest("tr").find(".quantity_issued").val();
			var current_facility_stock_id=$(this).closest("tr").find(".facility_stock_id").val();
                  if(new_id>row_id && parseInt($(this).val())==commodity_id){ // check for total issues for this item              	 
                  var value=parseInt($(this).closest("tr").find(".commodity_balance").val())+bal;  ///available_stock
                   $(this).closest("tr").find(".commodity_balance").val(value);
                   $(this).closest("tr").find(".balance").val(value);                  
                  }                
                  if(new_id>row_id && current_facility_stock_id==commodity_stock_id){// check for total issues for batch this item
                   var value=parseInt($("input[name='available_stock["+new_id+"]']").val())+bal;  ///available_stock
                   $("input[name='available_stock["+new_id+"]']").val(value);
                  }
                 count_rows++;
		        });
	       //finally remove the row 
	       if(count_rows==1){
	       	 clone_the_last_row_of_the_table();
	       	 $(this).parent().parent().remove(); 
	       }else{
	       	$(this).parent().parent().remove(); 
	       }	         
      });
    // validate the form
	$("#myform").validate();
      /************save the data here*******************/
	$('.save').button().click(function() {
		var selector_object=$('#facility_issues_table tr:last');
        var form_data=check_if_the_form_has_been_filled_correctly(selector_object);
        if(isNaN(form_data[0])){
        var notification='<ol>'+form_data[0]+'</ol>&nbsp;&nbsp;&nbsp;&nbsp;';
           //hcmp custom message dialog
           hcmp_message_box(title='HCMP error message',notification,message_type='error')
       // dialog_box(notification,'<button type="button" class="btn btn-primary" data-dismiss="modal">Close</button>');
        return; }
    $("input[name^=commodity_id]").each(function() {
                	$(this).closest("tr").find(".batch_no").removeAttr('disabled'); 
                	$(this).closest("tr").find(".commodity_unit_of_issue").removeAttr('disabled'); 	
                	$(this).closest("tr").find(".desc").removeAttr('disabled');  	
                	$(this).closest("tr").find(".commodity_unit_of_issue").removeAttr('disabled');	
                	});
    // save the form
    
    confirm_if_the_user_wants_to_save_the_form("#myform");
     });
     /************************* form validation ************/
        function clone_the_last_row_of_the_table(){
            var last_row = $('#facility_issues_table tr:last');
            var cloned_object = last_row.clone(true);
            var table_row = cloned_object.attr("row_id");
            var next_table_row = parseInt(table_row) + 1;    
            var blank_value = "";       
		    cloned_object.attr("row_id", next_table_row);
			cloned_object.find(".service_point").attr('name','service_point['+next_table_row+']'); 
			cloned_object.find(".commodity_id").attr('name','commodity_id['+next_table_row+']'); 
			cloned_object.find(".total_units").attr('name','total_units['+next_table_row+']');
			cloned_object.find(".commodity_id").attr('id',next_table_row); 
			cloned_object.find(".quantity_issued").attr('name','quantity_issued['+next_table_row+']'); 	
			cloned_object.find(".clone_datepicker_normal_limit_today").attr('name','clone_datepicker_normal_limit_today['+next_table_row+']'); 
			cloned_object.find(".available_stock").attr('name','available_stock['+next_table_row+']'); 
			cloned_object.find(".facility_stock_id").attr('name','facility_stock_id['+next_table_row+']'); 
			cloned_object.find(".batch_no").attr('name','batch_no['+next_table_row+']');
			cloned_object.find(".commodity_unit_of_issue").attr('name','commodity_unit_of_issue['+next_table_row+']');
			cloned_object.find(".expiry_date").attr('name','expiry_date['+next_table_row+']');
			cloned_object.find(".desc").attr('name','desc['+next_table_row+']');
			cloned_object.find(".commodity_balance").attr('name','commodity_balance['+next_table_row+']');
			cloned_object.find(".s11_no").attr('name','s11_no['+next_table_row+']');					
           // cloned_object.find("input").attr('value',"");     
            //cloned_object.find(".quantity_issued").attr('value',blank_value); 
            cloned_object.find("input").val(blank_value);
            cloned_object.find(".quantity_issued").val(blank_value);  
            cloned_object.find(".quantity_issued").removeAttr('readonly');  
            cloned_object.find(".batch_no").removeAttr('disabled');
            cloned_object.find(".commodity_unit_of_issue").removeAttr('disabled'); 
            cloned_object.find(".desc").removeAttr('disabled');   
            //cloned_object.find(".commodity_balance").attr('value',"");     
            cloned_object.find(".commodity_balance").val(blank_value);        
            cloned_object.find(".batch_no").html("");
            // remove the error class
            cloned_object.find("label.error").remove();             
			cloned_object.insertAfter('#facility_issues_table tr:last');	
			refresh_clone_datepicker_normal_limit_today();	
        }
		function check_if_the_form_has_been_filled_correctly(selector_object){
		var alert_message='';
		var service_point=selector_object.closest("tr").find(".service_point").val();
		var commodity_id=selector_object.closest("tr").find(".desc").val();
		var issue_date=selector_object.closest("tr").find(".clone_datepicker_normal_limit_today").val();
		var issue_quantity=selector_object.closest("tr").find(".quantity_issued").val();
		//set the message here
		if (service_point==0) {alert_message+="<li>Select a Service Point</li>";}
	    if (commodity_id==0) {alert_message+="<li>Select a commodity first</li>";}
	    if (issue_date==0) {alert_message+="<li>Indicate the date of the issue</li>";}	
	    if (issue_quantity==0) {alert_message+="<li>Indicate how much you want to issue</li>";}	    
	    return[alert_message,service_point,commodity_id,issue_quantity,issue_date];	
		}//extract facility_data  from the json object 		
		function extract_data(commodity_id_,commodity_stock_row_id,type_of_drop_down){
			var row_id=0; var dropdown='';var facility_stock_id_='';  var total_stock_bal=0; var expiry_date='';var total_commodity_balance = 0;
			$.each(facility_stock_data, function(i, jsondata) {
			var commodity_id=facility_stock_data[i]['commodity_id'];
			
			if(parseInt(commodity_id)==commodity_id_){
				if(type_of_drop_down=='batch_data'){//check if the user option is to create a batch combobox
					if(row_id==0){//if the row is 0, create a selected default value
					var facility_stock_id=facility_stock_data[i]['facility_stock_id'];	
			  		dropdown+="<option selected='selected'"+ "class='batch_no_specific'" + 
			  		 "special_data="+facility_stock_data[i]['expiry_date']+
			  		 "^"+facility_stock_data[i]['commodity_balance']+
			  		 "^"+facility_stock_data[i]['facility_stock_id']+
			  		 "^"+facility_stock_data[i]['commodity_balance']+">";
			  				 expiry_date=$.datepicker.formatDate('d M yy', new Date(facility_stock_data[i]['expiry_date']));
			  				 bal=facility_stock_data[i]['commodity_balance'];
			  				 facility_stock_id_=facility_stock_data[i]['facility_stock_id'];
			  				 total_stock_bal=facility_stock_data[i]['commodity_balance'];
			  				 total_commodity_balance=total_commodity_balance+parseInt(facility_stock_data[i]['commodity_balance']);
			  				 drug_id_current=commodity_id_;			  				 
			  			}else{
			  		dropdown+="<option "+ "class='batch_no_specific'" + 
			  		 "special_data="+facility_stock_data[i]['expiry_date']+
			  		 "^"+facility_stock_data[i]['commodity_balance']+
			  		 "^"+facility_stock_data[i]['facility_stock_id']+
			  		 "^"+facility_stock_data[i]['commodity_balance']+">";	 
			  			total_stock_bal=facility_stock_data[i]['commodity_balance'];
			  			total_commodity_balance= total_commodity_balance + parseInt(facility_stock_data[i]['commodity_balance']);
			  		}			  			
						dropdown+=facility_stock_data[i]['batch_no'];
						//dropdown+="data-date="+facility_stock_data[i]['expiry_date'];						
						dropdown+="</option>";}
			row_id++; //auto-increment the checker
			}
				});
			return 	[dropdown,facility_stock_id_,total_stock_bal,expiry_date,total_commodity_balance];
		}
		 $('.toolt').tooltip({
        placement: 'left'
    }); 
	});	
</script>
 <script type="text/javascript">
      function startIntro(){
        var intro = introJs();
          intro.setOptions({
            steps: [
              {
                element: '#step1',
                intro: "Select <i>Service point</i> <b>here.</b> ."
              },
              {
                element: '#step2',
                intro: "Select, <i>Commodity</i> <b>here.</b>This will populate the batch no. ",
                position: 'right'
              },
              {
                element: '#step3',
                intro: "Select, <i>Batch No</i> <b>here.</b>This will populate the Expiry date. ",
                position: 'right'
              },
              {
                element: '#step4',
                intro: "Select, <i>Issue date</i> <b>here.</b> ",
                position: 'right'
              },
              {
                element: '#step5',
                intro: "Select, <i>Issue type</i> <b>here.</b> ",
                position: 'right'
              },
              {
                element: '#step6',
                intro: 'Enter, <i>Issue Quantity</i> <b>here.</b>',
                position: 'bottom'
              },
              {
                element: '#step7',
                intro: "<span style='font-family: Tahoma'>Click here to Issue more commodities</span>",
                position: 'left'
              },
              {
                element: '#step8',
                intro: "<span style='font-family: Tahoma'>Click here to remove commodities</span>",
                position: 'left'
              },
              {
                element: '#step9',
                intro: "<span style='font-family: Tahoma'>Click here to Complete  activity</span> ",
                position: 'left'
              }
            ]
          });

          intro.start();
      }
    </script>
   <script src="<?php echo base_url().'assets/bower_components/intro.js/intro.js'?>" type="text/javascript"></script>