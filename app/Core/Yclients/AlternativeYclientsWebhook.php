<?php
//
//
//namespace App\Core\Yclients;
//
//
//use App\Core\Config;
//use App\Core\Core;
//use App\Core\Smsc;
//use App\Core\TheLashesMessage;
//use App\Core\WhatsApp;
//use App\Core\YclientsCore;
//use Illuminate\Support\Facades\DB;
//
//class AlternativeYclientsWebhook
//{
//
//    public static function cron(){
//        $startPHP = microtime(true);
//        $saveRecords = Assistant::getSaveRecords();
//        $actualRecords = Assistant::getActualRecords();
//
//        foreach($actualRecords as $actualRecord){
//            print_r("<pre>");
//            print_r($actualRecord);
//            print_r("</pre>");
//            if (!empty($saveRecords[$actualRecord['id']])){
//                print_r("<pre>");
//                print_r('этот рекорд есть в базе. Нужно проеврить на UPDATE');
//                print_r("</pre>");
//            } else {
//                print_r("<pre>");
//                print_r('этого рекорда нет в базе. ДОБАВЛЕН НОВЫЙ РЕКОРД! CREATE !!!!');
//                print_r("</pre>");
//            }
//        }
//
//        print_r("<pre>");
//        print_r('------------------------------------------------');
//        print_r("</pre>");
//        foreach($saveRecords as $saveRecord){
//            if (!empty($actualRecords[$saveRecord['id']])){
//                print_r("<pre>");
//                print_r('этот рекорд есть и в базе и в актуальном');
//                print_r("</pre>");
//            } else {
//                print_r("<pre>");
//                print_r('этот рекорд исчез!. УДАЛЕН РЕКОРД! DELETE !!!!');
//                print_r("</pre>");
//            }
//        }
//
//        print_r("<pre>");
//        print_r("<b>The Lashes Alt Webhook Time: ".(microtime(true) - $startPHP) . " sec</b>");
//        print_r("</pre>");
//        Core::sendDev("<b>The Lashes Alt Webhook Time: ".(microtime(true) - $startPHP) . " sec</b>");
//    }
//
//
//    public static function YclientsRecordCreate($record){
//        if (time() > strtotime($record['datetime'])){ return null; } // визит в прошлом
//        if(empty($record['client']['phone'])){ return null; } // техническая запись
//
//        YclientsCore::addRecord($record);
//        $clientName = YclientsCore::getClientNameFromRecord($record, 'Здравствуйте!', ',');
//        $clientPhone = YclientsCore::getClientPhone($record);
//
//        if(in_array(YclientsCore::getStaffId($record), Config::$staffListWaitIds)){
//            if ($record['online']){
//                $message = TheLashesMessage::getMessageCreateRecordToListWait($record);
//            } else {
//                $message = TheLashesMessage::getMessageCreateRecordToListWaitAdminOffline($record);
//            }
//            $smsMessage = 'Запись в Листе ожидания! Подробнее: https://thelashes.ru/?2';
//            $current = 'AutoNotification';
//        } else {
//            $smsMessage = 'Подтвердите запись по ссылке: https://thelashes.ru/?1';
//            $current = null;
//            $message = TheLashesMessage::getMessageCreateRecordToMaster($record, $companies);
//            $calendar = TheLashesMessage::addCalendar($record);
////            Core::sendDev($calendar);
////                $message = "$message
////
////$calendar";
////                Core::sendDev("$message
////
////$calendar");
//        }
//
//
//
//
//        $numberSecondsToday = time() - strtotime('today');
//        $numberSecondsTodayLeft = 2* 24 * 60 * 60 - $numberSecondsToday;
//        if(time() > strtotime($record['datetime']) - $numberSecondsTodayLeft){
//            if(!in_array(YclientsCore::getStaffId($record), Config::$staffListWaitIds)){
////                    YclientsCore::sendTelegram('визит создан в будущем но не более двух дней для мастера');
//
//                foreach(Config::getConfig() as $companyId => $userToken) {
//                    if ($record['company_id'] == $companyId) {
//                        $recordId = $record['id'];
//                        $visitId = $record['visit_id'];
//                        $putVisitServices = [];
//                        foreach ($record['services'] as $i => $service) {
//                            $putVisitServices[$i] = $service;
//                            $putVisitServices[$i]['record_id'] = $recordId;
//                        }
//                        $fields = [
//                            'attendance' => 2, // клиент подтвердил   //'attendance' => -1, // клиент отменил
//                            'comment' => '',
//                            'services' => $putVisitServices
//                        ];
//                        $r = YclientsCore::putVisit(Config::getYclientsApi(), $fields, $visitId, $recordId, $userToken);
//                    }
//                }
//
//            }
//        }
//
//        $companyId = $data['company_id'];
//
////            YclientsCore::sendTelegram('перед бд');
//        $clientPhone = YclientsCore::getOnlyNumberString($clientPhone);
//        $user = DB::table('bot')->where('phone', $clientPhone)->first();
//        if ($user === null) {
//            $insert = [];
//            $insert['vendor'] = 'whatsapp';
//            $insert['userId'] = $clientPhone . '@c.us';
//            $insert['userName'] = $clientName;
//            $insert['phone'] = $clientPhone;
//            $insert['current'] = $current;
//            $insert['waLastStatus'] = null;
//            $insert['LastAutoNotification'] = json_encode($record, true);
//            $insert['LastUpdateRecord'] = json_encode($record, true);
//            $insert['updateTime'] = time();
//            $insert['LastAction'] = 'AutoNotification';
//            $insert['checkAction'] = 0;
//            DB::table('bot')->insert($insert);
//        } else {
//            $update = [];
//            $update['current'] = $current;
//            $insert['userName'] = $clientName;
//            $update['waLastStatus'] = null;
//            $update['LastAutoNotification'] = json_encode($record, true);
//            $update['LastUpdateRecord'] = json_encode($record, true);
//            $update['updateTime'] = time();
//            $update['LastAction'] = 'AutoNotification';
//            $update['checkAction'] = 0;
//            DB::table('bot')->where('phone', $clientPhone)->update($update);
//        }
////            YclientsCore::sendTelegram('после бд');
//        $whatsapp = new WhatsApp($this->waApiUrl, $this->token);
//        $data = [
//            'phone' => $clientPhone,
//            'body' => $message
//        ];
//
//
//
//
//        if (!self::$smsActivated || YclientsCore::checkAccessUserSendMessageToWhatsApp($clientPhone)){
//            $whatsapp->request('readChat', ['phone' => $clientPhone]);
//            $result = $whatsapp->request('sendMessage', $data);
//            $whatsapp->request('readChat', ['phone' => $clientPhone]);
//        } else {
//
//            $message = $smsMessage;
//
//            $login = $this->smsConfig[$companyId]['login'];
//            $password = $this->smsConfig[$companyId]['password'];
//
//            $sender = $this->AlfaName;
//            $result = Smsc::sendSMS($login, $password, $clientPhone, $message, $sender);
//            YclientsCore::sendAdminTelegram("
//<b>The Lashes WA bot</b>
//Отправлено смс на номер $clientPhone
//Сообщение: $message
//Ответ сервера: $result");
//        }
//    }
//
//}