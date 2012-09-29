CREATE TABLE  `plugin_camm_syslog` (
   `id` int(11) unsigned NOT NULL auto_increment,
   `host` varchar(128) default NULL,
   `sourceip` varchar(45) NOT NULL,
   `facility` varchar(10) default NULL,
   `priority` varchar(10) default NULL,
   `sys_date` datetime default NULL,
   `message` text,
   `status` tinyint(4) NOT NULL default '0',
   `alert` tinyint(3) NOT NULL default '0',
   PRIMARY KEY  (`id`),
   KEY `facility` (`facility`),
   KEY `priority` (`priority`),
   KEY `sourceip` (`sourceip`),
   KEY `status` (`status`),
   KEY `status_date` (`status`,`sys_date`),
   KEY `sys_date` (`sys_date`),
   KEY `alert` (`alert`)
 ) ENGINE=MyISAM AUTO_INCREMENT=100 DEFAULT CHARSET=latin1;
 
