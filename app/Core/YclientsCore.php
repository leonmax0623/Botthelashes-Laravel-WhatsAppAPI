<?php
namespace App\Core;
use App\Models\Records;
use Yclients\YclientsApi;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Core\Config;
use App\Core\WhatsApp;

class YclientsCore
{

    public static function getFinanceTransactions($YclientsApi, $companyId, $entity, $userToken){
        return $YclientsApi->request("finance_transactions/$companyId$entity", [], $YclientsApi::METHOD_GET, $userToken);
    }

    public static function yclientsRequest($YclientsApi, $url, $fields, $method, $userToken){
        return $YclientsApi->request($url, $fields, $method, $userToken);
    }

    public static function getLabels($YclientsApi, $companyId, $entity, $userToken){
//        $entity = 0; // общие метки (общие категории)
//        $entity = 1; // метки клиентов (категории клиентов)
//        $entity = 2; // метки записей (категории записей)
        return $YclientsApi->request("labels/$companyId/$entity", [], $YclientsApi::METHOD_GET, $userToken);
    }

//    public static function getClient($YclientsApi, $companyId, $entity, $userToken){
//        return $YclientsApi->request("group/$companyId/$entity", [], $YclientsApi::METHOD_GET, $userToken);
//    }

    public static function postLabels($YclientsApi, $companyId, $fields, $userToken){
//        $entity = 0; // общие метки (общие категории)
//        $entity = 1; // метки клиентов (категории клиентов)
//        $entity = 2; // метки записей (категории записей)
        return $YclientsApi->request("labels/$companyId", $fields, $YclientsApi::METHOD_POST, $userToken);
    }

    public static function putVisit($YclientsApi, $fields, $visitId, $recordId, $userToken){
        return $YclientsApi->request("visits/$visitId/$recordId", $fields, $YclientsApi::METHOD_PUT, $userToken);
    }

    public static function getVisit($YclientsApi, $visitId, $userToken){
        return $YclientsApi->request("visits/$visitId", [], $YclientsApi::METHOD_GET, $userToken);
    }

    public static function checkLagYclientsWebhook($event, $record){
//        Core::sendDev("<b>The Lashes</b> - <i>$event</i>
//Время, когда пришел вебхук = ". time()." ".date("Y-m-d H:i:s", time())."
//last_change_date record = ". strtotime($record['last_change_date'])." ".date("Y-m-d H:i:s", strtotime($record['last_change_date']))."
//");

        $sec = time() - strtotime($record['last_change_date']);

        $name = self::getClientNameFromRecord($record);
        $phone = self::getClientPhone($record);
        $time = date("Y-d-m H:i:s");
        $last = $record['last_change_date'];
        if ($sec > 600){
            $message = "⚠️Внимание! 
$event. 
Бот работает с задержкой более 10 минут. 
$name $phone
Текущее время: $time
Время изменения: $last
";
            YclientsCore::sendTelegramLog($message);
        } elseif ($sec > 90){
//            $message = "⚠️Внимание! $event. Временной лаг между событием в Yclients и получением вебхука от Yclients составил более 90 секунд! ($sec секунд)";
//            YclientsCore::sendTelegramLog($message);
        }
    }

    public static function getAllAttendanceAndPaidRecords($allClientRecords){
        $allLashesRecords = [];
        foreach($allClientRecords as $oneRecord){
            if ($oneRecord['attendance'] == 1 && $oneRecord['paid_full'] == 1 && time() > strtotime($oneRecord['datetime'])){
                $allLashesRecords[strtotime($oneRecord['datetime'])] = $oneRecord;
            }
        }
        krsort($allLashesRecords);
        return $allLashesRecords;
    }

    public static function getCountLashes($record){
        $clientPhone = YclientsCore::getClientPhone($record);
        $clientIds = YclientsCore::getAllClientIds($clientPhone);
        $allClientRecords = YclientsCore::getAllClientRecords($clientIds);
        $allLashesRecords = YclientsCore::getAllAttendanceAndPaidRecords($allClientRecords);
        $previousTime = time() - Config::$BotoxTime;
        $countLashes = 0;
        foreach($allLashesRecords as $time => $oneRecord){
            if ($previousTime < $time ){
                if (YclientsCore::haveBotoxService($oneRecord)){
                    break;
                }
                if (YclientsCore::haveEyelashExtensionsService($oneRecord)){
                    $countLashes++;
                }
            } else {
                break;
            }
            $previousTime = $time - Config::$BotoxTime;
        }
        return $countLashes;
    }

    public static function checkPutRecordAddBotoxCommentAndSendMessageToAdmin($record){
        if(YclientsCore::haveEyelashExtensionsService($record) && !YclientsCore::haveBotoxService($record)){
            if (YclientsCore::getCountLashes($record) == 5){
                YclientsCore::putRecordAddBotoxComment($record);
                YclientsCore::sendAdmin(TheLashesMessage::getMessageBotoxAdmin($record), '74957647602');
                Core::sendDev(TheLashesMessage::getMessageBotoxAdmin($record));
            }
        }
    }

