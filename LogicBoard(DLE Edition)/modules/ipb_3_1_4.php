<?php

require_once "EngineBase.php";
require_once "include/ipb_parser.php";

class IPB_3_1_4 extends EngineBase
{
    protected $setup = array(
        "caption" => "Invision Power Board ver. 3.1.4",
        "id" => "ipb_3_1_4",
        "db_prefix" => "ipb_",
        "add_text" => array("Пароли пользователей не перенесутся. Каждому пользователю надо будет сделать запрос на восстановление
        пароля на электронную почту", "При конвертации будет осуществлён перенос пользователей из форума в базу данных сайта, всвязи с этим некоторым пользователям потребуется восстановить пароль, используя адрес своей почты (имеено почты, а не логина), т.к. в некоторых случаях логин пользователя будет изменён (при необходимости в АЦ сайта Вы сможете сменить логин пользователю на любой другой).
Создайте на сайте новость с данным предупреждением для Ваших пользователей.","Права групп перенесены будут не все. Пожалуйста, отредакртируйте их после конвертации в панели управления","После конвертации не забудьте очистить кеш форума, иначе новые форумы не появятся."),
        "dle_based" => false,
    );

    public function Convert($options)
    {
        $this->Start("Install");
        $this->InstallLB($options);
        $this->Finish();


        $groups_masks = $users_id = $posts_key = $posts_attachment =
        $post_files = $users_ban = $users_group = array();


        $this->Start("Groups");

        $groups_key = array("1" => "4", "2" => "6", "4" => "3", "5" => "2");
        $un_groups_key = array("4" => "1", "6" => "2", "3" => "4", "2" => "5");
        $access = array();
        $access['local_opentopic'] = 0;
        $access['local_closetopic'] = 0;
        $access['local_deltopic'] = 0;
        $access['local_titletopic'] = 0;
        $access['local_polltopic'] = 0;
        $access['local_delpost'] = 0;
        $access['local_changepost'] = 0;


        $this->srcSQL->Query("SELECT * FROM groups");
        $groups = $this->srcSQL->ResultArray();
        foreach ($groups as $group)
            if ($group['g_id'] > 6) {
                $this->dleSQL->Query("INSERT INTO usergroups SET group_name=%%, allow_admin=%%", $group['g_title'],
                                     $group['g_access_cp']);
                $groups_key[$this->dleSQL->InsertedId()] = $group['g_id'];
            }


        $this->dleSQL->Query("SELECT * FROM usergroups");
        $groups = $this->dleSQL->ResultArray();
        foreach ($groups as $group)
        {
            if (!isset($groups_key[$group['id']])) {

                $this->destSQL->Query("INSERT INTO groups SET g_id=%%,g_title=%%,g_access_cc=%%, g_access=%%", $group['id'],
                                      $group['group_name'], $group['allow_admin'], serialize($access));
                continue;
            }
            $dle_group_id = $group['id'];
            $ipb_group_id = $groups_key[$dle_group_id];

            $this->srcSQL->Query("SELECT * FROM groups WHERE g_id=%%", $ipb_group_id);
            $ipb_group = $this->srcSQL->ResultArray();
            $ipb_group = $ipb_group[0];


            $access['local_opentopic'] = $ipb_group['g_open_close_posts'];
            $access['local_closetopic'] = $ipb_group['g_open_close_posts'];
            $access['local_deltopic'] = $ipb_group['g_delete_own_topics'];
            $access['local_titletopic'] = $ipb_group['g_edit_topic'];
            $access['local_polltopic'] = $ipb_group['g_edit_topic'];
            $access['local_delpost'] = $ipb_group['g_delete_own_posts'];
            $access['local_changepost'] = $ipb_group['g_edit_posts'];

            $this->destSQL->Query("INSERT INTO groups SET g_id=%%,g_title=%%,g_prefix_st=%%,g_supermoders=%%,g_access_cc=%%,
                g_show_close_f = %%,g_access = %%,g_new_topic = %%,g_reply_topic = %%,
                g_reply_close = %%,g_search = %%,g_prefix_end = %%",
                                  $dle_group_id, $ipb_group['g_title'], $ipb_group['prefix'], $ipb_group['g_is_supmod'], $ipb_group['g_access_cp'],
                                  $ipb_group['g_access_offline'], serialize($access), $ipb_group['g_post_new_topics'],
                                  $ipb_group['g_reply_other_topics'], $ipb_group['g_post_closed'], $ipb_group['g_use_search'], $ipb_group['suffix']);
            $groups_masks[$ipb_group['g_id']] = explode(',', $ipb_group['g_perm_id']);
        }
        $this->Finish();

        $this->Start("Users");
        $dle_user_names = $dle_user_emails = $dle_user_ids = array();
        while (true)
        {
            $this->dleSQL->LimitQuery("SELECT * FROM users");
            $users = $this->dleSQL->ResultArray();
            if (!$users) break;
            foreach ($users as $user)
            {
                $dle_user_names[] = strtolower($user['name']);
                $dle_user_ids[strtolower($user['email'])] = $user['user_id'];
                $dle_user_emails[] = strtolower($user['email']);
            }
        }
        $exist_users = array();
        while (true)
        {
            $this->srcSQL->LimitQuery("SELECT * FROM members");
            $users = $this->srcSQL->ResultArray();
            if (!$users) break;
            foreach ($users as $user)
            {
                if ($user['member_group_id'] == 5 || $user['member_group_id'] < 3) continue;
                $group = (isset($un_groups_key[$user['member_group_id']])) ? $un_groups_key[$user['member_group_id']] :
                        $user['member_group_id'];

                $user_email = strtolower($user['email']);
                $user_name = strtolower($user['name']);
				$user['email'] = strtolower($user['email']);
				$user['name'] = strtolower($user['name']);
                if(in_array($user_name, $exist_users))continue;

                $exist_users[] = $user_name;
                if (in_array($user_email, $dle_user_emails)) {
                    $this->dleSQL->Query("UPDATE users SET posts_num=%%, count_warning=%%,user_group=%% WHERE user_id=%%",
                                         $user['posts'], $user['warn_level'], $group, $dle_user_ids[$user['name']]);
                    $users_id[$user['member_id']] = $dle_user_ids[$user['email']];
                    $users_group[$dle_user_ids[$user['name']]] = $group;
                    if ($user['temp_ban'] != "" && $user['temp_ban'] != "0")
                        $users_ban[$dle_user_ids[$user['email']]] = $user['temp_ban'];
                    continue;
                }
                $name = in_array($user_name, $dle_user_names) ? "ipbuser_" . substr(md5(rand()), 0, 6) : $user['name'];

                $this->dleSQL->Query("INSERT INTO users SET name=%%,email=%%,reg_date=%%,user_group=%%,password=%%,logged_ip=%%,
                                   personal_title=%%,lastdate=%%,lb_b_day=%%,lb_b_month=%%,banned=%%,lb_b_year=%%,
                                   count_warning=%%,posts_num=%%", $name, $user['email'], $user['joined'], $group,
                                     $user['member_login_key'], $user['ip_address'], $user['title'], $user['last_visit'],
                                     $user['bday_day'], $user['bday_month'], $user['member_banned'], $user['bday_year'],
                                     $user['warn_level'], $user['posts']);

                $users_id[$user['member_id']] = $this->dleSQL->InsertedId();
                $users_group[$this->dleSQL->InsertedId()] = $group;
                if ($user['temp_ban'] != "" && $user['temp_ban'] != "0")
                    $users_ban[$this->dleSQL->InsertedId()] = $user['temp_ban'];
            }
        }

        $this->Finish();


        $this->Start("Categories");

        $this->srcSQL->Query("SELECT * FROM forums WHERE parent_id=-1");
        $categories = $this->srcSQL->ResultArray();
        foreach ($categories as $category)
        {
            $this->destSQL->Query("INSERT INTO forums SET id=%%,parent_id=0,title=%%,alt_name=%%,postcount=%%,posi=%%",
                                  $category['id'],$category['name'], translit($category['name']), $category['inc_postcount'],
                                  $category['position']);
        }


        $this->srcSQL->Query("SELECT * FROM forum_perms");
        $group_masks_ = $this->srcSQL->ResultArray();
        $group_masks = array();
        foreach ($group_masks_ as $group)
            $group_masks[] = $group['perm_id'];

        $this->Finish();
        $this->Start("Forums");

        $this->srcSQL->Query("SELECT * FROM forums WHERE parent_id != -1 ORDER BY id ASC");
        $forums = $this->srcSQL->ResultArray();
        $group_template = array();
        $group_names = array("read_forum", "read_theme", "creat_theme", "answer_theme", "upload_files", "download_files");
        $this->srcSQL->Query("SELECT * FROM groups ORDER BY g_id ASC");
        $groups = $this->srcSQL->ResultArray();

        foreach ($groups as $group)
        {
            $id = $group['g_id'];
            $group_template[$id] = array();
            foreach ($group_names as $name)
                $group_template[$id][$name] = 0;
        }

        foreach ($forums as $forum)
        {
            $sort_order = $forum['sort_order'] == 'Z-A' ? 'DESC' : 'ASC';
            $this->destSQL->Query("INSERT INTO forums SET id=%%,posi=%%,parent_id=%%,title=%%,alt_name=%%,description=%%,allow_bbcode=1,
                                allow_poll=%%,postcount=%%,topics_hiden=%%,posts_hiden=%%,last_post_member=%%,
                                last_post_member_id=%%,last_title=%%,sort_order=%%,posts=%%,rules=%%", $forum['id'],$forum['position'],
                                  $forum['parent_id'], $forum['name'], translit($forum['name']),
                                  $forum['description'], $forum['allow_poll'], $forum['inc_postcount'], $forum['queued_topics'],
                                  $forum['queued_posts'], $forum['last_poster_name'], $users_id[$forum['last_poster_id']],
                                  $forum['last_title'], $sort_order, $forum['posts'], $forum['rules_text']);
        }

        $this->Finish();
        $this->Start("Forum permissions");

        $this->srcSQL->Query("SELECT * FROM permission_index WHERE app='forums' AND perm_type='forum'");
        $perms = $this->srcSQL->ResultArray();
        foreach ($perms as $perm)
        {
            $forum_id = $perm['perm_type_id'];
            $poles = array(
                "perm_view" => "read_forum",
                "perm_2" => "read_theme",
                "perm_3" => "answer_theme",
                "perm_4" => "creat_theme",
                "perm_5" => "upload_files",
                "perm_6" => "download_files"
            );
            $mask_perms = array();
            foreach ($group_masks as $gr_mask)
            {
                $cur_mask = array();
                foreach ($poles as $old_pole => $new_pole)
                {
                    $cur_mask[$new_pole] = 0;
                    $value = $perm[$old_pole];
                    if ($value == "*")
                        $cur_mask[$new_pole] = 1;
                    else
                    {
                        $value = substr($value, 1);
                        $value = explode(",", $value);
                        foreach ($value as $c_value)
                            if ($c_value == $gr_mask) {
                                $cur_mask[$new_pole] = 1;
                                break;
                            }
                    }
                }

                $mask_perms[$gr_mask] = $cur_mask;
            }
            $result_perms = array();
            foreach ($group_template as $group_id => $value)
            {
                if ($group_id == 5) continue;
                $lb_group_id = (isset($un_groups_key[$group_id])) ? $un_groups_key[$group_id] : $group_id;
                $result_perms[$lb_group_id] = array();
                foreach ($poles as $new_pole)
                {
                    $result_perms[$lb_group_id][$new_pole] = 0;
                    $curgroup_masks = $groups_masks[$group_id];
                    if ($curgroup_masks)
                        foreach ($curgroup_masks as $mask)
                        {
                            $mask = $mask_perms[$mask];
                            $mask_value = $mask[$new_pole];
                            if ($mask_value == 1)
                                $result_perms[$lb_group_id][$new_pole] = 1;
                        }
                }
            }

            $this->destSQL->Query("UPDATE forums SET group_permission=%% WHERE id=%%", serialize($result_perms), $forum_id);
        }

        $this->Finish();
        $this->Start("Topics");

        while (true)
        {
            $this->srcSQL->LimitQuery("SELECT * FROM topics ORDER BY tid ASC");
            $topics = $this->srcSQL->ResultArray();
            if (!$topics) break;

            foreach ($topics as $topic)
            {
                $hiden = ($topic['approved'] == 1) ? 0 : 1;
                $this->destSQL->Query("INSERT INTO topics SET id=%%,forum_id=%%,title=%%,description=%%,date_open=%%,date_last=%%,
                                    status=%%,post_num=%%,post_hiden=%%,hiden=%%,fixed=%%,views=%%,last_post_member=%%,
                                    member_name_open=%%,member_id_open=%%,member_name_last=%%", $topic['tid'], $topic['forum_id'],
                                      $topic['title'], $topic['description'], $topic['start_date'], $topic['last_post'],
                                      $topic['state'], $topic['posts'], $topic['topic_queuedposts'], $hiden, $topic['pinned'],
                                      $topic['views'], $users_id[$topic['last_poster_id']], $topic['starter_name'],
                                      $users_id[$topic['starter_id']], $topic['last_poster_name']);
            }
        }

        $this->Finish();
        $this->Start("Posts");

        while (true)
        {
            $this->srcSQL->LimitQuery("SELECT * FROM posts ORDER BY pid ASC");
            $posts = $this->srcSQL->ResultArray();
            if (!$posts) break;
            foreach ($posts as $post)
            {
                $user_id = $users_id[$post['author_id']];
                $text = ipb_to_lb($post['post']);
                $post_member_name = $this->GetMemberName($user_id);
                $edit_member_id = $this->GetMemberId($post['edit_name']);
                $this->destSQL->Query("INSERT INTO posts SET pid=%%,topic_id=%%,new_topic=%%,text=%%,post_date=%%,edit_date=%%,
                                    post_member_id=%%,post_member_name=%%,ip=%%,hide=%%,edit_member_name=%%, edit_member_id=%%,
                                    edit_reason=%%", $post['pid'], $post['topic_id'], $post['new_topic'], $text, $post['post_date'],
                                      $post['edit_time'], $user_id, $post_member_name, $post['ip_address'], $post['queued'],
                                      $post['edit_name'], $edit_member_id, $post['post_edit_reason']);
                $posts_key[$post['post_key']] = $this->destSQL->InsertedId();
                $posts_attachment[$this->destSQL->InsertedId()] = 0;
            }
        }

        $this->Finish();

        while (true)
        {
            $this->srcSQL->LimitQuery("SELECT * FROM topics");
            $topics = $this->srcSQL->ResultArray();
            if (!$topics) break;

            foreach ($topics as $topic)
            {
                $first_post = $topic['topic_firstpost'];
                $topic_id = $topic['tid'];
                $this->destSQL->Query("UPDATE topics SET post_id=%% WHERE id=%%", $first_post, $topic_id);
                $this->destSQL->Query("SELECT * FROM posts WHERE topic_id=%%", $topic_id);
                $posts = $this->destSQL->ResultArray();
                $max_time = 0;
                $max_id = -1;
                foreach ($posts as $post)
                    if ($post['post_date'] > $max_time) {
                        $max_time = $post['post_date'];
                        $max_id = $post['pid'];
                    }
                $this->destSQL->Query("UPDATE topics SET last_post_id=%% WHERE id=%%", $max_id, $topic_id);
            }
        }
        $this->srcSQL->Query("SELECT * FROM forums WHERE parent_id != -1");
        $forums = $this->srcSQL->ResultArray();

        foreach ($forums as $forum)
        {
            $last_topic_id = $forum['last_id'];
            $forum_id =$forum['id'];
            $this->destSQL->Query("UPDATE forums SET last_topic_id=%% WHERE id=%%", $last_topic_id, $forum_id);
        }
        $this->destSQL->Query("SELECT * FROM forums WHERE parent_id != 0");
        $forums = $this->destSQL->ResultArray();
        foreach ($forums as $forum)
        {
            $this->destSQL->Query("SELECT * FROM topics WHERE forum_id='" . $forum['id'] . "'");
            $topics = $this->destSQL->ResultArray();
            foreach ($topics as $topic)
            {
                $this->destSQL->Query("SELECT * FROM posts WHERE topic_id='" . $topic['id'] . "'");
                $posts = $this->destSQL->ResultArray();
                $last_post_time = 0;
                $last_post_id = -1;
                foreach ($posts as $post)
                {
                    if ($post['post_date'] > $last_post_time) {
                        $last_post_time = $post['post_date'];
                        $last_post_id = $post['pid'];
                    }
                }
                $this->destSQL->Query("UPDATE forums SET last_post_date=%%,last_post_id=%% WHERE id=%%", $last_post_time,
                                      $last_post_id, $forum['id']);
            }
            $this->destSQL->Query("UPDATE forums SET topics=%% WHERE id=%%", count($topics), $forum['id']);
        }

        $this->Finish();
        $this->Start("Polls");


        $this->srcSQL->Query("SELECT * FROM polls");
        $polls = $this->srcSQL->ResultArray();

        foreach ($polls as $poll)
        {
            $topic_id = $poll['tid'];

            $choises_text = stripslashes($poll['choices']);
            $choises = unserialize(iconv("Windows-1251", "UTF-8", stripslashes($poll['choices'])));
            $choises = $choises[1];
            $variants = "";
            foreach ($choises['choice'] as $ind => $choice)
            {
                $variants .= $choice;
                if ($ind < count($choises['choice']))
                    $variants .= "\r\n";
            }

            $multiple = ($poll['poll_only'] == 1) ? 0 : 1;
            $answers = "";
            for ($i = 0; $i < count($choises['votes']); $i++)
                if ($choises['votes'][$i + 1] != 0) {
                    if ($answers != "")
                        $answers .= "|";
                    $answers .= $i . ":" . $choises['votes'][$i + 1];
                }
            $question = iconv("UTF-8", "Windows-1251", $choises['question']);
            $variants = iconv("UTF-8", "Windows-1251", $variants);
            $this->destSQL->Query("INSERT INTO topics_poll SET tid=%%,vote_num=%%,open_date=%%,title=%%,question=%%,multiple=%%,
                                variants=%%,answers=%%", $topic_id, $poll['votes'], $poll['start_date'], $poll['poll_question'],
                                  $question, $multiple, $variants, $answers);
            $this->destSQL->Query("UPDATE topics SET poll_id=%% WHERE id=%%", $this->destSQL->InsertedId(), $topic_id);
        }

        $this->Finish();
        $this->Start("Poll logs");

        while (true)
        {
            $this->srcSQL->LimitQuery("SELECT * FROM voters");
            $logs = $this->srcSQL->ResultArray();
            if (!$logs) break;
            foreach ($logs as $log)
            {
                $this->destSQL->Query("SELECT poll_id FROM topics WHERE id='" . $log['tid'] . "'");
                $poll_id = $this->destSQL->Result();
                $member_name = $this->GetMemberName($users_id[$log['member_id']]);
                $this->destSQL->Query("INSERT INTO topics_poll_logs SET poll_id=%%,ip=%%,log_date=%%,member_id=%%,
                                        member_name=%%", $poll_id, $log['ip_address'], $log['vote_date'],
                                      $users_id[$log['member_id']], $member_name);
            }
        }

        $this->Finish();
        $this->Start("Attachments");


        while (true)
        {
            $this->srcSQL->LimitQuery("SELECT * FROM attachments");
            $attachments = $this->srcSQL->ResultArray();
            if (!$attachments) break;
            foreach ($attachments as $attachment)
            {
                $post_id = $posts_key[$attachment['attach_post_key']];
                if (!$post_id)
                    continue;

                $this->destSQL->Query("SELECT topic_id FROM posts WHERE pid='$post_id'");
                $topic_id = $this->destSQL->Result();
                $this->destSQL->Query("SELECT forum_id FROM topics WHERE id='$topic_id'");
                $forum_id = $this->destSQL->Result();

                $file_mname = $this->GetMemberName($users_id[$attachment['attach_member_id']]);
                $file_type = $attachment['attach_is_image'] == 1 ? 'picture' : $attachment['attach_ext'];

                $this->destSQL->Query("INSERT INTO topics_files SET file_title=%%,file_name=%%,file_type=%%,file_mname=%%,
                                        file_mid=%%,file_date=%%,file_size=%%,file_fid=%%,file_tid=%%,file_pid=%%,file_count=%%,file_convert=1",
                                      $attachment['attach_file'], $attachment['attach_location'],
                                      $file_type, $file_mname, $users_id[$attachment['attach_member_id']],
                                      $attachment['attach_date'], $attachment['attach_filesize'], $forum_id, $topic_id,
                                      $post_id, $attachment['attach_hits']);

                $this->destSQL->Query("SELECT text FROM posts WHERE pid='$post_id'");
                $text = $this->destSQL->Result();
                if ($posts_attachment[$post_id] == 0) {
                    $text .= "<br/>";
                    $posts_attachment[$post_id] = 1;
                }
                $text .= "<br/>[attachment=" . $this->destSQL->InsertedId() . "]";
                //   $this->destSQL->Query("UPDATE posts SET text=%% WHERE pid=%%", $text, $post_id);
            }
        }

        while (true)
        {
            $this->destSQL->LimitQuery("SELECT * FROM posts");
            $posts = $this->destSQL->ResultArray();
            if (!$posts) break;
            foreach ($posts as $post)
            {
                preg_match_all('#\[attachment=(\d+):.+?\]#si', $post['text'], $files);
                $post_text = $post['text'];
                foreach ($files[1] as $file_id)
                {
                    $post_text = preg_replace('#\[attachment=' . $file_id . ':.+?\]#', '[attachment=' . $file_id . ']', $post_text);
                    $post_files[$post['pid']][] = $file_id;
                }
                $this->destSQL->Query("UPDATE posts SET text=%% WHERE pid=%%", $post_text, $post['pid']);
            }
        }
        while (true)
        {
            $this->destSQL->LimitQuery("SELECT * FROM topics_files");
            $files = $this->destSQL->ResultArray();
            if (!$files) break;
            foreach ($files as $file)
            {
                $file_id = $file['file_id'];
                $post_id = $file['file_pid'];
                $this->destSQL->Query("SELECT * FROM posts WHERE pid=%%", $post_id);
                $result = $this->destSQL->ResultArray();
                $text = $result[0]['text'];
                $old_text = $text;
                $attachments_text = $result[0]['attachments'];
                if ($posts_attachment[$post_id] == 0) {
                    $text .= "<br/>";
                    $posts_attachment[$post_id] = 1;
                }
                else if ($attachments_text != "")
                    $attachments_text .= ',';
                $attachments_text .= $file_id;
                $text .= "<br/>[attachment=" . $file_id . "]";
                if ((isset($post_files[$post_id]) && in_array($file_id, $post_files[$post_id])))
                    $text = $old_text;
                $this->destSQL->Query("UPDATE posts SET text=%%,attachments=%% WHERE pid=%%", $text, $attachments_text, $post_id);
            }
        }

        $this->Finish();
        $this->Start("Ranks");

        $this->srcSQL->Query("SELECT * FROM titles");
        $ranks = $this->srcSQL->ResultArray();

        foreach ($ranks as $rank)
            $this->destSQL->Query("INSERT INTO members_ranks SET title=%%,post_num=%%,stars=%%",
                                  $rank['title'], $rank['posts'], $rank['pips']);

        $this->Finish();
        $this->Start("Warning logs");

        while (true)
        {
            $this->srcSQL->LimitQuery("SELECT * FROM warn_logs");
            $logs = $this->srcSQL->ResultArray();
            if (!$logs) break;

            foreach ($logs as $log)
            {
                $mode = $log['wlog_type'] == "neg" ? -1 : 0;
                $text = $log['wlog_notes'];
                $moder_name = $this->GetMemberName($users_id[$log['wlog_addedby']]);
                preg_match('#<content>(.+?)</content>#si', $text, $text);
                $this->destSQL->Query("INSERT INTO members_warning SET mid=%%,moder_id=%%,moder_name=%%,date=%%,
                                       description=%%,st_w=%%", $users_id[$log['wlog_mid']], $users_id[$log['wlog_addedby']],
                                      $moder_name, $log['wlog_date'], $text[1], $mode);
            }
        }

        $this->Finish();

        $this->Start("Moderators");


        $this->srcSQL->Query("SELECT * FROM moderators");
        $moderators = $this->srcSQL->ResultArray();

        foreach ($moderators as $moder)
        {
            if ($moder['member_id'] != -1) {
                $member_id = $users_id[$moder['member_id']];
                $member_name = $this->GetMemberName($member_id);
                $group_id = $users_group[$member_id];
                $is_group = 0;
            }
            else
            {
                $member_id = 0;
                $member_name = "";
                if ($moder['group_id'] == '5') continue;
                $group_id = isset($groups_key[$moder['group_id']]) ? $groups_key[$moder['group_id']] : $moder['group_id'];
                $is_group = 1;
            }

            $hide = ($moder['topic_q'] == 1 && $moder['post_q'] == 1) ? 1 : 0;

            $permissions = array();
            $permissions['global_hideshow'] = $hide;
            $permissions['global_hidetopic'] = $hide;
            $permissions['global_deltopic'] = $moder['delete_topic'];
            $permissions['global_titletopic'] = $moder['edit_topic'];
            $permissions['global_polltopic'] = $moder['edit_post'];
            $permissions['global_opentopic'] = $moder['open_topic'];
            $permissions['global_closetopic'] = $moder['close_topic'];
            $permissions['global_fixtopic'] = $moder['pin_topic'];
            $permissions['global_unfixtopic'] = $moder['unpin_topic'];
            $permissions['global_movetopic'] = $moder['move_topic'];
            $permissions['global_uniontopic'] = $moder['split_merge'];
            $permissions['global_delpost'] = $moder['delete_post'];
            $permissions['global_unionpost'] = $moder['edit_post'];
            $permissions['global_changepost'] = $moder['edit_post'];
            $permissions['global_movepost'] = $moder['edit_post'];
            $permissions['global_fixedpost'] = $moder['edit_post'];


            $this->destSQL->Query("INSERT INTO forums_moderator SET fm_forum_id=%%,fm_member_id=%%,fm_member_name=%%,
                                   fm_group_id=%%,fm_is_group=%%,fm_permission=%%", $moder['forum_id'],
                                  $member_id, $member_name, $group_id, $is_group, serialize($permissions));

        }

        $this->Finish();
        $this->Start("Subscribe");

        while (true)
        {
            $this->srcSQL->LimitQuery("SELECT * FROM tracker");
            $subscribes = $this->srcSQL->ResultArray();
            if (!$subscribes) break;
            foreach ($subscribes as $subscribe)
                $this->destSQL->Query("INSERT INTO topics_subscribe SET subs_member=%%,topic=%%,date=%%",
                                      $users_id[$subscribe['member_id']], $subscribe['topic_id'],
                                      $subscribe['start_date']);
        }
        $this->Finish();

    }
}

?>