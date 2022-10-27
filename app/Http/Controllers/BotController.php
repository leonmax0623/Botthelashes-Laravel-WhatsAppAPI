<?php
namespace App\Http\Controllers;

use App\Core\checkWhatsApp;
use App\Core\Events\EventAssistant;
use App\Core\Events\YclientsWebhook;
use App\Core\Events\YclientsWebhookCreateRecordEvent;
use App\Core\Events\YclientsWebhookDeleteRecordEvent;
use App\Core\Events\YclientsWebhookUpdateRecordEvent;
use App\Core\Payment;
use App\Core\PaymentModel;
use App\Core\Smsc;
use App\Core\Test;
use App\Core\TheLashes;
use App\Core\theLashesModel;
use App\Core\theLashesSmsRedirect;
use App\Models\Bot;
use App\Yclients\Connect\WebHook;
use App\Yclients\Modules\DeletedRecordForDebts;
use App\Yclients\Modules\History\History;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Yclients\YclientsApi;
use App\Core\YclientsCore;
use App\Core\Telegram;
use App\Core\WhatsApp;
use App\Core\Core;
use Illuminate\Support\Facades\DB;
use App\Core\Config;
use App\Core\TheLashesMessage;

class BotController extends Controller
{

    public $YclientsApi;
    public $config;
    public $smsConfig;
    public $client;
    public $token;
    public static $hz = false;
    public static $smsActivated = true;
    public $AlfaName = 'The Lashes';
    public $waApiUrl;
    public $companies;
    public $allServices;
    public $allServicesArray;
    public $allServicesCategories;
    public $allServicesCategoriesArray;
    public static $sendContact = false;
    public static $defaultBotMessage = 0;
    public $staffListWaitIds = [395306, 292305, 654281];



    public function del(){
        DB::table('bot')->where('phone', '79265896504')->delete();
        DB::table('bot')->where('phone', '79854331197')->delete();
    }

    public function clearMessagesQueue(){
        $whatsapp = new WhatsApp(Config::$waApiUrl, Config::$waToken);
        $result = $whatsapp->request('clearMessagesQueue', []);
        print_r("<pre>");
        print_r($result);
        print_r("</pre>");
    }
    public function test(){

        print_r('ok');
    }

