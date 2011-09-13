<?php

include_once('geshi/geshi.php');
include_once('global_functions.php');

function bb_decode($msg)
{

    $msg = preg_replace("#\[b\](.+?)\[/b\]#is", "<strong>\\1</strong>", $msg); //Bold
    $msg = preg_replace("#\[i\](.+?)\[/i\]#is", "<i>\\1</i>", $msg); //italic
    $msg = preg_replace("#\[s\](.+?)\[/s\]#is", "<s>\\1</s>", $msg); //S


    $msg = preg_replace("#\[u\](.+?)\[/u\]#is", "<u>\\1</u>", $msg); //S


    $msg = preg_replace("#\[center\](.+?)\[/center\]#is", "<center>\\1</center>", $msg); //center


    $msg = preg_replace("#\[size=([0-9]+?)\](.+?)\[/size\]#is", "<font size='\\1'>\\2</font>", $msg); //size

    $msg = preg_replace("#\[font=([a-z ]+?)\](.+?)\[/font\]#is", "<font style='font-family:\\1'>\\2</font>", $msg); //font-family

    $colors = array(
        "aqua"=>"#00ffff",
        "gray"=>"#808080",
        "navy"=>"#000080",
        "silver"=>"#c0c0c0",
        "black"=>"#000000",
        "green"=>"#008000",
        "olive"=>"#808000",
        "teal"=>"#008080",
        "blue"=>"#0000ff",
        "lime"=>"#00ff00",
        "purple"=>"#800080",
        "white"=>"#ffffff",
        "fuchsia"=>"#ff00ff",
        "maroon"=>"#800000",
        "red"=>"#ff0000",
        "yellow"=>"#ffff00",
    );

    foreach($colors as $word=>$code)
        $msg = str_replace("[color=".$word."]", "[color=".$code."]", $msg);
    $msg = preg_replace("#\[color=[a-zA-Z]+?\](.+?)\[/color\]#si", "[color=#000000]\\1[/color]", $msg);
    $msg = preg_replace("#\[color=(\#*[0-9ACDEF]+?)\](.+?)\[/color\]#si", "<font style='color:\\1'>\\2</font>", $msg); //Color
    if (preg_match_all("#\[quote(=((.+?)(\|([0-9\., :]+?))?))?\]#si", $msg, $shadow) == preg_match_all("#\[/quote\]#si", $msg, $shadow)) {
        $msg = preg_replace("#\[quote\]#si", "<blockquote class=\"blockquote\"><p><span class=\"titlequote\">" . "Цитата:" . "</span><span class=\"textquote\">", $msg); //quote
        $msg = preg_replace("#\[quote(=((.+?)(\|([0-9\., :]+?))?))?\]#si", "<blockquote class=\"blockquote\"><p><span class=\"titlequote\">\\3 (\\5) " . "писал:" . "</span><span class=\"textquote\">", $msg); //quote
        $msg = preg_replace("#\[/quote\]#si", "</span></p></blockquote><!--quote -->", $msg); //quote
        $msg = preg_replace("#<blockquote class=\"blockquote\"><p><span class=\"titlequote\">(.+?) \(\) (.+?)</span>#si", "<blockquote class=\"blockquote\"><p><span class=\"titlequote\">\\1 \\2</span>", $msg); //quote
    }
    $msg = preg_replace("#\[youtube=http://(www\.)?youtube.com/v/(.+?)\]#si", '<object width="480" height="385"><param name="movie" value="http://youtube.com/v/\\2?fs=1"></param><param name="allowFullScreen" value="true"></param><param name="allowscriptaccess" value="always"></param><embed src="http://youtube.com/v/\\2?fs=1" type="application/x-shockwave-flash" allowscriptaccess="always" allowfullscreen="true" width="480" height="385"></embed></object>', $msg); //youtube
    $msg = preg_replace("#\[youtube=http://(www\.)?youtube.com/watch\?v=(.*?)\]#si", '<object width="480" height="385"><param name="movie" value="http://youtube.com/v/\\2?fs=1"></param><param name="allowFullScreen" value="true"></param><param name="allowscriptaccess" value="always"></param><embed src="http://youtube.com/v/\\2?fs=1" type="application/x-shockwave-flash" allowscriptaccess="always" allowfullscreen="true" width="480" height="385"></embed></object>', $msg); //youtube
    $msg = preg_replace("#\[youtube=http://(www\.)?rutube.ru/tracks/([0-9])+.html\?v=(.*?)\]#si", '<object width="470" height="353"><param name="movie" value="http://video.rutube.ru/\\3"></param><param name="wmode" value="window"></param><param name="allowFullScreen" value="true"></param><embed src="http://video.rutube.ru/\\3" type="application/x-shockwave-flash" wmode="window" width="470" height="353" allowFullScreen="true" ></embed></object>', $msg); //rutube
    $msg = preg_replace("#\[youtube=http://(www\.)?video.rutube.ru/(.*?)\]#si", '<object width="470" height="353"><param name="movie" value="http://video.rutube.ru/\\2"></param><param name="wmode" value="window"></param><param name="allowFullScreen" value="true"></param><embed src="http://video.rutube.ru/\\2" type="application/x-shockwave-flash" wmode="window" width="470" height="353" allowFullScreen="true" ></embed></object>', $msg); //rutube

    $msg = preg_replace("#(^|\s|>)((http://|https://|ftp://|www\.)\w+[^\s\[\]\<]+)#i", '\\1[url=\\2]\\2[/url]', $msg);

    $msg = preg_replace("#\[url=(\S.+?)\](.+?)\[/url\]#sei", "bb_url('\\1', '\\2')", $msg); //url

    $msg = preg_replace("#\[email=([a-z_\.\-0-9]+?@[a-z_\.\-0-9]+?\.[a-z]+?)\](.+?)\[/email\]#si", "<a href='mailto:\\1'>\\2</a>", $msg); //email
    $msg = preg_replace("#\[img=?\](\S.+?)\[/img\]#sie", "bb_create_img('\\1')", $msg); //img center
    $msg = preg_replace("#\[img=(left|right|center)+?\](\S.+?)\[/img\]#sie", "bb_create_img('\\2', '\\1')", $msg); //img left|right
    $msg = preg_replace_callback("#\[php\]([\s\S]+?)\[/php\]#si", "php_syntax", $msg); //php

    $msg = preg_replace_callback("#\[javascript\]([\s\S]+?)\[/javascript\]#si", "javascript_syntax", $msg); //javascript

    $msg = preg_replace_callback("#\[html\]([\s\S]+?)\[/html\]#si", "html_syntax", $msg); //html

    $msg = preg_replace_callback("#\[translite\]([\s\S]+?)\[/translite\]#si", "transliteit", $msg); //translite

    if (preg_match_all("#\[spoiler(=(.+?))?\]#si", $msg, $shadow) == preg_match_all("#\[/spoiler\]#si", $msg, $shadow)) {
        $msg = preg_replace_callback("#\[spoiler(=(.+?))?\]#si", "makespoiler", $msg); //spoiler
        $msg = preg_replace("#\[/spoiler\]#i", "</div></blockquote><!--spoiler -->", $msg); //spoiler
    }
    $msg = preg_replace("#::([0-9]{3,3})::#i", "<img id='smiles_img' src='{TEMPLATE}/bbcode/smiles/\\1.gif' />", $msg); //smailes
    return $msg;
}