    public static function checkPutRecordAddOrangeColor($record){
        if(
            $record['custom_font_color'] !== 'ffffff' && $record['custom_color'] !== 'ff9800' &&
            YclientsCore::haveEyelashExtensionsService($record) && !YclientsCore::haveBotoxService($record)
        ){
            if (YclientsCore::getCountLashes($record) == 5){
                YclientsCore::putRecordAddOrangeColor($record);
            }
        }
    }

    public static function putRecordAddBotoxComment($record){
        $recordsNow = Config::getYclientsApi()->getRecord($record['company_id'], $record['id'], Config::getConfig()[$record['company_id']]);
        $fields = [
            'staff_id' => $recordsNow['staff_id'],
            'services' => $recordsNow['services'],
            'client' => $recordsNow['client'],
            'datetime' => $recordsNow['datetime'],
            'comment' => $recordsNow['comment']."👉Было 5-ть наращиваний подряд. Предложить уход/перерыв",
            'seance_length' => $recordsNow['seance_length'],
            'attendance' => $recordsNow['attendance'],
            'visit_attendance' => $recordsNow['visit_attendance'],
//            'custom_color' => 'ff9800',
//            'custom_font_color' => 'ffffff',
        ];
        $res =  Config::getYclientsApi()->putRecord($record['company_id'], $record['id'], Config::getConfig()[$record['company_id']], $fields);
    }

    public static function putRecordAddOrangeColor($record){
        $recordsNow = Config::getYclientsApi()->getRecord($record['company_id'], $record['id'], Config::getConfig()[$record['company_id']]);
        $fields = [
            'staff_id' => $recordsNow['staff_id'],
            'services' => $recordsNow['services'],
            'client' => $recordsNow['client'],
            'datetime' => $recordsNow['datetime'],
            'comment' => $recordsNow['comment'],
            'seance_length' => $recordsNow['seance_length'],
            'attendance' => $recordsNow['attendance'],
            'visit_attendance' => $recordsNow['visit_attendance'],
            'custom_color' => 'ff9800',
            'custom_font_color' => 'ffffff',
        ];
        $res =  Config::getYclientsApi()->putRecord($record['company_id'], $record['id'], Config::getConfig()[$record['company_id']], $fields);
//        Core::sendDev("putRecordAddOrangeColor".json_encode($res,1));
    }

    public static function getAllClientRecords($clientIds){
        $allClientRecords = [];
        foreach($clientIds as $companyId => $clientId){
            $result = Config::getYclientsApi()->getRecords($companyId, Config::getConfig()[$companyId], null, null, null, $clientId);
            foreach($result['data'] as $oneRecord){
                $allClientRecords[] = $oneRecord;
            }
        }
        return $allClientRecords;
    }

    public static function getAllClientIds($clientPhone){
        $clientIds = [];
        foreach(Config::getConfig() as $companyId => $userToken){
            $clients = Config::getYclientsApi()->getClients($companyId, $userToken, null, $clientPhone);
            foreach($clients['data'] as $client){
                $clientIds[$companyId] = $client['id'];
            }
        }
        return $clientIds;
    }

    public static function haveBotoxService($record){
        $botoxStatus = false;
        $getAllServices = YclientsCore::getAllServices(Config::getYclientsApi(), Config::getConfig());
        $getAllServicesArray = YclientsCore::getAllServicesArray($getAllServices);
        if (!empty($record['services'])){
            foreach($record['services'] as $service){
                if (!empty($service['id']) && !empty($getAllServicesArray[$service['id']])){
                    if (in_array($getAllServicesArray[$service['id']],Config::$BotoxArrayCategries)){
                        $botoxStatus = true;
                    }
                    if (in_array($service['id'], Config::$BotoxArrayServices)){
                        $botoxStatus = true;
                    }
                }
            }
        }
        return $botoxStatus;
    }

    public static function haveEyelashExtensionsService($record){
        $lashesStatus = false;
        $getAllServices = YclientsCore::getAllServices(Config::getYclientsApi(), Config::getConfig());
        $getAllServicesArray = YclientsCore::getAllServicesArray($getAllServices);
        if (!empty($record['services'])){
            foreach($record['services'] as $service){
                if (!empty($service['id']) && !empty($getAllServicesArray[$service['id']])){
                    if (in_array($getAllServicesArray[$service['id']],Config::$lashesArrayCategries)){
                        $lashesStatus = true;
                    }
                }
                if (!empty($service['id']) && in_array($service['id'],Config::$lashesArrayServices)){
                    $lashesStatus = true;
                }
            }
        }
        return $lashesStatus;
    }

