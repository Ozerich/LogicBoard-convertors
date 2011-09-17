<?php

require_once "EngineBase.php";
require_once "include/ipb_parser.php";

class LB_2_0 extends EngineBase
{
    protected $setup = array(
        "caption" => "LogicBoard ver 2.0",
        "id" => "lb_2_0",
        "db_prefix" => "LB_",
        "add_text" => array("При конвертации будет осуществлён перенос пользователей из форума в базу данных сайта, всвязи с этим некоторым пользователям потребуется восстановить пароль, используя адрес своей почты (имеено почты, а не логина), т.к. в некоторых случаях логин пользователя будет изменён (при необходимости в АЦ сайта Вы сможете сменить логин пользователю на любой другой).
Создайте на сайте новость с данным предупреждением для Ваших пользователей.","Права групп перенесены будут не все. Пожалуйста, отредакртируйте их после конвертации в панели управления","После конвертации не забудьте очистить кеш форума, иначе новые форумы не появятся."),
        "dle_based" => false,
    );


    private function GetMemberId($name)
    {
        $this->dleSQL->Query("SELECT user_id FROM users WHERE name=%%", $name);
        return $this->dleSQL->Result(0);
    }

    private function GetMemberName($id)
    {
        $this->dleSQL->Query("SELECT name FROM users WHERE user_id=%%", $id);
        return $this->dleSQL->Result(iconv("UTF-8", "Windows-1251", "Удалён"));
    }

    public function convert($options)
    {
        $this->Start("Install");
        $this->InstallLB($options);
        $this->Finish();

        $this->Start("Groups");

        $groups_id = array("0" => "0", "1" => "1", "2" => "2", "3" => "3", "4" => "4", "5" => "5");

        $this->srcSQL->Query("SELECT * FROM groups");
        $groups = $this->srcSQL->ResultArray();
        foreach ($groups as $group)
            if ($group['g_id'] > 6) {
                $this->dleSQL->Query("INSERT INTO usergroups SET group_name=%%,allow_cats='all',cat_add='all'", $group['g_title']);
                $groups_id[$group['g_id']] = $this->dleSQL->InsertedId();
            }

        $this->srcSQL->Query("SELECT * FROM groups");
        $groups = $this->srcSQL->ResultArray();
        foreach ($groups as $group)
        {
            if ($group['g_id'] == 6) continue;
            $dest_group = $groups_id[$group['g_id']];
            $this->destSQL->Query("INSERT INTO groups SET g_id=%%,g_title=%%, g_prefix_st=%%, g_prefix_end=%%, g_icon=%%,
                g_access=%%,g_supermoders = %%, g_access_cc =%%, g_show_online = %%, g_new_topic =%%, g_reply_topic = %%, 
                g_reply_close = %%, g_warning = %%, g_show_hiden = %%, g_show_close_f = %%, g_hide_text = %%, g_signature = %%,
                g_search = %%, g_status = %%, g_link_forum = %%", $dest_group, $group['g_title'], $group['g_prefix_st'],
                                  $group['g_prefix_end'], $group['g_icon'], $group['g_access'], $group['g_supermoders'], $group['g_access_cc'],
                                  $group['g_show_online'], $group['g_new_topic'], $group['g_reply_topic'], $group['g_reply_close'], $group['g_warning'],
                                  $group['g_show_hiden'], $group['g_show_close_f'], $group['g_hide_text'], $group['g_signature'], $group['g_search'],
                                  $group['g_status'], $group['g_link_forum']);
        }

        $this->Finish();

        $this->Start("Users");

        $dle_user_names = $dle_user_emails = $dle_user_ids = $user_reps = $names = array();
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
        $names[''] = '';
        $users_id[0] = 0;
        $users_id[''] = 0;
        while (true)
        {
            $this->srcSQL->LimitQuery("SELECT * FROM members");
            $users = $this->srcSQL->ResultArray();
            if (!$users) break;
            foreach ($users as $user)
            {
                if ($user['member_group'] == 5 || $user['member_group'] == 6)
                    continue;

                $user_email = strtolower($user['email']);
                $user_name = strtolower($user['name']);
                $user_group = $groups_id[$user['member_group']];

                if (in_array($user_email, $dle_user_emails)) {
                    $this->dleSQL->Query("UPDATE users SET user_group=%%,lb_favorite=%%,lb_subscribe=%%,mf_options=%%,count_warning=%%,
                     mstatus=%%,personal_title=%%,topics_num=%%,posts_num=%%,secret_key=%%,lb_twitter=%%,lb_vkontakte=%%,
                     lb_skype=%%,lb_sex=%%,lb_limit_publ=%%,lb_b_day=%%,lb_b_month=%%,lb_b_year=%% WHERE user_id=%%",
                                         $user_group,
                                         $user['favorite'], $user['subscribe'], $user['mf_options'], $user['count_warnings'], $user['mstatus'],
                                         $user['personal_title'], $user['topics_num'], $user['posts_num'], $user['secret_key'], $user['twitter'],
                                         $user['vkontakte'], $user['skype'], $user['sex'], $user['limit_publ'], $user['b_day'], $user['b_month'],
                                         $user['b_year'], $dle_user_ids[$user['email']]);
                    $names[$user['name']] = $this->GetMemberName($dle_user_ids[$user['email']]);
                    $users_id[$user['member_id']] = $dle_user_ids[$user['email']];
                    $users_group[$dle_user_ids[$user['name']]] = $user_group;
                    continue;
                }
                $name = in_array($user_name, $dle_user_names) ? "lbuser_" . substr(md5(rand()), 0, 6) : $user['name'];
                $this->dleSQL->Query("INSERT INTO users SET name=%%, email=%%, password=%%,user_group=%%,lb_favorite=%%,lb_subscribe=%%,mf_options=%%,count_warning=%%,
                     mstatus=%%,personal_title=%%,topics_num=%%,posts_num=%%,secret_key=%%,lb_twitter=%%,lb_vkontakte=%%,
                     lb_skype=%%,lb_sex=%%,lb_limit_publ=%%,lb_b_day=%%,lb_b_month=%%,lb_b_year=%%, lastdate=%%,
                      reg_date=%%",
                                     $name, $user['email'], $user['password'], $user_group,
                                     $user['favorite'], $user['subscribe'], $user['mf_options'], $user['count_warnings'], $user['mstatus'],
                                     $user['personal_title'], $user['topics_num'], $user['posts_num'], $user['secret_key'], $user['twitter'],
                                     $user['vkontakte'], $user['skype'], $user['sex'], $user['limit_publ'], $user['b_day'],
                                      $user['b_month'],$user['b_year'],$user['lastdate'],$user['reg_date']);
                $names[$user['name']] = $name;
                $users_id[$user['member_id']] = $this->dleSQL->InsertedId();
                $users_group[$this->dleSQL->InsertedId()] = $user_group;
            }
        }
        $this->dleSQL->ResetLimit();
        while (true)
        {
            $this->dleSQL->LimitQuery("SELECT * FROM users");
            $users = $this->dleSQL->ResultArray();
            if (!$users) break;
            foreach ($users as $user)
            {
                $user_reps[$user['name']] = array("level" => 0, "plus" => 0, "minus" => 0);
                $user_reps[$user['name']]['level'] = $user['repa'];
                $user_reps[$user['name']]['plus'] = substr($user['repa_mod'], 0, strpos($user['repa_mod'], "|"));
                $user_reps[$user['name']]['minus'] = substr($user['repa_mod'], strpos($user['repa_mod'], "|") + 1);
            }
        }

        $this->Finish();

        $this->Start("Categories & Forums");

        $this->destSQL->InsertTable("forums", $this->srcSQL);

        while (true)
        {
            $this->destSQL->LimitQuery("SELECT * FROM forums");
            $forums = $this->destSQL->ResultArray();
            if (!$forums) break;
            foreach ($forums as $forum)
                $this->destSQL->Query("UPDATE forums SET last_post_member_id=%%, last_post_member=%% WHERE id=%%",
                                      $users_id[$forum['last_post_member_id']],$names[$forum['last_post_member']], $forum['id']);
        }
        $this->Finish();

        $this->Start("Topics");
        $this->destSQL->InsertTable("topics", $this->srcSQL);

        while (true) {
            $this->destSQL->LimitQuery("SELECT * FROM topics");
            $topics = $this->destSQL->ResultArray();
            if (!$topics) break;
            foreach ($topics as $topic)
                $this->destSQL->Query("UPDATE topics SET last_post_member=%%, member_id_open=%%,
                        member_name_open=%%, member_name_last=%% WHERE id=%%",
                                      $users_id[$topic['last_post_member']], $users_id[$topic['member_id_open']],
                                      $names[$topic['member_name_open']], $names[$topic['member_name_last']],
                                      $topic['id']);
        }
        $this->Finish();

        $this->Start("Posts");
        $this->destSQL->InsertTable("posts", $this->srcSQL);
        while (true) {
            $this->destSQL->LimitQuery("SELECT * FROM posts");
            $posts = $this->destSQL->ResultArray();
            if (!$posts) break;
            foreach ($posts as $post)
                $this->destSQL->Query("UPDATE posts SET post_member_id=%%, edit_member_id=%%,moder_member_id=%%,
                    	post_member_name=%%, edit_member_name=%%, moder_member_name=%% WHERE pid=%%",
                                      $users_id[$post['post_member_id']], $users_id[$post['edit_member_id']],
                                                          $users_id[$post['moder_member_id']],
                                    $names[$post['post_member_name']], $names[$post['edit_member_name']],
                    $names[$post['moder_member_name']],
$post['pid']);
        }
        $this->Finish();

        $this->Start("Polls");
        $this->destSQL->InsertTable("topics_poll", $this->srcSQL);
        $this->destSQL->InsertTable("topics_poll_logs", $this->srcSQL);
        while (true)
        {
            $this->destSQL->LimitQuery("SELECT * FROM topics_poll_logs");
            $logs = $this->destSQL->ResultArray();
            if (!$logs) break;
            foreach ($logs as $log)
                $this->destSQL->Query("UPDATE topics_poll_logs SET member_id=%%, member_name=%% WHERE id=%%",
                                      $users_id[$log['member_id']], $names[$log['member_name']], $log['id']);
        }
        $this->Finish();

        $this->Start("Member ranks");
        $this->destSQL->InsertTable("members_ranks", $this->srcSQL);
        $this->Finish();

        $this->Start("Advertise");
        $this->destSQL->Query("TRUNCATE TABLE ".$this->destSQL->db_prefix."adtblock");
        $this->destSQL->InsertTable("adtblock", $this->srcSQL);
        $this->Finish();

        $this->Start("Statuses");
        $this->destSQL->InsertTable("members_status", $this->srcSQL);
        while (true)
        {
            $this->destSQL->LimitQuery("SELECT * FROM members_status");
            $statuses = $this->destSQL->ResultArray();
            if (!$statuses) break;
            foreach ($statuses as $status)
                $this->destSQL->Query("UPDATE members_status SET member_id=%% WHERE id=%%",
                                      $users_id[$status['member_id']], $status['id']);
        }
        $this->Finish();

        $this->Start("Forum notices");
        $this->destSQL->InsertTable("forums_notice", $this->srcSQL);
        while (true)
        {
            $this->destSQL->LimitQuery("SELECT * FROM forums_notice");
            $notices = $this->destSQL->ResultArray();
            if (!$notices) break;
            foreach ($notices as $notice)
                $this->destSQL->Query("UPDATE forums_notice SET author_id=%%, author=%% WHERE id=%%",
                                      $users_id[$notice['author_id']], $names[$notice['author']], $notice['id']);
        }
        $this->Finish();

        $this->Start("Warnings");
        $this->destSQL->InsertTable("members_warning", $this->srcSQL);
        while (true)
        {
            $this->destSQL->LimitQuery("SELECT * FROM members_warning");
            $warnings = $this->destSQL->ResultArray();
            if (!$warnings) break;
            foreach ($warnings as $warning)
                $this->destSQL->Query("UPDATE members_warning SET mid=%%,moder_id=%%,moder_name=%% WHERE id=%%",
                                      $users_id[$warning['mid']], $users_id[$warning['moder_id']],
                                      $names[$warning['moder_name']], $warning['id']);
        }
        $this->Finish();

        $this->Start("Moderators");
        $this->destSQL->InsertTable("forums_moderator", $this->srcSQL);

        $this->destSQL->Query("SELECT * FROM forums_moderator");
        $moders = $this->destSQL->ResultArray();
        foreach ($moders as $moder)
            $this->destSQL->Query("UPDATE forums_moderator SET fm_member_id=%%,fm_group_id=%%,fm_member_name=%%
             WHERE fm_id=%%",
                                  $users_id[$moder['fm_member_id']], $groups_id[$moder['fm_group_id']],
                                  $names[$moder['fm_member_name']], $moder['fm_id']);
        $this->Finish();


        $this->Start("Bans");
        $this->srcSQL->Query("SELECT * FROM members_banfilters");
        $bans = $this->srcSQL->ResultArray();
        foreach ($bans as $ban)
        {
            if ($ban['type'] == "name")
                $this->dleSQL->Query("INSERT INTO banned SET users_id=%%,descr=%%,date=%%,days=%%,ip=%%",
                                     $users_id[$ban['ban_member_id']], $names[$ban['moder_desc']], $ban['date'], $ban['ban_days'], '');
            else if ($ban['type'] == "ip")
                $this->dleSQL->Query("INSERT INTO banned SET users_id=%%,descr=%%,date=%%,days=%%,ip=%%",
                                     $users_id[$ban['ban_member_id']], $ban['moder_desc'], $ban['date'], $ban['ban_days'],
                                     $ban['description']);
        }

        $this->Finish();

        $this->Start("Subscribes");
        $this->destSQL->InsertTable("topics_subscribe", $this->srcSQL);
        while (true)
        {
            $this->destSQL->LimitQuery("SELECT * FROM topics_subscribe");
            $subscribes = $this->destSQL->ResultArray();
            if (!$subscribes) break;
            foreach ($subscribes as $subscribe)
                $this->destSQL->Query("UPDATE topics_subscribe SET subs_member=%% WHERE id=%%",
                                      $users_id[$subscribe['subs_member']], $subscribe['id']);
        }
        $this->Finish();

        $this->Start("Files");
        $this->destSQL->InsertTable("topics_files", $this->srcSQL);
        while (true)
        {
            $this->destSQL->LimitQuery("SELECT * FROM topics_files");
            $files = $this->destSQL->ResultArray();
            if (!$files) break;
            foreach ($files as $file)
                $this->destSQL->Query("UPDATE topics_files SET file_mid=%%,file_mname=%% WHERE file_id=%%",
                                      $users_id[$file['file_mid']], $names[$file['file_mname']], $file['file_id']);
        }
        $this->Finish();
        if (in_array("rep_mod", $options)) {
            $this->Start("Reputation");

            while (true)
            {

                $this->srcSQL->LimitQuery("SELECT * FROM members_reputation");
                $reps = $this->srcSQL->ResultArray();
                if (!$reps) break;
                foreach ($reps as $rep)
                {
                    $name = $names[$rep['to_name']];
                    if ($rep['how'] == 1) {
                        $user_reps[$name]['plus']++;
                        $user_reps[$name]['level']++;
                    }
                    else
                    {
                        $user_reps[$name]['minus']++;
                        $user_reps[$name]['level']--;
                    }
                    $this->dleSQL->Query("INSERT INTO repa_comm SET date=%%,how=%%,author=%%,komu=%%,text=%%",
                                         int_to_datetime($rep['date']), $rep['how'], $names[$rep['from_name']], $names[$rep['to_name']], $rep['text']);
                }
            }

            foreach ($user_reps as $name => $rep)
                $this->dleSQL->Query("UPDATE users SET repa=%%,repa_mod=%% WHERE name=%%", $rep['level'],
                                     $rep['plus'] . "|" . $rep['minus'], $name);


            while (true)
            {
                $this->srcSQL->LimitQuery("SELECT * FROM members_reputation_log");
                $reps = $this->srcSQL->ResultArray();
                if (!$reps) break;
                foreach ($reps as $rep)
                    $this->dleSQL->Query("INSERT INTO repa_log SET autor_id=%%, komu_id=%%, date_change=%%",
                                         $rep['from_id'], $rep['to_id'], $rep['date']);
            }
            $this->Finish();
        }

        $this->Start("Private messages");

        $this->srcSQL->Query("SELECT id, title FROM members_pm_topic");
        $res = $this->srcSQL->ResultArray();
        $topics_subj = array();
        foreach($res as $item)
            $topics_subj[$item['id']] = $item['title'];

        $this->srcSQL->Query("SELECT * FROM members_pm");
        $pms = $this->srcSQL->ResultArray();
        foreach($pms as $pm)
        {
            if( $pm['send_by'] == $pm['pm_member'])continue;
            $this->dleSQL->Query("INSERT INTO pm SET subj=%%,text=%%,user=%%,user_from=%%,date=%%,pm_read='yes',folder=%%",
                $topics_subj[$pm['topic']], lb_to_dle($pm['text']), $users_id[$pm['pm_member']], $this->GetMemberName($users_id[$pm['send_by']]),
                $pm['send_date'], "inbox");
            $this->dleSQL->Query("INSERT INTO pm SET subj=%%,text=%%,user=%%,user_from=%%,date=%%,pm_read='yes',folder=%%",
                $topics_subj[$pm['topic']], lb_to_dle($pm['text']), $users_id[$pm['pm_member']], $this->GetMemberName($users_id[$pm['send_by']]),
                $pm['send_date'], "outbox");
        }

        $this->Finish();

        echo "It's all";

    }
}

?>