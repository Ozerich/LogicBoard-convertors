<?php

set_time_limit(0);
ini_set('memory_limit', '512M');


$status = array(
    "MYSQL_FROM_ERROR" => "Невозможно подлючиться к MySQL серверу DLE форума",
    "MYSQL_TO_ERROR" => "Невозможно подлючиться к MySQL серверу LogicBoard",
    "FROM_DB_NOFOUND" => "Не найдена база данных DLE форума",
    "TO_DB_NOFOUND" => "Не найдена база данных LogicBoard",
    "BAD_SITEPATH" => "Неправильный формат адреса сайта",
    "NO_ERROR" => "Форум успешно перенесён. Удалите этот файл!",
);

$last_limit_value = 0;
$last_limit_query = "";

require_once "include/parser.php";

$lb_prefix = $lb_dbname = $tws_prefix = $tws_dbname = $dle_prefix = "";

$limit_count = 1000;

function fetch_array($sql_result)
{
    for ($result = array(); $row = mysql_fetch_array($sql_result); $result[] = $row) ;
    return $result;
}

function get_limit_query($query, $sql_handle)
{
    global $limit_count, $last_limit_query, $last_limit_value;
    if ($query != $last_limit_query) {
        $last_limit_value = 0;
        $last_limit_query = $query;
    }
    $start = $last_limit_value;
    $finish = $start + $limit_count;
    $last_limit_value = $finish;
    $sql_result = mysql_query($query . " LIMIT " . $start . "," . $limit_count, $sql_handle);
    return $sql_result;
}


function get_member_id($name)
{
    global $lb_prefix, $tws_prefix, $lb_dbname, $dle_dbname, $dle_prefix, $sql_from;
    mysql_select_db($dle_dbname, $sql_from);
    $sql_result = mysql_query("SELECT user_id FROM " . $dle_prefix . "_users WHERE name = '$name'", $sql_from) or die(mysql_error());
    $result = @mysql_result($sql_result, 0, 0);
    return ($result == FALSE) ? -1 : $result;

}

