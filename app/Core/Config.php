<?php


namespace App\Core;


use Yclients\YclientsApi;

class Config
{
//https://botthelashes.ru/login
//admin@botthelashes.ru
//trendo1ber121

    public static $ContactCenterPhone = '74957647602';




    public static $waApiUrl = 'https://eu20.chat-api.com/instance41762/';
    public static $waToken = '?token=z10mgarq8wqglurk';
    public static $instagramLabels = [1414854, 1417201, 1594351];
    public static $guaranteeTags = [1421650, 1421649, 1542354];
    public static $mustPrepaymentTags = [2016616, 2016624, 2016632];
    public static $mustPrepaymentTagsForClient = [2002029, 2002028, 2002027];


    public static $staffListWaitIds = [395306, 292305, 654281];
    public static $staffListPrepaymentsIds = [804718, 805151, 805230];
    public static $staffListAdminIds = [309615];

    public static $lashesArrayCategries = [2738088, 1963837, 1963838, 1963839, 1963835, 1963834, 1963836];
    public static $lashesArrayServices = [];
    public static $BotoxArrayCategries = [];
    public static $BotoxArrayServices = [1963913, 1963915, 1963914, 3914342];
    public static $BotoxTime = 35 * 24 * 60 * 60;

    public static function getYclientsApi(){
        $YclientsApi = new YclientsApi(env("YCLIENTS_PARTNER_TOKEN"));
        return $YclientsApi;
    }

    public static function getConfig(){
        $YclientsApi = self::getYclientsApi();
        $userToken_138368 = YclientsCore::getAuthYclientsUserToken($YclientsApi, 138368, "mail1@thelashes.ru","trendo1ber121");
        $userToken_41332 = YclientsCore::getAuthYclientsUserToken($YclientsApi, 41332, "mail1@thelashes.ru","trendo1ber121");
        $userToken_225366 = YclientsCore::getAuthYclientsUserToken($YclientsApi, 225366, "mail1@thelashes.ru","trendo1ber121");
        $config = [
            138368 => $userToken_138368,
            41332 => $userToken_41332,
            225366 => $userToken_225366
        ];
        return $config;
    }
    public static $smsActivated = true; // ДОЛЖЕН БЫТЬ ТОЛЬКО TRUE ИНАЧЕ МОЖЕТ ОТПРАВИТСЯ СООБЩЕНИЕ В ВОТСАП БЕЗ ПОДПИСКИ НА БОТА
    public static $AlfaName = 'The Lashes';
    public static $smsConfig = [
        138368 => [
            'login' => 'thelashesavtozavod',
            'password' => 'only1lashes'
        ],
        41332 => [
            'login' => 'thelashes',
            'password' => '245onlylashes'
        ],
        225366 => [
            'login' => 'thelashesramenki',
            'password' => 'only2lashes'
        ]
    ];


    const WebHookArchitecture = 'WebHook Architecture';
    const RequestArchitecture = 'Request Architecture';
    const MODEL = 'модел';



}
