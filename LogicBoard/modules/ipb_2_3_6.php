<?php

require_once "EngineBase.php";
require_once "include/ipb_parser.php";

class IPB_2_3_6 extends EngineBase
{
    protected $setup = array(
    "caption" => "Invision Power Board ver. 2.3.6",
    "id" => "ipb_2_3_6",
    "db_prefix" => "ipb_",
    "add_text" => "Пароли пользователей не перенесутся. Каждому пользователю надо будет сделать запрос на восстановление
        пароля на электронную почту",
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

    public function convert()
    {
               $this->Start("Install");
               $this->InstallLB();
               $this->Finish();


        $groups_key = array("4" => "1", "6" => "2", "3" => "4", "2" => "5", "1" => "6");
        $groups_masks = $users_id = $forums_id = $topics_id = $posts_id = $posts_key = $posts_attachment = $users_ban = $users_group = array();
        
        $this->Start("Groups");
        
        $this->srcSQL->Query("SELECT * FROM groups");
        $groups = $this->srcSQL->ResultArray();
        foreach ($groups as $group)
        {
            if ($group['g_id'] == '5') continue;
            $lb_group_id = isset($groups_key[$group['g_id']]) ? $groups_key[$group['g_id']] : $group['g_id'];

            $access = array();
            $access['local_opentopic'] = $group['g_open_close_posts'];
            $access['local_closetopic'] = $group['g_open_close_posts'];
            $access['local_deltopic'] = $group['g_delete_own_topics'];
            $access['local_titletopic'] = $group['g_edit_topic'];
            $access['local_polltopic'] = $group['g_edit_topic'];
            $access['local_delpost'] = $group['g_delete_own_posts'];
            $access['local_changepost'] = $group['g_edit_posts'];

            $this->destSQL->Query("INSERT INTO groups SET g_id=%,g_title=%,g_prefix_st=%,g_supermoders=%,g_access_cc=%,
            g_show_close_f=%,g_access=%,g_show_profile=%,g_new_topic=%,g_reply_topic=%,g_reply_close=%,g_pm=%,g_maxpm=%,
            g_search=%,g_prefix_end=%", $lb_group_id, $group['g_title'],$group['g_is_supmod'],$group['g_access_cp'],
                                  $group['g_access_offline'],serialize($access),$group['g_mem_info'],
                                  $group['g_post_new_topics'],$group['g_reply_other_topics'],$group['g_post_closed'],
                                  $group['g_use_pm'],$group['g_max_messages'],$group['g_use_search'],$group['suffix']);
            $groups_masks[$group['g_id']] = explode(',', $group['g_perm_id']);
        }

        $this->Finish();
        $this->Start("Users");
        while (true)
        {            
            $this->srcSQL->LimitQuery("SELECT * FROM members");
            $users = $this->srcSQL->ResultArray();
            if (!$users) break;
            foreach ($users as $user)
            {
                if ($user['mgroup'] == 5) continue;
                $group = (isset($groups_key[$user['mgroup']])) ? $groups_key[$user['mgroup']] : $user['mgroup'];

                $this->destSQL->Query("INSERT INTO members SET name=%,email=%,reg_date=%,member_group=%,password=%,
                                        secret_key='',ip=%,personal_title=%,reg_status=1,lastdate=%,b_day=%,b_month=%,
                                        b_year=%,count_warning=%,posts_num=%",$user['name'], $user['email'],
                                      $user['join'], $group, $user['member_login_key'], $user['ip_address'],
                                      $user['title'], $user['last_visit'], $user['bday_day'], $user['bday_year'],
                                      $user['warn_level'],$user['posts']);
                $users_id[$user['id']] = $this->destSQL->InsertedId();
                $users_group[$this->destSQL->InsertedId()] = $group;

                if ($user['temp_ban']!="")
                    $users_ban[$this->destSQL->InsertedId()] = $user['temp_ban'];
            }
        }
        while (true)
        {
            $this->srcSQL->LimitQuery("SELECT * FROM member_extra");
            $users_extra = $this->srcSQL->ResultArray();
            if (!$users_extra) break;
            foreach ($users_extra as $info)
                $this->destSQL->Query("UPDATE members SET icq=%,signature=%,town=% WHERE member_id=%",
                                      $info['icq_number'],$info['signature'], $info['location'], $users_id[$info['id']]);
        }
        while (true)
        {
            $this->srcSQL->LimitQuery("SELECT * FROM profile_portal");
            $users_extra = $this->srcSQL->ResultArray();
            if (!$users_extra) break;
            foreach ($users_extra as $info)
            {
                if ($info['gender'] == 'male') $sex = 1;
                else if ($info['gender'] == 'female') $sex = 2;
                else $sex = 0;
                $this->destSQL->Query("UPDATE members SET about=%,sex=% WHERE member_id=%",
                                      $info['pp_bio_content'],$sex,$users_id[$info['pp_member_id']]);
            }
        }

        $this->Finish();
        $this->Start("Categories");
        
        $this->srcSQL->Query("SELECT * FROM forums WHERE parent_id=-1");
        $categories = $this->srcSQL->ResultArray();
        foreach ($categories as $category)
        {
            $this->destSQL->Query("INSERT INTO forums SET parent_id=0,title=%,alt_name=%,postcount=%,posi=%",
                                  $category['name'],translit($category['name']),$category['inc_postcount'],
                                  $category['position']);
            $forums_id[$category['id']] = $this->destSQL->InsertedId();
        }       
        $this->srcSQL->Query("SELECT * FROM forum_perms");
        $group_masks_ = $this->srcSQL->ResultArray();
        $group_masks = array();
        foreach ($group_masks_ as $group)
            $group_masks[] = $group['perm_id'];


        $this->Finish();
        $this->Start("Forums");
        
        $this->srcSQL->Query("SELECT * FROM forums WHERE parent_id!=-1 ORDER BY id ASC");
        $forums = $this->srcSQL->ResultArray();

        $group_template = array();
        $group_names = array("read_forum", "read_theme", "creat_theme", "answer_theme", "upload_files", "download_files");
        $this->destSQL->Query("SELECT * FROM groups ORDER BY g_id ASC");
        $groups = $this->destSQL->ResultArray();
        foreach ($groups as $group)
        {
            $id = $group['g_id'];
            $group_template[$id] = array();
            foreach ($group_names as $name)
                $group_template[$id][$name] = 0;
        }
        foreach ($forums as $forum)
        {
            $old_permissions = unserialize(str_replace('\\', '', $forum['permission_array']));
            $permissions = $group_template;
            $mask_permissions = array();
            foreach ($group_masks as $mask_id)
            {
                $mask_permissions[$mask_id] = array();
                foreach ($old_permissions as $item_key => $item_value)
                    $mask_permissions[$mask_id][$item_key] = 0;
            }
            foreach ($old_permissions as $key => $value)
            {
                if (strlen($value) == 0)
                    continue;
                if ($value == "*") {
                    $value = "";
                    foreach ($group_masks as $group)
                        $value .= $group . ",";
                    $value = substr($value, 0, -1);
                }
                $value = explode(",", $value);
                foreach ($value as $item)
                    $mask_permissions[$item][$key] = 1;
            }
            foreach ($groups_masks as $group_id => $group_value)
            {
                if ($group_id == 5) continue;
                $lb_group_id = (isset($groups_key[$group_id])) ? $groups_key[$group_id] : $group_id;
                foreach ($groups_masks[$group_id] as $mask_id)
                {
                    $mask = $mask_permissions[$mask_id];
                    foreach ($mask as $mask_key => $mask_value)
                    {
                        if ($mask_value == 0)
                            continue;
                        if ($mask_key == "start_perms")
                            $permissions[$lb_group_id]['creat_theme'] = 1;
                        if ($mask_key == "reply_perms")
                            $permissions[$lb_group_id]['answer_theme'] = 1;
                        if ($mask_key == "read_perms")
                            $permissions[$lb_group_id]['read_theme'] = 1;
                        if ($mask_key == "upload_perms")
                            $permissions[$lb_group_id]['upload_files'] = 1;
                        if ($mask_key == "download_perms")
                            $permissions[$lb_group_id]['download_files'] = 1;
                        if ($mask_key == "show_perms")
                            $permissions[$lb_group_id]['read_forum'] = 1;

                    }
                }
            }
            foreach ($permissions[3] as $key => $value)
                $permissions[3][$key] = 1;
            $sort_order = $forum['sort_order'] == 'Z-A' ? 'DESC' : 'ASC';
            $this->destSQL->Query("INSERT INTO forums SET posi=%,parent_id=%,title=%,alt_name=%,description=%,
                    allow_bbcode=1,allow_poll=%,postcount=%,topics_hiden=%,posts_hiden=%,group_permission=%,
                    last_post_member=%,last_post_member_id=%,last_title=%,sort_order=%,posts=%,rules=%",
                                  $forum['position'],$forums_id[$forum['parent_id']],$forum['name'],translit($forum['name']),
                                  $forum['description'],$forum['allow_poll'],$forum['inc_postcount'],$forum['queued_topics'],
                                  $forum['queued_posts'],serialize($permissions),$forum['last_poster_name'],
                                  $users_id[$forum['last_poster_id']],$forum['last_title'],$sort_order,$forum['posts'],
                                  $forum['rules_text']);
            $forums_id[$forum['id']] = $this->destSQL->InsertedId();
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
                $this->destSQL->Query("INSERT INTO topics SET forum_id=%,title=%,description=%,date_open=%,date_last=%,
                                       status=%,post_num=%,post_hiden=%,hiden=%,fixed=%,views=%,last_post_member=%,
                                       member_name_open=%,member_id_open=%,member_name_last=%",
                                      $forums_id[$topic['forum_id']],$topic['title'],$topic['description'],
                                      $topic['start_date'],$topic['last_post'],$topic['state'],$topic['posts'],
                                      $topic['topic_queuedposts'],$topic['approved'],$topic['pinned'],$topic['views'],
                                      $topic['last_poster_id'],$topic['starter_name'],$topic['starter_id'],
                                      $topic['last_poster_name']);
                $topics_id[$topic['tid']] = $this->destSQL->InsertedId();
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
                $this->destSQL->Query("INSERT INTO posts SET topic_id=%,new_topic=%,text=%,post_date=%,edit_date=%,
                                    post_member_id=%,post_member_name=%,ip=%,hide=%,edit_member_name=%,edit_member_id=%,
                                    edit_reason=%",$topics_id[$post['topic_id']],$post['new_topic'],$text,
                                      $post['post_date'],$post['edit_time'],$user_id,$post_member_name,
                                      $post['ip_address'],$post['queued'],$post['edit_name'],$edit_member_id,
                                      $post['post_edit_reason']);
                $posts_id[$post['pid']] = $posts_key[$post['post_key']] = $this->destSQL->InsertedId();
                $posts_attachment[$this->destSQL->InsertedId()] = 0;
            }
        }

        $this->Finish();
        $this->Start("Update posts, topics, forums");

        while (true)
        {
            $this->srcSQL->LimitQuery("SELECT * FROM topics");
            $topics = $this->srcSQL->ResultArray();
            if (!$topics) break;
            foreach ($topics as $topic)
            {
                $first_post = $posts_id[$topic['topic_firstpost']];
                $topic_id = $topics_id[$topic['tid']];
                $this->destSQL->Query("UPDATE topics SET post_id=% WHERE id=%", $first_post, $topic_id);
                $this->destSQL->Query("SELECT * FROM posts WHERE topic_id=%", $topic_id);
                $posts = $this->destSQL->ResultArray();
                $max_time = 0;
                $max_id = -1;
                foreach ($posts as $post)
                    if ($post['post_date'] > $max_time) {
                        $max_time = $post['post_date'];
                        $max_id = $post['pid'];
                    }
                $this->destSQL->Query("UPDATE topics SET last_post_id=% WHERE id=%", $max_id, $topic_id);
            }
        }
        $this->srcSQL->Query("SELECT * FROM forums WHERE parent_id!=-1");
        $forums = $this->srcSQL->ResultArray();
        foreach ($forums as $forum)
        {
            $last_topic_id = $topics_id[$forum['last_id']];
            $forum_id = $forums_id[$forum['id']];
            $this->destSQL->Query("UPDATE forums SET last_topic_id=% WHERE id=%",$last_topic_id,$forum_id);
        }
        $this->destSQL->Query("SELECT * FROM forums WHERE parent_id!=0");
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
                $this->destSQL->Query("UPDATE forums SET last_post_date=%, last_post_id=% WHERE id=%",$last_post_time,
                                        $last_post_id,$forum['id']);
            }
            $this->destSQL->Query("UPDATE forums SET topics=% WHERE id=%", count($topics), $forum['id']);
        }

        $this->Finish();
        $this->Start("Polls");


        $this->srcSQL->Query("SELECT * FROM polls");
        $polls = $this->srcSQL->ResultArray();

        foreach ($polls as $poll)
        {
            $topic_id = $topics_id[$poll['tid']];
            $choises_text = stripslashes($poll['choices']);
            $choises = unserialize($choises_text);
            $choises = $choises[1];
            $variants = "";
            foreach ($choises['choice'] as $ind=>$choice)
            {
                $variants .= $choice;
                if($ind < count($choises['choice']))
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
            $this->destSQL->Query("INSERT INTO topics_poll SET tid=%,vote_num=%,open_date=%,title=%,question=%,multiple=%,
                                variants=%,answers=%", $topic_id, $poll['votes'], $poll['start_date'], $poll['poll_question'],
                                  $choises['question'], $multiple, $variants, $answers);
            $this->destSQL->Query("UPDATE topics SET poll_id=% WHERE id=%", $this->destSQL->InsertedId(), $topic_id);
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
                $this->destSQL->Query("SELECT poll_id FROM topics WHERE id='" . $topics_id[$log['tid']] . "'");
                $poll_id = $this->destSQL->Result();
                $member_name = $this->GetMemberName($users_id[$log['member_id']]);
                $this->destSQL->Query("INSERT INTO topics_poll_logs SET poll_id=%,ip=%,log_date=%,member_id=%,
                                        member_name=%", $poll_id, $log['ip_address'],$log['vote_date'],
                                        $users_id[$log['member_id']],$member_name);
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
                $file_type = is_image(".".$attachment['attach_ext']) ? "picture" : "file";

                $this->destSQL->Query("INSERT INTO topics_files SET file_title=%,file_name=%,file_type=%,file_mname=%,
                                        file_mid=%,file_date=%,file_size=%,file_fid=%,file_tid=%,file_pid=%,file_convert=1",
                                      $attachment['attach_file'], $attachment['attach_location'],
                                      $file_type, $file_mname, $users_id[$attachment['attach_member_id']],
                                      $attachment['attach_date'], $attachment['attach_filesize'], $forum_id, $topic_id,
                                      $post_id);

                $this->destSQL->Query("SELECT text FROM posts WHERE pid='$post_id'");
                $text = $this->destSQL->Result();
                if ($posts_attachment[$post_id] == 0) {
                    $text .= "<br/>";
                    $posts_attachment[$post_id] = 1;
                }
                $text .= "<br/>[attachment=" . $this->destSQL->InsertedId() . "]";
               // $this->destSQL->Query("UPDATE posts SET text=% WHERE pid=%", $text, $post_id);
            }
        }
        while (true)
        {
            $this->destSQL->LimitQuery("SELECT * FROM posts");
            $posts = $this->destSQL->ResultArray();
            if (!$posts) break;
            foreach ($posts as $post)
            {
                $post_id = $post['pid'];
                $this->destSQL->Query("SELECT * FROM topics_files WHERE file_pid=%",$post_id);
                $files = $this->destSQL->ResultArray();
                $text = '';
                foreach ($files as $file)
                    $text .= $file['file_id'] . ",";
                if (strlen($text) > 0)
                    $text = substr($text, 0, -1);
                $this->destSQL->Query("UPDATE posts SET attachments=% WHERE pid=%",$text,$post['pid']);
            }
        }

        $this->Finish();
        $this->Start("Ranks");

        $this->srcSQL->Query("SELECT * FROM titles");
        $ranks = $this->srcSQL->ResultArray();

        foreach ($ranks as $rank)
            $this->destSQL->Query("INSERT INTO members_ranks SET title=%,post_num=%,stars=%",
                                    $rank['title'],$rank['posts'],$rank['pips']);

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
                preg_match('#<content>(.+?)</content>#sui', $text, $text);
                $this->destSQL->Query("INSERT INTO members_warning SET mid=%,moder_id=%,moder_name=%,date=%,
                                       description=%,st_w=%",$users_id[$log['wlog_mid']],$users_id[$log['wlog_addedby']],
                                       $moder_name, $log['wlog_date'],$text[1], $mode);
            }
        }

        $this->Finish();
        $this->Start("Ban Filters");
        
        $this->srcSQL->Query("SELECT * FROM banfilters");
        $filters = $this->srcSQL->ResultArray();
        foreach ($filters as $filter)
            $this->destSQL->Query("INSERT INTO members_banfilters SET type=%,description=%,date=%",
                                  $filter['ban_type'],$filter['ban_content'],$filter['ban_date']);

        $this->Finish();
        $this->Start("Ban members");

        foreach ($users_ban as $user_id => $info)
        {
            $info = explode(":", $info);
            $days = $info[3] == "h" ? 1 : $info[2];
            $description = $this->GetMemberName($user_id);
            $this->destSQL->Query("INSERT INTO members_banfilters SET type='name',description=%,date=%,date_end=%,
                                    ban_days=%,ban_member_id=%", $description, $info[0], $info[1], $days, $user_id);
        }

        $this->Finish();
        $this->Start("Moderators");

        $this->srcSQL->Query("SELECT * FROM moderators");
        $moderators = $this->srcSQL->ResultArray();
        foreach ($moderators as $moder)
        {
            if ($moder['member_id']!=-1) {
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


            $this->destSQL->Query("INSERT INTO forums_moderator SET fm_forum_id=%,fm_member_id=%,fm_member_name=%,
                                   fm_group_id=%,fm_is_group=%,fm_permission=%",$forums_id[$moder['forum_id']],
                                   $member_id, $member_name, $group_id, $is_group,serialize($permissions));
        }

        $this->Finish();
        $this->Start("Subscribe");

        while (true)
        {
            $this->srcSQL->LimitQuery("SELECT * FROM tracker");
            $subscribes = $this->srcSQL->ResultArray();
            if (!$subscribes) break;
            foreach ($subscribes as $subscribe)
                $this->destSQL->Query("INSERT INTO topics_subscribe SET subs_member=%,topic=%,date=%",
                                      $users_id[$subscribe['member_id']],$topics_id[$subscribe['topic_id']],
                                      $subscribe['start_date']);
        }
        $this->Finish();
    }
}

?>
