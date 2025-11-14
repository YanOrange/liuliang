<?php

namespace app\validate\admin\merchantrechargedetail;

use think\Validate;
use app\model\admin\Channel as ChannelModel;
use app\validate\BaseValidate;

class MerchantRechargeDetail extends BaseValidate
{
    //数组顺序就是检测的顺序
    protected $rule = [
        'recharge_amount'          => 'require',
        'merchant_id'         => 'require',
    ];

    //定义内置方法检验失败后返回的字符
    protected $message = [
        'recharge_amount.require'             => '请输入充值金额',
        'merchant_id.require'        => '请选择充值的商户',
    ];

    protected $scene = [
        'recharge' => ['recharge_amount','merchant_id'],
    ];
}