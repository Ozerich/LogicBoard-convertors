<?php

function InstallLB($host, $login, $password, $db_name, $table_prefix, $dle_host, $dle_login, $dle_password, $dle_tbname, $dle_prefix, $options)
{

    $handle = mysql_connect($host, $login, $password, true) or die("Error to connect to LB SQL host");
    mysql_select_db($db_name, $handle) or die("Error to select LB database");
    mysql_query("SET NAMES cp1251", $handle);


$install_db[] = "DROP TABLE IF EXISTS ".$table_prefix."groups";
$install_db[] = "CREATE TABLE ".$table_prefix."groups (
  `g_id` smallint(5) NOT NULL auto_increment,
  `g_title` varchar(40) NOT NULL default '',
  `g_prefix_st` varchar(250) NOT NULL default '',
  `g_prefix_end` varchar(250) NOT NULL default '',
  `g_icon` varchar(255) NOT NULL default '',
  `g_access_cc` tinyint(1) NOT NULL default '0',
  `g_supermoders` tinyint(1) NOT NULL default '0',
  `g_access` text,
  `g_show_online` tinyint(1) NOT NULL default '1',
  `g_new_topic` tinyint(1) NOT NULL default '1',
  `g_reply_topic` tinyint(1) NOT NULL default '1',
  `g_reply_close` tinyint(1) NOT NULL default '0',
  `g_warning` tinyint(1) NOT NULL default '0',
  `g_show_hiden` tinyint(1) NOT NULL default '0',
  `g_show_close_f` tinyint(1) NOT NULL default '0',
  `g_hide_text` tinyint(1) NOT NULL default '0',
  `g_signature` tinyint(1) NOT NULL default '1',
  `g_search` tinyint(1) NOT NULL default '1',
  `g_status` mediumint(8) NOT NULL default '50',
  `g_link_forum` tinyint(1) NOT NULL default '1',
  PRIMARY KEY  (`g_id`),
  KEY `g_title` (`g_title`),
  KEY `g_supermoders` (`g_supermoders`)
) ENGINE=MyISAM DEFAULT CHARSET=cp1251";

$install_db[] = "DROP TABLE IF EXISTS ".$table_prefix."members_ranks";
$install_db[] = "CREATE TABLE ".$table_prefix."members_ranks (
  `id` mediumint(8) NOT NULL auto_increment,
  `title` varchar(255) NOT NULL default '',
  `post_num` int(10) NOT NULL default '0',
  `stars` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`id`),
  KEY `title` (`title`),
  KEY `post_num` (`post_num`),
  KEY `stars` (`stars`)
) ENGINE=MyISAM DEFAULT CHARSET=cp1251";


$install_db[] = "DROP TABLE IF EXISTS ".$table_prefix."forums";
$install_db[] = "CREATE TABLE ".$table_prefix."forums (
`id` smallint(5) NOT NULL auto_increment,
`parent_id` smallint(5) NOT NULL DEFAULT '0',
`posi` smallint(5) NOT NULL DEFAULT '1',
`title` varchar(255) NOT NULL default '',
`description` text,
`alt_name` varchar(200) NOT NULL default '',
`last_post_member` varchar(255) NOT NULL default '',
`last_post_member_id` mediumint(8) NOT NULL default '0',
`last_post_date` int(10) NOT NULL default '0',
`last_post_id` int(10) default NULL default '0',
`last_title` varchar(255) NOT NULL default '',
`last_topic_id` int(10) NOT NULL default '0',
`postcount` tinyint(1) NOT NULL default '1',
`allow_bbcode` tinyint(1) NOT NULL default '1',
`allow_poll` tinyint(1) NOT NULL default '1',
`posts` mediumint(8) NOT NULL default '0',
`topics` mediumint(8) NOT NULL default '0',
`posts_hiden` mediumint(7) NOT NULL default '0',
`topics_hiden` mediumint(7) NOT NULL default '0',
`password` varchar(40) NOT NULL default '',
`password_notuse` varchar(255) NOT NULL default '',
`group_permission` text,
`sort_order` varchar(40) NOT NULL default '',
`rules` text,
`meta_desc` text,
`meta_key` text,
PRIMARY KEY  (`id`),
KEY `parent_id` (`parent_id`),
KEY `posi` (`posi`),
KEY `alt_name` (`alt_name`),
KEY `last_post_member` (`last_post_member`),
KEY `last_post_member_id` (`last_post_member_id`),
KEY `last_post_date` (`last_post_date`),
KEY `last_post_id` (`last_post_id`),
KEY `last_topic_id` (`last_topic_id`),
KEY `lposts` (`posts`),
KEY `topics` (`topics`),
KEY `sort_order` (`sort_order`)
) ENGINE=MyISAM DEFAULT CHARSET=cp1251";


