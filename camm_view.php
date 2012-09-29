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
 
 global $colors, $config;
 
$show_console_tab = true;
$show_graph_tab = true;

 $oper_mode = api_plugin_hook_function('top_header', OPER_MODE_NATIVE);
 if ($oper_mode == OPER_MODE_RESKIN) {
 	return;
 }
 
 /* Alot of this code was taken from the top_graph_header.php */
 
 if (read_config_option("auth_method") != 0) {

	/* find out if we should show the "console" tab or not, based on this user's permissions */
	if (sizeof(db_fetch_assoc("select realm_id from user_auth_realm where realm_id=8 and user_id=" . $_SESSION["sess_user_id"])) == 0) {
		$show_console_tab = false;
	}
	
	/* find out if we should show the "graph" tab or not, based on this user's permissions */
	if (sizeof(db_fetch_assoc("select realm_id from user_auth_realm where realm_id=7 and user_id=" . $_SESSION["sess_user_id"])) == 0) {
		$show_graph_tab = false;
	}	
}
 
 $page_title = api_plugin_hook_function('page_title', 'Cacti');
 
 ?>
 
 <html>
 <head>
 
 	
 	<title><?php echo $page_title; ?></title>
 	<link href="<?php echo $config['url_path']; ?>include/main.css" rel="stylesheet">
 	<link href="<?php echo $config['url_path']; ?>images/favicon.ico" rel="shortcut icon"/>
 
 	<?php
 	// development version
 	if("192.168.16.32" === $_SERVER["SERVER_NAME"]) {
 	    $lifeTime = 0;
 	}
 	// production version
 	else {
 	    $lifeTime = 3600 * 24; // one day
 	}
 
 	$expires = gmdate("D, d M Y H:i:s", time() + $lifeTime) . " GMT";
 	echo "<META HTTP-EQUIV=EXPIRES CONTENT='$expires'>";
 	echo "<META HTTP-EQUIV=Cache-Control max-age=$lifeTime pre-check=$lifeTime must-revalidate private>";
 	// eof
 	?>
 
 
 	<!-- Ext CSS and Libs -->
 	<link rel="stylesheet" type="text/css"	href="<?php echo $config['url_path']; ?>plugins/camm/css/ext-all.css" >
 	<link rel="stylesheet" type="text/css"	href="<?php echo $config['url_path']; ?>plugins/camm/css/xtheme-default.css">
 	<link rel="stylesheet" type="text/css"	href="<?php echo $config['url_path']; ?>plugins/camm/css/main.css" >
 	
 
 	<script type="text/javascript" src="<?php echo $config['url_path']; ?>plugins/camm/js/ext-base.js"></script>
	<!-- <script type="text/javascript" src="<?php echo $config['url_path']; ?>plugins/camm/js/jquery.js"></script>--> <!-- flot-->	
 	<script type="text/javascript" src="<?php echo $config['url_path']; ?>plugins/camm/js/ext-all.js"></script>
 		 
 	<!-- Custom CSS and Libs -->
	<!--[if IE]><script type="text/javascript" src="<?php echo $config['url_path']; ?>plugins/camm/js/excanvas.compiled.js"></script><![endif]-->
 	
 	<script type="text/javascript" src="<?php echo $config['url_path']; ?>plugins/camm/js/cacti.plugin.camm-min.js"></script>
 		
 </head>
 
 <?php if ($oper_mode == OPER_MODE_NATIVE) {?>
 <body leftmargin="0" topmargin="0" marginwidth="0" marginheight="0" <?php print api_plugin_hook_function("body_style", "");?>>
 <?php }else{?>
 <body leftmargin="15" topmargin="15" marginwidth="15" marginheight="15" <?php print api_plugin_hook_function("body_style", "");?>>
 <?php }?>
 
 	<div id="cacti_north" >
 
 		<table width="100%" cellspacing="0" cellpadding="0">
 			<tr height="1" bgcolor="#a9a9a9">
 				<td valign="bottom" colspan="3" nowrap>
 					<table width="100%" cellspacing="0" cellpadding="0">
 						<tr style="background: transparent url('<?php echo $config['url_path']; ?>images/cacti_backdrop.gif') no-repeat center right;">
 							<td id="tabs" valign="bottom">
								<?php if ($show_console_tab == true) {?><a href="<?php echo $config['url_path']; ?>index.php">     <img src="<?php echo $config['url_path']; ?>images/tab_console.gif" alt="Console" align="absmiddle" border="0"></a><?php };
								if ($show_graph_tab == true)   {?><a href="<?php echo $config['url_path']; ?>graph_view.php"><img src="<?php echo $config['url_path']; ?>images/tab_graphs.gif" alt="Graphs" align="absmiddle" border="0"></a><?php };
 								api_plugin_hook('top_header_tabs');?>
							</td>
 						</tr>
 					</table>
 				</td>
 			</tr>
 			<tr height="2" bgcolor="#183c8f">
 				<td colspan="3">
 					<img src="<?php echo $config['url_path']; ?>images/transparent_line.gif" height="2" border="0"><br>
 				</td>
 			</tr>
 			<tr height="5" bgcolor="#e9e9e9">
 				<td colspan="3">
 					<table width="100%">
 						<tr>
 							<td>
 								<?php draw_navigation_text();?>
 							</td>
 							<td align="right">
 								<?php if (read_config_option("auth_method") != 0) { ?>
 								Logged in as <strong><?php print db_fetch_cell("select username from user_auth where id=" . $_SESSION["sess_user_id"]);?></strong> (<a href="<?php echo $config['url_path']; ?>logout.php">Logout</a>)&nbsp;
 								<?php } ?>
 							</td>
 						</tr>
 					</table>
 				</td>
 			</tr>
 			<tr>
 				<td colspan="3" height="8" style="background-image: url(<?php echo $config['url_path']; ?>images/shadow.gif); background-repeat: repeat-x;" bgcolor="#ffffff">
 
 				</td>
 			</tr>
 		</table>		
 	</div>
 	<div id="cacti_south" >
 		Reserved for future use
 	</div>
 	
 			<div id="loading-mask" style="width:100%;height:100%;background:#fff;position:absolute;z-index:100;left:0;top:0;">
 		        <div class="loading-item" style="width:100%;height:50%;background:#fff;position:relative;z-index:101;left:0;top:43.2%;">
 		            <div style="left:40%;width:20%;border: 1px solid #99bbe8;position:relative;">
 		                <br />
 		                <center>
 		                    <img src="<?php echo $config['url_path']; ?>plugins/camm/images/large-loading.gif" style="margin-right:12px;" align="absmiddle"/><span id="loading-text" class="loading-item">Loading Cacti camm plugin...</span>
 		                </center>
 		                <br />
 		            </div>
 		        </div>
 		    </div>
 		    
 </body>
 </html>
 
