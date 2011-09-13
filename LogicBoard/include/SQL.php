<?php

class SQL
{
    private $last_sql_pos;
    private $last_sql_query;

    private $db_prefix;
    private $sql_handle;
    private $db_name;
    private $query_handle;

    private function FixQuery($query)
    {
        $query = trim($query);
        if(substr(strtoupper($query), 0, strlen("INSERT INTO")) == "INSERT INTO")
            $query = "INSERT INTO ".$this->db_prefix.trim(substr($query, strlen("INSERT INTO")));

        if(substr(strtoupper($query), 0, strlen("SELECT")) == "SELECT")
        {
            if(strpos($query, "FROM ") === false) die('"FROM" is missed');

            $sl = substr($query, 0, strpos($query, "FROM ") + strlen("FROM "));
            $sr = trim(substr($query, strlen($sl)));
            $query = $sl.$this->db_prefix.$sr;
        }

        if(substr(strtoupper($query), 0, strlen("UPDATE")) == "UPDATE")
            $query = "UPDATE ".$this->db_prefix.trim(substr($query, strlen("UPDATE")));
        return $query;
    }

    public function ResetLimit()
    {
        $this->last_sql_pos = 0;
        $this->last_sql_query = "";
    }

    public function LimitQuery()
    {
        $args = func_get_args();
        if(count($args) == 0)return;
        $query = & $args[0];
        if ($query != $this->last_sql_query) {
            $this->last_sql_pos = 0;
            $this->last_sql_query = $query;
        }
        $start = $this->last_sql_pos;
        $finish = $start + SQL_ITEMS_IN_QUERY;
        $this->last_sql_pos = $finish;
        $query = $query . " LIMIT " . $start . "," . SQL_ITEMS_IN_QUERY;

        $query = str_replace("%", "%s", $this->FixQuery($args[0]));
        $args[0] = $query;

        foreach($args as $ind=>$arg)
        {
            if(!$ind || is_int($arg))
                continue;
            $args[$ind] = "'".mysql_escape_string($arg)."'";
        }
        $query = call_user_func_array("sprintf", $args);
        $this->query_handle = mysql_query($query, $this->sql_handle) or die("SQL Query Error: ".mysql_error($this->sql_handle));

    }

    public function Query()
    {
        $args = func_get_args();
        if(count($args) == 0)return;

        $query = str_replace("%", "%s", $this->FixQuery($args[0]));
        $args[0] = $query;

        foreach($args as $ind=>$arg)
        {
            if(!$ind || is_int($arg))
                continue;
            $args[$ind] = "'".mysql_escape_string($arg)."'";
        }
        $query = call_user_func_array("sprintf", $args);
        $this->query_handle = mysql_query($query, $this->sql_handle) or die("SQL Query Error: ".mysql_error($this->sql_handle));
    }

    public function Result($default = "")
    {
        $result = @mysql_result($this->query_handle, 0 , 0);
        return $result == FALSE ? $default : $result;
    }

    public function ResultArray()
    {
        $result = array();
        for(;$row = mysql_fetch_assoc($this->query_handle); $result[] = $row);
        return $result;
    }

    public function Connect($host, $login, $password)
    {
        $this->sql_handle = mysql_connect($host, $login, $password, TRUE) or
                            die("Error to SQL connect: ".$login."@".$host.":".$password);
        $this->Query("SET NAMES UTF8");
    }

    public function SelectDb($db_name,$db_prefix)
    {
        $this->db_name = $db_name;
        $this->db_prefix = $db_prefix;
        mysql_select_db($db_name, $this->sql_handle);
    }

    public function InsertedId()
    {
        return mysql_insert_id($this->sql_handle);
    }
}

?>