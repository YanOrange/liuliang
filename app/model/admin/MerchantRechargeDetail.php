<?php
/**
 * 后台商户充值明细表模型
 */

namespace app\model\admin;

use laytp\BaseModel;
use think\model\concern\SoftDelete;

class MerchantRechargeDetail extends BaseModel
{
    use SoftDelete;

    //模型名
    protected $name = 'merchant_recharge_detail';

    public function merchant()
    {
        return $this->belongsTo('app\model\admin\Merchant','merchant_id','id')->removeOption('soft_delete');
    }
    public function operator()
    {
        return $this->belongsTo('app\model\admin\User','operator_id','id')->removeOption('soft_delete');
    }

    // 充值类型
    public function getRechargeTypeAttr($value, $data)
    {
        $arr = [
            0 => '-',
            1 => '正常打款',
            2 => '免测',
            3 => '赠送',
            4 => '补量',
            5 => '技术充值',
            6 => '系统预充值',
            7 => '补量充值',
        ];

        return $arr[$data['recharge_type']];
    }

}
