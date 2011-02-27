<?php

set_time_limit(0);
ini_set('memory_limit', '512M');

require_once "include/parser.php";


$status = array(
    "MYSQL_FROM_ERROR" => "Невозможно подлючиться к MySQL серверу IPB форума",
    "MYSQL_TO_ERROR" => "Невозможно подлючиться к MySQL серверу LogicBoard",
    "FROM_DB_NOFOUND" => "Не найдена база данных IPB форума",
    "TO_DB_NOFOUND" => "Не найдена база данных LogicBoard",
    "BAD_SITEPATH" => "Неправильный формат адреса сайта",
    "NO_ERROR" => "Форум успешно перенесён. Удалите этот файл!",
);
   		

$last_limit_value = 0;
$last_limit_query = "";

$lb_prefix = $lb_dbname = $tws_prefix = $tws_dbname = $ipb_prefix = "";

$limit_count = 1000;

function fetch_array($sql_result)
{
    for ($result = array(); $row = mysql_fetch_array($sql_result); $result[] = $row) ;
    return $result;
}

function get_limit_query($query, $sql_hanipb)
{
    global $limit_count, $last_limit_query, $last_limit_value;
    if ($query != $last_limit_query) {
        $last_limit_value = 0;
        $last_limit_query = $query;
    }
    $start = $last_limit_value;
    $finish = $start + $limit_count;
    $last_limit_value = $finish;
    $sql_result = mysql_query($query . " LIMIT " . $start . "," . $limit_count, $sql_hanipb);
    return $sql_result;
}

function translit($str)
{
    $tr = array(
        "А" => "A", "Б" => "B", "В" => "V", "Г" => "G",
        "Д" => "D", "Е" => "E", "Ж" => "J", "З" => "Z", "И" => "I",
        "Й" => "Y", "К" => "K", "Л" => "L", "М" => "M", "Н" => "N",
        "О" => "O", "П" => "P", "Р" => "R", "С" => "S", "Т" => "T",
        "У" => "U", "Ф" => "F", "Х" => "H", "Ц" => "TS", "Ч" => "CH",
        "Ш" => "SH", "Щ" => "SCH", "Ъ" => "", "Ы" => "YI", "Ь" => "",
        "Э" => "E", "Ю" => "YU", "Я" => "YA", "а" => "a", "б" => "b",
        "в" => "v", "г" => "g", "д" => "d", "е" => "e", "ж" => "j",
        "з" => "z", "и" => "i", "й" => "y", "к" => "k", "л" => "l",
        "м" => "m", "н" => "n", "о" => "o", "п" => "p", "р" => "r",
        "с" => "s", "т" => "t", "у" => "u", "ф" => "f", "х" => "h",
        "ц" => "ts", "ч" => "ch", "ш" => "sh", "щ" => "sch", "ъ" => "y",
        "ы" => "yi", "ь" => "", "э" => "e", "ю" => "yu", "я" => "ya"
    );
    return str_replace(" ", "-", strtr($str, $tr));
}


function check_url($url)
{
    $preg = '#http://.+?/$#sui';
    return preg_match($preg, $url);
}

function get_member_id($name)
{
    global $lb_prefix, $lb_dbname, $sql_to;
    mysql_select_db($lb_dbname, $sql_to);
    $sql_result = mysql_query("SELECT member_id FROM ".$lb_prefix."_members WHERE name ='$name'", $sql_to) or die(mysql_error());
    $result = @mysql_result($sql_result, 0, 0);
    return ($result) ? $result : 0;
}

function get_member_name($id)
{
    global $lb_prefix, $lb_dbname, $sql_to;
    mysql_select_db($lb_dbname, $sql_to);
    $sql_result = mysql_query("SELECT name FROM ".$lb_prefix."_members WHERE member_id = '$id'", $sql_to) or die(mysql_error());
    $result = @mysql_result($sql_result, 0, 0);
    return ($result) ? $result : "Удалён";
}

