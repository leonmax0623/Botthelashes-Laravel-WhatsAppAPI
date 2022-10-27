<?php
namespace App\Core\Events;

use App\Core\Test;
use App\Yclients\Record\Record;

class YclientsWebhook
{

    public static function webhookYclients()
    {
        $webhook = file_get_contents('php://input');
        $data = json_decode($webhook,1);

        if (EventAssistant::getConnectYclients() == 'webhook'){
            // вебхук о событии создании записи
            if(!empty($data['resource']) && $data['resource'] == 'record' && $data['status'] == 'create'){

                if (in_array(Record::getClientPhone($data['data']), Test::$betaClients)){
                    die();
                }

                YclientsWebhookCreateRecordEvent::CreateRecordEvent($data['data'], 'WebHook Architecture');
            }

            // вебхук о событии создании записи
            if(!empty($data['resource']) && $data['resource'] == 'record' && $data['status'] == 'update'){

                if (in_array(Record::getClientPhone($data['data']), Test::$betaClients)){
                    die();
                }

                YclientsWebhookUpdateRecordEvent::UpdateRecordEvent($data['data'], 'WebHook Architecture');
            }

            // вебхук о событии создании записи
            if(!empty($data['resource']) && $data['resource'] === 'record' && $data['status'] === 'delete'){

                if (in_array(Record::getClientPhone($data['data']), Test::$betaClients)){
                    die();
                }

                YclientsWebhookDeleteRecordEvent::DeleteRecordEvent($data['data'], 'WebHook Architecture');
            }
        }
    }

}
