<?php

require_once "EngineBase.php";
require_once 'include/parse/functions.php';
require_once 'include/parse/bbcode/function.php';

function to_bb($text)
{
    $text = str_replace(array("[code", "[/code"), array("[php", "[/php"), $text);
    $bb_codes = array("b", "i", "u", "quote","url","img", "php", "spoiler");
    $smiles = array(
"\:D" => "007",
"\:\)" => "002",
"\;\)" => "004",
"\:\(" => "003",
":shock:" => "009",
":o" => "043",
":\?" => "030",
"8-\)" => "006",
":lol:" => "035",
":P" => "005",
":oops:" => "046",
":cry:" => "037",
":evil:" => "017",
":twisted:" => "017",
":x" => "047",
    );

    foreach($bb_codes as $bb_code)
    {
        $text = preg_replace('#\['.$bb_code.'(.*?)\:.+?]#si', '['.$bb_code.'\\1]', $text);
        $text = preg_replace('#\[/'.$bb_code.'\:.+?]#si', '[/'.$bb_code.']', $text);
    }
    $text = preg_replace('#\[quote=\&quot;(.+?)\&quot;#si', '[quote=\\1', $text);

    foreach($smiles as $old=>$new)
        $text = preg_replace('#\<\!-- s'.$old.' --\>.+?\<\!-- s'.$old.' --\>#si', '::'.$new.'::', $text);
     $text = preg_replace('#\<\!-- s.+? --\>.+?\<\!-- s.+? --\>#si', '', $text);

    $text = preg_replace("#\[url\](.+?)\[/url\]#si", "[url=\\1]\\1[/url]", $text);
    $text = preg_replace("#\[img\](.+?)\[/img\]#si", "<center><img src='\\1' class='lb_img' /></center>", $text);
    if (preg_match_all("#\[quote(=((.+?)(\|([0-9\., :]+?))?))?\]#si", $text, $shadow) == preg_match_all("#\[/quote\]#si", $text, $shadow)) {
        $text = preg_replace("#\[quote\]#si", "<blockquote class=\"blockquote\"><p><span class=\"titlequote\">" . iconv("UTF-8", "Windows-1251","Цитата:") . "</span><span class=\"textquote\">", $text); //quote
        $text = preg_replace("#\[quote(=((.+?)(\|([0-9\., :]+?))?))?\]#si", "<blockquote class=\"blockquote\"><p><span class=\"titlequote\">\\3 (\\5) " . iconv("UTF-8", "Windows-1251","писал:") . "</span><span class=\"textquote\">", $text); //quote
        $text = preg_replace("#\[/quote\]#si", "</span></p></blockquote><!--quote -->", $text); //quote
        $text = preg_replace("#<blockquote class=\"blockquote\"><p><span class=\"titlequote\">(.+?) \(\) (.+?)</span>#si", "<blockquote class=\"blockquote\"><p><span class=\"titlequote\">\\1 \\2</span>", $text); //quote
    }

    return parse_word($text, "");
}

class phpBB_3_0_9 extends EngineBase
{
    protected $setup = array(
        "caption" => "phpBB ver. 3.0.9",
        "id" => "phpbb_3_0_9",
        "db_prefix" => "phpBB_",
        "add_text" => array("Пароли пользователей не перенесутся. Каждому пользователю надо будет сделать запрос на восстановление
        пароля на электронную почту", "При конвертации будет осуществлён перенос пользователей из форума в базу данных сайта, всвязи с этим некоторым пользователям потребуется восстановить пароль, используя адрес своей почты (имеено почты, а не логина), т.к. в некоторых случаях логин пользователя будет изменён (при необходимости в АЦ сайта Вы сможете сменить логин пользователю на любой другой).
Создайте на сайте новость с данным предупреждением для Ваших пользователей.", "Права групп перенесены будут не все. Пожалуйста, отредакртируйте их после конвертации в панели управления","После конвертации не забудьте очистить кеш форума, иначе новые форумы не появятся."),
        "dle_based" => false,
    );

    public function Convert($options)
    {

        $this->Start("Install");
        $this->InstallLB($options);
        $this->Finish();


        $users_id = array();

        $group_masks = array("2" => 3, "3" => 3, "4" => 2, "5" => 1, "7" => 3);

        $this->Start("Groups");

        $this->srcSQL->Query("SELECT * FROM groups WHERE group_id > 7");
        $user_groups = $this->srcSQL->ResultArray();
        foreach ($user_groups as $group)
        {
            $this->dleSQL->Query("INSERT INTO usergroups SET group_name=%%", $group['group_name']);
            $group_masks[$group['group_id']] = $this->dleSQL->InsertedId();
        }

        $this->dleSQL->Query("SELECT * FROM usergroups");
        $groups = $this->dleSQL->ResultArray();
        foreach ($groups as $group)
        {
            $dle_group = $group['id'];

            $access = array(
                'local_opentopic' => 1,
                'local_closetopic' => 0,
                'local_deltopic' => 0,
                'local_polltopic' => 1,
                'local_delpost' => 0,
                'local_changepost' => 1,
            );

            $this->destSQL->Query("INSERT INTO groups SET g_id=%%,g_title=%%, g_access=%%",
                                  $dle_group, $group['group_name'], serialize($access));
        }
        $this->destSQL->Query("UPDATE groups SET g_prefix_st=%%, g_prefix_end=%%, g_access_cc=1 WHERE g_id=1",
                              "<font color=\"#ff0000\">", "</font>");
        $this->destSQL->Query("UPDATE groups SET g_supermoders=1 WHERE g_id=1");

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
                $dle_user_names[] = $user['name'];
                $dle_user_ids[$user['email']] = $user['user_id'];
                $dle_user_emails[] = $user['email'];
            }
        }

