<?php

namespace app\validate\admin\customer;

use think\Validate;
use app\model\admin\Customer as CustomerModel;
use app\validate\BaseValidate;

class JzdCustomer extends BaseValidate
{
    //数组顺序就是检测的顺序
    protected $rule = [
        'month'              => 'require',
        'day'                => 'require',
        'assign_mode'        => 'require',
        'merchant_organization_id'        => 'require',
        'daily_intake_limit_nums'        => 'require',
    ];

    //定义内置方法检验失败后返回的字符
    protected $message = [
        'month.require'     => '月份不能为空',
        'day.require'      => '天数不能为空',
        'assign_mode.require'      => '分配方式不能为空',
        'merchant_organization_id.require'      => '销售组不能为空',
        'daily_intake_limit_nums.require'      => '销售组线索数不能为空',
    ];

    protected $scene = [
        'setRestDay' => ['month','day'],
        'setNewCustomerNums' => ['assign_mode'],
        'setMerchantOrganizationNums' => ['merchant_organization_id','daily_intake_limit_nums'],
        'addCustomerMode' => ['assign_mode'],
    ];
}