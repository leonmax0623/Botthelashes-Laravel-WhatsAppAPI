<?php
namespace App\Yclients\Connect;


use App\Core\Config;
use App\Core\Yclients\Assistant;
use App\Models\Records;
use App\Yclients\Assistant\Control;
use App\Yclients\Connect\WebHook\CreateRecord;
use App\Yclients\Connect\WebHook\DeleteRecord;
use App\Yclients\Connect\WebHook\UpdateRecord;
use App\Yclients\Notification\Notification;
use App\Yclients\Record\Record;

class RequestArchitecture
{

    const CREATE = 'create';
    const UPDATE = 'update';
    const DELETE = 'delete';

    public static $betaTest = false;

    public static function execute(){
        $createCount = 0;
        $updateCount = 0;
        $deleteCount = 0;
        $startPHP = microtime(true);
        $saveRecordsFromDB = Records::getAssociatedRecords();
        $actualRecordsFromYclients = Record::getActualRecordsFromYclients();

        $clientsEvents = [];

        foreach($actualRecordsFromYclients as $actualRecordFromYclients){
            if(Control::checkBetaTest($actualRecordFromYclients, self::$betaTest)){
                $clientPhone = Record::getClientPhone($actualRecordFromYclients);
                if (!empty($saveRecordsFromDB[$actualRecordFromYclients['id']])){
                    // записи есть в Базе Данных и есть в YCLIENTS, проверяем на update = Изменение Записи
                    if (
                        UpdateRecord::stopEvent($actualRecordFromYclients) &&
                        (
                            strtotime($actualRecordFromYclients['date']) !== strtotime($saveRecordsFromDB[$actualRecordFromYclients['id']]['date']) ||
                            Record::getStaffId($actualRecordFromYclients) !== Record::getStaffId($saveRecordsFromDB[$actualRecordFromYclients['id']]) ||
                            Record::getRecordServicesIds($actualRecordFromYclients) !== Record::getRecordServicesIds($saveRecordsFromDB[$actualRecordFromYclients['id']])
                        )
                    ){
                        $updateCount++;
                        $clientsEvents[$clientPhone][self::getTimeKey($clientsEvents, $clientPhone, $actualRecordFromYclients['last_change_date'])] = [
                            'event' => self::UPDATE,
                            'record' => $actualRecordFromYclients,
                        ];
                    }
                } else {
                    // записи нет в Базе Данных, но есть в YCLIENTS = Создание Записи
                    if (CreateRecord::stopEvent($actualRecordFromYclients)){
                        $createCount++;
                        $clientsEvents[$clientPhone][self::getTimeKey($clientsEvents, $clientPhone, $actualRecordFromYclients['create_date'])] = [
                            'event' => self::CREATE,
                            'record' => $actualRecordFromYclients,
                        ];
                    }
                }
            }
        }

        foreach($saveRecordsFromDB as $saveRecordFromDB){
            if(
                time() < strtotime($saveRecordFromDB['date']) && // только записи, которые будут в будущем
                Control::checkBetaTest($saveRecordFromDB, self::$betaTest) && // для всех или только бета-тестеров
                empty($actualRecordsFromYclients[$saveRecordFromDB['id']]) && // если запись есть в Базе Данных, но уже нет в YCLIENTS
                DeleteRecord::stopEvent($saveRecordFromDB) // если запись не подпадает под ограничения
            ){
                // запись есть в Базе Данных, но уже нет в YCLIENTS = Удаление Записи
                $clientPhone = Record::getClientPhone($saveRecordFromDB);
                $deleteCount++;
                $deletedRecord = Config::getYclientsApi()->getRecord($saveRecordFromDB['company_id'], $saveRecordFromDB['id'], Config::getConfig()[$saveRecordFromDB['company_id']]);
                $clientsEvents[$clientPhone][self::getTimeKey($clientsEvents, $clientPhone, $deletedRecord['last_change_date'])] = [
                    'event' => self::DELETE,
                    'record' => $deletedRecord,
                ];
            }
        }

        if (
            $createCount !== 0 ||
            $updateCount !== 0 ||
            $deleteCount !== 0
        ){


            foreach($clientsEvents as $clientPhone => $data){
                ksort($data);
                foreach($data as $row){
                    $event = $row['event'];
                    $record = $row['record'];
                    if ($event === self::CREATE){
                        CreateRecord::CreateRecord($record, Config::RequestArchitecture);
                    }
                    if ($event === self::UPDATE){
                        UpdateRecord::UpdateRecord($record, Config::RequestArchitecture);
                    }
                    if ($event === self::DELETE){
                        DeleteRecord::DeleteRecord($record, Config::RequestArchitecture);
                    }
                }
            }
            $message = "<b>TheLashes YclientsRequest Time: ".(microtime(true) - $startPHP) . " sec</b>
createCount = $createCount
updateCount = $updateCount
deleteCount = $deleteCount
";
            Notification::sendAdminTelegram($message);
        }
    }

    public static function getTimeKey($clientsEvents, $clientPhone, $datetime){
        $time = strtotime($datetime);
        if (isset($clientsEvents[$clientPhone][$time])){
            $time = $time + 1;
        }
        return $time;
    }

}