    public static function getAllCategoriesToServicesArray($allServices){
        if (Cache::get("AllCategoriesToServicesArray")){
            $AllCategoriesToServicesArray = Cache::get("AllCategoriesToServicesArray");
        } else {
            $AllCategoriesToServicesArray = [];
            foreach($allServices as $companyId => $services){
                foreach($services as $service){
                    $AllCategoriesToServicesArray[$service['category_id']][] = $service['id'];
                }
            }
            Cache::put("AllCategoriesToServicesArray", $AllCategoriesToServicesArray, 1440);
        }
        return $AllCategoriesToServicesArray;
    }

    public static function getAllServicesArray($allServices){
        if (Cache::get("AllServicesArray")){
            $AllServicesArray = Cache::get("AllServicesArray");
        } else {
            $AllServicesArray = [];
            foreach($allServices as $companyId => $services){
                foreach($services as $service){
                    $AllServicesArray[$service['id']] = $service['category_id'];
                }
            }
            Cache::put("AllServicesArray", $AllServicesArray, 1440);
        }
        return $AllServicesArray;
    }

    public static function getAllServicesCategoriesArray($allServicesCategories){
        if (Cache::get("AllServicesCategoriesArray")){
            $AllServicesCategoriesArray = Cache::get("AllServicesCategoriesArray");
        } else {
            $AllServicesCategoriesArray = [];
            foreach($allServicesCategories as $companyId => $servicesCategories){
                foreach($servicesCategories as $servicesCategory){
                    $AllServicesCategoriesArray[$servicesCategory['id']] = $servicesCategory['title'];
                }
            }
            Cache::put("AllServicesCategoriesArray", $AllServicesCategoriesArray, 1440);
        }
        return $AllServicesCategoriesArray;
    }

    public static function getAllServicesCategories($YclientsApi, $config){
        if (Cache::get("allServicesCategories")){
            $allServicesCategories = Cache::get("allServicesCategories");
        } else {
            $allServicesCategories = [];
            foreach($config as $companyId => $userToken){
                $allServicesCategories[$companyId] = $YclientsApi->getServiceCategories($companyId, null);
            }
            Cache::put("allServicesCategories", $allServicesCategories, 1440);
        }
        return $allServicesCategories;
    }

    public static function getAllServices($YclientsApi, $config){
        if (Cache::get("allServices")){
            $allServices = Cache::get("allServices");
        } else {
            $allServices = [];
            foreach($config as $companyId => $userToken){
                $allServices[$companyId] = $YclientsApi->getServices($companyId);
            }
            Cache::put("allServices", $allServices, 1440);
        }
        return $allServices;
    }

    public static function getCompaniesInfo($YclientsApi, $config){
        if (Cache::get("companies")){
            $companies = Cache::get("companies");
        } else {
            $companies = [];
            foreach($config as $companyId => $userToken){
                $companies[$companyId] = $YclientsApi->getCompany($companyId);
            }
            Cache::put("companies", $companies, 1440);
        }
        return $companies;
    }

    public static function getClientId($record){
        $clientId = null;
        if (isset($record['client']['id'])) {
            $clientId = $record['client']['id'];
        }
        return $clientId;
    }

    public static function getClientEmail($record){
        $clientEmail = null;
        if (isset($record['client']['email'])) {
            $clientEmail = trim($record['client']['email']);
        }
        return $clientEmail;
    }

    public static function getClientPhone($record){
        $clientPhone = null;
        if (isset($record['client']['phone'])) {
            $clientPhone = self::getOnlyNumberString($record['client']['phone']);
        }
        return $clientPhone;
    }

    public static function getStaffId($record){
        $staffId = null;
        if(isset($record['staff']['id'])){
            $staffId = $record['staff']['id'];
        }
        return $staffId;
    }

    private static function checkInstagramFollowSearch($categories, $instagramLabels, $instagram){
        foreach($categories as $category){
            if (in_array($category['id'], $instagramLabels)){
                $instagram = true;
            }
        }
        return $instagram;
    }

    public static function checkInstagramFollow($record){
        $YclientsApi = Config::getYclientsApi();
        $config = Config::getConfig();
        $instagramLabels = Config::$instagramLabels;
        $companyId = $record['company_id'];
        $clientId = @$record['client']['id'];
        $userToken = $config[$companyId];
        $client = $YclientsApi->getClient($companyId, $clientId, $userToken);
        if (!empty($client['phone'])){
            $phone = YclientsCore::getOnlyNumberString($client['phone']);
        } else {
            $phone = null;
        }

        $instagram = false;
        if(!empty($client['categories'])){
            $instagram = self::checkInstagramFollowSearch($client['categories'], $instagramLabels, $instagram);
        }
        foreach($config as $companyId => $userToken){
            $clients = $YclientsApi->getClients($companyId, $userToken, null, $phone);
            foreach($clients['data'] as $client){
                if(!empty($client['categories'])){
                    $instagram = self::checkInstagramFollowSearch($client['categories'], $instagramLabels, $instagram);
                }
            }
        }
        return $instagram;
    }

