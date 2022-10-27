<?php


namespace App\Http\Controllers;


use App\Core\Config;
use App\Core\Events\YclientsRequest;
use App\Core\Models\ModelException;
use App\Core\PaymentModel;
use App\Core\Test;
use App\Core\WhatsApp;
use App\Core\YclientsCore;
use App\Models\Records;
use App\Yclients\API\Request;
use App\Yclients\Cache\AdminConfig;
use App\Yclients\Connect\RequestArchitecture;
use App\Yclients\Modules\DeletedRecordForDebts;
use App\Yclients\Modules\History\History;
use App\Yclients\Modules\Scoring\Scoring;
use App\Yclients\Record\Record;
use Illuminate\Support\Facades\DB;

class TestController extends Controller
{


    public function settings(){
//        $data = [
////            'ignoreOldMessages' => 1,
//            'sendDelay' => 5,
//        ];
//        $whatsapp = new WhatsApp(Config::$waApiUrl, Config::$waToken);
//        $result = $whatsapp->request('settings', $data);
//        print_r("<pre>");
//        print_r(json_decode($result,1));
//        print_r("</pre>");

        $whatsapp = new WhatsApp(Config::$waApiUrl, Config::$waToken);
        $result = $whatsapp->requestGet('settings');
        print_r("<pre>");
        print_r(json_decode($result,1));
        print_r("</pre>");


    }

    public function test(){


        $record = self::getRecordFromYCLIENTS(41332, 181998290);

        $res = Scoring::run($record);
        print_r("<pre>");
        var_dump($res);
        print_r("</pre>");

        die();
        echo '5';
        $r=Config::getYclientsApi()->getCompanyUsers(41332, Config::getConfig()[41332]);
        print_r("<pre>");
        print_r($r);
        print_r("</pre>");

//        History::cronSendUnrecognizedMessageToAdmin();
//       $r= $this->getRecordFromYCLIENTS(41332,174367631);
//        print_r("<pre>");
//        print_r($r);
//        print_r("</pre>");
//        die();
//        $r=History::checkUserLastMessageUnrecognized("380633282597");
////        $r=History::getLastUserMessage("380633282597");
//        print_r("<pre>");
//        print_r($r);
//        print_r("</pre>");
//        $r=History::checkUserLastMessageUnrecognized(380633282597);
//
//        print_r("<pre>");
//        print_r($r);
//        print_r("</pre>");
////        History::cronSendUnrecognizedMessageToAdmin();

    }



    public function sorry(){

        die();
        $result = DB::table('deletedRecords')->where('id', '>', 1444)->get();

        $i = 0;
        foreach($result as $recordDB){
            $record = json_decode($recordDB->record,1);
            $name = Record::getClientName($record);
            $date = YclientsCore::getDateTimeStartVisitFromRecord($record);
            $services = YclientsCore::getServicesStringFromRecord($record);
            $specialization = YclientsCore::getStaffSpecializationFromRecord($record);
            $master = YclientsCore::getStaffNameFromRecord($record);
            $clientPhone = Record::getClientPhone($record);

            $message = "$name, простите меня!🤖 Я отправил сообщение по ошибке и обещаю так больше не делать🙏🙏🙏

Ваша запись $date, $services к $specialization $master подтверждена, все без изменений!";

            $i++;
            print_r("<pre>$i) ");
            print_r($message);
            print_r("</pre><br><br>");

            $sleep = rand(3, 8); sleep($sleep);
            $whatsapp = new WhatsApp(Config::$waApiUrl, Config::$waToken);
            $data = [
                'phone' => $clientPhone,
                'body' => $message
            ];
            $result = $whatsapp->request('sendMessage', $data);
        }



    }

    public function clearMessagesAndActionsQueue(){
        $whatsapp = new WhatsApp(Config::$waApiUrl, Config::$waToken);
        $result = $whatsapp->request('clearMessagesQueue', []);
        print_r("<pre>");
        print_r(json_decode($result,1));
        print_r("</pre>");
        $whatsapp = new WhatsApp(Config::$waApiUrl, Config::$waToken);
        $result = $whatsapp->request('clearActionsQueue', []);
        print_r("<pre>");
        print_r(json_decode($result,1));
        print_r("</pre>");
    }

    public function showActionsQueue(){
        $whatsapp = new WhatsApp(Config::$waApiUrl, Config::$waToken);
        $result = $whatsapp->request('showActionsQueue', []);
        print_r("<pre>");
        print_r(json_decode($result,1));
        print_r("</pre>");
    }

    public function showMessagesQueue(){
        $whatsapp = new WhatsApp(Config::$waApiUrl, Config::$waToken);
        $result = $whatsapp->request('showMessagesQueue', []);
        print_r("<pre>");
        print_r(json_decode($result,1));
        print_r("</pre>");
    }



    public function getRecordFromYCLIENTS($companyId, $recordId){
        $record = Config::getYclientsApi()->getRecord($companyId, $recordId, Config::getConfig()[$companyId]);
        return $record;
    }

}