$install_db[] = "DROP TABLE IF EXISTS ".$table_prefix."topics";
$install_db[] = "CREATE TABLE ".$table_prefix."topics (
  `id` int(10) NOT NULL auto_increment,
  `forum_id` smallint(5) NOT NULL default '0',
  `title` varchar(255) NOT NULL default '',
  `description` varchar(255) NOT NULL default '',
  `post_id` int(10) NOT NULL default '0',
  `date_open` int(10) NOT NULL default '0',
  `date_last` int(10) NOT NULL default '0',
  `status` varchar(10) NOT NULL,
  `post_num` int(10) NOT NULL default '0',
  `post_hiden` smallint(5) NOT NULL default '0',
  `views` mediumint(8) NOT NULL default '0',
  `last_post_id` int(10) NOT NULL default '0',
  `last_post_member` mediumint(8) NOT NULL default '0',
  `member_name_open` varchar(40) NOT NULL default '',
  `member_id_open` mediumint(8) NOT NULL default '0',
  `member_name_last` varchar(40) NOT NULL default '',
  `fixed` tinyint(1) NOT NULL default '0',
  `hiden` tinyint(1) NOT NULL default '0',
  `poll_id` int(10) NOT NULL default '0',
  `basket` tinyint(1) NOT NULL default '0',
  `basket_fid` smallint(5) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `forum_id` (`forum_id`),
  KEY `post_id` (`post_id`),
  KEY `date_open` (`date_open`),
  KEY `date_last` (`date_last`),
  KEY `status` (`status`),
  KEY `post_num` (`post_num`),
  KEY `views` (`views`),
  KEY `last_post_id` (`last_post_id`),
  KEY `last_post_member` (`last_post_member`),
  KEY `member_name_open` (`member_name_last`),
  KEY `member_id_open` (`member_id_open`),
  KEY `fixed` (`fixed`),
  KEY `hiden` (`hiden`),
  KEY `basket` (`basket`),
  KEY `poll_id` (`poll_id`)
) ENGINE=MyISAM DEFAULT CHARSET=cp1251";

$install_db[] = "DROP TABLE IF EXISTS ".$table_prefix."posts";
$install_db[] = "CREATE TABLE ".$table_prefix."posts (
  `pid` int(10) NOT NULL auto_increment,
  `topic_id` int(10) NOT NULL default '0',
  `new_topic` tinyint(1) NOT NULL default '0',
  `text` text,
  `post_date` int(10) NOT NULL default '0',
  `edit_date` int(10) NOT NULL default '0',
  `post_member_id` int(10) NOT NULL default '0',
  `post_member_name` varchar(40) NOT NULL default '',
  `ip` varchar(16) default NULL,
  `hide` tinyint(1) NOT NULL default '0',
  `fixed` tinyint(1) NOT NULL default '0',
  `attachments` text,
  `edit_member_id` int(10) NOT NULL default '0',
  `edit_member_name` varchar(40) NOT NULL default '',
  `edit_reason` varchar(255) NOT NULL default '',
  `moder_member_id` int(10) NOT NULL default '0',
  `moder_member_name` varchar(40) NOT NULL default '',
  `moder_reason` varchar(255) NOT NULL default '',
  `moder_date` int(10) NOT NULL default '0',
  PRIMARY KEY  (`pid`),
  KEY `topic_id` (`topic_id`),
  KEY `new_topic` (`new_topic`),
  KEY `post_date` (`post_date`),
  KEY `post_member_id` (`post_member_id`),
  KEY `post_member_name` (`post_member_name`),
  KEY `hide` (`hide`),
  KEY `fixed` (`fixed`),
  KEY `ip` (`ip`)
) ENGINE=MyISAM DEFAULT CHARSET=cp1251";



