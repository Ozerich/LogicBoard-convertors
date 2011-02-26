<?php


include_once('geshi/geshi.php');

$count = 0;
function bb_decode($msg, $site)
{
    global $count;
    $count++;
    $cache_config['general_site']['conf_value'] = $site; // здесь нужно будет передать адрес форума, добавить поле в конвертер. Вид: http://site.ru/forum/ или просто http://site.ru/  
	$msg = preg_replace("#\[b\](.+?)\[/b\]#is", "<strong>\\1</strong>", $msg); //Bold	
	$msg = preg_replace("#\[i\](.+?)\[/i\]#ius", "<i>\\1</i>", $msg); //italic
	$msg = preg_replace("#\[s\](.+?)\[/s\]#ius", "<s>\\1</s>", $msg); //S
	$msg = preg_replace("#\[u\](.+?)\[/u\]#ius", "<u>\\1</u>", $msg); //S
	
    $msg = preg_replace("#\[center\](.+?)\[/center\]#ius", "<center>\\1</center>", $msg); //center
	$msg = preg_replace("#\[size=([0-9]+?)\](.+?)\[/size\]#ius", "<font size='\\1'>\\2</font>", $msg); //size
	$msg = preg_replace("#\[font=([a-z ]+?)\](.+?)\[/font\]#ius", "<font style='font-family:\\1'>\\2</font>", $msg); //font-family
	$msg = preg_replace("#\[color=(\#[0-9ACDEF]+?)\](.+?)\[/color\]#ius", "<font style='color:\\1'>\\2</font>", $msg); //Color
    if(preg_match_all("#\[quote(=(([a-zа-я0-9№ _\-]+?)(\|([0-9\., :]+?))?))?\]#ius", $msg, $shadow) == preg_match_all("#\[/quote\]#ius", $msg, $shadow))
    {
        $msg = preg_replace("#\[quote\]#ius", "<blockquote><p><span>Цитата:</span>", $msg); //quote
        $msg = preg_replace("#\[quote(=(([a-zа-я0-9№ _\-]+?)(\|([0-9\., :]+?))?))?\]#ius", "<blockquote><p><span>\\3 (\\5) писал:</span>", $msg); //quote
	    $msg = preg_replace("#\[/quote\]#ius", "</p></blockquote><!--quote -->", $msg); //quote
        $msg = preg_replace("#<blockquote><p><span>(.+?) \(\) (.+?)</span>#ius", "<blockquote><p><span>\\1 \\2</span>", $msg); //quote 	
	} 

    $msg = preg_replace("#\[youtube=(http://(www\.)?youtube.com/v/.+?)\]#ius", '<object width="480" height="385"><param name="movie" value="\\1"></param><param name="allowFullScreen" value="true"></param><param name="allowscriptaccess" value="always"></param><embed src="\\1" type="application/x-shockwave-flash" allowscriptaccess="always" allowfullscreen="true" width="480" height="385"></embed></object>', $msg); //youtube
	$msg = preg_replace("#\[youtube=http://(www\.)?youtube.com/watch\?v=([a-z0-9]+).*?\]#ius", '<object width="480" height="385"><param name="movie" value="http://youtube.com/v/\\2?fs=1"></param><param name="allowFullScreen" value="true"></param><param name="allowscriptaccess" value="always"></param><embed src="http://youtube.com/v/\\2?fs=1" type="application/x-shockwave-flash" allowscriptaccess="always" allowfullscreen="true" width="480" height="385"></embed></object>', $msg); //youtube
    
	//$msg = preg_replace("#\[url=([:a-z_\-/\.\?&=0-9;\#]+?)\](.+?)\[/url\]#ius", "<a href='\\1'>\\2</a>", $msg); //url
    $msg = preg_replace("#\[url=(\S.+?)\](.+?)\[/url\]#iuse", "bb_url('\\1', '\\2')", $msg); //url
	$msg = preg_replace("#\[email=([a-z_\-0-9]+?@[a-z_\-0-9]+?\.[a-z]+?)\](.+?)\[/email\]#ius", "<a href='mailto:\\1'>\\2</a>", $msg); //email
    
    $msg = preg_replace("#\[img=?\](.+?)\[/img\]#iuse", "bb_create_img('\\1')", $msg); //img center
	$msg = preg_replace("#\[img=(left|right|center)+?\](.+?)\[/img\]#iuse", "bb_create_img('\\2', '\\1')", $msg); //img left|right
    
    $msg = preg_replace_callback("#\[php\]([\s\S]+?)\[/php\]#ius", "php_syntax", $msg); //php	
	$msg = preg_replace_callback("#\[javascript\]([\s\S]+?)\[/javascript\]#ius", "javascript_syntax", $msg); //javascript	
	$msg = preg_replace_callback("#\[html\]([\s\S]+?)\[/html\]#ius", "html_syntax", $msg); //html	
    
	$msg = preg_replace_callback("#\[translite\]([\s\S]+?)\[/translite\]#ius", "transliteit", $msg); //translite	   
       
    if(preg_match_all("#\[spoiler(=([^;!@\"'<>]+?))?\]#ius", $msg, $shadow) == preg_match_all("#\[/spoiler\]#ius", $msg, $shadow))
    {
        $msg = preg_replace_callback("#\[spoiler(=([^;!@\"'<>]+?))?\]#ius", "makespoiler", $msg); //spoiler	
	    $msg = preg_replace("#\[/spoiler\]#i", "</div></blockquote><!--spoiler -->", $msg); //spoiler
	}
    
	$msg = preg_replace("#::([0-9]{3,3})::#i", "<img id='smiles_img' src='".$cache_config['general_site']['conf_value']."components/scripts/bbcode/img/smiles/\\1.gif' />", $msg); //smailes	
    return $msg;
}