    public static function getAuthYclientsUserToken($YclientsApi, $campanyId, $login, $password){
        $cacheParam = 'YclientsUserToken'.$campanyId;
        if (Cache::get($cacheParam)){
            $userToken = Cache::get($cacheParam);
        } else {
            $userToken = $YclientsApi->getAuth($login,$password)["user_token"];
            Cache::put($cacheParam, $userToken, 1440); // на сутки
        }
        return $userToken;
    }

    public static function getOnlyNumberString($number){
        $number = preg_replace("/[^0-9]/", '', $number);
        return $number;
    }

    public static function getAllRecordsNextDay($YclientsApi, $config){
        $dateTime = new \DateTime;
        $nextDay = time() + 60 * 60 * 24;
        $dateTime->setDate(date('Y', $nextDay), date('m', $nextDay), date('d', $nextDay));
        $records = [];
        foreach($config as $companyId => $userToken){
            $records[$companyId] = $YclientsApi->getRecords($companyId, $userToken, null, 9999, null, null, $dateTime, $dateTime);
        }
        return $records;
    }


    public static function checkAccessUserSendMessageToWhatsApp($phone){
        $user = DB::table('bot')->where('phone', $phone)->first();
        if ($user !== null) {
            $start = $user->start;
            $stop = $user->stop;
            if ($start == 1 && $stop == 0){
                return true;
            }
        }
        return false;
    }


    public static function getDateTimeStartVisitFromRecord($record, $format = 'd.m.Y в H:i'){
        $date = 0;
        if (isset($record['date'])) {
            $date = $record['date'];
        }
        $date = date_format(date_create($date), $format);
        return $date;
    }

    public static function getPaymentLink($record){
        return Payment::getPaymentLink($record);
    }

    public static function getRecordLast($record){
        try {
            $recordLast = DB::table('records')->where('recordId', $record['id'])->first();
        } catch (\Exception $e) {
            Core::sendDev(base_path()." ::getRecordLast()".$e->getMessage());
            $recordLast = null;
        }
        return $recordLast;
    }

    public static function getClientNameFromRecord($record, $defaultName = 'Имя не указано', $afterName = ''){
        $clientName = $defaultName;
        if (!empty($record['client']['name'])) {
            $clientName = $record['client']['name'] . $afterName;
        }
        return $clientName;
    }

    public static function getStaffSpecializationFromRecord($record){
        $specialization = null;
        if (isset($record['staff']['specialization'])) {
            $specialization = $record['staff']['specialization'];
        }
        return $specialization;
    }

    public static function getAddressCompanyFromRecord($record, $companies, $defaultValue = 'Адрес не найден'){
        $address = $defaultValue;
        $companyId = $record['company_id'];
        if (isset($companies[$companyId]['address'])) {
            $address = $companies[$companyId]['address'];
        }
        return $address;
    }

    public static function getStaffNameFromRecord($record){
        $staffName = 'Мастер не указан';
        if (!empty($record['staff']['name'])) {
            $staffName = $record['staff']['name'];
        }
        return $staffName;
    }

    public static function getStudioLocation($companyId){
        $studioName = '';
        return $studioName;
    }

    public static function getStudioNameFromRecord($record, $companies){
        $companyId = $record['company_id'];
        $studioName = 'Студия не определена';
        foreach($companies as $company){
            if ($companyId == $company['id']){
                $studioName = $company['title'].' ('.$company['address'].')';
            }
        }
        return $studioName;
    }

    public static function getServicesStringFromRecord($record){
        $services = 'в салон';
        if (isset($record['services']) && is_array($record['services']) && count($record['services']) > 0){
            $j = 0;
            foreach($record['services'] as $service){
                if($j>0){$services .= ', ';} else{$services = 'на ';} $j++;
                $services .= ''.$service['title'].'';
            }
        }
        return $services;
    }

    public static function getRecordServicesIds($record){
        $servicesIds = [];
        if (isset($record['services']) && is_array($record['services']) && count($record['services']) > 0){
            foreach($record['services'] as $service){
                $servicesIds[] = $service['id'];
            }
        }
        return $servicesIds;
    }

    public static function checkToday($record){
        $recordDate = strtotime($record['date']);
        $todayStart = strtotime(date("Y-m-d 00:00:00")); // с СЕГОДНЯ 00:00
        $todayEnd = strtotime(date("Y-m-d 23:59:59")); // до СЕГОДНЯ 23:59
        if ($recordDate > $todayStart && $recordDate < $todayEnd){
            return true;
        } else {
            return false;
        }
    }

