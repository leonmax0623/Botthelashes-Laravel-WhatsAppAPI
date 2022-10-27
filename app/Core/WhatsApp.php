<?php
namespace App\Core;
use App\Yclients\Modules\History\History;

class WhatsApp
{
    public $token;
    public $waApiUrl;
    public $client;

    public function __construct($waApiUrl, $token)
    {
        $this->token = $token;
        $this->waApiUrl = $waApiUrl;
        $this->client = new \GuzzleHttp\Client(['timeout'  => 60]);
    }

    public function request($method, $data, $defaultBotMessage = 0){
        $uri = $this->waApiUrl.$method.$this->token;
        $sleep = rand(1, 5); sleep($sleep);

        try {
            if (isset($data['body'])){
                $message = $data['body'];
                $userPhone = NULL;
                if (isset($data['chatId'])){
                    $userPhone = YclientsCore::getOnlyNumberString($data['chatId']);
                }
                if (isset($data['phone'])){
                    $userPhone = $data['phone'];
                }
                History::addSendBotMessageToHistory($userPhone, $message, $defaultBotMessage);
            }
        }
        catch (\GuzzleHttp\Exception\RequestException $e) {
            Core::sendDev('The Lashes WhatsApp->request .. History::addSendBotMessageToHistory Error');
        }

        try {
            $response = $this->client->request('POST', $uri, [
                'form_params' => $data
            ]);

//            Core::sendDev('The Lashes WA response error = '.$response);
//            Core::sendDev('ОТПРАВЛЕН ЗАПРОС В chat api The Lashes WA response success = '.$response->getBody()->getContents());
            return $response->getBody()->getContents(); // string
        }
        catch (\GuzzleHttp\Exception\RequestException $e) {
            $response = 'The Lashes Bot - RequestException - error code 5** - '.json_encode($data,1); //string
//            if ($e->hasResponse()) {
//                $result = $e->getResponse()->getBody();
//                $result->rewind();
//                $response = $e->getResponse()->getBody(); // string
//            }
            Core::sendDev('The Lashes WA response error = '.$response);
            return $response;
        }
    }

    public function requestGet($method){
        $uri = $this->waApiUrl.$method.$this->token;
        try {
            $response = $this->client->request('GET', $uri);
            return $response->getBody()->getContents();
        }
        catch (\GuzzleHttp\Exception\RequestException $e) {
            $response = ['RequestException - error code 5** - '.$uri];
            if ($e->hasResponse()) {
                $result = $e->getResponse()->getBody();
                $result->rewind();
                $response = $e->getResponse()->getBody();
            }
            return $response;
        }
    }

}