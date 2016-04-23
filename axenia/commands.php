<?php

//--------------------Users----------------------------------

function GetUserID($username)
{
    $res = Query2DB("SELECT id FROM Users WHERE username='" . str_ireplace("@", "", $username) . "'");
    return (!$res[0]) ? false : $res[0];
}

function GetUserName($id)
{
    $res = Query2DB("SELECT username,firstname,lastname FROM Users WHERE id=" . $id);
    return (!$res[0]) ? $res[1] : $res[0];
}

function AddUser($user_id, $username, $firstname, $lastname)
{
    $query = "INSERT INTO `Users` SET `id`='" . $user_id . "',`username`='" . $username . "',`firstname`='" . $firstname . "',`lastname`='" . $lastname . "' ON DUPLICATE KEY UPDATE `username`='" . $username . "' , `firstname`='" . $firstname . "' , `lastname`='" . $lastname . "'";
    Query2DB($query);
}


//--------------------Chats----------------------------------

function SetHello($text, $chat_id)
{
    $res = Query2DB("UPDATE  `Chats` SET  `greeterings` =  '" . $text . "' WHERE  id = " . $chat_id);
    return ($res === false) ? false : "Добавлено";
}

function GetGroupName($id)
{
    $res = Query2DB("SELECT title FROM Chats WHERE id=" . $id);
    return (!$res[0]) ? false : $res[0];
}

function AddChat($chat_id, $title)
{
    $query = "INSERT INTO `Chats` SET `id`=" . $chat_id . ",`title`='" . $title . "' ,`reports_num`=3 ON DUPLICATE KEY UPDATE `title`='" . $title . "'";
    $res = Query2DB($query);
    return ($res === false) ? false : "Всем чмаффки в этом чатике.";
}


//--------------------Admins----------------------------------

function SetAdmin($chat_id, $user_id)
{
    if ($user_id !== false) {
        $res = Query2DB("INSERT INTO `Admins` SET `user_id`='" . $user_id . "',`chat_id`=" . $chat_id);
        return ($res === false) ? false : "{username}, жду твоих указаний.";
    }
    return "Пользователь не найден";
}

function CheckAdmin($chat_id, $user_id)
{
    $res = Query2DB("SELECT id FROM Admins WHERE chat_id=" . $chat_id . " AND user_id=" . $user_id);
    return $res[0];
}

//--------------------Karma----------------------------------

/**
 * получить уровень кармы пользователя из чата
 * @param $user_id
 * @param $chat_id
 * @return mixed
 */
function getUserLevel($user_id, $chat_id)
{
    $query = "SELECT level FROM Karma WHERE user_id=" . $user_id . " AND chat_id=" . $chat_id;
    $res = Query2DB($query);
    return $res;
}

/**
 * Добавляет запись с уровня кармы пользователя в чате.
 * Если пользователь уже имеется с каким то левелом то левел обновится из параметра $level
 * @param $user_id
 * @param $chat_id
 * @param $level
 * @return mixed
 */
function setUserLevel($user_id, $chat_id, $level)
{
    $query = "INSERT INTO `Karma` SET `user_id`=" . $user_id . ",`chat_id`=" . $chat_id . ",`level`=" . $level . " ON DUPLICATE KEY UPDATE `level`=" . $level;
    $res = Query2DB($query);
    return $res;
}


function SetCarma($chat, $user, $level)
{
    $query = "INSERT INTO `Karma` SET `chat_id`=" . $chat . ",`user_id`=" . $user . ",`level`=" . $level . " ON DUPLICATE KEY UPDATE `level`=" . $level;
    $res = Query2DB($query);
    return ($res === false) ? false : true;
}


//--------------------Rewards----------------------------------


function getRewardOldType($user_id, $chat_id)
{
    return Query2DB("select type_id from Rewards where user_id=" . $user_id . " and group_id=" . $chat_id . " and type_id>=2 and type_id<=4");
}

function updateReward($new_type_id, $old_type_id, $desc, $user_id, $chat_id)
{
    Query2DB("update Rewards set type_id=" . $new_type_id . ", description='" . $desc . "'  where type_id=" . $old_type_id . " and user_id=" . $user_id . " and group_id=" . $chat_id);
}

