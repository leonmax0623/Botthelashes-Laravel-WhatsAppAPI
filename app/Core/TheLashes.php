<?php
namespace App\Core;

class TheLashes
{

    public static function getMessageAfterSms($YclientsApi, $config, $user, $staffListWaitIds, $companies){
        $message = 'default';
        $recordNow = self::getLastActionRecord($YclientsApi, $config, $user);
        print_r("<pre>");
        print_r($recordNow);
        print_r("</pre>");
        if ($recordNow !== null && self::checkRecordDontBegin($recordNow) && !empty($recordNow['client'])){
            print_r("<pre>");
            print_r('Запись не пустая, клиент не пустой и запись еще не начата.');
            print_r("</pre>");
            if (in_array(self::getStaffIdInRecord($recordNow), $staffListWaitIds)){
                // 'createRecordToListWait';
                $message = TheLashesMessage::getMessageCreateRecordToListWait($recordNow);
            } else {
                print_r("<pre>");
                print_r('Запись к мастеру');
                print_r("</pre>");
                if (($user->current == 'AutoNotification' || $user->LastAction == 'AutoNotification')
//                    && $recordNow['attendance'] == 0
                ){
                    // 'createRecordToMaster';
                    $message = TheLashesMessage::getMessageCreateRecordToMaster($recordNow, $companies);
                }
                if ($user->current == 'AutoConfirmation' && $recordNow['attendance'] == 0){
                    // 'autoConfirmationToMaster';
                    $message = TheLashesMessage::getMessageAutoConfirmationToMaster($recordNow, $companies);
                }
            }
        } else {
            // визит уже начат. сообщение по-умолчанию
        }
        return $message;
    }

    public static function getStaffIdInRecord($record){
        $staffId = null;
        if (isset($record['staff']['id'])){
            $staffId = $record['staff']['id'];
        }
        return $staffId;
    }

    public static function checkRecordDontBegin($record){
        $now = time();
        $timeStartVisit = strtotime($record['datetime']);
        if ($now > $timeStartVisit){
            return false;
        }
        return true;
    }

    public static function getLastActionRecord($YclientsApi, $config, $user){
        // узнаем какое последнее действие было с этим номером телефона, после которого мы отправили ему СМС
        $lastAction = $user->LastAction;
        $record = null;
        switch($lastAction){
            case 'AutoNotification': $record = $user->LastAutoNotification; break;
            case 'AutoConfirmation': $record = $user->LastAutoConfirmation;  break;
        }
        if ($record !== null){
            // получить актуальные данные по recordId
            $record = json_decode($record,1);
            $companyId = $record['company_id'];
            $recordId = $record['id'];
            $userToken = $config[$companyId];
            $recordsNow = $YclientsApi->getRecord($companyId, $recordId, $userToken);
            $record = $recordsNow;
        }
        return $record;
    }
}