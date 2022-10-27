<?php
namespace App\Core\Events;

use App\Core\Config;
use App\Core\TheLashesMessage;
use App\Core\theLashesModel;
use App\Core\YclientsCore;

class YclientsWebhookDeleteRecordEvent
{

    protected static function insertOrUpdateUserBotDB($record){
        $clientPhone = YclientsCore::getClientPhone($record);
        $user = theLashesModel::getUserBotFromDB($clientPhone);
        if ($user === null) {
            $insert = [];
            $insert['vendor'] = 'whatsapp';
            $insert['userId'] = $clientPhone . '@c.us';
            $insert['phone'] = $clientPhone;
            $insert['current'] = 'DeletedRecord';
            $insert['LastDeletedRecord'] = json_encode($record, true);
            $insert['updateTime'] = time();
            theLashesModel::insertToBot($insert);
        } else {
            $update = [];
            $update['current'] = 'DeletedRecord';
            $update['LastDeletedRecord'] = json_encode($record, true);
            $update['updateTime'] = time();
            theLashesModel::updateBot($clientPhone, $update);
        }
    }

    public static function stopEvent($record){
        // если запись создана на одного из сотрудников Предоплаты
        if (in_array(YclientsCore::getStaffId($record), Config::$staffListPrepaymentsIds)){return false;}

        // если запись создана на одного из сотрудников Листа Ожидания
        if(in_array(YclientsCore::getStaffId($record), Config::$staffListWaitIds)){return false;}

        // если запись создана на одного из сотрудников Администраторов
        if(in_array(YclientsCore::getStaffId($record), Config::$staffListAdminIds)){return false;}

        // если запись создана "в прошлом", когда текущее время больше время начала записи
        if (time() > strtotime($record['date'])){return false;}

        // если отсутствует номер телефона клиента
        if(empty($record['client']['phone'])){return false;}

        return true;
    }

    public static function DeleteRecordEvent($record, $nameArchitecture){
        // удаляем запись в таблице БД records
        $deleteCountRecords = YclientsCore::deleteRecord($record);
        // проверяем на ограничения И проверяем, что удалено больше 1 строки, если удалено 0 строк - это значит, что событие уже произошло ранее
        if ($deleteCountRecords > 0 && self::stopEvent($record)){
            // insert Or Update to DB table bot // статус пользователя НЕ меняем, если визит удален в прошлом, поэтому обновляем после self::stopEvent($record);
            self::insertOrUpdateUserBotDB($record);
            // отправляем сообщение клиенту
            $clientPhone = YclientsCore::getClientPhone($record);
            if (!Config::$smsActivated || YclientsCore::checkAccessUserSendMessageToWhatsApp($clientPhone)){
                $message = TheLashesMessage::getMessageDelete($record);
                EventAssistant::sendClientToWhatsApp($clientPhone, $message);
            } else {
                $smsMessage = 'Удалена запись. Восстановить: https://thelashes.ru/?_='.theLashesModel::insertSmsId('6-'.$record['id']);;
                EventAssistant::sendClientToSMS($clientPhone, $smsMessage, $record['company_id']);
            }
        }
        // проверяем и сообщаем о задержке вебхуков от Yclients
        YclientsCore::checkLagYclientsWebhook("$nameArchitecture - Удаление записи", $record);
    }

}