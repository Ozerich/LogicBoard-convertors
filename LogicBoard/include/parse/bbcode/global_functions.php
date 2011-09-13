<?php


function microTimer_start()
{
    global $starttime;
    $mtime = microtime();
    $mtime = explode( ' ', $mtime );
    $mtime = $mtime[1] + $mtime[0];
    $starttime = $mtime;
}
function microTimer_stop()
{
    global $starttime;
    $mtime = microtime();
    $mtime = explode( ' ', $mtime );
	$mtime = $mtime[1] + $mtime[0];
	$endtime = $mtime;
	$totaltime = round( ($endtime - $starttime), 5 );
	return $totaltime;
}

function filters_input($check = 'all')
{
    require_once LB_CLASS . '/safehtml.php';
    $safehtml = new safehtml( );
    $safehtml->protocolFiltering = "black";

    require_once LB_CLASS . '/safeinput.php';
    $safeinput = new safeinput;
    $safeinput->safeinput_check($check);

    unset($safehtml);
    unset($safeinput);
}

function wrap_word($str)
{
    global $cache_config;

   	$max_lenght = 111111;
	$breaker = ' ';

	$str = preg_replace_callback("#(<|\[)(.+?)(>|\])#", create_function('$matches', 'return str_replace(" ", "--_", $matches[1].$matches[2].$matches[3]);'), $str);

	$words = explode(" ", $str);
	$words = preg_split("# |\n#", $str);

	foreach($words as $word)
    {
		$word."=";
		$split = 1;
		$array = array();
		$count = 0;
		$begin_tag = false;
		$lastKey = '';
		$flag = false;

		for ($i=0; $i < strlen($word); )
        {
			//unicode
			$value = ord($word[$i]);
			if($value > 127){
				if ($value >= 192 && $value <= 223)      $split = 2;
				elseif ($value >= 224 && $value <= 239)  $split = 3;
				elseif ($value >= 240 && $value <= 247)  $split = 4;
			} else $split = 1;
			$key = null;
			for ( $j = 0; $j < $split; $j++, $i++ ) $key .= $word[$i];//


			if($count%$max_lenght == 0 and $count != 0 and !$begin_tag)
            {
				array_push( $array, $breaker);
			}

			array_push( $array, $key );

			//echo $key."--$count--$flag<br/>";
			//если урл
			if(preg_match("#^http://#", $word)) continue;

			if($key == '[' or $key == '<' or $key == '&'){
				$begin_tag = true;

				if($word[$i].$word[$i+1].$word[$i+2] == 'img' or $word[$i].$word[$i+1].$word[$i+2] == 'url' ){
					$flag = true;
				}elseif($word[$i].$word[$i+1].$word[$i+2].$word[$i+3] == '/img' or $word[$i].$word[$i+1].$word[$i+2].$word[$i+3] == '/url'){
					$flag = false;
				}

			}

			if(($key == ']' or $key == '>') and !$flag) { $begin_tag = false;$count--;}

			if($begin_tag and $key == ';' and !$flag) { $begin_tag = false;}

			if(!$begin_tag and !$flag ){
				$count++;
			}
		}
		$new_word = join("", $array);
		$str = str_replace($word, $new_word, $str);
	}

	$str = preg_replace_callback("#(<|\[)(.+?)(>|\])#", create_function('$matches', 'return str_replace("--_", " ", $matches[1].$matches[2].$matches[3]);'), $str);

	return $str;
}

function add_br ($msg = "")
{
    $find = array();
    $find[] = "'\r'";
    $find[] = "'\n'";

    $replace = array();
    $replace[] = "";
    $replace[] = "<br />";

    $msg = preg_replace( $find, $replace, $msg );

    return $msg;
}

function search_tag_preg ($left = "", $word = "", $right = "", $encode = 1)
{
    global $lang_g_function;

    if ($encode)
    {
        preg_match_all ("#\)#isu", $word, $matches);

        if (count($matches[0]) >= 1)
            return $left."s#(".$word.")".$right;

        return $left."<a href=\"".link_on_module("search")."&w=".urlencode($word)."&p=1\" title=\"".$lang_g_function['search_tag_preg']."\">s#(".$word.")</a>".$right;
    }
    else
    {
        return "s#(".urldecode($word).")";
    }
}

function search_tag ($msg, $parse = 1)
{
    if (!$msg)
        return "";

    if ($parse)
        $msg = preg_replace("#([\b|\s|\<br \/>]|^)s\#\((.*?)\)([\b|\s|\!|\?|\.|,]|$)#iuse", "search_tag_preg('\\1', '\\2', '\\3')", $msg);
    else
        $msg = preg_replace("#\<a href=\"(.*?)&w=(.*?)&p=[1|0]\"(.*?)\>s\#\((.*?)\)\</a\>#iuse", "search_tag_preg('', '\\2', '', '0')", $msg);

    return $msg;
}

function parse_word ($msg, $bbcode = true, $wrap_word = true, $words_wilter = true, $bb_allowed = "")
{
    global $member, $cache_group, $cache_config, $cache_forums_filter;
    $msg = trim(htmlspecialchars($msg));

    if ($wrap_word)
        $msg = wrap_word($msg);

    $find = array();
	$find[] = "'\r'";
	$find[] = "'\n'";

   	$replace = array();
	$replace[] = "";
	$replace[] = "<br />";

	$msg = preg_replace( $find, $replace, $msg );

    $msg = str_replace( "{TEMPLATE}", "&#123;TEMPLATE}", $msg );
    $msg = str_replace( "{TEMPLATE_NAME}", "&#123;TEMPLATE_NAME}", $msg );
    $msg = str_replace( "{HOME_LINK}", "&#123;HOME_LINK}", $msg );
    $msg = str_replace( "{SECRET_KEY}", "&#123;SECRET_KEY}", $msg );

    if ($cache_config['posts_searchtag']['conf_value'])
        $msg = search_tag($msg);

    if ($words_wilter)
        $msg = words_wilter($msg);

        $msg = bb_decode($msg, $bb_allowed);

    return $msg;
}

function parse_back_word ($msg, $bbcode = true)
{
    global $member, $cache_group, $cache_config, $cache_forums_filter;

    $msg = str_replace( "<br>", "\n", $msg );
    $msg = str_replace( "<br />", "\n", $msg );

    if ($cache_config['posts_searchtag']['conf_value'])
        $msg = search_tag($msg, 0);

    if ($bbcode)
        $msg = bb_encode($msg);

    return $msg;
}

function totranslit($string, $lower = true)
{
    $translit = array('а' => 'a', 'б' => 'b', 'в' => 'v',
		'г' => 'g', 'д' => 'd', 'е' => 'e',
		'ё' => 'e', 'ж' => 'zh', 'з' => 'z',
		'и' => 'i', 'й' => 'y', 'к' => 'k',
		'л' => 'l', 'м' => 'm', 'н' => 'n',
		'о' => 'o', 'п' => 'p', 'р' => 'r',
		'с' => 's', 'т' => 't', 'у' => 'u',
		'ф' => 'f', 'х' => 'h', 'ц' => 'c',
		'ч' => 'ch', 'ш' => 'sh', 'щ' => 'sch',
		'ь' => '', 'ы' => 'y', 'ъ' => '',
		'э' => 'e', 'ю' => 'yu', 'я' => 'ya',
		"ї" => "yi", "є" => "ye", 'А' => 'A',
        'Б' => 'B', 'В' => 'V',	'Г' => 'G',
        'Д' => 'D', 'Е' => 'E',	'Ё' => 'E',
        'Ж' => 'Zh', 'З' => 'Z', 'И' => 'I',
        'Й' => 'Y', 'К' => 'K',	'Л' => 'L',
        'М' => 'M', 'Н' => 'N',	'О' => 'O',
        'П' => 'P', 'Р' => 'R',	'С' => 'S',
        'Т' => 'T', 'У' => 'U',	'Ф' => 'F',
        'Х' => 'H', 'Ц' => 'C',	'Ч' => 'Ch',
        'Ш' => 'Sh', 'Щ' => 'Sch', 'Ь' => '',
        'Ы' => 'Y', 'Ъ' => '', 'Э' => 'E',
        'Ю' => 'Yu', 'Я' => 'Ya', "Ї" => "yi",
        "Є" => "ye");

    $string = str_replace( ".php", "", $string );
    $string = trim( strip_tags( $string ) );
    $string = preg_replace( "/\s+/ms", "-", $string );
    $string = preg_replace( '#[\-]+#i', '-', $string );

    $string = str_replace( "'", "", $string );
	$string = str_replace( '"', "", $string );

    $string = strtr($string, $translit);
    if ( $lower )
        $string = strtolower( $string );

    return $string;
}

function utf8_strlen($word, $charset = "utf-8")
{
    if (strtolower($charset) == "utf-8")
        return mb_strlen($word, "utf-8");
	else
        return strlen($word);
}

function utf8_substr($s, $offset, $len, $charset = "utf-8")
{
    if (strtolower($charset) == "utf-8")
        return mb_substr($s, $offset, $len, "utf-8");
    else
        return substr($s, $offset, $len);
}

function utf8_strrpos($s, $needle, $charset = "utf-8" )
{
	if (strtolower($charset) == "utf-8")
        return iconv_strrpos($s, $needle, "utf-8");
	else
        return strrpos($s, $needle);
}

function formatsize($size)
{
	if( $size >= 1073741824 )
		$size = round( $size / 1073741824 * 100 ) / 100 . " Gb";
	elseif( $size >= 1048576 )
		$size = round( $size / 1048576 * 100 ) / 100 . " Mb";
	elseif( $size >= 1024 )
		$size = round( $size / 1024 * 100 ) / 100 . " Kb";
	else
		$size = $size . " b";

	return $size;
}

function formatdate($date)
{
	global $time, $lang_g_function;

	if( date( "Ymd", $date ) == date( "Ymd", $time ) )
		$when = $lang_g_function['formatdate_today'].date( "H:i", $date );
	elseif( date( "Ymd", $date ) == date( "Ymd", ($time - 86400) ) )
		$when = $lang_g_function['formatdate_yesterday'].date( "H:i", $date );
	else
		$when = date( "H:i, d.m.Y", $date );

	return $when;
}


function clean_url($url)
{
	if( $url == "" )
		return;

	$url = strtolower($url);
	$url = str_replace('http://', '',$url);
	$url = str_replace("https://", "", $url);
	$url = str_replace("www.",    "", $url);
	$url = explode('/', $url);
	$url = $url[0];

	return $url;
}

function update_cookie($name, $value, $date = "365")
{
    $host = clean_url($_SERVER['HTTP_HOST']);

    $parts = explode('.', $host);
    if(count($parts)>1)
    {
        $tld = array_pop($parts);
        $domain = array_pop($parts).'.'.$tld;
    }
    else
    {
        $domain = array_pop($parts);
    }

    $domain = ".".$domain;

	if($date)
		$date = time() + ($date * 86400);
	else
		$date = FALSE;

	if( phpversion() < 5.2 )
		setcookie( $name, $value, $date, "/", $domain."; HttpOnly" );
	else
		setcookie( $name, $value, $date, "/", $domain, NULL, TRUE );
}

function message ($title, $message, $link = 0)
{
	global $tpl, $lang_g_function;

	$tpl->load_template( 'message.tpl' );

	$mes = "";

	if (is_array($message))
	{
		foreach ($message as $mes_data)
		{
			$mes .= str_replace("{text}", $mes_data, $lang_g_function['message_info']);
		}
	}
	else
		$mes .= str_replace("{text}", $message, $lang_g_function['message_info']);

	if ($link)
		$mes .= $lang_g_function['message_back'];

	$tpl->tags( '{title}', $title );
	$tpl->tags( '{message}', $mes );

	$tpl->compile( 'message' );
	$tpl->clear();
}

function LB_filters($type, $word)
{
	global $cache_banfilters;

	$word =	strtolower($word);
	if ($type == "name" OR $type == "email")
	{
		foreach ($cache_banfilters as $ban)
		{
			if ($ban['type'] == $type)
			{
				$ban['description'] = preg_quote( $ban['description'] );
				$ban['description'] = str_replace( "\*", ".*", $ban['description'] );
				if(preg_match( "#{$ban['description']}#iu", $word ) )
					return true;
			}
		}
	}

	return false;
}

