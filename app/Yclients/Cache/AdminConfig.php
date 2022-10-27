<?php

namespace App\Yclients\Cache;

use App\Core\Config;
use App\Core\PaymentModel;
use Illuminate\Support\Facades\Cache;

class AdminConfig
{

    public static function get(){
        if (Cache::get("AdminConfig")){
            $result = Cache::get("AdminConfig");
        } else {
            $result = self::save();
        }
        return $result;
    }

    public static function save(){
        $config = json_decode(PaymentModel::getPaymentConfig()->value,1);
        Cache::put("AdminConfig", $config, 2000);
        return $config;
    }

}