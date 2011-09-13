<?php

function fetch_array($sql_result)
{
    for ($result = array(); $row = mysql_fetch_array($sql_result); $result[] = $row) ;
    return $result;
}


function translit($str, $utf=true)
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
        if(!$utf){
    $trr = array();

        foreach($tr as $rus => $eng)
            $trr[iconv("UTF-8", "Windows-1251", $rus)] = $eng;
    $tr = $trr;
}
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


function int_to_datetime($time)
{
    $date = getdate($time);
    $result = $date['year']."-".
            ($date['mon'] > 10 ? $date['mon'] : "0".$date['mon'])."-".
           ($date['mday'] > 10 ? $date['mday'] : "0".$date['mday'])." ".
           ($date['hours'] > 10 ? $date['hours'] : "0".$date['hours']).":".
           ($date['minutes'] > 10 ? $date['minutes'] : "0".$date['minutes']).":".
           ($date['seconds'] > 10 ? $date['seconds'] : "0".$date['seconds']);
    return $result;
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
    return in_array($extension,$images_ext);
}



?>