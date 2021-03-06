<?php

require_once "EngineBase.php";
require_once "include/dle_parser.php";


class DLE_2_5 extends EngineBase
{
    protected $setup = array(
    "caption" => "DLE FORUM ver. 2.5",
    "id" => "dle_2_5",
    "db_prefix" => "dle_",
    );

    private function GetMemberId($name)
    {
        $this->destSQL->Query("SELECT member_id FROM members WHERE name=%", $name);
        return $this->destSQL->Result(0);
    }

    private function GetMemberName($id)
    {
        $this->destSQL->Query("SELECT name FROM members WHERE member_id=%", $id);
        return $this->destSQL->Result("Удалён");
    }

    public function Convert()
    {
        $this->Start("Install");
        $this->InstallLB();
        $this->Finish();

        $this->Start("Users");

        $users_id = array("-1" => "-1");
        $users_id[-1] = -1;
        $this->srcSQL->Query("SELECT * FROM users ORDER by reg_date ASC");
        $result = $this->srcSQL->ResultArray();

        foreach ($result as $item)
        {
            $member_sk = md5(md5($item['password'] . time() . $item['logged_ip']));
            $member_group = $item['user_group'] < 6 ? $item['user_group'] : $item['user_group'] + 1;
            $banned = $item['banned'] == "yes" ? 1 : 0;

            $this->srcSQL->Query("SELECT COUNT(*) FROM forum_topics WHERE author_topic=%", $item['name']);
            $topics_num = $this->srcSQL->Result();

            $this->destSQL->Query("INSERT INTO members SET name=%,password=%,secret_key=%, email=%,member_group=%,
                lastdate=%,reg_date=%, ip=%, personal_title='', reg_status='1', fullname=%, town=%, about=%,
                signature=%, icq=%,banned=%, posts_num=%,reputation=%,topics_num=%",
                                  $item['name'], $item['password'], $member_sk, $item['email'], $member_group, $item['lastdate'],
                                  $item['reg_date'], $item['logged_ip'], $item['fullname'], $item['land'], $item['info'], $item['signature'],
                                  $item['icq'], $banned, $item['forum_post'], $item['forum_reputation'], $topics_num);

            $users_id[$item['user_id']] = $this->destSQL->InsertedID();
        }
        $this->Finish();

        $this->Start("Categories");

        $this->srcSQL->Query("SELECT * FROM forum_category ORDER by sid ASC");
        $result = $this->srcSQL->ResultArray();
        foreach ($result as $item)
        {
            $postcount = isset($item['postcount']) ? $item['postcount'] : 1;
            $this->destSQL->Query("INSERT INTO forums SET parent_id=0,title=%,alt_name=%,postcount=%,posi=%",
                                  $item['cat_name'], translit($item['cat_name']), $postcount, $item['posu']);;
            $categories_id[$item['sid']] = $this->destSQL->InsertedId();
        }

        $this->Finish();

        $this->Start("Groups");

        $this->srcSQL->Query("SELECT * FROM usergroups ORDER by id ASC");
        $result = $this->srcSQL->ResultArray();

        $groups_count = 0;
        for ($i = 0, $id = 1; $i < 5; $i++, $groups_count++)
            $this->destSQL->Query("INSERT INTO groups SET g_id=%, g_title=%", $id++, $result[$i]['group_name']);
        $this->destSQL->Query("INSERT INTO groups SET g_id=6, g_title=%", "Неактивные");
        for ($i = 5, $id = 7, $groups_count = 6; $i < count($result); $i++, $groups_count++)
            $this->destSQL->Query("INSERT INTO groups SET g_id=%, g_title=%", $id++, $result[$i]['group_name']);
        $this->destSQL->Query("UPDATE groups SET g_access_cc=1,g_supermoders=1 WHERE g_id=1");

        $this->Finish();


        $this->Start("Forums");
        while (true)
        {
            $this->srcSQL->LimitQuery("SELECT * FROM forum_forums ORDER by id ASC");

            $result = $this->srcSQL->ResultArray();
            if (!$result) break;
            foreach ($result as $item)
            {
                $access_write = explode(":", $item['access_write']);
                $access_read = explode(":", $item['access_read']);
                $access_topic = explode(":", $item['access_topic']);
                $access_upload = explode(":", $item['access_upload']);
                $access_download = explode(":", $item['access_download']);

                $permissions = array();

                for ($i = 1; $i <= $groups_count; $i++)
                    $permissions[$i] = array("read_forum" => 0, "read_theme" => 0, "creat_theme" => 0, "answer_theme" => 0, "upload_files" => 0, "download_files" => 0);

                for ($j = 0; $j < count($access_read); $j++)
                {
                    $index = ($access_read[$j] > 5) ? $access_read[$j] + 1 : $access_read[$j];
                    $permissions[$index]['read_forum'] = 1;
                    $permissions[$index]['read_theme'] = 1;
                }
                for ($j = 0; $j < count($access_write); $j++)
                {
                    $index = ($access_write[$j] > 5) ? $access_write[$j] + 1 : $access_write[$j];
                    $permissions[$index]['answer_theme'] = 1;
                }
                for ($j = 0; $j < count($access_topic); $j++)
                {
                    $index = ($access_topic[$j] > 5) ? $access_topic[$j] + 1 : $access_topic[$j];
                    $permissions[$index]['creat_theme'] = 1;
                }
                for ($j = 0; $j < count($access_upload); $j++)
                {
                    $index = ($access_upload[$j] > 5) ? $access_upload[$j] + 1 : $access_upload[$j];
                    $permissions[$index]['upload_files'] = 1;
                }
                for ($j = 0; $j < count($access_download); $j++)
                {
                    $index = ($access_download[$j] > 5) ? $access_download[$j] + 1 : $access_download[$j];
                    $permissions[$index]['download_files'] = 1;
                }
                $permissions[6] = $permissions[5];

                $permissions = serialize($permissions);
                $postcount = (isset($item['postcount'])) ? $item['postcount'] : 1;

                $last_user = $this->GetMemberId($item['f_last_poster_name']);
                $last_post_date = datetime_to_int($item['f_last_date']);

                $this->destSQL->Query("INSERT INTO forums SET
            id=%,posi=%,title=%,alt_name=%,description=%,last_post_member=%, last_post_member_id=%,
            last_post_date=%,allow_bbcode=1, allow_poll=1,postcount=%, group_permission=%,password=%,
            sort_order='DESC',posts=%,topics=%,rules=%,meta_desc='',meta_key=''",
                            $item['id'], $item['position'], $item['name'], translit($item['name']), $item['description'],
                            $item['fget_member_id_last_poster_name'],$last_user, $last_post_date, $postcount, $permissions,
                            $item['password'], $item['posts'],$item['topics'], $item['rules']);
            }
        }

        $this->srcSQL->ResetLimit();

        while (true)
        {
            $this->srcSQL->LimitQuery("SELECT * FROM forum_forums ORDER by id ASC");
            $result = $this->srcSQL->ResultArray();
            if (!$result) break;
            foreach ($result as $item)
            {
                $parent_id = $item['parentid'] != 0 ? $item['parentid'] : $item['main_id'];
                $forum_id = $item['id'];
                $this->destSQL->Query("UPDATE forums SET parent_id=% WHERE id=%", $parent_id, $forum_id);
            }
        }
        $this->Finish();

        $this->Start("Topics");
        $min_data = $max_data = array();
        while (true)
        {
            $this->srcSQL->LimitQuery("SELECT * FROM forum_topics ORDER by tid ASC");
            $result = $this->srcSQL->ResultArray();
            if (!$result) break;
            $max_data = array();
            foreach ($result as $item)
            {
                $forum_id = $item['forum_id'];
                $date_last = datetime_to_int($item['last_date']);
                $hidden = ($item['hidden'] >= 1) ? 1 : 0;
                $status = $item['topic_status'] == 0 ? "open" : "closed";
                $fixed = $item['fixed'] == 0 ? 1 : 0;
                $date_open = datetime_to_int($item['start_date']);

                $this->destSQL->Query("INSERT INTO topics SET tid=%,forum_id=%,title=%,description=%,date_open=%,date_last=%,
                status=%,views=%,post_num=%,post_hiden='0',fixed=%,hiden=%, poll_id='ERROR',postfixed='0'",
                            $item['tid'],$forum_id, $item['title'], $item['topic_descr'], $date_open, $date_last, $status,
                            $item['views'],$item['post'], $fixed, $hidden);

                if ($item['poll_title'] != "") {
                    $topic_id = $item['tid'];
                    $variants = str_replace("<br />", "\r\n", $item['poll_body']);
                    $open_date = datetime_to_int($item['start_date']);
                    $this->destSQL->Query("INSERT INTO topics_poll SET
                        tid=%,vote_num=%,title=%,question=%, variants=%,answers=%,multiple=%,open_date=%",
                        $topic_id, $item['poll_count'], $item['poll_title'], $item['frage'], $variants, $item['answer'],
                        $item['mulpiple'], $open_date);
                    $this->destSQL->Query("UPDATE topics SET poll_id=% WHERE id=%", $this->destSQL->InsertedId(),
                                          $topic_id);
                }
                if ((!isset($max_data[$forum_id])) || ($max_data[$forum_id]["time"] < $date_last)) {
                    $max_data[$forum_id]["time"] = $date_last;
                    $max_data[$forum_id]["tid"] = $item['tid'];
                }
                $max_data[$forum_id]['hiden'] += $hidden;
            }
        }


        foreach ($max_data as $fid => $value)
        {
            $last_topic_id = $value['tid'];
            $topics_hidden = $value['hiden'];
            $this->destSQL->Query("SELECT title FROM topics WHERE id='$last_topic_id'");
            $last_title = $this->destSQL->Result();
            $this->destSQL->Query("UPDATE forums SET last_topic_id=%,topics_hiden=%,last_title=% WHERE id=%",
                                  $last_topic_id, $topics_hidden, $last_title, $fid);
        }

        $this->Finish();

        $this->Start("Posts");

        $min_data = $max_data = array();
        while (true)
        {
            $this->srcSQL->LimitQuery("SELECT * FROM forum_posts  ORDER by pid ASC");
            $result = $this->srcSQL->ResultArray();
            if (!$result) break;

            foreach ($result as $item)
            {
                $topic_id = $item['topic_id'];
                $post_date = datetime_to_int($item['post_date']);
                $post_member_id = $this->GetMemberId($item['post_author']);
                $edit_member_id = $this->GetMemberId($item['edit_user']);
                $edit_member_id = ($edit_member_id == -1) ? "" : $edit_member_id;
                $edit_member_name = ($item['edit_user'] == "0") ? "" : $item['edit_user'];
                $hiden = ($item['hidden'] == 1) ? 1 : 0;

                $text = dle_to_lb($item['post_text']);

                $this->destSQL->Query("INSERT INTO posts SET
            pid=%,topic_id=%,new_topic='0',text=%,post_date=%,edit_date=%,post_member_id=%,post_member_name=%,
            ip=%,hide=%,edit_member_id=%,edit_member_name=%,edit_reason='',fixed='0'",
                            $item['pid'],$topic_id, $text, $post_date, $item['edit_time'], $post_member_id, $item['post_author'],
                $item['post_ip'], $hiden, $edit_member_id, $edit_member_name);


                if ((!isset($min_data[$topic_id])) || ($min_data[$topic_id]["time"] > $post_date)) {
                    $min_data[$topic_id]["time"] = $post_date;
                    $min_data[$topic_id]["pid"] = $item['pid'];
                }
                if ((!isset($max_data[$topic_id])) || ($max_data[$topic_id]["time"] < $post_date))
                    $max_data[$topic_id] = array("time" => $post_date, "pid" => $item['pid']);
                $min_data[$topic_id]['hiden'] += $hiden;
            }
        }
        foreach ($min_data as $tid => $item)
        {
            $this->destSQL->Query("UPDATE posts SET new_topic='1' WHERE pid=%", $item['pid']);
            $this->destSQL->Query("UPDATE topics SET post_hiden=% WHERE id=%", $item['hiden'], $tid);
        }

        while (true)
        {
            $this->destSQL->LimitQuery("SELECT * FROM posts");
            $result = $this->destSQL->ResultArray();
            if (!$result) break;
            foreach ($result as $item)
                if ($item['hide'] == 0) {
                    $topic_id = $item['topic_id'];
                    $this->destSQL->Query("SELECT hiden FROM topics WHERE id=%", $topic_id);
                    $hiden_topic = $this->destSQL->Result();
                    if ($hiden_topic && $item['new_topic'] == 1)
                        $this->destSQL->Query("UPDATE posts SET hide='1' WHERE pid=%",$item['pid']);
                }
        }

        foreach ($max_data as $tid => $item)
        {
            $last_post_id = $item['pid'];

            $this->destSQL->Query("SELECT post_member_id FROM posts WHERE pid=%", $last_post_id);

            $last_post_member_id =  $this->destSQL->Result();
            $member_name_last = $this->GetMemberName($last_post_member_id);

             $this->destSQL->Query("UPDATE topics SET last_post_id=%,last_post_member=%,member_name_last=%
		        WHERE id=%", $last_post_id, $last_post_member_id, $member_name_last, $tid);

        }

        $this->destSQL->Query("SELECT * FROM posts WHERE new_topic='1'");
        $result = $this->destSQL->ResultArray();
        foreach ($result as $item)
        {
            $user_name = $this->GetMemberName($item['post_member_id']);
            $this->destSQL->Query("UPDATE topics SET post_id=%, member_id_open=%, member_name_open=% WHERE id=%",
		        $item['pid'], $item['post_member_id'], $user_name, $item['topic_id']);
        }

        $this->destSQL->Query("SELECT * FROM forums WHERE parent_id != '0'");
        $result = $this->destSQL->ResultArray();
        foreach ($result as $forum)
        {
            $last_topic_id = $forum['last_topic_id'];
            $this->destSQL->Query("SELECT * FROM posts WHERE topic_id=%", $last_topic_id);
            $posts = $this->destSQL->ResultArray();
            $max = $last_post_id = 0;
            foreach ($posts as $item)
                if ($item['post_date'] >= $max)
                    $last_post_id = $item['pid'];

            $this->destSQL->Query("SELECT * FROM topics WHERE forum_id=%", $forum['id']);
            $topics = $this->destSQL->ResultArray();
            $hiden = $posts_hidden = $post_max = 0;
            foreach ($topics as $item)
            {
                $hiden += $item['hiden'];
                $this->destSQL->Query("SELECT * FROM posts WHERE topic_id=%", $item['id']);
                $posts = $this->destSQL->ResultArray();
                foreach ($posts as $post)
                {
                    $posts_hidden += $post['hide'];
                    if ($post['post_date'] > $post_max) {
                        $post_max = $post['post_date'];
                        $last_post_id = $post['pid'];
                    }
                }
            }
            $this->destSQL->Query("SELECT post_member_id, post_member_name, post_date FROM posts WHERE pid=%", $last_post_id);
            $result = $this->destSQL->ResultArray();
            $result = $result[0];


            $this->destSQL->Query("UPDATE forums SET topics_hiden=%,posts_hiden=%,last_post_id=%,last_post_member=%,
                                   last_post_member_id=%,last_post_date=% WHERE id=%",
                                  $hiden, $posts_hidden, $last_post_id, $result['post_member_name'],
                                  $result['post_member_id'], $result['post_date'], $forum['id']);
        }

        $this->Finish();

        $this->Start("Ban filters");

        $this->srcSQL->Query("SELECT * FROM banned ORDER by id ASC");
        $result = $this->srcSQL->ResultArray();
        $begindate = time();
        foreach ($result as $item)
        {

           $ban_member_id = "";
            if ($item['users_id'] != 0) {
                $user_id = $users_id[$item['users_id']];
                $descr = $this->GetMemberName($user_id);
                $type = "name";
                $ban_member_id = $user_id;
                $this->destSQL->Query("INSERT INTO members_banfilters SET
			            type=%,description=%,date=%,moder_desc=%,date_end=%,ban_days=%,ban_member_id=%",
                                      $type, $descr, $begindate, $item['descr'], $item['date'], $item['days'], $ban_member_id);
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

                 $this->destSQL->Query("INSERT INTO members_banfilters SET
			            type=%,description=%,date=%,moder_desc=%,date_end=%,ban_days=%,ban_member_id=%",
                            $type, $descr, $begindate, $item['descr'], $item['date'],$item['days'], $ban_member_id);
            }
        }

        $this->Finish();

        $this->Start("Votes");

        $this->srcSQL->Query("SELECT * FROM forum_poll_log  ORDER by id ASC");
        $result = $this->srcSQL->ResultArray();
        foreach ($result as $item)
        {
            $user_id = $users_id[$item['member']];

            $user_name = $this->GetMemberName($user_id);
            $this->destSQL->Query("SELECT ip FROM members WHERE member_id=%", $user_id);
            $user_ip = $this->destSQL->Result();

            $topic_id = $item['topic_id'];
            $this->destSQL->Query("SELECT poll_id FROM topics WHERE id=%",$topic_id);

            $poll_id = $this->destSQL->Result();

            $this->destSQL->Query("INSERT INTO topics_poll_logs SET
		        poll_id=%,ip=%,member_id=%,log_date=%,answer='0',member_name=%", $poll_id, $user_ip, time(), $user_name);
        }

        $this->Finish();

        $this->Start("Moderators");

        $this->srcSQL->Query("SELECT * FROM forum_moderators  ORDER by mid ASC");
        $result = $this->srcSQL->ResultArray();
        foreach ($result as $item)
        {
            if ($item['member_id'] == 0)
                continue;

            $permissions = array(
                "global_changepost" => $item['edit_post'],
                "global_titletopic" => $item['edit_topic'],
                "global_delpost" => $item['delete_post'],
                "global_deltopic" => $item['delete_topic'],
                "global_opentopic" => $item['open_topic'],
                "global_closetopic" => $item['close_topic'],
                "global_movetopic" => $item['move_topic'],
                "global_movepost" => $item['move_post'],
                "global_unionpost" => $item['combining_post'],
                "global_changepost" => $item['edit_post'],
                "global_hideshow" => '0',
                "global_polltopic" => '0',
                "global_fixtopic" => $item['pin_topic'],
                "global_hidetopic" => '0',
                "global_uniontopic" => '0',
                "global_fixedpost" => '0',
                "global_unfixtopic" => $item['unpin_topic']
            );
            $permissions = serialize($permissions);

            $member_id = $users_id[$item['member_id']];
            $member_name = $this->GetMemberName($member_id);

            $this->destSQL->Query("SELECT user_group FROM members WHERE member_id=%", $member_id);
            $group = $this->destSQL->Result();
            $group = $group < 6 ? $group : $item['group_id'] + 1;
            $this->destSQL->Query("INSERT INTO forums_moderator SET
		            fm_forum_id=%,fm_member_id=%,fm_member_name=%,fm_group_id=%,fm_is_group='0',fm_permission=%",
                    $item['forum_id'],$member_id,$member_name,$group,$permissions);
        }

        $this->Finish();

        $this->Start("Ranks");
        $this->srcSQL->Query("SELECT * FROM forum_titles  ORDER by id ASC");
        $result = $this->srcSQL->ResultArray();
        foreach ($result as $item)
            $this->destSQL->Query("INSERT INTO members_ranks SET title=%,post_num=%,stars=%", $item['title'],
                                  $item['posts'], $item['pips']);
        $this->Finish();

        $this->Start("Subscribe");

        $this->srcSQL->Query("SELECT * FROM forum_subscription  ORDER by sid ASC");
        $result = $this->srcSQL->ResultArray();

        foreach ($result as $item)
        {
            $user_id = $users_id[$item['user_id']];
            $topic_id = $item['topic_id'];
            $this->destSQL->Query("INSERT INTO topics_subscribe SET subs_member=%,topic=%", $user_id, $topic_id);

            $this->destSQL->Query("SELECT subscribe FROM members WHERE member_id=%", $user_id);
            $s_text = $this->destSQL->Result();
            $s_text = ($s_text == "") ? $topic_id : $s_text . "," . $topic_id;

            $this->destSQL->Query("UPDATE members SET subscribe=% WHERE member_id=%", $s_text, $user_id);
        }
        $this->Finish();

        $this->Start("Files");

        $this->srcSQL->Query("SELECT * FROM forum_files ORDER by file_id ASC");
        $result = $this->srcSQL->ResultArray();

        $files_id = array();
        foreach ($result as $file)
        {

            $author_name = $file['file_author'];
            $author_id = $this->GetMemberId($author_name);
            $topic_id = $file['topic_id'];
            $this->destSQL->Query("SELECT forum_id FROM topics WHERE id=%",$topic_id);
            $forum_id = $this->destSQL->Result();

            $file_type = $file['file_type'];
            if ($file_type == "thumb")
                $file_type = "picture";
            $this->destSQL->Query("INSERT INTO topics_files SET
                    file_title=%,file_name=%,file_type=%,file_mname=%,file_mid=%,file_date=%,file_size=%,file_count=%,
                    file_tid=%,file_fid=%,file_convert='1',file_pid=%",
                    $file['file_name'], $file['onserver'], $file_type, $author_name, $author_id, $file['file_date'],
                    $file['file_size'], $file['dcount'], $topic_id, $forum_id, $file['post_id']);;

            $files_id[$file['file_id']] = $this->destSQL->InsertedId();
        }

        $this->destSQL->Query("SELECT * FROM posts");
        $result = $this->destSQL->ResultArray();
        foreach ($result as $post)
        {
            $text = $post['text'];
            preg_match_all('#\[attachment=(\d+)\]#sui', $text, $attachments, PREG_SET_ORDER);
            foreach($attachments as $attachment)
            {
                $file_id = $files_id[$attachment[1]];
                $text = str_replace("[attachment=" . $attachment[1] . "]", "[attachment=$file_id]", $text);
            }
            $this->destSQL->Query("UPDATE posts SET text=% WHERE pid=%", $text, $post['pid']);
        }
        $this->Finish();

        $this->Start("Reputation");


        $this->srcSQL->Query("SELECT * FROM forum_reputation_log ORDER by rid ASC");
        $result = $this->srcSQL->ResultArray();

        foreach ($result as $item)
        {
            $to_name = $this->GetMemberName($users_id[$item['mid']]);
            $how = $item['action'] == '-' ? '-1' : '+1';
            $this->destSQL->Query("INSERT INTO members_reputation SET
		from_id=%,from_name=%,to_id=%,to_name=%,date=%,how=%,text=%",
                $this->GetMemberId($item['author']), $item['author'],
                $users_id[$item['mid']], $to_name, $item['date'], $how, $item['cause']);
        }

        $this->Finish();
        $this->Start("Attachments");
        $this->destSQL->ResetLimit();
        while (true)
        {
            $this->destSQL->LimitQuery("SELECT * FROM posts");
            $posts = $this->destSQL->ResultArray();
            if (!$posts) break;
            foreach ($posts as $post)
            {
                $post_id = $post['pid'];
                $this->destSQL->Query("SELECT * FROM topics_files WHERE file_pid=%", $post_id);
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

        $this->Start("Punishments");

        $this->srcSQL->Query("SELECT * FROM forum_warn_log");
        $warns = $this->srcSQL->ResultArray();

        foreach ($warns as $warning)
            if ($warning['action'] == '+')
                $this->destSQL->Query("INSERT INTO members_warning SET mid=%, moder_id=%, moder_name=%, date=%,
                description=%,st_w='1'", $users_id[$warning['mid']], $this->GetMemberId($warning['author']),
                                      $warning['author'],$warning['date'],$warning['cause']);


        $this->srcSQL->Query("SELECT * FROM forum_warn_log");
        $warns = $this->srcSQL->ResultArray();

        foreach ($warns as $warning)
            if ($warning['action'] == '-') {

                $this->destSQL->Query("SELECT * FROM members_warning");
                $lb_warns = $this->destSQL->ResultArray();
                if ($lb_warns) {
                    $min_date = 2000000000;
                    $min_id = -1;
                    foreach ($lb_warns as $lb_warn)
                    {
                        if ($lb_warn['st_w'] == 0) continue;
                        if ($lb_warn['date'] < $min_date) {
                            $min_date = $lb_warn['date'];
                            $min_id = $lb_warn['id'];
                        }
                    }
                    if ($min_id == -1) break;
                    $this->destSQL->Query("UPDATE members_warning SET st_w='0' WHERE id=%", $min_id);
                }
            }
        $this->destSQL->Query("SELECT * FROM members");
        $users = $this->destSQL->ResultArray();
        foreach ($users as $user)
        {
            $user_id = $user['member_id'];
            $this->destSQL->Query("SELECT * FROM members_warning WHERE mid=% AND st_w='1'", $user_id);
            $result = $this->destSQL->ResultArray();
            $count = count($result);
            $this->destSQL->Query("UPDATE members SET count_warning=% WHERE member_id=%", $count, $user_id);
        }
        $this->Finish();
    }
}


?>