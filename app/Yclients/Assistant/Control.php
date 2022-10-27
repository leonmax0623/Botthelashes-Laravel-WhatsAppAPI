<?php
namespace App\Yclients\Assistant;

use App\Core\Test;
use App\Models\Bot;
use App\Models\Records;
use App\Yclients\Cache\AdminConfig;
use App\Yclients\Record\Record;

class Control
{

    public static function checkDatNowAndNotNight(){
        $time = date('H:i');
        if (
            strtotime($time) < strtotime(AdminConfig::get()['timeEvening']) &&
            strtotime($time) > strtotime(AdminConfig::get()['timeMorning'])
        ) {
            return true;
        } else {
            return false;
        }
    }

    public static function addRecord($record){
        $newRecordHasBeenAdded = false;
        $result = Records::getRecord($record['id']);
        if ($result === null && Records::insertRecord($record)){
            $newRecordHasBeenAdded = true;
        }
        return $newRecordHasBeenAdded;
    }

    public static function checkAccessUserSendMessageToWhatsApp($phone){
        $user = Bot::getBotWherePhone($phone);
        if ($user !== null) {
            $start = $user->start;
            $stop = $user->stop;
            if ($start == 1 && $stop == 0){
                return true;
            }
        }
        return false;
    }

    public static function checkBetaTest($record, $betaTest){
        if (!$betaTest || in_array(Record::getClientPhone($record), Test::$betaClients)){
            return true;
        } else {
            return false;
        }
    }

}