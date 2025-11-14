<?php

namespace app\validate\admin\customerpaiban;

use think\Validate;
use app\validate\BaseValidate;

class CustomerPaiban extends BaseValidate
{
    //数组顺序就是检测的顺序
    protected $rule = [
        'customer_id'           => 'require',
        'demand'      => 'require|gt:0',
        'plan_time'       => 'require',
    ];

    //定义内置方法检验失败后返回的字符
    protected $message = [
        'customer_id.require'          => '销售不能为空',
        'demand.require'     => '总需求量不能为空',
        'demand.gt'     => '总需求量不能为空',
        'plan_time.require'     => '接量时间不能为空',
    ];

    protected $scene = [
        'add' => ['customer_id','plan_time'],
        'edit' => ['customer_id','plan_time'],
        'addDemand' => ['demand','plan_time'],
        'editDemand' => ['demand','plan_time'],
    ];
}