    public static function getServicesString($record, $defaultValue = 'Услуги не выбраны'){
        $services = $defaultValue;
        if (isset($record['services']) && is_array($record['services']) && count($record['services']) > 0){
            $services = '';
            foreach($record['services'] as $service){
                $services .= '
- '.$service['title'].'';
            }
        }
        return $services;
    }

    public static function getPhoneAdmin($companyId){
        switch($companyId){
            case '138368': $adminPhone = '79629952225'; break;
            case '41332': $adminPhone = '79265896504'; break;
            case '225366': $adminPhone = '79778003516'; break;
            default: $adminPhone = '380633282597'; break;
        }
        return $adminPhone;
    }

    public static function sendAdmin($message, $phone = '79854331197'){
        $whatsapp = new WhatsApp(Config::$waApiUrl, Config::$waToken);
        $data = [
            'phone' => $phone,
            'body' => $message
        ];
        $result = $whatsapp->request('sendMessage', $data);
    }

    public static function sendTelegram($message){
        $bot = new Telegram('820009438:AAHAcT41CqjdUTR3HAXHh81a-xMEPOIHlfg');
        $bot->request('sendMessage', ['text' => $message, 'chat_id' => 57625110, 'parse_mode' => 'HTML']);
    }

    public static function sendAdminTelegram($message){
        $bot = new Telegram('820009438:AAHAcT41CqjdUTR3HAXHh81a-xMEPOIHlfg');
        $bot->request('sendMessage', ['text' => $message, 'chat_id' => 57625110, 'parse_mode' => 'HTML']);
        $bot->request('sendMessage', ['text' => $message, 'chat_id' => 147256227, 'parse_mode' => 'HTML']);
    }

    public static function sendTelegramLog($message){
        $bot = new Telegram('820009438:AAHAcT41CqjdUTR3HAXHh81a-xMEPOIHlfg');
        $bot->request('sendMessage', ['text' => $message, 'chat_id' => 57625110, 'parse_mode' => 'HTML']);
        $bot->request('sendMessage', ['text' => $message, 'chat_id' => 147256227, 'parse_mode' => 'HTML']);
        $bot->request('sendMessage', ['text' => $message, 'chat_id' => 657871477, 'parse_mode' => 'HTML']); // Михаил личный +79265896504
    }

    public static function getStartTimeToday(){
        $Y = date('Y', time());
        $m = date('m', time());
        $d = date('d', time());
        $startTimeToday = strtotime(date("$Y-$m-$d 00:00:00"));
        return $startTimeToday;
    }

    public static function getRecordLabelNotInWhatsAppId($record){
        $companyId = $record['company_id'];
        switch($companyId){
            case '138368': $labelId = 1503183; break;
            case '41332': $labelId = 1503319; break;
            case '225366': $labelId = 1637898; break;
            default: $labelId = 1503118; break;
        }
        return $labelId;
    }

    public static function putRecordAttendancelientDidNotCome($YclientsApi, $config, $record){
        $recordId = $record['id'];
        $companyId = $record['company_id'];
        $userToken = $config[$companyId];
        $recordsNow = $YclientsApi->getRecord($companyId, $recordId, $userToken);
//        $labelId = YclientsCore::getRecordLabelNotInWhatsAppId($record);
        if($recordsNow['attendance'] != 1 && $recordsNow['attendance'] != 2) {
            $fields = [
                'staff_id' => $recordsNow['staff_id'],
                'services' => $recordsNow['services'],
                'client' => $recordsNow['client'],
                'datetime' => $recordsNow['datetime'],
                'seance_length' => $recordsNow['seance_length'],
                'comment' => $recordsNow['comment'],
                'api_id' => $recordsNow['api_id'],
                'attendance' => '-1', // клиент не пришел!
                'activity_id' => $recordsNow['activity_id'],
                'clients_count' => $recordsNow['clients_count'],
//                    'fast_payment' => $record['fast_payment'],
//                    'sms_remain_hours' => $record['sms_remain_hours'],
//                    'email_remain_hours' => $record['email_remain_hours'],
//                    'save_if_busy' => $record['save_if_busy'],
//                    'send_sms' => $record['send_sms'],
            ];
            $res = $YclientsApi->putRecord($companyId, $recordId, $userToken, $fields);
        }
    }