function LB_banned($type, $word)
{
	global $cache_banfilters;

	$word =	strtolower($word);
	if ($type == "name" OR $type == "email")
	{
		foreach ($cache_banfilters as $ban)
		{
			if ($ban['type'] == $type)
			{
				$ban['description'] = preg_quote( $ban['description'] );
				if (! preg_match( "#\*#", $ban['description'] ))
				{
					if(preg_match( "#^{$ban['description']}$#iu", $word ) )
						return true;

				}
			}
		}
	}
	if ($type == "ip")
	{
		foreach ($cache_banfilters as $ban)
		{
			if ($ban['type'] == $type)
			{
				$ban['description'] = preg_quote( $ban['description'] );
				if (preg_match( "#\*#", $ban['description'] ))
				{
					$ban['description'] = str_replace( "\*", "([0-9]|[0-9][0-9]|[0-9][0-9][0-9])*", $ban['description'] );
					if(preg_match( "#{$ban['description']}#i", $word ) )
						return true;
				}
				else
				{
					if(preg_match( "#^{$ban['description']}$#i", $word ) )
						return true;
				}
			}
		}
	}

	return false;
}

function LB_member_ip($options = "")
{
	global $_IP;

    if (!$options)
        return true;

    $options = unserialize($options);

    if (!$options['block_ip'])
        return true;

    $check = explode ("\r\n", $options['block_ip']);
    $result = array();
    $j = 0;
    foreach ($check as $check_ip)
    {
        $ip_mass = explode (".", $check_ip);
        $ip_user = explode (".", $_IP);
        $result[$j] = 1;

        for($i=0;$i<=3;$i++)
        {
            if($ip_mass[$i] != "*")
            {
                if($ip_mass[$i] != $ip_user[$i])
                    $result[$j] = 0;
 			}
        }
        $j ++;
    }

    if (in_array(1, $result))
        return true;
    else
        return false;
}

function ForumsList($categoryid = 0, $parentid = 0, $sublevelmarker = "", $returnstring = "", $access = false)
{
	global $cache_forums;

	if ($parentid != 0)
		$sublevelmarker .= '--&nbsp;';

	if (isset ( $cache_forums ))
	{
		$root_category = array();
		foreach ( $cache_forums as $cats )
		{
			if( $cats['parent_id'] == $parentid )
				$root_category[] = $cats['id'];
		}
		if( count( $root_category ) )
		{
			foreach ( $root_category as $id )
			{
				$category_name = $cache_forums[$id]['title'];

                if ((forum_permission($id, "read_forum") AND $access) OR !$access)
                {
                    if (is_array($categoryid))
                    {
				        if (in_array($id, $categoryid))
					       $returnstring .= "<option value=\"".$id."\" selected>".$sublevelmarker.$category_name."</option>";
				        else
					       $returnstring .= "<option value=\"".$id."\">".$sublevelmarker.$category_name."</option>";
                    }
                    else
                    {
				        if ($categoryid == $id)
					       $returnstring .= "<option value=\"".$id."\" selected>".$sublevelmarker.$category_name."</option>";
				        else
					       $returnstring .= "<option value=\"".$id."\">".$sublevelmarker.$category_name."</option>";
                    }
                }

				$returnstring = ForumsList ( $categoryid, $id, $sublevelmarker, $returnstring, $access );
			}
		}
	}
	return $returnstring;
}

function mail_sender ($member_mail, $member_name = "", $message, $title = "", $file = "", $from_email = "")
{
    global $cache_config;

    require_once(LB_CLASS . '/phpmailer/class.phpmailer.php');

    if ($message == "" OR $member_mail == "")
        return;

    if ($from_email)
        $cache_config['mail_email']['conf_value'] = $from_email;

    if (!$title)
        $title = $cache_config['general_name']['conf_value'];

    $mail = new PHPMailer();

	$body = $message;
	$body = eregi_replace("[\]", '', $body);

	if ($cache_config['mail_sendmetod']['conf_value'] == "php")
	{
        //$mail->IsSendmail();
        $mail->IsMail();
	}
	else
	{
        $mail->IsSMTP();
        $mail->SMTPDebug = false;

        if ($cache_config['mail_smtpname']['conf_value'] != "" AND $cache_config['mail_smtppass']['conf_value'] != "")
            $mail->SMTPAuth = false;
        else
            $mail->SMTPAuth = true;

        $mail->Host       = $cache_config['mail_smtphost']['conf_value'];
        $mail->Port       = $cache_config['mail_smtpport']['conf_value'];
        $mail->Username   = $cache_config['mail_smtpname']['conf_value'];
        $mail->Password   = $cache_config['mail_smtppass']['conf_value'];
	}

    $mail->SetFrom($cache_config['mail_email']['conf_value'], $cache_config['general_name']['conf_value']);
    $mail->AddReplyTo($cache_config['mail_email']['conf_value'], $cache_config['general_name']['conf_value']);
    $mail->AddAddress($member_mail, $member_name);

    $mail->Subject = $title;
    $mail->MsgHTML($body);

    if (is_array($file) AND count($file))
    {
        foreach ($file as $fname)
        {
            $mail->AddAttachment($fname);
        }
    }
    elseif ($file != "")
        $mail->AddAttachment($file);

    $mail->Send();

	unset ($mail);
}

function speedbar ($speedbar = "")
{
    global $cache_config, $redirect_url, $lang_g_function;

    $link = "";
    $speedbar = explode ("|", $speedbar);
    if ($speedbar[0] == "")
    {
        $link = "<a href=\"".$redirect_url."?do=board\">".$cache_config['general_name']['conf_value']."</a> &raquo; ".$lang_g_function['speedbar'];
    }
    else
    {
        $link = implode (" &raquo; ", $speedbar);
    }
    return $link;
}

function online_bots($user_agent = "")
{
    global $cache_user_agent;

    if (!$user_agent)
        return false;

	$found_bot = false;
	foreach($cache_user_agent as $bot)
    {
		if(stristr($user_agent, $bot['search_ua']))
        {
			$found_bot = $bot['name'];
			break;
		}
	}

	return $found_bot;
}

function online_members ($users = "all", $onl_do = "", $onl_op = "", $onl_id = 0)
{
    global $DB, $cache_group, $cache_config, $time, $cache, $lang_g_function, $onl_limit;

    $cache_online_max = intval($cache->take("online_max", "", "statistics"));

    $list = "";
    $onl_g = 0;
    $onl_u = 0;
    $onl_a = 0;
    $onl_h = 0;

    $where = array();
    $where[] = "mo_date > '$onl_limit'";

    if ($onl_do != "")
    {
        $where[] = "mo_loc_do = '{$onl_do}'";

        if ($onl_op != "") $where[] = "mo_loc_op = '{$onl_op}'";
        if ($onl_id) $where[] = "mo_loc_id = '{$onl_id}'";
    }

    $where = implode(" AND ", $where);
    $bots_online = array();

    if ($users == "all")
    {
        $DB->join_select( "mo.*, m.banned", "LEFT", "members_online mo||members m", "mo.mo_member_id=m.member_id", $where, "ORDER by mo_date DESC" );
        while ( $row = $DB->get_row() )
        {
            $onl_a ++;
            $name_bot = online_bots($row['mo_browser']);
            if ($name_bot AND !in_array($name_bot, $bots_online) AND !$row['mo_member_name'])
            {
                $bots_online[] = $name_bot;

                if (!$list)
                    $list .= str_replace("{info}", $name_bot, $lang_g_function['online_members_first']);
                else
                    $list .= str_replace("{info}", $name_bot, $lang_g_function['online_members_next']);
                $onl_g ++;
            }
            elseif ($row['mo_member_name'] AND !$row['mo_hide'])
            {
                $onl_u ++;
                $row['mo_location'] = htmlspecialchars(strip_tags($row['mo_location']));

                if ($row['banned'])
                    $member_name = "<font color=gray>".$row['mo_member_name']."</font>";
                else
                    $member_name = $cache_group[$row['mo_member_group']]['g_prefix_st'].$row['mo_member_name'].$cache_group[$row['mo_member_group']]['g_prefix_end'];

                $info = "<a href=\"".profile_link($row['mo_member_name'], $row['mo_member_id'])."\" title=\"".$row['mo_location']."; ".formatdate($row['mo_date'])."\" onclick=\"ProfileInfo(this, '".$row['mo_member_id']."');return false;\">".$member_name."</a>";

                if (!$list)
                    $list = str_replace("{info}", $info, $lang_g_function['online_members_first']);
                else
                    $list .= str_replace("{info}", $info, $lang_g_function['online_members_next']);
            }
            elseif ($row['mo_hide'])
                $onl_h ++;
            else
                $onl_g ++;

            $name_bot = "";
        }
        $DB->free();
    }
    $online = $onl_g."|".$onl_u."|".$onl_a."|".$onl_h."|".$list;
    $online = explode("|", $online);

    if ($cache_online_max < $onl_a)
    {
        $online_max = $onl_a."|".$time;
        $cache->update("online_max", $online_max, "statistics");
    }

    return $online;
}

function member_group ($id = 0, $banned = false)
{
    global $cache_group, $cache_config;
    if ($banned)
        $group = "<font color=grey>".$cache_config['general_bangroup']['conf_value']."</font>";
    else
        $group = $cache_group[$id]['g_prefix_st'].$cache_group[$id]['g_title'].$cache_group[$id]['g_prefix_end'];
    return $group;
}

function profile_link ($name = "", $id = 0)
{
    global $redirect_url;
    if($name != "")
        $link = $redirect_url."?do=users&op=profile&member_name=".urlencode($name);
    else
        $link = "#";
    return $link;
}

function member_reputation ($reputation = 0, $reputation_freeze = false, $name = "", $id = 0, $group = 0)
{
    global $redirect_url, $cache_group, $member;

    if(!$cache_group[$group]['g_reputation'])
        return "";

    $link = "<a href=\"".$redirect_url."?do=users&op=rep_history&member_name=".urlencode($name)."\">".$reputation."</a>";

    if ($reputation_freeze OR !$cache_group[$member['member_group']]['g_reputation_change'])
        return $link;
    else
        $link = "<a href=\"".$redirect_url."?do=users&op=reputation&act=1&member_name=".urlencode($name)."\"><img src=\"{TEMPLATE}/images/repa_up.gif\" /></a>".$link."<a href=\"".$redirect_url."?do=users&op=reputation&act=0&member_name=".urlencode($name)."\"><img src=\"{TEMPLATE}/images/repa_dn.gif\" /></a>";

    return $link;
}

function member_group_icon ($group = 0)
{
    global $redirect_url, $cache_group;

    if(!$group OR !$cache_group[$group]['g_icon'])
        return "";

    $link = "<img src=\"".$redirect_url.$cache_group[$group]['g_icon']."\" />";

    return $link;
}

function member_favorite ($name = "", $id = 0)
{
    global $redirect_url;
    $link = $redirect_url."?do=users&op=favorite";

    return $link;
}

function member_topics_link ($name = "", $id = 0)
{
    global $redirect_url;
    $link = $redirect_url."?do=users&op=topics&member_name=".urlencode($name);
    return $link;
}

function warning_link ($name = "", $id = 0, $type = 0, $cc = 0)
{
    global $redirect_url, $cache_config;

    if ($cc)
        $link = $cache_config['general_site']['conf_value'];
    else
        $link = $redirect_url;

    if ($type)
        $link = $link."?do=users&op=warning_add&member_name=".urlencode($name);
    else
        $link = $link."?do=users&op=warning&member_name=".urlencode($name);
    return $link;
}

function member_posts_link ($name = "", $id = 0)
{
    global $redirect_url;
    $link = $redirect_url."?do=users&op=posts&member_name=".urlencode($name);
    return $link;
}


function profile_edit_link ($name = "", $id = 0, $act = "edit")
{
    global $redirect_url;
    if ($act == "avatar")
        $link = $redirect_url."?do=users&op=edit_avatar&member_name=".urlencode($name);
    elseif ($act == "options")
        $link = $redirect_url."?do=users&op=options&member_name=".urlencode($name);
    elseif ($act == "password")
        $link = $redirect_url."?do=users&op=edit_pass&member_name=".urlencode($name);
    elseif ($act == "status")
        $link = $redirect_url."?do=users&op=edit_status&member_name=".urlencode($name);
    else
        $link = $redirect_url."?do=users&op=edit&member_name=".urlencode($name);

    return $link;
}

function member_avatar ($img = "")
{
    global $redirect_url;

    if($img == "")
        $link = $redirect_url."uploads/users/no_avatar.png";
    else
        $link = $redirect_url."uploads/users/avatars/".$img;
    return $link;
}

function notice_link ($id = 0)
{
    global $redirect_url;

    if (!$id)
        $online = "#";
    else
        $online = $redirect_url."?do=board&op=notice&id=".$id;

    return $online;
}


function member_online ($id = 0, $date = 0, $limit = 0)
{
    if ($id AND $date >= $limit)
        return true;
    else
        return false;
}

function topic_link ($id = 0, $last = false, $hide = false)
{
    global $redirect_url;
    if ($last)
        $link = $redirect_url."?do=board&op=topic&id=".$id."&go=last";
    elseif ($hide)
        $link = $redirect_url."?do=board&op=topic&id=".$id."&go=hide";
    else
        $link = $redirect_url."?do=board&op=topic&id=".$id;
    return $link;
}