function deleteReward($user_id, $chat_id)
{
    Query2DB("delete from Rewards where user_id=" . $user_id . " and group_id=" . $chat_id . " and (type_id>=2 and type_id<=4)");
}

function insertReward($new_type_id, $desc, $user_id, $chat_id)
{
    Query2DB("insert into Rewards(type_id,user_id,group_id,description) values (" . $new_type_id . "," . $user_id . "," . $chat_id . ",'" . $desc . "')");
}


//--------------------Others----------------------------------


function HandleKarma($dist, $from, $to, $chat_id)
{
    if ($from == $to) return "Давай <b>без</b> кармадрочерства";
    if ($from != 1) {
        $query = "SELECT level FROM Karma WHERE user_id=" . $from . " AND chat_id=" . $chat_id;
        if (!Query2DB($query)[0]) {
            $query = "INSERT INTO `Karma` SET `chat_id`=" . $chat_id . ",`user_id`=" . $from . ",`level`=0";
            Query2DB($query);
            $a = 0;
        } else $a = Query2DB($query)[0];
        if ($a < 0) return "Ты <b>не  можешь</b> голосовать с отрицательной кармой";
        $output = "<b>" . GetUserName($from) . " (" . $a . ")</b>";
    } else {
        $output = "<b>Аксинья</b>";
    }
    $query = "SELECT level FROM Karma WHERE user_id=" . $to . " AND chat_id=" . $chat_id;
    (!Query2DB($query)[0]) ? $b = 0 : $b = Query2DB($query)[0];
    if ($a == 0) $a = 1;
    switch ($dist) {
        case "+":
            $output .= " плюсанул в карму ";
            $result = round($b + sqrt($a), 1);
            break;
        case "-":
            $output .= " минусанул в карму ";
            $result = ($from != 1) ? round($b - sqrt($a), 1) : $b - 0.1;
            break;
    }
    $output .= "<b>" . GetUserName($to) . " (" . $result . ")</b>";
    $query = "INSERT INTO `Karma` SET `chat_id`=" . $chat_id . ",`user_id`=" . $to . ",`level`=" . $result . " ON DUPLICATE KEY UPDATE `level`=" . $result;
    Query2DB($query);

    //проверка наград
    switch ($result) {
        case $result >= 200 and $result < 500:
            $new_type_id = 2;
            $title = "Кармодрочер";
            $min = 200;
            break;
        case $result >= 500 and $result < 1000:
            $new_type_id = 3;
            $title = "Карманьяк";
            $min = 500;
            break;
        case $result >= 1000:
            $new_type_id = 4;
            $title = "Кармонстр";
            $min = 1000;
            break;
        default:
            $title = "title";
            $min = "min";
            break;
    }
    $old_type_id = getRewardOldType($to, $chat_id);
    if ($old_type_id != false) {
        //если есть награды
        if (isset($new_type_id)) {
            if ($new_type_id <> $old_type_id[0]) {
                $desc = generateRewardDesc($chat_id, $min);
                updateReward($new_type_id, $old_type_id[0], $desc, $to, $chat_id);
            }
            if ($new_type_id > $old_type_id[0]) {
                $output .= getRewardMessage($to, $title);
            }
        } else {
            deleteReward($to, $chat_id);
        }
    } elseif (isset($new_type_id)) {
        //Если нет наград, но
        $desc = generateRewardDesc($chat_id, $min);
        insertReward($new_type_id, $desc, $to, $chat_id);
        $output .= getRewardMessage($to, $title);
    }
    return $output;
}

function generateRewardDesc($chat_id, $min)
{
    return "Карма в группе " . GetGroupName($chat_id) . " превысило отметку в " . $min;
}

function getRewardMessage($user_id, $title)
{
    return "\r\nТоварищ награждается отличительным знаком «<a href='" . PATH_TO_SITE . "?user_id=" . $user_id . "'>" . $title . "</a>»";
}

function Punish($user, $chat)
{
    if ($chat == -1001016901471) return HandleKarma("-", 1, $user, $chat);
}


?>