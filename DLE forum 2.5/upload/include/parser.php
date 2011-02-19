<?php

require_once 'include/parse/functions.php';
require_once 'include/parse/bbcode/function.php';
require_once 'include/dle_parser.php';

$count = 0;

function bb_text($text)
{

    $dle_parser = new ParseFilter();

   // $text = str_replace(array('<!--dle_leech_begin-->','<!--dle_leech_end-->'), array('',''), $text);

    $text = htmlspecialchars_decode($text);
    $text = $dle_parser->decodeBBCodes($text, false);
    $text = str_replace("&nbsp;"," ", $text);
    $text = html_entity_decode($text);


    $smiles = array("winked" => "019",
    "wink" => "007",
"smile" => "002",
"am" => "017",
"belay" => "012",
"feel" => "008",
"fellow" => "013",
"laughing" => "002",
"lol" => "035",
"love" => "026",
"no" => "012",
"recourse" => "011",
"request" => "008",
"sad" => "003",
"tongue" => "005",
"wassat" => "009",
"crying" => "033",
"what" => "022",
"bully" => "006",
"angry" => "046");

    foreach($smiles as  $k=>$v)
        $text = str_replace(":".$k.":", ":".$v.":", $text);

    $text = str_replace(array("[code]","[/code]"), array("[php]", "[/php]"), $text);

    $exist = array();
    foreach($smiles as $smile)
    {
        if(isset($exist[$smile]))continue;
        $exist[$smile] = 1;
        $text = str_replace(":".$smile.":","::".$smile."::", $text);
    }

    
    return $text;
}

function html_text($text, $site)
{

    $text = preg_replace("#\[leech=(.+?)\](.+?)\[/leech\]#is", '[url=\\1]\\2[/url]', $text);


    $text = parse_word($text,  $site);

    return $text;
}

function dle_to_lb($text, $site)
{
    $text = bb_text($text);


    $text = html_text($text, $site);
 
    return $text;

}
?>