function topic_new_link ($id = 0)
{
    global $redirect_url;
    $link = $redirect_url."?do=board&op=newtopic&id=".$id;
    return $link;
}

function forum_link ($id = 0)
{
    global $redirect_url;
    $link = $redirect_url."?do=board&op=forum&id=".$id;
    return $link;
}

function reply_link ($id = 0, $pid = 0)
{
    global $redirect_url;
    if (!$pid)
        $link = $redirect_url."?do=board&op=reply&id=".$id;
    else
        $link = $redirect_url."?do=board&op=reply&id=".$id."&pid=".$pid;
    return $link;
}

function post_edit_link ($id = 0, $act = "edit", $page = 0)
{
    global $redirect_url, $secret_key;

    if (intval($page))
        $page = "&page=".$page;
    else
        $page = "";

    $link = $redirect_url."?do=board&op=post_edit&act=".$act."&secret_key=".$secret_key."&id=".$id.$page;
    return $link;
}

function comm_edit_link ($id = 0, $act = "edit")
{
    global $redirect_url, $secret_key;
    $link = $redirect_url."?do=users&op=comm_edit&act=".$act."&secret_key=".$secret_key."&id=".$id;
    return $link;
}

function pm_topics_link ($id = 0, $last = false)
{
    global $redirect_url;
    if ($last)
        $link = $redirect_url."?do=users&op=pm_show&id=".$id."&go=last";
    else
        $link = $redirect_url."?do=users&op=pm_show&id=".$id;
    return $link;
}

function pm_new_link ()
{
    global $redirect_url;

    $link = $redirect_url."?do=users&op=pm_new";
    return $link;
}

function pm_member ($name = "", $id = 0)
{
    global $redirect_url;

    if (!$name)
        $link = $redirect_url."?do=users&op=pm";
    else
        $link = $redirect_url."?do=users&op=pm_new&member_name=".urlencode($name);
    return $link;
}

function member_subscribe ()
{
    global $redirect_url;
    $link = $redirect_url."?do=users&op=subscribe";

    return $link;
}

function pm_member_folder ()
{
    global $redirect_url;
    $link = $redirect_url."?do=users&op=pm_folders";
    return $link;
}

function pm_folder_link ($id = 0)
{
    global $redirect_url;
    $link = $redirect_url."?do=users&op=pm&folder=".$id;
    return $link;
}

function topic_favorite ($id = 0)
{
    global $redirect_url, $secret_key;

    $id = intval($id);

    if (!$id)
        $link = "#";
    else
        $link = $redirect_url."?do=board&op=favorite&id=".$id."&secret_key=".$secret_key;
    return $link;
}

function topic_subscribe ($id = 0)
{
    global $redirect_url, $secret_key;

    $id = intval($id);

    if (!$id)
        $link = "#";
    else
        $link = $redirect_url."?do=board&op=subscribe&id=".$id."&secret_key=".$secret_key;
    return $link;
}

function link_on_module ($do = "", $op = "")
{
    global $redirect_url;

    if (!$do)
        return "#";

    if ($op)
        $op = "&op=".$op;

    $link = $redirect_url."?do=".$do.$op;
    return $link;
}

function navigation_link ($do = "board", $op = "", $id = 0, $other = "")
{
    global $redirect_url;

    if ($op != "")
        $op = "&op=".$op;

    $id = intval($id);
    if ($id > 0)
        $id = "&id=".$id;
    else
        $id = "";

    if ($other != "")
        $other = "&".$other;

    $link = $redirect_url."?do=".$do.$op.$id.$other."&page=";
    return $link;
}

function main_forum ($id = 0, $list = "")
{
	global $cache_forums;

    if($id)
    {
	   if ($list == "")
		  $list = $id;
	   else
		  $list .= "|".$id;

	   if ($cache_forums[$id]['parent_id'] != 0 )
		  $list = main_forum($cache_forums[$id]['parent_id'], $list);
    }
    else
        return;

	return $list;
}

function speedbar_forum ($id = 0, $main_link = false)
{
    global $redirect_url, $cache_config, $cache_forums;

    $speedbar = main_forum($id);
    if($speedbar)
    {
	   $speedbar = explode ("|", $speedbar);
	   sort($speedbar);
	   reset($speedbar);
	   if( count( $speedbar ) )
	   {
		  $link_speddbar = "<a href=\"".$redirect_url."\">".$cache_config['general_name']['conf_value']."</a>";
		  foreach ($speedbar as $link_forum)
		  {
			 if ($id == $link_forum)
			 {
                $link_speddbar .= "|<a href=\"".forum_link($link_forum)."\">".$cache_forums[$link_forum]['title']."</a>";
			 }
			 else
			 {
				$link_speddbar .= "|<a href=\"".forum_link($link_forum)."\">".$cache_forums[$link_forum]['title']."</a>";
			 }
		  }
	   }
       else
            $link_speddbar = $cache_config['general_name']['conf_value'];
    }
    else
        $link_speddbar = $cache_config['general_name']['conf_value'];

    if (!$id AND $main_link)
        $link_speddbar = "<a href=\"".$redirect_url."\">".$cache_config['general_name']['conf_value']."</a>";

    return $link_speddbar;
}

function forum_permission ($id = 0, $perm = "") // права на форумы, настройки форума (не модерация)
{
    global $cache_forums, $member;

    if (!$id OR $perm == "")
        return false;

    if ($perm == "read_forum")
    {
        $id_mass = main_forum($id);
        $id_mass = explode ("|", $id_mass);

        $category = array_pop($id_mass); // проверки доступа у категории
        if ($cache_forums[$category]['group_permission'] != 0)
        {
             $category = explode (",", $cache_forums[$category]['group_permission']);
             if (!in_array($member['member_group'], $category))
                return false;
        }

	    sort($id_mass); // сортировка массива, переворачиваем, начиная от выбранного форума и заканчивая главным форумом (не категории)
        reset($id_mass);
        if( count( $id_mass ) )
        {
            foreach ($id_mass as $idd)
            {
                $forum_permission = unserialize($cache_forums[$idd]['group_permission']);
                if($forum_permission[$member['member_group']][$perm] != 1)
                    return false;
		    }
        }
    }

    if ($cache_forums[$id]['parent_id'] != 0)
    {
        $forum_permission = unserialize($cache_forums[$id]['group_permission']);
        if($forum_permission[$member['member_group']][$perm] == 1)
            return true;
        else
            return false;
    }
    else
        return true;
}

function forum_password ($id = 0)
{
    global $cache_forums, $member, $_IP;

    if($member['member_group'] != 5)
        $who = $member['name'];
    else
        $who = $_IP;

    if($_COOKIE['LB_password_forum_'.$id] == md5($who.$cache_forums[$id]['password']))
        return false;

    if ($cache_forums[$id]['password_notuse'] != "")
    {
        $notuse = explode(",", $cache_forums[$id]['password_notuse']);
        if (in_array($member['member_group'], $notuse))
            return false;
    }

    if ($cache_forums[$id]['password'] != "")
        return true;
    else
        return false;
}

function forum_all_password ($id = 0)
{
    $id_forum_pass = 0;
    $id_f_pass = main_forum ($id);
    $id_f_pass = explode ("|", $id_f_pass);
    array_pop($id_f_pass); // вырезаем категорию из массива
    sort($id_f_pass); // сортировка массива, переворачиваем, начиная от выбранного форума и заканчивая главным форумом (не категории)
    reset($id_f_pass);
    if( count( $id_f_pass ) )
    {
        foreach ($id_f_pass as $idd_f)
        {
            if(forum_password($idd_f))
            {
                return $idd_f;
            }
        }
    }

    return false;
}

function forum_options ($id = 0, $link = "")
{
    global $member, $cache_group, $lang_g_function;

    $options = "";

    if ($cache_group[$member['member_group']]['g_show_hiden'] OR $cache_group[$member['member_group']]['g_supermoders'] OR forum_options_topics($id, "hideshow"))
    {
        $options .= str_replace("{link}", $link."&hide=topics", $lang_g_function['forum_options_hide_topics']);
        $options .= str_replace("{link}", $link."&hide=posts", $lang_g_function['forum_options_hide_posts']);
    }

    return $options;
}

function forum_options_topics_check ($id = 0)
{
      global $member, $cache_forums_moder;

      foreach ($cache_forums_moder as $moder)
      {
            if ($moder['fm_forum_id'] == $id)
            {
                if ($moder['fm_forum_id'] == $id AND ($moder['fm_member_id'] == $member['member_id'] OR ($moder['fm_is_group'] == 1 AND $moder['fm_group_id'] == $member['member_group'])))
                    return $moder['fm_permission'];
            }
      }

      return false;
}

function forum_options_topics ($id = 0, $check_func = "") // права на форумы, модераторы, проверка просмотра тем, ответов и т.п.
{
    global $member, $cache_group, $cache_forums_moder, $lang_g_function;

    $options = "";
    $access = "";

    if ($check_func == "hideshow" AND $cache_group[$member['member_group']]['g_show_hiden'])
        return true;

    if ($check_func == "reply_close" AND $cache_group[$member['member_group']]['g_reply_close'])
        return true;

    if ($cache_group[$member['member_group']]['g_supermoders'] OR $member['member_group'] == 1)
    {
        if ($check_func != "")
            return true;

        $options .= "<option value=\"1\">".$lang_g_function['forum_options_topics_open']."</option>";
        $options .= "<option value=\"2\">".$lang_g_function['forum_options_topics_close']."</option>";
        $options .= "<option value=\"3\">".$lang_g_function['forum_options_topics_hide']."</option>";
        $options .= "<option value=\"4\">".$lang_g_function['forum_options_topics_publ']."</option>";
        $options .= "<option value=\"5\">".$lang_g_function['forum_options_topics_up']."</option>";
        $options .= "<option value=\"6\">".$lang_g_function['forum_options_topics_down']."</option>";
        $options .= "<option value=\"7\">".$lang_g_function['forum_options_topics_move']."</option>";
        $options .= "<option value=\"8\">".$lang_g_function['forum_options_topics_union']."</option>";
        $options .= "<option value=\"10\">".$lang_g_function['forum_options_topics_subscribe']."</option>";
        $options .= "<option value=\"9\">".$lang_g_function['forum_options_topics_del']."</option>";
    }
    else
    {
        $find = false;

        if (count($cache_forums_moder) == 0)
            return false;

        foreach ($cache_forums_moder as $moder)
        {
            if ($moder['fm_forum_id'] == $id)
            {
                if ($moder['fm_forum_id'] == $id AND ($moder['fm_member_id'] == $member['member_id'] OR ($moder['fm_is_group'] == 1 AND $moder['fm_group_id'] == $member['member_group'])))
                {
                    $access = $moder['fm_permission'];
                    $find = true;
                }
            }
        }

        if (!$find)
        {
            $id_f_moder = main_forum ($id);
            $id_f_moder = explode ("|", $id_f_moder);
            if( count( $id_f_moder ) )
            {
                foreach ($id_f_moder as $idd_f)
                {
                    $access = forum_options_topics_check ($idd_f);
                    if ($access)
                    {
                        break;
                    }
                }
            }

        }

        if($access)
        {
            $access = unserialize($access);

            if (!$check_func)
            {
                if ($access['global_opentopic']) $options .= "<option value=\"1\">".$lang_g_function['forum_options_topics_open']."</option>";
                if ($access['global_closetopic']) $options .= "<option value=\"2\">".$lang_g_function['forum_options_topics_close']."</option>";

                if ($access['global_hidetopic'])
                {
                    $options .= "<option value=\"3\">".$lang_g_function['forum_options_topics_hide']."</option>";
                    $options .= "<option value=\"4\">".$lang_g_function['forum_options_topics_publ']."</option>";
                }

                if ($access['global_fixtopic']) $options .= "<option value=\"5\">".$lang_g_function['forum_options_topics_up']."</option>";
                if ($access['global_unfixtopic']) $options .= "<option value=\"6\">".$lang_g_function['forum_options_topics_down']."</option>";
                if ($access['global_movetopic']) $options .= "<option value=\"7\">".$lang_g_function['forum_options_topics_move']."</option>";
                if ($access['global_uniontopic']) $options .= "<option value=\"8\">".$lang_g_function['forum_options_topics_union']."</option>";
                if ($access['global_deltopic']) $options .= "<option value=\"9\">".$lang_g_function['forum_options_topics_del']."</option>";
            }
            else
            {
                if ($access['global_opentopic'] AND $check_func == "opentopic") return true;
                elseif (!$access['global_opentopic'] AND $check_func == "opentopic") return false;

                if ($access['global_closetopic'] AND $check_func == "closetopic") return true;
                elseif (!$access['global_closetopic'] AND $check_func == "closetopic") return false;

                if ($access['global_hidetopic'] AND $check_func == "hidetopic") return true;
                elseif (!$access['global_hidetopic'] AND $check_func == "hidetopic") return false;

                if ($access['global_fixtopic'] AND $check_func == "fixtopic") return true;
                elseif (!$access['global_fixtopic'] AND $check_func == "fixtopic") return false;

                if ($access['global_unfixtopic'] AND $check_func == "unfixtopic") return true;
                elseif (!$access['global_unfixtopic'] AND $check_func == "unfixtopic") return false;

                if ($access['global_uniontopic'] AND $check_func == "uniontopic") return true;
                elseif (!$access['global_uniontopic'] AND $check_func == "uniontopic") return false;

                if ($access['global_movetopic'] AND $check_func == "movetopic") return true;
                elseif (!$access['global_movetopic'] AND $check_func == "movetopic") return false;

                if ($access['global_deltopic'] AND $check_func == "deltopic") return true;
                elseif (!$access['global_deltopic'] AND $check_func == "deltopic") return false;

                if ($access['global_hideshow'] AND $check_func == "hideshow") return true;
                elseif (!$access['global_hideshow'] AND $check_func == "hideshow") return false;

                if ($access['global_titletopic'] AND $check_func == "titletopic") return true;
                elseif (!$access['global_titletopic'] AND $check_func == "titletopic") return false;

                if ($access['global_polltopic'] AND $check_func == "polltopic") return true;
                elseif (!$access['global_polltopic'] AND $check_func == "polltopic") return false;

                if ($access['global_delpost'] AND $check_func == "delpost") return true;
                elseif (!$access['global_delpost'] AND $check_func == "delpost") return false;

                if ($access['global_changepost'] AND $check_func == "changepost") return true;
                elseif (!$access['global_changepost'] AND $check_func == "changepost") return false;

                if ($access['global_movepost'] AND $check_func == "movepost") return true;
                elseif (!$access['global_movepost'] AND $check_func == "movepost") return false;
            }
        }
    }

    if ($check_func != "")
        return false;

    return $options;
}

