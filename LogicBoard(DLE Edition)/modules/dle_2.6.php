<?php

require_once "EngineBase.php";
require_once "include/dle_parser.php";


class DLE_2_6 extends EngineBase
{
    protected $setup = array(
        "caption" => "DLE FORUM ver. 2.6",
        "id" => "dle_2_6",
        "db_prefix" => "dle_",
        "dle_based" => "1",
        "add_text" => array("Права групп перенесены будут не все. Пожалуйста, отредакртируйте их после конвертации в панели управления","После конвертации не забудьте очистить кеш форума, иначе новые форумы не появятся."),

    );

    private function GetMemberId($name)
    {
        $this->srcSQL->Query("SELECT user_id FROM users WHERE name=%%", $name);
        return $this->srcSQL->Result(0);
    }

    private function GetMemberName($id)
    {
        $this->srcSQL->Query("SELECT name FROM users WHERE user_id=%%", $id);
        return $this->srcSQL->Result("Удалён");
    }

    public function Convert($options)
    {
       $user_topics = $user_posts = $user_rep = array();
        $this->Start("Install");
        $this->InstallLB($options);
        $this->Finish();

        $this->Start("Users");

        $this->srcSQL->Query("SELECT * FROM users");
        $users = $this->srcSQL->ResultArray();
        foreach ($users as $user)
        {
            $user_topics[$user['user_id']] = $user_posts[$user['user_id']] = 0;
            if (in_array("mod_rep", $options)) {
                $user_rep[$user['user_id']] = array("level" => 0, "plus" => 0, "minus" => 0);
                $user_rep[$user['user_id']]['level'] = $user['repa'];
                $user_rep[$user['user_id']]['plus'] = substr($user['repa_mod'], 0, strpos($user['repa_mod'], "|"));
                $user_rep[$user['user_id']]['minus'] = substr($user['repa_mod'], strpos($user['repa_mod'], "|") + 1);
            }
        }
        $user_topics[0] = $user_posts[0] = 0;
        $this->Finish();


        $this->Start("Categories");

        $this->srcSQL->Query("SELECT * FROM forum_forums WHERE is_category = '1' ORDER by id ASC");
        $result = $this->srcSQL->ResultArray();
        foreach ($result as $item)
        {
            $alt_name = ($item['alt_name'] == "") ? translit($item['alt_name']) : $item['alt_name'];
            $postcount = (isset($item['postcount'])) ? $item['postcount'] : 1;
            $this->destSQL->Query("INSERT INTO forums SET id=%%,parent_id='0', title=%%, alt_name=%%, postcount=%%, posi=%%",
                                  $item['id'],$item['name'], $alt_name, $postcount, $item['position']);
        }

        $this->Finish();

        $this->Start("Groups");

        $this->srcSQL->Query("SELECT * FROM usergroups ORDER by id ASC");
        $result = $this->srcSQL->ResultArray();

        foreach ($result as $group)
            if ($group['id'] > 0)
                $this->destSQL->Query("INSERT INTO groups SET g_id=%%, g_title=%%", $group['id'], $group['group_name']);
        $this->destSQL->Query("UPDATE groups SET g_access_cc=1,g_supermoders=1 WHERE g_id=1");

        $this->Finish();


        $this->Start("Forums");
        while (true)
        {
            $this->srcSQL->LimitQuery("SELECT * FROM forum_forums WHERE is_category=0 ORDER by id ASC");

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

                $this->srcSQL->Query("SELECT * FROM usergroups ORDER by id ASC");
                $result = $this->srcSQL->ResultArray();
                foreach ($result as $group)
                    $permissions[$group['id']] = array("read_forum" => 0, "read_theme" => 0, "creat_theme" => 0, "answer_theme" => 0, "upload_files" => 0, "download_files" => 0);

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

                $permissions = serialize($permissions);
                $postcount = (isset($item['postcount'])) ? $item['postcount'] : 1;

                $last_user = $this->GetMemberId($item['f_last_poster_name']);
                $last_post_date = datetime_to_int($item['f_last_date']);

                $this->destSQL->Query("INSERT INTO forums SET
            id=%%,posi=%%,title=%%,alt_name=%%,description=%%,last_post_member=%%, last_post_member_id=%%,
            last_post_date=%%,allow_bbcode=1, allow_poll=1,postcount=%%, group_permission=%%,password=%%,
            sort_order='DESC',posts=%%,topics=%%,rules=%%,meta_desc='',meta_key=''",
                                      $item['id'],$item['position'], $item['name'], translit($item['name']), $item['description'],
                                      $item['f_last_poster_name'], $last_user, $last_post_date, $postcount, $permissions,
                                      $item['password'], $item['posts'], $item['topics'], $item['rules']);
            }
        }

