<?php


namespace App\Core;


use App\Models\Records;
use App\Yclients\Assistant\Control;
use App\Yclients\Modules\Scoring\Scoring;
use App\Yclients\Notification\Notification;
use App\Yclients\Record\Record;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class Payment
{

    public static function webhookPayment(Request $request){
        $hash = '8d7872979b96ad122587d6e70482a932f3da18b3';
        $orderId = $request->input('Order_id');
        $status = $request->input('Status');
        $secret = $request->input('secret');
        $total = $request->input('Total');
//        Core::sendDev("The Lashes Bot webhookPayment :: пришел статус $status");
//        Core::sendDev("The Lashes Bot webhookPayment :: total $total");
        if ($secret === strtoupper(md5($orderId . $status . $total. $hash))){


            if ($status == 'authorized' || $status == 'paid'){




//                Core::sendDev("The Lashes Bot Payment Request^
//        $orderId
//        $status
//        $secret
//        ");

                self::paymentToYclients($orderId, $total, true);

            } else {
//                Core::sendDev("The Lashes Bot :: пришел статус $status");
            }
        } else {
//            Core::sendDev("The Lashes Bot :: error secret payment key.");
        }
    }

    public static function getServicesWithDiscountForPrepayment($services, $sum){
        $servicesWithDiscount = [];
        $countServices = count($services);
        if ($countServices == 0){
            $cost = $sum;
        } else {
            $cost = round($sum / $countServices);
        }
        foreach($services as $i => $service){
            $correction = 0;
            if ($i === 0){
                if ($cost * count($services) !== $sum){
                    $correction = $cost * count($services) - $sum;
                }
            }
            $serviceWithDiscount = $service;
            if ($serviceWithDiscount['cost'] == 0){
                $discount = 0;
            } else {
                $discount = 100 - 100 * $cost / $serviceWithDiscount['cost'];
            }
            $serviceWithDiscount['cost'] = $cost - $correction;
            $serviceWithDiscount['discount'] = $discount;
            $servicesWithDiscount[] = $serviceWithDiscount;
        }
        return $servicesWithDiscount;
    }

    public static function getServicesWithDiscount($services, $sum){
        $servicesWithDiscount = [];
        $countServices = count($services);
        if ($countServices == 0){
            $cost = $sum;
        } else {
            $cost = round($sum / $countServices);
        }
        foreach($services as $i => $service){
            $correction = 0;
            if ($i === 0){
                if ($cost * count($services) !== $sum){
                    $correction = $sum - $cost * count($services);
                }
            }
            $serviceWithDiscount = $service;
            if ($serviceWithDiscount['cost'] == 0){
                $discount = 100;
            } else {
                $discount = 100 * $cost / $serviceWithDiscount['cost'];
            }
            $serviceWithDiscount['cost'] = $serviceWithDiscount['cost'] - $cost - $correction;
            $serviceWithDiscount['discount'] = $discount;
            $servicesWithDiscount[] = $serviceWithDiscount;
        }
        return $servicesWithDiscount;
    }

    public static function getAccountId($companyId){
        $accountId = null;
        switch($companyId){
            case '41332': $accountId = 522791; break; // пресня
            case '138368' : $accountId = 502541; break; // Автозаводская
            case '225366' : $accountId = 522796; break; // Раменки
        }
        return $accountId;
    }

    public static function financeTransaction($companyId, $amount, $record, $accountId){
        $fields = [
            'expense_id' => 5,
            'account_id' => $accountId,
            'amount' => $amount,
            'date' => date('Y-m-d H:i:s'),
            'client_id' => Record::getClientId($record),
            'master_id' => YclientsCore::getStaffId($record),
            'comment' => "Чат-бот",
            'record_id' => $record['id'],
        ];
        $financeTransaction = YclientsCore::yclientsRequest(Config::getYclientsApi(), "finance_transactions/$companyId", $fields, 'POST', Config::getConfig()[$companyId]);
        return $financeTransaction;
    }

    public static function putClientVisitWithDiscount($record, $total){
        $servicesWithDiscount = self::getServicesWithDiscount($record['services'], $total);
        $fields = [
            'staff_id' => $record['staff_id'],
            'services' => $servicesWithDiscount,
            'client' => $record['client'],
            'datetime' => $record['datetime'],
            'comment' => $record['comment'],
            'seance_length' => $record['seance_length'],
            'attendance' => 0,
//            'fast_payment' => 130,
            'visit_attendance' => 0
        ];
        $res =  Config::getYclientsApi()->putRecord($record['company_id'], $record['id'], Config::getConfig()[$record['company_id']], $fields);
    }

    public static function putVisitAndConnectVisitWithFinanceTransaction($record, $financeTransactionId, $total, $deleteFinanceTransaction){
        $visitId = $record['visit_id'];
        $recordId = $record['id'];
        $companyId = $record['company_id'];
        $accountId = self::getAccountId($companyId);

        $visit = YclientsCore::yclientsRequest(Config::getYclientsApi(), "visits/$visitId", [], 'GET', Config::getConfig()[$companyId]);
//        $services = $visit['records'][0]['services'];
//        $serviceWithRecordId = [];
//        foreach($services as $key => $service){
//            $serviceWithRecordId[$key] = $service;
//            $serviceWithRecordId[$key]['record_id'] = $recordId;
//        }
//        $servicesWithDiscount = self::getServicesWithDiscountForPrepayment($serviceWithRecordId, $total);

        $serviceWithRecordId = [];
        $i = 0;
        foreach($visit['records'] as $visitRecord){
            if ($visitRecord['id'] === $recordId){
                foreach($visitRecord['services'] as $service){
                    $serviceWithRecordId[$i] = $service;
                    $serviceWithRecordId[$i]['record_id'] = $visitRecord['id'];
                    $i++;
                }
            }
        }
        $servicesWithDiscount = Payment::getServicesWithDiscountForPrepayment($serviceWithRecordId, $total);
        foreach($visit['records'] as $visitRecord){
            if ($visitRecord['id'] !== $recordId){
                foreach($visitRecord['services'] as $service){
                    $serviceWithRecordIdCleanArray = $service;
                    $serviceWithRecordIdCleanArray['record_id'] = $visitRecord['id'];
                    $i++;
                    $servicesWithDiscount[] = $serviceWithRecordIdCleanArray;
                }
            }
        }

        $fields = [
            'comment' => $visit['comment'],
            'attendance' => 1,
            'services' => $servicesWithDiscount,
            'goods_transactions' => $visit['records'][0]['goods_transactions'],
//                'fast_payment' => 130,
            'new_transactions' => [
                [
                    'id' => $financeTransactionId,
                    'amount' => $total,
                    'account_id' => $accountId,
                ]
            ],
            'deleted_transaction_ids' => [$financeTransactionId]
        ];

//        if ($deleteFinanceTransaction){
//            $fields['deleted_transaction_ids'] = [$financeTransactionId];
//        }

        $visit = YclientsCore::yclientsRequest(Config::getYclientsApi(), "visits/$visitId/$recordId", $fields, 'PUT', Config::getConfig()[$companyId]);

        print_r("<pre>");
        print_r($visit);
        print_r("</pre>");

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
        $services = $servicesWithDiscountForPrepayment;
//        $services = $record['services'];
        $client = $record['client'];
        $DateTime = new \DateTime;
        $time = time();
        $DateTime->setDate(date('Y', $time), date('m', $time), date('d', $time));
        $DateTime->setTime(date('H', $time), date('i', self::getMinutes15(time())));
        $result = Config::getYclientsApi()->postRecords($companyId, Config::getConfig()[$companyId], $staffId, $services, $client, $DateTime, 15*60, 1, 0);
        return $result;
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

    public static function checkPaymentDeletedRecord($orderId, $total){
        $result = Records::getDeletedRecord($orderId);
        if ($result !== null){
            $record = json_decode($result->record, 1);
            $datetime = $record['date'];
            $studioName = YclientsCore::getStudioNameFromRecord($record, YclientsCore::getCompaniesInfo(Config::getYclientsApi(), Config::getConfig()));
            $services = YclientsCore::getServicesString($record);
            $clientName = YclientsCore::getClientNameFromRecord($record);
            $clientPhone = YclientsCore::getClientPhone($record);
            $message = "⚠Внимание!

Клиент оплатил уже удаленную запись. Восстанови запись без предоплаты или оформи возврат денег.

Студия: $studioName
Дата время записи: $datetime
Имя клиента: $clientName
Услуги: $services
Телефон: +$clientPhone

Для этой записи:
- запись не отмечена как оплаченная и может быть удалена!
- не будет создана запись для сотрудника Предоплата!
- не будет учтена скидка!
- не отправлено сообщение клиенту об успешной оплате!
- не создана финансовая транзакция в Yclients!

Сообщить разработчикам о внештатной ситуации.
";
        } else {
            $message = "Внимание! Пришла успешная оплата $total руб. Заказ №$orderId.
Записи нет ни в базе бота, ни в удаленных. Клиент испортил ссылку и оплатил по испорченной ссылке.
Для этого заказа:
- запись не будет отмечена как оплаченная и может быть удалена!
- не будет создана запись для сотрудника Предоплата!
- не будет учтена скидка!
- не отправлено сообщение клиенту об успешной оплате!
- не создана финансовая транзакция в Yclients!

Сообщить разработчикам о внештатной ситуации.
";
        }
        Notification::sendAdminTelegram($message);
        Notification::sendContactCenterWhatsApp($message);
    }

    public static function paymentToYclients($orderId, $total, $deleteFinanceTransaction = false){
        $recordDB = PaymentModel::getRecordDB($orderId);
        if ($recordDB === null){
            self::checkPaymentDeletedRecord($orderId, $total);
            die('ok');
        }
        $record = json_decode($recordDB->record,1);
        $recordId = $record['id'];
        $paymentStatus = $recordDB->paymentStatus;
        if ($paymentStatus === 'ok'){return 'ok';}
        PaymentModel::paymentSuccess($recordId, $total);
        $paymentSum = $recordDB->paymentSum;
        $amount = $total;
        $companyId = $record['company_id'];
        $accountId = self::getAccountId($companyId);
        $recordPrepayment = self::addRecordToPrepaymentMaster($record, $total);
        $financeTransaction = self::financeTransaction($companyId, $amount, $recordPrepayment, $accountId);
        if ($financeTransaction['success']){
            print_r("<pre>");
            print_r($financeTransaction);
            print_r("</pre>");
            $financeTransactionId = $financeTransaction['data']['id'];
            self::putVisitAndConnectVisitWithFinanceTransaction($recordPrepayment, $financeTransactionId, $total, $deleteFinanceTransaction); // визит, который создан на сотрудника Предоплата в момент Сейчас
            self::putClientVisitWithDiscount($record, $total); // визит, который в будущем я обновляю.
            $clientPhone = YclientsCore::getClientPhone($record);
            $message = TheLashesMessage::getMessagePaymentOk($record, $total);
            $whatsapp = new WhatsApp(Config::$waApiUrl, Config::$waToken);
            if (YclientsCore::checkAccessUserSendMessageToWhatsApp($clientPhone)) {
                $data = [
                    'phone' => $clientPhone,
                    'body' => $message
                ];
                $result = $whatsapp->request('sendMessage', $data);
            } else {
                $rub = '₽';
                $smsMessage = "Вы успешно оплатили $total$rub. Детали: https://thelashes.ru/?_=".theLashesModel::insertSmsId('1-'.$record['id']);
                $result = Smsc::sendSMS(Config::$smsConfig[$companyId]['login'], Config::$smsConfig[$companyId]['password'],
                    $clientPhone, $smsMessage, Config::$AlfaName);
            }

            $data = [
                'phone' => self::getAdminPhone($record),
                'body' => self::adminMessage($record, $total, $paymentSum)
            ];
            $result = $whatsapp->request('sendMessage', $data);

//            Core::sendDev("Сообщение админу - ".self::adminMessage($record, $total, $paymentSum)."отправлено на номер ".self::getAdminPhone($record));
        } else {
            Core::sendDev("The Lashes Bot webhookPayment :: financeTransaction['success'] = FALSE");
            Core::sendDev("The Lashes Bot webhookPayment :: ".$financeTransaction['meta']['message']);
        }
    }

    public static function getShopId($record){
        $companyId = $record['company_id'];
        $shopId = 0;
        //            00018860 - Пресня (основной) поддерживает Apple Pay Google Pay
//00019154 - Пресня (резерв) на случай проблем с основным
//00020008 - Автозаводская
//00020009 - Раменки
        switch ($companyId){
            case '41332' : $shopId = '00018860'; break; // Пресня
            case '138368' : $shopId = '00020008'; break; // Автозаводская
            case '225366' : $shopId = '00020009'; break; // Раменки
        }
        return $shopId;
    }

    public static function getAdminPhone($record){
        $companyId = $record['company_id'];
        $phone = '79265896504';
        switch ($companyId){
            case '41332' :  $phone = '74957647602'; break; // Пресня
            case '138368' : $phone = '79629952225'; break; // Автозаводская
            case '225366' : $phone = '79774050705'; break; // Раменки
        }
        return $phone;
    }

    public static function getPaymentLink($record, $sendScoringNotification = false){
        if ( Payment::checkActivatePaymentMode($record, $sendScoringNotification) ){
            $customerId = YclientsCore::getClientId($record);
            $orderId = $record['id'];
            $email = YclientsCore::getClientEmail($record);
            if ($email == ""){
                $emailText = "";
            } else {
                $emailText = "&email=$email";
            }
            $sum = Payment::getPrepaymentSumFromRecord($record);
            $shopId = self::getShopId($record);
            $link = "https://thelashes.ru/payment/?shop_id=$shopId&customer_id=$customerId&order_id=$orderId&price=$sum$emailText";
            PaymentModel::paymentSave($record, $sum);
        } else {
            $link = "";
        }
        return $link;
    }

    public static function checkActivatePaymentMode($record, $sendScoringNotification = true){
        $mode = false;
        $config = json_decode(PaymentModel::getPaymentConfig()->value,1);
        $recordDatetime = strtotime($record['datetime']);
        $dateStart = strtotime($config['dateStart']);
        $dateGlobalMustPrepaymentStart = strtotime($config['dateGlobalMustPrepaymentStart']);
        $dateFinish = strtotime($config['dateFinish']);
        $dateGlobalMustPrepaymentFinish = strtotime($config['dateGlobalMustPrepaymentFinish']);

        $clientPhone = Record::getClientPhone($record);
        $clientName = Record::getClientName($record);

        $scoringTagNotPrepaymentImportant = Scoring::checkTagNotPrepaymentImportant($record);

        // -1. СВЕРХ ГЛОБАЛЬНОЕ УСЛОВИЯ. Если есть тег "не требуется предоплата визита", то всегда игнорируем все остальное.
        if ($scoringTagNotPrepaymentImportant === true){
            $mode = false;

            if ($sendScoringNotification){
                Notification::sendAdminTelegram("Предоплата. $clientName $clientPhone - Обнаружен тег 'Предоплата не требуется' с высшим приоритетом перед всеми остальными!");

            }

        } else {
            // 0. Соответсиве СУПЕР ГЛОБАЛЬНЫМ условиям
            if (
                $recordDatetime > $dateGlobalMustPrepaymentStart
                && $recordDatetime < $dateGlobalMustPrepaymentFinish
                && !YclientsCore::checkToday($record) // если Запись не с сегодня на сегодня
            ){
                $mode = true; // без выбора на скоринг и пр.
                Notification::sendAdminTelegram("Предоплата. $clientName $clientPhone - Обязательная глобальная предоплата");

            } else {
                // 1. Соответсиве ГЛОБАЛЬНЫМ условиям
                if (
                    $recordDatetime > $dateStart && // если дата/время записи БОЛЬШЕ чем дата СТАРТА модуля в админке
                    $recordDatetime < $dateFinish && // если дата/время записи МЕНЬШЕ чем дата КОНЦА в админке
                    !YclientsCore::checkToday($record) // если Запись не с сегодня на сегодня
                ){
                    if ($config['paymentMode'] == 1){
                        // обязательная предоплата для ВСЕХ
                        $mode = true;
                    } elseif($config['paymentMode'] == 2){
                        // режим ВЫБОРОЧНОЙ предоплаты после скоринга
                        if (Scoring::run($record, $sendScoringNotification)){
                            $mode = true;
                        }
                    }
                }
            }
        }








//        if (in_array(Record::getClientPhone($record), Test::$betaClients)){
//            if (Scoring::run($record)){
//                $mode = true;
//            }
//        }

        return $mode;
    }

    public static function cronCheckPaymentFirst($hours, $forceStart = false){
//        Notification::sendDevTelegram("cronCheckPaymentFirst $hours");
        if ($forceStart || Control::checkDatNowAndNotNight()) {
            $paymentTime = time() - $hours * 60 * 60;
            $recordsDB = PaymentModel::getRecordsDB([
                ['paymentStatus','=','0'], // не оплачено
                ['paymentSendReminder','=','0'], // отправили один раз напоминание клиенту в вотсап
                ['paymentAdminSendReminder','=','0'], // клиента нет в боте и отправили администратору
                ['paymentTime', '<', $paymentTime]
            ]);
            $whatsapp = new WhatsApp(Config::$waApiUrl, Config::$waToken);
            foreach($recordsDB as $recordDB){
                $record = json_decode($recordDB->record, 1);
                if(
                    !in_array(YclientsCore::getStaffId($record), Config::$staffListPrepaymentsIds) &&
                    !in_array(YclientsCore::getStaffId($record), Config::$staffListWaitIds)
                    && time() < strtotime($record['datetime'])
                ){
//                    if ( Payment::checkActivatePaymentMode($record, false) ){
                        $clientPhone = YclientsCore::getClientPhone($record);
                        $paymentTime = $recordDB->paymentTime ;
                        $paymentTimeFinish = $paymentTime + 24 * 60 * 60;
                        $paymentTimeLeftHours = round(($paymentTimeFinish - time())/60/60,0,PHP_ROUND_HALF_DOWN);
                        $message = TheLashesMessage::getMessagePaymentFirst($record, $paymentTimeLeftHours);

                        $sleep = rand(5, 15); sleep($sleep);
                        if (YclientsCore::checkAccessUserSendMessageToWhatsApp($clientPhone)) {
                            $data = [
                                'phone' => YclientsCore::getClientPhone($record),
                                'body' => $message
                            ];
                            $result = $whatsapp->request('sendMessage', $data);
                            PaymentModel::paymentSendReminder($record['id']);
                        } else {
//                            // отправляем сообщение админу
                            PaymentModel::paymentAdminSendReminder($record['id']);
                            if(!$recordDB->paymentAdminSendReminder){
                                if ($paymentTimeLeftHours >= 0){
                                    $adminMessage = "
Событие: Напоминание о предоплате

Уведомить клиента по альтернативным каналам связи!

Номер телефона: +$clientPhone

Ссылка на диалог: https://api.whatsapp.com/send?phone=".$clientPhone."&text=".urlencode($message)."
";
                                    YclientsCore::sendAdmin($adminMessage, '74957647602');
                                    Core::sendDev($adminMessage);
                                }
                            }
                        }
//                    }
                }
            }
        }
    }

    public static function cronCheckPaymentSecond(){
//        Notification::sendDevTelegram("cronCheckPaymentSecond");
        if (Control::checkDatNowAndNotNight()){
            $paymentTime = time() - 24 * 60 * 60;
            $recordsDB = PaymentModel::getRecordsDB([
                ['paymentStatus','=','0'],
                ['deletedForDebts','=','0'],
                ['paymentTime', '<', $paymentTime]
            ]);
            foreach($recordsDB as $recordDB){
                $record = json_decode($recordDB->record, 1);
                if(!in_array(YclientsCore::getStaffId($record), Config::$staffListPrepaymentsIds) && !in_array(YclientsCore::getStaffId($record), Config::$staffListWaitIds)){
                    if ( time() < strtotime($record['datetime']) && Payment::checkActivatePaymentMode($record, false) ){


                       Records::updateRecordCustomFields($record, ['deletedForDebts' => 1]);


                        $r = Config::getYclientsApi()->deleteRecord($record['company_id'], $record['id'], Config::getConfig()[$record['company_id']]);

//                        Core::sendDev(json_encode($r,1));
//                        print_r("<pre>ОТВЕТ айклаентс ");
//                        print_r($r);
//                        print_r("</pre>");
//                        print_r("<pre>Запись, для которой пришел ответ выше");
//                        print_r($record);
//                        print_r("</pre>");
                        YclientsCore::sendAdmin(TheLashesMessage::getMessageAdminDeleteRecord($record), '74957647602');

                        PaymentModel::paymentDelete($record);

                    }
                }
            }
        }
    }


    public static function getPrepaymentSumFromRecord($record){
        $sum = self::getTotalSumFromRecord($record);
        $config = json_decode(PaymentModel::getPaymentConfig()->value,1);
        $paymentMode = $config['paymentMode'];
        $paymentType = $config['paymentType'];
        $paymentAmount = $config['paymentAmount'];
        if (stristr(mb_strtolower(Record::getClientName($record)), Config::MODEL)){
//            $prepayment = $sum;
//            if ($prepayment == 0){
                $prepayment = $paymentAmount;
//            }
        } else {


            $prepayment = $paymentAmount;
            if ($paymentMode){
                if ($paymentType == 'fix'){
                    $prepayment = $paymentAmount;
                }
                if ($paymentType == 'percent'){
                    $prepayment = $sum * $paymentAmount / 100;
                }
            }
            if ($prepayment > $sum){
                $prepayment = $sum;
            }
            if ($prepayment == 0){
                $prepayment = $paymentAmount;
            }
        }
        return $prepayment;
    }

    public static function getTotalSumFromRecord($record){
        $sum = 0;
        foreach($record['services'] as $service){
            $sum = $sum + $service['cost'];
        }
        return $sum;
    }

    public static function adminMessage($record, $total, $paymentSum){
        $datetime = $record['date'];
        $master = '';
        $studioName = YclientsCore::getStudioNameFromRecord($record, YclientsCore::getCompaniesInfo(Config::getYclientsApi(), Config::getConfig()));
        $services = YclientsCore::getServicesString($record);
        $clientName = YclientsCore::getClientNameFromRecord($record);
        if (isset($record['staff']['name'])) {$master = $record['staff']['name'];}
        $clientPhone = '';
        if (isset($record['client']['phone'])) {$clientPhone = $record['client']['phone'];}
        $clientPhone = YclientsCore::getOnlyNumberString($clientPhone);

        $companyId = $record['company_id'];
        $date = date("Y-m-d", time());
        $rub = '₽';
        $message = "⚠ Распечатай чек предоплаты! ⚠
";
        if ($total == $paymentSum ){
            $message .= "Сумма $total$rub";

        } else {
            $message .= "Оплачено $total$rub, а сумма предоплаты была $paymentSum$rub !!!";
        }
//        Core::sendDev($message);
        $message .= "
Студия: $studioName
Дата время: $datetime
Имя: $clientName
Мастер: $master
Процедура: $services
Телефон клиента: +$clientPhone

Запись по ссылке: https://yclients.com/timetable/$companyId#main_date=$date";
        return $message;
    }

}