    public function sendMessageFromWebsie(Request $request){
//        YclientsCore::sendTelegram(json_encode($request->all(),1));
        if(!empty($request->input('url'))){$url = $request->input('url');} else {$url = 'Неизвестно';}
        if(!empty($request->input('roistat_visit'))){$roistat_visit = "Код визита: ".$request->input('roistat_visit');} else {$roistat_visit = '';}
        if(!empty($request->input('phone'))){$phone = YclientsCore::getOnlyNumberString($request->input('phone'));} else {$phone = 'Неизвестно';}
        $text = rawurlencode("Добрый день!
Спасибо что оставили заявку на сайте студии The Lashes. $roistat_visit

Сможете уточнить какие даты и время будут удобны для записи, а также какую процедуру хотелось бы получить?");
        $message = "Заявка со страницы: $url
Связаться в течение 5 мин:
Написать с ПК в WA: https://api.whatsapp.com/send?phone=$phone&text=$text
Написать с телефона в WA: https://thelashes.ru?p=$phone
Если ссылка не нажимается звони клиенту по номеру: +$phone";
        $whatsapp = new WhatsApp($this->waApiUrl, $this->token);
        $data = [
            'phone' => '74957647602',
            'body' => $message
        ];
        $result = $whatsapp->request('sendMessage', $data);
        $whatsapp->request('readChat', ['phone' => '74957647602']);

        echo 'OK';
    }

    public function theLashesSmsRedirect($eventId, $recordId){
        theLashesSmsRedirect::bootstrap($eventId, $recordId);
    }

    public function theLashesSmsRedirectWithHash($hash){
        theLashesSmsRedirect::theLashesSmsRedirectWithHash($hash);
    }






    public function testPhone($phone){

      $user = DB::table('bot')->where('phone', $phone)->first();

      print_r("<pre>");
      print_r($user);
      print_r("</pre>");
        $message = TheLashes::getMessageAfterSms($this->YclientsApi, $this->config, $user, $this->staffListWaitIds, $this->companies);

        print_r("<pre>");
        print_r($message);
        print_r("</pre>");

        if ($message == 'default'){
            $message = self::messageDefault(); $shareContact = true;
        }

      print_r("<pre>");
      print_r($message);
      print_r("</pre>");

    }

    public function __construct()
    {
        $this->client = new \GuzzleHttp\Client(['timeout'  => 2]);
        $this->YclientsApi = new YclientsApi(env("YCLIENTS_PARTNER_TOKEN"));
        $userToken_138368 = YclientsCore::getAuthYclientsUserToken($this->YclientsApi, 138368, "700000011114","trendo1ber121");
        $userToken_41332 = YclientsCore::getAuthYclientsUserToken($this->YclientsApi, 41332, "700000011114","trendo1ber121");
        $userToken_225366 = YclientsCore::getAuthYclientsUserToken($this->YclientsApi, 225366, "700000011114","trendo1ber121");
        $this->config = [
            138368 => $userToken_138368,
            41332 => $userToken_41332,
            225366 => $userToken_225366
        ];
        $this->smsConfig = [
            138368 => [
                'login' => 'thelashesavtozavod',
                'password' => 'only1lashes'
            ],
            41332 => [
                'login' => 'thelashes',
                'password' => 'onlylashes'
            ],
            225366 => [
                'login' => 'thelashesramenki',
                'password' => 'only2lashes'
            ]
        ];
        $this->waApiUrl = Config::$waApiUrl;
        $this->token = Config::$waToken;
        $this->companies = YclientsCore::getCompaniesInfo($this->YclientsApi, $this->config);
        $this->allServices = YclientsCore::getAllServices($this->YclientsApi, $this->config);
        $this->allServicesArray = YclientsCore::getAllServicesArray($this->allServices);
        $this->allServicesCategories = YclientsCore::getAllServicesCategories($this->YclientsApi, $this->config);
        $this->allServicesCategoriesArray = YclientsCore::getAllServicesCategoriesArray($this->allServicesCategories);
    }


    public function redirectLink($id){
        $result = DB::table('links')->where('id', $id)->first();
        $link = $result->link;
        header("Location: $link");
    }

    public function AutoConfirmation()
    {
        $companies = $this->companies;
        $records = YclientsCore::getAllRecordsNextDay($this->YclientsApi, $this->config);

        foreach($this->config as $companyId => $userToken){
            foreach($records[$companyId]['data'] as $record){
                print_r("<pre>");
                print_r($record);
                print_r("</pre>");

                $staffId = null; if(isset($record['staff']['id'])){$staffId = $record['staff']['id'];}
                if (!in_array($staffId, $this->staffListWaitIds) && $record['attendance'] == 0 && !empty($record['client'])){
                    $clientPhone = YclientsCore::getClientPhone($record);

                    $subscriber = Bot::getBotWherePhone($clientPhone);
                    if ($subscriber !== null){
                        $offerStatus = $subscriber->offer;
                    } else {
                        $offerStatus = 0;
                    }

                    $messageAutoConfirmation = TheLashesMessage::getMessageAutoConfirmationToMaster($record, $companies, $offerStatus);


                    print_r("<pre>");
                    print_r($messageAutoConfirmation);
                    print_r("</pre>");




                    if(true){
                        $whatsapp = new WhatsApp($this->waApiUrl, $this->token);
                        $data = [
                            'phone' => $clientPhone,
                            'body' => $messageAutoConfirmation
                        ];
                        if (!self::$smsActivated || YclientsCore::checkAccessUserSendMessageToWhatsApp($clientPhone)){


                            $sleep = rand(5, 10); sleep($sleep);
                            $whatsapp->request('readChat', ['phone' => $clientPhone]);
                            $result = $whatsapp->request('sendMessage', $data);
                            $whatsapp->request('readChat', ['phone' => $clientPhone]);


//                        YclientsCore::sendTelegram("$messageAutoConfirmation - $result");

//                            YclientsCore::sendAdminTelegram("
//<b>Auto Confirmation</b>
//Отправлено WhatsApp сообщение на номер $clientPhone
//Сообщение: $messageAutoConfirmation");
                        } else {
                            $login = $this->smsConfig[$companyId]['login'];
                            $password = $this->smsConfig[$companyId]['password'];
                            $message = "Завтра запись! Подтвердить по ссылке https://thelashes.ru/?_=".theLashesModel::insertSmsId('3-'.$record['id']);
                            $sender = $this->AlfaName;
                            $result = Smsc::sendSMS($login, $password, $clientPhone, $message, $sender);
//                            YclientsCore::sendAdminTelegram("
//<b>The Lashes WA bot</b>
//Отправлено смс на номер $clientPhone
//Сообщение: $message
//Ответ сервера: $result");
                        }
                        $user = DB::table('bot')->where('phone', $clientPhone)->first();
                        if ($user === null) {
                            $insert = [];
                            $insert['vendor'] = 'whatsapp';
                            $insert['userId'] = $clientPhone . '@c.us';
                            $insert['phone'] = $clientPhone;
                            $insert['current'] = 'AutoConfirmation';
                            $insert['waLastStatus'] = null;
                            $insert['LastAutoConfirmation'] = json_encode($record, true);
                            $insert['updateTime'] = time();
                            $insert['LastAction'] = 'AutoConfirmation';
                            $insert['checkAction'] = 0;
                            $insert['EventForCheckWhatsApp'] = 'AutoConfirmation';
                            $insert['RecordIdForCheckWhatsApp'] = $record['id'];
                            $insert['CompanyIdForCheckWhatsApp'] = $record['company_id'];
                            DB::table('bot')->insert($insert);
                        } else {
                            $update = [];
                            $update['current'] = 'AutoConfirmation';
                            $update['waLastStatus'] = null;
                            $update['LastAutoConfirmation'] = json_encode($record, true);
                            $update['updateTime'] = time();
                            $update['checkAction'] = 0;
                            $update['LastAction'] = 'AutoConfirmation';
                            $update['EventForCheckWhatsApp'] = 'AutoConfirmation';
                            $update['RecordIdForCheckWhatsApp'] = $record['id'];
                            $update['CompanyIdForCheckWhatsApp'] = $record['company_id'];
                            DB::table('bot')->where('phone', $clientPhone)->update($update);
                        }
                        //
                    }

                }
            }
        }
        echo 'ok';
    }

    public function AutoNps()
    {
// добавили в базу ВСЕ записи, которые ОПЛАЧЕНЫ и не в Листе лжидания
        $time = time() - 24 * 60 * 60; // last 1 days
        $nps = DB::table('nps')->where('timeEndVisit', '>', $time)->get();
        $recordAddedIds = [];
        foreach($nps as $row){
            $recordAddedIds[] = $row->recordId;
        }
        $dateTime = new \DateTime;
        $today = time();
        $dateTime->setDate(date('Y', $today), date('m', $today), date('d', $today));
        $records = [];
        foreach($this->config as $companyId => $userToken){
            $records[$companyId] = $this->YclientsApi->getRecords($companyId, $userToken, null, 9999, null, null, $dateTime, $dateTime);
        }

        foreach($this->config as $companyId => $userToken){
            foreach($records[$companyId]['data'] as $record){
                $recordId = $record['id'];
                $attendance = $record['attendance'];
                if (!in_array($recordId, $recordAddedIds)){
                    $staffId = null; if(isset($record['staff']['id'])){$staffId = $record['staff']['id'];}
                    if (!in_array($staffId, Config::$staffListWaitIds) && !in_array($staffId, Config::$staffListPrepaymentsIds) && $record['paid_full'] == 1 && $attendance == 1){
                        $clientPhone = null;if(isset($record['client']['phone'])){$clientPhone = YclientsCore::getOnlyNumberString($record['client']['phone']);}
                        $timeEndVisit = strtotime($record['datetime']) + $record['seance_length'];
                        $insert = [];
                        $insert['phone'] = $clientPhone;
                        $insert['recordId'] = $recordId;
                        $insert['record'] = json_encode($record,1);
                        $insert['timeEndVisit'] = $timeEndVisit;
                        $insert['timeSendNps'] = time();
                        DB::table('nps')->insert($insert);
                        print_r("<pre>");
                        print_r($record);
                        print_r("</pre>");
                    }
                }
            }
        }

        ////////////

        $nps = DB::table('nps')->where('timeEndVisit', '>', $time)->get();
        $timeNow = time();
        foreach($nps as $row){
            $record = json_decode($row->record,1);
            if(($timeNow > $row->timeEndVisit + 10 * 60) && $row->send != 1){
                $clientPhone = null;if(isset($record['client']['phone'])){$clientPhone = YclientsCore::getOnlyNumberString($record['client']['phone']);}
                $clientName = 'Здравствуйте!'; if (!empty($record['client']['name'])) {$clientName = $record['client']['name'].',';}
                $masterName = ''; if (!empty($record['staff']['name'])) {$masterName = $record['staff']['name'];}
                $r = DB::table('bot')->where('phone', $clientPhone)->update([
                    'current' => 'AutoNps',
                    'LastAutoNps' => json_encode($record,1),
                    'updateTime' => time(),
                    'checkAction' => 0,
                    'EventForCheckWhatsApp' => 'AutoNps',
                    'RecordIdForCheckWhatsApp' => $record['id'],
                    'CompanyIdForCheckWhatsApp' => $record['company_id']
//                    'LastAction' => 'AutoNps'
                ]);
                if (!$r){
                    // клиента не существует в боте, то добавить его в бот
                    $insert = [];
                    $insert['vendor'] = 'whatsapp';
                    $insert['userId'] = $clientPhone . '@c.us';
                    $insert['phone'] = $clientPhone;
                    $insert['current'] = 'AutoNps';
                    $insert['LastAutoNps'] = json_encode($record,1);
                    $insert['updateTime'] = time();
//                    $insert['LastAction'] = 'AutoNps';
                    $insert['checkAction'] = 0;
                    $insert['EventForCheckWhatsApp'] = 'AutoNps';
                    $insert['RecordIdForCheckWhatsApp'] = $record['id'];
                    $insert['CompanyIdForCheckWhatsApp'] = $record['company_id'];

                    DB::table('bot')->insert($insert);
                }
                $message = "
$clientName

⚠ Оцените, пожалуйста, как сегодня прошел визит сегодня к мастеру $masterName?

🔢```Поставьте оценку от 0 до 10, где:```👇
*10* - 😇 Все отлично, обязательно приду вновь и буду рекомендовать знакомым!
*0* - 🤬 Больше не приду и никому рекомендовать не буду!
";
                $whatsapp = new WhatsApp($this->waApiUrl, $this->token);
                $data = [
                    'phone' => $clientPhone,
                    'body' => $message
                ];

                print_r("<pre>");
                print_r($message);
                print_r("</pre>");
                // НПС только подписчикам бота
                if (!self::$smsActivated || YclientsCore::checkAccessUserSendMessageToWhatsApp($clientPhone)){
                    $whatsapp->request('readChat', ['phone' => $clientPhone]);
                    $result = $whatsapp->request('sendMessage', $data);
                    $whatsapp->request('readChat', ['phone' => $clientPhone]);
                }


//                YclientsCore::sendTelegram("$message - $result");

                DB::table('nps')->where('phone', $clientPhone)->update(['send' => 1]);

            }

        }


        echo 'AutoNps';
    }



    public function waWebhook(){



        $webhook = file_get_contents('php://input');
        $data = json_decode($webhook,1);
        Core::sendMichael($webhook);
//        die();
        /* update Ack */
        if (isset($data['ack'])){ YclientsCore::updateWhatsAppStatus($data); }

        if (isset($data['messages'])){
            foreach($data['messages'] as $message){
                $body = $message['body'];
                $fromMe = $message['fromMe'];
                $chatId = $message['chatId'];
                if(!empty( $message['senderName'])){$senderName = $message['senderName'];} else {$senderName = 'Имя не распознано';}

                if ($fromMe == true){
                    // это сообщение отправил сам бот.
                } else {
                    // человек написал сообщение боту. вот здесь начинается уже ЛОГИКА
//                    Core::sendDev("The Lashes Bot - $chatId - $body");
//                    die();

                    // То, что нам пишет подписчик WhatsApp сохраняем в историю.
                    $clientPhone = YclientsCore::getOnlyNumberString($chatId);
                    History::addSendUserMessageToHistory(YclientsCore::getOnlyNumberString($chatId), $senderName, $body);


                    $user = DB::table('bot')->where('userId', $chatId)->first();
                    $firstWriteBotAfterSms = false;
                    if ($user === null) {
                        // Если пользователя нет, значит он пишет нам первый и ему никогда не приходило ранее СМС !
                        $insert = [];
                        $insert['vendor'] = 'whatsapp';
                        $insert['userId'] = $chatId;
                        $insert['phone'] = YclientsCore::getOnlyNumberString($chatId);
                        $insert['waLastStatus'] = 'answered';
                        $insert['updateTime'] = time();
                        $insert['start'] = 1;
                        DB::table('bot')->insert($insert);
                        $current = null;
                        $isModel = 0;
                    } else {
                        $start = $user->start;
                        if ($start == 0 && self::$smsActivated){
                            $firstWriteBotAfterSms = true;
//                            $firstWriteBotAfterSms = false; // смс отключены
                            DB::table('bot')->where('userId', $chatId)->update(['start' => 1]);
                        }
                        $current = $user->current;
                        $isModel = $user->isModel;
                        $isModel = 0; // отключаем функциональность моделей, когда циклится запрос. все ненужно.
                    }

                    if($isModel){
                        $message = self::messageModel();
                        $whatsapp = new WhatsApp($this->waApiUrl, $this->token);
                        $data = [
                            'chatId' => $chatId,
                            'body' => $message
                        ];
                        $result = $whatsapp->request('sendMessage', $data);
                        $whatsapp->request('readChat', ['chatId' => $chatId]);
                        die();
                    }

                    $command = $body;

                    $shareContact = false;
                    $hz = false;



                    if ($code = theLashesSmsRedirect::getCodeFromHash($command)){

                        $message = theLashesSmsRedirect::messageWithHash($command, $chatId, $senderName, $code);
//                       Core::sendDev($message);
                    }


//                    if(stristr($command, 'Идентификатор записи: #')){
//
//                        $message = theLashesSmsRedirect::message($command, $chatId, $senderName);
//
//                    }
//                    if ($firstWriteBotAfterSms){
//
////                        $message = theLashesSmsRedirect::message($command, $chatId, $senderName);
//                        $message = TheLashes::getMessageAfterSms($this->YclientsApi, $this->config, $user, $this->staffListWaitIds, $this->companies);
//
//                        if ($message == 'default'){
//                            $message = self::messageDefault(); $shareContact = true; $hz = true;
//                        }
//
//                        if ($current == 'updateRecord'){
//                            $message = self::messageUpdateRecord($chatId, $this->companies); $shareContact = false; $hz = false;
//                        }
//
//                        if ($current == 'AutoReminder'){
//                            $message = self::messageReminder($chatId, $this->companies); $shareContact = false; $hz = false;
//                        }
//
//                    }
                    else {
                        switch($current){
//                        case null: $message = self::messageDefault(); break;
//                            case 'updateRecord': $message = self::messageUpdateRecord($chatId, $this->companies); break;
                            case 'waitReview78': $message = self::messageAutoNPS78Review($chatId, $command); break;
                            case 'waitReview16': $message = self::messageAutoNPS16Review($chatId, $command); break;
                            case 'AutoConfirmation': switch($command){
                                case '1': case '✔': $message = self::messageAutoConfirmationConfirmed($this->YclientsApi, $this->config, $chatId); break;
                                case '3': case '📲': $message = self::messageAutoConfirmationRecordAgain($chatId, $this->YclientsApi, $this->config, $this->companies); break;
                                case '2': case '🚫': $message = self::messageAutoConfirmationRecordDelete($this->YclientsApi, $this->config, $chatId); break;
                                default:
                                    $return = YclientsCore::commandFind($command, ['✔', '📲', '🚫', '1', '3', '2' ]);
                                    if($return['find']){
                                        switch($return['command']){
                                            case '1': case '✔': $message = self::messageAutoConfirmationConfirmed($this->YclientsApi, $this->config, $chatId); break;
                                            case '3': case '📲': $message = self::messageAutoConfirmationRecordAgain($chatId, $this->YclientsApi, $this->config, $this->companies); break;
                                            case '2': case '🚫': $message = self::messageAutoConfirmationRecordDelete($this->YclientsApi, $this->config, $chatId); break;
                                            default: $message = self::messageAutoConfirmationRepeat($chatId); $hz = true; break;
                                        }
                                    } else {
                                        $message = self::messageAutoConfirmationRepeat($chatId); $hz = true; break;
                                    }
                            }; break;
                            case 'DeletedRecordForDebts': switch($command){
                                case '1': case '➕': $message = DeletedRecordForDebts::messageRestoreRecord($clientPhone); break;
                                case '3': case '📲': $message = DeletedRecordForDebts::messageCallMeAfterDeletedRecordForDebts($clientPhone); break;
                                default:
                                    $return = YclientsCore::commandFind($command, ['📲', '➕', '1', '3']);
                                    if($return['find']){
                                        switch($return['command']){
                                            case '1': case '➕': $message = DeletedRecordForDebts::messageRestoreRecord($clientPhone); break;
                                            case '3': case '📲': $message = DeletedRecordForDebts::messageCallMeAfterDeletedRecordForDebts($clientPhone); break;
                                            default: $message = self::messageDefault(); $shareContact = true; $hz = true;  break;
                                        }
                                    } else {
                                        $message = self::messageDefault(); $shareContact = true; $hz = true;  break;
                                    }
                            }; break;
                            case 'DeletedRecord': switch($command){
                                case '3': case '📲': $message = self::messageDeletedRecord($chatId, $this->companies); break;
                                default:
                                    $return = YclientsCore::commandFind($command, ['📲', '3']);
                                    if($return['find']){
                                        switch($return['command']){
                                            case '3': case '📲': $message = self::messageDeletedRecord($chatId, $this->companies); break;
                                            default: $message = self::messageDefault(); $shareContact = true; $hz = true;  break;
                                        }
                                    } else {
                                        $message = self::messageDefault(); $shareContact = true; $hz = true;  break;
                                    }
                            }; break;
                            case 'AutoNotification': switch($command){
                                case '1': case '✔': $message = self::messageAutoNotificationOk($this->YclientsApi, $this->config, $chatId); break;
                                case '3': case '📲': $message = self::messageAutoNotificationCallMeNow($this->YclientsApi, $this->config, $chatId, $this->companies); break;
                                case '2': case '🚫': $message = self::messageAutoNotificationDeleteRecord($this->YclientsApi, $this->config, $chatId); break;
                                default:
                                    $return = YclientsCore::commandFind($command, ['✔', '📲', '🚫',  '1', '3', '2']);
                                    if($return['find']){
                                        switch($return['command']){
                                            case '1': case '✔': $message = self::messageAutoNotificationOk($this->YclientsApi, $this->config, $chatId); break;
                                            case '3': case '📲': $message = self::messageAutoNotificationCallMeNow($this->YclientsApi, $this->config, $chatId, $this->companies); break;
                                            case '2': case '🚫': $message = self::messageAutoNotificationDeleteRecord($this->YclientsApi, $this->config, $chatId); break;
                                            default: $message = self::messageAutoNotificationRepeat($chatId); $hz = true; break;
                                        }
                                    } else {
                                        $message = self::messageAutoNotificationRepeat($chatId); $hz = true; break;
                                    }
                            }; break;
                            case 'AutoNps': switch($command){
                                case '🤬': case '0': case '1': case '2': case '3': case '4': case '5': case '6': $message = self::messageAutoNPS16($chatId, $command); break;
                                case '7': case '8': $message = self::messageAutoNPS78($chatId, $command); break;
                                case '😇': case '9': case '10': $message = self::messageAutoNPS910($chatId, $command); break;
                                default:
                                    $return = YclientsCore::commandFind($command, ['10', '9', '8', '7', '6', '5', '4', '3', '2', '1', '0', '😇', '🤬']);
                                    if($return['find']){
                                        switch($return['command']){
                                            case '🤬': case '0': case '1': case '2': case '3': case '4': case '5': case '6': $message = self::messageAutoNPS16($chatId, $return['command']); break;
                                            case '7': case '8': $message = self::messageAutoNPS78($chatId, $return['command']); break;
                                            case '😇': case '9': case '10': $message = self::messageAutoNPS910($chatId, $return['command']); break;
                                            default: $message = self::messageAutoNPSRepeat($chatId); $hz = true; break;
                                        }
                                    } else {
                                        $message = self::messageAutoNPSRepeat($chatId); $hz = true; break;
                                    }
                            }; break;
                            default: $message = self::messageDefault(); $shareContact = true; $hz = true;  break;
                        }
                    }



                    $stopAdmin = false;
                    if (stristr(mb_strtolower($command), Config::MODEL)){
                        $message = self::messageModel();
                        $shareContact = false;
                        DB::table('bot')->where('userId', $chatId)->update(['isModel' => 1]);
                        $stopAdmin = true;
                    }


                    if ((self::$hz || $hz) && !$stopAdmin){
//                        TheLashesMessage::sendUnrecognizedMessageToAdmin($command, $chatId, $senderName);
                    }


                    if (($hz || self::$hz) && $isModel == 0){
                        $message = $message ."
```⚡Если ответ нужен немедленно, просто нажмите на ссылку ниже и ваш вопрос попадет в чат с администратором, минуя бота:⬇⬇⬇```
https://api.whatsapp.com/send?phone=74957647602&text=".urlencode($command);
                    }

//                    if(self::$sendContact){
//                        $shareContact = true;
//                    }


//                    $r = $bot->request('sendMessage', ['text' => "$current - Мне написал $chatId - ".$body, 'chat_id' => 57625110]);


                    $allowSendMessage = true; // по-умолчанию, разрешаем боту отправлять клиенту сообщения в ответ на его сообщения
                    if (self::$defaultBotMessage && History::checkUserLastMessageUnrecognized($clientPhone)){
                        // если текущее сообщение - это ответ на нераспознанное
                        // а перед эти мы уже и так отправили нераспознанное, тогда блокируем отправку этого сообщения
                        $allowSendMessage = false;
                    }

                    $whatsapp = new WhatsApp($this->waApiUrl, $this->token);
                    $data = [
                        'chatId' => $chatId,
                        'body' => $message
                    ];
                    if ($allowSendMessage){
                        $whatsapp->request('sendMessage', $data, self::$defaultBotMessage);
                        $whatsapp->request('readChat', ['chatId' => $chatId]);
                    }

                }
            }
        }
    }

    public function webhookYclients()
    {
        WebHook::WebHook();
        die();

        YclientsWebhook::webhookYclients();
        die();
//        YclientsCore::sendTelegram('хук пришел the lashes');
        $companies = $this->companies;
        $webhook = file_get_contents('php://input');
        $data = json_decode($webhook,1);

//        Core::sendDev($webhook);

        // Если ЗАПИСЬ СОЗДАНА
        if(!empty($data['resource']) && $data['resource'] == 'record' && $data['status'] == 'create'){
            $record = $data['data'];

            if (in_array(YclientsCore::getClientPhone($record), Test::$betaClients)){
                Core::sendDev("lashes YclientsWebhookCreateRecordEvent");
                if (EventAssistant::getConnectYclients() == 'webhook'){
                    YclientsWebhookCreateRecordEvent::CreateRecordEvent($record);
                }

                die();
            }



            $timeEndVisit = strtotime($record['datetime']) + $record['seance_length'];
            $now = time();

            if (in_array(YclientsCore::getStaffId($record), Config::$staffListPrepaymentsIds)){die();} // если создан визит на предоплату в пресне


            if ($now > $timeEndVisit){
//                YclientsCore::sendTelegram('визит в прошлом');
                die();
            }



            YclientsCore::addRecord($record);
            YclientsCore::checkPutRecordAddBotoxCommentAndSendMessageToAdmin($record);

//            Core::sendDev($webhook);
//            Core::sendDev("online - ".$record['online']);
//            Core::sendDev("sms_before - ".$record['sms_before']);
//            Core::sendDev("sms_now - ".$record['sms_now']);
//            Core::sendDev("sms_now_text - ".$record['sms_now_text']);
//            Core::sendDev("email_now - ".$record['email_now']);
//            Core::sendDev("notified - ".$record['notified']);

            if ($record['online']){
                // сам на сайте
            } else {
                // администратор
            }


            $companyId = $data['company_id'];
            $clientName = YclientsCore::getClientNameFromRecord($record, 'Здравствуйте!', ',');
            $staffId = null;
            if(empty($record['client']['phone'])){die();}
            $clientPhone = null;
            if (isset($record['staff']['id'])) {$staffId = $record['staff']['id'];}
            if (isset($record['client']['phone'])) {$clientPhone = $record['client']['phone'];}
            if(in_array($staffId, $this->staffListWaitIds)){

                $online = $record['online'];
                if ($online){
//                    YclientsCore::sendTelegram("Онлайн ДА-  $online");

                    $message = TheLashesMessage::getMessageCreateRecordToListWait($record);
                } else {
//                    YclientsCore::sendTelegram("Онлайн NO-  $online");
//                    $current = 'AutoNotificationAdminOffline';
                    $message = TheLashesMessage::getMessageCreateRecordToListWaitAdminOffline($record);
                }

                $hash = theLashesModel::insertSmsId('2-'.$record['id']);
                $smsMessage = 'Запись в Листе ожидания! Подробнее: https://thelashes.ru/?_='.theLashesModel::insertSmsId('2-'.$record['id']);

                $EventForCheckWhatsApp = 'ListWaitAutoNotification';
                $current = 'AutoNotification';


                $sendLocation = false;
            } else {

                $smsMessage = 'Подтвердите запись по ссылке: https://thelashes.ru/?_='.theLashesModel::insertSmsId('1-'.$record['id']);

                $current = null;

                $message = TheLashesMessage::getMessageCreateRecordToMaster($record, $companies);

                $sendLocation = true;

                $EventForCheckWhatsApp = 'MasterAutoNotification';

            }




            $numberSecondsToday = time() - strtotime('today');
            $numberSecondsTodayLeft = 2* 24 * 60 * 60 - $numberSecondsToday;
            if(time() > strtotime($record['datetime']) - $numberSecondsTodayLeft){
                if(!in_array($staffId, $this->staffListWaitIds)){
//                    YclientsCore::sendTelegram('визит создан в будущем но не более двух дней для мастера');

                    foreach($this->config as $companyId => $userToken) {
                        if ($record['company_id'] == $companyId) {
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
                            $r = YclientsCore::putVisit($this->YclientsApi, $fields, $visitId, $recordId, $userToken);
                        }
                    }

                }
            }

            $companyId = $data['company_id'];

//            YclientsCore::sendTelegram('перед бд');
            $clientPhone = YclientsCore::getOnlyNumberString($clientPhone);
            $user = DB::table('bot')->where('phone', $clientPhone)->first();
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
                DB::table('bot')->insert($insert);
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
                DB::table('bot')->where('phone', $clientPhone)->update($update);
            }
//            YclientsCore::sendTelegram('после бд');
            $whatsapp = new WhatsApp($this->waApiUrl, $this->token);
            $data = [
                'phone' => $clientPhone,
                'body' => $message
            ];


//            Core::sendDev($message);


            if (!self::$smsActivated || YclientsCore::checkAccessUserSendMessageToWhatsApp($clientPhone)){
                $whatsapp->request('readChat', ['phone' => $clientPhone]);
                $result = $whatsapp->request('sendMessage', $data);
                $whatsapp->request('readChat', ['phone' => $clientPhone]);
            } else {

                $message = $smsMessage;

                $login = $this->smsConfig[$companyId]['login'];
                $password = $this->smsConfig[$companyId]['password'];

                $sender = $this->AlfaName;
                        $result = Smsc::sendSMS($login, $password, $clientPhone, $message, $sender);
//                        YclientsCore::sendAdminTelegram("
//<b>The Lashes WA bot</b>
//Отправлено смс на номер $clientPhone
//Сообщение: $message
//Ответ сервера: $result");
            }


            YclientsCore::checkLagYclientsWebhook("Вебхук - Создание записи", $record);

        }


        if(!empty($data['resource']) && $data['resource'] == 'record' && $data['status'] == 'update'){
//            Core::sendDev($webhook);
            $record = $data['data'];

            if (in_array(YclientsCore::getClientPhone($record), Test::$betaClients)){
                Core::sendDev("lashes YclientsWebhookUpdateRecordEvent");
                if (EventAssistant::getConnectYclients() == 'webhook'){
                    YclientsWebhookUpdateRecordEvent::UpdateRecordEvent($record);
                }

                die();
            }


            $recordLastDB = YclientsCore::getRecordLast($record);
            if ($recordLastDB !== null){
                $recordLast = json_decode($recordLastDB->record, 1);


                $companyId = $data['company_id'];

                $clientPhone = YclientsCore::getClientPhone($record);
                $user = DB::table('bot')->where('phone', $clientPhone)->first();
                if (empty($user->phone)){ die();}
//            if(in_array(YclientsCore::getStaffId($record), $this->staffListWaitIds)){die();}
                if (time() > strtotime($record['datetime'])){ die();}
                if ($record['attendance'] == '-1'){ die();}




                $recordId = $record['id'];
                $recordDate = $record['date'];
                $recordStaffId = YclientsCore::getStaffId($record);
                $recordServicesIds = YclientsCore::getRecordServicesIds($record);
                $recordLastId = $recordLast['id'];
                $recordLastDate = $recordLast['date'];
                $recordLastStaffId = YclientsCore::getStaffId($recordLast);
                $recordLastServicesIds = YclientsCore::getRecordServicesIds($recordLast);


//                YclientsCore::sendTelegram('-----------------------------'. $clientPhone);
//                YclientsCore::sendTelegram('$recordId - '. $recordId);
//                YclientsCore::sendTelegram('$recordLastId - '. $recordLastId);
//                YclientsCore::sendTelegram('дата время новое - '. $record['date']);
//                YclientsCore::sendTelegram('дата время сохраненного - '. $recordLast['date']);
//
//                YclientsCore::sendTelegram('сорудник новое - '. $recordStaffId);
//                YclientsCore::sendTelegram('сорудник сохраненного - '. $recordLastStaffId);
//
//                YclientsCore::sendTelegram('услуги новое - '. json_encode($recordServicesIds,1));
//                YclientsCore::sendTelegram('услуги сохраненного - '. json_encode($recordLastServicesIds,1));

                if(
                    $recordDate !== $recordLastDate ||
                    $recordStaffId !== $recordLastStaffId ||
                    $recordServicesIds !== $recordLastServicesIds
                ){
//                    YclientsCore::sendTelegram('запись изменена - '. $clientPhone);
                    $message = TheLashesMessage::getMessageRecordUpdate($record, $recordLast, $this->companies);

                    $whatsapp = new WhatsApp($this->waApiUrl, $this->token);

                    if (!self::$smsActivated || YclientsCore::checkAccessUserSendMessageToWhatsApp($clientPhone)){
                        $whatsapp->request('readChat', ['phone' => $clientPhone]);
                        $data = [
                            'phone' => $clientPhone,
                            'body' => $message
                        ];
                        $result = $whatsapp->request('sendMessage', $data);
                        $whatsapp->request('readChat', ['phone' => $clientPhone]);
                    } else {
                        $message = 'Ваша запись изменена. Детали: https://thelashes.ru/?_='.theLashesModel::insertSmsId('4-'.$record['id']);
                        $result = Smsc::sendSMS($this->smsConfig[$companyId]['login'], $this->smsConfig[$companyId]['password'], $clientPhone, $message, $this->AlfaName);
//                    YclientsCore::sendAdminTelegram("
//<b>The Lashes WA bot</b>
//Отправлено смс на номер $clientPhone
//Сообщение: $message
//Ответ сервера: $result");
                    }

                    $update['current'] = null;

                    $update['checkAction'] = 0;
                    $update['updateTime'] = time();
                    $update['EventForCheckWhatsApp'] = 'UpdateRecord';
                    $update['CompanyIdForCheckWhatsApp'] = $record['company_id'];
                    $update['RecordIdForCheckWhatsApp'] = $record['id'];
                    $update['LastUpdateRecord'] = json_encode($record, true);

                    DB::table('bot')->where('phone', $clientPhone)->update($update);
                }

                YclientsCore::checkLagYclientsWebhook("Вебхук - Изменение записи", $record);
                YclientsCore::updateRecord($record);
                YclientsCore::checkPutRecordAddOrangeColor($record);
            }

//            else {
//                    // если записи действительно нет в БД, а нам пришел вебхук об ее изменении, то добавляем актуальную запись в БД
//                    EventAssistant::addRecord($record);
//                    Core::sendDev($record['id']." added");
//                }

        }




        // Если ЗАПИСЬ УДАЛЕНА
        if(!empty($data['resource']) && $data['resource'] === 'record' && $data['status'] === 'delete'){

//            die();
            $record = $data['data'];

            if (in_array(YclientsCore::getClientPhone($record), Test::$betaClients)){
                Core::sendDev("lashes YclientsWebhookUpdateRecordEvent");
                if (EventAssistant::getConnectYclients() == 'webhook'){
                    YclientsWebhookDeleteRecordEvent::DeleteRecordEvent($record);
                }

                die();
            }

            $timeEndVisit = strtotime($record['datetime']) + $record['seance_length'];
            $now = time();
            YclientsCore::deleteRecord($record);
            if ($now > $timeEndVisit){
                die();
            }
            if(in_array(YclientsCore::getStaffId($record), $this->staffListWaitIds)){die();}
            if(in_array(YclientsCore::getStaffId($record), Config::$staffListPrepaymentsIds)){die();}


            $companyId = $data['company_id'];


            $message = TheLashesMessage::getMessageDelete($record);
            $clientPhone = YclientsCore::getClientPhone($record);
            $user = DB::table('bot')->where('phone', $clientPhone)->first();
            if ($user === null) {
                $insert = [];
                $insert['vendor'] = 'whatsapp';
                $insert['userId'] = $clientPhone . '@c.us';
                $insert['phone'] = $clientPhone;
                $insert['current'] = 'DeletedRecord';
                $insert['LastDeletedRecord'] = json_encode($record, true);
                $insert['updateTime'] = time();
                DB::table('bot')->insert($insert);
            } else {
                $update = [];
                $update['current'] = 'DeletedRecord';
                $update['LastDeletedRecord'] = json_encode($record, true);
                $update['updateTime'] = time();
                DB::table('bot')->where('phone', $clientPhone)->update($update);
            }
            $whatsapp = new WhatsApp($this->waApiUrl, $this->token);
            $data = [
                'phone' => $clientPhone,
                'body' => $message
            ];



            if (!self::$smsActivated || YclientsCore::checkAccessUserSendMessageToWhatsApp($clientPhone)){
                $whatsapp->request('readChat', ['phone' => $clientPhone]);
                $result = $whatsapp->request('sendMessage', $data);
                $whatsapp->request('readChat', ['phone' => $clientPhone]);
            } else {

                $message = 'Удалена запись. Восстановить: https://thelashes.ru/?_='.theLashesModel::insertSmsId('6-'.$record['id']);;

                $login = $this->smsConfig[$companyId]['login'];
                $password = $this->smsConfig[$companyId]['password'];

                $sender = $this->AlfaName;
                $result = Smsc::sendSMS($login, $password, $clientPhone, $message, $sender);
//                        YclientsCore::sendAdminTelegram("
//<b>The Lashes WA bot</b>
//Отправлено смс на номер $clientPhone
//Сообщение: $message
//Ответ сервера: $result");
            }

            YclientsCore::checkLagYclientsWebhook("Вебхук - Удаление записи", $record);

            if (!$record['online'] && in_array(YclientsCore::getStaffId($record), Config::$staffListWaitIds)){
                die();
            }
        }



    }




