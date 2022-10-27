<?php
namespace App\Yclients\Modules;

use App\Core\Config;
use App\Core\Core;
use App\Core\YclientsCore;
use App\Models\Bot;
use App\Models\Records;
use App\Yclients\API\Request;
use App\Yclients\Notification\Notification;
use App\Yclients\Record\Record;
use Illuminate\Support\Facades\Cache;

class DeletedRecordForDebts
{

    public static function getLabelIdPrepayment($companyId){
        $labelId = 2016616;
        switch($companyId){
            case '138368': $labelId = 2016624; break; // автозаводская
            case '41332': $labelId = 2016616; break; // пресня
            case '225366': $labelId = 2016632; break; // раменки
        }
        return $labelId;
    }

    public static function addRecordToYclients($record, $clientPhone){
        $companyId = $record['company_id'];
        $userToken = Config::getConfig()[$companyId];
        $parameters = [
            'staff_id' => $record['staff_id'],
            'services' => $record['services'],
            'client' => $record['client'],
            'datetime' => $record['datetime'],
            'seance_length' => $record['seance_length'],
            'save_if_busy' => false,
            'send_sms' => false,
            'comment' => "Запись восстановлена после удаления из-за отсутствия оплаты предоплаты",
            'record_labels' => [
                    'id' => self::getLabelIdPrepayment($companyId),
                    'title' => 'Нужна предоплата визита',
                    'color' => 'dfd4f2',
                    'icon' => 'key',
                    'font_color' => '000000',
            ],
        ];
        $result = Request::addRecord($companyId, $userToken, $parameters);
        if (!empty($result['errors'])){
            // Если есть ошибка
            $messageError = $result['errors']['message'];
            $message = "$messageError
            
Администратор свяжется с вами в ближайшее время.
        
```Если все верно, то отвечать на это сообщение не нужно.```";
            self::adminErrorAfterRestoreRecord($clientPhone, $messageError);
        } else {
            $recordId = $result['id'];
            Cache::put("restoreAfterDebts$recordId", 1, 1440);
            // Если все ок и запись УСПЕШНО добавлена обратно
            // прекращаем работу скрипта. Клиенту придет вебхук о создании новой записи.
            die();
        }
        return $message;
    }

    public static function messageRestoreRecord($clientPhone){
        // Обнуляем current
        Bot::updateBotWherePhone($clientPhone, ['current' => null]);
        $user = Bot::getBotWherePhone($clientPhone);
        $record = json_decode($user->LastDeletedRecord, 1);
        // пробуем добавить новую запись на то же время
        $message = self::addRecordToYclients($record, $clientPhone);
        return $message;
    }

    public static function messageCallMeAfterDeletedRecordForDebts($clientPhone){
        // Обнуляем current
        Bot::updateBotWherePhone($clientPhone, ['current' => null]);

        // Сообщение админу
        self::adminCallMeAfterDeletedRecordForDebts($clientPhone);

        // Сообщение клиенту
        $message = "Администратор свяжется с вами в ближайшее время.
        
```Если все верно, то отвечать на это сообщение не нужно.```";

        return $message;
    }

    public static function adminErrorAfterRestoreRecord($clientPhone, $messageError){
        $user = Bot::getBotWherePhone($clientPhone);
        $record = json_decode($user->LastDeletedRecord, 1);
        $clientName = Record::getClientName($record);
        $staffName = Record::getStaffName($record);
        $date = YclientsCore::getDateTimeStartVisitFromRecord($record);
        $services = YclientsCore::getServicesStringFromRecord($record);
        $studioName = YclientsCore::getStudioNameFromRecord($record, YclientsCore::getCompaniesInfo(Config::getYclientsApi(), Config::getConfig()));

        $adminMessage = "⚠ Клиент не смог восстановить запись + не внес предоплату, 
        
Свяжись и подбери удобные альтернативы $date

Имя: $clientName      
Номер телефона: +$clientPhone 
Студия: $studioName
Дата/время визита: $date
Мастер: $staffName
Услуги: $services

Ссылка на диалог: https://api.whatsapp.com/send?phone=".$clientPhone."&text=".urlencode("Добрый день, $clientName!")."
";
        YclientsCore::sendAdmin($adminMessage, Config::$ContactCenterPhone);
    }

    public static function adminCallMeAfterDeletedRecordForDebts($clientPhone){
        $user = Bot::getBotWherePhone($clientPhone);
        $record = json_decode($user->LastDeletedRecord, 1);
        $clientName = Record::getClientName($record);
        $staffName = Record::getStaffName($record);
        $date = YclientsCore::getDateTimeStartVisitFromRecord($record);
        $services = YclientsCore::getServicesStringFromRecord($record);
        $studioName = YclientsCore::getStudioNameFromRecord($record, YclientsCore::getCompaniesInfo(Config::getYclientsApi(), Config::getConfig()));

        $adminMessage = "⚠ Клиент не оплатил запись в течении 24 часов и просит, чтобы с ним связались.
Имя: $clientName      
Номер телефона: +$clientPhone 
Студия: $studioName
Дата/время визита: $date
Мастер: $staffName
Услуги: $services

Ссылка на диалог: https://api.whatsapp.com/send?phone=".$clientPhone."&text=".urlencode("Добрый день, $clientName!")."
";
        YclientsCore::sendAdmin($adminMessage, Config::$ContactCenterPhone);
    }


}