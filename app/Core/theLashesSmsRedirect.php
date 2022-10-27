<?php
namespace App\Core;

/*
Алгоритм The Lashes SMS Redirect:

При создании записи (а также при Изменении/удалении и иных событиях)

1) Изменяем или Создаем сущности Подписчика с параметрами
$EventForCheckWhatsApp = 'ListWaitAutoNotification';
$current = 'AutoNotification';

2) theLashesModel::insertSmsId('1-'.$record['id']); - добавляем защифрованный код в базе для редиректа к нужному событию.

---

Далее человек переходит по ссылке и попадает в theLashesSmsRedirect::theLashesSmsRedirectWithHash($hash)
Узнаю код и получаю ИД события. И формирую стартовое сообщение при переходе в мессенджер.

-----

Когда человек отправил стартовое сообщения, мы его парсим методом theLashesSmsRedirect::getCodeFromHash и узнаю КОЖ
далее узнаю текст сообщения на основе события, которое напишем пользователю и
запустим определенный сценарий - theLashesSmsRedirect::messageWithHash($command, $chatId, $senderName, $code)

*/

class theLashesSmsRedirect
{

    public static function theLashesSmsRedirectWithHash($hash){
        $code = theLashesSmsRedirect::getCodeFromHash($hash);
        $eventId =  explode("-", $code)[0];
//        Core::sendDev("eventId = $eventId. code = $code. hash = $hash");
        switch($eventId){
            // создание записи к мастеру
            case "1": $message = "Добрый день! Пришлите информацию записи (адрес, время, схему проезда) на мой WhatsApp"; break;
            // создание записи в Лист Ожидания
            case "2": $message = "Узнать подробности!"; break;
            // авто-подтверждение визита на завтра
            case "3": $message = "Добрый день! Хочу подтвердить мою запись на завтра"; break;
            // вебхук "изменение записи"
            case "4": $message = "Добрый день! Пришлите информацию об изменении моей записи"; break;
            // авто-Напоминания о визите
            case "5": $message = "Добрый день! Напомните мне о моем визите"; break;
            // удаление записи
            case "6": $message = "Добрый день! Восстановите мою запись."; break;
            // DeletedRecordForDebts
            case "7": $message = "Добрый день! Восстановите мою запись."; break;

            default: $message = "Добрый день! Узнать детали записи"; break;
        }
        $message .= "
Идентификатор записи: #id$hash.";
//        Core::sendDev("message = $message");
        header("Location: whatsapp://send?phone=79197249947&text=".rawurlencode($message)."");
    }

    public static function bootstrap($eventId, $recordId){
        switch($eventId){
            // создание записи к мастеру
            case "1": $message = "Добрый день! Пришлите информацию записи (адрес, время, схему проезда) на мой WhatsApp"; break;
            // создание записи в Лист Ожидания
            case "2": $message = "Узнать подробности!"; break;
            // авто-подтверждение визита на завтра
            case "3": $message = "Добрый день! Хочу подтвердить мою запись на завтра"; break;
            // вебхук "изменение записи"
            case "4": $message = "Добрый день! Пришлите информацию об изменении моей записи"; break;
            // авто-Напоминания о визите
            case "5": $message = "Добрый день! Напомните мне о моем визите"; break;
            // удаление записи
            case "6": $message = "Добрый день! Восстановите мою запись."; break;
            default: $message = "Добрый день! Узнать детали записи"; break;
        }
        $rand = rand(100,999);
        $code = "#id-$eventId-$recordId-$rand";
        $hash = theLashesModel::insertSmsId('#id-$eventId-$recordId-$rand');
        $message .= "
```Идентификатор записи: #$eventId-$recordId-$rand ```";
        header("Location: whatsapp://send?phone=79197249947&text=".rawurlencode($message)."");
    }

    public static function getCodeFromHash($command){
        $allHashes = theLashesModel::getAllHashes();
        $code = false;
//        Core::sendDev("command=$command");
        if (stristr($command, '#id')){
            $explode = explode("#id", $command);
            if (isset($explode[1])){
                $explode2 = explode(".", $explode[1]);
                $hash = $explode2[0];
                if (isset($allHashes[$hash])){
                    $code = $allHashes[$hash];
                }
            }
        } else {
            $result = theLashesModel::getHash($command);
            if ($result != false){
                $code = $result->code;
            }
        }
        return $code;
    }

