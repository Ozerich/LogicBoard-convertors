<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
    <meta http-equiv="content-type" content="text/html; charset=windows-1251"/>
    <title>Конвертор LogicBoard 2.1(DLE Edition)</title>
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

         div.commonoption_item {
            display: block;
            padding-top: 15px;
             clear: both;
        }



        div#submit {
            padding-top: 10px;
            margin: 0 auto 10px auto;
            clear: both;
            width: 100px;
        }

        .option_item > label {
            width: 110px;
            display: inline-block;
        }

        .commonoption_item > label {
            width: 410px;
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

        #warning ul li{
            color: #9F6000;
            font-size: 11px;
            padding-top: 5px;
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
ini_set('memory_limit', '1024M');
ini_set('display_errors',1);


require_once "modules/dle_2.5.php";
require_once "modules/dle_2.6.php";
require_once "modules/phpbb_3.0.9.php";
require_once "modules/ipb_3_1_4.php";
require_once "modules/lb_2.0.php";
require_once "modules/twsf.php";

$engines = array(new DLE_2_5(), new DLE_2_6(), new TWSF(), new IPB_3_1_4(), new LB_2_0(), new phpBB_3_0_9(), new VB_3_6);
$engine = null;

if(isset($_POST['engine'])) {
    foreach($engines as $cur_engine)
    {
        if($_POST['engine'] == $cur_engine->Setup("id"))
        {
            $engine = $cur_engine;
            break;
        }
    }
}


if(isset($_POST['convert_submit']))
{
    $engine->ConnectDestSql($_POST['to_mysql_host'], $_POST['to_mysql_login'], $_POST['to_mysql_password']);
    $engine->ConnectSrcSql($_POST['from_mysql_host'], $_POST['from_mysql_login'], $_POST['from_mysql_password']);

    $engine->SetDestDb($_POST['to_db_name'], $_POST['to_db_prefix']);
    $engine->SetSrcDb($_POST['from_db_name'], $_POST['from_db_prefix']);



    if(!$engine->Setup("dle_based"))
        $engine->SetDleDB($_POST['to_db_dleprefix']);
    $options = array();
    if(isset($_POST['rep_mod']) && $_POST['rep_mod'] == "on")
        $options[] = "rep_mod";
    if(isset($_POST['admin_name']))
        $options['admin_name'] = $_POST['admin_name'];

    $engine->Convert($options);
    $engine->OnFinish();
}

else if(isset($_POST['engine_choose_submit'])){


?>

<form action="" method="POST">
    <div id="page">
        <div id="header">Конвертор LogicBoard 2.1(DLE Edition)</div>
        <?php if($engine->Setup("add_text")){ echo '<div id="warning"><ul>';
            foreach($engine->Setup("add_text") as $text)
                 echo "<li>".$text.'</li>';
        echo '</ul></div>';}
        ?>
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
            <?php if ($engine->Setup("id") == "phpbb_3_0_9") {?>
            <div class="option_item">
                <label>Логин администратора</label>
                <input type="text" name="admin_name"/>
            </div>
            <?php } ?>
        </div>
        <div id="to_container">
            <label class="container-header">LogicBoard 2.1(DLE)</label>

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
            <?php if ($engine->Setup("dle_based")) {?>
            <div class="option_item">
                <label>Префикс таблиц</label>
                <input onfocus='show_tooltip(this, "Если у вас база с \"_\" то не забудьте её добавить в поле");'
                       onblur='hide();'
                       type="text" name="to_db_prefix" value="LB_"/>
            </div>
            <?php } else { ?>
            <div class="option_item">
                <label>Префикс таблиц DLE</label>
                <input onfocus='show_tooltip(this, "Если у вас база с \"_\" то не забудьте её добавить в поле");'
                       onblur='hide();'
                       type="text" name="to_db_dleprefix" value="dle_"/>
            </div>
             <div class="option_item">
                <label>Префикс таблиц LB</label>
                <input onfocus='show_tooltip(this, "Если у вас база с \"_\" то не забудьте её добавить в поле");'
                       onblur='hide();'
                       type="text" name="to_db_prefix" value="LB_"/>
            </div>
            <?php } ?>

        </div>
        <div class="commonoption_item">
           <label>Установлен модуль репутации для CMS DLE от ShapeShifter</label>
           <input type="checkbox" name="rep_mod"/>
        </div>
        <div id="submit">
            <input type="submit" name="convert_submit" value="Конвертировать"/>
        </div>
        <div id="warning">Если у вас нет модуля репутации, то вы можете купить его
            <a target="_blank" href="http://savgroup.ru/modules_dle/pay_modules_dle/38-reputaciya-65.html">здесь</a></div>
    </div>
    <input type="hidden" name="engine" value="<?echo $_POST['engine'];?>"/>
</form>

<?php }else{ ?>

<form action="" method="POST">
    <div id="page">
        <div id="header">Конвертор LogicBoard 2.1(DLE Edition)</div>
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
