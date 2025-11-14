<?php


namespace app\model\admin;

use laytp\BaseModel;

class MobileGetCityinfo extends BaseModel
{

    //模型名
    protected $name = 'mobile_get_cityinfo';


    //根据手机号获取省市
    public static function getMobileCityInfo($mobile = null)
    {
        $province = '';
        $city = '';
        if (!empty($mobile) && strlen($mobile) == 11) {
            $mobile = mb_substr($mobile, 0, 7);
            $cityInfo  = self::field('provice,city_county')->where('paragraph', $mobile)->find();
            if (!empty($cityInfo)) {
                $province = $cityInfo->provice;
                $city = $cityInfo->city_county;
            }
        }
        return compact('province', 'city');
    }
}