    public static function getNavigator($companyId){
        $navigator = '';
        switch($companyId){
            case '138368':
//                $navigator = 'https://30488.redirect.appmetrica.yandex.com/show_point_on_map?appmetrica_tracking_id=745803332332869252&desc=The%20Lashes&lang=ru&lat=55.711524&lon=37.663964&subtitle=%D0%92%D0%B5%D0%BB%D0%BE%D0%B7%D0%B0%D0%B2%D0%BE%D0%B4%D1%81%D0%BA%D0%B0%D1%8F%20%D1%83%D0%BB%D0%B8%D1%86%D0%B0%2C%206%D0%90';
                $navigator = 'http://bit.ly/YandexNavi';
                break;
            case '41332':
//                $navigator = 'https://30488.redirect.appmetrica.yandex.com/show_point_on_map?appmetrica_tracking_id=745803332332869252&desc=THE%20LASHES%20-%20%D0%A1%D1%82%D1%83%D0%B4%D0%B8%D1%8F%20%D0%BD%D0%B0%D1%80%D0%B0%D1%89%D0%B8%D0%B2%D0%B0%D0%BD%D0%B8%D1%8F%20%D1%80%D0%B5%D1%81%D0%BD%D0%B8%D1%86&lang=ru&lat=55.771267&lon=37.570278&subtitle=%D1%83%D0%BB%D0%B8%D1%86%D0%B0%20%D0%9F%D1%80%D0%B5%D1%81%D0%BD%D0%B5%D0%BD%D1%81%D0%BA%D0%B8%D0%B9%20%D0%92%D0%B0%D0%BB%2C%2038%2C%20%D1%81%D1%82%D1%80.%201';
                $navigator = 'http://bit.ly/Yandex_Navi';
                break;
            case '225366':
                $navigator = 'http://bit.ly/YandexNavi3';
                break;
        }
        return $navigator;
    }

//    public static function messageSendLocation($companyId, $address, $whatsapp, $phone = '79854331197'){
//        switch($companyId){
//            case '138368': $lat = '55.711606'; $lng = '37.664628'; break;
//            case '41332': $lat = '55.771426'; $lng = '37.5680063'; break;
//            default: $lat = '55.71160198'; $lng = '37.66466367'; break;
//        }
//
//        $data = [
//            'phone' => $phone,
////            'phone' => '380633282597',
//            'lat' => $lat,
//            'lng' => $lng,
//            'address' => "The Lashes\n$address"
//        ];
//        $result = $whatsapp->request('sendLocation', $data);
//    }

