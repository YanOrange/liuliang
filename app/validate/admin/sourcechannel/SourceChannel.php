<?php

namespace app\validate\admin\sourcechannel;

use think\Validate;
use app\validate\BaseValidate;

class SourceChannel extends BaseValidate
{
    protected $rule = [
        'channel_name'         => 'require'
    ];

    //定义内置方法检验失败后返回的字符
    protected $message = [
        'channel_name.require'      => '请填写渠道名称'
    ];
    protected $scene = [
        'add' => ['channel_name'],
        'edit' => ['channel_name'],
    ];
}