$install_db[] = "DROP TABLE IF EXISTS ".$table_prefix."forums_moderator";
$install_db[] = "CREATE TABLE ".$table_prefix."forums_moderator (
  `fm_id` smallint(5) NOT NULL auto_increment,
  `fm_forum_id` int(11) NOT NULL default '0',
  `fm_member_id` mediumint(8) NOT NULL default '0',
  `fm_member_name` varchar(40) NOT NULL default '',
  `fm_group_id` smallint(5) NOT NULL default '0',
  `fm_is_group` tinyint(1) NOT NULL default '0',
  `fm_permission` text,
  PRIMARY KEY  (`fm_id`),
  KEY `fm_forum_id` (`fm_forum_id`),
  KEY `fm_member_id` (`fm_member_id`),
  KEY `fm_member_name` (`fm_member_name`),
  KEY `fm_group_id` (`fm_group_id`),
  KEY `fm_is_group` (`fm_is_group`)
) ENGINE=MyISAM DEFAULT CHARSET=cp1251";

$install_db[] = "DROP TABLE IF EXISTS ".$table_prefix."control_center_admins";
$install_db[] = "CREATE TABLE ".$table_prefix."control_center_admins (
  `cca_id` smallint(5) NOT NULL auto_increment,
  `cca_member_id` mediumint(8) NOT NULL default '0',
  `cca_group` smallint(5) NOT NULL default '0',
  `cca_is_group` tinyint(1) NOT NULL default '0',
  `cca_permission` text,
  `cca_update` int(10) NOT NULL default '0',
  PRIMARY KEY  (`cca_id`),
  KEY `cca_member_id` (`cca_member_id`),
  KEY `cca_group` (`cca_group`)
) ENGINE=MyISAM DEFAULT CHARSET=cp1251";



$install_db[] = "DROP TABLE IF EXISTS ".$table_prefix."forums_filter";
$install_db[] = "CREATE TABLE ".$table_prefix."forums_filter (
  `id` mediumint(8) NOT NULL auto_increment,
  `word` varchar(40) NOT NULL default '',
  `word_replace` varchar(40) NOT NULL default '',
  `type` tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=cp1251";

$install_db[] = "DROP TABLE IF EXISTS ".$table_prefix."topics_subscribe";
$install_db[] = "CREATE TABLE ".$table_prefix."topics_subscribe (
  `id` int(10) NOT NULL auto_increment,
  `subs_member` mediumint(8) NOT NULL default '0',
  `topic` int(10) NOT NULL default '0',
  `date` int(10) NOT NULL default '0',
  `send_status` tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `subs_member` (`subs_member`),
  KEY `topic` (`topic`),
  KEY `date` (`date`),
  KEY `send_status` (`send_status`)
) ENGINE=MyISAM DEFAULT CHARSET=cp1251";

