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
 function plugin_init_camm() {
 
     global $plugin_hooks;
     $plugin_hooks['config_arrays']['camm'] = 'camm_config_arrays'; 
     $plugin_hooks['config_settings']['camm'] = 'camm_config_settings'; // Settings tab
     $plugin_hooks['top_header_tabs']['camm'] = 'plugin_camm_show_tab'; // Top tab
     $plugin_hooks['top_graph_header_tabs']['camm'] = 'plugin_camm_show_tab'; // Top tab for graphs
     $plugin_hooks['draw_navigation_text']['camm'] = 'camm_draw_navigation_text';
 	$plugin_hooks['poller_top']['camm'] = 'camm_poller_bottom';
 
 }
 
 function plugin_camm_install () {
 
     api_plugin_register_hook('camm', 'top_header_tabs', 'plugin_camm_show_tab', 'includes/tab.php');
     api_plugin_register_hook('camm', 'top_graph_header_tabs', 'plugin_camm_show_tab', 'includes/tab.php');
     api_plugin_register_hook('camm', 'config_arrays', 'camm_config_arrays', 'setup.php');
     api_plugin_register_hook('camm', 'config_settings', 'camm_config_settings', 'setup.php');
     api_plugin_register_hook('camm', 'draw_navigation_text', 'camm_draw_navigation_text', 'setup.php');
     api_plugin_register_hook('camm', 'poller_bottom', 'camm_poller_bottom', 'setup.php');
 	api_plugin_register_hook('camm', 'page_title', 'camm_page_title', 'setup.php');
 	
 
     //Check - if this is a upgrade from snmptt plugin
 	$old_snmp_tt_realm = db_fetch_cell("SELECT count(*) FROM `plugin_realms` where `plugin`='snmptt';");
 	$new_camm_realm = db_fetch_cell("SELECT count(*) FROM `plugin_realms` where `plugin`='camm';");
 	if (($old_snmp_tt_realm > 0) && ($new_camm_realm == 0)){
 		db_execute("UPDATE `plugin_realms` SET `plugin`='camm', `file`='camm_view.php,camm_db.php', `display`='Plugin -> camm: View' WHERE `plugin`='snmptt' and `file`='snmptt_view.php,snmptt_db.php';");
 		db_execute("UPDATE `plugin_realms` SET `plugin`='camm', `file`='camm_db_admin.php', `display`='Plugin -> camm: Manage' WHERE `plugin`='snmptt' and `file`='snmptt_db_admin.php';");
 		
 		camm_raise_message3(array("device_descr" => "Upgrade from SNMPTT plugin" , "type" => "upgrade_db", "object"=> "update","cellpading" => false, "message" => "upgrade realms from snmptt plugin", "step_rezult" => "OK", "step_data" => "OK"));     
 	}else{	
 		api_plugin_register_realm('camm', 'camm_view.php,camm_db.php', 'Plugin -> camm: View', 1);
 		api_plugin_register_realm('camm', 'camm_db_admin.php', 'Plugin -> camm: Manage', 1);
 	}
 	
 	camm_setup_table ();
 
 }
 
 function plugin_camm_show_tab () {
 
 	global $config, $user_auth_realm_filenames;
 	$realm_id2 = 0;
 
 	if (isset($user_auth_realm_filenames{basename('camm_view.php')})) {
 		$realm_id2 = $user_auth_realm_filenames{basename('camm_view.php')};
 	}
 	
 	if ((db_fetch_assoc("select user_auth_realm.realm_id
 		from user_auth_realm where user_auth_realm.user_id='" . $_SESSION["sess_user_id"]
 		. "'and user_auth_realm.realm_id='$realm_id2'")) || (empty($realm_id2))) {
 		print '<a href="' . $config['url_path'] . 'plugins/camm/camm_view.php"><img src="' . $config['url_path'] . 'plugins/camm/images/tab_camm';
 		// Red tab code
 		if(preg_match('/plugins\/camm\/camm_view.php/',$_SERVER['REQUEST_URI'] ,$matches) || preg_match('/plugins\/camm\/camm_alert.php/',$_SERVER['REQUEST_URI'] ,$matches))
 		{
 			print "_red";
 		}
		unset($matches);
		
 		if(read_config_option("camm_tab_image_size") == "1"){
 			print "_small";
 		}
 
 		print '.gif" alt="camm" align="absmiddle" border="0"></a>';
 	}
 	camm_check_upgrade ();	
 }
 
 function camm_page_title ($in) {
 	global $config;
 	
 	$out = $in;
 	
 	$url = $_SERVER['REQUEST_URI'];
 		
 	if(preg_match('#/plugins/camm/camm_view.php#', $url))
 	{
 		$out .= " - CAMM (CActi Message Managment)";
 	}
 		
 	return ($out);	
 }
 
 function plugin_camm_uninstall () {
     // Do any extra Uninstall stuff here
 	db_execute("delete FROM settings where name like 'camm%';");
 	kill_session_var("camm_output_messages");
 }
 
 
 function plugin_camm_check_config () {
     // Here we will check to ensure everything is configured
     camm_check_upgrade ();
     return true;
 }
 
 function plugin_camm_upgrade () {
     // Here we will upgrade to the newest version
     camm_check_upgrade ();
     return false;
 }
 
 function plugin_camm_version () {
     // Here we will upgrade to the newest version
     return camm_version ();
 }
 
 function camm_config_arrays () {
 	global $user_auth_realms, $menu, $user_auth_realm_filenames;
 	global $camm_poller_purge_frequencies, $camm_purge_delay, $camm_purge_tables,  $camm_rows_test, $camm_tree_update, $camm_rows_selector,  $camm_grid_update;
 
 
     $camm_rows_test = array(
     100 => "100",
     200 => "200",
     500 => "500",
     1000 => "1000",
 	5000 => "5000",
 	10000 => "10000",
     0 => "ALL");
 	
 	$camm_rows_selector = array(
 		-1 => "Default",
 		10 => "10",
 		15 => "15",
 		20 => "20",
 		30 => "30",
 		50 => "50",
 		100 => "100",
 		500 => "500",
 		1000 => "1000",
 		-2 => "All");	
 		
 	$camm_poller_purge_frequencies = array(
 		"disabled" => "Disabled",
 		"10" => "Every 10 Minutes",
 		"15" => "Every 30 Minutes",
 		"60" => "Every 1 Hour",
 		"120" => "Every 2 Hours",
 		"240" => "Every 4 Hours",
 		"480" => "Every 8 Hours",
 		"720" => "Every 12 Hours",
 		"1440" => "Every Day");
 	$camm_purge_delay = array(
 		"1" => "1 Day",
 		"3" => "3 Days",
 		"5" => "5 Days",
 		"7" => "1 Week",
 		"14" => "2 Week",
 		"30" => "1 Month",
 		"60" => "2 Month");	
 	$camm_tree_update = array(
 		"30" => "30 Sec",
 		"60" => "1 Minute",
 		"120" => "2 Minutes",
 		"180" => "3 Minutes",
 		"300" => "5 Minutes",
 		"600" => "10 Minutes",
 		"1800" => "30 Minutes",
 		"3600" => "Every 1 Hour",
 		"7200" => "Every 2 Hours",
 		"14400" => "Every 4 Hours",
 		"28800" => "Every 8 Hours"		
 		);		
 	$camm_purge_tables = array(
 		"1" => "plugin_camm_traps",
 		"2" => "plugin_camm_unknown_traps",
 		"3" => "both");
 	$camm_grid_update = array(
 		"0" => "Never",
 		"0.2" => "12 Sec",
 		"0.5" => "30 Sec",
 		"1" => "1 Minute",
 		"5" => "5 Minutes",
 		"10" => "10 Minutes",
 		"15" => "15 Minutes",
 		"30" => "30 Minutes",
 		"60" => "Every 1 Hour"
 		);			
 }
 
 function camm_config_settings () {
 	global $tabs, $settings, $camm_poller_purge_frequencies, $camm_purge_delay, $camm_purge_tables, $camm_tree_update, $camm_rows_test, $camm_grid_update;
	global $database_default;
 
 	$tabs["camm"] = "camm";
	$camm_use_group_by_host = read_config_option("camm_use_group_by_host", true);
	
 	$settings["camm"] = array(
 		"camm_hdr_components" => array(
 			"friendly_name" => "1. CaMM components",
 			"method" => "spacer",
 			),
 		"camm_use_snmptt" => array(
 			"friendly_name" => "Use SNMPTT",
 			"description" => "Use SNMPTT component (both traps and unknown traps)",
 			"order" => "1.1.",			
 			"method" => "drop_array",
 			"default" => "false",
 			"array" => array(1=>"true",0=>"false"),
 			),
 		"camm_use_syslog" => array(
 			"friendly_name" => "Use SYSLOG",
 			"description" => "Use Syslog-ng database data",
 			"order" => "1.2.",			
 			"method" => "drop_array",
 			"default" => "false",
 			"array" => array(1=>"true",0=>"false"),
 			),
 		"camm_use_cactilog" => array(
 			"friendly_name" => "Use Cacti log",
 			"description" => "Use Cacti log from database",
 			"order" => "1.3.",			
 			"method" => "drop_array",
 			"default" => "not yet :)",
 			"array" => array(0=>"not yet :)"),
 			),				
 		"camm_hdr_general" => array(
 			"friendly_name" => "2. CaMM General Settings",
 			"method" => "spacer",
 			),			
 		"camm_test_row_count" => array(
 			"friendly_name" => "Count rows to test",
 			"description" => "Choose count rows to test with rule when create it.",
 			"order" => "2.1.",
 			"method" => "drop_array",
 			"default" => "1000",
 			"array" => $camm_rows_test,			
 			),
 		"camm_autopurge_timing" => array(
 			"friendly_name" => "Data Purge Timing",
 			"description" => "Choose when auto purge records from database.",
 			"order" => "2.2.",			
 			"method" => "drop_array",
 			"default" => "disabled",
 			"array" => $camm_poller_purge_frequencies,
 			),		
 		"camm_show_all_records" => array(
 			"friendly_name" => "Show all records",
 			"description" => "Choose - show all records or only already processed by rules.",
 			"order" => "2.3.",			
 			"method" => "drop_array",
 			"default" => "show all",
 			"array" => array(0=>"show only processed",1=>"show all"),
 			),
 		"camm_join_field" => array(
 			"friendly_name" => "Join field name",
 			"description" => "Choose join field on which record (trap or syslog) will be joined with cacti device's",
 			"order" => "2.3.",			
 			"method" => "drop_array",
 			"default" => "IP-address (if you device DON'T use DNS name)",
 			"array" => array("hostname"=>"DNS-hostname (if you device use DNS name)","sourceip"=>"IP-address (if you device DON'T use DNS name)"),
 			),
 		"camm_use_fqdn" => array(
 			"friendly_name" => "Hostname include domain",
 			"description" => "Do you use host with FQDN in cacti device's ?",
 			"order" => "2.4.",			
 			"method" => "drop_array",
 			"default" => "Don't use FQDN in cacti hosts hostname (like cacti) - default",
 			"array" => array(0=>"0 - Don't use FQDN in cacti hosts hostname (like cacti) - default",1=>"1 - Use FQDN in cacti hosts hostname(like cacti.domain.local). Parameter [Join field name] MUST BE hostname"),
 			),			
 		"camm_debug_mode" => array(
 			"friendly_name" => "Debug mode",
 			"description" => "Enable debug mode for more verbose output in cacti log file",
 			"order" => "2.5.",			
 			"method" => "drop_array",
 			"default" => "0",
 			"array" => array(0=>"Disable",1=>"Enable"),	
 			),
 		"camm_general_graphs_ids" => array(
 			"friendly_name" => "Graphs ID's to show",
 			"description" => "Enter the Graph's ID to show in stats tab.",
 			"order" => "2.6.",			
 			"method" => "textbox",
 			"value" => "|arg1:camm_general_graphs_ids|",
 			"default" => "0",
 			"max_length" => "50",
 			),			
 		"camm_tab_image_size" => array(
 			"friendly_name" => "Tab style",
 			"description" => "Which size tabs to use?",
 			"order" => "2.7.",			
 			"method" => "drop_array",
 			"default" => "0",
 			"array" => array(0=>"Regular",1=>"Smaller"),	
 			),
		"camm_process_markers" => array(
 			"friendly_name" => "Create tree menu for Markers",
 			"description" => "Create tree menu based on markers (alert) field ?",
 			"order" => "2.8.",			
 			"method" => "drop_array",
 			"default" => "0",
 			"array" => array(1=>"true",0=>"false"),
 			),
		"camm_rule_order" => array(
 			"friendly_name" => "Choose a sort order rules",
 			"description" => "What sort of use? (which rules to handle in the first place)",
 			"order" => "2.9.",			
 			"method" => "drop_array",
 			"default" => "1",
 			"array" => array(1=>"default (first maximum for removal)",2=>"by order field"),
 			),
		"camm_action_order" => array(
 			"friendly_name" => "Choose a order of actions in rule",
 			"description" => "In what order to perform actions in each rule?",
 			"order" => "2.10.",			
 			"method" => "drop_array",
 			"default" => "1",
 			"array" => array(1=>"func, mail, del or mark (default )",2=>"mail, func, mark, del",3=>"mark, mail, func, del",4=>"mail, mark, func, del"),
 			),
 		"camm_email_title" => array(
 			"friendly_name" => "Email Title",
 			"description" => "Enter string that will be put in every email",
 			"order" => "2.11.",			
 			"method" => "textbox",
 			"value" => "|arg1:camm_email_title|",
 			"default" => "An alert has been issued that requires your attention.",
 			"max_length" => "150",
 			),
		"camm_use_group_by_host" => array(
 			"friendly_name" => "Use grouping by host's hostname ? (Read-only)",
 			"description" => "If the host table has records with identical values of the hostname field that will be used to force grouping",
 			"order" => "2.11.",			
 			"method" => "drop_array",
 			"default" => "1",
 			),		
 		"camm_hdr_period" => array(
 			"friendly_name" => "3. CaMM Period Settings",
 			"method" => "spacer",
 			),
		"camm_period_hour" => array(
 			"friendly_name" => "Hour period",
 			"description" => "Create menu and stat for hour period ?",
 			"order" => "3.1.",			
 			"method" => "drop_array",
 			"default" => "1",
 			"array" => array(1=>"true",0=>"false"),
 			),
		"camm_period_day" => array(
 			"friendly_name" => "Day period",
 			"description" => "Create menu and stat for day period ?",
 			"order" => "3.2.",			
 			"method" => "drop_array",
 			"default" => "1",
 			"array" => array(1=>"true",0=>"false"),
 			),
		"camm_period_week" => array(
 			"friendly_name" => "Week period",
 			"description" => "Create menu and stat for week period ?",
 			"order" => "3.3.",			
 			"method" => "drop_array",
 			"default" => "0",
 			"array" => array(1=>"true",0=>"false"),
 			),				
 		"camm_hdr_timing" => array(
 			"friendly_name" => "4. CaMM SNMPTT Settings",
 			"method" => "spacer",
 			),
 		"camm_snmptt_delay_purge_day" => array(
 			"friendly_name" => "Data Purge Delay",
 			"description" => "Choose after what period data may be purged.",
 			"order" => "4.1.",			
 			"method" => "drop_array",
 			"default" => "7",
 			"array" => $camm_purge_delay,
 			),			
 		"camm_snmptt_min_row_all" => array(
 			"friendly_name" => "Min rows in tables",
 			"description" => "Specify the minimum number of rows.<br>No matter their retention period specified number of rows can not be removed.<br>Ie If you specify 1 million, then deleting the old records at least 1 million will remain,<br>even if they are older than the specified term. <br> Zerro for unlimited.",
 			"order" => "4.2.",			
 			"method" => "textbox",
 			"value" => "|arg1:camm_snmptt_min_row_all|",
 			"default" => "50000",
 			"max_length" => "7",
 			),
 		"camm_snmptt_max_row_per_device" => array(
 			"friendly_name" => "Max rows per device in day",
 			"description" => "Enter max count rows in tables per device per day. Zerro for unlimited.",
 			"order" => "4.3.",			
 			"method" => "textbox",
 			"value" => "|arg1:camm_snmptt_max_row_per_device|",
 			"default" => "1200",
 			"max_length" => "7",
 			),				
 		"camm_snmptt_tables" => array(
 			"friendly_name" => "What tables process",
 			"description" => "Choose table for processing",
 			"order" => "4.4.",			
 			"method" => "drop_array",
 			"default" => "3",
 			"array" => $camm_purge_tables,
 			),
 		"camm_snmptt_trap_tab_update" => array(
 			"friendly_name" => "Default Traps tab autoupdate interval",
 			"description" => "Choose how often Traps Tab grid will be AutoUpdated ?",
 			"order" => "4.5.",			
 			"method" => "drop_array",
 			"default" => "0",
 			"array" => $camm_grid_update,		
 			),
 		"camm_snmptt_unktrap_tab_update" => array(
 			"friendly_name" => "Default Unk. Traps tab autoupdate interval",
 			"description" => "Choose how often Unk. Traps Tab grid will be AutoUpdated ?",
 			"order" => "4.6.",			
 			"method" => "drop_array",
 			"default" => "0",
 			"array" => $camm_grid_update,		
 			),				
 		"camm_hdr_sys_purge" => array(
 			"friendly_name" => "5. CaMM SYSLOG Settings",
 			"method" => "spacer",
 			),
			
 		"camm_syslog_db_name" => array(
 			"friendly_name" => "Syslog db name",
 			"description" => "Enter syslog database name.",
 			"order" => "5.1.",			
 			"method" => "textbox",
 			"value" => "|arg1:camm_syslog_db_name|",
 			"default" => $database_default,
 			"max_length" => "50",
 			),
 		"camm_syslog_pretable_name" => array(
 			"friendly_name" => "Syslog incoming table",
 			"description" => "If You use separate table for incoming messages before processing rules - enter table name here <br> Or use [plugin_camm_syslog] for default (one table shema) <br> Table must be in [Syslog db name] database!",
 			"order" => "5.2.",			
 			"method" => "textbox",
 			"value" => "|arg1:camm_syslog_pretable_name|",
 			"default" => "plugin_camm_syslog",
 			"max_length" => "50",
 			),			
 		"camm_sys_delay_purge_day" => array(
 			"friendly_name" => "Data Purge Delay",
 			"description" => "Choose after what period data may be purged.",
 			"order" => "5.3.",			
 			"method" => "drop_array",
 			"default" => "7",
 			"array" => $camm_purge_delay,
 			),			
 		"camm_sys_min_row_all" => array(
 			"friendly_name" => "Min rows in table",
 			"description" => "Specify the minimum number of rows.<br>No matter their retention period specified number of rows can not be removed.<br>Ie If you specify 1 million, then deleting the old records at least 1 million will remain,<br>even if they are older than the specified term. <br> Zerro for unlimited.",
 			"order" => "5.4.",			
 			"method" => "textbox",
 			"value" => "|arg1:camm_sys_min_row_all|",
 			"default" => "50000",
 			"max_length" => "7",
 			),
 		"camm_sys_max_row_per_device" => array(
 			"friendly_name" => "Max rows per device in day",
 			"description" => "Enter max count rows in table per device per day. Zerro for unlimited. . Maximum = 5000000",
 			"order" => "5.5.",			
 			"method" => "numberfield",
			"max_value" => 5000000,
 			"value" => "|arg1:camm_sys_max_row_per_device|",
 			"default" => "1200",
 			"max_length" => "7",
 			),
 		"camm_sys_tab_update" => array(
 			"friendly_name" => "Default Sysalog tab autoupdate interval",
 			"description" => "Choose how often Syslog Tab grid will be AutoUpdated ?",
 			"order" => "5.6.",			
 			"method" => "drop_array",
 			"default" => "0",
 			"array" => $camm_grid_update,		
 			),			
 		"camm_hdr_startup" => array(
 			"friendly_name" => "6. CaMM Startup Settings",
 			"method" => "spacer",
 			),	
 		"camm_startup_tab" => array(
 			"friendly_name" => "Default start tab",
 			"description" => "Choose which tab will be opeb by default, at startup",
 			"order" => "6.1.",			
 			"method" => "drop_array",
 			"default" => "0",
 			"array" => array(0=>"Syslog",1=>"Traps",2=>"Unknown Traps",3=>"Rules",4=>"Stats"),		
 			),				
 		"camm_tree_update" => array(
 			"friendly_name" => "Tree update interval",
 			"description" => "Choose how often update Tree.",
 			"order" => "6.2.",			
 			"method" => "drop_array",
 			"default" => "300",
 			"array" => $camm_tree_update,			
 			),
 		"camm_num_rows" => array(
 			"friendly_name" => "Rows Per Page",
 			"description" => "The number of rows to display on a single page for Syslog messages, Traps and unknow Traps.",
 			"order" => "6.3.",			
 			"method" => "drop_array",
 			"default" => "50",
 			"array" => array("5"=>5,"10"=>10,"20"=>20,"50"=>50,"100"=>100,"200"=>200)		
 			),
 		"camm_tree_menu_width" => array(
 			"friendly_name" => "Startup tree menu width",
 			"description" => "Enter tree menu width in % of browser width (10, 20 etc). Maximum = 50",
 			"order" => "5.6.",			
 			"method" => "numberfield",
 			"value" => "|arg1:camm_tree_menu_width|",
 			"default" => "20",
			"max_value" => 50,
 			"max_length" => "3",
 			) 		
 			
 	);
	
	if ($camm_use_group_by_host == 0) {
		$settings["camm"]["camm_use_group_by_host"]["array"]["0"] = "not use (fast select record)";
	} else {
		$settings["camm"]["camm_use_group_by_host"]["array"]["1"] = "use grouping (default )";
	}
	
	
	
 
 }
 
 
 function camm_draw_navigation_text ($nav) {
 
   $nav["camm_devices.php:"] = array("title" => "camm", "mapping" => "index.php:", "url" => "camm_devices.php", "level" => "1");
   $nav["camm_view.php:"] = array("title" => "camm", "mapping" => "index.php:", "url" => "camm_view.php", "level" => "1");
   $nav["start.php:"] = array("title" => "CAMM (CActi Message Manager)", "mapping" => "index.php:", "url" => "start.php", "level" => "2");
   
    return $nav;
 }
 
 function camm_poller_bottom () {
 	global $config;
 	$command_string = read_config_option("path_php_binary");
 	$extra_args = "-q " . $config["base_path"] . "/plugins/camm/poller_camm.php";
 	exec_background($command_string, "$extra_args");
 }
 
 function camm_version () {
 	return array(	
 		'name'		=> 'CAMM',
 		'version' 	=> '1.6.7',
 		'longname'	=> 'CAMM (CActi Message Manager)',
 		'author'	=> 'Susanin',
 		'homepage'	=> 'http://forums.cacti.net/viewtopic.php?t=31374',
 		'url'		=> '',
 		'email'		=> 'gthe72@yandex.ru'
 	);
 }
 
 
 function camm_check_upgrade () {
 
 	// Let's only run this check if we are on a page that actually needs the data
 	$files = array('camm_view.php');
 	if (isset($_SERVER['PHP_SELF']) && !in_array(basename($_SERVER['PHP_SELF']), $files))
 		return;
 
 	$current = camm_version ();
 	$current = $current['version'];
 	$old = db_fetch_cell("SELECT `value` FROM `settings` where name = 'camm_version'");
 	if ($current != $old) {
 		/* re-register the hooks */
 		plugin_camm_install();
 	}
 }
 

 
 function camm_upgrade_from_snm_ptt () {
 global $database_default;
 
 
 	$result = db_fetch_assoc("show tables LIKE '%plugin_snmptt%';");
 
 	//Change cacti old table names
 	if (count($result) > 1) {
 		foreach($result as $index => $arr) {
 			foreach ($arr as $old_name) {
 				if ($old_name != "plugin_snmptt_syslog") {
 					if ($old_name == "plugin_snmptt") {
 						$new_name="plugin_camm_snmptt";
 					}elseif($old_name == "plugin_snmptt_unknown"){
 						$new_name="plugin_camm_snmptt_unk";
 					}elseif($old_name == "plugin_snmptt_statistics"){
 						$new_name="plugin_camm_snmptt_stat";
 					}elseif($old_name == "plugin_snmptt_alert"){
 						$new_name="plugin_camm_rule";
 					}else{
 						$new_name=str_replace("snmptt","camm",$old_name);
 					}
 					db_execute("ALTER TABLE `" . $old_name . "` RENAME TO `" . $new_name . "`;");
 				}
 			}
 		}
 	}
 	camm_raise_message3(array("device_descr" => "Upgrade from SNMPTT plugin" , "type" => "upgrade_db", "object"=> "update","cellpading" => false, "message" => "Change cacti old table names", "step_rezult" => "OK", "step_data" => "OK"));     
 
 	//Change syslog DB if used
 	$camm_use_syslog = read_config_option("snmptt_use_syslog");
 	if ($camm_use_syslog == '1') {
 		$camm_syslog_db_name = read_config_option("snmptt_syslog_db_name");
 		$camm_syslog_db_name = (strlen(trim($camm_syslog_db_name))>0 ? $camm_syslog_db_name : $database_default);
 		db_execute("ALTER TABLE `" . $camm_syslog_db_name . "`.`plugin_snmptt_syslog` RENAME TO `" . $camm_syslog_db_name . "`.`plugin_camm_syslog`;");
 		camm_raise_message3(array("device_descr" => "Upgrade from SNMPTT plugin" , "type" => "upgrade_db", "object"=> "update","cellpading" => false, "message" => "Change syslog DB if used", "step_rezult" => "OK", "step_data" => "OK"));     
 	}
 	
 
 	//Change settings name
 	$result = db_fetch_assoc("SELECT `name` FROM `settings` WHERE `name` like '%snmptt%';");
 
 	if (count($result) > 1) {
 		foreach($result as $index => $arr) {
 			foreach ($arr as $old_name) {
 				if ($old_name != "dimpb_use_snmptt_plugin") {
 					if ($old_name == "stats_snmptt_time") {
 						$new_name="camm_stats_time";
 					}elseif($old_name == "stats_snmptt_ruledel"){
 						$new_name="camm_stats_ruledel";
 					}elseif($old_name == "stats_snmptt"){
 						$new_name="camm_stats";
 					}elseif($old_name == "snmptt_delay_purge_day"){
 						$new_name="camm_snmptt_delay_purge_day";
 					}elseif($old_name == "snmptt_max_row_all"){
 						$new_name="camm_snmptt_min_row_all";
 					}elseif($old_name == "snmptt_max_row_per_device"){
 						$new_name="camm_snmptt_max_row_per_device";
 					}elseif($old_name == "snmptt_tables"){
 						$new_name="camm_snmptt_tables";						
 					}elseif($old_name == "snmptt_trap_tab_update"){
 						$new_name="camm_snmptt_trap_tab_update";	
 					}elseif($old_name == "snmptt_unktrap_tab_update"){
 						$new_name="camm_snmptt_unktrap_tab_update";	
 					}else{
 						$new_name=str_replace("snmptt","camm",$old_name);
 					}
 					$new_name=str_replace("snmptt","camm",$old_name);
 					db_execute("UPDATE `settings` SET `name` = '" . $new_name . "' WHERE `name` = '" . $old_name . "';");
 				}
 			}
 		}
 		
 		camm_raise_message3(array("device_descr" => "Upgrade from SNMPTT plugin" , "type" => "upgrade_db", "object"=> "update","cellpading" => false, "message" => "Change settings name", "step_rezult" => "OK", "step_data" => "OK"));     
 	}
 
 }
 
 
 function camm_setup_table () {
     global $config, $database_default;
     include_once($config["library_path"] . "/database.php");
 
     //Check - if this is a upgrade from snmptt plugin
 	$snm_ptt_db = db_fetch_cell("SELECT count(*) FROM `plugin_db_changes` where `plugin`='snmptt';");
 	$camm_db_change_count = db_fetch_cell("SELECT count(*) FROM `plugin_db_changes` where `plugin`='camm';");
 	$snm_ptt_db_real = db_fetch_assoc("show tables LIKE '%plugin_snmptt%';");
 	if (($snm_ptt_db > 0) && (count($snm_ptt_db_real) > 1) && ($camm_db_change_count == 0)){
 		camm_upgrade_from_snm_ptt();
 		db_execute("DELETE FROM  `plugin_db_changes` WHERE `plugin`='snmptt';");
 	}
 	
 	
 	$data = array();
     $data['columns'][] = array('name' => 'id', 'type' => 'int(11)', 'unsigned' => true, 'NULL' => false, 'auto_increment' => true);
     $data['columns'][] = array('name' => 'eventname', 'type' => 'varchar(50)', 'NULL' => true);
     $data['columns'][] = array('name' => 'eventid', 'type' => 'varchar(50)', 'NULL' => true);
     $data['columns'][] = array('name' => 'trapoid', 'type' => 'varchar(100)', 'NULL' => true);
 	$data['columns'][] = array('name' => 'enterprise', 'type' => 'varchar(100)', 'NULL' => true);
 	$data['columns'][] = array('name' => 'community', 'type' => 'varchar(20)', 'NULL' => true);
 	$data['columns'][] = array('name' => 'hostname', 'type' => 'varchar(250)', 'NULL' => true);
 	$data['columns'][] = array('name' => 'agentip', 'type' => 'varchar(16)', 'NULL' => true);
 	$data['columns'][] = array('name' => 'category', 'type' => 'varchar(20)', 'NULL' => true);
 	$data['columns'][] = array('name' => 'severity', 'type' => 'varchar(20)', 'NULL' => true);
 	$data['columns'][] = array('name' => 'uptime', 'type' => 'varchar(20)', 'NULL' => true);
 	$data['columns'][] = array('name' => 'traptime', 'type' => 'datetime', 'NULL' => true);
 	$data['columns'][] = array('name' => 'formatline', 'type' => 'text', 'NULL' => true);
	$data['columns'][] = array('name' => 'add', 'type' => 'varchar(50)', 'NULL' => true);
     $data['primary'] = 'id';
 	$data['keys'][] = array('name' => 'hostname', 'columns' => 'hostname');
     $data['type'] = 'MyISAM';
     $data['comment'] = 'camm data';
 
     api_plugin_db_table_create ('camm', 'plugin_camm_snmptt', $data);
 
     $data = array();
 	
 	$data['columns'][] = array('name' => 'stat_time', 'type' => 'datetime', 'NULL' => true);
     $data['columns'][] = array('name' => 'total_received', 'type' => 'bigint(20)', 'NULL' => true);
 	$data['columns'][] = array('name' => 'total_translated', 'type' => 'bigint(20)', 'NULL' => true);
 	$data['columns'][] = array('name' => 'total_ignored', 'type' => 'bigint(20)', 'NULL' => true);
 	$data['columns'][] = array('name' => 'total_unknown', 'type' => 'bigint(20)', 'NULL' => true);
     $data['type'] = 'MyISAM';
 	$data['keys'][] = array('name' => 'stat_time', 'columns' => 'stat_time');
     $data['comment'] = 'camm Statistics';
 
     api_plugin_db_table_create ('camm', 'plugin_camm_snmptt_stat', $data);
 	
 	$data = array();
 	
     $data['columns'][] = array('name' => 'id', 'type' => 'int(11)', 'unsigned' => true, 'NULL' => false, 'auto_increment' => true);
     $data['columns'][] = array('name' => 'trapoid', 'type' => 'varchar(100)', 'NULL' => true);
     $data['columns'][] = array('name' => 'enterprise', 'type' => 'varchar(100)', 'NULL' => true);
     $data['columns'][] = array('name' => 'community', 'type' => 'varchar(20)', 'NULL' => true);
 	$data['columns'][] = array('name' => 'hostname', 'type' => 'varchar(250)', 'NULL' => true);
 	$data['columns'][] = array('name' => 'agentip', 'type' => 'varchar(16)', 'NULL' => true);
 	$data['columns'][] = array('name' => 'uptime', 'type' => 'varchar(20)', 'NULL' => true);
 	$data['columns'][] = array('name' => 'traptime', 'type' => 'datetime', 'NULL' => true);
 	$data['columns'][] = array('name' => 'formatline', 'type' => 'text', 'NULL' => true);
     $data['primary'] = 'id';
 	$data['keys'][] = array('name' => 'id', 'columns' => 'id');
     $data['type'] = 'MyISAM';
     $data['comment'] = 'camm Unkn Traps';
 
     api_plugin_db_table_create ('camm', 'plugin_camm_snmptt_unk', $data);
 
 	
 	$data = array();
 	
    $data['columns'][] = array('name' => 'id', 'type' => 'int(10)', 'unsigned' => true, 'NULL' => false, 'auto_increment' => true);
    $data['columns'][] = array('name' => 'name', 'type' => 'varchar(255)', 'NULL' => false);
 	$data['columns'][] = array('name' => 'rule_type', 'type' => 'varchar(10)', 'NULL' => false, 'default' => 'camm');
 	$data['columns'][] = array('name' => 'rule_enable', 'type' => 'tinyint(1)', 'NULL' => false, 'default' => '1');
 	$data['columns'][] = array('name' => 'is_function', 'type' => 'tinyint(1)', 'NULL' => false, 'default' => '0');
 	$data['columns'][] = array('name' => 'is_email', 'type' => 'tinyint(1)', 'NULL' => false, 'default' => '0');	
 	$data['columns'][] = array('name' => 'is_mark', 'type' => 'tinyint(1)', 'NULL' => false, 'default' => '0');	
 	$data['columns'][] = array('name' => 'is_delete', 'type' => 'tinyint(1)', 'NULL' => false, 'default' => '0');	
 	$data['columns'][] = array('name' => 'function_name', 'type' => 'varchar(255)', 'NULL' => true);
 	$data['columns'][] = array('name' => 'email', 'type' => 'varchar(255)', 'NULL' => true);
 	$data['columns'][] = array('name' => 'email_message', 'type' => 'text');
 	$data['columns'][] = array('name' => 'marker', 'type' => 'tinyint(2)', 'NULL' => false, 'default' => '0');	
 	$data['columns'][] = array('name' => 'notes', 'type' => 'varchar(255)', 'NULL' => true);
 	$data['columns'][] = array('name' => 'json_filter', 'type' => 'text');
 	$data['columns'][] = array('name' => 'sql_filter', 'type' => 'text');
 	$data['columns'][] = array('name' => 'user_id', 'type' => 'int(10)', 'unsigned' => true, 'NULL' => false, 'default' => '0');
 	$data['columns'][] = array('name' => 'date', 'type' => 'datetime', 'NULL' => true);
 	$data['columns'][] = array('name' => 'count_triggered', 'type' => 'int(11)', 'unsigned' => true, 'NULL' => false, 'default' => '0');
     $data['primary'] = 'id';
 	$data['keys'][] = array('name' => 'id', 'columns' => 'id');
     $data['type'] = 'MyISAM';
     $data['comment'] = 'Traps Alert';
 
     api_plugin_db_table_create ('camm', 'plugin_camm_rule', $data);


 	$data = array();
 	
    $data['columns'][] = array('name' => 'device_type_name', 'type' => 'varchar(100)', 'NULL' => true);
	$data['columns'][] = array('name' => 'device_type_id', 'type' => 'mediumint(8)','unsigned' => true, 'NULL' => false, 'default' => '0');
    $data['columns'][] = array('name' => 'device_id', 'type' => 'int(10)', 'unsigned' => false, 'NULL' => false,'default' => '0');
	$data['columns'][] = array('name' => 'description', 'type' => 'varchar(150)', 'NULL' => true);
    $data['columns'][] = array('name' => 'hostname', 'type' => 'varchar(100)', 'NULL' => false,'default' => '');
 	$data['columns'][] = array('name' => 'agentip', 'type' => 'int(10)', 'unsigned' => true, 'NULL' => true);
	$data['columns'][] = array('name' => 'agentip_source', 'type' => 'varchar(16)', 'NULL' => false, 'default' => '0');
 	$data['columns'][] = array('name' => 'gr_f', 'type' => 'varchar(50)', 'NULL' => false);
	$data['columns'][] = array('name' => 'gr_v', 'type' => 'varchar(50)', 'NULL' => false);
	$data['columns'][] = array('name' => 'type', 'type' => 'varchar(10)',  'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'period', 'type' => 'varchar(4)',  'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'count', 'type' => 'int(10)', 'unsigned' => true, 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'dev_count', 'type' => 'int(10)', 'unsigned' => true, 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'typ_count', 'type' => 'int(10)', 'unsigned' => true, 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'online', 'type' => 'tinyint(1)', 'NULL' => false, 'default' => '0');
    $data['primary'] = 'hostname`,`gr_f`,`gr_v`,`type`,`period`,`agentip_source';
 	$data['keys'][] = array('name' => 'type', 'columns' => 'type');
 	$data['keys'][] = array('name' => 'period', 'columns' => 'period');
	$data['keys'][] = array('name' => 'device_type_id', 'columns' => 'device_type_id');
	$data['keys'][] = array('name' => 'device_id', 'columns' => 'device_id');
    $data['type'] = 'MEMORY';
    $data['comment'] = 'camm temp data for menu';
 
    api_plugin_db_table_create ('camm', 'plugin_camm_temp', $data);
	 
	 
 	$data = array();
 	
    $data['columns'][] = array('name' => 'id', 'type' => 'int(11)', 'unsigned' => true, 'NULL' => false, 'auto_increment' => true);
    $data['columns'][] = array('name' => 'device_type_name', 'type' => 'varchar(100)', 'NULL' => true);
	$data['columns'][] = array('name' => 'device_type_id', 'type' => 'mediumint(8)','unsigned' => false, 'NULL' => false, 'default' => '0');	
    $data['columns'][] = array('name' => 'device_id', 'type' => 'int(10)', 'unsigned' => true, 'NULL' => false,'default' => '0');
	$data['columns'][] = array('name' => 'description', 'type' => 'varchar(150)', 'NULL' => true);
    $data['columns'][] = array('name' => 'hostname', 'type' => 'varchar(100)', 'NULL' => false,'default' => '');
 	$data['columns'][] = array('name' => 'agentip', 'type' => 'int(10)', 'unsigned' => true, 'NULL' => true);
	$data['columns'][] = array('name' => 'agentip_source', 'type' => 'varchar(16)', 'NULL' => false, 'default' => '0');
 	$data['columns'][] = array('name' => 'gr_f', 'type' => 'varchar(50)', 'NULL' => false);
	$data['columns'][] = array('name' => 'gr_v', 'type' => 'varchar(50)', 'NULL' => false);
	$data['columns'][] = array('name' => 'type', 'type' => 'varchar(10)',  'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'period', 'type' => 'varchar(4)',  'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'count', 'type' => 'int(10)', 'unsigned' => true, 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'dev_count', 'type' => 'int(10)', 'unsigned' => true, 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'typ_count', 'type' => 'int(10)', 'unsigned' => true, 'NULL' => false);
	$data['columns'][] = array('name' => 'online', 'type' => 'tinyint(1)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => '_is_device', 'type' => 'tinyint(1)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => '_is_type', 'type' => 'tinyint(1)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => '_is_marker', 'type' => 'tinyint(1)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => '_parent', 'type' => 'int(11)', 'unsigned' => true, 'NULL' => false);
	$data['columns'][] = array('name' => '_is_leaf', 'type' => 'tinyint(1)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => '_lvl', 'type' => 'tinyint(1)', 'NULL' => false);
    $data['primary'] = 'id';
 	$data['keys'][] = array('name' => 'type', 'columns' => 'type');
 	$data['keys'][] = array('name' => 'period', 'columns' => 'period');	
 	$data['keys'][] = array('name' => 'hostname', 'columns' => 'hostname');
	$data['keys'][] = array('name' => 'gr_f', 'columns' => 'gr_f');
 	$data['keys'][] = array('name' => 'gr_v', 'columns' => 'gr_v');
    $data['type'] = 'MyISAM';
    $data['comment'] = 'camm menu tree';
 
     api_plugin_db_table_create ('camm', 'plugin_camm_tree2', $data);	 
	 

	$data = array(); 
	 
    $data['columns'][] = array('name' => 'id', 'type' => 'int(11)', 'unsigned' => true, 'NULL' => false, 'auto_increment' => true);
    $data['columns'][] = array('name' => 'host', 'type' => 'varchar(128)', 'NULL' => true);
	$data['columns'][] = array('name' => 'sourceip', 'type' => 'varchar(45)', 'NULL' => false);
    $data['columns'][] = array('name' => 'facility', 'type' => 'varchar(10)', 'NULL' => true);
	$data['columns'][] = array('name' => 'priority', 'type' => 'varchar(10)', 'NULL' => true);
	$data['columns'][] = array('name' => 'sys_date', 'type' => 'datetime', 'NULL' => true);
	$data['columns'][] = array('name' => 'message', 'type' => 'text');
	$data['columns'][] = array('name' => 'status', 'type' => 'tinyint(4)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'alert', 'type' => 'SMALLINT(3)', 'unsigned' => true,'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'add', 'type' => 'varchar(50)', 'NULL' => true);
    $data['primary'] = 'id';
 	$data['keys'][] = array('name' => 'facility', 'columns' => 'facility');
 	$data['keys'][] = array('name' => 'priority', 'columns' => 'priority');	
	$data['keys'][] = array('name' => 'sourceip', 'columns' => 'sourceip');
 	$data['keys'][] = array('name' => 'status', 'columns' => 'status');
	$data['keys'][] = array('name' => 'alert', 'columns' => 'alert');
    $data['type'] = 'MyISAM';
    $data['comment'] = 'camm plugin SYSLOG Data';
 
    api_plugin_db_table_create ('camm', 'plugin_camm_syslog', $data);	 

	$data = array(); 
	 
    $data['columns'][] = array('name' => 'id', 'type' => 'int(11)', 'unsigned' => true, 'NULL' => false, 'auto_increment' => true);
    $data['columns'][] = array('name' => 'host', 'type' => 'varchar(128)', 'NULL' => true);
	$data['columns'][] = array('name' => 'sourceip', 'type' => 'varchar(45)', 'NULL' => false);
    $data['columns'][] = array('name' => 'facility', 'type' => 'varchar(10)', 'NULL' => true);
	$data['columns'][] = array('name' => 'priority', 'type' => 'varchar(10)', 'NULL' => true);
	$data['columns'][] = array('name' => 'sys_date', 'type' => 'datetime', 'NULL' => true);
	$data['columns'][] = array('name' => 'message', 'type' => 'text');
	$data['columns'][] = array('name' => 'status', 'type' => 'tinyint(4)', 'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'alert', 'type' => 'SMALLINT(3)', 'unsigned' => true,'NULL' => false, 'default' => '0');
	$data['columns'][] = array('name' => 'add', 'type' => 'varchar(50)', 'NULL' => true);
    $data['primary'] = 'id';
 	$data['keys'][] = array('name' => 'facility', 'columns' => 'facility');
 	$data['keys'][] = array('name' => 'priority', 'columns' => 'priority');	
	$data['keys'][] = array('name' => 'sourceip', 'columns' => 'sourceip');
	$data['keys'][] = array('name' => 'sys_date', 'columns' => 'sys_date');
 	$data['keys'][] = array('name' => 'status', 'columns' => 'status');
	$data['keys'][] = array('name' => 'alert', 'columns' => 'alert');
    $data['type'] = 'MEMORY';
    $data['comment'] = 'camm plugin SYSLOG incoming Data';
 
    api_plugin_db_table_create ('camm', 'plugin_camm_syslog_incoming', $data);

	
	$data = array(); 
    $data['columns'][] = array('name' => 'krid', 'type' => 'int(11)', 'unsigned' => true, 'NULL' => false);
    $data['columns'][] = array('name' => 'rule_id', 'type' => 'int(11)', 'unsigned' => true, 'NULL' => false);
	$data['columns'][] = array('name' => 'ktype', 'type' => 'tinyint(1)', 'unsigned' => true, 'NULL' => false);
    $data['primary'] = 'rule_id`,`krid`,`ktype';
 	$data['keys'][] = array('name' => 'krid', 'columns' => 'krid');
 	$data['keys'][] = array('name' => 'rule_id', 'columns' => 'rule_id`,`ktype');	
    $data['type'] = 'MyISAM';
    $data['comment'] = 'camm plugin';
 
    api_plugin_db_table_create ('camm', 'plugin_camm_keys', $data);	
	
	
 	camm_update();
 
 }
 
 	
 function camm_update () {
 	global $config, $database_default;
 
 	include_once($config["library_path"] . "/database.php");
 	include_once($config["base_path"] . "/plugins/camm/lib/camm_functions.php");
 
 	// Set the new version
 	$new = camm_version();
 	$n_name = $new['longname'];
 	$new = $new['version'];
 	$old = db_fetch_cell("SELECT `value` FROM `settings` where name = 'camm_version'");
 	db_execute("REPLACE INTO settings (name, value) VALUES ('camm_version', '$new')");
 	if (trim($old) == "") {
 		$old = "0.0.01b";
 	}
 
 	
 	$result = db_fetch_assoc("SELECT `name` FROM `settings` where name like '%camm%' order by name");
 	foreach($result as $row) {
 		$result_new[] =$row['name'];
 	}
 	//delete block
 	if (in_array("stats_camm_tree", $result_new))
 		$sql[] = array("camm_execute_sql","Delete from [settings] unused parameter [stats_camm_tree]","DELETE FROM `settings` WHERE `name` = 'stats_camm_tree';");	
 	if (in_array("camm_sys_collection_timing", $result_new))
 		$sql[] = array("camm_execute_sql","Delete from [settings] unused parameter [camm_sys_collection_timing]","DELETE FROM `settings` WHERE `name` = 'camm_sys_collection_timing';");			
 	if (in_array("camm_stats_ruledel", $result_new))
 		$sql[] = array("camm_execute_sql","Delete from [settings] unused parameter [camm_stats_ruledel]","DELETE FROM `settings` WHERE `name` = 'camm_stats_ruledel';");			
 	if (in_array("camm_collection_timing", $result_new)) {
 		if (in_array("camm_autopurge_timing", $result_new)) {
 			$sql[] = array("camm_execute_sql","Delete unused parameter in  [settings] [camm_collection_timing]","DELETE FROM `settings` WHERE `name` = 'camm_collection_timing';");			
 		}else{
 			$sql[] = array("camm_execute_sql","Change parameter in  [settings] unused parameter [camm_collection_timing] to [camm_autopurge_timing]","UPDATE settings SET `name` = 'camm_autopurge_timing' WHERE `name` = 'camm_collection_timing';");					
 		}
 	}

 
 
 	//change block
 	if (in_array("snmptt_delay_purge_day", $result_new))
 		$sql[] = array("camm_execute_sql","Rename in [settings] parameter [snmptt_delay_purge_day] to [camm_snmptt_delay_purge_day]","UPDATE `settings`  SET `name`='camm_snmptt_delay_purge_day' WHERE `name` = 'snmptt_delay_purge_day';");			
 	if (in_array("snmptt_max_row_all", $result_new))
 		$sql[] = array("camm_execute_sql","Rename in [settings] parameter [snmptt_max_row_all] to [camm_snmptt_min_row_all]","UPDATE `settings`  SET `name`='camm_snmptt_min_row_all' WHERE `name` = 'snmptt_max_row_all';");
 	if (in_array("snmptt_max_row_per_device", $result_new))
 		$sql[] = array("camm_execute_sql","Rename in [settings] parameter [snmptt_max_row_per_device] to [camm_snmptt_max_row_per_device]","UPDATE `settings`  SET `name`='camm_snmptt_max_row_per_device' WHERE `name` = 'snmptt_max_row_per_device';");			
 	if (in_array("snmptt_tables", $result_new))
 		$sql[] = array("camm_execute_sql","Rename in [settings] parameter [snmptt_tables] to [camm_snmptt_tables]","UPDATE `settings`  SET `name`='camm_snmptt_tables' WHERE `name` = 'snmptt_tables';");			
 	if (in_array("snmptt_trap_tab_update", $result_new))
 		$sql[] = array("camm_execute_sql","Rename in [settings] parameter [snmptt_trap_tab_update] to [camm_snmptt_trap_tab_update]","UPDATE `settings`  SET `name`='camm_snmptt_trap_tab_update' WHERE `name` = 'snmptt_trap_tab_update';");			
 	if (in_array("snmptt_unktrap_tab_update", $result_new))
 		$sql[] = array("camm_execute_sql","Rename in [settings] parameter [snmptt_unktrap_tab_update] to [camm_snmptt_unktrap_tab_update]","UPDATE `settings`  SET `name`='camm_snmptt_unktrap_tab_update' WHERE `name` = 'snmptt_unktrap_tab_update';");
  	if (in_array("camm_sys_max_row_all", $result_new))
 		$sql[] = array("camm_execute_sql","Rename in [settings] parameter [camm_sys_max_row_all] to [camm_sys_min_row_all]","UPDATE `settings`  SET `name`='camm_sys_min_row_all' WHERE `name` = 'camm_sys_max_row_all';");
  	if (in_array("camm_snmptt_max_row_all", $result_new))
 		$sql[] = array("camm_execute_sql","Rename in [settings] parameter [camm_snmptt_max_row_all] to [camm_snmptt_min_row_all]","UPDATE `settings`  SET `name`='camm_snmptt_min_row_all' WHERE `name` = 'camm_snmptt_max_row_all';");

  		
 
 	//add block				
 	if (!in_array("camm_num_rows", $result_new))
 		$sql[] = array("camm_execute_sql","Insert into [settings] new parameter [camm_num_rows]","INSERT INTO settings VALUES ('camm_num_rows','50');");	
 	if (!in_array("camm_last_run_time", $result_new))		
 		$sql[] = array("camm_execute_sql","Insert into [settings] new parameter [camm_last_run_time]","INSERT INTO settings VALUES ('camm_last_run_time',0);");
 	if (!in_array("camm_autopurge_timing", $result_new))	
 		$sql[] = array("camm_execute_sql","Insert into [settings] new parameter [camm_autopurge_timing]","INSERT INTO settings VALUES ('camm_autopurge_timing','120');");
 	if (!in_array("camm_snmptt_delay_purge_day", $result_new))	
 		$sql[] = array("camm_execute_sql","Insert into [settings] new parameter [camm_snmptt_delay_purge_day]","INSERT INTO settings VALUES ('camm_snmptt_delay_purge_day','7');");
 	if (!in_array("camm_snmptt_min_row_all", $result_new))	
 		$sql[] = array("camm_execute_sql","Insert into [settings] new parameter [camm_snmptt_min_row_all]","INSERT INTO settings VALUES ('camm_snmptt_min_row_all','0');");
 	if (!in_array("camm_snmptt_max_row_per_device", $result_new))	
 		$sql[] = array("camm_execute_sql","Insert into [settings] new parameter [camm_snmptt_max_row_per_device]","INSERT INTO settings VALUES ('camm_snmptt_max_row_per_device','0');");
 	if (!in_array("camm_snmptt_tables", $result_new))
 		$sql[] = array("camm_execute_sql","Insert into [settings] new parameter [camm_snmptt_tables]","INSERT INTO settings VALUES ('camm_snmptt_tables','3');");	
 	if (!in_array("camm_startup_tab", $result_new))
 		$sql[] = array("camm_execute_sql","Insert into [settings] new parameter [camm_startup_tab]","INSERT INTO settings VALUES ('camm_startup_tab','0');");	
 	if (!in_array("camm_tree_update", $result_new))
 		$sql[] = array("camm_execute_sql","Insert into [settings] new parameter [camm_tree_update]","INSERT INTO settings VALUES ('camm_tree_update','300');");	
 	if (!in_array("camm_stats_time", $result_new))
 		$sql[] = array("camm_execute_sql","Insert into [settings] new parameter [camm_stats_time]","INSERT INTO settings VALUES ('camm_stats_time','Time:0');");	
 	if (!in_array("camm_stats_ruledel_snmptt", $result_new))
 		$sql[] = array("camm_execute_sql","Insert into [settings] new parameter [camm_stats_ruledel_snmptt]","INSERT INTO settings VALUES ('camm_stats_ruledel_snmptt','0');");	
 	if (!in_array("camm_stats_ruledel_syslog", $result_new))
 		$sql[] = array("camm_execute_sql","Insert into [settings] new parameter [camm_stats_ruledel_syslog]","INSERT INTO settings VALUES ('camm_stats_ruledel_syslog','0');");	
 	if (!in_array("camm_test_row_count", $result_new))
 		$sql[] = array("camm_execute_sql","Insert into [settings] new parameter [camm_test_row_count]","INSERT INTO settings VALUES ('camm_test_row_count','1000');");	
 	if (!in_array("camm_use_syslog", $result_new))
 		$sql[] = array("camm_execute_sql","Insert into [settings] new parameter [camm_use_syslog]","INSERT INTO settings VALUES ('camm_use_syslog','0');");	
 	if (!in_array("camm_sys_delay_purge_day", $result_new))
 		$sql[] = array("camm_execute_sql","Insert into [settings] new parameter [camm_sys_delay_purge_day]","INSERT INTO settings VALUES ('camm_sys_delay_purge_day','7');");	
 	if (!in_array("camm_sys_min_row_all", $result_new))
 		$sql[] = array("camm_execute_sql","Insert into [settings] new parameter [camm_sys_min_row_all]","INSERT INTO settings VALUES ('camm_sys_min_row_all','50000');");	
 	if (!in_array("camm_sys_max_row_per_device", $result_new))
 		$sql[] = array("camm_execute_sql","Insert into [settings] new parameter [camm_sys_max_row_per_device]","INSERT INTO settings VALUES ('camm_sys_max_row_per_device','1200');");	
 	if (!in_array("camm_snmptt_unktrap_tab_update", $result_new))
 		$sql[] = array("camm_execute_sql","Insert into [settings] new parameter [camm_snmptt_unktrap_tab_update]","INSERT INTO settings VALUES ('camm_snmptt_unktrap_tab_update','0');");	
 	if (!in_array("camm_snmptt_trap_tab_update", $result_new))
 		$sql[] = array("camm_execute_sql","Insert into [settings] new parameter [camm_snmptt_trap_tab_update]","INSERT INTO settings VALUES ('camm_snmptt_trap_tab_update','0');");	
 	if (!in_array("camm_sys_tab_update", $result_new))
 		$sql[] = array("camm_execute_sql","Insert into [settings] new parameter [camm_sys_tab_update]","INSERT INTO settings VALUES ('camm_sys_tab_update','0');");	
 	if (!in_array("camm_stats_snmptt_tree", $result_new))
 		$sql[] = array("camm_execute_sql","Insert into [settings] new parameter [camm_stats_snmptt_tree]","INSERT INTO settings VALUES ('camm_stats_snmptt_tree','TreecammTime:0');");	
 	if (!in_array("camm_stats_syslog_tree", $result_new))
 		$sql[] = array("camm_execute_sql","Insert into [settings] new parameter [camm_stats_syslog_tree]","INSERT INTO settings VALUES ('camm_stats_syslog_tree','TreesyslogTime:0');");	
 	if (!in_array("camm_syslog_db_name", $result_new)) {
 		$sql[] = array("camm_execute_sql","Insert into [settings] new parameter [camm_syslog_db_name]","INSERT INTO settings VALUES ('camm_syslog_db_name','" . $database_default . "');");	
 		$camm_syslog_db_name = $database_default;
 	}else{
 		$camm_syslog_db_name = read_config_option("camm_syslog_db_name");
 	}
 	if (!in_array("camm_show_all_records", $result_new))
 		$sql[] = array("camm_execute_sql","Insert into [settings] new parameter [camm_show_all_records]","INSERT INTO settings VALUES ('camm_show_all_records','1');");				
 	if (!in_array("camm_join_field", $result_new)) {
 		$sql[] = array("camm_execute_sql","Truncate table plugin_camm_tree2","TRUNCATE table `plugin_camm_tree2`;");				
		$sql[] = array("camm_execute_sql","Increment index plugin_camm_tree2","ALTER TABLE `plugin_camm_tree2` AUTO_INCREMENT = 100;");				
 		$sql[] = array("camm_execute_sql","Insert into [settings] new parameter [camm_join_field]","INSERT INTO settings VALUES ('camm_join_field','sourceip');");				
 	}
 	if (!in_array("camm_tab_image_size", $result_new))
 		$sql[] = array("camm_execute_sql","Insert into [settings] new parameter [camm_tab_image_size]","INSERT INTO settings VALUES ('camm_tab_image_size','0');");				
 	if (!in_array("camm_debug_mode", $result_new))
 		$sql[] = array("camm_execute_sql","Insert into [settings] new parameter [camm_debug_mode]","INSERT INTO settings VALUES ('camm_debug_mode','0');");				
 	if (!in_array("camm_syslog_pretable_name", $result_new))
 		$sql[] = array("camm_execute_sql","Insert into [settings] new parameter [camm_syslog_pretable_name]","INSERT INTO settings VALUES ('camm_syslog_pretable_name','plugin_camm_syslog');");				
 	if (!in_array("camm_period_hour", $result_new))
 		$sql[] = array("camm_execute_sql","Insert into [settings] new parameter [camm_period_hour]","INSERT INTO settings VALUES ('camm_period_hour','1');");				
 	if (!in_array("camm_period_day", $result_new))
 		$sql[] = array("camm_execute_sql","Insert into [settings] new parameter [camm_period_day]","INSERT INTO settings VALUES ('camm_period_day','1');");	
 	if (!in_array("camm_period_week", $result_new))
 		$sql[] = array("camm_execute_sql","Insert into [settings] new parameter [camm_period_week]","INSERT INTO settings VALUES ('camm_period_week','0');");	 
 	if (!in_array("camm_tree_menu_width", $result_new))
 		$sql[] = array("camm_execute_sql","Insert into [settings] new parameter [camm_tree_menu_width]","INSERT INTO settings VALUES ('camm_tree_menu_width','20');");	 
 	if (!in_array("camm_process_markers", $result_new))
 		$sql[] = array("camm_execute_sql","Insert into [settings] new parameter [camm_process_markers]","INSERT INTO settings VALUES ('camm_process_markers','0');");	     
 	if (!in_array("camm_action_order", $result_new))
 		$sql[] = array("camm_execute_sql","Insert into [settings] new parameter [camm_action_order]","INSERT INTO settings VALUES ('camm_action_order','1');");	      
 	if (!in_array("camm_rule_order", $result_new))
 		$sql[] = array("camm_execute_sql","Insert into [settings] new parameter [camm_rule_order]","INSERT INTO settings VALUES ('camm_rule_order','1');");	       		
 	if (!in_array("camm_email_title", $result_new))
 		$sql[] = array("camm_execute_sql","Insert into [settings] new parameter [camm_email_title]","INSERT INTO settings VALUES ('camm_email_title','An alert has been issued that requires your attention.');");	       		
 	if (!in_array("camm_use_fqdn", $result_new))
 		$sql[] = array("camm_execute_sql","Insert into [settings] new parameter [camm_use_fqdn]","INSERT INTO settings VALUES ('camm_use_fqdn','0');");	       		  
 	if (!in_array("camm_use_group_by_host", $result_new))
 		$sql[] = array("camm_execute_sql","Insert into [settings] new parameter [camm_use_group_by_host]","INSERT INTO settings VALUES ('camm_use_group_by_host','1');");	       		  
 	if (!in_array("camm_dependencies", $result_new))
 		$sql[] = array("camm_execute_sql","Insert into [settings] new parameter [camm_dependencies]","INSERT INTO settings VALUES ('camm_dependencies','false');");	       		    
 				
 	$found = false;
 	$result = db_fetch_assoc("SHOW INDEX FROM `host`;");
 	foreach($result as $row) {
 		if ($row['Column_name'] == 'hostname')
 			$found = true;
 	}
 	if (!$found) {
 		$sql[] = array("camm_add_index","host","hostname", "ALTER TABLE `host` ADD INDEX `hostname`(`hostname`);");
 	}
 
 	$found = false;
 	$result = db_fetch_assoc("SHOW INDEX FROM `plugin_camm_rule`;");
 	foreach($result as $row) {
 		if ($row['Key_name'] == 'unique')
 			$found = true;
 	}
 	if ($found) {
// 		$sql[] = array("camm_add_index","plugin_camm_rule","unique", "ALTER TABLE `plugin_camm_rule` ADD UNIQUE KEY `unique` USING BTREE (`is_function`,`is_email`,`is_mark`,`is_delete`,`function_name`(25),`email`(25),`marker`);");
		$sql[] = array("camm_delete_index","plugin_camm_rule","unique", "ALTER TABLE `plugin_camm_rule` DROP  KEY `unique` ;");
 	}	


	
	
	
	
 	$result = db_fetch_row("SHOW TABLE STATUS where Name = 'plugin_camm_rule';");
 
 	if ($result["Auto_increment"] < 100) {
 		$sql[] = array("camm_execute_sql","UPDATE plugin_camm_rule Auto_increment field","ALTER TABLE `plugin_camm_rule` AUTO_INCREMENT = 100;");
 	}
 	
 	$found = false;
	$found2 = false;
	$found3 = false;
 	$result = db_fetch_assoc("SHOW columns FROM `plugin_camm_snmptt`;");
 	foreach($result as $row) {
 		if ($row['Field'] == 'status')
 			$found = true;
 		if ($row['Field'] == 'alert')
 			$found2 = true;
 		if ($row['Field'] == 'hostname'){
 			if ($row['Type'] == 'varchar(250)') {
 			}else{
 				$sql[] = array("camm_modify_column","plugin_camm_snmptt","hostname", "ALTER TABLE `plugin_camm_snmptt` MODIFY COLUMN `hostname` VARCHAR(250) ;");
 			}
		}
 		if ($row['Field'] == 'add')
 			$found3 = true;		
 	}
 	if (!$found) {
 		$sql[] = array("camm_add_column","plugin_camm_snmptt","status", "ALTER TABLE `plugin_camm_snmptt` ADD column `status` tinyint(1) NOT NULL default '0';");
 	}	
  	if (!$found2) {
 		$sql[] = array("camm_add_column","plugin_camm_snmptt","alert", "ALTER TABLE `plugin_camm_snmptt` ADD column `alert` int(10) unsigned NOT NULL default '0';");
 	}
 	if (!$found3) {
 		$sql[] = array("camm_add_column","plugin_camm_snmptt","add", "ALTER TABLE `plugin_camm_snmptt` ADD column `add` varchar(50) default '' ;");
 	}	
 
 	$result = db_fetch_assoc("SHOW columns FROM `plugin_camm_snmptt_unk`;");
 	foreach($result as $row) {
 		if ($row['Field'] == 'hostname'){
 			if ($row['Type'] == 'varchar(250)') {
 			}else{
 				$sql[] = array("camm_modify_column","plugin_camm_snmptt_unk","hostname", "ALTER TABLE `plugin_camm_snmptt_unk` MODIFY COLUMN `hostname` VARCHAR(250) ;");
 			}
		};
 		if ($row['Field'] == 'formatline'){
 			if ($row['Type'] == 'varchar(255)') {
 				$sql[] = array("camm_modify_column","plugin_camm_snmptt_unk","formatline", "ALTER TABLE `plugin_camm_snmptt_unk` MODIFY COLUMN `formatline` text ;");			
 			}
		}		
 	}	

 	
 	$found = false;
 	$found1 = false;
 	$found2 = false;
 	$found3 = false;
 	$result = db_fetch_assoc("SHOW INDEX FROM `plugin_camm_snmptt_unk`;");
 	foreach($result as $row) {
 		if ($row['Key_name'] == 'traptime')
 			$found = true;
 		if ($row['Key_name'] == 'trapoid')
 			$found1 = true;
 		if ($row['Key_name'] == 'community')
 			$found2 = true;	
 		if ($row['Key_name'] == 'hostname')
 			$found3 = true;			
 	}
 	if (!$found) {
 		$sql[] = array("camm_add_index","plugin_camm_snmptt_unk","traptime", "ALTER TABLE `plugin_camm_snmptt_unk` ADD INDEX `traptime`(`traptime`);");
 	}	
 	if (!$found1) {
 		$sql[] = array("camm_add_index","plugin_camm_snmptt_unk","trapoid", "ALTER TABLE `plugin_camm_snmptt_unk` ADD INDEX `trapoid`(`trapoid`);");
 	}
 	if (!$found2) {
 		$sql[] = array("camm_add_index","plugin_camm_snmptt_unk","community", "ALTER TABLE `plugin_camm_snmptt_unk` ADD INDEX `community`(`community`);");
 	}
 	if (!$found3) {
 		$sql[] = array("camm_add_index","plugin_camm_snmptt_unk","hostname", "ALTER TABLE `plugin_camm_snmptt_unk` ADD INDEX `hostname` USING BTREE(`hostname`);");
 	}
 
 	
 	$found = false;
 	$found1 = false;
 	$found2 = false;
 	$found3 = false;
 	$found4 = false;
	$found5 = false;
	$found6 = false;
 	$result = db_fetch_assoc("SHOW INDEX FROM `plugin_camm_snmptt`;");
 	foreach($result as $row) {
 		if ($row['Key_name'] == 'traptime')
 			$found = true;
 		if ($row['Key_name'] == 'eventname')
 			$found1 = true;			
 		if ($row['Key_name'] == 'severity')
 			$found2 = true;
 		if ($row['Key_name'] == 'category')
 			$found3 = true;
 		if ($row['Key_name'] == 'status_date')
 			$found4 = true;	
 		if ($row['Key_name'] == 'hostname')
 			$found5 = true;
 		if ($row['Key_name'] == 'status'){
 			$found6 = true;
		}			
 	}
 	if (!$found) {
 		$sql[] = array("camm_add_index","plugin_camm_snmptt","traptime", "ALTER TABLE `plugin_camm_snmptt` ADD INDEX `traptime`(`traptime`);");
 	}		
 	if (!$found1) {
 		$sql[] = array("camm_add_index","plugin_camm_snmptt","eventname", "ALTER TABLE `plugin_camm_snmptt` ADD INDEX `eventname`(`eventname`);");
 	}	
 	if (!$found2) {
 		$sql[] = array("camm_add_index","plugin_camm_snmptt","severity", "ALTER TABLE `plugin_camm_snmptt` ADD INDEX `severity`(`severity`);");
 	}
 	if (!$found3) {
 		$sql[] = array("camm_add_index","plugin_camm_snmptt","category", "ALTER TABLE `plugin_camm_snmptt` ADD INDEX `category`(`category`);");
 	}	
 	if (!$found4) {
 		$sql[] = array("camm_add_index","plugin_camm_snmptt","status_date", "ALTER TABLE `plugin_camm_snmptt` ADD INDEX `status_date`(`status`,`traptime`);");
 	}	
 	if (!$found5) {
 		$sql[] = array("camm_add_index","plugin_camm_snmptt","hostname", "ALTER TABLE `plugin_camm_snmptt` ADD INDEX `hostname` USING BTREE(`hostname`);");
 	} 	
 	if (!$found6) {
 		$sql[] = array("camm_add_index","plugin_camm_snmptt","status", "ALTER TABLE `plugin_camm_snmptt` ADD INDEX `status`(`status`);");
 	} 	
 

 
 	$result = db_fetch_assoc("show tables;");
  
  	$tables_cacti = array();
  
  	if (count($result) > 1) {
  		foreach($result as $index => $arr) {
  			foreach ($arr as $t) {
  				$tables_cacti[] = $t;
  			}
  		}
  	}
 	
 	
 	$found = false;
 	$found1 = false;
 	$result = db_fetch_assoc("SHOW columns FROM `plugin_camm_rule`;");
 	foreach($result as $row) {
 		if ($row['Field'] == 'type')
 		$found = true;
 		if ($row['Field'] == 'mode')
 		$found1 = true;		
 	}
 	
 	if (($found && $found1) || (!in_array('plugin_camm_rule', $tables_cacti))) {
 		$sql[] = array("camm_execute_sql","Drop table plugin_camm_rule", "DROP TABLE `plugin_camm_rule`;");
 		$sql[] = array("camm_execute_sql","UPDATE plugin_camm_snmptt alert field", "UPDATE `plugin_camm_snmptt` SET `alert`='0'");
 
  		$sql[] = array("camm_create_table","plugin_camm_rule","CREATE TABLE `plugin_camm_rule` (
 		  `id` int(10) unsigned NOT NULL auto_increment,
		  `order` tinyint(4) UNSIGNED NOT NULL default '0',
 		  `name` varchar(255) NOT NULL,
 		  `is_function` tinyint(1) NOT NULL default '0',
 		  `is_email` tinyint(1) NOT NULL default '0',
 		  `is_mark` tinyint(1) NOT NULL default '0',
 		  `is_delete` tinyint(1) NOT NULL default '0',
 		  `function_name` varchar(255) default NULL,
 		  `email` varchar(255) default NULL,
 		  `email_message` text,
 		  `marker` tinyint(2) NOT NULL default '0',
 		  `notes` varchar(255) default NULL,
 		  `json_filter` text,
 		  `sql_filter` text,
 		  `user_id` int(10) unsigned NOT NULL default '0',
 		  `date` datetime default NULL,		  
 		  PRIMARY KEY  (`id`),
 		  UNIQUE KEY `unique` USING BTREE (`is_function`,`is_email`,`is_mark`,`is_delete`,`function_name`(25),`email`(25),`marker`)
 		) ENGINE=MyISAM AUTO_INCREMENT=100 COMMENT='camm rule';");
 		
 	}

	

  	$result = db_fetch_assoc("show tables from `" . $camm_syslog_db_name . "`;");
  
  	$tables = array();
  
  	if (count($result) > 1) {
  		foreach($result as $index => $arr) {
  			foreach ($arr as $t) {
  				$tables[] = $t;
  			}
  		}
  	}
 	

 	
 	
 	$found = false;
 	$found1 = false;
 	$found2 = false;
 	$found3 = false;
 	$found4 = false;
 	$found5 = false;
 	$found6 = false;
	$found7 = false;
 	$result = db_fetch_assoc("SHOW INDEX FROM `" . $camm_syslog_db_name . "`.`plugin_camm_syslog`;");
	if (sizeof($result) > 0) {
	 	foreach($result as $row) {
	 		if ($row['Key_name'] == 'facility')
	 			$found = true;
	 		if ($row['Key_name'] == 'priority')
	 			$found1 = true;			
	 		if ($row['Key_name'] == 'sourceip')
	 			$found2 = true;
	 		if ($row['Key_name'] == 'status')
	 			$found3 = true;				
	 		if ($row['Key_name'] == 'alert')
	 			$found4 = true;
	 		if ($row['Key_name'] == 'status_date')
	 			$found5 = true;	
	 		if ($row['Key_name'] == 'sys_date')
	 			$found6 = true;				
	 		if ($row['Key_name'] == 'hostname')
	 			$found7 = true;						
	 	}
	 	if (!$found) {
	 		$sql[] = array("camm_execute_sql","Add Index, Table -> `" . $camm_syslog_db_name . "`.`plugin_camm_syslog`, Index -> facility", "ALTER TABLE `" . $camm_syslog_db_name . "`.`plugin_camm_syslog` ADD INDEX `facility`(`facility`);");
	 	}		
	 	if (!$found1) {
	 		$sql[] = array("camm_execute_sql","Add Index, Table -> `" . $camm_syslog_db_name . "`.`plugin_camm_syslog`, Index -> priority","ALTER TABLE `" . $camm_syslog_db_name . "`.`plugin_camm_syslog` ADD INDEX `priority`(`priority`);");
	 	}	
	 	if (!$found2) {
	 		$sql[] = array("camm_execute_sql","Add Index, Table -> `" . $camm_syslog_db_name . "`.`plugin_camm_syslog`, Index -> sourceip","ALTER TABLE `" . $camm_syslog_db_name . "`.`plugin_camm_syslog` ADD INDEX `sourceip`(`sourceip`);");
	 	}
	 	if (!$found3) {
	 		$sql[] = array("camm_execute_sql","Add Index, Table -> `" . $camm_syslog_db_name . "`.`plugin_camm_syslog`, Index -> status", "ALTER TABLE `" . $camm_syslog_db_name . "`.`plugin_camm_syslog` ADD INDEX `status`(`status`);");
	 	}	
	 	if (!$found4) {
	 		$sql[] = array("camm_execute_sql","Add Index, Table -> `" . $camm_syslog_db_name . "`.`plugin_camm_syslog`, Index -> alert", "ALTER TABLE `" . $camm_syslog_db_name . "`.`plugin_camm_syslog` ADD INDEX `alert`(`alert`);");
	 	}	
	 	if (!$found5) {
	 		$sql[] = array("camm_execute_sql","Add Index, Table -> `" . $camm_syslog_db_name . "`.`plugin_camm_syslog`, Index -> status_date", "ALTER TABLE `" . $camm_syslog_db_name . "`.`plugin_camm_syslog` ADD INDEX `status_date`(`status`,`sys_date`);");
	 	}	
	 	if (!$found6) {
	 		$sql[] = array("camm_execute_sql","Add Index, Table -> `" . $camm_syslog_db_name . "`.`plugin_camm_syslog`, Index -> sys_date", "ALTER TABLE `" . $camm_syslog_db_name . "`.`plugin_camm_syslog` ADD INDEX `sys_date`(`sys_date`);");
	 	}
	 	if (!$found7) {
	 		$sql[] = array("camm_execute_sql","Add Index, Table -> `" . $camm_syslog_db_name . "`.`plugin_camm_syslog`, Index -> hostname", "ALTER TABLE `" . $camm_syslog_db_name . "`.`plugin_camm_syslog` ADD INDEX `hostname`(`host`);");
	 	}		
 	}
	

	   
 	$found = false;
 	$result = db_fetch_assoc("SHOW INDEX FROM `plugin_camm_tree2`;");
 	foreach($result as $row) {
 		if ($row['Key_name'] == 'unique')
 			$found = true;
 	}
 	if (!$found) {
 		$sql[] = array("camm_add_index","plugin_camm_tree2","unique", "ALTER TABLE `plugin_camm_tree2` ADD UNIQUE INDEX `unique` USING BTREE (`hostname`,`gr_f`,`gr_v`,`type`,`period`,`agentip_source`,`_is_device`,`_is_type`,`_is_marker`,`_lvl`);");
 	}
 	
	//добавим еще один файл для доступа админу
 	$result = db_fetch_cell("SELECT `file` FROM `plugin_realms` WHERE plugin = 'camm' AND `display`='Plugin -> camm: Manage';");
 	if ($result == 'camm_alert.php,camm_devices.php') {
 		$sql[] = array("camm_execute_sql","Update Plugin Realms", "UPDATE `plugin_realms` SET `file`='camm_db_admin.php' WHERE plugin = 'camm' AND `display`='Plugin -> camm: Manage'");
 	}
 	
 
 	$found = false;
 	$found1 = false;
 	$found2 = false;
	$found3 = false;
	$found4 = false;
	$found5 = false;
	$found6 = false;
	$found7 = false;
	$found8 = false;
	$found9 = false;
	$found10 = false;
 	$result = db_fetch_assoc("SHOW columns FROM `plugin_camm_rule`;");
 	foreach($result as $row) {
 		if ($row['Field'] == 'rule_type')
 		$found = true;
 		if ($row['Field'] == 'rule_enable')
 		$found1 = true;
 		if ($row['Field'] == 'count_triggered')
 		$found2 = true;	
 		if ($row['Field'] == 'email_mode')
 		$found3 = true;
 		if ($row['Field'] == 'marker_name')
 		$found4 = true;
 		if ($row['Field'] == 'order')
 		$found5 = true;			
 		if ($row['Field'] == 'inc_cacti_name')
 		$found6 = true;
 		if ($row['Field'] == 'sup_mode')
 		$found7 = true;
 		if ($row['Field'] == 'email_format')
 		$found8 = true;	
 		if ($row['Field'] == 'actual_triggered')
 		$found9 = true;			
 		if ($row['Field'] == 'marker')
 		$found10 = true;		
 	}
 	if (!$found) {
 		$sql[] = array("camm_add_column","plugin_camm_rule","rule_type", "ALTER TABLE `plugin_camm_rule` ADD column `rule_type` varchar(10) NOT NULL default 'camm' after `name`;");
 		$sql[] = array("camm_execute_sql","Truncate Table [plugin_camm_tree2]", "TRUNCATE table  `plugin_camm_tree2`;");
 	}
 	if (!$found1) {
 		$sql[] = array("camm_add_column","plugin_camm_rule","rule_enable", "ALTER TABLE `plugin_camm_rule` ADD column `rule_enable` tinyint(1) NOT NULL default '1' after `rule_type`;");
 	}
 	if (!$found2) {
 		$sql[] = array("camm_add_column","plugin_camm_rule","count_triggered", "ALTER TABLE `plugin_camm_rule` ADD column `count_triggered` int(11) unsigned NOT NULL default '0';");
 	}
 	if (!$found3) {
 		$sql[] = array("camm_add_column","plugin_camm_rule","email_mode", "ALTER TABLE `plugin_camm_rule` ADD column `email_mode` tinyint(1) unsigned NOT NULL default '1' after `email`;");
 	} 
 	if ($found4) {
 		$sql[] = array("camm_modify_column","plugin_camm_rule","marker_name", "ALTER TABLE `plugin_camm_rule` CHANGE column `marker_name` `marker_name_` varchar(30) NOT NULL default 'marker' ;");
	}
 	if (!$found5) {
 		$sql[] = array("camm_add_column","plugin_camm_rule","order", "ALTER TABLE `plugin_camm_rule` ADD column `order` tinyint(4) UNSIGNED NOT NULL default '0' after `name`;");
 	}
 	if (!$found6) {
 		$sql[] = array("camm_add_column","plugin_camm_rule","inc_cacti_name", "ALTER TABLE `plugin_camm_rule` ADD column `inc_cacti_name` tinyint(1) UNSIGNED NOT NULL default '1';");
 	}	
 	if (!$found7) {
 		$sql[] = array("camm_add_column","plugin_camm_rule","sup_mode", "ALTER TABLE `plugin_camm_rule` ADD column `sup_mode` tinyint(1) UNSIGNED NOT NULL default '1';");
 	}	
 	if (!$found8) {
 		$sql[] = array("camm_add_column","plugin_camm_rule","email_format", "ALTER TABLE `plugin_camm_rule` ADD column `email_format` tinyint(1) UNSIGNED NOT NULL default '1';");
 	}	
 	if (!$found9) {
 		$sql[] = array("camm_add_column","plugin_camm_rule","actual_triggered", "ALTER TABLE `plugin_camm_rule` ADD column `actual_triggered` int(11) unsigned NOT NULL default '0';");
 	}
 	if ($found10) {
 		$sql[] = array("camm_modify_column","plugin_camm_rule","marker", "ALTER TABLE `plugin_camm_rule` CHANGE column `marker` `marker__` TINYINT(2) NOT NULL DEFAULT 0 ;");
	}
	
 	$found = false;
	$found1 = false;
	$found2 = false;
 	$result = db_fetch_assoc("SHOW columns FROM `" . $camm_syslog_db_name . "`.`plugin_camm_syslog`;");
	if (sizeof($result) > 0) {
	 	foreach($result as $row) {
	 		if ($row['Field'] == 'alert'){
				$found = true;
			}
			if ($row['Field'] == 'alert'){
				if (strtolower(substr($row['Type'], 0 ,7)) == 'tinyint') {
					$found1 = true;
				}
			}
	 		if ($row['Field'] == 'add'){
				$found2 = true;
			}			
	 	}
	}
 
 	if (!$found) {
 		$sql[] = array("camm_execute_sql","Add Column, Table -> `" . $camm_syslog_db_name . "`.`plugin_camm_syslog`, Column -> alert", "ALTER TABLE `" . $camm_syslog_db_name . "`.`plugin_camm_syslog` ADD column `alert`  SMALLINT(3) unsigned NOT NULL default '0' ;");
 		$sql[] = array("camm_execute_sql","Add Index, Table -> `" . $camm_syslog_db_name . "`.`plugin_camm_syslog`, Index -> alert", "ALTER TABLE `" . $camm_syslog_db_name . "`.`plugin_camm_syslog` ADD INDEX `alert`(`alert`);");		
 	}

 	if ($found1) {
 		$sql[] = array("camm_execute_sql","Change Column, Table -> `" . $camm_syslog_db_name . "`.`plugin_camm_syslog`, Column -> alert", "ALTER TABLE `" . $camm_syslog_db_name . "`.`plugin_camm_syslog` MODIFY column `alert`  SMALLINT(3) unsigned NOT NULL default '0' ;");
 	}
 	if (!$found2) {
 		$sql[] = array("camm_execute_sql","Add Column, Table -> `" . $camm_syslog_db_name . "`.`plugin_camm_syslog`, Column -> add", "ALTER TABLE `" . $camm_syslog_db_name . "`.`plugin_camm_syslog` ADD column `add` varchar(50) default '' ;");
 	}
	
	
 	$found = false;
	$found1 = false;
	$found2 = false;
	$result = db_fetch_assoc("SHOW columns FROM `" . $camm_syslog_db_name . "`.`plugin_camm_syslog_incoming`;");
	if (sizeof($result) > 0) {
	 	foreach($result as $row) {
	 		if ($row['Field'] == 'alert'){
				$found = true;
			}
			if ($row['Field'] == 'alert'){
				if (strtolower(substr($row['Type'], 0 ,7)) == 'tinyint') {
					$found1 = true;
				}
			}
	 		if ($row['Field'] == 'add'){
				$found2 = true;
			}		
	 	}
	}
 
 	if (!$found) {
 		$sql[] = array("camm_execute_sql","Add Column, Table -> `" . $camm_syslog_db_name . "`.`plugin_camm_syslog_incoming`, Column -> alert", "ALTER TABLE `" . $camm_syslog_db_name . "`.`plugin_camm_syslog` ADD column `alert`  SMALLINT(3) unsigned NOT NULL default '0' ;");
 		$sql[] = array("camm_execute_sql","Add Index, Table -> `" . $camm_syslog_db_name . "`.`plugin_camm_syslog_incoming`, Index -> alert", "ALTER TABLE `" . $camm_syslog_db_name . "`.`plugin_camm_syslog` ADD INDEX `alert`(`alert`);");		
 	}

 	if ($found1) {
 		$sql[] = array("camm_execute_sql","Change Column, Table -> `" . $camm_syslog_db_name . "`.`plugin_camm_syslog_incoming`, Column -> alert", "ALTER TABLE `" . $camm_syslog_db_name . "`.`plugin_camm_syslog_incoming` MODIFY column `alert`  SMALLINT(3) unsigned NOT NULL default '0' ;");
 	}	
 	if (!$found2) {
 		$sql[] = array("camm_execute_sql","Add Column, Table -> `" . $camm_syslog_db_name . "`.`plugin_camm_syslog_incoming`, Column -> add", "ALTER TABLE `" . $camm_syslog_db_name . "`.`plugin_camm_syslog_incoming` ADD column `add` varchar(50) default '' ;");
 	}
	
 	//come clearing in setting table
 	$sql[] = array("camm_execute_sql","delete old settings and stats", "DELETE FROM settings where name in ('camm_stats_camm_tree','stats_camm_time','stats_camm_ruledel','stats_camm','camm_last_cammtreedb_time');");

 	//proper update plugin version and plugin name
 	$sql[] = array("camm_execute_sql","update version", "UPDATE `plugin_config` SET `version`='" . $new . "', `name`='" . $n_name . "' WHERE `directory`='camm';");
 		

	// удаление уже ненужной таблицы
	if (in_array('plugin_camm_tree', $tables_cacti)) {
		$sql[] = array("camm_execute_sql","Remove of unnecessary table plugin_camm_tree 1", "DROP TABLE `plugin_camm_tree`;");
		$sql[] = array("camm_execute_sql","Remove of unnecessary table plugin_camm_tree 2", "DELETE FROM `plugin_db_changes` where `table` = 'plugin_camm_tree';");
	}
 
	//** plugin_camm_tree2 MUST have Auto_increment => 100 !!!
	$result = db_fetch_row("SHOW TABLE STATUS where Name = 'plugin_camm_tree2';");
	if ($result["Auto_increment"] < 100) {
		$sql[] = array("camm_execute_sql","Increment index plugin_camm_tree2","ALTER TABLE `plugin_camm_tree2` AUTO_INCREMENT = 100;");				
	}	

	
	//теперь  проверим на множественные записи в таблице (неправильная установка/удаление плагина
	$result = db_fetch_cell("SELECT count(*) FROM plugin_config where `directory`='camm';");
	if ($result > 1) {
		$sql[] = array("camm_execute_sql","Delete rows from plugin_config","DELETE FROM `plugin_config` WHERE `directory`='camm' LIMIT " . ($result-1) . ";");				
	}

 	$found = false;
	$found2 = false;
	$found3 = false;
 	$result = db_fetch_assoc("SHOW full columns FROM `plugin_camm_tree2`;");
 	foreach($result as $row) {
 		if ($row['Field'] == '_is_marker') {
 			$found = true;
		};
 		if ($row['Field'] == 'device_type_id') {
 			$pos = strpos($row['Type'], 'unsigned');
			if ($pos !== false) {
				$found2 = true;
			};
		};
 		if ($row['Field'] == '_path') {
 			$found3 = true;
		};		
 	}
 	if (!$found) {
 		$sql[] = array("camm_add_column","plugin_camm_tree2","_is_marker", "ALTER TABLE `plugin_camm_tree2` ADD column `_is_marker` tinyint(1) NOT NULL default '0' after `_is_type`;");
		$sql[] = array("camm_delete_index","plugin_camm_tree2","unique", "ALTER TABLE `plugin_camm_tree2` DROP INDEX `unique`;");
		$sql[] = array("camm_add_index","plugin_camm_tree2","unique", "ALTER TABLE `plugin_camm_tree2` ADD UNIQUE INDEX `unique` USING BTREE (`hostname`,`gr_f`,`gr_v`,`type`,`period`,`agentip_source`,`_is_device`,`_is_type`,`_is_marker`,`_lvl`);");
 	}	
 	if ($found2) {
		$sql[] = array("camm_modify_column","plugin_camm_tree2","device_type_id", "ALTER TABLE `plugin_camm_tree2` MODIFY COLUMN `device_type_id` MEDIUMINT(8) NOT NULL DEFAULT 0 ;");
 	}
 	if (!$found3) {
		$sql[] = array("camm_execute_sql","Truncate table plugin_camm_tree2","TRUNCATE TABLE plugin_camm_tree2;");
		$sql[] = array("camm_add_column","plugin_camm_tree2","_path", "ALTER TABLE `plugin_camm_tree2` ADD column `_path` varchar(45) NOT NULL default '' ;");
 	}
	
 	$found = false;
 	$result = db_fetch_assoc("SHOW full columns FROM `plugin_camm_temp`;");
 	foreach($result as $row) {
 		if ($row['Field'] == 'device_type_id') {
 			$pos = strpos($row['Type'], 'unsigned');
			if ($pos !== false) {
				$found = true;
			};
		};		
 	}
 	if ($found) {
		$sql[] = array("camm_modify_column","plugin_camm_temp","device_type_id", "ALTER TABLE `plugin_camm_temp` MODIFY COLUMN `device_type_id` MEDIUMINT(8) NOT NULL DEFAULT 0 ;");
 	}	

 	$found = false;
	$found2 = false;
 	$result = db_fetch_assoc("SHOW INDEX FROM `plugin_camm_temp`;");
 	foreach($result as $row) {
 		if ($row['Key_name'] == 'device_type_id'){
 			$found = true;}
 		if ($row['Key_name'] == 'device_id'){
 			$found2 = true;}
 	}
 	if (!$found) {
 		$sql[] = array("camm_add_index","plugin_camm_temp","device_type_id", "ALTER TABLE `plugin_camm_temp` ADD INDEX `device_type_id` USING BTREE (`device_type_id`);");
 	}	
 	if (!$found2) {
 		$sql[] = array("camm_add_index","plugin_camm_temp","device_id", "ALTER TABLE `plugin_camm_temp` ADD INDEX `device_id` USING BTREE (`device_id`);");
 	}

 	$found = true;
 	$result = db_fetch_assoc("SHOW TABLE STATUS where Name = 'plugin_camm_temp';");
 	foreach($result as $row) {
 		if ($row['Engine'] == 'MEMORY'){
 			$found = false;
		}
 	}
 	if (!$found) {
 		$sql[] = array("camm_execute_sql","Convert plugin_camm_temp to MEMORY", "ALTER TABLE `plugin_camm_temp` ENGINE = MEMORY;");
 	}
	
	
	//ALTER TABLE `cacti`.`plugin_camm_tree2` MODIFY COLUMN `typ_count` INT(10) UNSIGNED NOT NULL DEFAULT 0;

	
	
 	if (!empty($sql)) {
 		for ($a = 0; $a < count($sql); $a++) {
 			$step_sql = $sql[$a];
 			$rezult = "";
 			switch ($step_sql[0]) {
 				case 'camm_execute_sql':
 					$rezult = camm_execute_sql ($step_sql[1], $step_sql[2]);
 					break;
 				case 'camm_create_table':
 					$rezult = camm_create_table ($step_sql[1], $step_sql[2]);
 					break;
 				case 'camm_add_column':
 					$rezult = camm_add_column ($step_sql[1], $step_sql[2],$step_sql[3]);
 					break;				
 				case 'camm_modify_column':
 					$rezult = camm_modify_column ($step_sql[1], $step_sql[2],$step_sql[3]);
 					break;
 				case 'camm_delete_column':
 					$rezult = camm_delete_column ($step_sql[1], $step_sql[2],$step_sql[3]);
 					break;
 				case 'camm_add_index':
 					$rezult = camm_add_index ($step_sql[1], $step_sql[2],$step_sql[3]);
 					break;
 				case 'camm_delete_index':
 					$rezult = camm_delete_index ($step_sql[1], $step_sql[2],$step_sql[3]);
 					break;
 			}
 			camm_raise_message3(array("device_descr" => "Upgrade to [" . $new . "]" , "type" => "update_db", "object"=> "update","cellpading" => false, "message" => $rezult["message"], "step_rezult" => $rezult["step_rezult"], "step_data" => $rezult["step_data"]));     
 		}
 	}
 
 
 	
 }
 
 
 function camm_execute_sql($message, $syntax) {
 	$result = db_execute($syntax);
 	$return_rezult = array();
 	
 	if ($result) {
 		$return_rezult["message"] =  "SUCCESS: Execute SQL,   $message";
 		$return_rezult["step_rezult"] = "OK";
 	}else{
 		$return_rezult["message"] =  "ERROR: Execute SQL,   $message";
 		$return_rezult["step_rezult"] = "Error";
 	}
 	$return_rezult["step_data"] = $return_rezult["step_rezult"] ;
 	return $return_rezult;
 }
 
 function camm_create_table($table, $syntax) {
 	$tables = db_fetch_assoc("SHOW TABLES LIKE '$table'");
 	$return_rezult = array();
 
 	if (!sizeof($tables)) {
 		$result = db_execute($syntax);
 		if ($result) {
 			$return_rezult["message"] =  "SUCCESS: Create Table,  Table -> $table";
 			$return_rezult["step_rezult"] = "OK";
 		}else{
 			$return_rezult["message"] =  "ERROR: Create Table,  Table -> $table";
 			$return_rezult["step_rezult"] = "Error";
 		}
 		$return_rezult["step_data"] = $return_rezult["step_rezult"] ;
 	}else{
 		$return_rezult["message"] =  "SUCCESS: Create Table,  Table -> $table";
 		$return_rezult["step_rezult"] = "OK";
 		$return_rezult["step_data"] = "Already Exists";
 	}
 	return $return_rezult;
 }
 
 function camm_add_column($table, $column, $syntax) {
 	$return_rezult = array();
 	$columns = db_fetch_assoc("SHOW COLUMNS FROM $table LIKE '$column'");
 
 	if (sizeof($columns)) {
 		$return_rezult["message"] = "SUCCESS: Add Column,    Table -> $table, Column -> $column";
 		$return_rezult["step_rezult"] = "OK";
 		$return_rezult["step_data"] = "Already Exists";
 	}else{
 		$result = db_execute($syntax);
 
 		if ($result) {
 			$return_rezult["message"] ="SUCCESS: Add Column,    Table -> $table, Column -> $column";
 			$return_rezult["step_rezult"] = "OK";
 		}else{
 			$return_rezult["message"] ="ERROR: Add Column,    Table -> $table, Column -> $column";
 			$return_rezult["step_rezult"] = "Error";
 		}
 		$return_rezult["step_data"] = $return_rezult["step_rezult"] ;
 	}
 	return $return_rezult;
 }
 
 function camm_add_index($table, $index, $syntax) {
 	$tables = db_fetch_assoc("SHOW TABLES LIKE '$table'");
 	$return_rezult = array();
 
 	if (sizeof($tables)) {
 		$indexes = db_fetch_assoc("SHOW INDEXES FROM $table");
 
 		$index_exists = FALSE;
 		if (sizeof($indexes)) {
 			foreach($indexes as $index_array) {
 				if ($index == $index_array["Key_name"]) {
 					$index_exists = TRUE;
 					break;
 				}
 			}
 		}
 
 		if ($index_exists) {
 			$return_rezult["message"] =  "SUCCESS: Add Index,     Table -> $table, Index -> $index";
 			$return_rezult["step_rezult"] = "OK";
 			$return_rezult["step_data"] = "Already Exists";
 		}else{
 			$result = db_execute($syntax);
 
 			if ($result) {
 				$return_rezult["message"] =  "SUCCESS: Add Index,     Table -> $table, Index -> $index";
 				$return_rezult["step_rezult"] = "OK";
 			}else{
 				$return_rezult["message"] =  "ERROR: Add Index,     Table -> $table, Index -> $index";
 				$return_rezult["step_rezult"] = "Error";
 			}
 			$return_rezult["step_data"] = $return_rezult["step_rezult"] ;
 		}
 	}else{
 		$return_rezult["message"] ="ERROR: Add Index,     Table -> $table, Index -> $index";
 		$return_rezult["step_rezult"] = "Error";
 		$return_rezult["step_data"] = "Table Does NOT Exist";
 	}
 	return $return_rezult;
 }
 
 function camm_modify_column($table, $column, $syntax) {
 	$tables = db_fetch_assoc("SHOW TABLES LIKE '$table'");
 	$return_rezult = array();
 
 	if (sizeof($tables)) {
 		$columns = db_fetch_assoc("SHOW COLUMNS FROM $table LIKE '$column'");
 
 		if (sizeof($columns)) {
 			$result = db_execute($syntax);
 
 			if ($result) {
 				$return_rezult["message"] =  "SUCCESS: Modify Column, Table -> $table, Column -> $column";
 				$return_rezult["step_rezult"] = "OK";
 			}else{
 				$return_rezult["message"] =  "ERROR: Modify Column, Table -> $table, Column -> $column";
 				$return_rezult["step_rezult"] = "Error";
 			}
 			$return_rezult["step_data"] = $return_rezult["step_rezult"] ;
 		}else{
 			$return_rezult["message"] =  "ERROR: Modify Column, Table -> $table, Column -> $column";
 			$return_rezult["step_rezult"] = "Error";
 			$return_rezult["step_data"] = "Column Does NOT Exist";
 		}
 	}else{
 		$return_rezult["message"] =  "ERROR: Modify Column, Table -> $table, Column -> $column";
 		$return_rezult["step_rezult"] = "Error";
 		$return_rezult["step_data"] = "Table Does NOT Exist";
 	}
 	return $return_rezult;
 }
 
 function camm_delete_column($table, $column, $syntax) {
 	$tables = db_fetch_assoc("SHOW TABLES LIKE '$table'");
 	$return_rezult = array();
 
 	if (sizeof($tables)) {
 		$columns = db_fetch_assoc("SHOW COLUMNS FROM $table LIKE '$column'");
 
 		if (sizeof($columns)) {
 			$result = db_execute($syntax);
 
 			if ($result) {
 				$return_rezult["message"] =  "SUCCESS: Delete Column, Table -> $table, Column -> $column";
 				$return_rezult["step_rezult"] = "OK";
 			}else{
 				$return_rezult["message"] =  "ERROR: Delete Column, Table -> $table, Column -> $column";
 				$return_rezult["step_rezult"] = "Error";
 			}
 			$return_rezult["step_data"] = $return_rezult["step_rezult"] ;
 		}else{
 			$return_rezult["message"] =  "SUCCESS: Delete Column, Table -> $table, Column -> $column";
 			$return_rezult["step_rezult"] = "Error";
 			$return_rezult["step_data"] = "Column Does NOT Exist";			
 		}
 	}else{
 		$return_rezult["message"] =  "SUCCESS: Delete Column, Table -> $table, Column -> $column";
 		$return_rezult["step_rezult"] = "Error";
 		$return_rezult["step_data"] = "Table Does NOT Exist";
 	}
 	return $return_rezult;
 }
 
 function camm_delete_index($table, $index, $syntax) {
 	$tables = db_fetch_assoc("SHOW TABLES LIKE '$table'");
 	$return_rezult = array();
 
 	if (sizeof($tables)) {
 		$indexes = db_fetch_assoc("SHOW INDEXES FROM $table");
 
 		$index_exists = FALSE;
 		if (sizeof($indexes)) {
 			foreach($indexes as $index_array) {
 				if ($index == $index_array["Key_name"]) {
 					$index_exists = TRUE;
 					break;
 				}
 			}
 		}
 
 		if (!$index_exists) {
 			$return_rezult["message"] =  "SUCCESS: Delete Index,     Table -> $table, Index -> $index";
 			$return_rezult["step_rezult"] = "OK";
 			$return_rezult["step_data"] = "Index Does NOT Exist!";
 		}else{
 			$result = db_execute($syntax);
 
 			if ($result) {
 				$return_rezult["message"] =  "SUCCESS: Delete Index,     Table -> $table, Index -> $index";
 				$return_rezult["step_rezult"] = "OK";
 			}else{
 				$return_rezult["message"] =  "ERROR: Delete Index,     Table -> $table, Index -> $index";
 				$return_rezult["step_rezult"] = "Error";
 			}
 			$return_rezult["step_data"] = $return_rezult["step_rezult"] ;
 		}
 	}else{
 		$return_rezult["message"] ="ERROR: Delete Index,     Table -> $table, Index -> $index";
 		$return_rezult["step_rezult"] = "Error";
 		$return_rezult["step_data"] = "Table Does NOT Exist";
 	}
 	return $return_rezult;
 }
 	
 ?>
