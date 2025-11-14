<?php

namespace app\lib\api\other;

//生成随机跑马灯数据
class RandHorseData
{
    //随机生成手机号
    public static function randomPhone($nums = 10)
    {
        $tel_arr = [
            '130','131','132','133','134','135','136','137','138','139','144','147','150','151','152','153','155','156','157','158','159','176','177','178','180','181','182','183','184','185','186','187','188','189',
        ];
        for ($i = 0; $i < $nums; $i++) {
            $tmp[] = substr_replace($tel_arr[array_rand($tel_arr)].mt_rand(1000,9999).mt_rand(1000,9999), '****', 3, 4);
        }
        return array_unique($tmp);
    }
    public static function getRandHorsePhoneData()
    {
        $phoneData = self::randomPhone();
        $tempData = [];
        foreach ($phoneData as $val) {
            $tempData[] = ['userData' => '用户 '. $val . ' 已提交申请', 'lastTime' =>  rand(5,30) . '分钟前'];
        }
        return $tempData;
    }
}