function bb_encode($msg, $site)
{   
    $cache_config['general_site']['conf_value'] = $site; // здесь нужно будет передать адрес форума, добавить поле в конвертер. Вид: http://site.ru/forum/ или просто http://site.ru/
    
	$msg = preg_replace("#<strong>(.+?)</strong>#ius", "[b]\\1[/b]", $msg); //Bold
    $msg = preg_replace("#<b>(.+?)</b>#ius", "[b]\\1[/b]", $msg); //Bold	
	$msg = preg_replace("#<i>(.+?)</i>#ius", "[i]\\1[/i]", $msg); //italic
	$msg = preg_replace("#<s>(.+?)</s>#ius", "[s]\\1[/s]", $msg); //S
	$msg = preg_replace("#<u>(.+?)</u>#ius", "[u]\\1[/u]", $msg); //S
    $msg = preg_replace("#<center>(.+?)</center>#ius", "[center]\\1[/center]", $msg); //center
	$msg = preg_replace("#<font size='([0-9]+?)'>(.+?)</font>#ius", "[size=\\1]\\2[/size]", $msg); //size
	$msg = preg_replace("#<font style='font-family:([a-z ]+?)'>(.+?)</font>#ius", "[font=\\1]\\2[/font]", $msg); //font-family
	$msg = preg_replace("#<font style='color:(\#[0-9ACDEF]+?)'>(.+?)</font>#ius", "[color=\\1]\\2[/color]", $msg); //Color
    
    $pattern = array("@<blockquote><a href='\#' onclick=\"ShowAndHide\('.+?'\); return false;\">([a-zа-я \-_0-9]+?)</a><div id='.+?' style='display:none;'>@ius", 
					"@<blockquote><a href='\#' onclick=\"ShowAndHide\('.+?'\); return false;\">Спойлер \[\+\]</a><div id='.+?' style='display:none;'>@ius", 
					"@</div></blockquote><!--spoiler -->@i"); 
       
	$replacement = array( 
	'[spoiler=\\1]', 
	'[spoiler]', 
	'[/spoiler]'); 
	$msg = preg_replace($pattern, $replacement, $msg);
       
        
    $pattern = array("@<blockquote><p><span>([a-zа-я0-9 _\-]+?) ?(\(([0-9\., :]+?)\))? писал:</span>@ius", 
					"@<blockquote><p><span>Цитата:</span>@ius", 
					"@</p><\/blockquote><!--quote -->@i"); 
       
	$replacement = array( 
	'[quote=$1|$3]', 
	'[quote]', 
	'[/quote]'); 
	$msg = preg_replace($pattern, $replacement, $msg);
	
	$msg = preg_replace("#\[quote=(.+?)\|\]#ius", "[quote=\\1]", $msg); 
    
	$msg = preg_replace("#<a href='mailto:([a-z_\-0-9]+?@[a-z_\-0-9]+?\.[a-z]+?)'>(.+?)</a>#ius", "[email=\\1]\\2[/email]", $msg); //email
	$msg = preg_replace("#<a href=['\"](\S.+?)['\"]\s*>(.+?)</a>#ius", "[url=\\1]\\2[/url]", $msg); //url
    $msg = preg_replace('#<object width="480" height="385"><param name="movie" value="([:a-z_\-/\.\?&;=0-9]+?)"></param><param name="allowFullScreen" value="true"></param><param name="allowscriptaccess" value="always"></param><embed src="\\1" type="application/x-shockwave-flash" allowscriptaccess="always" allowfullscreen="true" width="480" height="385"></embed></object>#ius', '[youtube=\\1]', $msg); //youtube
    
    $msg = preg_replace("#<!-- Small_img:([:a-z_\-/\.0-9]+?)\|(left|right|center)? -->(.+?)<!--/Small_img -->#iuse", "bb_create_img_back('\\1|\\2')", $msg); //php	
	$msg = preg_replace("#<center><img src='(\S+?)'/></center>#ius", "[img]\\1[/img]", $msg); //img center
	$msg = preg_replace("#<img src='(\S+?)' align='(left|right|center)'/>#ius", "[img=\\2]\\1[/img]", $msg); //img left|right
    
    $msg = preg_replace("#<img id='smiles_img' src='".$cache_config['general_site']['conf_value']."components/scripts/bbcode/img/smiles/([0-9]{3,3})\.gif' />#i", "::\\1::", $msg); //smailes
    
    $msg = preg_replace_callback("#<!-- PHP code -->(.+?)<!--/PHP code -->#ius", "php_decode", $msg); //php	
	$msg = preg_replace_callback("#<!-- JS code -->(.+?)<!--/JS code -->#ius", "js_decode", $msg); //php	
	$msg = preg_replace_callback("#<!-- HTML code -->(.+?)<!--/HTML code -->#ius", "html_decode", $msg); //php
	
	return $msg;
}