$install_db[] = "DROP TABLE IF EXISTS ".$table_prefix."forums_notice";
$install_db[] = "CREATE TABLE ".$table_prefix."forums_notice (
  `id` mediumint(8) NOT NULL auto_increment,
  `author` varchar(40) NOT NULL default '',
  `author_id` mediumint(8) NOT NULL default '0',
  `title` varchar(255) NOT NULL default '',
  `text` text,
  `forum_id` text,
  `start_date` int(10) NOT NULL default '0',
  `end_date` int(10) NOT NULL default '0',
  `group_access` text,
  `active_status` tinyint(1) NOT NULL default '1',
  PRIMARY KEY  (`id`),
  KEY `author_id` (`author_id`),
  KEY `start_date` (`start_date`),
  KEY `end_date` (`end_date`),
  KEY `active_status` (`active_status`)
) ENGINE=MyISAM DEFAULT CHARSET=cp1251";

$install_db[] = "DROP TABLE IF EXISTS ".$table_prefix."topics_poll";
$install_db[] = "CREATE TABLE ".$table_prefix."topics_poll (
  `id` int(10) NOT NULL auto_increment,
  `tid` int(10) NOT NULL default '0',
  `vote_num` mediumint(8) NOT NULL default '0',
  `title` varchar(255) NOT NULL default '',
  `question` varchar(255) NOT NULL default '',
  `variants` text,
  `multiple` tinyint(1) NOT NULL default '0',
  `open_date` int(10) NOT NULL default '0',
  `answers` text,
  PRIMARY KEY  (`id`),
  KEY `tid` (`tid`),
  KEY `vote_num` (`vote_num`),
  KEY `open_date` (`open_date`)
) ENGINE=MyISAM DEFAULT CHARSET=cp1251";

$install_db[] = "DROP TABLE IF EXISTS ".$table_prefix."topics_poll_logs";
$install_db[] = "CREATE TABLE ".$table_prefix."topics_poll_logs (
  `id` int(11) NOT NULL auto_increment,
  `poll_id` int(10) NOT NULL default '0',
  `ip` varchar(16) NOT NULL default '',
  `member_id` mediumint(8) NOT NULL default '0',
  `member_name` varchar(40) NOT NULL default '',
  `log_date` int(10) NOT NULL default '0',
  `answer`varchar(255) NOT NULL default '',
  PRIMARY KEY  (`id`),
  KEY `poll_id` (`poll_id`),
  KEY `member_id` (`member_id`),
  KEY `member_name` (`member_name`),
  KEY `log_date` (`log_date`),
  KEY `answer` (`answer`)
) ENGINE=MyISAM DEFAULT CHARSET=cp1251";

$install_db[] = "DROP TABLE IF EXISTS ".$table_prefix."members_status";
$install_db[] = "CREATE TABLE ".$table_prefix."members_status (
  `id` mediumint(8) NOT NULL auto_increment,
  `member_id` mediumint(8) NOT NULL default '0',
  `date` int(10) NOT NULL default '0',
  `text` text,
  PRIMARY KEY  (`id`),
  KEY `member_id` (`member_id`),
  KEY `date` (`date`)
) ENGINE=MyISAM DEFAULT CHARSET=cp1251";

$install_db[] = "DROP TABLE IF EXISTS ".$table_prefix."topics_files";
$install_db[] = "CREATE TABLE ".$table_prefix."topics_files (
  `file_id` mediumint(8) NOT NULL auto_increment,
  `file_title` varchar(255) NOT NULL default '',
  `file_name` varchar(255) NOT NULL default '',
  `file_type` varchar(255) NOT NULL default '',
  `file_mname` varchar(255) NOT NULL default '',
  `file_mid` varchar(255) NOT NULL default '',
  `file_date` int(10) NOT NULL default '0',
  `file_size` int(11) NOT NULL default '0',
  `file_count` int(11) NOT NULL default '0',
  `file_fid` smallint(5) NOT NULL default '0',
  `file_tid` int(10) NOT NULL default '0',
  `file_pid` int(10) NOT NULL default '0',
  `file_convert` tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (`file_id`),
  KEY `file_mid` (`file_mid`),
  KEY `file_fid` (`file_fid`),
  KEY `file_tid` (`file_tid`),
  KEY `file_pid` (`file_pid`)
) ENGINE=MyISAM DEFAULT CHARSET=cp1251";

