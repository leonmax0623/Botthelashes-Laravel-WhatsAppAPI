<?php
namespace App\Yclients\Modules\Scoring;

use App\Core\Config;
use App\Core\Core;
use App\Models\Records;
use App\Yclients\Cache\AdminConfig;
use App\Yclients\Notification\Notification;
use App\Yclients\Record\Record;

class Scoring
{

    public static $notPrepaymentImportant = false;

    public static $recordLabelsNotPrepayment = [
        2218064, 2218066, 2218075
    ];

    public static $clientLabelsNotPrepayment = [
        2218065, 2218074, 2218076
    ];

    public static $clientsIds = [];
    public static function run($record, $sendScoringNotification = true){
        $start = microtime(true);
        $must = false;
        $notification = false;
        $clientPhone = Record::getClientPhone($record);
        $clientName = Record::getClientName($record);

        $TagMustPrepaymentForClient = self::checkTagMustPrepaymentForClient($clientPhone);
//        print_r("<pre>");
//        print_r(self::$clientsIds);
//        print_r("</pre>");
//        $recordsDB = Records::getRecords();
        $recordsFromYclients = self::getRecordsFromYclients();
        print_r("<pre>");
        print_r($recordsFromYclients);
        print_r("</pre>");
//        $recordsDB = self::getRecordsFromYclients($clientPhone);

        $scoringData = self::getScoringData($recordsFromYclients);

        print_r("<pre>");
        print_r($scoringData);
        print_r("</pre>");

        if(self::isModel($clientName)){
            $must = true;
            $notification = "Scoring $clientName $clientPhone - isModel";
        } elseif(self::checkTagMustPrepaymentForThisRecord($record)){
            //Возможность для контакт-центра включать предоплату в ручном режиме. Присвоение тега записи (не клиенту)
            $must = true;
            $notification = "Scoring $clientName $clientPhone - checkTagMustPrepaymentForThisRecord";
        } elseif (self::checkFutureRecords($scoringData)){
            //2.3. если забронировано больше нескольких дат в будущем. То на каждую новую дату - предоплата
            $must = true;
            $notification = "Scoring $clientName $clientPhone - checkFutureRecords";
        } elseif ($scoringData['recordLast'] !== null && $scoringData['recordLast']['attendance'] == '-1'){
            // 2.4. ЗАПРАШИВАТЬ –предоплату даже если у клиента хороший скоринг, но последний визит был со статусом «не пришел»
            $must = true;
            $notification = "Scoring $clientName $clientPhone - последний визит - не пришел";
        } elseif(
            AdminConfig::get()['prepaymentForNewClient'] &&
            ($scoringData['recordComeCount'] + $scoringData['recordDidNotComeCount'] === 0) &&
            self::DateTimeRecordNotToday($record)
        ){
            //2.1.4 Вывести в админку возможность включения/выключения предоплаты для всех новых клиентов записывающихся «не день в день»
            $must = true;
            $notification = "Scoring $clientName $clientPhone - Новый клиент и не на сегодня + в админке ВКЛ";
        } elseif(self::didNotComeStop($scoringData)){
            //2.1.2. Если у клиента процент «неприходов» превышает 20% от общего числа визитов по СЕТИ
            $must = true;
            $notification = "Scoring $clientName $clientPhone - didNotComeStop";
        } elseif(self::guaranteeStop($scoringData)){
            //Если у человека было более 20% визитов с ярлыком «гарантия» по СЕТИ
            $must = true;
            $notification = "Scoring $clientName $clientPhone - guaranteeStop";
        } elseif($TagMustPrepaymentForClient) {
            //Если клиент с обязательной предоплатой на уровне СЕТИ
            $must = true;
            $notification = "Scoring $clientName $clientPhone - checkTagMustPrepaymentForClient";
        }

        if (self::$notPrepaymentImportant){
            $must = false;
            $notification = "Scoring $clientName $clientPhone - скоринг завершен. Обнаружен тег 'Предоплата не требуется' с высшим приоритетом перед всеми остальными!";
        }

        if (!$notification){
            $notification = "Scoring $clientName $clientPhone - скоринг завершен. клиент хороший. запись без предоплаты.";
        }

        if ($sendScoringNotification === true && $notification !== false){
            $sec = microtime(true) - $start;
            Notification::sendAdminTelegram($notification."

Execution Scoring Time: $sec sec");
        }

        return $must;
    }


    public static function getRecordsFromYclients(){
        $records = [];
        foreach(Config::getConfig() as $companyId => $userToken){
            if (isset(self::$clientsIds[$companyId])){
                $records[$companyId] = Config::getYclientsApi()->getRecords($companyId, $userToken, null, null, null, self::$clientsIds[$companyId]);
            }
        }
        $recordsFromYclients = [];
        foreach ($records as $companyId => $data){
            foreach ($data['data'] as $rec){
                if (
                    !in_array(Record::getStaffId($rec), Config::$staffListPrepaymentsIds)
                    && !in_array(Record::getStaffId($rec), Config::$staffListWaitIds)
                    && !in_array(Record::getStaffId($rec), Config::$staffListAdminIds)
                ) {
                    $recordsFromYclients[] = $rec;
                }

                if (
                    in_array(Record::getStaffId($rec), Config::$staffListWaitIds) &&
                    $rec['attendance'] == '-1'
                ){
                    // если это Лист Ожидания и именно в статусе "клиент не пришел", тогда тоже добавляем в Скоринг
                    $recordsFromYclients[] = $rec;
                }
            }
        }
        return $recordsFromYclients;
    }

    public static function DateTimeRecordNotToday($record){
        $Y = date('Y', strtotime($record['datetime']));
        $m = date('m', strtotime($record['datetime']));
        $d = date('d', strtotime($record['datetime']));
        $startTimeToday = strtotime(date("$Y-$m-$d 00:00:00"));
        if (time() < $startTimeToday){
            return true;
        } else {
            return false;
        }
    }

    public static function isModel($clientName){
        if (stristr(mb_strtolower($clientName), Config::MODEL)){
            return true;
        }
        return false;
    }

    public static function didNotComeStop($scoringData){
        $percentStop = 20;
        $allVisits = $scoringData['recordComeCount'] + $scoringData['recordDidNotComeCount'];
        if ($allVisits !== 0){
            $percent = 100 * $scoringData['recordDidNotComeCount'] / $allVisits;
        } else {
            $percent = 0;
        }
        if ($percent >= $percentStop){
            return true;
        } else {
            return false;
        }
    }

    public static function guaranteeStop($scoringData){
        $percentStop = 20;
        $allVisits = $scoringData['recordComeCount'] + $scoringData['recordDidNotComeCount'];
        if ($allVisits !== 0){
            $percent = 100 * $scoringData['guaranteeCount'] / $allVisits;
        } else {
            $percent = 0;
        }
        if ($percent >= $percentStop){
            return true;
        } else {
            return false;
        }
    }

    public static function conditionRecordLabels($record){
        if (is_array($record['record_labels']) && count($record['record_labels']) > 0){
            foreach($record['record_labels'] as $label){
                if (time() > $record['datetime']){
                    if (in_array($label['id'], Config::$guaranteeTags)){
                        return true;
                    }
                }
            }
        }
        return false;
    }

    public static function conditionRecordAttendance($record, $attendance){
        if ($record['attendance'] == $attendance){
            return true;
        }
        return false;
    }

    public static function getEndDay(){
        $Y = date("Y", time());
        $m = date("m", time());
        $d = date("d", time());
//        Core::sendDev("getEndDay = $Y-$m-$d 23:59:59 = ".strtotime("$Y-$m-$d 23:59:59"));
        return strtotime("$Y-$m-$d 23:59:59");
    }

    public static function getScoringData($recordsFromYclients){
        $datetimeLast = strtotime('2000-01-01');
        $recordLast = null;
        $records = [];
        $guaranteeCount = 0;
        $recordComeCount = 0;
        $recordDidNotComeCount = 0;
        foreach($recordsFromYclients as $record){
            $records[] = $record;
            if (self::conditionRecordAttendance($record, '1')){$recordComeCount++;}
            if (self::conditionRecordAttendance($record, '-1')){$recordDidNotComeCount++;}
            if (self::conditionRecordLabels($record)){$guaranteeCount++;}

            if (
                $datetimeLast < strtotime($record['datetime']) &&
                self::getEndDay() > strtotime($record['datetime']) &&
                ($record['attendance'] == '1' || $record['attendance'] == '-1')
            ){
                $datetimeLast = strtotime($record['datetime']);
                $recordLast = $record;
            }
        }
//        Core::sendDev('recordLast = '.json_encode($recordLast,1));
        return [
            'records' => $records,
            'recordLast' => $recordLast,
            'guaranteeCount' => $guaranteeCount,
            'recordComeCount' => $recordComeCount,
            'recordDidNotComeCount' => $recordDidNotComeCount,
        ];
    }

    public static function checkFutureRecords($scoringData){
        $todayEnd = date(date("Y", time())."-".date("m", time())."-".date("d", time())." 23:59:59");
        print_r("<pre>");
        print_r($todayEnd);
        print_r("</pre>");
        $dates = [];
        foreach($scoringData['records'] as $record){
            if (
            !in_array(Record::getStaffId($record), Config::$staffListPrepaymentsIds) &&
            !in_array(Record::getStaffId($record), Config::$staffListAdminIds) &&
            !in_array(Record::getStaffId($record), Config::$staffListWaitIds)
            ){
                if (strtotime($todayEnd) < strtotime($record['datetime'])){

                    $recordDatetime = date("Y-m-d", strtotime($record['datetime']));
                    print_r("<pre>");
                    print_r($recordDatetime);
                    print_r("</pre>");
                    if (!isset($dates[$recordDatetime])){
                        $dates[$recordDatetime] = 1;
                    } else {
                        $dates[$recordDatetime]++;
                    }
                }
            }
        }
        print_r("<pre>");
        print_r($dates);
        print_r("</pre>");
        if (count($dates) > 1){
            $i = 1;
            foreach($dates as $date => $count){
                if ($i !== 1){

                    if ($count == 1){
                        return true;
                    }

                }
                $i++;
            }

        }
        return false;
    }

    public static function checkTagMustPrepaymentForThisRecord($record){
        $TagMustPrepayment = false;
        if (is_array($record['record_labels']) && count($record['record_labels']) > 0){
            foreach($record['record_labels'] as $label){
                if (in_array($label['id'], Config::$mustPrepaymentTags)){
                    $TagMustPrepayment = true;
                }
                if (in_array($label['id'], self::$recordLabelsNotPrepayment)){
                    self::$notPrepaymentImportant = true;
                }
            }
        }
        return $TagMustPrepayment;
    }

    public static function checkTagMustPrepaymentForClient($clientPhone){
        $TagMustPrepaymentForClient = false;
        foreach(Config::getConfig() as $companyId => $userToken){
            $clients = Config::getYclientsApi()->getClients($companyId, $userToken, null, $clientPhone);
            foreach($clients['data'] as $client){
                self::$clientsIds[$companyId] = $client['id'];
                if(!empty($client['categories']) && is_array($client['categories']) && count($client['categories']) > 0){
                    foreach($client['categories'] as $tag){
                        if (in_array($tag['id'], Config::$mustPrepaymentTagsForClient)){
                            $TagMustPrepaymentForClient = true;
                        }
                        if (in_array($tag['id'], self::$clientLabelsNotPrepayment)){
                            self::$notPrepaymentImportant = true;
                        }
                    }
                }
            }
        }
        return $TagMustPrepaymentForClient;
    }


    public static function checkTagNotPrepaymentImportant($record){
        $clientPhone = Record::getClientPhone($record);
        $notPrepaymentImportant = false;

        // на уровне клиента
        foreach(Config::getConfig() as $companyId => $userToken){
            $clients = Config::getYclientsApi()->getClients($companyId, $userToken, null, $clientPhone);
            foreach($clients['data'] as $client){
                self::$clientsIds[$companyId] = $client['id'];
                if(!empty($client['categories']) && is_array($client['categories']) && count($client['categories']) > 0){
                    foreach($client['categories'] as $tag){
                        if (in_array($tag['id'], self::$clientLabelsNotPrepayment)){
                            $notPrepaymentImportant = true;
                        }
                    }
                }
            }
        }

        // на уровне записи
        if (is_array($record['record_labels']) && count($record['record_labels']) > 0){
            foreach($record['record_labels'] as $label){
                if (in_array($label['id'], self::$recordLabelsNotPrepayment)){
                    $notPrepaymentImportant = true;
                }
            }
        }

        return $notPrepaymentImportant;

    }


}
