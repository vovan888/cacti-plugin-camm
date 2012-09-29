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
     Version ........ 1.2.2rc
 
 *******************************************************************************/
 
 /* register this users functions */
 if (!isset($camm_users_functions)) { $camm_users_functions = array(); }
 array_push($camm_users_functions, "camm_user_test1", "camm_user_test", "camm_user_test3");
 
 
 function camm_user_test1 ($alerted_traps, $alert) {
 
 	if (sizeof($alerted_traps) > 0) {
 		foreach ($alerted_traps as $alerted_trap) {
 			cacti_log("user_test1 - Got trap with id=" . $alerted_trap["id"], false, "camm");
 		}
 	}
 	
 };
 
 function camm_user_test2 ($alerted_traps, $alert) {
 
 	if (sizeof($alerted_traps) > 0) {
 		foreach ($alerted_traps as $alerted_trap) {
 			cacti_log("user_test2 - Got trap with id=" . $alerted_trap["id"], false, "camm");
 		}
 	}
 
 };
 function camm_user_test3 ($alerted_traps, $alert) {
 
 	if (sizeof($alerted_traps) > 0) {
 		foreach ($alerted_traps as $alerted_trap) {
 			cacti_log("user_test3 - Got trap with id=" . $alerted_trap["id"], false, "camm");
 		}
 	}
 
 }; 
 ?>
