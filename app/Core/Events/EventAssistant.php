<?php

namespace App\Core\Events;

use App\Core\Config;
use App\Core\Models\Records;
use App\Core\Smsc;
use App\Core\Test;
use App\Core\theLashesModel;
use App\Core\WhatsApp;
use App\Core\Yclients\Assistant;
use App\Core\YclientsCore;
use Illuminate\Support\Facades\Cache;

class EventAssistant
{

    public static function sendClientToWhatsApp($clientPhone, $message){
        $whatsApp = new WhatsApp(Config::$waApiUrl, Config::$waToken);
        $data = [
            'phone' => $clientPhone,
            'body' => $message
        ];
        $whatsApp->request('sendMessage', $data);
        $whatsApp->request('readChat', ['phone' => $clientPhone]);
    }

    public static function sendClientToSMS($clientPhone, $message, $companyId){
        $login = Config::$smsConfig[$companyId]['login'];
        $password = Config::$smsConfig[$companyId]['password'];
        $sender = Config::$AlfaName;
        $result = Smsc::sendSMS($login, $password, $clientPhone, $message, $sender);
//                        YclientsCore::sendAdminTelegram("
//<b>The Lashes WA bot</b>
//Отправлено смс на номер $clientPhone
//Сообщение: $message
//Ответ сервера: $result");
    }

    public static function saveToCacheCompaniesInfo(){
        $companies = [];
        foreach(Config::getConfig() as $companyId => $userToken){
            $companies[$companyId] = Config::getYclientsApi()->getCompany($companyId);
        }
        Cache::put("companies", $companies, 2000);
        return $companies;
    }

    public static function getConnectYclients(){
        $config = json_decode(theLashesModel::getAdminConfigDB()->value,1);
        return $config['connectYclients'];
    }

    public static function getCompaniesInfo(){
        if (Cache::get("companies")){
            $companies = Cache::get("companies");
        } else {
            $companies = self::saveToCacheCompaniesInfo();
        }
        return $companies;
    }

    public static function getAllRecords($startTime = null, $endTime = null){
        $records = [];
        foreach(Config::getConfig() as $companyId => $userToken){
            $records[$companyId] = Config::getYclientsApi()->getRecords($companyId, $userToken, null, 1000, null, null,
                Assistant::_getDateTimeObject(Assistant::_getTimeDefault($startTime)),
                Assistant::_getDateTimeObject(Assistant::_getTimeDefault($endTime))
            );
        }
        return $records;
    }

    public static function addRecord($record){
        $result = Records::getRecord($record['id']);
        $newRecordHasBeenAdded = false;
        // если null, то значит, что запись еще не создана, а значит можно добавить ее в базу данных
        if ($result === null){
            YclientsCore::addRecord($record);
            $newRecordHasBeenAdded = true;
        }
        return $newRecordHasBeenAdded;
    }

    public static function checkBetaTest($record, $betaTest){
        if (!$betaTest || in_array(YclientsCore::getClientPhone($record), Test::$betaClients)){
            return true;
        } else {
            return false;
        }
    }

}