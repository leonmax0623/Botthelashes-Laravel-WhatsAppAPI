<?php
namespace App\Core;


use App\Core\Telegram;
use GuzzleHttp\Client;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
use Illuminate\Support\Facades\DB;

class Core
{


    public static function sendDev($message){
//        $bot = new Telegram('820009438:AAFNlDdJ5ci1uYlYTs2UWRZHR-Nuspi-fao');
//        $bot->request('sendMessage', ['text' => $message, 'chat_id' => 57625110, 'parse_mode' => 'HTML']);
    }

    public static function sendMichael($message){
        $bot = new Telegram('820009438:AAFNlDdJ5ci1uYlYTs2UWRZHR-Nuspi-fao');
        $bot->request('sendMessage', ['text' => $message, 'chat_id' => 657871477, 'parse_mode' => 'HTML']);
//        $bot->request('sendMessage', ['text' => $message, 'chat_id' => 57625110, 'parse_mode' => 'HTML']);

    }

    public static function updateUserAfterAutoConfirmation(){
        $user = DB::table('bot')->where('userId', $data['userId'])->first();
    }

    public static function getOrCreateUser($data){
        $user = self::selectUser($table, $data, $notification);
        if ($user === null){
            Core::insertNewUser($table, $data, $notification);
            $user = Core::selectUser($table, $data, $notification);
        }
        return $user;
    }

    public static function selectUser($table, $data, $notification){
        try {
            $user = DB::table($table)->where('vendor', $data['vendor'])->where('userId', $data['userId'])->first();
            return $user;
        } catch (\Exception $e) {
            if(!empty($data['userId'])){ $userId = $data['userId']; } else { $userId = 'unknown'; }
            self::sendException($notification, $e, $userId, "SELECT пользователя.");
            return die();
        }
    }


    public static function insertNewUser($table, $data, $notification)
    {
        $insert = [];
        $insert['vendor'] = 'whatsapp';
        $insert['userId'] = $data['userId'];
        $insert['phone'] = json_decode($data['firstName'],1);
        $insert['current'] = json_decode($data['lastName'],1);
        $insert['userName'] = $data['userName'];
        $insert['request'] = json_encode([], true);
        $insert['segments'] = json_encode([], true);
        $insert['forms'] = json_encode([], true);
        $insert['response'] = json_encode([], true);
        $insert['resultOfResponse'] = json_encode([], true);
        $insert['timeOfOperation'] = json_encode([], true);
        $insert['updatedTime'] = time();
        $insert['createdTime'] = time();
        try {
            DB::table($table)->insert($insert);
        } catch (\Exception $e) {
            self::sendException($notification, $e, $data['userId'], "INSERT пользователя.");
        }
    }



    const TELEGRAM = 'telegram';
    const VIBER = 'viber';
    const WHATSAPP = 'whatsapp';
    const VK = 'vk';
    const FACEBOOK = 'facebook';
    const INSTAGRAM = 'instagram';

    public static function getDataPhpInputFromBot($vendor, $input){
        switch($vendor){
            case self::TELEGRAM:
                $data = Telegram::getData($input);
                break;
            case self::VIBER:
                if ($input['event'] !== 'message'){die();} // если это не входящее сообщение, то рубим
                $data['userId'] = 0;
                $data['firstName'] = "";
                $data['lastName'] = "";
                $data['userName'] = "";
                if (!empty($input["sender"]["id"])) { $data['userId'] = $input["sender"]["id"]; }
                if (!empty($input["sender"]["name"])) {
                    $data['firstName'] = self::cleanString($input["sender"]["name"]);
//                    $data['lastName'] = $input["sender"]["name"];
//                    $data['userName'] = $input["sender"]["name"];
                }
                if (!empty($input["message"]["text"])) { $data['userCommand'] = $input["message"]["text"]; } else {$data['userCommand'] = null;}
                if (!empty($input["message"]["contact"]["phone_number"])) { $data['phone'] = $input["message"]["contact"]["phone_number"]; }
                break;
        }
        $data['vendor'] = $vendor;
        return $data;
    }

    public static function cleanString($str){
        $str = preg_replace( "/[^а-яёa-z]/iu", '', $str );
        return $str;
    }

    public static function createBot($vendor, $config, $notification = []){
        $bot = null;
        switch($vendor){
            case self::TELEGRAM: $bot = new Telegram($config[self::TELEGRAM], $notification); break;
            case self::VIBER: $bot = new Viber($config[self::VIBER], $notification); break;
        }
        return $bot;
    }





//    public static function insertNewUser($table, $data, $notification)
//    {
//        $insert = [];
//        $insert['vendor'] = $data['vendor'];
//        $insert['userId'] = $data['userId'];
//        $insert['firstName'] = json_decode($data['firstName'],1);
//        $insert['lastName'] = json_decode($data['lastName'],1);
//        $insert['userName'] = $data['userName'];
//        $insert['request'] = json_encode([], true);
//        $insert['segments'] = json_encode([], true);
//        $insert['forms'] = json_encode([], true);
//        $insert['response'] = json_encode([], true);
//        $insert['resultOfResponse'] = json_encode([], true);
//        $insert['timeOfOperation'] = json_encode([], true);
//        $insert['updatedTime'] = time();
//        $insert['createdTime'] = time();
//        try {
//            DB::table($table)->insert($insert);
//        } catch (\Exception $e) {
//            self::sendException($notification, $e, $data['userId'], "INSERT пользователя.");
//        }
//    }

