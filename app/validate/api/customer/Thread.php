<?php

namespace app\validate\api\customer;

use app\validate\BaseValidate;

class Thread extends BaseValidate
{
    //数组顺序就是检测的顺序，比如这里，会先检测code验证码的正确性
    protected $rule = [
        'thread_id' => 'require'
    ];

    //定义内置方法检验失败后返回的字符
    protected $message = [
        'thread_id.require' => '参数错误'
    ];

    protected $scene = [
        'detail' => ['thread_id'],
    ];
}