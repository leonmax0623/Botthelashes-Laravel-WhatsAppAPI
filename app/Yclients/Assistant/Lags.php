<?php
namespace App\Yclients\Assistant;

use App\Core\Config;
use App\Yclients\Notification\Notification;
use App\Yclients\Record\Record;

class Lags
{

    public static function checkLag($record, $event, $nameArchitecture = ''){
        $sec = time() - strtotime($record['last_change_date']);
        $name = Record::getClientName($record);
        $phone = Record::getClientPhone($record);
        $staffName = Record::getStaffName($record);
        $companyId = $record['company_id'];
        $id = $record['id'];
        $date = $record['date'];
        $time = date("Y-m-d H:i:s");
        $last = $record['last_change_date'];
        if ($sec > 600){
            $message = "⚠️TheLashes Lag
$event - $nameArchitecture - lag: $sec sec
$name $phone
Текущее время: $time
Время изменения: $last
ID: $id ($date к $staffName в филиал $companyId)
";
            Notification::sendAdminTelegram($message);
        }
//        elseif ($sec > 90 && $nameArchitecture === Config::WebHookArchitecture){
//            $message = "⚠️TheLashes Lag
//$event - lag: $sec sec
//$name $phone
//Текущее время: $time
//Время изменения: $last
//ID: $id ($date к $staffName в филиал $companyId)
//";
//            Notification::sendAdminTelegram($message);
//        }
    }

}