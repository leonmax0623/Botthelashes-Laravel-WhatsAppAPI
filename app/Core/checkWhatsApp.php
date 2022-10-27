<?php


namespace App\Core;


use Illuminate\Support\Facades\DB;

class checkWhatsApp
{

    public static function checkWhatsApp(){
//        print_r("<pre>");
//        print_r("123");
//        print_r("</pre>");
//        print_r("<pre>");
//        print_r(date("Y-m-d H:i:s", 1570552280));
//        print_r("</pre>");
//        print_r("<pre>");
//        print_r(time()-24*60*60);
//        print_r("</pre>");
        $time = time() - 2 * 3600;
//        $time = time();
        $timeStartToday = YclientsCore::getStartTimeToday() - 4 * 60 * 60 ;
        print_r("<pre>");
        print_r("время от ".date("Y-m-d H:i:s", $timeStartToday)." до ".date("Y-m-d H:i:s", $time));
        print_r("</pre>");
        $result = DB::select("
SELECT * FROM bot 
WHERE
waLastStatus IS NULL
AND checkAction = 0
AND updateTime < $time
AND updateTime > $timeStartToday
AND CompanyIdForCheckWhatsApp IS NOT NULL
AND RecordIdForCheckWhatsApp IS NOT NULL
AND EventForCheckWhatsApp IS NOT NULL
");
//        print_r("<pre>");
//        print_r($result);
//        print_r("</pre>");
        foreach($result as $r){
            $EventForCheckWhatsApp = $r->EventForCheckWhatsApp;
            $RecordIdForCheckWhatsApp = $r->RecordIdForCheckWhatsApp;
            $CompanyIdForCheckWhatsApp = $r->CompanyIdForCheckWhatsApp;
            $recordsNow = Config::getYclientsApi()->getRecord($CompanyIdForCheckWhatsApp, $RecordIdForCheckWhatsApp, Config::getConfig()[$CompanyIdForCheckWhatsApp]);
            print_r("<pre>");
            print_r($recordsNow);
            print_r("</pre>");
//            print_r("<pre>updateTime = ");
//            print_r(date("Y-m-d H:i:s", $r->updateTime));
//            print_r("</pre>");
//            print_r("<pre>");
//            print_r($r);
//            print_r("</pre>");
            if($recordsNow['attendance'] != 1 && $recordsNow['attendance'] != 2 && $recordsNow["deleted"] != 1 && !empty(YclientsCore::getClientPhone($recordsNow))) {

                switch($EventForCheckWhatsApp){
                    case 'MasterAutoNotification': $eventId = 1; $adminHelp = 'Создание записи к мастеру'; break;
                    case 'ListWaitAutoNotification': $eventId = 2; $adminHelp = 'Создание записи в Лист Ожидания'; break;
//                case 'AutoNps': $eventId = 1; $adminHelp = 'НПС';  break;
                    case 'AutoConfirmation': $eventId = 3; $adminHelp = 'Подтверждение записи'; break;
                    case 'UpdateRecord': $eventId = 4; $adminHelp = 'Изменение записи'; break;
                    case 'AutoReminder': $eventId = 5; $adminHelp = 'Напоминание о записи'; break;
                    default: $eventId = 100; $adminHelp = 'Нераспознанное сообщение'; break;
                }
                $messageWhatsApp = theLashesSmsRedirect::returnMessageFromEvent($eventId, "", $recordsNow);


//            print_r("<pre>");
//            print_r($messageWhatsApp);
//            print_r("</pre>");

                $yclientsResult = YclientsCore::putRecordAddLabelNotInWhatsApp( Config::getYclientsApi(), Config::getConfig(), $recordsNow);
                print_r("<pre>");
                print_r($yclientsResult);
                print_r("</pre>");
                $adminMessage = TheLashesMessage::getMessageAdminNotWhatsApp($recordsNow, YclientsCore::getCompaniesInfo(Config::getYclientsApi(), Config::getConfig()), $adminHelp, $messageWhatsApp);
                YclientsCore::sendAdmin($adminMessage, '74957647602');
                print_r("<pre>");
                print_r($adminMessage);
                print_r("</pre>");
                DB::table('bot')->where('id', $r->id)->update(['checkAction'=>1]);
            } else {
                print_r("<pre>");
                print_r("УДАЛЕН ИЛИ ПОДТВЕРЖДЕН");
                print_r("</pre>");
            }
        }
    }

}