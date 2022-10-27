<?php


namespace App\Core;


class Cron
{

    public static function test(){
        Core::sendDev("cron work");
    }

    public static function cronCheckPayment(){
        Payment::cronCheckPaymentFirst(6);
        Payment::cronCheckPaymentFirst(18);
        echo 'ok';
    }

}