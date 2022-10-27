<?php
namespace App\Yclients\Assistant;

class Str
{

    public static function returnOnlyNumberOfString($number){
        $number = preg_replace("/[^0-9]/", '', $number);
        return $number;
    }

}