    public static function putRecordAddLabelNotInWhatsApp($YclientsApi, $config, $record){
        $res = 'nothing';
        $recordId = $record['id'];
        $companyId = $record['company_id'];
        $userToken = $config[$companyId];
        $recordsNow = $YclientsApi->getRecord($companyId, $recordId, $userToken);
        $labelId = YclientsCore::getRecordLabelNotInWhatsAppId($record);
        if($recordsNow['attendance'] != 1 && $recordsNow['attendance'] != 2) {
            $fields = [
                'staff_id' => $recordsNow['staff_id'],
                'services' => $recordsNow['services'],
                'client' => $recordsNow['client'],
                'datetime' => $recordsNow['datetime'],
                'seance_length' => $recordsNow['seance_length'],
                'comment' => $recordsNow['comment'],
                'api_id' => $recordsNow['api_id'],
                'attendance' => $recordsNow['attendance'],
                'activity_id' => $recordsNow['activity_id'],
                'clients_count' => $recordsNow['clients_count'],
                'record_labels' => [
                    'id' => $labelId,
                    'title' => 'Нет в WhatsApp',
                    'color' => 'ffff00',
                    'icon' => 'whatsapp',
                    'font_color' => 'ffffff',
                ],
//                    'fast_payment' => $record['fast_payment'],
//                    'sms_remain_hours' => $record['sms_remain_hours'],
//                    'email_remain_hours' => $record['email_remain_hours'],
//                    'save_if_busy' => $record['save_if_busy'],
//                    'send_sms' => $record['send_sms'],
            ];
            $res = $YclientsApi->putRecord($companyId, $recordId, $userToken, $fields);

        }
        return $res;
    }

    public static function recordDelete($YclientsApi, $config, $chatId, $column = 'LastAutoConfirmation'){
        $actual = false;
//        YclientsCore::sendTelegram('recordDelete');
        try {
            $user = DB::table('bot')->where('userId', $chatId)->first();
            $record = json_decode($user->$column,1);
            $staffId = self::getStaffId($record);
            if(YclientsCore::ifBeforeRecordMoreThan12HoursThenDeleteTheRecord($record) || in_array($staffId, Config::$staffListWaitIds)){
                $actual = true;
//                YclientsCore::sendTelegram("более 12 часов. actual = $actual");
                foreach($config as $companyId => $userToken){
                    if ($record['company_id'] == $companyId){
                        $recordId = $record['id'];
                        $del = $YclientsApi->deleteRecord($companyId, $recordId, $userToken);
                    }
                }
            } elseif(YclientsCore::ifBeforeRecordLessThan12HoursAndMore0HoursThenChangeStatus($record)){
                $actual = true;
//                YclientsCore::sendTelegram("менее 12 часов actual = $actual");
                YclientsCore::putRecordAttendancelientDidNotCome($YclientsApi, $config, $record);
                // change status
            } else {
//                YclientsCore::sendTelegram("уже началось = $actual");
                $actual = false;
                // эта запись уже в прошлом, удаление или изменение невозможно.
            }
        } catch (\Exception $e) {
            self::sendTelegram($e->getMessage());
        }
//        YclientsCore::sendTelegram("actual = $actual");
        return $actual;
    }

    public static function ifBeforeRecordMoreThan12HoursThenDeleteTheRecord($record){
        $timeStartVisit = strtotime($record['datetime']);
        $now = time();
//        YclientsCore::sendTelegram("timeStartVisit = $timeStartVisit; now = $now");
        if ($now + 12 * 60 * 60 < $timeStartVisit){
            return true;
        }
        return false;
    }

    public static function ifBeforeRecordLessThan12HoursAndMore0HoursThenChangeStatus($record){
        $timeStartVisit = strtotime($record['datetime']);
        $now = time();
//        YclientsCore::sendTelegram("timeStartVisit = $timeStartVisit; now = $now");
        if ($now < $timeStartVisit){
            return true;
        }
        return false;
    }

    public static function checkDateRecord($record){
        $timeEndVisit = strtotime($record['datetime']) + $record['seance_length'];
        $now = time();
        if ($now > $timeEndVisit){
            return true;
        }
        return false;
    }

    public static function getMinutes15($now){
        $min = date("i", $now);
        if ($min <= 14) {
            $min = 0;
        } elseif ($min <= 29) {
            $min = 15;}
        elseif ($min <= 44) {
            $min = 30;}
        elseif ($min <= 59) {
            $min = 45;
        }
        return $min;
    }

    public static function getPrepaymentMasterId($record){
        $companyId = $record['company_id'];
        $staffId = 804718;
        switch($companyId){
            case '138368': $staffId = 805151; break; // автозаводская
            case '41332': $staffId = 804718; break; // пресня
            case '225366': $staffId = 805230; break; // раменки
        }
        return $staffId;
    }

