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

    protected $dle_users, $dle_emails, $default_moder_permissions;

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
                  $this->lb_sql_host, $this->lb_sql_login, $this->lb_sql_password, $dle_dbname, $dle_prefix, $options);
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


    public function GetMemberId($name)
    {
        $sql = ($this->dleSQL) ? $this->dleSQL : $this->srcSQL;
        $sql->Query("SELECT user_id FROM users WHERE name=%%", $name);
        $result = $sql->Result(0);
        if ($result == 0) {
            $sql->Query("SELECT user_id FROM users WHERE name=%%", strtolower($name));
            $result = $sql->Result(0);
        }
        return $result;
    }

    public function GetMemberName($id)
    {
        $sql = ($this->dleSQL) ? $this->dleSQL : $this->srcSQL;
        $sql->Query("SELECT name FROM users WHERE user_id=%%", $id);
        return $sql->Result(iconv("UTF-8", "Windows-1251", "Удалён"));
    }


    public function OnStart()
    {
        $this->Start("Saved DLE users");

        $sql = ($this->dleSQL) ? $this->dleSQL : $this->srcSQL;

        $sql->Query("SELECT user_id, email, name FROM users");
        $users = $sql->ResultArray();
        foreach ($users as $user)
        {
            $this->dle_users[strtolower($user['name'])] = $user['user_id'];
            $this->dle_emails[strtolower($user['email'])] = $user['user_id'];
        }

        $this->Finish();

        $this->default_moder_permissions = serialize(array(
                                                          "global_changepost" => 1,
                                                          "global_titletopic" => 1,
                                                          "global_delpost" => 1,
                                                          "global_deltopic" => 1,
                                                          "global_opentopic" => 1,
                                                          "global_closetopic" => 1,
                                                          "global_movetopic" => 1,
                                                          "global_movepost" => 1,
                                                          "global_unionpost" => 1,
                                                          "global_changepost" => 1,
                                                          "global_hideshow" => 0,
                                                          "global_polltopic" => 0,
                                                          "global_fixtopic" => 1,
                                                          "global_hidetopic" => 0,
                                                          "global_uniontopic" => 0,
                                                          "global_fixedpost" => 0,
                                                          "global_unfixtopic" => 1
                                                     ));
    }


    public function OnFinish()
    {
        $this->Start("Recount posts, topics, forums values");

        $topic_first = $topic_last = $topic_count = $post_data = array();
        $this->destSQL->Query("SELECT pid, topic_id, post_date, post_member_id, post_member_name,hide,fixed FROM posts");
        $posts = $this->destSQL->ResultArray();
        foreach ($posts as $post)
        {
            $post_data[$post['pid']] = $post;
            if (!isset($topic_first[$post['topic_id']])) {
                $topic_first[$post['topic_id']] = $topic_last[$post['topic_id']] = $post;
                $topic_count[$post['topic_id']] = array("post" => 0, "fixed" => 0, "hiden" => 0);
            }
            else
            {
                if ($post['post_date'] < $topic_first[$post['topic_id']]['post_date'])
                    $topic_first[$post['topic_id']] = $post;
                if ($post['post_date'] > $topic_last[$post['topic_id']]['post_date'])
                    $topic_last[$post['topic_id']] = $post;
            }
            $topic_count[$post['topic_id']]['post']++;

        }
        foreach ($topic_first as $topic_id => $first_post)
        {
            $last_post = $topic_last[$topic_id];
            $count = $topic_count[$topic_id];
            $this->destSQL->Query("UPDATE posts SET new_topic=1 WHERE pid=%%", $first_post['pid']);
            $this->destSQL->Query("UPDATE topics SET post_id=%%,date_open=%%,date_last=%%,post_num=%%,post_hiden=%%,
                post_fixed=%%,last_post_id=%%,last_post_member=%%,member_name_open=%%,member_id_open=%%,
                member_name_last=%% WHERE id=%%", $first_post['id'], $first_post['post_date'], $last_post['post_date'],
                                  $count['post'], $count['hiden'], $count['fixed'], $last_post['pid'], $last_post['post_member_id'],
                                  $first_post['post_member_name'], $first_post['post_member_id'], $last_post['post_member_name'], $topic_id);
        }

        $this->destSQL->Query("SELECT * FROM topics");
        $last_topic = $count_data = array();
        $topics = $this->destSQL->ResultArray();
        foreach ($topics as $topic)
        {
            if (!isset($last_topic[$topic['forum_id']])) {
                $last_topic[$topic['forum_id']] = $topic;
                $count_data[$topic['forum_id']] = array("posts" => 0, "topics" => 0, "posts_hiden" => 0, "topics_hiden" => 0);
            }
            else if ($last_topic[$topic['forum_id']] < $topic['date_last'])
                $last_topic[$topic['forum_id']] = $topic;
            $count_data[$topic['forum_id']]['posts'] += $topic['post_num'];
            $count_data[$topic['forum_id']]['posts_hidden'] += $topic['post_hiden'];
            $count_data[$topic['forum_id']]['topics']++;
            if ($topic['hiden'])
                $count_data[$topic['forum_id']]['topics_hiden']++;
        }

        foreach ($last_topic as $forum_id => $last_topic)
        {
            $last_post = $post_data[$last_topic['last_post_id']];
            $count = $count_data[$forum_id];

            $this->destSQL->Query("UPDATE forums SET last_post_member=%%, last_post_member_id=%%,last_post_date=%%,last_post_id=%%,
                last_title=%%, last_topic_id=%%, posts=%%, topics=%%, posts_hiden=%%, topics_hiden=%% WHERE id=%%",
                                  $last_post['post_member_name'], $last_post['post_member_id'], $last_post['post_date'], $last_post['pid'],
                                  $last_topic['title'], $last_topic['id'], $count['posts'], $count['topics'], $count['posts_hiden'],
                                  $count['topics_hiden'], $forum_id);
        }


        $this->Finish();
    }

}
