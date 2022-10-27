<?php
namespace App\Yclients\Notification;

use App\Core\Config;

class WhatsApp
{

    public static function sendMessage($clientPhone, $message){
        $whatsApp = new \App\Core\WhatsApp(Config::$waApiUrl, Config::$waToken);
        $data = [
            'phone' => $clientPhone,
            'body' => $message
        ];
        $whatsApp->request('sendMessage', $data);
        $whatsApp->request('readChat', ['phone' => $clientPhone]);
    }

}