    public static function addRecordToPrepaymentMaster($record, $total){
        $servicesWithDiscountForPrepayment = self::getServicesWithDiscountForPrepayment($record['services'], $total);
        $companyId = $record['company_id'];
//        $staffId = 804718; // пресня
        $staffId = self::getPrepaymentMasterId($record); // пресня
//        $services = $servicesWithDiscountForPrepayment;
        $services = $record['services'];
        $client = $record['client'];
        $DateTime = new \DateTime;
        $time = time();
        $DateTime->setDate(date('Y', $time), date('m', $time), date('d', $time));
        $DateTime->setTime(date('H', $time), date('i', self::getMinutes15(time())));
        $result = Config::getYclientsApi()->postRecords($companyId, Config::getConfig()[$companyId], $staffId, $services, $client, $DateTime, 15*60, 1, 0);
        return $result;
    }

    public static function addRecord($record){
        try {
            $insert = [];
            $insert['recordId'] = $record['id'];
            $insert['record'] = json_encode($record,1);
            $insert['phone'] = YclientsCore::getClientPhone($record);
            $insert['timeSmsReminder'] = strtotime($record['datetime']) - $record['sms_before'] * 60 * 60;
            if ($record['sms_now']){
                $insert['sendSmsReminder'] = 0;
            } else {
                $insert['sendSmsReminder'] = 1;
            }
            DB::table('records')->insert($insert);
        } catch (\Exception $e) {
            Core::sendDev(base_path()." addRecord() ".$e->getMessage());
        }
    }

    public static function deleteRecord($record){
        $deleteCountRecords = 0;
        try {
            $recDel = Records::getRecord($record['id']);
            $in = DB::table('deletedRecords')->insert([
                'recordId' => $record['id'],
                'deletedForDebts' => $recDel->deletedForDebts,
                'record' => json_encode($record,1)
            ]);
            $deleteCountRecords = DB::table('records')->where('recordId', $record['id'])->delete();
        } catch (\Exception $e) {
            Core::sendDev(base_path()." deleteRecord() ".$e->getMessage());
        }
        return $deleteCountRecords;
    }

    public static function updateRecord($record){
        try {
            $update = [];
            $update['record'] = json_encode($record,1);
            $update['timeSmsReminder'] = strtotime($record['datetime']) - $record['sms_before'] * 60 * 60;
//            if ($record['sms_now']){
//                $update['sendSmsReminder'] = 0;
//            } else {
//                $update['sendSmsReminder'] = 1;
//            }
            DB::table('records')->where('recordId', $record['id'])->update($update);
        } catch (\Exception $e) {
            Core::sendDev(base_path()." updateRecord() ".$e->getMessage());
        }
    }

    public static function recordConfirmed($YclientsApi, $config, $chatId, $column = 'LastAutoConfirmation'){
        $actual = false;
        try {
            $user = DB::table('bot')->where('userId', $chatId)->first();
            $record = json_decode($user->$column,1);
            if (!YclientsCore::checkDateRecord($record)){
                $actual = true;
                foreach($config as $companyId => $userToken) {
                    if ($record['company_id'] == $companyId) {
                        $recordId = $record['id'];
                        $visitId = $record['visit_id'];
                        $putVisitServices = [];
                        foreach ($record['services'] as $i => $service) {
                            $putVisitServices[$i] = $service;
                            $putVisitServices[$i]['record_id'] = $recordId;
                        }
                        $fields = [
                            'attendance' => 2, // клиент подтвердил   //'attendance' => -1, // клиент отменил
                            'comment' => '',
                            'services' => $putVisitServices
                        ];
                        $r = self::putVisit($YclientsApi, $fields, $visitId, $recordId, $userToken);
                    }
                }
            }
        } catch (\Exception $e) {
            self::sendTelegram($e->getMessage());
        }
        return $actual;
    }

    public static function setWebhook($YclientsApi, $config){
        foreach($config as $companyId => $userToken){
            $fields = [
                'url' => 'https://'.$_SERVER['HTTP_HOST'].'/yclients/webhook',
                'active' => 1,
                'salon' => 1,
                'service_category' => 1,
                'service' => 1,
                'master' => 1,
                'client' => 1,
                'record' => 1,
                'schedule' => 1,
                'goods_operations_sale' => 1,
                'goods_operations_receipt' => 1,
                'goods_operations_consumable' => 1,
                'goods_operations_stolen' => 1,
                'goods_operations_move' => 1,
                'finances_operation' => 1,
            ];
            print_r("<pre>");
            print_r($YclientsApi->postHooks($companyId, $fields, $userToken));
            print_r("</pre>");
            print_r("<pre>");
            print_r($YclientsApi->getHooks($companyId, $userToken));
            print_r("</pre>");

        }
    }



//    public static function getAllRecordsOld($YclientsApi, $config){
//        $dateTime = new \DateTime;
//
//        $nextDay = time() + 2*60 * 60 * 24;
//        $dateTime->setDate(date('Y', $nextDay), date('m', $nextDay), date('d', $nextDay));
//
//        $records = [];
//        $companies = [];
//        foreach($config as $companyId => $userToken){
//            $records[$companyId] = $YclientsApi->getRecords($companyId, $userToken, null, 9999, null, null, $dateTime, $dateTime);
//            $companies[$companyId] = $YclientsApi->getCompany($companyId);
//        }
//
////        print_r("<pre>");
////        print_r($companies);
////        print_r("</pre>");
////        print_r("<pre>");
////        print_r($records);
////        print_r("</pre>");
//
//        foreach($config as $companyId => $userToken){
//            foreach($records[$companyId]['data'] as $record){
//                $date = null;
//                $master = 'Мастер не найден';
//                $address = 'Адрес не найден';
//                $services = '';
//                if (isset($record['date'])) {$date = $record['date'];}
//                if (isset($record['staff']['name'])) {$master = $record['staff']['name'];}
//                if (isset($companies[$companyId]['address'])) {$companies[$companyId]['address'];}
//                if (isset($record['services']) && is_array($record['services']) && count($record['services']) > 0){
//                    foreach($record['services'] as $service){
//                        $services .= '
//'.$service['title'].'';
//                    }
//                }
//
//
//
////                $visitId = $record['visit_id'];
////                $recordId = $record['id'];
////                $clientId = $record['client']['id'];
//
//                print_r("<pre>");
//                print_r($record);
//                print_r("</pre>");
//            }
//        }
//
//
//
//
//
//
//
//    }