        $this->srcSQL->Query("SELECT * FROM users");
        $users = $this->srcSQL->ResultArray();
        foreach ($users as $user)
        {
            if (!isset($group_masks[$user['group_id']])) continue;
            $group = $group_masks[$user['group_id']];

            $user_email = $user['user_email'];
            $user_name = $user['username'];

            if (in_array($user_email, $dle_user_emails)) {
                $this->dleSQL->Query("UPDATE users SET posts_num=%%, count_warning=%%,user_group=%%  WHERE user_id=%%",
                                     $user['user_posts'], $user['user_warnings'], $group, $dle_user_ids[$user['name']]);
                $users_id[$user['user_id']] = $dle_user_ids[$user['user_email']];
                $users_group[$dle_user_ids[$user['username']]] = $group;
                continue;
            }
            $name = in_array($user_name, $dle_user_names) ? "phpbbuser_" . substr(md5(rand()), 0, 6) : $user_name;
            $b_day = $b_month = $b_year = 0;

            if (isset($user['user_birthday'])) {
                $date = explode("-", $user['user_birthday']);
                $b_day = $date[0];
                $b_month = $date[1];
                $b_year = $date[2];
            }
            $this->dleSQL->Query("INSERT INTO users SET name=%%,email=%%,reg_date=%%,user_group=%%,password=%%,logged_ip=%%,
                                   lastdate=%%,count_warning=%%,posts_num=%%,icq=%%,lb_b_day=%%,lb_b_month=%%,lb_b_year=%%", $name, $user['user_email'], $user['user_regdate'],
                                 $group, md5(rand() % 100000), $user['user_ip'], $user['user_lastvisit'],
                                 $user['user_warnings'], $user['user_posts'], $user['user_icq'], $b_day, $b_month, $b_year);

            $users_id[$user['user_id']] = $this->dleSQL->InsertedId();
            $users_group[$this->dleSQL->InsertedId()] = $group;
        }

        $this->dleSQL->Query("UPDATE users SET user_group=1 WHERE name=%%", $options['admin_name']);


        $this->Finish();



        $this->Start("Categories");


        $this->srcSQL->Query("SELECT * FROM forums WHERE forum_type=1 AND parent_id=0");
        $forums = $this->srcSQL->ResultArray();
        if(!empty($forums))
            $this->destSQL->Query("INSERT INTO forums SET id=1,parent_id=0, title=%%", iconv("UTF-8", "Windows-1251", 'Форум'));


        $this->srcSQL->Query("SELECT * FROM forums WHERE forum_type=0 ORDER by forum_id ASC");
        $posi = 2;
        $categories = $this->srcSQL->ResultArray();
        foreach ($categories as $category)
            $this->destSQL->Query("INSERT INTO forums SET id=%%,title=%%, posi=%%", $category['forum_id'],$category['forum_name'], $posi++);

        $this->Finish();

        $this->Start("Forums");

