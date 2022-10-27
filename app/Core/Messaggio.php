<?php
namespace App\Core;

class Messaggio
{

    public static function sendSMS($login, $phone, $message, $sender, $secret_key){
        $sign = md5($login.$sender.$phone.$message.$secret_key);
        $uri = "https://bulk.sms-online.com/?sending_method=sms&from=$sender&user=$login&txt=".urlencode($message)."&phone=$phone&sign=$sign";
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