<?php

namespace App\Core\Models;
use App\Core\Core;
use App\Core\YclientsCore;

class ModelException
{

    public static function Exception(\Exception $e, $classNameException, $functionNameException){
        $appName = env("APP_NAME");
        $messageException = $e->getMessage();
        $message = "<b>$appName - DB Exception</b>
$classNameException::$functionNameException

$messageException";
        YclientsCore::sendTelegramLog($message);
    }

}