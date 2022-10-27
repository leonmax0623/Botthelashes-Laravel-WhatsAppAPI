<?php


namespace App\Core;


use GuzzleHttp\Exception\RequestException;

class Telegram
{
    public $url;
    public $notification;

    public function __construct($token, $notification = [])
    {
        $this->url = "https://api.telegram.org/bot$token/";
        $this->notification = $notification;
    }

    public function request($method, $data){
        $url = $this->url . $method;
        $client = new \GuzzleHttp\Client();


//        $text = $data['text'];
//        $chat_id = $data['chat_id'];
//        $client->request('POST', $this->url . 'sendMessage', [
//            'json' => [
//                'chat_id' => 57625110,
//                'parse_mode' => 'HTML',
//                'text' => "
//<b>Отправка сообщения ...</b>
//Text: $text
//ID: $chat_id
//"]
//        ]);

        try {
            $response = $client->request('POST', $url, [
                'json' => $data
            ]);
            $response = $response->getBody()->getContents();

//            $ok = json_decode($response, true);
//            if ($ok['ok']){
//                Core::sendCoreException($this->notification, '11');
//            }


            return $response;
//            $statusCode = $response->getStatusCode();
//            $reasonPhrase = $response->getReasonPhrase();
        }
        catch (RequestException $e) {
//            $exceptionRequest = Psr7\str($e->getRequest());
            $response = ["RequestException - error code 5**"];
            if ($e->hasResponse()) {
//                $exceptionResponse = Psr7\str($e->getResponse());
                $result = $e->getResponse()->getBody();
                $result->rewind();
                $result = json_decode($e->getResponse()->getBody(),true);
//                $ok = $result["ok"];
//                $code = $result["error_code"];
//                $description = $result["description"];
//                $response = "Error code: $code, description: $description";
                $response = $result;
            }

//            $exceptionText = "
//<b>Внимание! Критическая ошибка!</b>
//Telegram Core - GuzzleHttp RequestException
//ID: <b>" . $data['chat_id'] . "</b>
//" . json_encode($response) . "
//
//";
//            Core::sendCoreException($this->notification, $exceptionText);

            $response = json_encode($response, true);
            return $response;

        }
    }


    public static function getData($data){
        $result = [];
        $result["userId"] = self::getTelegramUserId($data);
        $result["firstName"] = self::getTelegramFirstName($data);
        $result["lastName"] = self::getTelegramLastName($data);
        $result["userName"] = self::getTelegramUserName($data);
        $result["userCommand"] = self::getTelegramUserCommand($data);
        $result["typeCommand"] = self::getTelegramTypeCommand($data);
        $result["phone"] = self::getTelegramUserPhone($data);
        return $result;
    }

    public static function cleanString($str){
        $str = preg_replace( "/[^а-яёa-z]/iu", '', $str );
        return $str;
    }

    public static function getTelegramUserId($data){
        $userId = 0;
        if (!empty($data["message"]["from"]["id"])) { $userId = $data["message"]["from"]["id"]; }
        if (!empty($data["callback_query"]["from"]["id"])) { $userId = $data["callback_query"]["from"]["id"]; }
        if (!empty($data["edited_message"]["from"]["id"])) { $userId = $data["edited_message"]["from"]["id"]; }
        return $userId;
    }

    public static function getTelegramFirstName($data){
        $firstName = "";
        if (!empty($data["message"]["from"]["first_name"])) { $firstName = $data["message"]["from"]["first_name"]; }
        if (!empty($data["callback_query"]["from"]["first_name"])) { $firstName = $data["callback_query"]["from"]["first_name"]; }
        if (!empty($data["edited_message"]["from"]["first_name"])) { $firstName = $data["edited_message"]["from"]["first_name"]; }
        $firstName = self::cleanString($firstName);
        return $firstName;
    }

    public static function getTelegramLastName($data){
        $lastName = "";
        if (!empty($data["message"]["from"]["last_name"])) { $lastName = $data["message"]["from"]["last_name"]; }
        if (!empty($data["callback_query"]["from"]["last_name"])) { $lastName = $data["callback_query"]["from"]["last_name"]; }
        if (!empty($data["edited_message"]["from"]["last_name"])) { $lastName = $data["edited_message"]["from"]["last_name"]; }
        $lastName = self::cleanString($lastName);
        return $lastName;
    }

    public static function getTelegramUserName($data){
        $userName = "";
        if (!empty($data["message"]["from"]["username"])) { $userName = $data["message"]["from"]["username"]; }
        if (!empty($data["callback_query"]["from"]["username"])) { $userName = $data["callback_query"]["from"]["username"]; }
        if (!empty($data["edited_message"]["from"]["username"])) { $userName = $data["edited_message"]["from"]["username"]; }
        return $userName;
    }

    public static function getTelegramTypeCommand($data){
        $typeCommand = "undefined";
        if (!empty($data["message"]["text"])) { $typeCommand = "text"; }
        if (!empty($data["message"]["voice"])) { $typeCommand = "voice"; }
        if (!empty($data["message"]["video_note"])) { $typeCommand = "video_note"; }
        if (!empty($data["message"]["photo"])) { $typeCommand = "photo"; }
        if (!empty($data["message"]["document"])) { $typeCommand = "document"; }
        if (!empty($data["message"]["sticker"])) { $typeCommand = "sticker"; }
        if (!empty($data["callback_query"]["data"])) { $typeCommand = "callback_query"; }
        if (!empty($data["edited_message"]["text"])) { $typeCommand = "edited_message"; }
        return $typeCommand;
    }

    public static function getTelegramUserCommand($data){
        $userCommand = "undefined";
        if (!empty($data["message"]["text"])) { $userCommand = $data["message"]["text"]; }
        if (!empty($data["callback_query"]["data"])) { $userCommand = $data["callback_query"]["data"]; }
        if (!empty($data["edited_message"]["text"])) { $userCommand = $data["edited_message"]["text"]; }
        return $userCommand;
    }

    public static function getTelegramUserPhone($data){
        $phone = null;
        if (!empty($data["message"]["contact"]["phone_number"])) { $phone = preg_replace("/[^0-9]/", '', $data["message"]["contact"]["phone_number"]); }
        return $phone;
    }
}