    public static function updateUser($table, $user, $notification, $wait = 0)
    {
        $update = [];
        $update['vendor'] = $user->vendor;
        $update['firstName'] = json_decode($user->firstName,1);
        $update['lastName'] = json_decode($user->lastName,1);
        $update['userName'] = $user->userName;
        $update['phone'] = $user->phone;
        $update['current'] = $user->current;
        $update['segments'] = $user->segments;
        $update['wait'] = 0;
        $update['blocked'] = 0;
        $update['forms'] = $user->forms;
        $update['request'] = $user->request;
        $update['response'] = $user->response;
        $update['resultOfResponse'] = $user->resultOfResponse;
        $update['timeOfOperation'] = $user->timeOfOperation;
        $update['updatedTime'] = time();
        try {
            DB::table($table)->where('vendor', $user->vendor)->where('userId', $user->userId)->update($update);
        } catch (\Exception $e) {
            self::sendException($notification, $e, $user->userId, "UPDATE пользователя.");
        }
    }

    public static function sendException($notification, \Exception $e, $userId, $type){
        if (!empty($notification[self::TELEGRAM])){
            $bot = new Telegram($notification[self::TELEGRAM]['token']);
            foreach($notification[self::TELEGRAM]['userIds'] as $chatId){
                $bot->request('sendMessage', [
                    'chat_id' => $chatId,
                    'parse_mode' => 'HTML',
                    'text' => "
<b>Внимание! Критическая ошибка!</b>
$type
ID: <b>" . $userId . "</b>
" . $e->getMessage() . "

"]);
            }
        }
    }


    public static function sendCoreException($notification, $text){
//        die('ok');
        if (!empty($notification[self::TELEGRAM])){
            $bot = new Telegram($notification[self::TELEGRAM]['token'], $notification);
            foreach($notification[self::TELEGRAM]['userIds'] as $chatId){
                $bot->request('sendMessage', [
                    'chat_id' => $chatId,
                    'parse_mode' => 'HTML',
                    'text' => $text
                ]);
            }
        }
        if (!empty($notification[self::VIBER])){
            $bot = new Viber($notification[self::VIBER]['token'], $notification);
            foreach($notification[self::VIBER]['userIds'] as $chatId){
                $bot->request('send_message', [
                    'receiver' => $chatId,
                    'type' => 'text',
                    'text' => $text
                ]);
            }
        }
    }

    public static function insertInputOrigin($table, $vendor, $inputOrigin, $notification){
        try {
            DB::insert("INSERT INTO $table (vendor, indata) VALUES (?, ?)", [$vendor, $inputOrigin]);
        } catch (\Exception $e) {
            $exceptionText = "
<b>Внимание! Критическая ошибка!</b>
Core - $vendor - insertInputOrigin

Message: " . $e->getMessage() . "

Input: $inputOrigin";
            Core::sendCoreException($notification, $exceptionText);
        }
    }



    public static function getWebhookInfoTelegram($token){
        $url = "https://api.telegram.org/bot$token/getWebhookInfo";
        $client = new \GuzzleHttp\Client();
        $response = $client->request('GET', $url);
        $response = $response->getBody()->getContents();
        print_r("<pre>");
        print_r($response);
        print_r("</pre>");
    }




    public static function returnBotsClass($config, $notification){
        $bots = [];
        foreach($config as $vendor => $token){
            $bots[$vendor] = Core::createBot($vendor, $config, $notification);
        }
        return $bots;
    }


    public static function sendPoolGuzzleAsyncRequests($uri, $users, $command, $numberOfUsersInOneConcurrency){
        $client = new Client();
        $concurrency = ceil(count($users) / $numberOfUsersInOneConcurrency); // кол-во потоков
        $users = self::getUsersDividedIntoParts($users, $concurrency, $numberOfUsersInOneConcurrency);
        $requests = function($uri, $users, $command) {
            foreach($users as $concurrencyID => $partOfUsers){
                $json = json_encode([
                    'users' => $partOfUsers,
                    'command' => $command,
                ], 1);
                yield new Request('POST', $uri, [], $json);
            }
        };
        $pool = new Pool($client, $requests($uri, $users, $command), [
            'concurrency' => $concurrency, // кол-во потоков
            'fulfilled' => function ($response, $index) { /* удачный запрос */ },
            'rejected' => function ($reason, $index) { /* НЕ удачный запрос */ },
        ]);
        $promise = $pool->promise();
        $promise->wait();
    }

    public static function getUsersDividedIntoParts($users, $concurrency, $numberOfUsersInOneConcurrency){
        $usersDividedIntoParts = [];
        for($i = 0; $i < $concurrency; $i++){
            $j = 0;
            while($j < $numberOfUsersInOneConcurrency && $i * $numberOfUsersInOneConcurrency + $j < count($users)){
                $usersDividedIntoParts[$i][] = $users[$i * $numberOfUsersInOneConcurrency + $j];
                $j++;
            }
        }
        return $usersDividedIntoParts;
    }

}
