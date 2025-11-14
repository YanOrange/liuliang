<?php

namespace app\validate\api\advertising;

use app\validate\BaseValidate;

class Synchronization extends BaseValidate
{
    protected $rule = [
        'thread_id'      => 'require',
        'phone'      => 'require',
    ];

    protected $message = [
        'thread_id.require' => '线索参数错误',
        'phone.require' => '手机号码参数错误',
    ];

    /**
     * 验证场景
     */
    protected $scene = [
        'callback' => ['thread_id','phone'],
    ];
}