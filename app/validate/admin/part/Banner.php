<?php

namespace app\validate\admin\part;

use think\Validate;
use app\validate\BaseValidate;

class Banner extends BaseValidate
{
    protected $rule = [
        'image'         => 'require',
        'status'                => 'require|in:0,1',
    ];

    //定义内置方法检验失败后返回的字符
    protected $message = [
        'image.require'      => '请上传背景图',
        'status.require'        => '请选择状态',
        'status.in'        => '请选择状态',
    ];
    protected $scene = [
        'add' => ['image','status'],
        'edit' => ['image','status'],
    ];
}