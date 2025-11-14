<?php
/**
 * BI商户补量手机号记录登记
 * @date 2022-11-08
 * @author chenlele
 */

namespace app\model\admin;

use laytp\BaseModel;
use think\model\concern\SoftDelete;

class BiBoostPhone extends BaseModel
{
    use SoftDelete;

    //模型名
    protected $name = 'bi_merchant_boost_phone';

    protected $append = [
        'phone_str'
    ];

    public function getTypeAttr($value, $data)
    {
        $arr = [1 => '未成年人补量', 2 => '秒删补量', 3 => '加微信补量',4 => '特殊补量'];
        return isset($arr[$value]) ? $arr[$value] : '-';
    }

//    public function getStatusAttr($value, $data)
//    {
//        $arr = [1 => '是', 2 => '否'];
//        return isset($arr[$value]) ? $arr[$value] : '-';
//    }

    public function getPhoneStrAttr($value, $data)
    {
        $phoneStr = $data['phone'] ? substr_replace($data['phone'],'****',3,4) : '';
        return $phoneStr;
    }

}