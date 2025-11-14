<?php

namespace app\validate\admin\thread;

use think\Validate;
use app\validate\BaseValidate;
use Yurun\PaySDK\Base;

class ThreadTransformation extends BaseValidate {
    protected $rule = [
        'uid'                  => 'require',
        'thread_id'            => 'require',
        'merchant_id'          => 'require',
        'customer_id'          => 'require',
        'channel_id'           => 'require',
        'nickname'             => 'require',
        'phone'                => 'require',
        'age_range_id'         => 'require',
        'identify_id'          => 'require',
    ];

    //定义内置方法检验失败后返回的字符
    protected $message = [
        'uid.require' => '分配参数错误',
        'thread_id.require' => '分配参数错误',
        'merchant_id.require' => '分配参数错误',
        'customer_id.require' => '分配参数错误',
        'channel_id.require' => '请选择渠道',
        'nickname.require' => '请填写昵称',
        'phone.require' => '请填写手机号',
        'age_range_id.require' => '请选择年龄',
        'identify_id.require' => '请选择身份',

    ];
    protected $scene = [
        'assign' => ['thread_id','merchant_id','customer_id'],
        'assignMerchant' => ['uid','merchant_id','customer_id'],
        'getMessage' => ['thread_id'],
        'sendMessage' => ['thread_id'],
        'addUser' => ['channel_id','phone'],
    ];
}