function forum_moderation() // является ли пользователь модератором чего-либо
{
    global $member, $cache_group, $cache_forums_moder;

    if ($cache_group[$member['member_group']]['g_supermoders'] == 1 OR $member['member_group'] == 1)
        return true;

    foreach ($cache_forums_moder as $moder)
    {
        if ($moder['fm_member_id'] == $member['member_id'] OR ($moder['fm_is_group'] == 1 AND $moder['fm_group_id'] == $member['member_group']))
        {
            return true;
        }
    }

    return false;
}

function group_permission($check = "")
{
    global $member, $cache_group;

    if (!$check)
        return false;

    $access = unserialize($cache_group[$member['member_group']]['g_access']);

    if ($access[$check])
        return true;
    else
        return false;

    return false;
}

function forum_options_topics_mas ($fid = 0, $id = 0, $type = "") // права на посты, массовые действия
{
    global $member, $cache_group, $cache_forums_moder, $lang_g_function;

    $options = "";
    $access = "";

    if ($cache_group[$member['member_group']]['g_supermoders'] OR $member['member_group'] == 1)
    {
        if ($type != "posts" AND $type != "topic")
            return true;

        if($type == "posts")
        {
            $options .= "<option value=\"1\">".$lang_g_function['forum_options_topics_mas_p_hide']."</option>";
            $options .= "<option value=\"2\">".$lang_g_function['forum_options_topics_mas_p_publ']."</option>";
            $options .= "<option value=\"3\">".$lang_g_function['forum_options_topics_mas_p_edit']."</option>";
            $options .= "<option value=\"7\">".$lang_g_function['forum_options_topics_mas_p_fix']."</option>";
            $options .= "<option value=\"8\">".$lang_g_function['forum_options_topics_mas_p_unfix']."</option>";
            $options .= "<option value=\"4\">".$lang_g_function['forum_options_topics_mas_p_union']."</option>";
            $options .= "<option value=\"5\">".$lang_g_function['forum_options_topics_mas_p_move']."</option>";
            $options .= "<option value=\"6\">".$lang_g_function['forum_options_topics_mas_p_del']."</option>";
        }
        elseif($type == "topic")
        {
            $options .= "<option value=\"10\">".$lang_g_function['forum_options_topics_mas_t_unsubsc']."</option>";
            $options .= "<option value=\"1\">".$lang_g_function['forum_options_topics_mas_t_hide']."</option>";
            $options .= "<option value=\"2\">".$lang_g_function['forum_options_topics_mas_t_pub']."</option>";
            $options .= "<option value=\"3\">".$lang_g_function['forum_options_topics_mas_t_edit']."</option>";
            $options .= "<option value=\"4\">".$lang_g_function['forum_options_topics_mas_t_up']."</option>";
            $options .= "<option value=\"5\">".$lang_g_function['forum_options_topics_mas_t_down']."</option>";
            $options .= "<option value=\"6\">".$lang_g_function['forum_options_topics_mas_t_open']."</option>";
            $options .= "<option value=\"7\">".$lang_g_function['forum_options_topics_mas_t_close']."</option>";
            $options .= "<option value=\"8\">".$lang_g_function['forum_options_topics_mas_t_move']."</option>";
            $options .= "<option value=\"9\">".$lang_g_function['forum_options_topics_mas_t_del']."</option>";
        }
    }
    else
    {
        $find = false;

        if (count($cache_forums_moder) == 0)
            return false;

        foreach ($cache_forums_moder as $moder)
        {
            if ($moder['fm_forum_id'] == $fid)
            {
                if ($moder['fm_forum_id'] == $fid AND ($moder['fm_member_id'] == $member['member_id'] OR ($moder['fm_is_group'] == 1 AND $moder['fm_group_id'] == $member['member_group'])))
                {
                    $access = $moder['fm_permission'];
                    $find = true;
                }
            }
        }

        if (!$find)
        {
            $id_f_moder = main_forum ($fid);
            $id_f_moder = explode ("|", $id_f_moder);
            if( count( $id_f_moder ) )
            {
                foreach ($id_f_moder as $idd_f)
                {
                    $access = forum_options_topics_check ($idd_f);
                    if ($access)
                    {
                        break;
                    }
                }
            }
        }

        if($access)
        {
            $access = unserialize($access);

            if ($type == "posts" OR $type == "check")
            {
                if ($access['global_hidetopic'] AND $type != "check")
                {
                    $options .= "<option value=\"1\">".$lang_g_function['forum_options_topics_mas_p_hide']."</option>";
                    $options .= "<option value=\"2\"".$lang_g_function['forum_options_topics_mas_p_publ']."</option>";
                }
                elseif ($access['global_hidetopic'] AND $type == "check") return true;

                if ($access['global_changepost'] AND $type != "check") $options .= "<option value=\"3\">".$lang_g_function['forum_options_topics_mas_p_edit']."</option>";
                elseif ($access['global_changepost'] AND $type == "check") return true;

                if ($access['global_fixedpost'] AND $type != "check") $options .= "<option value=\"7\">".$lang_g_function['forum_options_topics_mas_p_fix']."</option>";
                elseif ($access['global_fixedpost'] AND $type == "check") return true;

                if ($access['global_fixedpost'] AND $type != "check") $options .= "<option value=\"8\">".$lang_g_function['forum_options_topics_mas_p_unfix']."</option>";
                elseif ($access['global_fixedpost'] AND $type == "check") return true;

                if ($access['global_unionpost'] AND $type != "check") $options .= "<option value=\"4\">".$lang_g_function['forum_options_topics_mas_p_union']."</option>";
                elseif ($access['global_unionpost'] AND $type == "check") return true;

                if ($access['global_movepost'] AND $type != "check") $options .= "<option value=\"5\">".$lang_g_function['forum_options_topics_mas_p_move']."</option>";
                elseif ($access['global_movepost'] AND $type == "check") return true;

                if ($access['global_delpost'] AND $type != "check") $options .= "<option value=\"6\">".$lang_g_function['forum_options_topics_mas_p_del']."</option>";
                elseif ($access['global_delpost'] AND $type == "check") return true;
            }
            elseif($type == "topic" OR $type == "check")
            {
                if ($access['global_hidetopic'] AND $type != "check")
                {
                    $options .= "<option value=\"1\">".$lang_g_function['forum_options_topics_mas_t_hide']."</option>";
                    $options .= "<option value=\"2\">".$lang_g_function['forum_options_topics_mas_t_pub']."</option>";
                }
                elseif ($access['global_hidetopic'] AND $type == "check") return true;

                if (($access['global_titletopic'] OR $access['global_polltopic']) AND $type != "check") $options .= "<option value=\"3\">".$lang_g_function['forum_options_topics_mas_t_edit']."</option>";
                elseif (($access['global_titletopic'] OR $access['global_polltopic']) AND $type == "check") return true;

                if ($access['global_fixtopic'] AND $type != "check") $options .= "<option value=\"4\">".$lang_g_function['forum_options_topics_mas_t_up']."</option>";
                elseif ($access['global_fixtopic'] AND $type == "check") return true;

                if ($access['global_unfixtopic'] AND $type != "check") $options .= "<option value=\"5\">".$lang_g_function['forum_options_topics_mas_t_down']."</option>";
                elseif ($access['global_unfixtopic'] AND $type == "check") return true;

                if ($access['global_opentopic'] AND $type != "check") $options .= "<option value=\"6\">".$lang_g_function['forum_options_topics_mas_t_open']."</option>";
                elseif ($access['global_opentopic'] AND $type == "check") return true;

                if ($access['global_closetopic'] AND $type != "check") $options .= "<option value=\"7\">".$lang_g_function['forum_options_topics_mas_t_close']."</option>";
                elseif ($access['global_closetopic'] AND $type == "check") return true;

                if ($access['global_movetopic'] AND $type != "check") $options .= "<option value=\"8\">".$lang_g_function['forum_options_topics_mas_t_move']."</option>";
                elseif ($access['global_movetopic'] AND $type == "check") return true;

                if ($access['global_deltopic'] AND $type != "check") $options .= "<option value=\"9\">".$lang_g_function['forum_options_topics_mas_t_del']."</option>";
                elseif ($access['global_deltopic'] AND $type == "check") return true;
            }
            else
            {
                if ($access['global_titletopic'] AND $type == "titletopic") return true;
                elseif (!$access['global_titletopic'] AND $type == "titletopic") return false;

                if ($access['global_polltopic'] AND $type == "polltopic") return true;
                elseif (!$access['global_polltopic'] AND $type == "polltopic") return false;

                if ($access['global_uniontopic'] AND $type == "uniontopic") return true;
                elseif (!$access['global_uniontopic'] AND $type == "uniontopic") return false;

                if ($access['global_opentopic'] AND $type == "opentopic") return true;
                elseif (!$access['global_opentopic'] AND $type == "opentopic") return false;

                if ($access['global_closetopic'] AND $type == "closetopic") return true;
                elseif (!$access['global_closetopic'] AND $type == "closetopic") return false;

                if ($access['global_hidetopic'] AND $type == "hidetopic") return true;
                elseif (!$access['global_hidetopic'] AND $type == "hidetopic") return false;

                if ($access['global_fixtopic'] AND $check_func == "fixtopic") return true;
                elseif (!$access['global_fixtopic'] AND $check_func == "fixtopic") return false;

                if ($access['global_unfixtopic'] AND $type == "unfixtopic") return true;
                elseif (!$access['global_unfixtopic'] AND $type == "unfixtopic") return false;

                if ($access['global_movetopic'] AND $type == "movetopic") return true;
                elseif (!$access['global_movetopic'] AND $type == "movetopic") return false;

                if ($access['global_deltopic'] AND $type == "deltopic") return true;
                elseif (!$access['global_deltopic'] AND $type == "deltopic") return false;

                if ($access['global_hideshow'] AND $type == "hideshow") return true;
                elseif (!$access['global_hideshow'] AND $type == "hideshow") return false;

                if ($access['global_delpost'] AND $type == "delpost") return true;
                elseif (!$access['global_delpost'] AND $type == "delpost") return false;

                if ($access['global_changepost'] AND $type == "changepost") return true;
                elseif (!$access['global_changepost'] AND $type == "changepost") return false;

                if ($access['global_unionpost'] AND $type == "unionpost") return true;
                elseif (!$access['global_unionpost'] AND $type == "unionpost") return false;

                if ($access['global_movepost'] AND $type == "movepost") return true;
                elseif (!$access['global_movepost'] AND $type == "movepost") return false;

                if ($access['global_fixedpost'] AND $type == "fixedpost") return true;
                elseif (!$access['global_fixedpost'] AND $type == "fixedpost") return false;

                if ($access['global_fixedpost'] AND $type != "fixedpost") return true;
                elseif (!$access['global_fixedpost'] AND $type == "fixedpost") return false;
            }
        }
    }

    if ($options == "")
        return false;

    return $options;
}

