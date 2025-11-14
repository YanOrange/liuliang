<?php

namespace app\validate\admin\thread;

use think\Validate;
use app\validate\BaseValidate;
use Yurun\PaySDK\Base;

class Thread extends BaseValidate
{
    protected $rule = [
        'origin_merchant_id'    => 'require',
        'num'                   => 'require|gt:0',
        'target_merchant_id'    => 'require',
        'customer_id'           => 'require',
        'thread_price'          => 'require|gt:0',
        'origin_create_time'    => 'require',
        'target_create_time'    => 'require',
        'thread_ids'            => 'require'
    ];

    //定义内置方法检验失败后返回的字符
    protected $message = [
        'origin_merchant_id.require'      => '请选择原商户',
        'num.require'                     => '请填写分配数量',
        'num.gt'                          => '分配数量必须大于0',
        'target_customer_id.require'      => '请选择目标商户',
        'customer_id.require'             => '请选择客服',
        'thread_price.require'            => '请填写线索价格',
        'thread_price.gt'                 => '线索价格必须大于0',
        'origin_create_time.require'      => '请填写原商户线索时间',
        'target_create_time.require'      => '请填写目标商户线索时间',
        'thread_ids.require'              => '请填写线索ID值'
    ];
    protected $scene = [
        'assignThread' => ['origin_merchant_id','num','target_merchant_id','customer_id','thread_price','origin_create_time','target_create_time'],
        'assignThreadId' => ['num','target_merchant_id','target_create_time'],
    ];
}