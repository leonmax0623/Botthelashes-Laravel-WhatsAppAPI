<?php
namespace App\Yclients\API;

use GuzzleHttp\Exception\RequestException;

class Request
{

    public static $path = 'https://api.yclients.com/api/v1/';

    public static function addRecord($companyId, $userToken, $parameters){
        $url = self::$path."records/$companyId";
        $client = new \GuzzleHttp\Client([
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer '.env('YCLIENTS_PARTNER_TOKEN'). ', User ' . $userToken,
            ]
        ]);
        try {
            $response = $client->request('POST', $url, [
                'json' => $parameters
            ]);
            $response = $response->getBody()->getContents();
//            return $response;
        }
        catch (RequestException $e) {
            $response = ["RequestException - error code 5**"];
            if ($e->hasResponse()) {
                $result = $e->getResponse()->getBody();
                $result->rewind();
                $result = json_decode($e->getResponse()->getBody(),true);
                $response = $result;
            }
            $response = json_encode($response, true);
//            return $response;
        }
        return json_decode($response,1);
    }

    public static function request($companyId, $userToken, $href, $parameters, $method){
        $url = 'https://api.yclients.com/api/v1/';
        $url .= $href . $companyId;
        if (count($parameters)) {
            if ($method === 'GET') {
                $url .= '?' . http_build_query($parameters);
            } else {
//                json_encode($parameters);
            }
        }
//        $url = 'https://api.yclients.com/api/v1/records/company_id?page=1&count=50&staff_id=7572&client_id=572&created_user_id=7572&start_date=2016-02-23&end_date=2016-02-23&c_start_date=2016-02-23&c_end_date=2016-02-23&changed_after=2016-02-23T12%3A00%3A00&changed_before=2016-02-23T12%3A00%3A00&include_consumables=0&include_finance_transactions=0'; . $method;
//        $client = new \GuzzleHttp\Client();
        $client = new \GuzzleHttp\Client([
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer '.env('YCLIENTS_PARTNER_TOKEN'). ', User ' . $userToken,
            ]
        ]);
//        $headers[] = 'Authorization: Bearer ' . env('YCLIENTS_PARTNER_TOKEN') . (is_string($userToken) ? ', User ' . $userToken : '');
        try {
            $response = $client->request('GET', $url, [
                'json' => $parameters,
//                'headers' => $headers
            ]);
            $response = $response->getBody()->getContents();
            return $response;
        }
        catch (RequestException $e) {
            $response = ["RequestException - error code 5**"];
            if ($e->hasResponse()) {
                $result = $e->getResponse()->getBody();
                $result->rewind();
                $result = json_decode($e->getResponse()->getBody(),true);
                $response = $result;
            }
            $response = json_encode($response, true);
            return $response;
        }
    }

    public static function __requestOld($companyId, $userToken, $parameters, $method){
        $url = 'https://api.yclients.com/api/v1/';
        $url .= 'records/' . $companyId;
        if (count($parameters)) {
            if ($method === 'GET') {
                $url .= '?' . http_build_query($parameters);
            } else {
//                json_encode($parameters);
            }
        }
//        $url = 'https://api.yclients.com/api/v1/records/company_id?page=1&count=50&staff_id=7572&client_id=572&created_user_id=7572&start_date=2016-02-23&end_date=2016-02-23&c_start_date=2016-02-23&c_end_date=2016-02-23&changed_after=2016-02-23T12%3A00%3A00&changed_before=2016-02-23T12%3A00%3A00&include_consumables=0&include_finance_transactions=0'; . $method;
//        $client = new \GuzzleHttp\Client();
        $client = new \GuzzleHttp\Client([
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer '.env('YCLIENTS_PARTNER_TOKEN'). ', User ' . $userToken,
            ]
        ]);
//        $headers[] = 'Authorization: Bearer ' . env('YCLIENTS_PARTNER_TOKEN') . (is_string($userToken) ? ', User ' . $userToken : '');
        try {
            $response = $client->request('GET', $url, [
                'json' => $parameters,
//                'headers' => $headers
            ]);
            $response = $response->getBody()->getContents();
            return $response;
        }
        catch (RequestException $e) {
            $response = ["RequestException - error code 5**"];
            if ($e->hasResponse()) {
                $result = $e->getResponse()->getBody();
                $result->rewind();
                $result = json_decode($e->getResponse()->getBody(),true);
                $response = $result;
            }
            $response = json_encode($response, true);
            return $response;
        }
    }

}