<?php
namespace App\Core;


use App\Models\ModelException;
use Illuminate\Support\Facades\DB;

class theLashesModel
{

    public static function getAllHashes(){
        $hashes = [];
        try {
            $result = DB::table('smsId')->get();
            $hashes = [];
            foreach($result as $row){
                $hashes[$row->hash] = $row->code;
            }
        } catch (\Exception $e) {
            YclientsCore::sendTelegramLog(base_path()." theLashesModel::getAllHashes() ".$e->getMessage());
        }
        return $hashes;
    }

    public static function getUserBotFromDB($phone){
        $user = null;
        try {
            $user = DB::table('bot')->where('phone', $phone)->first();
        } catch (\Exception $e) {
            YclientsCore::sendTelegramLog(base_path()." theLashesModel::getUserBotFromDB($phone) ".$e->getMessage());
        }
        return $user;
    }

    public static function getHash($hash){
        $result = false;
        try {
            $result = DB::table('smsId')->where('hash', $hash)->first();
        } catch (\Exception $e) {
            YclientsCore::sendTelegramLog(base_path()." theLashesModel::getAllHashes() ".$e->getMessage());
        }
        return $result;
    }

    public static function getRecord($recordId){
        $result = false;
        try {
            $result = DB::table('records')->where('recordId', $recordId)->first();
            if ($result === null){
                $result = DB::table('deletedRecords')->where('recordId', $recordId)->first();
            }
            // поискать в удаленных рекордах.
        } catch (\Exception $e) {
            YclientsCore::sendTelegramLog(base_path()." theLashesModel::getRecord() ".$e->getMessage());
        }
        return $result;
    }

    public static function insertToBot($insert){
        try {
            DB::table('bot')->insert($insert);
        } catch (\Exception $e) {
            YclientsCore::sendTelegramLog(base_path()." theLashesModel::insertToBot() ".$e->getMessage());
        }
    }

    public static function updateBot($phone, $update){
        try {
            DB::table('bot')->where('phone', $phone)->update($update);
        } catch (\Exception $e) {
            YclientsCore::sendTelegramLog(base_path()." theLashesModel::updateBot() ".$e->getMessage());
        }
    }

    public static function getAdminConfigDB(){
        $result = [];
        try {
            $result = DB::table('config')->where('id', 1)->first();
        } catch (\Exception $e) {
            Core::sendDev(base_path()." PaymentModel::getAdminConfigDB() ".$e->getMessage());
        }
        return $result;
    }

    public static function getSaveRecords(){
        try {
            $saveRecordsDB = DB::table('records')->get();
        } catch (\Exception $e) {
            Core::sendDev(base_path()." theLashesModel::getSaveRecords() ".$e->getMessage());
            return [];
        }
        return $saveRecordsDB;
    }

    public static function insertSmsId($code){
        try {
            $rand = rand(100,999);
            $code = $code ."-".$rand;
            $hash = date("s").substr(md5($code), 2, 6);
            DB::table('smsId')->insert([
                'code' => $code,
                'hash' => $hash
            ]);
        } catch (\Exception $e) {
            ModelException::Exception($e, __CLASS__, __FUNCTION__);
            $hash = false;
        }
        return $hash;
    }



}