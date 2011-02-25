<?php

set_time_limit(0);
ini_set('memory_limit', '512M');

$status = array(
    "MYSQL_FROM_ERROR" => "Невозможно подлючиться к MySQL серверу IPB форума",
    "MYSQL_TO_ERROR" => "Невозможно подлючиться к MySQL серверу LogicBoard",
    "FROM_DB_NOFOUND" => "Не найдена база данных IPB форума",
    "TO_DB_NOFOUND" => "Не найдена база данных LogicBoard",
    "BAD_SITEPATH" => "Неправильный формат адреса сайта",
    "NO_ERROR" => "Форум успешно перенесён. Удалите этот файл!",
);

print_r(unserialize('a:6:{s:11:"start_perms";s:7:"1,2,3,4";s:11:"reply_perms";s:7:"2,3,4,5";s:10:"read_perms";s:7:"1,2,4,5";s:12:"upload_perms";s:7:"1,3,4,5";s:14:"download_perms";s:7:"1,2,3,5";s:10:"show_perms";s:1:"*";}'));
print_r(unserialize('a:6:{i:1;a:6:{s:10:"read_forum";i:1;s:10:"read_theme";i:1;s:11:"creat_theme";i:1;s:12:"answer_theme";i:1;s:12:"upload_files";i:1;s:14:"download_files";i:1;}i:2;a:6:{s:10:"read_forum";i:1;s:10:"read_theme";i:1;s:11:"creat_theme";i:1;s:12:"answer_theme";i:1;s:12:"upload_files";i:1;s:14:"download_files";i:1;}i:3;a:6:{s:10:"read_forum";i:1;s:10:"read_theme";i:1;s:11:"creat_theme";i:1;s:12:"answer_theme";i:1;s:12:"upload_files";i:1;s:14:"download_files";i:1;}i:4;a:6:{s:10:"read_forum";i:1;s:10:"read_theme";i:1;s:11:"creat_theme";i:1;s:12:"answer_theme";i:1;s:12:"upload_files";i:0;s:14:"download_files";i:1;}i:5;a:6:{s:10:"read_forum";i:1;s:10:"read_theme";i:1;s:11:"creat_theme";i:0;s:12:"answer_theme";i:0;s:12:"upload_files";i:0;s:14:"download_files";i:0;}i:6;a:6:{s:10:"read_forum";i:1;s:10:"read_theme";i:1;s:11:"creat_theme";i:0;s:12:"answer_theme";i:0;s:12:"upload_files";i:0;s:14:"download_files";i:0;}}'));
exit();

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

    $users_id = array();
    $forums_id = array();
    $topics_id = array();
    $posts_id = array();


    echo "Groups...";
    mysql_select_db($ipb_dbname, $sql_from);
    $sql_result = mysql_query("SELECT * FROM ".$ipb_prefix."_groups", $sql_from) or die(mysql_error());
    $groups = fetch_array($sql_result);
    mysql_select_db($lb_dbname, $sql_to);
    foreach($groups as $group)
    {
        if($group['g_id'] == '5')continue;
        $lb_group_id = isset($groups_key[$group['g_id']]) ? $groups_key[$group['g_id']] : $group['g_id'];

        mysql_query("INSERT INTO ".$lb_prefix."_groups SET
        g_id = '$lb_group_id',
        g_title = '".mysql_escape_string($group['g_title'])."',
        g_prefix_st = '".mysql_escape_string($group['prefix'])."',
        g_prefix_end = '".mysql_escape_string($group['suffix'])."'"
           , $sql_to) or die(mysql_error());
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


    echo "Forums...";
    mysql_select_db($ipb_dbname, $sql_from);
    $sql_result = mysql_query("SELECT * FROM ".$ipb_prefix."_forums WHERE parent_id != '-1' ORDER BY id ASC") or die(mysql_error());
    $forums = fetch_array($sql_result);
    mysql_select_db($lb_dbname, $sql_to);
    foreach($forums as $forum)
    {
            $sort_order = $forum['sort_order'] == 'Z-A' ? 'DESC' : 'ASC';
            mysql_query("INSERT INTO " . $lb_prefix . "_forums SET
            posi='" . $forum['position'] . "',
            parent_id='" . $forums_id[$forum['parent_id']] . "',
            title='" . mysql_escape_string($forum['name']) . "',
            alt_name='" . mysql_escape_string(translit($forum['name'])) . "',
            description='" . mysql_escape_string($forum['description']) . "',
            allow_bbcode='1',
            allow_poll='".$forum['allow_poll']."',
            postcount='".$forum['inc_postcount']."',
            topics_hiden='".$forum['queued_topics']."',
            posts_hiden='".$forum['queued_posts']."',
            last_post_member='".$forum['last_poster_name']."',
            last_post_member_id='".$users_id[$forum['last_poster_id']]."',
            last_title='".$forum['last_title']."',
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
        text = '".mysql_escape_string($post['post'])."',
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
        $posts_id[$post['pid']] = mysql_insert_id();
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
	
	echo "OK";





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
