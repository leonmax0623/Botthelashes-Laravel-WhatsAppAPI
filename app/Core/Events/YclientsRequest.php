<?php
namespace App\Core\Events;

use App\Core\Config;
use App\Core\Core;
use App\Core\Test;
use App\Core\Yclients\Assistant;
use App\Core\YclientsCore;

class YclientsRequest
{

    public static $betaTest = false;

    public static function getActualRecordsFromYclients(){
        $actualRecords = [];
        foreach(Config::getConfig() as $companyId => $userToken){
            $actualRecords[$companyId] = Config::getYclientsApi()->getRecords($companyId, $userToken, null, 1000, null, null,
                Assistant::_getDateTimeObject(Assistant::_getTimeDefault(Assistant::_getStartTime())),
                Assistant::_getDateTimeObject(Assistant::_getTimeDefault(Assistant::_getEndTime()))
            );
        }
        $actualRecordsFromYclients = [];
        foreach(Config::getConfig() as $companyId => $userToken){
            foreach($actualRecords[$companyId]['data'] as $actualRecord){
                $actualRecordsFromYclients[$actualRecord['id']] = $actualRecord;
            }
        }
        return $actualRecordsFromYclients;
    }

    public static function YclientsRequest(){
        if (EventAssistant::getConnectYclients() == 'request'){
            $createCount = 0;
            $updateCount = 0;
            $deleteCount = 0;
            $startPHP = microtime(true);
            $saveRecordsFromDB = Assistant::getSaveRecords();
            $actualRecordsFromYclients = self::getActualRecordsFromYclients();

            foreach($actualRecordsFromYclients as $actualRecordFromYclients){
                if(EventAssistant::checkBetaTest($actualRecordFromYclients, self::$betaTest)){
                    if (!empty($saveRecordsFromDB[$actualRecordFromYclients['id']])){
                        // записи есть в Базе Данных и есть в YCLIENTS, проверяем на update = Изменение Записи

                        if (
                            YclientsWebhookUpdateRecordEvent::stopEvent($actualRecordFromYclients) &&
                            (
                                strtotime($actualRecordFromYclients['date']) !== strtotime($saveRecordsFromDB[$actualRecordFromYclients['id']]['date']) ||
                                YclientsCore::getStaffId($actualRecordFromYclients) !== YclientsCore::getStaffId($saveRecordsFromDB[$actualRecordFromYclients['id']]) ||
                                YclientsCore::getRecordServicesIds($actualRecordFromYclients) !== YclientsCore::getRecordServicesIds($saveRecordsFromDB[$actualRecordFromYclients['id']])
                            )
                        ){
                            if($recordUpdate = YclientsWebhookUpdateRecordEvent::UpdateRecordEvent($actualRecordFromYclients, 'Request Architecture')){
                                $updateCount++;
                            }
                        }
                    } else {
                        // записи нет в Базе Данных, но есть в YCLIENTS = Создание Записи
                        if (YclientsWebhookCreateRecordEvent::stopEvent($actualRecordFromYclients)){
                            $createCount++;
                            YclientsWebhookCreateRecordEvent::CreateRecordEvent($actualRecordFromYclients, 'Request Architecture');
                        }
                    }
                }
            }

            foreach($saveRecordsFromDB as $saveRecordFromDB){
                if(
                    time() < strtotime($saveRecordFromDB['date']) && // только записи, которые будут в будущем
                    EventAssistant::checkBetaTest($saveRecordFromDB, self::$betaTest) && // для всех или только бета-тестеров
                    empty($actualRecordsFromYclients[$saveRecordFromDB['id']]) && // если запись есть в Базе Данных, но уже нет в YCLIENTS
                    YclientsWebhookDeleteRecordEvent::stopEvent($saveRecordFromDB) // если запись не подпадает под ограничения
                ){
                    // запись есть в Базе Данных, но уже нет в YCLIENTS = Удаление Записи
                    YclientsWebhookDeleteRecordEvent::DeleteRecordEvent($saveRecordFromDB, 'Request Architecture');
                    $deleteCount++;
                }
            }

            if (
                $createCount !== 0 ||
                $updateCount !== 0 ||
                $deleteCount !== 0
            ){
                YclientsCore::sendTelegramLog("<b>TheLashes YclientsRequest Time: ".(microtime(true) - $startPHP) . " sec</b>
createCount = $createCount
updateCount = $updateCount
deleteCount = $deleteCount
");
            }
        }
    }

}