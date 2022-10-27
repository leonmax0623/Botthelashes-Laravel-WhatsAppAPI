<?php


namespace App\Core\Yclients;


use App\Core\Core;
use Illuminate\Support\Facades\DB;

class YModel
{

    public static function getSaveRecords(){
        try {
            $saveRecordsDB = DB::table('records')->where([['timeStartVisit', '>', Assistant::_getStartTime()],['timeStartVisit', '<', Assistant::_getEndTime()]])->get();
        } catch (\Exception $e) {
            Core::sendDev(base_path()." YModel::getSaveRecords() ".$e->getMessage());
            return [];
        }
        return $saveRecordsDB;
    }

}