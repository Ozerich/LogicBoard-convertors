<?php

require_once "EngineBase.php";

class TWSF extends EngineBase
{
    protected $setup = array(
    "caption" => "TWS Forum",
    "id" => "twsf",
    "db_prefix" => "twsf_"
    );

    private function GetMemberId($name)
    {
        $this->srcSQL->Query("SELECT user_id FROM users WHERE name = %", $name);
        return $this->srcSQL->Result(-1);
    }

    private function GetMemberName($id)
    {
        $this->srcSQL->Query("SELECT name FROM users WHERE user_id = %", $id);
        return $this->srcSQL->Result("Удалён");
    }

    private function get_forum_permissions($forum_id, $group_id, $type)
    {
        $this->srcSQL->Query("SELECT $type FROM twsf_forums WHERE forum_id=%", $forum_id);
        $data = $this->srcSQL->Result();

        if ($data == "")
            return 1;
        else
        {
            $items = explode(',', $data);
            for ($i = 0; $i < count($items); ++$i)
                if ($items[$i] == $group_id)
                    return 1;
            return 0;
        }
    }

    public function convert()
    {
        $users_id = $user_topics = $files_id = array();

        $this->Start("Install");
        $this->InstallLB();
        $this->Finish();

        $this->Start("Groups");

        $groups_count = 0;
        $this->srcSQL->Query("SELECT * FROM usergroups ORDER by id ASC");
        $result = $this->srcSQL->ResultArray();
        for ($i = 0, $id = 1; $i < 5; $i++, $groups_count++)
            $this->destSQL->Query("INSERT INTO groups SET g_id=%, g_title=%", $id++, $result[$i]['group_name']);
        $this->destSQL->Query("INSERT INTO groups SET g_id=6, g_title='Неактивные'");
        for ($i = 5, $id = 7, $groups_count = 6; $i < count($result); $i++, $groups_count++)
            $this->destSQL->Query("INSERT INTO groups SET g_id=%, g_title=%", $id++, $result[$i]['group_name']);
        $this->destSQL->Query("UPDATE groups SET g_access_cc=1,g_supermoders=1 WHERE g_id=1");

        $this->Finish();
        $this->Start("Users");

        $this->srcSQL->Query("SELECT * FROM users");
        $users = $this->srcSQL->ResultArray();
        foreach ($users as $item)
        {
            $member_sk = md5(md5($item['password'] . time() . $item['logged_ip']));
            $member_group = $item['user_group'] < 6 ? $item['user_group'] : $item['user_group'] + 1;
            $banned = $item['banned'] == "yes" ? 1 : 0;

            $this->destSQL->Query("INSERT INTO members SET name=%,password=%,secret_key=%,email=%,member_group=%,
		lastdate=%,reg_date=%,ip=%,personal_title='',reg_status=1,avatar='',fullname=%,town=%,about=%,
		signature=%,icq=%,banned=%,posts_num=%,reputation=%", $item['name'], $item['password'],
                                  $member_sk, $item['email'], $member_group, $item['lastdate'], $item['reg_date'],
                                  $item['logged_ip'], $item['fullname'], $item['land'], $item['info'],
                                  $item['signature'], $item['icq'], $banned, $item['twsf_posts'], $item['twsf_rank']);
            $users_id[$item['user_id']] = $this->destSQL->InsertedId();
        }

        $this->Finish();
        $this->Start("Categories");

        $this->srcSQL->Query("SELECT * FROM twsf_categories");
        $categories = $this->srcSQL->ResultArray();
        foreach ($categories as $category)
        {
            $this->destSQL->Query("INSERT INTO forums SET id=%,parent_id=0,posi=%,title=%,alt_name=%,
            group_permission=0", $category['cat_id'], $category['cat_order'], ($category['cat_title']),
                                  translit($category['cat_title']));
        }

        $this->Finish();
        $this->Start("Forums");
        while (true)
        {
            $this->srcSQL->LimitQuery("SELECT * FROM twsf_forums");
            $forums = $this->srcSql->ResultArray();
            if (!$forums) break;
            foreach ($forums as $forum)
            {
                $permissions = array();
                for ($i = 1; $i <= $groups_count; ++$i)
                {
                    $group_id = ($i <= 5) ? $i : ($i + 1);
                    $permissions[$i]['read_forum'] = $this->get_forum_permissions($forum['forum_id'], $group_id, 'auth_view');
                    $permissions[$i]['read_theme'] = $this->get_forum_permissions($forum['forum_id'], $group_id, 'auth_read');
                    $permissions[$i]['creat_theme'] = $this->get_forum_permissions($forum['forum_id'], $group_id, 'auth_post');
                    $permissions[$i]['answer_theme'] = $this->get_forum_permissions($forum['forum_id'], $group_id, 'auth_reply');
                    $permissions[$i]['upload_files'] = $this->get_forum_permissions($forum['forum_id'], $group_id, 'auth_sendfile');
                    $permissions[$i]['download_files'] = $this->get_forum_permissions($forum['forum_id'], $group_id, 'auth_getfile');
                }
                $permissions[6] = $permissions[5];
                if ($forum['forum_status'] == 'hidden') {
                    foreach ($permissions as $p_ind => $permissions_item)
                        foreach ($permissions_item as $key => $val)
                            $permissions[$p_ind][$key] = 0;
                }
                else if ($forum['forum_status'] == 'lock') {

                    foreach ($permissions as $p_ind => $permissions_item)
                        foreach ($permissions_item as $key => $val)
                            if ($key != 'read_forum')
                                $permissions[$p_ind][$key] = 0;
                }
                $permissions = serialize($permissions);

                $this->destSQL->Query("INSERT INTO forums SET
            id = '".$forum['forum_id']."',
            title = '" . ($forum['forum_name']) . "',
            alt_name = '" . (translit($forum['forum_name'])) . "',
            description = '" . ($forum['forum_desc']) . "',
            posi = '" . $forum['forum_order'] . "',
            topics = '" . $forum['forum_topics'] . "',
            posts = '" . $forum['forum_posts'] . "',
            meta_desc = '" . ($forum['descr']) . "',
            meta_key = '" . ($forum['keywords']) . "',
            allow_bbcode = '1',
            allow_poll = '1',
            sort_order='DESC',
            postcount = '1',
            group_permission = '$permissions'
            ");
            }
        }
        $this->srcSQL->ResetLimit();
        while (true)
        {
            $this->srcSQL->LimitQuery("SELECT * FROM twsf_forums");
            $forums = $this->srcSQL->ResultArray();
            if (!$forums) break;
            foreach ($forums as $forum)
            {
                $parent_id = $forum['p_forum_id'] == 0 ? $forum['cat_id'] : $forum['p_forum_id'];
                $this->destSQL->Query("UPDATE forums SET parent_id=% WHERE id=%", $parent_id, $forum['forum_id']);
            }
        }

        $this->Finish();
        $this->Start("Topics");
        while (true)
        {
            $this->srcSQL->LimitQuery("SELECT * FROM twsf_topics");
            $topics = $this->srcSQL->ResultArray();
            if (!$topics) break;
            foreach ($topics as $topic)
            {
                $fixed = ($topic['topic_type'] == 1 && $topic['topic_lock'] == 0) ? 1 : 0;
                $member_id_open = $users_id[$this->GetMemberId($topic['topic_poster'])];
                $status = ($topic['topic_lock'] == 1) ? "closed" : "open";

                $this->destSQL->Query("INSERT INTO topics SET id=%,forum_id = %,title = %,description=%,member_name_open=%,
                                    member_id_open = %,views = %,status =%,fixed = %,hiden = 0, post_num = %",
                                    $topic['topic_id'],$topic['forum_id'],$topic['topic_title'],$topic['topic_subject'],
                                    $topic['topic_poster'],$member_id_open,$topic['topic_views'],$status,$fixed,$topic['topic_replies']);
                $user_topics[$member_id_open] = isset($user_topics[$member_id_open]) ? $user_topics[$member_id_open] + 1 : 1;
            }
        }
        foreach ($user_topics as $user_id => $count)
            $this->destSQL->Query("UPDATE members SET topics_num=% WHERE member_id=%", $count, $user_id);

        $this->Finish();
        $this->Start("Posts");

        while (true)
        {
            $this->srcSQL->LimitQuery("SELECT * FROM twsf_posts");
            $posts = $this->srcSQL->ResultArray();
            if (!$posts) break;
            foreach ($posts as $post)
            {
                $member_id = $users_id[get_member_id($post['poster'])];
                $this->srcSQL->Query("SELECT text FROM twsf_posts_text WHERE t_post_id=%");
                $post_text = (dle_to_lb($this->srcSQL->Result()));

                $this->destSQL->Query("INSERT INTO posts SET pid=%,topic_id = %, post_date = %,post_member_name = %,post_member_id = %,
                    text = %,ip = %, hide = 0,fixed = 0",$post['post_id'], $post['topic_id'],timetoint($post['post_time']),
                                      $post['poster'],$member_id,$post_text,$post['ip']);

            }
        }


        $this->destSQL->LimitQuery("SELECT * FROM posts");
        $posts = $this->destSQL->ResultArray();
        $post_data = array();
        foreach ($posts as $post)
        {
            $topic_id = $post['topic_id'];
            $post_id = $post['pid'];
            $post_date = $post['post_date'];
            if (!isset($post_data[$topic_id]) || $post_data[$topic_id]['min_date'] > $post_date) {
                $post_data[$topic_id]['min_date'] = $post_date;
                $post_data[$topic_id]['post_id'] = $post_id;
            }
        }

        foreach ($post_data as $item)
            $this->destSQL->Query("UPDATE posts SET new_topic=1 WHERE pid=%", $item['post_id']);

        $this->destSQL->Query("SELECT * FROM topics");
        $topics = $this->destSQL->ResultArray();
        foreach ($topics as $topic)
        {
            $topic_id = $topic['id'];
            $this->destSQL->Query("SELECT * FROM posts WHERE new_topic='1' AND topic_id='$topic_id'");
            $result = $this->destSQL->ResultArray();
            $first_post_date = $result[0]['post_date'];
            $first_post_id = $result[0]['pid'];
            $this->destSQL->Query("UPDATE topics SET date_open=%, post_id=% WHERE id=%", $first_post_date,$first_post_id,$topic_id);

            $this->destSQL->Query("SELECT * FROM posts WHERE topic_id=%",$topic['id']);
            $posts = $this->destSQL->ResultArray();
            $last_date = 0;
            $last_post = null;
            foreach ($posts as $post)
                if ($post['post_date'] > $last_date) {
                    $last_date = $post['post_date'];
                    $last_post = $post;
                }

            if ($last_post != null) {
                $this->destSQL->Query("UPDATE topics SET last_post_id=%,last_post_member=%,date_last=%,member_name_last=% WHERE id=%",
                                      $last_post['pid'],$last_post['post_member_id'],$last_post['post_date'],
                                      $last_post['post_member_name'], $topic_id);
            }
        }

        $this->destSQL->Query("SELECT * FROM forums WHERE parent_id > 0");
        $forums = $this->destSQL->ResultArray();
        foreach ($forums as $forum)
        {
            $forum_id = $forum['id'];
            $this->destSQL->Query("SELECT * FROM topics WHERE forum_id=%", $forum_id);
            $topics = $this->destSQL->ResultArray();
            $last_date = 0;
            $last_topic = null;
            foreach ($topics as $topic)
                if ($topic['date_last'] > $last_date) {
                    $last_date = $topic['date_last'];
                    $last_topic = $topic;
                }
            if ($last_topic != null) {
                $this->destSQL->Query("UPDATE forums SET last_post_member = %,last_post_member_id = %,last_post_date = %,
                            last_title = %,last_topic_id =%,last_post_id = % WHERE id=%",$last_topic['member_name_last'],
                            $last_topic['last_post_member'],$last_topic['date_last'],$last_topic['title'], $last_topic['id'],
                            $last_topic['last_post_id'],$forum_id);
            }
        }

        $this->Finish();
        $this->Start("Polls");

        $this->srcSQL->Query("SELECT * FROM twsf_poll");
        $polls = $this->srcSQL->ResultArray();
        foreach ($polls as $poll)
        {
            $topic_id = $poll['top_id'];
            $this->destSQL->Query("SELECT date_open FROM topics WHERE id=%", $topic_id);
            $open_date = $this->destSQL->Result();
            $variants = str_replace("<br />", "\r\n", $poll['body']);
            $this->destSQL->Query("INSERT INTO topics_poll SET tid = %,vote_num = %,title = %,question = %,variants = %,
                multiple = %,open_date = %,answers =%",$topic_id, $poll['votes'], $poll['title'], $variants, $poll['multiple'],
                                  $open_date, $poll['answer']);
            $poll_id = $this->destSQL->InsertedId();
            $this->destSQL->Query("UPDATE topics SET poll_id=% WHERE id=%",$poll_id, $topic_id);
            $polls_id[$poll['id']] = $poll_id;
        }

        $this->Finish();
        $this->Start("Poll logs");

        $this->srcSQL->Query("SELECT * FROM twsf_poll_log");
        $logs = $this->srcSQL->ResultArray();
        foreach ($logs as $log)
        {
            $topic_id = $log['top_id'];
            $this->destSQL->Query("SELECT poll_id FROM topics WHERE id=%", $topic_id);
            $poll_id = $this->destSQL->Result();

            $member_id = $users_id[$log['member']];
            $this->destSQL->Query("SELECT * FROM members WHERE member_id=%", $member_id);
            $member = $this->destSQL->Result();
            $member = $member[0];

            $this->destSQL->Query("INSERT INTO topics_poll_logs SET poll_id = %,ip =%,member_id = %,log_date = %,member_name = %,
                            answer=0", $poll_id, $member['ip'], $member_id, timetoint($log['date']), $member['name']);
        }

        $this->Finish();
        $this->Start("Ranks");

        $this->srcSQL->Query("SELECT * FROM twsf_ranks");
        $ranks = $this->srcSql->ResultArray();
        foreach ($ranks as $rank)
        {
            if ($rank['group_id'] > 0) continue;
            $this->destSQL->Query("INSERT INTO members_ranks SET title=%,post_num=%,stars=0",$rank['rank_title'],$rank['rank_min']);
        }

        $this->Finish();
        $this->Start("Reputation logs");

        $this->srcSQL->Query("SELECT * FROM twsf_rate_logs");
        $logs = $this->srcSQL->ResultArray();
        foreach ($logs as $log)
        {
            $from_name = $log['member'];
            $from_id = get_member_id($from_name);
            $post_id = $log['post_id'];
            $this->destSQL->Query("SELECT * FROM posts WHERE pid=%",$post_id);
            $result = $this->destSQL->ResultArray();
            $to_id = $result[0]['post_member_id'];
            $to_name = $result[0]['post_member_name'];
            $how = $log['direct'] == 1 ? '-1' : '+1';
            $this->destSQL->Query("INSERT INTO members_reputation SET from_id=%,from_name=%,to_id=%,to_name=%, date=%,how=%,
                        text=%", $from_id, $from_name, $to_id, $to_name, timetoint($log['date']), $how);
        }

        $this->Finish();
        $this->Start("Subscribe");

        $this->srcSQL->Query("SELECT * FROM twsf_topics_watch");
        $watchs = $this->srcSQL->ResultArray();

        foreach ($watchs as $watch)
        {
            $this->destSQL->Query("INSERT INTO topics_subscribe SET subs_member = %,topic = %",$watch['user_id'],$watch['topic_id']);
            $this->destSQL->Query("SELECT subscribe FROM members WHERE member_id=%",$user_id);
            $s_text = $this->destSQL->Result();
            $s_text = ($s_text == "") ? $watch['topic_id'] : $s_text . "," . $watch['topic_id'];
            $this->destSQL->Query("UPDATE members SET subscribe=% WHERE member_id=%", $s_text, $user_id);
        }

        $this->Finish();
        $this->Start("Moderators");

        $this->srcSQL->Query("SELECT * FROM twsf_config");
        $config = $this->srcSQL->ResultArray();

        $permissions['global_hidetopic'] = $permissions['global_titletopic'] = $permissions['global_polltopic'] =
        $permissions['global_opentopic'] = $permissions['global_closetopic'] = $permissions['global_fixtopic'] =
        $permissions['global_unfixtopic'] = $permissions['global_movetopic'] = $permissions['global_uniontopic'] =
        $permissions['global_changepost'] = $permissions['global_movepost'] = $permissions['global_unionpost'] = $config['m_edit'];
        $permissions['global_deltopic'] = $permissions['global_delpost'] = $config['m_delete'];
        $permissions = serialize($permissions);

        $this->srcSQL->Query("SELECT * FROM twsf_forums");
        $forums = $this->srcSQL->ResultArray();

        foreach ($forums as $forum)
        {
            $forum_moders = explode(',', $forum['moderators']);
            if (!$forum_moders) continue;
            $forum_id = $forum['forum_id'];

            foreach ($forum_moders as $moder)
            {
                $moder_id = $users_id[$moder];
                $this->destSQL->Query("SELECT name FROM members WHERE member_id='$moder_id'");
                $moder_name = $this->destSQL->Result();

                $this->destSQL->Query("INSERT INTO forums_moderator SET fm_forum_id=%,fm_member_id=%,fm_member_name=%,
                            fm_group_id=0,fm_is_group=0,fm_permission=%", $forum_id, $moder_id, $moder_name, $permissions);
            }
        }

        $this->Finish();
        $this->Start("Ban filters");

        $this->srcSQL->Query("SELECT * FROM banned  ORDER by id ASC");
        $result = $this->srcSQL->ResultArray();
        $begindate = time();
        foreach ($result as $item)
        {
            $ban_member_id = "";
            if ($item['users_id'] != 0) {
                $user_id = $users_id[$item['users_id']];
                $descr = $this->GetMemberName($item['users_id']);
                $type = "name";
                $ban_member_id = $user_id;

                $this->destSQL->Query("INSERT INTO members_banfilters SET type=%,description=%,date=%,moder_desc=%,date_end=%,
                                ban_days=%,ban_member_id=%", $type, $descr, $begindate, $item['descr'], $item['date'],
                                      $item['days'], $ban_member_id);
            }
            if ($item['ip'] != "") {
                $ip = $item['ip'];
                if (preg_match('#(\d+)\.(\d+)\.(\d+)\.(\d+)#', $ip))
                    $type = "ip";
                else if (strpos($ip, '@') !== false)
                    $type = "email";
                else
                    $type = "name";
                $descr = $ip;

                $this->destSQL->Query("INSERT INTO members_banfilters SET type=%,description = %,date=%,moder_desc=%,
                        date_end=%,ban_days=%,ban_member_id=%",$type, $descr, $begindate, $item['descr'],
                                      $item['date'], $item['days'], $ban_member_id);
            }
        }


        $this->Finish();
        $this->Start("Files");

        $this->srcSQL->Query("SELECT * FROM twsf_files ORDER by id ASC");
        $result = $this->srcSQL->ResultArray();
        foreach ($result as $file)
        {
            $author_name = $file['author'];
            $author_id = $this->GetMemberId($author_name);
            $post_id = $file['post_id'];

            $this->destSQL->Query("SELECT topic_id FROM posts WHERE pid = '$post_id'");
            $topic_id = $this->destSQL->Result();

            $this->destSQL->Query("SELECT forum_id FROM topics WHERE id='$topic_id'");
            $forum_id = $this->destSQL->Result();;

            $type = is_image($file['onserver']) ? "picture" : "file";

            $this->destSQL->Query("INSERT INTO topics_files SET file_title=%,file_name=%, file_type=%,file_mname=%,
                                file_mid=%,file_date=%,file_size=0,file_count=%,file_tid=%,file_fid=%,file_convert=1,
                                file_pid=%", $file['name'], $file['onserver'], $type, $author_name, $author_id, timetoint($file['date']),
                                $file['dcount'], $topic_id, $forum_id, $post_id);

            $files_id[$file['id']] = $this->destSQL->InsertedId();
        }

        $this->Finish();
        $this->Start("Attachments");
        while (true)
        {
            $this->destSQL->LimitQuery("SELECT * FROM posts");
            $result = $this->destSQL->ResultArray();
            if (!$result) break;
            foreach ($result as $post)
            {
                $text = ($post['text']);
                preg_match('#forum_attachment=(\d+)#sui', $text, $item);
                if ($item) {

                    $file_id = $files_id[$item[1]];
                    $text = str_replace("[forum_attachment=" . $item[1] . "]", "[attachment=$file_id]", $text);
                    $this->destSQL->Query("UPDATE posts SET text=% WHERE pid=%", $text, $post['pid']);
                }
                $post_id = $post['pid'];
                $this->destSQL->Query("SELECT * FROM topics_files WHERE file_pid=%",$post_id);
                $files = $this->destSQL->ResultArray();
                $text = '';
                foreach ($files as $file)
                    $text .= $file['file_id'] . ",";
                if (strlen($text) > 0)
                    $text = substr($text, 0, -1);
                $this->destSQL->Query("UPDATE posts SET attachments=% WHERE pid=%", $text, $post['pid']);
            }
        }

        $this->Finish();
        $this->Start("Warnings");

        $this->srcSQL->Query("SELECT * FROM twsf_uwarnings");
        $warnings = $this->srcSQL->ResultArray();
        foreach ($warnings as $warning)
        {
            $user_id = $users_id[$warning['user_id']];
            $moder_id = $users_id[$warning['w_user_id']];
            $moder_name = $this->GetMemberName($warning['w_user_id']);
            $date = timetoint($warning['time']);
            $cause = ($warning['text']);

            $this->destSQL->Query("INSERT INTO members_warning SET mid=%,moder_id=%,moder_name=%,date=%,description=%,
                        st_w=1", $user_id, $moder_id, $moder_name, $date, $cause);
        }

        $this->destSQL->Query("SELECT * FROM members");
        $users = $this->destSQL->ResultArray();
        foreach ($users as $user)
        {
            $user_id = $user['member_id'];
            $this->destSQL->Query("SELECT * FROM members_warning WHERE mid=% AND st_w=1",$user_id);
            $result = $this->destSQL->ResultArray();
            $count = count($result);
            $this->destSQL->Query("UPDATE members SET count_warning=% WHERE member_id=%", $count, $user_id);
        }
        $this->Finish();
    }
}

?>