function member_publ_access ($type = 1)
{
    global $member, $logged;

    if ($type == 0)
        return false;

    if ($logged)
    {
        if ($member['member_group'] == 1)
            return true;

        if ($member['limit_publ'] == $type OR $member['limit_publ'] == 3)
            return false;
        else
            return true;
    }
    else
        return true;

    return false;
}

function member_publ_info ()
{
    global $member, $logged, $cache_limitpubl, $lang_g_function;

    $limit_publ_end = 0;

    if(count($cache_limitpubl))
    {
        foreach ($cache_limitpubl as $limit_publ)
        {
            if ($limit_publ['limit_member_id'] == $member['member_id'])
            {
                $limit_publ_end = $limit_publ['limit_end'];
                break;
            }
        }
    }

    if ($limit_publ_end)
        $message = str_replace("{date}", formatdate($limit_publ_end), $lang_g_function['member_publ_info1']);
    else
        $message = $lang_g_function['member_publ_info2'];

    return $message;
}

function forum_options_topics_author ($type = "", $opt = "all")
{
    global $member, $cache_group, $lang_g_function;

    $options = "";
    $access = unserialize($cache_group[$member['member_group']]['g_access']);

    if (($access['local_titletopic'] OR $access['local_polltopic']) AND $type != "check") $options .= "<option value=\"3\">".$lang_g_function['forum_options_topics_author_edit']."</option>";
    elseif (($access['local_titletopic'] OR $access['local_polltopic']) AND $type == "check" AND ($opt == "edit" OR $opt == "all")) return true;

    if ($access['local_opentopic'] AND $type != "check") $options .= "<option value=\"6\">".$lang_g_function['forum_options_topics_author_open']."</option>";
    elseif ($access['local_opentopic'] AND $type == "check" AND ($opt == "open" OR $opt == "all")) return true;

    if ($access['local_closetopic'] AND $type != "check") $options .= "<option value=\"7\">".$lang_g_function['forum_options_topics_author_close']."</option>";
    elseif ($access['local_closetopic'] AND $type == "check" AND ($opt == "close" OR $opt == "all")) return true;

    if ($access['local_deltopic'] AND $type != "check") $options .= "<option value=\"1\">".$lang_g_function['forum_options_topics_author_hide']."</option>";
    elseif ($access['local_deltopic'] AND $type == "check" AND ($opt == "delete" OR $opt == "all")) return true;

    if ($options == "")
        return false;

    return $options;
}
eval(base64_decode('JHByYXZBdnRvdGE9IjxhIGhyZWY9XCJodHRwOi8vbG9naWNib2FyZC5ydS9cIiB0YXJnZXQ9XCJibGFua1wiPkxvZ2ljQm9hcmQ8L2E+Ijs='));
function meta_info ($text = "", $type = "", $is_forum = false, $other = "")
{
    global $redirect_url, $cache_config, $cache_forums;

    $info = $cache_config['general_name']['conf_value'];

    if ($is_forum)
        $text = intval($text);
    else
        $text = htmlspecialchars(strip_tags(stripslashes($text)), ENT_QUOTES);

    if ($other)
        $other = strip_tags(stripslashes($other));

    if ($type == "title")
    {
        if ($is_forum)
        {
            $speedbar = main_forum($text);
            if($speedbar)
            {
                $info = "";
                $speedbar = explode ("|", $speedbar);
                if( count( $speedbar ) )
                {
                    foreach ($speedbar as $link_forum)
                    {
                    $info .= $cache_forums[$link_forum]['title']." &raquo; ";
                    }
                }
                $info .= $cache_config['general_name']['conf_value'];
            }
        }

        if ($other)
            $info = $other." &raquo; ".$info;

        return $info;
    }

   	$find = array("'\r'", "'\n'");

    if ($type == "description")
    {
        if ($is_forum)
            $text = $cache_forums[$text]['meta_desc'];

        if (!$text)
            $text = htmlspecialchars(strip_tags(stripslashes(preg_replace($find, "", $cache_config['general_meta_desc']['conf_value']))), ENT_QUOTES);

        if (utf8_strlen($text) > 200)
            $text = utf8_substr($text, 0, 200);

        $info = $text;

        return $info;
    }

    if ($type == "keyword")
    {
        if ($is_forum)
            $text = $cache_forums[$text]['meta_key'];

        if (!$text)
            $text = htmlspecialchars(strip_tags(stripslashes(preg_replace($find, "", $cache_config['general_meta_key']['conf_value']))), ENT_QUOTES);

        if (utf8_strlen($text) > 1000)
            $text = utf8_substr($text, 0, 1000);

        $info = $text;

        return $info;
    }

    return $info;
}

function change_template ()
{
    global $cache_config;

   	$templates_list = array ();
    $skin = $cache_config['template_name']['conf_value'];

	$temp_main = opendir( LB_MAIN . "/templates/" );

	while ( false !== ($temp_dir = readdir( $temp_main )) )
    {
		if(@is_dir( LB_MAIN . "/templates/".$temp_dir ) AND $temp_dir != "." AND $temp_dir != "..")
			$templates_list[] = $temp_dir;
	}

	closedir( $temp_main );
	sort($templates_list);

	$skin_list = "<form method=\"post\" action=\"\" name=\"change_template\"><select id=\"ex33\" onchange=\"submit();\" name=\"skin_name\" class=\"lbselect\">";

	foreach ( $templates_list as $template )
    {
		if( strtolower($template) == strtolower($skin) )
            $selected = "selected=\"selected\"";
		else
            $selected = "";

		$skin_list .= "<option value=\"".$template."\" ".$selected.">".$template."</option>";
	}

	$skin_list .= '</select><input type="hidden" name="change_template" value="yes" /></form>';

	return $skin_list;
}

function captcha_dop ()
{
    global $cache_config;

    $question = explode( "\r\n", $cache_config['security_captcha_dop']['conf_value'] );
    if ($cache_config['security_captcha_dop_v']['conf_value'])
        $question_num = array_rand($question);
    else
        $question_num = 0;

    $question_keys = explode( "=", $question[$question_num] );
    unset($question);
    list($question, $answer) = $question_keys;
    $_SESSION['captcha_keystring_q_num'] = $question_num;
    $_SESSION['captcha_keystring_q'] = $question;

    return $question;
}

function captcha_dop_check ($type = "")
{
    global $cache_config;

    if ($cache_config['security_captcha_dop_out']['conf_value'] == "")
        return false;

    $security_cdo = explode(",", $cache_config['security_captcha_dop_out']['conf_value']);
    if (!in_array($type, $security_cdo) OR $cache_config['security_captcha_dop']['conf_value'] == "")
       return false;

    return true;
}

function captcha_dop_check_answer ()
{
    global $cache_config;

    $_SESSION['captcha_keystring_q_num'] = intval($_SESSION['captcha_keystring_q_num']);

    if (!isset($_SESSION['captcha_keystring_q_num']) OR !isset($_SESSION['captcha_keystring_q']) OR !$_SESSION['captcha_keystring_a'])
        return false;

    $_SESSION['captcha_keystring_a'] = strtolower($_SESSION['captcha_keystring_a']);

    $question = explode( "\r\n", $cache_config['security_captcha_dop']['conf_value'] );

    if (!$cache_config['security_captcha_dop_v']['conf_value'] AND $_SESSION['captcha_keystring_q_num'] != 0)
        return false;

    if ($question[$_SESSION['captcha_keystring_q_num']] == "")
        return false;

    $question_keys = explode( "=", $question[$_SESSION['captcha_keystring_q_num']] );
    unset($question);
    list($question, $answer) = $question_keys;

    if ($question == $_SESSION['captcha_keystring_q'] AND strtolower($answer) == $_SESSION['captcha_keystring_a'])
        return true;
    else
        return false;
}

function words_wilter ($text = "")
{
    global $cache_config, $cache_forums_filter;

    if (!$text)
        return "";

    $find = array ();
    $replace = array ();

    if (count($cache_forums_filter))
    {
        foreach($cache_forums_filter as $filter)
        {
            if ($filter['type'] == 1)
            {
                $find[] = "#([\b|\s|\<br \/>]|^)".preg_quote( $filter['word'], "#" )."([\b|\s|\!|\?|\.|,]|$)#iu";
                if ($filter['word_replace'])
                    $replace[] = "$1".$filter['word_replace']."$2";
                else
                    $replace[] = "\\1\\2";
            }
            else
            {
                $find[] = "#".preg_quote($filter['word'], "#")."#iu";
                if ($filter['word_replace'])
                    $replace[] = $filter['word_replace'];
                else
                    $replace[] = "";
            }
        }
    }
    else
        return $text;

    if (!count($find))
        return "";

    $text = preg_replace( $find, $replace, $text );

    return $text;
}

function select_code($name = "", $massive, $selected = "", $lbselect = true)
{
    if ($lbselect)
        $select = "<select name=\"".$name."\" id=\"".$name."\" class=\"lbselect\">";
    else
        $select = "<select name=\"".$name."\" id=\"".$name."\">";

    foreach ($massive as $key => $value)
    {
        if ($selected == $key)
            $select .= "<option value=\"".$key."\" selected>".$value."</option>";
        else
            $select .= "<option value=\"".$key."\">".$value."</option>";
    }

    $select .= "</select>";

    return $select;
}

function send_new_pm($title = "", $pm_to_id = 0, $text = "", $email = "", $mname = "", $mf_options = "", $system = 0, $pm_topic = 0) // функция создания нового ЛС и отправки уведомлений системы
{
    global $DB, $time, $cache_config, $member, $topic_id, $cache_email, $lang_g_function;

    $key_post = md5($time.$title);

    if ($system)
    {
        $member_name = $cache_config['pm_bot']['conf_value'];
        $member_id = 0;
    }
    else
    {
        $member_name = $member['name'];
        $member_id = $member['member_id'];
    }

    $new_pm_plus = 1;

    if (!$pm_topic)
    {
        $DB->insert("title = '{$title}', topic_member = '{$pm_to_id}', member_start = '{$member_id}', member_to = '{$pm_to_id}', start_date = '{$time}', last_date = '{$time}', last_member = '{$member_name}', last_member_id = '{$member_id}', folder = 'inbox', pmt_key = '{$key_post}', isdel = '{$system}'", "members_pm_topic");
        $topic_id = $DB->insert_id();
    }
    else
    {
        $topic = $DB->one_select( "*", "members_pm_topic", "id = '{$pm_topic}'" );
        if ($topic['id'])
        {
            $topic_id = $topic['id'];
            $key_post = $topic['pmt_key'];

            if ($topic['isread'] == 0)
                $new_pm_plus = 0;
        }
        else
        {
            $DB->insert("title = '{$title}', topic_member = '{$pm_to_id}', member_start = '{$member_id}', member_to = '{$pm_to_id}', start_date = '{$time}', last_date = '{$time}', last_member = '{$member_name}', last_member_id = '{$member_id}', folder = 'inbox', pmt_key = '{$key_post}', isdel = '{$system}'", "members_pm_topic");
            $topic_id = $DB->insert_id();
        }
        $DB->free($topic);
    }

    if ($new_pm_plus)
        $DB->update("pm_new=pm_new+1, pm_count=pm_count+1", "members", "member_id = '{$pm_to_id}'");

    $DB->insert("topic = '{$topic_id}', pm_member = '{$pm_to_id}', send_by = '{$member_id}', text = '{$text}', send_date = '{$time}', pm_key = '{$key_post}'", "members_pm");
    $pm_id = $DB->insert_id();

    if ($pm_topic AND $topic['id'])
        $DB->update("last_messid = '{$pm_id}', count = count+1, last_date = '{$time}', isread = '0'", "members_pm_topic", "id = '{$pm_topic}'");
    else
        $DB->update("last_messid = '{$pm_id}', first_messid = '{$pm_id}'", "members_pm_topic", "last_messid = '0' AND first_messid = '0' AND member_start = '{$member_id}' AND topic_member = '{$pm_to_id}'");
    $DB->free();

    $member_options_send = unserialize($mf_options);
    $member_options_send = member_options_default($member_options_send);
    if ($member_options_send['pmtoemail'])
    {
        $email_message = $cache_email[7];
        $message = str_replace("{name}", $member_name, $lang_g_function['send_new_pm_by']).$text;
        $email_message = str_replace( "{froum_link}", $cache_config['general_site']['conf_value'], $email_message );
        $email_message = str_replace( "{forum_name}", $cache_config['general_name']['conf_value'], $email_message );
        $email_message = str_replace( "{user_name}", $mname, $email_message );
        $email_message = str_replace( "{user_id}", $pm_to_id, $email_message );
        $email_message = str_replace( "{user_ip}", "", $email_message );
        $email_message = str_replace( "{active_link}", pm_member($name, $pm_to_id), $email_message );
        $email_message = str_replace( "{user_password}", "", $email_message );
        $email_message = str_replace( "{message}", $message, $email_message );

        mail_sender ($email, $mname, $email_message, $lang_g_function['send_new_pm_title']);
    }

    if (!$system)
    {
        // Создание поста и темы у отправителя
        $DB->insert("title = '{$title}', topic_member = '{$member_id}', member_start = '{$member_id}', member_to = '{$pm_to_id}', start_date = '{$time}', last_date = '{$time}', last_member = '{$member_name}', last_member_id = '{$member_id}', isread = '1', folder = 'outbox', pmt_key = '{$key_post}'", "members_pm_topic");
        $topic_id = $DB->insert_id();

        $DB->insert("topic = '{$topic_id}', pm_member = '{$member_id}', send_by = '{$member_id}', text = '{$text}', send_date = '{$time}', pm_key = '{$key_post}'", "members_pm");
        $pm_id = $DB->insert_id();
        $DB->update("last_messid = '{$pm_id}', first_messid = '{$pm_id}'", "members_pm_topic", "last_messid = '0' AND first_messid = '0' AND member_start = '{$member_id}' AND topic_member = '{$member_id}'");
        $DB->free();
        $DB->update("pm_count=pm_count+1", "members", "member_id = '{$member_id}'");
    }
}