function bb_encode($msg)
{

    global $cache_config, $lang_message;

    $msg = preg_replace("#<strong>(.+?)</strong>#si", "[b]\\1[/b]", $msg); //Bold
    $msg = preg_replace("#<b>(.+?)</b>#si", "[b]\\1[/b]", $msg); //Bold	
    $msg = preg_replace("#<i>(.+?)</i>#si", "[i]\\1[/i]", $msg); //italic
    $msg = preg_replace("#<s>(.+?)</s>#si", "[s]\\1[/s]", $msg); //S
    $msg = preg_replace("#<u>(.+?)</u>#si", "[u]\\1[/u]", $msg); //S
    $msg = preg_replace("#<font size='([0-9]+?)'>(.+?)</font>#si", "[size=\\1]\\2[/size]", $msg); //size
    $msg = preg_replace("#<font style='font-family:([a-z ]+?)'>(.+?)</font>#si", "[font=\\1]\\2[/font]", $msg); //font-family
    $msg = preg_replace("#<font style='color:(\#[0-9ACDEF]+?)'>(.+?)</font>#si", "[color=\\1]\\2[/color]", $msg); //Color

    $spoiler_title = preg_quote($lang_message['spoiler_title'], "#");

    $pattern = array(
        "#<blockquote class=\"blockspoiler\"><span class=\"titlespoiler\"><a href='\#' onclick=\"ShowAndHide\('.+?'\); return false;\">" . $spoiler_title . "</a></span><div id='.+?' style='display:none;' class=\"textspoiler\">#si",
        "#<blockquote class=\"blockspoiler\"><span class=\"titlespoiler\"><a href='\#' onclick=\"ShowAndHide\('.+?'\); return false;\">(.+?)</a></span><div id='.+?' style='display:none;' class=\"textspoiler\">#si",
        "#</div></blockquote><!--spoiler -->#i"
    );

    $replacement = array(
        '[spoiler]',
        '[spoiler=\\1]',
        '[/spoiler]');

    $msg = preg_replace($pattern, $replacement, $msg);

    $pattern = array(
        "#<blockquote class=\"blockquote\"><p><span class=\"titlequote\">" . $lang_message['quote_title'] . "</span><span class=\"textquote\">#si",
        "#<blockquote class=\"blockquote\"><p><span class=\"titlequote\">(.+?) ?(\(([0-9\., :]+?)\))? " . $lang_message['quote_title2'] . "</span><span class=\"textquote\">#si",
        "#<blockquote class=\"blockquote\"><p><span class=\"titlequote\">(.+?)</span><span class=\"textquote\">#si",
        "#</span></p><\/blockquote><!--quote -->#i"
    );

    $replacement = array(
        '[quote]',
        '[quote=$1|$3]',
        '[quote]',
        '[/quote]'
    );

    $msg = preg_replace($pattern, $replacement, $msg);

    $msg = preg_replace("#\[quote=(.+?)\|\]#si", "[quote=\\1]", $msg);

    $msg = preg_replace("#<a href='mailto:([a-z_\.\-0-9]+?@[a-z_\.\-0-9]+?\.[a-z]+?)'>(.+?)</a>#si", "[email=\\1]\\2[/email]", $msg); //email
    $msg = preg_replace("#<a href=['\"](\S.+?)['\"]\s*(target=\"_blank\")?\s*>(.+?)</a>#sie", "bb_url('\\1', '\\3', false)", $msg); //url
    $msg = preg_replace('#<object width="([0-9]){1,4}" height="([0-9]){1,4}"><param name="movie" value="http://youtube.com/v/(.*?)\?fs=1"></param><param name="allowFullScreen" value="true"></param><param name="allowscriptaccess" value="always"></param><embed src="(.*?)" type="application/x-shockwave-flash" allowscriptaccess="always" allowfullscreen="true" width="([0-9]){1,4}" height="([0-9]){1,4}"></embed></object>#si', '[youtube=http://youtube.com/v/\\3]', $msg); //youtube
    $msg = preg_replace('#<object width="([0-9]){1,4}" height="([0-9]){1,4}"><param name="movie" value="http://video.rutube.ru/(.*?)"></param><param name="wmode" value="window"></param><param name="allowFullScreen" value="true"></param><embed src="(.*?)" type="application/x-shockwave-flash" wmode="window" width="([0-9]){1,4}" height="([0-9]){1,4}" allowFullScreen="true" ></embed></object>#si', '[youtube=http://video.rutube.ru/\\3]', $msg); //youtube

    $msg = preg_replace("#<!-- Small_img:([:a-z_\-/\.0-9]+?)\|(left|right|center)? -->(.+?)<!--/Small_img -->#sie", "bb_create_img_back('\\1|\\2')", $msg); //php	
    $msg = preg_replace("#<center><img src='(\S+?)'\s*(class='lb_img')?\s*/></center>#si", "[img]\\1[/img]", $msg); //img center
    $msg = preg_replace("#<img src='(\S+?)' align='(left|right)'\s*(class='lb_img')?\s*/>#si", "[img=\\2]\\1[/img]", $msg); //img left|right

    $msg = preg_replace("#<center>(.+?)</center>#si", "[center]\\1[/center]", $msg); //center

    $msg = preg_replace("#<img id='smiles_img' src='{TEMPLATE}/bbcode/smiles/([0-9]{3,3})\.gif' />#si", "::\\1::", $msg); //smailes

    $msg = preg_replace_callback("#<!-- PHP code -->(.+?)<!--/PHP code -->#si", "php_decode", $msg); //php	
    $msg = preg_replace_callback("#<!-- JS code -->(.+?)<!--/JS code -->#si", "js_decode", $msg); //php
    $msg = preg_replace_callback("#<!-- HTML code -->(.+?)<!--/HTML code -->#si", "html_decode", $msg); //php
    return $msg;
}


