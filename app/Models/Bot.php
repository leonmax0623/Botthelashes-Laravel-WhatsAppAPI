<?php
namespace App\Models;

use Illuminate\Support\Facades\DB;

class Bot
{

    const TABLE = 'bot';

    public static function getBotWherePhone($phone){
        $result = null;
        try {
            $result = DB::table(self::TABLE)->where('phone', $phone)->first();
        } catch (\Exception $e) {
            ModelException::Exception($e, __CLASS__, __FUNCTION__);
        }
        return $result;
    }

    public static function insertBot($insert){
        $result = false;
        try {
            $result = DB::table(self::TABLE)->insert($insert);
        } catch (\Exception $e) {
            ModelException::Exception($e, __CLASS__, __FUNCTION__);
        }
        return $result;
    }

    public static function updateBotWherePhone($phone, $update){
        $int = 0;
        try {
            $int = DB::table(self::TABLE)->where('phone', $phone)->update($update);
        } catch (\Exception $e) {
            ModelException::Exception($e, __CLASS__, __FUNCTION__);
        }
        return $int;
    }

    public static function updateBotWhereUserId($userId, $update){
        $int = 0;
        try {
            $int = DB::table(self::TABLE)->where('userId', $userId)->update($update);
        } catch (\Exception $e) {
            ModelException::Exception($e, __CLASS__, __FUNCTION__);
        }
        return $int;
    }

}