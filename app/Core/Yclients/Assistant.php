<?php


namespace App\Core\Yclients;


use App\Core\Config;
use App\Core\theLashesModel;

class Assistant
{
    public static function getActualRecords(){
        $actualRecords = self::getAllRecords(Assistant::_getStartTime(), Assistant::_getEndTime());
        $actual = [];
        foreach(Config::getConfig() as $companyId => $userToken){
            foreach($actualRecords[$companyId]['data'] as $actualRecord){
                $actual[$actualRecord['id']] = $actualRecord;
            }
        }
        return $actual;
        print_r("<pre>");
        print_r($actualRecords);
        print_r("</pre>");

//        $c=0;
//        foreach(Config::getConfig() as $companyId => $userToken){
//            foreach($actualRecords[$companyId]['data'] as $actualRecord){
//                if (!empty($saveRecords[$actualRecord['id']])){
//                    print_r("<pre>");
//                    print_r('этот рекорд есть в базе. Нужно проеврить на UPDATE');
//                    print_r("</pre>");
//                } else {
//                    print_r("<pre>");
//                    print_r('этого рекорда нет в базе. ДОБАВЛЕН НОВЫЙ РЕКОРД! CREATE !!!!');
//                    print_r("</pre>");
//                }
//
//
//
//
//                DB::table('records')->where('recordId',$actualRecord['id'])->update(['timeStartVisit' => strtotime($actualRecord['datetime'])]);
////                print_r("<pre>");
////                print_r($rec);
////                print_r("</pre>");
//                $c++;
//            }
//        }
    }

    public static function getSaveRecords(){
        $saveRecordsDB = theLashesModel::getSaveRecords();
        $saveRecords = [];
        foreach($saveRecordsDB as $saveRecordDB){
            $saveRecords[$saveRecordDB->recordId] = json_decode($saveRecordDB->record,1);
        }
        return $saveRecords;
    }

    public static function _getStartTime(){
        return mktime(0, 0, 0, date("m"), date("d"), date("Y"));
    }

    public static function _getEndTime(){
        return mktime(23, 59, 59, date("m")  , date("d") + 180, date("Y"));
    }

    public static function getAllRecords($startTime = null, $endTime = null){
        $records = [];
        foreach(Config::getConfig() as $companyId => $userToken){
            $records[$companyId] = Config::getYclientsApi()->getRecords($companyId, $userToken, null, 1000, null, null,
                self::_getDateTimeObject(self::_getTimeDefault($startTime)),
                self::_getDateTimeObject(self::_getTimeDefault($endTime))
            );
        }
        return $records;
    }

    public static function _getDateTimeObject($time){
        $DateTime = new \DateTime;
        $DateTime->setDate(date('Y', $time), date('m', $time), date('d', $time));
        return $DateTime;
    }

    public static function _getTimeDefault($time){
        if ($time == null){
            $time = time() + 60 * 60 * 24;
        }
        return $time;
    }
}