function convert($params)
{

    $lb_prefix_ = $params['to_db_prefix'];
    $ipb_prefix_ = $params['from_db_prefix'];
    $lb_dbname_ = $params['to_db_name'];
    $ipb_dbname_ = $params['from_db_name'];
    $site_path = $params['to_site_path'];

    $GLOBALS['lb_prefix'] = $lb_prefix_;
    $GLOBALS['ipb_prefix'] = $ipb_prefix_;
    $GLOBALS['lb_dbname'] = $lb_dbname_;
    $GLOBALS['ipb_dbname'] = $ipb_dbname_;

    global $lb_prefix, $lb_dbname, $ipb_dbname, $ipb_prefix, $last_limit_value;

    if (!check_url($site_path))
        return "BAD_SITEPATH";

    $sql_from = mysql_connect($params['from_mysql_host'], $params['from_mysql_login'], $params['from_mysql_password']);
    $sql_to = mysql_connect($params['to_mysql_host'], $params['to_mysql_login'], $params['to_mysql_password']);

    if ($sql_from == FALSE) return "MYSQL_FROM_ERROR";
    if ($sql_to == FALSE) return "MYSQL_TO_ERROR";

    $GLOBALS['sql_from'] = $sql_from;
    $GLOBALS['sql_to'] = $sql_to;
    global $sql_to, $sql_from;


    if (mysql_select_db($params['from_db_name'], $sql_from) == FALSE) return "FROM_DB_NOFOUND";
    if (mysql_select_db($params['to_db_name'], $sql_to) == FALSE) return "TO_DB_NOFOUND";

    mysql_query("SET NAMES UTF8", $sql_from) or die(mysql_error());
    mysql_query("SET NAMES UTF8", $sql_to) or die(mysql_error());

    echo "Install...";
    include "install.php";
    install($sql_to, $lb_dbname, $lb_prefix);
    echo "OK<br/>";

    $groups_key = array("4"=>"1","6"=>"2","3"=>"4","2"=>"5","1"=>"6");

    $groups_masks = $users_id = $forums_id = $topics_id = $posts_id = $posts_key = $posts_attachment = $users_ban = $users_group = array();


    echo "Groups...";
    mysql_select_db($ipb_dbname, $sql_from);
    $sql_result = mysql_query("SELECT * FROM ".$ipb_prefix."_groups", $sql_from) or die(mysql_error());
    $groups = fetch_array($sql_result);
    mysql_select_db($lb_dbname, $sql_to);
    foreach($groups as $group)
    {
        if($group['g_id'] == '5')continue;
        $lb_group_id = isset($groups_key[$group['g_id']]) ? $groups_key[$group['g_id']] : $group['g_id'];

        $access = array();
        $access['local_opentopic'] = $group['g_open_close_posts'];
        $access['local_closetopic'] = $group['g_open_close_posts'];
        $access['local_deltopic'] = $group['g_delete_own_topics'];
        $access['local_titletopic'] = $group['g_edit_topic'];
        $access['local_polltopic'] = $group['g_edit_topic'];
        $access['local_delpost'] = $group['g_delete_own_posts'];
        $access['local_changepost'] = $group['g_edit_posts'];

        mysql_query("INSERT INTO ".$lb_prefix."_groups SET
        g_id = '$lb_group_id',
        g_title = '".mysql_escape_string($group['g_title'])."',
        g_prefix_st = '".mysql_escape_string($group['prefix'])."',
        g_supermoders = '".$group['g_is_supmod']."',
        g_access_cc = '".$group['g_access_cp']."',
        g_show_close_f = '".$group['g_access_offline']."',
        g_access  = '".serialize($access)."',
        g_show_profile = '".$group['g_mem_info']."',
        g_new_topic = '".$group['g_post_new_topics']."',
        g_reply_topic = '".$group['g_reply_other_topics']."',
        g_reply_close = '".$group['g_post_closed']."',
        g_pm = '".$group['g_use_pm']."',
        g_maxpm = '".$group['g_max_messages']."',
        g_search = '".$group['g_use_search']."',
        g_prefix_end = '".mysql_escape_string($group['suffix'])."'"
           , $sql_to) or die(mysql_error());
        $groups_masks[$group['g_id']] = explode(',',$group['g_perm_id']);
    }
    echo "OK<br/>";


    echo "Users...";
    mysql_select_db($ipb_dbname, $sql_from);
    $sql_result = mysql_query("SELECT * FROM ".$ipb_prefix."_members", $sql_from) or die(mysql_error());
    $users = fetch_array($sql_result);
    mysql_select_db($lb_dbname, $sql_to);
    foreach($users as $user)
    {
        if($user['mgroup'] == 5)continue;
        $group = (isset($groups_key[$user['mgroup']])) ? $groups_key[$user['mgroup']] : $user['mgroup'];

        mysql_query("INSERT INTO ".$lb_prefix."_members SET
        name = '".mysql_escape_string($user['name'])."',
        email = '".mysql_escape_string($user['email'])."',
        reg_date = '".$user['join']."',
        member_group = '$group',
        password = '".$user['member_login_key']."',
        secret_key = '',
        ip = '".$user['ip_address']."',
        personal_title = '".mysql_escape_string($user['title'])."',
        reg_status = '1',
        lastdate = '".$user['last_visit']."',
        b_day = '".$user['bday_day']."',
        b_month = '".$user['bday_month']."',
        b_year = '".$user['bday_year']."',
        count_warning = '".$user['warn_level']."',
        posts_num = '".$user['posts']."'"
        ,$sql_to) or die(mysql_error());
        $users_id[$user['id']] = mysql_insert_id();
        $users_group[mysql_insert_id()] = $group;

        if($user['temp_ban'] != "")
            $users_ban[mysql_insert_id()] = $user['temp_ban'];
    }
    mysql_select_db($ipb_dbname, $sql_from);
    $sql_result = mysql_query("SELECT * FROM ".$ipb_prefix."_member_extra", $sql_from) or die(mysql_error());
    $users_extra = fetch_array($sql_result);
    mysql_select_db($lb_dbname, $sql_to);
    foreach($users_extra as $info)
    {
        $user_id = $users_id[$info['id']];
        mysql_query("UPDATE ".$lb_prefix."_members SET
        icq = '".$info['icq_number']."',
        signature = '".mysql_escape_string($info['signature'])."',
        town = '".mysql_escape_string($info['location'])."'
         WHERE member_id = '$user_id'", $sql_to) or die(mysql_error());
    }
    mysql_select_db($ipb_dbname, $sql_from);
    $sql_result = mysql_query("SELECT * FROM ".$ipb_prefix."_profile_portal", $sql_from) or die(mysql_error());
    $users_extra = fetch_array($sql_result);
    mysql_select_db($lb_dbname, $sql_to);
    foreach($users_extra as $info)
    {
        $user_id = $users_id[$info['pp_member_id']];
        $sex = 0;
        if($item['gender'] == 'male')$sex = 1;
        if($item['gender'] == 'female')$sex = 2;
        mysql_query("UPDATE ".$lb_prefix."_members SET
        about = '".mysql_escape_string($info['pp_bio_content'])."',
        sex = '$sex'
         WHERE member_id = '$user_id'", $sql_to) or die(mysql_error());
    }
    echo "OK<br/>";

    echo "Categories...";
    mysql_select_db($ipb_dbname, $sql_from);
    $sql_result = mysql_query("SELECT * FROM ".$ipb_prefix."_forums WHERE parent_id = '-1'") or die(mysql_error());
    $categories = fetch_array($sql_result);
    mysql_select_db($lb_dbname, $sql_to);
    foreach($categories as $category)
    {
        mysql_query("INSERT INTO " . $lb_prefix . "_forums SET
		parent_id='0',
		title='" . mysql_escape_string($category['name']) . "',
		alt_name='" . mysql_escape_string(translit($category['name'])) . "',
		postcount='".$category['inc_postcount']."',
		posi='" . $category['position'] . "'"
            , $sql_to) or die(mysql_error());
        $forums_id[$category['id']] = mysql_insert_id();
    }
    echo "OK<br/>";

    mysql_select_db($ipb_dbname, $sql_from);
    $sql_result = mysql_query("SELECT * FROM ".$ipb_prefix."_forum_perms", $sql_from) or die(mysql_error());
    $group_masks_ = fetch_array($sql_result);
    $group_masks = array();
    foreach($group_masks_ as $group)
        $group_masks[] = $group['perm_id'];

    echo "Forums...";
    mysql_select_db($ipb_dbname, $sql_from);
    $sql_result = mysql_query("SELECT * FROM ".$ipb_prefix."_forums WHERE parent_id != '-1' ORDER BY id ASC", $sql_from) or die(mysql_error());
    $forums = fetch_array($sql_result);
    mysql_select_db($lb_dbname, $sql_to);
    $group_template = array();
    $group_names = array("read_forum","read_theme","creat_theme","answer_theme","upload_files","download_files");
    $sql_result = mysql_query("SELECT * FROM ".$lb_prefix."_groups ORDER BY g_id ASC", $sql_to) or die(mysql_error());
    $groups = fetch_array($sql_result);
    foreach($groups as $group)
    {
        $id = $group['g_id'];
        $group_template[$id] = array();
        foreach($group_names as $name)
            $group_template[$id][$name] = 0;
    }
    foreach($forums as $forum)
    {
        $old_permissions = unserialize(str_replace('\\','',$forum['permission_array']));
        $permissions = $group_template;
        $mask_permissions = array();

        foreach ($group_masks as $mask_id)
        {
            $mask_permissions[$mask_id] = array();
            foreach($old_permissions as $item_key => $item_value)
                $mask_permissions[$mask_id][$item_key] = 0;
        }
        foreach ($old_permissions as $key => $value)
        {
            if(strlen($value) == 0)
                continue;
            if($value == "*")
            {
                $value = "";
                foreach($group_masks as $group)
                    $value .= $group .",";
                $value = substr($value, 0, -1);
            }
            $value = explode(",", $value);
            foreach($value as $item)
                $mask_permissions[$item][$key] = 1;
        }
        foreach($groups_masks as $group_id => $group_value)
        {
            if($group_id == 5)continue;
            $lb_group_id = (isset($groups_key[$group_id])) ? $groups_key[$group_id] : $group_id;
            foreach($groups_masks[$group_id] as $mask_id)
            {
                $mask = $mask_permissions[$mask_id];
                foreach($mask as $mask_key => $mask_value)
                {
                    if($mask_value == 0)
                        continue;
                    if($mask_key == "start_perms")
                        $permissions[$lb_group_id]['creat_theme'] = 1;
                    if($mask_key == "reply_perms")
                        $permissions[$lb_group_id]['answer_theme'] = 1;
                    if($mask_key == "read_perms")
                        $permissions[$lb_group_id]['read_theme'] = 1;
                    if($mask_key == "upload_perms")
                        $permissions[$lb_group_id]['upload_files'] = 1;
                    if($mask_key == "download_perms")
                        $permissions[$lb_group_id]['download_files'] = 1;
                    if($mask_key == "show_perms")
                        $permissions[$lb_group_id]['read_forum'] = 1;

                }
            }
        }
        foreach($permissions[3] as $key=>$value)
            $permissions[3][$key] = 1;


        $sort_order = $forum['sort_order'] == 'Z-A' ? 'DESC' : 'ASC';
        mysql_query("INSERT INTO " . $lb_prefix . "_forums SET
            posi='" . $forum['position'] . "',
            parent_id='" . $forums_id[$forum['parent_id']] . "',
            title='" . mysql_escape_string($forum['name']) . "',
            alt_name='" . mysql_escape_string(translit($forum['name'])) . "',
            description='" . mysql_escape_string($forum['description']) . "',
            allow_bbcode='1',
            allow_poll='" . $forum['allow_poll'] . "',
            postcount='" . $forum['inc_postcount'] . "',
            topics_hiden='" . $forum['queued_topics'] . "',
            posts_hiden='" . $forum['queued_posts'] . "',
           	group_permission = '".serialize($permissions)."',
            last_post_member='" . $forum['last_poster_name'] . "',
            last_post_member_id='" . $users_id[$forum['last_poster_id']] . "',
            last_title='" . $forum['last_title'] . "',
            sort_order='$sort_order',
            posts='" . $forum['posts'] . "',
            rules='" . $forum['rules_text '] . "'
            ", $sql_to) or die(mysql_error());
        $forums_id[$forum['id']] = mysql_insert_id();
    }
    echo "OK<br/>";

    echo "Topics...";
    mysql_select_db($ipb_dbname, $sql_from);
    $sql_result = mysql_query("SELECT * FROM ".$ipb_prefix."_topics ORDER BY tid ASC") or die(mysql_error());
    $topics = fetch_array($sql_result);
    mysql_select_db($lb_dbname, $sql_to);
    foreach($topics as $topic)
    {
        mysql_query("INSERT INTO ". $lb_prefix. "_topics SET
        forum_id = '".$forums_id[$topic['forum_id']]."',
        title  = '".mysql_escape_string($topic['title'])."',
        description  = '".mysql_escape_string($topic['description'])."',
        date_open = '".$topic['start_date']."',
        date_last = '".$topic['last_post']."',
        status = '".$topic['state']."',
        post_num = '".$topic['posts']."',
        post_hiden = '".$topic['topic_queuedposts']."',
        hiden = '".$topic['approved']."',
        fixed = '".$topic['pinned']."',
        views = '".$topic['views']."',
        last_post_member  = '".$topic['last_poster_id']."',
        member_name_open  = '".$topic['starter_name']."',
        member_id_open  = '".$topic['starter_id']."',
        member_name_last  = '".$topic['last_poster_name']."'
        ", $sql_to) or die(mysql_error());
        $topics_id[$topic['tid']] = mysql_insert_id();
    }
    echo "OK<br/>";

    echo "Posts...";
    mysql_select_db($ipb_dbname, $sql_from);
    $sql_result = mysql_query("SELECT * FROM ".$ipb_prefix."_posts ORDER BY pid ASC") or die(mysql_error());
    $posts = fetch_array($sql_result);
    mysql_select_db($lb_dbname, $sql_to);
    foreach($posts as $post)
    {
        $user_id = $users_id[$post['author_id']];
        mysql_query("INSERT INTO ".$lb_prefix."_posts SET
        topic_id = '".$topics_id[$post['topic_id']]."',
        new_topic = '".$post['new_topic']."',
        text = '".mysql_escape_string(ipb_to_lb($post['post'], $site_path))."',
        post_date = '".$post['post_date']."',
        edit_date = '".$post['edit_time']."',
        post_member_id = '".$user_id."',
        post_member_name = '".get_member_name($user_id)."',
        ip = '".$post['ip_address']."',
		hide = '".$post['queued']."',
        edit_member_name = '".$post['edit_name']."',
        edit_member_id = '".get_member_id($post['edit_name'])."',
        edit_reason = '".mysql_escape_string($post['post_edit_reason'])."'
        ", $sql_to) or die(mysql_error());
        $posts_id[$post['pid']] = $posts_key[$post['post_key']] = mysql_insert_id();
        $posts_attachment[mysql_insert_id()] = 0;
    }
    echo "OK<br>";

	echo "Update posts, topics, forums...";
    mysql_select_db($ipb_dbname, $sql_from);
    $sql_result = mysql_query("SELECT * FROM ".$ipb_prefix."_topics") or die(mysql_error());
    $topics = fetch_array($sql_result);
    mysql_select_db($lb_dbname, $sql_to);
    foreach($topics as $topic)
    {
        $first_post = $posts_id[$topic['topic_firstpost']];
        $topic_id = $topics_id[$topic['tid']];
        mysql_query("UPDATE ".$lb_prefix."_topics SET post_id = '$first_post' WHERE id = '$topic_id'", $sql_to) or die(mysql_error());
        $sql_result = mysql_query("SELECT * FROM ".$lb_prefix."_posts WHERE topic_id = '$topic_id'", $sql_to) or die(mysql_error());
        $posts = fetch_array($sql_result);
        $max_time = 0;
		$max_id = -1;
        foreach($posts as $post)
            if($post['post_date'] > $max_time)
            {
                $max_time = $post['post_date'];
                $max_id = $post['pid'];
            }
        mysql_query("UPDATE ".$lb_prefix."_topics SET last_post_id='$max_id' WHERE id='$topic_id'", $sql_to) or die(mysql_error());
    }
	
	mysql_select_db($ipb_dbname, $sql_from);
	$sql_result = mysql_query("SELECT * FROM ".$ipb_prefix."_forums WHERE parent_id != '-1'", $sql_from) or die(mysql_error());
	$forums = fetch_array($sql_result);
	mysql_select_db($lb_dbname, $sql_to);
	foreach($forums as $forum)
	{
		$last_topic_id = $topics_id[$forum['last_id']];
		$forum_id = $forums_id[$forum['id']];
		mysql_query("UPDATE ".$lb_prefix."_forums SET last_topic_id='$last_topic_id' WHERE id = '$forum_id'", $sql_to) or die(mysql_error());
	}
	
	mysql_select_db($lb_dbname, $sql_to);
	$sql_result = mysql_query("SELECT * FROM ".$lb_prefix."_forums WHERE parent_id != 0") or die(mysql_error());
	$forums = fetch_array($sql_result);
	foreach($forums as $forum)
	{
		$sql_result = mysql_query("SELECT * FROM ".$lb_prefix."_topics WHERE forum_id='".$forum['id']."'", $sql_to) or die(mysql_error());
		$topics = fetch_array($sql_result);
		foreach($topics as $topic)
		{
			$sql_result = mysql_query("SELECT * FROM ".$lb_prefix."_posts WHERE topic_id='".$topic['id']."'", $sql_to) or die(mysql_error());
			$posts = fetch_array($sql_result);
			$last_post_time = 0;
			$last_post_id = -1;
			foreach($posts as $post)
			{
				if($post['post_date'] > $last_post_time)
				{
					$last_post_time = $post['post_date'];
					$last_post_id = $post['pid'];
				}
			}
			mysql_query("UPDATE ".$lb_prefix."_forums SET last_post_date='$last_post_time', last_post_id='$last_post_id'
				WHERE id ='".$forum['id']."'", $sql_to) or die(mysql_error());
		}
		mysql_query("UPDATE ".$lb_prefix."_forums SET topics='".count($topics)."' WHERE id = '".$forum['id']."'", $sql_to) or die(mysql_error());
	}

    echo "OK<br/>";
    echo "Polls...";

    mysql_select_db($ipb_dbname, $sql_from);
    $sql_result = mysql_query("SELECT * FROM ".$ipb_prefix."_polls", $sql_from) or die(mysql_error());
    $polls = fetch_array($sql_result);
    mysql_select_db($lb_dbname, $sql_to);
    foreach($polls as $poll)
    {
        $topic_id = $topics_id[$poll['tid']];
        $choises_text = stripslashes($poll['choices']);
        preg_match('#(.+?)"votes"#sui', $choises_text, $text);
        preg_match_all('#"(.+?)"#sui', $text[1], $choises);
        $question = $choises[1][1];
        $variants = "";
        $multiple = ($choises[1][2] == "multi") ? 1 : 0;
        $beg = ($choises[1][2] == "multi") ? 4 : 3;
        for($i = $beg; $i < count($choises[1]); $i++)
        {
            $variants .= $choises[1][$i];
            if($i < count($choises[1]) - 1)
                $variants .= "\r\n";
        }
        preg_match('#"votes"(.+?)}#sui', $choises_text, $choises_text);
        $answers_text = "";
        preg_match_all('#i:\d+;i:(\d+);#sui', $choises_text[1], $answers);
        for($i = 0; $i < count($answers[1]); $i++)
            if($answers[1][$i] != 0)
                $answers_text .= $i.":".$answers[1][$i]."|";
        if(strlen($answers_text) > 0)
            $answers_text = substr($answers_text, 0, -1);


        mysql_query("INSERT INTO ".$lb_prefix."_topics_poll SET
        tid='$topic_id',
        vote_num='".$poll['votes']."',
        open_date='".$poll['start_date']."',
        title='".mysql_escape_string($poll['poll_question'])."',
        question='".mysql_escape_string($question)."',
        multiple='".$multiple."',
        variants ='".mysql_escape_string($variants)."',
        answers ='".mysql_escape_string($answers_text)."'"

        , $sql_to) or die(mysql_error());

        mysql_query("UPDATE ".$lb_prefix."_topics SET poll_id='".mysql_insert_id()."' WHERE id='$topic_id'", $sql_to) or
                    die(mysql_error());
    }

    echo "OK<br>";
    echo "Poll logs...";

    mysql_select_db($ipb_dbname, $sql_from);
    $sql_result = mysql_query("SELECT * FROM ".$ipb_prefix."_voters", $sql_from) or die(mysql_error());
    $logs = fetch_array($sql_result);
    mysql_select_db($lb_dbname, $sql_to);
    foreach($logs as $log)
    {
        $sql_result = mysql_query("SELECT poll_id FROM ".$lb_prefix."_topics WHERE id='".$topics_id[$log['tid']]."'", $sql_to) or die(mysql_error());
        $poll_id = mysql_result($sql_result, 0, 0);
        mysql_query("INSERT INTO ".$lb_prefix."_topics_poll_logs SET
        poll_id = '$poll_id',
        ip = '".$log['ip_address']."',
        log_date = '".$log['vote_date']."',
        member_id = '".$users_id[$log['member_id']]."',
        member_name = '".get_member_name($users_id[$log['member_id']])."'
        ", $sql_to) or die(mysql_error());
    }

    echo "OK<br/>";
    echo "Attachments...";

    mysql_select_db($ipb_dbname, $sql_from);
    $sql_result = mysql_query("SELECT * FROM ".$ipb_prefix."_attachments", $sql_from) or die(mysql_error());
    $attachments = fetch_array($sql_result);
    mysql_select_db($lb_dbname, $sql_to);
    foreach($attachments as $attachment)
    {
        $post_id = $posts_key[$attachment['attach_post_key']];
        if(!$post_id)
            continue;

        $sql_result = mysql_query("SELECT topic_id FROM ".$lb_prefix."_posts WHERE pid='$post_id'", $sql_to) or die(mysql_error());
        $topic_id = mysql_result($sql_result, 0, 0);
        $sql_result = mysql_query("SELECT forum_id FROM ".$lb_prefix."_topics WHERE id='$topic_id'", $sql_to) or die(mysql_error());
        $forum_id = mysql_result($sql_result, 0, 0);

        mysql_query("INSERT INTO ".$lb_prefix."_topics_files SET
        file_title = '".mysql_escape_string($attachment['attach_file'])."',
        file_name = '".mysql_escape_string($attachment['attach_location'])."',
        file_type = '".$attachment['attach_ext']."',
        file_mname = '".get_member_name($users_id[$attachment['attach_member_id']])."',
        file_mid = '".$users_id[$attachment['attach_member_id']]."',
        file_date = '".$attachment['attach_date']."',
        file_size = '".$attachment['attach_filesize']."',
        file_fid = '$forum_id',
        file_tid = '$topic_id',
        file_pid = '$post_id'
        ", $sql_to) or die(mysql_error());

        $sql_result = mysql_query("SELECT text FROM ".$lb_prefix."_posts WHERE pid='$post_id'", $sql_to) or die(mysql_error());
        $text = mysql_result($sql_result, 0, 0);
        if($posts_attachment[$post_id] == 0)
        {
            $text .= "<br/>";
            $posts_attachment[$post_id] = 1;
        }
        $text .= "<br/>[attachment=".mysql_insert_id()."]";
        mysql_query("UPDATE ".$lb_prefix."_posts SET text='".mysql_escape_string($text)."' WHERE pid='$post_id'", $sql_to) or die(mysql_error());
    }
    mysql_select_db($lb_dbname, $sql_to);
    $sql_result = get_limit_query("SELECT * FROM " . $lb_prefix . "_posts", $sql_to);
    $posts = fetch_array($sql_result);
    foreach ($posts as $post)
    {
        $post_id = $post['pid'];
        $sql_result = mysql_query("SELECT * FROM " . $lb_prefix . "_topics_files WHERE file_pid='$post_id'", $sql_to) or die(mysql_error());
        $files = fetch_array($sql_result);
        $text = '';
        foreach ($files as $file)
            $text .= $file['file_id'] . ",";
        if (strlen($text) > 0)
            $text = substr($text, 0, -1);
        mysql_query("UPDATE " . $lb_prefix . "_posts SET attachments='$text' WHERE pid='" . $post['pid'] . "'", $sql_to) or die(mysql_error());
    }

    echo "OK<br/>";
    echo "Ranks...";

    mysql_select_db($ipb_dbname, $sql_from);
    $sql_result = mysql_query("SELECT * FROM ".$ipb_prefix."_titles", $sql_from) or die(mysql_error());
    $ranks = fetch_array($sql_result);
    mysql_select_db($lb_dbname, $sql_to);
    foreach($ranks as $rank)
    {
        mysql_query("INSERT INTO ".$lb_prefix."_members_ranks SET
        title='".mysql_escape_string($rank['title'])."',
        post_num='".$rank['posts']."',
        stars='".$rank['pips']."'
        ", $sql_to) or die(mysql_error());
    }

    echo "OK<br/>";
    echo "Warning logs...";

    mysql_select_db($ipb_dbname, $sql_from);
    $sql_result = mysql_query("SELECT * FROM ".$ipb_prefix."_warn_logs",$sql_from) or die(mysql_error());
    $logs = fetch_array($sql_result);
    mysql_select_db($lb_dbname, $sql_to);
    foreach($logs as $log)
    {
        $mode = $log['wlog_type'] == "neg" ? -1 : 0;
        $text = $log['wlog_notes'];
        preg_match('#<content>(.+?)</content>#sui', $text, $text);
        mysql_query("INSERT INTO ".$lb_prefix."_members_warning SET
        mid = '".$users_id[$log['wlog_mid']]."',
        moder_id = '".$users_id[$log['wlog_addedby']]."',
        moder_name = '".get_member_name($users_id[$log['wlog_addedby']])."',
        date = '".$log['wlog_date']."',
        description = '".mysql_escape_string($text[1])."',
        st_w = '$mode'
        ", $sql_to) or die(mysql_error());
    }
    echo "OK<br/>";

    echo "Ban filters...";
    mysql_select_db($ipb_dbname, $sql_from);
    $sql_result = mysql_query("SELECT * FROM ".$ipb_prefix."_banfilters", $sql_from) or die(mysql_error());
    $filters = fetch_array($sql_result);
    mysql_select_db($lb_dbname, $sql_to);
    foreach($filters as $filter)
    {
        mysql_query("INSERT INTO ".$lb_prefix."_members_banfilters SET
        type='".$filter['ban_type']."',
        description='".mysql_escape_string($filter['ban_content'])."',
        date='".$filter['ban_date']."'
        ", $sql_to) or die(mysql_error());
    }


    echo "OK<br/>";
    echo "Ban members...";

    mysql_select_db($lb_dbname, $sql_to);
    foreach($users_ban as $user_id=>$info)
    {
        $info = explode(":", $info);
        $days = $info[3] == "h" ? 1 : $info[2];
        mysql_query("INSERT INTO ".$lb_prefix."_members_banfilters SET
        type='name',
        description='".get_member_name($user_id)."',
        date = '".$info[0]."',
        date_end = '".$info[1]."',
        ban_days = '$days',
        ban_member_id = '$user_id'
        ", $sql_to) or die(mysql_error());
    }

    echo "OK<br/>";
    echo "Moderators...";

    mysql_select_db($ipb_dbname, $sql_from);
    $sql_result = mysql_query("SELECT * FROM ".$ipb_prefix."_moderators", $sql_from) or die(mysql_error());
    $moderators = fetch_array($sql_result);
    mysql_select_db($lb_dbname, $sql_to);
    foreach($moderators as $moder)
    {
        if($moder['member_id'] != -1)
        {
            $member_id = $users_id[$moder['member_id']];
            $member_name = get_member_name($member_id);
            $group_id = $users_group[$member_id];
            $is_group = 0;
        }
        else
        {
           $member_id = 0;
           $member_name = "";
           if($moder['group_id'] == '5')continue;
           $group_id = isset($groups_key[$moder['group_id']]) ? $groups_key[$moder['group_id']] : $moder['group_id'];
           $is_group = 1;
        }

        $hide = ($moder['topic_q'] == 1 && $moder['post_q'] == 1) ? 1 : 0;

        $permissions = array();
        $permissions['global_hideshow'] = $hide;
        $permissions['global_hidetopic'] = $hide;
        $permissions['global_deltopic'] = $moder['delete_topic'];
        $permissions['global_titletopic'] = $moder['edit_topic'];
        $permissions['global_polltopic'] = $moder['edit_post'];
        $permissions['global_opentopic'] = $moder['open_topic'];
        $permissions['global_closetopic'] = $moder['close_topic'];
        $permissions['global_fixtopic'] = $moder['pin_topic'];
        $permissions['global_unfixtopic'] = $moder['unpin_topic'];
        $permissions['global_movetopic'] = $moder['move_topic'];
        $permissions['global_uniontopic'] = $moder['split_merge'];
        $permissions['global_delpost'] = $moder['delete_post'];
        $permissions['global_unionpost'] = $moder['edit_post'];
        $permissions['global_changepost'] = $moder['edit_post'];
        $permissions['global_movepost'] = $moder['edit_post'];
        $permissions['global_fixedpost'] = $moder['edit_post'];


        mysql_query("INSERT INTO ".$lb_prefix."_forums_moderator SET
        fm_forum_id = '".$forums_id[$moder['forum_id']]."',
        fm_member_id = '$member_id',
        fm_member_name = '$member_name',
        fm_group_id = '$group_id',
        fm_is_group = '$is_group',
        fm_permission = '".serialize($permissions)."'
        ", $sql_to) or die(mysql_error());
    }

    echo "OK<br/>";
    echo "Subscribe...";

    mysql_select_db($ipb_dbname, $sql_from);
    $sql_result = mysql_query("SELECT * FROM ".$ipb_prefix."_tracker", $sql_from) or die(mysql_error());
    $subscribes = fetch_array($sql_result);
    mysql_select_db($lb_dbname, $sql_to);
    foreach($subscribes as $subscribe)
    {
        mysql_query("INSERT INTO ".$lb_prefix."_topics_subscribe SET
        subs_member = '".$users_id[$subscribe['member_id']]."',
        topic = '".$topics_id[$subscribe['topic_id']]."',
        date = '".$subscribe['start_date']."'
        ", $sql_to) or die(mysql_error());
    }

    echo "OK<br/><br/>";




    return "NO_ERROR";

}

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8"/>
    <title>Конвертор IPB 2.3.6 -> LogicBoard</title>
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

        .container-header {
            font-weight: bold;
        }

        #page {
            margin: auto;
            display: block;
            width: 570px;
            overflow: hidden;
        }

        div#header {
            font-size: 25px;
            margin: 0 auto;
            display: block;
            text-align: center;
            padding-bottom: 10px;
        }

        div#from_container {
            display: block;
            float: left;
        }

        div#to_container {
            display: block;
            float: right;
        }

        div.option_item {
            display: block;
            padding-top: 5px;
        }

        div#submit {
            margin: auto;
            padding-top: 10px;
            clear: both;
            width: 100px;
        }

        .option_item > label {
            width: 110px;
            display: inline-block;
        }

        input[type=submit] {
            margin: 0 auto;
            display: block;
        }
    </style>
</head>
<body>

<?php
    if (isset($_POST['convert_submit'])) {

    $params = array(
        "from_mysql_host" => $_POST['from_mysql_host'],
        "from_mysql_login" => $_POST['from_mysql_login'],
        "from_mysql_password" => $_POST['from_mysql_password'],
        "from_db_name" => $_POST['from_db_name'],
        "from_db_prefix" => $_POST['from_db_prefix'],
        "to_mysql_host" => $_POST['to_mysql_host'],
        "to_mysql_login" => $_POST['to_mysql_login'],
        "to_mysql_password" => $_POST['to_mysql_password'],
        "to_db_name" => $_POST['to_db_name'],
        "to_db_prefix" => $_POST['to_db_prefix'],
        "to_site_path" => $_POST['to_site_path'],
    );


    $result = convert($params);
    foreach ($status as $key => $value)
        if ($key == $result) {
            echo $value;
            break;
        }
    exit();
}
?>


<form action="" method="POST">
    <div id="page">
        <div id="header">Конвертор IPB 2.3.6 --> LogicBoard</div>
        <div id="from_container">
            <label class="container-header">IPB</label>

            <div class="option_item">
                <label>MySQL Сервер</label>
                <input type="text" name="from_mysql_host"/>
            </div>
            <div class="option_item">
                <label>MySQL Логин</label>
                <input type="text" name="from_mysql_login"/>
            </div>
            <div class="option_item">
                <label>MySQL Пароль</label>
                <input type="text" name="from_mysql_password"/>
            </div>
            <div class="option_item">
                <label>База данных</label>
                <input type="text" name="from_db_name"/>
            </div>
            <div class="option_item">
                <label>Префикс таблиц</label>
                <input type="text" name="from_db_prefix"/>
            </div>
        </div>
        <div id="to_container">
            <label class="container-header">LogicBoard</label>

            <div class="option_item">
                <label>MySQL Сервер</label>
                <input type="text" name="to_mysql_host"/>
            </div>
            <div class="option_item">
                <label>MySQL Логин</label>
                <input type="text" name="to_mysql_login"/>
            </div>
            <div class="option_item">
                <label>MySQL Пароль</label>
                <input type="text" name="to_mysql_password"/>
            </div>
            <div class="option_item">
                <label>База данных</label>
                <input type="text" name="to_db_name"/>
            </div>
            <div class="option_item">
                <label>Префикс таблиц</label>
                <input type="text" name="to_db_prefix"/>
            </div>
            <div class="option_item">
                <label>Адрес форума</label>
                <input type="text" name="to_site_path"/>
            </div>
        </div>
        <div id="submit">
            <input type="submit" name="convert_submit" value="Конвертировать"/>
        </div>
    </div>
</form>
</body>
</html>
