<?php

namespace app\validate\admin\customer;

use think\Validate;
use app\model\admin\Customer as CustomerModel;
use app\validate\BaseValidate;

class OverdueAppCustomer extends BaseValidate
{
    //数组顺序就是检测的顺序
    protected $rule = [
        'channel_id'         => 'require',
        'customer_ids'         => 'require',
    ];

    //定义内置方法检验失败后返回的字符
    protected $message = [
        'channel_id.require'     => '渠道参数错误',
        'customer_ids.require'      => '请选择销售',
    ];

    protected $scene = [
        'add' => ['channel_ids','customer_ids'],
        'edit' => ['channel_ids','customer_ids'],
    ];
}