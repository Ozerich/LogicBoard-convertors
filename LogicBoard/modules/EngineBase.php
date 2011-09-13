<?php

define("SQL_ITEMS_IN_QUERY", 1000);

require_once "include/functions.php";
require_once "include/SQL.php";
require_once "install.php";

abstract class EngineBase
{
    abstract function Convert();

    protected $destSQL, $srcSQL;
    private $lb_sql_host, $lb_sql_login, $lb_sql_password, $lb_db_name, $lb_db_prefix;
    private $startTime;

    public function __construct()
    {
        $this->destSQL = new SQL();
        $this->srcSQL = new SQL();
    }

    public function Setup($option)
    {
        foreach($this->setup as $ind=>$val)
            if($ind == $option)
                return $val;
        return "";
    }

    public function ConnectDestSql($host, $login, $password)
    {
        $this->lb_sql_host = $host;
        $this->lb_sql_login = $login;
        $this->lb_sql_password = $password;
        $this->destSQL->connect($host, $login, $password);
    }

    public function ConnectSrcSql($host, $login, $password)
    {
        $this->srcSQL->connect($host, $login, $password);
    }

    public function SetSrcDb($db_name, $db_prefix)
    {
        $this->srcSQL->SelectDb($db_name, $db_prefix);
    }

    public function SetDestDb($db_name, $db_prefix)
    {
        $this->lb_db_name = $db_name;
        $this->lb_db_prefix = $db_prefix;
        $this->destSQL->SelectDb($db_name, $db_prefix);
    }


    protected function InstallLB()
    {
        InstallLB($this->lb_sql_host, $this->lb_sql_login, $this->lb_sql_password, $this->lb_db_name, $this->lb_db_prefix);
    }

    protected function Start($operation)
    {
        echo date("H:i:s")." - ".$operation."...";flush();
        $this->startTime = time();
    }

    protected function Finish()
    {
        echo "OK"." (".(time() - $this->startTime)." sec)"."<br />\n"; flush();
    }




}
