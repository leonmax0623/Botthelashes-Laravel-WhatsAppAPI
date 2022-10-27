<?php
namespace App\Yclients\Connect\WebHook;

use App\Core\Config;
use App\Core\TheLashesMessage;
use App\Core\theLashesModel;
use App\Core\YclientsCore;
use App\Models\Bot;
use App\Models\Records;
use App\Yclients\Assistant\Control;
use App\Yclients\Assistant\Lags;
use App\Yclients\Notification\SMS;
use App\Yclients\Notification\WhatsApp;
use App\Yclients\Record\Record;

class CreateRecord
{

    protected static function insertOrUpdateUserBotDB($record, $current, $EventForCheckWhatsApp){
        $clientPhone = Record::getClientPhone($record);
        $clientName = Record::getClientName($record);
        $user = Bot::getBotWherePhone($clientPhone);
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
            Bot::insertBot($insert);
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
            Bot::updateBotWherePhone($clientPhone, $update);
        }
    }

    protected static function changeStatusRecord($record){
        $numberSecondsToday = time() - strtotime('today');
        $numberSecondsTodayLeft = 24 * 60 * 60 + $numberSecondsToday;
        if(
            (time() > strtotime($record['datetime']) - $numberSecondsTodayLeft) // если запись на сегодня или завтра
            &&
            !in_array(Record::getStaffId($record), Config::$staffListWaitIds) // если это не Лист Ожидания
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
        if (in_array(Record::getStaffId($record), Config::$staffListPrepaymentsIds)){return false;}

        // если запись создана на одного из сотрудников Администраторов
        if(in_array(Record::getStaffId($record), Config::$staffListAdminIds)){return false;}

        // если запись создана "в прошлом", когда текущее время больше время начала записи
        if (time() > strtotime($record['date'])){return false;}

        // если отсутствует номер телефона клиента
        if(empty($record['client']['phone'])){return false;}

        // если пришел вебхук с запозданием, когда по запросам мы уже выяснили, что запись удалена, а пришло создание
        if(Records::getDeletedRecord($record['id']) !== null){return false;}

        return true;
    }

    public static function CreateRecord($record, $nameArchitecture){
        // добавляем запись в таблицу БД records
        $newRecordHasBeenAdded = Control::addRecord($record);
        // проверяем на ограничения. И проверяем, что ранее такой записи не было и она была добавлена только сейчас впервые
        if ($newRecordHasBeenAdded && self::stopEvent($record)){
            // проверяем запись, если было 6 наращиваний подряд, то добавляем комментарий и сообщаем администратору
            YclientsCore::checkPutRecordAddBotoxCommentAndSendMessageToAdmin($record);
            // в заивисимости от сотрудника и способа создания записи генерируем разные сообщения
            if(in_array(Record::getStaffId($record), Config::$staffListWaitIds)){
                if ($record['online']){
                    $message = TheLashesMessage::getMessageCreateRecordToListWait($record);
                } else {
                    $message = TheLashesMessage::getMessageCreateRecordToListWaitAdminOffline($record);
                }
                $smsMessage = 'Запись в Листе ожидания! Подробнее: https://thelashes.ru/?_='.theLashesModel::insertSmsId('2-'.$record['id']);
                $EventForCheckWhatsApp = 'ListWaitAutoNotification';
                $current = 'AutoNotification';
            } else {
                $message = TheLashesMessage::getMessageCreateRecordToMasterVer2($record);
                $smsMessage = 'Подтвердите запись по ссылке: https://thelashes.ru/?_='.theLashesModel::insertSmsId('1-'.$record['id']);
                $EventForCheckWhatsApp = 'MasterAutoNotification';
                $current = null;
            }
            // если запись добавлена на сегодня или на завтра, то автоматически меняем статус записи на "клиент подтвердил"
            self::changeStatusRecord($record);
            // insert Or Update to DB table bot
            self::insertOrUpdateUserBotDB($record, $current, $EventForCheckWhatsApp);
            // отправляем сообщение клиенту
            $clientPhone = Record::getClientPhone($record);
            if (!Config::$smsActivated || Control::checkAccessUserSendMessageToWhatsApp($clientPhone)){
                WhatsApp::sendMessage($clientPhone, $message);
            } else {
                SMS::sendSMS($clientPhone, $smsMessage, $record['company_id']);
            }
        }
        // проверяем и сообщаем о задержке вебхуков от yCLIENTS
        Lags::checkLag($record, 'Create Record', $nameArchitecture);
    }

}