<?php
namespace App\Core;

use App\Models\ModelException;
use Illuminate\Support\Facades\DB;

class PaymentModel
{

    public static function getRecordDB($recordId){
        $result = [];
        try {
            $result = DB::table('records')->where('recordId', $recordId)->first();
        } catch (\Exception $e) {
            Core::sendDev(base_path()." PaymentModel::getRecordDB() ".$e->getMessage());
        }
        return $result;
    }

    public static function paymentAdminSendReminder($recordId){
        try {
            DB::table('records')->where('recordId', $recordId)->update(['paymentAdminSendReminder' => 1]);
        } catch (\Exception $e) {
            Core::sendDev(base_path()." PaymentModel::paymentAdminSendReminder() ".$e->getMessage());
        }
    }

    public static function paymentSendReminder($recordId){
        try {
            DB::table('records')->where('recordId', $recordId)->update(['paymentSendReminder' => 1]);
        } catch (\Exception $e) {
            Core::sendDev(base_path()." PaymentModel::paymentSendReminder() ".$e->getMessage());
        }
    }

//    public static function getRecordPaymentStatus($record){
//        $paymentStatus = null;
//        try {
//            $recordDB = DB::table('records')->where('recordId', $record['id'])->first();
//            $paymentStatus = $recordDB->paymentStatus;
//        } catch (\Exception $e) {
//            Core::sendDev(base_path()." PaymentModel::getRecordPaymentStatus() recordID = ".$record['id']."
//".$e->getMessage());
//        }
//        return $paymentStatus;
//    }

    public static function paymentSave($record, $sum){
        try {
            $recordDB = DB::table('records')->where('recordId', $record['id'])->first();
            $paymentStatus = $recordDB->paymentStatus;
            if ($paymentStatus === null){
                $update = [
                    'paymentSum' => $sum,
                    'paymentTime' => time(),
                    'paymentDateTime' => date("Y-m-d H:i:s"),
                    'paymentStatus' => 0, // по умолчанию null, ставим 0 - т.е. не оплачено
                ];
                DB::table('records')->where('recordId', $record['id'])->update($update);
            }
        } catch (\Exception $e) {
            Core::sendDev(base_path()." PaymentModel::paymentSave() recordID = ".$record['id']." 
".$e->getMessage());
        }
    }

    public static function paymentSuccess($recordId, $total){
        try {
            $r = DB::table('records')->where('recordId', $recordId)->update([
                'paymentSumPaid' => $total,
                'paymentStatus' => 'ok',
                'paymentTimePaid' => time(),
                'paymentDateTimePaid' => date("Y-m-d H:i:s")
            ]);
        } catch (\Exception $e) {
            Core::sendDev(base_path()." PaymentModel::paymentSuccess() ".$e->getMessage());
        }
    }

    public static function paymentDelete($record){
        try {
            DB::table('records')->where('recordId', $record['id'])->update(['paymentStatus' => 'delete', 'paymentDeleteDateTime' => date("Y-m-d H:i:s")]);
        } catch (\Exception $e) {
            Core::sendDev(base_path()." PaymentModel::paymentDelete() ".$e->getMessage());
        }
    }

    public static function getRecordsDB($where){
        $result = [];
        try {
            $result = DB::table('records')->where($where)->get();
        } catch (\Exception $e) {
            Core::sendDev(base_path()." PaymentModel::getRecordsDB() ".$e->getMessage());
        }
        return $result;
    }

    public static function getPaymentConfig(){
        $result = [];
        try {
            $result = DB::table('config')->where('id', 1)->first();
        } catch (\Exception $e) {
            ModelException::Exception($e, __CLASS__, __FUNCTION__);
        }
        return $result;
    }

    public static function configUpdate($config){
        try {

            DB::table('config')->where('id', 1)->update([
                'value' => json_encode($config,1)
            ]);
        } catch (\Exception $e) {
            Core::sendDev(base_path()." PaymentModel::configUpdate() ".$e->getMessage());
        }
    }

}