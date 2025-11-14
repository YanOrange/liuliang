<?php

namespace app\lib\api\other;
use app\lib\api\city\IpCity;
use app\model\api\UserList;
use think\facade\Db;

class UserCity
{
    public static function checkCity($channel = null, $ip = null)
    {
        //$ip = request()->ip();
        $cityInfo = getIpArea($ip);
        if (isset($cityInfo['data']['city']) && !empty($cityInfo['data']['city'])) {
            $city = $cityInfo['data']['city'];
            if (isset($GLOBALS['uid'])) {
                $phone = UserList::where('id', $GLOBALS['uid'])->order('id desc')->value('phone');
               /* if ($phone == 11865249998) {
                    $city = '重庆';
                }*/
               /* if ($phone =='11548484894') {
                    return true;
                }*/
                /*if ($phone == '11523230001') {
                    $city = '重庆市';
                }
                if ($phone == '11568758998') {
                    $city = '成都市';
                }
                if ($phone == '11568758999') {
                    $city = '贵阳';
                }*/
                if (strstr($channel, 'oppo') !== false) {
                    $count = Db::query("select count(*) as nums from lt_user_list INNER JOIN lt_thread on  lt_thread.uid=lt_user_list.id where lt_user_list.phone='$phone' and lt_thread.city like '%成都%'");
                    if (isset($count[0]['nums']) && $count[0]['nums'] > 0) {
                        return true;
                    }
                }
            }
            return (strstr($channel, 'oppo') !== false && strstr($city, '成都') !== false) || (strstr($channel, 'oppo') !== false && strstr($city, '贵阳') !== false) || (strstr($channel, 'vivo') !== false && strstr($city, '重庆') !== false) || ($channel == 'yqfzcl_rongyao' && strstr($city, '南京') !== false) ? true : false;
        }
        return false;
    }
}