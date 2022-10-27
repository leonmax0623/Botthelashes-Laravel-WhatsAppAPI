<?php
namespace App\Yclients\Record;

use App\Core\Config;
use App\Core\Yclients\Assistant;
use App\Yclients\Assistant\Str;

class Record
{

    public static function getClientPhone($record){
        $clientPhone = null;
        if (isset($record['client']['phone'])) {
            $clientPhone = Str::returnOnlyNumberOfString($record['client']['phone']);
        }
        return $clientPhone;
    }

    public static function getClientId($record){
        $clientId = null;
        if (isset($record['client']['id'])) {
            $clientId = $record['client']['id'];
        }
        return $clientId;
    }

    public static function getClientName($record, $defaultName = 'Имя не указано', $afterName = ''){
        $clientName = $defaultName;
        if (!empty($record['client']['name'])) {
            $clientName = $record['client']['name'] . $afterName;
        }
        return $clientName;
    }

    public static function getStaffId($record){
        $staffId = null;
        if(isset($record['staff']['id'])){
            $staffId = $record['staff']['id'];
        }
        return $staffId;
    }

    public static function getStaffName($record){
        $staffName = 'Мастер не указан';
        if (!empty($record['staff']['name'])) {
            $staffName = $record['staff']['name'];
        }
        return $staffName;
    }

    public static function getRecordServicesIds($record){
        $servicesIds = [];
        if (isset($record['services']) && is_array($record['services']) && count($record['services']) > 0){
            foreach($record['services'] as $service){
                $servicesIds[] = $service['id'];
            }
        }
        return $servicesIds;
    }

    public static function getActualRecordsFromYclients(){
        $actualRecords = [];
        foreach(Config::getConfig() as $companyId => $userToken){
//            $param = [
//                'count' => 1000,
//                'start_date' => Assistant::_getDateTimeObject(Assistant::_getTimeDefault(Assistant::_getStartTime())),
//                'end_date' => Assistant::_getDateTimeObject(Assistant::_getTimeDefault(Assistant::_getEndTime()))
//            ];
//            $actualRecords[$companyId] = \App\Yclients\API\Request::request($companyId, $userToken, $param, "GET");
            $actualRecords[$companyId] = Config::getYclientsApi()->getRecords($companyId, $userToken, null, 1000, null, null,
                Assistant::_getDateTimeObject(Assistant::_getTimeDefault(Assistant::_getStartTime())),
                Assistant::_getDateTimeObject(Assistant::_getTimeDefault(Assistant::_getEndTime()))
            );
        }
        $actualRecordsFromYclients = [];
        foreach(Config::getConfig() as $companyId => $userToken){
            if (isset($actualRecords[$companyId]['data']) && is_array($actualRecords[$companyId]['data']) && count($actualRecords[$companyId]['data']) > 0){
                foreach($actualRecords[$companyId]['data'] as $actualRecord){
                    $actualRecordsFromYclients[$actualRecord['id']] = $actualRecord;
                }
            }
        }
        return $actualRecordsFromYclients;
    }

}