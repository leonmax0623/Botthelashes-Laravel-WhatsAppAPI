<?php
namespace App\Yclients\Cache;

use App\Core\Config;
use Illuminate\Support\Facades\Cache;

class CompaniesInfo
{

    public static function get(){
        if (Cache::get("companies")){
            $companies = Cache::get("companies");
        } else {
            $companies = self::save();
        }
        return $companies;
    }

    public static function save(){
        $companies = [];
        foreach(Config::getConfig() as $companyId => $userToken){
            $companies[$companyId] = Config::getYclientsApi()->getCompany($companyId);
        }
        Cache::put("companies", $companies, 2000);
        return $companies;
    }

}