function send_reply_pm($key = "", $text = "") // функция ответа на ЛС
{
    global $DB, $time, $cache_config, $member, $cache_email, $lang_g_function;

    $find_tpm = $DB->join_select( "pt.*, m.email, m.name, m.mf_options", "LEFT", "members_pm_topic pt||members m", "pt.topic_member=m.member_id", "pt.pmt_key = '{$key}'" );
    while ( $row = $DB->get_row($find_tpm) )
    {
        $where = "";
        $DB->insert("topic = '{$row['id']}', pm_member = '{$row['topic_member']}', send_by = '{$member['member_id']}', text = '{$text}', send_date = '{$time}', pm_key = '{$key}'", "members_pm");
        $pm_id = $DB->insert_id();

        if ($row['topic_member'] != $member['member_id'])
        {
            if ($row['isread'])
                $pm_new = "pm_new=pm_new+1, ";
            else
                $pm_new = "";

            $DB->update($pm_new."pm_count=pm_count+1", "members", "member_id = '{$row['topic_member']}'");
            $where = ", isread = '0'";

            $member_options_send = unserialize($row['mf_options']);
            $member_options_send = member_options_default($member_options_send);
            if ($member_options_send['pmtoemail'])
            {
                $email_message = $cache_email[7];
                $message = str_replace("{name}", $member['name'], $lang_g_function['send_reply_pm_by']).$text;
                $email_message = str_replace( "{froum_link}", $cache_config['general_site']['conf_value'], $email_message );
                $email_message = str_replace( "{forum_name}", $cache_config['general_name']['conf_value'], $email_message );
                $email_message = str_replace( "{user_name}", $row['name'], $email_message );
                $email_message = str_replace( "{user_id}", $row['topic_member'], $email_message );
                $email_message = str_replace( "{user_ip}", "", $email_message );
                $email_message = str_replace( "{active_link}", pm_topics_link($row['id']), $email_message );
                $email_message = str_replace( "{user_password}", "", $email_message );
                $email_message = str_replace( "{message}", $message, $email_message );

                mail_sender ($row['email'], $row['name'], $email_message, $lang_g_function['send_reply_pm_title']);
            }
        }

        $DB->update("last_messid = '{$pm_id}', last_date = '{$time}', count=count+1, last_member = '{$member['name']}', last_member_id = '{$member['member_id']}' {$where}", "members_pm_topic", "id = '{$row['id']}'");
    }
    $DB->free($find_tpm);
    $DB->update("pm_count=pm_count+1", "members", "member_id = '{$member['member_id']}'");
}

function send_forward_pm($topics = "", $pm_to_id = 0, $email = "", $mname = "", $mf_options = "") // функция пересылки ЛС
{
    global $DB, $time, $cache_config, $member, $cache_email, $lang_g_function;

    $topic_db = $DB->select( "*", "members_pm_topic", "topic_member='{$member['member_id']}' AND id regexp '[[:<:]](".$topics.")[[:>:]]'" );
    while ( $row = $DB->get_row($topic_db) )
    {
        $key_post = $row['pmt_key'];
        $row['title'] = "Fwd: ".$DB->addslashes($row['title']);
        $DB->insert("topic_member = '{$pm_to_id}', first_messid = '{$row['first_messid']}', title = '{$row['title']}', member_start = '{$row['member_start']}', member_to = '{$row['member_to']}', start_date = '{$row['start_date']}', last_date = '{$row['last_date']}', count = '{$row['count']}', last_member = '{$row['last_member']}', last_messid = '{$row['last_messid']}', last_member_id = '{$row['last_member_id']}', isread = '0', isdel = '1', folder = 'inbox', pmt_key = '{$key_post}'", "members_pm_topic");
        $topic_id = $DB->insert_id();

        $post_db = $DB->select( "*", "members_pm", "topic='{$row['id']}'" );
        $pm_count = 0;
        while ( $row2 = $DB->get_row($post_db) )
        {
            $pm_count ++;
            $DB->insert("topic = '{$topic_id}', pm_member = '{$pm_to_id}', send_by = '{$row2['send_by']}', text = '{$row2['text']}', send_date = '{$row2['send_date']}', pm_key = '{$key_post}'", "members_pm");
        }
        $DB->free($post_db);

        $DB->update("pm_new=pm_new+1, pm_count=pm_count+{$pm_count}", "members", "member_id = '{$pm_to_id}'");
    }

    $member_options_send = unserialize($mf_options);
    $member_options_send = member_options_default($member_options_send);
    if ($member_options_send['pmtoemail'])
    {
        $email_message = $cache_email[7];
        $message = str_replace("{name}", $member['name'], $lang_g_function['send_forward_pm_by']);

        $email_message = str_replace( "{froum_link}", $cache_config['general_site']['conf_value'], $email_message );
        $email_message = str_replace( "{forum_name}", $cache_config['general_name']['conf_value'], $email_message );
        $email_message = str_replace( "{user_name}", $mname, $email_message );
        $email_message = str_replace( "{user_id}", $pm_to_id, $email_message );
        $email_message = str_replace( "{user_ip}", "", $email_message );
        $email_message = str_replace( "{active_link}", pm_member($name, $pm_to_id), $email_message );
        $email_message = str_replace( "{user_password}", "", $email_message );
        $email_message = str_replace( "{message}", $message, $email_message );

        mail_sender ($email, $mname, $email_message, $lang_g_function['send_forward_pm_title']);
    }

    $DB->free($topic_db);
}

function forums_notice ($id = 0)
{
    global $cache_forums_notice, $redirect_url, $tpl, $member;

    if (!$id)
        return false;

    if (!count($cache_forums_notice))
        return false;

    $id_mass = main_forum($id);
    $id_mass = explode ("|", $id_mass);
    $category = array_pop($id_mass); // вырезаем категорию из массива

    foreach ($cache_forums_notice as $cache_notice)
    {
        if (!$cache_notice['active_status'])
            continue;

        $notice_group = explode (",", $cache_notice['group_access']);
        if (!in_array($member['member_group'], $notice_group ) AND !in_array("0", $notice_group ))
            continue;

        $notice_mass = explode (",", $cache_notice['forum_id']);

        if (in_array($category, $notice_mass))
        {
            $tpl->load_template ( 'board/forum_notice.tpl' );
            $tpl->tags('{title}', $cache_notice['title']);
            $tpl->tags('{notice_link}', notice_link($cache_notice['id']));
            $tpl->tags('{author}', $cache_notice['author']);
            $tpl->tags('{author_link}', profile_link($cache_notice['author'], $cache_notice['author_id']));
            $tpl->tags('{start_date}', date("d.m.Y", $cache_notice['start_date']));
            $tpl->tags('{end_date}', date("d.m.Y", $cache_notice['end_date']));
            $tpl->compile('notice');
            $tpl->clear();
        }

        if( count( $id_mass ) )
        {
            foreach ($id_mass as $idd)
            {
                if (in_array($idd, $notice_mass))
                {
                    $tpl->load_template ( 'board/forum_notice.tpl' );
                    $tpl->tags('{title}', $cache_notice['title']);
                    $tpl->tags('{notice_link}', notice_link($cache_notice['id']));
                    $tpl->tags('{author}', $cache_notice['author']);
                    $tpl->tags('{author_link}', profile_link($cache_notice['author'], $cache_notice['author_id']));
                    $tpl->tags('{start_date}', date("d.m.Y", $cache_notice['start_date']));
                    $tpl->tags('{end_date}', date("d.m.Y", $cache_notice['end_date']));
                    $tpl->compile('notice');
                    $tpl->clear();
                }
            }
        }
    }

    if (!isset($tpl->result['notice']))
        return false;

    $tpl->load_template ( 'board/forum_notice_global.tpl' );
    $tpl->tags('{notice}', $tpl->result['notice']);
    $tpl->compile('content');
    $tpl->clear();
}

function pm_limit ()
{
    global $member, $cache_group, $lang_g_function;

    $level_limit = $cache_group[$member['member_group']]['g_maxpm']/4;
    $limit_pm_status = $lang_g_function['pm_limit_1'];
    if (($level_limit*2) >= $member['pm_count'] AND $level_limit <= $member['pm_count'])
        $limit_pm_status = $lang_g_function['pm_limit_2'];
    elseif (($level_limit*3) >= $member['pm_count'] AND ($level_limit*2) <= $member['pm_count'])
        $limit_pm_status = $lang_g_function['pm_limit_3'];
    elseif (($level_limit*4) > $member['pm_count'] AND ($level_limit*3) <= $member['pm_count'])
        $limit_pm_status = $lang_g_function['pm_limit_4'];
    elseif (($level_limit*4) <= $member['pm_count'])
        $limit_pm_status = $lang_g_function['pm_limit_5'];

    $limit_pm = $lang_g_function['pm_limit_info'];
    $limit_pm = str_replace ("{status}", $limit_pm_status, $limit_pm);
    $limit_pm = str_replace ("{count}", $member['pm_count'], $limit_pm);
    $limit_pm = str_replace ("{max}", $cache_group[$member['member_group']]['g_maxpm'], $limit_pm);

    return $limit_pm;
}

function topic_poll_variants ($variants = "", $multiple = false)
{
    $variants = explode ("\r\n", $variants);
    $echo_v = "";
    foreach($variants as $key => $spisok)
    {
        if ($multiple)
            $echo_v .= "<li><input type=\"checkbox\" id=\"tp_".$key."\" name=\"tp[]\" value=\"".$key."\" /> <label for=\"tp_".$key."\">".$spisok."</label></li>";
        else
            $echo_v .= "<li><input type=\"radio\" id=\"tp_".$key."\" name=\"tp_1\" value=\"".$key."\" /> <label for=\"tp_".$key."\">".$spisok."</label></li>";
    }

    return $echo_v;
}

function topic_poll_logs ($variants = "", $answer = "", $all = 0)
{
    global $lang_g_function;

    $variants = explode ("\r\n", $variants);
    $echo_v = "";
    $result = array ();
    if ($answer)
    {
        $answer = explode ("|", $answer);
        foreach ($answer as $vote)
        {
            $vote = explode (":", $vote);
            list($sp, $num) = $vote;
            $result[$sp] = $num;
        }
    }

    foreach($variants as $key => $spisok)
    {
        if (count($result))
        {
            if (!isset($result[$key]))
                $result[$key] = 0;

            if ($result[$key] > 0)
                $num = round( (100 * $result[$key])/$all, 2 );
            else
                $num = 0;

            $num = str_replace (",", ".", $num);

            $vote_line = $lang_g_function['topic_poll_logs'];
            $vote_line = str_replace ("{spisok}", $spisok, $vote_line);
            $vote_line = str_replace ("{vote_num}", $result[$key], $vote_line);
            $vote_line = str_replace ("{num}", $num, $vote_line);

            $echo_v .= $vote_line;
        }
        else
        {
            $vote_line = $lang_g_function['topic_poll_logs'];
            $vote_line = str_replace ("{spisok}", $spisok, $vote_line);
            $vote_line = str_replace ("{vote_num}", "0", $vote_line);
            $vote_line = str_replace ("{num}", "0.00", $vote_line);

            $echo_v .= $vote_line;
        }
    }

    return $echo_v;
}