$install_db[] = "DROP TABLE IF EXISTS ".$table_prefix."members_warning";
$install_db[] = "CREATE TABLE ".$table_prefix."members_warning (
  `id` mediumint(8) NOT NULL auto_increment,
  `mid` mediumint(8) NOT NULL default '0',
  `moder_id` mediumint(8) NOT NULL default '0',
  `moder_name` varchar(40) NOT NULL default '',
  `date` int(10) NOT NULL default '0',
  `description` text,
  `st_w` tinyint(1) NOT NULL default '1',
  PRIMARY KEY  (`id`),
  KEY `mid` (`mid`),
  KEY `st_w` (`st_w`),
  KEY `moder_id` (`moder_id`)
) ENGINE=MyISAM DEFAULT CHARSET=cp1251;";
                              



   foreach($install_db as $query)
    mysql_query($query, $handle) or die(mysql_error());
    mysql_close($handle);

	$handle = mysql_connect($dle_host, $dle_login, $dle_password, true) or die("Error to connect to LB SQL host");
    mysql_select_db($dle_tbname, $handle) or die("Error to select DLE database".mysql_error());
    mysql_query("SET NAMES cp1251", $handle);

$install_db = array();

$install_db[] = "ALTER TABLE `".$dle_prefix."users` ADD `lb_favorite` text;";
$install_db[] = "ALTER TABLE `".$dle_prefix."users` ADD `lb_subscribe` text;";
$install_db[] = "ALTER TABLE `".$dle_prefix."users` ADD `mf_options` text;";
$install_db[] = "ALTER TABLE `".$dle_prefix."users` ADD `view_topic` text default '';";
$install_db[] = "ALTER TABLE `".$dle_prefix."users` ADD `count_warning` tinyint(3) NOT NULL default '0';";
$install_db[] = "ALTER TABLE `".$dle_prefix."users` ADD `mstatus` mediumint(8) NOT NULL default '0';";
$install_db[] = "ALTER TABLE `".$dle_prefix."users` ADD `personal_title` varchar(50) NOT NULL default '';";
$install_db[] = "ALTER TABLE `".$dle_prefix."users` ADD `topics_num` smallint(5) NOT NULL default '0';";
$install_db[] = "ALTER TABLE `".$dle_prefix."users` ADD `posts_num` mediumint(7) NOT NULL default '0';";
$install_db[] = "ALTER TABLE `".$dle_prefix."users` ADD `secret_key` varchar(32) NOT NULL default '';";
$install_db[] = "ALTER TABLE `".$dle_prefix."users` ADD `lb_twitter` varchar(200) NOT NULL default '';";
$install_db[] = "ALTER TABLE `".$dle_prefix."users` ADD `lb_vkontakte` varchar(200) NOT NULL default '';";
$install_db[] = "ALTER TABLE `".$dle_prefix."users` ADD `lb_skype` varchar(200) NOT NULL default '';";
$install_db[] = "ALTER TABLE `".$dle_prefix."users` ADD `lb_sex` tinyint(2) NOT NULL default '0';";
$install_db[] = "ALTER TABLE `".$dle_prefix."users` ADD `lb_limit_publ` tinyint(1) NOT NULL default '0';";
$install_db[] = "ALTER TABLE `".$dle_prefix."users` ADD `lb_limit_days` smallint(4) NOT NULL default '0';";
$install_db[] = "ALTER TABLE `".$dle_prefix."users` ADD `lb_limit_date` int(10) NOT NULL default '0';";
$install_db[] = "CREATE INDEX mstatus ON `".$dle_prefix."users` (mstatus);";

