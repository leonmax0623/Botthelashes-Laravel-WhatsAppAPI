<?php
/*
 * Тестирование модуля
 *
 * Создать запись с пустым номером телефона - ничего не произойдет, модуль остановлен
 * Создать запись "в прошлом" - ничего не произойдет, модуль остановлен
 * Создать запись к любому из сотрудников Предоплата - ничего не произойдет, модуль остановлен
 *
 * Если было более 5-ти наращиваний с промежутками меньше равно 35 дней Config::$BotoxTime, то добавить комментарий к записи и отправить сообщение админу
 *
 * Если запись на Лист Ожидания и онлайн и клиент подписан на бота - проверить сообщение
 * Если запись на Лист Ожидания и оффлайн и клиент подписан на бота - проверить сообщение
 * Если запись на Лист Ожидания и клиент НЕ подписан на бота - проверить сообщение
 *
 * Если запись к мастеру и клиент подписан на бота - проверить сообщение
 * Если запись к мастеру и клиент НЕ подписан на бота - проверить сообщение
 *
 * */
namespace App\Core\Events;
use App\Core\Config;
use App\Core\TheLashesMessage;
use App\Core\theLashesModel;
use App\Core\YclientsCore;

class YclientsWebhookCreateRecordEvent
{

    protected static function insertOrUpdateUserBotDB($record, $current, $EventForCheckWhatsApp){
        $clientPhone = YclientsCore::getClientPhone($record);
        $clientName = YclientsCore::getClientNameFromRecord($record);
        $user = theLashesModel::getUserBotFromDB($clientPhone);
        if ($user === null) {
            $insert = [];
            $insert['vendor'] = 'whatsapp';
            $insert['userId'] = $clientPhone . '@c.us';
            $insert['userName'] = $clientName;
            $insert['phone'] = $clientPhone;
            $insert['current'] = $current;
            $insert['waLastStatus'] = null;
            $insert['LastAutoNotification'] = json_encode($record, true);
            $insert['LastUpdateRecord'] = json_encode($record, true);
            $insert['updateTime'] = time();
            $insert['LastAction'] = 'AutoNotification';
            $insert['checkAction'] = 0;
            $insert['EventForCheckWhatsApp'] = $EventForCheckWhatsApp;
            $insert['RecordIdForCheckWhatsApp'] = $record['id'];
            $insert['CompanyIdForCheckWhatsApp'] = $record['company_id'];
            theLashesModel::insertToBot($insert);
        } else {
            $update = [];
            $update['current'] = $current;
            $insert['userName'] = $clientName;
            $update['waLastStatus'] = null;
            $update['LastAutoNotification'] = json_encode($record, true);
            $update['LastUpdateRecord'] = json_encode($record, true);
            $update['updateTime'] = time();
            $update['LastAction'] = 'AutoNotification';
            $update['checkAction'] = 0;
            $update['EventForCheckWhatsApp'] = $EventForCheckWhatsApp;
            $update['RecordIdForCheckWhatsApp'] = $record['id'];
            $update['CompanyIdForCheckWhatsApp'] = $record['company_id'];
            theLashesModel::updateBot($clientPhone, $update);
        }
    }

    protected static function changeStatusRecord($record){
        $numberSecondsToday = time() - strtotime('today');
        $numberSecondsTodayLeft = 24 * 60 * 60 + $numberSecondsToday;
        if(
            (time() > strtotime($record['datetime']) - $numberSecondsTodayLeft) // если запись на сегодня или завтра
            &&
            !in_array(YclientsCore::getStaffId($record), Config::$staffListWaitIds) // если это не Лист Ожидания
        ){
            $companyId = $record['company_id'];
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
            $r = YclientsCore::putVisit(Config::getYclientsApi(), $fields, $visitId, $recordId, Config::getConfig()[$companyId]);
        }
    }

    public static function stopEvent($record){
        // если запись создана на одного из сотрудников Предоплаты
        if (in_array(YclientsCore::getStaffId($record), Config::$staffListPrepaymentsIds)){return false;}

        // если запись создана на одного из сотрудников Администраторов
        if(in_array(YclientsCore::getStaffId($record), Config::$staffListAdminIds)){return false;}

        // если запись создана "в прошлом", когда текущее время больше время начала записи
        if (time() > strtotime($record['date'])){return false;}

        // если отсутствует номер телефона клиента
        if(empty($record['client']['phone'])){return false;}

        return true;
    }

    public static function CreateRecordEvent($record, $nameArchitecture){
        // добавляем запись в таблицу БД records
        $newRecordHasBeenAdded = EventAssistant::addRecord($record);
        // проверяем на ограничения. И проверяем, что ранее такой записи не было и она была добавлена только сейчас впервые
        if ($newRecordHasBeenAdded && self::stopEvent($record)){
            // проверяем запись, если было 6 наращиваний подряд, то добавляем комментарий и сообщаем администратору
            YclientsCore::checkPutRecordAddBotoxCommentAndSendMessageToAdmin($record);
            // в заивисимости от сотрудника и способа создания записи генерируем разные сообщения
            if(in_array(YclientsCore::getStaffId($record), Config::$staffListWaitIds)){
                if ($record['online']){
                    $message = TheLashesMessage::getMessageCreateRecordToListWait($record);
                } else {
                    $message = TheLashesMessage::getMessageCreateRecordToListWaitAdminOffline($record);
                }
                $smsMessage = 'Запись в Листе ожидания! Подробнее: https://thelashes.ru/?_='.theLashesModel::insertSmsId('2-'.$record['id']);
                $EventForCheckWhatsApp = 'ListWaitAutoNotification';
                $current = 'AutoNotification';
            } else {
                $smsMessage = 'Подтвердите запись по ссылке: https://thelashes.ru/?_='.theLashesModel::insertSmsId('1-'.$record['id']);
                $current = null;
                $message = TheLashesMessage::getMessageCreateRecordToMasterVer2($record);
                $EventForCheckWhatsApp = 'MasterAutoNotification';
            }
            // если запись добавлена на сегодня или на завтра, то автоматически меняем статус записи на "клиент подтвердил"
            self::changeStatusRecord($record);
            // insert Or Update to DB table bot
            self::insertOrUpdateUserBotDB($record, $current, $EventForCheckWhatsApp);
            // отправляем сообщение клиенту
            $clientPhone = YclientsCore::getClientPhone($record);
            if (!Config::$smsActivated || YclientsCore::checkAccessUserSendMessageToWhatsApp($clientPhone)){
                EventAssistant::sendClientToWhatsApp($clientPhone, $message);
            } else {
                EventAssistant::sendClientToSMS($clientPhone, $smsMessage, $record['company_id']);
            }
        }
        // проверяем и сообщаем о задержке вебхуков от Yclients
        YclientsCore::checkLagYclientsWebhook("$nameArchitecture - Создание записи", $record);
    }

}