        $this->srcSQL->Query("SELECT * FROM forums WHERE forum_type=1 ORDER by forum_id ASC");
        $forums = $this->srcSQL->ResultArray();
        $perm = array();
        $permissions = array(
            "read_forum" => 1,
            "read_theme" => 1,
            "answer_theme" => 1,
            "creat_theme" => 1,
            "upload_files" => 1,
            "download_files" => 1,
        );
        $this->destSQL->Query("SELECT * FROM groups");
        $groups = $this->destSQL->ResultArray();
        foreach ($groups as $group)
            $perm[$group['g_id']] = $permissions;
        $perm = serialize($perm);
        $posi = 1;
        foreach ($forums as $forum)
        {
            $this->destSQL->Query("INSERT INTO forums SET id=%%,title=%%, alt_name=%%, posts=%%, topics=%%, last_post_id=%%,
            last_post_member_id=%%,	last_post_member=%%, last_post_date=%%,group_permission=%%, posi=%%",
                                  $forum['forum_id'], $forum['forum_name'], translit($forum['forum_name'], false), $forum['forum_posts'], $forum['forum_topics'],
                                  $forum['forum_last_post_id'], $users_id[$forum['forum_last_poster_id']],
                                  $this->GetMemberName($users_id[$forum['forum_last_poster_id']]),
                                  $forum['forum_last_post_time'], $perm, $posi++);
        }
        foreach ($forums as $forum)
        {
            $parent = $forum['parent_id'] == 0 ? 1 : $forum['parent_id'];
            $this->destSQL->Query("UPDATE forums SET parent_id=%% WHERE id=%%",
                                  $parent, $forum['forum_id']);
        }

        $this->Finish();


        $this->Start("Topics");

        $topics_poll = array();
        $this->srcSQL->Query("SELECT * FROM topics");
        $topics = $this->srcSQL->ResultArray();
        foreach ($topics as $topic)
        {
            $this->destSQL->Query("INSERT INTO topics SET id=%%, forum_id=%%, title=%%, post_id=%%, date_open=%%, date_last=%%,
            post_num=%%, views=%%, last_post_id=%%, last_post_member=%%,member_name_last=%%, member_id_open=%%, member_name_open=%%",
                                  $topic['topic_id'], $topic['forum_id'], $topic['topic_title'], $topic['topic_first_post_id'],
                                  $topic['topic_time'], $topic['topic_last_post_time'], $topic['topic_replies'],
                                  $topic['topic_views'], $topic['topic_last_post_id'], $users_id[$topic['topic_last_poster_id']],
                                  $this->GetMemberName($users_id[$topic['topic_last_poster_id']]), $users_id[$topic['topic_poster']],
                                  $this->GetMemberName($users_id[$topic['topic_poster']]));
            if($topic['poll_title'] != "")
                $topics_poll[$topic['topic_id']] = array("title"=>$topic['poll_title'], "time" => $topic['poll_start']);
        }

        $this->Finish();
        $this->Start("Posts");

        $this->srcSQL->Query("SELECT * FROM posts");
        $posts = $this->srcSQL->ResultArray();
        foreach ($posts as $post)
        {
            $topic_id = $post['topic_id'];
            $post_time = $post['post_time'];
            $this->destSQL->Query("INSERT INTO posts SET pid=%%, topic_id=%%, text=%%, post_date=%%, edit_date=%%,post_member_id=%%,
                                post_member_name=%%, ip=%%, edit_member_id=%%, edit_member_name=%%, edit_reason=%%",
                                  $post['post_id'],$topic_id, to_bb($post['post_text']), $post_time, $post['post_edit_time'],
                                  $users_id[$post['poster_id']], $this->GetMemberName($users_id[$post['poster_id']]),
                                  $post['poster_ip'], $users_id[$post['post_edit_user']],
                                  $this->GetMemberName($users_id[$post['post_edit_user']]), $post['post_edit_reason']);
            if(!isset($topic_first_posts[$topic_id]) || $post_time < $topic_first_posts[$topic_id]['time'])
                $topic_first_posts[$topic_id] = array("post"=>$this->destSQL->InsertedId(), "time" => $post_time);
        }
        $this->Finish();
        $this->Start("Update forums, topics and posts");

        $this->destSQL->Query("SELECT * FROM topics");
        $topics = $this->destSQL->ResultArray();
        foreach($topics as $topic)
        {
            $post_id = $topic['post_id'];
            $topic_id = $topic['id'];
            $this->destSQL->Query("UPDATE posts SET new_topic=1 WHERE pid=%%", $post_id);
            $this->destSQL->Query("UPDATE topics SET post_id=%%, last_post_id=%% WHERE id=%%",
                                  $post_id, $topic['last_post_id'], $topic_id);
        }

        $this->destSQL->Query("SELECT * FROM forums");
        $forums = $this->destSQL->ResultArray();
        foreach($forums as $forum)
        {
            $last_post_id = $forum['last_post_id'];
            $this->destSQL->Query("SELECT topic_id FROM posts WHERE pid=%%", $last_post_id);
            $last_topic_id = $this->destSQL->Result();
            $this->destSQL->Query("SELECT title FROM topics WHERE id=%%", $last_topic_id);
            $last_title = $this->destSQL->Result();
            $this->destSQL->Query("UPDATE forums SET last_post_id=%%, last_topic_id=%%, last_title=%% WHERE id=%%",
                                  $last_post_id,$last_topic_id,$last_title, $forum['id']);
        }

