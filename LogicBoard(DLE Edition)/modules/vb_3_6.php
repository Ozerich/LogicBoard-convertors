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

        
        
    }
}

?>