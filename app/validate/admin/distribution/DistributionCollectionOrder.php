<?php

namespace app\validate\admin\distribution;

use think\Validate;
use app\model\admin\App as AppModel;
use app\validate\BaseValidate;

class DistributionCollectionOrder extends BaseValidate
{
    //数组顺序就是检测的顺序
    protected $rule = [
        'pay_time'         => 'require',
        'pay_amount'       => 'require',
    ];

    //定义内置方法检验失败后返回的字符
    protected $message = [
        'pay_time.require'         => '支付时间不能为空',
        'pay_amount.require'       => '支付金额不能为空',
    ];

    protected $scene = [
        'edit' => ['pay_time','pay_amount'],
    ];
}