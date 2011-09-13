<?php


require_once 'include/parse/functions.php';
require_once 'include/parse/bbcode/function.php';
require_once 'include/ipb.php';

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
"ph34r" => "032"
);


function ipb_to_bb($text)
{
    $text = preg_replace("#(\[quote.+?) post=.+?\&\#39;#si", "\\1", $text);

    $text = preg_replace("#(\[quote.+?).*?name=\&\#39;(.+?)\&\#39;\s#si", "[quote=\\2 ", $text);


    preg_match_all('#\[quote=.+?\ |timestamp=\&\#39;(.+?)\&\#39;\]#si', $text, $quotes, PREG_SET_ORDER);
    foreach($quotes as $quote)
    {
        $time = date("d.m.Y, H:i", $quote[1]);
        $text = str_replace(" timestamp=&#39;".$quote[1]."&#39;", "|".$time, $text);
    }
	global $smiles;
    $text = str_replace("\n","",$text);
    $text = htmlspecialchars_decode($text);

    foreach($smiles as $text_smile=>$code_smile)
    {
        preg_match("#<img src='[^\#]+?style_emoticons/<\#EMO_DIR\#>/".$text_smile."\.gif' class='bbc_emoticon' alt='.+?' />#si", $text, $a);
        $text = preg_replace("#<img src='[^\#]+?style_emoticons/<\#EMO_DIR\#>/".$text_smile."\.gif' class='bbc_emoticon' alt='.+?' />#si",
            '::'.$code_smile.'::', $text);
    }

    $text = str_replace('[/list]<br />', '[/list]', $text);
    $text = preg_replace('#\[list.*?\]<br />(.+?)\[/list\]#si', '\\1', $text);


    $text = preg_replace('#<strike>(.+?)</strike>#si', '[s]\\1[/s]', $text);
    $text = preg_replace("#<!--quoteo.*?--><div class='quotetop'>.+?</div><div class='quotemain'><!--quotec-->(.+?)<!--QuoteEnd--></div><!--QuoteEEnd-->#si",
        "[quote]\\1[/quote]", $text);

    //$text = preg_replace('#\[quote.*?\]#si', '[quote]', $text);

    $text = preg_replace("#<a href='index.php\?showtopic=(\d+)'>(.+?)</a>#si", '[url=?do=board&op=topic&id=\\1]\\2[/url]', $text);
    $text = preg_replace("#<a href='index.php\?act=findpost&pid=(\d+)'>(.+?)</a>#si", '', $text);
    $text = preg_replace("#<div class='codetop'>.+?</div><div class='codemain' style='height:200px;white-space:pre;overflow:auto'>(.+?)</div>#si",
       '[code]\\1[/code]', $text);
    $text = preg_replace("#<span style='color:\#000000;background:\#000000'>(.+?)</span>#si", '[spoiler]\\1[/spoiler]', $text);

    $parser = new post_parser();
    $text = $parser->unconvert($text);

    $text = preg_replace('#\[code\](.+?)\[/code\]#si', '[php]\\1[/php]', $text);
    $text = preg_replace('#\[sql\](.+?)\[/sql\]#si', '[php]\\1[/php]', $text);
    $text = preg_replace('#\[xml\](.+?)\[/xml\]#si', '[php]\\1[/php]', $text);

    return $text;
}

function ipbbb_to_html($text)
{
    $text = parse_word($text, "");
    return $text;
}

function ipb_to_lb($text)
{

    $text = ipb_to_bb($text);
   //   $text = "[quote]Вот обсудим вложения [/quote]";
    $text = ipbbb_to_html($text);
   //   print_r($text);exit();
    $text = htmlspecialchars_decode($text);
    return $text;
}


?>
