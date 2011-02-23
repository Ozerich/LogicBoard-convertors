<?php

set_time_limit(0);
ini_set('memory_limit', '512M');

$status = array(
    "MYSQL_FROM_ERROR" => "Невозможно подлючиться к MySQL серверу IPB форума",
    "MYSQL_TO_ERROR" => "Невозможно подлючиться к MySQL серверу LogicBoard",
    "FROM_DB_NOFOUND" => "Не найдена база данных IPB форума",
    "TO_DB_NOFOUND" => "Не найдена база данных LogicBoard",
    "BAD_SITEPATH" => "Неправильный формат адреса сайта",
    "NO_ERROR" => "Форум успешно перенесён. Удалите этот файл!",
);

$last_limit_value = 0;
$last_limit_query = "";

$lb_prefix = $lb_dbname = $tws_prefix = $tws_dbname = $ipb_prefix = "";

$limit_count = 1000;

function fetch_array($sql_result)
{
    for ($result = array(); $row = mysql_fetch_array($sql_result); $result[] = $row) ;
    return $result;
}

function get_limit_query($query, $sql_hanipb)
{
    global $limit_count, $last_limit_query, $last_limit_value;
    if ($query != $last_limit_query) {
        $last_limit_value = 0;
        $last_limit_query = $query;
    }
    $start = $last_limit_value;
    $finish = $start + $limit_count;
    $last_limit_value = $finish;
    $sql_result = mysql_query($query . " LIMIT " . $start . "," . $limit_count, $sql_hanipb);
    return $sql_result;
}

function translit($str)
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
    return str_replace(" ", "-", strtr($str, $tr));
}


function check_url($url)
{
    $preg = '#http://.+?/$#sui';
    return preg_match($preg, $url);
}

function convert($params)
{

    $lb_prefix_ = $params['to_db_prefix'];
    $ipb_prefix_ = $params['from_db_prefix'];
    $lb_dbname_ = $params['to_db_name'];
    $ipb_dbname_ = $params['from_db_name'];
    $site_path = $params['to_site_path'];

    $GLOBALS['lb_prefix'] = $lb_prefix_;
    $GLOBALS['ipb_prefix'] = $ipb_prefix_;
    $GLOBALS['lb_dbname'] = $lb_dbname_;
    $GLOBALS['ipb_dbname'] = $ipb_dbname_;

    global $lb_prefix, $lb_dbname, $ipb_dbname, $ipb_prefix, $last_limit_value;

    if (!check_url($site_path))
        return "BAD_SITEPATH";

    $sql_from = mysql_connect($params['from_mysql_host'], $params['from_mysql_login'], $params['from_mysql_password']);
    $sql_to = mysql_connect($params['to_mysql_host'], $params['to_mysql_login'], $params['to_mysql_password']);

    if ($sql_from == FALSE) return "MYSQL_FROM_ERROR";
    if ($sql_to == FALSE) return "MYSQL_TO_ERROR";

    $GLOBALS['sql_from'] = $sql_from;
    $GLOBALS['sql_to'] = $sql_to;
    global $sql_to, $sql_from;


    if (mysql_select_db($params['from_db_name'], $sql_from) == FALSE) return "FROM_DB_NOFOUND";
    if (mysql_select_db($params['to_db_name'], $sql_to) == FALSE) return "TO_DB_NOFOUND";

    mysql_query("SET NAMES UTF8", $sql_from) or die(mysql_error());
    mysql_query("SET NAMES UTF8", $sql_to) or die(mysql_error());

    echo "Install...";
    include "install.php";
    install($sql_to, $lb_dbname, $lb_prefix);
    echo "OK<br/>";

}

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8"/>
    <title>Конвертор IPB 2.3.6 -> LogicBoard</title>
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
    </style>
</head>
<body>

<?php
    if (isset($_POST['convert_submit'])) {

    $params = array(
        "from_mysql_host" => $_POST['from_mysql_host'],
        "from_mysql_login" => $_POST['from_mysql_login'],
        "from_mysql_password" => $_POST['from_mysql_password'],
        "from_db_name" => $_POST['from_db_name'],
        "from_db_prefix" => $_POST['from_db_prefix'],
        "to_mysql_host" => $_POST['to_mysql_host'],
        "to_mysql_login" => $_POST['to_mysql_login'],
        "to_mysql_password" => $_POST['to_mysql_password'],
        "to_db_name" => $_POST['to_db_name'],
        "to_db_prefix" => $_POST['to_db_prefix'],
        "to_site_path" => $_POST['to_site_path'],
    );


    $result = convert($params);
    foreach ($status as $key => $value)
        if ($key == $result) {
            echo $value;
            break;
        }
    exit();
}
?>


<form action="" method="POST">
    <div id="page">
        <div id="header">Конвертор IPB 2.3.6 --> LogicBoard</div>
        <div id="from_container">
            <label class="container-header">IPB</label>

            <div class="option_item">
                <label>MySQL Сервер</label>
                <input type="text" name="from_mysql_host"/>
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
                <input type="text" name="from_db_name"/>
            </div>
            <div class="option_item">
                <label>Префикс таблиц</label>
                <input type="text" name="from_db_prefix"/>
            </div>
        </div>
        <div id="to_container">
            <label class="container-header">LogicBoard</label>

            <div class="option_item">
                <label>MySQL Сервер</label>
                <input type="text" name="to_mysql_host"/>
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
                <input type="text" name="to_db_prefix"/>
            </div>
            <div class="option_item">
                <label>Адрес форума</label>
                <input type="text" name="to_site_path"/>
            </div>
        </div>
        <div id="submit">
            <input type="submit" name="convert_submit" value="Конвертировать"/>
        </div>
    </div>
</form>
</body>
</html>