        $this->srcSQL->ResetLimit();

        while (true)
        {
            $this->srcSQL->LimitQuery("SELECT * FROM forum_forums WHERE is_category=0 ORDER by id ASC");
            $result = $this->srcSQL->ResultArray();
            if (!$result) break;
            foreach ($result as $item)
            {
                $parent_id = $item['parentid'];
                $forum_id = $item['id'];
                $this->destSQL->Query("UPDATE forums SET parent_id=%% WHERE id=%%", $parent_id, $forum_id);
            }
        }
        $this->Finish();

        $this->Start("Topics");
        $max_data = $min_data = array();
        while (true)
        {
            $this->srcSQL->LimitQuery("SELECT * FROM forum_topics ORDER by tid ASC");
            $result = $this->srcSQL->ResultArray();
            if (!$result) break;
            $max_data = array();
            foreach ($result as $item)
            {
                $forum_id = $item['forum_id'];
                $max_data[$forum_id] = array("time" => "", "tid" => "", "hiden" => "");
                $date_last = datetime_to_int($item['last_date']);
                $hidden = ($item['hidden'] >= 1) ? 1 : 0;
                $status = $item['topic_status'] == 0 ? "open" : "closed";
                $fixed = $item['fixed'] == 0 ? 1 : 0;
                $date_open = datetime_to_int($item['start_date']);

                $this->destSQL->Query("INSERT INTO topics SET id=%%,forum_id=%%,title=%%,description=%%,date_open=%%,date_last=%%,
                status=%%,views=%%,post_num=%%,post_hiden='0',fixed=%%,hiden=%%, poll_id='0',post_fixed='0'",
                                      $item['tid'], $forum_id, $item['title'], $item['topic_descr'], $date_open, $date_last, $status,
                                      $item['views'], $item['post'], $fixed, $hidden);


                $user_topics[$this->GetMemberId($item['author_topic'])]++;

                if ($item['poll_title'] != "") {
                    $topic_id = $item['tid'];
                    $variants = str_replace("<br />", "\r\n", $item['poll_body']);
                    $open_date = datetime_to_int($item['start_date']);
                    $this->destSQL->Query("INSERT INTO topics_poll SET
                        tid=%%,vote_num=%%,title=%%,question=%%, variants=%%,answers=%%,multiple=%%,open_date=%%",
                                          $topic_id, $item['poll_count'], $item['poll_title'], $item['frage'], $variants, $item['answer'],
                                          $item['multiple'], $open_date);
                    $this->destSQL->Query("UPDATE topics SET poll_id=%% WHERE id=%%", $this->destSQL->InsertedId(),
                                          $topic_id);
                }
                if ((!isset($max_data[$forum_id])) || ($max_data[$forum_id]["time"] < $date_last)) {
                    $max_data[$forum_id]["time"] = $date_last;
                    $max_data[$forum_id]["tid"] =$item['tid'];
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
            $this->destSQL->Query("UPDATE forums SET last_topic_id=%%,topics_hiden=%%,last_title=%% WHERE id=%%",
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
            pid=%%,topic_id=%%,new_topic='0',text=%%,post_date=%%,edit_date=%%,post_member_id=%%,post_member_name=%%,
            ip=%%,hide=%%,edit_member_id=%%,edit_member_name=%%,edit_reason='',fixed='0'",$item['pid'],
                                      $topic_id, $text, $post_date, $item['edit_time'], $post_member_id, $item['post_author'],
                                      $item['post_ip'], $hiden, $edit_member_id, $edit_member_name);

                $user_posts[$this->GetMemberId($item['post_author'])]++;

                if ((!isset($min_data[$topic_id])) || ($min_data[$topic_id]["time"] > $post_date)) {
                    $min_data[$topic_id]["time"] = $post_date;
                    $min_data[$topic_id]["pid"] = $item['pid'];
                }
                if ((!isset($max_data[$topic_id])) || ($max_data[$topic_id]["time"] < $post_date))
                    $max_data[$topic_id] = array("time" => $post_date, "pid" => $item['pid']);
                $min_data[$topic_id]['hiden'] += $hiden;
            }
        }

        foreach ($user_topics as $user_id => $topics_count)
        {
            $posts_count = $user_posts[$user_id];
            $this->srcSQL->Query("UPDATE users SET posts_num=%%,topics_num=%% WHERE user_id=%%", $posts_count, $topics_count, $user_id);
        }

        foreach ($min_data as $tid => $item)
        {
            $this->destSQL->Query("UPDATE posts SET new_topic='1' WHERE pid=%%", $item['pid']);
            $this->destSQL->Query("UPDATE topics SET post_hiden=%% WHERE id=%%", $item['hiden'], $tid);
        }


        while (true)
        {
            $this->destSQL->LimitQuery("SELECT * FROM posts");
            $result = $this->destSQL->ResultArray();
            if (!$result) break;
            foreach ($result as $item)
                if ($item['hide'] == 0) {
                    $topic_id = $item['topic_id'];
                    $this->destSQL->Query("SELECT hiden FROM topics WHERE id=%%", $topic_id);
                    $hiden_topic = $this->destSQL->Result();
                    if ($hiden_topic && $item['new_topic'] == 1)
                        $this->destSQL->Query("UPDATE posts SET hide='1' WHERE pid=%%", $item['pid']);
                }
        }

        foreach ($max_data as $tid => $item)
        {
            $last_post_id = $item['pid'];

            $this->destSQL->Query("SELECT post_member_id FROM posts WHERE pid=%%", $last_post_id);

            $last_post_member_id = $this->destSQL->Result();
            $member_name_last = $this->GetMemberName($last_post_member_id);
            $last_post_member_id = $last_post_member_id ? $last_post_member_id : "0";

            $this->destSQL->Query("UPDATE topics SET last_post_id=%%,last_post_member=%%,member_name_last=%%
		        WHERE id=%%", $last_post_id, $last_post_member_id, $member_name_last, $tid);

        }

        $this->destSQL->Query("SELECT * FROM posts WHERE new_topic='1'");
        $result = $this->destSQL->ResultArray();
        foreach ($result as $item)
        {
            $user_name = $this->GetMemberName($item['post_member_id']);
            $this->destSQL->Query("UPDATE topics SET post_id=%%, member_id_open=%%, member_name_open=%% WHERE id=%%",
                                  $item['pid'], $item['post_member_id'], $user_name, $item['topic_id']);
        }

        $this->destSQL->Query("SELECT * FROM forums WHERE parent_id != '0'");
        $result = $this->destSQL->ResultArray();
        foreach ($result as $forum)
        {
            $last_topic_id = $forum['last_topic_id'];
            $this->destSQL->Query("SELECT * FROM posts WHERE topic_id=%%", $last_topic_id);
            $posts = $this->destSQL->ResultArray();
            $max = $last_post_id = 0;
            foreach ($posts as $item)
                if ($item['post_date'] >= $max)
                    $last_post_id = $item['pid'];

            $this->destSQL->Query("SELECT * FROM topics WHERE forum_id=%%", $forum['id']);
            $topics = $this->destSQL->ResultArray();
            $hiden = $posts_hidden = $post_max = 0;
            foreach ($topics as $item)
            {
                $hiden += $item['hiden'];
                $this->destSQL->Query("SELECT * FROM posts WHERE topic_id=%%", $item['id']);
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

            $this->destSQL->Query("SELECT post_member_id, post_member_name, post_date FROM posts WHERE pid=%%", $last_post_id);
            $result = $this->destSQL->ResultArray();
            if (count($result) == 0) continue;
            $result = $result[0];
            $result['post_member_id'] = $result['post_member_id'] ? $result['post_member_id'] : 0;


            $this->destSQL->Query("UPDATE forums SET topics_hiden=%%,posts_hiden=%%,last_post_id=%%,last_post_member=%%,
                                   last_post_member_id=%%,last_post_date=%% WHERE id=%%",
                                  $hiden, $posts_hidden, $last_post_id, $result['post_member_name'],
                                  $result['post_member_id'], $result['post_date'], $forum['id']);
        }

        $this->Finish();

        if (in_array("rep_mod", $options)) {
            $this->Start("Reputatation");

            $this->srcSQL->Query("SELECT * FROM forum_reputation_log");
            $logs = $this->srcSQL->ResultArray();
            foreach ($logs as $log)
            {
                $how = ($log['action'] == "+") ? "+1" : "-1";
                $date = int_to_datetime($log['date']);
                $this->srcSQL->Query("INSERT INTO repa_comm SET how=%%,date=%%,author=%%,komu=%%,text=%%,url_page=''",
                                     $how, $date, $log['author'], $this->GetMemberName($log['mid']), $log['cause']);
                $this->srcSQL->Query("INSERT INTO repa_log SET autor_id=%%,komu_id=%%,date_change=%%",
                                     $this->GetMemberId($log['author']), $log['mid'], $log['date']);
                $user_rep[$log['mid']] = array("minus" => 0, "plus" => 0, "level" => 0);
                if ($how == "+1") {
                    $user_rep[$log['mid']]['level']++;
                    $user_rep[$log['mid']]['plus']++;
                }
                else if ($how == "-1") {
                    $user_rep[$log['mid']]['level']--;
                    $user_rep[$log['mid']]['minus']++;
                }
                $user_rep[$log['mid']]['level'] = $user_rep[$log['mid']]['level'] ? $user_rep[$log['mid']]['level'] : 0;
                $user_rep[$log['mid']]['plus'] = $user_rep[$log['mid']]['plus'] ? $user_rep[$log['mid']]['plus'] : 0;
                $user_rep[$log['mid']]['minus'] = $user_rep[$log['mid']]['minus'] ? $user_rep[$log['mid']]['minus'] : 0;
            }

            foreach ($user_rep as $ind => $rep)
                $this->srcSQL->Query("UPDATE users SET repa=%%,repa_mod=%% WHERE user_id=%%", $rep['level'], $rep['plus'] . "|" . $rep['minus'], $ind);
            $this->Finish();
        }

        $this->Start("Votes");

        $this->srcSQL->Query("SELECT * FROM forum_poll_log  ORDER by id ASC");
        $result = $this->srcSQL->ResultArray();
        foreach ($result as $item)
        {
            $user_id = $item['member'];

            $user_name = $this->GetMemberName($user_id);
            $this->srcSQL->Query("SELECT logged_ip FROM users WHERE user_id=%%", $user_id);
            $user_ip = $this->srcSQL->Result();

            $topic_id = $item['topic_id'];
            $this->destSQL->Query("SELECT poll_id FROM topics WHERE id=%%", $topic_id);

            $poll_id = $this->destSQL->Result();

            $this->destSQL->Query("INSERT INTO topics_poll_logs SET
		        poll_id=%%,ip=%%,member_id=%%,log_date=%%,answer='0',member_name=%%", $poll_id, $user_ip, $user_id, time(), $user_name);
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

            $member_id = $item['member_id'];
            $member_name = $this->GetMemberName($member_id);

            $this->srcSQL->Query("SELECT user_group FROM users WHERE user_id=%%", $member_id);
            $group = $this->srcSQL->Result();
            $group = $group < 6 ? $group : $item['group_id'] + 1;
            $this->destSQL->Query("INSERT INTO forums_moderator SET
		            fm_forum_id=%%,fm_member_id=%%,fm_member_name=%%,fm_group_id=%%,fm_is_group='0',fm_permission=%%",
                                  $item['forum_id'], $member_id, $member_name, $group, $permissions);
        }

        $this->Finish();

        $this->Start("Ranks");
        $this->srcSQL->Query("SELECT * FROM forum_titles  ORDER by id ASC");
        $result = $this->srcSQL->ResultArray();
        foreach ($result as $item)
            $this->destSQL->Query("INSERT INTO members_ranks SET title=%%,post_num=%%,stars=%%", $item['title'],
                                  $item['posts'], $item['pips']);
        $this->Finish();

        $this->Start("Subscribe");

        $this->srcSQL->Query("SELECT * FROM forum_subscription  ORDER by sid ASC");
        $result = $this->srcSQL->ResultArray();

        foreach ($result as $item)
        {
            $user_id = $item['user_id'];
            if ($item['topic_id'] == -1) continue;
            $topic_id = $item['topic_id'];
            $this->destSQL->Query("INSERT INTO topics_subscribe SET subs_member=%%,topic=%%", $user_id, $topic_id);

            $this->srcSQL->Query("SELECT lb_subscribe FROM users WHERE user_id=%%", $user_id);
            $s_text = $this->srcSQL->Result();
            $s_text = ($s_text == "") ? $topic_id : $s_text . "," . $topic_id;

            $this->srcSQL->Query("UPDATE users SET lb_subscribe=%% WHERE user_id=%%", $s_text, $user_id);
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
            $this->destSQL->Query("SELECT forum_id FROM topics WHERE id=%%", $topic_id);
            $forum_id = $this->destSQL->Result();

            $file_type = $file['file_type'];
            if ($file_type == "thumb")
                $file_type = "picture";
            $this->destSQL->Query("INSERT INTO topics_files SET
                    file_title=%%,file_name=%%,file_type=%%,file_mname=%%,file_mid=%%,file_date=%%,file_size=%%,file_count=%%,
                    file_tid=%%,file_fid=%%,file_convert='1',file_pid=%%",
                                  $file['file_name'], $file['onserver'], $file_type, $author_name, $author_id, $file['file_date'],
                                  $file['file_size'], $file['dcount'], $topic_id, $forum_id, $file['post_id']);
            ;

            $files_id[$file['file_id']] = $this->destSQL->InsertedId();
        }

        $this->destSQL->Query("SELECT * FROM posts");
        $result = $this->destSQL->ResultArray();
        foreach ($result as $post)
        {
            $text = $post['text'];
            preg_match_all('#\[attachment=(\d+)\]#si', $text, $attachments, PREG_SET_ORDER);
            foreach ($attachments as $attachment)
            {
                $file_id = $files_id[$attachment[1]];
                $text = str_replace("[attachment=" . $attachment[1] . "]", "[attachment=$file_id]", $text);
            }
            $this->destSQL->Query("UPDATE posts SET text=%% WHERE pid=%%", $text, $post['pid']);
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
                $this->destSQL->Query("SELECT * FROM topics_files WHERE file_pid=%%", $post_id);
                $files = $this->destSQL->ResultArray();
                $text = '';
                foreach ($files as $file)
                    $text .= $file['file_id'] . ",";
                if (strlen($text) > 0)
                    $text = substr($text, 0, -1);

                $this->destSQL->Query("UPDATE posts SET attachments=%% WHERE pid=%%", $text, $post['pid']);
            }
        }

        $this->Finish();


        $this->Start("Punishments");

        $this->srcSQL->Query("SELECT * FROM forum_warn_log");
        $warns = $this->srcSQL->ResultArray();


        foreach ($warns as $warning)
            if ($warning['action'] == '+')
                $this->destSQL->Query("INSERT INTO members_warning SET mid=%%, moder_id=%%, moder_name=%%, date=%%,
                description=%%,st_w='1'", $warning['mid'], $this->GetMemberId($warning['author']),
                                      $warning['author'], $warning['date'], $warning['cause']);


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
                    $this->destSQL->Query("UPDATE members_warning SET st_w='0' WHERE id=%%", $min_id);
                }
            }
        $this->srcSQL->Query("SELECT * FROM users");
        $users = $this->srcSQL->ResultArray();
        foreach ($users as $user)
        {
            $user_id = $user['user_id'];
            $this->destSQL->Query("SELECT * FROM members_warning WHERE mid=%% AND st_w='1'", $user_id);
            $result = $this->destSQL->ResultArray();
            $count = count($result);
            $this->srcSQL->Query("UPDATE users SET count_warning=%% WHERE user_id=%%", $count, $user_id);
        }
        $this->Finish();
    }
}


?>