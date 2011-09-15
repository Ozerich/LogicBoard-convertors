<?php


require_once 'include/parse/functions.php';
require_once 'include/parse/bbcode/function.php';
require_once 'include/ipb_parser.php';

$count = 0;

$smiles = array(
"mellow" => "002",
"huh" => "002",
"happy" => "041",
"ohmy" => "009",
"wink" => "004",
"tongue" => "005",
"biggrin" => "007",
"laugh" => "002",
"cool" => "006",
"rolleyes" => "008",
"sleep" => "021",
"dry" => "004",
"smile" => "002",
"wub" => "026",
"angry" => "046",
"sad" => "003",
"unsure" => "039",
"wacko" => "039",
"blink" => "038",
"ph34r" => "032");


function bb_text($text, $site)
{
	global $smiles;
    $text = str_replace("\n","",$text);
    $text = htmlspecialchars_decode($text);

    foreach($smiles as $text_smile=>$code_smile)
    {
        preg_match("#<img src='[^\#]+?style_emoticons/<\#EMO_DIR\#>/".$text_smile."\.gif' class='bbc_emoticon' alt='.+?' />#sui", $text, $a);
        $text = preg_replace("#<img src='[^\#]+?style_emoticons/<\#EMO_DIR\#>/".$text_smile."\.gif' class='bbc_emoticon' alt='.+?' />#sui",
            '::'.$code_smile.'::', $text);
    }

    $text = str_replace('[/list]<br />', '[/list]', $text);
    $text = preg_replace('#\[list.*?\]<br />(.+?)\[/list\]#sui', '\\1', $text);


    $text = preg_replace('#<strike>(.+?)</strike>#sui', '[s]\\1[/s]', $text);
    $text = preg_replace("#<!--quoteo.*?--><div class='quotetop'>.+?</div><div class='quotemain'><!--quotec-->(.+?)<!--QuoteEnd--></div><!--QuoteEEnd-->#sui",
        "[quote]\\1[/quote]", $text);

    $text = preg_replace('#\[quote.*?\]#sui', '[quote]', $text);

    $text = preg_replace("#<a href='index.php\?showtopic=(\d+)'>(.+?)</a>#sui", '[url='.$site.'?do=board&op=topic&id=\\1]\\2[/url]', $text);
    $text = preg_replace("#<a href='index.php\?act=findpost&pid=(\d+)'>(.+?)</a>#sui", '', $text);
    $text = preg_replace("#<div class='codetop'>.+?</div><div class='codemain' style='height:200px;white-space:pre;overflow:auto'>(.+?)</div>#sui",
       '[code]\\1[/code]', $text);
    $text = preg_replace("#<span style='color:\#000000;background:\#000000'>(.+?)</span>#sui", '[spoiler]\\1[/spoiler]', $text);

    $parser = new post_parser();
    $text = $parser->unconvert($text);

    $text = preg_replace('#\[code\](.+?)\[/code\]#sui', '[php]\\1[/php]', $text);
    $text = preg_replace('#\[sql\](.+?)\[/sql\]#sui', '[php]\\1[/php]', $text);
    $text = preg_replace('#\[xml\](.+?)\[/xml\]#sui', '[php]\\1[/php]', $text);

    return $text;
}

function html_text($text, $site)
{
    $text = parse_word($text,  $site);
    return $text;
}

function ipb_to_lb($text, $site)
{
    $text = bb_text($text, $site);
    $text = html_text($text, $site);
    $text = htmlspecialchars_decode($text);
    return $text;
}


?>