        $this->Finish();

        $this->Start("Polls");

        foreach($topics_poll as $tid=>$poll)
        {
            $this->srcSQL->Query("SELECT * FROM poll_options WHERE topic_id=%%", $tid);
            $options = $this->srcSQL->ResultArray();
            $variants = $answers = "";
            $count = 0;
            foreach($options as $ind=>$option)
            {
                if($ind > 0)
                    $variants .= "\r\n";
                $variants .= $option['poll_option_text'];
                if($option['poll_option_total'] > 0)
                {
                    $count += $option['poll_option_total'];
                    if($answers)
                        $answers .= "|";
                    $answers .= $ind.":".$option['poll_option_total'];
                }
            }
            $this->destSQL->Query("INSERT INTO topics_poll SET tid=%%,vote_num=%%,question=%%,variants=%%, open_date=%%,
            answers=%%", $tid, $count, $poll['title'], $variants, $poll['time'], $answers);
            $this->destSQL->Query("UPDATE topics SET poll_id=%% WHERE id=%%", $this->destSQL->InsertedId(), $tid);
        }

        $this->Finish();

        $this->Start("Poll logs");

        $this->srcSQL->Query("SELECT * FROM poll_votes");
        $logs = $this->srcSQL->ResultArray();
        foreach($logs as $log)
        {
            $this->destSQL->Query("SELECT poll_id FROM topics WHERE id=%%", $log['topic_id']);
            $poll_id = $this->destSQL->Result();

            $this->destSQL->Query("SELECT variants FROM topics_poll WHERE id=%%", $poll_id);
            $variants = explode("\n", $this->destSQL->Result());

            $this->srcSQL->Query("SELECT poll_option_text FROM poll_options WHERE poll_option_id=%%", $log['poll_option_id']);
            $answer = $this->srcSQL->Result();

            $answer_id = -1;
            foreach($variants as $ind=>$variant)
                if(trim($answer) == trim($variant))
                {
                    $answer_id = $ind;
                    break;
                }

            $this->destSQL->Query("INSERT INTO topics_poll_logs SET poll_id=%%,ip=%%,member_id=%%, member_name=%%,log_date=%%,
            answer=%%", $poll_id, $log['vote_user_ip'], $users_id[$log['vote_user_id']],
                                  $this->GetMemberName($users_id[$log['vote_user_id']]),time(),$answer_id);

        }

        $this->Finish();

        $this->Start("Attachments");

        $this->srcSQL->Query("SELECT * FROM attachments");
        $files = $this->srcSQL->ResultArray();
        foreach($files as $file)
        {
            $this->destSQL->Query("SELECT forum_id FROM topics WHERE id=%%", $file['topic_id']);
            $fid = $this->destSQL->Result();
            $type = strpos($file['mimetype'], "image") !== false ? "picture" : "file";
            $this->destSQL->Query("INSERT INTO topics_files SET file_title=%%,file_name=%%,file_type=%%,file_mid=%%,file_mname=%%,
                file_date=%%, file_size=%%, file_count=%%,file_fid=%%, file_tid=%%, file_pid=%%, file_convert=1",
                                  $file['real_filename'], $file['physical_filename'],$type, $users_id[$file['poster_id']],
                                  $this->GetMemberName($users_id[$file['poster_id']]),$file['filetime'], $file['filesize'],
                                  $file['download_count'], $fid, $file['topic_id'], $file['post_msg_id']);
            $file_id = $this->destSQL->InsertedId();
            $post_id = $file['post_msg_id'];
            $this->destSQL->Query("SELECT attachments FROM posts WHERE pid=%%", $post_id);
            $text = $this->destSQL->Result();
            if($text)
                $text .= ",";
            $text .= $file_id;
            $this->destSQL->Query("UPDATE posts SET attachments=%% WHERE pid=%%", $text, $post_id);
        }

        $this->destSQL->Query("SELECT * FROM posts WHERE attachments != ''");
        $posts = $this->destSQL->ResultArray();
        foreach($posts as $post)
        {
            $files = explode(",", $post['attachments']);
            $text = $post['text'];
            preg_match_all('#\[attachment\=(\d+)\:.+?\].+?\[/attachment\:.+?\]#si', $text, $files_in_post, PREG_SET_ORDER);
            foreach($files_in_post as $file)
                $text = str_replace($file[0], "<br />[attachment=".$files[$file[1]]."]<br />", $text);

            foreach($files as $file)
                if(strpos($text, "[attachment=".$file."]") === false)
                    $text .= "\r\n<br />[attachment=".$file."]";
            $this->destSQL->Query("UPDATE posts SET text=%% WHERE pid=%%", $text, $post['pid']);
        }

        $this->Finish();


    }
}

?>