<?php

namespace App\Core\Models;
use App\Core\Core;
use App\Core\YclientsCore;
use Illuminate\Support\Facades\DB;

class Records
{

    public static function getRecord($recordId){
        $result = null;
        try {
            $result = DB::table('records')->where('recordId', $recordId)->first();
        } catch (\Exception $e) {
            ModelException::Exception($e, __CLASS__, __FUNCTION__);
        }
        return $result;
    }

    public static function insertRecord($record){
        try {
            $insert = [];
            $insert['recordId'] = $record['id'];
            $insert['record'] = json_encode($record,1);
            $insert['phone'] = YclientsCore::getClientPhone($record);
            $insert['timeSmsReminder'] = strtotime($record['datetime']) - $record['sms_before'] * 60 * 60;
            if ($record['sms_now']){
                $insert['sendSmsReminder'] = 0;
            } else {
                $insert['sendSmsReminder'] = 1;
            }
            DB::table('records')->insert($insert);
        } catch (\Exception $e) {
            ModelException::Exception($e, __CLASS__, __FUNCTION__);
        }
    }

}