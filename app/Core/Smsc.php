<?php
namespace App\Core;

class Smsc
{

    public static function sendSMS($login, $psw, $phone, $message, $sender){
        $uri = "https://smsc.ru/sys/send.php?login=$login&psw=$psw&phones=$phone&mes=".urlencode($message)."&sender=".urlencode($sender);
        $client = new \GuzzleHttp\Client();
        try {
            $response = $client->request('GET', $uri);
            return $response->getBody()->getContents();
        }
        catch (\GuzzleHttp\Exception\RequestException $e) {
            $response = 'RequestException - error code 5** - '.$uri;
            if ($e->hasResponse()) {
                $result = $e->getResponse()->getBody();
                $result->rewind();
                $response = $e->getResponse()->getBody();
            }
            return $response;
        }
    }

}