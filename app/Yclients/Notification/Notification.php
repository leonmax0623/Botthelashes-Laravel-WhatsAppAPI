<?php
namespace App\Yclients\Notification;

use App\Core\Config;
use App\Core\Telegram;
use App\Core\WhatsApp;

class Notification
{

    public static function sendAdminTelegram($message){
//        $bot = new Telegram('820009438:AAHAcT41CqjdUTR3HAXHh81a-xMEPOIHlfg');
        $bot = new Telegram('1094054041:AAG6lD0JqVtD7D-z0ZSRtAsnD1JZo2CZDr8');
        $bot->request('sendMessage', ['text' => $message, 'chat_id' => 57625110, 'parse_mode' => 'HTML']);
        $bot->request('sendMessage', ['text' => $message, 'chat_id' => 147256227, 'parse_mode' => 'HTML']);
        $bot->request('sendMessage', ['text' => $message, 'chat_id' => 657871477, 'parse_mode' => 'HTML']); // Михаил личный +79265896504
    }

    public static function sendDevTelegram($message){
        $bot = new Telegram('820009438:AAHAcT41CqjdUTR3HAXHh81a-xMEPOIHlfg');
        $bot->request('sendMessage', ['text' => $message, 'chat_id' => 57625110, 'parse_mode' => 'HTML']);
    }

    public static function sendContactCenterWhatsApp($message){
        $whatsapp = new WhatsApp(Config::$waApiUrl, Config::$waToken);
        $data = [
            'phone' => Config::$ContactCenterPhone,
            'body' => $message
        ];
        $result = $whatsapp->request('sendMessage', $data);
    }

}