function bb_url ($link, $text)
{
    $link = bb_clear_url(trim($link));
   	if(!preg_match( "#^(http|news|https|ed2k|ftp|aim|mms)://|(magnet:?)#", $link ))
        $link = 'http://'.$link;
        
    if ($link == 'http://' )
        return "[url=".$link."]".$text."[/url]";
            
   return "<a href=\"".$link."\">".$text."</a>";
}

function bb_create_img($img, $align = "")
{
    global $cache_config;

        $img = trim($img);
		$img = urldecode($img);
        
        if( preg_match( "#[?&;%<\[\]]#", $img ) )
        {
			if( $align != "" )
                return "[img=" . $align . "]" . $img . "[/img]";
			else
                return "[img]" . $img . "[/img]";
		
		}
                
        $img = bb_clear_url($img);
        
        if($img == "")
            return;
        
        $img_info = "";
        
        if (!$align OR $align == "center")
            $img_block = "<center><img src='".$img."' class='lb_img' /></center>";
        else
            $img_block = "<img src='".$img."' align='".$align."' class='lb_img' />";

	return $img_block;
}

function bb_clear_url($url)
{
    $url = strip_tags( trim( stripslashes( $url ) ) );
	$url = str_replace( '\"', '"', $url );
	$url = htmlspecialchars( $url, ENT_QUOTES );
	$url = str_replace( "document.cookie", "", $url );
	$url = str_replace( " ", "%20", $url );
	$url = str_replace( "'", "", $url );
	$url = str_replace( '"', "", $url );
	$url = str_replace( "<", "&#60;", $url );
	$url = str_replace( ">", "&#62;", $url );
	$url = preg_replace( "#javascript:#i", "j&#097;vascript:", $url );
	$url = preg_replace( "#data:#i", "d&#097;ta:", $url );
		
	return $url;
}

function bb_create_img_back($img)
{    
    $img = explode ("|", $img);
    if ($img[1] != "")
        return "[img=".$img[1]."]".$img[0]."[/img]";
    else
        return "[img]".$img[0]."[/img]";

}

function makespoiler($arg)
{
	if($arg[2])
		$name = $arg[2];
    else
        $name = "";
	
	$id = md5($arg[3].$name.rand(5,1000));
	
	$divs = "<blockquote>";
    if ($name)
        $divs.= "<a href='#' onclick=\"ShowAndHide('$id'); return false;\">$name</a>";
    else
        $divs.= "<a href='#' onclick=\"ShowAndHide('$id'); return false;\">Спойлер [+]</a>";
        
	$divs.= "<div id='$id' style='display:none;'>{$arg[3]}";

	return $divs;
}

function transliteit($str)
{
    $tr = array(
        "A","B","V","G", "D","E", "J","Z","I",
        "Y","K","L","M","N", "O","P","R","S","T",
        "U","F","H","TS","CH", "SH","SCH","YI",
        "YU","YA",
        "a","b","v","g", "d","e", "j","z","i",
        "y","k","l","m","n", "o","p","r","s","t",
        "u","f","h","ts","ch", "sh","sch","yi",
        "yu","ya"
    );	
    
     $rr = array(
        "A","Б","В","Г", "Д","Е","Ж","З","И",
        "Й","К","Л","М","Н","О","П","Р","С","Т",
        "У","Ф","Х","Ц","Ч","Ш","Щ","Ы","Ю","Я",
        "а","б","в","г", "д","е","ж","з","и",
        "й","к","л","м","н", "о","п","р","с","т",
        "у","ф","ч","ц","ч","ш","щ","ы","ю","я"
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
	$rtn.= $geshi->parse_code();
	$rtn.= "<!--/PHP code -->";
	
	return $rtn."";
}
function php_decode($str)
{
	$str = strip_tags($str[1]);
	$str = preg_replace("#^php code:#", "",$str);
	
	$rtn = "[php]\n".$str."[/php]";
	
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
	$rtn.= $geshi->parse_code();
	$rtn.= "<!--/JS code -->";
	
	return $rtn;
}

function js_decode($str)
{
	$str = strip_tags($str[1]);
	$str = preg_replace("#^JavaScript code:#", "",$str);
	
	$rtn = "[javascript]\n".$str."[/javascript]";
	
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
	$rtn.= $geshi->parse_code();
	$rtn.= "<!--/HTML code -->";
	
	return $rtn;
}

function html_decode($str)
{
	//$str = htmlspecialchars($str[1]);
	
	$str = strip_tags($str[1]);
	$str = preg_replace("#^HTML code:#", "",$str);
	
	$rtn = "[html]".$str."[/html]";
	
	return $rtn;
}

?>

