<?php


require_once 'include/parse/functions.php';
require_once 'include/parse/bbcode/function.php';
require_once 'include/dle.php';

$count = 0;

    $smiles_hash = array("winked" => "019",
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

function dle_to_bb($text)
{
    global $smiles_hash;
    $dle_parser = new ParseFilter();

    $text = htmlspecialchars_decode($text);

    $text = $dle_parser->decodeBBCodes($text, false);
    $text = str_replace("&nbsp;"," ", $text);
    $text = html_entity_decode($text,ENT_QUOTES);	


    foreach($smiles_hash as  $k=>$v)
        $text = str_replace(":".$k.":", ":".$v.":", $text);

    $text = str_replace(array("[code]","[/code]"), array("[php]", "[/php]"), $text);

    $exist = array();
    foreach($smiles_hash as $smile)
    {
        if(isset($exist[$smile]))continue;
        $exist[$smile] = 1;
        $text = str_replace(":".$smile.":","::".$smile."::", $text);
    }
	$text = preg_replace('#\[color=\#(.+?);\]#si','[color=#\\1]',$text);

    return $text;
}

function dlebb_to_html($text, $site)
{
    $text = preg_replace("#\[leech=(.+?)\](.+?)\[/leech\]#is", '[url=\\1]\\2[/url]', $text);
    $text = parse_word($text,  $site);
    return $text;
}

function dle_to_lb($text)
{
    $text = dle_to_bb($text);
    $text = dlebb_to_html($text,"");
    return $text;
}

function lb_to_dle($text)
{
    global $smiles_hash;
    $parser = new ParseFilter();
    $text = bb_encode($text);

    foreach($smiles_hash as $k => $v)
        $text = str_replace("::".$v."::", ":".$k.":", $text);

    $text = $parser->BB_Parse($text);

    $text = preg_replace("#\:\:\d+\:\:#si", "", $text);

    return $text;
}


?>
