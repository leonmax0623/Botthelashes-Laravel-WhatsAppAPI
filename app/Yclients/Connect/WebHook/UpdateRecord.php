<?php
namespace App\Yclients\Connect\WebHook;

use App\Core\Config;
use App\Core\Core;
use App\Core\Payment;
use App\Core\PaymentModel;
use App\Core\Test;
use App\Core\TheLashesMessage;
use App\Core\theLashesModel;
use App\Core\YclientsCore;
use App\Models\Bot;
use App\Models\Records;
use App\Yclients\Assistant\Control;
use App\Yclients\Assistant\Lags;
use App\Yclients\Cache\CompaniesInfo;
use App\Yclients\Notification\SMS;
use App\Yclients\Notification\WhatsApp;
use App\Yclients\Record\Record;

class UpdateRecord
{

    protected static function UpdateUserBotDB($record){
        $update['current'] = null;
        $update['checkAction'] = 0;
        $update['updateTime'] = time();
        $update['EventForCheckWhatsApp'] = 'UpdateRecord';
        $update['CompanyIdForCheckWhatsApp'] = $record['company_id'];
        $update['RecordIdForCheckWhatsApp'] = $record['id'];
        $update['LastUpdateRecord'] = json_encode($record, true);
        Bot::updateBotWherePhone(Record::getClientPhone($record), $update);
    }

    public static function stopEvent($record){
        // если запись создана на одного из сотрудников Предоплаты
        if (in_array(Record::getStaffId($record), Config::$staffListPrepaymentsIds)){return false;}

        // если запись создана на одного из сотрудников Администраторов
        if(in_array(Record::getStaffId($record), Config::$staffListAdminIds)){return false;}

        // если запись создана на одного из сотрудников Листа Ожидания
        if(in_array(Record::getStaffId($record), Config::$staffListWaitIds)){return false;}

        // Если пользователь с таким номером не найден
        $user = Bot::getBotWherePhone(Record::getClientPhone($record));
        if ($user === null || empty($user->phone)){return false;}

        // если запись создана "в прошлом", когда текущее время больше время начала записи
        if (time() > strtotime($record['date'])){return false;}

        // если статус записи "клиент не пришел"
        if ($record['attendance'] == '-1'){return false;}

        // если отсутствует номер телефона клиента
        if(empty($record['client']['phone'])){return false;}

        // если пришел вебхук с запозданием, когда по запросам мы уже выяснили, что запись удалена, а пришло изменение
        if(Records::getDeletedRecord($record['id']) !== null){return false;}

        return true;
    }

    public static function UpdateRecord($record, $nameArchitecture){
        // проверяем на ограничения.
        $recordUpdate = false;
        if (self::stopEvent($record)){
            // получаем из Базы Данных данные записи, которые были ДО изменения
            $recordLastDB = Records::getRecord($record['id']);
            if ($recordLastDB !== null){
                $recordLast = json_decode($recordLastDB->record, 1);
                $clientPhone = Record::getClientPhone($record);
                $sendUpdateMessage = true;

//                if (in_array($clientPhone, Test::$betaClients)){
//                    if ($recordLastDB->paymentStatus === null){
//                        if ( Payment::checkActivatePaymentMode($record)
////                            && $recordLastDB->paymentStatus != 'ok'
//                        ){
//                            if (!Config::$smsActivated || Control::checkAccessUserSendMessageToWhatsApp($clientPhone)){
//                                $sum = Payment::getPrepaymentSumFromRecord($record);
//                                $rub = '₽';
//                                $clientName = YclientsCore::getClientNameFromRecord($record, 'Здравствуйте!', ',');
//                                $date = YclientsCore::getDateTimeStartVisitFromRecord($record);
//                                $customerId = YclientsCore::getClientId($record);
//                                $orderId = $record['id'];
//                                $email = YclientsCore::getClientEmail($record);
//                                if ($email == ""){$emailText = "";} else {$emailText = "&email=$email";}
//                                $shopId = Payment::getShopId($record);
//                                $link = "https://thelashes.ru/payment/?shop_id=$shopId&customer_id=$customerId&order_id=$orderId&price=$sum$emailText";
//                                PaymentModel::paymentSave($record, $sum);
//                                $message = "$clientName
//
//Для подтверждения записи на $date необходимо внести предоплату в размере $sum$rub по ссылке: $link
//
//```Возврат предоплаты осуществляется при уведомлении об отмене записи, не позднее чем за 24 часа до начала визита.```
//";
//                                WhatsApp::sendMessage($clientPhone, $message);
//                                $sendUpdateMessage = false;
//                            }
//                        }
//                    } else {

//                        Core::sendDev('была предоплата');
//                        if (!Payment::checkActivatePaymentMode($record) ){
////                            Core::sendDev('а теперьпредоплата ОТМЕНЕНА!');
//                            Records::updateRecordCustomFields($record, [
//                                'paymentSum' => null,
//                                'paymentStatus' => null,
//                                'paymentDateTime' => null,
//                                'paymentTime' => null
//                            ]);
//                        }

//                    }
//                }

                // если изменено дата/время, мастер или перечень услуг, то уведомляем клиента SMS или WhatsApp
                if(
                    $sendUpdateMessage &&
                    $record['date'] !== $recordLast['date'] ||
                    Record::getStaffId($record) !== Record::getStaffId($recordLast) ||
                    Record::getRecordServicesIds($record) !== Record::getRecordServicesIds($recordLast)
                ){
                    $recordUpdate = true;
                    if (!Config::$smsActivated || Control::checkAccessUserSendMessageToWhatsApp($clientPhone)){
                        $message = TheLashesMessage::getMessageRecordUpdate($record, $recordLast, CompaniesInfo::get());
                        WhatsApp::sendMessage($clientPhone, $message);
                    } else {
                        $smsMessage = 'Ваша запись изменена. Детали: https://thelashes.ru/?_='.theLashesModel::insertSmsId('4-'.$record['id']);
                        SMS::sendSMS($clientPhone, $smsMessage, $record['company_id']);
                    }
                    // в случае, если запись изменена и об этом уведомлен клиент - меняем статус подписчика бота
                    self::UpdateUserBotDB($record);
                }
                // если это пятое наращивание, то меняем цвет записи в Yclients на оранжевый
                YclientsCore::checkPutRecordAddOrangeColor($record);
                // проверяем и сообщаем о задержке вебхуков от Yclients
                Lags::checkLag($record, 'Update Record', $nameArchitecture);
            } else {
                // если записи действительно нет в БД, а нам пришел вебхук об ее изменении, то добавляем актуальную запись в БД
                Control::addRecord($record);
            }
        }
        // изменяем запись в таблице БД records. Обновляем в конце, чтобы изменения не влияли на работу
        Records::updateRecord($record);
        return $recordUpdate;
    }

}