    public static function messageAutoNotificationOk($YclientsApi, $config, $chatId){
        $actual = YclientsCore::recordConfirmed($YclientsApi, $config, $chatId, 'LastAutoNotification');
        if (!$actual) {self::$sendContact = true; return self::messageDefault();}
        DB::table('bot')->where('userId', $chatId)->update(['current' => null, 'updateTime' => time()]);
        $message = '✅Хорошо, сохранили заявку в Листе ожидания.

В случае, если освободится нужное время, обязательно сообщим.

```Если все верно, то отвечать на это сообщение не нужно.```';
        return $message;
    }

    public static function messageAutoNotificationDeleteRecord($YclientsApi, $config, $chatId){
        DB::table('bot')->where('userId', $chatId)->update(['current' => null, 'updateTime' => time()]);
        $actual = YclientsCore::recordDelete($YclientsApi, $config, $chatId, 'LastAutoNotification');
        if (!$actual) {self::$sendContact = true; return self::messageDefault();}
        $message = '🚫 Запись отменили. Встретимся в другой раз! 👋

```Если все верно, то отвечать на это сообщение не нужно.```';
        return $message;
    }



    public static function messageAutoNotificationCallMeNow($YclientsApi, $config, $chatId, $companies){
//        YclientsCore::sendTelegram('messageAutoNotificationCallMeNow');
        DB::table('bot')->where('userId', $chatId)->update(['current' => null, 'updateTime' => time()]);
        $actual = YclientsCore::recordDelete($YclientsApi, $config, $chatId, 'LastAutoNotification');
        if (!$actual) {self::$sendContact = true; return self::messageDefault();}
        $user = DB::table('bot')->where('userId', $chatId)->first();
        $record = json_decode($user->LastAutoNotification,1);
        $datetime = $record['date'];
        $companyId = $record['company_id'];
        $studioName = YclientsCore::getStudioNameFromRecord($record, $companies);
        $services = YclientsCore::getServicesString($record);
        $clientName = YclientsCore::getClientNameFromRecord($record);
        $day = YclientsCore::getDateTimeStartVisitFromRecord($record, 'd.m');
        $time = YclientsCore::getDateTimeStartVisitFromRecord($record, 'H:i');
        $adminMessage = "
Необходимо связаться с заявкой из листа ожидания:

Студия: $studioName
Дата время записи: $datetime
Имя клиента: $clientName
Услуги: $services
Телефон: +".YclientsCore::getOnlyNumberString($chatId)."

Ссылка на диалог: https://api.whatsapp.com/send?phone=".YclientsCore::getOnlyNumberString($chatId)."&text=".rawurlencode("$clientName,

Получили Вашу заявку на $day в $time в студию: $studioName.

Именно это время занято ко всем мастерам. Из альтернативных вариантов есть окошки:")."
";
        $phoneAdmin = YclientsCore::getPhoneAdmin($companyId);
        $phoneAdmin = '74957647602';
        Core::sendDev($adminMessage);
        YclientsCore::sendAdmin($adminMessage, $phoneAdmin);
//        YclientsCore::sendAdmin($adminMessage, '79854331197');
//        YclientsCore::sendAdmin($adminMessage, '380633282597');
        $message = '🚫 Запись отменили. Спасибо за предупреждение! Администратор свяжется с Вами в ближайшее время для подбора других вариантов.

```Если все верно, то отвечать на это сообщение не нужно.```';
        return $message;
    }

