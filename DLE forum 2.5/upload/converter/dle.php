<?php

    set_time_limit(0);
    ini_set('memory_limit', '512M');
    
    @error_reporting ( E_ERROR );
    @ini_set ( 'display_errors', true );
    @ini_set ( 'html_errors', false );
    @ini_set ( 'error_reporting', E_ERROR );

    require_once "include/parser.php";

	$lb_prefix = $lb_dbname = $dle_prefix = $dle_dbname = "";
	
	function fetch_array($sql_result)
	{
		for($result=array(); $row=mysql_fetch_array($sql_result); $result[] = $row);
		return $result;
	}
	
	function get_member_id($name)
	{
		if(!$name)
			return "";
		global $lb_prefix,$lb_dbname;
		mysql_select_db($lb_dbname);
		$sql_result = mysql_query("SELECT member_id FROM ".$lb_prefix."_members WHERE name='$name'");
		if(!$sql_result)
			return -1;
		return @mysql_result($sql_result, 0, 0);
	}

	function get_member_name($id)
	{
		global $lb_prefix,$lb_dbname;
		mysql_select_db($lb_dbname);
		$sql_result = mysql_query("SELECT name FROM ".$lb_prefix."_members WHERE member_id='$id'");
		return $sql_result ? @mysql_result($sql_result, 0, 0) : -1;
	}
	
	function translit($str) 
	{
    $tr = array(
        "А"=>"A","Б"=>"B","В"=>"V","Г"=>"G",
        "Д"=>"D","Е"=>"E","Ж"=>"J","З"=>"Z","И"=>"I",
        "Й"=>"Y","К"=>"K","Л"=>"L","М"=>"M","Н"=>"N",
        "О"=>"O","П"=>"P","Р"=>"R","С"=>"S","Т"=>"T",
        "У"=>"U","Ф"=>"F","Х"=>"H","Ц"=>"TS","Ч"=>"CH",
        "Ш"=>"SH","Щ"=>"SCH","Ъ"=>"","Ы"=>"YI","Ь"=>"",
        "Э"=>"E","Ю"=>"YU","Я"=>"YA","а"=>"a","б"=>"b",
        "в"=>"v","г"=>"g","д"=>"d","е"=>"e","ж"=>"j",
        "з"=>"z","и"=>"i","й"=>"y","к"=>"k","л"=>"l",
        "м"=>"m","н"=>"n","о"=>"o","п"=>"p","р"=>"r",
        "с"=>"s","т"=>"t","у"=>"u","ф"=>"f","х"=>"h",
        "ц"=>"ts","ч"=>"ch","ш"=>"sh","щ"=>"sch","ъ"=>"y",
        "ы"=>"yi","ь"=>"","э"=>"e","ю"=>"yu","я"=>"ya"
    );
    return str_replace(" ","-",strtr($str,$tr));
	}

	
	function datetime_to_int($date)
	{
		preg_match_all("#(\d+)\D*#sui", $date, $date_items);
		$date_items = $date_items[1];
		if($date_items[0] == '0000')
			return 0;
		return mktime($date_items[3], $date_items[4], $date_items[5], $date_items[1], $date_items[2], $date_items[0]);
	}

    function convert($mysql_host, $mysql_user, $mysql_password, $dle_dbname_, $dle_prefix_, $lb_dbname_, $lb_prefix_, $site_path)
    {


    $GLOBALS['lb_prefix'] = $lb_prefix_;
	$GLOBALS['dle_prefix'] = $dle_prefix_;
	$GLOBALS['lb_dbname'] = $lb_dbname_;
	$GLOBALS['dle_dbname'] = $dle_dbname_;
		
	global $lb_prefix, $dle_prefix, $lb_dbname, $dle_dbname;
		
	if(!@mysql_connect($mysql_host, $mysql_user, $mysql_password))
        return "MYSQL_ERROR";
	if(!@mysql_select_db($dle_dbname))
		return "DLE_NO_FOUND";
	if(!@mysql_select_db($lb_dbname))
		return "LB_NO_FOUND";



    $tables_dle = array("_usergroups", "_banned", "_users","_forum_category", "_forum_forums", "_forum_topics", "_forum_posts","_forum_poll_log","_forum_moderators", "_forum_titles", "_forum_subscription", "_forum_reputation_log", "_forum_files","_forum_warn_log");
    
    $tables = array();
    foreach($tables_dle as $table)$tables[] = $dle_prefix.$table;
    
    mysql_select_db($dle_dbname);
    foreach($tables as $table)
    {
       $query = "SELECT * FROM $table";
        if(!mysql_query($query))
           return "NO TABLE:$table";
    }
	
	include "install.php";
    mysql_query("SET NAMES UTF8");
    //users
	mysql_select_db($dle_dbname);
	$sql_result = mysql_query("SELECT * FROM ".$dle_prefix."_users ORDER by reg_date ASC");
	$result = fetch_array($sql_result);
	mysql_select_db($lb_dbname);
    $count = 0;
	foreach($result as $item)
	{
	   
       	$count++;
	   if ($count > 500)
	   {
		  sleep(4);
		  $count = 0;
	   }
       
		$member_sk = md5(md5($item['password'].time().$item['logged_ip']));
		mysql_query("INSERT INTO ".$lb_prefix."_members SET 
		name='".$item['name']."',
		password='".$item['password']."',
        secret_key='".$member_sk."',
		email='".$item['email']."',
		member_group='".(($item['user_group'] < 6) ? $item['user_group'] : $item['user_group'] + 1)."',
		lastdate='".$item['lastdate']."',
		reg_date='".$item['reg_date']."',
		ip='".$item['logged_ip']."',
		personal_title='',
		reg_status='1',
		avatar='',
		fullname='".$item['fullname']."',
		town='".$item['land']."',
		about='".$item['info']."',
		signature='".$item['signature']."',
		icq='".$item['icq']."',
		banned='".(($item['banned']=="yes") ? 1 : 0)."',
		posts_num ='".$item['forum_post']."',
		reputation='".$item['forum_reputation']."'
		");
		$users_id[$item['user_id']] = mysql_insert_id();
	}
	
    sleep(4);	

	//Categories
	mysql_select_db($dle_dbname);
	$sql_result = mysql_query("SELECT * FROM ".$dle_prefix."_forum_category ORDER by sid ASC");
	$result = fetch_array($sql_result);
	mysql_select_db($lb_dbname);
	foreach($result as $item)
	{
		$postcount = (isset($item['postcount'])) ? $item['postcount'] : 1;
		mysql_query("INSERT INTO ".$lb_prefix."_forums SET 
		parent_id='0',
		title='".mysql_escape_string($item['cat_name'])."',
		alt_name='".translit($item['cat_name'])."',
		postcount='$postcount',
		posi='".$item['posi']."'");
		$categories_id[$item['sid']] = mysql_insert_id();
	}

    sleep(4);

	//groups
	mysql_select_db($dle_dbname);
	$sql_result = mysql_query("SELECT * FROM ".$dle_prefix."_usergroups ORDER by id ASC");
	$result = fetch_array($sql_result);
	mysql_select_db($lb_dbname);
	for($i = 0, $id = 1; $i < 5; $i++, $groups_count++)
		mysql_query("INSERT INTO ".$lb_prefix."_groups SET g_id='".($id++)."', g_title='".$result[$i]['group_name']."'");
	mysql_query("INSERT INTO ".$lb_prefix."_groups SET g_id='6', g_title='Неактивные'");
	for($i = 5, $id = 7, $groups_count = 6; $i < count($result); $i++,$groups_count++)
		mysql_query("INSERT INTO ".$lb_prefix."_groups SET g_id='".($id++)."', g_title='".$result[$i]['group_name']."'");
	mysql_query("UPDATE ".$lb_prefix."_groups SET g_access_cc='1',g_supermoders='1' WHERE g_id='1'");

	sleep(4);
	
	//forums
	mysql_select_db($dle_dbname);
	$sql_result = mysql_query("SELECT * FROM ".$dle_prefix."_forum_forums ORDER by id ASC");
	$result = fetch_array($sql_result);
	mysql_select_db($lb_dbname);
	foreach($result as $item)
	{
		$access_write = explode(":",$item['access_write']);
		$access_read = explode(":",$item['access_read']);
		$access_mod = explode(":",$item['access_mod']);
		$access_write = explode(":",$item['access_write']);
		$access_topic = explode(":",$item['access_topic']);
		$access_upload = explode(":",$item['access_upload']);
		$access_download = explode(":",$item['access_download']);
		
		$permissions = array();
		
		
		
		for($i = 1; $i <= $groups_count; $i++)
			$permissions[$i] = array("read_forum" => 0,"read_theme" => 0,"creat_theme" => 0,"answer_theme" => 0,"upload_files" => 0,"download_files" => 0);
			
		for($j = 0; $j < count($access_read); $j++)
		{
			$index = ($access_read[$j] > 5) ? $access_read[$j] + 1 : $access_read[$j];
			$permissions[$index]['read_forum'] = 1;
			$permissions[$index]['read_theme'] = 1;
		}
		for($j = 0; $j < count($access_write); $j++)
		{
			$index = ($access_write[$j] > 5) ? $access_write[$j] + 1 : $access_write[$j];
			$permissions[$index]['answer_theme'] = 1;
		}
		for($j = 0; $j < count($access_topic); $j++)
		{
			$index = ($access_topic[$j] > 5) ? $access_topic[$j] + 1 : $access_topic[$j];
			$permissions[$index]['creat_theme'] = 1;
		}
		for($j = 0; $j < count($access_upload); $j++)
		{
			$index = ($access_upload[$j] > 5) ? $access_upload[$j] + 1 : $access_upload[$j];
			$permissions[$index]['upload_files'] = 1;
		}
		for($j = 0; $j < count($access_download); $j++)
		{
			$index = ($access_download[$j] > 5) ? $access_download[$j] + 1 : $access_download[$j];
			$permissions[$index]['download_files'] = 1;
		}
		$permissions[6] = $permissions[5];

		$permissions = serialize($permissions);
		$postcount = (isset($item['postcount'])) ? $item['postcount'] : 1;
		mysql_query("INSERT INTO ".$lb_prefix."_forums SET 
		posi='".$item['posi']."',
		title='".mysql_escape_string($item['name'])."',
		alt_name='".mysql_escape_string(translit($item['name']))."',
		description='".mysql_escape_string($item['description'])."',
		last_post_member='".$item['f_last_poster_name']."',
		last_post_member_id='".get_member_id($item['f_last_poster_name'])."',
		last_post_date='".datetime_to_int($item['f_last_date'])."',
		allow_bbcode='1',
		allow_poll='1',
		postcount='$postcount',
		group_permission ='$permissions',
		password='".$item['password']."',
		sort_order='DESC',
		posts='".$item['posts']."',
		topics ='".$item['topics']."',
		posts_hiden='".$item['posts_hiden']."',
		topics_hiden='".$item['topics_hiden']."',
		rules='".$item['rules']."',
		meta_desc='',
		meta_key=''
		");
		$forums_id[$item['id']] = mysql_insert_id();
	}
	
	foreach($result as $item)
	{
		$parent_id = $item['parentid'] != 0 ? $forums_id[$item['parentid']] : $categories_id[$item['main_id']];
		$forum_id = $forums_id[$item['id']];
		mysql_query("UPDATE ".$lb_prefix."_forums SET parent_id='$parent_id' WHERE id='$forum_id'");
	}
    
	sleep(4);

	//topics
	mysql_select_db($dle_dbname);
	$sql_result = mysql_query("SELECT * FROM ".$dle_prefix."_forum_topics  ORDER by tid ASC");
	$result = fetch_array($sql_result);
	mysql_select_db($lb_dbname);
	$max_data = array();
    $count = 0;
	foreach($result as $item)
	{
	   $count++;
	   if ($count > 100)
	   {
		  sleep(1);
		  $count = 0;
	   }
       
		$forum_id = $forums_id[$item['forum_id']];
		$date_last = datetime_to_int($item['last_date']);
		$hidden = ($item['hidden'] >= 1) ? 1 : 0;
		mysql_query("INSERT INTO ".$lb_prefix."_topics SET 
		forum_id='$forum_id',
		title='".mysql_escape_string($item['title'])."',
		description='".mysql_escape_string($item['topic_descr'])."',
		post_id='$first_post_id',     
		date_open='".datetime_to_int($item['start_date'])."',
		date_last='$date_last',
		status='".(($item['topic_status'] == 0) ? "open" : "closed")."',
		views='".$item['views']."',
		post_num='".$item['post']."',
		post_hiden='0',
		fixed='".(($item['fixed'] == 0) ? 1 : 0)."',
		hiden='$hidden',
		poll_id='ERROR',
		postfixed='0'
		");
		$topics_id[$item['tid']] = mysql_insert_id();
		
		if($item['poll_title'] != "")
		{
			$topic_id = $topics_id[$item['tid']];
			$variants = str_replace("<br />", "\r\n", $item['poll_body']);
			mysql_query("INSERT INTO ".$lb_prefix."_topics_poll SET
			tid='$topic_id',
			vote_num='".$item['poll_count']."',
			title='".$item['poll_title']."',
			question='".$item['frage']."',
			variants='$variants',
			answers='".$item['answer']."',
			multiple='".$item['multiple']."',
			open_date='".datetime_to_int($item['start_date'])."'");
			mysql_query("UPDATE ".$lb_prefix."_topics SET poll_id='".mysql_insert_id()."' WHERE id='$topic_id'");
		}
		
		if((!isset($max_data[$forum_id])) || ($max_data[$forum_id]["time"] < $date_last))
		{
			$max_data[$forum_id]["time"] = $date_last;
			$max_data[$forum_id]["tid"] = $topics_id[$item['tid']];
		}
		$max_data[$forum_id]['hiden'] += $hidden;
	}
    $count = 0;
	foreach($max_data as $fid=>$value)
	{
	   $count++;
	   if ($count > 100)
	   {
		  sleep(1);
		  $count = 0;
	   }
       
		$last_topic_id = $value['tid'];
		$topics_hidden = $value['hiden'];
		$sql_result = mysql_query("SELECT title FROM ".$lb_prefix."_topics WHERE id='$last_topic_id'");
		$last_title = mysql_result($sql_result, 0, 0);
		mysql_query("UPDATE ".$lb_prefix."_forums SET last_topic_id='$last_topic_id',topics_hiden='$topics_hidden',last_title='$last_title' WHERE id='$fid'");
	}

    sleep(4);
	//posts
	mysql_select_db($dle_dbname);
	$sql_result = mysql_query("SELECT * FROM ".$dle_prefix."_forum_posts  ORDER by pid ASC");
	$result = fetch_array($sql_result);
	$min_data = $max_data = array();
	mysql_select_db($lb_dbname);
    $count = 0;
	foreach($result as $item)
	{
	   $count++;
	   if ($count > 50)
	   {
		  sleep(0.4);
		  $count = 0;
	   }
       
		$topic_id = $topics_id[$item['topic_id']];
		$post_date = datetime_to_int($item['post_date']);
		$hiden = ($item['hidden'] == 1) ? 1 : 0;


		mysql_query("INSERT INTO ".$lb_prefix."_posts SET 
		topic_id='$topic_id',
		new_topic='0',
		text='".mysql_escape_string(dle_to_lb($item['post_text'], $site_path))."',
		post_date='$post_date',     
		edit_date='".$item['edit_time']."',
		post_member_id='".get_member_id($item['post_author'])."',
		post_member_name='".$item['post_author']."',
		ip='".$item['post_ip']."',
		hide='$hiden',
		edit_member_id='".get_member_id($item['edit_user'])."',
		edit_member_name='".$item['edit_user']."',
		edit_reason='',
		fixed='0'
		") or die(mysql_error());
		$posts_id[$item['pid']] = mysql_insert_id();
		
		if((!isset($min_data[$topic_id])) || ($min_data[$topic_id]["time"] > $post_date))
		{
			$min_data[$topic_id]["time"] = $post_date; 
			$min_data[$topic_id]["pid"] = $posts_id[$item['pid']];
		}
		if((!isset($max_data[$topic_id])) || ($max_data[$topic_id]["time"] < $post_date))
			$max_data[$topic_id] = array("time" => $post_date, "pid" => $posts_id[$item['pid']]);
		$min_data[$topic_id]['hiden'] += $hiden;
	}	
	foreach($min_data as $tid=>$item)
	{
		$tid = $topics_id[$tid];
		mysql_query("UPDATE ".$lb_prefix."_posts SET new_topic='1' WHERE pid='".$item['pid']."'");
		mysql_query("UPDATE ".$lb_prefix."_topics SET post_hiden='".$item['hiden']."' WHERE id='$tid'");
	}
    
    sleep(4);
	mysql_select_db($lb_dbname);
	$sql_result = mysql_query("SELECT * FROM ".$lb_prefix."_posts");
	$result = fetch_array($sql_result);
	foreach($result as $item)
	{	
		if($item['hide'] == 0)
		{
			$topic_id = $item['topic_id'];
			$sql_result = mysql_query("SELECT hiden FROM ".$lb_prefix."_topics WHERE id='$topic_id'");
			$hiden_topic = mysql_result($sql_result, 0, 0);
			if($hiden_topic && $item['new_topic'] == 1)
				mysql_query("UPDATE ".$lb_prefix."_posts SET hide='1' WHERE pid='".$item['pid']."'");
		}
	}
	

	

	//update topics and forums
    $count = 0;
	foreach($max_data as $tid=>$item)
	{
	   $count++;
	   if ($count > 500)
	   {
		  sleep(4);
		  $count = 0;
	   }
		$last_post_id = $item['pid'];
		$sql_result = mysql_query("SELECT post_member_id FROM ".$lb_prefix."_posts WHERE pid='$last_post_id'") or die(mysql_error());
		$last_post_member_id = mysql_result($sql_result, 0, 0);
		$member_name_last = get_member_name($last_post_member_id);
		mysql_query("UPDATE ".$lb_prefix."_topics SET last_post_id='$last_post_id',last_post_member='$last_post_member_id',member_name_last='$member_name_last' WHERE id='$tid'") or die(mysql_error());
		
	}
    
    sleep(4);
	$sql_result = mysql_query("SELECT * FROM ".$lb_prefix."_posts WHERE new_topic='1'");
	$result = fetch_array($sql_result);
    $count = 0;
	foreach($result as $item)
	{
	   $count++;
	   if ($count > 500)
	   {
		  sleep(4);
		  $count = 0;
	   }
		$topic_id = $item['topic_id'];
		$user_id = $item['post_member_id'];
		$post_id = $item['pid'];
		$user_name = get_member_name($user_id);
		mysql_query("UPDATE ".$lb_prefix."_topics SET post_id='$post_id', member_id_open='$user_id',member_name_open='$user_name' WHERE id='$topic_id'");
	}
	sleep(4);
	$sql_result = mysql_query("SELECT * FROM ".$lb_prefix."_forums WHERE parent_id != '0'");
	$result = fetch_array($sql_result);
    $count = 0;
	foreach($result as $forum)
	{
	   
        $count++;
	   if ($count > 500)
	   {
		  sleep(4);
		  $count = 0;
	   }
       
		$last_topic_id = $forum['last_topic_id'];
		$sql_result = mysql_query("SELECT * FROM ".$lb_prefix."_posts WHERE topic_id='$last_topic_id'");
		$posts = fetch_array($sql_result);
		$max = $last_post_id = 0;
		foreach($posts as $item)
		{
			if($item['post_date'] >= $max)
				$last_post_id = $item['pid'];
		}
        sleep(4);
		$sql_result = mysql_query("SELECT * FROM ".$lb_prefix."_topics WHERE forum_id='".$forum['id']."'");
		$topics = fetch_array($sql_result);
		$hiden = $posts_hidden = $post_max = 0;
		foreach($topics as $item)
		{
			$hiden += $item['hiden'];
			$sql_result = mysql_query("SELECT * FROM ".$lb_prefix."_posts WHERE topic_id='".$item['id']."'");
			$posts = fetch_array($sql_result);
			foreach($posts as $post)
			{
				$posts_hidden += $post['hide'];
				if($post['post_date'] > $post_max)
				{
					$post_max = $post['post_date'];
					$last_post_id = $post['pid'];
				}
			}
            sleep(4);
		}

		mysql_query("UPDATE ".$lb_prefix."_forums SET topics_hiden='$hiden',posts_hiden='$posts_hidden',last_post_id='$last_post_id' WHERE id='".$forum['id']."'");
	}
    sleep(4);
	//banfilters
	mysql_select_db($dle_dbname);
	$sql_result = mysql_query("SELECT * FROM ".$dle_prefix."_banned  ORDER by id ASC");
	$result = fetch_array($sql_result);
	$begindate = time();
    $count = 0;
	foreach($result as $item)
	{
	   $count++;
	   if ($count > 500)
	   {
		  sleep(4);
		  $count = 0;
	   }
		mysql_select_db($lb_dbname);
		
		$user_id = $desc = $type = $ban_member_id = "";
		if($item['users_id'] != 0)
		{
			$user_id = $users_id[$item['users_id']];
			$descr = get_member_name($user_id);
			$type = "name";
			$ban_member_id = $user_id;
			mysql_query("INSERT INTO ".$lb_prefix."_members_banfilters SET 
			type='$type',
			description = '$descr',
			date='$begindate',
			moder_desc='".$item['descr']."',
			date_end='".$item['date']."',
			ban_days='".$item['days']."',
			ban_member_id='$ban_member_id'
			");
		}
		if($item['ip'] != "")
		{
			$ip = $item['ip'];
			if(preg_match('#(\d+)\.(\d+)\.(\d+)\.(\d+)#', $ip))
				$type = "ip";
			else if(strpos($ip, '@') !== false)
				$type = "email";
			else
				$type = "name";
			$descr = $ip;

			mysql_query("INSERT INTO ".$lb_prefix."_members_banfilters SET 
			type='$type',
			description = '$descr',
			date='$begindate',
			moder_desc='".$item['descr']."',
			date_end='".$item['date']."',
			ban_days='".$item['days']."',
			ban_member_id='$ban_member_id'
			");
		}
	}


sleep(4);
	//vote_logs
	mysql_select_db($dle_dbname);
	$sql_result = mysql_query("SELECT * FROM ".$dle_prefix."_forum_poll_log  ORDER by id ASC");
	$result = fetch_array($sql_result);
	mysql_select_db($lb_dbname);
    $count = 0;
	foreach($result as $item)
	{
	   $count++;
	   if ($count > 500)
	   {
		  sleep(4);
		  $count = 0;
	   }
		$user_id = $users_id[$item['member']];
		$user_name = get_member_name($user_id);
		$sql_result = mysql_query("SELECT ip FROM ".$lb_prefix."_members WHERE member_id='$user_id'");
		$user_ip = mysql_result($sql_result, 0, 0);
		$topic_id = $topics_id[$item['topic_id']];
		$mysql_result = mysql_query("SELECT poll_id FROM ".$lb_prefix."_topics WHERE id='$topic_id'");
		$poll_id = mysql_result($mysql_result, 0 ,0);
		mysql_query("INSERT INTO ".$lb_prefix."_topics_poll_logs SET 
		poll_id='$poll_id',
		ip = '$user_ip',
		member_id='$user_id',
		log_date='".time()."',
		answer='0',
		member_name='$user_name'
		");
		
	}
	


	sleep(4);
	//moderators
	mysql_select_db($dle_dbname);
	$sql_result = mysql_query("SELECT * FROM ".$dle_prefix."_forum_moderators  ORDER by mid ASC");
	$result = fetch_array($sql_result);
	mysql_select_db($lb_dbname);
    $count = 0;
	foreach($result as $item)
	{
	   $count++;
	   if ($count > 500)
	   {
		  sleep(4);
		  $count = 0;
	   }
		if($item['member_id'] == 0)
			continue;
		$group = $item['group_id'];
		$is_group = ($group > 0) ? 1 : 0;
		
		$permissions = array(
		"global_changepost" => $item['edit_post'],
		"global_titletopic" => $item['edit_topic'],
		"global_delpost" => $item['delete_post'],
		"global_deltopic" => $item['delete_topic'],
		"global_opentopic" => $item['open_topic'],
		"global_closetopic" => $item['close_topic'],
		"global_movetopic" => $item['move_topic'],
		"global_movepost" => $item['move_post'],
		"global_unionpost" => $item['combining_post'],
		"global_changepost" => $item['edit_post'],
		"global_hideshow" => '0',
		"global_polltopic" => '0',
		"global_fixtopic" => $item['pin_topic'],
		"global_hidetopic" => '0',
		"global_uniontopic" => '0',
		"global_fixedpost" => '0',
		"global_unfixtopic" => $item['unpin_topic']
		);
		$permissions = serialize($permissions);
		
		$member_id = $users_id[$item['member_id']];
		$mysql_result = mysql_query("SELECT member_group FROM ".$lb_prefix."_members WHERE member_id='$member_id'");
		$group = mysql_result($mysql_result, 0, 0);
		$group = $group < 6 ? $group : $item['group_id'] + 1;
		mysql_query("INSERT INTO ".$lb_prefix."_forums_moderator SET 
		fm_forum_id='".$forums_id[$item['forum_id']]."',
		fm_member_id='$member_id',
		fm_member_name='".get_member_name($member_id)."',
		fm_group_id='$group',
		fm_is_group='0',
		fm_permission='$permissions'
		");
	}
sleep(4);
	//member ranks
	mysql_select_db($dle_dbname);
	$sql_result = mysql_query("SELECT * FROM ".$dle_prefix."_forum_titles  ORDER by id ASC");
	$result = fetch_array($sql_result);
	mysql_select_db($lb_dbname);
    $count = 0;
	foreach($result as $item)
	{
$count++;
	   if ($count > 500)
	   {
		  sleep(4);
		  $count = 0;
	   }

		mysql_query("INSERT INTO ".$lb_prefix."_members_ranks SET 
		title='".$item['title']."',
		post_num = '".$item['posts']."',
		stars='".$item['pips']."'
		");
	}
	sleep(4);
	//subcribe
	mysql_select_db($dle_dbname);
	$sql_result = mysql_query("SELECT * FROM ".$dle_prefix."_forum_subscription  ORDER by sid ASC");
	$result = fetch_array($sql_result);
	mysql_select_db($lb_dbname);
    $count = 0;
	foreach($result as $item)
	{
	   $count++;
	   if ($count > 500)
	   {
		  sleep(4);
		  $count = 0;
	   }
		mysql_query("INSERT INTO ".$lb_prefix."_subscribe SET 
		subs_member='".$item['user_id']."',
		topic = '".$item['topic_id']."'
		");
	}
sleep(4);
    //files
    mysql_select_db($dle_dbname);
    $sql_result = mysql_query("SELECT * FROM ".$dle_prefix."_forum_files ORDER by file_id ASC");
    $result = fetch_array($sql_result);
    mysql_select_db($lb_dbname);
	$files_id = array();
    $count = 0;
    foreach($result as $file)
    {
        $count++;
	   if ($count > 500)
	   {
		  sleep(4);
		  $count = 0;
	   }
        $author_name = $file['file_author'];
        $author_id = get_member_id($author_name);
        $topic_id = $topics_id[$file['topic_id']];
        $sql_result = mysql_query("SELECT forum_id FROM ".$lb_prefix."_topics WHERE id='$topic_id'");
        $forum_id = mysql_result($sql_result, 0, 0);
		$file_type = $file['file_type'];
		if($file_type=="thumb")
				$file_type="picture";
        mysql_query("INSERT INTO ".$lb_prefix."_topics_files SET
        file_title='".$file['file_name']."',
        file_name='".$file['onserver']."',
        file_type='$file_type',
        file_mname='$author_name',
        file_mid='$author_id',
        file_date='".$file['file_date']."',
        file_size='".$file['file_size']."',
        file_count='".$file['dcount']."',
        file_tid='$topic_id',
        file_fid='$forum_id',
        file_convert='1',
        file_pid='".$posts_id[$file['post_id']]."'") or die(mysql_error());
		
		$files_id[$file['file_id']] = mysql_insert_id();
    }
	sleep(4);
	//update posts
    mysql_select_db($lb_dbname);
    $sql_result = mysql_query("SELECT * FROM ".$lb_prefix."_posts");
    $result = fetch_array($sql_result);
    $count = 0;
    foreach($result as $post)
    {
        $count++;
	   if ($count > 500)
	   {
		  sleep(4);
		  $count = 0;
	   }
        $text = $post['text'];
        preg_match('#attachment=(\d+)#sui', $text, $item);
        if($item)
        {
			
            $file_id = $files_id[$item[1]]; 
            $text = mysql_escape_string(str_replace("[attachment=".$item[1]."]", "[attachment=$file_id]", $text));
            mysql_query("UPDATE ".$lb_prefix."_posts SET text='$text' WHERE pid='".$post['pid']."'") or die(mysql_error());
        }
    }
	//reputation
	mysql_select_db($dle_dbname);
	$sql_result = mysql_query("SELECT * FROM ".$dle_prefix."_forum_reputation_log ORDER by rid ASC");
	$result = fetch_array($sql_result);
	mysql_select_db($lb_dbname);
    $count = 0;
	foreach($result as $item)
	{
       mysql_select_db($lb_dbname);
	   $count++;
	   if ($count > 500)
	   {
		  sleep(4);
		  $count = 0;
	   }
		mysql_query("INSERT INTO ".$lb_prefix."_members_reputation SET 
		from_id='".get_member_id($item['author'])."',
		from_name='".$item['author']."',
		to_id='".$users_id[$item['mid']]."',
		to_name='".get_member_name($users_id[$item['mid']])."',
		date='".$item['date']."',
		how = '".(($item['action'] == '-') ? '-1' : '+1')."',
		text = '".$item['cause']."'
		");
	}
	

	
	//update attachments at posts
	mysql_select_db($lb_dbname);
	$sql_result = mysql_query("SELECT * FROM ".$lb_prefix."_posts");
	$posts = fetch_array($sql_result);
    $count = 0;
	foreach($posts as $post)
	{
	   $count++;
	   if ($count > 500)
	   {
		  sleep(4);
		  $count = 0;
	   }
		$post_id = $post['pid'];
		$sql_result = mysql_query("SELECT * FROM ".$lb_prefix."_topics_files WHERE file_pid='$post_id'") or die(mysql_error());
		$files = fetch_array($sql_result);
		$text = '';
		foreach($files as $file)
			$text.=$file['file_id'].",";
		if(strlen($text) > 0)
			$text = substr($text, 0, -1);

		mysql_query("UPDATE ".$lb_prefix."_posts SET attachments='$text' WHERE pid='".$post['pid']."'") or die(mysql_error());
	}


    //punishments
    mysql_select_db($dle_dbname);
    $sql_result = mysql_query("SELECT * FROM ".$dle_prefix."_forum_warn_log");
    $warns = fetch_array($sql_result);
    mysql_select_db($lb_dbname);
    foreach($warns as $warning)
    {
        if($warning['action'] == '+')
        {
            $user_id = $users_id[$warning['mid']];
            $moder_name = $warning['author'];
            $moder_id = get_member_id($moder_name);
            $cause = $warning['cause'];
            $date = $warning['date'];
            mysql_query("INSERT INTO ".$lb_prefix."_members_warning SET mid='$user_id', moder_id='$moder_id', moder_name='$moder_name', date='$date', description='$cause',st_w='1'");
        }
    }

    mysql_select_db($dle_dbname);
    $sql_result = mysql_query("SELECT * FROM ".$dle_prefix."_forum_warn_log");
    $warns = fetch_array($sql_result);
    mysql_select_db($lb_dbname);
    foreach($warns as $warning)
    {
        if($warning['action'] == '-')
        {
            $sql_result = mysql_query("SELECT * FROM ".$lb_prefix."_members_warning");
            $lb_warns = fetch_array($sql_result);
            if($lb_warns)
            {
                $min_date = 2000000000;
                $min_id = -1;
                foreach($lb_warns as $lb_warn)
                {
                    if($lb_warn['st_w'] == 0)continue;
                    if($lb_warn['date'] < $min_date)
                    {
                        $min_date = $lb_warn['date'];
                        $min_id = $lb_warn['id'];
                    }
                }
                if($min_id == -1)break;
                mysql_query("UPDATE ".$lb_prefix."_members_warning SET st_w='0' WHERE id='$min_id'");
           }
        }
    }

    mysql_select_db($lb_dbname);
    $sql_result = mysql_query("SELECT * FROM ".$lb_prefix."_members");
    $users = fetch_array($sql_result);
    foreach($users as $user)
    {
        $user_id = $user['member_id'];
        $sql_result = mysql_query("SELECT * FROM ".$lb_prefix."_members_warning WHERE mid='$user_id' AND st_w='1'");
        $result = fetch_array($sql_result);
        $count = count($result);

        mysql_query("UPDATE ".$lb_prefix."_members SET count_warning='$count' WHERE member_id='$user_id'");
    }
	
	return "NO_ERROR";
}
	
	
?>


<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8" />
    <title>Конвертор DLE-Forum -> Lb-Forum</title>
    <link rel="stylesheet" href="style.css"/>
    <style>
    * {
        font-family: sans-serif;
        font-size: 14px;
        color: #444;
    }
    input {
        border: 1px solid #777;
    }
    input:hover {
        border: 1px solid #444;
    }
    input:focus {
        border: 1px solid #222;
    }
    
    #page {
        margin: auto;
        display: block;
        width: 570px;
        overflow: hidden;
    }
    .header 
    {
        font-size:25px;
        margin:0 auto;
        display: block;
        text-align:center;
    }
    input[type=submit] 
    {
        margin:0 auto;
        display:block;
    }
    div.mysql-setup
    {
        width:200px;
        margin:0 auto;
    }
    div
    {
        padding-bottom:10px;
    }
    </style>
</head>
<body>
	
<?php
    if(isset($_POST['convert_submit']))
    {

        $result = convert($_POST['mysql_host'], $_POST['mysql_user'], $_POST['mysql_password'], $_POST['dle_dbname'],
            $_POST['dle_prefix'], $_POST['lb_dbname'], $_POST['lb_prefix'], $_POST['site_path']);
		if($result == "MYSQL_ERROR")
			$error_text = "Ошибка подключения к MySQL";
		else if($result == "DLE_NO_FOUND")
			$error_text = "Не найдена база данных DLE";
		else if($result == "LB_NO_FOUND")
			$error_text = "Не найдена база данных LB";
        else if($result == "BAD_SITEPATH")
            $error_text = "Неверно указан адрес сайта";
		else if($result == "NO_ERROR")
		{
			$error_text = "";
			echo "Форум успешно перенесён. Удалите этот файл!";
		}        else
            $error_text = $result;
        if($error_text != "")
            echo $error_text;
		exit();
    }
?>


<form action="" method="POST">
<div id="page">
    <div class="content">
        <span class="header">Конвертор DLE-Forum ---> Lb-Forum</span>
        <div class="mysql-setup">
            <table>
                <tr>
                    <td colspan="2">MySQL</td>
                </tr>
                <tr>
                    <td>Сервер</td>
                    <td><input type="text" name="mysql_host" value="<?echo @$_POST['mysql_host']?>"/></td>
                </tr>
                <tr>
                    <td>Пользователь</td>
                    <td><input type="text" name="mysql_user"/ value="<?echo @$_POST['mysql_user']?>"></td>
                </tr>
                <tr>
                    <td>Пароль</td>
                    <td><input type="text" name="mysql_password"/ value="<?echo @$_POST['mysql_password']?>"></td>
                </tr>
            </table>
        </div>
		<div style="overflow:hidden">
        <div style="float:left">
            <table>
                <tr>
                    <td colspan="2">DLE-Forum</td>
                </tr>
                <tr>
                    <td>База данных</td>
                    <td><input type="text" name="dle_dbname"/></td>
                </tr>
                <tr>
                    <td>Префикс таблиц</td>
                    <td><input type="text" name="dle_prefix"/></td>
                </tr>
            </table>
        </div>
        <div style="float:right">
            <table>
                <tr>
                    <td colspan="2">Lb-Forum</td>
                </tr>
                <tr>
                    <td>База данных</td>
                    <td><input type="text" name="lb_dbname"/></td>
                </tr>
                <tr>
                    <td>Префикс таблиц</td>
                    <td><input type="text" name="lb_prefix"/></td>
                </tr>
                <tr>
                    <td>Адрес форума</td>
                    <td><input type="text" name="site_path"></td>
                </tr>
            </table>
        </div></div>
        <div><input name="convert_submit" type="submit" value="Конвертировать"/></div>
    </div>
</div>
</form>
</body>
</html>

