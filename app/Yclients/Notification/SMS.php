<?php
namespace App\Yclients\Notification;

use App\Core\Config;

class SMS
{

    public static function sendSMS($clientPhone, $message, $companyId){
        $login = Config::$smsConfig[$companyId]['login'];
        $password = Config::$smsConfig[$companyId]['password'];
        $sender = Config::$AlfaName;
        $result = \App\Core\Smsc::sendSMS($login, $password, $clientPhone, $message, $sender);
//                        YclientsCore::sendAdminTelegram("
//<b>The Lashes WA bot</b>
//Отправлено смс на номер $clientPhone
//Сообщение: $message
//Ответ сервера: $result");
    }

}