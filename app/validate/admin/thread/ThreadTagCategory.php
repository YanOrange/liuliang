<?php

namespace app\validate\admin\thread;

use think\Validate;
use app\validate\BaseValidate;
use Yurun\PaySDK\Base;

class ThreadTagCategory extends BaseValidate {
    protected $rule = [
        'title' => 'require|length:1,20',
    ];

    //定义内置方法检验失败后返回的字符
    protected $message = [
        'title.require' => '请输入标签类别名称',
        'title.length' => '标签类别名称长度1-20字',
    ];
    protected $scene = [
        'add' => ['title'],
        'edit' => ['title'],
    ];
}