    public static function messageAutoNotificationRepeat($chatId){
        $message = '🤖 Не понимаю, возможно неверно ввели номер?

🔢```Чтобы подтвердить или отменить запись пришлите цифру нужного пункта меню```👇
*1* - ✔️ Оставить заявку в Листе ожидания
*2* - 🚫 Нет, отмените эту запись. Запишусь к конкретному мастеру самостоятельно.
*3* - 📲 Свяжитесь со мной, перенесу запись на другое время/дату';
        return $message;
    }

    public static function messageAutoConfirmationConfirmed($YclientsApi, $config, $chatId){
        $actual = YclientsCore::recordConfirmed($YclientsApi, $config, $chatId);
        if (!$actual) {self::$sendContact = true; return self::messageDefault();}

        Bot::updateBotWhereUserId($chatId, ['current' => null, 'updateTime' => time(), 'offer' => 1]);
//        DB::table('bot')->where('userId', $chatId)->update(['current' => null, 'updateTime' => time()]);

        $user = DB::table('bot')->where('userId', $chatId)->first();
        $record = json_decode($user->LastAutoConfirmation,1);
        $getInstagramOfferMessage = TheLashesMessage::getInstagramOfferMessage($record);

        $message = "✅Отлично! Запись подтверждена. $getInstagramOfferMessage

В случае необходимости изменения времени или опоздания, свяжитесь с нами по номеру:
📱 +7 (495) 764-76-02

```Если все верно, то отвечать на это сообщение не нужно.```";

//        В случае необходимости изменения времени или опоздания, свяжитесь с нами по номеру:
//📱 +7 (495) 764-76-02
        return $message;
    }

