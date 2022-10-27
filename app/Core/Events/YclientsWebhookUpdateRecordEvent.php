<?php
namespace App\Core\Events;

use App\Core\Config;
use App\Core\Core;
use App\Core\TheLashesMessage;
use App\Core\theLashesModel;
use App\Core\YclientsCore;

class YclientsWebhookUpdateRecordEvent
{

    protected static function UpdateUserBotDB($record){
        $update['current'] = null;
        $update['checkAction'] = 0;
        $update['updateTime'] = time();
        $update['EventForCheckWhatsApp'] = 'UpdateRecord';
        $update['CompanyIdForCheckWhatsApp'] = $record['company_id'];
        $update['RecordIdForCheckWhatsApp'] = $record['id'];
        $update['LastUpdateRecord'] = json_encode($record, true);
        theLashesModel::updateBot(YclientsCore::getClientPhone($record), $update);
    }

    public static function stopEvent($record){
        // если запись создана на одного из сотрудников Предоплаты
        if (in_array(YclientsCore::getStaffId($record), Config::$staffListPrepaymentsIds)){return false;}

        // если запись создана на одного из сотрудников Администраторов
        if(in_array(YclientsCore::getStaffId($record), Config::$staffListAdminIds)){return false;}

        // если запись создана на одного из сотрудников Листа Ожидания
        if(in_array(YclientsCore::getStaffId($record), Config::$staffListWaitIds)){return false;}

        // Если пользователь с таким номером не найден
        $user = theLashesModel::getUserBotFromDB(YclientsCore::getClientPhone($record));
        if ($user === null || empty($user->phone)){return false;}

        // если запись создана "в прошлом", когда текущее время больше время начала записи
        if (time() > strtotime($record['date'])){return false;}

        // если статус записи "клиент не пришел"
        if ($record['attendance'] == '-1'){return false;}

        // если отсутствует номер телефона клиента
        if(empty($record['client']['phone'])){return false;}

        return true;
    }

    public static function UpdateRecordEvent($record, $nameArchitecture){
        // проверяем на ограничения.
        $recordUpdate = false;
        if (self::stopEvent($record)){
            // получаем из Базы Данных данные записи, которые были ДО изменения
            $recordLastDB = YclientsCore::getRecordLast($record);
            if ($recordLastDB !== null){
                $recordLast = json_decode($recordLastDB->record, 1);
                // если изменено дата/время, мастер или перечень услуг, то уведомляем клиента SMS или WhatsApp
                if(
                    $record['date'] !== $recordLast['date'] ||
                    YclientsCore::getStaffId($record) !== YclientsCore::getStaffId($recordLast) ||
                    YclientsCore::getRecordServicesIds($record) !== YclientsCore::getRecordServicesIds($recordLast)
                ){
                    $clientPhone = YclientsCore::getClientPhone($record);
                    $recordUpdate = true;
                    if (!Config::$smsActivated || YclientsCore::checkAccessUserSendMessageToWhatsApp($clientPhone)){
                        $message = TheLashesMessage::getMessageRecordUpdate($record, $recordLast, EventAssistant::getCompaniesInfo());
                        EventAssistant::sendClientToWhatsApp($clientPhone, $message);
                    } else {
                        $smsMessage = 'Ваша запись изменена. Детали: https://thelashes.ru/?_='.theLashesModel::insertSmsId('4-'.$record['id']);
                        EventAssistant::sendClientToSMS($clientPhone, $smsMessage, $record['company_id']);
                    }
                    // в случае, если запись изменена и об этом уведомлен клиент - меняем статус подписчика бота
                    self::UpdateUserBotDB($record);
                }
                // если это пятое наращивание, то меняем цвет записи в Yclients на оранжевый
                YclientsCore::checkPutRecordAddOrangeColor($record);
                // проверяем и сообщаем о задержке вебхуков от Yclients
                YclientsCore::checkLagYclientsWebhook("$nameArchitecture - Изменение записи", $record);
            } else {
                // если записи действительно нет в БД, а нам пришел вебхук об ее изменении, то добавляем актуальную запись в БД
                EventAssistant::addRecord($record);
            }
        }
        // изменяем запись в таблице БД records. Обновляем в конце, чтобы изменения не влияли на работу
        YclientsCore::updateRecord($record);
        return $recordUpdate;
    }

}