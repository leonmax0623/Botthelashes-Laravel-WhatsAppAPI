<?php
namespace App\Yclients\Modules\History;

use App\Core\Config;
use App\Core\Core;
use App\Core\YclientsCore;
use App\Models\ModelException;
use App\Yclients\Notification\Notification;
use Illuminate\Support\Facades\DB;

class History
{

    const TABLE = 'history';

    public static $lastDateMessage;
    public static $lastBotMessage;

    public static function cronSendUnrecognizedMessageToAdmin(){
        $result = self::getUserListWhoHaveNewMessagesAndHistoryMessages();
        // получаем список юзеров, у которых были новые сообщения
        $userListWhoHaveNewMessages = $result['userListWhoHaveNewMessages'];
        // получаем историю сообщения для каждого юзера. История включает только последние сообщения, без старых.
        $historyMessages = $result['historyMessages'];
        // идем по списку всех юзеров
        foreach($userListWhoHaveNewMessages as $userPhone => $sender){
            // Если БОТ написал последним, тогда пропускаем мимо.
            // Если последним написал именно КЛИЕНТ, то разбираемся с нераспознанными сообщениями
            if ($sender == 'user'){
                // из всей истории сообщений выбираем именно последние нераспознанные и то, что ему предшествовало
                self::$lastBotMessage = '';
                $userHistoryUnrecognized = self::getUserHistoryUnrecognized(array_reverse($historyMessages[$userPhone], true));
                self::$lastDateMessage = date("Y-m-d H:i:s");
                $adminMessage = self::getAdminMessage($userPhone, $userHistoryUnrecognized);
                // если последнее сообщение от юзера было более, чем 5 минуты назад
                if (time() - strtotime(self::$lastDateMessage) > 300) {
                    Notification::sendAdminTelegram($adminMessage);
                    YclientsCore::sendAdmin($adminMessage, Config::$ContactCenterPhone);
                    $int = self::checkAdmin(strval($userPhone));
                }
            }
        }
    }

    public static function getAdminMessage($userPhone, $userHistoryUnrecognized){
        $historyMessage = '';
        $userName = "";

        $userHistoryUnrecognized = array_reverse($userHistoryUnrecognized, true);
        foreach($userHistoryUnrecognized as $item){
            if ($item->userName !== null){$userName = $item->userName;} // узнаем имя клиента в WhatsApp
            if ($item->sender === 'user' ||
                ($item->sender === 'bot' && $item->defaultBotMessage == 0)
            ){
                $userHistoryUnrecognized[] = $item;
                $date = $item->date;
                self::$lastDateMessage = $date;
                $message = $item->message;
                $historyMessage .= "💬 $date $message
";
            }
        }

        $adminMessage = "Номер телефона: +$userPhone 
Имя в WhatsApp: $userName
Ссылка на диалог: https://api.whatsapp.com/send?phone=".$userPhone."&text=".urlencode("Добрый день, $userName!")."

$historyMessage
📌 ".self::$lastBotMessage."";
        return $adminMessage;
    }

    public static function getUserListWhoHaveNewMessagesAndHistoryMessages(){
        $userListWhoHaveNewMessages = [];
        $historyMessages = [];
        $AllUserHistoryDB = self::getAllUserHistory();
        foreach($AllUserHistoryDB as $row){
            if (!in_array($row->userPhone, $userListWhoHaveNewMessages) && $row->defaultBotMessage == 0){
                // исключая Нераспознанные находит, кто последний писал БОТ или ЮЗЕР.
                $userListWhoHaveNewMessages[$row->userPhone] = $row->sender;
            }
            $historyMessages[$row->userPhone][] = $row;
        }
        return [
            'userListWhoHaveNewMessages' => $userListWhoHaveNewMessages,
            'historyMessages' => $historyMessages
        ];
    }

    public static function getUserHistoryUnrecognized($userHistory){
        $userHistoryUnrecognized = [];
        foreach($userHistory as $item){
            if ($item->sender === 'user'
//                || ($item->sender === 'bot' && $item->defaultBotMessage == 0)
            ){
                $userHistoryUnrecognized[] = $item;
            }
            if ($item->sender === 'bot' && $item->defaultBotMessage == 0){
                self::$lastBotMessage = $item->message;
                break;
            } // до последнего сообщения бота включительно
        }
        return $userHistoryUnrecognized;
    }

    public static function checkUserLastMessageUnrecognized($userPhone){
        $userPhone = strval($userPhone);
        $userHistoryDB = self::getUserHistory($userPhone);
        $userHistory = [];
        foreach($userHistoryDB as $row){
            $userHistory[] = $row;
        }
        $userHistory = array_reverse($userHistory);
        foreach ($userHistory as $row){
            if ($row->sender == 'bot'){
                if ($row->defaultBotMessage){
                    return true;
                } else {
                    return false;
                }
            }
        }
        return false;
//        return
//            true - последнее было НЕРАСПОЗНАННОЕ, и значит нужно не отправлять его повторно
//            false - последнее было РАСПОЗНАННОЕ, разрешаем отправку нераспознанного
    }

    public static function checkAdmin($userPhone){
        $int = 0;
        try {
            $int = DB::table(self::TABLE)->where('userPhone', $userPhone)->update(['checkAdmin' => 1]);
        } catch (\Exception $e) {
            ModelException::Exception($e, __CLASS__, __FUNCTION__);
        }
        return $int;
    }

    public static function getAllUserHistory(){
        $result = null;
        try {
            $result = DB::table(self::TABLE)
                ->where('checkAdmin', 0)
                ->get();
        } catch (\Exception $e) {
            ModelException::Exception($e, __CLASS__, __FUNCTION__);
        }
        return $result;
    }

    public static function getLastUserMessage($userPhone){
        $result = null;
        try {
            $result = DB::table(self::TABLE)->where('userPhone', $userPhone)
                ->orderBy('id', 'desc')
                ->first();
        } catch (\Exception $e) {
            ModelException::Exception($e, __CLASS__, __FUNCTION__);
        }
        return $result;
    }

    public static function getUserHistory($userId){
        $result = null;
        try {
            $result = DB::table(self::TABLE)->where('userId', $userId)->get();
        } catch (\Exception $e) {
            ModelException::Exception($e, __CLASS__, __FUNCTION__);
        }
        return $result;
    }

    public static function addSendBotMessageToHistory($userPhone, $message, $defaultBotMessage){
        self::insertHistory($userPhone, null, $message, $defaultBotMessage, 'bot', 'WhatsApp');
    }

    public static function addSendUserMessageToHistory($userPhone, $userName, $message){
        self::insertHistory($userPhone, $userName, $message, 0, 'user', 'WhatsApp');
    }

    public static function addSendSmsToHistory($userPhone, $message){
        self::insertHistory($userPhone, null, $message, 0, 'bot', 'SMS');
    }

    protected static function insertHistory($userPhone, $userName, $message, $defaultBotMessage, $sender, $vendor){
        $result = false;
        try {
            $insert = [];
            $insert['sender'] = $sender;
            $insert['vendor'] = $vendor;
            $insert['userId'] = $userPhone;
            $insert['userPhone'] = $userPhone;
            $insert['userName'] = $userName;
            $insert['date'] = date("Y-m-d H:i:s");
            $insert['message'] = $message;
            $insert['defaultBotMessage'] = $defaultBotMessage;
            $result = DB::table(self::TABLE)->insert($insert);
        } catch (\Exception $e) {
            ModelException::Exception($e, __CLASS__, __FUNCTION__);
        }
        return $result;
    }

}