    public static function messageWithHash($command, $chatId, $senderName, $code){
        $production = true;
        $explode2 = explode("-", $code);
        $explodeEventId = $explode2[0];
        $explodeRecordId = $explode2[1];
        $explodeClientPhone = YclientsCore::getOnlyNumberString($chatId);
        $findRecordDB = theLashesModel::getRecord($explodeRecordId);
        $paymentStatus = $findRecordDB->paymentStatus;
        if($findRecordDB){
            $record = json_decode($findRecordDB->record,1);
            if (TheLashes::checkRecordDontBegin($record)){
                $clientPhone = YclientsCore::getClientPhone($record);
                if ($explodeClientPhone != $clientPhone){
                    $errorYclients = false;
                    $clientPhone = YclientsCore::getClientPhone($record);
                    $adminMessage = "Добавь прямо сейчас правильные телефонные номера клиента, перейдя по КАЖДОЙ из ссылок 👇

Основной номер: '$explodeClientPhone
Дополнительный номер: '$clientPhone
";
                    foreach(Config::getConfig() as $companyId => $userToken){
                        $clients = Config::getYclientsApi()->getClients($companyId, $userToken, null, $clientPhone);
                        foreach($clients['data'] as $client){
                            $fields = $client;
                            $fields['phone'] = "+$explodeClientPhone";
                            $result = Config::getYclientsApi()->putClient($companyId, $client['id'], $userToken, $fields);
                            if (isset($result['errors']['message'])){
                                $errorYclients = true;
                                $errorMessage = $result['errors']['message'];
                                $date = date("Y-m-d", strtotime($record["datetime"]));
                                $adminMessage .= "
https://yclients.com/timetable/$companyId#main_date=$date
```$errorMessage```";
                            } else {
                                $adminMessage .= "
https://yclients.com/clients/$companyId?name=$explodeClientPhone";
                            }
                            if(!$errorYclients){
                                $record['client']['phone'] = "+$explodeClientPhone";
                                YclientsCore::updateRecord($record);
                            }
                        }
                    }
//                    Core::sendDev($adminMessage);
                    YclientsCore::sendAdmin($adminMessage, '74957647602');
                }


                //// ТУТ
                $message = self::returnMessageFromEvent($explodeEventId, $command, $record, $paymentStatus);
            } else {
                $message = TheLashesMessage::messageDefault($command);
                if($production){
                    TheLashesMessage::sendUnrecognizedMessageToAdmin($command, $chatId, $senderName);
                }
            }
        } else {
            // сообщение по умолчанию
            $message = TheLashesMessage::messageDefault($command);
            if($production){
                TheLashesMessage::sendUnrecognizedMessageToAdmin($command, $chatId, $senderName);
            }
        }
        return $message;
    }

    public static function message($command, $chatId, $senderName){

        $production = true;

        $explode = explode("#", $command);
        $explode2 = explode("-", $explode[1]);
        $explodeEventId = $explode2[0];
        $explodeRecordId = $explode2[1];
        $explodeClientPhone = YclientsCore::getOnlyNumberString($chatId);
        $findRecordDB = theLashesModel::getRecord($explodeRecordId);
        if($findRecordDB){
            $record = json_decode($findRecordDB->record,1);
            if (TheLashes::checkRecordDontBegin($record)){
                $clientPhone = YclientsCore::getClientPhone($record);
                if ($explodeClientPhone != $clientPhone){
                    $errorYclients = false;
                    $clientPhone = YclientsCore::getClientPhone($record);
                    $adminMessage = "Добавь прямо сейчас правильные телефонные номера клиента, перейдя по КАЖДОЙ из ссылок 👇

Основной номер: '$explodeClientPhone
Дополнительный номер: '$clientPhone
";
                    foreach(Config::getConfig() as $companyId => $userToken){
                        $clients = Config::getYclientsApi()->getClients($companyId, $userToken, null, $clientPhone);
                        foreach($clients['data'] as $client){

                            $fields = $client;
                            $fields['phone'] = "+$explodeClientPhone";
                            $result = Config::getYclientsApi()->putClient($companyId, $client['id'], $userToken, $fields);

                            if (isset($result['errors']['message'])){
                                $errorYclients = true;
                                $errorMessage = $result['errors']['message'];
                                $date = date("Y-m-d", strtotime($record["datetime"]));
                                $adminMessage .= "
https://yclients.com/timetable/$companyId#main_date=$date
```$errorMessage```";
                            } else {
                                $adminMessage .= "
https://yclients.com/clients/$companyId?name=$explodeClientPhone";
                            }

                            if(!$errorYclients){
                                $record['client']['phone'] = "+$explodeClientPhone";
                                YclientsCore::updateRecord($record);
                            }
                        }
                    }
//                    Core::sendDev($adminMessage);
                    YclientsCore::sendAdmin($adminMessage, '74957647602');
                } else {
                    print_r("<pre>");
                    print_r("Телефоны совпадают");
                    print_r("</pre>");
//                    Core::sendDev("АНДРЕЙ ВНИМАНИЕ !!!!!!!!!!!!!!! Телефоны совпадают $explodeClientPhone и $clientPhone ");
                }





                $message = self::returnMessageFromEvent($explodeEventId, $command, $record);
            } else {
                $message = TheLashesMessage::messageDefault($command);
                if($production){
                    TheLashesMessage::sendUnrecognizedMessageToAdmin($command, $chatId, $senderName);
                }
            }
        } else {
            // сообщение по умолчанию
            $message = TheLashesMessage::messageDefault($command);
            if($production){
                TheLashesMessage::sendUnrecognizedMessageToAdmin($command, $chatId, $senderName);
            }
        }
        return $message;
    }

    public static function returnMessageFromEvent($eventId, $command, $record, $paymentStatus = ''){
        $companies =  YclientsCore::getCompaniesInfo(Config::getYclientsApi(), Config::getConfig());
        switch($eventId){
            // создание записи к мастеру
            case "1": $message = TheLashesMessage::getMessageCreateRecordToMaster($record, $companies, $paymentStatus); break;
            // создание записи в Лист Ожидания
            case "2": $message = TheLashesMessage::getMessageCreateRecordToListWait($record); break;
            // авто-подтверждение визита на завтра
            case "3": $message = TheLashesMessage::getMessageAutoConfirmationToMaster($record, $companies); break;
            // вебхук "изменение записи"
            case "4": $message = TheLashesMessage::getMessageRecordUpdate($record, null, $companies); break;
            // авто-Напоминания о визите
            case "5": $message = TheLashesMessage::getMessageReminder($record, $companies); break;
            // визит удален
            case "6": $message = TheLashesMessage::getMessageDelete($record); break;
            // визит удален за НЕУПЛАТУ
            case "7": $message = TheLashesMessage::getMessageDeleteForDebts($record); break;
            // нераспознанное сообщение
            default: $message = TheLashesMessage::messageDefault($command); break;
        }
        return $message;
    }
}