function member_rank ($post_num = 0)
{
    global $cache_ranks, $redirect_url, $cache_config;

    if (!count($cache_ranks))
        return "";

    $mrank = array();
    foreach ($cache_ranks as $rank)
    {
        if ($rank['post_num'] <= $post_num)
        {
            if (is_numeric($rank['stars']))
            {
                $mrank[0] = "";
                for ($i=1; $i <= $rank['stars']; $i++)
                {
                    $mrank[0] .= "<img src=\"".$redirect_url."templates/".$cache_config['template_name']['conf_value']."/ranks/default.png\" />";
                }
            }
            else
            {
                $mrank[0] = "<img src=\"".$redirect_url."templates/".$cache_config['template_name']['conf_value']."/ranks/".$rank['stars']."\" />";
            }

            $mrank[1] = $rank['title'];
        }
    }

    if (!count($mrank))
        return "";

    return $mrank;
}

function member_options_default ($options)
{
    global $cache_config;

    if (!count($options))
        return "";

    if (!isset($options['pmtoemail'])) $options['pmtoemail'] = 1;
    if (!isset($options['subscribe'])) $options['subscribe'] = 1;
    if (!isset($options['online'])) $options['online'] = 0;
    if (!isset($options['block_ip'])) $options['block_ip'] = "";
    if (!isset($options['email_ip'])) $options['email_ip'] = "";
    if (!isset($options['comm_profile'])) $options['comm_profile'] = 1;

    return $options;
}

function topics_adtblock($i = 0, $num = 0)
{
    global $cache_adtblock, $member;

    $block = "";

    if (count($cache_adtblock))
    {
        foreach ( $cache_adtblock as $value )
        {
            $show_adt = false;

            if ($value['in_posts'])
            {
                $check_group = explode (",", $value['group_access']);

                if (in_array(0, $check_group) OR in_array($member['member_group'], $check_group))
                {
                    $middle = floor( $num / 2 );
                    $top = floor( $middle / 2 );
                    $bot = $middle + ceil( $middle / 2 );

                    if ($value['in_posts'] == 1 AND $top == $i)
                        $show_adt = true;
                    elseif ($value['in_posts'] == 2 AND $middle == $i)
                        $show_adt = true;
                    elseif ($value['in_posts'] == 3 AND $bot == $i)
                        $show_adt = true;
                    elseif ($value['in_posts'] == 4 AND ($top == $i OR $bot == $i OR $middle == $i))
                        $show_adt = true;
                }
            }

            if ($value['active_status'] AND $show_adt)
                $block .= $value['text'];
        }
    }

    return $block;
}

function show_jq_message ($type_mess = 2, $title = "", $text = "", $tout = 2000)
{

    $message = "
    <script type=\"text/javascript\">
    show_message('".$type_mess."', '".$title."', '".$text."', '".$tout."');
    </script>";

    return $message;
}

function share_links($tid = 0, $title = "")
{
    global $cache_sharelink, $cache_config;

    $block = "";

    if (count($cache_sharelink))
    {
        foreach ( $cache_sharelink as $value )
        {
            if(!$value['active_status'])
                continue;

            $link = $value['link']."?";
            $link_dop = array();

            if ($value['link_topic'] AND $value['title_topic'])
            {
                $link_dop[] = $value['link_topic']."=".urlencode(topic_link($tid));
                $link_dop[] = $value['title_topic']."=".rawurlencode($title);
            }
            else
            {
                if ($value['link_topic'])
                    $link_dop[] = $value['link_topic']."=".urlencode(topic_link($tid))." - ".rawurlencode($title);
                elseif ($value['title_topic'])
                    $link_dop[] = $value['title_topic']."=".urlencode(topic_link($tid))." - ".rawurlencode($title);
            }

            if ($value['dop_parametr'])
                $link_dop[] = $value['dop_parametr'];

            $link_dop = implode("&", $link_dop);

            $link = $link.$link_dop;
            $block .= " <a href=\"".$link."\" rel=\"nofollow\" target=\"blank\" title=\"Поделиться ссылкой через ".$value['title']."\"><img src=\"".$cache_config['general_site']['conf_value']."templates/".$cache_config['template_name']['conf_value']."/images/sharelink/".$value['icon'].".png\" /></a>";

            unset($link_dop);
        }
    }

    if ($block)
        $block = "<noindex>".$block."</noindex>";

    return $block;
}

function show_attach($template = "", $files = 0)
{
    global $cache_config, $DB, $member, $lang_g_function;

    if (is_array($files) AND count($files))
    {
        $files_f = array();
        foreach($files as $value)
        {
            $files_f[] = intval($value);
        }
        $where = "file_pid IN (".implode(",", $files_f).")";
    }
    else
    {
        if (!$files)
            $where = "file_pid = '0' AND file_mid = '{$member['member_id']}'";
        else
            $where = "file_pid = '".intval($files)."'";
    }

    $find1 = array();
    $find2 = array();

    $replace1 = array();
    $replace2 = array();

    // 1 - DLE Forum
    // 2 - TWSF

    $tfiles = $DB->select( "*", "topics_files", $where );
    while ( $row = $DB->get_row($tfiles) )
    {
        $find1[] = "[attachment={$row['file_id']}]";
        $find2[] = "#\[attachment={$row['file_id']}\|(.+?)\]#iu";

        if (!$cache_config['upload_download']['conf_value'])
        {
            $replace1[] = $lang_g_function['show_attach_off'];
			$replace2[] = $lang_g_function['show_attach_off'];
        }
        elseif(!forum_permission($row['file_fid'], "download_files") AND $row['file_type'] != "picture")
        {
            $replace1[] = $lang_g_function['show_attach_permission'];
			$replace2[] = $lang_g_function['show_attach_permission'];
        }
        else
        {
            if ($cache_config['upload_count']['conf_value'])
                $counter = str_replace ("{num}", $row['file_count'], $lang_g_function['show_attach_count']);
            else
                $counter = "";

            $dir_name = date( "Y-m", $row['file_date'] );

            if ($row['file_type'] != "picture")
            {
                if ($row['file_convert'] == "1" AND $cache_config['upload_convert']['conf_value'])
                {
                    $replace1[] = "<a href=\"".$cache_config['upload_convert']['conf_value'].$row['file_name']."\">".$row['file_title']."</a> <span class=\"attachment\">[".formatsize($row['file_size'])."]".$counter."</span>";
                    $replace2[] = "<a href=\"".$cache_config['upload_convert']['conf_value'].$row['file_name']."\">\\1</a> <span class=\"attachment\">[".formatsize($row['file_size'])."]".$counter."</span>";
                }
                else
                {
                    $replace1[] = "<a href=\"".$cache_config['general_site']['conf_value']."components/modules/download.php?id=".$row['file_id']."\">".$row['file_title']."</a> <span class=\"attachment\">[".formatsize($row['file_size'])."]".$counter."</span>";
                    $replace2[] = "<a href=\"".$cache_config['general_site']['conf_value']."components/modules/download.php?id=".$row['file_id']."\">\\1</a> <span class=\"attachment\">[".formatsize($row['file_size'])."]".$counter."</span>";
                }
            }
            else
            {
                if ($row['file_convert'] == "1" AND $cache_config['upload_convert_img']['conf_value'])
                    $img = bb_create_img($cache_config['upload_convert_img']['conf_value'].$row['file_name'], "center");
                elseif ($row['file_convert'] == "2" AND $cache_config['upload_convert_img']['conf_value'])
                    $img = bb_create_img($cache_config['upload_convert_img']['conf_value'].$dir_name."/".$row['file_name'], "center");
                else
                    $img = bb_create_img($cache_config['general_site']['conf_value']."uploads/attachment/".$dir_name."/".$row['file_name'], "center");

                $replace1[] = $img;
                $replace2[] = preg_quote($img);
            }
        }
    }
    $DB->free($tfiles);

    if (is_array($template))
    {
        $new_templ = array();
        foreach($template as $templ)
        {
            if (strpos($templ, "[attachment=") !== false)
            {
                $templ = str_replace ( $find1, $replace1, $templ );
                $templ = preg_replace( $find2, $replace2, $templ );
            }
            $new_templ[] = $templ;
        }
    }
    else
    {
        $new_templ = str_replace ( $find1, $replace1, $template );
        $new_templ = preg_replace( $find2, $replace2, $new_templ );
    }
    return $new_templ;
}

function hide_in_post($text = "", $post_mid = 0)
{
    global $cache_config, $cache_group, $member, $lang_g_function;

    if ($cache_group[$member['member_group']]['g_supermoders'] OR $member['member_group'] == 1 OR $member['member_id'] == $post_mid)
    {
        $text = preg_replace( "'\[hide\](.*?)\[/hide\]'si", $lang_g_function['hide_in_post_show_1']."\\1".$lang_g_function['hide_in_post_show_2'], $text);
        return $text;
    }

    if (!$cache_group[$member['member_group']]['g_hide_text'])
        $text = preg_replace ( "'\[hide\](.*?)\[/hide\]'si", str_replace("{group}", $cache_group[$member['member_group']]['g_title'], $lang_g_function['hide_in_post_access_denied_group']), $text );
    elseif (intval($cache_config['posts_bbhide']['conf_value']) > $member['posts_num'])
        $text = preg_replace ( "'\[hide\](.*?)\[/hide\]'si", str_replace("{group}", intval($cache_config['posts_bbhide']['conf_value']), $lang_g_function['hide_in_post_limit']), $text );
    else
        $text = preg_replace( "'\[hide\](.*?)\[/hide\]'si", $lang_g_function['hide_in_post_show_1']."\\1".$lang_g_function['hide_in_post_show_2'], $text);

    return $text;
}

function clear_cookie()
{
    update_cookie( "LB_member", "", 0 );
	update_cookie( "LB_password", "", 0 );
	update_cookie( "LB_secret_key", "", 0 );
    update_cookie( "LB_member_sc", "", 0 );
    update_cookie( "LB_last_news", "", 0 );
    update_cookie( "LB_forums_read_all", "", 0 );
    update_cookie( "LB_forums_read", "", 0 );
    update_cookie( "cook_side", "", 0 );
    update_cookie( "c_ids", "", 0 );
	update_cookie( session_name(), "", 0 );
    unset($_SESSION['LB_member']);
    unset($_SESSION['LB_password']);
    unset($_SESSION['LB_member_sc']);
    unset($_SESSION['LB_secret_key']);
}

function generate_password()
{
    $generate_pass = "q|w|e|r|t|y|u|i|o|p|a|s|d|f|g|h|j|k|l|z|x|c|v|b|n|m|1|2|3|4|5|6|7|8|9|0";
    $generate_pass = explode ("|", $generate_pass);
    $new_pass = "";
    for($i = 0; $i < 9; $i ++)
    {
        $new_pass .= $generate_pass[rand( 0, count($generate_pass))];
    }

    return $new_pass;
}

function strip_data($text)
{
    $quotes = array ("\x27", "\x22", "\x60", "\t", "\n", "\r", "'", ",", "/", "¬", ";", "@", "~", "[", "]", "{", "}", "=", ")", "(", "*", "&", "^", "%", "$", "<", ">", "?", "!", '"' );
    $goodquotes = array ("-", "+", "#" );
    $repquotes = array ("\-", "\+", "\#" );
    $text = trim( strip_tags( $text ) );
    $text = str_replace( $quotes, '', $text );
    $text = str_replace( $goodquotes, $repquotes, $text );
    $text = ereg_replace(" +", " ", $text);

    return $text;
}

function topic_do_subscribe($tid = 0, $t_title = "")
{
    global $DB, $member, $cache_config, $cache_email, $_IP, $time, $lang_g_function;

    $topic_subs_db = $DB->join_select( "ts.*, m.member_id, m.name, m.email, mf_options", "LEFT", "topics_subscribe ts||members m", "ts.subs_member=m.member_id", "ts.topic = '{$tid}' AND ts.send_status = '0'" );

    while ( $row = $DB->get_row($topic_subs_db) )
    {
        if ($row['member_id'] == $member['member_id'])
            continue;

        $member_options_send = unserialize($row['mf_options']);
        $member_options_send = member_options_default($member_options_send);

        if ($member_options_send['subscribe'] == 0) // по ЛС
        {
            $text = $cache_config['pm_subscribe']['conf_value'];
            $text = add_br($text);
            $text = str_replace( "{link}", topic_link($tid, true), $text );
            $text = str_replace( "{author}", $member['name'], $text );
            $text = str_replace( "{title}", $t_title, $text );
            $text = $DB->addslashes($text);
            $title = $DB->addslashes($lang_g_function['topic_do_subscribe_answers']).$t_title;

            send_new_pm($title, $row['member_id'], $text, $row['email'], $row['name'], $row['mf_options'], 1, $row['pm_topic']);
            if (!$row['pm_topic'])
                $DB->update("pm_topic = '{$topic_id}'", "topics_subscribe", "id = '{$row['id']}'");
        }
        else // по E-mail
        {
            $email_message = $cache_email[5];

            $message = str_replace( "{link}", "<a href=\"".topic_link($tid, true)."\">".$t_title."</a>", $lang_g_function['topic_do_subscribe_topic'] );
            $message = str_replace( "{name}", $member['name'], $message );
            $message = str_replace( "{date}", formatdate($time), $message );

            $email_message = str_replace( "{froum_link}", $cache_config['general_site']['conf_value'], $email_message );
            $email_message = str_replace( "{forum_name}", $cache_config['general_name']['conf_value'], $email_message );
            $email_message = str_replace( "{user_name}", $row['name'], $email_message );
            $email_message = str_replace( "{user_id}", $row['member_id'], $email_message );
            $email_message = str_replace( "{user_ip}", $_IP, $email_message );
            $email_message = str_replace( "{active_link}", "", $email_message );
            $email_message = str_replace( "{user_password}", "", $email_message );
            $email_message = str_replace( "{message}", $message, $email_message );

            mail_sender ($row['email'], $row['name'], $email_message, $lang_g_function['topic_do_subscribe_answers2']);
        }

        $DB->update("send_status = '1'", "topics_subscribe", "subs_member = '{$row['subs_member']}' AND topic = '{$tid}'");
    }
    $DB->free($topic_subs_db);
}