function bb_url($link, $text, $encode = true)
{
    global $cache_config, $cache_group, $member, $do, $op;

    if ($encode) {
        $link = bb_clear_url(trim($link));

        if (clean_url($link) != clean_url($cache_config['general_site']['conf_value']))
            $target = "target=\"_blank\"";
        else
            $target = "";

        if (!preg_match("#^(http|news|https|ed2k|ftp|aim|mms)://|(magnet:?)#", $link))
            $link = 'http://' . $link;

        if ($link == 'http://')
            return "[url=" . $link . "]" . $text . "[/url]";

        if ($target) {
            $redirect = true;
            if ($cache_config['link_white_list']['conf_value']) {
                $white_list = explode("\r\n", $cache_config['link_white_list']['conf_value']);
                foreach ($white_list as $white_list_c)
                {
                    if (clean_url($link) == clean_url($white_list_c)) {
                        $redirect = false;
                        break;
                    }
                }
            }

            if ($redirect) {
                if ($do == "users" AND $op == "edit")
                    $link = away_from_here($link, $cache_group[$member['member_group']]['g_link_signature']);
                else
                    $link = away_from_here($link, $cache_group[$member['member_group']]['g_link_forum']);
            }
        }
        return "<a href=\"" . $link . "\" " . $target . ">" . $text . "</a>";
    }
    else
    {
        if (preg_match("#away\.php\?s\=[http|www](.+?)#si", $link)) {
            $link = preg_replace("#((.+?)away\.php\?s\=)#si", "", $link);
            $link = away_from_here($link, 1, 0);

            return "[url=" . $link . "]" . $text . "[/url]";
        }

        return "[url=" . $link . "]" . $text . "[/url]";
    }
}

