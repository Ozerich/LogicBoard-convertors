<?php

define("SQL_ITEMS_IN_QUERY", 1000);

require_once "include/functions.php";
require_once "include/SQL.php";
require_once "install.php";

abstract class EngineBase
{
    abstract function Convert($options);

    protected $destSQL, $srcSQL, $dleSQL;
    private $lb_sql_host, $lb_sql_login, $lb_sql_password, $lb_db_name, $lb_db_prefix;
    private $dle_db_prefix;
    private $src_sql_host, $src_sql_login, $src_sql_password, $src_db_name, $src_db_prefix;
    private $startTime;

    public function __construct()
    {
        $this->srcSQL = new SQL();
        $this->destSQL = new SQL();
        if (!$this->Setup("dle_based"))
            $this->dleSQL = new SQL();
    }

    public function Setup($option)
    {
        foreach ($this->setup as $ind => $val)
            if ($ind == $option)
                return $val;
        return "";
    }


    public function ConnectDestSql($host, $login, $password)
    {
        $this->lb_sql_host = $host;
        $this->lb_sql_login = $login;
        $this->lb_sql_password = $password;
        $this->destSQL->connect($host, $login, $password);
        if (!$this->Setup("dle_based"))
            $this->dleSQL->connect($host, $login, $password);
    }

    public function ConnectSrcSql($host, $login, $password)
    {
        $this->src_sql_host = $host;
        $this->src_sql_login = $login;
        $this->src_sql_password = $password;
        $this->srcSQL->connect($host, $login, $password);
    }

    public function SetSrcDb($db_name, $db_prefix)
    {
        $this->src_db_name = $db_name;
        $this->src_db_prefix = $db_prefix;
        $this->srcSQL->SelectDb($db_name, $db_prefix);
    }

    public function SetDestDb($db_name, $db_prefix)
    {
        $this->lb_db_name = $db_name;
        $this->lb_db_prefix = $db_prefix;
        $this->destSQL->SelectDb($db_name, $db_prefix);
    }

    public function SetDleDb($db_prefix)
    {
        if ($this->Setup("dle_based")) return;
        $this->dle_db_prefix = $db_prefix;
        $this->dleSQL->SelectDb($this->destSQL->db_name, $db_prefix);
    }


    protected function InstallLB($options)
    {
        $dle_prefix = $this->Setup("dle_based") ? $this->destSQL->db_prefix : $this->dle_db_prefix;
        $dle_dbname = $this->destSQL->db_name;
        InstallLB($this->lb_sql_host, $this->lb_sql_login, $this->lb_sql_password, $this->lb_db_name, $this->lb_db_prefix,
                  $this->lb_sql_host, $this->lb_sql_login, $this->lb_sql_password,$dle_dbname,$dle_prefix, $options);
    }

    protected function Start($operation)
    {
        echo date("H:i:s") . " - " . $operation . "...";
        flush();
        $this->startTime = time();
    }

    protected function Finish()
    {
        echo "OK" . " (" . (time() - $this->startTime) . " sec)" . "<br />\n";
        flush();
    }


    public function OnFinish()
    {
        $this->Start("Recount fixed posts");
        $fixed = array();
        $this->destSQL->Query("SELECT * FROM posts WHERE fixed=1");
        $posts = $this->destSQL->ResultArray();
        foreach($posts as $post)
            $fixed[$post['topic_id']] = isset($fixed[$post['topic_id']]) ? $fixed[$post['topic_id']] + 1 : 1;
        foreach($fixed as $topic_id => $val)
            $this->destSQL->Query("UPDATE topics SET post_fixed=%% WHERE id=%%", $val, $topic_id);
        $this->Finish();
    }

}
