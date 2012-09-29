<?php
 /*
  +-------------------------------------------------------------------------+
  | Copyright (C) 2007 Susanin                                          |
  |                                                                         |
  | This program is free software; you can redistribute it and/or           |
  | modify it under the terms of the GNU General Public License             |
  | as published by the Free Software Foundation; either version 2          |
  | of the License, or (at your option) any later version.                  |
  |                                                                         |
  | This program is distributed in the hope that it will be useful,         |
  | but WITHOUT ANY WARRANTY; without even the implied warranty of          |
  | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the           |
  | GNU General Public License for more details.                            |
  +-------------------------------------------------------------------------+
 */
 
 chdir('../../');
 include("./include/auth.php");

 
 include_once($config["base_path"] . "/plugins/camm/lib/camm_functions.php");
 include_once($config["base_path"] . "/plugins/camm/lib/json_sql.php");
 
 //***********************************************************
 		
     /* ================= input validation ================= */
 
 	camm_input_validate_input_regex(get_request_var_request("task"), "/^camm_[a-zA-Z_]*$/",'Unrecognised command [' . get_request_var_request("task") . ']');
 	camm_input_validate_input_regex(get_request_var_request("task"), "/^(camm_recreate_tree|camm_test_rule|camm_get_user_functions|camm_delete_traps|camm_delete_rule|camm_delete_syslog_message|camm_save_rule|camm_execute_rule|camm_delete_unktraps|camm_convert_json_to_sql_string|camm_get_settings|camm_save_settings|camm_clone_rule|camm_delete_records)$/",'Unrecognised command [' . get_request_var_request("task") . ']');
    
     /* ==================================================== */
 
 
 	 if (is_error_message()) {
 		 echo "Validation error.";
 		 exit;
 	 }
 	 
 	 if (is_camm_admin() == '0') {
 		 echo "You do not have rigth for this action.";
 		 exit;
 	 }
 	 
  
 	 
 	$task = '';
 	if ( isset($_POST['task'])){
 		$task = $_POST['task'];
 	}elseif( isset($_GET['task'])){
 		$task = $_GET['task'];
 	}
 
 	 
 	if (camm_user_func_exists($task)) {
 		call_user_func($task);
 	}else{
 		echo "Unsupported command [" . $task . "].";
 	}	
 	 
 
 function camm_recreate_tree() {
 global $cacti_camm_components;
 
 $rezult=1;
 
     /* ================= input validation ================= */
 
 	camm_input_validate_input_regex(get_request_var_request("type"), "/^(traps|syslog)$/", 'Uncorrect input data');	
 
     /* ==================================================== */
 	//error checking
 	if (is_error_message()) {
 		$rezult="Input validation error.";
 	}
 	//business logic
 	if ($rezult==1) {
 		$tree_type = (string) (isset($_POST['type']) ? $_POST['type'] : "snmptt");
 		$tree_type = ($tree_type == 'traps' ? 'snmptt' : $tree_type);
		
 		if ($cacti_camm_components[$tree_type]) {
 			$rezult = camm_poller_recreate_tree($tree_type);
 		}else{
 			$rezult=$tree_type . " component NOT USED";
 		}
 	}
 	//output 
 	if ($rezult == '1') {
 		echo camm_JEncode(array('success' => true));
 	}else{
 		echo camm_JEncode(array('failure' => true,'error' => $rezult));
 	}
 
 }
 	
 	
 function camm_test_rule() {
 global $cacti_camm_components;
 
 $rezult=1;
 $query_string = "";
 $total_rows=0;
 
     /* ================= input validation ================= */
 
 	camm_input_validate_input_regex(get_request_var_request("start", "0"), "/^[0-9]{0,10}$/");
 	camm_input_validate_input_regex(get_request_var_request("limit", "50"), "/^[0-9]{1,4}$/");	
 	camm_input_validate_input_regex(get_request_var_request("type", ""), "/^(snmptt|syslog)$/");	
	camm_input_validate_input_regex(get_request_var_request("only_new", "false"), "/^(false|true)$/");
 	
 
     /* ==================================================== */
 
 	//error checking
 	if (is_error_message()) {
 		$rezult="Input validation error.";
 	}
 	
 	//business logic
 	if ($rezult==1) {
 		$row_start = (integer) (isset($_POST['start']) ? $_POST['start'] : $_POST['start']);
 		$row_limit = (integer) (isset($_POST['limit']) ? $_POST['limit'] : $_POST['limit']);
		$only_new = (boolean) (isset($_POST['only_new']) ? ($_POST['only_new'] == "true") : false);
 
 		$test_row_limit = read_config_option("camm_test_row_count");
 		$raw_json_where = (string) (isset($_POST['filter']) ? $_POST['filter'] : $_POST['filter']);
 		$type = (string) (isset($_POST['type']) ? $_POST['type'] : $_POST['type']);
 	  
 		if (!($cacti_camm_components[$type])) {
 			$rezult=$type . " component NOT USED";
 		}		
 		
 		//business logic
 		if ($rezult==1) {
 		
 			$sql_where = getSQL(camm_JDecode(stripslashes($raw_json_where)));
			$pos = strpos($sql_where, 'host_template_id');			
 			

 			
			if ($type == 'snmptt') {
				if (read_config_option("camm_join_field") == "sourceip") {
					$join_field = "agentip";
				}else{
					$join_field = "hostname";		
				}
				if ($only_new) {
					$sql_where = $sql_where . " AND (temp.`status`='0')";
				}
				if ($pos === false) { //sql без использования поиска по типу устройства				
					$query_string = " SELECT temp_unk.*, host.description, host.host_template_id, host.id as device_id, host.status, host.disabled " .
						"from (SELECT tempt.* FROM (SELECT * from plugin_camm_snmptt " . (($test_row_limit>0)? " order by traptime desc LIMIT " . $test_row_limit : "" ) . ") as tempt WHERE $sql_where order by traptime desc "; 
					
					$query_string .= " LIMIT " . $row_start . "," . $row_limit;
					
					$query_string .= ") as temp_unk Left join host on (temp_unk.`" . $join_field . "`=host.hostname)";
	 
					$total_rows = db_fetch_cell("SELECT count(*) FROM (SELECT * from plugin_camm_snmptt " . (($test_row_limit>0)? " order by traptime desc LIMIT " . $test_row_limit : "" ) . ")  as tempt WHERE  $sql_where;");
				}else{
				
					$query_string = "SELECT SQL_CALC_FOUND_ROWS tempt.*, host.description, host.host_template_id, host.id as device_id, host.status, host.disabled FROM " .
						" (SELECT * from plugin_camm_snmptt " . (($test_row_limit>0)? " order by traptime desc LIMIT " . $test_row_limit : "" ) . ") as tempt " . 
					    " Left join host on (tempt.`" . $join_field . "`=host.hostname)" .
					    " WHERE $sql_where " .
						" LIMIT " . $row_start . "," . $row_limit;
				
					$rows = db_fetch_assoc($query_string);
					$total_rows = db_fetch_cell("SELECT FOUND_ROWS()");						
				}
				
				
				
				
 			}elseif($type == 'syslog'){
 			
				$str_sys_db_name = "`" . read_config_option("camm_syslog_db_name") . "`.`plugin_camm_syslog`";
				
				if (read_config_option("camm_join_field") == "sourceip") {
					$join_field = "sourceip";
				}else{
					$join_field = "host";		
				}
				if ($only_new) {
					$sql_where = $sql_where . " AND (`t_sys`.`status`='0')";
				}
				if ($pos === false) { //sql без использования поиска по типу устройства
					$query_string = " SELECT temp_sys.*, host.description, host.host_template_id, host.id as device_id " .
					"from (SELECT id, `facility`, `priority`, `sys_date`, `host`, `message`,`sourceip` FROM (SELECT * FROM " . $str_sys_db_name . (($test_row_limit>0)? " order by sys_date desc LIMIT " . $test_row_limit : "" ) . ") as t_sys WHERE $sql_where "; 
				
					$query_string .= " LIMIT " . $row_start . "," . $row_limit;
				
					$query_string .= ") as temp_sys Left join host on (`temp_sys`.`" . $join_field . "`=host.hostname)";
	 
					$total_rows = db_fetch_cell("SELECT count(*) from  (SELECT * FROM " . $str_sys_db_name . (($test_row_limit>0)? " order by sys_date desc LIMIT " . $test_row_limit : "" ) . ") as t_sys WHERE $sql_where ;");
				}else{ //with host type search.
					$sql_where = str_replace('host_template_id','host_template_id', $sql_where);
					
					$query_string = " SELECT SQL_CALC_FOUND_ROWS `t_sys`.id,  `facility`, `priority`, `sys_date`, `host`, `message`,`sourceip`, `host`.`description`, `host`.`host_template_id`, `host`.`id` as device_id " .
					"  FROM (SELECT * FROM " . $str_sys_db_name . (($test_row_limit>0)? " order by sys_date desc LIMIT " . $test_row_limit : "" ) . ") as t_sys   LEFT JOIN `host` on (`t_sys`.`" . $join_field . "`=`host`.`hostname`) WHERE $sql_where "; 
				
					$query_string .= " LIMIT " . $row_start . ", " . $row_limit . ";";
				
					$rows = db_fetch_assoc($query_string);
					$total_rows = db_fetch_cell("SELECT FOUND_ROWS()");							
				
				}
			
			
			
			
			}
 		}
 	}
 	
 	//output 
 	if ($rezult==1) {
 		if($total_rows>0){
 			if (!isset($rows)) {
				$rows = db_fetch_assoc($query_string);
			}
 			//$jsonresult = camm_JEncode($rows);
 			//echo '({"total":"'.$total_rows.'","results":'.$jsonresult.'})';
 			echo camm_JEncode(array('success' => true,'total' => $total_rows, "results" => $rows));
 		}else{
 			echo camm_JEncode(array('success' => true,'total' => "0", "results" => ""));
 		}
 	}else{
 		echo camm_JEncode(array('failure' => true,'error' => $rezult));
 	}
 }
 
 
 function camm_get_user_functions() {
 global $config;
 
 include_once($config["base_path"] . "/plugins/camm/lib/camm_user_func.php");
 
 	foreach($camm_users_functions as $key => $camm_users_function) {
 		$n_camm_users_functions[$key]["func_name"]=$camm_users_function;
 	}
 	echo camm_JEncode(array('success' => true,'data' => $n_camm_users_functions));
 
 }
 
 
 function camm_delete_traps(){
 global $cacti_camm_components;
 $rezult=1;
 
 /* ================= input validation ================= */
 
 camm_input_validate_input_regex(stripslashes(get_request_var_request("ids")), "/^\[(\"[0-9]+\",?)+\]$/");
 
 /* ==================================================== */
 
 	//error checking
 	if (is_error_message()) {
 		$rezult="Input validation error.";
 	}
 	
 	//business logic
 	if ($rezult==1){
 		if ($cacti_camm_components["snmptt"]) {
 			if ( isset($_POST['ids'])){
 			   $ids = $_POST['ids']; // Get our array back and translate it :
 			   $ids = camm_JDecode(stripslashes($ids));
 
 			    if(sizeof($ids)<1){
 					$rezult=" no ID.";
 			    } else if (sizeof($ids) == 1){
 			      $query = "DELETE FROM `plugin_camm_snmptt` WHERE `id` = ".$ids[0];
 					db_execute($query);
 			    } else {
 					$str_ids = '';
 					for ($i=0;($i<sizeof($ids));$i++) {
 						$str_ids = $str_ids . "'" . $ids[$i] . "', ";
 					}
 					$str_ids = substr($str_ids, 0, strlen($str_ids) -2);
 					db_execute("DELETE FROM `plugin_camm_snmptt` where `id` in ("  . $str_ids . ");");
 			    }
 			} else 
 			{
 					$rezult=" no ID.";
 			}
 		}else{
 			$rezult=" SNMPTT component disabled.";
 		}
 	}
 	
 	//output 
 	if ($rezult==1) {
 		echo camm_JEncode(array('success' => true));
 	}else{
 		echo camm_JEncode(array('failure' => true,'error' => $rezult));
 	}	
 }
 
 function camm_delete_rule(){
 $rezult=1;
 
 /* ================= input validation ================= */
 
 camm_input_validate_input_regex(stripslashes(get_request_var_request("id")), "/^\\[([0-9]+,?)+\\]\$/","Uncorrect input value");
 
 /* ==================================================== */
 
 	//error checking
 	if (is_error_message()) {
 		$rezult="Input validation error.";
 	}
 
 	//business logic
 	if ($rezult==1){
 		if ( isset($_POST['id'])){
 		   $ids = $_POST['id']; // Get our array back and translate it :
 		   $ids = camm_JDecode(stripslashes($ids));
 
 		    if(sizeof($ids)<1){
 				$rezult=" no ID.";
 		    } else if (sizeof($ids) == 1){
 		      $query = "DELETE FROM `plugin_camm_rule` WHERE `id` = ".$ids[0];
 				db_execute($query);
 		    } else {
 				$rezult=" Incorrect ID.";
 		    }
 		} else 
 		{
 				$rezult=" no ID.";
 		}
 	}
 	
 	//output 
 	if ($rezult==1) {
 		echo camm_JEncode(array('success' => true));
 	}else{
 		echo camm_JEncode(array('failure' => true,'error' => $rezult));
 	}		
 }

 function camm_clone_rule(){
 $rezult=1;
 
 /* ================= input validation ================= */
 
 camm_input_validate_input_regex(stripslashes(get_request_var_request("id")), "/^\\[([0-9]+,?)+\\]\$/","Uncorrect input value");
 
 /* ==================================================== */
 
 	//error checking
 	if (is_error_message()) {
 		$rezult="Input validation error.";
 	}
 
 	//business logic
 	if ($rezult==1){
 		if ( isset($_POST['id'])){
			$n_name = (string) (isset($_POST['n_name']) ? $_POST['n_name'] : 'no_name');
			$ids = $_POST['id']; // Get our array back and translate it :
			$ids = camm_JDecode(stripslashes($ids));
 
 		    if(sizeof($ids)<1){
 				$rezult=" no ID.";
 		    } else if (sizeof($ids) == 1){
 		      $query = "INSERT INTO `plugin_camm_rule` (`name`,`order`,`rule_type`,`rule_enable`,`is_function`,`is_email`,`is_mark`,`is_delete`,`function_name`,`email`,`email_mode`,`email_message`,`notes`,`json_filter`,`sql_filter`,`user_id`,`count_triggered`) " .
					                          " SELECT '" . $n_name . "',`order`,`rule_type`,'0',`is_function`,`is_email`,`is_mark`,`is_delete`,`function_name`,`email`,`email_mode`,`email_message`,`notes`,`json_filter`,`sql_filter`,'" . $_SESSION["sess_user_id"] . "','0' " . 
					  " FROM `plugin_camm_rule` WHERE `id` = ".$ids[0];
 				db_execute($query);
				$last_id = db_fetch_insert_id(false);
				
				if ($last_id<=0) {
					$rezult=0;
				};
				
 		    } else {
 				$rezult=" Incorrect ID.";
 		    }
 		} else 
 		{
 				$rezult=" no ID.";
 		}
 	}
 	
 	//output 
 	if ($rezult==1) {
 		echo camm_JEncode(array('success' => true, 'last_id'=>$last_id));
 	}else{
 		echo camm_JEncode(array('failure' => true,'error' => $rezult));
 	}		
 }
 
 function camm_execute_rule(){
 global $cacti_camm_components;
 
 $rezult=1;
 
 /* ================= input validation ================= */
 
 camm_input_validate_input_regex(stripslashes(get_request_var_request("id")), "/^\\[([0-9]+,?)+\\]\$/","Uncorrect input value");
 
 /* ==================================================== */
 
 	//error checking
 	if (is_error_message()) {
 		$rezult="Input validation error.";
 	}
 	
 	//business logic
 	if ($rezult==1) {
 		
 		$id = $_POST['id']; // Get our array back and translate it :
 		$id = camm_JDecode(stripslashes($id));
 		
 
 		if(sizeof($id)<>1){
 			$rezult=" no ID.";
 		} else if (sizeof($id) == 1){
 			$rule = db_fetch_row("SELECT * FROM `plugin_camm_rule` where `rule_enable`=1 AND `id`='" . $id[0] . "';");
 			if ((isset($rule["id"]) && ($rule["id"] == $id[0]))) {
 				if ($cacti_camm_components[$rule["rule_type"]]) {
					//Update dependencies
					camm_check_dependencies();
 					$rezult=camm_process_rule($rule, true);
 				}else{
 					$rezult = $rule["rule_type"] . " component disabled.";
 				}
 			}else{
 				$rezult = "Got incorrect rule or it is disabled";
 			}
 		} else {
 			$rezult=" no ID.";
 		}
 	}
 	
 	//output 
 	if ($rezult==1) {
 		echo camm_JEncode(array('success' => true));
 	}else{
 		echo camm_JEncode(array('failure' => true,'error' => $rezult));
 	}	
 }
 
 function camm_delete_syslog_message(){
 global $cacti_camm_components;
    
 /* ================= input validation ================= */
 
 camm_input_validate_input_regex(stripslashes(get_request_var_request("ids")), "/^\[(\"[0-9]+\",?)+\]$/");
 
 /* ==================================================== */
 
  if (is_error_message()) {
 	 exit;
 }else {
 	if ($cacti_camm_components["syslog"]) {
 		if ( isset($_POST['ids'])){
 		   $ids = $_POST['ids']; // Get our array back and translate it :
 		   $ids = camm_JDecode(stripslashes($ids));
 		    // You could do some checkups here and return '0' or other error consts.
 			// Make a single query to delete all of the presidents at the same time :
 		    if(sizeof($ids)<1){
 		      echo '{success:0}';
 		    } else if (sizeof($ids) == 1){
 		      $query = "DELETE FROM `" . read_config_option("camm_syslog_db_name") . "`.`plugin_camm_syslog` WHERE `id` = ".$ids[0];
 				db_execute($query);
 				echo camm_JEncode(array('success' => true));
 		    } else {
 				$str_ids = '';
 				for ($i=0;($i<sizeof($ids));$i++) {
 					$str_ids = $str_ids . "'" . $ids[$i] . "', ";
 				}
 				$str_ids = substr($str_ids, 0, strlen($str_ids) -2);
 
 				db_execute("DELETE FROM `" . read_config_option("camm_syslog_db_name") . "`.`plugin_camm_syslog` where `id` in ("  . $str_ids . ");");
 				echo camm_JEncode(array('success' => true));
 		    }
 		    // echo $query;  This helps me find out what the heck is going on in Firebug...
 		    
 		} else 
 		{
 				echo '{success:0}';
 		}
 	}else{
 		echo camm_JEncode(array('failure' => true,'error' => "SYSLOG NOT USED"));	
 	}
 }
 }
 
 function camm_save_rule(){
    
 if ( isset($_POST['data'])){
 	
    $ids = $_POST['data']; // Get our array back and translate it :
	camm_debug("php camm_save_rule  ids=[" . $ids . "]");
    $ids = camm_JDecode(stripslashes($ids));
     // You could do some checkups here and return '0' or other error consts.
     
     // Make a single query to delete all of the presidents at the same time :
     if(sizeof($ids)<1){
       echo camm_JEncode(array('failure' => true,'error' => 'Zerro count input data'));
     } else {
 		//$cur_user = db_fetch_cell("select username from user_auth where id=" . $_SESSION["sess_user_id"]);
 		$str_ids = '';
 		foreach($ids as $key => $value) {
 	    	//if(is_array($value)) {
 				$sql_update = ' `plugin_camm_rule` SET  ';
 				$new_record = false;
 				$id = null;
 
 				foreach($value as $key1 => $value1) {
 					
 					if ($key1 == 'id') {
 
 					}elseif(($key1 == 'newRecord') && ($value1 == 'true')){
 						$new_record = true;
 					}elseif($key1 == 'sql_filter'){
 					}elseif(($key1 == 'json_filter') && (strlen(trim($value1))>0 )){
 						$sql_update = $sql_update . " `" . $key1 . "`='" . $value1 . "', ";
 						$sql_update = $sql_update . " `sql_filter`='" . addslashes(getSQL(camm_JDecode(stripslashes($value1)))) . "', ";
 					}elseif(substr($key1, 0,3) == "is_"){
 						$sql_update = $sql_update . " `" . $key1 . "`='" . ($value1 ? 1 : 0) . "', ";
 					}else {
 						$sql_update = $sql_update . " `" . $key1 . "`='" . $value1 . "', ";
 					}
 				}
 				$sql_update = $sql_update . " `user_id`='" . $_SESSION["sess_user_id"] . "' ";
 				if ((isset($value->id)) && is_numeric($value->id) && (!$new_record)){
 					$sql_update = $sql_update . " WHERE (`id`=" . $value->id . ")";
 				}
 				if ($new_record) {
 					$sql_update = "INSERT INTO " . $sql_update;
 				}else{
 					$sql_update = "UPDATE " . $sql_update;
 				}
 			db_execute($sql_update);
 	    }
 		//$str_ids = substr($str_ids, 0, strlen($str_ids) -2);
 
 		//db_execute("DELETE FROM `plugin_camm_snmptt` where `id` in ("  . $str_ids . ");");
 		echo "{success:true}";
 	}
     // echo $query;  This helps me find out what the heck is going on in Firebug...
 } else 
 {
 	echo "{failure:true}";
 
 }
 
 }
 
 
 function camm_delete_unktraps(){
 global $cacti_camm_components;
 
 $rezult=1;
 
 /* ================= input validation ================= */
 
 camm_input_validate_input_regex(stripslashes(get_request_var_request("ids")), "/^\[(\"[0-9]+\",?)+\]$/");
 
 /* ==================================================== */
 
 	//error checking
 	if (is_error_message()) {
 		$rezult="Input validation error.";
 	}
 	
 	//business logic
 	if ($rezult==1){
 		if ($cacti_camm_components["snmptt"]) {
 			if ( isset($_POST['ids'])){
 			   $ids = $_POST['ids']; // Get our array back and translate it :
 			   $ids = camm_JDecode(stripslashes($ids));
 
 			    if(sizeof($ids)<1){
 					$rezult=" no ID.";
 			    } else if (sizeof($ids) == 1){
 			      $query = "DELETE FROM `plugin_camm_snmptt_unk` WHERE `id` = ".$ids[0];
 					db_execute($query);
 			    } else {
 					$str_ids = '';
 					for ($i=0;($i<sizeof($ids));$i++) {
 						$str_ids = $str_ids . "'" . $ids[$i] . "', ";
 					}
 					$str_ids = substr($str_ids, 0, strlen($str_ids) -2);
 					db_execute("DELETE FROM `plugin_camm_snmptt_unk` where `id` in ("  . $str_ids . ");");
 			    }
 			} else 
 			{
 					$rezult=" no ID.";
 			}
 		}else{
 			$rezult=" SNMPTT component disabled.";
 		}
 	}
 	
 	//output 
 	if ($rezult==1) {
 		echo camm_JEncode(array('success' => true));
 	}else{
 		echo camm_JEncode(array('failure' => true,'error' => $rezult));
 	}	
 }
 
 
 
 
 
 
 
 
 
 
 
 
 function camm_convert_json_to_sql_string() {
     /* ================= input validation ================= */
 
     /* ==================================================== */
 
 	 if (is_error_message()) {
 		 echo "Validation error.";
 		 exit;
 	 }
 
 $raw_json_where = (string) (isset($_POST['filter']) ? $_POST['filter'] : $_POST['filter']);
 $sql_where = getSQL(camm_JDecode(stripslashes($raw_json_where)));
 
 echo camm_JEncode(array('success' => true,'sql_filter' => $sql_where));
 	
 
 }
 
 
 function camm_get_settings() {
 global $settings;
 
 	$i = 0;
 	$group = "default";
 	foreach($settings["camm"] as $sett_name => $setting) {
 		//$settings[$i]["name_id"] = "";
 		
 		if ($setting["method"] == "spacer") {
			$group = $setting["friendly_name"];
			$i=$i-1; 		
 		}else{
		
			$n_settings[$i]["name"] = $sett_name;
			$n_settings[$i]["group"] = $group;
			$n_settings[$i]["fname"] = $setting["friendly_name"];
			$n_settings[$i]["text"] = $setting["description"];
		
			switch($setting["method"]){
				case "spacer":
					$group = $setting["friendly_name"];
					$i=$i-1;
					break;
				case "textbox":
					$n_settings[$i]["type"] = "textbox";
					if (config_value_exists($sett_name)) {
						$n_settings[$i]["value"] = db_fetch_cell("select value from settings where name='$sett_name'");
					}else{
						$n_settings[$i]["value"] = $setting["default"];
					}	
					break;
				case "drop_array":
					$n_settings[$i]["type"] = "combobox";
					
					if (config_value_exists($sett_name)) {
						$n_settings[$i]["real_value"] = db_fetch_cell("select value from settings where name='$sett_name'");
					}else{
						$n_settings[$i]["real_value"] = $setting["default"];
					}
	 
					$tmp_arr = array();
					foreach($setting["array"] as $ar_value => $ar_name) {
						$tmp_arr[]=array($ar_name,$ar_value);
						if ($ar_value == $n_settings[$i]["real_value"]) {
							$n_settings[$i]["value"] = $ar_name;
						}
					}
					$n_settings[$i]["editor"] = $tmp_arr;
					break;
				case "numberfield":
					$n_settings[$i]["type"] = "numberfield";
					$n_settings[$i]["max_value"] = $setting["max_value"];
					if (config_value_exists($sett_name)) {
						$n_settings[$i]["value"] = db_fetch_cell("select value from settings where name='$sett_name'");
					}else{
						$n_settings[$i]["value"] = $setting["default"];
					}	
					break;				
			} 
		}		
 		
 	
 	$i=$i+1;
 	}
 	
 
 	
 	echo camm_JEncode(array('success' => true,'settings' => $n_settings));
 
 }
 
 
 
 function camm_save_settings(){
    
 if ( isset($_POST['data'])){
 	
    $n_settings = $_POST['data']; // Get our array back and translate it :
 
    $n_settings = camm_JDecode(stripslashes($n_settings));
     // You could do some checkups here and return '0' or other error consts.
     
     // Make a single query to delete all of the presidents at the same time :
     if(sizeof($n_settings)<1){
       echo camm_JEncode(array('failure' => true,'error' => 'Zerro count input data'));
     } else {
 		
 		if (isset($_SESSION["sess_config_array"])) {
 			$config_array = $_SESSION["sess_config_array"];
 		}else if (isset($config["config_options_array"])) {
 			$config_array = $config["config_options_array"];
 		}
 		foreach($n_settings as $key => $n_setting) {
 			if (isset($n_setting->r_value)){
 				$tmp_value = $n_setting->r_value;
 			}else{
 				$tmp_value = $n_setting->value;
 			}
			$config_array[$n_setting->name] = $tmp_value;
			$sql_update = "REPLACE INTO settings (name, value) VALUES ('" . $n_setting->name . "', '" . addslashes($tmp_value) . "')";
			db_execute($sql_update);

 	    }
 		if (isset($_SESSION)) {
 			$_SESSION["sess_config_array"]  = $config_array;
 		}else{
 			$config["config_options_array"] = $config_array;
 		}
 		echo "{success:true}";
 	}
 } else 
 {
 	echo "{failure:true}";
 
 }
 
 }
 

 function camm_delete_records(){
 global $cacti_camm_components;
 
 $rezult=1;
 $sql_limit = '';
 
 /* ================= input validation ================= */
 							   // task: 	"camm_delete_records", 
 				               // type:  	type,
							   // id: 		a_id,
							   // val:		a_v 
 camm_input_validate_input_regex(stripslashes(get_request_var_request("id")), "/^[0-9]{1,10}$/", 'Uncorrect row id');	
 camm_input_validate_input_regex(get_request_var_request("a_id", ""), "/^[a-z,_,0-9]{10,13}$/", 'Uncorrect action id');	
 camm_input_validate_input_regex(get_request_var_request("a_v", "0"), "/^[0-9]{0,10}$/", 'Uncorrect action value');	 
 
 /* ==================================================== */
 
 	//error checking
 	if (is_error_message()) {
 		$rezult="Input validation error.";
 	}
 	
 	//business logic
 	if ($rezult==1){
	
 		$tree_id = (integer) (isset($_POST['id']) ? $_POST['id'] : '0');
 		$act_id = (string) (isset($_POST['a_id']) ? $_POST['a_id'] : '');
		$act_v = (integer) (isset($_POST['a_v']) ? $_POST['a_v'] : '0');
 		
		if ($act_id != '' && !($act_id=='cm_del_old_date' && $act_v == 0)){
			if ($tree_id > 0) {
				$tree_row = db_fetch_row("select * from `plugin_camm_tree2` where `id`=" . $tree_id . "; ");
				if ($cacti_camm_components[$tree_row["type"]]) {
					$tree_sql=camm_create_sql_from_tree($tree_id);
					
					if ($tree_row["type"] == 'snmptt') {
						$table = '`plugin_camm_snmptt`';
						$date_field = 'traptime';
					}elseif($tree_row["type"] == 'syslog') {
						$table = '`' . read_config_option("camm_syslog_db_name") . '`.`plugin_camm_syslog`';
						$date_field = 'sys_date';
					}else{
						$rezult = "Incorrect type";
					}					
					
					switch (substr($act_id, 0, 10)) {
						case 'cm_del_old':
							$perc = (integer) substr($act_id, -2);
							if ($perc > 0) {
								$all_count = (integer) db_fetch_cell("SELECT count(*) from " . $table . " where (1=1 " . $tree_sql . "); ");
								$need_count = (integer) ($all_count * $perc /100);
								$del_sql = ' 1=1 ';
								$sql_limit = ' ORDER BY ' . $date_field . ' LIMIT ' . $need_count ;
							}else{
								$rezult=" Incorrect percent value =[" . $perc . "]";
							}
						break;
						case 'cm_del_dat':
							$del_sql = ' UNIX_TIMESTAMP(' . $date_field . ') < ' . $act_v;
						break;				
						case 'cm_del_all':
							$del_sql = ' 1=1 ';
						break;				
					}
					if ($rezult==1){
						$sql = "DELETE FROM " . $table . " WHERE (" . $del_sql . " " . $tree_sql . " ) " . $sql_limit . " ;";
						$rezult = db_execute($sql);
					}

				}else{
					$rezult=$type . " component disabled.";
				}
			}else{
				$rezult=" Incorrect tree menu id";
			}
		}else{
			$rezult=" Incorrect menu item id";
		}
 	}
 	
 	//output 
 	if ($rezult==1) {
 		echo camm_JEncode(array('success' => true, 'type'=>$tree_row["type"]));
 	}else{
 		echo camm_JEncode(array('failure' => true,'error' => $rezult));
 	}	
 }
 
  
 
 ?>
 