function bb_create_img($img, $align = "")
{

    global $cache_config;

    $img = trim($img);
    $img = urldecode($img);

    if (preg_match("#[?&;%<\[\]]#", $img)) {
        if ($align != "")
            return "[img=" . $align . "]" . $img . "[/img]";
        else
            return "[img]" . $img . "[/img]";
    }

    $img = bb_clear_url($img);

    if ($img == "")
        return;

    $img_info = "";

    if (!$align OR $align == "center")
        $img_block = "<center><img src='" . $img . "' class='lb_img' /></center>";
    else
        $img_block = "<img src='" . $img . "' align='" . $align . "' class='lb_img' />";

    return $img_block;
}

function bb_clear_url($url)
{
    $url = strip_tags(trim(stripslashes($url)));
    $url = str_replace('\"', '"', $url);
    $url = str_replace("document.cookie", "", $url);
    $url = str_replace(" ", "%20", $url);
    $url = str_replace("'", "", $url);
    $url = str_replace('"', "", $url);
    $url = str_replace("<", "&#60;", $url);
    $url = str_replace(">", "&#62;", $url);
    $url = preg_replace("#javascript:#i", "j&#097;vascript:", $url);
    $url = preg_replace("#data:#i", "d&#097;ta:", $url);

    return $url;
}

function bb_create_img_back($img)
{
    global $cache_config;

    $img = explode("|", $img);
    if ($img[1] != "")
        return "[img=" . $img[1] . "]" . $img[0] . "[/img]";
    else
        return "[img]" . $img[0] . "[/img]";
}

function makespoiler($arg)
{
    global $lang_message;

    if ($arg[2]) $name = $arg[2];
    else $name = $lang_message['spoiler_title'];

    $id = md5($arg[3] . $name . rand(5, 1000));

    $divs = "<blockquote class=\"blockspoiler\">";
    $divs .= "<span class=\"titlespoiler\"><a href='#' onclick=\"ShowAndHide('" . $id . "'); return false;\">" . $name . "</a></span>";
    $divs .= "<div id='" . $id . "' style='display:none;' class=\"textspoiler\">" . $arg[3];

    return $divs;
}