    public static function messageAutoConfirmationRecordAgain($chatId, $YclientsApi, $config, $companies){
        DB::table('bot')->where('userId', $chatId)->update(['current' => null, 'updateTime' => time(), 'time3to3' => time()]);
        $actual = YclientsCore::recordDelete($YclientsApi, $config, $chatId);
        if (!$actual) {self::$sendContact = true; return self::messageDefault();}
        $user = DB::table('bot')->where('userId', $chatId)->first();
        $record = json_decode($user->LastAutoConfirmation,1);
        $datetime = $record['date'];
        $companyId = $record['company_id'];
        $studioName = YclientsCore::getStudioNameFromRecord($record, $companies);
        $services = YclientsCore::getServicesString($record);
        $clientName = YclientsCore::getClientNameFromRecord($record);
        $clientPhone = YclientsCore::getOnlyNumberString($chatId);
        $adminMessage = "
Отмена визита на завтра. Просит связаться!

Студия: $studioName
Дата время записи: $datetime
Имя клиента: $clientName
Услуги: $services
Телефон: +$clientPhone

Ссылка на диалог: https://api.whatsapp.com/send?phone=$clientPhone&text=".rawurlencode("$clientName, добрый день!

Запись отменили.

Какие альтернативы для записи на $services будут удобны? В ближайшие даты или конкретные числа? И есть ли предпочтения по мастерам?")."";
        $phoneAdmin = YclientsCore::getPhoneAdmin($companyId);
        $phoneAdmin = '74957647602';
        YclientsCore::sendAdmin($adminMessage, $phoneAdmin);
//        YclientsCore::sendAdmin($adminMessage, '79854331197');
//        YclientsCore::sendAdmin($adminMessage, '380633282597');
        $message = '🚫 Запись отменили. Спасибо за предупреждение! Администратор свяжется с Вами в ближайшее время для подбора других вариантов.

```Если все верно, то отвечать на это сообщение не нужно.```';
        return $message;
    }

    public static function messageAutoConfirmationRecordDelete($YclientsApi, $config, $chatId){
        DB::table('bot')->where('userId', $chatId)->update(['current' => null, 'updateTime' => time()]);
        $actual = YclientsCore::recordDelete($YclientsApi, $config, $chatId);
        if (!$actual) {self::$sendContact = true; return self::messageDefault();}
        $message = '🚫 Запись отменили. Встретимся в другой раз! 👋

```Если все верно, то отвечать на это сообщение не нужно.```';
        return $message;
    }

    public static function messageAutoConfirmationRepeat($chatId){
//        DB::table('bot')->where('userId', $chatId)->update(['current' => null]);
        $message = '🤖 Не понимаю, возможно неверно ввели номер?

🔢```Чтобы подтвердить или отменить запись пришлите цифру нужного пункта меню```👇
*1* - ✔️  Запись подтверждаю, завтра буду
*2* - 🚫 Нет, отмените мою запись
*3* - 📲 Свяжитесь со мной, перенесу запись на другое время/дату';
        return $message;
    }



    public static function messageAutoNPS16($chatId, $command){
        DB::table('bot')->where('userId', $chatId)->update(['current' => 'waitReview16', 'updateTime' => time()]);
        $user = DB::table('bot')->where('userId', $chatId)->first();
        $record = json_decode($user->LastAutoNps,1);
        $recordId = $record['id'];
        if (self::checkSendDefaultInsteadNps($recordId)){
            DB::table('bot')->where('userId', $chatId)->update(['current' => null, 'updateTime' => time()]);
            return self::messageDefault();
        }
        DB::table('nps')->where('recordId', $recordId)->update([
            'nps' => $command,
            'timeNps' => time(),
            'statusSendAdminNps' => 0
        ]);
        $message = '😢 Мне очень жаль! Что-то произошло?

Напишите, в ответном сообщении что именно вас возмутило.

📃 Я перешлю Ваше сообщение основателю сети The Lashes - Михаилу, и он попробует решить возникшую проблему максимально оперативно

🙏🏻 Напишите, пожалуйста, ниже ваши пожелания и отправьте их:';
        return $message;
    }

    public static function messageAutoNPS78($chatId, $command){
        DB::table('bot')->where('userId', $chatId)->update(['current' => 'waitReview78', 'updateTime' => time()]);
        $user = DB::table('bot')->where('userId', $chatId)->first();
        $record = json_decode($user->LastAutoNps,1);
        $recordId = $record['id'];
        if (self::checkSendDefaultInsteadNps($recordId)){
            DB::table('bot')->where('userId', $chatId)->update(['current' => null, 'updateTime' => time()]);
            return self::messageDefault();
        }
        DB::table('nps')->where('recordId', $recordId)->update([
            'nps' => $command,
            'timeNps' => time(),
            'statusSendAdminNps' => 0
        ]);
        $message = 'Сожалеем что не все прошло идеально😢

🙏🏻 Будем очень признательны, если сможете рассказать, что именно мы можем сделать, чтобы в следующий раз вы получили максимальное удовольствие от работы с нами.

✍🏻Напишите, пожалуйста, ответным сообщением ваши рекомендации/пожелания и отправьте его, а я перешлю его управляющему студии.';


//Сожалеем что не все прошло безупречно😢
//
//Мы заботимся о том, чтобы каждый наш клиент получил самое высшее качество обслуживания и предоставляемых услуг 🔥
//
//🙏🏻 Будем очень признательны, если сможете рассказать, что именно мы можем сделать, чтобы в следующий раз вы получили максимальное удовольствие от работы с нами.
//
//✍🏻 Напишите, пожалуйста, ответным сообщением ваши рекомендации.';
        return $message;
    }

