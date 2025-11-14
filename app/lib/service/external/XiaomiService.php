<?php

namespace app\lib\service\external;

use app\lib\service\common\CurlService;

class XiaomiService {

//    private $callbackUrl = 'https://trail.e.mi.com/global/log?';
    private $callbackUrl = 'https://trail.e.mi.com/global/test?';


    /**
     * 数据回传
     */
    public static function callback(, $token, $unionId, $state, $eventType)
    {
        $callbackUrl= 'https://clue.oceanengine.com/outer/wechat/applet/event/track/1816399451811995';
        $token      = 'D8E95A98A2E47EC9B5BFC1F19A5450E8';
        $eventType  = 386;
        $unionId    = '';
        $state      = '';

        $time       = time();
        $nonce      = mt_rand(10000, 99999);
        $signature  = sha1($token . $time . $nonce);
        $url        = "{$callbackUrl}?signature={$signature}&timestamp={$time}&nonce={$nonce}";

        $responseData = CurlService::postJson($url, ['union_id' => $unionId, 'event_type' => $eventType, 'state' => $state]);
        $responseData = json_decode($responseData, true);

        return (isset($responseData['status']) && $responseData['status'] != 200);
    }
}