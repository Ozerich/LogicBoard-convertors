<?php
/*
function filters_input($check = 'all')
{
    require_once 'parse/safehtml.php';
    $safehtml = new safehtml( );
    $safehtml->protocolFiltering = "black";
    
    require_once 'parse/safeinput.php';
    $safeinput = new safeinput;
    $safeinput->safeinput_check($check);
    
    unset($safehtml);
    unset($safeinput);
}

function wrap_word($str)
{    
   	$max_lenght = 80; // максимальная длина слова
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

function parse_word ($msg, $site, $bbcode = true, $wrap_word = true)
{
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
   
    if ($bbcode)
        $msg = bb_decode($msg, $site);

    return $msg;
}

function parse_back_word ($msg, $bbcode = true)
{   
    $msg = str_replace( "<br>", "\n", $msg );
    $msg = str_replace( "<br />", "\n", $msg );
    
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
                 */
?>