    public static function checkSendDefaultInsteadNps($recordId){
        $n = DB::table('nps')->where('recordId', $recordId)->first();
        $timeSendNps = $n->timeSendNps;
//        Core::sendDev($timeSendNps.' - now - '. time());
        if (time() < $timeSendNps + 24 * 60 * 60 ){

            return false;
        } else {
//            Core::sendDev('time< send');
//            Core::sendDev('time меньше чем send');
            self::$hz = true;
//            Core::sendDev('hz = true');
            return true;
        }

    }

    public static function messageAutoNPS910($chatId, $command){
        DB::table('bot')->where('userId', $chatId)->update(['current' => null, 'updateTime' => time()]);
        $user = DB::table('bot')->where('userId', $chatId)->first();
        $record = json_decode($user->LastAutoNps,1);
        $recordId = $record['id'];

        if (self::checkSendDefaultInsteadNps($recordId)){
            return self::messageDefault();
        }


        DB::table('nps')->where('recordId', $recordId)->update(['nps' => $command]);
        $companyId = $record['company_id'];

        switch($companyId){
            case '138368':
//                $links = '📕Яндекс.Карты
//https://yandex.ru/maps/org/the_lashes/74578584133/?add-review
//
//🔎Google Maps
//https://g.page/thelashes_avtozavodskaya/review
//
//👤Facebook
//https://www.facebook.com/pg/make.the.lashes.avtozavodskaya/reviews/
//
//📗2GIS
//https://m.2gis.ru/moscow/firm/70000001032758113';

                $links = 'https://thelashes.ru/filials/velozavodskaya/review';
                break;
            case '41332':
//                $links = '📕Яндекс.Карты
//https://yandex.ru/maps/org/the_lashes/244650914985/?add-review
//
//🔎Google Maps
//https://g.page/thelashes_presnya/review
//
//👤Facebook
//https://www.facebook.com/pg/make.the.lashes.presnya/reviews/
//
//📗2GIS
//https://m.2gis.ru/moscow/firm/70000001020146068';

                $links = 'https://thelashes.ru/filials/presnenskiyval/review';

                break;
            case '225366':
//                $links = '📕Яндекс.Карты
//https://yandex.ru/maps/org/the_lashes/80243503452/?add-review
//
//🔎Google Maps
//https://g.page/thelashes_ramenki/review
//
//👤Facebook
//https://www.facebook.com/pg/TheLashes3/reviews/
//
//📗2 GIS
//https://m.2gis.ru/moscow/firm/70000001037683745';
                $links = 'https://thelashes.ru/filials/stoletova/review';
                break;
            default:
                $links = '';
                break;
        }
//        $adminPhone = YclientsCore::getPhoneAdmin($companyId);

        $message = "Напишите все что думаете о нас 😈:

🔻по ссылке🔻
$links

🎁Подарок за отзыв:
гель для роста бровей марки Henna Spa или для укладки by Vivienne Sabo

```Если хотите завершить диалог, то отвечать на это сообщение не нужно.```";

        return $message;

//Ого, спасибо! 🎁Подарок за отзыв - гель для роста бровей марки Henna Spa либо гель для укладки бровей Vivienne Sabo.
//Просто покажите свой отзыв на любой из площадок администратору студии:
//
//$links
//
//```Если хотите завершить диалог, то отвечать на это сообщение не нужно.```



//Спасибо за высокую оценку!
//
//Мы очень рады, что вам так понравилось. Будем делать все возможное, чтобы каждый раз вы получили только приятные впечатления! 🔥
//
//Будем благодарны за Вашу оценку визита на *любой удобной* площадке:
//
//$links
//
//Подарок за отзыв - гель для роста бровей марки Henna Spa! (просто покажите оставленный отзыв администратору студии)
//
//Вы очень поможете нам, если поделитесь и расскажите, что именно вам понравилось 🙏🏻
//


    }

    public static function messageAutoNPSRepeat($chatId){
//        DB::table('bot')->where('userId', $chatId)->update(['current' => null]);
        $user = DB::table('bot')->where('userId', $chatId)->first();
        $record = json_decode($user->LastAutoNps,1);
        $recordId = $record['id'];

        if (self::checkSendDefaultInsteadNps($recordId)){
            return self::messageDefault();
        }
        $message = 'Спасибо! А если именно шкале от 0 до 10, то как бы оценили? Введите, пожалуйста, цифру от 0 до 10.';
        return $message;
    }

    public static function messageModel(){
        $message = 'Добрый день!

Вы можете подписаться на наш канал в Телеграм, где мы ежедневно сообщаем о новых окошках для моделей:
https://thelashes.ru/dlya-modeley-raspisanie/

Что делать если у Вас нет приложения? Установить его можно по ссылке:

Apple (iOS): https://itunes.apple.com/app/telegram-messenger/id686449807

Android: https://play.google.com/store/apps/details?id=org.telegram.messenger
';
        return $message;
    }

