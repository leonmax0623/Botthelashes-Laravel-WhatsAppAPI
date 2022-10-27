<?php


namespace App\Core;


use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class TheLashesMessage
{

    public static function getMessageDeleteForDebts($record){
        $date = 0;
        if (isset($record['date'])) {$date = $record['date'];}
//        $services = YclientsCore::getServicesStringFromRecord($record);
        $services = YclientsCore::getServicesString($record);

        $master = YclientsCore::getStaffNameFromRecord($record);
        $date = date_format(date_create($date), 'd.m.Y в H:i');
        $message = "Отменена запись.
Услуга: $services.
Мастер: $master.
Время: $date.

🔢```Если вы хотите подобрать другой вариант или восстановить запись пришлите цифру нужного пункта меню```👇
*1* - ➕ Восстановить запись!
*3* - 📲 Свяжитесь со мной, хочу уточнить детали

```Если все верно, то отвечать на это сообщение не нужно.```
";
        return $message;
    }

    public static function getMessageDelete($record){
        $date = 0;
        if (isset($record['date'])) {$date = $record['date'];}
//        $services = YclientsCore::getServicesStringFromRecord($record);
        $services = YclientsCore::getServicesString($record);

        $master = YclientsCore::getStaffNameFromRecord($record);
        $date = date_format(date_create($date), 'd.m.Y в H:i');
        $message = "Отменена запись.
Услуга: $services.
Мастер: $master.
Время: $date.

🔢```Если вы хотите подобрать другой вариант отправьте цифру 3 в ответном сообщении ```👇

*3* - 📲 Свяжитесь со мной, перенесу запись на другое время/дату

```Если все верно, то отвечать на это сообщение не нужно.```
";
        return $message;
    }

    public static function getMessageReminder($record, $companies){
        $clientName = YclientsCore::getClientNameFromRecord($record, 'Здравствуйте!', ',');
        $date = YclientsCore::getDateTimeStartVisitFromRecord($record);
        $services = YclientsCore::getServicesStringFromRecord($record);
        $specialization = YclientsCore::getStaffSpecializationFromRecord($record);
        $master = YclientsCore::getStaffNameFromRecord($record);
        $address = YclientsCore::getAddressCompanyFromRecord($record, $companies);
        $navigator = self::getNavigator($record['company_id']);
        $getInstagramOfferMessage = TheLashesMessage::getInstagramOfferMessage($record);
        $message = "$clientName

Напоминаем о записи $date, $services к $specialization $master.

Адрес студии: $address

Как пройти? Схема/видео: $navigator $getInstagramOfferMessage

```Если все верно, то отвечать на это сообщение не нужно.```
";
        return $message;
    }

    public static function getMessageAdminDeleteRecord($record){
        $companies = YclientsCore::getCompaniesInfo(Config::getYclientsApi(), Config::getConfig());
        $datetime = $record['date'];
        $date = YclientsCore::getDateTimeStartVisitFromRecord($record);
        $clientPhone = YclientsCore::getClientPhone($record);
        $master = YclientsCore::getStaffNameFromRecord($record);
        $studioName = YclientsCore::getStudioNameFromRecord($record, $companies);
        $services = YclientsCore::getServicesString($record);
        $clientName = YclientsCore::getClientNameFromRecord($record);
        $messageWhatsApp = "$clientName, добрый день!

Увидели что не успели внести предоплату записи:

$date
Студия: $studioName
Мастер: $master
Процедуры: $services

В связи с чем запись была удалена. Нужно ли прислать новую ссылку на формы оплаты?";

        $adminMessage = "⚠ Клиент не внес предоплату за 24 часа. Запись удалена.
Студия: $studioName
Дата время: $datetime
Имя: $clientName
Мастер: $master
Процедура: $services
Телефон клиента: +$clientPhone

https://api.whatsapp.com/send?phone=$clientPhone&text=" . rawurlencode($messageWhatsApp);
        return $adminMessage;
    }

    public static function getMessageAdminNotWhatsApp($record, $companies, $adminHelp, $messageWhatsApp = ""){
        $datetime = $record['date'];
        $master = '';
        $studioName = YclientsCore::getStudioNameFromRecord($record, $companies);
        $services = YclientsCore::getServicesString($record);
        $clientName = YclientsCore::getClientNameFromRecord($record);
        if (isset($record['staff']['name'])) {$master = $record['staff']['name'];}
        $clientPhone = '';
        if (isset($record['client']['phone'])) {$clientPhone = $record['client']['phone'];}
        $clientPhone = YclientsCore::getOnlyNumberString($clientPhone);
        $adminMessage = "⚠Отсутствует WhatsApp на номере:

Студия: $studioName
Дата время: $datetime
Имя: $clientName
Мастер: $master
Процедура: $services
Телефон клиента: +$clientPhone

Событие: $adminHelp

Уведомить клиента по альтернативным каналам связи!

Оригинал сообщения: $messageWhatsApp

https://api.whatsapp.com/send?phone=$clientPhone&text=" . rawurlencode($messageWhatsApp);
        return $adminMessage;
    }

    public static function getMessageBotoxAdmin($record){
        $datetime = $record['date'];
        $studioName = YclientsCore::getStudioNameFromRecord($record, YclientsCore::getCompaniesInfo(Config::getYclientsApi(), Config::getConfig()));
        $services = YclientsCore::getServicesString($record);
        $clientName = YclientsCore::getClientNameFromRecord($record);
        $clientPhone = YclientsCore::getClientPhone($record);
        $adminMessage = "Было 5-ть наращиваний подряд. Предложить уход/перерыв

Студия: $studioName
Дата время записи: $datetime
Имя клиента: $clientName
Услуги: $services
Телефон: +".$clientPhone."";
        return $adminMessage;
    }

    public static function getMessageRecordUpdate($record, $recordLast, $companies){
        $clientName = YclientsCore::getClientNameFromRecord($record, 'Здравствуйте!', ',');
        $date = YclientsCore::getDateTimeStartVisitFromRecord($record);
        $services = YclientsCore::getServicesStringFromRecord($record);
        $specialization = YclientsCore::getStaffSpecializationFromRecord($record);
        $master = YclientsCore::getStaffNameFromRecord($record);
        $address = YclientsCore::getAddressCompanyFromRecord($record, $companies);
        $navigator = self::getNavigator($record['company_id']);
        $getInstagramOfferMessage = TheLashesMessage::getInstagramOfferMessage($record);
        if ($recordLast == null){
            $calendar = TheLashesMessage::addCalendar($record);
        } else {
            $calendar = TheLashesMessage::addUpdateCalendar($record, $recordLast);
        }
        $message = "$clientName

Ваша запись изменена.

$date, Вы записаны $services к $specialization $master.

Адрес студии: $address

Как пройти? Схема/видео: $navigator $getInstagramOfferMessage

$calendar

```Если все верно, то отвечать на это сообщение не нужно.```
";
        return $message;
    }

    public static function getMessageAutoConfirmationToMaster($record, $companies, $offerStatus = 0){
        $clientName = YclientsCore::getClientNameFromRecord($record, 'Добрый день!', ', добрый день!');
        $time = YclientsCore::getDateTimeStartVisitFromRecord($record, 'H:i');
        $services = YclientsCore::getServicesStringFromRecord($record);
        $specialization = YclientsCore::getStaffSpecializationFromRecord($record);
        $master = YclientsCore::getStaffNameFromRecord($record);
        $address = YclientsCore::getAddressCompanyFromRecord($record, $companies);
        $navigator = self::getNavigator($record['company_id']);

        $calendar = TheLashesMessage::addCalendar($record);
        if (!$offerStatus){
            $offer = 'Принимаю оферту: https://thelashes.ru/oferta/';
        } else {
            $offer = '';
        }
        $message = "$clientName

Завтра в $time Вы записаны $services к $specialization $master.

Адрес студии: $address

Как пройти? Схема/видео: $navigator

$calendar

🔢```Чтобы подтвердить или отменить запись пришлите цифру нужного  пункта меню```👇
*1* - ✔️  Запись подтверждаю, завтра буду.
$offer
*2* - 🚫 Нет, отмените мою запись
*3* - 📲 Свяжитесь со мной, перенесу запись на другое время/дату
";
        return $message;
    }

    public static function getCalendarLinkId($record){
        $timeStartVisit = strtotime($record['datetime']);
        $timeEndVisit = strtotime($record['datetime']) + $record['seance_length'];
        $specialization = YclientsCore::getStaffSpecializationFromRecord($record);
        $master = YclientsCore::getStaffNameFromRecord($record);
        $services = YclientsCore::getServicesStringFromRecord($record);

        $AllServicesArray = Cache::get("AllServicesArray");
        $AllServicesCategoriesArray = Cache::get("AllServicesCategoriesArray");
        $companies = Cache::get("companies");

        $title = '';
        foreach($record['services'] as $serv){
            $servId = $serv['id'];
            if (!empty($AllServicesArray[$servId]) && !empty($AllServicesCategoriesArray[$AllServicesArray[$servId]])){
                $servCatId = $AllServicesArray[$servId];
                $title .= mb_strtoupper($AllServicesCategoriesArray[$servCatId] . "/".$serv['title']." ");
            } else {
                $title .= mb_strtoupper("/".$serv['title']." ");
            }

        }
        $title .= "в ".$companies[$record['company_id']]['title'];

//        НАРАЩИВАНИЕ РЕСНИЦ 1D/ЛАМИНИРОВАНИЕ РЕСНИЦ/LASH BOTOX/КОРРЕКЦИЯ БРОВЕЙ в The Lashes Пресненский Вал
        $text = urlencode("$title");
//        $text = urlencode("Визит к $specialization $master");
        $dates = date("Ymd\\THi00", $timeStartVisit)."/".date("Ymd\\THi00", $timeEndVisit);
        $details = urlencode("Визит к $specialization $master.
Вы записаны $services");
        $location = urlencode(TheLashesMessage::getCalendarAddress($record['company_id']));
        $link = "http://www.google.com/calendar/event?action=TEMPLATE&text=$text&dates=$dates&details=$details&location=$location";
//        Core::sendDev($link);
        $id = DB::table('links')->insertGetId(['link' => $link]);
        return $id;
    }

    public static function addUpdateCalendar($record, $recordLast){
        $oldStartVisit = date("d.m.Y H:i",strtotime($recordLast['datetime']));
        $oldMaster = YclientsCore::getStaffNameFromRecord($recordLast);
        $oldSpecialization = YclientsCore::getStaffSpecializationFromRecord($recordLast);
        $id = self::getCalendarLinkId($record);
        $calendar = "Добавить визит в Google Календарь: http://thelashes.ru/?g=$id

Удалите старый визит к $oldSpecialization $oldMaster на $oldStartVisit, если добавляли ранее в Google Календарь";
        return $calendar;
    }

    public static function addCalendar($record){
        $id = self::getCalendarLinkId($record);
        $calendar = "Добавить визит в Google Календарь: http://thelashes.ru/?g=$id";
        return $calendar;
    }


    public static function getMessagePaymentFirst($record, $paymentTimeLeftHours){
        $clientName = YclientsCore::getClientNameFromRecord($record, 'Здравствуйте!', ',');
//        $date = YclientsCore::getDateTimeStartVisitFromRecord($record);
//        $services = YclientsCore::getServicesStringFromRecord($record);
//        $specialization = YclientsCore::getStaffSpecializationFromRecord($record);
//        $master = YclientsCore::getStaffNameFromRecord($record);
//        $address = YclientsCore::getAddressCompanyFromRecord($record, YclientsCore::getCompaniesInfo(Config::getYclientsApi(), Config::getConfig()));
//        $navigator = self::getNavigator($record['company_id']);
//        $getInstagramOfferMessage = TheLashesMessage::getInstagramOfferMessage($record);
//        $calendar = TheLashesMessage::addCalendar($record);
        $link = YclientsCore::getPaymentLink($record);
        $rub = '₽';
        $sum = Payment::getPrepaymentSumFromRecord($record);
            $message = "$clientName

Ссылка для внесения предоплаты в размере $sum$rub действует еще только $paymentTimeLeftHours часов: $link

```Записи без внесенной предоплаты, деактивируются автоматически через этот отрезок времени.```
";
        return $message;
    }

    public static function getMessagePaymentOk($record, $total){

        $clientName = YclientsCore::getClientNameFromRecord($record, 'Здравствуйте!', ',');
        $date = YclientsCore::getDateTimeStartVisitFromRecord($record);
        $services = YclientsCore::getServicesStringFromRecord($record);
        $specialization = YclientsCore::getStaffSpecializationFromRecord($record);
        $master = YclientsCore::getStaffNameFromRecord($record);
        $address = YclientsCore::getAddressCompanyFromRecord($record, YclientsCore::getCompaniesInfo(Config::getYclientsApi(), Config::getConfig()));
        $navigator = self::getNavigator($record['company_id']);
        $getInstagramOfferMessage = TheLashesMessage::getInstagramOfferMessage($record);
        $calendar = TheLashesMessage::addCalendar($record);
        $rub = '₽';
//        $sum = Payment::getPrepaymentSumFromRecord($record);

//        $textChange = rawurlencode("Добрый день! Хочу перенести свою запись c сохранением предоплаты. На xx число в xx:xx к мастеру Xxxx");
//        $textChange = "https://api.whatsapp.com/send?phone=74957647602&text=$textChange";
//        $textCancel = rawurlencode("Добрый день! Хочу отменить свою запись и вернуть предоплату. Что необходимо сделать?");
//        $textCancel = "https://api.whatsapp.com/send?phone=74957647602&text=$textCancel";

        $message = "$clientName спасибо за предоплату $total$rub!

$date, Вы записаны на $services к $specialization $master.

Адрес студии: $address

Как пройти? Схема/видео: $navigator $getInstagramOfferMessage

$calendar

Если у Вас изменились планы, сообщите нам, не позднее чем за 24 часа до начала визита, для возврата внесенной суммы:
*Изменить время/дату визита* http://thelashes.ru/?g=7
*Отменить запись* http://thelashes.ru/?g=8";

        return $message;
    }

    public static function getMessageCreateRecordToMasterVer2($record, $paymentStatus = ''){
        $clientName = YclientsCore::getClientNameFromRecord($record, 'Здравствуйте!', ',');
        $date = YclientsCore::getDateTimeStartVisitFromRecord($record);
        $services = YclientsCore::getServicesStringFromRecord($record);
        $specialization = YclientsCore::getStaffSpecializationFromRecord($record);
        $master = YclientsCore::getStaffNameFromRecord($record);
        $address = YclientsCore::getAddressCompanyFromRecord($record, YclientsCore::getCompaniesInfo(Config::getYclientsApi(), Config::getConfig()));
        $navigator = self::getNavigator($record['company_id']);
        $getInstagramOfferMessage = TheLashesMessage::getInstagramOfferMessage($record);
        $calendar = TheLashesMessage::addCalendar($record);

        $sum = Payment::getPrepaymentSumFromRecord($record);
        $rub = '₽';
        if ( Payment::checkActivatePaymentMode($record) && $paymentStatus != 'ok'){
            $link = YclientsCore::getPaymentLink($record);
            $message = "$clientName

Для подтверждения записи на $date необходимо внести предоплату в размере $sum$rub по ссылке: $link

```Возврат предоплаты осуществляется при уведомлении об отмене записи, не позднее чем за 24 часа до начала визита.```
";
        } else {
            $message = "$clientName

$date, Вы записаны $services к $specialization $master.

Адрес студии: $address

Как пройти? Схема/видео: $navigator $getInstagramOfferMessage

$calendar

```Если все верно, то отвечать на это сообщение не нужно.```
";
        }



        return $message;
    }

    public static function getMessageCreateRecordToMaster($record, $companies, $paymentStatus = ''){
        $clientName = YclientsCore::getClientNameFromRecord($record, 'Здравствуйте!', ',');
        $date = YclientsCore::getDateTimeStartVisitFromRecord($record);
        $services = YclientsCore::getServicesStringFromRecord($record);
        $specialization = YclientsCore::getStaffSpecializationFromRecord($record);
        $master = YclientsCore::getStaffNameFromRecord($record);
        $address = YclientsCore::getAddressCompanyFromRecord($record, YclientsCore::getCompaniesInfo(Config::getYclientsApi(), Config::getConfig()));
        $navigator = self::getNavigator($record['company_id']);
        $getInstagramOfferMessage = TheLashesMessage::getInstagramOfferMessage($record);
        $calendar = TheLashesMessage::addCalendar($record);

        $sum = Payment::getPrepaymentSumFromRecord($record);
        $rub = '₽';
        if ( Payment::checkActivatePaymentMode($record) && $paymentStatus != 'ok'){
            $link = YclientsCore::getPaymentLink($record);
            $message = "$clientName

Для подтверждения записи на $date необходимо внести предоплату в размере $sum$rub по ссылке: $link

```Возврат предоплаты осуществляется при уведомлении об отмене записи, не позднее чем за 24 часа до начала визита.```
";
        } else {
            $message = "$clientName

$date, Вы записаны $services к $specialization $master.

Адрес студии: $address

Как пройти? Схема/видео: $navigator $getInstagramOfferMessage

$calendar

```Если все верно, то отвечать на это сообщение не нужно.```
";
        }



        return $message;
    }

    public static function getMessageCreateRecordToListWaitAdminOffline($record){
        $clientName = YclientsCore::getClientNameFromRecord($record, 'Здравствуйте!', ',');
        $message = "$clientName

Внесли заявку в Лист ожидания. Если освободиться подходящее время мастеру нужной категории, то обязательно сообщим.

🔢```Чтобы подтвердить или отменить запись пришлите цифру нужного пункта меню```👇
1 - ✔  Оставить заявку в Листе ожидания
2 - 🚫 Нет, отмените эту запись. Запишусь к конкретному мастеру самостоятельно
";
        return $message;
    }

    public static function getMessageCreateRecordToListWait($record){
        $clientName = YclientsCore::getClientNameFromRecord($record, 'Здравствуйте!', ',');
        $message = "$clientName

К сожалению, заявка попала в Лист ожидания, поскольку в это время ко всем мастерам полная запись.
Если освободиться подходящее время мастеру нужной категории, то обязательно сообщим.

Чтобы увидеть свободное время без Листа ожидания при записи нужно выбрать конкретного мастера, без опции \"пропустить выбор сотрудника\"

🔢```Чтобы подтвердить или отменить запись пришлите цифру нужного пункта меню```👇
*1* - ✔️  Оставить заявку в Листе ожидания
*2* - 🚫 Нет, отмените эту запись. Запишусь к конкретному мастеру самостоятельно.
*3* - 📲 Свяжитесь со мной, перенесу запись на другое время/дату
";
        return $message;
    }


    public static function getInstagramOfferMessage($record){

        if (stristr(mb_strtolower(YclientsCore::getClientNameFromRecord($record)), Config::MODEL)){
            // для моделей
            return '';
        }

        if (YclientsCore::checkInstagramFollow($record)){
            return '';
        } else {
            return '

🎁 *200₽ в подарок* за подписку: https://www.instagram.com/make_the_lashes/ (покажите свой аккаунт администратору при оплате)';
        }
    }

    public static function getCalendarAddress($companyId){
        switch($companyId){
            case '138368': $address = 'The Lashes - студия по наращиванию ресниц, Велозаводская ул., 6А, Москва, Россия, 115280'; break;
            case '41332': $address = 'The Lashes - студия по наращиванию ресниц, ул. Пресненский Вал, 38 строение 1, Москва, Россия, 123557'; break;
            case '225366': $address = 'The Lashes - студия по наращиванию ресниц, ул. Столетова, 9, Москва, Россия, 119192';break;
            default: $address = ''; break;
        }
        return $address;
    }

    public static function getNavigator($companyId){
        $navigator = '';
        switch($companyId){
            case '138368':
                $navigator = 'http://bit.ly/YandexNavi';
                $navigator = 'https://thelashes.ru/filials/velozavodskaya/#branch_video';
                break;
            case '41332':
                $navigator = 'http://bit.ly/Yandex_Navi';
                $navigator = 'https://thelashes.ru/filials/presnenskiyval/#branch_video';
                $navigator = 'http://joxi.ru/Vrw6Lenc8Ljd1A';
                $navigator = 'https://thelashes.ru/filials/presnenskiyval/#branch_video';
                break;
            case '225366':
                $navigator = 'http://bit.ly/YandexNavi3';
                $navigator = 'https://thelashes.ru/filials/stoletova/#branch_video';
                break;
        }
        return $navigator;
    }

    public static function messageDefault($command){
        $message = '```Добрый день!🤖

Не думаю, что я понял сообщение правильно, поэтому уже переслал его администратору студии, который точно сможет решить любой вопрос.```
';
        $message = $message ."
```⚡Если ответ нужен немедленно, просто нажмите на ссылку ниже и ваш вопрос попадет в чат с администратором, минуя бота:⬇⬇⬇```
https://api.whatsapp.com/send?phone=74957647602&text=".urlencode($command);
        return $message;
    }


    public static function sendUnrecognizedMessageToAdmin($command, $chatId, $senderName){

        // ВОТ ВМЕСТО ЭТОГО БУДЕТ КРОН !


        $ph = YclientsCore::getOnlyNumberString($chatId);
        $adminMessage = "
Нераспознанное сообщение: $command
Номер телефона: +$ph
Имя в WhatsApp: $senderName
Ссылка на диалог: https://api.whatsapp.com/send?phone=".$ph."&text=".urlencode("Добрый день, $senderName!")."
";
        YclientsCore::sendAdmin($adminMessage, '74957647602');
    }

}
