<?php
 /*
  +-------------------------------------------------------------------------+
  | Copyright (C) 2008 Susanin                                          |
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
 
 /*******************************************************************************
 
     Author ......... Susanin (gthe in forum.cacti.net)
     Program ........ camm viewer for cacti
     Version ........ 0.0.08b
 
 *******************************************************************************/
 
 define("SNMP_METHOD_PHP_SET", 1);
 define("SNMP_METHOD_BINARY_SET", 2);
 
 include_once($config["base_path"] . "/lib/poller.php");
 
 /**
  * The RegEx validation class .
  *
  * Usage:
  * if (!RegEx::isValid($expression)) {
  *    echo 'Your regular expression is invalid because: ' . RegEx::error();
  * }
  */
 class RegEx {
     /**
      * Validates a regular expression. Returns TRUE
      * if the expression is valid, FALSE if not. If
      * the expression is not valid, the reason why
      * can be fetched from RegEx::error().
      *
      * @access public
      * @static
      * @param string $regex Regular Expression
      * @return bool
      */
     function isValid($regex)
     {
         RegEx::error(FALSE);
        
         set_error_handler(array('RegEx', 'errorHandler'));
         preg_match($regex, '');
         restore_error_handler();
        
         return (RegEx::error() === FALSE) ? TRUE : FALSE;
     }
    
     /**
      * Error handler for RegEx. Used internally by RegEx::validate()
      *
      * @access package
      * @static
      * @param int $code Error Code
      * @param string $message Error Message
      */
     function errorHandler($code, $message)
     {
         // Cuts off the 'preg_match(): ' part of the error message.
         $error = substr($message, 14);
        
         // Sets the error flag with the message.
         RegEx::error($error);
     }
    
     /**
      * Holds the error from the last validation check.
      *
      * The first parameter is used internally and should
      * not be used by the developer.
      *
      * @access public
      * @static
      * @param FALSE|string $value value to set for $flag
      * @return FALSE|string
      */
     function error($value = NULL)
     {
         static $flag = FALSE;
         if (!is_null($value))
         {
             $flag = $value;
         }
         return $flag;
     }
 }
 
 /*	valid_snmp_device - This function validates that the device is reachable via snmp.
   It first attempts	to utilize the default snmp readstring.  If it's not valid, it
   attempts to find the correct read string and then updates several system
   information variable. it returns the status	of the host (up=true, down=false)
 */
 /* we must use an apostrophe to escape community names under Unix in case the user uses
 characters that the shell might interpret. the ucd-snmp binaries on Windows flip out when
 you do this, but are perfectly happy with a quotation mark. */
 if ($config["cacti_server_os"] == "unix") {
 	define("SNMP_SET_ESCAPE_CHARACTER", "'");
 }else{
 	define("SNMP_SET_ESCAPE_CHARACTER", "\"");
 }
 
 $cacti_camm_components["snmptt"]=(read_config_option("camm_use_snmptt", true)==1 ? true : false);
 $cacti_camm_components["syslog"]=(read_config_option("camm_use_syslog", true)==1 ? true : false);
 $camm_debug = (read_config_option("camm_debug_mode", true)==1 ? true : false);
 
 
 if (phpversion () < "5"){ // define PHP5 functions if server uses PHP4
 
 function str_split($text, $split = 1)
 {
 if (!is_string($text)) return false;
 if (!is_numeric($split) && $split < 1) return false;
 $len = strlen($text);
 $array = array();
 $s = 0;
 $e=$split;
 while ($s <$len)
     {
         $e=($e <$len)?$e:$len;
         $array[] = substr($text, $s,$e);
         $s = $s+$e;
     }
 return $array;
 }
 }
 if (! function_exists("array_fill_keys")) {
 	function array_fill_keys($array, $values) {
 	    if(is_array($array)) {
 	        foreach($array as $key => $value) {
 	            $arraydisplay[$array[$key]] = $values;
 	        }
 	    }
 	    return $arraydisplay;
 	} 
 }
 
 
 function camm_debug($message) {
 	global $camm_debug;
 
 	if ($camm_debug) {
 		//print("camm_DEBUG (" . date("H:i:s") . "): [" . $message . "]\n<br>");
 	}
 	
 
 	if (($camm_debug) || (substr_count($message, "ERROR:"))) {
 		cacti_log($message, false, "camm");
 	}
 }
 
 
 function camm_raise_message3($args) {
 	
 	if (count($args) > 0){
 		if (!isset($args["mes_id"])) {
 			if (isset($_SESSION["camm_output_messages"])) {
 		        $mes_id = count($_SESSION["camm_output_messages"]) + 1;
 		    }else{
 			$mes_id = 1;
 			}	
 		}else{
 			$mes_id = $args["mes_id"];
 		}
 		foreach($args as $arg => $value) {
 			$_SESSION["camm_output_messages"][$mes_id][$arg] = $value;
 		}
 	}
 return $mes_id;
 }
 
 function camm_format_filesize( $data ) {
 
 	// bytes
 	if( $data < 1024 ) {
 	
 		return $data . " bytes";
 	
 	}
 	// kilobytes
 	else if( $data < 1024000 ) {
 	
 		return round( ( $data / 1024 ), 1 ) . " KB";
 	
 	}
 	// megabytes
 	else {
 	
 		return round( ( $data / 1024000 ), 1 ) . " MB";
 	
 	}
 }
     
 	
 function camm_format_datediff( $startdata, $enddata = null) {
 $rezult = "";
 if ($enddata == null) {
 	$enddata = strtotime("now");
 }
 	$dateDiff = $enddata - $startdata;
 	$fullDays = floor($dateDiff/(60*60*24));
 	$fullHours = floor(($dateDiff-($fullDays*60*60*24))/(60*60));
 	$fullMinutes = floor(($dateDiff-($fullDays*60*60*24)-($fullHours*60*60))/60); 
 	$fullSeconds = floor($dateDiff-($fullDays*60*60*24)-($fullHours*60*60)-($fullMinutes*60));
 
 	$rezult =  ($fullDays>0 ? $fullDays . "d ": "") . ($fullHours>0 ? $fullHours . "h ": "") . ($fullMinutes>0 ? $fullMinutes . "m ": "") . ($fullSeconds>0 ? $fullSeconds . "s ": "") . "ago";
 
 return $rezult;
 
 }	
  
 	
 function camm_poller_recreate_tree($tree_type = "") {
 global $cacti_camm_components;

ini_set('max_execution_time', "0");
camm_set_memory_limit(256 * 1024 * 1024);

/* take time and log performance data */
list($micro,$seconds) = split(" ", microtime());
$start = $seconds + $micro;
	
 	$rezult = "0";
 	
 	if (strlen(trim($tree_type)) > 0) {
 		switch ($tree_type) {
 		case "snmptt":
 			if ($cacti_camm_components["snmptt"]) {
 				$arr[]="snmptt";
 			}else{
 				$rezult = "Can't recreate syslog tree until syslog not used";
 				camm_debug("  Error: Can't recreate syslog tree until syslog not used\n");
 			}
 			break;
 		case "syslog":
 			if ($cacti_camm_components["syslog"]) {
 				$arr[]="syslog";
 			}else{
 				$rezult = "Can't recreate syslog tree until syslog not used";
 				camm_debug("  Error: Can't recreate syslog tree until syslog not used\n");
 			}
 			break;
 		default:
 			if ($cacti_camm_components["syslog"]) {
 				$arr[] ="syslog";
 			}
 			if ($cacti_camm_components["snmptt"]) {
 				$arr[]="snmptt";
 			}			
 		}
 	}else{
 		if ($cacti_camm_components["syslog"]) {
 			$arr[] ="syslog";
 		}
 		if ($cacti_camm_components["snmptt"]) {
 			$arr[]="snmptt";
 		}	
 	}

	$arr_gr_f["syslog"]["0"] = 'facility';
	$arr_gr_f["syslog"]["1"] = 'priority';
	$arr_gr_f["snmptt"]["0"] = 'eventname';
	$arr_gr_f["snmptt"]["1"] = 'severity';	
	
	$periods = db_fetch_assoc("SELECT * FROM settings where name like 'camm_period_%' and `value`=1;");
	
	$periods = array_merge(array('0'=>array('name'=>"camm_period_all",'value'=>'1')),$periods);
	
 	if (read_config_option("camm_join_field") == "sourceip") {
 		$join_field = "agentip_source";
 	}else{
 		$join_field = "hostname";		
 	}
 	$use_markers = read_config_option("camm_process_markers", true);
	$use_fqdn = (read_config_option("camm_use_fqdn", true) == '1');
	
 	if 	(sizeof($arr) > 0) {
 	
 		foreach ($arr as $tree_type) {
 			list($micro,$seconds) = split(" ", microtime());
 			$start = $seconds + $micro;
 			
 
 			
 			//db_execute("DELETE FROM `plugin_camm_tree` WHERE `type`='t_" . $tree_type . "';");
			db_execute("DELETE FROM `plugin_camm_temp` WHERE `type`='" . $tree_type . "';");
 				
 			
			foreach ($periods as $period) {
				$period = str_replace('camm_period_','',$period["name"]);
				$period_sql=" WHERE " . camm_create_sql_from_period($period, $tree_type);
				
				if ($tree_type == 'snmptt') {
					foreach ($arr_gr_f["snmptt"] as $gr_f) {
						db_execute("INSERT INTO `plugin_camm_temp` (`hostname`,`gr_f`,`gr_v`,`type`,`period`,`agentip_source`,`count`)
							SELECT  `plugin_camm_snmptt`.`hostname`, '" . $gr_f . "', `" . $gr_f . "`, 'snmptt','" . $period . "',`agentip`,count(*) FROM `plugin_camm_snmptt` " .
							$period_sql . " GROUP BY `hostname`, `plugin_camm_snmptt`.`" . $gr_f . "`");					
					}					
					
					If ($use_markers) {
						db_execute("INSERT INTO `plugin_camm_temp` (`device_type_name`,`device_type_id`,`hostname`,`gr_f`,`gr_v`,`type`,`period`,`agentip_source`,`dev_count`,`count`) " .
							" select 'Markers','-1',CONCAT('[',`plugin_camm_rule`.`id`,'] ' , IFNULL(`plugin_camm_rule`.`name`,'no_marker_name')),'marker', " .
							" `plugin_camm_rule`.`id`,'snmptt','" . $period . "','0',count(*),count(*) FROM `plugin_camm_snmptt` " . 
							" left join plugin_camm_keys on (`krid`=`plugin_camm_snmptt`.`id`) " .
							" left join plugin_camm_rule on (`rule_id`=`plugin_camm_rule`.`id`) " . 
							$period_sql . " and plugin_camm_rule.is_mark=1 and `status` = '2' and `ktype`=2 " . 
							" Group by  plugin_camm_rule.id;" );					
					}
					
				}else{
					//**
					foreach ($arr_gr_f["syslog"] as $gr_f) {
						db_execute("INSERT INTO `plugin_camm_temp` (`hostname`,`gr_f`,`gr_v`,`type`,`period`,`agentip_source`,`count`)
							SELECT  `sysl`.`host`, '" . $gr_f . "',`sysl`.`" . $gr_f . "`, 'syslog','" . $period . "',`sysl`.`sourceip`,count(*) FROM `" . read_config_option("camm_syslog_db_name") . "`.`plugin_camm_syslog` as sysl " .
							$period_sql . " GROUP BY `host`, `sysl`.`" . $gr_f . "`");					
					}
					If ($use_markers) {
						db_execute("INSERT INTO `plugin_camm_temp` (`device_type_name`,`device_type_id`,`hostname`,`gr_f`,`gr_v`,`type`,`period`,`agentip_source`,`dev_count`,`count`) " .
							" select 'Markers','-1',CONCAT('[',`plugin_camm_rule`.`id`,'] ' , IFNULL(`plugin_camm_rule`.`name`,'no_marker_name')),'marker', " .
							" `plugin_camm_rule`.`id`,'syslog','" . $period . "','0',count(*),count(*) FROM `" . read_config_option("camm_syslog_db_name") . "`.`plugin_camm_syslog` " . 
							" left join plugin_camm_keys on (`krid`=`" . read_config_option("camm_syslog_db_name") . "`.`plugin_camm_syslog`.`id`) " .
							" left join plugin_camm_rule on (`rule_id`=`plugin_camm_rule`.`id`) " . 
							$period_sql . " and plugin_camm_rule.is_mark=1 and `status` = '2' and `ktype`=1 " . 
							" Group by  plugin_camm_rule.id;" );
					
						
						// db_execute("INSERT INTO `plugin_camm_temp` (`device_type_name`,`device_type_id`,`hostname`,`gr_f`,`gr_v`,`type`,`period`,`agentip_source`,`dev_count`,`count`) " .
							// " SELECT 'Markers','-1',CONCAT(IFNULL(`marker_name`,'no_marker_name'),' [',`alert`,']'),'marker',`alert`,'syslog',`" . $period . "`,'0',`ccount`,`ccount` " .
							// " FROM (SELECT   `alert`, '" . $period . "',count(*) as ccount FROM `" . read_config_option("camm_syslog_db_name") . "`.`plugin_camm_syslog` as sysl " .
							// $period_sql . " and `alert` > 0 and `status` = '2' GROUP BY `sysl`.`alert`) as t " .
							// " left join `plugin_camm_rule` on (`t`.`alert`=`plugin_camm_rule`.`marker`) ");					
					}					
					
				}
			}

					//**
					$update_sql = "UPDATE `plugin_camm_temp`,`host` SET " .
						" `plugin_camm_temp`.`device_type_id`=`host`.`host_template_id`, " .
						" `plugin_camm_temp`.`description`=`host`.`description`, " .
						" `plugin_camm_temp`.`device_id`=`host`.`id` ";
					
					if ($use_fqdn && $join_field == 'hostname') {
						$update_sql .= " WHERE (`plugin_camm_temp`.`hostname`=SUBSTRING_INDEX(`host`.`hostname`,'.',1)) AND `type`='" . $tree_type . "'";	
					}else{
						$update_sql .= " WHERE (`plugin_camm_temp`.`" . $join_field . "`=`host`.`hostname`) AND `type`='" . $tree_type . "'";	
					}
					
					db_execute($update_sql);	
	

					//**
					db_execute("UPDATE `plugin_camm_temp`,`host_template` SET " .
						" `plugin_camm_temp`.`device_type_name`=`host_template`.`name` " .
						" WHERE (`plugin_camm_temp`.`device_type_id`=`host_template`.`id`) AND `type`='" . $tree_type . "'");	
				
					
					//**
					db_execute("UPDATE `plugin_camm_temp` SET " .
						" `plugin_camm_temp`.`agentip`= inet_aton(`plugin_camm_temp`.`agentip_source`)  " .
						" WHERE `type`='" . $tree_type . "'");				
				
					//** обновим количество записей у устройств (кроме неизвестных устройств)
					db_execute("UPDATE plugin_camm_temp t2 JOIN (SELECT sum(count)as tsum, device_type_name,device_type_id,device_id,type, period,gr_f FROM plugin_camm_temp where `device_type_id` > '0' group by  type,device_type_id,device_id,period,gr_f) as  t1 " .
						" on (t2.device_type_id=t1.device_type_id and t2.type=t1.type and t2.device_id=t1.device_id and t2.period=t1.period and t2.gr_f=t1.gr_f) " .
						" set t2.dev_count = t1.tsum;");	
					//** обновим количество записей у неизвестных устройств
					db_execute("UPDATE plugin_camm_temp t2 JOIN (SELECT sum(count)as tsum, device_type_name,device_type_id,agentip,type, period,gr_f FROM plugin_camm_temp where `device_type_id` = '0' group by  type,device_type_id,agentip,period,gr_f) as  t1 " .
						" on (t2.device_type_id=t1.device_type_id and t2.type=t1.type and t2.agentip=t1.agentip and t2.period=t1.period and t2.gr_f=t1.gr_f) " .
						" set t2.dev_count = t1.tsum;");	
						
					//**
					db_execute("UPDATE plugin_camm_temp t2 JOIN (SELECT sum(count)as tsum, device_type_name,device_type_id,type,period,gr_f FROM plugin_camm_temp group by  type,device_type_id,period,gr_f) as  t1 " .
						" on (t2.device_type_id=t1.device_type_id and t2.type=t1.type and t2.period=t1.period and t2.gr_f=t1.gr_f) " .
						" set t2.typ_count = t1.tsum;");				
		
					//** plugin_camm_tree2 MUST have Auto_increment => 100 !!!
					$t_result = db_fetch_row("SHOW TABLE STATUS where Name = 'plugin_camm_tree2';");
					if ($t_result["Auto_increment"] < 100) {
						db_execute("ALTER TABLE `plugin_camm_tree2` AUTO_INCREMENT = 100;");
					}					
					
					// пометим все существующие записи как старые. После добовления новых те старын, что не були обновлены будут удалены					
					db_execute("UPDATE `plugin_camm_tree2` SET `online`='0' WHERE `type`='" . $tree_type . "'");	
					
					
					
					
					//переносим строки первого уровня (типы устройств)
					db_execute("INSERT INTO `plugin_camm_tree2` (`device_type_name`,`device_type_id`,`device_id`,`description`,`hostname`,`gr_f`,`gr_v`,`agentip`,`agentip_source`,`type`   ,`period`,`_is_type`,`_is_marker`,                   `_is_device`,`_parent`,`count`,`dev_count`,`typ_count`,`online`,`_is_leaf`,`_lvl`) " .
						" SELECT IFNULL(`device_type_name`,'not_defined'),`device_type_id`,'0'        ,''          ,''         ,''    ,''    ,`agentip`,`agentip_source`,'" . $tree_type . "',`period`,   '1', IF(`device_type_id`=-1,'1','0'),     '0'         ,IF(`device_type_id`=-1,'0','1')      ,'0'    ,'0'        ,`typ_count`,'1','0'       ,IF(`device_type_id`=-1,'1','2')      " .
						" FROM plugin_camm_temp WHERE `type`='" . $tree_type . "' group by device_type_id,period order by if(device_type_id>0,0,1), device_type_name" .
						" ON DUPLICATE KEY UPDATE " .
						" `plugin_camm_tree2`.`device_type_name` = IFNULL(values(`device_type_name`),'not_defined'), " .
						" `plugin_camm_tree2`.`device_type_id` = values(`device_type_id`), " .
						" `plugin_camm_tree2`.`agentip` = values(`agentip`), " .
						" `plugin_camm_tree2`.`_parent` = IF(`device_type_id`=-1,'0','1'), " .
						" `plugin_camm_tree2`.`typ_count` = values(`typ_count`), " .
						" `plugin_camm_tree2`.`online` = '1' ;");



					db_execute("INSERT INTO `plugin_camm_tree2` (`device_type_name`,`device_type_id`,`device_id`,`description`,                    `hostname`,`gr_f`,`gr_v`,`agentip`,`agentip_source` ,`type`             ,`period`     ,`_is_type`,`_is_device` ,`_parent`     ,`count`,`dev_count`     ,`typ_count`     ,`online`     ,`_is_leaf`,              `_lvl`,`_is_marker`) " .
						" SELECT IFNULL(`t0`.`device_type_name`,'not_defined'),`t0`.`device_type_id`,`t0`.`device_id`,`t0`.`description`,`t0`.`hostname`,'',IF(`t0`.`device_type_id`=-1,`t0`.`gr_v`,''),`t0`.`agentip`,`t0`.`agentip_source`,'" . $tree_type . "'   ,`t0`.`period`,'0'       ,'1'          ,t1.id      ,'0'    ,`t0`.`dev_count`,`t0`.`typ_count`,'1',IF(`t0`.`device_type_id`=-1,'1','0') ,IF(`t0`.`device_type_id`=-1,'2','3'),IF(`t0`.`device_type_id`=-1,'1','0')      " .
						" FROM plugin_camm_temp as t0 join plugin_camm_tree2 as t1 on (t0.type=t1.type and t0.device_type_id=t1.device_type_id and t0.period=t1.period) WHERE `t0`.`type`='" . $tree_type . "' and `t1`.`online`='1' group by hostname,period order by description " .
						" ON DUPLICATE KEY UPDATE " .
						" `plugin_camm_tree2`.`device_type_name` = IFNULL(values(`device_type_name`),'not_defined'), " .
						" `plugin_camm_tree2`.`device_type_id` = values(`device_type_id`), " .	
						" `plugin_camm_tree2`.`hostname` = values(`hostname`), " .						
						" `plugin_camm_tree2`.`device_id` = values(`device_id`), " .	
						" `plugin_camm_tree2`.`description` = values(`description`), " .	
						" `plugin_camm_tree2`.`agentip` = values(`agentip`), " .
						" `plugin_camm_tree2`.`dev_count` = values(`dev_count`), " .
						" `plugin_camm_tree2`.`_parent` = values(`_parent`), " .
						" `plugin_camm_tree2`.`typ_count` = values(`typ_count`), " .
						" `plugin_camm_tree2`.`online` = '1' ;");								
			
					db_execute("INSERT INTO `plugin_camm_tree2` (`device_id`,`description`,                    `hostname`,`gr_f`,`gr_v`,`agentip`,`agentip_source`                              ,`type`              ,`period`,`_is_type`,`_is_device`,`_parent`     ,`count`    ,`dev_count`     ,`online`      ,`_is_leaf`,`_lvl`) " .
						" SELECT `t0`.`device_id`,`t0`.`description`,`t0`.`hostname`,`t0`.`gr_f`,`t0`.`gr_v`,`t0`.`agentip`,`t0`.`agentip_source`,'" . $tree_type . "',`t0`.`period`                                          ,'0'       ,'0'         ,t1.id         ,t0.`count` ,`t0`.`dev_count`,'1' ,'0'       ,'4'      " .
						" FROM plugin_camm_temp as t0 join plugin_camm_tree2 as t1 on (t0.type=t1.type and t0.hostname=t1.hostname and t0.period=t1.period) WHERE `t0`.`type`='" . $tree_type . "' and `t1`.`online`='1' and `_is_marker`='0'  group by hostname,period,gr_f " .
						" ON DUPLICATE KEY UPDATE " .
						" `plugin_camm_tree2`.`dev_count` = values(`dev_count`), " .
						" `plugin_camm_tree2`.`_parent` = values(`_parent`), " .
						" `plugin_camm_tree2`.`count` = values(`count`), " .
						" `plugin_camm_tree2`.`online` = '1' ;");								
								
					db_execute("INSERT INTO `plugin_camm_tree2` (`device_id`,`description`,                    `hostname`,`gr_f`,`gr_v`,`agentip`,`agentip_source`                              ,`type`              ,`period`,`_is_type`,`_is_device`,`_parent`     ,`count`    ,`dev_count`     ,`online`      ,`_is_leaf`,`_lvl`) " .
						" SELECT `t0`.`device_id`,`t0`.`description`,`t0`.`hostname`,`t0`.`gr_f`,`t0`.`gr_v`,`t0`.`agentip`,`t0`.`agentip_source`,'" . $tree_type . "',`t0`.`period`                                ,'0'       ,'0'         ,t1.id         ,t0.`count` ,`t0`.`dev_count`,'1' ,'1'       ,'5'      " .
						" FROM plugin_camm_temp as t0 join plugin_camm_tree2 as t1 on (t0.type=t1.type and t0.hostname=t1.hostname and t0.period=t1.period and t0.gr_f=t1.gr_f) WHERE `t0`.`type`='" . $tree_type . "' and `t1`.`online`='1' and `_is_marker`='0'  group by hostname,period,`t0`.gr_f,gr_v " .
						" ON DUPLICATE KEY UPDATE " .
						" `plugin_camm_tree2`.`dev_count` = values(`dev_count`), " .
						" `plugin_camm_tree2`.`_parent` = values(`_parent`), " .
						" `plugin_camm_tree2`.`count` = values(`count`), " .
						" `plugin_camm_tree2`.`online` = '1' ;");
						
					//превратим ветку с одним содержимом в сам лист
					 db_execute("UPDATE plugin_camm_tree2 t0, (SELECT _parent FROM plugin_camm_tree2 where `_lvl`=5 and `type`='" . $tree_type . "' group by hostname,period,gr_f having count(`id`)=1) as t1 " .
								 " SET `_is_leaf`=1 where t0.id=t1._parent; ");
					// обозначим как ненужные листья, родители которых сами стали листьями (в предыдущем шаге)
					 db_execute("UPDATE plugin_camm_tree2 t0, (SELECT `id` FROM plugin_camm_tree2 where `_lvl`=5 and `type`='" . $tree_type . "' group by hostname,period,gr_f having count(`id`)=1) as t1 " .
								 " SET `online`=0 where t0.id=t1.id; ");
					
					//все, листья маркеров переназначим на родителя с id=2. Сам ID у родителя будем менять на ходу, во время выборки с того, что в базе на 2.
					//db_execute("update cacti.plugin_camm_tree2 set _parent='2' where _is_marker=1 and  _lvl=2;");
					
					// все относящееся к ветке Markers поднимим на один уровень вверх.
					//db_execute("UPDATE `plugin_camm_tree2` SET `_lvl`=(_lvl-1) WHERE `_is_marker`='1' and `type`='" . $tree_type . "'  and `online`='1';");
					//db_execute("UPDATE `plugin_camm_tree2` SET `_parent`='0' WHERE `_is_marker`='1' and `_lvl`='1' and `type`='" . $tree_type . "'  and `online`='1';");
					 
					 db_execute("DELETE FROM `plugin_camm_tree2` WHERE `type`='" . $tree_type . "' and `online`='0';");
					 
					 //обновим поле path для сортировки по нему в базе, а не на стороне клиента..
					 db_execute("UPDATE plugin_camm_tree2, (SELECT t1.id, concat_ws('-',t4.id ,t3.id,t2.id,t1.id) as _pth FROM plugin_camm_tree2 AS t1 LEFT JOIN plugin_camm_tree2 AS t2 ON t2.id = t1._parent LEFT JOIN plugin_camm_tree2 AS t3 ON t3.id = t2._parent LEFT JOIN plugin_camm_tree2 AS t4 ON t4.id = t3._parent order by t1.device_type_name, t1.description) as tmp set `plugin_camm_tree2`.`_path`=`_pth` where plugin_camm_tree2.id=tmp.id;");
					 
					 // у рутового маркера уменьшим значение path что бы он был выше при сортировке..
					db_execute("UPDATE plugin_camm_tree2 set `plugin_camm_tree2`.`_path`=1 where plugin_camm_tree2._is_marker=1 and _lvl=1;");
										
 			camm_debug(" php [camm_poller_recreate_tree] The last time the " . $tree_type . " Tree was recreated at '" . date("Y-m-d G:i:s", read_config_option("camm_last_" . $tree_type . "treedb_time", true)) . "'. " );
			db_execute("REPLACE INTO settings (name, value) VALUES ('camm_last_" . $tree_type . "treedb_time', '" . strtotime("now") . "')");
			
 
 			list($micro,$seconds) = split(" ", microtime());
 			$end = $seconds + $micro;
 			$camm_stats_tree = sprintf(
 			"Tree" . $tree_type . "Time:%01.4f " ,
 			round($end-$start,4));
 				/* log to the database */
 			db_execute("REPLACE INTO settings (name,value) VALUES ('camm_stats_" . $tree_type . "_tree', '" . $camm_stats_tree . "')");
 			
 			$rezult = 1;
 		}
 	}
/* take time and log performance data */
list($micro,$seconds) = split(" ", microtime());
$end = $seconds + $micro;	
camm_debug(" S. CAMM [" . $tree_type . "] Tree was recreated for [" . round($end-$start,4) . "] sec with rezult=[" . $rezult . "]");
camm_debug(" php [camm_poller_recreate_tree] The last time the " . $tree_type . " Tree was manually recreated at '" . date("Y-m-d G:i:s", read_config_option("camm_last_" . $tree_type . "treedb_time", true)) . "'. " );
	
 	return $rezult;
 
 }
 
 function camm_check_dependencies() {
 	global $plugins, $config;
 	$rezult = false;
 	if (in_array('settings', $plugins)) {
 		$v = settings_version();
 		if ($v['version'] >= 0.2) {
 			$rezult = true;
 		}
 		
 	}else{
		$v = db_fetch_cell("SELECT `version`  FROM `plugin_config` where `directory`='settings';");
		if (isset($v) and $v >= 0.2 ){
			$rezult = true;
		}
	
	}
	db_execute("UPDATE  `settings` SET `value`=" . $rezult . " where `name`='camm_dependencies'");
	//force update camm_dependencies in cache
	$temp = read_config_option("camm_dependencies", $rezult);
 	return $rezult;
	
 } 
 
 function camm_sendemail($to, $from, $subject, $message, $email_format) {
 	
	if (read_config_option("camm_dependencies")) {
 		camm_debug("  Sending Alert email to '" . $to . "'\n");
		if ($email_format == 1) {
			send_mail($to, $from, $subject, $message);
		}else{
			$headers = "MIME-Version: 1.0\r\n";
			$headers .= "Content-Type: text/html; charset=ISO-8859-1\r\n";
			send_mail($to, $from, $subject, "<html><body>$message</body></html>", $headers); 
		}
 	} else {
 		camm_debug("  Error: Could not send alert, you are missing the Settings plugin\n");
 	}
 }
 
 function camm_check_regexp ($regexp) {
 $RegEx = new RegEx;
 
  $rezult = array();
  $expression = preg_quote($regexp);
  $rezult["rezult"] = $RegEx->isValid("/" . $expression . "/");
  if (!$rezult["rezult"]) {
     $rezult["error"] = 'Your regular expression is invalid because: ' . $RegEx->error();
  }else{
 	$rezult["regexp"] = $expression;
  }
  return $rezult;
 }
 
 
 
 /**
  * @param string $function_name The user function name, as a string.
  * @return Returns TRUE if function_name  exists and is a function, FALSE otherwise.
  */
 function camm_user_func_exists($function_name = 'do_action') {
   
     $func = get_defined_functions();
   
     $user_func = array_flip($func['user']);
   
     unset($func);
   
     return ( isset($user_func[$function_name]) );  
 }
 
 
 function is_camm_admin () {
 global $user_auth_realm_filenames;
 $rezult = 0;
 //get camm Plugin -> camm: Manage Realm ID
 $camm_admin_realm_id = db_fetch_cell("SELECT `id`+100 FROM `plugin_realms` where `display` like 'plugin%camm%manage%'");
 
 if (isset($camm_admin_realm_id)) {
 	//Check this user - for camm admin realms
 	if ((!empty($camm_admin_realm_id)) && (db_fetch_assoc("select user_auth_realm.realm_id from user_auth_realm
 		where user_auth_realm.user_id='" . $_SESSION["sess_user_id"] . "'
 		and user_auth_realm.realm_id='$camm_admin_realm_id'"))) {
 			$rezult = 1;
 		}	
 }
 
 return $rezult;
 
 }
 
 function camm_JEncode($arr){
 global $config;
     
 	if (function_exists('json_encode')) {
          $data = json_encode($arr);  //encode the data in json format
 		 
     }else{
 		require_once($config["base_path"] . "/plugins/camm/lib/JSON.php"); //if php<5.2 need JSON class
         $json = new Services_JSON();//instantiate new json object
         $data=$json->encode($arr);  //encode the data in json format	
 	}
 		
     return $data;
 }
 
 function camm_JDecode($arr){
 global $config;
     
 	if (function_exists('json_decode')) {
          $data = json_decode($arr);  //encode the data in json format
 		 
     }else{
         require_once($config["base_path"] . "/plugins/camm/lib/JSON.php"); //if php<5.2 need JSON class
         $json = new Services_JSON();//instantiate new json object
         $data=$json->decode(stripslashes($arr));  //encode the data in json format	
 	}
 		
     return $data;
 }
 
 
 function get_graph_camm_url ($sql_like_graph) {
 
 $rezult = 0;
 //get graph template ID
 $graph_template_id = db_fetch_cell("SELECT `id` FROM `graph_templates` where `name` like '" . $sql_like_graph . "'");
 
 if ((isset($graph_template_id)) && ($graph_template_id>0)) {
 	//Now find Graph ID
 	$graph_id = db_fetch_cell("SELECT `id` FROM `graph_local` where `graph_template_id`='" . $graph_template_id . "'");
 	
 	if ((isset($graph_id)) && ($graph_id>0)) {
 		$rezult = $graph_id;
 	}
 }
 
 return $rezult;
 
 }
 
 
 function camm_input_validate_input_regex($value, $regex, $error_msg = '') {
 	if ((!preg_match($regex, $value)) && ($value != "")) {
 		camm_die_html_input_error($error_msg);
 	}
 }
 
 function camm_die_html_input_error($error_msg) {
 	echo json_encode(array('failure' => true,'error' => $error_msg));
 	exit;
 }
 
 function camm_process_rule ($rule, $force = false) {

$ar_order = array(
	"1" => array("func","mail","del","mark"),
	"2" => array("mail","func","mark","del"),
	"3" => array("mark","mail","func","del"),
	"4" => array("mail","mark","func","del")
);
$camm_email_title = read_config_option("camm_email_title");

 //camm_debug("  - Found " . $stat_ruleDeleTraps . " new trap" . ($stat_ruleDeleTraps == 1 ? "" : "s" ) . " to process");
 camm_debug(" = Start process rule id=[" . $rule["id"] . "]");
 
 //$rule = db_fetch_row("SELECT * FROM `plugin_camm_rule` where `rule_enable`=1 AND `id`='" . $rule_id . "';");
 $rezult = 1;
 
 	$sql_where = '';
 	$alertm = '';
 
	
 	$sql_where = getSQL(camm_JDecode(stripslashes($rule["json_filter"])));
 
 	if ($rule["rule_type"] == 'snmptt') {
 		$table = '`plugin_camm_snmptt`';
 		$col_alert = '`alert`';
 		$col_message = 'formatline';
		$ktype = 2;
	}elseif($rule["rule_type"] == 'syslog') {
 		if ((strlen(trim(read_config_option("camm_syslog_pretable_name"))) > 0) && (read_config_option("camm_syslog_pretable_name") != "plugin_camm_syslog") && ($force == false)) {
 			$syslog_use_pretable = true;
 			$table = '`' . read_config_option("camm_syslog_db_name") . '`.`' . read_config_option("camm_syslog_pretable_name") . '`';
 		}else{
 			$syslog_use_pretable = false;
 			$table = '`' . read_config_option("camm_syslog_db_name") . '`.`plugin_camm_syslog`';
 		}
 		$col_alert = '`alert`';
 		$col_message = 'message';
		$ktype = 1;
 	}else{
 		$rezult = "Incorrect rule type";
 	}
	//в зависимости от режима нужно знать какие записи обрабатывать - новые или уже обработанные
	if ($force) {
		$sql_force = " " . $table . ".`status`=2";
		camm_debug("   - Process already processed record (force execute)");
	}else{
		$sql_force = " " . $table . ".`status`=1";
		camm_debug("   - Process only new records");
	}


	
	$sql_where = $sql_where . " AND " . $sql_force;
	
 	camm_debug("   - SQL where conditions=[" . $sql_where . "]");
 
 	if (($sql_where != '') || ($rezult == 1)) {
 		
		$pos = strpos($sql_where, 'host_template_id');
		$use_fqdn = (read_config_option("camm_use_fqdn", true) == '1');		

		if ($use_fqdn && (read_config_option("camm_join_field") == "hostname")) {
			$sql_fqdn_host = " SUBSTRING_INDEX(`host`.`hostname`,'.',1) ";
		}else{
			$sql_fqdn_host = " `host`.`hostname` ";
		}
		
			if ($rule["rule_type"] == 'snmptt') {

				if (read_config_option("camm_join_field") == "sourceip") {
					$join_field = "agentip";
				}else{
					$join_field = "hostname";		
				}
				
				if ($rule["sup_mode"]=="2") { //by host
					$supress_sql = " count(*) as count_r,min(`traptime`) as date_min,max(`traptime`) as date_max,  ";
					$group_sql = " group by `" . $join_field . "`";
				}else{
					$supress_sql = " ";
					$group_sql = " ";
				}
				
				if ($pos === false) { //sql без использования поиска по типу устройства				
					$sql_update = " INSERT IGNORE  INTO `plugin_camm_keys` (`krid`,`rule_id`,`ktype`)  SELECT `id`, '" . $rule["id"] . "','2' FROM " . $table . " WHERE " . $sql_where;
				
				}else{
					$sql_update = " INSERT IGNORE  INTO `plugin_camm_keys` (`krid`,`rule_id`, `ktype`) SELECT  " . $table . ".`id`,'" . $rule["id"] . "','2' FROM " . $table . " LEFT JOIN host ON (" . $table . ".`" . $join_field . "`=" . $sql_fqdn_host . ") WHERE " . $sql_where;						
					
				}
				
 			}elseif($rule["rule_type"] == 'syslog'){

				if (read_config_option("camm_join_field") == "sourceip") {
					$join_field = "sourceip";
				}else{
					$join_field = "host";		
				}
				
				if ($rule["sup_mode"]=="2") { //by host
					$supress_sql = " count(*) as count_r,min(`sys_date`) as date_min,max(`sys_date`) as date_max,  ";
					$group_sql = " group by `" . $join_field . "`";
				}else{
					$supress_sql = " ";
					$group_sql = " ";
				}

				if ($pos === false) { //sql без использования поиска по типу устройства
					
					$sql_update = " INSERT IGNORE  INTO `plugin_camm_keys` (`krid`,`rule_id`,`ktype`)  SELECT `id`, '" . $rule["id"] . "','1' FROM " . $table . " WHERE " . $sql_where;
					
				}else{ //with host type search.
					$sql_update = " INSERT IGNORE  INTO `plugin_camm_keys` (`krid`,`rule_id`, `ktype`) SELECT  " . $table . ".`id`,'" . $rule["id"] . "','1' FROM " . $table . " LEFT JOIN host ON (" . $table . ".`" . $join_field . "`=" . $sql_fqdn_host . ") WHERE " . $sql_where;
				}
			}
		
		db_execute($sql_update);
 		//$records_updated=mysql_affected_rows(); - больше использовать нельзя, ибо если INSERT IGNORE - то  он будет не обновлять если уже есть. REPLACE дает двойное увеличение - так как сначала удаляет, а потом вставляет.

		
 		if (($rule["is_function"]=="1") || ($rule["is_email"]=="1")) { //for this type of actions we are need array of alerted rows
 			if ($rule["inc_cacti_name"]=="2") {
				$alerted_rows = db_fetch_assoc("SELECT " . $supress_sql . " " . $table . ".*,`host`.`description`  FROM `plugin_camm_keys` LEFT JOIN " . $table . " ON (`krid`=" . $table . ".`id`) LEFT JOIN `host` ON (" . $table . ".`" . $join_field . "` = " . $sql_fqdn_host . ") WHERE `plugin_camm_keys`.`rule_id`='" . $rule["id"] . "' AND `ktype`=" . $ktype . " AND " . $sql_force . " " . $group_sql . " ;");
				//$alerted_rows = db_fetch_assoc("SELECT " . $supress_sql . " " . $table . ".*,`host`.`description`  FROM " . $table . " LEFT JOIN `host` ON (" . $table . ".`" . $join_field . "` = `host`.`hostname`) WHERE " . $col_alert . "='" . $rule["id"] . "' AND " . $sql_force . " " . $group_sql . " ;");
			}else{
				$alerted_rows = db_fetch_assoc("SELECT " . $supress_sql . " " . $table . ".*  FROM `plugin_camm_keys` LEFT JOIN " . $table . " ON (`krid`=" . $table . ".`id`) WHERE `plugin_camm_keys`.`rule_id`='" . $rule["id"] . "' AND `ktype`=" . $ktype . " AND " . $sql_force . " " . $group_sql . " ;");
				//$alerted_rows = db_fetch_assoc("SELECT *, '-' as description  " . $supress_sql . "  FROM " . $table . " WHERE " . $col_alert . "='" . $rule["id"] . "' AND " . $sql_force . " " . $group_sql . " ;");
			}
			$records_updated = sizeof($alerted_rows);
			camm_debug("   - Select records to process = [" . $records_updated . "]\n");
 		}else{
			$records_updated = db_fetch_cell("SELECT count(*)  FROM `plugin_camm_keys` LEFT JOIN " . $table . " ON (`krid`=" . $table . ".`id`) WHERE `plugin_camm_keys`.`rule_id`='" . $rule["id"] . "' AND " . $sql_force . " " . $group_sql . " ;");
			camm_debug("   - No need select records to process.  Updated records=[" . $records_updated . "]\n");
 		}

		if ($force) {
			// если обрабатываем все записи - значит нужно заменить счетчик даже при его нулевом значении
			db_execute("UPDATE `plugin_camm_rule` SET `count_triggered`=`count_triggered`+'" . $records_updated . "',`actual_triggered`='" . $records_updated . "'  WHERE id='" . $rule["id"] .  "';");
		}else{
			if ($records_updated > 0) {
				//иначе - просто добавим к счетчику.
				db_execute("UPDATE `plugin_camm_rule` SET `count_triggered`=`count_triggered`+'" . $records_updated . "',`actual_triggered`=`actual_triggered`+'" . $records_updated . "' WHERE id='" . $rule["id"] .  "';");
			}
		} 			
		
		if (isset($ar_order[read_config_option("camm_action_order")])) {
			$action_order = $ar_order[read_config_option("camm_action_order")];
		}else{
			$action_order = $ar_order[1];
		}
		
		foreach($action_order as $step) {
		
		switch ($step) {
			case "func":
				if ($rule["is_function"]=="1") { //execute user functions
				
					if (strlen(trim($rule["function_name"])) > 0) {
						if (function_exists($rule["function_name"])) {
							if (sizeof($alerted_rows) > 0) {
								call_user_func_array($rule["function_name"], array($alerted_rows, $rule));
								camm_debug("   -1 Execute user function [" . $rule["function_name"] . "]");
							}
						}
					}
				}
			break;
			
			case "mail":
				if ($rule["is_email"]=="1") { //email alert rule
					if (sizeof($alerted_rows) > 0) {
							camm_debug("   Alert Rule '" . $rule['name'] . "' - Email Action - has been activated\n");
							foreach ($alerted_rows as $alerted_trap) {
								$alerted_trap[$col_message] = str_replace('  ', "\n", $alerted_trap[$col_message]);
								while (substr($alerted_trap[$col_message], -1) == "\n") {
									$alerted_trap[$col_message] = substr($alerted_trap[$col_message], 0, -1);
								}

								if ($rule["rule_type"] == 'snmptt') {
									$alertm .= $camm_email_title . "<br><br>";
									$alertm .= 'Hostname    : ' . $alerted_trap['hostname'] . "<br>";
									$alertm .= 'Description : ' . $alerted_trap['description'] . "<br>";
									$alertm .= 'Date        : ' . $alerted_trap['traptime'] . "<br>";
									$alertm .= 'EventName   : ' . strtoupper($alerted_trap['eventname']) . "<br>";
									$alertm .= 'TrapOid     : ' . $alerted_trap['trapoid'] . "<br>";
									$alertm .= 'Category    : ' . strtoupper($alerted_trap['category']) . "<br>";
									$alertm .= 'Severity    : ' . strtoupper($alerted_trap['severity']) . "<br>";
									$alertm .= 'Trap Message : ' . $alerted_trap['formatline'] . "<br>";
									$alertm .= 'Notes	    : ' . $rule['email_message'] . "<br>";
									$alertm .= "-----------------------------------------------<br><br>";
								}elseif ($rule["rule_type"] == 'syslog') {
									$alertm .= $camm_email_title . "<br><br>";
									if ($rule["sup_mode"] > 1) {
										$alertm .= 'Count :      ' . $alerted_trap['count_r'] . "<br>";
									}
									$alertm .= 'Host :      ' . $alerted_trap['host'] . "<br>";
									$alertm .= 'Description    : ' . $alerted_trap['description'] . "<br>";
									$alertm .= 'Date :      ' . $alerted_trap['sys_date'] . "<br>";
									$alertm .= 'IP Address :  ' . $alerted_trap['sourceip'] . "<br>";
									$alertm .= 'Facility :  ' . strtoupper($alerted_trap['facility']) . "<br>";
									$alertm .= 'Priority :  ' . strtoupper($alerted_trap['priority']) . "<br>";
									$alertm .= 'Message  :  ' . $alerted_trap['message'] . "<br><br>";               
									$alertm .= 'Alert : '  . $rule['email_message'] . "<br>";   
									$alertm .= "-----------------------------------------------<br><br>";	
								} 
								
								//each record in separate email message
								if ($rule["email_mode"]=="2"){
									if ($alertm != '') {
										camm_sendemail($rule['email'], '', 'Event Alert - ' . $rule['name'], $alertm, $rule['email_format']);
										camm_debug($rule['email'] . "   " . 'Event Alert - ' . "   " . $rule['name'] . "   " . $alertm);
									}						
									$alertm = '';
								}

							}
							
							if ($alertm != '') {
								camm_sendemail($rule['email'], '', 'Event Alert - ' . $rule['name'] . " (" . sizeof($alerted_rows) . ") times", $alertm, $rule['email_format']);
								camm_debug($rule['email'] . "   " . 'Event Alert - ' . "   " . $rule['name'] . "   " . $alertm);
							}
					}else{
						camm_debug("   Alert Rule '" . $rule['name'] . "' - Email Action - has been activated without records\n");
					}

				}
			break;
			
			case "del":
				if ($rule["is_delete"]=="1") { //delete records
					db_execute("DELETE " . $table . ", plugin_camm_keys FROM " . $table . 
						" left join plugin_camm_keys on (`krid`=" . $table . ".`id`) " .
						" where rule_id='" . $rule["id"] . "' and " . $sql_force . " ;");
					camm_debug("   -3 Delete records.");
				}
			break;
			//маркировка как токовая теперь не используеться - выборка будет напрямую через правило с данным маркером.
			// case "mark":				
				// if ($rule["is_mark"]=="1") { //mark records
					// db_execute("UPDATE " . $table . " SET " . $col_alert . "='" . $rule["marker"] . "' WHERE " . $col_alert . "='" . $rule["id"] . "' AND " . $sql_force . ";");
					// camm_debug("   -4 Mark records [" . $rule["marker"] . "]");
				// }
			// break;			
			}
		}
			
			

			


 		
 	}
 
 return $rezult;
 }
 
function camm_create_sql_from_tree ($tree_id) {
$tree_sql = '';
$j_field = '';

if ($tree_id > 0) {
	
	$tree_row = db_fetch_row("select * from `plugin_camm_tree2` where `id`=" . $tree_id . "; ");
	$tree_type = $tree_row["type"];
	
	if ($tree_type == 'snmptt') {
		$j_field = 'hostname';
	}elseif($tree_type == 'syslog'){
		$j_field = 'host';
	}
	
	if (($j_field != '') || ($tree_id == 2)) { //если неизвестен тип (нет строки с таким id) - тогда нечего делать  ИЛИ ID=2 (т.е. рутовый маркер)
		if (isset($tree_row["_is_marker"]) && $tree_row["_is_marker"]=='1') {
			$tree_sql = " and `alert`='" . $tree_row["gr_v"] . "'";
		}elseif($tree_id == 2){
			$tree_sql = " and `alert`>0";
		}else{				
			if (isset($tree_row["_lvl"])) {
				switch($tree_row["_lvl"]){
					case "1":
						$tree_sql = " ";
						break;
					case "3":
						$tree_sql = " and  (`" . $j_field . "` = '" . $tree_row["hostname"] . "') ";
						break;
					case "5":
						$tree_sql = " and (`" . $tree_row["gr_f"] . "` = '" . $tree_row["gr_v"] . "' and  `" . $j_field . "` = '" . $tree_row["hostname"] . "') ";
						break;
					case "4":
						$tree_sql = " and  (`" . $j_field . "` = '" . $tree_row["hostname"] . "') ";
						break;						
					case "2":
						$search_hostnames = db_fetch_assoc("SELECT `hostname` FROM plugin_camm_tree2 where `device_type_id`='" . $tree_row["device_type_id"] . "' AND `type`='" . $tree_type . "' and `_lvl`=3  group by hostname;");
						$search_hostname = '';
						for ($i=0;($i<count($search_hostnames));$i++) {
							$search_hostname = $search_hostname . "'" . $search_hostnames[$i]["hostname"] . "', ";
						}
						$search_hostname = substr($search_hostname, 0, strlen($search_hostname) -2);		

						$tree_sql = " and (`" . $j_field . "` IN (" . $search_hostname . ")) ";
						break;				
				}					
			}else{
				$tree_sql = "";
			}
		}
	}else{
		$tree_sql = "";
	}

}else{
	$tree_sql = "";
}


return $tree_sql;

} 

function camm_create_sql_from_period ($period, $tree_type) {
$period_sql=' 1=1 ';
	switch ($period) {

		case "day":
			$period_sql=" DATE_FORMAT(`" . ($tree_type == 'snmptt' ? "traptime":"sys_date") . "`,'%Y-%m-%d')='" . date("Y-m-d") . "' ";
		break;
		case "week":
			$period_sql=" WEEKOFYEAR(`" . ($tree_type == 'snmptt' ? "traptime":"sys_date") . "`)=" . date("W") . " ";
		break;
		case "hour":
			$period_sql=" DATE_FORMAT(`" . ($tree_type == 'snmptt' ? "traptime":"sys_date") . "`,'%Y-%m-%d-%H')='" . date("Y-m-d-H") . "' ";
		break;				
	}
return $period_sql;
}

function camm_set_memory_limit ($memory_minimum) {

	//$memory_minimum = 128 * 1024 * 1024;
	$memory_limit = ini_get('memory_limit');
	// -1 means no limit
	if ($memory_limit != -1) {
	 switch (strtolower(substr($memory_limit, -1))) {
		 case 'g': // Gigabytes
			// Minimum is 1GB, so no need to raise it
		 break;
			 case 'm': // Megabytes
			 if ((substr($memory_limit, 0, -1) * 1024 * 1024) < $memory_minimum) {
			 @ini_set('memory_limit', $memory_minimum);
			 }
			 break;
		 case 'k': // Kilobytes
			 if ((substr($memory_limit, 0, -1) * 1024) < $memory_minimum) {
			 @ini_set('memory_limit', $memory_minimum);
			 }
			 break;
		 default: // Just bytes
			 if (ctype_digit($memory_limit) && $memory_limit < $memory_minimum) {
			 @ini_set('memory_limit', $memory_minimum);
			 }
	 }
	}


}




 ?>