    public static function messageDefault(){
        $message = '```Добрый день!🤖

Не думаю, что я понял сообщение правильно, поэтому уже переслал его администратору студии, который точно сможет решить любой вопрос.```
';
        self::$defaultBotMessage = 1;
        return $message;
    }

    public static function messageReminder($chatId, $companies){
        $user = DB::table('bot')->where('userId', $chatId)->first();
        $record = json_decode($user->LastAutoReminder,1);
        DB::table('bot')->where('userId', $chatId)->update(['current' => null, 'updateTime' => time()]);
        $message = TheLashesMessage::getMessageReminder($record, $companies);
        return $message;
    }

    public static function messageUpdateRecord($chatId, $companies){
        $user = DB::table('bot')->where('userId', $chatId)->first();
        $record = json_decode($user->LastUpdateRecord,1);
        DB::table('bot')->where('userId', $chatId)->update(['current' => null, 'updateTime' => time()]);
        $message = TheLashesMessage::getMessageRecordUpdate($record, null, $companies);
        return $message;
    }

    public static function messageAutoNPS78Review($chatId, $command){
        DB::table('bot')->where('userId', $chatId)->update(['current' => null, 'updateTime' => time()]);
        $user = DB::table('bot')->where('userId', $chatId)->first();
        $record = json_decode($user->LastAutoNps,1);
        $recordId = $record['id'];

        if (self::checkSendDefaultInsteadNps($recordId)){
            return self::messageDefault();
        }

        $companyId = $record['company_id'];
        $phoneAdmin = YclientsCore::getPhoneAdmin($companyId);
        $date = $record['date'];
        $npsDB = DB::table('nps')->where('recordId', $recordId)->first();
        $nps = $npsDB->nps;
        $npsId = $npsDB->id;
        DB::table('nps')->where('id', $npsId)->update(['statusSendAdminNps' => 1]);
        $clientName = YclientsCore::getClientNameFromRecord($record);
        $services = YclientsCore::getServicesString($record);
        $staffName = YclientsCore::getStaffNameFromRecord($record);
        $message = 'Спасибо! 👍🏻

Я переслал ваши пожелания управляющему студии.

📝 Мы постараемся сделать все возможное чтобы следующий визит прошел лучше!

Хорошего дня!

```Если хотите завершить диалог, то отвечать на это сообщение не нужно.```';
        $clientPhone = YclientsCore::getOnlyNumberString($chatId);
        $adminMessage = "
NPS: $nps
Имя клиента: $clientName
Телефон: +$clientPhone

Время: $date
Услуга: $services
Мастер: $staffName

Отзыв: $command
";
        YclientsCore::sendAdmin($adminMessage, $phoneAdmin);
//        YclientsCore::sendAdmin($adminMessage, '380633282597');
        return $message;
    }


    public static function messageAutoNPS16Review($chatId, $command){
        DB::table('bot')->where('userId', $chatId)->update(['current' => null, 'updateTime' => time()]);
        $user = DB::table('bot')->where('userId', $chatId)->first();
        $record = json_decode($user->LastAutoNps,1);
        $recordId = $record['id'];

        if (self::checkSendDefaultInsteadNps($recordId)){
            return self::messageDefault();
        }

        $companyId = $record['company_id'];
        $phoneAdmin = YclientsCore::getPhoneAdmin($companyId);
        $date = $record['date'];
        $npsDB = DB::table('nps')->where('recordId', $recordId)->first();
        $nps = $npsDB->nps;
        $npsId = $npsDB->id;
        DB::table('nps')->where('id', $npsId)->update(['statusSendAdminNps' => 1]);
        $clientName = YclientsCore::getClientNameFromRecord($record);
        $services = YclientsCore::getServicesString($record);
        $staffName = YclientsCore::getStaffNameFromRecord($record);
        $message = 'Спасибо! 👍🏻
Мы очень благодарны вам, за ваше мнение.

Приложим все усилия чтобы решить проблему максимально быстро.🙏

```Если хотите завершить диалог, то отвечать на это сообщение не нужно.```';
        $clientPhone = YclientsCore::getOnlyNumberString($chatId);
        $adminMessage = "
NPS: $nps
Имя клиента: $clientName
Телефон: +$clientPhone

Время: $date
Услуга: $services
Мастер: $staffName

Отзыв: $command
";
        YclientsCore::sendAdmin($adminMessage, $phoneAdmin);
//        YclientsCore::sendAdmin($adminMessage, '380633282597');
        YclientsCore::sendAdmin($adminMessage, '79265896504'); // Михаил The Lashes
        return $message;
    }

    public static function messageDeletedRecord($chatId, $companies){
        DB::table('bot')->where('userId', $chatId)->update(['current' => null, 'updateTime' => time()]);
        $user = DB::table('bot')->where('userId', $chatId)->first();
        $time3to3 = $user->time3to3;
        $record = json_decode($user->LastDeletedRecord,1);
        $companyId = $record['company_id'];
        $phoneAdmin = YclientsCore::getPhoneAdmin($companyId);
        $date = $record['date'];
        $studioName = YclientsCore::getStudioNameFromRecord($record, $companies);
        $clientName = YclientsCore::getClientNameFromRecord($record);
        $services = YclientsCore::getServicesString($record);
        $staffName = YclientsCore::getStaffNameFromRecord($record);
        $message = 'Администратор получил уведомление и свяжется с вами в ближайшее время.

```Если хотите завершить диалог, то отвечать на это сообщение не нужно.```';
        $clientPhone = YclientsCore::getOnlyNumberString($chatId);
        $adminMessage = "Необходимо связаться с клиентом, который удалил свою запись:
Студия: $studioName
Имя клиента: $clientName
Телефон: +$clientPhone

Время: $date
Услуга: $services
Мастер: $staffName

Ссылка на диалог: https://api.whatsapp.com/send?phone=$clientPhone&text=".rawurlencode("$clientName, добрый день!

Запись отменили.

Какие альтернативы для записи на $services будут удобны? В ближайшие даты или конкретные числа? И есть ли предпочтения по мастерам?")."";

        if (time() > $time3to3 + 1 * 60 * 60){
            YclientsCore::sendAdmin($adminMessage, '74957647602');
            Core::sendDev("отправляем алмину");
        } else {
            Core::sendDev("НЕ отправляем алмину");
        }

//        YclientsCore::sendAdmin($adminMessage, '380633282597');
        return $message;
    }

    public function checkWA(){
        checkWhatsApp::checkWhatsApp();
//        $time = time() - 2 * 3600;
//        $result = DB::select("SELECT * FROM bot WHERE waLastStatus IS NULL AND checkAction = '0' AND updateTime < $time");
//        print_r("<pre>");
//        print_r($result);
//        print_r("</pre>");
//        foreach($result as $r){
//            $id = $r->id;
//            $lastAction = $r->LastAction;
//            $record = null;
//            $adminHelp = '';
//            switch($lastAction){
//                case 'AutoNotification': $record = $r->LastAutoNotification; $adminHelp = 'Создание записи'; break;
//                case 'AutoNps': $record = $r->LastAutoNps; break;
//                case 'AutoConfirmation': $record = $r->LastAutoConfirmation; $adminHelp = 'Авто-подтверждение записи'; break;
//            }
//
//            if ($record !== null){
//                $record = json_decode($record,1);
//                $companyId = $record['company_id'];
//                $recordId = $record['id'];
//                $clientPhone = YclientsCore::getClientPhone($record);
//                $userToken = $this->config[$companyId];
//
//                // получить актуальные данные по recordId
//                $recordsNow = $this->YclientsApi->getRecord($companyId, $recordId, $userToken);
//
//                if($recordsNow['attendance'] != 1 && $recordsNow['attendance'] != 2 && $recordsNow["deleted"] != 1 ) {
//                    YclientsCore::putRecordAddLabelNotInWhatsApp($this->YclientsApi, $this->config, $recordsNow);
//                    $adminMessage = TheLashesMessage::getMessageAdminNotWhatsApp($record, $this->companies, $adminHelp);
//                    // вот здесь добавить текст.
//                    if ($clientPhone != '380633282597'){
//                        YclientsCore::sendAdmin($adminMessage, '74957647602');
//                    }
////                    Core::sendDev($adminMessage);
//                    if ($clientPhone != '380633282597'){
//                        DB::table('bot')->where('id', $id)->update(['checkAction'=>1]);
//                    }
//                }
//            }
//        }
    }

    public function null()
    {
        $whatsapp = new WhatsApp($this->waApiUrl, $this->token);
        $data = [
            'phone' => '000633282597',
            'body' => 'hi'
        ];
        $result = $whatsapp->request('sendMessage', $data);
        print_r("<pre>");
        print_r($result);
        print_r("</pre>");
    }

    public function visitReminder(){
        $whatsapp = new WhatsApp($this->waApiUrl, $this->token);
        $recordsDB = DB::table('records')->where('sendSmsReminder',0)->get();
        foreach ($recordsDB as $recordDB){
            $record = json_decode($recordDB->record, 1);
            $staffId = YclientsCore::getStaffId($record);
            if (
                !in_array($staffId, Config::$staffListWaitIds) &&
                time() > $recordDB->timeSmsReminder && time() < strtotime($record['datetime'])){
                print_r("<pre>");
                print_r("send");
                print_r("</pre>");

                print_r("<pre>");
                print_r($recordDB);
                print_r("</pre>");
                $clientPhone = YclientsCore::getClientPhone($record);
                $companyId = $companyId = $record['company_id'];
                DB::table('records')->where('id', $recordDB->id)->update(['sendSmsReminder' => 1]);
                $message = TheLashesMessage::getMessageReminder($record, $this->companies);
                $smsMessage = "Напоминаем о визите. Детали: https://thelashes.ru/?_=".theLashesModel::insertSmsId('5-'.$record['id']);
                $data = [
                    'phone' => $clientPhone,
                    'body' => $message
                ];
//                if ($clientPhone == '380633282597') {
                    if (!self::$smsActivated || YclientsCore::checkAccessUserSendMessageToWhatsApp($clientPhone)) {
                        $whatsapp->request('sendMessage', $data);
                        $whatsapp->request('readChat', ['phone' => $clientPhone]);
                    } else {
                        DB::table('bot')->where('phone', $clientPhone)->update([
                            'current' => null,
                            'checkAction' => 0,
                            'updateTime' => time(),
                            'LastAutoReminder' => json_encode($record, 1),
                            'EventForCheckWhatsApp' => 'AutoReminder',
                            'RecordIdForCheckWhatsApp' => $record['id'],
                            'CompanyIdForCheckWhatsApp' => $record['company_id']
                        ]);
                        $result = Smsc::sendSMS($this->smsConfig[$companyId]['login'], $this->smsConfig[$companyId]['password'], $clientPhone, $smsMessage, $this->AlfaName);
//                        YclientsCore::sendAdminTelegram("
//<b>The Lashes WA bot</b>
//Отправлено смс на номер $clientPhone
//Сообщение: $smsMessage
//Ответ сервера: $result");
                    }
//                }
            } else {
                print_r("<pre>");
                print_r('not send');
                print_r("</pre>");
            }
        }
    }

    public function webhookPaid(Request $request){
        echo "ok";
        Payment::webhookPayment($request);
    }

    public function alternativeYclientsWebhook(){
        AlternativeYclientsWebhook::cron();
    }

    public function cron(){

//        $this->webhook();
        $this->visitReminder();

        $time = time() - 3600;
        $npsDB = DB::table('nps')->where([['timeNps', '>', $time],['statusSendAdminNps', 0]])->get();
        foreach($npsDB as $nps){
            if(time() - $nps->timeNps > 1200){
                echo 'обольше. отправляем';
                $record = json_decode($nps->record,1);
                $clientPhone = $nps->phone;
                $companyId = $record['company_id'];
                $phoneAdmin = YclientsCore::getPhoneAdmin($companyId);
                $date = $record['date'];
                $npsRate = $nps->nps;
                $clientName = YclientsCore::getClientNameFromRecord($record);
                $services = YclientsCore::getServicesString($record);
                $staffName = YclientsCore::getStaffNameFromRecord($record);
                $adminMessage = "
NPS: $npsRate
Имя клиента: $clientName
Телефон: +$clientPhone

Время: $date
Услуга: $services
Мастер: $staffName

*Проигнорировал написание отзыва более чем 20 минут.*
";
                YclientsCore::sendAdmin($adminMessage, $phoneAdmin);
                DB::table('nps')->where('id', $nps->id)->update(['statusSendAdminNps' => 1]);
            } else {
//                echo 'меньше. не отправляем';
            }
        }
    }

}
