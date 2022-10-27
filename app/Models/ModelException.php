<?php
namespace App\Models;

use App\Yclients\Notification\Notification;

class ModelException
{

    public static function Exception(\Exception $e, $classNameException, $functionNameException){
        $appName = env("APP_NAME");
        $messageException = $e->getMessage();
        $message = "<b>$appName - DB Exception</b>
$classNameException::$functionNameException

$messageException";
        Notification::sendAdminTelegram($message);
    }

}