function transliteit($str)
{
    $tr = array(
        "A", "B", "V", "G", "D", "E", "J", "Z", "I",
        "Y", "K", "L", "M", "N", "O", "P", "R", "S", "T",
        "U", "F", "H", "TS", "CH", "SH", "SCH", "YI",
        "YU", "YA",
        "a", "b", "v", "g", "d", "e", "j", "z", "i",
        "y", "k", "l", "m", "n", "o", "p", "r", "s", "t",
        "u", "f", "h", "ts", "ch", "sh", "sch", "yi",
        "yu", "ya"
    );

    $rr = array(
        "A", "?", "?", "?", "?", "?", "?", "?", "?",
        "?", "?", "?", "?", "?", "?", "?", "?", "?", "?",
        "?", "?", "?", "?", "?", "?", "?", "?", "?", "?",
        "?", "?", "?", "?", "?", "?", "?", "?", "?",
        "?", "?", "?", "?", "?", "?", "?", "?", "?", "?",
        "?", "?", "?", "?", "?", "?", "?", "?", "?", "?"
    );
    return str_replace($tr, $rr, $str[1]);
}

function php_syntax($str)
{
    $rtn = str_replace("<br />", "\r", $str[1]);
    $rtn = trim(htmlspecialchars_decode($rtn));

    $geshi = new GeSHi($rtn, "php");
    $geshi->enable_keyword_links(false);
    $geshi->set_header_type(GESHI_HEADER_DIV);
    $geshi->enable_line_numbers(GESHI_NORMAL_LINE_NUMBERS);
    $geshi->set_overall_style('font: normal normal 90% monospace; color: #000066; border: 1px solid #d0d0d0; background-color: #f0f0f0;', false);

    $geshi->set_header_content('php code:');
    $geshi->set_header_content_style('font-family: sans-serif; color: #808080; font-size: 70%; font-weight: bold; background-color: #f0f0ff; border-bottom: 1px solid #d0d0d0; padding: 2px;');

    $rtn = "<!-- PHP code -->";
    $rtn .= $geshi->parse_code();
    $rtn .= "<!--/PHP code -->";

    return $rtn . "";
}

function php_decode($str)
{
    $str = strip_tags($str[1]);
    $str = preg_replace("#^php code:#", "", $str);

    $rtn = "[php]\n" . $str . "[/php]";

    return $rtn;
}

function javascript_syntax($str)
{
    $rtn = str_replace("<br />", "\r", $str[1]);
    $rtn = trim(htmlspecialchars_decode($rtn));

    $geshi = new GeSHi($rtn, "javascript");
    $geshi->enable_keyword_links(false);
    $geshi->set_header_type(GESHI_HEADER_DIV);
    $geshi->enable_line_numbers(GESHI_NORMAL_LINE_NUMBERS);
    $geshi->set_overall_style('font: normal normal 90% monospace; color: #000066; border: 1px solid #d0d0d0; background-color: #f0f0f0;', false);

    $geshi->set_header_content('JavaScript code:');
    $geshi->set_header_content_style('font-family: sans-serif; color: #808080; font-size: 70%; font-weight: bold; background-color: #f0f0ff; border-bottom: 1px solid #d0d0d0; padding: 2px;');

    $rtn = "<!-- JS code -->";
    $rtn .= $geshi->parse_code();
    $rtn .= "<!--/JS code -->";

    return $rtn;
}

function js_decode($str)
{
    $str = strip_tags($str[1]);
    $str = preg_replace("#^JavaScript code:#", "", $str);

    $rtn = "[javascript]\n" . $str . "[/javascript]";

    return $rtn;
}

function html_syntax($str)
{
    $rtn = str_replace("<br />", "\r", $str[1]);
    $rtn = trim(htmlspecialchars_decode($rtn));

    $geshi = new GeSHi($rtn, "html4strict");
    $geshi->enable_keyword_links(false);
    $geshi->set_header_type(GESHI_HEADER_DIV);
    $geshi->enable_line_numbers(GESHI_NORMAL_LINE_NUMBERS);
    $geshi->set_overall_style('font: normal normal 90% monospace; color: #000066; border: 1px solid #d0d0d0; background-color: #f0f0f0;', false);

    $geshi->set_header_content('HTML code:');
    $geshi->set_header_content_style('font-family: sans-serif; color: #808080; font-size: 70%; font-weight: bold; background-color: #f0f0ff; border-bottom: 1px solid #d0d0d0; padding: 2px;');

    $rtn = "<!-- HTML code -->";
    $rtn .= $geshi->parse_code();
    $rtn .= "<!--/HTML code -->";

    return $rtn;
}

function html_decode($str)
{
    //$str = htmlspecialchars($str[1]);

    $str = strip_tags($str[1]);
    $str = preg_replace("#^HTML code:#", "", $str);

    $rtn = "[html]" . $str . "[/html]";

    return $rtn;
}

?>