function get_member_name($id)
{
    global $lb_prefix, $tws_prefix, $lb_dbname, $dle_dbname, $dle_prefix, $sql_from;
    mysql_select_db($dle_dbname, $sql_from);
    $sql_result = mysql_query("SELECT name FROM " . $dle_prefix . "_users WHERE user_id = '$id'", $sql_from) or die(mysql_error());
    $result = @mysql_result($sql_result, 0, 0);
    return ($result == FALSE) ? "Удалён" : $result;
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


function datetime_to_int($date)
{
    preg_match_all("#(\d+)\D*#sui", $date, $date_items);
    $date_items = $date_items[1];
    if ($date_items[0] == '0000')
        return 0;
    return mktime($date_items[3], $date_items[4], $date_items[5], $date_items[1], $date_items[2], $date_items[0]);
}

function convert($params)
{

    $lb_prefix_ = $params['to_db_prefix'];
    $dle_prefix_ = $params['from_db_prefix'];
    $lb_dbname_ = $params['to_db_name'];
    $dle_dbname_ = $params['from_db_name'];
    $site_path = $params['to_site_path'];

    $GLOBALS['lb_prefix'] = $lb_prefix_;
    $GLOBALS['dle_prefix'] = $dle_prefix_;
    $GLOBALS['lb_dbname'] = $lb_dbname_;
    $GLOBALS['dle_dbname'] = $dle_dbname_;

    global $lb_prefix, $lb_dbname, $dle_dbname, $dle_prefix, $last_limit_value;

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

    echo "Users...";
    //users
    $users_id = array();
    $users_id[-1] = -1;
    mysql_select_db($dle_dbname, $sql_from) or die(mysql_error());
    $sql_result = mysql_query("SELECT * FROM " . $dle_prefix . "_users ORDER by reg_date ASC", $sql_from) or die(mysql_error());
    $result = fetch_array($sql_result);
    mysql_select_db($lb_dbname, $sql_to);
    foreach ($result as $item)
    {
        $member_sk = md5(md5($item['password'] . time() . $item['logged_ip']));
        mysql_query("INSERT INTO " . $lb_prefix . "_members SET
		name='" . $item['name'] . "',
		password='" . mysql_escape_string($item['password']) . "',
        secret_key='" . mysql_escape_string($member_sk) . "',
		email='" . $item['email'] . "',
		member_group='" . (($item['user_group'] < 6) ? $item['user_group'] : $item['user_group'] + 1) . "',
		lastdate='" . $item['lastdate'] . "',
		reg_date='" . $item['reg_date'] . "',
		ip='" . $item['logged_ip'] . "',
		personal_title='',
		reg_status='1',
		avatar='',
		fullname='" . mysql_escape_string($item['fullname']) . "',
		town='" . mysql_escape_string($item['land']) . "',
		about='" . mysql_escape_string($item['info']) . "',
		signature='" . mysql_escape_string($item['signature']) . "',
		icq='" . $item['icq'] . "',
		banned='" . (($item['banned'] == "yes") ? 1 : 0) . "',
		posts_num ='" . $item['forum_post'] . "',
		reputation='" . $item['forum_reputation'] . "'
		", $sql_to) or die(mysql_error());
        $users_id[$item['user_id']] = mysql_insert_id();
    }
    echo "OK<br/>";
    //Categories
    echo "Categories...";
    mysql_select_db($dle_dbname, $sql_from);
    $sql_result = mysql_query("SELECT * FROM " . $dle_prefix . "_forum_category ORDER by sid ASC", $sql_from) or die(mysql_error());
    $result = fetch_array($sql_result);
    mysql_select_db($lb_dbname, $sql_to);
    foreach ($result as $item)
    {
        $postcount = (isset($item['postcount'])) ? $item['postcount'] : 1;
        mysql_query("INSERT INTO " . $lb_prefix . "_forums SET
		parent_id='0',
		title='" . mysql_escape_string($item['cat_name']) . "',
		alt_name='" . mysql_escape_string(translit($item['cat_name'])) . "',
		postcount='$postcount',
		posi='" . $item['posi'] . "'", $sql_to) or die(mysql_error());
        $categories_id[$item['sid']] = mysql_insert_id();
    }
    echo "OK<br/>";
    echo "Groups...";
    //groups
    mysql_select_db($dle_dbname, $sql_from);
    $sql_result = mysql_query("SELECT * FROM " . $dle_prefix . "_usergroups ORDER by id ASC") or die(mysql_error());
    $result = fetch_array($sql_result);
    mysql_select_db($lb_dbname, $sql_to);
    $groups_count = 0;
    for ($i = 0, $id = 1; $i < 5; $i++, $groups_count++)
        mysql_query("INSERT INTO " . $lb_prefix . "_groups SET g_id='" . ($id++) . "', g_title='" . mysql_escape_string($result[$i]['group_name']) . "'", $sql_to) or die(mysql_error());
    mysql_query("INSERT INTO " . $lb_prefix . "_groups SET g_id='6', g_title='Неактивные'", $sql_to) or die(mysql_error());
    for ($i = 5, $id = 7, $groups_count = 6; $i < count($result); $i++, $groups_count++)
        mysql_query("INSERT INTO " . $lb_prefix . "_groups SET g_id='" . ($id++) . "', g_title='" . mysql_escape_string($result[$i]['group_name']) . "'", $sql_to) or die(mysql_error());
    mysql_query("UPDATE " . $lb_prefix . "_groups SET g_access_cc='1',g_supermoders='1' WHERE g_id='1'", $sql_to) or die(mysql_error());

    echo "OK<br/>";
    echo "Forums...";

    //forums
    while (true)
    {
        mysql_select_db($dle_dbname, $sql_from);
        $sql_result = get_limit_query("SELECT * FROM " . $dle_prefix . "_forum_forums ORDER by id ASC", $sql_from);
        $result = fetch_array($sql_result);
        if (!$result) break;
        foreach ($result as $item)
        {
            $access_write = explode(":", $item['access_write']);
            $access_read = explode(":", $item['access_read']);
            $access_mod = explode(":", $item['access_mod']);
            $access_write = explode(":", $item['access_write']);
            $access_topic = explode(":", $item['access_topic']);
            $access_upload = explode(":", $item['access_upload']);
            $access_download = explode(":", $item['access_download']);

            $permissions = array();

            for ($i = 1; $i <= $groups_count; $i++)
                $permissions[$i] = array("read_forum" => 0, "read_theme" => 0, "creat_theme" => 0, "answer_theme" => 0, "upload_files" => 0, "download_files" => 0);

            for ($j = 0; $j < count($access_read); $j++)
            {
                $index = ($access_read[$j] > 5) ? $access_read[$j] + 1 : $access_read[$j];
                $permissions[$index]['read_forum'] = 1;
                $permissions[$index]['read_theme'] = 1;
            }
            for ($j = 0; $j < count($access_write); $j++)
            {
                $index = ($access_write[$j] > 5) ? $access_write[$j] + 1 : $access_write[$j];
                $permissions[$index]['answer_theme'] = 1;
            }
            for ($j = 0; $j < count($access_topic); $j++)
            {
                $index = ($access_topic[$j] > 5) ? $access_topic[$j] + 1 : $access_topic[$j];
                $permissions[$index]['creat_theme'] = 1;
            }
            for ($j = 0; $j < count($access_upload); $j++)
            {
                $index = ($access_upload[$j] > 5) ? $access_upload[$j] + 1 : $access_upload[$j];
                $permissions[$index]['upload_files'] = 1;
            }
            for ($j = 0; $j < count($access_download); $j++)
            {
                $index = ($access_download[$j] > 5) ? $access_download[$j] + 1 : $access_download[$j];
                $permissions[$index]['download_files'] = 1;
            }
            $permissions[6] = $permissions[5];

            $permissions = serialize($permissions);
            $postcount = (isset($item['postcount'])) ? $item['postcount'] : 1;

            $last_user = $users_id[get_member_id($item['f_last_poster_name'])];
            mysql_select_db($lb_dbname, $sql_to);
            mysql_query("INSERT INTO " . $lb_prefix . "_forums SET
            posi='" . $item['position'] . "',
            title='" . mysql_escape_string($item['name']) . "',
            alt_name='" . mysql_escape_string(translit($item['name'])) . "',
            description='" . mysql_escape_string($item['description']) . "',
            last_post_member='" . $item['f_last_poster_name'] . "',
            last_post_member_id='$last_user',
            last_post_date='" . datetime_to_int($item['f_last_date']) . "',
            allow_bbcode='1',
            allow_poll='1',
            postcount='$postcount',
            group_permission ='$permissions',
            password='" . mysql_escape_string($item['password']) . "',
            sort_order='DESC',
            posts='" . $item['posts'] . "',
            topics ='" . $item['topics'] . "',"
                        /*posts_hiden='".$item['posts_hiden']."',
                        topics_hiden='".$item['topics_hiden']."',*/ . "
            rules='" . $item['rules'] . "',
            meta_desc='',
            meta_key=''
            ", $sql_to) or die(mysql_error());
            $forums_id[$item['id']] = mysql_insert_id();
        }
    }

    $last_limit_value = 0;

    while (true)
    {
        mysql_select_db($dle_dbname, $sql_from);
        $sql_result = get_limit_query("SELECT * FROM " . $dle_prefix . "_forum_forums ORDER by id ASC", $sql_from);
        $result = fetch_array($sql_result);
        if (!$result) break;
        mysql_select_db($lb_dbname, $sql_to);
        foreach ($result as $item)
        {
            $parent_id = $item['parentid'] != 0 ? $forums_id[$item['parentid']] : $categories_id[$item['main_id']];
            $forum_id = $forums_id[$item['id']];
            mysql_query("UPDATE " . $lb_prefix . "_forums SET parent_id='$parent_id' WHERE id='$forum_id'", $sql_to) or die(mysql_error());
        }
    }

    echo "OK<br/>";
    echo "Topics...";
    //topics
    while (true)
    {
        mysql_select_db($dle_dbname, $sql_from);
        $sql_result = get_limit_query("SELECT * FROM " . $dle_prefix . "_forum_topics ORDER by tid ASC", $sql_from);
        $result = fetch_array($sql_result);
        if (!$result) break;
        mysql_select_db($lb_dbname, $sql_to);
        $max_data = array();
        foreach ($result as $item)
        {
            $first_post_id = "0"; //!!!!!!
            $forum_id = $forums_id[$item['forum_id']];
            $date_last = datetime_to_int($item['last_date']);
            $hidden = ($item['hidden'] >= 1) ? 1 : 0;
            mysql_query("INSERT INTO " . $lb_prefix . "_topics SET
            forum_id='$forum_id',
            title='" . mysql_escape_string($item['title']) . "',
            description='" . mysql_escape_string($item['topic_descr']) . "',
            post_id='$first_post_id',                     
            date_open='" . datetime_to_int($item['start_date']) . "',
            date_last='$date_last',
            status='" . (($item['topic_status'] == 0) ? "open" : "closed") . "',
            views='" . $item['views'] . "',
            post_num='" . $item['post'] . "',
            post_hiden='0',
            fixed='" . (($item['fixed'] == 0) ? 1 : 0) . "',
            hiden='$hidden',
            poll_id='ERROR',
            postfixed='0'
            ", $sql_to) or die(mysql_error());
            $topics_id[$item['tid']] = mysql_insert_id();

            if ($item['poll_title'] != "") {
                $topic_id = $topics_id[$item['tid']];
                $variants = str_replace("<br />", "\r\n", $item['poll_body']);
                mysql_query("INSERT INTO " . $lb_prefix . "_topics_poll SET
                tid='$topic_id',
                vote_num='" . $item['poll_count'] . "',
                title='" . mysql_escape_string($item['poll_title']) . "',
                question='" . mysql_escape_string($item['frage']) . "',
                variants='$variants',
                answers='" . mysql_escape_string($item['answer']) . "',
                multiple='" . $item['multiple'] . "',
                open_date='" . datetime_to_int($item['start_date']) . "'", $sql_to) or die(myqsl_error());
                mysql_query("UPDATE " . $lb_prefix . "_topics SET poll_id='" . mysql_insert_id() . "' WHERE id='$topic_id'", $sql_to) or die(mysql_error());
            }

            if ((!isset($max_data[$forum_id])) || ($max_data[$forum_id]["time"] < $date_last)) {
                $max_data[$forum_id]["time"] = $date_last;
                $max_data[$forum_id]["tid"] = $topics_id[$item['tid']];
            }
            $max_data[$forum_id]['hiden'] += $hidden;
        }
    }

    mysql_select_db($lb_dbname, $sql_to);
    foreach ($max_data as $fid => $value)
    {
        $last_topic_id = $value['tid'];
        $topics_hidden = $value['hiden'];
        $sql_result = mysql_query("SELECT title FROM " . $lb_prefix . "_topics WHERE id='$last_topic_id'", $sql_to) or die(mysql_error());
        $last_title = mysql_result($sql_result, 0, 0);
        mysql_query("UPDATE " . $lb_prefix . "_forums SET last_topic_id='$last_topic_id',topics_hiden='$topics_hidden',last_title='$last_title' WHERE id='$fid'");
    }


    echo "OK<br>";
    echo "Posts...";

    //posts
    while (true)
    {
        mysql_select_db($dle_dbname, $sql_from);
        $sql_result = get_limit_query("SELECT * FROM " . $dle_prefix . "_forum_posts  ORDER by pid ASC", $sql_from);
        $result = fetch_array($sql_result);
        if (!$result) break;
        $min_data = $max_data = array();
        foreach ($result as $item)
        {
            $topic_id = $topics_id[$item['topic_id']];
            $post_date = datetime_to_int($item['post_date']);
            $post_member_id = $users_id[get_member_id($item['post_author'])];
            $edit_member_id = $users_id[get_member_id($item['edit_user'])];
            $hiden = ($item['hidden'] == 1) ? 1 : 0;

            mysql_select_db($lb_dbname, $sql_to);
            mysql_query("INSERT INTO " . $lb_prefix . "_posts SET
            topic_id='$topic_id',
            new_topic='0',
            text='" . mysql_escape_string(dle_to_lb($item['post_text'], $site_path)) . "',
            post_date='$post_date',     
            edit_date='" . $item['edit_time'] . "',
            post_member_id='$post_member_id',
            post_member_name='" . $item['post_author'] . "',
            ip='" . $item['post_ip'] . "',
            hide='$hiden',
            edit_member_id='$edit_member_id',
            edit_member_name='" . $item['edit_user'] . "',
            edit_reason='',
            fixed='0'
            ", $sql_to) or die(mysql_error());
            $posts_id[$item['pid']] = mysql_insert_id();

            if ((!isset($min_data[$topic_id])) || ($min_data[$topic_id]["time"] > $post_date)) {
                $min_data[$topic_id]["time"] = $post_date;
                $min_data[$topic_id]["pid"] = $posts_id[$item['pid']];
            }
            if ((!isset($max_data[$topic_id])) || ($max_data[$topic_id]["time"] < $post_date))
                $max_data[$topic_id] = array("time" => $post_date, "pid" => $posts_id[$item['pid']]);
            $min_data[$topic_id]['hiden'] += $hiden;
        }
    }
    mysql_select_db($lb_dbname, $sql_to);
    foreach ($min_data as $tid => $item)
    {
        $tid = $topics_id[$tid];
        mysql_query("UPDATE " . $lb_prefix . "_posts SET new_topic='1' WHERE pid='" . $item['pid'] . "'", $sql_to) or die(mysql_error());
        mysql_query("UPDATE " . $lb_prefix . "_topics SET post_hiden='" . $item['hiden'] . "' WHERE id='$tid'", $sql_to) or die(mysql_error());
    }

    while (true)
    {
        mysql_select_db($lb_dbname, $sql_to);
        $sql_result = get_limit_query("SELECT * FROM " . $lb_prefix . "_posts", $sql_to);
        $result = fetch_array($sql_result);
        if (!$result) break;
        foreach ($result as $item)
        {
            if ($item['hide'] == 0) {
                $topic_id = $item['topic_id'];
                $sql_result = mysql_query("SELECT hiden FROM " . $lb_prefix . "_topics WHERE id='$topic_id'", $sql_to) or die(mysql_error());
                $hiden_topic = mysql_result($sql_result, 0, 0);
                if ($hiden_topic && $item['new_topic'] == 1)
                    mysql_query("UPDATE " . $lb_prefix . "_posts SET hide='1' WHERE pid='" . $item['pid'] . "'", $sql_to) or die(mysql_error());
            }
        }
    }
    //update topics and forums
    foreach ($max_data as $tid => $item)
    {
        $last_post_id = $item['pid'];
        mysql_select_db($lb_dbname, $sql_to);
        $sql_result = mysql_query("SELECT post_member_id FROM " . $lb_prefix . "_posts WHERE pid='$last_post_id'", $sql_to)
        or die(mysql_error());
        $last_post_member_id = mysql_result($sql_result, 0, 0);
        $member_name_last = get_member_name($last_post_member_id);
        mysql_select_db($lb_dbname, $sql_to);
        mysql_query("UPDATE " . $lb_prefix . "_topics SET last_post_id='$last_post_id',
		    last_post_member='$last_post_member_id',
		    member_name_last='$member_name_last'
		    WHERE id='$tid'", $sql_to) or die(mysql_error());

    }

    $sql_result = mysql_query("SELECT * FROM " . $lb_prefix . "_posts WHERE new_topic='1'");
    $result = fetch_array($sql_result);
    $count = 0;
    foreach ($result as $item)
    {

        $topic_id = $item['topic_id'];
        $user_id = $item['post_member_id'];
        $post_id = $item['pid'];
        $user_name = get_member_name($user_id);
        mysql_select_db($lb_dbname, $sql_to);
        mysql_query("UPDATE " . $lb_prefix . "_topics SET post_id='$post_id',
		    member_id_open='$user_id',
		    member_name_open='$user_name'
		    WHERE id='$topic_id'", $sql_to) or die(mysql_error());
    }

    $sql_result = mysql_query("SELECT * FROM " . $lb_prefix . "_forums WHERE parent_id != '0'", $sql_to) or die(mysql_error());
    $result = fetch_array($sql_result);
    foreach ($result as $forum)
    {
        $last_topic_id = $forum['last_topic_id'];
        $sql_result = mysql_query("SELECT * FROM " . $lb_prefix . "_posts WHERE topic_id='$last_topic_id'", $sql_to) or die(mysql_error());
        $posts = fetch_array($sql_result);
        $max = $last_post_id = 0;
        foreach ($posts as $item)
        {
            if ($item['post_date'] >= $max)
                $last_post_id = $item['pid'];
        }
        $sql_result = mysql_query("SELECT * FROM " . $lb_prefix . "_topics WHERE forum_id='" . $forum['id'] . "'", $sql_to) or die(mysql_error());
        $topics = fetch_array($sql_result);
        $hiden = $posts_hidden = $post_max = 0;
        foreach ($topics as $item)
        {
            $hiden += $item['hiden'];
            $sql_result = mysql_query("SELECT * FROM " . $lb_prefix . "_posts WHERE topic_id='" . $item['id'] . "'", $sql_to) or die(mysql_error());
            $posts = fetch_array($sql_result);
            foreach ($posts as $post)
            {
                $posts_hidden += $post['hide'];
                if ($post['post_date'] > $post_max) {
                    $post_max = $post['post_date'];
                    $last_post_id = $post['pid'];
                }
            }
        }

        mysql_query("UPDATE " . $lb_prefix . "_forums SET topics_hiden='$hiden',posts_hiden='$posts_hidden',last_post_id='$last_post_id' WHERE id='" . $forum['id'] . "'", $sql_to) or die(mysql_error());
    }

    echo "OK<br/>";
    echo "Ban filters...";
    //banfilters
    mysql_select_db($dle_dbname, $sql_from);
    $sql_result = mysql_query("SELECT * FROM " . $dle_prefix . "_banned  ORDER by id ASC", $sql_from) or die(mysql_error());
    $result = fetch_array($sql_result);
    $begindate = time();
    foreach ($result as $item)
    {
        mysql_select_db($lb_dbname, $sql_to);
        $user_id = $desc = $type = $ban_member_id = "";
        if ($item['users_id'] != 0) {
            $user_id = $users_id[$item['users_id']];
            $descr = get_member_name($user_id);
            $type = "name";
            $ban_member_id = $user_id;
            mysql_query("INSERT INTO " . $lb_prefix . "_members_banfilters SET
			type='$type',
			description = '$descr',
			date='$begindate',
			moder_desc='" . mysql_escape_string($item['descr']) . "',
			date_end='" . $item['date'] . "',
			ban_days='" . $item['days'] . "',
			ban_member_id='$ban_member_id'
			", $sql_to) or die(mysql_error());
        }
        if ($item['ip'] != "") {
            $ip = $item['ip'];
            if (preg_match('#(\d+)\.(\d+)\.(\d+)\.(\d+)#', $ip))
                $type = "ip";
            else if (strpos($ip, '@') !== false)
                $type = "email";
            else
                $type = "name";
            $descr = $ip;

            mysql_query("INSERT INTO " . $lb_prefix . "_members_banfilters SET
			type='$type',
			description = '$descr',
			date='$begindate',
			moder_desc='" . mysql_escape_string($item['descr']) . "',
			date_end='" . $item['date'] . "',
			ban_days='" . $item['days'] . "',
			ban_member_id='$ban_member_id'
			", $sql_to) or die(mysql_error());
        }
    }

    echo "OK<br/>";
    echo "Votes...";

    //vote_logs
    mysql_select_db($dle_dbname, $sql_from);
    $sql_result = mysql_query("SELECT * FROM " . $dle_prefix . "_forum_poll_log  ORDER by id ASC", $sql_from) or die(mysql_error());
    $result = fetch_array($sql_result);
    mysql_select_db($lb_dbname, $sql_to);
    foreach ($result as $item)
    {
        $user_id = $users_id[$item['member']];
        $user_name = get_member_name($user_id);
        $sql_result = mysql_query("SELECT ip FROM " . $lb_prefix . "_members WHERE member_id='$user_id'", $sql_to) or die(mysql_error());
        $user_ip = mysql_result($sql_result, 0, 0);
        $topic_id = $topics_id[$item['topic_id']];
        $mysql_result = mysql_query("SELECT poll_id FROM " . $lb_prefix . "_topics WHERE id='$topic_id'", $sql_to) or die(mysql_error());
        $poll_id = mysql_result($mysql_result, 0, 0);
        mysql_query("INSERT INTO " . $lb_prefix . "_topics_poll_logs SET
		poll_id='$poll_id',
		ip = '$user_ip',
		member_id='$user_id',
		log_date='" . time() . "',
		answer='0',
		member_name='$user_name'
		", $sql_to) or die(mysql_error());

    }
    echo "OK<br/>";
    echo "Moderators...";
    //moderators
    mysql_select_db($dle_dbname, $sql_from);
    $sql_result = mysql_query("SELECT * FROM " . $dle_prefix . "_forum_moderators  ORDER by mid ASC", $sql_from) or die(mysql_error());
    $result = fetch_array($sql_result);
    mysql_select_db($lb_dbname, $sql_to);
    foreach ($result as $item)
    {
        if ($item['member_id'] == 0)
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
        $mysql_result = mysql_query("SELECT member_group FROM " . $lb_prefix . "_members WHERE member_id='$member_id'", $sql_to) or die(mysql_error());
        $group = mysql_result($mysql_result, 0, 0);
        $group = $group < 6 ? $group : $item['group_id'] + 1;
        mysql_query("INSERT INTO " . $lb_prefix . "_forums_moderator SET
		fm_forum_id='" . $forums_id[$item['forum_id']] . "',
		fm_member_id='$member_id',
		fm_member_name='" . get_member_name($member_id) . "',
		fm_group_id='$group',
		fm_is_group='0',
		fm_permission='$permissions'
		", $sql_to) or die(mysql_error());
    }
    echo "OK<br/>";
    echo "Ranks...";
    //member ranks
    mysql_select_db($dle_dbname, $sql_from);
    $sql_result = mysql_query("SELECT * FROM " . $dle_prefix . "_forum_titles  ORDER by id ASC", $sql_from) or die(mysql_error());
    $result = fetch_array($sql_result);
    mysql_select_db($lb_dbname, $sql_to);
    $count = 0;
    foreach ($result as $item)
    {
        mysql_query("INSERT INTO " . $lb_prefix . "_members_ranks SET
		title='" . mysql_escape_string($item['title']) . "',
		post_num = '" . $item['posts'] . "',
		stars='" . $item['pips'] . "'
		", $sql_to) or die(mysql_error());
    }
    echo "OK<br/>";
    echo "Subscribe...";
    //subcribe
    mysql_select_db($dle_dbname, $sql_from);
    $sql_result = mysql_query("SELECT * FROM " . $dle_prefix . "_forum_subscription  ORDER by sid ASC", $sql_from) or die(mysql_error());
    $result = fetch_array($sql_result);
    mysql_select_db($lb_dbname, $sql_to);
    foreach ($result as $item)
    {
        mysql_query("INSERT INTO " . $lb_prefix . "_subscribe SET
		subs_member='" . $users_id[$item['user_id']] . "',
		topic = '" . $topics_id[$item['topic_id']] . "'
		", $sql_to) or die(mysql_error());
    }
    echo "OK<br/>";
    echo "Files...";
    //files
    mysql_select_db($dle_dbname, $sql_from);
    $sql_result = mysql_query("SELECT * FROM " . $dle_prefix . "_forum_files ORDER by file_id ASC", $sql_from)  or die(mysql_error());
    $result = fetch_array($sql_result);
    mysql_select_db($lb_dbname, $sql_to);
    $files_id = array();
    foreach ($result as $file)
    {

        $author_name = $file['file_author'];
        $author_id = $users_id[get_member_id($author_name)];
        $topic_id = $topics_id[$file['topic_id']];
        $sql_result = mysql_query("SELECT forum_id FROM " . $lb_prefix . "_topics WHERE id='$topic_id'", $sql_to) or die(mysql_error());
        $forum_id = mysql_result($sql_result, 0, 0);
        $file_type = $file['file_type'];
        if ($file_type == "thumb")
            $file_type = "picture";
        mysql_query("INSERT INTO " . $lb_prefix . "_topics_files SET
        file_title='" . mysql_escape_string($file['file_name']) . "',
        file_name='" . mysql_escape_string($file['onserver']) . "',
        file_type='$file_type',
        file_mname='$author_name',
        file_mid='$author_id',
        file_date='" . $file['file_date'] . "',
        file_size='" . $file['file_size'] . "',
        file_count='" . $file['dcount'] . "',
        file_tid='$topic_id',
        file_fid='$forum_id',
        file_convert='1',
        file_pid='" . $posts_id[$file['post_id']] . "'", $sql_to) or die(mysql_error());

        $files_id[$file['file_id']] = mysql_insert_id();
    }
    echo "OK<br/>";
    //update posts
    mysql_select_db($lb_dbname, $sql_to);
    $sql_result = mysql_query("SELECT * FROM " . $lb_prefix . "_posts", $sql_to) or die(mysql_error());
    $result = fetch_array($sql_result);
    foreach ($result as $post)
    {
        $text = $post['text'];
        preg_match('#attachment=(\d+)#sui', $text, $item);
        if ($item) {
            $file_id = $files_id[$item[1]];
            $text = mysql_escape_string(str_replace("[attachment=" . $item[1] . "]", "[attachment=$file_id]", $text));
            mysql_query("UPDATE " . $lb_prefix . "_posts SET text='$text' WHERE pid='" . $post['pid'] . "'", $sql_to) or die(mysql_error());
        }
    }
    echo "Reputation...";
    //reputation
    mysql_select_db($dle_dbname, $sql_from);
    $sql_result = mysql_query("SELECT * FROM " . $dle_prefix . "_forum_reputation_log ORDER by rid ASC", $sql_from) or die(mysql_error());
    $result = fetch_array($sql_result);
    mysql_select_db($lb_dbname, $sql_to);
    foreach ($result as $item)
    {
        mysql_select_db($lb_dbname, $sql_to);
        mysql_query("INSERT INTO " . $lb_prefix . "_members_reputation SET
		from_id='" . $users_id[get_member_id($item['author'])] . "',
		from_name='" . $item['author'] . "',
		to_id='" . $users_id[$item['mid']] . "',
		to_name='" . get_member_name($users_id[$item['mid']]) . "',
		date='" . $item['date'] . "',
		how = '" . (($item['action'] == '-') ? '-1' : '+1') . "',
		text = '" . mysql_escape_string($item['cause']) . "'
		", $sql_to) or die(mysql_error());
    }

    echo "OK<br/>";
    echo "Attachments...";
    //update attachments at posts

    mysql_select_db($lb_dbname, $sql_to);
    while (true)
    {
        $sql_result = get_limit_query("SELECT * FROM " . $lb_prefix . "_posts", $sql_to);
        $posts = fetch_array($sql_result);
        if (!$posts) break;
        $count = 0;
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
    }

    echo "OK<br/>";
    echo "Punishments...";

    //punishments
    mysql_select_db($dle_dbname, $sql_from);
    $sql_result = mysql_query("SELECT * FROM " . $dle_prefix . "_forum_warn_log", $sql_from) or die(mysql_error());
    $warns = fetch_array($sql_result);
    mysql_select_db($lb_dbname, $sql_to);
    foreach ($warns as $warning)
    {
        if ($warning['action'] == '+') {
            $user_id = $users_id[$warning['mid']];
            $moder_name = $warning['author'];
            $moder_id = $users_id[get_member_id($moder_name)];
            $cause = mysql_escape_string($warning['cause']);
            $date = $warning['date'];
            mysql_query("INSERT INTO " . $lb_prefix . "_members_warning SET mid='$user_id', moder_id='$moder_id', moder_name='$moder_name', date='$date', description='$cause',st_w='1'", $sql_to) or die(mysql_error());
        }
    }

    mysql_select_db($dle_dbname, $sql_from);
    $sql_result = mysql_query("SELECT * FROM " . $dle_prefix . "_forum_warn_log", $sql_from) or die(mysql_error());
    $warns = fetch_array($sql_result);
    mysql_select_db($lb_dbname, $sql_to);
    foreach ($warns as $warning)
    {
        if ($warning['action'] == '-') {
            $sql_result = mysql_query("SELECT * FROM " . $lb_prefix . "_members_warning", $sql_to) or die(mysql_error());
            $lb_warns = fetch_array($sql_result);
            if ($lb_warns) {
                $min_date = 2000000000;
                $min_id = -1;
                foreach ($lb_warns as $lb_warn)
                {
                    if ($lb_warn['st_w'] == 0) continue;
                    if ($lb_warn['date'] < $min_date) {
                        $min_date = $lb_warn['date'];
                        $min_id = $lb_warn['id'];
                    }
                }
                if ($min_id == -1) break;
                mysql_query("UPDATE " . $lb_prefix . "_members_warning SET st_w='0' WHERE id='$min_id'", $sql_to) or die(mysql_error());
            }
        }
    }

    mysql_select_db($lb_dbname, $sql_to);
    $sql_result = mysql_query("SELECT * FROM " . $lb_prefix . "_members", $sql_to) or die(mysql_error());
    $users = fetch_array($sql_result);
    foreach ($users as $user)
    {
        $user_id = $user['member_id'];
        $sql_result = mysql_query("SELECT * FROM " . $lb_prefix . "_members_warning WHERE mid='$user_id' AND st_w='1'", $sql_to) or die(mysql_error());
        $result = fetch_array($sql_result);
        $count = count($result);
        mysql_query("UPDATE " . $lb_prefix . "_members SET count_warning='$count' WHERE member_id='$user_id'", $sql_to) or die(mysql_error());
    }

    echo "OK<br/><br/>";

    return "NO_ERROR";
}


?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8"/>
    <title>Конвертор DLE Forum -> LogicBoard</title>
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
        <div id="header">Конвертор DLE Forum 2.5 --> LogicBoard</div>
        <div id="from_container">
            <label class="container-header">DLE Forum</label>

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
