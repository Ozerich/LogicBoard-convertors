<?
$title = "Конвертор LogicBoard 2.1";
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8"/>
    <title><?=$title?></title>
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

        div#engine-choose{
            margin:0 auto;
            width: 325px;
        }

        #tooltip{
            position: absolute;
            width: 200px;
            background: #FEEFB3;
            color: #9F6000;
	        padding: 10px;
            -webkit-border-radius: 9px;
            -moz-border-radius: 9px;
            border-radius: 9px;
            border: 1px solid;
            visibility:hidden;
            margin-top: 5px;
            padding: 10px 10px 10px 20px;
        }

        #warning{
            -webkit-border-radius: 9px;
            -moz-border-radius: 9px;
            border-radius: 9px;
            color: #9F6000;
            background: #FEEFB3;
            border: 1px solid;
            margin-bottom:10px;
            padding:10px 5px;
            text-align:center;
        }

    </style>
    <script type="text/javascript">

        var tooltip, op;
        function show_tooltip(el, txt)
        {
            tooltip = document.getElementById('tooltip');
            tooltip.innerHTML = txt;
            op = 0.1;
            tooltip.style.opacity = op;
            tooltip.style.visibility = "visible";

            tooltip.style.left = (el.offsetLeft - 10)+"px";
            tooltip.style.top = (el.offsetTop + 20)+"px";

            show();
            return false;
        }

        function show()
        {
            if(op < 1)
            {
                op += 0.1;
                tooltip.style.opacity = op;
                tooltip.style.filter = 'alpha(opacity='+op*100+')';
                t = setTimeout("show()", 30);
            }
        }

        function hide(el)
        {
            tooltip.style.visibility = "hidden";
        }

    </script>
</head>
<body>
<div id="tooltip"></div>

<?php

set_time_limit(0);
ini_set('memory_limit', '512M');

require_once "modules/dle_2.5.php";
require_once "modules/dle_2.6.php";
require_once "modules/ipb_2_3_6.php";
require_once "modules/ipb_3_1_4.php";
require_once "modules/twsf.php";

$engines = array(new DLE_2_5(), new DLE_2_6(), new TWSF(), new IPB_2_3_6(), new IPB_3_1_4());
$engine = null;

if(isset($_POST['engine']))
    foreach($engines as $cur_engine)
        if($_POST['engine'] == $cur_engine->Setup("id"))
        {
            $engine = $cur_engine;
            break;
        }

if(isset($_POST['convert_submit']))
{
    $engine->ConnectDestSql($_POST['to_mysql_host'], $_POST['to_mysql_login'], $_POST['to_mysql_password']);
    $engine->ConnectSrcSql($_POST['from_mysql_host'], $_POST['from_mysql_login'], $_POST['from_mysql_password']);

    $engine->SetDestDb($_POST['to_db_name'], $_POST['to_db_prefix']);
    $engine->SetSrcDb($_POST['from_db_name'], $_POST['from_db_prefix']);

    $engine->Convert();
}

else if(isset($_POST['engine_choose_submit'])){


?>

<form action="" method="POST">
    <div id="page">
        <div id="header"><?=$title?></div>
        <?php if($engine->Setup("add_text") != "") echo '<div id="warning">'.$engine->Setup("add_text").'</div>'; ?>
        <div id="from_container">

            <label class="container-header"><?php echo $engine->Setup("caption");?></label>

            <div class="option_item">
                <label>MySQL Сервер</label>
                <input type="text" name="from_mysql_host" value="localhost"/>
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
                <input type="text" name="from_db_name" />
            </div>
            <div class="option_item">
                <label>Префикс таблиц</label>
                <input onfocus='show_tooltip(this, "Если у вас база с \"_\" то не забудьте её добавить в поле");'
                       onblur='hide();'
                       type="text" name="from_db_prefix" value="<?php echo $engine->Setup("db_prefix"); ?>"/>
            </div>
        </div>
        <div id="to_container">
            <label class="container-header">LogicBoard 2.0</label>

            <div class="option_item">
                <label>MySQL Сервер</label>
                <input type="text" name="to_mysql_host" value="localhost"/>
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
                <input onfocus='show_tooltip(this, "Если у вас база с \"_\" то не забудьте её добавить в поле");'
                       onblur='hide();'
                       type="text" name="to_db_prefix" value="LB_"/>
            </div>

        </div>
        <div id="submit">
            <input type="submit" name="convert_submit" value="Конвертировать"/>
        </div>
    </div>
    <input type="hidden" name="engine" value="<? echo $_POST['engine']; ?>"/>
</form>

<?php }else{ ?>

<form action="" method="POST">
    <div id="page">
        <div id="header"><?=$title?></div>
        <div id="engine-choose">
            <label>Форум для конвертирования</label>
            <select name="engine">
                <?php
                foreach($engines as $eng)
                    echo "<option value=\"". $eng->Setup("id")."\">".$eng->Setup("caption")."</option>";
                ?>
            </select>
        </div>
        <div id="submit">
            <input type="submit" name="engine_choose_submit" value="Далее"/>
        </div>
    </div>
</form>

<?php }?>

</body>
</html>
