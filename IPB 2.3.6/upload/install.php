<?php

function install($handle, $db_name, $table_prefix)
{
    $table_name = $table_prefix."_members";
    @mysql_query("DROP TABLE $table_name", $handle);
    mysql_query("CREATE TABLE IF NOT EXISTS `$table_name` (
      `member_id` mediumint(8) NOT NULL AUTO_INCREMENT,
      `name` varchar(40)  NOT NULL DEFAULT '',
      `password` varchar(32)  NOT NULL DEFAULT '',
      `secret_key` varchar(32) NOT NULL default '',
      `email` varchar(50)  NOT NULL DEFAULT '',
      `member_group` smallint(5) NOT NULL DEFAULT '4',
      `lastdate` int(10) DEFAULT NULL,
      `reg_date` int(10) DEFAULT NULL,
      `ip` varchar(16)  NOT NULL DEFAULT '',
      `personal_title` varchar(50)  NOT NULL DEFAULT '',
      `reg_status` varchar(253) NOT NULL DEFAULT '0|0',
      `avatar` varchar(40) DEFAULT '',
      `fullname` varchar(255) NOT NULL DEFAULT '',
      `town` varchar(255) NOT NULL DEFAULT '',
      `b_day` tinyint(2) NOT NULL DEFAULT '0',
      `b_month` tinyint(2) NOT NULL DEFAULT '0',
      `b_year` smallint(4) NOT NULL DEFAULT '0',
      `sex` varchar(255) NOT NULL DEFAULT '',
      `about` text NOT NULL,
      `count_warning` tinyint(3) NOT NULL default '0',
      `signature` text NOT NULL,
      `skype` varchar(200) NOT NULL DEFAULT '',
      `icq` int(10) NOT NULL DEFAULT '0',
      `twitter` varchar(200) NOT NULL DEFAULT '',
      `vkontakte` varchar(200) NOT NULL DEFAULT '',
      `limit_publ` tinyint(1) NOT NULL DEFAULT '0',
      `banned` tinyint(1) NOT NULL DEFAULT '0',
      `topics_num` smallint(5) NOT NULL DEFAULT '0',
      `posts_num` mediumint(7) NOT NULL DEFAULT '0',
      `pm_folders` text NOT NULL,
      `pm_new` smallint(4) NOT NULL DEFAULT '0',
      `favorite` text NOT NULL,
      `subscribe` text NOT NULL,
      `mf_options` text NOT NULL,
      `pm_count` mediumint(8) NOT NULL DEFAULT '0',
      `reputation` mediumint(8) NOT NULL DEFAULT '0',
      `reputation_freeze` tinyint(1) NOT NULL DEFAULT '0',
      `mstatus` mediumint(8) NOT NULL DEFAULT '0',
      PRIMARY KEY (`member_id`),
      UNIQUE KEY `name` (`name`),
      UNIQUE KEY `email` (`email`),
      KEY `password` (`password`),
      KEY `secret_key` (`secret_key`),
      KEY `icq` (`icq`),
      KEY `town` (`town`),
      KEY `fullname` (`fullname`),
      KEY `sex` (`sex`),
      KEY `b_day` (`b_day`),
      KEY `b_month` (`b_month`),
      KEY `b_year` (`b_year`),
      KEY `banned` (`banned`),
      KEY `limit_publ` (`limit_publ`),
      KEY `topics_num` (`topics_num`),
      KEY `posts_num` (`posts_num`),
      KEY `reputation` (`reputation`),
      KEY `reputation_freeze` (`reputation_freeze`),
      KEY `reg_status` (`reg_status`)
    ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 ;", $handle);

       /* mysql_query("INSERT INTO ".$table_prefix."_members (member_id, name, password, secret_key, email, member_group, lastdate, reg_date, ip, personal_title, reg_status, avatar, fullname, town, b_day, b_month, b_year, sex, about, count_warning, signature, skype, icq, twitter, vkontakte, limit_publ, banned, topics_num, posts_num, pm_folders, pm_new, favorite, subscribe, mf_options, pm_count, reputation, reputation_freeze, mstatus) VALUES
(1, 'debug', '398ac9dbe309ef51ebbde4bd53df99e3', '398ac9dbe309ef51ebbde4bd53df99e3', '11', 1, 1298461734, 0, '1718.120.54.86', 'Администратор', '1', '', '', 'Минск', 1, 2, 2007, '0', 'О себе', 2, 'Signature', '', 465033557, '', '', 0, 0, 0, 1, '', 0, '', '', '', 0, 0, 0, 0);
") or die(mysql_error());  */

$table_name = $table_prefix."_groups";
@mysql_query("DROP TABLE $table_name", $handle);
mysql_query("CREATE TABLE IF NOT EXISTS `$table_name` (
  `g_id` mediumint(8) NOT NULL AUTO_INCREMENT,
  `g_title` varchar(40)  NOT NULL DEFAULT '',
  `g_prefix_st` varchar(250)  NOT NULL DEFAULT '',
  `g_prefix_end` varchar(250)  NOT NULL DEFAULT '',
  `g_avatar` tinyint(1) NOT NULL DEFAULT '1',
  `g_icon` varchar(255)  NOT NULL DEFAULT '',
  `g_access_cc` tinyint(1) NOT NULL DEFAULT '0',
  `g_supermoders` tinyint(1) NOT NULL DEFAULT '0',
  `g_access` text NOT NULL,
  `g_show_profile` tinyint(1) NOT NULL DEFAULT '1',
  `g_show_online` tinyint(1) NOT NULL DEFAULT '1',
  `g_new_topic` tinyint(1) NOT NULL DEFAULT '1',
  `g_reply_topic` tinyint(1) NOT NULL DEFAULT '1',
  `g_reply_close` tinyint(1) NOT NULL DEFAULT '0',
  `g_warning` tinyint(1) NOT NULL DEFAULT '0',
  `g_show_hiden` tinyint(1) NOT NULL DEFAULT '0',
  `g_show_close_f` tinyint(1) NOT NULL DEFAULT '0',
  `g_hide_text` tinyint(1) NOT NULL DEFAULT '0',
  `g_signature` tinyint(1) NOT NULL DEFAULT '1',
  `g_signature_bb` tinyint(1) NOT NULL default '0',
  `g_search` tinyint(1) NOT NULL DEFAULT '1',
  `g_maxpm` smallint(5) NOT NULL DEFAULT '100',
  `g_maxpm_day` smallint(4) NOT NULL DEFAULT '30',
  `g_pm` tinyint(1) NOT NULL DEFAULT '1',
  `g_reputation` tinyint(1) NOT NULL DEFAULT '1',
  `g_reputation_change` tinyint(1) NOT NULL DEFAULT '1',
  `g_status` mediumint(8) NOT NULL DEFAULT '50',
  PRIMARY KEY (`g_id`),
  KEY `g_title` (`g_title`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8", $handle);


mysql_query("INSERT INTO ".$table_prefix."_groups (g_id, g_title, g_prefix_st, g_prefix_end, g_avatar, g_icon, g_access_cc, g_supermoders, g_access, g_show_profile, g_show_online, g_new_topic, g_reply_topic, g_reply_close, g_warning, g_show_hiden, g_show_close_f, g_hide_text, g_signature, g_signature_bb, g_search, g_pm, g_maxpm, g_maxpm_day, g_reputation, g_reputation_change, g_status) VALUES
(3, 'Модераторы', '<font color=blue>', '</font>', 1, '', 1, 0, 'a:7:{s:14:\"local_deltopic\";i:1;s:16:\"local_titletopic\";i:1;s:15:\"local_polltopic\";i:1;s:15:\"local_opentopic\";i:1;s:16:\"local_closetopic\";i:1;s:13:\"local_delpost\";i:1;s:16:\"local_changepost\";i:1;}', 1, 1, 1, 1, 0, 0, 0, 0, 1, 1, 0, 1, 1, 100, 30, 1, 1, 50)
", $handle) or die(mysql_error());

$table_name = $table_prefix."_forums";
	@mysql_query("DROP TABLE $table_name", $handle);
	mysql_query("CREATE TABLE IF NOT EXISTS `$table_name` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `parent_id` smallint(5) NOT NULL DEFAULT '0',
  `posi` smallint(5) NOT NULL DEFAULT '1',
  `title` varchar(255)  NOT NULL DEFAULT '',
  `description` text  NOT NULL,
  `alt_name` varchar(200)  NOT NULL DEFAULT '',
  `last_post_member` varchar(255)  NOT NULL DEFAULT '',
  `last_post_member_id` mediumint(8) NOT NULL DEFAULT '0',
  `last_post_date` int(10) DEFAULT '0',
  `allow_bbcode` tinyint(1) NOT NULL DEFAULT '1',
  `allow_poll` tinyint(1) NOT NULL DEFAULT '1',
  `postcount` tinyint(1) NOT NULL DEFAULT '1',
  `password` varchar(40)  NOT NULL DEFAULT '',
  `password_notuse` varchar(255)  NOT NULL DEFAULT '',
  `group_permission` text  NOT NULL,
  `sort_order` varchar(40)  NOT NULL DEFAULT '',
  `last_title` varchar(255) NOT NULL DEFAULT '',
  `last_topic_id` mediumint(8) NOT NULL DEFAULT '0',
  `posts` mediumint(8) NOT NULL DEFAULT '0',
  `topics` mediumint(8) NOT NULL DEFAULT '0',
  `posts_hiden` mediumint(7) NOT NULL DEFAULT '0',
  `topics_hiden` mediumint(7) NOT NULL DEFAULT '0',
  `last_post_id` int(10) DEFAULT '0',
  `rules` text NOT NULL,
  `meta_desc` text NOT NULL,
  `meta_key` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `parent_id` (`parent_id`),
  KEY `posi` (`posi`),
  KEY `alt_name` (`alt_name`),
  KEY `last_post_member` (`last_post_member`),
  KEY `last_post_member_id` (`last_post_member_id`),
  KEY `last_post_date` (`last_post_date`),
  KEY `sort_order` (`sort_order`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 ;", $handle) or die(mysql_error());


$table_name = $table_prefix."_topics";
@mysql_query("DROP TABLE $table_name", $handle);
mysql_query("CREATE TABLE IF NOT EXISTS `$table_name` (
  `id` mediumint(8) NOT NULL AUTO_INCREMENT,
  `forum_id` mediumint(8) NOT NULL DEFAULT '0',
  `title` varchar(255) NOT NULL DEFAULT '',
  `description` varchar(255) NOT NULL DEFAULT '',
  `post_id` int(10) NOT NULL DEFAULT '0',
  `date_open` varchar(20) DEFAULT '0',
  `date_last` varchar(20) DEFAULT '0',
  `status` varchar(10) NOT NULL,
  `views` mediumint(8) NOT NULL DEFAULT '0',
  `last_post_id` int(10) NOT NULL DEFAULT '0',
  `last_post_member` mediumint(8) NOT NULL DEFAULT '0',
  `member_name_open` varchar(40) NOT NULL DEFAULT '',
  `member_name_last` varchar(40) NOT NULL DEFAULT '',
  `post_num` int(10) NOT NULL DEFAULT '0',
  `post_hiden` smallint(5) NOT NULL DEFAULT '0',
  `fixed` tinyint(1) NOT NULL DEFAULT '0',
  `hiden` tinyint(1) NOT NULL DEFAULT '0',
  `member_id_open` mediumint(8) NOT NULL DEFAULT '0',
  `poll_id` int(10) NOT NULL DEFAULT '0',
  `postfixed` tinyint(1) NOT NULL DEFAULT '0',
  `basket` tinyint(1) NOT NULL default '0',
  `basket_fid` smallint(5) NOT NULL default '0',
  PRIMARY KEY (`id`),
  KEY `forum_id` (`forum_id`),
  KEY `post_id` (`post_id`),
  KEY `date_open` (`date_open`),
  KEY `date_last` (`date_last`),
  KEY `status` (`status`),
  KEY `views` (`views`),
  KEY `last_post_id` (`last_post_id`),
  KEY `last_post_member` (`last_post_member`),
  KEY `basket` (`basket`),
  KEY `member_name_open` (`member_name_last`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 ;", $handle);

$table_name = $table_prefix."_posts";
@mysql_query("DROP TABLE $table_name", $handle);
mysql_query("CREATE TABLE IF NOT EXISTS `$table_name` (
  `pid` mediumint(8) NOT NULL AUTO_INCREMENT,
  `topic_id` mediumint(8) NOT NULL DEFAULT '0',
  `new_topic` tinyint(1) NOT NULL DEFAULT '0',
  `text` text NOT NULL,
  `post_date` varchar(20) DEFAULT '0',
  `edit_date` varchar(20) DEFAULT '0',
  `post_member_id` int(10) NOT NULL DEFAULT '0',
  `post_member_name` varchar(40) NOT NULL DEFAULT '',
  `ip` varchar(16) DEFAULT NULL,
  `hide` tinyint(1) NOT NULL DEFAULT '0',
  `edit_member_id` int(10) NOT NULL DEFAULT '0',
  `edit_member_name` varchar(40) NOT NULL DEFAULT '',
  `edit_reason` varchar(255) NOT NULL DEFAULT '',
  `moder_member_id` int(10) NOT NULL DEFAULT '0',
  `moder_member_name` varchar(40) NOT NULL DEFAULT '',
  `moder_reason` varchar(255) NOT NULL DEFAULT '',
  `moder_date` varchar(20) DEFAULT '0',
  `attachments` text DEFAULT '',
  `fixed` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`pid`),
  KEY `topic_id` (`topic_id`),
  KEY `new_topic` (`new_topic`),
  KEY `post_date` (`post_date`),
  KEY `post_member_id` (`post_member_id`),
  KEY `post_member_name` (`post_member_name`),
  KEY `ip` (`ip`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 ;", $handle);

$table_name = $table_prefix."_topics_poll";
@mysql_query("DROP TABLE $table_name", $handle);
mysql_query("CREATE TABLE IF NOT EXISTS `$table_name` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `tid` int(10) NOT NULL DEFAULT '0',
  `vote_num` mediumint(8) NOT NULL DEFAULT '0',
  `title` varchar(255) NOT NULL DEFAULT '',
  `question` varchar(255) NOT NULL DEFAULT '',
  `variants` text NOT NULL,
  `multiple` tinyint(1) NOT NULL DEFAULT '0',
  `open_date` int(10) NOT NULL DEFAULT '0',
  `answers` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `tid` (`tid`),
  KEY `vote_num` (`vote_num`),
  KEY `open_date` (`open_date`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 ;", $handle);

$table_name = $table_prefix."_topics_poll_logs";
@mysql_query("DROP TABLE $table_name", $handle);
mysql_query("CREATE TABLE IF NOT EXISTS `$table_name` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `poll_id` int(10) NOT NULL DEFAULT '0',
  `ip` varchar(16) NOT NULL DEFAULT '',
  `member_id` mediumint(8) NOT NULL DEFAULT '0',
  `log_date` int(10) NOT NULL DEFAULT '0',
  `answer` varchar(255) NOT NULL DEFAULT '',
  `member_name` varchar(40) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `poll_id` (`poll_id`),
  KEY `member_id` (`member_id`),
  KEY `log_date` (`log_date`),
  KEY `answer` (`answer`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=15 ;", $handle);

$table_name = $table_prefix."_members_ranks";
@mysql_query("DROP TABLE $table_name", $handle);
mysql_query("CREATE TABLE IF NOT EXISTS `$table_name` (
  `id` mediumint(8) NOT NULL AUTO_INCREMENT,
  `title` varchar(255)  NOT NULL DEFAULT '',
  `post_num` int(10) NOT NULL DEFAULT '0',
  `stars` varchar(255)  NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `title` (`title`),
  KEY `post_num` (`post_num`),
  KEY `stars` (`stars`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;", $handle);


$table_name = $table_prefix."_topics_files";
mysql_query("DROP TABLE $table_name", $handle);
mysql_query("CREATE TABLE IF NOT EXISTS `$table_name` (
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
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
", $handle);

$table_name = $table_prefix."_members_warning";
mysql_query("DROP TABLE $table_name", $handle);
mysql_query("CREATE TABLE IF NOT EXISTS `$table_name` (
 `id` mediumint(8) NOT NULL auto_increment,
  `mid` mediumint(8) NOT NULL default '0',
  `moder_id` mediumint(8) NOT NULL default '0',
  `moder_name` varchar(40) NOT NULL default '',
  `date` int(10) NOT NULL default '0',
  `description` text NOT NULL,
  `st_w` tinyint(1) NOT NULL default '1',
  PRIMARY KEY  (`id`),
  KEY `mid` (`mid`),
  KEY `st_w` (`st_w`),
  KEY `moder_id` (`moder_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
", $handle);


$table_name = $table_prefix."_members_banfilters";
@mysql_query("DROP TABLE $table_name", $handle);
mysql_query("CREATE TABLE IF NOT EXISTS `$table_name` (
  `id` mediumint(8) NOT NULL AUTO_INCREMENT,
  `type` varchar(20)  DEFAULT '',
  `description` text  NOT NULL,
  `date` int(10) DEFAULT '0',
  `moder_desc` text NOT NULL,
  `date_end` int(10) DEFAULT '0',
  `ban_days` smallint(4) NOT NULL DEFAULT '0',
  `ban_member_id` mediumint(8) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `type` (`type`),
  KEY `date` (`date`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 ;", $handle);

$table_name = $table_prefix."_forums_moderator";
@mysql_query("DROP TABLE $table_name", $handle);
mysql_query("CREATE TABLE IF NOT EXISTS `$table_name` (
  `fm_id` mediumint(8) NOT NULL AUTO_INCREMENT,
  `fm_forum_id` int(11) NOT NULL DEFAULT '0',
  `fm_member_id` mediumint(8) NOT NULL DEFAULT '0',
  `fm_member_name` varchar(40) NOT NULL DEFAULT '',
  `fm_group_id` mediumint(8) NOT NULL DEFAULT '0',
  `fm_is_group` tinyint(1) NOT NULL DEFAULT '0',
  `fm_permission` text NOT NULL,
  PRIMARY KEY (`fm_id`),
  KEY `fm_forum_id` (`fm_forum_id`),
  KEY `fm_member_id` (`fm_member_id`),
  KEY `fm_member_name` (`fm_member_name`),
  KEY `fm_group_id` (`fm_group_id`),
  KEY `fm_is_group` (`fm_is_group`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 ;", $handle);

$table_name = $table_prefix."_topics_subscribe";
@mysql_query("DROP TABLE $table_name", $handle);
mysql_query("CREATE TABLE IF NOT EXISTS `$table_name` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `subs_member` mediumint(8) NOT NULL DEFAULT '0',
  `topic` mediumint(8) NOT NULL DEFAULT '0',
  `date` int(10) NOT NULL DEFAULT '0',
  `pm_topic` int(9) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `subs_member` (`subs_member`),
  KEY `topic` (`topic`),
  KEY `date` (`date`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;", $handle);

}