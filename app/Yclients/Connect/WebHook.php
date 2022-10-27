<?php
namespace App\Yclients\Connect;

use App\Core\Config;
use App\Core\Core;
use App\Yclients\Connect\WebHook\CreateRecord;
use App\Yclients\Connect\WebHook\DeleteRecord;
use App\Yclients\Connect\WebHook\UpdateRecord;

class WebHook
{

    public static function WebHook()
    {
        $webHook = file_get_contents('php://input');
        $data = json_decode($webHook, 1);

        Core::sendMichael($webHook);
//        Core::sendDev($webHook);

        if (!empty($data['resource']) && $data['resource'] == 'record' && $data['status'] == 'create') {
            CreateRecord::CreateRecord($data['data'], Config::WebHookArchitecture);
        }

        if (!empty($data['resource']) && $data['resource'] == 'record' && $data['status'] == 'update') {
            UpdateRecord::UpdateRecord($data['data'], Config::WebHookArchitecture);
        }

        if (!empty($data['resource']) && $data['resource'] === 'record' && $data['status'] === 'delete') {
            DeleteRecord::DeleteRecord($data['data'], Config::WebHookArchitecture);
        }
    }

}