$install_db[] = "ALTER TABLE `".$dle_prefix."users` ADD `lb_b_day` tinyint(2) NOT NULL default '0';";
$install_db[] = "ALTER TABLE `".$dle_prefix."users` ADD `lb_b_month` tinyint(2) NOT NULL default '0';";
$install_db[] = "ALTER TABLE `".$dle_prefix."users` ADD `lb_b_year` smallint(4) NOT NULL default '0';";

$install_db[] = "CREATE INDEX lb_b_day ON `".$dle_prefix."users` (lb_b_day);";
$install_db[] = "CREATE INDEX lb_b_month ON `".$dle_prefix."users` (lb_b_month);";
$install_db[] = "CREATE INDEX lb_b_year ON `".$dle_prefix."users` (lb_b_year);";

$install_db[] = "ALTER TABLE ".LB_DB_PREFIX."_members_ranks ADD mid mediumint(8) NOT NULL default '0'";
$install_db[] = "ALTER TABLE ".LB_DB_PREFIX."_forums ADD allow_bbcode_list varchar(100) NOT NULL default '' AFTER `allow_bbcode`";
$install_db[] = "ALTER TABLE ".LB_DB_PREFIX."_forums ADD ficon varchar(255) NOT NULL default '' AFTER `id`";
$install_db[] = "ALTER TABLE ".LB_DB_PREFIX."_topics ADD post_fixed smallint(5) NOT NULL default '0' AFTER `post_hiden`";
$install_db[] = "ALTER TABLE ".LB_DB_PREFIX."_posts ADD utility smallint(5) NOT NULL default '0'";
$install_db[] = "ALTER TABLE ".LB_DB_PREFIX."_forums_notice ADD show_sub tinyint(1) NOT NULL default '0'";

if(in_array("rep_mod", $options))
{

     $install_db[] = "CREATE TABLE " .$dle_prefix. "repa_comm (
  `id` int(10) unsigned NOT NULL auto_increment,
  `how` tinytext,
  `date` datetime NOT NULL default '0000-00-00 00:00:00',
  `author` varchar(40) NOT NULL default '',
  `komu` varchar (40) NOT NULL default'',
  `text` text,
  `edit_repa` TINYINT( 3 ) NOT NULL DEFAULT '0',
  `hide` TINYINT( 3 ) NOT NULL DEFAULT '0',
  `url_page` text,
  PRIMARY KEY  (`id`),
  FULLTEXT KEY `text` (`text`)
) ENGINE=MyISAM DEFAULT CHARSET=cp1251;";
   $install_db[] = "CREATE TABLE " .$dle_prefix. "repa_log (
`id` int(11) NOT NULL auto_increment,
`autor_id` mediumint(8) NOT NULL,
`komu_id` mediumint(8) NOT NULL,
`date_change` varchar(20) default NULL,
PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=cp1251;";

    $install_db[] = "CREATE TABLE " .$dle_prefix. "repa_del_logs (
`id` int(11) NOT NULL auto_increment,
`date` datetime NOT NULL,
`username` varchar(100) NOT NULL,
`autor` varchar(100) NOT NULL,
`moder` varchar(100) NOT NULL,
`repa_id` int(11) NOT NULL default '0',
`description` text,
PRIMARY KEY  (`id`),
KEY `date` (`date`,`username`,`autor`,`moder`,`repa_id`),
KEY `repa_id` (`repa_id`),
KEY `autor` (`autor`),
KEY `moder` (`moder`),
KEY `username` (`username`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=cp1251;";

    $install_db[] = "ALTER TABLE `" .$dle_prefix. "users` ADD `repa` MEDIUMINT( 8 ) DEFAULT '0'";
    $install_db[] = "ALTER TABLE `" .$dle_prefix. "users` ADD `repa_mod` varchar(20) NOT NULL DEFAULT '0|0' AFTER `repa`;";
}

foreach($install_db as $query)
    mysql_query($query, $handle);

mysql_close($handle);
}


?>