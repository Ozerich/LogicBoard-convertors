<?php

require_once "EngineBase.php";
require_once "include/ipb_parser.php";

class VB_3_6 extends EngineBase
{
    protected $setup = array(
        "caption" => "vBulletin ver. 3.6",
        "id" => "vb_3_6",
        "db_prefix" => "vb_",
        "add_text" => array("Пароли пользователей не перенесутся. Каждому пользователю надо будет сделать запрос на восстановление
        пароля на электронную почту", "При конвертации будет осуществлён перенос пользователей из форума в базу данных сайта, всвязи с этим некоторым пользователям потребуется восстановить пароль, используя адрес своей почты (имеено почты, а не логина), т.к. в некоторых случаях логин пользователя будет изменён (при необходимости в АЦ сайта Вы сможете сменить логин пользователю на любой другой).
Создайте на сайте новость с данным предупреждением для Ваших пользователей.", "Права групп перенесены будут не все. Пожалуйста, отредакртируйте их после конвертации в панели управления", "После конвертации не забудьте очистить кеш форума, иначе новые форумы не появятся."),
        "dle_based" => false,
    );


    public function Convert($options)
    {
        $added_email = $added_users = array();

        /*$this->Start("Install");
        $this->InstallLB($options);
        $this->Finish();*/

        $this->Start("Groups");

        $vb_dle_groups = array("5" => "2", "6" => "1", "7" => "3", "2" => "4");
        foreach ($vb_dle_groups as $vb_ind => $dle_ind)
        {
            $this->srcSQL->Query("SELECT title,opentag,closetag FROM usergroup WHERE usergroupid=%%", $vb_ind);
            $data = $this->srcSQL->ResultArray(true);
            $this->dleSQL->Query("UPDATE usergroups SET group_name=%%,group_prefix=%%,group_suffix=%% WHERE id=%%",
                                 $data['title'], $data['opentag'], $data['closetag'], $dle_ind);
        }

        $this->srcSQL->Query("SELECT title,opentag,closetag FROM usergroup WHERE usergroupid > 7");
        $groups = $this->srcSQL->ResultArray();
        foreach ($groups as $group)
        {
            $this->dleSQL->Query("INSERT INTO usergroups SET group_name=%%, group_prefix=%%, group_suffix=%%",
                                 $group['title'], $group['opentag'], $group['closetag']);
            $vb_dle_groups[$group['usergroupid']] = $this->dleSQL->InsertedId();
        }

        $this->dleSQL->Query("SELECT id, group_name, group_prefix, group_suffix FROM usergroups");
        $groups = $this->dleSQL->ResultArray();
        foreach ($groups as $group)
            $this->destSQL->Query("INSERT INTO groups SET g_id=%%,g_title=%%,g_prefix_st=%%,g_prefix_end=%%,g_access_cc=%%,
                g_supermoders=%%", $group['id'], $group['group_name'], $group['group_prefix'], $group['group_suffix'],
                                  $group['id'] == 1 ? 1 : 0, $group['id'] <= 2 ? 1 : 0);

        $this->Finish();

        $this->Start("Users");

        $this->srcSQL->Query("SELECT * FROM user");
        $users = $this->srcSQL->ResultArray();
        foreach ($users as $user)
        {
            $this->srcSQL->Query("SELECT COUNT(*) FROM thread WHERE postusername=%%", $user['username']);
            $topics_count = $this->srcSQL->Result();
            $birthday = $user['birthday'];
            $user['username'] = trim($user['username']);
            $user['email'] = trim($user['email']);
            if (in_array(strtolower($user['email']), $added_email))
                continue;
            $b_day = $b_month = $b_year = "";
            if (!empty($birthday)) {
                $birthday = explode("-", $birthday);
                $b_day = $birthday[0];
                $b_month = $birthday[1];
                $b_year = $birthday[2];
            }
            if (array_key_exists(strtolower($user['email']), $this->dle_emails) && !in_array(strtolower($user['email']), $added_email)) {
                $user_id = $this->dle_emails[strtolower($user['email'])];
                $this->dleSQL->Query("UPDATE users SET count_warning=%%,topics_num=%%,posts_num=%%,lb_skype=%%,lb_b_day=%%,
                            lb_b_month=%%,lb_b_year=%% WHERE user_id=%%", $user['warnings'], $topics_count, $user['posts'], $user['skype'],
                                     $b_day, $b_month, $b_year, $user_id);
            }
            else
            {
                $iter = 0;
                while (array_key_exists(strtolower($user['username']), $this->dle_users) || in_array(strtolower($user['username']), $added_users) && $iter++ < 5)
                    $user['username'] .= "_";
                if ($iter >= r)
                    continue;
                $this->dleSQL->Query("INSERT INTO users SET email=%%,password=%%,name=%%,user_group=%%,lastdate=%%,reg_date=%%,
                            icq=%%,logged_ip=%%,count_warning=%%,topics_num=%%,posts_num=%%,lb_skype=%%,lb_b_day=%%,lb_b_month=%%,
                            lb_b_year=%%", $user['email'], md5(rand() % 100000), $user['username'], $vb_dle_groups[$user['usergroupid']],
                                     $user['lastvisit'], $user['joindate'], $user['icq'], $user['ipaddress'], $user['warnings'], $topics_count,
                                     $user['posts'], $user['skype'], $b_day, $b_month, $b_year);

                $user_id = $this->dleSQL->InsertedId();
            }
            $vb_dle_users[$user['userid']] = $user_id;
            $added_email[] = strtolower($user['email']);
            $added_users[] = strtolower($user['username']);
        }

        $this->Finish();

/*
        $this->Start("Categories & Forums");

        $this->dleSQL->Query("SELECT id FROM usergroups");
        $result = $this->dleSQL->ResultArray();
        $permissions = array();
        foreach ($result as $group)
            $permissions[$group['id']] = array("read_forum" => 1, "read_theme" => 1, "creat_theme" => 1, "answer_theme" => 1, "upload_files" => 1, "download_files" => 1);
        $permissions = serialize($permissions);


        $this->srcSQL->Query("SELECT forumid, parentid, title, description, displayorder FROM forum");
        $categories = $this->srcSQL->ResultArray();
        foreach ($categories as $category)
            $this->destSQL->Query("INSERT INTO forums SET id=%%,parent_id=%%,title=%%,alt_name=%%,posi=%%,description=%%, group_permission=%%",
                                  $category['forumid'], $category['parentid'] > 0 ? $category['parentid']
                        : 0, strip_tags($category['title']),
                                  translit(strip_tags($category['title'])), $category['displayorder'], $category['description'], $permissions);
        $this->Finish();

        $this->Start("Topics");
        $this->srcSQL->Query("SELECT threadid, title, forumid, views, attach FROM thread");
        $topics = $this->srcSQL->ResultArray();
        foreach ($topics as $topic)
            $this->destSQL->Query("INSERT INTO topics SET id=%%,forum_id=%%,title=%%,views=%%,fixed=%%",
                                  $topic['threadid'], $topic['forumid'], $topic['title'], $topic['views'], $topic['attach']);
        $this->Finish();

        $this->Start("Posts");
        $this->srcSQL->Query("SELECT postid,threadid,userid,username,dateline,pagetext,visible,attach,ipaddress FROM post LIMIT 149990, 100000");
        $posts = $this->srcSQL->ResultArray();
        foreach ($posts as $post)
            $this->destSQL->Query("INSERT INTO posts SET pid=%%, topic_id=%%,text=%%,post_date=%%,ip=%%,hide=%%,fixed=%%,
                  post_member_id=%%,post_member_name=%%", $post['postid'], $post['threadid'], parse_word($post['pagetext']), $post['dateline'],
                                  $post['ipaddess'], ($post['visible'] + 1) % 2, $post['attach'],
                                  $post['userid'] == 0 ? $this->GetMemberId($post['username'])
                                          : $post['userid'], $post['username']);

        $this->Finish();
*/

        $this->Start("Ranks");
        $this->srcSQL->Query("SELECT minposts, title FROM usertitle");
        $ranks = $this->srcSQL->ResultArray();
        foreach ($ranks as $ind => $rank)
            $this->destSQL->Query("INSERT INTO members_ranks SET title=%%,post_num=%%,stars=%%",
                                  $rank['title'], $rank['minposts'], $ind + 1);
        $this->Finish();

        $this->Start("Bans");

        $this->srcSQL->Query("SELECT userid,bandate,liftdate,reason FROM userban");
        $bans = $this->srcSQL->ResultArray();
        foreach ($bans as $ban)
        {
            $this->dleSQL->Query("SELECT logged_ip FROM users WHERE user_id=%%", $vb_dle_users[$ban['userid']]);
            $ip = $this->dleSQL->Result();
            $this->dleSQL->Query("INSERT INTO banned SET users_id=%%,descr=%%,date=%%,days=%%,ip=%%",
                                 $vb_dle_users[$ban['userid']], $ban['reason'], $ban['bandate'],
                                 ceil(($ban['liftdate'] - $ban['bandate']) / 86400), $ip);
        }

        $this->Finish();

        $this->Start("Polls");

        $this->srcSQL->Query("SELECT pollid, question, dateline,options, votes,voters FROM poll");
        $polls = $this->srcSQL->ResultArray();
        foreach ($polls as $poll)
        {
            $this->srcSQL->Query("SELECT threadid FROM thread WHERE pollid=%%", $poll['pollid']);
            $topic_id = $this->srcSQL->Result();

            $answers = "";
            $ans_data = explode("|||", $poll['votes']);
            foreach ($ans_data as $ind => $val)
                if ($val > 0) {
                    if ($answers)
                        $answers .= "|";
                    $answers .= $ind . ":" . $val;
                }
            $this->destSQL->Query("INSERT INTO topics_poll SET id=%%, tid=%%,vote_num=%%, title=%%, question=%%,variants=%%,
                open_date=%%,answers=%%", $poll['pollid'], $topic_id, $poll['voters'], $poll['question'], $poll['question'],
                                  str_replace('|||', "\r\n", $poll['options']), $poll['dateline'], $answers);
            $this->destSQL->Query("UPDATE topics SET poll_id=%% WHERE id=%%", $poll['pollid'], $topic_id);
        }

        $this->Finish();

        $this->Start("Poll logs");

        $this->srcSQL->Query("SELECT pollid,userid,votedate,voteoption FROM pollvote");
        $logs = $this->srcSQL->ResultArray();
        foreach ($logs as $log)
        {
            $this->dleSQL->Query("SELECT logged_ip, user_id, name FROM users WHERE user_id=%%", $vb_dle_users[$log['user_id']]);
            $user = $this->dleSQL->ResultArray(true);
            $this->destSQL->Query("INSERT INTO topics_poll_logs SET poll_id=%%,ip=%%,member_id=%%,member_name=%%,
                log_date=%%,answer=%%", $log['pollid'], $user['logged_ip'], $user['user_id'], $user['name'],
                                  $log['votedate'], $log['voteoption']);
        }

        $this->Finish();

        $this->Start("Subscribes");

        $this->srcSQL->Query("SELECT userid, threadid, emailupdate FROM subscribethread");
        $subscribes = $this->srcSQL->ResultArray();
        foreach ($subscribes as $subscribe)
            $this->destSQL->Query("INSERT INTO topics_subscribe SET subs_member=%%,topic=%%,date=%%,send_status='1'",
                                  $vb_dle_users[$subscribe['userid']], $subscribe['threadid'], time());
        $this->Finish();

        $this->Start("Moderators");

        $this->srcSQL->Query("SELECT userid, forumid FROM moderator");
        $moders = $this->srcSQL->ResultArray();

        foreach ($moders as $moder)
        {
            $this->destSQL->Query("INSERT INTO forums_moderator SET fm_forum_id=%%,fm_member_id=%%,fm_member_name=%%,fm_permission=%%",
                                  $moder['forumid'], $vb_dle_users[$moder['userid']], $this->GetMemberName($vb_dle_users[$moder['userid']]),
                                  $this->default_moder_permissions);
        }
        $this->Finish();
    }
}

?>