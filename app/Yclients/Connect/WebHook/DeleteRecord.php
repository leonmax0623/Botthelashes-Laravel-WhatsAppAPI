<?php
namespace App\Yclients\Connect\WebHook;


use App\Core\Config;
use App\Core\Core;
use App\Core\Test;
use App\Core\TheLashesMessage;
use App\Core\theLashesModel;
use App\Models\Bot;
use App\Models\Records;
use App\Yclients\Assistant\Control;
use App\Yclients\Assistant\Lags;
use App\Yclients\Notification\SMS;
use App\Yclients\Notification\WhatsApp;
use App\Yclients\Record\Record;

class DeleteRecord
{

    protected static function insertOrUpdateUserBotDB($record){

        $clientPhone = Record::getClientPhone($record);
        $user = Bot::getBotWherePhone($clientPhone);
        if ($user === null) {
            $insert = [];
            $insert['vendor'] = 'whatsapp';
            $insert['userId'] = $clientPhone . '@c.us';
            $insert['phone'] = $clientPhone;
            $insert['current'] = 'DeletedRecord';
            $insert['LastDeletedRecord'] = json_encode($record, true);
            $insert['updateTime'] = time();
            $update['updateDateTime'] = date("Y-m-d H:i:s");
            Bot::insertBot($insert);
        } else {
            $update = [];
            $update['current'] = 'DeletedRecord';
            $update['LastDeletedRecord'] = json_encode($record, true);
            $update['updateTime'] = time();
            $update['updateDateTime'] = date("Y-m-d H:i:s");
            Bot::updateBotWherePhone($clientPhone, $update);
        }
    }

    public static function stopEvent($record){
        // если запись создана на одного из сотрудников Предоплаты
        if (in_array(Record::getStaffId($record), Config::$staffListPrepaymentsIds)){return false;}

        // если запись создана на одного из сотрудников Листа Ожидания
        if(in_array(Record::getStaffId($record), Config::$staffListWaitIds)){return false;}

        // если запись создана на одного из сотрудников Администраторов
        if(in_array(Record::getStaffId($record), Config::$staffListAdminIds)){return false;}

        // если запись создана "в прошлом", когда текущее время больше время начала записи
        if (time() > strtotime($record['date'])){return false;}

        // если отсутствует номер телефона клиента
        if(empty($record['client']['phone'])){return false;}

        // если пришел вебхук с запозданием, когда по запросам мы уже выяснили, что запись удалена, и пришло удаление
        if(Records::getDeletedRecord($record['id']) !== null){return false;}

        return true;
    }


    public static function DeleteRecord($record, $nameArchitecture){
        // проверяем на ограничения И проверяем, что удалено больше 1 строки, если удалено 0 строк - это значит, что событие уже произошло ранее
        if (self::stopEvent($record)){
            // удаляем запись в таблице БД records
            $deleteCountRecords = Records::deleteRecord($record);
            if ($deleteCountRecords > 0 ){
                // insert Or Update to DB table bot // статус пользователя НЕ меняем, если визит удален в прошлом, поэтому обновляем после self::stopEvent($record);
                self::insertOrUpdateUserBotDB($record);
                $clientPhone = Record::getClientPhone($record);
                // узнаем, а не удалена ли эта запись нами же за не неуплату
                $deletedRecord = Records::getDeletedRecord($record['id']);

//                if (true || in_array($clientPhone, Test::$betaClients)){
                    // NEW
                    // Если пришел вебхук о записи, которая УДАЛЕНА ЗА НЕУПЛАТУ и УДАЛЕНА ВПЕРВЫЕ (НЕ ВОССТАНОВЛЕНА)
                    if ($deletedRecord->deletedForDebts && !$deletedRecord->restoreAfterDebts){
                        $message = TheLashesMessage::getMessageDeleteForDebts($record);
                        $smsMessage = 'Удалена запись. Восстановить: https://thelashes.ru/?_='.theLashesModel::insertSmsId('7-'.$record['id']);
                        Bot::updateBotWherePhone($clientPhone, ['current' => 'DeletedRecordForDebts']);
                    } else {
                        $message = TheLashesMessage::getMessageDelete($record);
                        $smsMessage = 'Удалена запись. Восстановить: https://thelashes.ru/?_='.theLashesModel::insertSmsId('6-'.$record['id']);
                    }
                    // NEW END
//                } else {
//                    $message = TheLashesMessage::getMessageDelete($record);
//                    $smsMessage = 'Удалена запись. Восстановить: https://thelashes.ru/?_='.theLashesModel::insertSmsId('6-'.$record['id']);
//                }

                // отправляем сообщение клиенту
                if (!Config::$smsActivated || Control::checkAccessUserSendMessageToWhatsApp($clientPhone)){
                    WhatsApp::sendMessage($clientPhone, $message);
                } else {
                    SMS::sendSMS($clientPhone, $smsMessage, $record['company_id']);
                }
            }
        }
        // проверяем и сообщаем о задержке вебхуков от Yclients
        Lags::checkLag($record, 'Delete Record', $nameArchitecture);
    }

}