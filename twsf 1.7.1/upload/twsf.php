<?php
    set_time_limit(0);
ini_set('memory_limit', '512M');


$status = array(
    "MYSQL_FROM_ERROR" => "Невозможно подлючиться к MySQL серверу TWS форума",
    "MYSQL_TO_ERROR" => "Невозможно подлючиться к MySQL серверу LogicBoard",
    "FROM_DB_NOFOUND" => "Не найдена база данных TWS форума",
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

function timetoint($time)
{
    if (strlen($time) == 10) {
        preg_match('#(\d+)-(\d+)-(\d+)#sui', $time, $items);
        $result = mktime(0, 0, 0, $items[2], $items[3], $items[1]);
    }
    else
    {
        preg_match('#(\d+)-(\d+)-(\d+)\s(\d+):(\d+):(\d+)#sui', $time, $items);
        $result = mktime($items[4], $items[5], $items[6], $items[2], $items[3], $items[1]);
    }
    return $result;
}

function is_image($path)
{
    $images_ext = array("jpg", "png", "bmp", "gif", "psd");
    $extension = strtolower(substr($path, strrpos($path, ".") + 1));
    foreach ($images_ext as $ext)
        if ($ext == $extension)
            return true;
    return false;

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

function get_forum_permissions($forum_id, $group_id, $type)
{
    global $lb_prefix, $tws_prefix, $lb_dbname, $dle_dbname, $dle_prefix, $sql_from;
    mysql_select_db($dle_dbname, $sql_from);
    $sql_result = mysql_query("SELECT $type FROM " . $tws_prefix . "_forums WHERE forum_id = '$forum_id'", $sql_from) or die(mysql_error());
    $data = mysql_result($sql_result, 0, 0);

    if ($data == "")
        return 1;
    else
    {
        $items = explode(',', $data);
        for ($i = 0; $i < count($items); ++$i)
        {
            if ($items[$i] == $group_id)
                return 1;
        }
        return 0;
    }
}

function check_url($url)
{
    $preg = '#http://.+?/$#sui';
    return preg_match($preg, $url);
}

function convert($params)
{

    $lb_prefix_ = $params['to_db_prefix'];
    $dle_prefix_ = $params['from_db_prefix'];
    $lb_dbname_ = $params['to_db_name'];
    $dle_dbname_ = $params['from_db_name'];
    $site_path = $params['to_site_path'];

    $GLOBALS['lb_prefix'] = $lb_prefix_;
    $GLOBALS['tws_prefix'] = $dle_prefix_ . "_twsf";
    $GLOBALS['dle_prefix'] = $dle_prefix_;
    $GLOBALS['lb_dbname'] = $lb_dbname_;
    $GLOBALS['dle_dbname'] = $dle_dbname_;

    global $lb_prefix, $tws_prefix, $lb_dbname, $dle_dbname, $dle_prefix, $last_limit_value;

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

    echo "Groups...";
    //groups
    $groups_count = 0;
    mysql_select_db($params['from_db_name'], $sql_from) or die(mysql_error());
    $sql_result = mysql_query("SELECT * FROM " . $dle_prefix . "_usergroups ORDER by id ASC", $sql_from) or die(mysql_error());
    $result = fetch_array($sql_result);

    mysql_select_db($lb_dbname, $sql_to);
    for ($i = 0, $id = 1; $i < 5; $i++, $groups_count++)
        mysql_query("INSERT INTO " . $lb_prefix . "_groups SET g_id='" . ($id++) . "', g_title='" . mysql_escape_string($result[$i]['group_name']) . "'", $sql_to);
    mysql_query("INSERT INTO " . $lb_prefix . "_groups SET g_id='6', g_title='Неактивные'", $sql_to);
    for ($i = 5, $id = 7, $groups_count = 6; $i < count($result); $i++, $groups_count++)
        mysql_query("INSERT INTO " . $lb_prefix . "_groups SET g_id='" . ($id++) . "', g_title='" . mysql_escape_string($result[$i]['group_name']) . "'", $sql_to);
    mysql_query("UPDATE " . $lb_prefix . "_groups SET g_access_cc='1',g_supermoders='1' WHERE g_id='1'", $sql_to);
    echo "OK<br/>";

    echo "Users...";
    //users
    mysql_select_db($params['from_db_name'], $sql_from) or die(mysql_error());
    $sql_result = mysql_query("SELECT * FROM " . $dle_prefix . "_users", $sql_from) or die(mysql_error());
    $users = fetch_array($sql_result);

    $users_id = array();

    foreach ($users as $item)
    {
        $member_sk = md5(md5($item['password'] . time() . $item['logged_ip']));
        mysql_select_db($lb_dbname, $sql_to);
        mysql_query("INSERT INTO " . $lb_prefix . "_members SET
		name='" . mysql_escape_string($item['name']) . "',
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
		posts_num ='" . $item['twsf_posts'] . "',
		reputation='" . $item['twsf_rank'] . "'
		", $sql_to) or die(mysql_error());
        $users_id[$item['user_id']] = mysql_insert_id();
    }

    echo "OK<br/>";
    echo "Categories...";
    //categories
        mysql_select_db($dle_dbname, $sql_from) or die(mysql_error());
    $sql_result = mysql_query("SELECT * FROM " . $tws_prefix . "_categories", $sql_from) or die(mysql_error());
	$categories = fetch_array($sql_result);

      mysql_select_db($lb_dbname, $sql_to) or die(mysql_error());
   	foreach ($categories as $category)
       {
           mysql_query("INSERT INTO " . $lb_prefix . "_forums SET
        parent_id = '0',
        posi = '" . $category['cat_order'] . "',
        title = '" . mysql_escape_string($category['cat_title']) . "',
        alt_name = '" . translit($category['cat_title']) . "',
        group_permission = '0'
        ", $sql_to) or die(mysql_error());
           $categories_id[$category['cat_id']] = mysql_insert_id();
       }
	echo "OK<br/>";
    echo "Forums...";
    //forums
    while (true)
    {
        mysql_select_db($dle_dbname, $sql_from) or die(mysql_error());
        $sql_result = get_limit_query("SELECT * FROM " . $tws_prefix . "_forums", $sql_from);
        $forums = fetch_array($sql_result);
        if (!$forums) break;
        foreach ($forums as $forum)
        {
            $permissions = array();
            for ($i = 1; $i <= $groups_count; ++$i)
            {
                $group_id = ($i <= 5) ? $i : ($i + 1);
                $permissions[$i]['read_forum'] = get_forum_permissions($forum['forum_id'], $group_id, 'auth_view');
                $permissions[$i]['read_theme'] = get_forum_permissions($forum['forum_id'], $group_id, 'auth_read');
                $permissions[$i]['creat_theme'] = get_forum_permissions($forum['forum_id'], $group_id, 'auth_post');
                $permissions[$i]['answer_theme'] = get_forum_permissions($forum['forum_id'], $group_id, 'auth_reply');
                $permissions[$i]['upload_files'] = get_forum_permissions($forum['forum_id'], $group_id, 'auth_sendfile');
                $permissions[$i]['download_files'] = get_forum_permissions($forum['forum_id'], $group_id, 'auth_getfile');
            }
            $permissions[6] = $permissions[5];
            if ($forum['forum_status'] == 'hidden') {
                foreach ($permissions as $p_ind => $permissions_item)
                    foreach ($permissions_item as $key => $val)
                        $permissions[$p_ind][$key] = 0;
            }
            else if ($forum['forum_status'] == 'lock') {

                foreach ($permissions as $p_ind => $permissions_item)
                    foreach ($permissions_item as $key => $val)
                        if ($key != 'read_forum')
                            $permissions[$p_ind][$key] = 0;
            }
            $permissions = serialize($permissions);
            mysql_select_db($lb_dbname, $sql_to) or die(mysql_error());
            mysql_query("INSERT INTO " . $lb_prefix . "_forums SET
            title = '" . mysql_escape_string($forum['forum_name']) . "',
            alt_name = '" . mysql_escape_string(translit($forum['forum_name'])) . "',
            description = '" . mysql_escape_string($forum['forum_desc']) . "',
            posi = '" . $forum['forum_order'] . "',
            topics = '" . $forum['forum_topics'] . "',
            posts = '" . $forum['forum_posts'] . "',
            meta_desc = '" . mysql_escape_string($forum['descr']) . "',
            meta_key = '" . mysql_escape_string($forum['keywords']) . "',
            allow_bbcode = '1',
            allow_poll = '1',
            sort_order='DESC',
            postcount = '1',
            group_permission = '$permissions'
            ", $sql_to) or die(mysql_error());
            $forums_id[$forum['forum_id']] = mysql_insert_id();
        }
    }
	$last_limit_value = 0;
	while (true)
    {
        mysql_select_db($dle_dbname, $sql_from) or die(mysql_error());
        $sql_result = get_limit_query("SELECT * FROM " . $tws_prefix . "_forums", $sql_from);
        $forums = fetch_array($sql_result);
        if (!$forums) break;
        foreach ($forums as $forum)
        {
            $parent_id = $forum['p_forum_id'] == 0 ? $categories_id[$forum['cat_id']] : $forums_id[$forum['p_forum_id']];
            mysql_select_db($lb_dbname, $sql_to) or die(mysql_error());
            mysql_query("UPDATE  " . $lb_prefix . "_forums SET
			parent_id = '$parent_id' WHERE id='" . $forums_id[$forum['forum_id']] . "'
			", $sql_to) or die(mysql_error());
        }
    }
    echo "OK<br/>";
    echo "Topics...";
    //topics
	$user_topics = array();
	
	while (true)
    {
        mysql_select_db($dle_dbname, $sql_from) or die(mysql_error());
        $sql_result = get_limit_query("SELECT * FROM " . $tws_prefix . "_topics", $sql_from);
        $topics = fetch_array($sql_result);
        if (!$topics) break;

        foreach ($topics as $topic)
        {
            $fixed = 0;
            if ($topic['topic_type'] == 1 && $topic['topic_lock'] == 0)
                $fixed = 1;
            $member_id_open = $users_id[get_member_id($topic['topic_poster'])];
            $status = ($topic['topic_lock'] == 1) ? "closed" : "open";
            mysql_select_db($lb_dbname, $sql_to) or die(mysql_error());
            mysql_query("INSERT INTO " . $lb_prefix . "_topics SET
        forum_id = '" . $forums_id[$topic['forum_id']] . "',
        title = '" . mysql_escape_string($topic['topic_title']) . "',
        description = '" . mysql_escape_string($topic['topic_subject']) . "',
        member_name_open = '" . $topic['topic_poster'] . "',
        member_id_open = '$member_id_open',
        views = '" . $topic['topic_views'] . "',
        status = '$status',
        fixed = '$fixed',
        hiden = '0',
        post_num = '" . $topic['topic_replies'] . "'
        ", $sql_to) or die(mysql_error());

            $user_topics[$member_id_open] = (isset($user_topics[$member_id_open])) ? $user_topics[$member_id_open] + 1 : 1;
            $topics_id[$topic['topic_id']] = mysql_insert_id();
        }
    }
    mysql_select_db($lb_dbname, $sql_to) or die(mysql_error());
	foreach ($user_topics as $user_id => $count)
        mysql_query("UPDATE " . $lb_prefix . "_members SET topics_num = '" . $count . "' WHERE member_id='" . $user_id . "'", $sql_to) or die(mysql_error());

    echo "OK<br/>";
    echo "Posts...";
    //posts
	while (true)
    {
        mysql_select_db($dle_dbname, $sql_from) or die(mysql_error());
        $sql_result = get_limit_query("SELECT * FROM " . $tws_prefix . "_posts", $sql_from);
        $posts = fetch_array($sql_result);
        if (!$posts) break;
        foreach ($posts as $post)
        {
            $member_id = $users_id[get_member_id($post['poster'])];
            mysql_select_db($dle_dbname, $sql_from) or die(mysql_error());
            $sql_result = mysql_query("SELECT text FROM " . $tws_prefix . "_posts_text WHERE t_post_id='" . $post['post_id'] . "'", $sql_from) or die(mysql_error());
            $post_text = mysql_escape_string(dle_to_lb(mysql_result($sql_result, 0, 0), $site_path));
            mysql_select_db($lb_dbname, $sql_to) or die(mysql_error());
            mysql_query("INSERT INTO " . $lb_prefix . "_posts SET
            topic_id = '" . $topics_id[$post['topic_id']] . "',
            post_date = '" . timetoint($post['post_time']) . "',
            post_member_name = '" . $post['poster'] . "',
            post_member_id = '$member_id',
            text = '" . $post_text . "',
            ip = '" . $post['ip'] . "',
            hide = '0',
            fixed = '0'
            ", $sql_to) or die(mysql_error());

            $posts_id[$post['post_id']] = mysql_insert_id();
        }
    }
    //update posts
    mysql_select_db($lb_dbname, $sql_to) or die(mysql_error());
    $sql_result = mysql_query("SELECT * FROM " . $lb_prefix . "_posts", $sql_to) or die(mysql_error());
    $posts = fetch_array($sql_result);   
    $post_data = array();
    
    foreach ($posts as $post)
    {
        $topic_id = $post['topic_id'];
        $post_id = $post['pid'];
        $post_date = $post['post_date'];
        if (!isset($post_data[$topic_id]) || $post_data[$topic_id]['min_date'] > $post_date) {
            $post_data[$topic_id]['min_date'] = $post_date;
            $post_data[$topic_id]['post_id'] = $post_id;
        }
    }
        mysql_select_db($lb_dbname, $sql_to) or die(mysql_error());
    foreach ($post_data as $item)
    {
        $post_id = $item['post_id'];
        mysql_query("UPDATE " . $lb_prefix . "_posts SET new_topic='1' WHERE pid='$post_id'", $sql_to) or die(mysql_error());
    }
    //update topics
    mysql_select_db($lb_dbname, $sql_to) or die(mysql_error());    
    $sql_result = mysql_query("SELECT * FROM " . $lb_prefix . "_topics", $sql_to) or die(mysql_error());
    $topics = fetch_array($sql_result);
    foreach ($topics as $topic)
    {
        $topic_id = $topic['id'];
        $sql_result = mysql_query("SELECT post_date,pid FROM " . $lb_prefix . "_posts WHERE new_topic='1' AND topic_id='$topic_id'", $sql_to) or die(mysql_error());
        $first_post_date = mysql_result($sql_result, 0, 0);
        $first_post_id = mysql_result($sql_result, 0, 1);
        mysql_query("UPDATE " . $lb_prefix . "_topics SET date_open='$first_post_date', post_id='$first_post_id' WHERE id='$topic_id'", $sql_to) or die(mysql_error());

        $sql_result = mysql_query("SELECT * FROM " . $lb_prefix . "_posts WHERE topic_id='" . $topic['id'] . "'", $sql_to) or die(mysql_error());
        $posts = fetch_array($sql_result);

        $last_date = 0;
        $last_post = null;
        foreach ($posts as $post)
            if ($post['post_date'] > $last_date) {
                $last_date = $post['post_date'];
                $last_post = $post;
            }

        if ($last_post != null) {
            mysql_query("UPDATE " . $lb_prefix . "_topics SET
            last_post_id = '" . $last_post['pid'] . "',
            last_post_member = '" . $last_post['post_member_id'] . "',
            date_last = '" . $last_post['post_date'] . "',
            member_name_last = '" . $last_post['post_member_name'] . "'
            WHERE id='$topic_id'", $sql_to) or die(mysql_error());
        }
    }
    //update forums
        mysql_select_db($lb_dbname, $sql_to) or die(mysql_error());
    $sql_result = mysql_query("SELECT * FROM " . $lb_prefix . "_forums WHERE parent_id > 0", $sql_to) or die(mysql_error());
    $forums = fetch_array($sql_result);
    foreach ($forums as $forum)
    {
        $forum_id = $forum['id'];
        $sql_result = mysql_query("SELECT * FROM " . $lb_prefix . "_topics WHERE forum_id='$forum_id'", $sql_to) or die(mysql_error());
        $topics = fetch_array($sql_result);

        $last_date = 0;
        $last_topic = null;
        foreach ($topics as $topic)
            if ($topic['date_last'] > $last_date) {
                $last_date = $topic['date_last'];
                $last_topic = $topic;
            }
        if ($last_topic != null) {
            mysql_query("UPDATE " . $lb_prefix . "_forums SET
            last_post_member = '" . $last_topic['member_name_last'] . "',
            last_post_member_id = '" . $last_topic['last_post_member'] . "',
            last_post_date = '" . $last_topic['date_last'] . "',
            last_title = '" . $last_topic['title'] . "',
            last_topic_id = '" . $last_topic['id'] . "',
            last_post_id = '" . $last_topic['last_post_id'] . "'
            WHERE id='$forum_id'
            ", $sql_to) or die(mysql_error());
        }
    }
    echo "OK<br/>";
    echo "Polls...";
    //polls
        mysql_select_db($dle_dbname, $sql_from) or die(mysql_error());
    $sql_result = mysql_query("SELECT * FROM " . $tws_prefix . "_poll", $sql_from) or die(mysql_error());
    $polls = fetch_array($sql_result);

        mysql_select_db($lb_dbname, $sql_to) or die(mysql_error());
    foreach ($polls as $poll)
    {
        $topic_id = $topics_id[$poll['top_id']];
        $sql_result = mysql_query("SELECT date_open FROM " . $lb_prefix . "_topics WHERE id='$topic_id'", $sql_to) or die(mysql_error());
        $open_date = mysql_result($sql_result, 0, 0);

        mysql_query("INSERT INTO " . $lb_prefix . "_topics_poll SET
        tid = '$topic_id',
        vote_num = '" . $poll['votes'] . "',
        title = '" . $poll['title'] . "',
        question = '" . $poll['frage'] . "',
        variants = '" . str_replace("<br />", "\r\n", $poll['body']) . "',
        multiple = '" . $poll['multiple'] . "',
        open_date = '$open_date',
        answers = '" . $poll['answer'] . "'
        ", $sql_to) or die(mysql_error());

        $poll_id = mysql_insert_id();
        mysql_query("UPDATE " . $lb_prefix . "_topics SET poll_id='$poll_id' WHERE id='$topic_id'");
        $polls_id[$poll['id']] = $poll_id;
    }
    //poll_logs
        mysql_select_db($dle_dbname, $sql_from) or die(mysql_error());
    $sql_result = mysql_query("SELECT * FROM " . $tws_prefix . "_poll_log", $sql_from) or die(mysql_error());
    $logs = fetch_array($sql_result);
      mysql_select_db($lb_dbname, $sql_to) or die(mysql_error());
    foreach ($logs as $log)
    {
        $topic_id = $topics_id[$log['top_id']];
        $sql_result = mysql_query("SELECT poll_id FROM " . $lb_prefix . "_topics WHERE id='$topic_id'", $sql_to) or die(mysql_error());
        $poll_id = mysql_result($sql_result, 0, 0);

        $member_id = $users_id[$log['member']];
        $sql_result = mysql_query("SELECT * FROM " . $lb_prefix . "_members WHERE member_id='$member_id'", $sql_to) or die(mysql_error());
        $member = fetch_array($sql_result);
        $member = $member[0];

        mysql_query("INSERT INTO " . $lb_prefix . "_topics_poll_logs SET
        poll_id = '$poll_id',
        ip = '" . $member['ip'] . "',
        member_id = '$member_id',
        log_date = '" . timetoint($log['date']) . "',
        member_name = '" . $member['name'] . "',
        answer='0'
        ", $sql_to) or die(mysql_error());
    }
    echo "OK<br/>";
    echo "Ranks...";
    //ranks
        mysql_select_db($dle_dbname, $sql_from) or die(mysql_error());
    $sql_result = mysql_query("SELECT * FROM " . $tws_prefix . "_ranks", $sql_from) or die(mysql_error());
    $ranks = fetch_array($sql_result);
       mysql_select_db($lb_dbname, $sql_to) or die(mysql_error());
    foreach ($ranks as $rank)
    {
        if ($rank['group_id'] > 0) continue;
        mysql_query("INSERT INTO " . $lb_prefix . "_members_ranks SET
        title='" . $rank['rank_title'] . "',
        post_num='" . $rank['rank_min'] . "',
        stars='0'
        ", $sql_to) or die(mysql_error());
    }
    echo "OK<br/>";
    echo "Reputation logs...";
    //reputation logs
        mysql_select_db($dle_dbname, $sql_from) or die(mysql_error());
    $sql_result = mysql_query("SELECT * FROM " . $tws_prefix . "_rate_logs", $sql_from) or die(mysql_error());
    $logs = fetch_array($sql_result);
    
    foreach ($logs as $log)
    {
        $from_name = $log['member'];
        $from_id = get_member_id($from_name);
        $post_id = $posts_id[$log['post_id']];
        mysql_select_db($lb_dbname, $sql_to) or die(mysql_error());
        $sql_result = mysql_query("SELECT post_member_id, post_member_name FROM " . $lb_prefix . "_posts WHERE pid='$post_id'", $sql_to) or die(mysql_error());
        $to_id = mysql_result($sql_result, 0, 0);
        $to_name = mysql_result($sql_result, 0, 1);


        mysql_query("INSERT INTO " . $lb_prefix . "_members_reputation SET
        from_id='$from_id',
        from_name='$from_name',
        to_id='$to_id',
        to_name='$to_name',
        date='" . timetoint($log['date']) . "',
        how='" . (($log['direct'] == 1) ? '-1' : '+1') . "',
        text=''
        ", $sql_to) or die(mysql_error());
    }
    echo "OK<br/>";
    echo "Subscribe...";
    //subscribe
    mysql_select_db($dle_dbname, $sql_from) or die(mysql_error());
    $sql_result = mysql_query("SELECT * FROM " . $tws_prefix . "_topics_watch", $sql_from) or die(mysql_error());
    $watchs = fetch_array($sql_result);
     mysql_select_db($lb_dbname, $sql_to) or die(mysql_error());
    foreach ($watchs as $watch)
    {
        mysql_query("INSERT INTO " . $lb_prefix . "_topics_subscribe SET
        subs_member = '" . $watch['user_id'] . "',
        topic = '" . $watch['topic_id'] . "'
        ", $sql_to) or die(mysql_error());
        $sql_result = mysql_query("SELECT subscribe FROM ".$lb_prefix."_members WHERE member_id='$user_id'", $sql_to) or die(mysql_error());
        $s_text = mysql_result($sql_result, 0, 0);
        $s_text = ($s_text == "") ? $topic_id : $s_text.",".$topic_id;
        mysql_query("UPDATE ".$lb_prefix."_members SET subscribe='$s_text' WHERE member_id='$user_id'", $sql_to) or die(mysql_error());

    }
    echo "OK<br/>";
    echo "Moderators...";
    //moderators
        mysql_select_db($dle_dbname, $sql_from) or die(mysql_error());
    $sql_result = mysql_query("SELECT * FROM " . $tws_prefix . "_config", $sql_from) or die(mysql_error());
    $config = fetch_array($sql_result);
    
    $permissions['global_hidetopic'] = $permissions['global_titletopic'] = $permissions['global_polltopic'] =
    $permissions['global_opentopic'] = $permissions['global_closetopic'] = $permissions['global_fixtopic'] =
    $permissions['global_unfixtopic'] = $permissions['global_movetopic'] = $permissions['global_uniontopic'] =
    $permissions['global_changepost'] = $permissions['global_movepost'] = $permissions['global_unionpost'] = $config['m_edit'];
    
    $permissions['global_deltopic'] = $permissions['global_delpost'] = $config['m_delete'];
    
    $permissions = serialize($permissions);
    
    $sql_result = mysql_query("SELECT * FROM " . $tws_prefix . "_forums", $sql_from) or die(mysql_error());
    $forums = fetch_array($sql_result);

    foreach ($forums as $forum)
    {
        if (!$forum_moders) continue;
        $forum_moders = explode(',', $forum['moderators']);
        $forum_id = $forum['forum_id'];
        mysql_select_db($lb_dbname, $sql_to) or die(mysql_error());
        foreach ($forum_moders as $moder)
        {
            $moder_id = $users_id[$moder];
            $sql_result = mysql_query("SELECT name FROM " . $lb_prefix . "_members WHERE member_id='$moder_id'", $sql_to) or die(mysql_error());
            $moder_name = mysql_result($sql_result, 0, 0);

            mysql_query("INSERT INTO " . $lb_prefix . "_forums_moderator SET
            fm_forum_id='$forum_id',
            fm_member_id='$moder_id',
            fm_member_name='$moder_name',
            fm_group_id='0',
            fm_is_group='0',
            fm_permission='$permissions'
            ", $sql_to) or die(mysql_error());
        }
    }
    
    echo "OK<br/>";
    echo "Ban filters...";
    //banfilters
    mysql_select_db($dle_dbname, $sql_from) or die(mysql_error());
	$sql_result = mysql_query("SELECT * FROM " . $dle_prefix . "_banned  ORDER by id ASC", $sql_from) or die(mysql_error());
	$result = fetch_array($sql_result);
	$begindate = time();
	foreach ($result as $item)
    {
        $ban_member_id = "";
        if ($item['users_id'] != 0) {
            $user_id = $users_id[$item['users_id']];
            $descr = get_member_name($item['users_id']);
            $type = "name";
            $ban_member_id = $user_id;
            mysql_select_db($lb_dbname, $sql_to) or die(mysql_error());
            mysql_query("INSERT INTO " . $lb_prefix . "_members_banfilters SET
			type='$type',
			description = '$descr',
			date='$begindate',
			moder_desc='" . $item['descr'] . "',
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
            mysql_select_db($lb_dbname, $sql_to) or die(mysql_error());
            mysql_query("INSERT INTO " . $lb_prefix . "_members_banfilters SET
			type='$type',
			description = '$descr',
			date='$begindate',
			moder_desc='" . $item['descr'] . "',
			date_end='" . $item['date'] . "',
			ban_days='" . $item['days'] . "',
			ban_member_id='$ban_member_id'
			", $sql_to) or die(mysql_error());
        }
    }
    
    echo "OK<br/>";
    echo "Files...";
   //files
       $files_id = array();
        mysql_select_db($dle_dbname, $sql_from) or die(mysql_error());
    $sql_result = mysql_query("SELECT * FROM " . $tws_prefix . "_files ORDER by id ASC", $sql_from) or die(mysql_error());
    $result = fetch_array($sql_result);
    foreach ($result as $file)
    {
        $author_name = $file['author'];

        $author_id = get_member_id($author_name);
        $post_id = $posts_id[$file['post_id']];
        mysql_select_db($lb_dbname, $sql_to) or die(mysql_error());
        $sql_result = mysql_query("SELECT topic_id FROM " . $lb_prefix . "_posts WHERE pid = '$post_id'", $sql_to) or die(mysql_error());
        $topic_id = mysql_result($sql_result, 0, 0);

        $sql_result = mysql_query("SELECT forum_id FROM " . $lb_prefix . "_topics WHERE id='$topic_id'", $sql_to) or die(mysql_error());
        $forum_id = mysql_result($sql_result, 0, 0);

        $type = is_image($file['onserver']) ? "picture" : "file";

        mysql_query("INSERT INTO " . $lb_prefix . "_topics_files SET
        file_title='" . $file['name'] . "',
        file_name='" . $file['onserver'] . "',
        file_type='$type',
        file_mname='$author_name',
        file_mid='$author_id',
        file_date='" . timetoint($file['date']) . "',
        file_size='0',
        file_count='" . $file['dcount'] . "',
        file_tid='$topic_id',
        file_fid='$forum_id',
        file_convert='1',
        file_pid='$post_id'", $sql_to) or die(mysql_error());
        $files_id[$file['id']] = mysql_insert_id();
    }
    echo "OK<br/>";
    echo "Attachments...";
    //update posts  and attachments
    mysql_select_db($lb_dbname, $sql_to) or die(mysql_error());
    while (true)
    {
        $sql_result = get_limit_query("SELECT * FROM " . $lb_prefix . "_posts", $sql_to);
        $result = fetch_array($sql_result);
        if (!$result) break;
        foreach ($result as $post)
        {
            $text = mysql_escape_string($post['text']);
            preg_match('#forum_attachment=(\d+)#sui', $text, $item);
            if ($item) {

                $file_id = $files_id[$item[1]];
                $text = str_replace("[forum_attachment=" . $item[1] . "]", "[attachment=$file_id]", $text);
                mysql_query("UPDATE " . $lb_prefix . "_posts SET text='$text' WHERE pid='" . $post['pid'] . "'", $sql_to) or die(mysql_error());
            }
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
    echo "Warnings...";

    //warnings
    mysql_select_db($dle_dbname, $sql_from) or die(mysql_error());
    $sql_result = mysql_query("SELECT * FROM " . $tws_prefix . "_uwarnings", $sql_from) or die(mysql_error());
    $warnings = fetch_array($sql_result);
    foreach ($warnings as $warning)
    {
        $user_id = $users_id[$warning['user_id']];
        $moder_id = $users_id[$warning['w_user_id']];
        $moder_name = get_member_name($warning['w_user_id']);
        $date = timetoint($warning['time']);
        $cause = mysql_escape_string($warning['text']);
        mysql_select_db($lb_dbname, $sql_to) or die(mysql_error());
        mysql_query("INSERT INTO " . $lb_prefix . "_members_warning SET
        mid='$user_id',
        moder_id='$moder_id',
        moder_name='$moder_name',
        date='$date',
        description='$cause',
        st_w='1'", $sql_to) or die(mysql_error());
        ;
    }
    mysql_select_db($lb_dbname, $sql_to) or die(mysql_error());
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
    echo "OK<br/><br />";
    return "NO_ERROR";

    }

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8"/>
    <title>Конвертор TWS Forum -> LogicBoard</title>
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
        <div id="header">Конвертор TWSF 1.7.1 --> LogicBoard</div>
        <div id="from_container">
            <label class="container-header">TWS Форум</label>

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