    public static function text(){

        print_r("<pre>");
        print_r(self::getLabels($YclientsApi, $companyId, 1, $userToken));
        print_r("</pre>");


                $fields = [
                    'title' => 'Нет в WhatsApp',
                    'color' => '#ff0000',
                    'entity' => 1,
                    'icon' => 'minus-circle'
                ];
                print_r("<pre>");
                print_r(self::postLabels($YclientsApi, $companyId, $fields, $userToken));
                print_r("</pre>");

        $client = $YclientsApi->getClient($companyId, $clientId, $userToken);

        print_r("CLIENT<pre>");
        print_r($client);
        print_r("</pre>");


        $categoriesOfClient = [];
        foreach($client['categories'] as $category){
            if ($category['id'] !== 1473984) {
                $categoriesOfClient[] = $category['id'];
            }

        }
        $categoriesOfClient[] = 1473984;
        print_r("<pre>");
        print_r($categoriesOfClient);
        print_r("</pre>");
        $fields = [
            'name' => $client['name'],
            'phone' => $client['phone'],
            'email' => $client['email'],
            'sex_id' => $client['sex_id'],
            'importance_id' => $client['importance_id'],
            'discount' => $client['discount'],
            'card' => $client['card'],
            'birth_date' => $client['birth_date'],
            'comment' => $client['comment'],
            'spent' => $client['spent'],
            'balance' => $client['balance'],
            'sms_check' => $client['sms_check'], // 1 - Поздравлять с Днем Рождения по SMS, 0 - не поздравлять
            'sms_not' => $client['sms_bot'], // 1 - Исключить клиента из SMS рассылок, 0 - не исключать
            'labels' => $categoriesOfClient, // категории клиента !
//                    'custom_fields' => $client['custom_fields'], //
        ];
        print_r("<pre>");
        print_r($YclientsApi->putClient($companyId, $clientId, $userToken, $fields));
        print_r("</pre>");
    }








    public static function commandFind($command, $combinations){
        $commandFind = false;
        foreach($combinations as $combination){
            if(stristr($command, $combination)){
                $command = $combination;
                $commandFind = true;
                break;
            }
        }
        $return = [
            'find' => $commandFind,
            'command' => $command
        ];
        return $return;
    }

    public static function updateWhatsAppStatus($data){
        try {
            $chatId = $data['ack'][0]['chatId'];
            $status = $data['ack'][0]['status'];
            DB::table('bot')->where('userId', $chatId)->update(['waLastStatus'=>$status]);
        } catch (\Exception $e) {
            self::sendTelegram(
'<b>The Lashes WA Bot Error:</b>
'.$e->getMessage());
        }
    }



    public static function getAllRecords($YClientsConfig, $YClientsApi, $startTime = null, $endTime = null){
        $records = [];
        foreach($YClientsConfig as $companyId => $userToken){
            $records[$companyId] = $YClientsApi->getRecords($companyId, $userToken, null, 9999, null, null,
                self::_getDateTimeObject(self::_getTimeDefault($startTime)),
                self::_getDateTimeObject(self::_getTimeDefault($endTime))
            );
        }
        return $records;
    }

    public static function _getDateTimeObject($time){
        $DateTime = new \DateTime;
        $DateTime->setDate(date('Y', $time), date('m', $time), date('d', $time));
        return $DateTime;
    }

    public static function _getTimeDefault($time){
        if ($time == null){
            $time = time() + 60 * 60 * 24;
        }
        return $time;
    }

}