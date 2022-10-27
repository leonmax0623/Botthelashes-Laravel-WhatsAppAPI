<?php
namespace App\Models;

use App\Core\Core;
use App\Yclients\Record\Record;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class Records
{

    const TABLE = 'records';

    public static function getRecord($recordId){
        $result = null;
        try {
            $result = DB::table(self::TABLE)->where('recordId', $recordId)->first();
        } catch (\Exception $e) {
            ModelException::Exception($e, __CLASS__, __FUNCTION__);
        }
        return $result;
    }

    public static function insertRecord($record){
        $result = false;
        $recordId = $record['id'];
        if (Cache::get("restoreAfterDebts$recordId") === 1){
            $restoreAfterDebts = 1;
        } else {
            $restoreAfterDebts = 0;
        }
        try {
            $insert = [];
            $insert['recordId'] = $record['id'];
            $insert['record'] = json_encode($record,1);
            $insert['restoreAfterDebts'] = $restoreAfterDebts;
            $insert['phone'] = Record::getClientPhone($record);
            $insert['timeSmsReminder'] = strtotime($record['datetime']) - $record['sms_before'] * 60 * 60;
            if ($record['sms_now']){
                $insert['sendSmsReminder'] = 0;
            } else {
                $insert['sendSmsReminder'] = 1;
            }
            $result = DB::table(self::TABLE)->insert($insert);
        } catch (\Exception $e) {
            ModelException::Exception($e, __CLASS__, __FUNCTION__);
        }
        return $result;
    }


    public static function updateRecordCustomFields($record, $update){
        $int = 0;
        try {
            $int = DB::table(self::TABLE)->where('recordId', $record['id'])->update($update);
        } catch (\Exception $e) {
            ModelException::Exception($e, __CLASS__, __FUNCTION__);
        }
        return $int;
    }

    public static function updateRecord($record){
        $int = 0;
        try {
            $update = [];
            $update['record'] = json_encode($record,1);
            $update['timeSmsReminder'] = strtotime($record['datetime']) - $record['sms_before'] * 60 * 60;
            $int = DB::table(self::TABLE)->where('recordId', $record['id'])->update($update);
        } catch (\Exception $e) {
            ModelException::Exception($e, __CLASS__, __FUNCTION__);
        }
        return $int;
    }

    public static function deleteRecord($record){
        $int = 0;
        self::insertDeletedRecord($record);
        try {
            $int = DB::table(self::TABLE)->where('recordId', $record['id'])->delete();
        } catch (\Exception $e) {
            ModelException::Exception($e, __CLASS__, __FUNCTION__);
        }
        return $int;
    }

    protected static function insertDeletedRecord($record){
        $result = false;
        try {
            $recDel = Records::getRecord($record['id']);
            $result = DB::table('deletedRecords')->insert([
                'recordId' => $record['id'],
                'phone' => Record::getClientPhone($record),
                'deletedForDebts' => $recDel->deletedForDebts,
                'restoreAfterDebts' => $recDel->restoreAfterDebts,
                'record' => json_encode($record,1)
            ]);
        } catch (\Exception $e) {
            ModelException::Exception($e, __CLASS__, __FUNCTION__);
        }
        return $result;
    }

    public static function getDeletedRecord($recordId){
        $result = null;
        try {
            $result = DB::table('deletedRecords')->where('recordId', $recordId)->first();
        } catch (\Exception $e) {
            ModelException::Exception($e, __CLASS__, __FUNCTION__);
        }
        return $result;
    }

    public static function getRecords(){
        $saveRecordsDB = [];
        try {
            $saveRecordsDB = DB::table(self::TABLE)->get();
        } catch (\Exception $e) {
            ModelException::Exception($e, __CLASS__, __FUNCTION__);
        }
        return $saveRecordsDB;
    }

    public static function getAssociatedRecords(){
        $saveRecordsDB = self::getRecords();
        $saveRecords = [];
        foreach($saveRecordsDB as $saveRecordDB){
            $saveRecords[$saveRecordDB->recordId] = json_decode($saveRecordDB->record,1);
        }
        return $saveRecords;
    }

}