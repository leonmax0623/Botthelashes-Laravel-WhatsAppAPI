<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Exception\RequestException;

class WaController extends Controller
{

    public $token = '?token=ozppye4kz0jrvx4z';
    public $client;
    public $waApiUrl = 'https://eu36.chat-api.com/instance41407/';

    public function __construct()
    {
        $this->client = new \GuzzleHttp\Client(['timeout'  => 2]);
    }

    public function setWaWebhook(){
        $response = $this->client->request('POST', $this->waApiUrl.'webhook'.$this->token, [
            'form_params' => [
                'webhookUrl' => 'https://'.$_SERVER['HTTP_HOST'].'/wa/webhook'
            ]
        ]);
        return $response->getBody()->getContents();
    }

    public function reload(){
//        $response = $this->client->request('GET', $this->waApiUrl.'reload'.$this->token);
//        $result = $response->getBody()->getContents();
//        print_r("<pre>");
//        print_r($result);
//        print_r("</pre>");


    }
    public function getWaWebhook(){
        $response = $this->client->request('GET', $this->waApiUrl.'webhook'.$this->token);
        return $response->getBody()->getContents();
    }

    public function send(){
        echo time();
        $startPHP = microtime(true);
        try {
            $response = $this->client->request('POST', $this->waApiUrl.'sendMessage'.$this->token, [
                'form_params' => [
//                    'phone' => '792787887990878',
                    'phone' => '79265896504',
                    'body' => '
Сейчас можно протестировать:
1. Уведомлении об онлайн-записи через виджет
2. Уведомление о создании записи внутри Yclients

Если "Лист ожидания", то свой текст. Если мастер, то к мастеру.

Имя берется из Yclients. Потому что в WhatsApp зачастую имя и фамилия, а мы не сможем узнать что из этого имя. Также там часто сердечки
или просто набор символов. Если в айклаентс имени нет, то вместо имени "Здравствуйте!". так только имя появиться, будет персонализированное

Сообщение корректно, если запись без услуги, с услугой или с множеством услуг.

Можете протестировать, сообщение придет вам.

https://thelashes.falcons-it.com/AutoConfirmation - перейдя по ссылке вы запускаете рассылку авто-подтверждений и получите то, что придет клиентам.

Сейчас все сообщения идут вам.
Реальным клиентам ничего не идет!

Чуть позже подключу уведомление об запросе обратной связи.

Затем, чтобы бот отвечал на ваши сообщения, т.е. реагировал на ответы нужно будет подключить ваш номер в chat-api.com

Тогда без привязки к моему личному номеру телефону, можно будет корректно настроить. Можно и на моем, но мне могут писать, и сбивать. Вы будете думать, что вам пишет клиент, а на самом деле это условно моя мама :)

Поэтому предлагаю тоже там зарегистрироваться и подключить номер. Попутно сейчас все сообщения отредактируем, если нужно.
'
                ]
            ]);
            print_r("<pre>");
            print_r($response->getBody()->getContents());
            print_r("</pre>");
        }
        catch (RequestException $e) {

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

            print_r("<pre>");
            print_r($response);
            print_r("</pre>");

        }
        echo (microtime(true) - $startPHP);
    }

    public function wa()
    {
        echo time();
        $startPHP = microtime(true);
        try {
            $response = $this->client->request('POST', $this->waApiUrl.'sendMessage'.$this->token, [
                'form_params' => [
//                    'phone' => '792787887990878',
                    'phone' => '380635819801',
                    'body' => 'тут?'
                ]
            ]);
            print_r("<pre>");
            print_r($response->getBody()->getContents());
            print_r("</pre>");
        }
        catch (RequestException $e) {

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

            print_r("<pre>");
            print_r($response);
            print_r("</pre>");

        }
        echo (microtime(true) - $startPHP);
    }
}
