<?php

namespace app\validate\admin\distribution;

use think\Validate;
use app\model\admin\App as AppModel;
use app\validate\BaseValidate;

class DistributionCommissionRule extends BaseValidate
{
    //数组顺序就是检测的顺序
    protected $rule = [
        'settlement_period'     => 'require|gt:0',
        'commission_rate'       => 'require|gt:0',
    ];

    //定义内置方法检验失败后返回的字符
    protected $message = [
        'settlement_period.require'        => '结算周期不能为空',
        'settlement_period.gt'             => '结算周期必须大于0',
        'commission_rate.require'          => '佣金占比不能为空',
        'commission_rate.gt'               => '佣金占比必须大于0',
    ];

    protected $scene = [
        'add' => ['settlement_period','commission_rate'],
        'edit' => ['settlement_period','commission_rate'],
    ];
}