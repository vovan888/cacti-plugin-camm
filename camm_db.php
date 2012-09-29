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
 
 	camm_input_validate_input_regex(get_request_var_request("task"), "/^camm_[a-zA-Z_]*/",'Unrecognised command [' . get_request_var_request("task") . ']');
 	//camm_input_validate_input_regex(get_request_var_request("task"), "view_traps|view_unktraps|delete_traps|delete_unktraps|view_rules|get_menu_tree|get_eventname|save_Rule|delete_rule|view_stats|get_graphurl|view_rezults|count_rezults|recreate_tree|get_eventnames|get_severites|test_rule|get_full_trap|get_full_unk_trap|get_full_rule|convert_json_to_sql_string|get_user_functions|view_syslog|get_facilitys|get_prioritys|get_full_sys_mes",'Unrecognised command [' . get_request_var_request("task") . ']');
    
     /* ==================================================== */
 
 
 	 if (is_error_message()) {
 		 echo "Validation error.";
 		 exit;
 	 }
 	
 	$task = '';
 	if ( isset($_POST['task'])){
 		$task = $_POST['task'];
 	}elseif( isset($_GET['task'])){
 		$task = $_GET['task'];
 	}
 
   $sql_where = '';
   
 	 
 	if (camm_user_func_exists($task)) {
 		call_user_func($task);
 	}else{
 		echo "Unsupported command [" . $task . "].";
 	}	
 	
 
 function camm_view_rezults() {
     /* ================= input validation ================= */
 
 
     /* ==================================================== */
 
 $rezult = array();
 	
 if (isset($_SESSION["camm_output_messages"])) {
 	$i = -1;
 	if (is_array($_SESSION["camm_output_messages"])) {
 		
 		
 		foreach ($_SESSION["camm_output_messages"] as $current_message) {
 			$i = $i +1;
 			$rezult[$i]["id"]=$i;
 			$rezult[$i]["group"]=((isset($current_message["object"])) ? $current_message["object"] : "");
 			$rezult[$i]["title"]=$current_message["device_descr"];
 			$rezult[$i]["message"]=$current_message["message"];
 			if ($current_message["type"]=="update_db"){
 				$rezult[$i]["step_rezult"]=((isset($current_message["step_rezult"])) ? $current_message["step_rezult"] : "");
 				$rezult[$i]["step_data"]=((isset($current_message["step_data"])) ? $current_message["step_data"] : "");				
 				$rezult[$i]["check_rezult"]=((isset($current_message["step_rezult"])) ? $current_message["step_rezult"] : "");
 				$rezult[$i]["check_data"]=((isset($current_message["step_data"])) ? $current_message["step_data"] : "");	
 			}else{
 				$rezult[$i]["step_rezult"]=((isset($current_message["step_rezult"])) ? $current_message["step_rezult"] : "");
 				$rezult[$i]["step_data"]=((isset($current_message["step_data"])) ? $current_message["step_data"] : "");			
 				$rezult[$i]["check"]=((isset($current_message["check_rezult"])) ? $current_message["check_rezult"] : "no access");
 				$rezult[$i]["check_data"]=((isset($current_message["check_data"])) ? $current_message["check_data"] : "");
 			}
 
 		}
 	}
 }	
 	
 //kill_session_var("camm_output_messages");	
 	$jsonresult = camm_JEncode($rezult);
 	echo '({"rezults":'.$jsonresult.'})';	
 
 kill_session_var("camm_output_messages");		
 	
 }
 
 function camm_get_start_variable() {
 global $config, $cacti_camm_components;
 
 $out_array = array();
 
     /* ================= input validation ================= */
 
 
     /* ==================================================== */
 
 $int_count_output_mess = 0;
 	
 if (isset($_SESSION["camm_output_messages"])) {
 	if (is_array($_SESSION["camm_output_messages"])) {
 		$int_count_output_mess = sizeof($_SESSION["camm_output_messages"]);
 	}
 }	
 
 $out_array['success']= true;
 $out_array['count_output_mess']= $int_count_output_mess;
 $out_array['is_camm_admin']= is_camm_admin();
 $out_array['cacti_path']= $config['url_path'];
 $out_array['graph_camm_url_big']=  get_graph_camm_url('%camm%poller%big%stat%');
 $out_array['graph_camm_url_row']=  get_graph_camm_url('%camm%poller rows%stat%');
 $out_array['graph_camm_url_time']=  get_graph_camm_url('%camm%poller%time%stat%');
 $out_array['camm_num_rows']= read_config_option("camm_num_rows", true);
 $out_array['camm_startup_tab']= (int) read_config_option("camm_startup_tab", true);
 $out_array['camm_unktrap_tab_update']= read_config_option("camm_snmptt_unktrap_tab_update", true);
 $out_array['camm_trap_tab_update']= read_config_option("camm_snmptt_trap_tab_update", true);
 $out_array['camm_sys_tab_update']= read_config_option("camm_sys_tab_update", true);
 $out_array['camm_use_snmptt']= $cacti_camm_components["snmptt"];
 $out_array['camm_use_syslog']= $cacti_camm_components["syslog"];
 $out_array['camm_date']=  date("Y-m-d  H:i:s");
 $out_array['camm_tree_menu_width']=  read_config_option("camm_tree_menu_width", true);
 
 
 
 
 
 echo camm_JEncode($out_array);
 }
 
 
 function camm_get_traps_records() {
 global $cacti_camm_components;
 
 $rezult=1;
 
     /* ================= input validation ================= */
 
 	camm_input_validate_input_regex(get_request_var_request("start", "0"), "/^[0-9]{0,10}$/", 'Uncorrect input data');
 	camm_input_validate_input_regex(get_request_var_request("limit", "50"), "/^[0-9]{1,4}$/" ,'Uncorrect input data');
	camm_input_validate_input_regex(get_request_var_request("tree_id"), "/^([0-9]{1,9}|root)$/", 'Uncorrect input data [tree_id]');
	camm_input_validate_input_regex(get_request_var_request("rule_id"), "/^[0-9]{1,5}$/" ,'Uncorrect input data [rule_id]');
 	   
     /* ==================================================== */
 
 	//error checking
 	if (is_error_message()) {
 		$rezult="Input validation error.";
 	}
 	//business logic
 	if ($rezult==1){
 		if ($cacti_camm_components["snmptt"]) {
 			$row_start = (integer) (isset($_POST['start']) ? $_POST['start'] : 0);
 			$row_limit = (integer) (isset($_POST['limit']) ? $_POST['limit'] : read_config_option("camm_num_rows"));
 			$tree_id = (integer) (isset($_POST['tree_id']) ? $_POST['tree_id'] : 0);
 			$rule_id = (integer) (isset($_POST['rule_id']) ? $_POST['rule_id'] : 0);
			$raw_json_where = (string) (isset($_POST['filter']) ? $_POST['filter'] : '');
			$raw_json_where_chart = (string) (isset($_POST['filter_chart']) ? $_POST['filter_chart'] : '');
			
			$table = '`plugin_camm_snmptt`';

			$use_fqdn = (read_config_option("camm_use_fqdn", true) == '1');	
 
 			$query_string = "";
 			if ($raw_json_where_chart == '') {
				$sql_where = getSQL(camm_JDecode(stripslashes($raw_json_where)));
			}else{
				$sql_where = getSQL(camm_JDecode(stripslashes($raw_json_where_chart)));
			}
 
			if ($rule_id == 0) {
				$tree_sql=camm_create_sql_from_tree($tree_id);				
			}else{//если указан rule_id значит выборка на основе чарта. Учитываем только его.
				$tree_sql = "";
			}
			
			$sql_status = '';
 			if (read_config_option("camm_show_all_records") == "0") {
 				$sql_status = " and " . $table . ".`status`=2 ";
 			}
 
 			if (read_config_option("camm_join_field") == "sourceip") {
 				$join_field = "agentip";
 			}elseif (read_config_option("camm_join_field") == "hostname"){
 				$join_field = "hostname";		
 			}else{
 				$join_field = "agentip";
 			}

			if ($use_fqdn && $join_field == "host") {
				$sql_fqdn_host = " SUBSTRING_INDEX(`host`.`hostname`,'.',1) ";
			}else{
				$sql_fqdn_host = " `host`.`hostname` ";
			}
			
			$tree_node = db_fetch_row("SELECT * FROM `plugin_camm_tree2` where `id` = '" . $tree_id . "';");
			
			$sql_index = "";
			if (isset($tree_node["_lvl"]) && (($tree_node["_lvl"] == 2) || ($tree_node["_lvl"] == 3) || ($tree_node["_lvl"] == 4))) {
				$sql_index = " USE INDEX (`hostname`) ";
			}			
			
			$period_sql = " and " . camm_create_sql_from_period($tree_node['period'],$tree_node['type']);
		
			$marker = -1; //по умолчанию в древовидном меню выделено ветка НЕ маркер
			if ($rule_id == 0) {
				if (($tree_id > 0) and ($tree_node['_is_marker'] == 1)) {
					if ($tree_node['gr_v'] > 0) {
						$marker = $tree_node['gr_v'];
					} else{
						$marker = 0;
					}
				}
			}else{ //если указан rule_id значит выборка на основе чарта. Учитываем только его.
				$marker = $rule_id;
			}
 
			//Группируем только в том случае если есть двойные хосты (хосты с одинаковыми hostname)
			//или выделено пункт меню с хостом а не маркером...
			if (read_config_option("camm_use_group_by_host") == '1' or ($marker < 0)) {
				$sql_group = ' group by id ';
			}else{
				$sql_group = '';
			}
			
			if ($marker < 0) {
				//поиск по сообщениям, потом join таблицы с ключами алертов
	
				$query_string = " SELECT  CONVERT(GROUP_CONCAT(`plugin_camm_keys`.`rule_id`) USING UTF8) as rule_id, temp_snmtr.*, host.description, host.host_template_id, host.id as device_id " .
					"from (SELECT  " . $table . ".* FROM " . $table . " " . $sql_index . " WHERE $sql_where $tree_sql $period_sql order by traptime desc "; 
				$query_string .= " LIMIT " . $row_start . "," . $row_limit . " ) as temp_snmtr ";;
					//add table with alerted rows
				$query_string .= " Left join plugin_camm_keys on (temp_snmtr.id=plugin_camm_keys.krid and plugin_camm_keys.ktype='2') ";
					//группируем только при необходимости - group by id because cacti hosts table may have more than one device with one hostname
				$query_string .= " Left join host on (temp_snmtr." . $join_field . "=" . $sql_fqdn_host . ") " . $sql_group;

				
					$total_rows = db_fetch_cell("SELECT count(*) FROM " . $table . " WHERE " . $sql_where  . " " .  $tree_sql . " " . $period_sql . " ;");
			}
			else
			{
				//поиск алертам/правилам, потом join таблицы с сообщениями
				//группируем только при необходимости - group by id because cacti hosts table may have more than one device with one hostname
				$sql_marker = "";
				if (($tree_node['_lvl'] > 1) || ($rule_id > 0)) {  //выделен  маркер с конкретным ИД
						$sql_marker = " and `plugin_camm_rule`.`id`='" . $marker . "' ";
					$query_string = " SELECT `temp_key`.*,`host`.`description`, `host`.`host_template_id`, `host`.`id` as device_id " .
						  " FROM (SELECT `plugin_camm_keys`.*, " . $table . ".* from " . $table . " left join plugin_camm_keys on (`krid`=" . $table . ".`id`) " .
						  " left join plugin_camm_rule on (`plugin_camm_keys`.`rule_id`=`plugin_camm_rule`.`id`) " .
						  " where " . $sql_where  . " " . $period_sql . " and `plugin_camm_rule`.`is_mark`=1 " . $sql_marker . " " . $sql_status .
						  " order by `krid` desc LIMIT " . $row_start . "," . $row_limit . ") as temp_key " .
						  " Left join `host` on (temp_key." . $join_field . "=" . $sql_fqdn_host . ") " .
						  $sql_group;
					$total_rows = db_fetch_cell("SELECT count(*) from " . $table . " left join plugin_camm_keys on (`krid`=" . $table . ".`id`) " .
							" left join plugin_camm_rule on (`rule_id`=`plugin_camm_rule`.`id`) " .
							" where " . $sql_where  . " " . $period_sql . " and plugin_camm_rule.is_mark=1 " . $sql_marker . " " . $sql_status );
				}else {//иначе - выделена сама ветка Маркеры и нужно вывести сообщения с маркерами (при этом одному сообщению может соотвествовать несколько маркеров).

					$query_string = " SELECT `temp_key`.*,`host`.`description`, `host`.`host_template_id`, `host`.`id` as device_id " .
						  " FROM (SELECT `plugin_camm_keys`.*, " . $table . ".* from " . $table . " left join plugin_camm_keys on (`krid`=" . $table . ".`id`) " .
						  " left join plugin_camm_rule on (`plugin_camm_keys`.`rule_id`=`plugin_camm_rule`.`id`) " .
						  " where " . $sql_where  . " " . $period_sql . " and `plugin_camm_rule`.`is_mark`=1 " . $sql_marker . " " . $sql_status .
						  " order by `krid` LIMIT " . $row_start . "," . $row_limit . ") as temp_key " .
						  " Left join `host` on (temp_key." . $join_field . "=" . $sql_fqdn_host . ") " .
						  $sql_group;
					$total_rows = db_fetch_cell("SELECT count(*) from " . $table . " left join plugin_camm_keys on (`krid`=" . $table . ".`id`) " .
							" left join plugin_camm_rule on (`rule_id`=`plugin_camm_rule`.`id`) " .
							" where " . $sql_where  . " " . $period_sql . " and plugin_camm_rule.is_mark=1 " . $sql_marker . " " . $sql_status );
				}
			}
 		
 		}else{
 			$rezult=" SNMPTT component disabled.";
 		}
 	}
 	
 	//output 
 	if ($rezult==1) {
 		if($total_rows>0){
 			$rows = db_fetch_assoc($query_string);
 			echo camm_JEncode(array('success' => true,'total' => $total_rows, "results" => $rows));
 		}else{
 			echo camm_JEncode(array('success' => true,'total' => "0", "results" => ""));
 		}
 	}else{
 		echo camm_JEncode(array('failure' => true,'error' => $rezult));
 	}	
 }
 
 
 
 function camm_get_unktraps_records() {
 global $cacti_camm_components;
 
 $rezult=1;
 
     /* ================= input validation ================= */
 
 	camm_input_validate_input_regex(get_request_var_request("start", "0"), "/^[0-9]{0,10}$/");
 	camm_input_validate_input_regex(get_request_var_request("limit", "50"), "/^[0-9]{1,4}$/");
 	   
     /* ==================================================== */
 
 	//error checking
 	if (is_error_message()) {
 		$rezult="Input validation error.";
 	}
 	
 	//business logic
 	if ($rezult==1){
 		if ($cacti_camm_components["snmptt"]) {
 			$sql_where = "";
 
 			$row_start = (integer) (isset($_POST['start']) ? $_POST['start'] : $_POST['start']);
 			$row_limit = (integer) (isset($_POST['limit']) ? $_POST['limit'] : read_config_option("camm_num_rows"));
 			$raw_json_where = (string) (isset($_POST['filter']) ? $_POST['filter'] : $_POST['filter']);
			
			$use_fqdn = (read_config_option("camm_use_fqdn", true) == '1');	
 
 			$query_string = "";
 			$sql_where = getSQL(camm_JDecode(stripslashes($raw_json_where)));
 				
  			if (read_config_option("camm_join_field") == "sourceip") {
 				$join_field = "agentip";
 			}elseif (read_config_option("camm_join_field") == "hostname"){
 				$join_field = "hostname";		
 			}else{
 				$join_field = "agentip";
 			}
			
 			$query_string = " SELECT temp_unk.*, host.description, host.host_template_id, host.id as device_id " .
 				"from (SELECT plugin_camm_snmptt_unk.* FROM plugin_camm_snmptt_unk WHERE $sql_where "; 
 
 			$query_string .= " LIMIT " . $row_start . "," . $row_limit;
 
			
			if ($use_fqdn && $join_field == 'hostname'){
				$query_string .= ") as temp_unk Left join host on (temp_unk." . $join_field . "=SUBSTRING_INDEX(`host`.`hostname`,'.',1))";
			} else {
				$query_string .= ") as temp_unk Left join host on (temp_unk." . $join_field . "=host.hostname)";
			} 				
 
 			$total_rows = db_fetch_cell("SELECT count(plugin_camm_snmptt_unk.id) FROM plugin_camm_snmptt_unk WHERE $sql_where;");
 		}else{
 			$rezult=" SNMPTT component disabled.";
 		}
 	}
 	
 	//output 
 	if ($rezult==1) {
 		if($total_rows>0){
 			$rows = db_fetch_assoc($query_string);
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
 
 function camm_get_graphurl() {
 global $config;
 $rezult = '';
 
 	if (strlen(trim(read_config_option("camm_general_graphs_ids")))>0) {
 
 	$graph_ids=explode(',',read_config_option("camm_general_graphs_ids"));
 	
 	if (sizeof($graph_ids)>0){
 		foreach ($graph_ids as $graph_id) {
 			$graph_id = trim($graph_id);
 			if ((is_numeric($graph_id)) && ($graph_id != "")) {
 				$graph_row=db_fetch_row("SELECT * FROM `graph_templates_graph` where `local_graph_id`='" . $graph_id . "'; ");
 				if (sizeof($graph_row) > 0) {
 					$rezult=$rezult . '<img border="0" alt="' . $graph_row['title_cache'] . '" src="' . $config['url_path'] . 'graph_image.php?local_graph_id=' . $graph_id . '">';
 				}
 			}
 		}
 	}
 
 	echo $rezult;
 
 	}
 
 }
 
 
 function camm_get_stats() {
 global $cacti_camm_components, $camm_poller_purge_frequencies;
 
 $rezult = array();
 $i = 0;
 
 $tables_info = db_fetch_assoc("show table status where name in ('plugin_camm_snmptt','plugin_camm_tree2','plugin_camm_snmptt_unk') ");
 
 if ($cacti_camm_components["syslog"]) {
 	$table_syslog = db_fetch_row("show table status FROM `" . read_config_option("camm_syslog_db_name") . "` where name='plugin_camm_syslog'");
 	if (sizeof($table_syslog)>0) {
 		$tables_info[]=$table_syslog;
 	}
 }
 
 	if (sizeof($tables_info)>0) {
 		foreach ($tables_info as $table) {
 			$group_name = "Table " . $table["Name"] . " detailed info";
 			$rezult[$i]["type"] = $group_name;
 			$rezult[$i]["name"] = "Rows";
 			$rezult[$i]["value"] = number_format($table["Rows"], 0, ' ', ' ');
 			$i = $i + 1;
 			$rezult[$i]["type"] = $group_name;
 			$rezult[$i]["name"] = "Data Length";
 			$rezult[$i]["value"] = camm_format_filesize($table["Data_length"]);
 			$i = $i + 1;
 			$rezult[$i]["type"] = $group_name;
 			$rezult[$i]["name"] = "Index Length";
 			$rezult[$i]["value"] = camm_format_filesize($table["Index_length"]);
 			$i = $i + 1;
 			$rezult[$i]["type"] = $group_name;
 			$rezult[$i]["name"] = "Auto increment";
 			$rezult[$i]["value"] = $table["Auto_increment"];
 			$i = $i + 1;
 			$rezult[$i]["type"] = $group_name;
 			$rezult[$i]["name"] = "Update time";
 			$rezult[$i]["value"] = $table["Update_time"] . "  (" . camm_format_datediff(strtotime($table["Update_time"])) . ")";
 			$i = $i + 1;
 			$rezult[$i]["type"] = $group_name;
 			$rezult[$i]["name"] = "Check time";
 			$rezult[$i]["value"] = $table["Check_time"];
 			$i = $i + 1;			
 		}
 	}
 
 #info from snmptt stats table
 	if ($cacti_camm_components["snmptt"]) {
 		$table_snmptt_stats = db_fetch_row("show table status where name='plugin_camm_snmptt_stat'");
 		$group_name = "1. Stats from SMPTT stat table (since last snmptt service restart)";
 		if (sizeof($table_snmptt_stats)>0) {
 			$snmptt_last_stats = db_fetch_assoc("SELECT * FROM `plugin_camm_snmptt_stat` order by `stat_time` desc limit 2;");
 				if (sizeof($snmptt_last_stats) > 0) {
 						if (sizeof($snmptt_last_stats) > 0) {
 							$group_name = $group_name . " and [from last stat period]";
 							$snmptt_last_stat2 = $snmptt_last_stats[0];
 						}
 					
 					
 					$snmptt_last_stat1 = $snmptt_last_stats[1];
 					
 					$rezult[$i]["type"] = $group_name;
 					$rezult[$i]["name"] = "Last stat date";
 					$rezult[$i]["value"] = $snmptt_last_stat1["stat_time"];
 					$i = $i + 1;
 					$rezult[$i]["type"] = $group_name;
 					$rezult[$i]["name"] = "Total traps received";
 					$rezult[$i]["value"] = ((isset($snmptt_last_stat2["total_received"])) ? number_format($snmptt_last_stat1["total_received"], 0, ' ', ' ') . "    [" . number_format(($snmptt_last_stat2["total_received"] - $snmptt_last_stat1["total_received"]), 0, ' ', ' ') . "]":number_format($snmptt_last_stat1["total_received"], 0, ' ', ' '));
 					$i = $i + 1;
 					$rezult[$i]["type"] = $group_name;
 					$rezult[$i]["name"] = "Total traps translated";
 					$rezult[$i]["value"] = ((isset($snmptt_last_stat2["total_translated"])) ? number_format($snmptt_last_stat1["total_translated"], 0, ' ', ' ') . "    [" . number_format(($snmptt_last_stat2["total_translated"] - $snmptt_last_stat1["total_translated"]), 0, ' ', ' ') . "]":number_format($snmptt_last_stat1["total_translated"], 0, ' ', ' '));
 					$i = $i + 1;
 					$rezult[$i]["type"] = $group_name;
 					$rezult[$i]["name"] = "Total traps ignored";
 					$rezult[$i]["value"] = ((isset($snmptt_last_stat2["total_ignored"])) ? number_format($snmptt_last_stat1["total_ignored"], 0, ' ', ' ') . "    [" . number_format(($snmptt_last_stat2["total_ignored"] - $snmptt_last_stat1["total_ignored"]), 0, ' ', ' ') . "]":number_format($snmptt_last_stat1["total_ignored"], 0, ' ', ' '));					
 					$i = $i + 1;
 					$rezult[$i]["type"] = $group_name;
 					$rezult[$i]["name"] = "Total unknown traps";
 					$rezult[$i]["value"] = ((isset($snmptt_last_stat2["total_unknown"])) ? number_format($snmptt_last_stat1["total_unknown"], 0, ' ', ' ') . "    [" . number_format(($snmptt_last_stat2["total_unknown"] - $snmptt_last_stat1["total_unknown"]), 0, ' ', ' ') . "]":number_format($snmptt_last_stat1["total_unknown"], 0, ' ', ' '));										
 					$i = $i + 1;					
 				}else{
 					$rezult[$i]["type"] = $group_name;
 					$rezult[$i]["name"] = "General";
 					$rezult[$i]["value"] = "Table [plugin_camm_snmptt_stat] empty";
 					$i = $i + 1;				
 				}
 		}else{
 			$rezult[$i]["type"] = $group_name;
 			$rezult[$i]["name"] = "General";
 			$rezult[$i]["value"] = "Table [plugin_camm_snmptt_stat] not exist";
 			$i = $i + 1;
 		}
 	}	
 
 #info from camm poller output
 		$poller_camm_stats = db_fetch_assoc("SELECT * FROM settings where name like 'camm_%';");
 		foreach($poller_camm_stats as $row) {
 			$poller_camm_stats_new[$row['name']] = $row['value'];
 		}		
 		$group_name = "2. Stats from CaMM Poller output";
 		if (sizeof($poller_camm_stats_new)>0) {
 			//$snmptt_last_stats = db_fetch_assoc("SELECT * FROM `plugin_camm_snmptt_stat` order by `stat_time` desc limit 2;");
 
 			$rezult[$i]["type"] = $group_name;
 			$rezult[$i]["name"] = "Last poller run time";
			if ($poller_camm_stats_new["camm_last_run_time"] == ""){
				$poller_camm_stats_new["camm_last_run_time"] = 0;
			}
 			$rezult[$i]["value"] = date('Y-m-d h:i:s', $poller_camm_stats_new["camm_last_run_time"]) . "  (" . camm_format_datediff(($poller_camm_stats_new["camm_last_run_time"])) . ")";
 			$i = $i + 1;
 			if ($cacti_camm_components["snmptt"] && (isset($poller_camm_stats_new["camm_last_snmptttreedb_time"]) && isset($poller_camm_stats_new["camm_stats_snmptt_tree"]))) {
 				$rezult[$i]["type"] = $group_name;
 				$rezult[$i]["name"] = "Last snmptt tree menu recreate time";
 				$rezult[$i]["value"] = date('Y-m-d h:i:s', $poller_camm_stats_new["camm_last_snmptttreedb_time"]) . "  (" . camm_format_datediff(($poller_camm_stats_new["camm_last_snmptttreedb_time"])) . ")";
 				$i = $i + 1;
 				$poller_camm_stats_new["camm_stats_snmptt_tree"] = substr(stristr($poller_camm_stats_new["camm_stats_snmptt_tree"], ":"), 1);
 				$rezult[$i]["type"] = $group_name;
 				$rezult[$i]["name"] = "Last snmptt tree menu recreate duration";
 				$rezult[$i]["value"] = $poller_camm_stats_new["camm_stats_snmptt_tree"] . " sec.";
 				$i = $i + 1;				
 			}
 			
 			if ($cacti_camm_components["syslog"] && (isset($poller_camm_stats_new["camm_last_syslogtreedb_time"]) && isset($poller_camm_stats_new["camm_stats_syslog_tree"]))) {
 				$rezult[$i]["type"] = $group_name;
 				$rezult[$i]["name"] = "Last syslog tree menu recreate time";
 				$rezult[$i]["value"] = date('Y-m-d h:i:s', $poller_camm_stats_new["camm_last_syslogtreedb_time"]) . "  (" . camm_format_datediff(($poller_camm_stats_new["camm_last_syslogtreedb_time"])) . ")";
 				$i = $i + 1;
 				$poller_camm_stats_new["camm_stats_syslog_tree"] = substr(stristr($poller_camm_stats_new["camm_stats_syslog_tree"], ":"), 1);
 				$rezult[$i]["type"] = $group_name;
 				$rezult[$i]["name"] = "Last syslog tree menu recreate duration";
 				$rezult[$i]["value"] = $poller_camm_stats_new["camm_stats_syslog_tree"] . " sec.";
 				$i = $i + 1;				
 			}
      $group_name = "3. Stats from CaMM AutoPurge Process";



 			$rezult[$i]["type"] = $group_name;
 			$rezult[$i]["name"] = "AutoPurge Process Timing (hours)";
 			$rezult[$i]["value"] = $camm_poller_purge_frequencies[$poller_camm_stats_new["camm_autopurge_timing"]];
 			$i = $i + 1;
 			$rezult[$i]["type"] = $group_name;
 			$rezult[$i]["name"] = "Last AutoPurge Process run time";
			if (!isset($poller_camm_stats_new["camm_last_autopurge_run_time"])) {
				$poller_camm_stats_new["camm_last_autopurge_run_time"] = 0;
			}
			$rezult[$i]["value"] = date('Y-m-d h:i:s', $poller_camm_stats_new["camm_last_autopurge_run_time"]) . "  (" . camm_format_datediff(($poller_camm_stats_new["camm_last_autopurge_run_time"])) . ")";
 			$i = $i + 1;
       if (isset($poller_camm_stats_new["camm_stats"])) {			
 				list($del_traps, $del_unk_traps, $del_sys_messages) = sscanf($poller_camm_stats_new["camm_stats"], "del_traps:%s del_unk_traps:%s del_sys_messages:%s");
 				if ($cacti_camm_components["snmptt"]) {
 					$rezult[$i]["type"] = $group_name;
 					$rezult[$i]["name"] = "Count snmptt traps deleted";
 					$rezult[$i]["value"] = number_format($del_traps, 0, ' ', ' ');
 					$i = $i + 1;				
 					$rezult[$i]["type"] = $group_name;
 					$rezult[$i]["name"] = "Count unk. snmptt traps deleted";
 					$rezult[$i]["value"] = number_format($del_unk_traps, 0, ' ', ' ');
 					$i = $i + 1;					
 				}
 				if ($cacti_camm_components["syslog"]) {				
 					$rezult[$i]["type"] = $group_name;
 					$rezult[$i]["name"] = "Count syslog message deleted";
 					$rezult[$i]["value"] = number_format($del_sys_messages, 0, ' ', ' ');
 					$i = $i + 1;
 				}
 			}
 			
 		}else{
 			$rezult[$i]["type"] = $group_name;
 			$rezult[$i]["name"] = "General";
 			$rezult[$i]["value"] = "CaMM Poller output data not exist";
 			$i = $i + 1;
 		}
 	
 	echo camm_JEncode(array('success' => true,'stats' => $rezult));
 }
 
 
 
 
function camm_get_rules_records() {
$rezult=1;
 
     /* ================= input validation ================= */
 
 	camm_input_validate_input_regex(get_request_var_request("start", "0"), "/^[0-9]{0,10}$/");
 	camm_input_validate_input_regex(get_request_var_request("limit", "50"), "/^[0-9]{1,4}$/");
 	   
     /* ==================================================== */
 
 	//error checking
 	if (is_error_message()) {
 		$rezult="Input validation error.";
 	}
 	
 	//business logic
 	if ($rezult==1){
	
	   $sql_where = "";
	 
	   $row_start = (integer) (isset($_POST['start']) ? $_POST['start'] : "0");
	   $row_limit = (integer) (isset($_POST['limit']) ? $_POST['limit'] : "50");
	   $raw_json_where = (string) (isset($_POST['filter']) ? $_POST['filter'] : $_POST['filter']);
	   
		$query_string = "";
		$sql_where = getSQL(camm_JDecode(stripslashes($raw_json_where)));
			
		
		$query_string = " SELECT SQL_CALC_FOUND_ROWS plugin_camm_rule.*,user_auth.username FROM plugin_camm_rule left join user_auth on (plugin_camm_rule.user_id=user_auth.id) WHERE $sql_where";
		$query_string .= " LIMIT " . $row_start . "," . $row_limit;
		$rows = db_fetch_assoc($query_string);
		$total_rows = db_fetch_cell("SELECT FOUND_ROWS()"); 
 	}
 	
 	//output 
 	if ($rezult==1) {
 		if($total_rows>0){
 			echo camm_JEncode(array('success' => true,'total' => $total_rows, "results" => $rows));
 		}else{
 			echo camm_JEncode(array('success' => true,'total' => "0", "results" => ""));
 		}
 	}else{
 		echo camm_JEncode(array('failure' => true,'error' => $rezult));
 	}	


 }
 
 
 function camm_get_syslog_records() {
 global $cacti_camm_components;
 
 
 $rezult=1;
 
     /* ================= input validation ================= */
 
 	camm_input_validate_input_regex(get_request_var_request("start", "0"), "/^[0-9]{0,10}$/", 'Uncorrect input data');
 	camm_input_validate_input_regex(get_request_var_request("limit", "50"), "/^[0-9]{1,4}$/" ,'Uncorrect input data');
 	camm_input_validate_input_regex(get_request_var_request("tree_id"), "/^([0-9]{1,9}|root)$/", 'Uncorrect input data [tree_id]');
	camm_input_validate_input_regex(get_request_var_request("rule_id"), "/^[0-9]{1,5}$/" ,'Uncorrect input data [rule_id]');
 	   
     /* ==================================================== */
 
 	//error checking
 	if (is_error_message()) {
 		$rezult="Input validation error.";
 	}

 	//business logic
 	if ($rezult==1){
 		if ($cacti_camm_components["syslog"]) {
 			$row_start = (integer) (isset($_POST['start']) ? $_POST['start'] : 0);
 			$row_limit = (integer) (isset($_POST['limit']) ? $_POST['limit'] : read_config_option("camm_num_rows"));
 			$tree_id = (integer) (isset($_POST['tree_id']) ? $_POST['tree_id'] : 0);
			$rule_id = (integer) (isset($_POST['rule_id']) ? $_POST['rule_id'] : 0);
 			$raw_json_where = (string) (isset($_POST['filter']) ? $_POST['filter'] : '');
			$raw_json_where_chart = (string) (isset($_POST['filter_chart']) ? $_POST['filter_chart'] : '');
			
			$table = '`' . read_config_option("camm_syslog_db_name") . '`.`plugin_camm_syslog`';

		
			$use_fqdn = (read_config_option("camm_use_fqdn", true) == '1');	
			
			$query_string = "";			
 			if ($raw_json_where_chart == '') {
				$sql_where = getSQL(camm_JDecode(stripslashes($raw_json_where)));
			}else{
				$sql_where = getSQL(camm_JDecode(stripslashes($raw_json_where_chart)));
			}
			
			if ($rule_id == 0) {
				$tree_sql=camm_create_sql_from_tree($tree_id);				
			}else{//если указан rule_id значит выборка на основе чарта. Учитываем только его.
				$tree_sql = "";
			}
						
			$sql_status = '';
 			if (read_config_option("camm_show_all_records") == "0") {
 				$sql_status = " and " . $table . ".`status`=2 ";
 			}
 
if (read_config_option("camm_join_field") == "sourceip") {
 				$join_field = "sourceip";
 			}elseif (read_config_option("camm_join_field") == "hostname"){
 				$join_field = "host";		
 			}else{
 				$join_field = "sourceip";
 			}		

			if ($use_fqdn && $join_field == "host") {
				$sql_fqdn_host = " SUBSTRING_INDEX(`host`.`hostname`,'.',1) ";
			}else{
				$sql_fqdn_host = " `host`.`hostname` ";
			}

			$tree_node = db_fetch_row("SELECT * FROM `plugin_camm_tree2` where `id` = '" . $tree_id . "';");
			
			$sql_index = "";
			if (isset($tree_node["_lvl"]) && (($tree_node["_lvl"] == 2) || ($tree_node["_lvl"] == 3) || ($tree_node["_lvl"] == 4))) {
				$sql_index = " USE INDEX (`hostname`) ";
			}
			
			$period_sql = " and " . camm_create_sql_from_period($tree_node['period'],$tree_node['type']);
		
			$marker = -1; //по умолчанию в древовидном меню выделено ветка НЕ маркер
			if ($rule_id == 0) {
				if (($tree_id > 0) and ($tree_node['_is_marker'] == 1)) {
					if ($tree_node['gr_v'] > 0) {
						$marker = $tree_node['gr_v'];
					} else{
						$marker = 0;
					}
				}
			}else{ //если указан rule_id значит выборка на основе чарта. Учитываем только его.
				$marker = $rule_id;
			}
			
			//Группируем только в том случае если есть двойные хосты (хосты с одинаковыми hostname)
			//или выделено пункт меню с хостом а не маркером...
			if (read_config_option("camm_use_group_by_host") == '1' or ($marker < 0)) {
				$sql_group = ' group by id ';
			}else{
				$sql_group = '';
			}
			
			if ($marker < 0) {
				//поиск по сообщениям, потом join таблицы с ключами алертов
				$query_string = " SELECT CONVERT(GROUP_CONCAT(`plugin_camm_keys`.`rule_id`) USING UTF8) as rule_id, temp_sys.*, host.description, host.host_template_id, host.id as device_id " .
					"from (SELECT id, `facility`, `priority`, `sys_date`, `host`, `message`,`sourceip`,`status` FROM " . $table . " " . $sql_index . "  WHERE $sql_where $tree_sql $period_sql order by sys_date desc "; 
				$query_string .= " LIMIT " . $row_start . "," . $row_limit . " ) as temp_sys ";
				//add table with alerted rows
				$query_string .= " Left join plugin_camm_keys on (temp_sys.id=plugin_camm_keys.krid and plugin_camm_keys.ktype='1') ";
				//группируем только при необходимости - group by id because cacti hosts table may have more than one device with one hostname
				$query_string .= " Left join host on (temp_sys." . $join_field . "=" . $sql_fqdn_host . ") " . $sql_group;

				$total_rows = db_fetch_cell("SELECT count(*) FROM " . $table . " WHERE " . $sql_where  . " " .  $tree_sql . " " . $period_sql . " ;");
			}
			else
			{
				//поиск алертам/правилам, потом join таблицы с сообщениями
				//группируем только при необходимости - group by id because cacti hosts table may have more than one device with one hostname
				$sql_marker = "";
				if (($tree_node['_lvl'] > 1) || ($rule_id > 0)) {  //выделен  маркер с конкретным ИД
					$sql_marker = " and `plugin_camm_rule`.`id`='" . $marker . "' ";
					$query_string = " SELECT `temp_key`.*,`host`.`description`, `host`.`host_template_id`, `host`.`id` as device_id " .
						  " FROM (SELECT `plugin_camm_keys`.*, " . $table . ".* from " . $table . " left join plugin_camm_keys on (`krid`=" . $table . ".`id`) " .
						  " left join plugin_camm_rule on (`plugin_camm_keys`.`rule_id`=`plugin_camm_rule`.`id`) " .
						  " where " . $sql_where  . " " . $period_sql . " and `plugin_camm_rule`.`is_mark`=1 " . $sql_marker . " " . $sql_status .
						  " order by `krid` desc LIMIT " . $row_start . "," . $row_limit . ") as temp_key " .
						  " Left join `host` on (temp_key." . $join_field . "=" . $sql_fqdn_host . ") " .
						  $sql_group;

					$total_rows = db_fetch_cell("SELECT count(*) from " . $table . " left join plugin_camm_keys on (`krid`=" . $table . ".`id`) " .
							" left join plugin_camm_rule on (`rule_id`=`plugin_camm_rule`.`id`) " .
							" where " . $sql_where  . " " . $period_sql . " and plugin_camm_rule.is_mark=1 " . $sql_marker . " " . $sql_status );					
				}else {//иначе - выделена сама ветка Маркеры и нужно вывести сообщения с маркерами (при этом одному сообщению может соотвествовать несколько маркеров).
					$query_string = " SELECT `temp_key`.*,`host`.`description`, `host`.`host_template_id`, `host`.`id` as device_id " .
						  " FROM (SELECT CONVERT(GROUP_CONCAT(`plugin_camm_keys`.`rule_id`) USING UTF8) as rule_id, `plugin_camm_keys`.`krid`,`plugin_camm_keys`.`ktype`, " . $table . ".* from " . $table . " left join plugin_camm_keys on (`krid`=" . $table . ".`id`) " .
						  " left join plugin_camm_rule on (`plugin_camm_keys`.`rule_id`=`plugin_camm_rule`.`id`) " .
						  " where " . $sql_where  . " " . $period_sql . " and `plugin_camm_rule`.`is_mark`=1 " . $sql_marker . " " . $sql_status .
						  " group by krid order by `krid` LIMIT " . $row_start . "," . $row_limit . ") as temp_key " .
						  " Left join `host` on (temp_key." . $join_field . "=" . $sql_fqdn_host . ") " .
						  $sql_group;

					$total_rows = db_fetch_cell("SELECT count(*) from " . $table . " left join plugin_camm_keys on (`krid`=" . $table . ".`id`) " .
							" left join plugin_camm_rule on (`rule_id`=`plugin_camm_rule`.`id`) " .
							" where " . $sql_where  . " " . $period_sql . " and plugin_camm_rule.is_mark=1 " . $sql_marker . " " . $sql_status );									
				
				}


			}
 			
 		}else{
 			$rezult=" SYSLOG component disabled.";
 		}
 	}	
 	
 	//output 
 	if ($rezult==1) {
 		if($total_rows>0){
 			$rows = db_fetch_assoc($query_string);
			echo camm_JEncode(array('success' => true,'total' => $total_rows, "results" =>  $rows));
			//camm_debug("S php camm_get_syslog_records results=[" . camm_JEncode($rows) . "]");
 		}else{
 			echo camm_JEncode(array('success' => true,'total' => "0", "results" => ""));
 		}
 	}else{
 		echo camm_JEncode(array('failure' => true,'error' => $rezult));
 	}		
 	
 }
 
 
 function camm_get_full_trap() {
 global $cacti_camm_components;
 
 $rezult=1;
 
     /* ================= input validation ================= */
 
 	camm_input_validate_input_regex(stripslashes(get_request_var_request("id")), "/^\\[([0-9]+,?)+\\]\$/","Uncorrect input value");
	camm_input_validate_input_regex(stripslashes(get_request_var_request("dir")), "/^(next|prev)$/", 'Incorrect value [dir]');
 	   
     /* ==================================================== */
 
 	//error checking
 	if (is_error_message()) {
 		$rezult="Input validation error.";
 	}
 	
 	//business logic
 	if ($rezult==1){
 		if ($cacti_camm_components["snmptt"]) {
 			if ( isset($_POST['id'])){
				if (isset($_POST['dir'])) {
					$dir = $_POST['dir']; // Get 
				}else {
					$dir = ""; // Get 
				}			
 			   $id = $_POST['id']; // Get our array back and translate it :
 			   $id = camm_JDecode(stripslashes($id));
 
 			    if(sizeof($id)<1){
 					$rezult=" no ID.";
 			    } else if (sizeof($id) == 1){
					
					$use_fqdn = (read_config_option("camm_use_fqdn", true) == '1');	

					if (read_config_option("camm_join_field") == "sourceip") {
						$join_field = "agentip";
					}elseif (read_config_option("camm_join_field") == "hostname"){
						$join_field = "hostname";		
					}else{
						$join_field = "agentip";
					}

					if ($use_fqdn && $join_field == "host") {
						$sql_fqdn_host = " SUBSTRING_INDEX(`host`.`hostname`,'.',1) ";
					}else{
						$sql_fqdn_host = " `host`.`hostname` ";
					}				
										
					
					if ($dir == "") {
						$sql_where = "where `id`='" . $id[0] . "' ";
					}elseif ($dir == "next") {
						$sql_where = "where `id`>'" . $id[0] . "' ORDER BY id ASC limit 1 ";
					}elseif ($dir == "prev") {
						$sql_where = "where `id`<'" . $id[0] . "' ORDER BY id DESC limit 1 ";
					}
					
					$query = "SELECT * FROM `plugin_camm_snmptt` " . $sql_where . ";";
 					$row_rezult = db_fetch_row($query);
					
					$query = "SELECT CONVERT(GROUP_CONCAT(concat(t2.`rule_id`, ' [',t3.name, ']'),';\\r\\n' SEPARATOR '') USING UTF8) as rules_name,t1.*,host.description, host.host_template_id, host.id as device_id " .
							 "FROM (SELECT * FROM `plugin_camm_snmptt` " . $sql_where . ") as t1 " .
							 "Left join host on (t1.`" . $join_field . "`=" . $sql_fqdn_host . ") " .
							 "left join plugin_camm_keys as t2 on (t2.`krid`=t1.`id`) " .
							 "left join plugin_camm_rule as t3 on (t2.rule_id=t3.id) " .
							 " group by t1.id ;";

								
					$row_rezult = db_fetch_row($query);
					
					if (!(isset($row_rezult["id"]))) {
						$rezult = "No row data for this ID=[" . $id[0] . "]";
					}


				
					
 			    } else {
 					$rezult=" Incorrect ID.";
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
 		echo camm_JEncode(array('success' => true,'data' => $row_rezult));
 	}else{
 		echo camm_JEncode(array('failure' => true,'error' => $rezult));
 	}	
 		
 }
 
 
 function camm_get_full_unk_trap() {
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
 	if ($rezult==1){
 		if ($cacti_camm_components["snmptt"]) {
 			if ( isset($_POST['id'])){
 			   $id = $_POST['id']; // Get our array back and translate it :
 			   $id = camm_JDecode(stripslashes($id));
 
 			    if(sizeof($id)<1){
 					$rezult=" no ID.";
 			    } else if (sizeof($id) == 1){
 					$query = "SELECT * FROM `plugin_camm_snmptt_unk` WHERE `id` = '" . $id[0] . "';";
 					$row_rezult = db_fetch_row($query);
 			    } else {
 					$rezult=" Incorrect ID.";
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
 		echo camm_JEncode(array('success' => true,'data' => $row_rezult));
 	}else{
 		echo camm_JEncode(array('failure' => true,'error' => $rezult));
 	}	
 		
 }
 
 
 function camm_get_full_sys_mes() {
 global $cacti_camm_components;
 
 $rezult=1;
 
     /* ================= input validation ================= */
 
 	camm_input_validate_input_regex(stripslashes(get_request_var_request("id")), "/^\\[([0-9]+,?)+\\]\$/","Uncorrect input value");
	camm_input_validate_input_regex(stripslashes(get_request_var_request("dir")), "/^(next|prev)$/", 'Incorrect value [dir]');
 	   
     /* ==================================================== */
 
 
 //error checking
  if (is_error_message()) {
 	$rezult="Input validation error.";
 }
 
 //business logic
 if ($rezult==1){
 	if ($cacti_camm_components["syslog"]) {	
 		if ( isset($_POST['id'])){
		   if (isset($_POST['dir'])) {
			$dir = $_POST['dir']; // Get 
		   }else {
			$dir = ""; // Get 
		   }
 		   $id = $_POST['id']; // Get our array back and translate it :
 		   $id = camm_JDecode(stripslashes($id));
 		    if(sizeof($id)<1){
				$rezult = "Zerro count Input data";
 		    } else if (sizeof($id) == 1){
			
			$use_fqdn = (read_config_option("camm_use_fqdn", true) == '1');	

 			if (read_config_option("camm_join_field") == "sourceip") {
 				$join_field = "sourceip";
 			}elseif (read_config_option("camm_join_field") == "hostname"){
 				$join_field = "host";		
 			}else{
 				$join_field = "sourceip";
 			}
			if ($use_fqdn && $join_field == "host") {
				$sql_fqdn_host = "SUBSTRING_INDEX(`host`.`hostname`,'.',1) ";
			}else{
				$sql_fqdn_host = "`host`.`hostname` ";
			}
			if ($dir == "") {
				$sql_where = "where `id`='" . $id[0] . "' ";
			}elseif ($dir == "next") {
				$sql_where = "where `id`>'" . $id[0] . "' ORDER BY id ASC limit 1 ";
			}elseif ($dir == "prev") {
				$sql_where = "where `id`<'" . $id[0] . "' ORDER BY id DESC limit 1 ";
			}

			$query = "SELECT CONVERT(GROUP_CONCAT(concat(t2.`rule_id`, ' [',t3.name, ']'),';\\r\\n' SEPARATOR '') USING UTF8) as rules_name,t1.*,host.description, host.host_template_id, host.id as device_id " .
					 "FROM (SELECT * FROM `" . read_config_option("camm_syslog_db_name") . "`.`plugin_camm_syslog` " . $sql_where . ") as t1 " .
					 "Left join host on (t1.`" . $join_field . "`=" . $sql_fqdn_host . ") " .
					 "left join plugin_camm_keys as t2 on (t2.`krid`=t1.`id`) " .
					 "left join plugin_camm_rule as t3 on (t2.rule_id=t3.id) " .
					 " group by t1.id ;";

						
 				$row_rezult = db_fetch_row($query);
				
				if (!(isset($row_rezult["id"]))) {
					$rezult = "No row data for this ID";
				}
				
 		    } else {
 				$rezult=" Uncorrect count Input data.";
 		    }
 		    // echo $query;  This helps me find out what the heck is going on in Firebug...
 		    
 		} else {
 			$rezult=" no ID.";
 
 		}
 	}else{
 		$rezult=" SYSLOG component disabled.";
 	}
 	
 }
 
 	//output 
 	if ($rezult==1) {
 		echo camm_JEncode(array('success' => true,'data' => $row_rezult));
 	}else{
 		echo camm_JEncode(array('failure' => true,'error' => $rezult));
 	}
 	
 }
 
 function camm_get_eventnames() {
 global $cacti_camm_components;
 
 	if ($cacti_camm_components["snmptt"]) {	
 		$rows = db_fetch_assoc("SELECT DISTINCT BINARY `eventname` as value,  `eventname` as label FROM `plugin_camm_snmptt`;");
 		echo camm_JEncode(array('success' => true,'names' => $rows));
 	}else{
 		echo camm_JEncode(array('failure' => true,'error' => "SNMPTT component disabled"));
 	};
 
 }
 
 function camm_get_severites() {
 global $cacti_camm_components;
 
 	if ($cacti_camm_components["snmptt"]) {	
 		$rows = db_fetch_assoc("SELECT DISTINCT BINARY `severity` as value,  `severity` as label FROM `plugin_camm_snmptt`;");
 		echo camm_JEncode(array('success' => true,'names' => $rows));
 	}else{
 		echo camm_JEncode(array('failure' => true,'error' => "SNMPTT component disabled"));
 	};
 }
 
 function camm_get_facilitys() {
 global $cacti_camm_components;
 
 	if ($cacti_camm_components["syslog"]) {	
 		$rows = db_fetch_assoc("SELECT DISTINCT BINARY `facility` as value,  `facility` as label FROM `" . read_config_option("camm_syslog_db_name") . "`.`plugin_camm_syslog`;");
     
 		echo camm_JEncode(array('success' => true,'names' => $rows));
 	}else{
 		echo camm_JEncode(array('failure' => true,'error' => "SYSLOG component disabled"));
 	}
 }
 
 function camm_get_prioritys() {
 global $cacti_camm_components;
 	
 	if ($cacti_camm_components["syslog"]) {
 		$rows = db_fetch_assoc("SELECT DISTINCT BINARY `priority` as value,  `priority` as label FROM `" . read_config_option("camm_syslog_db_name") . "`.`plugin_camm_syslog`;");
     
 		echo camm_JEncode(array('success' => true,'names' => $rows));
 	}else{
 		echo camm_JEncode(array('failure' => true,'error' => "SYSLOG component disabled"));
 	}
 }
 
 function camm_get_host_types() {
 global $cacti_camm_components;
 	
 		$rows = db_fetch_assoc("SELECT DISTINCT `id` as value, `name` as label FROM `host_template`;");
     
 		echo camm_JEncode(array('success' => true,'names' => $rows));

 }
 
 function camm_get_menutree() {
 global $cacti_camm_components;
 
 $rezult=1;
 
 	/* ================= input validation ================= */
 
 	camm_input_validate_input_regex(stripslashes(get_request_var_request("type")), "/^(snmptt|syslog)$/");
	camm_input_validate_input_regex(stripslashes(get_request_var_request("stats")), "/^(0|1)$/", 'Incorrect value [stats]');
 
 	/* ==================================================== */
 
 	//error checking
 	if (is_error_message()) {
 		$rezult="Input validation error.";
 	}
 	
 	$type_tree = (string) (isset($_POST['type']) ? $_POST['type'] : "snmptt");
	$stats = (string) (isset($_POST['stats']) ? $_POST['stats'] : "0");
 	
 	//business logic
 	if ($rezult==1) {
 		if ($cacti_camm_components[$type_tree]) {
 
 			$stat_data =array();
			$test_tree = array();
 			$lv_2_id = 0;
 			$lv_3_id = 0;
 			$j = 0;
			
			
 
 			$tree_lists = db_fetch_assoc("SELECT * FROM plugin_camm_tree WHERE `type`='"  . $type_tree . "' order by device_type_name, description, eventname ");
			
			if ($stats == '1') $stat_data =array("uiProvider"=>"col");
 
 			$test_tree[0]= array_merge($stat_data, array( "text"=> $type_tree, "id"=>"root", $stat_data, "expanded"=>"true", "cls"=>"folder","children"=>array()));
 			if (sizeof($tree_lists) > 0) {
 				foreach ($tree_lists as $tree_list) {
					//$stat_data = (($stats != '1') ? array(): array("uiProvider"=>"col", "count"=>$tree_list["count"]));

 					$tree_list = $tree_lists[$j];
 					if (isset($tree_list["description"])) {
 						$name_leaf = "Host: " . addslashes($tree_list["description"]) ;
 					}else{
 						$name_leaf = "IP:" . addslashes($tree_list["hostname"]) ;
 					}
 					
 					if (isset($tree_list["device_type_name"])) {
 						//use old device_type_name leaf
 						if ((isset($tree_lists[$j-1]["device_type_name"])) && ($tree_lists[$j-1]["device_type_name"]==$tree_list["device_type_name"])) { 
 							if ($tree_lists[$j-1]["hostname"]==$tree_list["hostname"]) { 
 								// и использовать уже созданное устройство.
								$test_tree[0]["children"][$lv_2_id-1]["children"][$lv_3_id-1]["children"][] =  array_merge((($stats != '1') ? array(): array("uiProvider"=>"col", "count"=>$tree_list["count"])),   array( "text"=> addslashes($tree_list["eventname"]), "id"=>"evn-" . $tree_list["id"], "leaf"=>true));
 							}else{
 								// но создать новое устройство.
								$test_tree[0]["children"][$lv_2_id-1]["children"][$lv_3_id] =   array_merge((($stats != '1') ? array(): array("uiProvider"=>"col", "count"=>$tree_list["dev_count"])),   array( "text"=> $name_leaf, "id"=>"host-" . $tree_list["id"], "cls"=>"folder", "children"=>array()));
								$lv_3_id++;
 								$test_tree[0]["children"][$lv_2_id-1]["children"][$lv_3_id-1]["children"][] =  array_merge((($stats != '1') ? array(): array("uiProvider"=>"col", "count"=>$tree_list["count"])),    array( "text"=> addslashes($tree_list["eventname"]), "id"=>"evn-" . $tree_list["id"], "leaf"=>true));
 							}
 						}else{
 							//create new device_type_name leaf
 							$test_tree[0]["children"][$lv_2_id] =  array_merge((($stats != '1') ? array(): array("uiProvider"=>"col", "count"=>$tree_list["typ_count"])),  array( "text"=> "Type: " . addslashes($tree_list["device_type_name"]), "id"=>"typ-" . $tree_list["id"], "cls"=>"folder", "children"=>array()));
 							$lv_3_id=0;						
 							$test_tree[0]["children"][$lv_2_id]["children"][$lv_3_id] =  array_merge((($stats != '1') ? array(): array("uiProvider"=>"col", "count"=>$tree_list["dev_count"])),   array( "text"=> $name_leaf, "id"=>"host-" . $tree_list["id"], "cls"=>"folder", "children"=>array()));
 							
 							$test_tree[0]["children"][$lv_2_id]["children"][$lv_3_id]["children"][] =   array_merge((($stats != '1') ? array(): array("uiProvider"=>"col", "count"=>$tree_list["count"])),   array( "text"=> addslashes($tree_list["eventname"]), "id"=>"evn-" . $tree_list["id"], "leaf"=>true));
 							$lv_2_id++;
 							$lv_3_id++;
 						}
 					}else {
 						if ((isset($tree_lists[$j-1]["hostname"])) && ($tree_lists[$j-1]["hostname"]==$tree_list["hostname"])) { 
 							$test_tree[0]["children"][$lv_2_id-1]["children"][$lv_3_id] =  array( "text"=> addslashes($tree_list["eventname"]), "id"=>"evn-" . $tree_list["id"], "leaf"=>"true");
 							$lv_3_id++;
 						}else{
 							//создание заголовка для ИП
 							$test_tree[0]["children"][$lv_2_id] =  array( "text"=> $name_leaf, "id"=>"host-" . $tree_list["id"], "cls"=>"folder", "children"=>array());
 							$lv_3_id=0;
 							$test_tree[0]["children"][$lv_2_id]["children"][$lv_3_id] =  array( "text"=>  addslashes($tree_list["eventname"]), "id"=>"evn-" . $tree_list["id"], "leaf"=>"true");
 							$lv_2_id++;
 							
 						}				
 					}
					
 					$j++;
 				}
 			}
 		}else{
 			$rezult= $type_tree . " component disabled.";
 		}
 	}
 
 	//output 
 	if ($rezult==1) {
 		echo camm_JEncode(array('success' => true,'data' => $test_tree));
 	}else{
 		echo camm_JEncode(array('failure' => true,'error' => $rezult));
 	}	
 	
 }
/* 

truncate plugin_camm_tree3;

insert into plugin_camm_tree3 (`device_type_name`,`host_template_id`,`type`,`_lft`,`_rgt`,`_is_type`,`_is_device`,`_parent`,`count`,`dev_count`,`typ_count`,`online`,`_is_leaf`) SELECT `device_type_name`,`host_template_id`,`type`,'0','0','1','0','0','0','0',`typ_count`,`online`,'0' FROM plugin_camm_tree where type='syslog' group by device_type_name;

insert into plugin_camm_tree3 (`device_type_name`,`host_template_id`,`description`,`hostname`,`agentip`,`agentip_source`,`type`,`_parent`,`_lft`,`_rgt`,`_is_device`,`_is_type`,`count`,`dev_count`,`typ_count`,`online`,`_is_leaf`) SELECT t0.`device_type_name`,t0.`host_template_id`,t0.`description`,t0.`hostname`,t0.`agentip`,t0.`agentip_source`,t0.`type`,t1.id,'0','0','1','0','0',t0.`dev_count`,t0.`typ_count`,t0.`online`,'0' FROM plugin_camm_tree as t0 join plugin_camm_tree3 as t1 on (t0.type=t1.type and t0.host_template_id=t1.host_template_id )  group by t0.hostname;

insert into plugin_camm_tree3 (`device_type_name`,`host_template_id`,`description`,`hostname`,`agentip`,`agentip_source`,`eventname`,`type`,`_parent`,`_lft`,`_rgt`,`_is_type`,`_is_device`,`count`,`dev_count`,`typ_count`,`online`,`_is_leaf`) SELECT t0.`device_type_name`,t0.`host_template_id`,t0.`description`,t0.`hostname`,t0.`agentip`,t0.`agentip_source`,t0.`eventname`,t0.`type`,t1.id,'0','0','0','0',t0.`count`,t0.`dev_count`,'0',t0.`online`,'1' FROM plugin_camm_tree as t0 join plugin_camm_tree3 as t1 on (t0.type=t1.type and t0.host_template_id=t1.host_template_id and t0.hostname=t1.hostname ) ;


 */

function camm_get_topstat() {
 global $cacti_camm_components;

$rezult=1;

	/* ================= input validation ================= */

 	camm_input_validate_input_regex(get_request_var_request("type"), "/^(syslog|snmptt)$/", 'Uncorrect input data [type]');
	camm_input_validate_input_regex(stripslashes(get_request_var_request("period")), "/^(all|day|week|hour)$/");	
 	   
     /* ==================================================== */
 
 	//error checking
 	if (is_error_message()) {
 		$rezult="Input validation error.";
 	}
 	
 	//business logic
 	if ($rezult==1){
	$type_tree = (string) strtolower(isset($_POST['type']) ? $_POST['type'] : 'Syslog');
	$period = (string) strtolower(isset($_POST['period']) ? $_POST['period'] : 'all');
	 
		if ($cacti_camm_components[$type_tree]) {	
			$all_count = db_fetch_cell("SELECT sum(`dev_count`) FROM `plugin_camm_tree2` WHERE  `_is_device`='1' AND `type`='"  . $type_tree . "' and `period`='" . $period . "' and `_is_marker`=0 ;");
			
			if (is_null($all_count)) {
				$rows=array();
			}else {
				
				$rows = db_fetch_assoc("SELECT if((`_is_marker`=1 and `_parent`=0),`id`,`id`) as _id, " .
				
					" if(`_is_type`=1, IF(`_is_marker`=1,`device_type_name`,CONCAT('Type: ',`device_type_name`)),if(`_is_device`=1,IF(`device_id`>0,CONCAT('Host: ',`description`),IF(`_is_marker`=1,`hostname`,CONCAT('IP: ',`hostname`))),IF(`_lvl`=5,`gr_v`,IF(`_is_leaf`='0',CONCAT(`gr_f`,'\'s'),CONCAT(`gr_f`,': ',`gr_v`))))) as label, " .
					" if(`_is_type`=1,`typ_count`,if(`_is_device`=1,`dev_count`,IF(`_lvl`=4,IF(`_is_leaf`='0','',`count`),`count`))) as count, " . 
					" if(`_is_type`=1, round((`typ_count`/" . $all_count . ")*100,1),if(`_is_device`=1,round((`dev_count`/`typ_count`)*100,1),IF(`_lvl`=4,IF(`_is_leaf`='0','',round((`count`/`dev_count`)*100,1)),round((`count`/`dev_count`)*100,1)))) as perc, " . 
					" if(`_is_leaf`='0', 'false','true') as _is_leaf, " .
					" if(`_parent`='0',null,`_parent`) as _parent, " .
					" `hostname`, `_lvl` " .
					" FROM plugin_camm_tree2 where `type`='" . $type_tree . "' and `period`='" . $period . "' " .
					//" ORDER by id;");
					//" ORDER by device_type_name, description, gr_f, count;");
					" ORDER by _is_marker,_path;");
					
				if (is_null($rows)) {
					$rows=array();
				};			
			}
			
			// теперь добавим корневой заголовок			
			
			$rows = array_merge(array('0'=>array('_id'=>"1",'label'=>$type_tree,'count'=>$all_count,'_is_leaf'=>(count($rows) > 0 ? "false" : "true"),'_parent'=>null,'hostname'=>'','host_id'=>'0','_lvl'=>"1")),$rows);

			
			echo camm_JEncode(array('success' => true,'total'=>2, 'applySort' => false ,'data' => $rows));
		}else{
			echo camm_JEncode(array('failure' => true,'error' => $type_tree . " component disabled"));
		}
	}
}

 function camm_get_incorrect_devices() {
 
$rezult=1;
 
     /* ================= input validation ================= */
 
 	camm_input_validate_input_regex(get_request_var_request("start", "0"), "/^[0-9]{0,10}$/");
 	camm_input_validate_input_regex(get_request_var_request("limit", "50"), "/^[0-9]{1,4}$/");
 	   
     /* ==================================================== */
 
 
 	//error checking
 	if (is_error_message()) {
 		$rezult="Input validation error.";
 	}
 	
 	//business logic
 	if ($rezult==1){
		$sql_where = "";

		$row_start = (integer) (isset($_POST['start']) ? $_POST['start'] : $_POST['start']);
		$row_limit = (integer) (isset($_POST['limit']) ? $_POST['limit'] : $_POST['limit']);

		$query_string = "";
			

		$query_string = " SELECT SQL_CALC_FOUND_ROWS t.*, IFNULL(t_sys.dev_count,0) as syslog_count , IFNULL(t_snm.dev_count,0) as snmp_count   from (SELECT distinct  device_type_id,device_type_name, host.id, host.description, host.hostname,IF(`host`.`disabled`='on','-1',`host`.`status`) as rstatus FROM plugin_camm_tree2 " .
				" left join host  on (plugin_camm_tree2.device_type_id=host.host_template_id) " .
				" where device_type_id>0 order by device_type_id) as t " .
				" left join (select * from plugin_camm_tree2 where type='syslog' and period='all' and `_is_device`=1)  as t_sys on (t.id=t_sys.device_id) " .
				" left join (select * from plugin_camm_tree2 where type='snmptt' and period='all' and `_is_device`=1)  as t_snm on (t.id=t_snm.device_id) ";

		$query_string .= " LIMIT " . $row_start . "," . $row_limit;
		$rows = db_fetch_assoc($query_string); 	

		$total_rows = db_fetch_cell("SELECT FOUND_ROWS()"); 	
 	}
	
 	//output 
 	if ($rezult==1) {
 		if($total_rows>0){
 			echo camm_JEncode(array('success' => true,'total' => $total_rows, "results" => $rows));
 		}else{
 			echo camm_JEncode(array('success' => true,'total' => "0", "results" => ""));
 		}
 	}else{
 		echo camm_JEncode(array('failure' => true,'error' => $rezult));
 	}	
 	
 	
 }




function camm_get_markers() {
global $cacti_camm_components;

$rezult=1;
 
     /* ================= input validation ================= */
 
camm_input_validate_input_regex(get_request_var_request("type"), "/^(syslog|snmptt)$/", 'Uncorrect input data [type]');
camm_input_validate_input_regex(get_request_var_request("marker", "0"), "/^[0-9]{1,6}$/");
camm_input_validate_input_regex(get_request_var_request("period", "0"), "/^[0-9]{1,4}$/");
camm_input_validate_input_regex(get_request_var_request("interval", "300"), "/^[0-9]{1,4}$/");
 	   
     /* ==================================================== */
 
 
 	//error checking
 	if (is_error_message()) {
 		$rezult="Input validation error.";
 	}
 	
 	//business logic
 	if ($rezult==1){
	
	$type_tree = (string) strtolower(isset($_POST['type']) ? $_POST['type'] : 'syslog');
	
	
  	if ($cacti_camm_components[$type_tree]) {	
 		$rows = db_fetch_assoc("select `id`,concat(`name`, ' (',count(`rule_id`),' items)') as marker_name from `plugin_camm_rule` " .
								"left join `plugin_camm_keys` on (`plugin_camm_rule`.`id`=`plugin_camm_keys`.`rule_id`) " .
								" where `rule_type`='" . $type_tree . "' and `is_mark`=1 " .
								" group by `id` " .
								" order by `id`;");
								
		array_unshift($rows, array("id"=>"0","marker_name"=>"not_use"));
		
 	}else{
 		$rezult = $type_tree . " component disabled";
 	};	
	
	
	} 
 
  	//output 
 	if ($rezult==1) {
 			echo camm_JEncode(array('success' => true,"data" => $rows));
 	}else{
 		echo camm_JEncode(array('failure' => true,'error' => $rezult));
 	}	
 

 
 }

 
function camm_get_chart() {

//ini_set('memory_limit', '256M'); 
 
$rezult=1;
$i=0;
 
     /* ================= input validation ================= */
 
camm_input_validate_input_regex(get_request_var_request("type"), "/^(syslog|snmptt)$/", 'Uncorrect input data [type]');
camm_input_validate_input_regex(get_request_var_request("rule_id", "0"), "/^[0-9]{1,6}$/", 'Uncorrect input data [marker]');
camm_input_validate_input_regex(get_request_var_request("period", "0"), "/^[0-9]{1,4}$/");
camm_input_validate_input_regex(get_request_var_request("interval", "300"), "/^[0-9]{1,5}$/");
 	   
     /* ==================================================== */
 



 	//business logic
 	if ($rezult==1){
	
	$type_tree = (string) strtolower(isset($_POST['type']) ? $_POST['type'] : 'syslog');
	$rule_id = (integer) strtolower(isset($_POST['rule_id']) ? $_POST['rule_id'] : '0');
	$period = (integer) strtolower(isset($_POST['period']) ? $_POST['period'] : '0');
	$point_interval = (integer) strtolower(isset($_POST['interval']) ? $_POST['interval'] : '300');
	
	if ($type_tree == "syslog") {
		$table = '`' . read_config_option("camm_syslog_db_name") . '`.`plugin_camm_syslog`';
		$date_fld = '`sys_date`';
		$ktype = 1;
	}else{
		$table = '`plugin_camm_snmptt`';
		$date_fld = '`traptime`';
		$ktype = 2;
	}
	if ($period>0) {
		$period_start = date("Y-m-d H:i:s",mktime(date("H")-$period, date("i"), date("s"), date("m")  , date("d"), date("Y")));
		$ap_intervals = (integer)($period*60*60/$point_interval);
	}else{//проверим  количество точек. Максимальное - 8к
		if ($rule_id == 0) {
			$dates = db_fetch_row("select UNIX_TIMESTAMP(min(" . $date_fld . ")) as min_d , UNIX_TIMESTAMP(max(" . $date_fld . ")) as max_d from " . $table . ";");
		}else{
			$dates = db_fetch_row(" SELECT UNIX_TIMESTAMP(min(`sys_date`)) as min_d , UNIX_TIMESTAMP(max(`sys_date`)) as max_d from plugin_camm_keys " .
								" left join " . $table . " sl on (plugin_camm_keys.krid=`sl`.id) " .
								" where ktype=" . $ktype . " and rule_id=" . $rule_id . " ;");
		}
		$ap_intervals = (integer)(($dates["max_d"]-$dates["min_d"])/$point_interval);		
	}

	switch ($ap_intervals) {
		case $ap_intervals<5000:
			break;
		case $ap_intervals<12000:			
			camm_set_memory_limit (128 * 1024 * 1024);
 			break;
		default:
			$rezult = "The current values of the interval and the period will generate too many points (" . $ap_intervals . ") with maximum = 10 000. Either reduce the period or increase the interval.";
		break;
	}

	
	
	//one more business check
 	if ($rezult==1){
		if ($rule_id == 0) {
			if ($period == 0) {
				$query_string = " SELECT count(id) as point,floor(UNIX_TIMESTAMP(" . $date_fld . ")/" . $point_interval . ")*" . $point_interval . " as 'time' FROM " . $table . " group by `time` ";
			}else {
				$query_string = " SELECT count(id) as point,floor(UNIX_TIMESTAMP(" . $date_fld . ")/" . $point_interval . ")*" . $point_interval . " as 'time' FROM " . $table . 
								" WHERE " . $date_fld . " > '" . $period_start . "' group by `time` ";		
			}
		}else{
			if ($period == 0) {
				$query_string = " SELECT count(`sl`.`id`) as point,floor(UNIX_TIMESTAMP(`sys_date`)/" . $point_interval . ")*" . $point_interval . " as 'time' from plugin_camm_keys " .
								" left join " . $table . " sl on (plugin_camm_keys.krid=`sl`.id) " .
								" where ktype=" . $ktype . " and `rule_id`=" . $rule_id . " and `sl`.`id`>0 " .
								" group by `time`;";
			}else {
				$query_string = " SELECT count(`sl`.`id`) as point,floor(UNIX_TIMESTAMP(`sys_date`)/" . $point_interval . ")*" . $point_interval . " as 'time' from plugin_camm_keys " .
								" left join " . $table . " sl on (plugin_camm_keys.krid=`sl`.id) " .
								" where ktype=" . $ktype . " and `rule_id`=" . $rule_id . " and " . $date_fld . " > '" . $period_start . "' and `sl`.`id`>0 " .
								" group by `time`;";
			}
		}
		
		$rows = db_fetch_assoc($query_string);
		
		if (sizeof($rows) > 0) {
		
			$point_start=$rows[0]["time"];
			$point_end=$rows[end(array_keys($rows))]["time"];
			$point_count=(integer)($point_end-$point_start)/$point_interval;
			
			foreach ($rows as $row => $value) {
					$rows_[$value["time"]] = $value["point"];
			}
			
			while ($i<$point_count) {
				$cur_time=$point_start+($point_interval*$i);
				if (isset($rows_[$cur_time])){
					$rows_output[]=array("t"=>$cur_time . "000", "p"=>$rows_[$cur_time]);
					//$rows_output[]=array("point"=>(integer)$rows_[$cur_time]);
				}else{
					$rows_output[]=array("t"=>$cur_time . "000", "p"=>0);
					//$rows_output[]=array("point"=>0);
				}
				$i++;
			}
		}else{
			$rezult="No data for this period";
		}
		
		if ($point_interval < 3600) { 
			$str_interval = $point_interval/60 . ' minute';
		}elseif($point_interval < 86400) { 
			$str_interval = $point_interval/3600 . ' hour';
		}else{
			$str_interval = $point_interval/86400 . ' day';
		}
	}
	
	}

  	//output 
 	if ($rezult==1) {
 		echo camm_JEncode(array('success' => true,'message' => $type_tree . ' messages (interval ' . $str_interval . ', ' . sizeof($rows_output) . ' points)' ,'pointCount'=>sizeof($rows_output),'pointInterval'=>$point_interval . "000",'pointStart'=>$point_start . "000", 'Points' => $rows_output));
 	}else{
 		echo camm_JEncode(array('failure' => true,'error' => $rezult));
 	}	
 

}
 

 
function camm_get_chart_group_host() {

//ini_set('memory_limit', '256M'); 
 
$rezult=1;
$i=0;
 
     /* ================= input validation ================= */
 
camm_input_validate_input_regex(get_request_var_request("type"), "/^(syslog|snmptt)$/", 'Uncorrect input data [type]');
camm_input_validate_input_regex(get_request_var_request("rule_id", "0"), "/^[0-9]{1,6}$/", 'Uncorrect input data [marker]');
camm_input_validate_input_regex(get_request_var_request("limit", "10"), "/^[0-9]{1,4}$/" ,'Uncorrect input data [limit]');
camm_input_validate_input_regex(get_request_var_request("use_cacti"), "/^(true|false)$/", 'Uncorrect input data [use_cacti]');
camm_input_validate_input_regex(get_request_var_request("use_host_group"), "/^(true|false)$/", 'Uncorrect input data [use_host_group]');
//camm_input_validate_input_regex(get_request_var_request("period", "0"), "/^[0-9]{1,4}$/");
//camm_input_validate_input_regex(get_request_var_request("interval", "300"), "/^[0-9]{1,5}$/");
 	   
     /* ==================================================== */
 



 	//business logic
 	if ($rezult==1){
	
	$type_tree = (string) strtolower(isset($_POST['type']) ? $_POST['type'] : 'syslog');
	$rule_id = (integer) strtolower(isset($_POST['rule_id']) ? $_POST['rule_id'] : '0');
	$limit = (integer) (isset($_POST['limit']) ? $_POST['limit'] : '10');
	$raw_json_where_chart = (string) (isset($_POST['filter_by_chart']) ? $_POST['filter_by_chart'] : '');
	$use_cacti = (string) (isset($_POST['use_cacti']) ? $_POST['use_cacti'] : true);
	$use_host_group = (string) (isset($_POST['use_host_group']) ? $_POST['use_host_group'] : true);
	// $point_interval = (integer) strtolower(isset($_POST['interval']) ? $_POST['interval'] : '300');
	

	if ($type_tree == "syslog") {
		$table = '`' . read_config_option("camm_syslog_db_name") . '`.`plugin_camm_syslog`';
		$date_fld = '`sys_date`';
		$ktype = 1;
		if (read_config_option("camm_join_field") == "sourceip") {
			$join_field = "sourceip";
		}elseif (read_config_option("camm_join_field") == "hostname"){
			$join_field = "host";		
		}else{
			$join_field = "sourceip";
		}			
	}else{
		$table = '`plugin_camm_snmptt`';
		$date_fld = '`traptime`';
		$ktype = 2;
		if (read_config_option("camm_join_field") == "sourceip") {
			$join_field = "agentip";
		}elseif (read_config_option("camm_join_field") == "hostname"){
			$join_field = "hostname";		
		}else{
			$join_field = "agentip";
		}		
	}	
	
	$sql_where = getSQL(camm_JDecode(stripslashes($raw_json_where_chart)));
	
	//one more business check
 	if ($rezult==1){

		if ($rule_id == 0) {
				$query_string = " SELECT count(*) as v, " . $join_field . " as n from " .  $table . " sl WHERE " . $sql_where . " group by " . $join_field . " order by v desc ";
		}else{
				$query_string = " SELECT count(*) as `v`, " . $join_field . " as `n` " .
									" from `plugin_camm_keys` " .
									" left join " .  $table . " sl on (`plugin_camm_keys`.`krid`=`sl`.id) " .
									" where `ktype`=" . $ktype . " and `rule_id`=" . $rule_id . " and `sl`.`id`>0 and " . $sql_where . " " .
									" group by " . $join_field . " order by `v` desc ";
		}

		if ($use_cacti == "true") {
			if ($use_host_group == "true") {
				$query_string = "SELECT `t`.`v`, ifnull(`host_template`.`name`,concat('not_def_for_ip:',`t`.`n`)) as n from ( " .  $query_string .
							" ) t " . 
							" left join `host` on (`t`.`n` = `host`.`hostname`) " .
							" left join `host_template` on (`host`.`host_template_id`=`host_template`.`id`) " .
							" group by `host`.`host_template_id`  order by `v` desc limit " . $limit . ";";						
			}else{
				$query_string = "SELECT `t`.`v`, ifnull(`host`.`description`,t.n) as n from ( " .  $query_string .
							" limit " . $limit . " ) t " . 
							" left join `host` on (`t`.`n` = `host`.`hostname`) group by `t`.`n` order by `v` desc;";			
			}
		}else {
			$query_string = $query_string . ";";
		}

	
		//$query_string =  "SELECT count(*) as v, sourceip as n FROM syslog_ng.plugin_camm_syslog group by sourceip limit 10 ;";
	
		$rows_output = db_fetch_assoc($query_string);

	
	}
	
	}

  	//output 
 	if ($rezult==1) {
 		echo camm_JEncode(array('success' => true,  'Pies' => $rows_output));
 	}else{
 		echo camm_JEncode(array('failure' => true,'error' => $rezult));
 	}	
 

}
 
 
?>

 
