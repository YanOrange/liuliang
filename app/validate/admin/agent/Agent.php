<?php

namespace app\validate\admin\agent;

use think\Validate;
use app\validate\BaseValidate;

class Agent extends BaseValidate
{
    protected $rule = [
        'name'   => 'require',
    ];

    //定义内置方法检验失败后返回的字符
    protected $message = [
        'name.require'   => '请填写代理商名称',
    ];
    protected $scene = [
        'add'   => ['name'],
        'edit'  => ['name'],
    ];
}