function away_from_here ($text = "", $hide = 1, $encode = 1)
{
    global $redirect_url;

    if ($hide AND $encode)
        $text = $redirect_url."away.php?s=".urlencode($text);
    elseif (!$encode)
        $text = str_replace ("&amp;", "&", urldecode($text));

    return $text;
}

function stop_script($text = "")
{
    global $DB;

    $DB->close();
    exit ($text);
}

function language_forum ($file_name = "", $dir = "")
{

}

function logs_record($data_mas = "")
{
    global $DB, $member, $cache_config, $_IP, $time, $logged;

    if (!is_array($data_mas) OR !$logged)
        return;

    $where = array();

    if (!isset($data_mas['info']))
        $data_mas['info'] = "";

    if (!intval($cache_config['log_pt_info']['conf_value']))
    {
        $data_mas['info'] = "";
    }

    if ($data_mas['table'] == "logs_topics" AND count($data_mas) == 5 AND intval($cache_config['log_topics']['conf_value']))
    {
        $where[] = "fid = '".intval($data_mas['fid'])."'";
        $where[] = "tid = '".intval($data_mas['tid'])."'";
        $where[] = "mid = '".$member['member_id']."'";
        $where[] = "date = '".$time."'";
        $where[] = "ip = '".$_IP."'";
        $where[] = "act_st = '".intval($data_mas['act_st'])."'";
        $where[] = "info = '".$DB->addslashes($data_mas['info'])."'";
    }
    elseif ($data_mas['table'] == "logs_posts" AND count($data_mas) == 6 AND intval($cache_config['log_posts']['conf_value']))
    {
        $where[] = "fid = '".intval($data_mas['fid'])."'";
        $where[] = "tid = '".intval($data_mas['tid'])."'";
        $where[] = "pid = '".intval($data_mas['pid'])."'";
        $where[] = "mid = '".$member['member_id']."'";
        $where[] = "date = '".$time."'";
        $where[] = "ip = '".$_IP."'";
        $where[] = "act_st = '".intval($data_mas['act_st'])."'";
        $where[] = "info = '".$DB->addslashes($data_mas['info'])."'";
    }
    else
        return;

    $where = implode(", ", $where);

    $DB->insert($where, $data_mas['table']);
}

function sub_title ($text, $max = 0, $end_str = "...")
{
    if (!$max)
        return $text;

    if(utf8_strlen($text) > $max )
        $text = utf8_substr($text, 0, $max).$end_str;

    return $text;
}

function minify_compression ($files)
{
    global $cache_config;

    $files_comp = array();

    $components_js = array (
   	    'jquery.js',
       	'global.js',
        'ajax.js',
        'bbcode/script.js',
        'jquery.cookie.js',
        'highslide/highslide.min.js'
    );

	if ($cache_config['general_minify']['conf_value'])
    {
        $files_comp[] = "<script type=\"text/javascript\" src=\"{$cache_config['general_site']['conf_value']}components/scripts/min/index.php?charset=UTF-8&amp;b=components/scripts&amp;f=".implode(",", $components_js)."\"></script>";

		if (is_array($files) AND count($files))
        {
            $files_css = array();
            foreach ($files as $key => $value)
            {
                $value_check = explode (".", $value);
                if (end($value_check) == "css")
                {
                    $files_css[] = $value;
                    unset($files[$key]);
                }
            }

            if ($files[0] != "")
                $files_comp[] = "<script type=\"text/javascript\" src=\"{$cache_config['general_site']['conf_value']}components/scripts/min/index.php?charset=UTF-8&amp;f=".implode(",", $files)."\"></script>";
            if ($files_css[0] != "")
                $files_comp[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"{$cache_config['general_site']['conf_value']}components/scripts/min/index.php?charset=UTF-8&amp;f=".implode(",", $files_css)."\" />";
        }
	}
    else
    {
        foreach ($components_js as $value)
        {
            $files_comp[] = "<script type=\"text/javascript\" src=\"{$cache_config['general_site']['conf_value']}components/scripts/{$value}\" /></script>";
        }

		foreach ($files as $value)
        {
            $value_check = explode (".", $value);
            if (end($value_check) == "css")
                $files_comp[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"{$cache_config['general_site']['conf_value']}{$value}\" />";
            else
                $files_comp[] = "<script type=\"text/javascript\" src=\"{$cache_config['general_site']['conf_value']}{$value}\"></script>";
		}
	}

$script_file = <<<HTML

<!--[if lt IE 7]><link rel="stylesheet" type="text/css" href="{$cache_config['general_site']['conf_value']}components/scripts/min/index.php?charset=UTF-8&amp;f=components/scripts/highslide/highslide-ie6.css" /><![endif]-->
<script type="text/javascript">
 //<![CDATA[

hs.graphicsDir = '{$LB_root}components/scripts/highslide/graphics/';
hs.showCredits = false;
hs.registerOverlay({
	html: '<div class="closebutton" onclick="return hs.close(this)" title="' + LB_lang['hs_title'] + '"></div>',
	position: 'top right',
	useOnHtml: true,
	fade: 2
});

hs.lang = {
	cssDirection:      'ltr',
	loadingText:       LB_lang['hs_loadingText'],
	loadingTitle:      LB_lang['hs_loadingTitle'],
	focusTitle:        LB_lang['hs_focusTitle'],
	fullExpandTitle:   LB_lang['hs_fullExpandTitle'],
	moveText:          LB_lang['hs_moveText'],
	closeText:         LB_lang['hs_closeText'],
	closeTitle:        LB_lang['hs_closeTitle'],
	resizeTitle:       LB_lang['hs_resizeTitle'],
	moveTitle:         LB_lang['hs_moveTitle'],
	fullExpandText:    LB_lang['hs_fullExpandText'],
	restoreTitle:      LB_lang['hs_restoreTitle']
};

//]]>
</script>
HTML;

    return implode("\n", $files_comp).$script_file;
}

function sub_forums($id, $sub_forums = '')
{
	global $cache_forums;

	$subsearch = array ();
	if ($sub_forums == "")
		$sub_forums = $id;

	foreach ( $cache_forums as $forum )
	{
		if( $forum['parent_id'] == $id )
			$subsearch[] = $forum['id'];
	}

	foreach ( $subsearch as $parent_id )
	{
		$sub_forums .= "|" . $parent_id;
		$sub_forums = sub_forums( $parent_id, $sub_forums );
	}
	return $sub_forums;
}

function cookie_forums_read($id = 0)
{
    global $cache_forums;

    $data_2 = array();
    $unread = false;

    if ($_COOKIE['LB_forums_read'])
    {
        $data = explode("||", $_COOKIE['LB_forums_read']);
        foreach ($data as $value)
        {
            list($key, $ftime) = explode (":", $value);
            $data_2[$key] = $ftime;
        }
        unset($data);
    }

    $sub_forums = sub_forums($id);
    $sub_forums = explode ("|", $sub_forums);

    foreach ($sub_forums as $sub)
    {
        if ($cache_forums[$sub]['parent_id'] == 0)
            continue;

        if (isset($_COOKIE['LB_forums_read_all']) AND forum_permission($sub, "read_forum") AND $cache_forums[$sub]['last_post_date'] AND (!array_key_exists($sub, $data_2) OR (array_key_exists($sub, $data_2) AND intval($data_2[$sub]) < $cache_forums[$sub]['last_post_date'])))
        {
            if (intval($_COOKIE['LB_forums_read_all']) >= $cache_forums[$sub]['last_post_date'])
                $unread = false;
            else
            {
                $unread = true;
                break;
            }
        }
        elseif (forum_permission($sub, "read_forum") AND array_key_exists($sub, $data_2))
        {
            if (intval($data_2[$sub]) < $cache_forums[$sub]['last_post_date'])
            {
                $unread = true;
                break;
            }
            else
                $unread = false;
        }
        elseif(forum_permission($sub, "read_forum") AND !array_key_exists($sub, $data_2) AND $cache_forums[$sub]['last_post_date'])
        {
            $unread = true;
            break;
        }
        elseif(!forum_permission($sub, "read_forum"))
            continue;
	}

    unset($data_2);

    if ($unread) return false; // форум НЕ прочтён
    else return true; // форум прочтён
}

function cookie_forums_read_update($id, $check_time)
{
    global $time;

    if (!$_COOKIE['LB_forums_read'] AND !isset($_COOKIE['LB_forums_read_all']))
    {
        update_cookie( "LB_forums_read", $id.":".$time, 365 );
        return;
    }

    $old_time = false;
    $find = false;

    $data_2 = array();
    if ($_COOKIE['LB_forums_read'])
    {
        $data = explode("||", $_COOKIE['LB_forums_read']);
        foreach ($data as $value)
        {
            list($key, $ftime) = explode (":", $value);
            $data_2[$key] = $ftime;
        }
        unset($data);
    }

    if (isset($_COOKIE['LB_forums_read_all']) AND isset($data_2[$id]) AND intval($data_2[$id]) < $check_time)
    {
        if (intval($_COOKIE['LB_forums_read_all']) >= $check_time)
        {
            $old_time = false;
            $find = true;
        }
        else
            $data_2[$id] = $time;
    }
    elseif (isset($data_2[$id]))
    {
        if (intval($data_2[$id]) < $check_time)
        {
            $data_2[$id] = $time;
            $old_time = true;
        }
        $find = true;
    }
    elseif (!isset($data_2[$id]))
    {
        if ((isset($_COOKIE['LB_forums_read_all']) AND intval($_COOKIE['LB_forums_read_all']) < $check_time) OR !isset($_COOKIE['LB_forums_read_all']))
            $data_2[$id] = $time;
    }

    if (!$find OR $old_time)
    {
        $data = array();
        foreach ($data_2 as $key => $value)
        {
            $data[] = $key.":".$value;
        }
        update_cookie( "LB_forums_read", implode("||", $data), 365 );
        unset($data);
        unset($data_2);
    }

    return;
}

function member_topic_read_update ($tid, $check_time)
{
    global $time, $member, $logged, $DB;

    if (!$logged) return;

    $update = false;

    if ($member['view_topic'])
    {
        $views = unserialize($member['view_topic']);
        if (!is_array($views))
        {
            unset ($views);
            $views = array();
        }

        if (array_key_exists($tid, $views))
        {
            if ($views[$tid] < $check_time)
            {
                $update = true;
                $views[$tid] = $time;
            }
            elseif (($views[$tid] + 300) < $time)
            {
                $update = true;
                $views[$tid] = $time;
            }
        }
        else
        {
            $update = true;
            $views[$tid] = $time;
        }

        if (array_key_exists("all", $views))
        {
            if ($views["all"] >= $check_time)
                $update = false;
        }
    }
    else
    {
        $views = array();
        $update = true;
        $views[$tid] = $time;
    }

    if ($update)
    {
        $views = $DB->addslashes(serialize($views));
        $DB->not_filtred( "UPDATE LOW_PRIORITY ".LB_DB_PREFIX."_members SET view_topic='{$views}' WHERE member_id='{$member['member_id']}'" );
    }

    return;
}

function member_topic_read ($tid, $check_time)
{
    global $time, $member, $logged;

    if (!$logged) return false;

    $update = false;

    if ($member['view_topic'])
    {
        $views = unserialize($member['view_topic']);
        if (!is_array($views))
            return false;

        if (array_key_exists("all", $views))
        {
            if ($views["all"] >= $check_time)
                return true;
        }

        if (array_key_exists($tid, $views))
        {
            if ($views[$tid] >= $check_time)
                return true;
        }
    }

    return false;
}

$lang_g_function = language_forum ("board/global/function");

?>