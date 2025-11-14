<?php

namespace app\validate\admin\website;
use app\validate\BaseValidate;

class WebsiteStyleShow extends BaseValidate
{
    protected $rule = [
        'title' => 'require|length:2,200',
        'weight' => 'require',
    ];

    //定义内置方法检验失败后返回的字符
    protected $message = [
        'title.require' => '请输入标题',
        'title.length' => '标题长度2-200个字符',
        'weight.require' => '请输入排序',
    ];
    protected $scene = [
        'add' => ['title', 'weight'],
        'edit' => ['title', 'weight'],
    ];
}